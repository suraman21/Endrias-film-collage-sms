<?php
/**
 * Endrias Film College — public site: footer + page-closing + JS.
 * Port of the footer in src/components/Layout.tsx. Uses data.php ($EFC_UI,
 * $EFC_CONTACT, $EFC_NAV, $EFC_PROGRAM_TIER_NAMES) for byte-exact copy.
 */
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$EFC_YEAR = date('Y');
$EFC_TIER_LINKS = ['tl1', 'tl2', 'tl3', 'tl4', 'tl5', 'tl6'];
?>
    <div class="h-3 bg-brand-midnight sprocket-strip border-t border-brand-amber/30"></div>
    <footer class="bg-brand-midnight text-white pt-16 pb-10 cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 pb-12 border-b border-white/10">
                <!-- Brand Identity Column -->
                <div class="lg:col-span-4 space-y-5">
                    <div class="flex items-center gap-4">
                        <img src="college/assets/brand/logo-seal.png" alt="<?= $EFC_SITENAME ?> Seal"
                             class="w-16 h-16 rounded-full shrink-0">
                        <div>
                            <div class="font-ethiopic text-brand-amber font-bold text-base"><?= $e($EFC_UI['brand_name_am']) ?></div>
                            <div class="font-extrabold text-xl tracking-tight text-white"><?= $e($EFC_UI['brand_plc']) ?></div>
                            <div class="text-xs font-mono text-white/60 mt-0.5"><?= $e($EFC_UI['est_line']) ?></div>
                        </div>
                    </div>
                    <p class="text-sm text-white/75 leading-relaxed"><?= $e(bp($EFC_UI['foot_motto'])) ?></p>
                    <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-brand-navy/60 border border-brand-amber/30 text-xs text-white/90">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-brand-orange shrink-0"></i>
                        <span><?= $e(bp($EFC_UI['foot_accredit'])) ?></span>
                    </div>
                </div>

                <!-- Conservatory Pages -->
                <div class="lg:col-span-2 space-y-3">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-brand-amber font-semibold flex items-center gap-1.5">
                        <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $e(bp($EFC_UI['foot_head_cons'])) ?>
                    </h3>
                    <ul class="space-y-2.5 text-sm text-white/80">
                        <?php foreach ($EFC_NAV as $item): ?>
                            <li><a href="<?= efc_url($item['page']) ?>" class="hover:text-brand-orange transition-colors"><?= $e(bp($item)) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Academic Tiers -->
                <div class="lg:col-span-3 space-y-3">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-brand-amber font-semibold flex items-center gap-1.5">
                        <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $e(bp($EFC_UI['foot_head_tiers'])) ?>
                    </h3>
                    <ul class="space-y-2.5 text-sm text-white/80">
                        <?php foreach ($EFC_TIER_LINKS as $tk): ?>
                            <li><a href="<?= efc_url('programs') ?>" class="hover:text-brand-orange transition-colors"><?= $e(bp($EFC_UI[$tk])) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Campus & Direct Channels -->
                <div class="lg:col-span-3 space-y-3">
                    <h3 class="text-xs font-mono uppercase tracking-wider text-brand-amber font-semibold flex items-center gap-1.5">
                        <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $e(bp($EFC_UI['foot_head_campus'])) ?>
                    </h3>
                    <div class="space-y-3 text-sm text-white/80">
                        <div class="flex items-start gap-2.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-brand-orange shrink-0 mt-1"></i>
                            <div>
                                <p class="font-medium text-white"><?= $e(bp($EFC_UI['foot_addr1'])) ?></p>
                                <p class="text-xs text-white/65 mt-0.5"><?= $e(bp($EFC_UI['foot_addr2'])) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="phone" class="w-4 h-4 text-brand-orange shrink-0"></i>
                            <div class="font-mono text-xs sm:text-sm">
                                <a href="tel:+251900003919" class="hover:text-brand-orange"><?= $e($EFC_UI['phone1']) ?></a>
                                <span class="text-white/50"> / </span>
                                <a href="tel:+251942563535" class="hover:text-brand-orange"><?= $e($EFC_UI['phone2']) ?></a>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i data-lucide="mail" class="w-4 h-4 text-brand-orange shrink-0"></i>
                            <a href="mailto:<?= $e($EFC_CONTACT['emailPrimary']) ?>" class="text-xs hover:text-brand-orange break-all"><?= $e($EFC_CONTACT['emailPrimary']) ?></a>
                        </div>
                        <div class="pt-2 flex flex-wrap gap-2">
                            <?php foreach ($EFC_CONTACT['social'] as $soc): ?>
                                <a href="<?= $e($soc['url']) ?>" target="_blank" rel="noreferrer"
                                   class="px-2.5 py-1.5 rounded-lg bg-white/10 hover:bg-brand-orange hover:text-brand-midnight text-xs font-semibold transition-colors inline-flex items-center gap-1">
                                    <?= $e($soc['label']) ?> <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-white/60">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-brand-ivory p-1 flex items-center justify-center shrink-0">
                        <img src="college/assets/brand/logo-mark.png" alt="Endrias Emblem" class="w-full h-full object-contain">
                    </div>
                    <span>
                        © <?= $EFC_YEAR ?> <?= $e($EFC_UI['brand_plc']) ?> (<?= $e($EFC_UI['brand_name_am']) ?>).
                        <?= $e(bp($EFC_UI['foot_rights'])) ?>
                    </span>
                </div>
                <div class="flex items-center gap-2 text-white/70">
                    <i data-lucide="award" class="w-4 h-4 text-brand-orange"></i>
                    <span><?= $e(bp($EFC_UI['foot_sister'])) ?></span>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Render Lucide icons.
        if (window.lucide) { window.lucide.createIcons(); }
        (function () {
            var btn = document.getElementById('efcMenuBtn');
            var menu = document.getElementById('efcMobileMenu');
            var header = document.getElementById('efcHeader');
            if (btn && menu) {
                btn.addEventListener('click', function () { menu.classList.toggle('hidden'); });
            }
            if (header) {
                var onScroll = function () {
                    var scrolled = window.scrollY > 24;
                    header.classList.toggle('bg-brand-navy', !scrolled);
                    header.classList.toggle('bg-brand-navyDark', scrolled);
                    header.classList.toggle('backdrop-blur-md', scrolled);
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                onScroll();
            }
        })();
    </script>
    <script>
        // Lightweight scroll-reveal (stands in for framer-motion). Kept in its
        // own <script> block so an error in the code above can never prevent
        // content from being revealed.
        (function () {
            // Content is visible by default; we only hide sections that sit BELOW
            // the initial viewport, then fade them in on scroll. If anything fails,
            // nothing stays hidden — the page is never left blank.
            var revealItems = Array.prototype.slice.call(document.querySelectorAll('.efc-reveal'));
            if (revealItems.length) {
                var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduceMotion) {
                    revealItems.forEach(function (el) { el.classList.add('efc-in'); });
                } else {
                    var vh = window.innerHeight || document.documentElement.clientHeight;
                    var toReveal = [];
                    try {
                        revealItems.forEach(function (el) {
                            if (el.getBoundingClientRect().top > vh) { el.classList.add('efc-hidden'); toReveal.push(el); }
                        });
                    } catch (e) { toReveal = []; }
                    if (toReveal.length) {
                        // Safety net scheduled before observer setup: even if the
                        // observer throws, no section is ever left hidden.
                        setTimeout(function () {
                            toReveal.forEach(function (el) { el.classList.remove('efc-hidden'); el.classList.add('efc-in'); });
                        }, 1500);
                        if ('IntersectionObserver' in window) {
                            var io = new IntersectionObserver(function (entries) {
                                entries.forEach(function (en) {
                                    if (en.isIntersecting) {
                                        en.target.classList.remove('efc-hidden');
                                        en.target.classList.add('efc-in');
                                        io.unobserve(en.target);
                                    }
                                });
                            }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
                            toReveal.forEach(function (el) { io.observe(el); });
                        }
                    }
                }
            }
        })();
    </script>
</body>
</html>
