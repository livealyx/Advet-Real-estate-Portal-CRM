<?php
// FILE: admin/crm.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
$currentRole = strtolower($_SESSION['user']['role'] ?? '');
if (!in_array($currentRole, ['admin', 'agent'])) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => "Access Denied."];
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$user = $_SESSION['user'];
$userId = (int)$user['id'];
$isAdmin = $user['role'] === 'admin';

// Queries for CRM Stats
if ($isAdmin) {
    $activeLeads     = (int)$pdo->query("SELECT COUNT(*) FROM crm_contacts")->fetchColumn();
    $pipelineValue   = (float)$pdo->query("SELECT SUM(deal_value) FROM crm_deals WHERE status='active'")->fetchColumn();
    $wonThisMonth    = (float)$pdo->query("SELECT SUM(amount) FROM crm_transactions WHERE status='verified' AND MONTH(payment_date) = MONTH(CURDATE())")->fetchColumn();
    $pendingTasks    = (int)$pdo->query("SELECT COUNT(*) FROM crm_tasks WHERE status='pending'")->fetchColumn();
    $totalInquiries  = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
} else {
    $stmtAL = $pdo->prepare("SELECT COUNT(*) FROM crm_contacts WHERE assigned_to = ?");
    $stmtAL->execute([$userId]);
    $activeLeads = (int)$stmtAL->fetchColumn();

    $stmtPV = $pdo->prepare("SELECT SUM(d.deal_value) FROM crm_deals d JOIN crm_contacts c ON c.id = d.contact_id WHERE d.status='active' AND c.assigned_to = ?");
    $stmtPV->execute([$userId]);
    $pipelineValue = (float)$stmtPV->fetchColumn();

    $stmtWon = $pdo->prepare("SELECT SUM(t.amount) FROM crm_transactions t JOIN crm_deals d ON d.id = t.deal_id JOIN crm_contacts c ON c.id = d.contact_id WHERE t.status='verified' AND c.assigned_to = ? AND MONTH(t.payment_date) = MONTH(CURDATE())");
    $stmtWon->execute([$userId]);
    $wonThisMonth = (float)$stmtWon->fetchColumn();

    $stmtPT = $pdo->prepare("SELECT COUNT(*) FROM crm_tasks WHERE agent_id = ? AND status='pending'");
    $stmtPT->execute([$userId]);
    $pendingTasks = (int)$stmtPT->fetchColumn();

    $stmtInq = $pdo->prepare("SELECT COUNT(i.id) FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.agent_id = ?");
    $stmtInq->execute([$userId]);
    $totalInquiries = (int)$stmtInq->fetchColumn();
}

$modules = [
    [
        'title' => 'Sales Pipeline',
        'desc' => 'Manage active deals and visualize your sales funnel progression.',
        'url' => 'admin/crm-pipeline.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
        'metric' => formatPrice($pipelineValue),
        'metricLabel' => 'Active Value'
    ],
    [
        'title' => 'Direct Inquiries',
        'desc' => 'Review and respond to new property inquiries and general messages.',
        'url' => 'admin/inquiries.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.909A2.25 2.25 0 012.25 6.993V6.75" /></svg>',
        'metric' => $totalInquiries,
        'metricLabel' => 'Total Inquiries'
    ],
    [
        'title' => 'Leads & Archive',
        'desc' => 'Access raw lead data, qualify prospects, and browse the historical archive.',
        'url' => 'admin/crm-leads.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
        'metric' => null,
        'metricLabel' => null
    ],
    [
        'title' => 'Contacts',
        'desc' => 'Manage your centralized address book of qualified clients and partners.',
        'url' => 'admin/crm-contacts.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
        'metric' => $activeLeads,
        'metricLabel' => 'Active Contacts'
    ],
    [
        'title' => 'Daily Tasks',
        'desc' => 'Track your daily to-dos, follow-ups, and calendar action items.',
        'url' => 'admin/crm-tasks.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>',
        'metric' => $pendingTasks,
        'metricLabel' => 'Pending Tasks',
        'highlight' => $pendingTasks > 0
    ],
    [
        'title' => 'Documents',
        'desc' => 'Securely store and organize contracts, proposals, and client files.',
        'url' => 'admin/crm-documents.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9zm3.75 11.625a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
        'metric' => null,
        'metricLabel' => null
    ],
    [
        'title' => 'Transactions',
        'desc' => 'Monitor verified sales, financial logs, and revenue generation.',
        'url' => 'admin/crm-transactions.php',
        'icon' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>',
        'metric' => formatPrice($wonThisMonth),
        'metricLabel' => 'Monthly Revenue'
    ]
];

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body{-webkit-font-smoothing:antialiased;}
        .group:hover .absolute::before {
            content: ""; position: absolute; top: -2rem; left: 0; right: 0; height: 2rem; z-index: -1;
        }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>

<!-- Sidebar -->
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<!-- Main Content -->
<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex justify-between items-end mb-16 flex-wrap gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Advet Studio Hub</p>
            <h1 class="text-4xl font-serif font-light italic">CRM Overview.</h1>
            <div class="flex items-center gap-3 mt-6">
                <span class="flex items-center gap-2 px-3 py-1 bg-accent/10 rounded-full text-[9px] font-bold uppercase tracking-widest text-accent border border-accent/20">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span> Module Active
                </span>
                <span class="text-[9px] uppercase tracking-widest text-muted font-medium">Customer Relationship Engine</span>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php foreach ($modules as $mod): ?>
        <a href="<?= BASE ?><?= $mod['url'] ?>" class="group flex flex-col bg-background p-8 rounded-[2.5rem] shadow-sm border border-sand/40 hover:border-accent/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-500">
            <div class="w-16 h-16 rounded-2xl bg-surface/50 border border-sand/40 flex items-center justify-center text-accent mb-8 group-hover:bg-accent group-hover:text-background group-hover:border-accent transition-all duration-500 shadow-sm group-hover:shadow-md">
                <?= $mod['icon'] ?>
            </div>
            <h3 class="text-xl font-serif font-light mb-3 text-foreground group-hover:text-accent transition-colors"><?= $mod['title'] ?></h3>
            <p class="text-xs text-muted leading-relaxed mb-8 flex-grow"><?= $mod['desc'] ?></p>
            
            <div class="pt-6 border-t border-sand/30 flex justify-between items-end mt-auto">
                <?php if ($mod['metric'] !== null): ?>
                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-1"><?= $mod['metricLabel'] ?></p>
                        <p class="text-lg font-serif <?= !empty($mod['highlight']) ? 'text-accent' : 'text-foreground' ?>"><?= $mod['metric'] ?></p>
                    </div>
                <?php else: ?>
                    <div>
                        <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-1">Status</p>
                        <p class="text-sm font-medium text-foreground">Active</p>
                    </div>
                <?php endif; ?>
                
                <div class="w-8 h-8 rounded-full bg-surface border border-sand/40 flex items-center justify-center group-hover:bg-accent group-hover:text-background group-hover:border-accent transition-colors">
                    <svg class="w-3 h-3 group-hover:rotate-45 transition-transform duration-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
