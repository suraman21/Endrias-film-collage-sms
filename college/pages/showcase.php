<?php
/**
 * Endrias Film College — Endi Award / Showcase page (port of src/pages/ShowcasePage.tsx).
 * Renders the 12 Endi Award categories, the multi-year graduation archive, and the
 * alumni/student-work spotlight. Relies on $EFC_ENDI_CATEGORIES / $EFC_GRADUATIONS /
 * $EFC_SHOWCASE (data.php).
 */
$S = $EFC_SHOWCASE;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
?>
<main>

    <!-- Hero Banner with Endi Award Premiere Photo -->
    <section class="relative bg-brand-midnight text-white overflow-hidden border-b border-brand-amber/30">
        <div class="absolute inset-0">
            <img src="college/assets/images/endi-award-premiere.jpg"
                 alt="Endi Award premiere ceremony in Addis Ababa"
                 class="w-full h-full object-cover opacity-35">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-midnight via-brand-midnight/90 to-brand-midnight/60"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 max-w-4xl lg:mx-auto space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($S['hero_eyebrow'])) ?>
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($S['hero_h1'])) ?></h1>
            <p class="text-white/80 text-base sm:text-lg leading-relaxed max-w-3xl"><?= $esc(bp($S['hero_lead'])) ?></p>
        </div>
    </section>

    <!-- SECTION 1: THE ENDI AWARD — 12 CATEGORIES -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                        <i data-lucide="trophy" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($S['s1_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-ink"><?= $esc(bp($S['s1_h2'])) ?></h2>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-brand-navy text-white text-xs font-mono font-semibold"><?= $esc(bp($S['s1_badge'])) ?></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($EFC_ENDI_CATEGORIES as $i => $cat): ?>
                    <div class="p-5 rounded-2xl bg-white border border-brand-navy/12 shadow-card flex items-start gap-3.5 hover:border-brand-navy/30 transition-all">
                        <div class="w-9 h-9 rounded-xl bg-brand-orange/15 text-brand-orangeHover font-mono font-extrabold text-xs flex items-center justify-center shrink-0"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></div>
                        <div class="space-y-1">
                            <h3 class="font-extrabold text-brand-ink text-sm sm:text-base"><?= $esc($tr($cat['en'], $cat['am'])) ?></h3>
                            <p class="text-xs font-mono text-brand-muted"><?= $esc($cat['weight']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Guma Award Institutional Quota Highlight -->
            <div class="rounded-3xl bg-gradient-to-r from-brand-navyDark via-brand-navy to-brand-slate text-white p-7 sm:p-10 shadow-cinema grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-8 space-y-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange text-brand-midnight font-mono text-xs font-extrabold">
                        <?= $esc('GUMA AWARD (ጉማ አዋርድ) OFFICIAL PARTNER') ?>
                    </div>
                    <h3 class="text-2xl sm:text-3xl font-extrabold"><?= $esc(bp($S['guma_h3'])) ?></h3>
                    <p class="text-sm sm:text-base text-white/80 leading-relaxed"><?= $esc(bp($S['guma_p'])) ?></p>
                </div>
                <div class="lg:col-span-4 flex lg:justify-end">
                    <a href="<?= efc_url('programs') ?>"
                       class="inline-flex items-center gap-2 px-6 py-4 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm shadow-glow transition-all">
                        <?= $esc(bp($S['guma_cta'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: MULTI-YEAR GRADUATION ARCHIVE -->
    <section class="py-14 sm:py-20 bg-brand-sand/70 border-y border-brand-navy/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                    <i data-lucide="ticket" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($S['s2_eyebrow'])) ?>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($S['s2_h2'])) ?></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($EFC_GRADUATIONS as $g):
                    $hl = $am ? $g['highlightsAm'] : $g['highlightsEn'];
                ?>
                    <article class="bg-white rounded-3xl p-6 sm:p-8 border border-brand-navy/12 shadow-card space-y-4 flex flex-col justify-between hover:border-brand-navy/30 transition-all">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="px-3 py-1 rounded-lg bg-brand-navy text-white font-mono text-xs font-bold"><?= $esc($g['yearEc']) ?></span>
                                <span class="px-3 py-1 rounded-lg bg-brand-orange/15 text-brand-orangeHover font-bold text-xs"><?= $esc($g['graduatesCount']) ?></span>
                            </div>
                            <h3 class="text-xl font-extrabold text-brand-ink"><?= $esc($tr($g['venueEn'], $g['venueAm'])) ?></h3>
                            <div class="text-xs font-mono text-brand-muted"><?= $esc($tr($g['dateEn'], $g['dateAm'])) ?></div>

                            <?php if (!empty($g['honoreeEn'])): ?>
                                <div class="p-3 rounded-xl bg-brand-ivory border border-brand-amber/40 text-xs sm:text-sm font-bold text-brand-navy flex items-center gap-2">
                                    <i data-lucide="award" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                    <span><?= $esc($tr($g['honoreeEn'], isset($g['honoreeAm']) ? $g['honoreeAm'] : $g['honoreeEn'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <ul class="space-y-2 pt-1">
                                <?php foreach ($hl as $h): ?>
                                    <li class="flex items-start gap-2 text-xs sm:text-sm text-brand-ink/85">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                                        <span><?= $esc($h) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 3: NOTABLE STUDENT WORKS & ALUMNI SPOTLIGHT -->
    <section class="py-14 sm:py-20 bg-brand-ivory">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                    <i data-lucide="star" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($S['s3_eyebrow'])) ?>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($S['s3_h2'])) ?></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                $al_cards = [
                    'icon' => $S['alumni_icons'][0], 'h3' => $S['al1_h3'], 'p' => $S['al1_p'],
                    'icon' => $S['alumni_icons'][1], 'h3' => $S['al2_h3'], 'p' => $S['al2_p'],
                    'icon' => $S['alumni_icons'][2], 'h3' => $S['al3_h3'], 'p' => $S['al3_p'],
                ];
                ?>
                <?php foreach ($al_cards as $c): ?>
                    <div class="p-6 rounded-3xl bg-white border border-brand-navy/12 shadow-card space-y-3 hover:border-brand-navy/30 transition-all">
                        <i data-lucide="<?= $esc($c['icon']) ?>" class="w-7 h-7 text-brand-orange"></i>
                        <h3 class="font-extrabold text-lg text-brand-ink"><?= $esc(bp($c['h3'])) ?></h3>
                        <p class="text-xs sm:text-sm text-brand-muted leading-relaxed"><?= $esc(bp($c['p'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</main>
