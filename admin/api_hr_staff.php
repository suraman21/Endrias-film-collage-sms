<?php
/**
 * ════════════════════════════════════════════════════════════
 * HR Staff API (web dashboard) — employee roster CRUD
 * ════════════════════════════════════════════════════════════
 * The HR department is a company human-resources department
 * (2026-11-24): it manages the employee roster in the `staff`
 * domain (sql/099), fully separated from the admission/member
 * domain. This endpoint backs the HR dashboard's Staff section:
 *
 *   • list        — searchable roster (name, code, position,
 *                   phone) with status/department filters
 *   • stats       — dashboard cards (counts, departments,
 *                   today + current-month attendance snapshot)
 *   • create      — register an employee (reuses the member
 *                   registration form patterns: normalized
 *                   fields, photo upload, request-ID
 *                   idempotency). Mints the staff code.
 *   • update      — edit an employee (photo replace / remove)
 *   • set_status  — activate / deactivate
 *
 * Defense in depth:
 *   1. access_control.php ROLE_MAP limits this file to
 *      super_admin / school_admin / hr_dept.
 *   2. This file re-checks login + role itself.
 *   3. CSRF on every POST; per-user rate limiting.
 *   4. Exceptions never leak internals.
 *   5. Prepared statements only.
 *
 * Salary is stored data only — this API never computes or pays.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/services/SecurityRateLimiter.php';
require_once __DIR__ . '/backend/services/StaffFileService.php';
require_once __DIR__ . '/backend/services/ApiIdempotencyService.php';

if (!defined('HR_STAFF_API_VERSION')) define('HR_STAFF_API_VERSION', 'hr-staff-1');

function hr_staff_respond(array $payload): void
{
    $payload['v'] = HR_STAFF_API_VERSION;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(static function (\Throwable $e): void {
    $token = bin2hex(random_bytes(3));
    error_log('[hr-staff-unhandled #' . $token . '] ' . get_class($e) . ': '
        . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    hr_staff_respond([
        'status' => 'error',
        'message' => 'Unexpected server fault (log reference ' . $token . '). Please retry.',
    ]);
});

// ── 1. Auth (re-checked here even though ROLE_MAP already ran) ─
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    hr_staff_respond(['status' => 'error', 'message' => 'Please sign in again.']);
}
$adminId = (int)$_SESSION['admin_id'];
$role = (string)($_SESSION['admin_role'] ?? '');
if (!in_array($role, ['super_admin', 'school_admin', 'hr_dept'], true)) {
    http_response_code(403);
    hr_staff_respond(['status' => 'error', 'message' => 'You do not have permission to manage staff.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    hr_staff_respond(['status' => 'error', 'message' => 'POST only.']);
}
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!validateCsrf($csrfToken)) {
    hr_staff_respond(['status' => 'error', 'message' => 'Security token expired. Please refresh the page and try again.']);
}

// ── 2. Rate limiting (per user) ─
$rl = new \App\Services\SecurityRateLimiter($pdo ?? null, sys_get_temp_dir() . '/ssms_ratelimit');
$rlCheck = $rl->consume('hr_staff', 'user:' . $adminId, 120, 60);
if (!$rlCheck['allowed']) {
    http_response_code(429);
    hr_staff_respond(['status' => 'error', 'message' => 'Too many requests. Please wait a moment and try again.']);
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    hr_staff_respond(['status' => 'error', 'message' => 'Database connection error.']);
}

$action = (string)($_POST['action'] ?? '');

// ── Request payload (form-encoded or JSON body) ─
function hr_staff_input(): array
{
    $ctype = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    if (strpos($ctype, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }
    return $_POST;
}

// ── Field validators (same discipline as member registration) ─
function hr_staff_str(array $in, string $key, int $max, bool $required = false): array
{
    $value = trim((string)($in[$key] ?? ''));
    if (mb_strlen($value) > $max) {
        return [null, "“{$key}” is too long (max {$max} characters)."];
    }
    if ($required && $value === '') {
        return [null, "“{$key}” is required."];
    }
    return [$value, null];
}

function hr_staff_date(array $in, string $key): array
{
    $value = trim((string)($in[$key] ?? ''));
    if ($value === '') {
        return [null, null];
    }
    $dt = \DateTime::createFromFormat('Y-m-d', $value);
    if (!$dt || $dt->format('Y-m-d') !== $value) {
        return [null, "“{$key}” must be a valid date (YYYY-MM-DD)."];
    }
    return [$value, null];
}

function hr_staff_money(array $in, string $key): array
{
    $value = trim((string)($in[$key] ?? ''));
    if ($value === '') {
        return ['0.00', null];
    }
    if (!is_numeric($value) || (float)$value < 0 || (float)$value > 9999999999.99) {
        return [null, "“{$key}” must be a non-negative number."];
    }
    return [number_format((float)$value, 2, '.', ''), null];
}

/** Validate all roster fields. Returns [fields, error]. */
function hr_staff_validate(array $in, bool $forCreate): array
{
    $f = [];
    [$f['full_name_am'], $err] = hr_staff_str($in, 'full_name_am', 255, $forCreate);
    if ($err !== null) return [null, $err];
    [$f['full_name_en'], $err] = hr_staff_str($in, 'full_name_en', 255);
    if ($err !== null) return [null, $err];
    $gender = strtolower(trim((string)($in['gender'] ?? '')));
    if (!in_array($gender, ['male', 'female'], true)) {
        return [null, $forCreate ? 'Gender is required.' : null];
    }
    $f['gender'] = $gender;
    [$f['phone_number'], $err] = hr_staff_str($in, 'phone_number', 40);
    if ($err !== null) return [null, $err];
    [$f['email'], $err] = hr_staff_str($in, 'email', 255);
    if ($err !== null) return [null, $err];
    if ($f['email'] !== '' && filter_var($f['email'], FILTER_VALIDATE_EMAIL) === false) {
        return [null, 'Email is not valid.'];
    }
    [$f['position'], $err] = hr_staff_str($in, 'position', 255);
    if ($err !== null) return [null, $err];
    [$f['department'], $err] = hr_staff_str($in, 'department', 120);
    if ($err !== null) return [null, $err];
    [$f['hire_date'], $err] = hr_staff_date($in, 'hire_date');
    if ($err !== null) return [null, $err];
    [$f['salary_amount'], $err] = hr_staff_money($in, 'salary_amount');
    if ($err !== null) return [null, $err];
    [$f['salary_notes'], $err] = hr_staff_str($in, 'salary_notes', 255);
    if ($err !== null) return [null, $err];
    [$f['emergency_name'], $err] = hr_staff_str($in, 'emergency_name', 255);
    if ($err !== null) return [null, $err];
    [$f['emergency_phone'], $err] = hr_staff_str($in, 'emergency_phone', 40);
    if ($err !== null) return [null, $err];
    [$f['address'], $err] = hr_staff_str($in, 'address', 255);
    if ($err !== null) return [null, $err];
    [$f['notes'], $err] = hr_staff_str($in, 'notes', 5000);
    if ($err !== null) return [null, $err];
    return [$f, null];
}

/** Staff code: STF- + random 5-digit tail, unique (identity-style allocation). */
function hr_staff_allocate_code(\PDO $pdo): string
{
    $check = $pdo->prepare('SELECT 1 FROM staff WHERE staff_code = ?');
    for ($i = 0; $i < 10; $i++) {
        $code = 'STF-' . str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        $check->execute([$code]);
        if (!$check->fetch(\PDO::FETCH_ASSOC)) {
            return $code;
        }
    }
    return 'STF-' . str_pad((string)(time() % 90000 + 10000), 5, '0', STR_PAD_LEFT);
}

// ── 3. Actions ──────────────────────────────────────────────

if ($action === 'list') {
    $q = trim((string)($_POST['q'] ?? ''));
    $status = (string)($_POST['status'] ?? '');
    $department = trim((string)($_POST['department'] ?? ''));
    $limit = min(200, max(1, (int)($_POST['limit'] ?? 50)));
    $offset = max(0, (int)($_POST['offset'] ?? 0));

    $where = [];
    $params = [];
    if ($q !== '') {
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $where[] = '(staff.full_name_am LIKE ? OR staff.full_name_en LIKE ? OR staff.staff_code LIKE ? OR staff.position LIKE ? OR staff.phone_number LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if (in_array($status, ['active', 'inactive'], true)) {
        $where[] = 'staff.status = ?';
        $params[] = $status;
    }
    if ($department !== '') {
        $where[] = 'staff.department = ?';
        $params[] = $department;
    }
    $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

    $count = $pdo->prepare('SELECT COUNT(*) FROM staff ' . $whereSql);
    $count->execute($params);
    $total = (int)$count->fetchColumn();

    $sql = 'SELECT id, staff_code, full_name_am, full_name_en, gender, phone_number, email, '
         . 'position, department, hire_date, salary_amount, salary_notes, photo, '
         . 'emergency_name, emergency_phone, address, status, notes, created_at, updated_at '
         . 'FROM staff ' . $whereSql . ' ORDER BY staff.full_name_en ASC, staff.id ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['salary_amount'] = (string)$row['salary_amount'];
    }
    unset($row);

    $depts = $pdo->query('SELECT DISTINCT department FROM staff WHERE department <> \'\' ORDER BY department ASC')->fetchAll(\PDO::FETCH_COLUMN);

    hr_staff_respond(['status' => 'success', 'rows' => $rows, 'total' => $total, 'departments' => $depts]);
}

if ($action === 'stats') {
    $one = static function (string $sql): int {
        global $pdo;
        return (int)$pdo->query($sql)->fetchColumn();
    };
    $total = $one('SELECT COUNT(*) FROM staff');
    $active = $one('SELECT COUNT(*) FROM staff WHERE status = \'active\'');
    $inactive = $total - $active;
    $hiredThisMonth = $one('SELECT COUNT(*) FROM staff WHERE status = \'active\' AND hire_date IS NOT NULL AND DATE_FORMAT(hire_date, \'%Y-%m\') = DATE_FORMAT(CURDATE(), \'%Y-%m\')');

    $byDept = $pdo->query('SELECT COALESCE(NULLIF(department, \'\'), \'—\') AS department, COUNT(*) AS c FROM staff WHERE status = \'active\' GROUP BY 1 ORDER BY c DESC, department ASC')->fetchAll(\PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    $month = date('Y-m');
    $todayRows = $pdo->prepare('SELECT status, COUNT(*) AS c FROM staff_attendance WHERE attendance_date = ? AND staff_id IN (SELECT id FROM staff WHERE status = \'active\') GROUP BY status');
    $todayRows->execute([$today]);
    $todayCounts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
    foreach ($todayRows->fetchAll(\PDO::FETCH_ASSOC) as $r) {
        if (isset($todayCounts[$r['status']])) {
            $todayCounts[$r['status']] = (int)$r['c'];
        }
    }
    $todayMarked = array_sum($todayCounts);

    $monthRows = $pdo->prepare('SELECT status, COUNT(*) AS c FROM staff_attendance WHERE attendance_date >= ? AND staff_id IN (SELECT id FROM staff WHERE status = \'active\') GROUP BY status');
    $monthRows->execute([$month . '-01']);
    $monthCounts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
    foreach ($monthRows->fetchAll(\PDO::FETCH_ASSOC) as $r) {
        if (isset($monthCounts[$r['status']])) {
            $monthCounts[$r['status']] = (int)$r['c'];
        }
    }
    $monthMarks = array_sum($monthCounts);
    // Attendance rate: share of recorded employee-days (present/late/excused)
    // so far in the current month. No marks yet → null (UI shows —).
    $monthRate = $monthMarks > 0 ? round(100 * ($monthCounts['present'] + $monthCounts['late'] + $monthCounts['excused']) / $monthMarks, 1) : null;

    hr_staff_respond([
        'status' => 'success',
        'total' => $total,
        'active' => $active,
        'inactive' => $inactive,
        'hired_this_month' => $hiredThisMonth,
        'by_department' => array_map(static fn($r) => ['department' => $r['department'], 'count' => (int)$r['c']], $byDept),
        'attendance_today' => ['marked' => $todayMarked, 'active_staff' => $active] + $todayCounts,
        'attendance_month' => ['marks' => $monthMarks, 'rate' => $monthRate] + $monthCounts,
    ]);
}

if ($action === 'create') {
    $in = hr_staff_input();
    [$fields, $err] = hr_staff_validate($in, true);
    if ($err !== null) {
        http_response_code(422);
        hr_staff_respond(['status' => 'error', 'message' => $err]);
    }

    // Idempotency (registration request ID, same contract as member
    // registration): a retried create with the same ID + payload
    // replays the original response instead of double-inserting.
    $keyRaw = (string)($in['staff_request_id'] ?? ($_POST['staff_request_id'] ?? ''));
    $key = trim($keyRaw);
    if (strlen($key) < 16 || strlen($key) > 80 || !preg_match('/^[A-Za-z0-9._-]+$/', $key)) {
        http_response_code(400);
        hr_staff_respond(['status' => 'error', 'message' => 'A valid staff request ID is required. Refresh and retry.']);
    }
    $canonical = [
        'action' => 'create',
        'fields' => $fields,
        'file' => (isset($_FILES['staff_photo']) && (int)($_FILES['staff_photo']['error'] ?? \UPLOAD_ERR_NO_FILE) === \UPLOAD_ERR_OK
            && is_uploaded_file((string)($_FILES['staff_photo']['tmp_name'] ?? '')))
            ? (hash_file('sha256', (string)$_FILES['staff_photo']['tmp_name']) ?: '')
            : '',
    ];
    $requestHash = hash('sha256', 'POST\0/admin/api_hr_staff.php\0' . json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $service = new \App\Services\ApiIdempotencyService($conn ?? null, ROOT_PATH . '/admin/uploads/cache');
    $result = $service->begin($adminId, $key, 'POST /admin/api_hr_staff.php#create', $requestHash);
    $state = (string)($result['state'] ?? 'unavailable');
    if ($state === 'replay') {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code((int)($result['status_code'] ?? 200));
        header('Idempotency-Replayed: true');
        echo (string)($result['body'] ?? '');
        exit;
    }
    if ($state === 'conflict') {
        http_response_code(409);
        hr_staff_respond(['status' => 'error', 'message' => 'This staff request ID was reused for different data.']);
    }
    if ($state === 'processing') {
        header('Retry-After: ' . max(1, (int)($result['retry_after'] ?? 1)));
        http_response_code(409);
        hr_staff_respond(['status' => 'error', 'message' => 'This staff registration is already processing.']);
    }
    if ($state !== 'acquired') {
        // Idempotency unavailable — proceed without the guard (the
        // unique staff_code still prevents corrupt duplicates).
    }

    $photo = \App\Services\StaffFileService::storeRequestUpload('staff_photo');
    if ($photo['error'] !== null) {
        if ($state === 'acquired') { $service->abandon($result); }
        http_response_code(422);
        hr_staff_respond(['status' => 'error', 'message' => 'Photo upload failed: ' . $photo['error']]);
    }

    $code = hr_staff_allocate_code($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO staff (staff_code, full_name_am, full_name_en, gender, phone_number, email, '
        . 'position, department, hire_date, salary_amount, salary_notes, photo, emergency_name, '
        . 'emergency_phone, address, status, notes, created_by) '
        . 'VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $code, $fields['full_name_am'], $fields['full_name_en'], $fields['gender'],
        $fields['phone_number'], $fields['email'], $fields['position'], $fields['department'],
        $fields['hire_date'], $fields['salary_amount'], $fields['salary_notes'], $photo['path'],
        $fields['emergency_name'], $fields['emergency_phone'], $fields['address'],
        'active', $fields['notes'] !== '' ? $fields['notes'] : null, $adminId,
    ]);
    $id = (int)$pdo->lastInsertId();

    $response = ['status' => 'success', 'id' => $id, 'staff_code' => $code,
                 'message' => 'Employee registered: ' . $fields['full_name_am'] . ' (' . $code . ')'];
    if ($state === 'acquired') {
        $service->complete($result, (string)json_encode($response, JSON_UNESCAPED_UNICODE), 200);
    }
    hr_staff_respond($response);
}

if ($action === 'update') {
    $in = hr_staff_input();
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        hr_staff_respond(['status' => 'error', 'message' => 'Missing employee ID.']);
    }
    $stmt = $pdo->prepare('SELECT * FROM staff WHERE id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$current) {
        hr_staff_respond(['status' => 'error', 'message' => 'Employee not found.']);
    }

    // Merge posted fields over the current row, then validate the whole.
    $merged = [];
    foreach (['full_name_am', 'full_name_en', 'phone_number', 'email', 'position',
              'department', 'salary_notes', 'emergency_name', 'emergency_phone',
              'address', 'notes'] as $k) {
        $merged[$k] = $in[$k] ?? $current[$k];
    }
    $merged['gender'] = $in['gender'] ?? $current['gender'];
    $merged['hire_date'] = isset($in['hire_date']) ? $in['hire_date'] : ($current['hire_date'] ? substr($current['hire_date'], 0, 10) : '');
    $merged['salary_amount'] = $in['salary_amount'] ?? $current['salary_amount'];

    [$fields, $err] = hr_staff_validate($merged, true);
    if ($err !== null) {
        http_response_code(422);
        hr_staff_respond(['status' => 'error', 'message' => $err]);
    }

    $photoPath = $current['photo'];
    if (isset($_FILES['staff_photo'])) {
        $photo = \App\Services\StaffFileService::storeRequestUpload('staff_photo');
        if ($photo['error'] !== null) {
            http_response_code(422);
            hr_staff_respond(['status' => 'error', 'message' => 'Photo upload failed: ' . $photo['error']]);
        }
        if ($photo['path'] !== null) {
            \App\Services\StaffFileService::discard($photoPath);
            $photoPath = $photo['path'];
        }
    } elseif (isset($in['remove_photo']) && trim((string)$in['remove_photo']) === '1') {
        \App\Services\StaffFileService::discard($photoPath);
        $photoPath = null;
    }

    $upd = $pdo->prepare(
        'UPDATE staff SET full_name_am=?, full_name_en=?, gender=?, phone_number=?, email=?, '
        . 'position=?, department=?, hire_date=?, salary_amount=?, salary_notes=?, photo=?, '
        . 'emergency_name=?, emergency_phone=?, address=?, notes=? WHERE id=?'
    );
    $upd->execute([
        $fields['full_name_am'], $fields['full_name_en'], $fields['gender'],
        $fields['phone_number'], $fields['email'], $fields['position'], $fields['department'],
        $fields['hire_date'], $fields['salary_amount'], $fields['salary_notes'], $photoPath,
        $fields['emergency_name'], $fields['emergency_phone'], $fields['address'],
        $fields['notes'] !== '' ? $fields['notes'] : null, $id,
    ]);
    hr_staff_respond(['status' => 'success', 'message' => 'Employee updated.']);
}

if ($action === 'set_status') {
    $in = hr_staff_input();
    $id = (int)($in['id'] ?? 0);
    $status = (string)($in['status'] ?? '');
    if ($id <= 0 || !in_array($status, ['active', 'inactive'], true)) {
        http_response_code(400);
        hr_staff_respond(['status' => 'error', 'message' => 'Missing employee ID or invalid status.']);
    }
    $stmt = $pdo->prepare('UPDATE staff SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    hr_staff_respond(['status' => 'success', 'message' => $status === 'active' ? 'Employee activated.' : 'Employee deactivated.']);
}

http_response_code(400);
hr_staff_respond(['status' => 'error', 'message' => 'Unknown action.']);
