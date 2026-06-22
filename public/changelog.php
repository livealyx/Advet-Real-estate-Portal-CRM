<?php
// FILE: public/changelog.php
session_start();
$solidNav  = true;
$pageTitle = 'Changelog';
$pageDesc  = 'Advet Buildwell platform updates and release notes.';
require_once '../includes/header.php';

require_once '../includes/changelog-data.php';

// Pre-compute stats
$totalRefinements = 0;
$totalNew = 0;
foreach ($entries as $r) {
    $totalRefinements += count($r['changes']);
    foreach ($r['changes'] as $c) if ($c['type'] === 'new') $totalNew++;
}
?>

<style>
    @keyframes cl-in { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    .cl-entry { opacity: 0; transition: opacity .6s ease, transform .6s ease; transform: translateY(20px); }
    .cl-entry.visible { opacity: 1; transform: translateY(0); }

    .cl-badge-new    { background: rgba(52,211,153,.1); color:#34d399; border:1px solid rgba(52,211,153,.25); }
    .cl-badge-fix    { background: rgba(248,113,113,.1); color:#f87171; border:1px solid rgba(248,113,113,.25); }
    .cl-badge-update { background: rgba(251,191,36,.1);  color:#fbbf24; border:1px solid rgba(251,191,36,.25); }

    .cl-nav-link { color: var(--color-muted, #6b6b5a); transition: all .25s; }
    .cl-nav-link:hover, .cl-nav-link.active { color: var(--color-foreground, #1a1a14); }
    .cl-nav-link.active .cl-nav-dot { background: var(--color-accent, #899178); transform: scale(1.5); }
    .cl-nav-dot { transition: all .25s; }
    
    /* Redesigned Active State Overrides */
    .cl-nav-link-redesigned.active .cl-nav-bg { opacity: 1; }
    .cl-nav-link-redesigned.active .cl-nav-bar { height: 100%; opacity: 1; border-radius: 0; }
    .cl-nav-link-redesigned.active .cl-nav-label { color: var(--color-accent, #899178); }

    .cl-section { scroll-margin-top: 110px; }
    .cl-sidebar { position: sticky; top: 110px; max-height: calc(100vh - 130px); overflow-y: auto; scrollbar-width: none; }
    .cl-sidebar::-webkit-scrollbar { display: none; }

    #cl-progress-bar { position:fixed; top:0; left:0; height:2px; background: var(--color-accent, #899178); z-index:9999; width:0; transition: width .08s linear; pointer-events:none; }
</style>

<div id="cl-progress-bar"></div>

<main class="flex-grow bg-[#FDFCF9] dark:bg-[#080808] transition-colors duration-700">

    <!-- ═══ HERO ═══ -->
    <section class="relative overflow-hidden pt-44 pb-28 border-b border-sand/25">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 right-0 w-[800px] h-[800px] rounded-full bg-accent/5 blur-[180px] -translate-y-1/3 translate-x-1/4"></div>
        </div>
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-20 relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-14">
                <div>
                    <p class="flex items-center gap-3 text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-8">
                        <span class="block w-8 h-px bg-accent"></span>Platform Engineering — Advet Studio
                    </p>
                    <h1 class="text-7xl sm:text-8xl xl:text-[7rem] font-serif font-light leading-[0.88] mb-10 text-foreground">
                        Release<br><em class="italic text-muted/40">Manifest</em>
                    </h1>
                    <p class="text-base font-light leading-relaxed text-muted max-w-md">
                        A transparent, chronological record of every architectural decision, feature expansion, and stability refinement deployed to the Advet Buildwell ecosystem.
                    </p>
                </div>

                <!-- Stats Row -->
                <div class="flex items-end gap-10 sm:gap-16 shrink-0">
                    <div>
                        <p class="text-5xl font-serif font-light text-foreground"><?= count($entries) ?></p>
                        <p class="text-[9px] font-bold uppercase tracking-[0.35em] text-muted/50 mt-1.5">Releases</p>
                    </div>
                    <div class="w-px h-12 bg-sand/30"></div>
                    <div>
                        <p class="text-5xl font-serif font-light text-foreground"><?= $totalRefinements ?></p>
                        <p class="text-[9px] font-bold uppercase tracking-[0.35em] text-muted/50 mt-1.5">Refinements</p>
                    </div>
                    <div class="w-px h-12 bg-sand/30 hidden sm:block"></div>
                    <div class="hidden sm:block">
                        <p class="text-5xl font-serif font-light text-foreground"><?= $totalNew ?></p>
                        <p class="text-[9px] font-bold uppercase tracking-[0.35em] text-muted/50 mt-1.5">Features</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══ BODY ═══ -->
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-20 py-20">
        <div class="flex gap-14 xl:gap-20">

            <!-- ── SIDEBAR ── -->
            <aside class="hidden lg:block w-56 xl:w-64 shrink-0">
                <div class="cl-sidebar space-y-4">



                    <!-- Version nav card -->
                    <div class="rounded-[2.5rem] border border-sand/40 dark:border-white/10 bg-white/80 dark:bg-[#121212]/80 shadow-[0_8px_30px_rgb(0,0,0,0.04)] backdrop-blur-xl overflow-hidden relative group">
                        <!-- Abstract glow -->
                        <div class="absolute -top-12 -right-12 w-40 h-40 bg-accent/10 rounded-full blur-[40px] group-hover:bg-accent/20 transition-all duration-700 pointer-events-none"></div>
                        
                        <div class="px-8 pt-8 pb-5 border-b border-sand/30 dark:border-white/5 relative z-10 bg-white/50 dark:bg-black/20">
                            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-foreground mb-1.5">Release History</p>
                            <p class="text-[9px] text-muted tracking-widest uppercase">Select a version</p>
                        </div>
                        
                        <nav id="cl-nav" class="pb-4 pt-2 max-h-[50vh] overflow-y-auto relative z-10 custom-scrollbar relative">
                            <?php foreach($entries as $i => $release):
                                $vNum = explode(' — ', $release['version'])[0];
                                $vName = explode(' — ', $release['version'])[1] ?? '';
                            ?>
                            <a href="#cl-<?= $i ?>" class="cl-nav-link cl-nav-link-redesigned relative block px-8 py-4 transition-all duration-300 hover:bg-black/5 dark:hover:bg-white/5 group/item" data-target="cl-<?= $i ?>">
                                <!-- Active indicator bg glow -->
                                <div class="cl-nav-bg absolute inset-0 bg-accent/10 opacity-0 transition-opacity duration-300 pointer-events-none"></div>
                                <!-- Active indicator line -->
                                <div class="cl-nav-bar absolute left-0 top-1/2 -translate-y-1/2 w-1 h-0 bg-accent opacity-0 group-hover/item:h-1/2 group-hover/item:opacity-50 transition-all duration-300 ease-out"></div>
                                
                                <div class="flex items-start justify-between relative z-10">
                                    <div class="min-w-0 pr-4 flex-1">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <p class="cl-nav-label text-[12px] font-bold tracking-[0.2em] uppercase transition-colors duration-300 <?= $i === 0 ? 'text-accent' : 'text-foreground' ?>"><?= e($vNum) ?></p>
                                            <?php if($i === 0): ?>
                                                <span class="px-2 py-0.5 rounded flex items-center justify-center bg-accent/10 border border-accent/20 text-[8px] font-bold text-accent uppercase tracking-widest gap-1.5 shadow-sm">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span> Latest
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[10px] leading-relaxed text-muted/70 line-clamp-2 transition-colors duration-300 group-hover/item:text-muted"><?= e($vName) ?></p>
                                    </div>
                                    <span class="text-[9px] tabular-nums font-medium text-muted/40 uppercase tracking-widest pt-0.5 whitespace-nowrap transition-all duration-300"><?= date('M y', strtotime($release['date'])) ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                        
                        <!-- Bottom Fade -->
                        <div class="absolute bottom-0 left-0 right-0 h-10 bg-gradient-to-t from-white/90 dark:from-[#121212]/90 to-transparent pointer-events-none z-20"></div>
                    </div>

                    <!-- Legend card -->
                    <div class="rounded-2xl border border-sand/30 dark:border-white/5 bg-white/60 dark:bg-white/[0.02] p-5 backdrop-blur-sm">
                        <p class="text-[8px] font-bold uppercase tracking-[0.5em] text-muted/40 mb-4">Legend</p>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-400 shrink-0"></div>
                                <span class="text-[9px] font-semibold text-muted/60">New — Feature added</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></div>
                                <span class="text-[9px] font-semibold text-muted/60">Update — Improvement</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-rose-400 shrink-0"></div>
                                <span class="text-[9px] font-semibold text-muted/60">Fix — Bug resolved</span>
                            </div>
                        </div>
                    </div>

                </div>
            </aside>

            <!-- ── FEED ── -->
            <div class="flex-1 min-w-0">
                <div class="space-y-12">
                    <?php foreach($entries as $i => $release):
                        $vNum   = explode(' — ', $release['version'])[0];
                        $vName  = explode(' — ', $release['version'])[1] ?? '';
                        $vDigit = preg_replace('/[^0-9.]/', '', $vNum);
                        $grouped = [];
                        foreach ($release['changes'] as $c) $grouped[$c['type']][] = $c['note'];
                        $isLatest = ($i === 0);
                    ?>
                    <article id="cl-<?= $i ?>" class="cl-section cl-entry group relative rounded-3xl overflow-hidden border transition-all duration-500
                        <?= $isLatest
                            ? 'border-accent/30 bg-white dark:bg-white/[0.02] shadow-xl shadow-accent/5'
                            : 'border-sand/30 dark:border-white/5 bg-white/50 dark:bg-white/[0.01] hover:border-sand/50 dark:hover:border-white/10 hover:shadow-lg' ?>">

                        <!-- Watermark version number -->
                        <div class="pointer-events-none absolute right-6 top-3 font-serif italic leading-none text-[7rem] opacity-[0.03] select-none">
                            <?= e($vDigit) ?>
                        </div>

                        <!-- Top accent bar for latest -->
                        <?php if ($isLatest): ?>
                        <div class="h-0.5 w-full bg-gradient-to-r from-accent/0 via-accent to-accent/0"></div>
                        <?php endif; ?>

                        <!-- Card Inner -->
                        <div class="relative z-10 p-8 lg:p-12">
                            <!-- Meta row -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
                                <div class="flex items-center gap-4 flex-wrap">
                                    <!-- Release index -->
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-foreground/5 text-[10px] font-bold text-muted/60 tabular-nums">
                                        <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                                    </span>
                                    <div class="w-px h-5 bg-sand/30"></div>
                                    <span class="text-[9px] font-bold uppercase tracking-[0.45em] text-accent"><?= e($release['date']) ?></span>
                                    <?php if ($isLatest): ?>
                                        <span class="text-[8px] font-bold uppercase tracking-widest bg-accent text-white px-2.5 py-1 rounded-full">Latest</span>
                                    <?php endif; ?>
                                </div>
                                <!-- Change summary badges -->
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach(['new','update','fix'] as $t): if(empty($grouped[$t])) continue; ?>
                                        <span class="cl-badge-<?= $t ?> text-[8px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full">
                                            <?= count($grouped[$t]) ?> <?= $t ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Version title block -->
                            <div class="mb-10 pb-8 border-b border-sand/20">
                                <h2 class="text-3xl sm:text-5xl font-serif font-light leading-none mb-3"><?= e($vNum) ?></h2>
                                <p class="text-base sm:text-lg font-serif italic text-muted/60"><?= e($vName) ?></p>
                            </div>

                            <!-- Changes: grouped sections -->
                            <div class="grid grid-cols-1 md:grid-cols-<?= count($grouped) > 1 ? '2' : '1' ?> gap-x-12 gap-y-10">
                                <?php foreach(['new','update','fix'] as $type): if(empty($grouped[$type])) continue;
                                    [$dotCls, $badgeCls, $lineCls] = match($type) {
                                        'new'    => ['bg-emerald-400', 'cl-badge-new',    'bg-emerald-400/20'],
                                        'fix'    => ['bg-rose-400',    'cl-badge-fix',    'bg-rose-400/20'],
                                        default  => ['bg-amber-400',   'cl-badge-update', 'bg-amber-400/20'],
                                    };
                                ?>
                                <div>
                                    <!-- Section label -->
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="w-1 h-6 rounded-full <?= $lineCls ?>"></div>
                                        <span class="<?= $badgeCls ?> text-[8px] font-bold uppercase tracking-[0.35em] px-2.5 py-1 rounded-full"><?= $type ?></span>
                                        <span class="text-[9px] text-muted/40 font-medium"><?= count($grouped[$type]) ?> change<?= count($grouped[$type]) > 1 ? 's' : '' ?></span>
                                    </div>

                                    <!-- Note list -->
                                    <ol class="space-y-5">
                                        <?php foreach($grouped[$type] as $ni => $note): ?>
                                        <li class="group/note flex gap-4 items-start">
                                            <span class="text-[9px] tabular-nums text-muted/25 font-bold mt-0.5 w-4 shrink-0 group-hover/note:text-accent/60 transition-colors"><?= str_pad($ni + 1, 2, '0', STR_PAD_LEFT) ?></span>
                                            <p class="text-sm font-light leading-relaxed text-muted dark:text-muted/75 group-hover/note:text-foreground transition-colors duration-300"><?= e($note) ?></p>
                                        </li>
                                        <?php endforeach; ?>
                                    </ol>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Footer CTA -->
                <div class="mt-28 pt-16 border-t border-sand/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-8">
                    <p class="text-xl font-serif italic text-muted/50 max-w-xs leading-relaxed">
                        The journey of a thousand commits begins with a single deploy.
                    </p>
                    <a href="<?= BASE ?>contact" class="group inline-flex items-center gap-4 px-8 py-4 border border-sand/40 rounded-full hover:border-accent/60 hover:bg-accent/5 transition-all duration-300 shrink-0">
                        <span class="text-[10px] font-bold uppercase tracking-[0.4em] group-hover:text-accent transition-colors">Request a Feature</span>
                        <svg class="w-4 h-4 text-muted group-hover:text-accent group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
(function () {
    // Reading progress bar
    const bar = document.getElementById('cl-progress-bar');
    window.addEventListener('scroll', () => {
        const pct = Math.min(100, (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100);
        bar.style.width = pct + '%';
    }, { passive: true });

    // Scroll-reveal
    const reveal = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
    }, { threshold: 0.06 });
    document.querySelectorAll('.cl-entry').forEach(el => reveal.observe(el));

    // Scroll-spy nav
    const navLinks = [...document.querySelectorAll('.cl-nav-link')];
    const sections = [...document.querySelectorAll('.cl-section')];

    function setActive(lnk) {
        navLinks.forEach(l => {
            l.classList.remove('active');
            const bar = l.querySelector('.cl-nav-bar');
            const idx = l.querySelector('.cl-nav-index');
            const lbl = l.querySelector('.cl-nav-label');
        if (bar) { bar.style.height = ''; bar.style.opacity = ''; }
        if (idx) { idx.style.color = ''; }
        if (lbl) { lbl.style.color = ''; }
    });
    if (!lnk) return;
    lnk.classList.add('active');
    const bar = lnk.querySelector('.cl-nav-bar');
    const idx = lnk.querySelector('.cl-nav-index');
    const lbl = lnk.querySelector('.cl-nav-label');
    if (bar) { bar.style.height = '100%'; bar.style.opacity = '1'; bar.style.borderRadius = '0'; }
    if (idx) { idx.style.color = 'var(--color-accent,#899178)'; }
    if (lbl) { lbl.style.color = 'var(--color-accent,#899178)'; }
    lnk.scrollIntoView({ block: 'nearest' });
    }

    const spy = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                const lnk = document.querySelector(`.cl-nav-link[data-target="${e.target.id}"]`);
                setActive(lnk);
            }
        });
    }, { rootMargin: '-15% 0px -75% 0px' });
    sections.forEach(s => spy.observe(s));

    // Smooth scroll on nav click
    navLinks.forEach(l => l.addEventListener('click', e => {
        e.preventDefault();
        setActive(l);
        document.getElementById(l.dataset.target)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    // Activate first on load
    if (navLinks[0]) setActive(navLinks[0]);
})();
</script>

<?php require_once '../includes/footer.php'; ?>
