<?php
/**
 * ════════════════════════════════════════════════════════════
 * HR Staff Attendance API (web dashboard) — month register
 * ════════════════════════════════════════════════════════════
 * The daily attendance register behind the HR dashboard's
 * Attendance section (2026-11-24): HR opens a month grid and
 * marks each active employee present / late / absent / excused.
 *
 * Reuses the mark mechanics of the retired section-based HR
 * domain (sql/026) — same status set, same UNIQUE(date, subject)
 * shape, marked_by + marked_at — but for employees in the new
 * `staff_attendance` domain (sql/099), without the taker/packet
 * workflow. The retired hr_* archive tables are never touched.
 *
 * Gated by the existing `attendance` feature
 * (FEATURE_ATTENDANCE, default on via school_config.php): when
 * attendance is switched off, this API fails closed with 403.
 *
 * Actions:
 *   month_get — staff + marks + per-employee summary for a month
 *   mark_set  — upsert or clear one cell (staff_id + date)
 *
 * Defense in depth:
 *   1. access_control.php ROLE_MAP limits this file to
 *      super_admin / school_admin / hr_dept.
 *   2. This file re-checks login + role + feature itself.
 *   3. CSRF on every POST; per-user rate limiting.
 *   4. Exceptions never leak internals.
 *   5. Prepared statements only.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/services/SecurityRateLimiter.php';

if (!defined('HR_STAFF_ATTENDANCE_API_VERSION')) define('HR_STAFF_ATTENDANCE_API_VERSION', 'hr-staff-attendance-1');

function hr_satt_respond(array $payload): void
{
    $payload['v'] = HR_STAFF_ATTENDANCE_API_VERSION;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(static function (\Throwable $e): void {
    $token = bin2hex(random_bytes(3));
    error_log('[hr-staff-attendance-unhandled #' . $token . '] ' . get_class($e) . ': '
        . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    hr_satt_respond([
        'status' => 'error',
        'message' => 'Unexpected server fault (log reference ' . $token . '). Please retry.',
    ]);
});

// ── 1. Auth (re-checked here even though ROLE_MAP already ran) ─
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    hr_satt_respond(['status' => 'error', 'message' => 'Please sign in again.']);
}
$adminId = (int)$_SESSION['admin_id'];
$role = (string)($_SESSION['admin_role'] ?? '');
if (!in_array($role, ['super_admin', 'school_admin', 'hr_dept'], true)) {
    http_response_code(403);
    hr_satt_respond(['status' => 'error', 'message' => 'You do not have permission to manage staff attendance.']);
}
// Existing attendance feature — HR staff register rides on it.
if (!feature_enabled('attendance')) {
    http_response_code(403);
    hr_satt_respond(['status' => 'error', 'code' => 'FEATURE_DISABLED', 'message' => 'The attendance feature is switched off in this deployment.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    hr_satt_respond(['status' => 'error', 'message' => 'POST only.']);
}
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrf($csrfToken)) {
    hr_satt_respond(['status' => 'error', 'message' => 'Security token expired. Please refresh the page and try again.']);
}

$rl = new \App\Services\SecurityRateLimiter($pdo ?? null, sys_get_temp_dir() . '/ssms_ratelimit');
$rlCheck = $rl->consume('hr_staff_attendance', 'user:' . $adminId, 120, 60);
if (!$rlCheck['allowed']) {
    http_response_code(429);
    hr_satt_respond(['status' => 'error', 'message' => 'Too many requests. Please wait a moment and try again.']);
}
if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    hr_satt_respond(['status' => 'error', 'message' => 'Database connection error.']);
}

$action = (string)($_POST['action'] ?? '');
$STATUSES = ['present', 'late', 'absent', 'excused'];

/** Validate YYYY-MM. Returns normalized month or null. */
function hr_satt_month(?string $raw): ?string
{
    $raw = trim((string)$raw);
    if (!preg_match('/^(\d{4})-(\d{2})$/', $raw, $m)) {
        return null;
    }
    if ((int)$m[2] < 1 || (int)$m[2] > 12) {
        return null;
    }
    return sprintf('%04d-%02d', (int)$m[1], (int)$m[2]);
}

/** Validate YYYY-MM-DD. Returns the date or null. */
function hr_satt_date(?string $raw): ?string
{
    $raw = trim((string)$raw);
    $dt = \DateTime::createFromFormat('Y-m-d', $raw);
    if (!$dt || $dt->format('Y-m-d') !== $raw) {
        return null;
    }
    return $raw;
}

// ── 2. Actions ──────────────────────────────────────────────

if ($action === 'month_get') {
    $month = hr_satt_month($_POST['month'] ?? null);
    if ($month === null) {
        http_response_code(400);
        hr_satt_respond(['status' => 'error', 'message' => 'Invalid month (expected YYYY-MM).']);
    }
    $firstDay = $month . '-01';
    $dayCount = (int)date('t', strtotime($firstDay));

    // Active staff, plus anyone who has marks in this month even if
    // they are now inactive (past months stay complete).
    $staffStmt = $pdo->prepare(
        'SELECT s.id, s.staff_code, s.full_name_am, s.full_name_en, s.position, s.department, s.status, s.photo
         FROM staff s
         WHERE s.status = \'active\'
            OR s.id IN (SELECT DISTINCT staff_id FROM staff_attendance WHERE attendance_date >= ? AND attendance_date < ?)
         ORDER BY s.full_name_en ASC, s.id ASC'
    );
    $monthEndTs = strtotime($firstDay) + 32 * 86400;
    $staffStmt->execute([$firstDay, date('Y-m-d', $monthEndTs)]);
    $staffRows = $staffStmt->fetchAll(\PDO::FETCH_ASSOC);

    $marksStmt = $pdo->prepare(
        'SELECT staff_id, attendance_date, status FROM staff_attendance
         WHERE attendance_date >= ? AND attendance_date < ?'
    );
    $marksStmt->execute([$firstDay, date('Y-m-d', $monthEndTs)]);

    $marks = [];
    $summaries = [];
    foreach ($marksStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
        $sid = (int)$r['staff_id'];
        $day = (int)substr($r['attendance_date'], 8, 2);
        $marks[$sid][(string)$day] = $r['status'];
        $summaries[$sid][$r['status']] = ($summaries[$sid][$r['status']] ?? 0) + 1;
    }
    foreach ($staffRows as $s) {
        $sid = (int)$s['id'];
        $row = $summaries[$sid] ?? ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
        $summaries[$sid] = [
            'present' => (int)($row['present'] ?? 0),
            'late'    => (int)($row['late'] ?? 0),
            'absent'  => (int)($row['absent'] ?? 0),
            'excused' => (int)($row['excused'] ?? 0),
        ];
    }

    hr_satt_respond([
        'status' => 'success',
        'month' => $month,
        'days_in_month' => $dayCount,
        'today' => date('Y-m-d'),
        'staff' => $staffRows,
        'marks' => $marks,
        'summaries' => $summaries,
    ]);
}

if ($action === 'mark_set') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $date = hr_satt_date($_POST['date'] ?? null);
    $status = (string)($_POST['status'] ?? '');
    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($staffId <= 0 || $date === null) {
        http_response_code(400);
        hr_satt_respond(['status' => 'error', 'message' => 'Missing staff ID or invalid date.']);
    }
    if ($date > date('Y-m-d')) {
        http_response_code(400);
        hr_satt_respond(['status' => 'error', 'message' => 'Attendance cannot be marked for a future date.']);
    }
    $clearing = $status === '';
    if (!$clearing && !in_array($status, $STATUSES, true)) {
        http_response_code(400);
        hr_satt_respond(['status' => 'error', 'message' => 'Invalid status.']);
    }
    if (mb_strlen($notes) > 255) {
        http_response_code(400);
        hr_satt_respond(['status' => 'error', 'message' => 'Note is too long (max 255 characters).']);
    }

    $check = $pdo->prepare('SELECT id FROM staff WHERE id = ?');
    $check->execute([$staffId]);
    if (!$check->fetch(\PDO::FETCH_ASSOC)) {
        hr_satt_respond(['status' => 'error', 'message' => 'Employee not found.']);
    }

    if ($clearing) {
        $del = $pdo->prepare('DELETE FROM staff_attendance WHERE staff_id = ? AND attendance_date = ?');
        $del->execute([$staffId, $date]);
    } else {
        $up = $pdo->prepare(
            'INSERT INTO staff_attendance (attendance_date, staff_id, status, notes, marked_by)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes), marked_by = VALUES(marked_by)'
        );
        $up->execute([$date, $staffId, $status, $notes !== '' ? $notes : null, $adminId]);
    }
    hr_satt_respond(['status' => 'success', 'date' => $date, 'staff_id' => $staffId, 'status' => $clearing ? null : $status]);
}

http_response_code(400);
hr_satt_respond(['status' => 'error', 'message' => 'Unknown action.']);
