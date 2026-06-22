<?php
// FILE: public/album-detail.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);
$siteName = $settings['site_name'] ?? 'Advet Buildwell';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . BASE . 'gallery'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM albums WHERE slug = ? AND status='active'");
$stmt->execute([$slug]);
$album = $stmt->fetch();

if (!$album) {
    header('Location: ' . BASE . 'gallery'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM album_images WHERE album_id = ? ORDER BY display_order ASC, created_at DESC");
$stmt->execute([$album['id']]);
$photos = $stmt->fetchAll();

$pageTitle = $album['title'] . ' | Gallery';
$pageDesc  = $album['description'] ?: 'A collection of architectural moments and curated spaces.';
require_once '../includes/header.php';
?>

<section class="relative pt-48 pb-32 overflow-hidden bg-background">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 relative z-10">
        <header class="flex flex-col md:flex-row justify-between items-end mb-24 reveal gap-8">
            <div class="max-w-2xl">
                <a href="<?= BASE ?>gallery" class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-6 hover:text-foreground transition-all flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Return to Albums
                </a>
                <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8"><?= e($album['title']) ?></h1>
                <p class="text-muted text-lg font-light leading-relaxed max-w-xl italic"><?= e($album['description']) ?></p>
            </div>
            <div class="shrink-0 flex items-center self-start md:self-end">
                <span class="w-12 h-px bg-sand/60 block self-center"></span>
                <span class="text-[10px] font-medium uppercase tracking-[0.4em] text-accent pl-4"><?= count($photos) ?> Observations</span>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 reveal">
            <?php foreach ($photos as $i => $p): 
                // Creating a slight rhythm with aspect ratios
                $aspect = ($i % 5 === 0) ? 'aspect-[4/3] md:col-span-2' : (($i % 3 === 0) ? 'aspect-[3/4]' : 'aspect-square');
            ?>
                <div class="<?= $aspect ?> relative overflow-hidden rounded-[2.5rem] bg-surface group cursor-pointer shadow-sm hover:shadow-2xl transition-all duration-700">
                    <img src="<?= imgUrl($p['image_path']) ?>" alt="<?= e($album['title']) ?>" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110">
                    <div class="absolute inset-0 bg-foreground/20 opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]"></div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($photos)): ?>
            <div class="py-32 text-center text-muted italic font-light reveal">
                No images in this album.
            </div>
        <?php endif; ?>

        <div class="mt-32 pt-24 border-t border-sand/20 text-center reveal">
            <h3 class="text-2xl font-serif mb-12 italic">Experience the atmosphere for yourself.</h3>
            <a href="<?= BASE ?>contact" class="bg-foreground text-background px-12 py-5 rounded-full hover:bg-accent hover:text-white transition-all transform hover:-translate-y-1 shadow-xl font-bold text-[10px] uppercase tracking-widest leading-loose">
                Book a site visit
            </a>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
