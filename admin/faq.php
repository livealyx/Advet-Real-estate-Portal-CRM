<?php
// FILE: admin/faq.php
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
    $where  = 'WHERE question LIKE ? OR answer LIKE ?';
    $args[] = '%' . $search . '%';
    $args[] = '%' . $search . '%';
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM faqs $where");
$stmt->execute($args);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM faqs $where ORDER BY display_order ASC, created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($args);
$faqs = $stmt->fetchAll();

$settings = loadSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequently Asked Questions | Admin</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Support Center</p>
            <h1 class="text-4xl font-serif font-light italic">All <span class="text-muted">FAQ's</span></h1>
        </div>
        <a href="<?= BASE ?>admin/add-faq.php" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            + New FAQ
        </a>
    </header>

    <!-- Search -->
    <form method="GET" class="mb-8">
        <div class="relative max-w-md">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search questions or answers…"
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
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Order</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Question</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Status</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($faqs)): ?>
                    <tr><td colspan="4" class="px-8 py-16 text-center text-muted italic">No FAQ's found.</td></tr>
                    <?php else: foreach ($faqs as $f): 
                        $statusCls = $f['status'] === 'active' 
                            ? 'bg-green-50 text-green-700 border-green-200' 
                            : 'bg-amber-50 text-amber-700 border-amber-200';
                    ?>
                    <tr class="hover:bg-surface/10 transition-colors">
                        <td class="px-6 py-5 text-sm text-muted">#<?= $f['display_order'] ?></td>
                        <td class="px-6 py-5 text-sm font-medium max-w-[400px] truncate"><?= e($f['question']) ?></td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $statusCls ?>"><?= e(ucfirst($f['status'])) ?></span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex gap-3">
                                <a href="<?= BASE ?>admin/add-faq.php?id=<?= $f['id'] ?>" class="text-[10px] uppercase font-bold tracking-widest text-[#899178] hover:text-[#6E755F] transition-colors">Edit</a>
                                <form action="<?= BASE ?>actions/delete-faq.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this FAQ? This action cannot be undone.');">
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
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
