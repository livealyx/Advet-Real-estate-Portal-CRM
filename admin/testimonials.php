<?php
// FILE: admin/testimonials.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();

$status   = $_GET['status'] ?? 'all';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where = [];
$args  = [];
if ($status !== 'all') {
    $where[] = 'status = ?';
    $args[]  = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials $whereClause");
$stmt->execute($args);
$totalRows  = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM testimonials $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($args);
$feedbacks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Testimonials | Advet Studio</title>
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
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Testimonials</p>
            <h1 class="text-4xl font-serif font-light italic">Feedback <span class="text-muted">& Reviews</span></h1>
        </div>
        <div class="flex gap-2">
            <a href="?status=all" class="px-5 py-2 <?= $status === 'all' ? 'bg-foreground text-background' : 'bg-surface border border-sand/40 text-muted hover:bg-surface/70' ?> rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">All</a>
            <a href="?status=pending" class="px-5 py-2 <?= $status === 'pending' ? 'bg-foreground text-background' : 'bg-surface border border-sand/40 text-muted hover:bg-surface/70' ?> rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">Pending</a>
            <a href="?status=approved" class="px-5 py-2 <?= $status === 'approved' ? 'bg-foreground text-background' : 'bg-surface border border-sand/40 text-muted hover:bg-surface/70' ?> rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">Approved</a>
        </div>
    </header>

    <!-- Table -->
    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Client</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Type</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted w-1/2">Feedback</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap">Status</th>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($feedbacks)): ?>
                    <tr><td colspan="5" class="px-8 py-16 text-center text-muted italic">No feedback found.</td></tr>
                    <?php else: foreach ($feedbacks as $f): 
                        $statusCls = match($f['status']) {
                            'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                            'approved' => 'bg-green-50 text-green-700 border-green-200',
                            'declined' => 'bg-gray-100 text-gray-600 border-gray-200',
                            default    => 'bg-surface text-muted border-sand'
                        };
                    ?>
                    <tr class="hover:bg-surface/10 transition-colors">
                        <td class="px-6 py-5">
                            <p class="text-sm font-medium"><?= e($f['name']) ?></p>
                            <p class="text-xs text-muted"><?= e($f['affiliation'] ?: '—') ?></p>
                            <p class="text-[10px] text-muted/60 mt-1"><?= date('M j, Y', strtotime($f['created_at'])) ?></p>
                        </td>
                        <td class="px-6 py-5 text-xs uppercase tracking-widest font-bold text-muted">
                            <?= e($f['experience_type']) ?>
                        </td>
                        <td class="px-6 py-5 text-sm text-foreground/80 font-serif italic max-w-md">
                            <div class="flex gap-1 mb-2 scale-75 origin-left opacity-60">
                                <?php for($i=1; $i<=5; $i++): ?>
                                <svg class="w-4 h-4 <?= $i <= $f['rating'] ? 'text-accent' : 'text-sand' ?> fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                            "<?= e($f['content']) ?>"
                        </td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $statusCls ?>"><?= e($f['status']) ?></span>
                        </td>
                        <td class="px-6 py-5 text-right flex gap-3 justify-end whitespace-nowrap">
                            <?php if ($f['status'] !== 'approved'): ?>
                            <form action="<?= BASE ?>actions/manage-testimonial.php" method="POST" class="inline">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="text-[10px] uppercase font-bold tracking-widest text-[#899178] hover:text-[#6E755F] transition-colors">Approve</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($f['status'] !== 'declined'): ?>
                            <form action="<?= BASE ?>actions/manage-testimonial.php" method="POST" class="inline">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="action" value="decline">
                                <button type="submit" class="text-[10px] uppercase font-bold tracking-widest text-amber-600/80 hover:text-amber-600 transition-colors">Decline</button>
                            </form>
                            <?php endif; ?>
                            <form action="<?= BASE ?>actions/manage-testimonial.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this permanently?');">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="text-[10px] uppercase font-bold tracking-widest text-red-500/80 hover:text-red-500 transition-colors">Delete</button>
                            </form>
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
                <a href="?page=<?= $page-1 ?>&status=<?= urlencode($status) ?>" class="px-4 py-2 border border-sand/50 rounded-lg hover:bg-surface transition-colors">Prev</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&status=<?= urlencode($status) ?>" class="px-4 py-2 border border-sand/50 rounded-lg hover:bg-surface transition-colors">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
