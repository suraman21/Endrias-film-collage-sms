/// Declarative SQLite v44 schema contract — Mezmur department removal.
///
/// Version 44 removes the retired Mezmur department's offline tables from the
/// device. The server module, the mobile screens and the sync workers that
/// owned these tables no longer exist, so any rows left behind are dead data
/// that can never be read or uploaded again. The migration is a pure drop:
/// `DROP TABLE IF EXISTS` is repeat-safe, is a no-op on a fresh install (the
/// tables are never created any more) and runs inside sqflite's single
/// onUpgrade transaction.
///
/// These statements are referenced from `local_db.dart`'s `onCreate`/`onUpgrade`
/// harness through `_migrateToV44`.
const localDatabaseSchemaVersionV44 = 44;

/// Every local table the Mezmur module owned, in dependency-free order.
///
/// Kept as one list so the removal is auditable in a single place. A name that
/// no longer exists on a given device is simply skipped.
const mezmurRemovalV44Tables = <String>[
  // Offline attendance outbox + sheet/section/day read caches.
  'pending_mezmur',
  'cached_mezmur_sheet',
  'cached_mezmur_sheet_v2',
  'cached_mezmur_sections',
  'cached_mezmur_days',
  'cached_mezmur_analytics_last',
  // Shared hymn library: corpus, taxonomy, search indexes, sync cursor.
  'cached_hymns',
  'cached_mezmur_categories',
  'cached_hymn_categories',
  'cached_mezmur_zemarians',
  'cached_hymn_zemarians',
  'hymn_search_words',
  'hymn_search_grams',
  'hymn_search_dirty',
  'hymn_search_meta',
  'hymn_sync_meta',
  'pending_hymn_ops',
  // Streaming-style offline audio downloads.
  'hymn_downloads',
  'hymn_download_pins',
];

/// Drop statements for [mezmurRemovalV44Tables].
List<String> get mezmurRemovalV44Sql => [
      for (final table in mezmurRemovalV44Tables)
        'DROP TABLE IF EXISTS $table',
    ];
