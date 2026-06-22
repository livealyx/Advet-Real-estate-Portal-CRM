<?php
// FILE: admin/add-album.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
$album = [
    'title'         => '',
    'slug'          => '',
    'description'   => '',
    'cover_image'   => '',
    'display_order' => 0,
    'status'        => 'active',
];
$photos = [];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM albums WHERE id = ?');
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $album = $fetched;
        $stmt = $pdo->prepare('SELECT * FROM album_images WHERE album_id = ? ORDER BY display_order ASC, created_at DESC');
        $stmt->execute([$id]);
        $photos = $stmt->fetchAll();
    }
}
$settings = loadSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Manage Album' : 'New Album' ?> | Admin</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-32 items-start">
        <!-- Main Info -->
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
            <header class="flex flex-wrap justify-between items-end mb-10 gap-4 border-b border-sand pb-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-2 cursor-pointer hover:text-accent-dark transition-colors" onclick="location.href='albums.php'">← Back to Albums</p>
                    <h1 class="text-2xl font-serif font-light italic">
                        <?= $id ? 'Manage' : 'New' ?> <span class="text-muted text-lg">Portfolio</span>
                    </h1>
                </div>
                <button type="submit" form="album-form" class="px-6 py-3 bg-foreground text-background rounded-xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
                    Save Changes
                </button>
            </header>

            <form id="album-form" action="<?= BASE ?>actions/save-album.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                <?php if ($id > 0): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Album Title</label>
                    <input type="text" name="title" value="<?= e($album['title']) ?>" required
                           class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all"
                           placeholder="Ex: Architectural Shadows">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Curatorial Statement</label>
                    <textarea name="description" rows="4" 
                              class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light text-muted transition-all leading-relaxed"><?= e($album['description']) ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Order</label>
                        <input type="number" name="display_order" value="<?= $album['display_order'] ?>" 
                               class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Status</label>
                        <select name="status" class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                            <option value="active" <?= $album['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="draft" <?= $album['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                </div>

                <div class="border-t border-sand/20 pt-8 mt-4">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Ingest Visual Assets</label>
                    <input type="file" name="photos[]" multiple accept="image/*" 
                           class="block w-full text-xs text-sand file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-accent hover:file:bg-sand transition-all">
                    <p class="mt-4 text-[8px] uppercase tracking-[0.2em] text-muted opacity-60">High-fidelity imagery (JPG, PNG, WEBP)</p>
                </div>
            </form>
        </div>

        <!-- Current Photos -->
        <?php if ($id > 0): ?>
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 relative">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Manage Current Photos</h2>
            
            <div id="photos-grid" class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                <!-- Cover Photo Selector/Preview -->
                <div class="col-span-full mb-8 relative group aspect-video rounded-[2rem] overflow-hidden border border-sand/40 bg-surface">
                    <?php if ($album['cover_image']): ?>
                        <img id="cover-preview" src="<?= imgUrl($album['cover_image']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div id="cover-preview" class="w-full h-full flex flex-col items-center justify-center text-muted">
                            <svg class="w-12 h-12 mb-3 text-sand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <p class="text-[8px] uppercase tracking-widest font-bold">Album Cover Required</p>
                        </div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-foreground/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]">
                        <p class="text-white text-[10px] font-bold uppercase tracking-widest">Select Cover from images below</p>
                    </div>
                    <div class="absolute top-6 left-6 px-4 py-2 bg-white/90 backdrop-blur rounded-full text-[8px] font-bold uppercase tracking-[0.2em] text-foreground">Current Cover Image</div>
                </div>

                <?php foreach ($photos as $p): ?>
                <div class="relative group aspect-square rounded-2xl overflow-hidden shadow-sm border border-sand/20">
                    <img src="<?= imgUrl($p['image_path']) ?>" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                    <div class="absolute inset-x-0 bottom-0 p-2 bg-foreground/60 backdrop-blur translate-y-full group-hover:translate-y-0 transition-transform flex gap-2">
                        <!-- Set as Cover -->
                        <form action="<?= BASE ?>actions/save-album.php" method="POST" class="flex-grow">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="set_cover" value="<?= $p['image_path'] ?>">
                            <button class="w-full py-2 bg-white/10 hover:bg-white/20 rounded-lg text-[8px] uppercase font-bold tracking-widest text-white transition-all">Cover</button>
                        </form>
                        <!-- Delete Image -->
                        <form action="<?= BASE ?>actions/delete-album-image.php" method="POST" onsubmit="return confirm('Delete this image?');">
                            <input type="hidden" name="image_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="album_id" value="<?= $id ?>">
                            <button class="px-3 py-2 bg-red-500/80 hover:bg-red-500 rounded-lg text-[8px] uppercase font-bold tracking-widest text-white transition-all">×</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($photos)): ?>
                <div class="col-span-full py-12 text-center text-muted italic border-t border-sand/20 mt-4 font-light">
                    No images in this album yet. Upload photos using the form on the left.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
