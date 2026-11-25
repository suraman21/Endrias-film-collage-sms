<?php
/**
 * Endrias Film College — Programs page (port of src/pages/ProgramsPage.tsx).
 * Server-renders all 6 tiers, the study-planner recommendation, and the 15-module
 * catalog. Light JS reproduces the React useMemo/useState behaviour: tier filter
 * pills, goal+shift recommender, and discipline+search catalog filtering.
 * Relies on $EFC_PROGRAM_TIERS / $EFC_COURSE_MODULES / $EFC_DAILY_SHIFTS /
 * $EFC_PROGRAMS (data.php).
 */
$P = $EFC_PROGRAMS;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
$goalMap = $P['goal_map'];

// Default recommendation: goal 'degree' + shift 'shift-1' (React initial state).
$defaultTier = null;
foreach ($EFC_PROGRAM_TIERS as $p) {
    if ($p['id'] === $goalMap['degree']) { $defaultTier = $p; break; }
}
if ($defaultTier === null) $defaultTier = $EFC_PROGRAM_TIERS[0];
$defaultShift = null;
foreach ($EFC_DAILY_SHIFTS as $s) {
    if ($s['id'] === 'shift-1') { $defaultShift = $s; break; }
}
if ($defaultShift === null) $defaultShift = $EFC_DAILY_SHIFTS[0];

// Localized lookup tables consumed by the recommender JS.
$tierData = [];
foreach ($EFC_PROGRAM_TIERS as $p) {
    $tierData[$p['id']] = [
        'id' => $p['id'], 'code' => $p['code'],
        'title' => $tr($p['titleEn'], $p['titleAm']),
        'duration' => $tr($p['durationEn'], $p['durationAm']),
        'pathway' => $tr($p['pathwayEn'], $p['pathwayAm']),
    ];
}
$shiftData = [];
foreach ($EFC_DAILY_SHIFTS as $s) {
    $shiftData[$s['id']] = ['eth' => $s['ethiopianTime'], 'intl' => $s['intlTime']];
}
?>
<main>

    <!-- Page Hero -->
    <section class="bg-brand-navyDark text-white py-14 sm:py-20 border-b border-brand-amber/30 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($P['hero_eyebrow'])) ?>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($P['hero_h1'])) ?></h1>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed"><?= $esc(bp($P['hero_lead'])) ?></p>
            </div>
        </div>
    </section>

    <!-- SECTION 1: PROGRAM TIERS WITH FILTER PILLS -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($P['s1_h2'])) ?></h2>
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                    <?php foreach ($P['tier_tabs'] as $i => $b): ?>
                        <button type="button" data-tier="<?= $esc($b['id']) ?>"
                                class="efc-tier px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all <?= $i === 0 ? 'bg-brand-navy text-white shadow-sm' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/15' ?>">
                            <?= $esc(bp($b)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="efcTiers" class="space-y-6">
                <?php foreach ($EFC_PROGRAM_TIERS as $prog):
                    $feat = !empty($prog['featured']);
                    $hl = $am ? $prog['highlightsAm'] : $prog['highlightsEn'];
                    $mods = $am ? $prog['modulesAm'] : $prog['modulesEn'];
                ?>
                    <article data-category="<?= $esc($prog['category']) ?>"
                             class="rounded-3xl p-6 sm:p-8 border transition-all <?= $feat ? 'bg-brand-navy text-white border-brand-amber shadow-cinema' : 'bg-white text-brand-ink border-brand-navy/15 shadow-card' ?>">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            <!-- Left Overview -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="px-3 py-1 rounded-lg font-mono text-xs font-bold <?= $feat ? 'bg-brand-orange text-brand-midnight' : 'bg-brand-navy text-white' ?>"><?= $esc($prog['code']) ?></span>
                                    <span class="text-xs font-semibold px-3 py-1 rounded-lg <?= $feat ? 'bg-white/10 text-brand-amber' : 'bg-brand-sand text-brand-navy' ?>"><?= $esc($tr($prog['durationEn'], $prog['durationAm'])) ?></span>
                                    <span class="text-xs font-medium <?= $feat ? 'text-white/75' : 'text-brand-muted' ?>"><?= $esc($tr($prog['scheduleEn'], $prog['scheduleAm'])) ?></span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-extrabold"><?= $esc($tr($prog['titleEn'], $prog['titleAm'])) ?></h3>
                                <p class="text-sm sm:text-base leading-relaxed <?= $feat ? 'text-white/85' : 'text-brand-muted' ?>"><?= $esc($tr($prog['summaryEn'], $prog['summaryAm'])) ?></p>
                                <div class="space-y-2 pt-1">
                                    <?php foreach ($hl as $h): ?>
                                        <div class="flex items-start gap-2.5 text-sm">
                                            <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 mt-1 <?= $feat ? 'text-brand-orange' : 'text-brand-navy' ?>"></i>
                                            <span><?= $esc($h) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="p-3.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 <?= $feat ? 'bg-brand-midnight/60 text-brand-amber border border-white/10' : 'bg-brand-ivory text-brand-navy border border-brand-navy/10' ?>">
                                    <i data-lucide="graduation-cap" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                    <span><?= $esc($tr($prog['pathwayEn'], $prog['pathwayAm'])) ?></span>
                                </div>
                            </div>

                            <!-- Right Included Modules List -->
                            <div class="lg:col-span-5 rounded-2xl p-5 sm:p-6 flex flex-col justify-between <?= $feat ? 'bg-brand-navyDark/90 border border-white/10' : 'bg-brand-ivory border border-brand-navy/10' ?>">
                                <div class="space-y-3">
                                    <div class="text-xs font-mono uppercase tracking-wider font-bold text-brand-orange flex items-center gap-1.5">
                                        <i data-lucide="layers" class="w-4 h-4"></i>
                                        <?= $esc(bp($P['tier_courses'])) ?>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php foreach ($mods as $m): ?>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium <?= $feat ? 'bg-white/10 text-white/95' : 'bg-white text-brand-ink border border-brand-navy/10' ?>"><?= $esc($m) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="pt-6 mt-6 border-t border-current/10 flex items-center justify-between gap-3">
                                    <span class="text-xs opacity-75"><?= $esc(bp($P['tier_tuition'])) ?></span>
                                    <a href="<?= efc_url('admissions') ?>?program=<?= $esc($prog['id']) ?>"
                                       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-xs sm:text-sm transition-colors">
                                        <?= $esc(bp($P['tier_apply'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: INTERACTIVE TRACK & SHIFT RECOMMENDER -->
    <section class="py-14 sm:py-20 bg-brand-midnight text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-brand-navyDark border border-brand-amber/30 p-6 sm:p-10 shadow-cinema">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-7 space-y-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 text-xs font-mono text-brand-orange uppercase font-bold">
                                <i data-lucide="compass" class="w-4 h-4"></i>
                                <?= $esc(bp($P['s2_eyebrow'])) ?>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold"><?= $esc(bp($P['s2_h2'])) ?></h2>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-mono uppercase text-white/70 mb-2">1. <?= $esc(bp($P['s2_goal_label'])) ?></label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <?php foreach ($P['goal_options'] as $i => $o): ?>
                                        <button type="button" data-goal="<?= $esc($o['id']) ?>"
                                                class="efc-goal p-3 rounded-xl text-left text-xs sm:text-sm font-semibold border transition-all <?= $i === 0 ? 'bg-brand-orange text-brand-midnight border-brand-orange font-bold' : 'bg-white/5 text-white/85 border-white/10 hover:bg-white/10' ?>">
                                            <?= $esc(bp($o)) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-mono uppercase text-white/70 mb-2">2. <?= $esc(bp($P['s2_shift_label'])) ?></label>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($EFC_DAILY_SHIFTS as $sh):
                                        $active = ($sh['id'] === 'shift-1');
                                        $tz = strtok($sh['intlTime'], ' ');
                                    ?>
                                        <button type="button" data-shift="<?= $esc($sh['id']) ?>"
                                                class="efc-shift px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all <?= $active ? 'bg-white text-brand-midnight border-white font-bold' : 'bg-white/5 text-white/80 border-white/15 hover:bg-white/10' ?>">
                                            <?= $esc($tr($sh['labelEn'], $sh['labelAm'])) ?> (<?= $esc($tz) ?>)
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation Result Card -->
                    <div class="lg:col-span-5 rounded-2xl bg-brand-midnight p-6 sm:p-7 border border-brand-orange/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono uppercase text-brand-orange font-bold"><?= $esc(bp($P['rec_title'])) ?></span>
                            <span id="recCode" class="px-2.5 py-0.5 rounded bg-brand-orange/20 text-brand-orange font-mono text-xs font-bold"><?= $esc($defaultTier['code']) ?></span>
                        </div>
                        <h3 id="recTitle" class="text-xl sm:text-2xl font-extrabold text-white"><?= $esc($tr($defaultTier['titleEn'], $defaultTier['titleAm'])) ?></h3>
                        <div class="space-y-2 text-xs sm:text-sm text-white/80 border-y border-white/10 py-4">
                            <div class="flex justify-between gap-2">
                                <span class="text-white/55"><?= $esc(bp($P['rec_duration'])) ?></span>
                                <span id="recDuration" class="font-semibold text-white"><?= $esc($tr($defaultTier['durationEn'], $defaultTier['durationAm'])) ?></span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-white/55"><?= $esc(bp($P['rec_eth'])) ?></span>
                                <span id="recEth" class="font-ethiopic font-semibold text-brand-amber"><?= $esc($defaultShift['ethiopianTime']) ?></span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-white/55"><?= $esc(bp($P['rec_std'])) ?></span>
                                <span id="recIntl" class="font-mono text-white"><?= $esc($defaultShift['intlTime']) ?></span>
                            </div>
                        </div>
                        <p id="recPath" class="text-xs text-white/75 leading-relaxed"><?= $esc($tr($defaultTier['pathwayEn'], $defaultTier['pathwayAm'])) ?></p>
                        <a id="recCta" href="<?= efc_url('admissions') ?>?program=<?= $esc($defaultTier['id']) ?>&shift=<?= $esc($defaultShift['id']) ?>"
                           class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm shadow-glow transition-all">
                            <?= $esc(bp($P['rec_cta'])) ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: SEARCHABLE COURSE & MODULE CATALOG -->
    <section class="py-14 sm:py-20 bg-brand-ivory">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                        <i data-lucide="book-open" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($P['s3_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($P['s3_h2'])) ?></h2>
                </div>
                <div class="relative w-full lg:w-80">
                    <i data-lucide="search" class="w-4 h-4 text-brand-muted absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input id="efcSearch" type="text" placeholder="<?= $esc(bp($P['s3_search'])) ?>"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white border border-brand-navy/20 text-sm text-brand-ink focus:outline-none focus:ring-2 focus:ring-brand-orange">
                </div>
            </div>

            <!-- Discipline Filter Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                <i data-lucide="filter" class="w-4 h-4 text-brand-navy shrink-0 mr-1"></i>
                <?php foreach ($P['discipline_tabs'] as $i => $d): ?>
                    <button type="button" data-disc="<?= $esc($d['id']) ?>"
                            class="efc-disc px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= $i === 0 ? 'bg-brand-orange text-brand-midnight' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/12' ?>">
                        <?= $esc(bp($d)) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Course Cards Grid -->
            <div id="efcModules" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($EFC_COURSE_MODULES as $mod):
                    $searchHay = strtolower($mod['nameEn'] . ' ' . $mod['nameAm'] . ' ' . $mod['toolsOrFocus'] . ' ' . $mod['descEn']);
                    $ltClass = $mod['levelTag'] === '2018–2019 New' ? 'bg-brand-orange/20 text-brand-orangeHover'
                        : ($mod['levelTag'] === 'VIP Exclusive' ? 'bg-brand-midnight text-brand-amber'
                        : ($mod['levelTag'] === 'Advanced Diploma' ? 'bg-brand-navy text-white'
                        : 'bg-brand-sand text-brand-navy'));
                ?>
                    <article data-discipline="<?= $esc($mod['discipline']) ?>" data-search="<?= $esc($searchHay) ?>"
                             class="p-6 rounded-2xl bg-white border border-brand-navy/12 shadow-card flex flex-col justify-between space-y-4 hover:border-brand-navy/30 transition-all">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-mono font-bold <?= $esc($ltClass) ?>">
                                    <?= $esc($mod['levelTag']) ?>
                                </span>
                                <i data-lucide="sparkles" class="w-4 h-4 text-brand-amber/70"></i>
                            </div>
                            <h3 class="font-extrabold text-lg text-brand-ink"><?= $esc($tr($mod['nameEn'], $mod['nameAm'])) ?></h3>
                            <p class="text-xs sm:text-sm text-brand-muted leading-relaxed"><?= $esc($tr($mod['descEn'], $mod['descAm'])) ?></p>
                        </div>
                        <div class="pt-3 border-t border-brand-navy/10 text-[11px] font-mono text-brand-navy font-medium"><?= $esc($mod['toolsOrFocus']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- COC Assessment & Bridging Callout -->
            <div class="rounded-3xl bg-brand-sand border border-brand-navy/15 p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-2 space-y-2">
                    <div class="text-xs font-mono uppercase font-bold text-brand-navy"><?= $esc(bp($P['coc_eyebrow'])) ?></div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-brand-ink"><?= $esc(bp($P['coc_h3'])) ?></h3>
                    <p class="text-sm text-brand-muted leading-relaxed"><?= $esc(bp($P['coc_p'])) ?></p>
                </div>
                <div class="flex md:justify-end">
                    <a href="<?= efc_url('admissions') ?>?program=level-4-diploma"
                       class="inline-flex items-center gap-2 px-5 py-3.5 rounded-xl bg-brand-navy hover:bg-brand-navyDark text-white font-bold text-sm transition-colors">
                        <?= $esc(bp($P['coc_cta'])) ?> <i data-lucide="arrow-right" class="w-4 h-4 text-brand-orange"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function () {
        var GOAL_MAP = <?= json_encode($goalMap) ?>;
        var TIER = <?= json_encode($tierData, JSON_UNESCAPED_UNICODE) ?>;
        var SHIFT = <?= json_encode($shiftData, JSON_UNESCAPED_UNICODE) ?>;
        var ADMISSIONS = <?= json_encode(efc_url('admissions')) ?>;

        // 1. Tier filter pills
        var tierBtns = Array.prototype.slice.call(document.querySelectorAll('.efc-tier'));
        var tierCards = Array.prototype.slice.call(document.querySelectorAll('#efcTiers article'));
        tierBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                var f = b.getAttribute('data-tier');
                tierBtns.forEach(function (x) {
                    var on = x === b;
                    x.className = 'efc-tier px-4 py-2 rounded-xl text-xs sm:text-sm font-bold whitespace-nowrap transition-all ' +
                        (on ? 'bg-brand-navy text-white shadow-sm' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/15');
                });
                tierCards.forEach(function (c) {
                    c.style.display = (f === 'all' || c.getAttribute('data-category') === f) ? '' : 'none';
                });
            });
        });

        // 2. Study planner (goal + shift)
        var curGoal = 'degree', curShift = 'shift-1';
        var goalBtns = Array.prototype.slice.call(document.querySelectorAll('.efc-goal'));
        var shiftBtns = Array.prototype.slice.call(document.querySelectorAll('.efc-shift'));
        function updateRec() {
            var tid = GOAL_MAP[curGoal];
            var t = TIER[tid] || TIER[Object.keys(TIER)[0]];
            var sh = SHIFT[curShift] || SHIFT[Object.keys(SHIFT)[0]];
            document.getElementById('recCode').textContent = t.code;
            document.getElementById('recTitle').textContent = t.title;
            document.getElementById('recDuration').textContent = t.duration;
            document.getElementById('recEth').textContent = sh.eth;
            document.getElementById('recIntl').textContent = sh.intl;
            document.getElementById('recPath').textContent = t.pathway;
            document.getElementById('recCta').href = ADMISSIONS + '?program=' + t.id + '&shift=' + curShift;
        }
        goalBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                curGoal = b.getAttribute('data-goal');
                goalBtns.forEach(function (x) {
                    var on = x === b;
                    x.className = 'efc-goal p-3 rounded-xl text-left text-xs sm:text-sm font-semibold border transition-all ' +
                        (on ? 'bg-brand-orange text-brand-midnight border-brand-orange font-bold' : 'bg-white/5 text-white/85 border-white/10 hover:bg-white/10');
                });
                updateRec();
            });
        });
        shiftBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                curShift = b.getAttribute('data-shift');
                shiftBtns.forEach(function (x) {
                    var on = x === b;
                    x.className = 'efc-shift px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all ' +
                        (on ? 'bg-white text-brand-midnight border-white font-bold' : 'bg-white/5 text-white/80 border-white/15 hover:bg-white/10');
                });
                updateRec();
            });
        });

        // 3. Catalog: discipline + search
        var modCards = Array.prototype.slice.call(document.querySelectorAll('#efcModules article'));
        var discBtns = Array.prototype.slice.call(document.querySelectorAll('.efc-disc'));
        var search = document.getElementById('efcSearch');
        var curDisc = 'all', curQ = '';
        function filterModules() {
            modCards.forEach(function (c) {
                var d = c.getAttribute('data-discipline');
                var s = c.getAttribute('data-search');
                var okD = curDisc === 'all' || d === curDisc;
                var okQ = !curQ || s.indexOf(curQ) !== -1;
                c.style.display = (okD && okQ) ? '' : 'none';
            });
        }
        discBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                curDisc = b.getAttribute('data-disc');
                discBtns.forEach(function (x) {
                    var on = x === b;
                    x.className = 'efc-disc px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all ' +
                        (on ? 'bg-brand-orange text-brand-midnight' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/12');
                });
                filterModules();
            });
        });
        search.addEventListener('input', function () {
            curQ = search.value.trim().toLowerCase();
            filterModules();
        });
    })();
    </script>
</main>
