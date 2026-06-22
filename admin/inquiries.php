<?php
// FILE: admin/inquiries.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if (!in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo    = getPDO();
$settings = loadSettings($pdo);
$userRole = $_SESSION['user']['role'];
$userId   = (int)$_SESSION['user']['id'];

$filter = $_GET['status'] ?? 'all';
$validFilters = ['all','new','read','replied'];
if (!in_array($filter, $validFilters, true)) $filter = 'all';

$whereClauses = [];
$args  = [];

if ($filter !== 'all') {
    $whereClauses[] = 'i.status = ?';
    $args[] = $filter;
}

if ($userRole === 'agent') {
    $whereClauses[] = 'p.agent_id = ?';
    $args[] = $userId;
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

$stmt = $pdo->prepare(
    "SELECT i.*, p.title as property_title, p.id as property_id
       FROM inquiries i
  LEFT JOIN properties p ON p.id = i.property_id
     $whereSQL
     ORDER BY i.created_at DESC"
);
$stmt->execute($args);
$inquiries = $stmt->fetchAll();

// Counts for filter tabs
if ($userRole === 'admin') {
    $counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM inquiries GROUP BY status")->fetchAll();
} else {
    $stCount = $pdo->prepare("SELECT i.status, COUNT(i.id) as cnt FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.agent_id = ? GROUP BY i.status");
    $stCount->execute([$userId]);
    $counts = $stCount->fetchAll();
}
$countMap = array_column($counts, 'cnt', 'status');
$totalCount = array_sum($countMap);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries | Advet Studio</title>
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
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Client Relations</p>
            <h1 class="text-4xl font-serif font-light italic">Inquiries</h1>
        </div>
    </header>

    <!-- Filter Tabs -->
    <div class="flex flex-wrap gap-2 mb-8">
        <?php
        $tabs = ['all'=>'All','new'=>'New','read'=>'Read','replied'=>'Replied'];
        foreach ($tabs as $key => $label):
            $cnt  = $key === 'all' ? $totalCount : (int)($countMap[$key] ?? 0);
            $active = $filter === $key;
        ?>
        <a href="?status=<?= $key ?>"
           class="px-5 py-2.5 rounded-2xl text-xs font-bold uppercase tracking-widest transition-all <?= $active ? 'bg-foreground text-background' : 'bg-background border border-sand text-muted hover:bg-surface' ?>">
            <?= $label ?> <span class="ml-1 opacity-60">(<?= $cnt ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <?php foreach (['Name','Email','Phone','Property','Message','Status','Date','Actions'] as $th): ?>
                        <th class="px-6 py-5 text-[10px] uppercase tracking-widest font-bold text-muted whitespace-nowrap"><?= $th ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($inquiries)): ?>
                    <tr><td colspan="8" class="px-8 py-16 text-center text-muted italic">No inquiries found.</td></tr>
                    <?php else: foreach ($inquiries as $inq):
                        $sc = match($inq['status']) {
                            'new'     => 'bg-red-50 text-red-700 border-red-200',
                            'read'    => 'bg-amber-50 text-amber-700 border-amber-200',
                            'replied' => 'bg-green-50 text-green-700 border-green-200',
                            default   => 'bg-surface text-muted border-sand',
                        };
                    ?>
                    <tr class="hover:bg-surface/10 transition-colors">
                        <td class="px-6 py-5 text-sm font-medium whitespace-nowrap"><?= e($inq['name']) ?></td>
                        <td class="px-6 py-5 text-sm text-muted"><?= e($inq['email']) ?></td>
                        <td class="px-6 py-5 text-sm text-muted"><?= e($inq['phone'] ?: '—') ?></td>
                        <td class="px-6 py-5 text-sm text-muted">
                            <?php if ($inq['property_id'] && $inq['property_title']): ?>
                                <a href="<?= BASE ?>property-detail.php?id=<?= (int)$inq['property_id'] ?>" class="hover:text-accent transition-colors underline"><?= e($inq['property_title']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-sm text-muted max-w-[200px]">
                            <span class="line-clamp-2"><?= e($inq['message']) ?></span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $sc ?>"><?= e($inq['status']) ?></span>
                        </td>
                        <td class="px-6 py-5 text-[11px] text-muted whitespace-nowrap"><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                        <td class="px-6 py-5 whitespace-nowrap">
                                <div class="flex flex-col gap-1.5">
                                    <?php if ($inq['status'] !== 'read'): ?>
                                    <form method="POST" action="<?= BASE ?>actions/update-inquiry-status.php">
                                        <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                                        <input type="hidden" name="status" value="read">
                                        <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                        <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-amber-600 hover:text-amber-800 underline transition-colors">Mark Read</button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($inq['status'] !== 'replied'): ?>
                                    <form method="POST" action="<?= BASE ?>actions/update-inquiry-status.php">
                                        <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                                        <input type="hidden" name="status" value="replied">
                                        <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                        <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-green-700 hover:text-green-900 underline transition-colors">Mark Replied</button>
                                    </form>
                                    <?php endif; ?>
                                    <!-- View Full Message Modal Trigger -->
                                    <button onclick="openModal(<?= htmlspecialchars(json_encode(['name'=>$inq['name'],'email'=>$inq['email'],'msg'=>$inq['message'],'prop'=>$inq['property_title']??'','date'=>$inq['created_at']]), ENT_QUOTES) ?>)"
                                            class="text-[10px] uppercase tracking-widest font-bold text-foreground/50 hover:text-foreground underline transition-colors text-left">
                                        View
                                    </button>
                                    <!-- Delete Option -->
                                    <form method="POST" action="<?= BASE ?>actions/delete-inquiry.php" onsubmit="return confirm('Are you sure you want to delete this inquiry? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                                        <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                        <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-red-500 hover:text-red-700 underline transition-colors">Delete</button>
                                    </form>
                                </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Inquiry Detail Modal -->
<div id="inquiry-modal" class="fixed inset-0 z-[9998] bg-foreground/50 backdrop-blur-sm hidden items-center justify-center p-6" onclick="if(event.target===this)closeModal()">
    <div class="bg-background rounded-[2.5rem] shadow-2xl max-w-lg w-full p-10">
        <div class="flex justify-between items-start mb-8">
            <h3 class="text-2xl font-serif font-light" id="modal-name">—</h3>
            <button onclick="closeModal()" class="text-muted hover:text-foreground transition-colors">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-4 text-sm text-muted mb-8">
            <p><span class="font-bold text-foreground/50 uppercase tracking-widest text-[10px]">Email</span><br><span id="modal-email"></span></p>
            <p><span class="font-bold text-foreground/50 uppercase tracking-widest text-[10px]">Property</span><br><span id="modal-prop"></span></p>
            <p><span class="font-bold text-foreground/50 uppercase tracking-widest text-[10px]">Date</span><br><span id="modal-date"></span></p>
            <p><span class="font-bold text-foreground/50 uppercase tracking-widest text-[10px]">Message</span><br>
               <span id="modal-msg" class="leading-relaxed block mt-1 whitespace-pre-line"></span>
            </p>
        </div>
        <button onclick="closeModal()" class="w-full py-4 border border-sand text-muted rounded-2xl text-xs font-bold uppercase tracking-widest hover:bg-surface transition-all">Close</button>
    </div>
</div>

<script>
function openModal(data) {
    document.getElementById('modal-name').textContent  = data.name;
    document.getElementById('modal-email').textContent = data.email;
    document.getElementById('modal-prop').textContent  = data.prop || '—';
    document.getElementById('modal-date').textContent  = data.date;
    document.getElementById('modal-msg').textContent   = data.msg;
    document.getElementById('inquiry-modal').classList.replace('hidden','flex');
}
function closeModal() {
    document.getElementById('inquiry-modal').classList.replace('flex','hidden');
}
</script>
</body>
</html>
