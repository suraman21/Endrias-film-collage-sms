<?php
/**
 * HR Department dashboard — company human resources (2026-11-24).
 *
 * HR is now a real HR department: employee roster, daily attendance
 * register, staff ID cards and staff registration — fully separated
 * from the admission/member domain, which the registrar keeps
 * (students, family link, archive, student ID cards).
 *
 * Reused product building blocks:
 *   • attendance  — the mark mechanics of the retired section-based
 *                   HR domain (sql/026), now a month grid over the
 *                   new staff_attendance register (sql/099)
 *   • ID cards    — the shared id_card_template_layout.php via
 *                   id_cards/view_staff_id_card.php
 *   • registration— the member-registration form patterns (field
 *                   normalization, photo upload, request-ID
 *                   idempotency) via api_hr_staff.php#create
 *
 * Data flows through the governed APIs (admin/api_hr_staff.php,
 * admin/api_hr_staff_attendance.php); this page renders the stats
 * server-side for first paint and drives the sections with fetch.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../backend/ethiopian_date.php';
require_once __DIR__ . '/../backend/calendar_system.php';
// session is already started and role checked in dashboard.php

// ------------------------------------------------------------
// Auth / session context
// ------------------------------------------------------------
$userName = (string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'HR Department');
$userRole = (string) ($_SESSION['admin_role'] ?? 'HR Department');
$username = $_SESSION['admin_username'] ?? '';

$today = new DateTime('now', new DateTimeZone('Africa/Addis_Ababa'));
$todayFormatted = ethio_date_format($today, 'F j, Y');

// Determine path prefix for AJAX calls (mirrors admissions/info-dept)
$isInDashboardSubdir = false;
if (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/dashboards/') !== false) {
    $isInDashboardSubdir = true;
}
$ajaxPrefix = $isInDashboardSubdir ? '../' : '';

// Feature flags (existing features — HR rides on them)
$attendanceFeature = feature_enabled('attendance');
$idCardsFeature = feature_enabled('id_cards');

// ------------------------------------------------------------
// Live stats (server-side first paint; the APIs are the source
// of truth for the interactive sections)
// ------------------------------------------------------------
function hr_q_int(mysqli $conn, string $sql): int
{
    $r = $conn->query($sql);
    if (!$r) {
        return 0;
    }
    $row = $r->fetch_row();
    return $row ? (int)$row[0] : 0;
}

function hr_q_rows(mysqli $conn, string $sql): array
{
    $r = $conn->query($sql);
    $rows = [];
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function hr_att_counts(mysqli $conn, string $whereSql, array $params): array
{
    $counts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
    $sql = 'SELECT sa.status, COUNT(*) AS c FROM staff_attendance sa JOIN staff s ON s.id = sa.staff_id AND s.status = \'active\'' . $whereSql . ' GROUP BY sa.status';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $counts;
    }
    $stmt->bind_param('s', $params[0]);
    if ($stmt->execute() && $res = $stmt->get_result()) {
        while ($row = $res->fetch_assoc()) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['c'];
            }
        }
    }
    $stmt->close();
    return $counts;
}

// Attendance alerting (existing shared features HR reads):
// • Phase 2 — open attendance warnings per department
// • Phase 4 — current EC-month Education roll-up ("Monthly Attendance")
require_once __DIR__ . '/../backend/services/AttendanceWarningService.php';
require_once __DIR__ . '/../backend/services/AttendanceMonthRollupService.php';
$attendanceMonthCard = ['available' => false, 'month_label' => '', 'month_label_am' => '', 'below' => 0, 'avg_rate' => null, 'per_dept' => []];
$attendanceWarningsAvailable = false;
$attendanceWarningCounts = ['edu' => ['total' => 0, 'critical' => 0]];
if (isset($conn) && $conn instanceof mysqli) {
    try {
        $attendanceMonthCard = \App\Services\AttendanceMonthRollupService::dashboardCard($conn, ['edu']);
        $attendanceWarningsAvailable = \App\Services\AttendanceWarningService::available($conn);
        if ($attendanceWarningsAvailable) {
            $attendanceWarningCounts = \App\Services\AttendanceWarningService::openCounts($conn);
        }
    } catch (\Throwable $e) {
        error_log('HR dashboard attendance cards failed: ' . $e->getMessage());
    }
}

$staffTotal = 0;
$staffActive = 0;
$staffInactive = 0;
$hiredThisMonth = 0;
$byDepartment = [];
$todayCounts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
$monthCounts = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0];
$todayMarked = 0;
$monthRate = null;

if (isset($conn) && $conn instanceof mysqli) {
    try {
        $staffTotal = hr_q_int($conn, 'SELECT COUNT(*) FROM staff');
        $staffActive = hr_q_int($conn, 'SELECT COUNT(*) FROM staff WHERE status = \'active\'');
        $staffInactive = $staffTotal - $staffActive;
        $hiredThisMonth = hr_q_int($conn, "SELECT COUNT(*) FROM staff WHERE status = 'active' AND hire_date IS NOT NULL AND DATE_FORMAT(hire_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $byDepartment = hr_q_rows($conn, "SELECT COALESCE(NULLIF(department, ''), '—') AS department, COUNT(*) AS c FROM staff WHERE status = 'active' GROUP BY 1 ORDER BY c DESC, department ASC");
        $todayCounts = hr_att_counts($conn, ' WHERE sa.attendance_date = CURDATE()', [date('Y-m-d')]);
        $todayMarked = array_sum($todayCounts);
        $monthCounts = hr_att_counts($conn, " WHERE sa.attendance_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')", [date('Y-m-01')]);
        $monthMarks = array_sum($monthCounts);
        $monthRate = $monthMarks > 0 ? round(100 * ($monthCounts['present'] + $monthCounts['late'] + $monthCounts['excused']) / $monthMarks, 1) : null;
    } catch (Throwable $e) {
        error_log('HR dashboard stats failed: ' . $e->getMessage());
    }
}
$deptMax = 0;
foreach ($byDepartment as $d) {
    $deptMax = max($deptMax, (int)$d['c']);
}

$e = static function (?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Department - <?= SCHOOL_NAME_SHORT_AM ?></title>
    <?= wbws_calendar_scripts($conn) ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <script>
        // Global CSRF token for fetch requests
        const CSRF_TOKEN = '<?= generateCsrfToken() ?>';
    </script>

    <link rel="icon"
          href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛪</text></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/admin/js/request-id.js"></script>
    <script src="/admin/js/danger_confirm.js?v=20261022dc4"></script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+Ethiopic:wght@400;600;700&family=Poppins:wght@300;400;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            background: #f5f7fb;
        }

        .amharic-text {
            font-family: 'Noto Serif Ethiopic', serif;
        }

        .sidebar-gradient {
            width: 260px;
            background: linear-gradient(180deg, #064e3b 0%, #047857 40%, #16a34a 100%);
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
            overflow-y: auto;
            color: #ecfdf5;
            padding: 18px 16px 20px;
        }

        .stat-card {
            border-radius: 1.5rem;
            color: #ffffff;
            box-shadow: 0 12px 25px rgba(15, 118, 110, 0.35);
            transition: transform 0.18s ease-out, box-shadow 0.18s ease-out;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(15, 118, 110, 0.45);
        }

        .panel {
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(148, 163, 184, 0.35);
        }

        .nav-pill-active {
            background: rgba(255, 255, 255, 0.20);
            box-shadow: inset 3px 0 0 #fff;
        }

        .content-section { display: none; }
        .content-section.active { display: block; }

        /* Staff table */
        .staff-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .78rem; }
        .staff-table th { text-align: left; font-size: .62rem; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; padding: .5rem .65rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; white-space: nowrap; }
        .staff-table td { padding: .6rem .65rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .staff-table tr:hover td { background: #f0fdf4; }
        .staff-avatar { width: 2.2rem; height: 2.2rem; border-radius: .8rem; object-fit: cover; background: #e2e8f0; display: block; }
        .staff-avatar-fallback { width: 2.2rem; height: 2.2rem; border-radius: .8rem; background: linear-gradient(135deg, #059669, #0ea5e9); color: #fff; font-weight: 800; font-size: .9rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pill { display: inline-block; font-size: .64rem; font-weight: 700; border-radius: 999px; padding: .12rem .55rem; }
        .pill-active { background: #dcfce7; color: #166534; }
        .pill-inactive { background: #f1f5f9; color: #64748b; }
        .icon-btn { width: 1.9rem; height: 1.9rem; border-radius: .55rem; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer; transition: all .12s; }
        .icon-btn:hover { border-color: #34d399; color: #047857; background: #f0fdf4; }
        .icon-btn-danger:hover { border-color: #fda4af; color: #be123c; background: #fff1f2; }

        /* Attendance register */
        .att-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; }
        .att-table { border-collapse: separate; border-spacing: 0; font-size: .72rem; min-width: 720px; }
        .att-table th, .att-table td { border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 0; }
        .att-table th { font-size: .6rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; padding: .45rem .3rem; background: #f8fafc; font-weight: 700; text-align: center; white-space: nowrap; }
        .att-table td { text-align: center; }
        .att-name-cell { text-align: left !important; padding: .45rem .6rem !important; min-width: 180px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; color: #0f172a; background: #fff; position: sticky; left: 0; z-index: 2; box-shadow: 1px 0 0 #e2e8f0; }
        .att-day { width: 2.35rem; height: 2.35rem; display: flex; align-items: center; justify-content: center; cursor: pointer; user-select: none; font-weight: 700; font-size: .72rem; color: #cbd5e1; border-radius: .4rem; margin: .18rem; transition: all .1s; }
        .att-day:hover { background: #f1f5f9; color: #334155; }
        .att-day.future { cursor: not-allowed; opacity: .35; }
        .att-day.s-p { background: #dcfce7; color: #166534; }
        .att-day.s-l { background: #fef3c7; color: #92400e; }
        .att-day.s-a { background: #fee2e2; color: #b91c1c; }
        .att-day.s-e { background: #e0e7ff; color: #3730a3; }
        .att-sum { min-width: 3.4rem; font-weight: 700; font-size: .7rem; color: #334155; padding: .45rem .3rem !important; }
        .att-legend { display: flex; gap: .9rem; flex-wrap: wrap; align-items: center; font-size: .7rem; color: #475569; }
        .att-legend .sw { width: .95rem; height: .95rem; border-radius: .3rem; display: inline-block; margin-right: .3rem; vertical-align: -2px; }

        /* ID cards section */
        .idc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .8rem; }
        .idc-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem; transition: all .15s; }
        .idc-card:hover { box-shadow: 0 10px 24px rgba(148,163,184,.3); transform: translateY(-2px); }

        /* Modal */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .5); z-index: 90; display: none; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 2.5rem 1rem; }
        .modal-backdrop.open { display: flex; }
        .modal-panel { background: #fff; border-radius: 1.2rem; width: 100%; max-width: 720px; box-shadow: 0 25px 60px rgba(0,0,0,.3); }
        .fld { width: 100%; padding: .55rem .75rem; border: 1px solid #e2e8f0; border-radius: .7rem; font-size: .8rem; background: #fff; color: #0f172a; }
        .fld:focus { outline: none; border-color: #34d399; box-shadow: 0 0 0 3px rgba(52,211,153,.2); }
        .fld-lbl { display: block; font-size: .62rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .25rem; }

        .toast { position: fixed; left: 50%; bottom: 1.5rem; transform: translateX(-50%) translateY(20px); opacity: 0; background: #0f172a; color: #fff; font-size: .78rem; padding: .65rem 1.1rem; border-radius: .8rem; z-index: 200; transition: all .25s; max-width: 90vw; pointer-events: none; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast-err { background: #be123c; }

        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { scrollbar-width: none; }

        /* Mobile */
        @media (max-width: 768px) {
            .content-wrap { padding-bottom: 76px; }
        }
        .bottom-nav-shadow { box-shadow: 0 -6px 18px rgba(0,0,0,.12); }
        .mobile-touch-target { min-height: 44px; }
    </style>
</head>
<body class="bg-slate-100">
<?php if (function_exists("ay_context_bar_html")) echo ay_context_bar_html($conn ?? null); ?>

<div class="min-h-screen flex flex-col md:flex-row">

    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex sidebar-gradient school-sidebar text-white w-64 flex-col py-5 px-4">
        <div class="flex items-center mb-6">
            <div class="w-11 h-11 rounded-2xl bg-white/15 flex items-center justify-center shadow-md">
                <i class="fa-solid fa-users-gear text-xl"></i>
            </div>
            <div class="ml-3">
                <div class="text-sm font-bold amharic-text">HR Department</div>
                <div class="text-[11px] text-emerald-100 amharic-text"><?= SCHOOL_NAME_SHORT_AM ?> — የሰው ሀይል ክፍል</div>
            </div>
        </div>

        <nav class="flex-1 flex flex-col space-y-1 text-sm">
            <a href="#" data-section="dashboard"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-gauge text-sm"></i>
                </span>
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="#" data-section="staff"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-users text-sm"></i>
                </span>
                <span class="font-semibold">Staff</span>
                <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-white/20"><?= $staffActive ?></span>
            </a>

            <?php if ($attendanceFeature): ?>
            <a href="#" data-section="staff-attendance"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check text-sm"></i>
                </span>
                <span class="font-semibold">Attendance</span>
            </a>
            <?php endif; ?>

            <?php if ($idCardsFeature): ?>
            <a href="#" data-section="idcards"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-id-card text-sm"></i>
                </span>
                <span class="font-semibold">ID Cards</span>
            </a>
            <?php endif; ?>

            <a href="#" data-section="reports"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-chart-column text-sm"></i>
                </span>
                <span class="font-semibold">Attendance Reports</span>
            </a>

            <button type="button" data-comm-open="inbox"
                    class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition text-left">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-comments text-sm"></i>
                </span>
                <span class="font-semibold">Communication</span>
            </button>

            <a href="#" data-section="settings"
               class="mobile-touch-target flex items-center gap-3 px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-gear text-sm"></i>
                </span>
                <span class="font-semibold">Settings</span>
            </a>
        </nav>

        <div class="mt-4 pt-4 border-t border-white/15 text-[11px] text-emerald-100/80">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-user"></i>
                <span class="truncate"><?= e($userName) ?></span>
            </div>
            <div class="text-emerald-200/60 mt-0.5"><?= e($userRole) ?></div>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 min-w-0">

        <!-- Header (title + bell + user) -->
        <div class="bg-emerald-800 text-white px-4 py-3 flex items-center justify-between shadow">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fa-solid fa-users-gear"></i>
                </span>
                <div>
                    <h1 class="text-sm sm:text-base font-bold amharic-text leading-tight">HR Department</h1>
                    <p class="text-[10px] sm:text-[11px] text-emerald-100 amharic-text leading-tight">የሰው ሀይል ክፍል · ራተኞች</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php
                include __DIR__ . '/../components/notification_center.php';
                echo renderNotificationCenter();
                ?>
                <div class="hidden sm:flex flex-col text-right text-xs">
                    <span class="font-semibold"><?= e($todayFormatted) ?></span>
                    <span class="text-emerald-100"><?= e($userName) ?></span>
                </div>
                <div class="w-9 h-9 rounded-full bg-white/95 flex items-center justify-center text-xs font-bold text-emerald-700 sm:hidden">
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                </div>
            </div>
        </div>

        <div class="content-wrap p-4 sm:p-6 max-w-[1200px] mx-auto">

            <!-- ══════════════ DASHBOARD ══════════════ -->
            <section id="section-dashboard" class="content-section active">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2 text-emerald-700">
                            <i class="fa-solid fa-gauge-high text-sm"></i>
                            <span class="uppercase tracking-wide text-[11px] font-semibold">Dashboard</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-800">
                            Human Resources Overview
                        </h2>
                    </div>
                    <span class="px-3 py-1.5 rounded-full border border-emerald-200 text-emerald-700 bg-white text-xs w-fit">Live statistics</span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4">
                    <div class="stat-card p-3 sm:p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, #064e3b, #047857, #16a34a)">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">Active Staff</div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center"><i class="fa-solid fa-user-check text-sm"></i></div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold"><?= $staffActive ?></div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">of <?= $staffTotal ?> total employees</div>
                    </div>

                    <?php if ($attendanceFeature): ?>
                    <div class="stat-card p-3 sm:p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, #92400e, #d97706, #f59e0b)">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">Today</div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center"><i class="fa-solid fa-clipboard-check text-sm"></i></div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold"><?= $todayMarked ?><?= $staffActive > 0 ? ' / ' . $staffActive : '' ?></div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">attendance marked (P <?= $todayCounts['present'] ?> · L <?= $todayCounts['late'] ?> · A <?= $todayCounts['absent'] ?>)</div>
                    </div>
                    <div class="stat-card p-3 sm:p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, #115e59, #0d9488, #2dd4bf)">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">This Month</div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center"><i class="fa-solid fa-chart-line text-sm"></i></div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold"><?= $monthRate === null ? '—' : $monthRate . '%' ?></div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">attendance rate so far this month</div>
                    </div>
                    <?php endif; ?>

                    <div class="stat-card p-3 sm:p-4 flex flex-col justify-between" style="background:linear-gradient(135deg, #1e3a8a, #2563eb, #60a5fa)">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">New This Month</div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center"><i class="fa-solid fa-user-plus text-sm"></i></div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold"><?= $hiredThisMonth ?></div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">new employee<?= $hiredThisMonth === 1 ? '' : 's' ?> joined</div>
                    </div>
                </div>

                <!-- School attendance monitoring (existing shared features HR
                     reads: Education warnings + monthly roll-up, read-only). -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4">
                    <a href="/admin/attendance_warnings.php" class="stat-card bg-gradient-to-tr from-red-500 via-rose-500 to-orange-400 p-3 sm:p-4 flex flex-col justify-between" id="aw-dashboard-card" title="Open the attendance warnings list" style="text-decoration:none;color:inherit">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">Open Attendance Warnings</div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center">
                                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                            </div>
                        </div>
                        <?php if ($attendanceWarningsAvailable): ?>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold" data-aw-total>
                            <?= (int)$attendanceWarningCounts['edu']['total'] ?>
                        </div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-90 flex gap-3 flex-wrap">
                            <span>Education <b data-aw-edu><?= (int)$attendanceWarningCounts['edu']['total'] ?></b><?php if ((int)$attendanceWarningCounts['edu']['critical'] > 0): ?> <span class="opacity-80">(<?= (int)$attendanceWarningCounts['edu']['critical'] ?> critical)</span><?php endif; ?></span>
                        </div>
                        <?php else: ?>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold">—</div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">Not available yet (feature flag / sql/072)</div>
                        <?php endif; ?>
                    </a>
                    <a href="/admin/attendance_monthly.php" class="stat-card bg-gradient-to-tr from-teal-600 via-teal-500 to-emerald-400 p-3 sm:p-4 flex flex-col justify-between" id="am-dashboard-card" title="Monthly attendance report (Ethiopian months)" style="text-decoration:none;color:inherit">
                        <div class="flex items-center justify-between">
                            <div class="text-xs uppercase tracking-wide">Monthly Attendance · <?= htmlspecialchars($attendanceMonthCard['month_label_am'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="w-8 h-8 rounded-2xl bg-white/15 flex items-center justify-center">
                                <i class="fa-solid fa-calendar-days text-sm"></i>
                            </div>
                        </div>
                        <?php if ($attendanceMonthCard['available']): ?>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold" data-am-below><?= (int)$attendanceMonthCard['below'] ?></div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-90 flex gap-3 flex-wrap">
                            <span>below threshold</span>
                            <span>Education <b><?= (int)($attendanceMonthCard['per_dept']['edu']['below'] ?? 0) ?></b></span>
                            <?php if ($attendanceMonthCard['avg_rate'] !== null): ?><span>avg <b><?= $attendanceMonthCard['avg_rate'] ?>%</b></span><?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="mt-2 text-2xl sm:text-3xl font-bold">—</div>
                        <div class="mt-1 text-[11px] sm:text-xs opacity-80">Not available yet (feature flag / sql/074)</div>
                        <?php endif; ?>
                    </a>
                </div>

                <div class="grid lg:grid-cols-2 gap-4">
                    <!-- Department distribution -->
                    <div class="panel p-4 sm:p-5">
                        <h3 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-building text-emerald-600"></i> Staff by Department
                        </h3>
                        <?php if (!$byDepartment): ?>
                            <p class="text-xs text-slate-400 py-4 text-center">No employees yet — add your first employee in the Staff section.</p>
                        <?php else: ?>
                            <div class="space-y-2.5">
                                <?php foreach ($byDepartment as $d): ?>
                                    <div>
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="font-semibold text-slate-600"><?= e($d['department']) ?></span>
                                            <span class="text-slate-400 font-bold"><?= (int)$d['c'] ?></span>
                                        </div>
                                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full" style="background:linear-gradient(90deg,#059669,#34d399);width:<?= $deptMax > 0 ? round(100 * (int)$d['c'] / $deptMax) : 0 ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick actions -->
                    <div class="panel p-4 sm:p-5">
                        <h3 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-amber-500"></i> Quick Actions
                        </h3>
                        <div class="grid grid-cols-2 gap-2.5">
                            <button onclick="openStaffModal()" class="flex items-center gap-2.5 p-3 rounded-xl border border-emerald-100 bg-emerald-50/60 hover:bg-emerald-50 text-left transition">
                                <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm"><i class="fa-solid fa-user-plus"></i></span>
                                <span>
                                    <span class="block text-xs font-bold text-slate-700">Register Employee</span>
                                    <span class="block text-[10px] text-slate-400">New staff member</span>
                                </span>
                            </button>
                            <?php if ($attendanceFeature): ?>
                            <button onclick="navigateToSection('staff-attendance')" class="flex items-center gap-2.5 p-3 rounded-xl border border-amber-100 bg-amber-50/60 hover:bg-amber-50 text-left transition">
                                <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm"><i class="fa-solid fa-calendar-check"></i></span>
                                <span>
                                    <span class="block text-xs font-bold text-slate-700">Mark Attendance</span>
                                    <span class="block text-[10px] text-slate-400">Month register</span>
                                </span>
                            </button>
                            <?php endif; ?>
                            <?php if ($idCardsFeature): ?>
                            <button onclick="navigateToSection('idcards')" class="flex items-center gap-2.5 p-3 rounded-xl border border-blue-100 bg-blue-50/60 hover:bg-blue-50 text-left transition">
                                <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm"><i class="fa-solid fa-id-card"></i></span>
                                <span>
                                    <span class="block text-xs font-bold text-slate-700">ID Cards</span>
                                    <span class="block text-[10px] text-slate-400">View / print staff cards</span>
                                </span>
                            </button>
                            <?php endif; ?>
                            <a href="/admin/account.php" class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 text-left transition">
                                <span class="w-8 h-8 rounded-lg bg-slate-600 text-white flex items-center justify-center text-sm"><i class="fa-solid fa-user-shield"></i></span>
                                <span>
                                    <span class="block text-xs font-bold text-slate-700">My Profile</span>
                                    <span class="block text-[10px] text-slate-400">Account &amp; security</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ══════════════ STAFF ══════════════ -->
            <section id="section-staff" class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2 text-emerald-700">
                            <i class="fa-solid fa-users text-sm"></i>
                            <span class="uppercase tracking-wide text-[11px] font-semibold">Staff</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-800">Employees</h2>
                    </div>
                    <button onclick="openStaffModal()" class="w-fit px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-2 transition">
                        <i class="fa-solid fa-user-plus"></i> Register Employee
                    </button>
                </div>

                <div class="panel p-3 sm:p-4 mb-3">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <div class="relative flex-1">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                            <input id="staffSearch" type="text" placeholder="Search name, code, position, phone…" autocomplete="off" class="fld pl-8">
                        </div>
                        <select id="staffStatusFilter" class="fld sm:w-40">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select id="staffDeptFilter" class="fld sm:w-48">
                            <option value="">All departments</option>
                        </select>
                    </div>
                </div>

                <div class="panel overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Code</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Phone</th>
                                    <th>Hired</th>
                                    <th>Salary</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="staffTbody">
                                <tr><td colspan="9" class="text-center text-slate-400 py-8">Loading employees…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between px-4 py-2.5 border-t border-slate-100 text-xs text-slate-500">
                        <span id="staffCount">—</span>
                        <div class="flex gap-2">
                            <button id="staffPrev" class="icon-btn" title="Previous page"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button id="staffNext" class="icon-btn" title="Next page"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ══════════════ ATTENDANCE ══════════════ -->
            <?php if ($attendanceFeature): ?>
            <section id="section-staff-attendance" class="content-section">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2 text-amber-700">
                            <i class="fa-solid fa-calendar-check text-sm"></i>
                            <span class="uppercase tracking-wide text-[11px] font-semibold">Attendance</span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-800">Monthly Register</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="attPrev" class="icon-btn" title="Previous month"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                        <span id="attMonthLabel" class="px-3 py-1.5 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 min-w-[9.5rem] text-center">—</span>
                        <button id="attNext" class="icon-btn" title="Next month"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                    </div>
                </div>

                <div class="att-legend mb-3">
                    <span>Click a cell to cycle:</span>
                    <span><span class="sw" style="background:#dcfce7"></span>Present</span>
                    <span><span class="sw" style="background:#fef3c7"></span>Late</span>
                    <span><span class="sw" style="background:#fee2e2"></span>Absent</span>
                    <span><span class="sw" style="background:#e0e7ff"></span>Excused</span>
                    <span class="text-slate-400">· click again to clear</span>
                </div>

                <div id="attBody" class="att-wrap">
                    <div class="p-8 text-center text-slate-400 text-sm">Loading register…</div>
                </div>
                <p class="text-[11px] text-slate-400 mt-2">Marking saves automatically. Future days are locked. Inactive employees appear only in months where they were marked.</p>
            </section>
            <?php endif; ?>

            <!-- ══════════════ ID CARDS ══════════════ -->
            <?php if ($idCardsFeature): ?>
            <section id="section-idcards" class="content-section">
                <div class="flex items-center gap-2 text-blue-700 mb-1">
                    <i class="fa-solid fa-id-card text-sm"></i>
                    <span class="uppercase tracking-wide text-[11px] font-semibold">ID Cards</span>
                </div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 mb-3">Employee ID Cards</h2>
                <div id="idcBody" class="idc-grid">
                    <div class="col-span-full p-8 text-center text-slate-400 text-sm">Loading staff…</div>
                </div>
            </section>
            <?php endif; ?>

            <!-- ══════════════ ATTENDANCE REPORTS (read-only Education) ══════════════ -->
            <section id="section-reports" class="content-section">
                <!-- HR reads the Education attendance (HR's own attendance is now
                     the staff register above). Read-only, served by
                     api_hr_reports.php + the shared Analytics Hub. -->
                <div class="panel overflow-hidden mb-4">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-calendar-check text-emerald-600"></i> Attendance Reports
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">HR analyses the attendance recorded by the Education department — the same Analytics Hub the Information department uses.</p>
                        </div>
                    </div>

                    <!-- Shared Attendance Analytics Hub (Education) -->
                    <div class="p-4 sm:p-5">
                        <?php include __DIR__ . '/sections/attendance_analytics_hub.php'; ?>
                    </div>

                    <!-- Per-student month view -->
                    <div class="px-4 sm:px-5 pb-4 sm:pb-5 border-t border-slate-100 pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="text-sm font-bold text-slate-700"><i class="fa-solid fa-user-check mr-1 text-slate-400"></i> Student view</h3>
                            <input id="hrRepMonth" type="month" class="px-3 py-2 border border-slate-200 rounded-xl text-sm" aria-label="Report month" onchange="HrReports.loadSummary()">
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 mb-3">
                            <input id="hrRepSearch" type="text" placeholder="Search a student by name or code…" autocomplete="off"
                                   class="flex-1 px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                   onkeyup="if(event.key==='Enter') HrReports.searchMembers()">
                            <button type="button" onclick="HrReports.searchMembers()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i> Search
                            </button>
                        </div>
                        <div id="hrRepMemberResults" class="mb-3"></div>
                        <div id="hrRepMemberDetail"></div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="/admin/reports.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-semibold shadow hover:from-blue-700 hover:to-indigo-700 transition">
                        <i class="fa-solid fa-chart-line"></i> Open Advanced Analytics Center
                    </a>
                    <a href="/admin/reports_v2.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 text-white text-xs font-semibold shadow hover:from-teal-700 hover:to-emerald-700 transition">
                        <i class="fa-solid fa-table-list"></i> Reporting Centre
                        <span class="px-1.5 py-0.5 rounded-full bg-white/25 text-[9px] font-bold tracking-wide">BETA</span>
                    </a>
                </div>
            </section>

            <!-- ══════════════ SETTINGS ══════════════ -->
            <section id="section-settings" class="content-section">
                <div class="flex items-center gap-2 text-slate-600 mb-1">
                    <i class="fa-solid fa-gear text-sm"></i>
                    <span class="uppercase tracking-wide text-[11px] font-semibold">Settings</span>
                </div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 mb-4">Settings</h2>
                <!-- ===== COMMON ACCOUNT PAGE ===== -->
                <div class="panel p-5 text-center">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <i class="fa-solid fa-user-shield text-xl text-white"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 mb-1">Profile &amp; account security</h3>
                    <p class="text-sm text-slate-500 max-w-xl mx-auto mb-4">Manage your name, email, username, profile image and password in the shared account center.</p>
                    <a href="<?= e(ssms_app_url('admin/account.php')) ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open My Profile
                    </a>
                </div>
            </section>

        </div>
    </main>

    <!-- Mobile bottom nav -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-emerald-800 text-white bottom-nav-shadow z-50 flex items-stretch justify-around px-1 py-1.5">
        <a href="#" data-section="dashboard" class="flex flex-col items-center min-w-[64px] px-2 py-1.5 rounded-xl mobile-touch-target">
            <i class="fa-solid fa-gauge text-sm"></i><span class="text-[9px] font-semibold mt-0.5">Dashboard</span>
        </a>
        <a href="#" data-section="staff" class="flex flex-col items-center min-w-[64px] px-2 py-1.5 rounded-xl mobile-touch-target">
            <i class="fa-solid fa-users text-sm"></i><span class="text-[9px] font-semibold mt-0.5">Staff</span>
        </a>
        <?php if ($attendanceFeature): ?>
        <a href="#" data-section="staff-attendance" class="flex flex-col items-center min-w-[64px] px-2 py-1.5 rounded-xl mobile-touch-target">
            <i class="fa-solid fa-calendar-check text-sm"></i><span class="text-[9px] font-semibold mt-0.5">Attendance</span>
        </a>
        <?php endif; ?>
        <?php if ($idCardsFeature): ?>
        <a href="#" data-section="idcards" class="flex flex-col items-center min-w-[64px] px-2 py-1.5 rounded-xl mobile-touch-target">
            <i class="fa-solid fa-id-card text-sm"></i><span class="text-[9px] font-semibold mt-0.5">ID Cards</span>
        </a>
        <?php endif; ?>
        <a href="#" data-section="reports" class="flex flex-col items-center min-w-[64px] px-2 py-1.5 rounded-xl mobile-touch-target">
            <i class="fa-solid fa-chart-column text-sm"></i><span class="text-[9px] font-semibold mt-0.5">Reports</span>
        </a>
    </nav>
</div>

<!-- ══════════════ STAFF FORM MODAL (add / edit) ══════════════ -->
<div id="staffModal" class="modal-backdrop">
    <div class="modal-panel">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 id="staffModalTitle" class="font-bold text-slate-800 text-sm">Register Employee</h3>
            <button onclick="closeStaffModal()" class="icon-btn" title="Close"><i class="fa-solid fa-xmark text-xs"></i></button>
        </div>
        <form id="staffForm" class="p-5 space-y-3">
            <input type="hidden" name="id" id="staffEditId" value="">
            <input type="hidden" name="staff_request_id" id="staffRequestId" value="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="fld-lbl" for="sf_name_am">Full name (Amharic) *</label>
                    <input class="fld amharic-text" id="sf_name_am" name="full_name_am" maxlength="255" required>
                </div>
                <div class="col-span-2">
                    <label class="fld-lbl" for="sf_name_en">Full name (English)</label>
                    <input class="fld" id="sf_name_en" name="full_name_en" maxlength="255">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_gender">Gender *</label>
                    <select class="fld" id="sf_gender" name="gender" required>
                        <option value="">Select…</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="fld-lbl" for="sf_phone">Phone</label>
                    <input class="fld" id="sf_phone" name="phone_number" maxlength="40">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_position">Position</label>
                    <input class="fld" id="sf_position" name="position" maxlength="255" placeholder="e.g. Accountant, Teacher">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_department">Department</label>
                    <input class="fld" id="sf_department" name="department" maxlength="120" placeholder="e.g. Administration">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_email">Email</label>
                    <input class="fld" id="sf_email" name="email" type="email" maxlength="255">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_hire">Hire date</label>
                    <input class="fld" id="sf_hire" name="hire_date" type="date">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_salary">Salary (ETB)</label>
                    <input class="fld" id="sf_salary" name="salary_amount" type="number" min="0" step="0.01">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_salnotes">Salary notes</label>
                    <input class="fld" id="sf_salnotes" name="salary_notes" maxlength="255">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_emname">Emergency contact</label>
                    <input class="fld" id="sf_emname" name="emergency_name" maxlength="255">
                </div>
                <div>
                    <label class="fld-lbl" for="sf_emphone">Emergency phone</label>
                    <input class="fld" id="sf_emphone" name="emergency_phone" maxlength="40">
                </div>
                <div class="col-span-2">
                    <label class="fld-lbl" for="sf_address">Address</label>
                    <input class="fld" id="sf_address" name="address" maxlength="255">
                </div>
                <div class="col-span-2">
                    <label class="fld-lbl" for="sf_notes">Notes</label>
                    <textarea class="fld" id="sf_notes" name="notes" rows="2" maxlength="5000"></textarea>
                </div>
                <div class="col-span-2">
                    <label class="fld-lbl" for="sf_photo">Photo</label>
                    <div class="flex items-center gap-3">
                        <input class="fld" id="sf_photo" name="staff_photo" type="file" accept="image/*">
                        <span id="sf_photo_hint" class="text-[10px] text-slate-400">JPG/PNG, max 5 MB</span>
                        <label id="sf_remove_photo_wrap" class="hidden items-center gap-1.5 text-[11px] text-rose-600 cursor-pointer">
                            <input type="checkbox" id="sf_remove_photo" name="remove_photo" value="1" class="accent-rose-600"> Remove current photo
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeStaffModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50">Cancel</button>
                <button type="submit" id="staffSaveBtn" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> <span id="staffSaveLbl">Save employee</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
'use strict';
// ════════════════════════════════════════════════════════════
// HR Dashboard — staff, attendance register, ID cards
// ════════════════════════════════════════════════════════════
const HR_STAFF_API = '<?= $ajaxPrefix ?>api_hr_staff.php';
const HR_ATT_API = '<?= $ajaxPrefix ?>api_hr_staff_attendance.php';
const ID_CARD_URL = '<?= e(ssms_app_url('admin/id_cards/view_staff_id_card.php')) ?>';
const MONTHS_EN = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const WDAYS = ['Mo','Tu','We','Th','Fr','Sa','Su'];

function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
}); }

let toastTimer = null;
function toast(msg, isErr) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.toggle('toast-err', !!isErr);
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 3200);
}

async function hrPost(url, data) {
    const fd = new FormData();
    Object.keys(data).forEach(function (k) {
        if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]);
    });
    const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
    let json = null;
    try { json = await res.json(); } catch (e) { json = null; }
    if (!json) throw new Error('Network error');
    if (res.status === 401 || json.status === 'session_expired' || json.status === 'csrf_expired' || json.action === 'reload') {
        location.reload();
        throw new Error('Session expired');
    }
    if (!res.ok || json.status !== 'success') {
        throw new Error(json.message || ('Request failed (' + res.status + ')'));
    }
    return json;
}

function staffPhotoUrl(p) {
    if (!p) return '';
    return p.startsWith('http') ? p : '/admin/' + String(p).replace(/^\/+/, '');
}
function initials(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}
function salaryFmt(v) {
    const n = Number(v);
    if (!isFinite(n)) return '—';
    return n.toLocaleString('en-US', { maximumFractionDigits: 0 });
}

// ── Section navigation ─────────────────────────────────────
function navigateToSection(name) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    const sec = document.getElementById('section-' + name);
    if (sec) sec.classList.add('active');
    if (name === 'staff') loadStaffList(true);
    if (name === 'staff-attendance') loadAttMonth();
    if (name === 'idcards') loadIdCards();
    if (name === 'reports') {
        try { if (window.AttendanceHub) AttendanceHub.init(); } catch (e) { console.error(e); }
        try { if (window.HrReports) HrReports.init(); } catch (e) { console.error(e); }
    }

    const url = new URL(window.location);
    url.searchParams.set('section', name);
    history.replaceState(null, '', url);

    document.querySelectorAll('[data-section]').forEach(btn => {
        const on = btn.getAttribute('data-section') === name;
        if (btn.closest('aside')) {
            btn.classList.toggle('nav-pill-active', on);
            if (on) btn.classList.add('bg-white/20'); else btn.classList.remove('bg-white/20');
        }
    });
    document.querySelectorAll('nav.md\\:hidden [data-section]').forEach(btn => {
        btn.style.background = btn.getAttribute('data-section') === name ? 'rgba(255,255,255,.2)' : '';
    });
    window.scrollTo({ top: 0 });
}
window.navigateToSection = navigateToSection;
window.switchSection = navigateToSection;
window.showSection = navigateToSection;

document.querySelectorAll('[data-section]').forEach(el => {
    el.addEventListener('click', function (e) {
        e.preventDefault();
        const name = this.getAttribute('data-section');
        if (name) navigateToSection(name);
    });
});

// ── Staff section ──────────────────────────────────────────
const staffState = { q: '', status: '', department: '', offset: 0, limit: 50, total: 0, all: [] };

async function loadStaffList(reset) {
    if (reset) staffState.offset = 0;
    try {
        const data = await hrPost(HR_STAFF_API, {
            action: 'list', q: staffState.q, status: staffState.status,
            department: staffState.department, limit: staffState.limit, offset: staffState.offset
        });
        staffState.all = data.rows || [];
        staffState.total = data.total || 0;
        (data.departments || []).forEach(function (d) {
            if (!document.getElementById('staffDeptFilter').querySelector('option[value="' + CSS.escape(d) + '"]')) {
                const o = document.createElement('option');
                o.value = d; o.textContent = d;
                document.getElementById('staffDeptFilter').appendChild(o);
            }
        });
        renderStaffTable();
    } catch (err) {
        document.getElementById('staffTbody').innerHTML =
            '<tr><td colspan="9" class="text-center text-rose-500 py-8">' + esc(err.message) + '</td></tr>';
    }
}

function renderStaffTable() {
    const tb = document.getElementById('staffTbody');
    if (!staffState.all.length) {
        tb.innerHTML = '<tr><td colspan="9" class="text-center text-slate-400 py-8">No employees found.</td></tr>';
        document.getElementById('staffCount').textContent = '0 employees';
        return;
    }
    tb.innerHTML = staffState.all.map(function (s) {
        const name = s.full_name_en || s.full_name_am;
        const photo = staffPhotoUrl(s.photo);
        const photoHtml = photo
            ? '<img class="staff-avatar" src="' + esc(photo) + '" alt="" onerror="this.outerHTML=\'<div class=\\\'staff-avatar-fallback\\\'>' + esc(initials(name)) + '</div>\'">'
            : '<div class="staff-avatar-fallback">' + esc(initials(name)) + '</div>';
        return '<tr data-id="' + s.id + '">'
            + '<td><div class="flex items-center gap-2.5">' + photoHtml
            + '<div class="min-w-0"><div class="font-bold text-slate-800 truncate" style="max-width:16rem">' + esc(s.full_name_am) + '</div>'
            + (s.full_name_en ? '<div class="text-[10px] text-slate-400 truncate" style="max-width:16rem">' + esc(s.full_name_en) + '</div>' : '')
            + '</div></div></td>'
            + '<td class="font-mono text-[11px] text-slate-500 whitespace-nowrap">' + esc(s.staff_code) + '</td>'
            + '<td class="text-slate-600">' + esc(s.position || '—') + '</td>'
            + '<td class="text-slate-600">' + esc(s.department || '—') + '</td>'
            + '<td class="text-slate-600 whitespace-nowrap">' + esc(s.phone_number || '—') + '</td>'
            + '<td class="text-slate-600 whitespace-nowrap">' + (s.hire_date ? esc(String(s.hire_date).slice(0, 10)) : '—') + '</td>'
            + '<td class="text-slate-600 whitespace-nowrap">' + salaryFmt(s.salary_amount) + (Number(s.salary_amount) > 0 ? ' <span class="text-[9px] text-slate-400">ETB</span>' : '') + '</td>'
            + '<td>' + (s.status === 'active'
                ? '<span class="pill pill-active">Active</span>'
                : '<span class="pill pill-inactive">Inactive</span>') + '</td>'
            + '<td class="text-right whitespace-nowrap">'
            + '<button class="icon-btn" title="Edit" onclick="openStaffModal(' + s.id + ')"><i class="fa-solid fa-pen text-[11px]"></i></button> '
            + '<a class="icon-btn" title="ID card" href="' + esc(ID_CARD_URL) + '?staff_id=' + s.id + '"><i class="fa-solid fa-id-card text-[11px]"></i></a> '
            + '<button class="icon-btn icon-btn-danger" title="' + (s.status === 'active' ? 'Deactivate' : 'Activate') + '" onclick="toggleStaffStatus(' + s.id + ', \'' + s.status + '\', \'' + esc(name) + '\')">'
            + '<i class="fa-solid fa-' + (s.status === 'active' ? 'user-slash' : 'user-check') + ' text-[11px]"></i></button>'
            + '</td></tr>';
    }).join('');
    const from = staffState.offset + 1;
    const to = Math.min(staffState.offset + staffState.limit, staffState.total);
    document.getElementById('staffCount').textContent = from + '–' + to + ' of ' + staffState.total + ' employees';
    document.getElementById('staffPrev').disabled = staffState.offset === 0;
    document.getElementById('staffNext').disabled = to >= staffState.total;
}

document.getElementById('staffPrev').addEventListener('click', function () {
    staffState.offset = Math.max(0, staffState.offset - staffState.limit);
    loadStaffList();
});
document.getElementById('staffNext').addEventListener('click', function () {
    staffState.offset += staffState.limit;
    loadStaffList();
});
let searchTimer = null;
document.getElementById('staffSearch').addEventListener('input', function () {
    clearTimeout(searchTimer);
    const v = this.value.trim();
    searchTimer = setTimeout(function () { staffState.q = v; loadStaffList(true); }, 350);
});
document.getElementById('staffStatusFilter').addEventListener('change', function () {
    staffState.status = this.value; loadStaffList(true);
});
document.getElementById('staffDeptFilter').addEventListener('change', function () {
    staffState.department = this.value; loadStaffList(true);
});

// ── Staff modal (add / edit) ───────────────────────────────
let staffEditRow = null;

function openStaffModal(id) {
    const form = document.getElementById('staffForm');
    form.reset();
    staffEditRow = null;
    document.getElementById('staffEditId').value = '';
    document.getElementById('staffRequestId').value = '';
    document.getElementById('sf_remove_photo_wrap').classList.add('hidden');
    document.getElementById('sf_remove_photo_wrap').classList.remove('flex');
    document.getElementById('staffModalTitle').textContent = 'Register Employee';
    document.getElementById('staffSaveLbl').textContent = 'Save employee';
    if (id) {
        const row = staffState.all.find(function (s) { return s.id === id; });
        if (!row) { toast('Employee not found — refresh the list.', true); return; }
        staffEditRow = row;
        document.getElementById('staffEditId').value = String(row.id);
        document.getElementById('staffModalTitle').textContent = 'Edit Employee — ' + (row.full_name_en || row.full_name_am);
        document.getElementById('staffSaveLbl').textContent = 'Update employee';
        document.getElementById('sf_name_am').value = row.full_name_am || '';
        document.getElementById('sf_name_en').value = row.full_name_en || '';
        document.getElementById('sf_gender').value = row.gender || '';
        document.getElementById('sf_phone').value = row.phone_number || '';
        document.getElementById('sf_email').value = row.email || '';
        document.getElementById('sf_position').value = row.position || '';
        document.getElementById('sf_department').value = row.department || '';
        document.getElementById('sf_hire').value = row.hire_date ? String(row.hire_date).slice(0, 10) : '';
        document.getElementById('sf_salary').value = row.salary_amount || '0';
        document.getElementById('sf_salnotes').value = row.salary_notes || '';
        document.getElementById('sf_emname').value = row.emergency_name || '';
        document.getElementById('sf_emphone').value = row.emergency_phone || '';
        document.getElementById('sf_address').value = row.address || '';
        document.getElementById('sf_notes').value = row.notes || '';
        if (row.photo) {
            const w = document.getElementById('sf_remove_photo_wrap');
            w.classList.remove('hidden'); w.classList.add('flex');
        }
    }
    document.getElementById('staffModal').classList.add('open');
    document.getElementById('sf_name_am').focus();
}
function closeStaffModal() {
    document.getElementById('staffModal').classList.remove('open');
}
window.openStaffModal = openStaffModal;
window.closeStaffModal = closeStaffModal;
document.getElementById('staffModal').addEventListener('click', function (e) {
    if (e.target === this) closeStaffModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeStaffModal();
});

document.getElementById('staffForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('staffEditId').value;
    const btn = document.getElementById('staffSaveBtn');
    btn.disabled = true;

    const fd = new FormData(form);
    if (!id) {
        // Idempotency request ID for new registrations (same pattern as
        // the member registration form).
        const reqId = (window.SsmsRequestId && window.SsmsRequestId.ensure)
            ? window.SsmsRequestId.ensure(form, 'staff_request_id')
            : '';
        if (reqId) fd.set('staff_request_id', reqId);
        fd.set('action', 'create');
    } else {
        fd.set('action', 'update');
        fd.set('id', id);
    }

    try {
        const res = await fetch(HR_STAFF_API, { method: 'POST', body: fd, credentials: 'same-origin' });
        const json = await res.json().catch(function () { return null; });
        if (!json) throw new Error('Network error');
        if (res.status === 401 || json.status === 'session_expired' || json.status === 'csrf_expired') {
            location.reload();
            return;
        }
        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || 'Save failed');
        }
        toast(json.message || 'Saved');
        closeStaffModal();
        loadStaffList();
    } catch (err) {
        toast(err.message, true);
    } finally {
        btn.disabled = false;
    }
});

// ── Activate / deactivate (DangerConfirm-guarded) ──────────
function toggleStaffStatus(id, current, name) {
    const target = current === 'active' ? 'inactive' : 'active';
    const opts = target === 'inactive'
        ? { tier: 'medium', title: 'Deactivate ' + name + '?', object: name,
            intent: 'I want to deactivate this employee',
            consequences: ['The employee no longer appears in the active roster and the attendance register.',
                           'Their historical attendance marks are kept.'],
            keeps: ['All stored data (contact, salary, ID card) stays saved.'],
            phrase: 'DEACTIVATE STAFF', confirmLabel: 'Deactivate' }
        : { tier: 'low', title: 'Activate ' + name + '?', object: name,
            consequences: ['The employee returns to the active roster and the attendance register.'],
            keeps: ['All stored data is unchanged.'],
            phrase: 'CONFIRM', confirmLabel: 'Activate' };
    if (!(window.DangerConfirm && window.DangerConfirm.ask)) {
        toast('The confirmation dialog did not load. Reload and try again.', true);
        return;
    }
    window.DangerConfirm.ask(opts).then(function (ok) {
        if (!ok) return;
        hrPost(HR_STAFF_API, { action: 'set_status', id: id, status: target })
            .then(function () { toast(target === 'active' ? 'Employee activated.' : 'Employee deactivated.'); loadStaffList(); })
            .catch(function (err) { toast(err.message, true); });
    });
}
window.toggleStaffStatus = toggleStaffStatus;

// ── Attendance register ────────────────────────────────────
let attMonth = (function () { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'); })();
let attData = null;

function shiftMonth(delta) {
    let [y, m] = attMonth.split('-').map(Number);
    m += delta;
    if (m < 1) { m = 12; y--; }
    if (m > 12) { m = 1; y++; }
    attMonth = y + '-' + String(m).padStart(2, '0');
    loadAttMonth();
}
function cycleStatus(cur) {
    return cur === '' ? 'present' : cur === 'present' ? 'late' : cur === 'late' ? 'absent' : cur === 'absent' ? 'excused' : '';
}
const ATT_CLASS = { present: 's-p', late: 's-l', absent: 's-a', excused: 's-e' };

async function loadAttMonth() {
    const body = document.getElementById('attBody');
    body.innerHTML = '<div class="p-8 text-center text-slate-400 text-sm">Loading register…</div>';
    try {
        attData = await hrPost(HR_ATT_API, { action: 'month_get', month: attMonth });
        renderAtt();
    } catch (err) {
        attData = null;
        body.innerHTML = '<div class="p-8 text-center text-rose-500 text-sm">' + esc(err.message) + '</div>';
        document.getElementById('attMonthLabel').textContent = '—';
    }
}

function renderAtt() {
    const [y, m] = attData.month.split('-').map(Number);
    document.getElementById('attMonthLabel').textContent = MONTHS_EN[m - 1] + ' ' + y;
    document.getElementById('attNext').disabled = attMonth >= String(new Date().getFullYear()) + '-' + String(new Date().getMonth() + 1).padStart(2, '0');
    const days = attData.days_in_month;
    const firstDow = (new Date(y, m - 1, 1).getDay() + 6) % 7; // Monday = 0
    const today = attData.today;
    const rows = attData.staff || [];
    if (!rows.length) {
        document.getElementById('attBody').innerHTML = '<div class="p-8 text-center text-slate-400 text-sm">No employees to mark. Register employees first.</div>';
        return;
    }
    let html = '<table class="att-table"><thead><tr><th class="att-name-cell" style="text-align:left">Employee</th>';
    for (let d = 1; d <= days; d++) {
        const dt = new Date(y, m - 1, d);
        html += '<th>' + WDAYS[(dt.getDay() + 6) % 7] + '<br><span class="text-slate-300">' + d + '</span></th>';
    }
    html += '<th class="att-sum">P·L·A·E</th></tr></thead><tbody>';
    rows.forEach(function (s) {
        const marks = (attData.marks && attData.marks[s.id]) || {};
        const sum = (attData.summaries && attData.summaries[s.id]) || { present: 0, late: 0, absent: 0, excused: 0 };
        html += '<tr data-sid="' + s.id + '"><td class="att-name-cell" title="' + esc(s.full_name_am) + ' — ' + esc(s.staff_code) + '">'
            + (s.status === 'active' ? '' : '<span class="pill pill-inactive" style="margin-right:.35rem">inactive</span>')
            + esc(s.full_name_en || s.full_name_am) + '</td>';
        for (let d = 1; d <= days; d++) {
            const dayNum = String(d).padStart(2, '0');
            const dateStr = y + '-' + String(m).padStart(2, '0') + '-' + dayNum;
            const st = marks[dayNum] || '';
            const future = dateStr > today;
            html += '<td><div class="att-day ' + (ATT_CLASS[st] || '') + (future ? ' future' : '')
                + '" data-date="' + dateStr + '" data-status="' + esc(st) + '">' + (st ? st[0].toUpperCase() : '·') + '</div></td>';
        }
        html += '<td class="att-sum">' + sum.present + '·' + sum.late + '·' + sum.absent + '·' + sum.excused + '</td></tr>';
    });
    html += '</tbody></table>';
    document.getElementById('attBody').innerHTML = html;

    document.querySelectorAll('#attBody .att-day:not(.future)').forEach(function (cell) {
        cell.addEventListener('click', function () {
            const next = cycleStatus(cell.dataset.status || '');
            const dateStr = cell.dataset.date;
            const sid = cell.closest('tr').dataset.sid;
            applyMark(sid, dateStr, next);
        });
    });
}

function applyMark(sid, dateStr, next) {
    // Optimistic local update
    const tr = document.querySelector('#attBody tr[data-sid="' + sid + '"]');
    const cell = tr && tr.querySelector('.att-day[data-date="' + dateStr + '"]');
    if (!cell) return;
    const prev = cell.dataset.status || '';
    setCellVisual(cell, next);
    bumpSummary(tr, prev, next);

    hrPost(HR_ATT_API, { action: 'mark_set', staff_id: sid, date: dateStr, status: next })
        .catch(function (err) {
            setCellVisual(cell, prev);
            bumpSummary(tr, next, prev);
            toast('Saving failed: ' + err.message, true);
        });
}
function setCellVisual(cell, st) {
    cell.classList.remove('s-p', 's-l', 's-a', 's-e');
    if (ATT_CLASS[st]) cell.classList.add(ATT_CLASS[st]);
    cell.dataset.status = st;
    cell.textContent = st ? st[0].toUpperCase() : '·';
}
function bumpSummary(tr, from, to) {
    const key = { present: 0, late: 1, absent: 2, excused: 3 };
    const td = tr.querySelector('td.att-sum');
    if (!td) return;
    const parts = td.textContent.split('·').map(Number);
    if (from && key[from] !== undefined) parts[key[from]] = Math.max(0, parts[key[from]] - 1);
    if (to && key[to] !== undefined) parts[key[to]] = (parts[key[to]] || 0) + 1;
    td.textContent = parts.join('·');
}
document.getElementById('attPrev').addEventListener('click', function () { shiftMonth(-1); });
document.getElementById('attNext').addEventListener('click', function () { shiftMonth(1); });

// ── ID cards ───────────────────────────────────────────────
async function loadIdCards() {
    const body = document.getElementById('idcBody');
    try {
        const data = await hrPost(HR_STAFF_API, { action: 'list', limit: 200, offset: 0 });
        const rows = data.rows || [];
        if (!rows.length) {
            body.innerHTML = '<div class="col-span-full p-8 text-center text-slate-400 text-sm">No employees yet.</div>';
            return;
        }
        body.innerHTML = rows.map(function (s) {
            const name = s.full_name_en || s.full_name_am;
            const photo = staffPhotoUrl(s.photo);
            return '<div class="idc-card">'
                + '<div class="flex items-center gap-3 mb-3">'
                + (photo
                    ? '<img class="staff-avatar" style="width:2.8rem;height:2.8rem" src="' + esc(photo) + '" alt="" onerror="this.outerHTML=\'<div class=\\\'staff-avatar-fallback\\\' style=\\\'width:2.8rem;height:2.8rem\\\'>' + esc(initials(name)) + '</div>\'">'
                    : '<div class="staff-avatar-fallback" style="width:2.8rem;height:2.8rem">' + esc(initials(name)) + '</div>')
                + '<div class="min-w-0"><div class="text-xs font-bold text-slate-800 truncate">' + esc(s.full_name_am) + '</div>'
                + '<div class="text-[10px] text-slate-400 font-mono">' + esc(s.staff_code) + ' · ' + esc(s.position || '—') + '</div></div></div>'
                + (s.status === 'active' ? '<span class="pill pill-active mb-2">Active</span>' : '<span class="pill pill-inactive mb-2">Inactive</span>')
                + '<a href="' + esc(ID_CARD_URL) + '?staff_id=' + s.id + '" class="mt-2 block text-center px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition">'
                + '<i class="fa-solid fa-id-card mr-1"></i> View / Print card</a></div>';
        }).join('');
    } catch (err) {
        body.innerHTML = '<div class="col-span-full p-8 text-center text-rose-500 text-sm">' + esc(err.message) + '</div>';
    }
}

// ── Attendance Reports (read-only Education) ───────────────
// Reuses api_hr_reports.php + the shared Analytics Hub. HR analyses
// Education attendance; it does not take student attendance.
function escapeHtml(s) { return esc(s); }

const HrReports = (function () {
    'use strict';

    const API = '<?= $ajaxPrefix ?>api_hr_reports.php';
    let initialized = false;

    function $(id) { return document.getElementById(id); }

    function apiGet(query) {
        return fetch(API + '?' + query, { credentials: 'same-origin' })
            .then(r => r.json());
    }

    function currentMonth() {
        const el = $('hrRepMonth');
        if (el && el.value) return el.value;
        const now = new Date();
        return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    }

    let openMemberId = 0;
    function loadSummary() {
        const month = currentMonth();
        if ($('hrRepMonth')) $('hrRepMonth').value = month;
        if (openMemberId > 0) openMember(openMemberId);
    }

    function searchMembers() {
        const q = ($('hrRepSearch') && $('hrRepSearch').value || '').trim();
        const results = $('hrRepMemberResults');
        const detail = $('hrRepMemberDetail');
        if (detail) detail.innerHTML = '';
        if (q.length < 2) {
            if (results) results.innerHTML = '<p class="text-xs text-slate-400">Type at least 2 characters to search.</p>';
            return;
        }
        if (results) results.innerHTML = '<p class="text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Searching…</p>';
        apiGet('action=member_search&q=' + encodeURIComponent(q)).then(d => {
            if (d.status !== 'success' || !(d.items || []).length) {
                if (results) results.innerHTML = '<p class="text-xs text-slate-400">No students found.</p>';
                return;
            }
            if (results) results.innerHTML = '<div class="flex flex-wrap gap-2">' + d.items.filter(m => Number.isSafeInteger(Number(m.id)) && Number(m.id) > 0).map(m =>
                '<button type="button" onclick="HrReports.openMember(' + Number(m.id) + ')" class="px-3 py-1.5 bg-slate-100 hover:bg-emerald-50 hover:border-emerald-300 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition">' +
                escapeHtml(m.student_name + ' ' + (m.father_name || '')) +
                (m.member_code ? ' <span class="text-slate-400">· ' + escapeHtml(m.member_code) + '</span>' : '') +
                '</button>'
            ).join('') + '</div>';
        }).catch(() => {
            if (results) results.innerHTML = '<p class="text-xs text-slate-400">Connection error. Try again.</p>';
        });
    }

    function statusBadge(status) {
        const meta = {
            present: ['bg-emerald-100 text-emerald-700', 'Present'],
            absent: ['bg-red-100 text-red-700', 'Absent'],
            late: ['bg-amber-100 text-amber-700', 'Late'],
            excused: ['bg-blue-100 text-blue-700', 'Excused']
        }[status] || ['bg-slate-100 text-slate-600', status];
        return '<span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold ' + meta[0] + '">' + escapeHtml(meta[1]) + '</span>';
    }

    function openMember(memberId) {
        openMemberId = Number(memberId) || 0;
        const month = currentMonth();
        const box = $('hrRepMemberDetail');
        if (!box) return;
        box.innerHTML = '<p class="text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i>Loading student attendance…</p>';
        apiGet('action=member_detail&member_id=' + encodeURIComponent(memberId) + '&month=' + encodeURIComponent(month)).then(d => {
            if (d.status !== 'success' || !d.member) {
                box.innerHTML = '<p class="text-xs text-slate-400">' + escapeHtml(d.message || 'Student not found.') + '</p>';
                return;
            }
            const m = d.member;
            const rows = []
                .concat((d.education || []).map(r => ({ date: r.date, source: 'Education', label: r.class_name || '—', status: r.status })))
                .sort((a, b) => (a.date < b.date ? 1 : -1));
            let html = '<div class="bg-slate-50 rounded-2xl border border-slate-200 p-4">' +
                '<div class="flex flex-wrap items-center gap-2 mb-3">' +
                '<b class="text-sm text-slate-800">' + escapeHtml(m.student_name + ' ' + (m.father_name || '')) + '</b>' +
                (m.member_code ? '<span class="text-xs text-slate-400">' + escapeHtml(m.member_code) + '</span>' : '') +
                (m.current_section ? '<span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[11px] font-semibold">' + escapeHtml(m.current_section) + '</span>' : '') +
                '<span class="text-xs text-slate-400">· ' + escapeHtml(month) + '</span></div>';
            if (!rows.length) {
                html += '<p class="text-xs text-slate-400">No attendance recorded for this student in ' + escapeHtml(month) + '.</p>';
            } else {
                html += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100">' +
                    '<th class="py-2 pr-3">Date</th><th class="py-2 pr-3">Source</th><th class="py-2 pr-3">Class / Section</th><th class="py-2">Status</th></tr></thead><tbody>' +
                    rows.map(r =>
                        '<tr class="border-b border-slate-50">' +
                        '<td class="py-2 pr-3 whitespace-nowrap">' + escapeHtml(r.date) + '</td>' +
                        '<td class="py-2 pr-3"><span class="px-2 py-0.5 rounded-full text-[11px] font-semibold ' + (r.source === 'Education' ? 'bg-indigo-100 text-indigo-700' : 'bg-violet-100 text-violet-700') + '">' + r.source + '</span></td>' +
                        '<td class="py-2 pr-3">' + escapeHtml(r.label) + '</td>' +
                        '<td class="py-2">' + statusBadge(r.status) + '</td></tr>'
                    ).join('') + '</tbody></table></div>';
            }
            html += '</div>';
            box.innerHTML = html;
        }).catch(() => {
            box.innerHTML = '<p class="text-xs text-slate-400">Connection error. Try again.</p>';
        });
    }

    function init() {
        if (initialized) return;
        initialized = true;
        loadSummary();
    }

    return {
        init: init,
        loadSummary: loadSummary,
        searchMembers: searchMembers,
        openMember: openMember
    };
})();

// ── Init ───────────────────────────────────────────────────
(function init() {
    const params = new URLSearchParams(window.location.search);
    const sec = params.get('section');
    if (sec) navigateToSection(sec);
    else loadStaffList(); // warm the staff list so the first visit is instant
})();
</script>
<?php include __DIR__ . '/../components/comm/comm_section.php'; ?>
<script>
// Explain a permission bounce (access_control.php → index.php → dashboard.php?error=access_denied).
(function () {
    try {
        const u = new URL(window.location.href);
        if (u.searchParams.get('error') === 'access_denied') {
            toast('You do not have permission to open that page.', true);
            u.searchParams.delete('error');
            history.replaceState(null, '', u.toString());
        }
    } catch (e) { /* decorative */ }
})();
</script>
</body>
</html>
