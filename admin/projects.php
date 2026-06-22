<?php
// FILE: admin/projects.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if (!in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();
$settings = loadSettings($pdo);

$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where = [];
$args  = [];
if ($search !== '') {
    $where[] = '(title LIKE ? OR location LIKE ?)';
    $args[] = '%' . $search . '%';
    $args[] = '%' . $search . '%';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects $whereClause");
$stmt->execute($args);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM projects $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($args);
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management | Advet Studio</title>
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
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Project Module</p>
            <h1 class="text-4xl font-serif font-light italic">Managed <span class="text-muted">Projects</span></h1>
        </div>
        <a href="<?= BASE ?>admin/add-project.php" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            + Create New Project
        </a>
    </header>

    <!-- Search -->
    <form method="GET" class="mb-8">
        <div class="relative max-w-md">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search projects by name or location…"
                   class="w-full px-6 py-4 bg-background border border-sand/40 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
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
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Cover</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Project Title</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Type</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Location</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Possession</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Status</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($projects)): ?>
                    <tr><td colspan="7" class="px-8 py-16 text-center text-muted italic">No projects found. Start by creating one.</td></tr>
                    <?php else: foreach ($projects as $p):
                        $statusCls = $p['status'] === 'active' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200';
                    ?>
                    <tr class="hover:bg-surface/10 transition-colors">
                        <td class="px-6 py-5">
                            <img src="<?= imgUrl($p['cover_image']) ?>" alt="" class="w-16 h-12 object-cover rounded-xl shadow-sm">
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-medium"><?= e($p['title']) ?></div>
                            <div class="text-[10px] text-muted tracking-tight"><?= e($p['slug']) ?></div>
                        </td>
                        <td class="px-6 py-5 text-xs text-muted"><?= e($p['project_type']) ?></td>
                        <td class="px-6 py-5 text-xs text-muted"><?= e($p['location']) ?></td>
                        <td class="px-6 py-5">
                             <span class="text-[10px] font-bold text-accent uppercase tracking-wider"><?= e($p['possession_status']) ?></span>
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $statusCls ?>"><?= e($p['status']) ?></span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <a href="<?= BASE ?>admin/add-project.php?id=<?= (int)$p['id'] ?>"
                                   class="text-[10px] uppercase tracking-widest font-bold text-foreground hover:text-accent transition-colors underline">Edit</a>
                                <form method="POST" action="<?= BASE ?>actions/delete-project.php"
                                      onsubmit="return confirm('Delete \'<?= e(addslashes($p['title'])) ?>\' and all its data?')">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-red-400 hover:text-red-600 transition-colors underline">Delete</button>
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
        <div class="px-8 py-6 border-t border-sand/30 flex items-center justify-between">
            <p class="text-[11px] text-muted uppercase tracking-widest">
                Page <?= $page ?> of <?= $totalPages ?> &nbsp;·&nbsp; <?= $totalRows ?> Projects
            </p>
            <div class="flex gap-2">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                   class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-medium transition-all
                          <?= $i === $page ? 'bg-foreground text-background' : 'bg-surface hover:bg-sand text-muted' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
