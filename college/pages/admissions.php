<?php
/**
 * Endrias Film College — Admissions page (port of src/pages/AdmissionsPage.tsx).
 * Server-renders the program/shift selects + campus directory. Light JS reproduces
 * the React behaviour: ?program/?shift deep-links, pre-registration slip generation
 * (EFC-2019-xxxx), copy, print, and mailto composition.
 * Relies on $EFC_PROGRAM_TIERS / $EFC_DAILY_SHIFTS / $EFC_ADMISSIONS / $EFC_CONTACT.
 */
$A = $EFC_ADMISSIONS;
$esc = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$am = ($EFC_LANG === 'am');
$tr = static function (string $en, string $am_) use ($am): string { return $am ? $am_ : $en; };
$CT = $EFC_CONTACT;
$emailPrimary = $CT['emailPrimary'];
$telDean = $CT['telegramDean'];
$telChannel = $CT['telegram'];
$adminPhones = implode(' · ', $CT['phonesAdmin']);
$adminPhonesHref = function (array $phones): array {
    return array_map(static fn($p) => preg_replace('/\s+/', '', $p), $phones);
};
?>
<main>

    <!-- Page Hero -->
    <section class="bg-brand-navyDark text-white py-14 sm:py-20 border-b border-brand-amber/30 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-orange/20 text-brand-orange text-xs font-mono font-semibold">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $esc(bp($A['hero_eyebrow'])) ?>
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight"><?= $esc(bp($A['hero_h1'])) ?></h1>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed"><?= $esc(bp($A['hero_lead'])) ?></p>
            </div>
        </div>
    </section>

    <!-- SECTION 1: PRE-REGISTRATION FORM + CAMPUS DIRECTORY -->
    <section class="py-14 sm:py-20 ivory-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-12 gap-10">

            <!-- Left: Interactive Pre-Registration Builder -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-white rounded-3xl border border-brand-navy/15 shadow-card p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between gap-3 pb-4 border-b border-brand-navy/10">
                        <div>
                            <div class="text-xs font-mono uppercase text-brand-orangeHover font-bold"><?= $esc(bp($A['form_step'])) ?></div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-brand-ink mt-0.5"><?= $esc(bp($A['form_h2'])) ?></h2>
                        </div>
                        <img src="college/assets/brand/logo-seal.png" alt="Endrias Seal" class="w-12 h-12 rounded-full shrink-0">
                    </div>

                    <form id="efcSlipForm" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="fullName" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_name'])) ?></label>
                                <input id="fullName" required type="text" placeholder="<?= $esc(bp($A['f_name_ph'])) ?>"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                            <div>
                                <label for="phone" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_phone'])) ?></label>
                                <input id="phone" required type="tel" placeholder="09... / +251..."
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_email'])) ?></label>
                                <input id="email" type="email" placeholder="you@example.com"
                                       class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange">
                            </div>
                            <div>
                                <label for="eduBg" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_edu'])) ?></label>
                                <select id="eduBg" class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-orange">
                                    <?php foreach ($A['edu_options'] as $opt): ?>
                                        <option><?= $esc($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="programSel" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_program'])) ?></label>
                            <select id="programSel" class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm bg-white font-semibold focus:outline-none focus:ring-2 focus:ring-brand-orange">
                                <?php foreach ($EFC_PROGRAM_TIERS as $p): ?>
                                    <option value="<?= $esc($p['id']) ?>" data-title="<?= $esc($p['titleEn']) ?>" data-code="<?= $esc($p['code']) ?>">
                                        [<?= $esc($p['code']) ?>] <?= $esc($tr($p['titleEn'], $p['titleAm'])) ?> — <?= $esc($tr($p['durationEn'], $p['durationAm'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="shiftSel" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_shift'])) ?></label>
                            <select id="shiftSel" class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-orange">
                                <?php foreach ($EFC_DAILY_SHIFTS as $s): ?>
                                    <option value="<?= $esc($s['id']) ?>" data-label="<?= $esc($s['labelEn']) ?>" data-eth="<?= $esc($s['ethiopianTime']) ?>" data-intl="<?= $esc($s['intlTime']) ?>">
                                        <?= $esc($tr($s['labelEn'], $s['labelAm'])) ?> · <?= $esc($s['ethiopianTime']) ?> (<?= $esc($s['intlTime']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="notes" class="block text-xs font-bold text-brand-ink mb-1.5"><?= $esc(bp($A['f_notes'])) ?></label>
                            <textarea id="notes" rows="3" placeholder="<?= $esc(bp($A['f_notes_ph'])) ?>"
                                      class="w-full px-3.5 py-2.5 rounded-xl border border-brand-navy/20 text-sm focus:outline-none focus:ring-2 focus:ring-brand-orange"></textarea>
                        </div>
                        <button type="submit" class="w-full py-4 px-6 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-extrabold text-base shadow-glow transition-all">
                            <?= $esc(bp($A['f_submit'])) ?>
                        </button>
                    </form>

                    <!-- Generated Pre-Registration Card -->
                    <div id="efcSlip" class="p-6 rounded-2xl bg-brand-midnight text-white border-2 border-brand-orange space-y-4" style="display:none">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/15 pb-3">
                            <div>
                                <div class="text-[11px] font-mono text-brand-orange uppercase"><?= $esc('PRE-REGISTRATION REFERENCE') ?></div>
                                <div id="efcSlipCode" class="text-lg font-mono font-extrabold text-white"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="efcSlipCopy" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-bold">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                    <span><?= $esc(bp($A['slip_copy'])) ?></span>
                                </button>
                                <button type="button" id="efcSlipPrint" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-bold">
                                    <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                    <span><?= $esc(bp($A['slip_print'])) ?></span>
                                </button>
                            </div>
                        </div>
                        <pre id="efcSlipText" class="text-xs font-mono text-white/90 whitespace-pre-wrap leading-relaxed"></pre>
                        <div class="pt-2 flex flex-wrap gap-3">
                            <a id="efcSlipTel" href="<?= $esc($telDean) ?>" target="_blank" rel="noreferrer"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-orange text-brand-midnight font-extrabold text-xs sm:text-sm">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                <?= $esc(bp($A['slip_tel'])) ?>
                            </a>
                            <a id="efcSlipMail" href="#"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs sm:text-sm">
                                <i data-lucide="mail" class="w-4 h-4 text-brand-orange"></i>
                                <?= $esc(bp($A['slip_mail'])) ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: 4 Kilo Campus Locations, Direct Lines & Academic Policies -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Campus Directory Card -->
                <div class="bg-brand-navy text-white rounded-3xl p-6 sm:p-8 shadow-cinema space-y-5">
                    <div class="space-y-1">
                        <div class="text-xs font-mono uppercase text-brand-amber font-bold"><?= $esc(bp($A['dir_eyebrow'])) ?></div>
                        <h3 class="text-xl sm:text-2xl font-extrabold"><?= $esc(bp($A['dir_h3'])) ?></h3>
                    </div>
                    <div class="space-y-4 text-sm">
                        <div class="p-4 rounded-2xl bg-white/10 border border-white/10 space-y-1">
                            <div class="flex items-center gap-2 font-bold text-brand-amber">
                                <i data-lucide="map-pin" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                <?= $esc(bp($A['dir_main'])) ?>
                            </div>
                            <p class="text-white/90 text-xs sm:text-sm leading-relaxed"><?= $esc($tr($CT['addressMainEn'], $CT['addressMainAm'])) ?></p>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/10 border border-white/10 space-y-1">
                            <div class="flex items-center gap-2 font-bold text-brand-amber">
                                <i data-lucide="map-pin" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                <?= $esc(bp($A['dir_trinity'])) ?>
                            </div>
                            <p class="text-white/90 text-xs sm:text-sm leading-relaxed"><?= $esc($tr($CT['addressTrinityEn'], $CT['addressTrinityAm'])) ?></p>
                        </div>
                        <div class="space-y-2.5 pt-1">
                            <div class="text-xs font-mono uppercase text-brand-amber"><?= $esc(bp($A['dir_hl'])) ?></div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <?php foreach ($CT['phonesPrimary'] as $i => $ph): ?>
                                    <a href="tel:<?= $esc($adminPhonesHref($CT['phonesPrimary'])[$i]) ?>"
                                       class="p-3 rounded-xl bg-brand-midnight/70 hover:bg-brand-orange hover:text-brand-midnight border border-white/15 font-mono text-xs sm:text-sm font-bold flex items-center gap-2 transition-colors">
                                        <i data-lucide="phone" class="w-4 h-4 text-brand-orange shrink-0"></i>
                                        <?= $esc($ph) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-xs text-white/70 font-mono"><?= $esc('HR & Admin: ' . $adminPhones) ?></div>
                        </div>
                        <div class="pt-2 border-t border-white/15 space-y-1.5 text-xs">
                            <div class="text-brand-amber font-mono uppercase"><?= $esc(bp($A['dir_email'])) ?></div>
                            <div><?= $esc($CT['emailPrimary']) ?></div>
                            <div><?= $esc($CT['emailAcademic']) ?></div>
                            <div class="pt-1 flex flex-wrap gap-2">
                                <a href="<?= $esc($telChannel) ?>" target="_blank" rel="noreferrer"
                                   class="px-3 py-1.5 rounded-lg bg-brand-orange text-brand-midnight font-bold"><?= $esc('@endifilm Channel') ?></a>
                                <a href="<?= $esc($telDean) ?>" target="_blank" rel="noreferrer"
                                   class="px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white font-semibold"><?= $esc('Dean Inbox (@al0254)') ?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Registrar Academic Guidelines Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-brand-navy/12 shadow-card space-y-4">
                    <div class="flex items-center gap-2 text-brand-navy font-extrabold text-base">
                        <i data-lucide="calendar-check" class="w-5 h-5 text-brand-orange"></i>
                        <?= $esc(bp($A['guide_h3'])) ?>
                    </div>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-brand-muted">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                            <span><strong class="text-brand-ink"><?= $esc(bp($A['g1_label'])) ?></strong> <?= $esc(bp($A['g1'])) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                            <span><strong class="text-brand-ink"><?= $esc(bp($A['g2_label'])) ?></strong> <?= $esc(bp($A['g2'])) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-brand-navy shrink-0 mt-0.5"></i>
                            <span><strong class="text-brand-ink"><?= $esc(bp($A['g3_label'])) ?></strong> <?= $esc(bp($A['g3'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <script>
    (function () {
        var EMAIL = <?= json_encode($emailPrimary) ?>;
        var SLIP_CAMPUS = <?= json_encode($A['slip_campus']) ?>;
        var COPY = <?= json_encode(bp($A['slip_copy'])) ?>;
        var COPIED = <?= json_encode(bp($A['slip_copied'])) ?>;
        var ICON_COPY = '<i data-lucide="copy" class="w-3.5 h-3.5"></i>';
        var ICON_CHECK = '<i data-lucide="check" class="w-3.5 h-3.5 text-brand-orange"></i>';

        var programSel = document.getElementById('programSel');
        var shiftSel = document.getElementById('shiftSel');
        var slip = document.getElementById('efcSlip');
        var slipCodeEl = document.getElementById('efcSlipCode');
        var slipTextEl = document.getElementById('efcSlipText');
        var mailLink = document.getElementById('efcSlipMail');

        function val(id) { var el = document.getElementById(id); return el ? (el.value || '').trim() : ''; }

        // Deep-link: ?program= / ?shift= (mirrors the React useSearchParams behaviour)
        (function applyDeepLink() {
            var qs = new URLSearchParams(window.location.search);
            var p = qs.get('program'), s = qs.get('shift');
            function pick(sel, id) {
                if (!id) return;
                for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value === id) { sel.selectedIndex = i; return; } }
            }
            pick(programSel, p);
            pick(shiftSel, s);
        })();

        function buildSlip(code) {
            var p = programSel.options[programSel.selectedIndex];
            var s = shiftSel.options[shiftSel.selectedIndex];
            var title = p.getAttribute('data-title') || p.textContent;
            var codev = p.getAttribute('data-code') || '';
            return 'ENDRIAS FILM COLLEGE PLC \u2014 PRE-REGISTRATION SLIP (' + code + ')\n' +
                'Applicant: ' + val('fullName') + '\n' +
                'Phone: ' + val('phone') + '\n' +
                'Email: ' + (val('email') || 'N/A') + '\n' +
                'Program: ' + title + ' (' + codev + ')\n' +
                'Preferred Shift: ' + s.getAttribute('data-label') + ' \u2014 ' +
                s.getAttribute('data-eth') + ' (' + s.getAttribute('data-intl') + ')\n' +
                'Background: ' + val('eduBg') + '\n' +
                'Notes: ' + (val('notes') || 'None') + '\n' + SLIP_CAMPUS;
        }

        document.getElementById('efcSlipForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var code = 'EFC-2019-' + Math.floor(1000 + Math.random() * 9000);
            slipCodeEl.textContent = code;
            slipTextEl.textContent = buildSlip(code);
            mailLink.href = 'mailto:' + EMAIL + '?subject=' + encodeURIComponent('Admission Inquiry ' + code) +
                '&body=' + encodeURIComponent(slipTextEl.textContent);
            slip.style.display = '';
            slip.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        // Copy slip
        var copyBtn = document.getElementById('efcSlipCopy');
        copyBtn.addEventListener('click', function () {
            var text = slipTextEl.textContent;
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
                try { document.execCommand('copy'); } catch (err) {}
                document.body.removeChild(ta);
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, function () { fallback(); done(); });
            } else { fallback(); done(); }
        });

        document.getElementById('efcSlipPrint').addEventListener('click', function () { window.print(); });
    })();
    </script>
</main>
