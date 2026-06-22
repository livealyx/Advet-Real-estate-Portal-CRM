<?php
// FILE: admin/stories.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();

$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where = '';
$args  = [];
if ($search !== '') {
    $where  = 'WHERE title LIKE ?';
    $args[] = '%' . $search . '%';
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM stories $where");
$stmt->execute($args);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM story_comments WHERE story_id = s.id AND status = 'pending') as pending_comments
    FROM stories s 
    $where 
    ORDER BY s.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($args);
$stories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Stories | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Journal Archive</p>
            <h1 class="text-4xl font-serif font-light italic">All <span class="text-muted">Stories</span></h1>
        </div>
        <a href="<?= BASE ?>admin/add-story.php" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            + New Story
        </a>
    </header>

    <!-- Search -->
    <form method="GET" class="mb-8">
        <div class="relative max-w-md">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by title…"
                   class="w-full px-6 py-4 pr-16 bg-background border border-sand/40 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 px-4 py-2 bg-foreground text-background rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-accent transition-all">
                Go
            </button>
        </div>
    </form>

    <!-- Table -->
    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Image</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Title</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Status</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Added / Published</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($stories)): ?>
                    <tr><td colspan="5" class="px-8 py-16 text-center text-muted italic">No stories found.</td></tr>
                    <?php else: foreach ($stories as $s): 
                        $statusCls = $s['published_at'] && strtotime($s['published_at']) <= time() 
                            ? 'bg-green-50 text-green-700 border-green-200' 
                            : 'bg-amber-50 text-amber-700 border-amber-200';
                        $statusText = $s['published_at'] && strtotime($s['published_at']) <= time() ? 'Published' : 'Draft/Scheduled';
                    ?>
                    <tr class="hover:bg-surface/10 transition-colors">
                        <td class="px-6 py-5">
                            <?php if ($s['cover_image']): ?>
                            <img src="<?= imgUrl($s['cover_image']) ?>" alt="" class="w-16 h-12 object-cover rounded-xl">
                            <?php else: ?>
                            <div class="w-16 h-12 bg-surface rounded-xl flex items-center justify-center text-sand">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9l4-4 4 4 4-5 4 5"/></svg>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-sm font-medium max-w-[250px] truncate"><?= e($s['title']) ?></td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $statusCls ?>"><?= e($statusText) ?></span>
                            <?php if ($s['pending_comments'] > 0): ?>
                                <a href="<?= BASE ?>admin/comments.php" class="ml-2 inline-flex items-center justify-center w-5 h-5 bg-red-500 text-white rounded-full text-[10px] font-bold shadow-lg transform hover:scale-110 transition-transform" title="Pending Comments">
                                    <?= $s['pending_comments'] ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-sm text-muted">
                            <?php if ($s['published_at']): ?>
                                <span class="block">Pub: <?= date('M j, Y', strtotime($s['published_at'])) ?></span>
                            <?php else: ?>
                                <span class="block">Created: <?= date('M j, Y', strtotime($s['created_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex gap-3">
                                <a href="<?= BASE ?>admin/add-story.php?id=<?= $s['id'] ?>" class="text-[10px] uppercase font-bold tracking-widest text-[#899178] hover:text-[#6E755F] transition-colors">Edit</a>
                                <form action="<?= BASE ?>actions/delete-story.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this story? This action cannot be undone.');">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="text-[10px] uppercase font-bold tracking-widest text-red-500/80 hover:text-red-500 transition-colors">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-8 py-6 border-t border-sand/30 flex justify-between items-center text-sm text-muted">
            <p>Showing Page <?= $page ?> of <?= $totalPages ?></p>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-sand/50 rounded-lg hover:bg-surface transition-colors">Prev</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="px-4 py-2 border border-sand/50 rounded-lg hover:bg-surface transition-colors">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
