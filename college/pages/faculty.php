<?php
/**
 * Endrias Film College — Faculty page (port of src/pages/FacultyPage.tsx).
 * Server-renders all faculty + masterclasses; light JS filters the masterclass
 * grid by category (the React useState behaviour).
 * Relies on $EFC_FACULTY / $EFC_MASTERCLASSES / $EFC_FACULTY_UI (data.php).
 */
$F = $EFC_FACULTY_UI;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
?>
<main>

    <!-- Page Hero -->
    <section class="bg-brand-navyDark text-white py-14 sm:py-20 border-b border-brand-amber/30 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 max-w-4xl lg:mx-auto space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($F['hero_eyebrow'])) ?>
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($F['hero_h1'])) ?></h1>
            <p class="text-white/80 text-base sm:text-lg leading-relaxed max-w-3xl"><?= $esc(bp($F['hero_lead'])) ?></p>
        </div>
    </section>

    <!-- SECTION 1: LEADERSHIP & CORE FACULTY -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                        <i data-lucide="users" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($F['s1_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($F['s1_h2'])) ?></h2>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-sand text-brand-navy text-xs font-bold border border-brand-navy/15">
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($F['s1_std'])) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($EFC_FACULTY as $f):
                    $parts = explode(' ', $f['nameEn']);
                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                    $creds = $am ? $f['credentialsAm'] : $f['credentialsEn'];
                ?>
                    <article class="bg-white rounded-3xl p-6 sm:p-7 border border-brand-navy/12 shadow-card flex flex-col justify-between space-y-5 hover:border-brand-navy/35 transition-all">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-brand-navy text-brand-orange font-extrabold text-lg flex items-center justify-center shadow-sm shrink-0"><?= $esc($initials) ?></div>
                                <span class="px-3 py-1 rounded-full bg-brand-orange/15 text-brand-orangeHover text-xs font-bold"><?= $esc($tr($f['badgeEn'], $f['badgeAm'])) ?></span>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-brand-ink"><?= $esc($tr($f['nameEn'], $f['nameAm'])) ?></h3>
                                <p class="text-xs font-ethiopic text-brand-muted mt-0.5"><?= $esc($am ? $f['nameEn'] : $f['nameAm']) ?></p>
                                <div class="text-sm font-bold text-brand-navy mt-1.5"><?= $esc($tr($f['roleEn'], $f['roleAm'])) ?></div>
                            </div>
                            <div class="text-xs font-mono text-brand-muted bg-brand-ivory p-2.5 rounded-xl border border-brand-navy/10"><?= $esc($tr($f['specialtyEn'], $f['specialtyAm'])) ?></div>
                            <ul class="space-y-2">
                                <?php foreach ($creds as $c): ?>
                                    <li class="flex items-start gap-2 text-xs sm:text-sm text-brand-ink/85">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                                        <span><?= $esc($c) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: MASTERCLASSES -->
    <section class="py-14 sm:py-20 bg-brand-midnight text-white cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase text-brand-orange font-bold">
                        <i data-lucide="mic-2" class="w-4 h-4"></i> <?= $esc(bp($F['s2_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-4xl font-extrabold"><?= $esc(bp($F['s2_h2'])) ?></h2>
                    <p class="text-sm sm:text-base text-white/75"><?= $esc(bp($F['s2_lead'])) ?></p>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <?php foreach ($F['mc_tabs'] as $i => $b): ?>
                        <button type="button" data-mc="<?= $esc($b['id']) ?>"
                                class="efc-mctab px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= $i === 0 ? 'bg-brand-orange text-brand-midnight' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                            <?= $esc(bp($b)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="efcWorkshops" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($EFC_MASTERCLASSES as $w): ?>
                    <article data-category="<?= $esc($w['category']) ?>"
                             class="p-6 sm:p-7 rounded-3xl bg-brand-navyDark/90 border border-white/12 flex flex-col justify-between space-y-4">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-bold">
                                    <i data-lucide="globe" class="w-3.5 h-3.5"></i> <?= $esc($tr($w['originEn'], $w['originAm'])) ?>
                                </span>
                                <span class="text-xs font-mono text-white/60"><?= $esc($tr($w['dateEn'], $w['dateAm'])) ?></span>
                            </div>
                            <h3 class="text-xl font-extrabold text-white"><?= $esc($tr($w['speakerEn'], $w['speakerAm'])) ?></h3>
                            <div class="text-xs sm:text-sm font-semibold text-brand-amber"><?= $esc($tr($w['affiliationEn'], $w['affiliationAm'])) ?></div>
                            <div class="p-3 rounded-xl bg-white/5 border border-white/10 text-sm font-bold text-white flex items-center gap-2">
                                <i data-lucide="sparkles" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                <span><?= $esc($tr($w['topicEn'], $w['topicAm'])) ?></span>
                            </div>
                            <p class="text-xs sm:text-sm text-white/75 leading-relaxed"><?= $esc($tr($w['summaryEn'], $w['summaryAm'])) ?></p>
                        </div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between text-xs text-brand-amber">
                            <span><?= $esc(bp($F['mc_free'])) ?></span>
                            <i data-lucide="award" class="w-4 h-4 text-brand-orange"></i>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="pt-4 text-center">
                <a href="<?= efc_url('admissions') ?>"
                   class="inline-flex items-center gap-2 px-6 py-4 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm sm:text-base shadow-glow transition-all">
                    <?= $esc(bp($F['cta'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>

    <script>
    (function () {
        var tabs = Array.prototype.slice.call(document.querySelectorAll('.efc-mctab'));
        var cards = Array.prototype.slice.call(document.querySelectorAll('#efcWorkshops article'));
        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                var f = t.getAttribute('data-mc');
                tabs.forEach(function (x) {
                    var on = x === t;
                    x.classList.toggle('bg-brand-orange', on); x.classList.toggle('text-brand-midnight', on);
                    x.classList.toggle('bg-white/10', !on); x.classList.toggle('text-white/80', !on);
                    x.classList.toggle('hover:bg-white/20', !on);
                });
                cards.forEach(function (c) {
                    c.style.display = (f === 'all' || c.getAttribute('data-category') === f) ? '' : 'none';
                });
            });
        });
    })();
    </script>
</main>
