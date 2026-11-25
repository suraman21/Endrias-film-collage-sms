<?php
/**
 * Endrias Film College — public site: <head> + top chrome (announcement bar,
 * sticky nav, mobile menu). Port of index.html + src/components/Layout.tsx.
 * Requires lang.php + data.php (loaded by the front controller).
 * The front controller sets $EFC_PAGE ('' = Home) and optional $EFC_TITLE.
 */
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/data.php';

$EFC_PAGE = $EFC_PAGE ?? '';
$EFC_TITLE = $EFC_TITLE ?? '';
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
// Language toggle target: flip en<->am, keep the current page.
$EFC_LANG_NEXT = $EFC_LANG === 'am' ? 'en' : 'am';
$EFC_TOGGLE = efc_url($EFC_PAGE) . ($EFC_PAGE === '' ? '?' : '&') . 'lang=' . $EFC_LANG_NEXT;
$EFC_SITENAME = 'Endrias Film College';
$EFC_SITENAME_AM = 'እንድርያስ የፊልም ኮሌጅ';
$EFC_HEAD_TITLE = $EFC_TITLE !== '' ? ($EFC_TITLE . ' · ' . $EFC_SITENAME) : ($EFC_SITENAME . ' | ' . $EFC_SITENAME_AM . ' — From Imagination to Screen');
?>
<!DOCTYPE html>
<html lang="<?= $EFC_HTML_LANG ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($EFC_HEAD_TITLE) ?></title>
    <meta name="description" content="<?= $e($EFC_SITENAME) ?> (<?= $EFC_SITENAME_AM ?>) — AAETQAA-accredited TVET Film Conservatory (Levels I–IV Diploma & Short-Term Certificates) at 4 Kilo, Arada Sub-City. From Imagination to Screen.">
    <link rel="icon" type="image/png" href="college/assets/brand/logo-seal.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500;600&family=Noto+Sans+Ethiopic:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            navy: '#054677', navyDark: '#032948', midnight: '#031525',
                            slate: '#0A2239', orange: '#FF9000', orangeHover: '#E68200',
                            amber: '#E69A35', ivory: '#FAF7F2', sand: '#F2ECE1',
                            ink: '#0B1928', muted: '#4A5B6D'
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '"Noto Sans Ethiopic"', 'system-ui', 'sans-serif'],
                        serif: ['"Instrument Serif"', '"Noto Serif Ethiopic"', 'Georgia', 'serif'],
                        ethiopic: ['"Noto Sans Ethiopic"', '"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace']
                    },
                    boxShadow: {
                        cinema: '0 24px 60px -15px rgba(3, 21, 37, 0.35)',
                        card: '0 10px 30px -10px rgba(5, 70, 119, 0.08)',
                        glow: '0 0 32px rgba(255, 144, 0, 0.28)'
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="college/css/college.css?v=2">
    <!-- Fail-safe scroll-reveal styles. Inlined (and kept in sync with
         college.css) so a cached copy of college.css can never leave page
         content hidden: content is visible by default, .efc-hidden is only
         ever added by the footer script. -->
    <style>
        .efc-reveal { opacity: 1; transform: none; transition: opacity 0.6s ease-out, transform 0.6s ease-out; }
        .efc-reveal.efc-hidden { opacity: 0; transform: translateY(24px); }
        .efc-reveal.efc-in { opacity: 1; transform: translateY(0); }
        @media (prefers-reduced-motion: reduce) {
            .efc-reveal { transition: none; }
            .efc-reveal.efc-hidden { opacity: 1; transform: none; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
</head>
<body class="bg-brand-ivory text-brand-ink antialiased font-sans selection:bg-brand-orange selection:text-brand-midnight">

    <!-- Top announcement bar -->
    <div class="bg-brand-midnight text-white/85 border-b border-white/10 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-brand-orange/20 text-brand-orange font-mono text-[11px] font-semibold shrink-0">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-orange opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-brand-orange"></span>
                    </span>
                    <?= $e($EFC_UI['ann_pill']) ?>
                </span>
                <span class="truncate text-white/80"><?= $e(bp($EFC_UI['ann_tagline'])) ?></span>
            </div>
            <div class="hidden md:flex items-center gap-5 shrink-0">
                <a href="tel:+251900003919" class="inline-flex items-center gap-1.5 hover:text-brand-orange transition-colors font-mono">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-brand-orange"></i> <?= $e($EFC_UI['phone1']) ?>
                </a>
                <a href="tel:+251942563535" class="inline-flex items-center gap-1.5 hover:text-brand-orange transition-colors font-mono">
                    <i data-lucide="phone" class="w-3.5 h-3.5 text-brand-orange"></i> <?= $e($EFC_UI['phone2']) ?>
                </a>
                <a href="<?= $e($EFC_CONTACT['telegram']) ?>" target="_blank" rel="noreferrer"
                   class="inline-flex items-center gap-1.5 hover:text-brand-orange transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5 text-brand-orange"></i> <?= $e($EFC_UI['tel_handle']) ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Sticky header -->
    <header class="sticky top-0 z-40 bg-brand-navy border-b border-white/10" id="efcHeader">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3">
            <a href="<?= efc_url('') ?>" class="flex items-center gap-3 group min-w-0 shrink-0">
                <img src="college/assets/brand/logo-seal.png" alt="<?= $EFC_SITENAME ?> Official Seal"
                     class="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-contain drop-shadow-md group-hover:scale-105 transition-transform shrink-0">
                <div class="leading-tight min-w-0">
                    <div class="font-ethiopic font-bold text-sm sm:text-base text-brand-amber tracking-wide truncate"><?= $EFC_SITENAME_AM ?></div>
                    <div class="font-sans font-extrabold text-white text-base sm:text-lg tracking-tight truncate"><?= $EFC_SITENAME ?></div>
                </div>
            </a>

            <nav class="hidden xl:flex items-center gap-1" aria-label="Primary">
                <?php foreach ($EFC_NAV as $item): ?>
                    <?php $active = ($item['page'] === $EFC_PAGE); ?>
                    <a href="<?= efc_url($item['page']) ?>"
                       class="px-3 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap <?= $active ? 'bg-brand-orange text-brand-midnight shadow-sm' : 'text-white/90 hover:text-white hover:bg-white/10' ?>">
                        <?= $e(bp($item)) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <a href="<?= $EFC_TOGGLE ?>"
                   aria-label="Switch language between English and Amharic"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-white/20 bg-white/5 text-white text-xs sm:text-sm font-semibold min-h-[42px] hover:bg-white/20 transition-colors">
                    <i data-lucide="globe" class="w-4 h-4 text-brand-orange"></i>
                    <span><?= $EFC_LANG === 'en' ? 'አማርኛ' : 'EN' ?></span>
                </a>
                <a href="<?= efc_url('admissions') ?>"
                   class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-bold text-sm shadow-glow transition-all whitespace-nowrap">
                    <?= $e(bp($EFC_UI['apply_now'])) ?> <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </a>
                <a href="<?= $e($EFC_CONTACT['sms']) ?>" target="_blank" rel="noreferrer noopener"
                   class="hidden sm:inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg border border-brand-orange bg-brand-orange/10 text-brand-orange hover:bg-brand-orange hover:text-brand-midnight font-bold text-sm transition-all whitespace-nowrap">
                    <?= $e(bp($EFC_UI['student_portal'])) ?> <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </a>
                <button type="button" id="efcMenuBtn" aria-label="Open navigation menu"
                        class="xl:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg bg-white/10 hover:bg-white/20 text-white border border-white/15 transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="efcMobileMenu" class="hidden xl:hidden bg-brand-navyDark border-t border-white/10">
            <nav class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1" aria-label="Mobile">
                <?php foreach ($EFC_NAV as $item): ?>
                    <?php $active = ($item['page'] === $EFC_PAGE); ?>
                    <a href="<?= efc_url($item['page']) ?>"
                       class="px-3 py-2.5 rounded-lg text-sm font-semibold transition-colors <?= $active ? 'bg-brand-orange text-brand-midnight' : 'text-white/90 hover:bg-white/10' ?>">
                        <?= $e(bp($item)) ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= efc_url('admissions') ?>"
                   class="mt-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-brand-orange text-brand-midnight font-bold text-sm">
                    <?= $e(bp($EFC_UI['apply_now'])) ?> <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
                </a>
            </nav>
        </div>
    </header>
