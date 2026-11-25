<?php
/**
 * Endrias Film College — Home page (port of src/pages/HomePage.tsx).
 * Rendered between head.php and footer.php by the front controller.
 * Relies on $EFC_HOME / $EFC_UI / $EFC_PROGRAM_TIERS / $EFC_DAILY_SHIFTS /
 * $EFC_CONTACT (data.php) and bp()/efc_url() (lang.php), all in scope.
 */
$H = $EFC_HOME;

// Small local helpers ------------------------------------------------------
$eyebrow = static function (string $bg, array $pair, string $cls = ''): string {
    return '<div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest font-bold ' . $cls . ' ' . $bg . '">'
        . '<span class="font-ethiopic font-bold select-none tracking-widest">፠</span>'
        . htmlspecialchars(bp($pair), ENT_QUOTES, 'UTF-8') . '</div>';
};
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

// Enroll URL builder (admissions + program)
$enrollUrl = static function (string $id) use ($EFC_LANG): string {
    $qs = 'index.php?p=admissions';
    if ($EFC_LANG === 'am') { $qs .= '&lang=am'; }
    return $qs . '&program=' . rawurlencode($id);
};
?>
<main id="top">

    <!-- ═══════════ HERO ═══════════ -->
    <section class="relative bg-brand-midnight text-white overflow-hidden">
        <div class="absolute inset-0">
            <img src="college/assets/images/hero-studio.jpg"
                 alt="Endrias Film College students operating cinema camera and studio lighting on set"
                 class="w-full h-full object-cover object-center opacity-45">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-midnight via-brand-midnight/90 to-brand-midnight/45"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-brand-midnight via-transparent to-brand-midnight/40"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 lg:py-28">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                <!-- Left editorial column -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="inline-flex flex-wrap items-center gap-2.5 px-4 py-2 rounded-xl sm:rounded-full bg-brand-navy/85 border border-brand-amber/40 backdrop-blur-sm">
                        <span class="inline-flex items-center gap-2 font-mono text-xs font-semibold text-brand-orange">
                            <span class="relative flex h-2.5 w-2.5 shrink-0">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-orange opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-brand-orange"></span>
                            </span>
                            REC · 24 FPS
                        </span>
                        <span class="text-white/30 hidden sm:inline">|</span>
                        <span class="text-xs sm:text-sm font-medium text-white/95"><?= $esc(bp($H['hero_pill'])) ?></span>
                    </div>

                    <div class="space-y-3">
                        <div class="font-ethiopic text-brand-amber font-bold text-lg sm:text-2xl tracking-wide"><?= $esc($H['hero_kicker']) ?></div>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.08] text-white">
                            <?= $esc(bp($H['hero_head1'])) ?>
                            <span class="font-serif italic font-normal text-brand-orange"><?= $esc(bp($H['hero_head2'])) ?></span>
                        </h1>
                    </div>

                    <p class="text-base sm:text-lg text-white/80 max-w-2xl leading-relaxed"><?= $esc(bp($H['hero_lead'])) ?></p>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 pt-2">
                        <a href="<?= efc_url('programs') ?>"
                           class="group inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-base shadow-glow transition-all hover:scale-[1.02] active:scale-95">
                            <?= $esc(bp($H['hero_cta1'])) ?>
                            <span class="w-5 h-5 inline-flex transition-transform group-hover:translate-x-1"><i data-lucide="arrow-right" class="w-5 h-5"></i></span>
                        </a>
                        <a href="<?= efc_url('admissions') ?>"
                           class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-base border border-white/25 backdrop-blur-sm transition-all hover:scale-[1.03] active:scale-95">
                            <?= $esc(bp($H['hero_cta2'])) ?>
                        </a>
                    </div>

                    <div class="pt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-mono text-white/65">
                        <?php $specIcons = ['camera', 'sparkles', 'shield-check']; ?>
                        <?php foreach ($EFC_UI['spec_strip'] as $i => $spec): ?>
                            <span class="inline-flex items-center gap-1.5 text-white/85 hover:scale-105 transition-transform">
                                <i data-lucide="<?= $specIcons[$i] ?? 'film' ?>" class="w-4 h-4 text-brand-orange"></i>
                                <?= $esc($spec) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right crest medallion -->
                <div class="lg:col-span-4 flex justify-center lg:justify-end">
                    <div class="w-full max-w-sm rounded-3xl bg-brand-navyDark/80 border border-brand-amber/35 backdrop-blur-md p-6 sm:p-7 shadow-cinema viewfinder-frame">
                        <div class="flex flex-col items-center text-center space-y-4">
                            <img src="college/assets/brand/logo-seal.png" alt="Endrias Film College Crest"
                                 class="w-32 h-32 sm:w-36 sm:h-36 rounded-full drop-shadow-xl efc-float">
                            <div>
                                <div class="text-xs font-mono uppercase tracking-widest text-brand-amber"><?= $esc(bp($H['crest_seal'])) ?></div>
                                <h2 class="text-xl font-extrabold text-white mt-1">Endrias Film College</h2>
                                <p class="font-ethiopic text-sm text-white/80 mt-0.5"><?= $esc($EFC_UI['brand_name_am']) ?></p>
                            </div>
                            <div class="w-full pt-3 border-t border-white/10 space-y-2 text-left text-xs">
                                <div class="flex items-center justify-between text-white/85">
                                    <span class="text-white/60"><?= $esc(bp($H['crest_acc'])) ?></span>
                                    <span class="font-semibold text-brand-amber"><?= $esc($EFC_UI['crest_plain'][0]) ?></span>
                                </div>
                                <div class="flex items-center justify-between text-white/85">
                                    <span class="text-white/60"><?= $esc(bp($H['crest_deg'])) ?></span>
                                    <span class="font-semibold text-white"><?= $esc($EFC_UI['crest_plain'][1]) ?></span>
                                </div>
                                <div class="flex items-center justify-between text-white/85">
                                    <span class="text-white/60"><?= $esc(bp($H['crest_guma'])) ?></span>
                                    <span class="font-semibold text-white"><?= $esc($EFC_UI['crest_plain'][2]) ?></span>
                                </div>
                                <div class="flex items-center justify-between text-white/85">
                                    <span class="text-white/60"><?= $esc(bp($H['crest_campus'])) ?></span>
                                    <span class="font-semibold text-white"><?= $esc($EFC_UI['crest_plain'][3]) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ KEY METRICS STRIP ═══════════ -->
    <section class="bg-brand-navy text-white border-y border-brand-amber/30 efc-reveal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                <?php foreach ($H['m_stats'] as $m): ?>
                    <div class="border-l-2 border-brand-orange pl-4 space-y-1">
                        <div class="text-2xl sm:text-3xl font-extrabold font-mono text-brand-orange"><?= $esc($m['stat']) ?></div>
                        <div class="text-xs sm:text-sm text-white/85 leading-snug"><?= $esc(bp($H[$m['key']])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 1: SIX PROGRAM TIERS ═══════════ -->
    <section class="py-16 sm:py-24 ivory-grid-bg efc-reveal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
                <div class="space-y-2.5 max-w-2xl">
                    <?= $eyebrow('', $H['s1_eye'], 'text-brand-navy') ?>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-brand-ink tracking-tight"><?= $esc(bp($H['s1_h2'])) ?></h2>
                    <p class="text-brand-muted text-base"><?= $esc(bp($H['s1_p'])) ?></p>
                </div>
                <a href="<?= efc_url('programs') ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-navy hover:bg-brand-navyDark text-white font-bold text-sm transition-colors self-start md:self-auto shrink-0">
                    <?= $esc(bp($H['s1_btn'])) ?> <i data-lucide="arrow-right" class="w-4 h-4 text-brand-orange"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($EFC_PROGRAM_TIERS as $p): ?>
                    <?php $f = !empty($p['featured']); ?>
                    <div class="rounded-2xl p-6 sm:p-7 flex flex-col justify-between border hover:-translate-y-1 transition-transform <?= $f
                        ? 'bg-brand-navy text-white border-brand-amber shadow-cinema ring-2 ring-brand-orange/50'
                        : 'bg-white text-brand-ink border-brand-navy/12' ?>">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-1 rounded-md text-xs font-mono font-bold <?= $f ? 'bg-brand-orange text-brand-midnight' : 'bg-brand-navy/10 text-brand-navy' ?>">
                                    <?= $esc($p['code']) ?>
                                </span>
                                <span class="text-xs font-semibold <?= $f ? 'text-brand-amber' : 'text-brand-muted' ?>"><?= $esc($EFC_LANG === 'am' ? $p['durationAm'] : $p['durationEn']) ?></span>
                            </div>
                            <div>
                                <div class="text-xs font-semibold mb-1 <?= $f ? 'text-brand-amber' : 'text-brand-orangeHover' ?>"><?= $esc($EFC_LANG === 'am' ? $p['levelBadgeAm'] : $p['levelBadgeEn']) ?></div>
                                <h3 class="text-xl font-extrabold leading-snug"><?= $esc($EFC_LANG === 'am' ? $p['titleAm'] : $p['titleEn']) ?></h3>
                            </div>
                            <p class="text-sm leading-relaxed <?= $f ? 'text-white/80' : 'text-brand-muted' ?>"><?= $esc($EFC_LANG === 'am' ? $p['summaryAm'] : $p['summaryEn']) ?></p>
                            <ul class="space-y-2 pt-1">
                                <?php $hls = $EFC_LANG === 'am' ? $p['highlightsAm'] : $p['highlightsEn']; ?>
                                <?php foreach ($hls as $hl): ?>
                                    <li class="flex items-start gap-2 text-xs sm:text-sm">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 mt-0.5 <?= $f ? 'text-brand-orange' : 'text-brand-navy' ?>"></i>
                                        <span class="<?= $f ? 'text-white/90' : 'text-brand-ink/85' ?>"><?= $esc($hl) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-6 pt-4 border-t flex items-center justify-between gap-3 <?= $f ? 'border-white/15' : 'border-brand-navy/10' ?>">
                            <a href="<?= efc_url('programs') ?>#<?= $esc($p['id']) ?>"
                               class="text-sm font-bold inline-flex items-center gap-1.5 <?= $f ? 'text-brand-orange hover:text-white' : 'text-brand-navy hover:text-brand-orangeHover' ?>">
                                <?= $esc(bp($H['card_view'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                            <a href="<?= $enrollUrl($p['id']) ?>"
                               class="px-3.5 py-2 rounded-lg text-xs font-bold transition-colors <?= $f
                                   ? 'bg-white/15 hover:bg-brand-orange hover:text-brand-midnight text-white'
                                   : 'bg-brand-sand hover:bg-brand-navy hover:text-white text-brand-navy' ?>">
                                <?= $esc(bp($H['card_enroll'])) ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 2: STUDIO / AI CURRICULUM ═══════════ -->
    <section class="py-16 sm:py-24 bg-brand-sand/70 border-y border-brand-navy/10 efc-reveal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                <div class="lg:col-span-6 space-y-4">
                    <div class="relative rounded-3xl overflow-hidden shadow-cinema border border-brand-navy/15">
                        <img src="college/assets/images/editing-lab.jpg"
                             alt="Post-production and DaVinci Resolve color grading lab at Endrias Film College"
                             class="w-full h-72 sm:h-96 object-cover">
                        <div class="absolute bottom-4 left-4 right-4 p-4 rounded-2xl bg-brand-midnight/90 backdrop-blur-md text-white border border-white/10 flex items-center justify-between gap-4">
                            <div>
                                <div class="font-mono text-[11px] text-brand-orange uppercase"><?= $esc($EFC_UI['s2_img_label']) ?></div>
                                <div class="text-sm font-bold"><?= $esc(bp($H['s2_img'])) ?></div>
                            </div>
                            <img src="college/assets/brand/logo-seal.png" alt="Endrias Seal" class="w-10 h-10 rounded-full shrink-0">
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 space-y-6">
                    <?= $eyebrow('', $H['s2_eye'], 'text-brand-navy') ?>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-brand-ink tracking-tight"><?= $esc(bp($H['s2_h2'])) ?></h2>
                    <p class="text-brand-muted leading-relaxed"><?= $esc(bp($H['s2_p'])) ?></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($H['s2_cards'] as $card): ?>
                            <div class="p-4 rounded-2xl bg-white border border-brand-navy/10 shadow-sm space-y-1.5">
                                <div class="w-9 h-9 rounded-lg bg-brand-navy text-brand-orange flex items-center justify-center">
                                    <i data-lucide="<?= $esc($card['icon']) ?>" class="w-5 h-5"></i>
                                </div>
                                <h3 class="font-bold text-sm sm:text-base text-brand-ink"><?= $esc(bp($H[$card['key']])) ?></h3>
                                <p class="text-xs text-brand-muted leading-relaxed"><?= $esc(bp($H[$card['descKey']])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 3: ENDI AWARD / GUMA / MASTERCLASSES ═══════════ -->
    <section class="py-16 sm:py-24 bg-brand-midnight text-white cinema-grid-bg efc-reveal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-6 space-y-6">
                    <?= $eyebrow('', $H['s3_eye'], 'text-brand-orange') ?>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight"><?= $esc(bp($H['s3_h2'])) ?></h2>
                    <p class="text-white/75 leading-relaxed"><?= $esc(bp($H['s3_p'])) ?></p>

                    <div class="space-y-3">
                        <?php foreach ($H['s3_rows'] as $row): ?>
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 flex items-start gap-3.5">
                                <div class="w-10 h-10 rounded-xl bg-brand-orange/20 text-brand-orange flex items-center justify-center shrink-0">
                                    <i data-lucide="<?= $esc($row['icon']) ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-sm sm:text-base"><?= $esc(bp($H[$row['tKey']])) ?></h3>
                                    <p class="text-xs sm:text-sm text-white/70 mt-1 leading-relaxed"><?= $esc(bp($H[$row['xKey']])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="<?= efc_url('showcase') ?>"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-bold text-sm transition-colors">
                            <?= $esc(bp($H['s3_btn'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                        <a href="<?= efc_url('faculty') ?>"
                           class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm transition-colors">
                            <?= $esc(bp($H['fac_btn'])) ?>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-6">
                    <div class="rounded-3xl overflow-hidden border border-brand-amber/30 shadow-cinema bg-brand-navyDark">
                        <img src="college/assets/images/endi-award-premiere.jpg"
                             alt="Endrias Film College Endi Award graduation night in Addis Ababa cinema hall"
                             class="w-full h-80 sm:h-[420px] object-cover">
                        <div class="p-6 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-mono text-brand-amber">
                                <span><?= $esc($EFC_UI['s3_img_top']) ?></span>
                                <span><?= $esc($EFC_UI['s3_img_award']) ?></span>
                            </div>
                            <p class="text-sm text-white/85 leading-relaxed"><?= $esc(bp($H['s3_img_p'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════ SECTION 4: SCHEDULE + VISIT CTA ═══════════ -->
    <section class="py-16 sm:py-20 bg-brand-ivory efc-reveal">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="bg-white rounded-3xl border border-brand-navy/12 shadow-card p-6 sm:p-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-8 border-b border-brand-navy/10">
                    <div>
                        <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest font-bold text-brand-navy">
                            <i data-lucide="clock" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($H['s4_eye'])) ?>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink mt-1"><?= $esc(bp($H['s4_h2'])) ?></h2>
                    </div>
                    <a href="<?= efc_url('admissions') ?>"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-bold text-sm self-start md:self-auto">
                        <?= $esc(bp($H['s4_btn'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 pt-8">
                    <?php foreach ($EFC_DAILY_SHIFTS as $s): ?>
                        <div class="p-4 rounded-2xl bg-brand-ivory border border-brand-navy/10 flex flex-col justify-between space-y-3">
                            <div>
                                <span class="inline-block px-2 py-0.5 rounded bg-brand-navy/10 text-brand-navy font-mono text-[11px] font-bold"><?= $esc($EFC_LANG === 'am' ? $s['daysAm'] : $s['daysEn']) ?></span>
                                <h3 class="font-bold text-brand-ink text-sm mt-2"><?= $esc($EFC_LANG === 'am' ? $s['labelAm'] : $s['labelEn']) ?></h3>
                            </div>
                            <div class="pt-2 border-t border-brand-navy/10 space-y-0.5">
                                <div class="font-ethiopic text-xs font-bold text-brand-navy"><?= $esc($s['ethiopianTime']) ?></div>
                                <div class="font-mono text-xs text-brand-muted"><?= $esc($s['intlTime']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Visit 4 Kilo Campus CTA -->
            <div class="rounded-3xl bg-gradient-to-r from-brand-navyDark via-brand-navy to-brand-slate text-white p-8 sm:p-12 shadow-cinema flex flex-col lg:flex-row items-center justify-between gap-8">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                    <div class="w-20 h-20 rounded-2xl bg-brand-ivory p-2.5 flex items-center justify-center shrink-0 shadow-md">
                        <img src="college/assets/brand/logo-mark.png" alt="Endrias Mortarboard & Film Reel Emblem" class="w-full h-full object-contain">
                    </div>
                    <div class="space-y-2 max-w-xl">
                        <div class="inline-flex items-center gap-1.5 text-xs font-mono text-brand-amber uppercase">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-brand-orange"></i> <?= $esc($EFC_UI['s4_campushdr']) ?>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold"><?= $esc(bp($H['s4_visit'])) ?></h2>
                        <p class="text-sm sm:text-base text-white/80"><?= $esc(bp($H['s4_tour'])) ?></p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto shrink-0">
                    <a href="<?= efc_url('admissions') ?>"
                       class="px-6 py-4 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm sm:text-base text-center shadow-glow transition-all hover:scale-[1.03]">
                        <?= $esc(bp($H['s4_pre'])) ?>
                    </a>
                    <a href="tel:+251900003919"
                       class="px-6 py-4 rounded-xl bg-white/10 hover:bg-white/20 text-white font-mono font-bold text-sm sm:text-base text-center border border-white/20 transition-all">
                        <?= $esc($EFC_UI['phone1']) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>
