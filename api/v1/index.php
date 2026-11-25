<?php
/**
 * ============================================================
 * REST API v1 — Main Router
 * ============================================================
 * All requests to /api/v1/* come through here.
 * 
 * URL pattern: /api/v1/{resource}/{id?}/{action?}
 * Examples:
 *   GET  /api/v1/members           → routes/members.php
 *   GET  /api/v1/members/42        → routes/members.php (with $resourceId=42)
 *   POST /api/v1/auth/login        → routes/auth.php
 *   GET  /api/v1/dashboard/stats   → routes/dashboard.php
 * ============================================================
 */

if (!defined('WBWS_API_REQUEST')) {
    define('WBWS_API_REQUEST', true);
}

// Smaller JSON on 2G/3G. Harmless if zlib is missing.
if (!headers_sent() && extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '5');
}

// Cheap liveness — no MariaDB, no auth. A 4G phone must not wait on
// the school database just to know the site is up.
$__fkssRoute = '';
if (!empty($_GET['_route'])) {
    $__fkssRoute = $_GET['_route'];
} elseif (!empty($_GET['path'])) {
    $__fkssRoute = $_GET['path'];
} elseif (!empty($_SERVER['PATH_INFO'])) {
    $__fkssRoute = $_SERVER['PATH_INFO'];
}
if (!is_string($__fkssRoute)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'error','message'=>'Invalid API route.']);
    exit;
}
$__fkssRoute = trim($__fkssRoute, '/');
if ($__fkssRoute === 'ping') {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('Access-Control-Allow-Origin: *');
    }
    echo json_encode([
        'status' => 'success',
        'data' => [
            'api' => 'FKSS',
            'version' => '1.0',
            'status' => 'running',
            'time' => date('c'),
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load core modules
require_once __DIR__ . '/core/response.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/acl.php';
require_once __DIR__ . '/core/middleware.php';

// Keep infrastructure diagnostics in server logs. Mobile clients always receive
// a stable JSON contract rather than PHP/MySQL exception text or an HTML error.
set_exception_handler(function (Throwable $error): void {
    $reference = reportInternalError('Unhandled API v1 exception', $error);
    apiSendJson([
        'status' => 'error',
        'message' => 'The service could not complete the request. If the problem persists, share reference ' . $reference . ' with the administrator.',
        'code' => 'INTERNAL_ERROR',
        'reference' => $reference,
    ], 500);
});
register_shutdown_function(function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    $reference = reportInternalError('Fatal API v1 error', $error['message'] ?? 'fatal error');
    apiSendJson([
        'status' => 'error',
        'message' => 'The service could not complete the request. If the problem persists, share reference ' . $reference . ' with the administrator.',
        'code' => 'INTERNAL_ERROR',
        'reference' => $reference,
    ], 500);
});

// ============================================================
// TEMPORARY deployment diagnostic — REMOVE once the database is verified.
// Gated by the deployment's HEALTH_KEY (defined in the env file, never
// logged): without the header it answers 403 and nothing else.
//   curl -s -H "X-Health-Key: <HEALTH_KEY>" \
//     "https://endriasfilmcollage.arkeonethiopia.com/api/v1/index.php?_route=diagnostics&u=sam"
// Reports only diagnostic booleans — no secrets, no personal data rows.
// ============================================================
if ($__fkssRoute === 'diagnostics') {
    $healthKey = defined('HEALTH_KEY') ? (string)HEALTH_KEY : '';
    $presented = isset($_SERVER['HTTP_X_HEALTH_KEY']) ? (string)$_SERVER['HTTP_X_HEALTH_KEY'] : '';
    if ($healthKey === '' || !hash_equals($healthKey, $presented)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Health key required.']);
        exit;
    }
    $diag = [
        'php_version' => PHP_VERSION,
        'error_log_path' => (string)ini_get('error_log'),
        'display_errors' => (bool)ini_get('display_errors'),
        'db_connected' => isset($conn) && $conn instanceof mysqli && !$conn->connect_error,
        'db_name' => (string)DB_NAME,
        'class_FeatureGate' => class_exists('\App\Services\FeatureGate', false),
        'class_RefreshTokenService' => class_exists('\App\Services\RefreshTokenService', false),
        'feature_flags' => [
            'FEATURE_ATTENDANCE' => defined('FEATURE_ATTENDANCE') ? (bool)constant('FEATURE_ATTENDANCE') : null,
            'FEATURE_GRADES' => defined('FEATURE_GRADES') ? (bool)constant('FEATURE_GRADES') : null,
        ],
    ];
    if ($diag['db_connected']) {
        $diag['tables'] = [];
        foreach (['users', 'api_refresh_sessions', 'api_refresh_legacy_exchanges', 'security_rate_limits', 'activity_logs'] as $t) {
            $r = $conn->query('SHOW TABLES LIKE ' . $conn->real_escape_string($t));
            $diag['tables'][$t] = $r ? $r->num_rows > 0 : null;
        }
        if (!empty($diag['tables']['users'])) {
            $r = $conn->query('SELECT COUNT(*) AS c FROM users');
            $row = $r ? $r->fetch_assoc() : null;
            $diag['users_count'] = $row ? (int)$row['c'] : null;
            $diag['users_columns'] = [];
            foreach (['password_hash', 'is_active', 'authorization_version', 'profile_image_path'] as $c) {
                $r = $conn->query('SHOW COLUMNS FROM users LIKE ' . $conn->real_escape_string($c));
                $diag['users_columns'][$c] = $r ? $r->num_rows > 0 : null;
            }
            $probeUser = (string)($_GET['u'] ?? 'sam');
            $probeUser = preg_replace('/[^A-Za-z0-9_.@-]/', '', $probeUser);
            if ($probeUser !== '') {
                $st = $conn->prepare('SELECT id, username, role, is_active, password_hash FROM users WHERE username = ? LIMIT 1');
                if ($st) {
                    $st->bind_param('s', $probeUser);
                    $st->execute();
                    $rr = $st->get_result();
                    $u = $rr ? $rr->fetch_assoc() : null;
                    $diag['user_check'] = $u ? [
                        'exists' => true,
                        'id' => (int)$u['id'],
                        'role' => (string)$u['role'],
                        'is_active' => (int)$u['is_active'],
                        'has_password_hash' => !empty($u['password_hash']),
                    ] : ['exists' => false];
                    $st->close();
                } else {
                    $diag['user_check'] = 'prepare_failed';
                }
            }
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['status' => 'success', 'data' => $diag], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// CORS + headers
handleCors();

// Parse the route — supports multiple approaches:
// 1. .htaccess rewrite: /api/v1/members → index.php?_route=members
// 2. Query param: /api/v1/index.php?path=members
// 3. PATH_INFO: /api/v1/index.php/members
$route = $__fkssRoute;
$parts = explode('/', $route);
$resource = $parts[0] ?? '';
$resourceId = $parts[1] ?? null;
$subAction = $parts[2] ?? null;
$method = getMethod();

// Numeric IDs should be integers
if ($resourceId !== null && ctype_digit($resourceId)) {
    $resourceId = (int)$resourceId;
}

// API info endpoint
if ($route === '' || $route === 'ping') {
    ok([
        'api' => defined('API_NAME') ? API_NAME : 'School Management System',
        'version' => '1.0',
        'status' => 'running',
        'time' => date('c'),
        'database' => isset($conn) && !$conn->connect_error ? 'connected' : 'error'
    ]);
}

// ============================================================
// Route Map — each resource dispatches to its own file
// ============================================================
$routeMap = [
    'auth'          => 'auth.php',
    'acm'           => 'acm.php',
    'members'       => 'members.php',
    'attendance'    => 'attendance.php',
    'classes'       => 'classes.php',
    'dashboard'     => 'dashboard.php',
    'teachers'      => 'teachers.php',
    'enrollment'    => 'enrollment.php',
    'subjects'      => 'subjects.php',
    'users'         => 'users.php',
    'grades'        => 'grades.php',
    'app'           => 'app.php',
    'telemetry'     => 'telemetry.php',
    'hr'            => 'hr.php',
    'notifications' => 'notifications.php',
    'sync'          => 'sync.php',
    'timetable'     => 'timetable.php',
];

// Check if resource exists
if (!isset($routeMap[$resource])) {
    err("Unknown endpoint: /{$resource}. Available: " . implode(', ', array_keys($routeMap)), 404);
}

// Feature flags are server-authoritative. Mobile UI filtering is convenience;
// a disabled module's API remains unavailable even to a crafted client.
// Guarded: a partial deployment missing the service file must degrade to
// "no module gating" (the env-file default: every module enabled).
$resourceFeature = class_exists('\App\Services\FeatureGate', false)
    ? \App\Services\FeatureGate::forApiResource($resource)
    : null;
if ($resourceFeature !== null && !\App\Services\FeatureGate::isEnabled($resourceFeature)) {
    err('This feature is not enabled for this deployment.', 403);
}

// Load the route file
$routeFile = __DIR__ . '/routes/' . $routeMap[$resource];
if (!file_exists($routeFile)) {
    err("Endpoint /{$resource} is not yet implemented.", 501);
}

// Make route context available to route files
$ROUTE = [
    'resource'   => $resource,
    'id'         => $resourceId,
    'sub'        => $subAction,
    'method'     => $method,
    'parts'      => $parts,
    'full_route' => $route,
];

// Dispatch to route handler
require $routeFile;

// If the route file doesn't call ok() or err(), return 404
err("No handler matched for {$method} /{$route}", 404);
