<?php
/**
 * Endrias Film College — About & Accreditation page (port of src/pages/AboutPage.tsx).
 * Static port: regulatory dossier, brand-emblem symbolism, chronological timeline,
 * and cultural/institutional collaborations. Relies on $EFC_ABOUT / $EFC_TIMELINE
 * (data.php).
 */
$B = $EFC_ABOUT;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
?>
<main>

    <!-- Page Hero -->
    <section class="bg-brand-midnight text-white py-14 sm:py-20 border-b border-brand-amber/30 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-8 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($B['hero_eyebrow'])) ?>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($B['hero_h1'])) ?></h1>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed"><?= $esc(bp($B['hero_lead'])) ?></p>
            </div>
            <div class="lg:col-span-4 flex justify-center lg:justify-end">
                <div class="p-6 rounded-3xl bg-brand-navyDark border border-brand-amber/30 flex items-center gap-4">
                    <img src="college/assets/brand/logo-seal.png" alt="Endrias Seal" class="w-20 h-20 rounded-full shrink-0">
                    <div>
                        <div class="font-mono text-xs text-brand-orange font-bold"><?= $esc($B['seal_card'][0]) ?></div>
                        <div class="font-bold text-white text-base"><?= $esc($B['seal_card'][1]) ?></div>
                        <div class="text-xs text-white/70 mt-0.5"><?= $esc($B['seal_card'][2]) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 1: OFFICIAL GOVERNMENT ACCREDITATION VERIFICATION -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="max-w-3xl space-y-2.5">
                <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                    <i data-lucide="shield-check" class="w-4 h-4 text-brand-orange"></i>
                    <?= $esc(bp($B['s1_eyebrow'])) ?>
                </div>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-ink"><?= $esc(bp($B['s1_h2'])) ?></h2>
                <p class="text-brand-muted text-sm sm:text-base leading-relaxed"><?= $esc(bp($B['s1_lead'])) ?></p>
            </div>

            <!-- Regulatory Record Card -->
            <div class="bg-white rounded-3xl border border-brand-navy/15 shadow-card overflow-hidden">
                <div class="bg-brand-navy text-white px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 font-bold text-sm sm:text-base">
                        <i data-lucide="file-check-2" class="w-5 h-5 text-brand-orange"></i>
                        <?= $esc(bp($B['dossier_title'])) ?>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-brand-orange text-brand-midnight font-mono text-xs font-extrabold"><?= $esc($B['dossier_badge']) ?></span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-brand-navy/10">
                    <?php foreach ($B['dossier'] as $cell): ?>
                        <div class="p-5 sm:p-6 space-y-1">
                            <div class="text-xs font-mono uppercase text-brand-muted"><?= $esc($tr($cell['labelEn'], $cell['labelAm'])) ?></div>
                            <div class="font-extrabold text-brand-ink text-sm sm:text-base"><?= $esc($tr($cell['valEn'], $cell['valAm'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 2: BRAND EMBLEM & VISUAL IDENTITY -->
    <section class="py-14 sm:py-20 bg-brand-sand/70 border-y border-brand-navy/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5 grid grid-cols-2 gap-4">
                    <div class="bg-brand-midnight rounded-3xl p-6 flex flex-col items-center justify-center text-center border border-brand-amber/30 shadow-card space-y-3">
                        <img src="college/assets/brand/logo-seal.png" alt="Official Circular Seal" class="w-28 h-28 sm:w-32 sm:h-32 rounded-full object-contain">
                        <div class="text-xs font-mono text-brand-amber"><?= $esc($B['seal_caption']) ?></div>
                    </div>
                    <div class="bg-white rounded-3xl p-6 flex flex-col items-center justify-center text-center border border-brand-navy/15 shadow-card space-y-3">
                        <img src="college/assets/brand/logo-mark.png" alt="Standalone Mortarboard and Film Reel Emblem" class="w-28 h-28 sm:w-32 sm:h-32 object-contain">
                        <div class="text-xs font-mono text-brand-navy font-bold"><?= $esc($B['mark_caption']) ?></div>
                    </div>
                </div>
                <div class="lg:col-span-7 space-y-4">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                        <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($B['s2_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($B['s2_h2'])) ?></h2>
                    <p class="text-brand-muted text-sm sm:text-base leading-relaxed"><?= $esc(bp($B['s2_lead'])) ?></p>
                    <div class="flex flex-wrap gap-3 pt-2 text-xs font-mono">
                        <span class="px-3 py-1.5 rounded-lg bg-brand-navy text-white"><?= $esc($B['color_chips'][0]) ?></span>
                        <span class="px-3 py-1.5 rounded-lg bg-brand-orange text-brand-midnight font-bold"><?= $esc($B['color_chips'][1]) ?></span>
                        <span class="px-3 py-1.5 rounded-lg bg-white border border-brand-navy/20 text-brand-ink"><?= $esc($B['color_chips'][2]) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: CHRONOLOGICAL TIMELINE -->
    <section class="py-14 sm:py-20 bg-brand-ivory">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($B['s3_eyebrow'])) ?>
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span>
                </div>
                <h2 class="text-2xl sm:text-4xl font-extrabold text-brand-ink"><?= $esc(bp($B['s3_h2'])) ?></h2>
            </div>
            <div class="space-y-6 relative">
                <div class="hidden sm:block absolute top-4 bottom-4 left-6 w-0.5 bg-brand-navy/20"></div>
                <?php foreach ($EFC_TIMELINE as $m): ?>
                    <div class="relative sm:pl-16">
                        <div class="hidden sm:flex absolute left-2.5 top-5 w-7 h-7 rounded-full bg-brand-navy border-4 border-brand-orange items-center justify-center"></div>
                        <div class="bg-white rounded-2xl p-6 border border-brand-navy/12 shadow-card space-y-2 hover:border-brand-navy/30 transition-all">
                            <span class="inline-block px-2.5 py-1 rounded-md bg-brand-navy/10 text-brand-navy font-mono text-xs font-bold"><?= $esc($m['year']) ?></span>
                            <h3 class="text-lg sm:text-xl font-extrabold text-brand-ink"><?= $esc($tr($m['titleEn'], $m['titleAm'])) ?></h3>
                            <p class="text-sm text-brand-muted leading-relaxed"><?= $esc($tr($m['descEn'], $m['descAm'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 4: CULTURAL & INSTITUTIONAL COLLABORATIONS -->
    <section class="py-14 sm:py-20 bg-brand-navyDark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="max-w-2xl space-y-2">
                <div class="inline-flex items-center gap-2 text-xs font-mono uppercase text-brand-orange font-bold">
                    <i data-lucide="globe-2" class="w-4 h-4"></i>
                    <?= $esc(bp($B['s4_eyebrow'])) ?>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold"><?= $esc(bp($B['s4_h2'])) ?></h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($B['collabs'] as $item): ?>
                    <div class="p-6 rounded-2xl bg-white/5 border border-white/10 space-y-2.5">
                        <i data-lucide="<?= $esc($item['icon']) ?>" class="w-6 h-6 text-brand-orange"></i>
                        <h3 class="font-bold text-base text-white"><?= $esc($tr($item['titleEn'], $item['titleAm'])) ?></h3>
                        <p class="text-xs sm:text-sm text-white/75 leading-relaxed"><?= $esc($tr($item['descEn'], $item['descAm'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="pt-4">
                <a href="<?= efc_url('admissions') ?>"
                   class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm transition-colors">
                    <?= $esc(bp($B['cta'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>

</main>
