<?php
// FILE: admin/crm-transactions.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];
$isAdmin = $_SESSION['user']['role'] === 'admin';

$where = "WHERE 1=1";
$params = [];
if (!$isAdmin) {
    $where .= " AND c.assigned_to = ?";
    $params[] = $userId;
}

$stmt = $pdo->prepare("
    SELECT t.*, d.deal_value, c.name as contact_name, p.title as property_title
    FROM crm_transactions t
    JOIN crm_deals d ON d.id = t.deal_id
    JOIN crm_contacts c ON c.id = d.contact_id
    LEFT JOIN properties p ON p.id = d.property_id
    $where
    ORDER BY t.payment_date DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$totalSales = array_sum(array_map(fn($t) => $t['status'] === 'verified' ? (float)$t['amount'] : 0, $transactions));

// Fetch active deals for recording new payments
$dealsStmt = $pdo->prepare("
    SELECT d.id, c.name, p.title as property_title
    FROM crm_deals d
    JOIN crm_contacts c ON c.id = d.contact_id
    LEFT JOIN properties p ON p.id = d.property_id
    " . ($isAdmin ? "" : "WHERE c.assigned_to = " . $userId) . "
    ORDER BY d.created_at DESC
");
$dealsStmt->execute();
$dealsList = $dealsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | Advet CRM</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto max-h-screen">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Financial Records</p>
            <h1 class="text-4xl font-serif font-light italic">Sales & <span class="text-muted">Transactions</span></h1>
        </div>
        <div class="flex gap-4">
            <div class="bg-background rounded-2xl px-6 py-3 border border-sand/40 flex items-center gap-6 shadow-sm">
                <div class="text-center">
                    <p class="text-[8px] font-bold uppercase tracking-widest text-muted mb-1">Total Verified Revenue</p>
                    <p class="text-sm font-serif font-bold text-accent"><?= formatPrice($totalSales) ?></p>
                </div>
            </div>
            <button onclick="toggleModal('paymentModal')" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">+ Record Payment</button>
        </div>
    </header>

    <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface/40 border-b border-sand/30">
                    <tr>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Date</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Contact / Property</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Type</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Amount</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Status</th>
                        <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand/20">
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" class="px-8 py-20 text-center text-muted italic font-serif">No transactions recorded yet.</td></tr>
                    <?php else: foreach ($transactions as $t): 
                        $statusCls = match($t['status']) {
                            'verified' => 'text-green-600 bg-green-50',
                            'pending' => 'text-amber-600 bg-amber-50',
                            default => 'text-red-600 bg-red-50'
                        };
                    ?>
                        <tr class="hover:bg-surface/10 transition-colors">
                            <td class="px-8 py-6 text-[11px] text-muted uppercase font-bold tracking-widest"><?= date('M j, Y', strtotime($t['payment_date'])) ?></td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-medium text-foreground"><?= e($t['contact_name']) ?></p>
                                <p class="text-[10px] text-accent italic"><?= e($t['property_title'] ?: 'General Deal') ?></p>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-muted"><?= e($t['payment_type']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-sm font-serif font-bold"><?= formatPrice((float)$t['amount']) ?></td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 rounded-full text-[8px] font-bold uppercase tracking-widest <?= $statusCls ?>"><?= e($t['status']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-right text-[10px] font-mono text-muted"><?= e($t['ref_number'] ?: 'N/A') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Record Payment Modal -->
<div id="paymentModal" class="fixed inset-0 bg-foreground/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-background w-full max-w-lg rounded-[2.5rem] p-10 shadow-2xl border border-sand/40">
        <div class="flex justify-between items-center mb-10">
            <h3 class="text-xl font-serif italic text-foreground">Record <span class="text-muted">Payment</span></h3>
            <button onclick="toggleModal('paymentModal')" class="text-muted hover:text-foreground transition-colors"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <form id="paymentForm" action="<?= BASE ?>actions/crm-record-payment.php" method="POST" class="space-y-6">
            <!-- Custom Deal Selector -->
            <div class="space-y-2 relative" id="deal-selector">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Select Deal</label>
                <input type="hidden" name="deal_id" id="deal_id_input" required>
                <button type="button" onclick="toggleDropdown('deal-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                    <span id="selected-deal" class="text-muted/60">Select an active deal</span>
                    <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                </button>
                <div id="deal-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50 max-h-48 overflow-y-auto custom-scrollbar">
                    <div class="px-4 py-2 border-b border-sand/10">
                        <input type="text" placeholder="Search deals..." onkeyup="filterDeals(this)" class="w-full px-4 py-2 bg-surface/20 rounded-xl text-[10px] focus:outline-none focus:border-accent">
                    </div>
                    <div id="deal-list">
                        <?php foreach ($dealsList as $d): ?>
                            <div class="deal-option px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest truncate" data-name="<?= strtolower(e($d['name'] . ' ' . $d['property_title'])) ?>" onclick="selectDeal(<?= $d['id'] ?>, '<?= e($d['name']) ?> - <?= e($d['property_title'] ?: 'Gen') ?>')">
                                <?= e($d['name']) ?> <span class="text-muted/60 ml-2">@ <?= e($d['property_title'] ?: 'General') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Amount (<?= $settings['currency_symbol'] ?? '$' ?>)</label>
                    <input type="number" name="amount" step="0.01" required placeholder="0.00" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[12px] font-bold focus:outline-none focus:border-accent">
                </div>
                
                <!-- Custom Type Selector -->
                <div class="space-y-2 relative" id="type-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Type</label>
                    <input type="hidden" name="payment_type" id="type_input" value="Booking">
                    <button type="button" onclick="toggleDropdown('type-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-type">Booking</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="type-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                        <?php foreach(['Booking','Milestone','Taxes/Govt','Registry','Commission'] as $t): ?>
                            <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest" onclick="selectType('<?= $t ?>')"><?= $t ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Payment Date</label>
                    <div class="relative">
                        <input type="date" name="payment_date" required value="<?= date('Y-m-d') ?>" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[11px] font-bold focus:outline-none focus:border-accent appearance-none">
                    </div>
                </div>
                
                <!-- Custom Status Selector -->
                <div class="space-y-2 relative" id="status-selector">
                    <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Status</label>
                    <input type="hidden" name="status" id="status_input" value="pending">
                    <button type="button" onclick="toggleDropdown('status-options')" class="w-full flex items-center justify-between px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest focus:outline-none focus:border-accent transition-all">
                        <span id="selected-status" class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pending</span>
                        <svg class="w-3 h-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="status-options" class="absolute top-full left-0 right-0 mt-2 bg-background border border-sand/30 rounded-2xl shadow-2xl py-2 hidden z-50">
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-2" onclick="selectStatus('pending', 'amber', 'Pending')"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pending</div>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-2" onclick="selectStatus('verified', 'green', 'Verified')"><span class="w-1.5 h-1.5 rounded-full bg-green-400"></span> Verified</div>
                        <div class="px-6 py-3 hover:bg-surface/50 cursor-pointer text-[9px] font-bold uppercase tracking-widest flex items-center gap-2" onclick="selectStatus('failed', 'red', 'Failed')"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Failed</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] uppercase tracking-widest font-bold text-muted">Reference Number (Cheque / UTR)</label>
                <input type="text" name="ref_number" placeholder="Optional reference code" class="w-full px-6 py-4 bg-surface/30 border border-sand/40 rounded-2xl text-[12px] font-bold focus:outline-none focus:border-accent">
            </div>

            <div class="flex gap-4 pt-6">
                <button type="button" onclick="toggleModal('paymentModal')" class="flex-grow py-4 border border-sand/40 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-surface transition-all text-muted">Cancel</button>
                <button type="submit" class="flex-grow py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Record Now</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    document.getElementById(id).classList.toggle('hidden');
}

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const isHidden = dropdown.classList.contains('hidden');
    document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    if (isHidden) dropdown.classList.remove('hidden');
}

function selectDeal(id, name) {
    document.getElementById('deal_id_input').value = id;
    const label = document.getElementById('selected-deal');
    label.innerText = name;
    label.classList.remove('text-muted/60');
    document.getElementById('deal-options').classList.add('hidden');
}

function selectType(val) {
    document.getElementById('type_input').value = val;
    document.getElementById('selected-type').innerText = val;
    document.getElementById('type-options').classList.add('hidden');
}

function selectStatus(val, color, label) {
    document.getElementById('status_input').value = val;
    document.getElementById('selected-status').innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-${color}-400"></span> ${label}`;
    document.getElementById('status-options').classList.add('hidden');
}

function filterDeals(input) {
    const filter = input.value.toLowerCase();
    const options = document.querySelectorAll('.deal-option');
    options.forEach(opt => {
        const text = opt.getAttribute('data-name');
        opt.style.display = text.includes(filter) ? 'block' : 'none';
    });
}

window.onclick = function(event) {
    if (!event.target.closest('#deal-selector') && !event.target.closest('#type-selector') && !event.target.closest('#status-selector')) {
        document.querySelectorAll('[id$="-options"]').forEach(d => d.classList.add('hidden'));
    }
}
</script>

</body>
</html>
