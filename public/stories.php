<?php
// FILE: public/stories.php
session_start();
require_once '../config/db.php';

$pdo     = getPDO();
$stories = $pdo->query(
    "SELECT * FROM stories WHERE published_at IS NOT NULL AND published_at <= NOW() ORDER BY published_at DESC"
)->fetchAll();

$solidNav  = true;
$pageTitle = 'Stories & Journal';
$pageDesc  = 'Architectural insights, design notes, and field stories from the Advet Buildwell studio.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-24">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 reveal">
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Field Notes</p>
            <h1 class="text-5xl font-serif font-light mb-12 border-b border-sand/30 pb-8">
                Journal & <span class="italic text-muted">Stories</span>
            </h1>

            <?php if (empty($stories)): ?>
            <div class="text-center py-24">
                <h3 class="text-3xl font-serif font-light italic mb-4">No stories published yet.</h3>
                <p class="text-muted font-light">Check back soon for insights from the studio.</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <?php foreach ($stories as $i => $story): ?>
                    <a href="<?= BASE ?>story/<?= e($story['slug']) ?>" class="group cursor-pointer block">
                        <div class="aspect-square image-soft-clip overflow-hidden mb-6">
                            <img src="<?= imgUrl($story['cover_image']) ?>"
                                 alt="<?= e($story['title']) ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        </div>
                        <p class="text-xs font-medium uppercase tracking-widest text-accent mb-3">Design Notes</p>
                        <h3 class="text-2xl font-serif mb-3 group-hover:text-accent transition-colors"><?= e($story['title']) ?></h3>
                        <p class="text-sm text-muted font-light line-clamp-3"><?= e($story['excerpt']) ?></p>
                        <?php if ($story['published_at']): ?>
                        <p class="text-[11px] text-muted/60 uppercase tracking-widest mt-4"><?= date('F j, Y', strtotime($story['published_at'])) ?></p>
                        <?php endif; ?>
                    </a>

                    <?php if (($i + 1) % 4 === 0): ?>
                    <article class="bg-surface/50 p-8 rounded-[2rem] flex flex-col justify-center border border-sand/30">
                        <svg class="w-8 h-8 text-accent mb-6 opacity-50" viewBox="0 0 24 24" fill="currentColor"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                        <p class="text-lg font-serif italic text-foreground mb-6 leading-relaxed">"Living in an Advet home has fundamentally shifted my nervous system. It is so profoundly quiet."</p>
                        <p class="text-sm font-medium text-muted uppercase tracking-widest">— S. Reynolds, The Haven</p>
                    </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Service Banner -->
        <section class="relative py-48 overflow-hidden group mt-32">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1600607687644-c7171b42498f?auto=format&fit=crop&q=80&w=2000"
                     alt="Excellence Banner"
                     class="w-full h-full object-cover transition-transform duration-[12s] group-hover:scale-110">
                <div class="absolute inset-0 bg-foreground/60 backdrop-blur-[2px]"></div>
            </div>
            <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 text-center">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Service Excellence</p>
                <h2 class="text-5xl md:text-7xl font-serif font-light text-background mb-12 leading-tight">
                    Buy. Sell. <span class="italic opacity-80">Lease.</span>
                </h2>
                <a href="<?= BASE ?>contact" class="inline-flex items-center justify-center gap-3 bg-accent text-background px-10 py-5 rounded-full hover:bg-accent-dark transition-all transform hover:-translate-y-1 shadow-2xl font-medium">
                    Inquire About Services
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </section>
    </main>

<?php require_once '../includes/footer.php'; ?>
