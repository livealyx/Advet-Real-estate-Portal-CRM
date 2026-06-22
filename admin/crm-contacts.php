<?php
// FILE: admin/crm-contacts.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = "WHERE 1=1";
$params = [];

if (!$isAdmin) {
    $where .= " AND c.assigned_to = ?";
    $params[] = $userId;
}

if ($search) {
    $where .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $s = "%$search%";
    $params[] = $s; $params[] = $s; $params[] = $s;
}

if ($type) {
    $where .= " AND c.type = ?";
    $params[] = $type;
}

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts c $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Fetch
$stmt = $pdo->prepare("
    SELECT c.*, u.name as agent_name
    FROM crm_contacts c
    LEFT JOIN users u ON u.id = c.assigned_to
    $where
    ORDER BY c.name ASC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$contacts = $stmt->fetchAll();

$agents = $pdo->query("SELECT id, name FROM users WHERE role IN ('admin', 'agent') ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Directory | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/flash.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Client Registry</p>
            <h1 class="text-4xl font-serif font-light italic">Universal <span class="text-muted">Contacts</span></h1>
        </div>
        <div class="flex gap-4">
            <form method="GET" class="flex gap-4">
                <div class="relative">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search directory..." class="w-64 px-6 py-4 bg-background border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all shadow-sm">
                </div>
                <select name="type" onchange="this.form.submit()" class="px-6 py-4 bg-background border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent shadow-sm appearance-none">
                    <option value="">All Types</option>
                    <option value="buyer" <?= $type==='buyer'?'selected':'' ?>>Buyers</option>
                    <option value="seller" <?= $type==='seller'?'selected':'' ?>>Sellers</option>
                    <option value="investor" <?= $type==='investor'?'selected':'' ?>>Investors</option>
                    <option value="tenant" <?= $type==='tenant'?'selected':'' ?>>Tenants</option>
                </select>
                <button type="submit" class="hidden">Search</button>
            </form>
            <button onclick="toggleModal('contactModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">+ New Contact</button>
        </div>
    </header>

    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-10 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Person</th>
                        <th class="px-10 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Affiliation</th>
                        <th class="px-10 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Property / Budget</th>
                        <th class="px-10 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($contacts)): ?>
                        <tr><td colspan="4" class="px-10 py-20 text-center text-muted italic font-serif opacity-50">No contacts matching criteria found in your registry.</td></tr>
                    <?php else: foreach ($contacts as $c): ?>
                        <tr class="hover:bg-surface/10 transition-colors group">
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-12 rounded-2xl bg-surface border border-sand/40 flex items-center justify-center font-serif text-accent font-bold text-lg"><?= substr($c['name'],0,1) ?></div>
                                    <div>
                                        <p class="text-sm font-medium text-foreground"><?= e($c['name']) ?></p>
                                        <p class="text-[10px] text-muted font-light mt-1"><?= e($c['email'] ?: $c['phone']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                <div class="space-y-1.5">
                                    <span class="px-2.5 py-0.5 bg-accent/10 border border-accent/20 rounded-md text-[8px] font-bold uppercase tracking-widest text-accent"><?= e($c['type']) ?></span>
                                    <p class="text-[9px] text-muted uppercase tracking-widest font-medium opacity-60"><?= e($c['source']) ?></p>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                <p class="text-[11px] font-serif italic text-foreground"><?= e($c['property_type'] ?: 'Generic Inquiry') ?></p>
                                <p class="text-[10px] font-bold text-accent mt-1"><?= e($c['budget'] ?: '—') ?></p>
                            </td>
                            <td class="px-10 py-6 text-right">
                                <a href="crm-contact-detail.php?id=<?= $c['id'] ?>" class="px-6 py-2.5 bg-surface border border-sand/40 rounded-xl text-[9px] font-bold uppercase tracking-widest text-muted hover:bg-foreground hover:text-background transition-all">Manage Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="px-10 py-6 border-t border-sand/30 flex items-center justify-between">
            <p class="text-[9px] text-muted uppercase tracking-widest font-bold">Registry Page <?= $page ?> / <?= $totalPages ?> <span class="mx-2 opacity-20">|</span> <?= $totalRows ?> Total Entries</p>
            <div class="flex gap-2">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>" class="w-9 h-9 flex items-center justify-center rounded-xl text-[10px] font-bold transition-all <?= $i==$page ? 'bg-foreground text-background shadow-lg' : 'bg-surface hover:bg-sand text-muted' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Add Contact Modal (Mini version of Add Lead) -->
<div id="contactModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-xl rounded-[2.5rem] p-10 shadow-2xl border border-sand/40">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-xl font-serif italic text-foreground text-center flex-grow pl-6">New Registry <span class="text-muted">Entry</span></h3>
            <button onclick="toggleModal('contactModal')" class="text-muted hover:text-foreground transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
        <form action="<?= BASE ?>actions/crm-add-lead.php" method="POST" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2 col-span-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Full Legal Name</label>
                    <input type="text" name="name" required class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Email Address</label>
                    <input type="email" name="email" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Phone Number</label>
                    <input type="tel" name="phone" required class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
                <!-- Custom Affiliation Selector -->
                <div class="space-y-2 relative" id="attr-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Affiliation Type</label>
                    <input type="hidden" name="type" id="attr_input" value="buyer">
                    <button type="button" onclick="toggleDropdown('attr-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all text-left">
                        <span id="selected-attr">Buyer</span>
                        <svg class="w-3 h-3 text-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="attr-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-[101]">
                        <?php foreach(['buyer' => 'Buyer', 'seller' => 'Seller', 'investor' => 'Investor', 'tenant' => 'Tenant'] as $val => $lab): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectAttr('<?= $val ?>', '<?= $lab ?>')"><?= $lab ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Investment Budget</label>
                    <input type="text" name="budget" placeholder="e.g. 1.5 Cr" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-xs focus:outline-none focus:border-accent">
                </div>
            </div>
            <div class="flex gap-4 pt-6 mt-4 border-t border-sand/20">
                <button type="button" onclick="toggleModal('contactModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Save to Registry</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    document.getElementById(id).classList.toggle('hidden');
}
function toggleDropdown(id) {
    const d = document.getElementById(id);
    const h = d.classList.contains('hidden');
    document.querySelectorAll('[id$="-options"]').forEach(x => x.classList.add('hidden'));
    if (h) d.classList.remove('hidden');
}
function selectAttr(val, lab) {
    document.getElementById('attr_input').value = val;
    document.getElementById('selected-attr').innerText = lab;
    document.getElementById('attr-options').classList.add('hidden');
}
window.onclick = function(e) {
    if (!e.target.closest('#attr-selector')) {
        document.querySelectorAll('[id$="-options"]').forEach(x => x.classList.add('hidden'));
    }
}
</script>

</body>
</html>
