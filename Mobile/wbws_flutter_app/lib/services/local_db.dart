import 'dart:convert';
import 'dart:math';
import 'package:sqflite/sqflite.dart';

import 'attendance_delta.dart';
import 'attendance_original_year.dart';
import 'attendance_sync_receipt.dart';
import 'interrupted_database_migration.dart';
import 'package:path/path.dart';

import 'legacy_outbox_models.dart';
import 'local_schema_v34.dart';
import 'local_schema_v43.dart';
import 'local_schema_v44.dart';
import 'sync_attempt_models.dart';
import 'sync_execution.dart';
import 'session_models.dart';
import 'sync_recovery_models.dart';

String newClientOpId() {
  final r = Random.secure();
  final b = List<int>.generate(16, (_) => r.nextInt(256));
  b[6] = (b[6] & 0x0f) | 0x40;
  b[8] = (b[8] & 0x3f) | 0x80;
  String h(int i) => b[i].toRadixString(16).padLeft(2, '0');
  return '${h(0)}${h(1)}${h(2)}${h(3)}-${h(4)}${h(5)}-${h(6)}${h(7)}-${h(8)}${h(9)}-${h(10)}${h(11)}${h(12)}${h(13)}${h(14)}${h(15)}';
}

/// Local SQLite database for offline-first features.
/// Caches classes, students, subjects, assessments, dashboard stats, members.
/// Stores attendance + grades locally, syncs when connected.
class LocalDb {
  static final LocalDb _instance = LocalDb._internal();
  factory LocalDb() => _instance;
  LocalDb._internal();

  Database? _db;
  Future<void> _legacySaveTail = Future<void>.value();

  /// Canonical current-schema registry for authenticated read caches.
  ///
  /// Startup inventory and authorization-scope cleanup must traverse the same
  /// physical tables. Keeping one registry prevents a migration-only staging
  /// name from being copied into runtime SQL (a staging table is renamed to
  /// its final name before that migration completes).
  static const List<String> _privateReadCacheTables = [
    'cached_classes',
    'cached_students',
    'cached_subjects',
    'cached_assessments',
    'cached_dashboard',
    'cached_members',
    'cached_attendance',
    'cached_grade_sheets',
    'cached_review_packets',
    'cached_review_packet_details',
    'cached_review_stats',
    'cached_edu_classes',
    'cached_edu_class_rosters',
    'cached_edu_subjects',
    'cached_edu_teacher_snapshot',
    'cached_edu_teachers',
    'cached_edu_teacher_details',
    'cached_hr_sheet',
    'cached_hr_sections',
    'comm_threads',
    'comm_messages',
    'comm_meta',
    'cached_notifications',
    'cached_announcements',
    'sync_log',
  ];

  /// Serializes packet replacement in invocation order. SQLite transactions
  /// make each replacement atomic; this chain also makes rapid same-key user
  /// intent deterministic instead of depending on platform scheduling.
  Future<T> _serializeLegacySave<T>(Future<T> Function() action) {
    final result = _legacySaveTail.then((_) => action());
    _legacySaveTail = result.then<void>((_) {}).catchError((_) {});
    return result;
  }

  Future<Database> get database async {
    if (_db != null) return _db!;
    _db = await _initDb();
    return _db!;
  }

  Future<Database> _initDb() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, 'wbws_offline_v4.db');
    await ensureNoInterruptedDatabaseMigration(path);

    // The offline DB contains caches AND durable unsynchronized work. It is
    // ordinary SQLite, not app-level encrypted. OS sandbox/encryption varies
    // by device; server authority does not make pending local data disposable.
    return await openDatabase(
      path,
      // A warm UI engine and a cold background engine may open the production
      // database concurrently. Do not collapse those independent connections
      // into sqflite's per-isolate singleton; SQLite transactions arbitrate
      // the shared file and LocalDb's claim/lease protocol arbitrates work.
      singleInstance: false,
      version: localDatabaseSchemaVersion,
      onConfigure: (db) async {
        await db.execute('PRAGMA foreign_keys = ON');
        // Set-form PRAGMAs must go through rawQuery on Android: db.execute()
        // throws "Queries can be performed using ... rawQuery methods only"
        // for them, which crashed every database open in 1.1.15.
        try {
          await db.rawQuery('PRAGMA secure_delete = ON');
        } catch (_) {}
      },
      onCreate: (db, version) async {
        await _createTables(db);
        await _migrateToV34(db);
        await db.execute(localSyncStateV35Sql);
        await _createSyncAttemptLedger(db);
        await migrateAttendanceOriginalYear(db);
        await db.execute(attendancePreparationSnapshotSql);
        await db.execute(gradesPreparationSnapshotSql);
        await db.execute(attendanceSyncReceiptSql);
        await db.execute(attendanceSyncReceiptIndexSql);
        await db.execute(gradesSyncReceiptSql);
        await db.execute(gradesSyncReceiptIndexSql);
        await _migrateToV43(db);
        await _migrateToV44(db);
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        if (oldVersion < 2) {
          // Add new tables for v2
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_dashboard (
              id INTEGER PRIMARY KEY DEFAULT 1,
              stats_json TEXT,
              role TEXT,
              updated_at TEXT
            )
          ''');
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_members (
              id INTEGER PRIMARY KEY,
              student_name TEXT,
              father_name TEXT,
              member_code TEXT,
              gender TEXT,
              status TEXT,
              current_section TEXT,
              data_json TEXT,
              updated_at TEXT
            )
          ''');
        }
        if (oldVersion < 3) {
          // Add cached attendance responses
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_attendance (
              class_id INTEGER NOT NULL,
              date TEXT NOT NULL,
              response_json TEXT NOT NULL,
              updated_at TEXT,
              PRIMARY KEY (class_id, date)
            )
          ''');
        }
        if (oldVersion < 4) {
          try {
            await db.execute('ALTER TABLE pending_attendance ADD COLUMN notes TEXT');
          } catch (_) {}
        }
        if (oldVersion < 5) {
          try {
            await db.execute(
                "ALTER TABLE pending_attendance ADD COLUMN packet_kind TEXT DEFAULT 'draft'");
          } catch (_) {}
          try {
            await db.execute(
                "ALTER TABLE pending_grades ADD COLUMN packet_kind TEXT DEFAULT 'draft'");
          } catch (_) {}
          try {
            await db.execute(
                "UPDATE pending_attendance SET packet_kind = 'draft' WHERE packet_kind IS NULL");
          } catch (_) {}
          try {
            await db.execute(
                "UPDATE pending_grades SET packet_kind = 'draft' WHERE packet_kind IS NULL");
          } catch (_) {}
        }
        if (oldVersion < 6) {
          try {
            await db.execute('''
              CREATE TABLE IF NOT EXISTS cached_grade_sheets (
                assessment_id INTEGER PRIMARY KEY,
                class_id INTEGER,
                response_json TEXT NOT NULL,
                updated_at TEXT
              )
            ''');
          } catch (_) {}
        }
        if (oldVersion < 7) {
          try {
            await db.execute(
                "ALTER TABLE pending_attendance ADD COLUMN client_op_id TEXT");
          } catch (_) {}
          try {
            await db.execute(
                "ALTER TABLE pending_grades ADD COLUMN client_op_id TEXT");
          } catch (_) {}
        }
        if (oldVersion < 8) {
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_grade_sheets (
              assessment_id INTEGER PRIMARY KEY,
              class_id INTEGER,
              response_json TEXT NOT NULL,
              updated_at TEXT
            )
          ''');
        }
        if (oldVersion < 12) {
          // Phase B (2026-08): HR department attendance — HR's OWN
          // section-based domain with its own tables: HR data never mixes
          // with Education, on the server or on the phone.
          await db.execute('''
            CREATE TABLE IF NOT EXISTS pending_hr (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              date TEXT NOT NULL,
              section TEXT NOT NULL DEFAULT '',
              member_id INTEGER NOT NULL,
              status TEXT NOT NULL,
              notes TEXT,
              packet_kind TEXT NOT NULL DEFAULT 'draft',
              client_op_id TEXT,
              synced INTEGER NOT NULL DEFAULT 0,
              created_at TEXT NOT NULL,
              synced_at TEXT,
              sync_error TEXT
            )
          ''');
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_hr_sheet (
              date TEXT NOT NULL,
              section TEXT NOT NULL DEFAULT '',
              response_json TEXT NOT NULL,
              updated_at TEXT,
              PRIMARY KEY (date, section)
            )
          ''');
          await db.execute('''
            CREATE TABLE IF NOT EXISTS cached_hr_sections (
              id INTEGER PRIMARY KEY CHECK (id = 1),
              sections_json TEXT NOT NULL,
              updated_at TEXT
            )
          ''');
        }
        if (oldVersion < 26) {
          // O1 (offline-first comm): WhatsApp-style local store — the
          // UI reads these tables first; the network only refreshes
          // them. All five are member PII → wiped on logout below.
          await _createCommTables(db);
        }
        if (oldVersion < 27) {
          // P1-B (offline-first notification center): alerts +
          // announcements cached for instant local render, mirroring
          // the two server feeds' contracts. Caches start empty and
          // fill from the first successful refresh — no data
          // migration. User-scoped server responses → wiped on
          // logout like every other cache.
          await _createNotificationTables(db);
        }
        if (oldVersion < 29) {
          // P1-D (Review Inbox local-first): the department review
          // queue's read model — list packets, detail payloads, and
          // per-department stats, all stored verbatim from the three
          // /submissions endpoints. Cache starts empty — no data
          // migration. Dept-scoped server responses (detail rows
          // carry member marks) → wiped on logout.
          await _createReviewTables(db);
        }
        if (oldVersion < 30) {
          // P1-E (Education Classes local-first): the education
          // department's own read model — the complete active class
          // list plus per-class rosters, stored verbatim from the two
          // /classes endpoints. Deliberately SEPARATE tables: the
          // teacher workflow's cached_classes/cached_students are
          // protected shared caches (destructive writers, three
          // writers, no level_order/year contract) and stay
          // byte-untouched. Cache starts empty — no data migration.
          // Rosters carry member PII → wiped on logout.
          await _createEduTables(db);
        }
        if (oldVersion < 31) {
          // P1-F (Education Subjects local-first): the education
          // department's subject catalog — the complete active set
          // from GET /subjects, stored verbatim. Dedicated table:
          // cached_subjects is the teacher grade-bootstrap cache
          // ((id, class_id) composite PK, fed from /grades/bootstrap)
          // and stays byte-untouched. Cache starts empty — no data
          // migration. Role-scoped server response → wiped on logout
          // with everything else.
          await _createEduSubjectsTable(db);
        }
        if (oldVersion < 32) {
          // P1-G (Education Teachers local-first): a dedicated,
          // COMPLETE active-teacher snapshot plus view-once details.
          // The singleton metadata row distinguishes never-cached from
          // a valid empty directory; assignment details are explicitly
          // keyed by (teacher_id, academic_year_id). Nothing is derived
          // from or written into member/teacher workflow caches.
          await _createEduTeachersTables(db);
        }
        if (oldVersion < 34) {
          // Risks #1/#9/#8/#10: one coordinated owner/scope/state and
          // immutable-operation migration. sqflite wraps onUpgrade in one
          // transaction; the migration itself is repeat-safe.
            await _migrateToV34(db);
          }
          if (oldVersion < 35) {
            // Download-sync cursor state. Creating it empty is the whole
            // migration: an absent row means cursor 0, which the server
            // answers with bootstrap_required, so an upgrading device
            // rebuilds its attendance cache instead of resuming from a
            // revision it never actually applied.
            await db.execute(localSyncStateV35Sql);
          }
          if (oldVersion < 36) {
            // Operation/attempt lineage. Created empty on purpose: existing
            // pending work keeps its outbox row, its client_op_id and its
            // attempt_count untouched, and simply has no recorded history
            // before this point. Backfilling invented attempts would be
            // fabricated evidence.
            await _createSyncAttemptLedger(db);
          }
          if (oldVersion < 37) {
            // S2 Goal A.1 — execution provenance on the S1 attempt ledger.
            //
            // Guarded by a column probe because v36 installs may have been
            // created by _createSyncAttemptLedger (which now ships the column)
            // rather than by this ALTER, and adding it twice is an error.
            //
            // The DEFAULT backfills existing rows with 'foreground'. That is
            // not a placeholder: every attempt recorded before this version
            // was necessarily produced by an in-app drain, because no
            // background execution path existed to produce any other kind.
            if (await _tableExists(db, 'sync_attempts')) {
              final columns = await _columnNames(db, 'sync_attempts');
              if (!columns.contains('execution_source')) {
                await db.execute(
                  'ALTER TABLE sync_attempts ADD COLUMN execution_source '
                  "TEXT NOT NULL DEFAULT 'foreground'",
                );
              }
            }
          }
          if (oldVersion < 38) {
            await migrateAttendanceOriginalYear(db);
          }
          if (oldVersion < 39) {
            await db.execute(attendancePreparationSnapshotSql);
          }
          if (oldVersion < 40) {
            await db.execute(attendanceSyncReceiptSql);
            await db.execute(attendanceSyncReceiptIndexSql);
          }
          if (oldVersion < 41) {
            await db.execute(gradesPreparationSnapshotSql);
          }
          if (oldVersion < 42) {
            await db.execute(gradesSyncReceiptSql);
            await db.execute(gradesSyncReceiptIndexSql);
          }
          if (oldVersion < 43) {
            await _migrateToV43(db);
          }
          if (oldVersion < 44) {
            await _migrateToV44(db);
          }
        },
      onOpen: (db) async {
        // No HTTP request survives its issuing process. Recover durable claims
        // before any scheduler can observe/select work, preserving operation
        // and idempotency identity for safe replay.
        await _recoverOrphanedInFlightWithDb(db);
      },
    );
  }

  Future<bool> _tableExists(Database db, String table) async {
    final rows = await db.rawQuery(
      "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1",
      [table],
    );
    return rows.isNotEmpty;
  }

  Future<Set<String>> _columnNames(Database db, String table) async {
    final rows = await db.rawQuery('PRAGMA table_info($table)');
    return rows.map((row) => '${row['name']}').toSet();
  }

  /// Coordinated schema-v34 migration. onCreate/onUpgrade supply the outer
  /// transaction; every step is repeat-safe so interrupted opens can resume.
  Future<void> _migrateToV34(Database db) async {
    for (final spec in localV34ColumnSpecs) {
      if (!await _tableExists(db, spec.table)) continue;
      final columns = await _columnNames(db, spec.table);
      if (!columns.contains(spec.name)) {
        await db.execute(
          'ALTER TABLE ${spec.table} ADD COLUMN ${spec.name} '
          '${spec.declaration}',
        );
      }
    }

    await db.execute(localSessionStateV34Sql);
    await db.insert(
      'local_session_state',
      {
        'id': 1,
        'state': 'anonymous_clean',
        'generation': 0,
        'updated_at': DateTime.now().toUtc().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );

    final now = DateTime.now().toUtc().toIso8601String();
    for (final spec in legacyOutboxTableSpecs) {
      if (!await _tableExists(db, spec.table)) continue;
      await db.rawUpdate(
        "UPDATE ${spec.table} SET sync_state = 'synced' "
        "WHERE synced = 1 AND sync_state <> 'synced'",
      );
      await db.rawUpdate(
        "UPDATE ${spec.table} "
        "SET sync_state = 'needs_attention', "
        "failure_code = COALESCE(failure_code, 'LEGACY_REJECTION'), "
        'failed_at = COALESCE(failed_at, ?) '
        "WHERE synced = 0 AND sync_error IS NOT NULL "
        "AND sync_state = 'pending'",
        [now],
      );
    }


    if (await _tableExists(db, 'comm_outbox')) {
      // Keep `failed` for the existing bubble UI. The structured metadata
      // gives it needs-attention semantics without switching live behavior.
      await db.rawUpdate(
        "UPDATE comm_outbox SET "
        "failure_code = COALESCE(failure_code, 'LEGACY_COMM_FAILURE'), "
        'failed_at = COALESCE(failed_at, ?) '
        "WHERE state = 'failed'",
        [now],
      );
    }

    await _reconcileLegacyOperationIds(db, now);

    for (final sql in localV34IndexSql) {
      await db.execute(sql);
    }
  }

  /// Coordinated schema-v43 migration: offline photo outbox + exports +
  /// per-install device identity + sync-policy row.
  ///
  /// Repeat-safe: onCreate and onUpgrade both call this, and each CREATE
  /// uses IF NOT EXISTS. Seed rows use INSERT OR IGNORE so an interrupted
  /// migration can resume without collision.
  Future<void> _migrateToV43(Database db) async {
    await db.execute(photoDeviceIdentityV43Sql);
    await db.execute(photoOutboxV43Sql);
    for (final sql in photoOutboxIndexesV43Sql) {
      await db.execute(sql);
    }
    await db.execute(photoExportBatchesV43Sql);
    for (final sql in photoExportBatchesIndexV43Sql) {
      await db.execute(sql);
    }
    await db.execute(photoSyncPolicyV43Sql);

    final now = DateTime.now().toUtc().toIso8601String();

    // Seed the sync-policy row with safe defaults (sync OFF, no cellular).
    await db.rawInsert('''
      INSERT OR IGNORE INTO photo_sync_policy
        (id, sync_enabled, allow_cellular, max_concurrency, updated_at)
      VALUES (1, 0, 0, 2, ?)
    ''', [now]);
  }

  /// v44 (Mezmur department removal): drop every local table the retired
  /// module owned. Repeat-safe: every statement is DROP TABLE IF EXISTS and
  /// runs inside sqflite's single onUpgrade transaction. On a fresh install
  /// the tables are never created, so this is a no-op there.
  Future<void> _migrateToV44(Database db) async {
    for (final sql in mezmurRemovalV44Sql) {
      await db.execute(sql);
    }
  }

  String _legacyBusinessKey(
    LegacyOutboxTableSpec spec,
    Map<String, Object?> row,
  ) {
    return jsonEncode([
      for (final column in spec.businessKeyColumns) row[column],
    ]);
  }

  String _legacyPacketKind(Map<String, Object?> row) {
    final value = '${row['packet_kind'] ?? 'draft'}'.trim().toLowerCase();
    return value.isEmpty ? 'draft' : value;
  }

  /// Gives coherent pre-v34 packets one shared id. Ambiguous generations are
  /// never merged/selected lexically: every row is retained and quarantined.
  Future<void> _reconcileLegacyOperationIds(Database db, String now) async {
    for (final spec in legacyOutboxTableSpecs) {
      if (!await _tableExists(db, spec.table)) continue;
      final rows = await db.query(
        spec.table,
        columns: [
          'id',
          ...spec.businessKeyColumns,
          'packet_kind',
          'client_op_id',
          'failed_at',
        ],
        where: 'synced = 0',
        orderBy: 'id',
      );
      final groups = <String, List<Map<String, Object?>>>{};
      for (final row in rows) {
        groups.putIfAbsent(_legacyBusinessKey(spec, row), () => []).add(row);
      }

      for (final group in groups.values) {
        final ids = <String>{};
        final blankRows = <Map<String, Object?>>[];
        final packetKinds = <String>{};
        for (final row in group) {
          final id = '${row['client_op_id'] ?? ''}';
          if (id.trim().isEmpty) {
            blankRows.add(row);
          } else {
            ids.add(id);
          }
          packetKinds.add(_legacyPacketKind(row));
        }

        final validPacketKind = packetKinds.length == 1 &&
            (packetKinds.single == 'draft' ||
                packetKinds.single == 'submitted');
        final coherent = validPacketKind &&
            (ids.isEmpty || (ids.length == 1 && blankRows.isEmpty));

        if (coherent && ids.isEmpty) {
          final generated = newClientOpId();
          for (final row in blankRows) {
            await db.update(
              spec.table,
              {'client_op_id': generated},
              where: 'id = ? AND synced = 0',
              whereArgs: [row['id']],
            );
          }
          continue;
        }
        if (coherent) continue;

        // Blank children in an ambiguous set receive individual identities;
        // assigning one shared id would falsely assert a coherent generation.
        for (final row in group) {
          final values = <String, Object?>{
            'sync_state': 'needs_attention',
            'failure_code': 'LEGACY_MIXED_OPERATION_SET',
            if (row['failed_at'] == null) 'failed_at': now,
          };
          if ('${row['client_op_id'] ?? ''}'.trim().isEmpty) {
            values['client_op_id'] = newClientOpId();
          }
          await db.update(
            spec.table,
            values,
            where: 'id = ? AND synced = 0',
            whereArgs: [row['id']],
          );
        }
      }
    }

    // Also reject one id reused across business keys, packet kinds, or tables.
    // UUID lexical ordering is deliberately absent from this reconciliation.
    final identitiesById = <String, Set<String>>{};
    final tablesById = <String, Set<String>>{};
    for (final spec in legacyOutboxTableSpecs) {
      if (!await _tableExists(db, spec.table)) continue;
      final rows = await db.query(
        spec.table,
        columns: [
          ...spec.businessKeyColumns,
          'packet_kind',
          'client_op_id',
        ],
        where: 'synced = 0',
      );
      for (final row in rows) {
        final id = '${row['client_op_id'] ?? ''}';
        if (id.trim().isEmpty) continue;
        final identity = '${spec.table}|${_legacyBusinessKey(spec, row)}|'
            '${_legacyPacketKind(row)}';
        identitiesById.putIfAbsent(id, () => <String>{}).add(identity);
        tablesById.putIfAbsent(id, () => <String>{}).add(spec.table);
      }
    }
    for (final entry in identitiesById.entries) {
      if (entry.value.length <= 1) continue;
      for (final table in tablesById[entry.key] ?? const <String>{}) {
        await db.rawUpdate(
          "UPDATE $table SET sync_state = 'needs_attention', "
          "failure_code = 'LEGACY_MIXED_OPERATION_SET', "
          'failed_at = COALESCE(failed_at, ?) '
          'WHERE client_op_id = ? AND synced = 0',
          [now, entry.key],
        );
      }
    }
  }


  // ── S1: durable operation/attempt lineage ────────────────────────────────
  // The outbox row stays authoritative for execution. These helpers only
  // record what happened around it, inside the very same transactions, so a
  // crash can never leave the ledger disagreeing with the row it describes.

  Future<void> _createSyncAttemptLedger(DatabaseExecutor db) async {
    await db.execute(localSyncAttemptsV36Sql);
    for (final statement in localSyncAttemptsV36IndexSql) {
      await db.execute(statement);
    }
  }

  /// Correlation id for exactly one transmission.
  ///
  /// Opaque and random by construction: it carries no user id, no token, no
  /// payload and nothing guessable about the operation it belongs to.
  String newAttemptUid() => 'att_${newClientOpId().replaceAll('-', '')}';

  /// Opens an attempt row for a claim that is about to be transmitted.
  ///
  /// Runs inside the claim transaction. `attemptNumber` is the outbox row's
  /// own post-increment `attempt_count`, so the ledger and the row can never
  /// drift. The UNIQUE(client_op_id, attempt_number) index turns a
  /// non-incrementing attempt counter into a hard failure rather than a
  /// quietly corrupted history.
  Future<void> _openSyncAttempt(
    DatabaseExecutor txn, {
    required String clientOpId,
    required int attemptNumber,
    required String attemptUid,
    required String domain,
    required DateTime startedAt,
    Map<String, Object?>? entityRef,
    int? ownerUserId,
    int? authorizationVersion,
    SyncExecutionSource executionSource = SyncExecutionSource.foreground,
  }) async {
    await txn.insert(
      'sync_attempts',
      {
        'client_op_id': clientOpId,
        'attempt_number': attemptNumber,
        'attempt_uid': attemptUid,
        'domain': domain,
        // S2 Goal A.1: provenance of the drain that opened this attempt.
        'execution_source': executionSource.storageValue,
        // Natural key only — never the records payload.
        'entity_ref': entityRef == null ? null : jsonEncode(entityRef),
        'owner_user_id': ownerUserId,
        'created_authorization_version': authorizationVersion,
        'started_at': startedAt.toUtc().toIso8601String(),
        'retry_decision': SyncRetryDecision.pending.storageValue,
      },
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );
  }

  /// Closes the open attempt for an operation inside the settlement
  /// transaction. Never rewrites an attempt that is already closed, so
  /// earlier failures survive a later success.
  Future<void> _closeSyncAttempt(
    DatabaseExecutor txn, {
    required String clientOpId,
    required int attemptNumber,
    required SyncAttemptClosure closure,
    required DateTime finishedAt,
  }) async {
    final rows = await txn.query(
      'sync_attempts',
      columns: ['started_at'],
      where: 'client_op_id = ? AND attempt_number = ? AND finished_at IS NULL',
      whereArgs: [clientOpId, attemptNumber],
      limit: 1,
    );
    if (rows.isEmpty) return;
    final startedAt = DateTime.tryParse('${rows.first['started_at']}')?.toUtc();
    final finished = finishedAt.toUtc();
    final duration =
        startedAt == null ? null : finished.difference(startedAt).inMilliseconds;
    await txn.update(
      'sync_attempts',
      {
        'finished_at': finished.toIso8601String(),
        'duration_ms': duration != null && duration >= 0 ? duration : null,
        'http_status': closure.httpStatus,
        'error_category': closure.category.storageValue,
        'retry_decision': closure.decision.storageValue,
        'failure_message': _safeAttemptMessage(closure.failureMessage),
        'next_attempt_at': closure.nextAttemptAt?.toUtc().toIso8601String(),
        'server_ref': closure.serverRef,
      },
      where: 'client_op_id = ? AND attempt_number = ? AND finished_at IS NULL',
      whereArgs: [clientOpId, attemptNumber],
    );
  }

  /// Diagnostic messages are bounded and must never carry a payload.
  static String? _safeAttemptMessage(String? message) {
    if (message == null) return null;
    final trimmed = message.trim();
    if (trimmed.isEmpty) return null;
    return trimmed.length <= 200 ? trimmed : trimmed.substring(0, 200);
  }

  /// Marks attempts whose process disappeared. Called by the same recovery
  /// sweep that releases orphaned `in_flight` rows, so an interrupted
  /// transmission is never left looking like it is still running — and never
  /// looking like it succeeded.
  Future<void> _interruptOpenSyncAttempts(
    DatabaseExecutor txn,
    DateTime now,
  ) async {
    await txn.update(
      'sync_attempts',
      {
        'finished_at': now.toUtc().toIso8601String(),
        'retry_decision': SyncRetryDecision.interrupted.storageValue,
        'error_category': SyncErrorCategory.unknown.storageValue,
        'failure_message': 'The app closed before this attempt finished.',
      },
      where: 'finished_at IS NULL',
    );
  }

  /// Full history of one logical operation, oldest attempt first.
  Future<SyncOperationLineage?> operationLineage(String clientOpId) async {
    final db = await database;
    if (!await _tableExists(db, 'sync_attempts')) return null;
    final rows = await db.query(
      'sync_attempts',
      where: 'client_op_id = ?',
      whereArgs: [clientOpId],
      orderBy: 'attempt_number',
    );
    if (rows.isEmpty) return null;
    return SyncOperationLineage(
      clientOpId: clientOpId,
      domain: '${rows.first['domain']}',
      attempts: rows.map(SyncAttemptRecord.fromRow).toList(growable: false),
    );
  }

  /// Most recently active operations, newest first, each with full lineage.
  Future<List<SyncOperationLineage>> recentOperationLineages({
    int limit = 50,
  }) async {
    final db = await database;
    if (!await _tableExists(db, 'sync_attempts')) {
      return const <SyncOperationLineage>[];
    }
    final bounded = limit < 1 ? 1 : (limit > 500 ? 500 : limit);
    final heads = await db.rawQuery(
      'SELECT client_op_id, MAX(started_at) AS last_started '
      'FROM sync_attempts GROUP BY client_op_id '
      'ORDER BY last_started DESC, client_op_id DESC LIMIT ?',
      [bounded],
    );
    final lineages = <SyncOperationLineage>[];
    for (final head in heads) {
      final lineage = await operationLineage('${head['client_op_id']}');
      if (lineage != null) lineages.add(lineage);
    }
    return lineages;
  }

  /// Bounded growth guard.
  ///
  /// This is a capacity limit, not a retention policy: it keeps the newest
  /// [keepOperations] operations so the ledger cannot grow without end on a
  /// long-lived install. A dated retention policy is deliberately out of
  /// scope here. Operations with an attempt still open are never pruned.
  Future<int> pruneSyncAttempts({int keepOperations = 500}) async {
    final db = await database;
    if (!await _tableExists(db, 'sync_attempts')) return 0;
    final keep = keepOperations < 1 ? 1 : keepOperations;
    return db.rawDelete(
      'DELETE FROM sync_attempts WHERE client_op_id NOT IN ('
      '  SELECT client_op_id FROM sync_attempts'
      '  GROUP BY client_op_id'
      '  ORDER BY MAX(started_at) DESC, client_op_id DESC'
      '  LIMIT ?'
      ') AND client_op_id NOT IN ('
      '  SELECT DISTINCT client_op_id FROM sync_attempts WHERE finished_at IS NULL'
      ')',
      [keep],
    );
  }

  /// Public for startup orchestration and deterministic recovery tests.
  Future<void> recoverOrphanedInFlightOperations() async {
    final db = await database;
    await _recoverOrphanedInFlightWithDb(db);
  }

  Future<void> _recoverOrphanedInFlightWithDb(Database db) async {
    final now = DateTime.now().toUtc().toIso8601String();
    final existingLegacy = <LegacyOutboxTableSpec>[];
    for (final spec in legacyOutboxTableSpecs) {
      if (await _tableExists(db, spec.table)) existingLegacy.add(spec);
    }
    final hasComm = await _tableExists(db, 'comm_outbox');
    final hasAttemptLedger = await _tableExists(db, 'sync_attempts');
    await db.transaction((txn) async {
      for (final spec in existingLegacy) {
        await txn.rawUpdate(
          "UPDATE ${spec.table} SET sync_state = 'retry_wait', "
          'next_attempt_at = ? '
          "WHERE synced = 0 AND sync_state = 'in_flight'",
          [now],
        );
      }
      if (hasComm) {
        await txn.rawUpdate(
          "UPDATE comm_outbox SET state = 'retry_wait', next_attempt_at = ? "
          "WHERE state = 'in_flight'",
          [now],
        );
      }
      // S1 lineage: an attempt whose process vanished must not keep looking
      // like it is still running, and must never be mistaken for a success.
      if (hasAttemptLedger) {
        await _interruptOpenSyncAttempts(txn, DateTime.parse(now));
      }
    });
  }

  /// Communication offline tables (schema v26, offline-first O1).
  /// Kept in one place so onCreate and onUpgrade stay identical —
  /// every statement is CREATE ... IF NOT EXISTS, so running it on an
  /// upgraded device is a no-op. Threads/messages/outbox/drafts/meta
  /// mirror the v1 API rows; the pure row<->JSON mappers and merge
  /// rules live in comm_store.dart / messaging_view_model.dart.
  Future<void> _createCommTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS comm_threads (
        id INTEGER PRIMARY KEY,
        subject TEXT NOT NULL DEFAULT '',
        participants_label TEXT,
        last_body TEXT,
        last_message_at TEXT,
        unread_count INTEGER NOT NULL DEFAULT 0,
        message_count INTEGER NOT NULL DEFAULT 0,
        created_at TEXT,
        fetched_at TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS comm_messages (
        id INTEGER PRIMARY KEY,
        thread_id INTEGER NOT NULL,
        sender_id INTEGER,
        sender_name TEXT,
        sender_label TEXT,
        body TEXT NOT NULL DEFAULT '',
        created_at TEXT,
        edited INTEGER NOT NULL DEFAULT 0,
        deleted INTEGER NOT NULL DEFAULT 0,
        mine INTEGER NOT NULL DEFAULT 0,
        client_tag TEXT
      )
    ''');
    await db.execute(
        'CREATE INDEX IF NOT EXISTS idx_comm_messages_thread ON comm_messages(thread_id, id)');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS comm_outbox (
        client_tag TEXT PRIMARY KEY,
        thread_id INTEGER NOT NULL,
        body TEXT NOT NULL,
        state TEXT NOT NULL DEFAULT 'pending',
        attempts INTEGER NOT NULL DEFAULT 0,
        next_attempt_at TEXT,
        created_at TEXT NOT NULL,
        fail_reason TEXT,
        last_attempt_at TEXT,
        failure_code TEXT,
        failure_http_status INTEGER,
        failed_at TEXT,
        owner_user_id INTEGER,
        created_authorization_version INTEGER
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS comm_drafts (
        thread_id INTEGER PRIMARY KEY,
        body TEXT NOT NULL DEFAULT '',
        updated_at TEXT,
        owner_user_id INTEGER,
        created_authorization_version INTEGER
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS comm_meta (
        key TEXT PRIMARY KEY,
        value TEXT
      )
    ''');
  }


  /// P1-B (DB v27): local-first Notification Center store. Two
  /// tables because the two server feeds have different contracts —
  /// alerts page by id DESC / before_id; announcements by
  /// (is_pinned, id) DESC with the server-authoritative expires_at.
  Future<void> _createNotificationTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_notifications (
        id INTEGER PRIMARY KEY,
        is_unread INTEGER NOT NULL DEFAULT 1,
        data_json TEXT,
        fetched_at TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_announcements (
        id INTEGER PRIMARY KEY,
        is_pinned INTEGER NOT NULL DEFAULT 0,
        is_unread INTEGER NOT NULL DEFAULT 1,
        expires_at TEXT,
        data_json TEXT,
        fetched_at TEXT
      )
    ''');
  }


  /// P1-D (DB v29): local-first Review Inbox read model. Identity is
  /// (dept, id) — the three departments' submission tables are
  /// independent server tables, so the same numeric id can exist in
  /// more than one department context. Status stays a discrete
  /// verbatim column (the server's per-filter windows query it);
  /// updated_at (server string, verbatim) reproduces every service's
  /// ORDER BY updated_at DESC, id DESC. Details live in their own
  /// table so a list refresh can never clobber a cached detail's
  /// roster rows; stats are stored verbatim per department and are
  /// NEVER recomputed locally (server stats cover a different
  /// population/window than the cached rows).
  Future<void> _createReviewTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_review_packets (
        dept TEXT NOT NULL,
        id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT '',
        updated_at TEXT,
        data_json TEXT,
        fetched_at TEXT,
        PRIMARY KEY (dept, id)
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_review_packet_details (
        dept TEXT NOT NULL,
        id INTEGER NOT NULL,
        data_json TEXT,
        fetched_at TEXT,
        PRIMARY KEY (dept, id)
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_review_stats (
        dept TEXT NOT NULL PRIMARY KEY,
        stats_json TEXT,
        fetched_at TEXT
      )
    ''');
  }

  /// P1-E (DB v30): local-first Education Classes read model — its
  /// OWN tables, never the teacher workflow's cached_classes /
  /// cached_students (protected shared caches: CatalogService's
  /// destructive full replace, three cacheStudents writers, no
  /// level_order column, no roster-year contract). The class list is
  /// a complete scoped snapshot replaced on every successful refresh
  /// (deletion-aware: renames, deactivations, eligible hard deletes
  /// all propagate); local ordering reproduces the server's
  /// `level_order, class_name`. Each roster is ONE row per class_id
  /// holding the server response verbatim — including the
  /// year-resolution metadata (roster_year_id / roster_year_name /
  /// roster_fallback), which is server contract and is never
  /// reconstructed locally.
  Future<void> _createEduTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_classes (
        id INTEGER PRIMARY KEY,
        class_name TEXT NOT NULL DEFAULT '',
        class_name_en TEXT,
        level_order INTEGER NOT NULL DEFAULT 0,
        student_count INTEGER NOT NULL DEFAULT 0,
        data_json TEXT,
        fetched_at TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_class_rosters (
        class_id INTEGER PRIMARY KEY,
        roster_year_id INTEGER,
        roster_year_name TEXT,
        roster_fallback INTEGER NOT NULL DEFAULT 0,
        data_json TEXT,
        fetched_at TEXT
      )
    ''');
  }

  /// P1-F (DB v31): local-first Education Subjects read model — the
  /// complete active subject catalog from GET /subjects, one row per
  /// subject, payload verbatim (including the class_count aggregate,
  /// a server count over class_subjects that is NEVER recomputed
  /// locally). Deliberately SEPARATE from cached_subjects: that table
  /// is the teacher grade-bootstrap cache ((id, class_id) composite
  /// PK, written from /grades/bootstrap) and belongs to the protected
  /// teacher workflow. The catalog is a complete scoped snapshot
  /// replaced on every successful refresh (deletion-aware: renames,
  /// deactivations and eligible hard deletes propagate); local
  /// ordering reproduces the server's ORDER BY subject_name —
  /// COLLATE NOCASE approximates MySQL utf8mb4_unicode_ci's ASCII
  /// case fold (Amharic orders code-point-identically; the residual
  /// case-mixed Latin edge is a disclosed cosmetic limitation).
  Future<void> _createEduSubjectsTable(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_subjects (
        id INTEGER PRIMARY KEY,
        subject_name TEXT NOT NULL DEFAULT '',
        subject_name_en TEXT,
        subject_code TEXT,
        class_count INTEGER NOT NULL DEFAULT 0,
        data_json TEXT,
        fetched_at TEXT
      )
    ''');
  }

  /// P1-G: Education Teachers uses its OWN read model. The singleton
  /// snapshot row records a valid complete fetch even when zero teachers
  /// exist; sort_order preserves the server's cross-page ordering; details
  /// are scoped by the exact server-resolved academic year. Deliberately
  /// separate from cached_members and the teacher workflow's destructive
  /// cached_classes/cached_students/cached_subjects stores.
  Future<void> _createEduTeachersTables(Database db) async {
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_teacher_snapshot (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        academic_year_id INTEGER NOT NULL DEFAULT 0,
        academic_year_name TEXT,
        total INTEGER NOT NULL DEFAULT 0,
        fetched_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_teachers (
        id INTEGER PRIMARY KEY,
        username TEXT NOT NULL DEFAULT '',
        full_name TEXT NOT NULL DEFAULT '',
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT,
        assigned_classes INTEGER NOT NULL DEFAULT 0,
        assigned_subjects INTEGER NOT NULL DEFAULT 0,
        sort_order INTEGER NOT NULL,
        data_json TEXT NOT NULL,
        fetched_at TEXT NOT NULL
      )
    ''');
    await db.execute('''
      CREATE INDEX IF NOT EXISTS idx_cached_edu_teachers_sort
      ON cached_edu_teachers (sort_order)
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_edu_teacher_details (
        teacher_id INTEGER NOT NULL,
        academic_year_id INTEGER NOT NULL DEFAULT 0,
        academic_year_name TEXT,
        data_json TEXT NOT NULL,
        fetched_at TEXT NOT NULL,
        PRIMARY KEY (teacher_id, academic_year_id)
      )
    ''');
  }


  Future<void> _createTables(Database db) async {
    await _createCommTables(db);
    await _createNotificationTables(db);
    await _createReviewTables(db);
    await _createEduTables(db);
    await _createEduSubjectsTable(db);
    await _createEduTeachersTables(db);
    // ---- ATTENDANCE ----
    await db.execute('''
      CREATE TABLE pending_attendance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        class_id INTEGER NOT NULL,
        class_name TEXT,
        date TEXT NOT NULL,
        member_id INTEGER NOT NULL,
        student_name TEXT,
        father_name TEXT,
        member_code TEXT,
        status TEXT NOT NULL,
        notes TEXT,
        packet_kind TEXT NOT NULL DEFAULT 'draft',
        client_op_id TEXT,
        synced INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        synced_at TEXT,
        sync_error TEXT,
        sync_state TEXT NOT NULL DEFAULT 'pending',
        attempt_count INTEGER NOT NULL DEFAULT 0,
        next_attempt_at TEXT,
        last_attempt_at TEXT,
        failure_code TEXT,
        failure_http_status INTEGER,
        failed_at TEXT,
        created_authorization_version INTEGER,
        owner_user_id INTEGER
      )
    ''');

    // ---- HR ATTENDANCE (HR's own section-based domain) ----
    await db.execute('''
      CREATE TABLE pending_hr (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        section TEXT NOT NULL DEFAULT '',
        member_id INTEGER NOT NULL,
        status TEXT NOT NULL,
        notes TEXT,
        packet_kind TEXT NOT NULL DEFAULT 'draft',
        client_op_id TEXT,
        synced INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        synced_at TEXT,
        sync_error TEXT,
        sync_state TEXT NOT NULL DEFAULT 'pending',
        attempt_count INTEGER NOT NULL DEFAULT 0,
        next_attempt_at TEXT,
        last_attempt_at TEXT,
        failure_code TEXT,
        failure_http_status INTEGER,
        failed_at TEXT,
        created_authorization_version INTEGER,
        owner_user_id INTEGER
      )
    ''');
    await db.execute('''
      CREATE TABLE cached_hr_sheet (
        date TEXT NOT NULL,
        section TEXT NOT NULL DEFAULT '',
        response_json TEXT NOT NULL,
        updated_at TEXT,
        PRIMARY KEY (date, section)
      )
    ''');
    await db.execute('''
      CREATE TABLE cached_hr_sections (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        sections_json TEXT NOT NULL,
        updated_at TEXT
      )
    ''');
    await db.execute('''
      CREATE TABLE pending_grades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        assessment_id INTEGER NOT NULL,
        assessment_name TEXT,
        class_id INTEGER NOT NULL,
        class_name TEXT,
        subject_id INTEGER,
        subject_name TEXT,
        member_id INTEGER NOT NULL,
        student_name TEXT,
        record_id INTEGER,
        score REAL,
        remark TEXT,
        max_score REAL DEFAULT 100,
        packet_kind TEXT NOT NULL DEFAULT 'draft',
        client_op_id TEXT,
        synced INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        synced_at TEXT,
        sync_error TEXT,
        sync_state TEXT NOT NULL DEFAULT 'pending',
        attempt_count INTEGER NOT NULL DEFAULT 0,
        next_attempt_at TEXT,
        last_attempt_at TEXT,
        failure_code TEXT,
        failure_http_status INTEGER,
        failed_at TEXT,
        created_authorization_version INTEGER,
        owner_user_id INTEGER
      )
    ''');

    // ---- CACHE: CLASSES ----
    await db.execute('''
      CREATE TABLE cached_classes (
        id INTEGER PRIMARY KEY,
        class_name TEXT,
        class_name_en TEXT,
        section TEXT,
        student_count INTEGER DEFAULT 0,
        updated_at TEXT
      )
    ''');

    // ---- CACHE: STUDENTS ----
    await db.execute('''
      CREATE TABLE cached_students (
        member_id INTEGER NOT NULL,
        class_id INTEGER NOT NULL,
        student_name TEXT,
        father_name TEXT,
        member_code TEXT,
        gender TEXT,
        updated_at TEXT,
        PRIMARY KEY (member_id, class_id)
      )
    ''');

    // ---- CACHE: SUBJECTS ----
    await db.execute('''
      CREATE TABLE cached_subjects (
        id INTEGER NOT NULL,
        class_id INTEGER NOT NULL,
        subject_name TEXT,
        subject_name_en TEXT,
        subject_code TEXT,
        updated_at TEXT,
        PRIMARY KEY (id, class_id)
      )
    ''');

    // ---- CACHE: ASSESSMENTS ----
    await db.execute('''
      CREATE TABLE cached_assessments (
        id INTEGER PRIMARY KEY,
        class_id INTEGER NOT NULL,
        subject_id INTEGER NOT NULL,
        assessment_name TEXT,
        assessment_type TEXT,
        max_score REAL DEFAULT 100,
        weight_percentage REAL DEFAULT 100,
        grades_entered INTEGER DEFAULT 0,
        updated_at TEXT
      )
    ''');

    // ---- CACHE: DASHBOARD STATS ----
    await db.execute('''
      CREATE TABLE cached_dashboard (
        id INTEGER PRIMARY KEY DEFAULT 1,
        stats_json TEXT,
        role TEXT,
        updated_at TEXT
      )
    ''');

    // ---- CACHE: MEMBERS LIST ----
    await db.execute('''
      CREATE TABLE cached_members (
        id INTEGER PRIMARY KEY,
        student_name TEXT,
        father_name TEXT,
        member_code TEXT,
        gender TEXT,
        status TEXT,
        current_section TEXT,
        data_json TEXT,
        updated_at TEXT
      )
    ''');

    // ---- SYNC LOG ----
    await db.execute('''
      CREATE TABLE sync_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action TEXT,
        detail TEXT,
        status TEXT,
        created_at TEXT
      )
    ''');

    // ---- CACHE: ATTENDANCE RESPONSES (per class+date) ----
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_attendance (
        class_id INTEGER NOT NULL,
        date TEXT NOT NULL,
        response_json TEXT NOT NULL,
        updated_at TEXT,
        PRIMARY KEY (class_id, date)
      )
    ''');
    await db.execute('''
      CREATE TABLE IF NOT EXISTS cached_grade_sheets (
        assessment_id INTEGER PRIMARY KEY,
        class_id INTEGER,
        response_json TEXT NOT NULL,
        updated_at TEXT
      )
    ''');

  }

  // ============================================================
  // CACHED DASHBOARD STATS
  // ============================================================

  Future<void> cacheDashboardStats(Map<String, dynamic> stats, String role) async {
    final db = await database;
    await db.insert(
      'cached_dashboard',
      {
        'id': 1,
        'stats_json': jsonEncode(stats),
        'role': role,
        'updated_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<Map<String, dynamic>?> getCachedDashboardStats() async {
    final db = await database;
    final rows = await db.query('cached_dashboard', where: 'id = 1');
    if (rows.isEmpty) return null;
    final row = rows.first;
    try {
      final stats = jsonDecode(row['stats_json'] as String) as Map<String, dynamic>;
      stats['_cached'] = true;
      stats['_cached_at'] = row['updated_at'];
      return stats;
    } catch (_) {
      return null;
    }
  }

  // ============================================================
  // CACHED MEMBERS LIST
  // ============================================================

  Future<void> cacheMembers(List<dynamic> members) async {
    final db = await database;
    final batch = db.batch();
    // Don't clear — merge (keep existing if new fetch is partial)
    for (final m in members) {
      batch.insert(
        'cached_members',
        {
          'id': m['id'],
          'student_name': m['student_name'] ?? '',
          'father_name': m['father_name'] ?? '',
          'member_code': m['member_code'] ?? '',
          'gender': m['gender'] ?? '',
          'status': m['status'] ?? 'active',
          'current_section': m['current_section'] ?? '',
          'data_json': jsonEncode(m),
          'updated_at': DateTime.now().toIso8601String(),
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedMembers({
    String? search,
    String? status,
    int limit = 50,
    int offset = 0, // P1-A: local pagination (offline scrolling)
  }) async {
    final db = await database;
    String where = '1=1';
    List<dynamic> whereArgs = [];

    if (search != null && search.isNotEmpty) {
      where += ' AND (student_name LIKE ? OR father_name LIKE ? OR member_code LIKE ?)';
      whereArgs.addAll(['%$search%', '%$search%', '%$search%']);
    }
    if (status != null && status.isNotEmpty) {
      where += ' AND status = ?';
      whereArgs.add(status);
    }

    final rows = await db.query(
      'cached_members',
      where: where,
      whereArgs: whereArgs,
      orderBy: 'student_name',
      limit: limit,
      offset: offset,
    );

    // Return full member data from JSON
    return rows.map((row) {
      try {
        return jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
      } catch (_) {
        return <String, dynamic>{
          'id': row['id'],
          'student_name': row['student_name'],
          'father_name': row['father_name'],
          'member_code': row['member_code'],
          'gender': row['gender'],
          'status': row['status'],
          'current_section': row['current_section'],
        };
      }
    }).toList();
  }

  Future<int> getCachedMemberCount() async {
    final db = await database;
    final r = await db.rawQuery('SELECT COUNT(*) as cnt FROM cached_members');
    return r.first['cnt'] as int? ?? 0;
  }

  /// P1-A: newest cache-write timestamp across cached_members (null when
  /// nothing is cached) — drives the Members list's freshness line.
  /// Read-only; uses the column that already exists (no schema change).
  Future<String?> getCachedMembersLastSynced() async {
    final db = await database;
    final r = await db.rawQuery('SELECT MAX(updated_at) as m FROM cached_members');
    if (r.isEmpty) return null;
    final m = r.first['m'];
    return m == null ? null : '$m';
  }

  /// P1-A: single-member local read by PRIMARY KEY. Independent of the
  /// cached-list page size (the old detail fallback loaded the whole
  /// list with its default limit and could not find members past the
  /// first 50 rows). Returns the decoded server row plus a
  /// `local_updated_at` stamp for the cached-data banner; null when
  /// this member was never cached.
  Future<Map<String, dynamic>?> getCachedMemberById(int id) async {
    final db = await database;
    final rows = await db.query('cached_members',
        where: 'id = ?', whereArgs: [id], limit: 1);
    if (rows.isEmpty) return null;
    final row = rows.first;
    try {
      final decoded =
          jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
      decoded['local_updated_at'] = row['updated_at'];
      return decoded;
    } catch (_) {
      // Corrupt/missing blob — the filter columns still identify the
      // member honestly (never fabricate fields we do not have).
      return <String, dynamic>{
        'id': row['id'],
        'student_name': row['student_name'],
        'father_name': row['father_name'],
        'member_code': row['member_code'],
        'gender': row['gender'],
        'status': row['status'],
        'current_section': row['current_section'],
        'local_updated_at': row['updated_at'],
      };
    }
  }

  // ============================================================
  // P1-B: CACHED NOTIFICATION CENTER (alerts + announcements)
  // ============================================================

  /// Merge-upsert feed rows by server id (ConflictAlgorithm.replace —
  /// the server is authoritative on refresh; alert rows never
  /// disappear server-side, so merge-only is safe). The optimistic
  /// read flip writes is_unread separately and the next successful
  /// refresh reconciles it.
  Future<void> cacheNotificationRows(List<Map<String, dynamic>> rows) async {
    final db = await database;
    final batch = db.batch();
    final now = DateTime.now().toIso8601String();
    for (final m in rows) {
      batch.insert(
        'cached_notifications',
        {
          'id': m['id'],
          'is_unread': (m['is_unread'] ?? 0) == 1 ? 1 : 0,
          'data_json': jsonEncode(m),
          'fetched_at': now,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
    await batch.commit(noResult: true);
  }

  /// Local feed page — the server's exact ordering (id DESC) and
  /// cursor semantics (before_id → id < ?); [unreadOnly] mirrors the
  /// server-side unread filter. Rows come back shaped like the
  /// server's feed rows (is_unread from the local column, so
  /// optimistic flips survive restarts).
  Future<List<Map<String, dynamic>>> getCachedNotifications({
    bool unreadOnly = false,
    int? beforeId,
    int limit = 40,
  }) async {
    final db = await database;
    final where = <String>[
      if (unreadOnly) 'is_unread = 1',
      if (beforeId != null && beforeId > 0) 'id < ?',
    ];
    final rows = await db.query(
      'cached_notifications',
      where: where.isEmpty ? null : where.join(' AND '),
      whereArgs: [
        if (beforeId != null && beforeId > 0) beforeId,
      ],
      orderBy: 'id DESC',
      limit: limit,
    );
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['is_unread'] = (row['is_unread'] as int? ?? 0) == 1 ? 1 : 0;
        decoded['local_fetched_at'] = row['fetched_at'];
        return decoded;
      } catch (_) {
        // Corrupt/missing blob — identify the row honestly, never
        // fabricate content.
        return <String, dynamic>{
          'id': row['id'],
          'is_unread': (row['is_unread'] as int? ?? 0) == 1 ? 1 : 0,
          'local_fetched_at': row['fetched_at'],
        };
      }
    }).toList();
  }

  /// P1-B optimistic read persistence: the flip survives restart and
  /// offline browsing; the server reconciles on the next successful
  /// refresh (and reverts it there if the mark-read write never
  /// landed — the existing revert-by-refetch semantics).
  Future<void> markCachedNotificationRead(int id) async {
    final db = await database;
    await db.update('cached_notifications', {'is_unread': 0},
        where: 'id = ?', whereArgs: [id]);
  }

  /// Merge-upsert announcement rows by server id; expires_at is
  /// stored verbatim (server-authoritative — no local TTL).
  Future<void> cacheAnnouncementRows(List<Map<String, dynamic>> rows) async {
    final db = await database;
    final batch = db.batch();
    final now = DateTime.now().toIso8601String();
    for (final m in rows) {
      batch.insert(
        'cached_announcements',
        {
          'id': m['id'],
          'is_pinned': (m['is_pinned'] ?? 0) == 1 ? 1 : 0,
          'is_unread': (m['is_unread'] ?? 0) == 1 ? 1 : 0,
          'expires_at': m['expires_at'],
          'data_json': jsonEncode(m),
          'fetched_at': now,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
    await batch.commit(noResult: true);
  }

  /// Local announcements page — the server's exact ordering
  /// (is_pinned DESC, id DESC), its tuple cursor semantics, and its
  /// retention rule: a cached announcement whose stored server
  /// expires_at has passed is never displayed (and never modified —
  /// the next successful refresh stays authoritative).
  Future<List<Map<String, dynamic>>> getCachedAnnouncements({
    int? beforePin,
    int? beforeId,
    int limit = 40,
  }) async {
    final db = await database;
    final now = _localMysqlStyleNow();
    final where = <String>[
      "(expires_at IS NULL OR expires_at > '$now')",
    ];
    final args = <dynamic>[];
    if (beforeId != null && beforeId > 0 && beforePin != null) {
      // (is_pinned, id) < (beforePin, beforeId) — the server's tuple
      // cursor, written in the classic OR form (no row-value syntax
      // dependency).
      where.add('(is_pinned < ? OR (is_pinned = ? AND id < ?))');
      args..add(beforePin)..add(beforePin)..add(beforeId);
    }
    final rows = await db.query(
      'cached_announcements',
      where: where.join(' AND '),
      whereArgs: args,
      orderBy: 'is_pinned DESC, id DESC',
      limit: limit,
    );
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['is_unread'] = (row['is_unread'] as int? ?? 0) == 1 ? 1 : 0;
        decoded['is_pinned'] = (row['is_pinned'] as int? ?? 0) == 1 ? 1 : 0;
        decoded['local_fetched_at'] = row['fetched_at'];
        return decoded;
      } catch (_) {
        return <String, dynamic>{
          'id': row['id'],
          'is_unread': (row['is_unread'] as int? ?? 0) == 1 ? 1 : 0,
          'is_pinned': (row['is_pinned'] as int? ?? 0) == 1 ? 1 : 0,
          'local_fetched_at': row['fetched_at'],
        };
      }
    }).toList();
  }

  Future<void> markCachedAnnouncementRead(int id) async {
    final db = await database;
    await db.update('cached_announcements', {'is_unread': 0},
        where: 'id = ?', whereArgs: [id]);
  }

  /// Device-local 'YYYY-MM-DD HH:MM:SS' — the server DATETIME string
  /// shape — for the offline expiry comparison. Device clock/TZ skew
  /// vs the server is the accepted edge (audit §22); the next
  /// successful refresh remains authoritative.
  static String _localMysqlStyleNow() {
    final n = DateTime.now();
    String p2(int v) => v.toString().padLeft(2, '0');
    return '${n.year}-${p2(n.month)}-${p2(n.day)} '
        '${p2(n.hour)}:${p2(n.minute)}:${p2(n.second)}';
  }

  // ============================================================
  // P1-D: CACHED REVIEW INBOX (read model, dept-scoped)
  // ============================================================

  /// Merge-upsert list rows by (dept, id). Merge-only: review
  /// packets never disappear through the review workflow itself
  /// (deletion exists only via web-console class/subject removal —
  /// disclosed deletion-blindness, same class as P1-B), so nothing
  /// is ever deleted here and repeated refreshes cannot duplicate.
  Future<void> cacheReviewPackets(
      String dept, List<Map<String, dynamic>> rows) async {
    final db = await database;
    final batch = db.batch();
    final now = DateTime.now().toIso8601String();
    for (final m in rows) {
      batch.insert(
        'cached_review_packets',
        {
          'dept': dept,
          'id': m['id'],
          'status': '${m['status'] ?? ''}',
          'updated_at': '${m['updated_at'] ?? ''}',
          'data_json': jsonEncode(m),
          'fetched_at': now,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    }
    await batch.commit(noResult: true);
  }

  /// Local review window — the server's exact ordering
  /// (updated_at DESC, id DESC — every department service sorts this
  /// way) with the server's per-filter semantics supplied by the
  /// caller as a status set ([statusIn] null = the 'all' window).
  Future<List<Map<String, dynamic>>> getCachedReviewPackets(String dept,
      {List<String>? statusIn, int limit = 50}) async {
    final db = await database;
    final where = <String>['dept = ?'];
    final args = <dynamic>[dept];
    if (statusIn != null && statusIn.isNotEmpty) {
      where.add('status IN (${List.filled(statusIn.length, '?').join(', ')})');
      args.addAll(statusIn);
    }
    final rows = await db.query('cached_review_packets',
        where: where.join(' AND '),
        whereArgs: args,
        orderBy: 'updated_at DESC, id DESC',
        limit: limit);
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['local_fetched_at'] = row['fetched_at'];
        return decoded;
      } catch (_) {
        // Corrupt/missing blob — identify the row honestly, never
        // fabricate content.
        return <String, dynamic>{
          'dept': row['dept'],
          'id': row['id'],
          'status': row['status'],
          'updated_at': row['updated_at'],
          'local_fetched_at': row['fetched_at'],
        };
      }
    }).toList();
  }

  /// Cache one detail payload (roster rows included) — separate
  /// table so a list refresh can never clobber it.
  Future<void> cacheReviewPacketDetail(
      String dept, int id, Map<String, dynamic> payload) async {
    final db = await database;
    await db.insert(
      'cached_review_packet_details',
      {
        'dept': dept,
        'id': id,
        'data_json': jsonEncode(payload),
        'fetched_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<Map<String, dynamic>?> getCachedReviewPacketDetail(
      String dept, int id) async {
    final db = await database;
    try {
      final rows = await db.query('cached_review_packet_details',
          where: 'dept = ? AND id = ?', whereArgs: [dept, id], limit: 1);
      if (rows.isEmpty) return null;
      final raw = rows.first['data_json'] as String?;
      if (raw == null || raw.isEmpty) return null;
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        decoded['local_fetched_at'] = rows.first['fetched_at'];
        return decoded;
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  /// Stats are server aggregates over the department's FULL queue —
  /// a different population than any cached row window. Stored
  /// verbatim per department; NEVER recomputed from cached rows.
  Future<void> cacheReviewStats(
      String dept, Map<String, dynamic> stats) async {
    final db = await database;
    await db.insert(
      'cached_review_stats',
      {
        'dept': dept,
        'stats_json': jsonEncode(stats),
        'fetched_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<Map<String, dynamic>?> getCachedReviewStats(String dept) async {
    final db = await database;
    try {
      final rows = await db.query('cached_review_stats',
          where: 'dept = ?', whereArgs: [dept], limit: 1);
      if (rows.isEmpty) return null;
      final raw = rows.first['stats_json'] as String?;
      if (raw == null || raw.isEmpty) return null;
      final decoded = jsonDecode(raw);
      return decoded is Map<String, dynamic> ? decoded : null;
    } catch (_) {
      return null;
    }
  }

  // ============================================================
  // P1-E: CACHED EDU CLASSES (education read model)
  // ============================================================

  /// Replace-on-success snapshot of the Education class list.
  /// GET /classes returns the COMPLETE active class set for the
  /// signed-in scope, so this delete + insert in ONE transaction
  /// propagates renames, deactivations and eligible hard deletes on
  /// every successful refresh — deliberately NOT P1-D's merge-only
  /// model (a merge could never drop a deactivated class). Callers
  /// invoke this only AFTER a successful server response, so a
  /// failed or offline refresh never reaches this write and cached
  /// rows always survive failures. An empty-but-valid class set
  /// replaces too (a scope with zero active classes is honest
  /// emptiness, not a failure).
  Future<void> replaceCachedEduClasses(
      List<Map<String, dynamic>> rows) async {
    final db = await database;
    final now = DateTime.now().toIso8601String();
    await db.transaction((txn) async {
      await txn.delete('cached_edu_classes');
      for (final m in rows) {
        await txn.insert('cached_edu_classes', {
          'id': m['id'],
          'class_name': '${m['class_name'] ?? ''}',
          'class_name_en':
              m['class_name_en'] == null ? null : '${m['class_name_en']}',
          'level_order': _asIntLocal(m['level_order']),
          'student_count': _asIntLocal(m['student_count']),
          'data_json': jsonEncode(m),
          'fetched_at': now,
        });
      }
    });
  }

  /// Local Education class list — the server's exact ordering
  /// (level_order, class_name), never an alphabetical-only
  /// reconstruction (the shared cached_classes cannot express this).
  /// Rows come back shaped like the server's class items plus a
  /// local_fetched_at stamp for the '· updated HH:MM' banner.
  Future<List<Map<String, dynamic>>> getCachedEduClasses() async {
    final db = await database;
    final rows = await db.query('cached_edu_classes',
        orderBy: 'level_order, class_name');
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['local_fetched_at'] = row['fetched_at'];
        return decoded;
      } catch (_) {
        // Corrupt/missing blob — identify the row honestly from its
        // discrete columns, never fabricate content.
        return <String, dynamic>{
          'id': row['id'],
          'class_name': row['class_name'],
          'class_name_en': row['class_name_en'],
          'level_order': row['level_order'],
          'student_count': row['student_count'],
          'local_fetched_at': row['fetched_at'],
        };
      }
    }).toList();
  }

  /// Replace-on-success roster for ONE class — the /classes/{id}/
  /// students response stored verbatim (students + count + year
  /// metadata). Per-class keying means refreshing class A never
  /// touches class B, and merely SELECTING another class never
  /// clears anything: each roster is isolated by class_id. Called
  /// only after a successful response, so failures never write.
  /// Class membership comes ONLY from this endpoint — never derived
  /// from cached_members (a directory is not a relationship).
  Future<void> cacheEduClassRoster(
      int classId, Map<String, dynamic> payload) async {
    final db = await database;
    await db.insert(
      'cached_edu_class_rosters',
      {
        'class_id': classId,
        'roster_year_id': payload['roster_year_id'] == null
            ? null
            : _asIntLocal(payload['roster_year_id']),
        'roster_year_name': payload['roster_year_name'] == null
            ? null
            : '${payload['roster_year_name']}',
        'roster_fallback':
            (payload['roster_fallback'] == true ||
                    payload['roster_fallback'] == 1 ||
                    payload['roster_fallback'] == '1')
                ? 1
                : 0,
        'data_json': jsonEncode(payload),
        'fetched_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// Cached roster for one class, or null when it was never fetched.
  /// Returns the server response shape (class_id / students / count
  /// / roster_year_id / roster_year_name / roster_fallback) plus a
  /// local_fetched_at stamp. The year metadata is read back verbatim
  /// — the current-year vs most-populated-prior-year resolution is
  /// server contract and is never re-resolved locally.
  Future<Map<String, dynamic>?> getCachedEduClassRoster(int classId) async {
    final db = await database;
    try {
      final rows = await db.query('cached_edu_class_rosters',
          where: 'class_id = ?', whereArgs: [classId], limit: 1);
      if (rows.isEmpty) return null;
      final raw = rows.first['data_json'] as String?;
      if (raw == null || raw.isEmpty) return null;
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        decoded['local_fetched_at'] = rows.first['fetched_at'];
        return decoded;
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  // ============================================================
  // P1-F: CACHED EDU SUBJECTS (education read model)
  // ============================================================

  /// Replace-on-success snapshot of the Education subject catalog.
  /// GET /subjects returns the COMPLETE active set (no pagination),
  /// so this delete + insert in ONE transaction propagates renames,
  /// deactivations and eligible hard deletes on every successful
  /// refresh. Called only AFTER a successful server response; a
  /// failed or offline refresh never reaches this write. An
  /// empty-but-valid catalog replaces too (zero active subjects is
  /// honest emptiness, not a failure).
  Future<void> replaceCachedEduSubjects(
      List<Map<String, dynamic>> rows) async {
    final db = await database;
    final now = DateTime.now().toIso8601String();
    await db.transaction((txn) async {
      await txn.delete('cached_edu_subjects');
      for (final m in rows) {
        await txn.insert('cached_edu_subjects', {
          'id': m['id'],
          'subject_name': '${m['subject_name'] ?? ''}',
          'subject_name_en':
              m['subject_name_en'] == null ? null : '${m['subject_name_en']}',
          'subject_code':
              m['subject_code'] == null ? null : '${m['subject_code']}',
          'class_count': _asIntLocal(m['class_count']),
          'data_json': jsonEncode(m),
          'fetched_at': now,
        });
      }
    });
  }

  /// Local Education subject catalog — the server's ordering
  /// (ORDER BY subject_name under MySQL utf8mb4_unicode_ci;
  /// COLLATE NOCASE approximates the case-insensitive fold for
  /// ASCII — Amharic orders code-point-identically). Never the
  /// teacher grade-bootstrap cached_subjects (different identity:
  /// (id, class_id) composite PK from /grades/bootstrap). Rows come
  /// back shaped like the server's items plus a local_fetched_at
  /// stamp for the '· updated HH:MM' banner. class_count is the
  /// server's aggregate, read verbatim — never recomputed locally.
  Future<List<Map<String, dynamic>>> getCachedEduSubjects() async {
    final db = await database;
    final rows = await db.query('cached_edu_subjects',
        orderBy: 'subject_name COLLATE NOCASE');
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['local_fetched_at'] = row['fetched_at'];
        return decoded;
      } catch (_) {
        // Corrupt/missing blob — identify the row honestly from its
        // discrete columns, never fabricate content.
        return <String, dynamic>{
          'id': row['id'],
          'subject_name': row['subject_name'],
          'subject_name_en': row['subject_name_en'],
          'subject_code': row['subject_code'],
          'class_count': row['class_count'],
          'local_fetched_at': row['fetched_at'],
        };
      }
    }).toList();
  }

  // ============================================================
  // P1-G: CACHED EDU TEACHERS + YEAR-SCOPED DETAILS
  // ============================================================

  /// Metadata for the last COMPLETE teacher-directory crawl. A present
  /// row with total=0 is a valid empty server snapshot, not "never cached".
  Future<Map<String, dynamic>?> getCachedEduTeacherSnapshot() async {
    final db = await database;
    final rows = await db.query('cached_edu_teacher_snapshot',
        where: 'id = ?', whereArgs: [1], limit: 1);
    return rows.isEmpty ? null : Map<String, dynamic>.from(rows.first);
  }

  /// Replace the complete active-teacher directory only after EVERY server
  /// page has succeeded and the caller has validated stable pagination,
  /// year scope and identity uniqueness. The transaction also invalidates
  /// details for disappeared teachers and obsolete academic-year scopes.
  Future<void> replaceCachedEduTeachers(
    List<Map<String, dynamic>> rows, {
    required int academicYearId,
    String? academicYearName,
    required int total,
  }) async {
    final db = await database;
    final now = DateTime.now().toIso8601String();
    await db.transaction((txn) async {
      await txn.delete('cached_edu_teachers');
      for (var i = 0; i < rows.length; i++) {
        final m = rows[i];
        await txn.insert('cached_edu_teachers', {
          'id': _asIntLocal(m['id']),
          'username': '${m['username'] ?? ''}',
          'full_name': '${m['full_name'] ?? ''}',
          'is_active': _asIntLocal(m['is_active']),
          'created_at':
              m['created_at'] == null ? null : '${m['created_at']}',
          'assigned_classes': _asIntLocal(m['assigned_classes']),
          'assigned_subjects': _asIntLocal(m['assigned_subjects']),
          'sort_order': i,
          'data_json': jsonEncode(m),
          'fetched_at': now,
        });
      }
      await txn.insert(
        'cached_edu_teacher_snapshot',
        {
          'id': 1,
          'academic_year_id': academicYearId,
          'academic_year_name': academicYearName,
          'total': total,
          'fetched_at': now,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
      await txn.delete('cached_edu_teacher_details',
          where: 'academic_year_id != ?', whereArgs: [academicYearId]);
      await txn.rawDelete('''
        DELETE FROM cached_edu_teacher_details
        WHERE NOT EXISTS (
          SELECT 1 FROM cached_edu_teachers t
          WHERE t.id = cached_edu_teacher_details.teacher_id
        )
      ''');
    });
  }

  /// Search the COMPLETE cached directory locally, but retain the current
  /// screen's 50-result display cap. sort_order reproduces the server's
  /// ORDER BY u.full_name across the validated page crawl.
  Future<List<Map<String, dynamic>>> getCachedEduTeachers({
    String? search,
    int limit = 50,
  }) async {
    final db = await database;
    final q = search?.trim() ?? '';
    final rows = await db.query(
      'cached_edu_teachers',
      where: q.isEmpty ? null : '(full_name LIKE ? OR username LIKE ?)',
      whereArgs: q.isEmpty ? null : ['%$q%', '%$q%'],
      orderBy: 'sort_order ASC',
      limit: limit,
    );
    return rows.map((row) {
      try {
        final decoded =
            jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
        decoded['local_fetched_at'] = row['fetched_at'];
        decoded['local_sort_order'] = row['sort_order'];
        return decoded;
      } catch (_) {
        // Corrupt/missing blob: preserve only discrete server-derived fields.
        return <String, dynamic>{
          'id': row['id'],
          'username': row['username'],
          'full_name': row['full_name'],
          'is_active': row['is_active'],
          'created_at': row['created_at'],
          'assigned_classes': row['assigned_classes'],
          'assigned_subjects': row['assigned_subjects'],
          'local_fetched_at': row['fetched_at'],
          'local_sort_order': row['sort_order'],
        };
      }
    }).toList();
  }

  /// Cache one successfully validated teacher detail under the explicit
  /// server academic-year scope. A valid empty assignments list is stored;
  /// transport/server/malformed failures never call this method.
  Future<void> cacheEduTeacherDetail(
    Map<String, dynamic> detail, {
    required int academicYearId,
    String? academicYearName,
  }) async {
    final db = await database;
    await db.insert(
      'cached_edu_teacher_details',
      {
        'teacher_id': _asIntLocal(detail['id']),
        'academic_year_id': academicYearId,
        'academic_year_name': academicYearName,
        'data_json': jsonEncode(detail),
        'fetched_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<Map<String, dynamic>?> getCachedEduTeacherDetail(
      int teacherId, int academicYearId) async {
    final db = await database;
    final rows = await db.query(
      'cached_edu_teacher_details',
      where: 'teacher_id = ? AND academic_year_id = ?',
      whereArgs: [teacherId, academicYearId],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    final row = rows.first;
    try {
      final decoded =
          jsonDecode(row['data_json'] as String) as Map<String, dynamic>;
      decoded['local_fetched_at'] = row['fetched_at'];
      decoded['local_academic_year_id'] = row['academic_year_id'];
      decoded['local_academic_year_name'] = row['academic_year_name'];
      return decoded;
    } catch (_) {
      // Never fabricate assignments from a corrupt detail blob.
      return null;
    }
  }

  // ============================================================
  // CACHED CLASSES
  // ============================================================

  Future<void> cacheClasses(List<dynamic> classes) async {
    final db = await database;
    final batch = db.batch();
    batch.delete('cached_classes');
    for (final c in classes) {
      batch.insert('cached_classes', {
        'id': c['id'],
        'class_name': c['class_name'] ?? '',
        'class_name_en': c['class_name_en'] ?? '',
        'section': c['section'] ?? c['section_name'] ?? '',
        'student_count': c['student_count'] ?? 0,
        'updated_at': DateTime.now().toIso8601String(),
      });
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedClasses() async {
    final db = await database;
    return await db.query('cached_classes', orderBy: 'class_name');
  }

  // ============================================================
  // CACHED STUDENTS
  // ============================================================

  Future<void> cacheStudents(int classId, List<dynamic> students) async {
    // Capture before the first await: later caller mutations are not a refresh.
    // This is a read cache only, not authorized preparation or offline readiness.
    final updatedAt = DateTime.now().toIso8601String();
    final rows = students.map((s) => <String, Object?>{
      'member_id': s['member_id'] ?? s['id'],
      'class_id': classId,
      'student_name': s['student_name'] ?? '',
      'father_name': s['father_name'] ?? '',
      'member_code': s['member_code'] ?? '',
      'gender': s['gender'] ?? '',
      'updated_at': updatedAt,
    }).toList(growable: false);
    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('cached_students', where: 'class_id = ?',
          whereArgs: [classId]);
      final batch = txn.batch();
      for (final row in rows) {
        batch.insert('cached_students', row);
      }
      // Failure propagates and rolls back the delete as well as every insert.
      await batch.commit(noResult: true);
    });
  }

  Future<List<Map<String, dynamic>>> getCachedStudents(int classId) async {
    final db = await database;
    return await db.query('cached_students',
        where: 'class_id = ?', whereArgs: [classId], orderBy: 'student_name');
  }

  // ============================================================
  // CACHED SUBJECTS
  // ============================================================

  Future<void> cacheSubjects(int classId, List<dynamic> subjects) async {
    final db = await database;
    await db
        .delete('cached_subjects', where: 'class_id = ?', whereArgs: [classId]);
    final batch = db.batch();
    for (final s in subjects) {
      batch.insert(
          'cached_subjects',
          {
            'id': s['id'],
            'class_id': classId,
            'subject_name': s['subject_name'] ?? '',
            'subject_name_en': s['subject_name_en'] ?? '',
            'subject_code': s['subject_code'] ?? '',
            'updated_at': DateTime.now().toIso8601String(),
          },
          conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedSubjects(int classId) async {
    final db = await database;
    return await db.query('cached_subjects',
        where: 'class_id = ?', whereArgs: [classId], orderBy: 'subject_name');
  }

  // ============================================================
  // CACHED ASSESSMENTS
  // ============================================================

  Future<void> cacheAssessments(
      int classId, int subjectId, List<dynamic> assessments) async {
    final db = await database;
    await db.delete('cached_assessments',
        where: 'class_id = ? AND subject_id = ?',
        whereArgs: [classId, subjectId]);
    final batch = db.batch();
    for (final a in assessments) {
      batch.insert(
          'cached_assessments',
          {
            'id': a['id'],
            'class_id': classId,
            'subject_id': subjectId,
            'assessment_name': a['assessment_name'] ?? '',
            'assessment_type': a['assessment_type'] ?? 'test',
            'max_score': a['max_score'] ?? 100,
            'weight_percentage': a['weight_percentage'] ?? 100,
            'grades_entered': a['grades_entered'] ?? 0,
            'updated_at': DateTime.now().toIso8601String(),
          },
          conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedAssessments(
      int classId, int subjectId) async {
    final db = await database;
    return await db.query('cached_assessments',
        where: 'class_id = ? AND subject_id = ?',
        whereArgs: [classId, subjectId],
        orderBy: 'id');
  }

  // ============================================================
  // LEGACY OUTBOX OPERATION PRIMITIVES (schema v34)
  // ============================================================

  LegacyOutboxTableSpec _legacySpecFor(LegacyOperationKind kind) {
    final table = switch (kind) {
      LegacyOperationKind.attendance => 'pending_attendance',
      LegacyOperationKind.grades => 'pending_grades',
      LegacyOperationKind.hr => 'pending_hr',
    };
    return legacyOutboxTableSpecs.firstWhere((spec) => spec.table == table);
  }

  Map<String, Object?> _legacyNaturalKey(
    LegacyOutboxTableSpec spec,
    Map<String, Object?> row,
  ) {
    return {
      for (final column in spec.businessKeyColumns) column: row[column],
    };
  }

  (String, List<Object?>) _legacyExactWhere(
    LegacyOutboxTableSpec spec,
    LegacyClaimSnapshot claim,
  ) {
    final operation = claim.operation;
    final clauses = <String>[
      'client_op_id = ?',
      'synced = 0',
      "sync_state = 'in_flight'",
      'last_attempt_at = ?',
      'owner_user_id = ?',
      'created_authorization_version = ?',
    ];
    final args = <Object?>[
      operation.clientOpId,
      claim.claimedAt.toUtc().toIso8601String(),
      operation.ownerUserId,
      operation.createdAuthorizationVersion,
    ];
    for (final column in spec.businessKeyColumns) {
      clauses.add('$column = ?');
      args.add(operation.naturalKey[column]);
    }
    return (clauses.join(' AND '), args);
  }

  Future<void> pauseLegacyInFlightForAuthentication({
    required int ownerUserId,
    required int authorizationVersion,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      for (final spec in legacyOutboxTableSpecs) {
        await txn.update(
          spec.table,
          {
            'sync_state': 'paused_auth',
            'next_attempt_at': null,
            'failure_code': 'AUTHENTICATION_REQUIRED',
            'sync_error': 'Sign in to continue sending this work.',
          },
          where: "synced = 0 AND sync_state = 'in_flight' "
              'AND owner_user_id = ? '
              'AND created_authorization_version = ?',
          whereArgs: [ownerUserId, authorizationVersion],
        );
      }
      await txn.update(
        'comm_outbox',
        {
          'state': 'paused_auth',
          'next_attempt_at': null,
          'failure_code': 'AUTHENTICATION_REQUIRED',
          'fail_reason': 'Sign in to continue sending this message.',
        },
        where: "state = 'in_flight' AND owner_user_id = ? "
            'AND created_authorization_version = ?',
        whereArgs: [ownerUserId, authorizationVersion],
      );
    });
  }

  Future<void> resumeLegacyPausedAuthentication({
    required int ownerUserId,
    required int authorizationVersion,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      for (final spec in legacyOutboxTableSpecs) {
        await txn.update(
          spec.table,
          {
            'sync_state': 'pending',
            'next_attempt_at': null,
            'failure_code': null,
            'failure_http_status': null,
            'sync_error': null,
          },
          where: "synced = 0 AND sync_state = 'paused_auth' "
              'AND owner_user_id = ? '
              'AND created_authorization_version = ?',
          whereArgs: [ownerUserId, authorizationVersion],
        );
      }
      await txn.update(
        'comm_outbox',
        {
          'state': 'pending',
          'next_attempt_at': null,
          'failure_code': null,
          'failure_http_status': null,
          'fail_reason': null,
        },
        where: "state = 'paused_auth' AND owner_user_id = ? "
            'AND created_authorization_version = ?',
        whereArgs: [ownerUserId, authorizationVersion],
      );
    });
  }

  // Shared with scheduling/status: a blocked newer generation is not due.
  static const _attendanceClaimEligibilityWhere =
      "AND client_op_id IS NOT NULL AND TRIM(client_op_id) <> '' "
      "AND NOT EXISTS (SELECT 1 FROM pending_attendance older "
      "WHERE older.class_id = pending_attendance.class_id "
      "AND older.date = pending_attendance.date "
      "AND older.owner_user_id = pending_attendance.owner_user_id "
      "AND older.id < pending_attendance.id AND older.synced = 0 "
      "AND COALESCE(older.client_op_id, '') <> pending_attendance.client_op_id "
      "AND COALESCE(older.sync_state, '') <> 'resolved_conflict') ";

  /// Atomically claims and snapshots one due operation. The returned immutable
  /// snapshot is the only object that may cross the HTTP boundary.
  Future<LegacyClaimSnapshot?> claimNextLegacyOperation({
    required LegacyOperationKind kind,
    required int ownerUserId,
    required int authorizationVersion,
    required int runtimeGeneration,
    DateTime? now,
    SyncExecutionSource executionSource = SyncExecutionSource.foreground,
  }) async {
    final db = await database;
    final spec = _legacySpecFor(kind);
    final claimedAt = (now ?? DateTime.now()).toUtc();
    final claimedAtText = claimedAt.toIso8601String();
    // Probed on the outer handle: using `db` inside `db.transaction` would
    // deadlock sqflite, which serialises access to the single connection.
    final hasAttemptLedger = await _tableExists(db, 'sync_attempts');

    return db.transaction((txn) async {
      final sessionMatches = await activeSessionMatches(
        runtimeGeneration: runtimeGeneration,
        ownerUserId: ownerUserId,
        authorizationVersion: authorizationVersion,
        executor: txn,
      );
      if (!sessionMatches) return null;
      // A newer attendance intent may be saved while an older one is in
      // flight. Never send it ahead of that unresolved same-owner sheet.
      final attendanceOrder = kind == LegacyOperationKind.attendance
          ? _attendanceClaimEligibilityWhere : '';
      final candidates = await txn.rawQuery(
        'SELECT client_op_id, MIN(id) AS first_id '
        'FROM ${spec.table} '
        'WHERE synced = 0 '
        "AND sync_state IN ('pending', 'retry_wait') "
        'AND (next_attempt_at IS NULL OR next_attempt_at <= ?) '
        'AND owner_user_id = ? '
        'AND created_authorization_version = ? '
        "AND client_op_id IS NOT NULL AND TRIM(client_op_id) <> '' $attendanceOrder"
        'GROUP BY client_op_id '
        'ORDER BY MIN(created_at), MIN(id) LIMIT 1',
        [claimedAtText, ownerUserId, authorizationVersion],
      );
      if (candidates.isEmpty) return null;
      final clientOpId = '${candidates.first['client_op_id']}';
      final rows = await txn.query(
        spec.table,
        where: 'client_op_id = ? AND synced = 0 AND owner_user_id = ? '
            'AND created_authorization_version = ?',
        whereArgs: [clientOpId, ownerUserId, authorizationVersion],
        orderBy: 'id',
      );
      if (rows.isEmpty) return null;

      final naturalKeys = <String>{};
      final packetKinds = <String>{};
      final states = <String>{};
      for (final row in rows) {
        naturalKeys.add(_legacyBusinessKey(spec, row));
        packetKinds.add(_legacyPacketKind(row));
        states.add('${row['sync_state']}');
        if (row['owner_user_id'] != ownerUserId ||
            row['created_authorization_version'] != authorizationVersion) {
          return null;
        }
      }
      if (naturalKeys.length != 1 ||
          packetKinds.length != 1 ||
          !const {'draft', 'submitted'}.contains(packetKinds.single) ||
          states.length != 1 ||
          !const {'pending', 'retry_wait'}.contains(states.single)) {
        return null;
      }

      final naturalKey = _legacyNaturalKey(spec, rows.first);
      final keyClauses = <String>[];
      final updateArgs = <Object?>[claimedAtText, clientOpId, states.single];
      for (final column in spec.businessKeyColumns) {
        keyClauses.add('$column = ?');
        updateArgs.add(naturalKey[column]);
      }
      updateArgs.add(ownerUserId);
      updateArgs.add(authorizationVersion);
      final affected = await txn.rawUpdate(
        "UPDATE ${spec.table} SET sync_state = 'in_flight', "
        'attempt_count = attempt_count + 1, last_attempt_at = ?, '
        'next_attempt_at = NULL '
        'WHERE client_op_id = ? AND synced = 0 AND sync_state = ? '
        'AND ${keyClauses.join(' AND ')} '
        'AND owner_user_id = ? AND created_authorization_version = ?',
        updateArgs,
      );
      if (affected != rows.length) {
        throw StateError('Legacy operation claim was not atomic.');
      }

      final snapshotWhere = <String>[
        'client_op_id = ?',
        'synced = 0',
        "sync_state = 'in_flight'",
        ...keyClauses,
        'owner_user_id = ?',
        'created_authorization_version = ?',
      ].join(' AND ');
      final claimedRows = await txn.query(
        spec.table,
        where: snapshotWhere,
        whereArgs: [
          clientOpId,
          for (final column in spec.businessKeyColumns) naturalKey[column],
          ownerUserId,
          authorizationVersion,
        ],
        orderBy: 'id',
      );
      if (claimedRows.length != rows.length) {
        throw StateError('Legacy operation snapshot was not coherent.');
      }
      final attemptCount = int.tryParse(
            '${claimedRows.first['attempt_count'] ?? 0}',
          ) ??
          0;
      final packetKind = packetKinds.single == 'submitted'
          ? LegacyPacketKind.submitted
          : LegacyPacketKind.draft;

      // S1 lineage: one ledger row per real transmission, written in the
      // claim transaction so a process death cannot lose the fact that an
      // attempt started. attempt_number is the row's own attempt_count.
      final attemptUid = newAttemptUid();
      if (hasAttemptLedger) {
        await _openSyncAttempt(
          txn,
          clientOpId: clientOpId,
          attemptNumber: attemptCount,
          attemptUid: attemptUid,
          domain: spec.table,
          startedAt: claimedAt,
          entityRef: naturalKey,
          ownerUserId: ownerUserId,
          authorizationVersion: authorizationVersion,
          executionSource: executionSource,
        );
      }

      return LegacyClaimSnapshot(
        operation: LegacyOperationRef(
          kind: kind,
          originalAcademicYearId: kind == LegacyOperationKind.attendance
              ? coherentAttendanceOriginalYear(claimedRows) : null,
          naturalKey: naturalKey,
          clientOpId: clientOpId,
          packetKind: packetKind,
          ownerUserId: ownerUserId,
          createdAuthorizationVersion: authorizationVersion,
          runtimeGeneration: runtimeGeneration,
        ),
        records: claimedRows,
        claimedAt: claimedAt,
        attemptCount: attemptCount,
        attemptUid: attemptUid,
      );
    });
  }

  /// Settles only the exact claimed generation. A replacement, wrong state,
  /// owner/scope mismatch, or stale runtime generation changes no row.
  Future<LegacySettlementResult> settleLegacyOperation({
    required LegacyClaimSnapshot claim,
    required LegacySettlement settlement,
    required int currentOwnerUserId,
    required int currentAuthorizationVersion,
    required int currentRuntimeGeneration,
    SyncAttemptClosure? attemptClosure,
    DateTime? now,
  }) async {
    final operation = claim.operation;
    if (operation.runtimeGeneration != currentRuntimeGeneration ||
        operation.ownerUserId != currentOwnerUserId ||
        operation.createdAuthorizationVersion != currentAuthorizationVersion) {
      return LegacySettlementResult.supersededSession;
    }

    final db = await database;
    final spec = _legacySpecFor(operation.kind);
    final settledAt = (now ?? DateTime.now()).toUtc().toIso8601String();
    // Probed outside the transaction for the same reason as the claim path.
    final hasAttemptLedger = await _tableExists(db, 'sync_attempts');
    return db.transaction((txn) async {
      final sessionMatches = await activeSessionMatches(
        runtimeGeneration: currentRuntimeGeneration,
        ownerUserId: currentOwnerUserId,
        authorizationVersion: currentAuthorizationVersion,
        executor: txn,
      );
      if (!sessionMatches) {
        return LegacySettlementResult.supersededSession;
      }
      final exact = _legacyExactWhere(spec, claim);
      final before = await txn.rawQuery(
        'SELECT COUNT(*) AS count FROM ${spec.table} WHERE ${exact.$1}',
        exact.$2,
      );
      final matching = int.tryParse('${before.first['count'] ?? 0}') ?? 0;
      if (matching != claim.records.length) {
        return LegacySettlementResult.supersededLocal;
      }

      final values = <String, Object?>{
        'failure_code': settlement.failureCode,
        'failure_http_status': settlement.failureHttpStatus,
      };
      switch (settlement.kind) {
        case LegacySettlementKind.accepted:
          values.addAll({
            'sync_state': 'synced',
            'synced': 1,
            'synced_at': settledAt,
            'sync_error': null,
            'next_attempt_at': null,
            'failed_at': null,
          });
          break;
        case LegacySettlementKind.retryable:
          values.addAll({
            'sync_state': 'retry_wait',
            'next_attempt_at':
                (settlement.nextAttemptAt ?? DateTime.parse(settledAt))
                    .toUtc()
                    .toIso8601String(),
            'sync_error': settlement.failureMessage,
            'failed_at': null,
          });
          break;
        case LegacySettlementKind.needsAttention:
          values.addAll({
            'sync_state': 'needs_attention',
            'next_attempt_at': null,
            'sync_error': settlement.failureMessage,
            'failed_at': settledAt,
          });
          break;
        case LegacySettlementKind.pausedAuthentication:
          values.addAll({
            'sync_state': 'paused_auth',
            'next_attempt_at': null,
            'sync_error': settlement.failureMessage,
            'failed_at': null,
          });
          break;
        case LegacySettlementKind.pausedAuthorizationScope:
          values.addAll({
            'sync_state': 'paused_scope',
            'next_attempt_at': null,
            'sync_error': settlement.failureMessage,
            'failed_at': null,
          });
          break;
        case LegacySettlementKind.resolvedConflict:
          values.addAll({
            'sync_state': 'resolved_conflict',
            'next_attempt_at': null,
            'sync_error': settlement.failureMessage,
            'failed_at': settledAt,
          });
          break;
      }
      final affected = await txn.update(
        spec.table,
        values,
        where: exact.$1,
        whereArgs: exact.$2,
      );
      if (affected != matching) {
        throw StateError('Legacy operation settlement was not atomic.');
      }

      // S1 lineage: close this attempt in the same transaction that settles
      // the row. Only the matching open attempt is touched, so a later
      // success can never rewrite an earlier failure.
      if (attemptClosure != null && hasAttemptLedger) {
        await _closeSyncAttempt(
          txn,
          clientOpId: operation.clientOpId,
          attemptNumber: claim.attemptCount,
          closure: attemptClosure,
          finishedAt: DateTime.parse(settledAt),
        );
      }

      // Exact atomic receipt: when an attendance operation is accepted with a
      // server response, store the receipt in the same transaction that marks
      // the outbox row completed. Never outside the transaction.
      if (settlement.kind == LegacySettlementKind.accepted &&
          operation.kind == LegacyOperationKind.attendance &&
          settlement.receiptJson != null &&
          settlement.receiptJson!.isNotEmpty) {
        try {
          final nat = operation.naturalKey;
          final classId = nat['class_id'] is int
              ? nat['class_id'] as int
              : int.tryParse('${nat['class_id']}') ?? 0;
          final date = '${nat['date'] ?? ''}';
          // Idempotent replay must not overwrite the first receipt.
          await txn.rawInsert(
            'INSERT OR IGNORE INTO attendance_sync_receipt '
            '(client_op_id, owner_user_id, authorization_version, generation, class_id, date, http_status, receipt_json, server_request_id, created_at) '
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
              operation.clientOpId,
              operation.ownerUserId,
              operation.createdAuthorizationVersion,
              operation.runtimeGeneration,
              classId,
              date,
              settlement.httpStatus ?? 201,
              settlement.receiptJson,
              settlement.serverRequestId,
              settledAt,
            ],
          );
        } catch (_) {
          // Receipt write failure must not hide a successful business settlement.
          // The outbox row is already marked completed; the receipt can be
          // reconciled on the next pull. Never throw here.
        }
      }
      // Exact atomic receipt for grades (v42): same pattern, assessment-scoped.
      if (settlement.kind == LegacySettlementKind.accepted &&
          operation.kind == LegacyOperationKind.grades &&
          settlement.receiptJson != null &&
          settlement.receiptJson!.isNotEmpty) {
        try {
          final nat = operation.naturalKey;
          final assessmentId = nat['assessment_id'] is int
              ? nat['assessment_id'] as int
              : int.tryParse('${nat['assessment_id']}') ?? 0;
          await txn.rawInsert(
            'INSERT OR IGNORE INTO grades_sync_receipt '
            '(client_op_id, owner_user_id, authorization_version, generation, assessment_id, http_status, receipt_json, server_request_id, created_at) '
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
              operation.clientOpId,
              operation.ownerUserId,
              operation.createdAuthorizationVersion,
              operation.runtimeGeneration,
              assessmentId,
              settlement.httpStatus ?? 201,
              settlement.receiptJson,
              settlement.serverRequestId,
              settledAt,
            ],
          );
        } catch (_) {
          // Never hide business success.
        }
      }
      return LegacySettlementResult.applied;
    });
  }

  /// Converts only the exact, still-unclaimed submitted generation back to a
  /// fresh draft generation. It never falls back to a natural-key mutation.
  Future<SubmitUndoResult> undoSubmittedLegacyOperation(
    LegacyOperationRef operation,
  ) {
    if (operation.packetKind != LegacyPacketKind.submitted) {
      return Future.value(SubmitUndoResult.supersededLocal);
    }
    return _serializeLegacySave(() async {
      final db = await database;
      final spec = _legacySpecFor(operation.kind);
      return db.transaction((txn) async {
        Map<String, int> binding;
        int runtimeGeneration;
        try {
          binding = await requireActiveOwnerBinding(txn);
          runtimeGeneration = await _activeRuntimeGeneration(txn);
        } catch (_) {
          return SubmitUndoResult.supersededSession;
        }
        if (binding['owner_user_id'] != operation.ownerUserId ||
            binding['created_authorization_version'] !=
                operation.createdAuthorizationVersion ||
            runtimeGeneration != operation.runtimeGeneration) {
          return SubmitUndoResult.supersededSession;
        }

        final keyClauses = <String>[];
        final keyArgs = <Object?>[];
        for (final column in spec.businessKeyColumns) {
          keyClauses.add('$column = ?');
          keyArgs.add(operation.naturalKey[column]);
        }
        final baseWhere = 'synced = 0 AND ${keyClauses.join(' AND ')} '
            'AND owner_user_id = ? AND created_authorization_version = ?';
        final baseArgs = <Object?>[
          ...keyArgs,
          operation.ownerUserId,
          operation.createdAuthorizationVersion,
        ];
        final attendance = operation.kind == LegacyOperationKind.attendance;
        final latestOnly = attendance ? ' AND $_latestAttendanceGenerationWhere' : '';
        final rows = await txn.query(
          spec.table,
          columns: ['client_op_id', 'packet_kind', 'sync_state',
            'attempt_count', 'last_attempt_at'],
          where: '$baseWhere$latestOnly',
          whereArgs: baseArgs,
        );
        if (rows.isEmpty) return SubmitUndoResult.alreadyClaimed;
        final exact = rows.where((row) =>
            '${row['client_op_id']}' == operation.clientOpId &&
            '${row['packet_kind']}' == 'submitted');
        if (exact.isEmpty || exact.length != rows.length) {
          return SubmitUndoResult.supersededLocal;
        }
        if (attendance) {
          if (exact.any((row) => row['attempt_count'] != 0 ||
              row['last_attempt_at'] != null)) {
            return SubmitUndoResult.alreadyClaimed;
          }
          final attempts = await txn.query('sync_attempts', columns: ['id'],
              where: 'client_op_id = ?', whereArgs: [operation.clientOpId], limit: 1);
          if (attempts.isNotEmpty) return SubmitUndoResult.alreadyClaimed;
        }
        if (exact.any((row) => '${row['sync_state']}' == 'in_flight')) {
          return SubmitUndoResult.alreadyClaimed;
        }
        if (exact.any((row) => !const {'pending', 'retry_wait'}
            .contains('${row['sync_state']}'))) {
          return SubmitUndoResult.supersededLocal;
        }

        final freshId = newClientOpId();
        final affected = await txn.update(
          spec.table,
          {
            'packet_kind': 'draft',
            'client_op_id': freshId,
            'sync_state': 'pending',
            'attempt_count': 0,
            'next_attempt_at': null,
            'last_attempt_at': null,
            'failure_code': null,
            'failure_http_status': null,
            'failed_at': null,
            'sync_error': null,
            'created_at': DateTime.now().toUtc().toIso8601String(),
          },
          where: '$baseWhere AND client_op_id = ? '
              "AND packet_kind = 'submitted' "
              "AND sync_state IN ('pending', 'retry_wait')",
          whereArgs: [...baseArgs, operation.clientOpId],
        );
        if (affected != rows.length) {
          if (attendance) throw StateError('Attendance undo was not atomic.');
          return SubmitUndoResult.supersededLocal;
        }
        return SubmitUndoResult.applied;
      });
    });
  }

  Future<LegacyOperationRef> _replaceLegacyOperation({
    required LegacyOperationKind kind,
    required String table,
    required String naturalKeyWhere,
    required List<Object?> naturalKeyArgs,
    required Map<String, Object?> naturalKey,
    required String packetKind,
    DateTime? notBefore,
    required List<Map<String, Object?>> rows,
    LegacyWriteSession? expectedSession,
    int? originalAcademicYearId,
  }) {
    final memberIds = rows
        .map((row) => _asIntLocal(row['member_id']))
        .where((id) => id > 0)
        .toSet();
    if (rows.isEmpty || memberIds.length != rows.length) {
      throw ArgumentError(
          'A durable operation requires one row per valid member.');
    }
    return _serializeLegacySave(() async {
      final db = await database;
      final createdAt = DateTime.now().toUtc().toIso8601String();
      final normalizedKind =
          packetKind == 'submitted' ? 'submitted' : 'draft';
      final opId = newClientOpId();
      late Map<String, int> binding;
      late int runtimeGeneration;
      await db.transaction((txn) async {
        // Attendance captures belong to the issuing sheet, not a later login.
        // Check in this same transaction before any replacement can delete rows.
        if (expectedSession != null &&
            (!expectedSession.isValid ||
                !await activeSessionMatches(
                  runtimeGeneration: expectedSession.runtimeGeneration,
                  ownerUserId: expectedSession.ownerUserId,
                  authorizationVersion: expectedSession.authorizationVersion,
                  executor: txn,
                ))) {
          throw const StaleLegacyWriteSession();
        }
        binding = await requireActiveOwnerBinding(txn);
        runtimeGeneration = await _activeRuntimeGeneration(txn);
        // sqflite's delete builder disallows null whereArgs. Keep unknown
        // provenance explicit, not coerced to a sentinel year.
        final attendanceYearWhere = originalAcademicYearId == null
            ? "AND original_academic_year_id IS NULL "
            : "AND original_academic_year_id IS ? ";
        final replaceable = kind == LegacyOperationKind.attendance
            ? " AND sync_state = 'pending' AND attempt_count = 0 "
                "AND last_attempt_at IS NULL "
                "$attendanceYearWhere"
                "AND client_op_id IS NOT NULL AND TRIM(client_op_id) <> '' "
                "AND NOT EXISTS (SELECT 1 FROM pending_attendance sibling "
                "WHERE sibling.client_op_id = pending_attendance.client_op_id "
                "AND (sibling.synced IS NOT 0 OR sibling.sync_state IS NOT 'pending' "
                "OR sibling.attempt_count IS NOT 0 OR sibling.last_attempt_at IS NOT NULL "
                "OR sibling.original_academic_year_id IS NOT "
                "pending_attendance.original_academic_year_id)) "
                "AND NOT EXISTS (SELECT 1 FROM sync_attempts attempts "
                "WHERE attempts.client_op_id = pending_attendance.client_op_id)"
            : '';
        await txn.delete(
          table,
          where: '$naturalKeyWhere AND synced = 0 '
              'AND owner_user_id = ? '
              'AND created_authorization_version = ?$replaceable',
          whereArgs: [
            ...naturalKeyArgs,
            binding['owner_user_id'],
            binding['created_authorization_version'],
            if (kind == LegacyOperationKind.attendance && originalAcademicYearId != null)
              originalAcademicYearId,
          ],
        );
        final batch = txn.batch();
        for (final row in rows) {
          batch.insert(table, {
            ...row,
            if (kind == LegacyOperationKind.attendance)
              attendanceOriginalYearColumn: originalAcademicYearId,
            'packet_kind': normalizedKind,
            'client_op_id': opId,
            'synced': 0,
            'sync_state': 'pending',
            'attempt_count': 0,
            'next_attempt_at': notBefore?.toUtc().toIso8601String(),
            'last_attempt_at': null,
            'failure_code': null,
            'failure_http_status': null,
            'failed_at': null,
            'sync_error': null,
            'created_at': createdAt,
            ...binding,
          });
        }
        await batch.commit(noResult: true);
      });
      return LegacyOperationRef(
        kind: kind,
        originalAcademicYearId: originalAcademicYearId,
        naturalKey: naturalKey,
        clientOpId: opId,
        packetKind: normalizedKind == 'submitted'
            ? LegacyPacketKind.submitted
            : LegacyPacketKind.draft,
        ownerUserId: binding['owner_user_id']!,
        createdAuthorizationVersion:
            binding['created_authorization_version']!,
        runtimeGeneration: runtimeGeneration,
      );
    });
  }

  // ============================================================
  // PENDING ATTENDANCE
  // ============================================================

  Future<LegacyOperationRef> saveAttendanceLocal(int classId, String className, String date,
      List<Map<String, dynamic>> records,
      {required LegacyWriteSession expectedSession,
      int? originalAcademicYearId,
      String packetKind = 'draft', DateTime? notBefore}) async {
    if (originalAcademicYearId != null &&
        !isValidOriginalAttendanceYear(originalAcademicYearId)) {
      throw ArgumentError('Invalid original attendance academic year.');
    }
    const validStatuses = {'present', 'absent', 'late', 'excused'};
    if (records.isEmpty) {
      throw ArgumentError('Attendance records are required.');
    }
    for (final record in records) {
      final status = '${record['status'] ?? ''}'.trim().toLowerCase();
      final memberId = record['member_id'] is int
          ? record['member_id'] as int
          : int.tryParse('${record['member_id'] ?? ''}');
      if (memberId == null || memberId <= 0 || !validStatuses.contains(status)) {
        throw ArgumentError('Attendance must explicitly mark every student.');
      }
    }

    return _replaceLegacyOperation(
      kind: LegacyOperationKind.attendance,
      originalAcademicYearId: originalAcademicYearId,
      table: 'pending_attendance',
      naturalKeyWhere: 'class_id = ? AND date = ?',
      naturalKeyArgs: [classId, date],
      naturalKey: {'class_id': classId, 'date': date},
      packetKind: packetKind,
      expectedSession: expectedSession,
      notBefore: notBefore,
      rows: records
          .map((r) => <String, Object?>{
                'class_id': classId,
                'class_name': className,
                'date': date,
                'member_id': r['member_id'],
                'student_name': r['student_name'] ?? '',
                'father_name': r['father_name'] ?? '',
                'member_code': r['member_code'] ?? '',
                'status': '${r['status']}'.trim().toLowerCase(),
                'notes': r['notes'] ?? r['note'] ?? '',
              })
          .toList(),
    );
  }

  // Select across synced and unsynced history: an acknowledged newer intent
  // must not expose an older terminal/needs-review generation as new marks.
  static const _latestAttendanceGenerationWhere =
      'client_op_id IS (SELECT newest.client_op_id FROM pending_attendance newest '
      'WHERE newest.class_id = pending_attendance.class_id '
      'AND newest.date = pending_attendance.date '
      'AND newest.owner_user_id = pending_attendance.owner_user_id '
      'AND newest.created_authorization_version = pending_attendance.created_authorization_version '
      'ORDER BY newest.id DESC LIMIT 1)';

  Future<List<Map<String, dynamic>>> getPendingAttendance() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.rawQuery('''
        SELECT class_id, class_name, date,
               CASE WHEN SUM(CASE WHEN IFNULL(packet_kind,'draft') = 'submitted' THEN 1 ELSE 0 END) > 0
                    THEN 'submitted' ELSE 'draft' END as packet_kind,
               CASE WHEN COUNT(DISTINCT client_op_id) = 1
                    THEN MIN(client_op_id) ELSE NULL END as client_op_id,
               COUNT(*) as student_count, MIN(created_at) as created_at,
               MAX(CASE WHEN sync_error IS NOT NULL THEN 1 ELSE 0 END) as rejected
        FROM pending_attendance
        WHERE synced = 0 AND sync_state IN ('pending', 'retry_wait')
          AND owner_user_id = ? AND created_authorization_version = ?
          AND $_latestAttendanceGenerationWhere
        GROUP BY class_id, date ORDER BY date DESC
      ''', [
        binding['owner_user_id'],
        binding['created_authorization_version'],
      ]);
    });
  }

  Future<List<Map<String, dynamic>>> getPendingAttendanceRecords(
      int classId, String date) async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.query(
        'pending_attendance',
        where: 'class_id = ? AND date = ? AND synced = 0 '
            'AND owner_user_id = ? AND created_authorization_version = ? '
            'AND $_latestAttendanceGenerationWhere',
        whereArgs: [
          classId,
          date,
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
    });
  }

  /// Phase 8 QR scan: offline resolve of a scanned member code. Used to
  /// distinguish "member exists but not in THIS class/section" from
  /// "unknown to this device". Indexed lookup (idx_members_code mirror).
  Future<Map<String, dynamic>?> findCachedMemberByCode(String code) async {
    final db = await database;
    final rows = await db.query('cached_members',
        where: 'member_code = ?', whereArgs: [code], limit: 1);
    return rows.isEmpty ? null : rows.first;
  }

  /// Phase 8 QR scan (edu): the class a cached member is enrolled in,
  /// so a wrong-class scan can name the member's real class.
  Future<String?> cachedClassNameOfMember(int memberId) async {
    final db = await database;
    final rows = await db.rawQuery(
        'SELECT cc.class_name FROM cached_students cs '
        'JOIN cached_classes cc ON cc.id = cs.class_id '
        'WHERE cs.member_id = ? LIMIT 1',
        [memberId]);
    return rows.isEmpty ? null : rows.first['class_name'] as String?;
  }



  // ============================================================
  // PENDING GRADES
  // ============================================================

  Future<LegacyOperationRef> saveGradesLocal(
      int assessmentId,
      String assessmentName,
      int classId,
      String className,
      int subjectId,
      String subjectName,
      double maxScore,
      List<Map<String, dynamic>> grades,
      {String packetKind = 'draft', DateTime? notBefore}) async {
    final memberIds = grades
        .map((grade) => _asIntLocal(grade['member_id']))
        .where((id) => id > 0)
        .toSet();
    if (grades.isEmpty || memberIds.length != grades.length) {
      throw ArgumentError('Grades require one row per valid member.');
    }
    return _replaceLegacyOperation(
      kind: LegacyOperationKind.grades,
      table: 'pending_grades',
      naturalKeyWhere: 'assessment_id = ?',
      naturalKeyArgs: [assessmentId],
      naturalKey: {'assessment_id': assessmentId},
      packetKind: packetKind,
      notBefore: notBefore,
      rows: grades
          .map((g) => <String, Object?>{
                'assessment_id': assessmentId,
                'assessment_name': assessmentName,
                'class_id': classId,
                'class_name': className,
                'subject_id': subjectId,
                'subject_name': subjectName,
                'member_id': g['member_id'],
                'student_name': g['student_name'] ?? '',
                'record_id': g['record_id'],
                'score': g['score'],
                'remark': g['remark'] ?? '',
                'max_score': maxScore,
              })
          .toList(),
    );
  }

  Future<List<Map<String, dynamic>>> getPendingGrades() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.rawQuery('''
        SELECT assessment_id, assessment_name, class_name, subject_name,
               CASE WHEN SUM(CASE WHEN IFNULL(packet_kind,'draft') = 'submitted' THEN 1 ELSE 0 END) > 0
                    THEN 'submitted' ELSE 'draft' END as packet_kind,
               CASE WHEN COUNT(DISTINCT client_op_id) = 1
                    THEN MIN(client_op_id) ELSE NULL END as client_op_id,
               COUNT(*) as grade_count, MIN(created_at) as created_at,
               MAX(CASE WHEN sync_error IS NOT NULL THEN 1 ELSE 0 END) as rejected
        FROM pending_grades
        WHERE synced = 0 AND sync_state IN ('pending', 'retry_wait')
          AND owner_user_id = ? AND created_authorization_version = ?
        GROUP BY assessment_id ORDER BY created_at DESC
      ''', [
        binding['owner_user_id'],
        binding['created_authorization_version'],
      ]);
    });
  }

  Future<List<Map<String, dynamic>>> getPendingGradeRecords(
      int assessmentId) async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.query(
        'pending_grades',
        where: 'assessment_id = ? AND synced = 0 '
            'AND owner_user_id = ? AND created_authorization_version = ?',
        whereArgs: [
          assessmentId,
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
    });
  }

  // ============================================================
  // CACHED ATTENDANCE RESPONSES
  // ============================================================

  // ============================================================
  // DOWNLOAD SYNC — CURSOR STATE AND DELTA APPLICATION
  // ============================================================
  // The server feed (sql/057, sql/058) reports per-record attendance
  // changes. This cache does not store attendance records: it stores a
  // whole class/day sheet blob keyed by (class_id, date), whose student
  // entries are roster rows carrying member_id and a nullable status.
  //
  // So a delta is applied by patching the student entry inside the
  // affected sheet, not by inserting or deleting cache rows. Deleting an
  // attendance record means the student becomes unmarked again, because
  // the roster entry itself is enrollment data and must stay.
  //
  // Only the server layer is touched. Unsynced local edits live in
  // pending_attendance and are overlaid on top of the cache at read time
  // by the attendance screen, so a pull can never silently discard a
  // local change that has not been uploaded yet.

  /// Last revision whose changes are fully applied locally for [domain].
  /// Absent means 0, which the server answers with bootstrap_required.
  Future<int> getSyncCursor(String domain) async {
    final db = await database;
    try {
      final rows = await db.query('sync_state',
          columns: ['cursor'], where: 'domain = ?', whereArgs: [domain], limit: 1);
      if (rows.isEmpty) return 0;
      final value = rows.first['cursor'];
      return value is int ? value : int.tryParse('$value') ?? 0;
    } catch (_) {
      // A device that predates the table behaves like a fresh install.
      return 0;
    }
  }

  Future<Map<String, dynamic>?> getSyncState(String domain) async {
    final db = await database;
    try {
      final rows = await db.query('sync_state',
          where: 'domain = ?', whereArgs: [domain], limit: 1);
      return rows.isEmpty ? null : Map<String, dynamic>.from(rows.first);
    } catch (_) {
      return null;
    }
  }

  /// Record the outcome of a sync attempt without moving the cursor.
  /// Used for failures, so the UI can say "sync failed" while the cursor
  /// still points at the last revision that was genuinely applied.
  Future<void> setSyncStatus(String domain, String status,
      {String? error, LegacyWriteSession? expectedSession}) async {
    final db = await database;
    if (expectedSession != null) {
      await db.transaction((txn) async {
        await _requireAttendanceSyncSession(txn, expectedSession);
        final rows = await txn.query('sync_state', columns: ['cursor'],
            where: 'domain = ?', whereArgs: [domain], limit: 1);
        await txn.insert('sync_state', {
          'domain': domain,
          'cursor': rows.isEmpty ? 0 : _asIntLocal(rows.first['cursor']),
          'last_sync_at': DateTime.now().toIso8601String(),
          'status': status,
          'error': error,
        }, conflictAlgorithm: ConflictAlgorithm.replace);
      });
      return;
    }
    try {
      await db.insert(
        'sync_state',
        {
          'domain': domain,
          'cursor': await getSyncCursor(domain),
          'last_sync_at': DateTime.now().toIso8601String(),
          'status': status,
          'error': error,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    } catch (_) {}
  }

  Future<void> _requireAttendanceSyncSession(
      DatabaseExecutor executor, LegacyWriteSession expectedSession) async {
    if (!expectedSession.isValid ||
        !await activeSessionMatches(
          runtimeGeneration: expectedSession.runtimeGeneration,
          ownerUserId: expectedSession.ownerUserId,
          authorizationVersion: expectedSession.authorizationVersion,
          executor: executor,
        )) {
      throw const StaleAttendanceSyncSession();
    }
  }

  /// Apply one page of attendance changes and advance the cursor, both
  /// inside a single SQLite transaction.
  ///
  /// The cursor moves only on the same commit that writes the data, so
  /// the two can never disagree: if anything throws, sqflite rolls the
  /// whole transaction back and the next pull re-requests the same page.
  /// Re-applying a page is harmless — each change is an idempotent
  /// "set this student's status in this sheet to this value".
  ///
  /// [items] are the feed entries (op, class_id, member_id, date) and
  /// [records] maps attendance id to the canonical server row. An id
  /// missing from [records] is a tombstone.
  Future<void> applyAttendanceDelta({
    required LegacyWriteSession expectedSession,
    required List<Map<String, dynamic>> items,
    required Map<String, dynamic> records,
    required int nextCursor,
    String domain = 'attendance',
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      await _requireAttendanceSyncSession(txn, expectedSession);
      // Sheets are rewritten once each, not once per change, so a class
      // day with thirty corrections costs one read and one write.
      final touched = <String, Map<String, dynamic>>{};
      final sheetKeys = <String, List<Object>>{};

      Future<Map<String, dynamic>?> loadSheet(int classId, String date) async {
        final key = '$classId|$date';
        if (touched.containsKey(key)) return touched[key];
        final rows = await txn.query('cached_attendance',
            where: 'class_id = ? AND date = ?', whereArgs: [classId, date], limit: 1);
        if (rows.isEmpty) {
          // Nothing cached for that day: there is no stale copy to
          // correct, and inventing one would fabricate a roster the
          // server never sent.
          touched[key] = <String, dynamic>{};
          return null;
        }
        final decoded = _decodeSheet(rows.first['response_json'] as String);
        touched[key] = decoded;
        sheetKeys[key] = [classId, date];
        return decoded;
      }

      for (final item in items) {
        final raw = records['${item['entity_id'] ?? ''}'];
        final record = raw is Map ? Map<String, dynamic>.from(raw) : null;

        // What this change means is decided by AttendanceDelta, which is
        // pure and unit-testable; this method only supplies storage and
        // the transaction.
        final target = AttendanceDelta.resolve(item, record);
        if (target == null) continue;

        final sheet = await loadSheet(target.classId, target.date);
        if (sheet == null) continue;

        AttendanceDelta.applyToSheet(sheet, target);
      }

      for (final entry in sheetKeys.entries) {
        final sheet = touched[entry.key];
        if (sheet == null || sheet.isEmpty) continue;
        await txn.update(
          'cached_attendance',
          {
            'response_json': jsonEncode(sheet),
            'updated_at': DateTime.now().toIso8601String(),
          },
          where: 'class_id = ? AND date = ?',
          whereArgs: entry.value,
        );
      }

      // Same transaction as the data above. This is the only place the
      // cursor moves forward.
      await txn.insert(
        'sync_state',
        {
          'domain': domain,
          'cursor': nextCursor,
          'last_sync_at': DateTime.now().toIso8601String(),
          'status': 'ok',
          'error': null,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  /// Drop the cached attendance sheets and reset the cursor together, so
  /// a bootstrap cannot leave an empty cache paired with a cursor that
  /// claims to be up to date.
  Future<void> resetAttendanceSyncForBootstrap(
      {required LegacyWriteSession expectedSession}) async {
    final db = await database;
    await db.transaction((txn) async {
      await _requireAttendanceSyncSession(txn, expectedSession);
      await txn.delete('cached_attendance');
      await txn.insert(
        'sync_state',
        {
          'domain': 'attendance',
          'cursor': 0,
          'last_sync_at': DateTime.now().toIso8601String(),
          'status': 'bootstrap',
          'error': null,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  /// Store the cursor a completed bootstrap corresponds to.
  Future<void> completeAttendanceBootstrap(int cursor,
      {required LegacyWriteSession expectedSession}) async {
    final db = await database;
    await db.transaction((txn) async {
      await _requireAttendanceSyncSession(txn, expectedSession);
      await txn.insert(
        'sync_state',
        {
          'domain': 'attendance',
          'cursor': cursor,
          'last_sync_at': DateTime.now().toIso8601String(),
          'status': 'ok',
          'error': null,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  /// Publish the two legacy attendance read caches together under the captured
  /// durable session. This is NOT complete server preparation or a lease.
  Future<void> cacheAttendanceSnapshot(
      int classId, String date, List<Map<String, dynamic>> students,
      {required LegacyWriteSession expectedSession,
      String? submissionStatus, bool locked = false}) async {
    // Capture both representations before awaiting any database work.
    final updatedAt = DateTime.now().toIso8601String();
    final responseJson = jsonEncode({
      'students': students,
      'submission_status': submissionStatus ?? '',
      'locked': locked,
    });
    final rows = students.map((s) => <String, Object?>{
      'member_id': s['member_id'] ?? s['id'],
      'class_id': classId,
      'student_name': s['student_name'] ?? '',
      'father_name': s['father_name'] ?? '',
      'member_code': s['member_code'] ?? '',
      'gender': s['gender'] ?? '',
      'updated_at': updatedAt,
    }).toList(growable: false);
    final db = await database;
    await db.transaction((txn) async {
      await _requireAttendanceSyncSession(txn, expectedSession);
      await txn.delete('cached_students', where: 'class_id = ?',
          whereArgs: [classId]);
      final batch = txn.batch();
      for (final row in rows) {
        batch.insert('cached_students', row);
      }
      batch.insert('cached_attendance', {
        'class_id': classId,
        'date': date,
        'response_json': responseJson,
        'updated_at': updatedAt,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
      await batch.commit(noResult: true);
    });
  }

  Future<void> cacheAttendanceResponse(int classId, String date, List<Map<String, dynamic>> students,
      {String? submissionStatus, bool locked = false}) async {
    final db = await database;
    await db.insert(
      'cached_attendance',
      {
        'class_id': classId,
        'date': date,
        'response_json': jsonEncode({
          'students': students,
          'submission_status': submissionStatus ?? '',
          'locked': locked,
        }),
        'updated_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> cacheGradeSheet(int assessmentId, int classId, List<Map<String, dynamic>> students,
      {String? submissionStatus, bool locked = false}) async {
    final db = await database;
    await db.insert(
      'cached_grade_sheets',
      {
        'assessment_id': assessmentId,
        'class_id': classId,
        'response_json': jsonEncode({
          'students': students,
          'submission_status': submissionStatus ?? '',
          'locked': locked,
        }),
        'updated_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Map<String, dynamic> _decodeSheet(String raw) {
    final decoded = jsonDecode(raw);
    if (decoded is List) {
      return {
        'students': decoded.map((e) => Map<String, dynamic>.from(e)).toList(),
        'submission_status': '',
        'locked': false,
      };
    }
    if (decoded is Map) {
      final map = Map<String, dynamic>.from(decoded);
      final list = map['students'];
      return {
        'students': list is List
            ? list.map((e) => Map<String, dynamic>.from(e as Map)).toList()
            : <Map<String, dynamic>>[],
        'submission_status': '${map['submission_status'] ?? ''}',
        'locked': map['locked'] == true,
      };
    }
    return {'students': <Map<String, dynamic>>[], 'submission_status': '', 'locked': false};
  }

  Future<Map<String, dynamic>?> getCachedGradeSheetMeta(int assessmentId) async {
    final db = await database;
    try {
      final rows = await db.query(
        'cached_grade_sheets',
        where: 'assessment_id = ?',
        whereArgs: [assessmentId],
      );
      if (rows.isEmpty) return null;
      return _decodeSheet(rows.first['response_json'] as String);
    } catch (_) {
      return null;
    }
  }

  Future<List<Map<String, dynamic>>?> getCachedGradeSheet(int assessmentId) async {
    final meta = await getCachedGradeSheetMeta(assessmentId);
    if (meta == null) return null;
    return List<Map<String, dynamic>>.from(meta['students'] as List);
  }

  Future<Map<String, dynamic>?> getCachedAttendanceSheet(int classId, String date) async {
    final db = await database;
    try {
      final rows = await db.query(
        'cached_attendance',
        where: 'class_id = ? AND date = ?',
        whereArgs: [classId, date],
      );
      if (rows.isEmpty) return null;
      return _decodeSheet(rows.first['response_json'] as String);
    } catch (_) {
      return null;
    }
  }

  Future<List<Map<String, dynamic>>?> getCachedAttendanceResponse(int classId, String date) async {
    final meta = await getCachedAttendanceSheet(classId, date);
    if (meta == null) return null;
    return List<Map<String, dynamic>>.from(meta['students'] as List);
  }

  @Deprecated('A lock is not an acceptance receipt; do not use for lock cleanup.')
  Future<void> dropPendingAttendance(int classId, String date) async {
    throw StateError('Attendance requires exact server confirmation before removal.');
  }

  @Deprecated('A lock is not an acceptance receipt; do not use for lock cleanup.')
  Future<void> dropPendingGrades(int assessmentId) async {
    final db = await database;
    await db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      await txn.delete(
        'pending_grades',
        // F8: spare workflow-rejected rows (see dropPendingAttendance).
        where: "assessment_id = ? AND synced = 0 "
            "AND sync_state IN ('pending', 'retry_wait') "
            'AND sync_error IS NULL AND owner_user_id = ? '
            'AND created_authorization_version = ?',
        whereArgs: [
          assessmentId,
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
    });
  }

  // ============================================================
  // PENDING COUNTS
  // ============================================================

  Future<int> getPendingAttendanceCount() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final rows = await txn.rawQuery(
        'SELECT COUNT(DISTINCT client_op_id) AS cnt FROM pending_attendance '
        'WHERE synced = 0 AND owner_user_id = ? '
        'AND created_authorization_version = ?',
        [
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
      return rows.first['cnt'] as int? ?? 0;
    });
  }

  Future<int> getPendingGradesCount() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final rows = await txn.rawQuery(
        'SELECT COUNT(DISTINCT client_op_id) AS cnt FROM pending_grades '
        'WHERE synced = 0 AND owner_user_id = ? '
        'AND created_authorization_version = ?',
        [
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
      return rows.first['cnt'] as int? ?? 0;
    });
  }

  Future<DateTime?> nextOutboxAttemptAt({
    required int ownerUserId,
    required int authorizationVersion,
  }) async {
    final db = await database;
    final selects = <String>[
      for (final spec in legacyOutboxTableSpecs)
        "SELECT next_attempt_at AS due FROM ${spec.table} "
            "WHERE synced = 0 AND sync_state IN ('pending', 'retry_wait') "
            'AND next_attempt_at IS NOT NULL AND owner_user_id = ? '
            'AND created_authorization_version = ? '
            "${spec.table == 'pending_attendance' ? _attendanceClaimEligibilityWhere : ''}",
    ];
    final rows = await db.rawQuery(
      'SELECT MIN(due) AS due FROM (${selects.join(' UNION ALL ')})',
      [
        for (var i = 0; i < legacyOutboxTableSpecs.length; i++) ...[
          ownerUserId,
          authorizationVersion,
        ],
      ],
    );
    final raw = rows.isEmpty ? null : rows.first['due']?.toString();
    return raw == null ? null : DateTime.tryParse(raw)?.toUtc();
  }

  Future<int> getTotalPendingCount() async {
    return (await getPendingAttendanceCount()) +
        (await getPendingGradesCount()) +
        (await getPendingHrCount());
  }

  // ============================================================
  // HR DEPARTMENT ATTENDANCE (offline-first, section-scoped)
  // ============================================================

  // HR attendance was RETIRED (2026-10-07): no screen creates new HR
  // packets anymore. Kept for four-outbox symmetry (owner/scope stamping,
  // notBefore, rejected-replacement) — the outbox drain still settles
  // packets queued by old app versions against the server's honest 410.
  Future<LegacyOperationRef> saveHrLocal(String date, String section,
      List<Map<String, dynamic>> records,
      {String packetKind = 'draft', DateTime? notBefore}) async {
    const validStatuses = {'present', 'absent', 'late', 'excused'};
    if (records.isEmpty) {
      throw ArgumentError('HR attendance records are required.');
    }
    for (final record in records) {
      final status = '${record['status'] ?? ''}'.trim().toLowerCase();
      final memberId = record['member_id'] is int
          ? record['member_id'] as int
          : int.tryParse('${record['member_id'] ?? ''}');
      if (memberId == null || memberId <= 0 || !validStatuses.contains(status)) {
        throw ArgumentError('Attendance must explicitly mark every member.');
      }
    }

    return _replaceLegacyOperation(
      kind: LegacyOperationKind.hr,
      table: 'pending_hr',
      naturalKeyWhere: 'date = ? AND section = ?',
      naturalKeyArgs: [date, section],
      naturalKey: {'date': date, 'section': section},
      packetKind: packetKind,
      notBefore: notBefore,
      rows: records.map((r) {
        final note = '${r['notes'] ?? r['note'] ?? ''}'.trim();
        return <String, Object?>{
          'date': date,
          'section': section,
          'member_id': r['member_id'],
          'status': '${r['status']}'.trim().toLowerCase(),
          if (note.isNotEmpty) 'notes': note,
        };
      }).toList(),
    );
  }

  /// Pending HR packets grouped by (date, section).
  Future<List<Map<String, dynamic>>> getPendingHr() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.rawQuery('''
        SELECT date, section,
               CASE WHEN SUM(CASE WHEN IFNULL(packet_kind,'draft') = 'submitted' THEN 1 ELSE 0 END) > 0
                    THEN 'submitted' ELSE 'draft' END as packet_kind,
               CASE WHEN COUNT(DISTINCT client_op_id) = 1
                    THEN MIN(client_op_id) ELSE NULL END as client_op_id,
               COUNT(*) as member_count, MIN(created_at) as created_at,
               MAX(CASE WHEN sync_error IS NOT NULL THEN 1 ELSE 0 END) as rejected
        FROM pending_hr
        WHERE synced = 0 AND sync_state IN ('pending', 'retry_wait')
          AND owner_user_id = ? AND created_authorization_version = ?
        GROUP BY date, section ORDER BY date DESC
      ''', [
        binding['owner_user_id'],
        binding['created_authorization_version'],
      ]);
    });
  }

  Future<List<Map<String, dynamic>>> getPendingHrRecords(
      String date, String section) async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      return txn.query(
        'pending_hr',
        where: 'date = ? AND section = ? AND synced = 0 '
            'AND owner_user_id = ? AND created_authorization_version = ?',
        whereArgs: [
          date,
          section,
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
    });
  }



  // ── F8: workflow-rejected outbox batches ──────────────────────────
  //
  // A 409 from the school's workflow (day/test submitted while this
  // phone was offline) is NOT sync success. The batch stays here,
  // unsynced, with the server's reason in sync_error — recoverable
  // until the user explicitly discards it. Rejected batches are
  // excluded from every drain (the feeds above carry `rejected` and
  // the worker skips them) but still count as "not yet sent" in the
  // UI, which is the truth: the data lives only on this phone.









  /// Explicit, user-consented destruction of the exact terminal operation
  /// shown by the review sheet. A stale dialog can never delete a replacement
  /// save that reused the same natural key.
  LegacyOutboxTableSpec _legacyRecoverySpec(String kind) =>
      _legacySpecFor(switch (kind) {
        'attendance' => LegacyOperationKind.attendance,
        'grades' => LegacyOperationKind.grades,
        'hr' => LegacyOperationKind.hr,
        _ => throw ArgumentError.value(kind, 'kind', 'Unknown outbox kind'),
      });

  /// Compare-and-set retry of one exact terminal generation. A stale handle
  /// never falls back to the packet's natural key and therefore changes
  /// nothing if a save, worker claim, or scope transition won the race.
  Future<SyncRecoveryActionResult> retryRejectedOperation(
    String kind,
    String clientOpId,
    String expectedState,
  ) async {
    if (!const {'needs_attention', 'blocked_dependency', 'failed'}
        .contains(expectedState)) {
      return SyncRecoveryActionResult.stale;
    }
    final spec = _legacyRecoverySpec(kind);
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final baseWhere = 'client_op_id = ? AND synced = 0 '
          'AND owner_user_id = ? AND created_authorization_version = ?';
      final args = <Object?>[
        clientOpId,
        binding['owner_user_id'],
        binding['created_authorization_version'],
      ];
      final rows = await txn.query(spec.table,
          columns: ['sync_state', ...spec.businessKeyColumns],
          where: baseWhere,
          whereArgs: args);
      if (rows.isEmpty ||
          rows.any((row) => '${row['sync_state']}' != expectedState)) {
        return SyncRecoveryActionResult.stale;
      }
      final naturalKey = _legacyNaturalKey(spec, rows.first);
      if (rows.any((row) => spec.businessKeyColumns.any(
          (column) => row[column] != naturalKey[column]))) {
        return SyncRecoveryActionResult.stale;
      }
      final keyWhere = spec.businessKeyColumns
          .map((column) => '$column = ?')
          .join(' AND ');
      final keyArgs = spec.businessKeyColumns
          .map((column) => naturalKey[column])
          .toList(growable: false);
      final affected = await txn.update(
        spec.table,
        {
          'sync_state': 'pending',
          // Attendance ledger numbers must stay monotonic across retries.
          if (kind != 'attendance') 'attempt_count': 0,
          'next_attempt_at': null,
          if (kind != 'attendance') 'last_attempt_at': null,
          'failure_code': null,
          'failure_http_status': null,
          'failed_at': null,
          'sync_error': null,
        },
        where: '$baseWhere AND $keyWhere AND sync_state = ?',
        whereArgs: [...args, ...keyArgs, expectedState],
      );
      return affected == rows.length
          ? SyncRecoveryActionResult.applied
          : SyncRecoveryActionResult.stale;
    });
  }

  /// Confirmed compare-and-set deletion of one exact terminal generation.
  Future<SyncRecoveryActionResult> discardRejectedOperation(
    String kind,
    String clientOpId, {
    String? expectedState,
  }) async {
    // Neither explicit consent nor a local rejection/conflict label is proof
    // that this attendance operation has a terminal server disposition.
    if (kind == 'attendance') {
      return SyncRecoveryActionResult.serverConfirmationRequired;
    }
    const terminal = {
      'needs_attention',
      'blocked_dependency',
      'failed',
      'resolved_conflict',
    };
    if (expectedState != null && !terminal.contains(expectedState)) {
      return SyncRecoveryActionResult.stale;
    }
    final spec = _legacyRecoverySpec(kind);
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final baseWhere = 'client_op_id = ? AND synced = 0 '
          'AND owner_user_id = ? AND created_authorization_version = ?';
      final args = <Object?>[
        clientOpId,
        binding['owner_user_id'],
        binding['created_authorization_version'],
      ];
      final rows = await txn.query(spec.table,
          columns: ['sync_state', ...spec.businessKeyColumns],
          where: baseWhere,
          whereArgs: args);
      if (rows.isEmpty || rows.any((row) {
        final state = '${row['sync_state']}';
        return !terminal.contains(state) ||
            (expectedState != null && state != expectedState);
      })) {
        return SyncRecoveryActionResult.stale;
      }
      final naturalKey = _legacyNaturalKey(spec, rows.first);
      if (rows.any((row) => spec.businessKeyColumns.any(
          (column) => row[column] != naturalKey[column]))) {
        return SyncRecoveryActionResult.stale;
      }
      final keyWhere = spec.businessKeyColumns
          .map((column) => '$column = ?')
          .join(' AND ');
      final keyArgs = spec.businessKeyColumns
          .map((column) => naturalKey[column])
          .toList(growable: false);
      final affected = await txn.delete(
        spec.table,
        where: expectedState == null
            ? "$baseWhere AND $keyWhere AND sync_state IN ('needs_attention', "
                "'blocked_dependency', 'failed', 'resolved_conflict')"
            : '$baseWhere AND $keyWhere AND sync_state = ?',
        whereArgs: expectedState == null
            ? [...args, ...keyArgs]
            : [...args, ...keyArgs, expectedState],
      );
      return affected == rows.length
          ? SyncRecoveryActionResult.applied
          : SyncRecoveryActionResult.stale;
    });
  }

  /// All rejected batches across the three legacy outboxes, for the
  /// review sheet and the SyncStatus count. Fields: kind, label, detail,
  /// client_op_id and reason (PII-light: names/dates, never member rows).
  Future<List<Map<String, dynamic>>> getRejectedBatches() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final owner = binding['owner_user_id'];
      final scope = binding['created_authorization_version'];
      return txn.rawQuery('''
        SELECT 'attendance' AS kind, class_name AS label, date AS detail,
               client_op_id, MAX(sync_error) AS reason
        FROM pending_attendance
        WHERE synced = 0
          AND sync_state IN ('needs_attention', 'resolved_conflict')
          AND owner_user_id = ? AND created_authorization_version = ?
        GROUP BY client_op_id, class_id, date
        UNION ALL
        SELECT 'grades' AS kind, assessment_name AS label,
               COALESCE(class_name, '') AS detail,
               client_op_id, MAX(sync_error) AS reason
        FROM pending_grades
        WHERE synced = 0
          AND sync_state IN ('needs_attention', 'resolved_conflict')
          AND owner_user_id = ? AND created_authorization_version = ?
        GROUP BY client_op_id, assessment_id
        UNION ALL
        SELECT 'hr' AS kind, 'HR attendance' AS label,
               date || ' · ' || section AS detail,
               client_op_id, MAX(sync_error) AS reason
        FROM pending_hr
        WHERE synced = 0
          AND sync_state IN ('needs_attention', 'resolved_conflict')
          AND owner_user_id = ? AND created_authorization_version = ?
        GROUP BY client_op_id, date, section
        ORDER BY kind, detail
      ''', [owner, scope, owner, scope, owner, scope]);
    });
  }


  /// Current-scope detail rows for the Sync Recovery Center. The owner and
  /// authorization predicates are part of every private query. Older-scope
  /// work is intentionally absent here and is surfaced only as a safe count
  /// from [getOutboxInventory].
  Future<List<SyncRecoveryItem>> getSyncRecoveryItems() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final owner = binding['owner_user_id'];
      final scope = binding['created_authorization_version'];
      final rows = await txn.rawQuery('''
        SELECT 'attendance' AS domain, client_op_id AS operation_id,
               NULL AS row_id, sync_state AS state,
               COALESCE(NULLIF(class_name, ''), 'Attendance') AS title,
               date AS detail, MAX(sync_error) AS reason,
               MAX(next_attempt_at) AS next_attempt_at
          FROM pending_attendance
         WHERE synced = 0 AND owner_user_id = ?
           AND created_authorization_version = ?
         GROUP BY client_op_id, sync_state, class_id, class_name, date
        UNION ALL
        SELECT 'grades', client_op_id, NULL, sync_state,
               COALESCE(NULLIF(assessment_name, ''), 'Grades'),
               COALESCE(NULLIF(class_name, ''), 'Grade sheet'),
               MAX(sync_error), MAX(next_attempt_at)
          FROM pending_grades
         WHERE synced = 0 AND owner_user_id = ?
           AND created_authorization_version = ?
         GROUP BY client_op_id, sync_state, assessment_id,
                  assessment_name, class_name
        UNION ALL
        SELECT 'hr', client_op_id, NULL, sync_state,
               'HR attendance', date || ' · ' || section,
               MAX(sync_error), MAX(next_attempt_at)
          FROM pending_hr
         WHERE synced = 0 AND owner_user_id = ?
           AND created_authorization_version = ?
         GROUP BY client_op_id, sync_state, date, section
        UNION ALL
        SELECT 'communication', client_tag, NULL, state,
               'Message', 'Conversation #' || thread_id,
               fail_reason, next_attempt_at
          FROM comm_outbox
         WHERE state <> 'synced' AND owner_user_id = ?
           AND created_authorization_version = ?
        ORDER BY next_attempt_at, domain, detail
      ''', [
        owner, scope,
        owner, scope,
        owner, scope,
        owner, scope,
      ]);

      final result = <SyncRecoveryItem>[];
      for (final row in rows) {
        final domain = switch ('${row['domain']}') {
          'attendance' => SyncRecoveryDomain.attendance,
          'grades' => SyncRecoveryDomain.grades,
          'hr' => SyncRecoveryDomain.hr,
          _ => SyncRecoveryDomain.communication,
        };
        result.add(SyncRecoveryItem(
          domain: domain,
          operationId: '${row['operation_id']}',
          rowId: row['row_id'] == null ? null : _asIntLocal(row['row_id']),
          state: '${row['state']}',
          title: '${row['title']}',
          detail: '${row['detail']}',
          reason: row['reason']?.toString(),
          nextAttemptAt: DateTime.tryParse('${row['next_attempt_at'] ?? ''}')
              ?.toLocal(),
        ));
      }

      return List<SyncRecoveryItem>.unmodifiable(result);
    });
  }

  @Deprecated('A lock is not an acceptance receipt; do not use for lock cleanup.')
  Future<void> dropPendingHr(String date, String section) async {
    final db = await database;
    await db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      await txn.delete(
        'pending_hr',
        // F8: spare workflow-rejected rows (see dropPendingAttendance).
        where: 'date = ? AND section = ? AND synced = 0 '
            "AND sync_state IN ('pending', 'retry_wait') "
            'AND sync_error IS NULL AND owner_user_id = ? '
            'AND created_authorization_version = ?',
        whereArgs: [
          date,
          section,
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
    });
  }

  Future<int> getPendingHrCount() async {
    final db = await database;
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final rows = await txn.rawQuery(
        'SELECT COUNT(DISTINCT client_op_id) AS cnt FROM pending_hr '
        'WHERE synced = 0 AND owner_user_id = ? '
        'AND created_authorization_version = ?',
        [
          binding['owner_user_id'],
          binding['created_authorization_version'],
        ],
      );
      return rows.first['cnt'] as int? ?? 0;
    });
  }

  Future<void> cacheHrSheet(
      String date, String section, Map<String, dynamic> payload) async {
    final db = await database;
    await db.insert(
        'cached_hr_sheet',
        {
          'date': date,
          'section': section,
          'response_json': jsonEncode(payload),
          'updated_at': DateTime.now().toIso8601String(),
        },
        conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<Map<String, dynamic>?> getCachedHrSheet(
      String date, String section) async {
    final db = await database;
    try {
      final rows = await db.query('cached_hr_sheet',
          where: 'date = ? AND section = ?', whereArgs: [date, section]);
      if (rows.isEmpty) return null;
      final raw = rows.first['response_json'] as String?;
      if (raw == null || raw.isEmpty) return null;
      final decoded = jsonDecode(raw);
      return decoded is Map<String, dynamic> ? decoded : null;
    } catch (_) {
      return null;
    }
  }

  /// Warm cache for the HR [Section ▾] picker (offline parity).
  Future<void> cacheHrSections(List<Map<String, dynamic>> sections) async {
    final db = await database;
    await db.insert(
        'cached_hr_sections',
        {
          'id': 1,
          'sections_json': jsonEncode(sections),
          'updated_at': DateTime.now().toIso8601String(),
        },
        conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<List<Map<String, dynamic>>?> getCachedHrSections() async {
    final db = await database;
    try {
      final rows = await db.query('cached_hr_sections', where: 'id = 1');
      if (rows.isEmpty) return null;
      final raw = rows.first['sections_json'] as String?;
      if (raw == null || raw.isEmpty) return null;
      final decoded = jsonDecode(raw);
      if (decoded is List) {
        return decoded
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      }
      return null;
    } catch (_) {
      return null;
    }
  }

  Future<void> cleanupSyncedHr() async {
    final db = await database;
    await db.delete('pending_hr', where: 'synced = 1');
  }

  // ============================================================
  // SESSION OWNERSHIP + PRIVATE-DATA INVENTORY (v34)
  // ============================================================

  Future<bool> activeSessionMatches({
    required int runtimeGeneration,
    int? ownerUserId,
    int? authorizationVersion,
    DatabaseExecutor? executor,
  }) async {
    final db = executor ?? await database;
    final rows = await db.query(
      'local_session_state',
      columns: [
        'state',
        'generation',
        'owner_user_id',
        'owner_authorization_version',
      ],
      where: 'id = ?',
      whereArgs: [1],
      limit: 1,
    );
    if (rows.isEmpty || rows.first['state'] != 'active') return false;
    final row = rows.first;
    if (_asIntLocal(row['generation']) != runtimeGeneration) return false;
    if (ownerUserId != null &&
        _nullablePositiveInt(row['owner_user_id']) != ownerUserId) {
      return false;
    }
    if (authorizationVersion != null &&
        _nullableNonNegativeInt(row['owner_authorization_version']) !=
            authorizationVersion) {
      return false;
    }
    return true;
  }

  Future<int> _activeRuntimeGeneration(DatabaseExecutor executor) async {
    final rows = await executor.query(
      'local_session_state',
      columns: ['generation'],
      where: 'id = ?',
      whereArgs: [1],
      limit: 1,
    );
    if (rows.isEmpty) throw StateError('Active session generation is missing.');
    return _asIntLocal(rows.first['generation']);
  }

  Future<Map<String, int>> requireActiveOwnerBinding(
      [DatabaseExecutor? executor]) async {
    final db = executor ?? await database;
    final rows = await db.query(
      'local_session_state',
      columns: [
        'state',
        'owner_user_id',
        'owner_authorization_version',
      ],
      where: 'id = ?',
      whereArgs: [1],
      limit: 1,
    );
    if (rows.isEmpty || rows.first['state'] != 'active') {
      throw StateError('Private writes require an active reconciled session.');
    }
    final owner = _nullablePositiveInt(rows.first['owner_user_id']);
    final version =
        _nullableNonNegativeInt(rows.first['owner_authorization_version']);
    if (owner == null || version == null) {
      throw StateError('The active session has no owner/scope binding.');
    }
    return {
      'owner_user_id': owner,
      'created_authorization_version': version,
    };
  }

  /// Quarantine private writes created under any earlier authorization scope.
  /// Payloads and operation ids are retained for the recovery UI; none are
  /// rebound merely because the same user received a new role/version.
  Future<void> pausePrivateOperationsOutsideAuthorizationScope({
    required int ownerUserId,
    required int authorizationVersion,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      for (final spec in legacyOutboxTableSpecs) {
        await txn.update(
          spec.table,
          {
            'sync_state': 'paused_scope',
            'next_attempt_at': null,
            'sync_error':
                'Authorization changed before this saved operation was sent.',
            'failure_code': 'AUTH_SCOPE_CHANGED',
            'failure_http_status': null,
          },
          where: 'synced = 0 AND owner_user_id = ? AND '
              '(created_authorization_version IS NULL OR '
              'created_authorization_version <> ?)',
          whereArgs: [ownerUserId, authorizationVersion],
        );
      }
      await txn.update(
        'comm_outbox',
        {
          'state': 'paused_scope',
          'next_attempt_at': null,
          'fail_reason':
              'Authorization changed before this saved message was sent.',
          'failure_code': 'AUTH_SCOPE_CHANGED',
          'failure_http_status': null,
        },
        where: "state <> 'synced' AND owner_user_id = ? AND "
            '(created_authorization_version IS NULL OR '
            'created_authorization_version <> ?)',
        whereArgs: [ownerUserId, authorizationVersion],
      );
    });
  }

  /// Purge server-derived reads whose visibility depends on the current role.
  /// Durable private writes/drafts are preserved.
  Future<void> clearAuthorizationScopedReadCaches() async {
    final db = await database;
    await db.transaction((txn) async {
      for (final table in _privateReadCacheTables) {
        await txn.delete(table);
      }
      // The download cursor describes caches that no longer exist. Left
      // alone, the next pull would ask for changes "since" a revision it
      // can no longer substantiate and would only ever receive deltas,
      // never the rows the purge removed. Resetting to 0 makes the
      // server demand a bootstrap, which is the honest answer.
      try {
        await txn.delete('sync_state');
      } catch (_) {}
    });
    // Remove deleted role-scoped pages from the WAL without the much heavier
    // VACUUM used by an explicit destructive account purge.
    try {
      await db.rawQuery('PRAGMA wal_checkpoint(TRUNCATE)');
    } catch (_) {}
  }

  Future<LocalSessionRecord> getLocalSession() async {
    final db = await database;
    final rows = await db.query(
      'local_session_state',
      where: 'id = ?',
      whereArgs: [1],
      limit: 1,
    );
    if (rows.isEmpty) {
      return const LocalSessionRecord(
        state: SessionState.anonymousClean,
        generation: 0,
      );
    }
    final row = rows.first;
    return LocalSessionRecord(
      state: SessionState.fromStorage(row['state']?.toString()),
      generation: _asIntLocal(row['generation']),
      ownerUserId: _nullablePositiveInt(row['owner_user_id']),
      authorizationVersion:
          _nullableNonNegativeInt(row['owner_authorization_version']),
      ownerRole: row['owner_role']?.toString(),
      ownerUsername: row['owner_username']?.toString(),
      ownerDisplayName: row['owner_display_name']?.toString(),
      reauthReason: row['reason']?.toString(),
      updatedAt: row['updated_at']?.toString(),
    );
  }

  Future<void> persistLocalSession({
    required SessionState state,
    required int generation,
    int? ownerUserId,
    int? authorizationVersion,
    String? ownerRole,
    String? ownerUsername,
    String? ownerDisplayName,
    String? reason,
  }) async {
    final db = await database;
    await db.insert(
      'local_session_state',
      {
        'id': 1,
        'owner_user_id': ownerUserId,
        'owner_username': ownerUsername,
        'owner_display_name': ownerDisplayName,
        'owner_role': ownerRole,
        'owner_authorization_version': authorizationVersion,
        'state': state.storageValue,
        'reason': reason,
        'generation': generation,
        'updated_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  /// A complete credential bundle is the only evidence permitted to claim
  /// ownerless legacy rows.
  Future<void> backfillOwnerlessRows({
    required int ownerUserId,
    required int authorizationVersion,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      for (final table in const [
        'pending_attendance',
        'pending_grades',
        'pending_hr',
      ]) {
        await txn.update(
          table,
          {
            'owner_user_id': ownerUserId,
            'created_authorization_version': authorizationVersion,
          },
          where: 'synced = 0 AND owner_user_id IS NULL',
        );
      }
      for (final table in const ['comm_outbox', 'comm_drafts']) {
        await txn.update(
          table,
          {
            'owner_user_id': ownerUserId,
            'created_authorization_version': authorizationVersion,
          },
          where: 'owner_user_id IS NULL',
        );
      }
    });
  }

  /// Whether another bounded legacy drain pass has immediately due work for
  /// the active owner/scope. Communication rows are excluded;
  /// their own workers provide the corresponding bounded-rescan guarantees.
  Future<bool> hasDueLegacyOutbox({DateTime? now}) async {
    final db = await database;
    final nowText = (now ?? DateTime.now()).toUtc().toIso8601String();
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      for (final spec in legacyOutboxTableSpecs) {
        final rows = await txn.rawQuery(
          'SELECT 1 FROM ${spec.table} '
          "WHERE synced = 0 AND sync_state IN ('pending', 'retry_wait') "
          'AND (next_attempt_at IS NULL OR next_attempt_at <= ?) '
          'AND owner_user_id = ? '
          'AND created_authorization_version = ? '
          "${spec.table == 'pending_attendance' ? _attendanceClaimEligibilityWhere : ''}LIMIT 1",
          [
            nowText,
            binding['owner_user_id'],
            binding['created_authorization_version'],
          ],
        );
        if (rows.isNotEmpty) return true;
      }
      return false;
    });
  }

  /// One state-separated snapshot for schedulers and status UI. Counts are
  /// operations (not legacy child rows), and terminal review states never
  /// masquerade as network-retry work. Actionable private state counts use
  /// the active owner/scope; older-scope private work contributes only to the
  /// safe paused/total counts.
  Future<OutboxInventory> getOutboxInventory({DateTime? now}) async {
    final db = await database;
    final nowText = (now ?? DateTime.now()).toUtc().toIso8601String();
    return db.transaction((txn) async {
      final binding = await requireActiveOwnerBinding(txn);
      final ownerUserId = binding['owner_user_id']!;
      final authorizationVersion = binding['created_authorization_version']!;

      Future<int> scalar(String sql, [List<Object?>? args]) async {
        final rows = await txn.rawQuery(sql, args);
        return rows.isEmpty ? 0 : _asIntLocal(rows.first.values.first);
      }

      Future<int> legacyState(String predicate,
          [List<Object?> args = const [], String? attendancePredicate]) async {
        var total = 0;
        for (final spec in legacyOutboxTableSpecs) {
          final scopedPredicate = spec.table == 'pending_attendance'
              ? attendancePredicate ?? predicate : predicate;
          total += await scalar(
            'SELECT COUNT(DISTINCT client_op_id) FROM ${spec.table} '
            'WHERE synced = 0 AND ($scopedPredicate) '
            'AND owner_user_id = ? '
            'AND created_authorization_version = ?',
            [...args, ownerUserId, authorizationVersion],
          );
        }
        return total;
      }

      Future<int> legacyOwnerState(String predicate,
          [List<Object?> args = const []]) async {
        var total = 0;
        for (final spec in legacyOutboxTableSpecs) {
          total += await scalar(
            'SELECT COUNT(DISTINCT client_op_id) FROM ${spec.table} '
            'WHERE synced = 0 AND ($predicate) AND owner_user_id = ?',
            [...args, ownerUserId],
          );
        }
        return total;
      }

      Future<int> allState(
        String legacyPredicate,
        String commPredicate, {
        String? attendancePredicate,
        List<Object?> legacyArgs = const [],
        List<Object?> commArgs = const [],
      }) async {
        return await legacyState(legacyPredicate, legacyArgs, attendancePredicate) +
            await scalar(
              'SELECT COUNT(*) FROM comm_outbox WHERE ($commPredicate) '
              'AND owner_user_id = ? '
              'AND created_authorization_version = ?',
              [...commArgs, ownerUserId, authorizationVersion],
            );
      }

      final due = await allState(
        "sync_state IN ('pending', 'retry_wait') AND "
            '(next_attempt_at IS NULL OR next_attempt_at <= ?)',
        "state IN ('pending', 'retry_wait') AND "
            '(next_attempt_at IS NULL OR next_attempt_at <= ?)',
        attendancePredicate: "sync_state IN ('pending', 'retry_wait') AND "
            '(next_attempt_at IS NULL OR next_attempt_at <= ?) '
            '$_attendanceClaimEligibilityWhere',
        legacyArgs: [nowText],
        commArgs: [nowText],
      );
      final waiting = await allState(
        "sync_state IN ('pending', 'retry_wait') AND next_attempt_at > ?",
        "state IN ('pending', 'retry_wait') AND next_attempt_at > ?",
        attendancePredicate: "sync_state IN ('pending', 'retry_wait') "
            'AND next_attempt_at > ? $_attendanceClaimEligibilityWhere',
        legacyArgs: [nowText],
        commArgs: [nowText],
      );
      final inFlight = await allState(
        "sync_state = 'in_flight'",
        "state = 'in_flight'",
      );
      final attention = await allState(
        "sync_state = 'needs_attention'",
        "state IN ('needs_attention', 'failed')",
      );
      final pausedAuth = await allState(
        "sync_state = 'paused_auth'",
        "state = 'paused_auth'",
      );
      final currentScopePaused = await allState(
        "sync_state = 'paused_scope'",
        "state = 'paused_scope'",
      );
      // Older authorization versions are deliberately count-only. Include
      // every unresolved private row so even an interrupted reconciliation
      // cannot hide saved work or expose its roster/message payload.
      final oldScopePrivate = await legacyOwnerState(
            'created_authorization_version <> ?',
            [authorizationVersion],
          ) +
          await scalar(
            "SELECT COUNT(*) FROM comm_outbox WHERE state <> 'synced' "
            'AND owner_user_id = ? '
            'AND created_authorization_version <> ?',
            [ownerUserId, authorizationVersion],
          );
      final pausedScope = currentScopePaused + oldScopePrivate;
      final blocked = await allState(
        "sync_state = 'blocked_dependency'",
        "state = 'blocked_dependency'",
        attendancePredicate: "sync_state = 'blocked_dependency' OR "
            "(sync_state IN ('pending', 'retry_wait') "
            'AND NOT (1 = 1 $_attendanceClaimEligibilityWhere))',
      );
      final conflicts = await allState(
        "sync_state = 'resolved_conflict'",
        "state = 'resolved_conflict'",
      );
      final privateUnresolved =
          await legacyOwnerState('1 = 1') +
              await scalar(
                "SELECT COUNT(*) FROM comm_outbox WHERE state <> 'synced' "
                'AND owner_user_id = ?',
                [ownerUserId],
              );
      final drafts = await scalar(
        "SELECT COUNT(*) FROM comm_drafts WHERE TRIM(body) <> '' "
        'AND owner_user_id = ?',
        [ownerUserId],
      );
      return OutboxInventory(
        retryableDue: due,
        retryableWaiting: waiting,
        inFlight: inFlight,
        needsAttention: attention,
        pausedAuth: pausedAuth,
        pausedScope: pausedScope,
        blockedDependency: blocked,
        resolvedConflict: conflicts,
        privateUnresolvedTotal: privateUnresolved,
        communicationDraftCount: drafts,
      );
    });
  }

  /// One SQLite snapshot used by logout, forgot-PIN and owner activation.
  /// Legacy domains count operations/batches, not member rows. Paused and
  /// attention counts are reported separately and intentionally overlap the
  /// domain totals rather than inflating the destructive-work decision.
  Future<LocalDataInventory> getLocalDataInventory() async {
    final db = await database;
    return db.transaction((txn) async {
      Future<int> scalar(String sql, [List<Object?>? args]) async {
        final rows = await txn.rawQuery(sql, args);
        if (rows.isEmpty) return 0;
        return _asIntLocal(rows.first.values.first);
      }

      Future<int> operationCount(String table) => scalar(
            'SELECT COUNT(DISTINCT client_op_id) FROM $table '
            'WHERE synced = 0',
          );

      final attendance = await operationCount('pending_attendance');
      final grades = await operationCount('pending_grades');
      final hr = await operationCount('pending_hr');
      final commPending = await scalar(
        "SELECT COUNT(*) FROM comm_outbox WHERE state NOT IN "
        "('synced', 'failed', 'needs_attention', 'resolved_conflict')",
      );
      final commFailed = await scalar(
        "SELECT COUNT(*) FROM comm_outbox WHERE state IN "
        "('failed', 'needs_attention', 'resolved_conflict')",
      );
      final drafts = await scalar(
        "SELECT COUNT(*) FROM comm_drafts WHERE TRIM(body) <> ''",
      );
      final attention = await scalar('''
        SELECT
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_attendance
             WHERE synced = 0 AND sync_state = 'needs_attention') +
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_grades
             WHERE synced = 0 AND sync_state = 'needs_attention') +
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_hr
             WHERE synced = 0 AND sync_state = 'needs_attention') +
          (SELECT COUNT(*) FROM comm_outbox
             WHERE state IN ('needs_attention', 'failed'))
      ''');
      final paused = await scalar('''
        SELECT
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_attendance
             WHERE synced = 0 AND sync_state IN ('paused_auth', 'paused_scope')) +
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_grades
             WHERE synced = 0 AND sync_state IN ('paused_auth', 'paused_scope')) +
          (SELECT COUNT(DISTINCT client_op_id) FROM pending_hr
             WHERE synced = 0 AND sync_state IN ('paused_auth', 'paused_scope')) +
          (SELECT COUNT(*) FROM comm_outbox
             WHERE state IN ('paused_auth', 'paused_scope'))
      ''');
      final ownerRows = await txn.rawQuery('''
        SELECT owner_user_id FROM pending_attendance
          WHERE synced = 0 AND owner_user_id IS NOT NULL
        UNION SELECT owner_user_id FROM pending_grades
          WHERE synced = 0 AND owner_user_id IS NOT NULL
        UNION SELECT owner_user_id FROM pending_hr
          WHERE synced = 0 AND owner_user_id IS NOT NULL
        UNION SELECT owner_user_id FROM comm_outbox
          WHERE owner_user_id IS NOT NULL
        UNION SELECT owner_user_id FROM comm_drafts
          WHERE TRIM(body) <> '' AND owner_user_id IS NOT NULL
        ORDER BY owner_user_id
      ''');
      final privateOwners = ownerRows
          .map((row) => _nullablePositiveInt(row['owner_user_id']))
          .whereType<int>()
          .toList(growable: false);

      var cacheRows = 0;
      for (final table in _privateReadCacheTables) {
        cacheRows += await scalar('SELECT COUNT(*) FROM $table');
      }

      return LocalDataInventory(
        attendanceOperations: attendance,
        gradeOperations: grades,
        hrOperations: hr,
        communicationPending: commPending,
        communicationFailed: commFailed,
        communicationDrafts: drafts,
        attentionOperations: attention,
        pausedOperations: paused,
        privateCacheRows: cacheRows,
        privateOwnerUserIds: privateOwners,
      );
    });
  }

  static int? _nullablePositiveInt(Object? value) {
    final parsed = _asIntLocal(value);
    return parsed > 0 ? parsed : null;
  }

  static int? _nullableNonNegativeInt(Object? value) {
    if (value == null) return null;
    final parsed = _asIntLocal(value);
    return parsed >= 0 ? parsed : null;
  }

  // ============================================================
  // CLEANUP
  // ============================================================

  Future<void> cleanupSynced() async {
    final db = await database;
    final cutoff =
        DateTime.now().subtract(const Duration(days: 7)).toIso8601String();
    // Keep just the newest accepted checkpoint while older unsynced history
    // could otherwise reappear in the latest-generation attendance overlay.
    await db.delete('pending_attendance',
        where: 'synced = 1 AND synced_at < ? AND ('
            'EXISTS (SELECT 1 FROM pending_attendance newer '
            'WHERE newer.class_id = pending_attendance.class_id '
            'AND newer.date = pending_attendance.date '
            'AND newer.owner_user_id = pending_attendance.owner_user_id '
            'AND newer.created_authorization_version = pending_attendance.created_authorization_version '
            'AND newer.synced = 1 AND newer.id > pending_attendance.id '
            'AND newer.client_op_id IS NOT pending_attendance.client_op_id) '
            'OR NOT EXISTS (SELECT 1 FROM pending_attendance older '
            'WHERE older.class_id = pending_attendance.class_id '
            'AND older.date = pending_attendance.date '
            'AND older.owner_user_id = pending_attendance.owner_user_id '
            'AND older.created_authorization_version = pending_attendance.created_authorization_version '
            'AND older.synced = 0 AND older.id < pending_attendance.id))',
        whereArgs: [cutoff]);
    await db.delete('pending_grades',
        where: 'synced = 1 AND synced_at < ?', whereArgs: [cutoff]);
    await db.delete('pending_hr',
        where: 'synced = 1 AND synced_at < ?', whereArgs: [cutoff]);
  }

  Future<void> logSync(String action, String detail, String status) async {
    final db = await database;
    await db.insert('sync_log', {
      'action': action,
      'detail': detail,
      'status': status,
      'created_at': DateTime.now().toIso8601String(),
    });
  }

  /// Clear all cached data (on logout)
  Future<void> clearAllCache() async {
    final db = await database;
    await db.delete('cached_classes');
    await db.delete('cached_students');
    await db.delete('cached_subjects');
    await db.delete('cached_assessments');
    await db.delete('cached_dashboard');
    await db.delete('cached_members');
    try { await db.delete('cached_attendance'); } catch (_) {}
    try { await db.delete('cached_grade_sheets'); } catch (_) {}
    try { await db.delete('cached_hr_sheet'); } catch (_) {}
    try { await db.delete('cached_hr_sections'); } catch (_) {}
    await db.delete('attendance_preparation_snapshot');
    await db.delete('grades_preparation_snapshot');
    try { await db.delete('attendance_sync_receipt'); } catch (_) {}
    try { await db.delete('grades_sync_receipt'); } catch (_) {}
  }

  /// Full transactional wipe for this device user — member/attendance
  /// cache + unsynced rows. Prevents the next login on this phone from
  /// seeing the previous roster.
  Future<void> clearAllUserData() async {
    final db = await database;
    await db.transaction((txn) async {
      for (final table in [
        'cached_classes',
        'cached_students',
        'cached_subjects',
        'cached_assessments',
        'cached_dashboard',
        'cached_members',
        'cached_attendance',
        'cached_grade_sheets',
        'cached_review_packets',
        'cached_review_packet_details',
        'cached_review_stats',
        // P1-E: education read model is user-scoped (rosters carry
        // member PII; the class list is role-filtered) — same wipe
        // discipline as every other cache.
        'cached_edu_classes',
        'cached_edu_class_rosters',
        // P1-F: the subject catalog is a role-scoped server response
        // — same wipe discipline.
        'cached_edu_subjects',
        // P1-G: staff directory + assignment details are authenticated,
        // year-scoped data. Never retain or reuse them across logout.
        'cached_edu_teacher_snapshot',
        'cached_edu_teachers',
        'cached_edu_teacher_details',
        'pending_attendance',
        'pending_grades',
        'pending_hr',
        'attendance_preparation_snapshot',
        'grades_preparation_snapshot',
        'attendance_sync_receipt',
        'grades_sync_receipt',
        'cached_hr_sheet',
        'cached_hr_sections',
        // Comm tables (threads/messages/outbox/drafts/meta) ARE
        // member PII — wiped like everything else.
        'comm_threads',
        'comm_messages',
        'comm_outbox',
        'comm_drafts',
        'comm_meta',
        // P1-B: cached notification center rows are user-scoped
        // server responses — same wipe discipline as the comm store.
        'cached_notifications',
        'cached_announcements',
        'sync_log',
      ]) {
        await txn.delete(table);
      }
    });
    // secure_delete is enabled on open; checkpoint/truncate also discards WAL
    // pages that may contain the previous user's sensitive offline records.
    try { await db.rawQuery('PRAGMA wal_checkpoint(TRUNCATE)'); } catch (_) {}
    try { await db.execute('VACUUM'); } catch (_) {}
  }
}
