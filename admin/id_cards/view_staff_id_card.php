<?php
// FILE: /admin/id_cards/view_staff_id_card.php
//
// Employee ID card — same card design as the student card
// (2026-11-24: HR became a company HR department; the ID-card
// feature was reused for employees). Renders the shared
// id_card_template_layout.php with staff data mapped onto the
// template's $member contract:
//
//   member_code     → staff_code
//   name            → full name (EN, falling back to AM)
//   christian-name  → position title (the meaningful staff field)
//   age             → — (staff roster stores no date of birth)
//   emergency       → staff emergency contact
//
// SECURITY (IDOR discipline of the student viewer): authenticated
// admin session is mandatory; access_control.php ROLE_MAP limits
// this file to super_admin / school_admin / hr_dept and this file
// re-checks the role itself.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/libs/eth_date_helper.php';
require_once __DIR__ . '/../backend/services/IdCardLayout.php';

if (!isLoggedIn()) {
    header('Location: ' . ssms_app_url('admin/index.php'));
    exit;
}
$staffRole = (string)($_SESSION['admin_role'] ?? '');
if (!in_array($staffRole, ['super_admin', 'school_admin', 'hr_dept'], true)) {
    http_response_code(403);
    exit;
}

$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
$backDashboard = ssms_app_url('admin/dashboards/hr-dept.php');

// ── Renew action (POST, DangerConfirm-guarded on the client) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'renew') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit;
    }
    $renewId = (int)($_POST['staff_id'] ?? 0);
    if ($renewId > 0 && $renewId === $staff_id) {
        $upd = $conn->prepare('UPDATE staff SET id_card_generated_at = NOW() WHERE id = ?');
        $upd->bind_param('i', $renewId);
        $upd->execute();
        $upd->close();
    }
    header('Location: ' . ssms_app_url('admin/id_cards/view_staff_id_card.php') . '?staff_id=' . (int)$staff_id, true, 303);
    exit;
}

$stmt = $conn->prepare('SELECT * FROM staff WHERE id = ?');
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) die("Employee not found");

// ASSETS — same branding pipeline as the student card.
$CONFIG = [
    'logo'      => \App\Services\BrandingAssetService::assetUrl($conn, 'logo', '', true),
    'seal'      => \App\Services\BrandingAssetService::assetUrl($conn, 'seal', '', true),
    'sig_head'  => \App\Services\BrandingAssetService::assetUrl($conn, 'sig_head', '', true),
    'sig_admin' => \App\Services\BrandingAssetService::assetUrl($conn, 'sig_admin', '', true),
];
$layout = \App\Services\IdCardLayout::load($conn);
$idCardBg = \App\Services\IdCardLayout::background($conn);
$ID_CARD_STYLE = \App\Services\IdCardLayout::cssVars($layout, $idCardBg);
$ID_CARD_LAYOUT = $layout;

// --- ETHIOPIAN DATES ---
$issueDateGregorian = !empty($staff['id_card_generated_at']) ? substr((string)$staff['id_card_generated_at'], 0, 10) : substr((string)$staff['created_at'], 0, 10);
$issueDateEth = toEthiopianDate($issueDateGregorian);
$expiryDateGregorian = date('Y-m-d', strtotime($issueDateGregorian . ' + 4 years'));
$expiryDateEth = toEthiopianDate($expiryDateGregorian);
$isExpired = isExpired($issueDateGregorian);

// DATA PREP — staff → template $member contract.
$nameEn = trim((string)$staff['full_name_en']);
$nameAm = trim((string)$staff['full_name_am']);
$full_name = $nameEn !== '' ? $nameEn : $nameAm;
$christian_name = trim((string)$staff['position']) !== '' ? trim((string)$staff['position']) : '-----------';
$age = '—';

$member = [
    'member_code'     => (string)$staff['staff_code'],
    'student_name'    => $full_name,
    'gender'          => (string)$staff['gender'],
    'phone_number'    => (string)$staff['phone_number'],
    'address'         => (string)$staff['address'],
    'emergency_name'  => (string)$staff['emergency_name'],
    'emergency_phone' => (string)$staff['emergency_phone'],
    'student_photo_path' => '',
    'qr_code_path'    => '',
];

$filename = "ID_" . (string)$staff['staff_code'];

// PHOTO — staff photos live in admin/uploads/staff/photos/.
if (!empty($staff['photo'])) {
    $p = ltrim((string)$staff['photo'], '/');
    if (strpos($p, 'admin/') === 0) {
        $p = substr($p, 6);
    }
    $relativePath = '../' . $p;
    if (is_file(__DIR__ . '/' . $relativePath)) {
        $member['student_photo_path'] = '/admin/' . $p;
    }
}

// QR — canonical qr_staff_{id}.png, self-healed on first view. The
// QR payload is the staff code itself (there is no public staff
// lookup page; the scanned code identifies the employee).
$canonicalQrFile = 'qr_staff_' . (int)$staff_id . '.png';
$canonicalQrRel = 'assets/qr/' . $canonicalQrFile;
$canonicalQrWeb = '/admin/id_cards/' . $canonicalQrRel;
$canonicalQrDisk = __DIR__ . '/' . $canonicalQrRel;
$resolvedQrDisk = null;

if (is_file($canonicalQrDisk) && filesize($canonicalQrDisk) >= 32) {
    $resolvedQrDisk = $canonicalQrDisk;
}
if ($resolvedQrDisk === null) {
    require_once __DIR__ . '/libs/qr_loader.php';
    if (class_exists('QRcode') && extension_loaded('gd') && function_exists('imagepng')) {
        $qr_dir = __DIR__ . '/assets/qr';
        if (!is_dir($qr_dir)) {
            @mkdir($qr_dir, 0755, true); // 0755, never world-writable
        }
        if (is_dir($qr_dir) && is_writable($qr_dir)) {
            $qr_content = (string)$staff['staff_code'];
            $tmpQr = tempnam($qr_dir, '.qr-');
            if ($tmpQr !== false) {
                try {
                    QRcode::png($qr_content, $tmpQr, QR_ECLEVEL_L, 4, 2);
                    if (is_file($tmpQr) && filesize($tmpQr) >= 32) {
                        @chmod($tmpQr, 0644);
                        if (@rename($tmpQr, $canonicalQrDisk) || @copy($tmpQr, $canonicalQrDisk)) {
                            $resolvedQrDisk = $canonicalQrDisk;
                        }
                    }
                } catch (Throwable $qrError) {
                    error_log('Staff ID-card QR self-heal failed: ' . $qrError->getMessage());
                } finally {
                    if (is_file($tmpQr)) {
                        @unlink($tmpQr);
                    }
                }
            }
        }
    }
}
$member['qr_code_path'] = $resolvedQrDisk !== null ? $canonicalQrWeb : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff ID Card - <?php echo htmlspecialchars((string)$member['member_code'], ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha384-ZZ1pncU3bQe8y31yfZdMFdSpttDoPmOZg2wguVK9almUodir1PghgT0eY7Mrty8H" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Ethiopic:wght@400;700;900&display=swap">
    <link rel="stylesheet" href="/admin/css/id_card.css?v=20260823c">
    <style>
        /* page chrome only — card look lives in id_card.css */

        body { background: #374151; font-family: 'Noto Sans Ethiopic', 'Noto Serif Ethiopic', sans-serif; }
        /* PREVIEW ON SCREEN (Scaled Down CSS) */
        .preview-wrapper {
            transform: scale(0.6);
            transform-origin: top center;
            margin-bottom: -180px;
        }
        .preview-wrapper .id-card-template { margin-bottom: 28px; }
        #export-container .id-card-template { margin-bottom: 0; }

        /* EXPORT CONTAINER (Hidden from user, but visible to code) */
        #export-container {
            position: fixed;
            left: -9999px; /* Move off screen */
            top: 0;
        }
    </style>
<link rel="stylesheet" href="/admin/css/mobile.css?v=<?= SSMS_ASSET_VER ?>">
</head>
<body class="flex flex-col items-center p-6">

    <!-- TOP MENU -->
    <div class="fixed top-0 left-0 w-full bg-white shadow-lg p-3 flex justify-between items-center z-50">
        <div class="flex items-center gap-4">
            <a href="<?= e($backDashboard) ?>?section=idcards" class="text-gray-600 hover:text-black font-bold flex items-center gap-2">&larr; Back</a>
            <?php if($isExpired): ?>
                <div class="bg-red-100 text-red-700 px-3 py-1 rounded font-bold text-sm flex items-center gap-2">
                    <span class="w-2 h-2 bg-red-600 rounded-full animate-pulse"></span> EXPIRED
                </div>
            <?php else: ?>
                <div class="bg-green-100 text-green-700 px-3 py-1 rounded font-bold text-sm">
                    Active (Exp: <?php echo $expiryDateEth; ?>)
                </div>
            <?php endif; ?>
        </div>
        <div class="flex gap-2">
            <!-- RENEW BUTTON -->
            <form method="post" action="view_staff_id_card.php?staff_id=<?= (int)$staff_id ?>" id="dcRenewCardForm">
                <?= csrf_field() ?>
                <input type="hidden" name="staff_id" value="<?= (int)$staff_id ?>">
                <input type="hidden" name="action" value="renew">
                <button type="submit"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow font-semibold flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-rotate"></i> Update Date
                </button>
            </form>
            <button onclick="downloadPNG('front')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded shadow font-semibold text-sm">PNG Front</button>
            <button onclick="downloadPNG('back')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded shadow font-semibold text-sm">PNG Back</button>
            <button onclick="downloadPDF()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded shadow font-semibold text-sm">Download PDF</button>
        </div>
    </div>

    <div class="h-24"></div>

    <!-- PREVIEW AREA (What you see on screen) -->
    <div class="text-white font-bold mb-2">Card Preview (Front & Back)</div>
    <div class="preview-wrapper">
        <!-- We clone the layout here just for viewing -->
        <?php include 'id_card_template_layout.php'; ?>
    </div>


    <!-- EXPORT AREA (Hidden off-screen, Full Size for High Quality) -->
    <div id="export-container">
        <!-- This includes the exact same HTML but 100% scale -->
        <?php include 'id_card_template_layout.php'; ?>
    </div>

    <script>
        const memberCode = "<?php echo $filename; ?>";

        // Helper to get the correct element from the HIDDEN container
        function getExportElement(side) {
            const container = document.getElementById('export-container');
            const cards = container.querySelectorAll('.id-card-template');
            return side === 'front' ? cards[0] : cards[1];
        }

        function downloadPNG(side) {
            const element = getExportElement(side);
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                allowTaint: false,
                backgroundColor: "#f3e6c4",
                logging: false,
                // Ensure the off-screen container is rendered at correct size
                width: 1011,
                height: 638,
                onclone: function(clonedDoc) {
                    // Ensure the cloned export container is visible for rendering
                    const container = clonedDoc.getElementById('export-container');
                    if (container) {
                        container.style.position = 'absolute';
                        container.style.left = '0';
                        container.style.top = '0';
                    }
                }
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = `${memberCode}_${side}.png`;
                link.href = canvas.toDataURL("image/png");
                link.click();
            }).catch(err => {
                console.error('Export error:', err);
                alert('Download failed. If images are missing, ensure they are uploaded to the server.');
            });
        }

        async function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('l', 'mm', [85.6, 54]);

            const frontEl = getExportElement('front');
            const backEl = getExportElement('back');

            try {
                const opts = { scale: 2, useCORS: true, allowTaint: false, backgroundColor: "#f3e6c4", width: 1011, height: 638, logging: false };

                const frontCanvas = await html2canvas(frontEl, opts);
                pdf.addImage(frontCanvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 85.6, 54);

                pdf.addPage();
                const backCanvas = await html2canvas(backEl, opts);
                pdf.addImage(backCanvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, 85.6, 54);

                pdf.save(`${memberCode}_printable.pdf`);
            } catch(err) {
                console.error('PDF export error:', err);
                alert('PDF download failed.');
            }
        }
    </script>
<script src="/admin/js/danger_confirm.js?v=20261022dc4"></script>
<script>
(function () {
    var f = document.getElementById('dcRenewCardForm');
    if (f && window.DangerConfirm && window.DangerConfirm.guardForm) {
        window.DangerConfirm.guardForm(f, { tier: 'medium', title: 'Renew this staff ID card?', summary: 'A renewed card replaces the current one.', intent: 'I want to renew this staff card', consequences: ['The issue date is reset to TODAY and a new expiry date is calculated.', 'The card that was printed before shows old dates; print and hand out the new card.'], keeps: ['The employee code and QR code stay the same.', 'Employee data is not changed.'], phrase: 'RENEW CARD', confirmLabel: 'Renew card' });
    } else if (f) {
        f.addEventListener('submit', function (e) { e.preventDefault(); alert('The confirmation dialog did not load. Reload the page and try again.'); });
    }
})();
</script>
</body>
