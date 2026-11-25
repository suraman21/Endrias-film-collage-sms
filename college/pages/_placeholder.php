<?php
/**
 * Endrias Film College — temporary section placeholder.
 * Shown for pages not yet ported from the React site (Programs, About, Faculty,
 * Showcase/Endi Award, Careers, Admissions). Replaced by the real page file
 * as each section is ported. The front controller falls back to this file.
 */
$e = static function (?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
$navLabel = null;
foreach ($EFC_NAV as $item) {
    if ($item['page'] === $EFC_PAGE) { $navLabel = $item; break; }
}
$title = $navLabel ? bp($navLabel) : ucfirst((string) $EFC_PAGE);
?>
<main>
    <section class="bg-brand-midnight text-white cinema-grid-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">
            <div class="max-w-2xl space-y-5">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-orange/15 border border-brand-amber/40 text-brand-amber font-mono text-xs uppercase tracking-widest">
                    <span class="font-ethiopic font-bold select-none tracking-widest">፠</span> <?= $e($title) ?>
                </div>
                <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight"><?= $e($title) ?></h1>
                <div class="rounded-2xl bg-white/5 border border-white/10 p-6 text-sm text-white/75 leading-relaxed space-y-2">
                    <p>This section is part of the ongoing port of the Endrias Film College website (from React) to PHP.</p>
                    <p>Its full content — program modules, faculty, the Endi Award showcase, careers, and the admissions flow — is being carried over and will appear here shortly.</p>
                </div>
                <a href="<?= efc_url('') ?>"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-orange hover:bg-brand-orangeHover text-brand-midnight font-bold text-sm transition-colors">
                    Home <i data-lucide="arrow-left" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </section>
</main>
