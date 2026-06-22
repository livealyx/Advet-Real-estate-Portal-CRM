<?php
// FILE: admin/albums.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();

$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM albums");
$stmt->execute();
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM albums ORDER BY display_order ASC, created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$albums = $stmt->fetchAll();

$settings = loadSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management | Admin</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Content Assets</p>
            <h1 class="text-4xl font-serif font-light italic">Photo <span class="text-muted">Gallery</span></h1>
        </div>
        <a href="<?= BASE ?>admin/add-album.php" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            + New Album
        </a>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php if (empty($albums)): ?>
            <div class="col-span-full py-24 text-center bg-background rounded-[2.5rem] border border-dashed border-sand/60">
                <p class="text-muted italic">No albums created yet.</p>
            </div>
        <?php else: foreach ($albums as $a): 
            $statusCls = $a['status'] === 'active' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700';
        ?>
        <div class="group bg-background rounded-[2rem] border border-sand/40 overflow-hidden shadow-sm hover:shadow-xl transition-all flex flex-col">
            <div class="aspect-square relative overflow-hidden bg-surface">
                <?php if ($a['cover_image']): ?>
                    <img src="<?= imgUrl($a['cover_image']) ?>" alt="<?= e($a['title']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-sand/60">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                <?php endif; ?>
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 text-[8px] uppercase font-bold tracking-widest rounded-full border border-current <?= $statusCls ?>">
                        <?= e($a['status']) ?>
                    </span>
                </div>
            </div>
            <div class="p-6 flex-grow flex flex-col">
                <h3 class="text-lg font-serif mb-2 line-clamp-1"><?= e($a['title']) ?></h3>
                <p class="text-xs text-muted mb-6 flex-grow line-clamp-2"><?= e($a['description'] ?: 'No description provided.') ?></p>
                <div class="flex items-center justify-between mt-auto pt-4 border-t border-sand/20">
                    <a href="<?= BASE ?>admin/add-album.php?id=<?= $a['id'] ?>" class="text-[10px] uppercase font-bold tracking-widest text-accent hover:text-foreground transition-colors">Manage</a>
                    <form action="<?= BASE ?>actions/delete-album.php" method="POST" onsubmit="return confirm('Delete this album and all its photos?');">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button type="submit" class="text-[10px] uppercase font-bold tracking-widest text-red-400 hover:text-red-600 transition-colors">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="mt-12 flex justify-center gap-2">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border <?= $i===$page ? 'bg-foreground text-background border-foreground' : 'bg-background text-muted border-sand/40 hover:border-accent' ?> transition-all text-xs font-bold"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
