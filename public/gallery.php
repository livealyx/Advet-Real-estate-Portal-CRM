<?php
// FILE: public/gallery.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);
$siteName = $settings['site_name'] ?? 'Advet Buildwell';

// Fetch all active albums with their image count
$albums = $pdo->query("SELECT a.*, (SELECT COUNT(*) FROM album_images WHERE album_id = a.id) as photo_count FROM albums a WHERE status='active' ORDER BY display_order ASC, created_at DESC")->fetchAll();

$pageTitle = 'Gallery';
$pageDesc = 'A visual journey through our curated homes and architectural landscapes.';
require_once '../includes/header.php';
?>

<section class="relative pt-48 pb-32 overflow-hidden bg-background">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 relative z-10">
        <header class="text-center mb-24 reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-6">Visual Narrative</p>
            <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8">
                Our <span class="italic text-muted">Gallery.</span>
            </h1>
            <div class="w-12 h-px bg-sand/60 mx-auto mb-8"></div>
            <p class="text-muted text-lg font-light leading-relaxed max-w-2xl mx-auto">
                Step inside our most evocative projects. A study in precision, texture, and the art of modern living.
            </p>
        </header>

        <?php if (empty($albums)): ?>
            <div class="py-32 text-center text-muted italic font-light reveal">
                Our portfolio is currently being curated. Please check back shortly.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 reveal relative z-20">
                <?php foreach ($albums as $a): ?>
                    <a href="<?= navPath('gallery/' . $a['slug']) ?>" class="group block relative z-30">
                        <div
                            class="aspect-[4/5] relative overflow-hidden rounded-[2.5rem] bg-surface shadow-lg group-hover:shadow-2xl transition-all duration-700 pointer-events-auto">
                            <?php if ($a['cover_image']): ?>
                                <img src="<?= imgUrl($a['cover_image']) ?>" alt="<?= e($a['title']) ?>"
                                    class="w-full h-full object-cover transition-transform duration-[1.5s] group-hover:scale-110">
                            <?php endif; ?>
                            <div
                                class="absolute inset-x-0 bottom-0 p-12 bg-gradient-to-t from-foreground/80 to-transparent translate-y-8 group-hover:translate-y-0 transition-transform duration-500 pointer-events-none">
                                <span
                                    class="text-[8px] font-bold uppercase tracking-[0.3em] text-accent/80 block mb-3"><?= $a['photo_count'] ?>
                                    Captured Moments</span>
                                <h3
                                    class="text-3xl font-serif text-white opacity-90 group-hover:opacity-100 transition-opacity">
                                    <?= e($a['title']) ?>
                                </h3>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute top-1/4 -right-24 w-96 h-96 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 -left-24 w-96 h-96 bg-sand/10 rounded-full blur-3xl pointer-events-none"></div>
</section>

<?php require_once '../includes/footer.php'; ?>