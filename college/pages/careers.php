<?php
/**
 * Endrias Film College — Careers / Placement page (port of src/pages/CareersPage.tsx).
 * Server-renders the job board + two-branch portal. Light JS reproduces the React
 * useState behaviour: category filter, alumni/employer tab switch, dispatch-summary
 * generation, copy-to-clipboard, and mailto composition.
 * Relies on $EFC_JOB_LISTINGS / $EFC_CAREERS / $EFC_CONTACT (data.php).
 */
$C = $EFC_CAREERS;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
$telegramCareers = $EFC_CONTACT['telegramCareers'];
$emailPrimary = $EFC_CONTACT['emailPrimary'];
?>
<main>

    <!-- Hero -->
    <section class="bg-brand-midnight text-white py-14 sm:py-20 border-b border-brand-amber/30 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-8 space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($C['hero_eyebrow'])) ?>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($C['hero_h1'])) ?></h1>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed"><?= $esc(bp($C['hero_lead'])) ?></p>
            </div>
            <div class="lg:col-span-4">
                <div class="p-6 rounded-3xl bg-brand-navyDark border border-brand-orange/40 space-y-3">
                    <div class="flex items-center gap-2.5 text-brand-orange font-mono text-xs font-bold">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        <?= $esc('OFFICIAL ZERO-FEE PLEDGE') ?>
                    </div>
                    <p class="font-ethiopic text-sm text-white/90 leading-relaxed">«<?= $esc($C['pledge_am']) ?>»</p>
                    <div class="text-xs font-mono text-white/60 pt-1"><?= $esc('Placement Desks: ' . $C['depts'][0]) ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 1: PLACEMENT PIPELINE BOARD -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-brand-navy font-bold">
                        <i data-lucide="briefcase" class="w-4 h-4 text-brand-orange"></i> <?= $esc(bp($C['s1_eyebrow'])) ?>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($C['s1_h2'])) ?></h2>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <?php foreach ($C['cat_tabs'] as $i => $b): ?>
                        <button type="button" data-cat="<?= $esc($b['id']) ?>"
                                class="efc-cat px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all <?= $i === 0 ? 'bg-brand-navy text-white' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/15' ?>">
                            <?= $esc(bp($b)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div id="efcJobs" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($EFC_JOB_LISTINGS as $job):
                    $reqs = $am ? $job['requirementsAm'] : $job['requirementsEn'];
                ?>
                    <article data-category="<?= $esc($job['category']) ?>"
                             class="bg-white rounded-3xl p-6 sm:p-7 border border-brand-navy/12 shadow-card flex flex-col justify-between space-y-4 hover:border-brand-navy/30 transition-all">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="px-2.5 py-1 rounded-lg bg-brand-orange/15 text-brand-orangeHover font-mono text-xs font-bold">
                                    <?= $esc((string) $job['openings']) ?> <?= $esc(bp($C['positions'])) ?>
                                </span>
                                <span class="text-xs font-mono text-brand-muted"><?= $esc($tr($job['employmentTypeEn'], $job['employmentTypeAm'])) ?></span>
                            </div>
                            <h3 class="text-lg sm:text-xl font-extrabold text-brand-ink"><?= $esc($tr($job['titleEn'], $job['titleAm'])) ?></h3>
                            <div class="flex flex-wrap items-center gap-4 text-xs text-brand-muted">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-brand-navy"></i>
                                    <?= $esc($tr($job['districtEn'], $job['districtAm'])) ?>
                                </span>
                                <span class="inline-flex items-center gap-1 font-medium text-brand-navy">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-brand-orange"></i>
                                    <?= $esc($tr($job['experienceEn'], $job['experienceAm'])) ?>
                                </span>
                            </div>
                            <ul class="space-y-1.5 pt-1">
                                <?php foreach ($reqs as $r): ?>
                                    <li class="flex items-start gap-2 text-xs sm:text-sm text-brand-ink/85">
                                        <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                                        <span><?= $esc($r) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="pt-4 border-t border-brand-navy/10 flex items-center justify-between gap-3">
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md"><?= $esc(bp($C['zero_fee'])) ?></span>
                            <a href="<?= $esc($telegramCareers) ?>" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-brand-navy hover:text-brand-orangeHover">
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                <?= $esc(bp($C['apply_tel'])) ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION 2: DUAL INTERACTIVE PLACEMENT & EMPLOYER PORTAL -->
    <section class="py-14 sm:py-20 bg-brand-sand/70 border-t border-brand-navy/10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="text-center space-y-2">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-brand-ink"><?= $esc(bp($C['s2_h2'])) ?></h2>
                <p class="text-sm text-brand-muted"><?= $esc(bp($C['s2_lead'])) ?></p>
            </div>

            <div class="bg-white rounded-3xl border border-brand-navy/15 shadow-card overflow-hidden">
                <!-- Switch Tabs -->
                <div class="grid grid-cols-2 border-b border-brand-navy/10">
                    <button type="button" id="tabAlumni"
                            class="efc-ptab py-4 px-4 text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition-colors bg-brand-navy text-white">
                        <i data-lucide="users" class="w-4 h-4 text-brand-orange"></i>
                        <?= $esc(bp($C['tab_alumni'])) ?>
                    </button>
                    <button type="button" id="tabEmployer"
                            class="efc-ptab py-4 px-4 text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition-colors bg-brand-ivory text-brand-muted hover:text-brand-ink">
                        <i data-lucide="building-2" class="w-4 h-4 text-brand-orange"></i>
                        <?= $esc(bp($C['tab_employer'])) ?>
                    </button>
                </div>

                <div class="p-6 sm:p-8">
                    <!-- Alumni form -->
                    <form id="alumniForm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="aName" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['a_name'])) ?></label>
                                <input id="aName" required type="text" placeholder="<?= $esc(bp($C['a_name_ph'])) ?>"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                            <div>
                                <label for="aPhone" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['a_phone'])) ?></label>
                                <input id="aPhone" required type="tel" placeholder="09..."
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="aTrack" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['a_track'])) ?></label>
                                <select id="aTrack" class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-orange">
                                    <?php foreach ($C['track_options'] as $opt): ?>
                                        <option><?= $esc($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="aRole" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['a_role'])) ?></label>
                                <input id="aRole" required type="text" value="Video Editor &amp; Cinematographer"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                        </div>
                        <div>
                            <label for="aPortfolio" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['a_portfolio'])) ?></label>
                            <textarea id="aPortfolio" rows="3" placeholder="<?= $esc(bp($C['a_portfolio_ph'])) ?>"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange"></textarea>
                        </div>
                        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-sm transition-colors">
                            <?= $esc(bp($C['a_submit'])) ?>
                        </button>
                    </form>

                    <!-- Employer form -->
                    <form id="employerForm" class="space-y-4" style="display:none">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="eName" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['e_name'])) ?></label>
                                <input id="eName" required type="text" placeholder="<?= $esc(bp($C['e_name_ph'])) ?>"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                            <div>
                                <label for="ePhone" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['e_phone'])) ?></label>
                                <input id="ePhone" required type="tel" placeholder="09..."
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label for="eRole" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['e_role'])) ?></label>
                                <input id="eRole" required type="text" value="Cinematographer &amp; Video Editor"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                            <div>
                                <label for="eCount" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['e_count'])) ?></label>
                                <input id="eCount" required type="number" min="1" max="30" value="2"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                        </div>
                        <div>
                            <label for="eDistrict" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($C['e_district'])) ?></label>
                            <input id="eDistrict" required type="text" value="Bole, Addis Ababa"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                        </div>
                        <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-brand-navy hover:bg-brand-navyDark text-white font-extrabold text-sm transition-colors">
                            <?= $esc(bp($C['e_submit'])) ?>
                        </button>
                    </form>

                    <!-- Dispatch result panel -->
                    <div id="efcDispatch" class="mt-6 p-5 rounded-2xl bg-brand-ivory border border-brand-orange/50 space-y-3" style="display:none">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-mono font-bold text-brand-navy"><?= $esc(bp($C['r_header'])) ?></span>
                            <button type="button" id="efcCopy"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-brand-navy/20 text-xs font-bold text-brand-ink hover:bg-brand-sand">
                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                <span><?= $esc(bp($C['copy'])) ?></span>
                            </button>
                        </div>
                        <pre id="efcSummary" class="text-xs font-mono bg-white p-3.5 rounded-xl border border-brand-navy/10 whitespace-pre-wrap text-brand-ink"></pre>
                        <div class="flex flex-wrap gap-2.5 pt-1">
                            <a id="efcTelegram" href="<?= $esc($telegramCareers) ?>" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-navy text-white text-xs font-bold hover:bg-brand-navyDark">
                                <i data-lucide="send" class="w-3.5 h-3.5 text-brand-orange"></i>
                                <?= $esc(bp($C['r_tel'])) ?>
                            </a>
                            <a id="efcMail" href="#"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-orange text-brand-midnight text-xs font-bold">
                                <?= $esc(bp($C['r_mail'])) ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function () {
        var TEL = <?= json_encode($telegramCareers) ?>;
        var EMAIL = <?= json_encode($emailPrimary) ?>;
        var COPY = <?= json_encode(bp($C['copy'])) ?>;
        var COPIED = <?= json_encode(bp($C['copied'])) ?>;
        var ICON_COPY = '<i data-lucide="copy" class="w-3.5 h-3.5"></i>';
        var ICON_CHECK = '<i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>';

        // 1. Category filter
        var catBtns = Array.prototype.slice.call(document.querySelectorAll('.efc-cat'));
        var jobCards = Array.prototype.slice.call(document.querySelectorAll('#efcJobs article'));
        catBtns.forEach(function (b) {
            b.addEventListener('click', function () {
                var f = b.getAttribute('data-cat');
                catBtns.forEach(function (x) {
                    var on = x === b;
                    x.className = 'efc-cat px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all ' +
                        (on ? 'bg-brand-navy text-white' : 'bg-white text-brand-muted hover:text-brand-ink border border-brand-navy/15');
                });
                jobCards.forEach(function (c) {
                    c.style.display = (f === 'all' || c.getAttribute('data-category') === f) ? '' : 'none';
                });
            });
        });

        // 2. Portal tab switch
        var tabAlumni = document.getElementById('tabAlumni');
        var tabEmployer = document.getElementById('tabEmployer');
        var formAlumni = document.getElementById('alumniForm');
        var formEmployer = document.getElementById('employerForm');
        var dispatch = document.getElementById('efcDispatch');
        function selectTab(which) {
            var a = which === 'alumni';
            tabAlumni.className = 'efc-ptab py-4 px-4 text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition-colors ' + (a ? 'bg-brand-navy text-white' : 'bg-brand-ivory text-brand-muted hover:text-brand-ink');
            tabEmployer.className = 'efc-ptab py-4 px-4 text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition-colors ' + (a ? 'bg-brand-ivory text-brand-muted hover:text-brand-ink' : 'bg-brand-navy text-white');
            formAlumni.style.display = a ? '' : 'none';
            formEmployer.style.display = a ? 'none' : '';
            dispatch.style.display = 'none';
        }
        tabAlumni.addEventListener('click', function () { selectTab('alumni'); });
        tabEmployer.addEventListener('click', function () { selectTab('employer'); });

        // 3. Dispatch summary generation
        var pre = document.getElementById('efcSummary');
        var mail = document.getElementById('efcMail');
        function val(id) { var el = document.getElementById(id); return el ? (el.value || '').trim() : ''; }
        function alumniSummary() {
            var port = val('aPortfolio') || 'Available on request';
            return '[Endrias Alumni Job Placement]\nName: ' + val('aName') +
                '\nPhone: ' + val('aPhone') + '\nGraduated Track: ' + val('aTrack') +
                '\nTarget Role: ' + val('aRole') + '\nPortfolio/Notes: ' + port;
        }
        function employerSummary() {
            return '[Zero-Fee Crew Request \u2014 Employer]\nOrganization: ' + val('eName') +
                '\nContact Phone: ' + val('ePhone') + '\nRole Needed: ' + val('eRole') + ' (x' + val('eCount') + ')' +
                '\nLocation: ' + val('eDistrict');
        }
        function showDispatch(text) {
            pre.textContent = text;
            mail.href = 'mailto:' + EMAIL + '?subject=' + encodeURIComponent('Endrias Film College Career Placement') +
                '&body=' + encodeURIComponent(text);
            dispatch.style.display = '';
            dispatch.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        document.getElementById('alumniForm').addEventListener('submit', function (e) { e.preventDefault(); showDispatch(alumniSummary()); });
        document.getElementById('employerForm').addEventListener('submit', function (e) { e.preventDefault(); showDispatch(employerSummary()); });

        // 4. Copy to clipboard
        var copyBtn = document.getElementById('efcCopy');
        copyBtn.addEventListener('click', function () {
            var text = pre.textContent;
            function done() {
                copyBtn.innerHTML = ICON_CHECK + '<span>' + COPIED + '</span>';
                if (window.lucide) { window.lucide.createIcons(); }
                setTimeout(function () {
                    copyBtn.innerHTML = ICON_COPY + '<span>' + COPY + '</span>';
                    if (window.lucide) { window.lucide.createIcons(); }
                }, 2200);
            }
            function fallback() {
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, function () { fallback(); done(); });
            } else { fallback(); done(); }
        });
    })();
    </script>
</main>
