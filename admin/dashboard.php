<?php
// FILE: admin/dashboard.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
$currentRole = strtolower($_SESSION['user']['role'] ?? '');
if (!in_array($currentRole, ['admin', 'agent'])) {
    $_SESSION['flash'] = [
        'type' => 'error', 
        'msg' => "Access Denied: Your current role ($currentRole) does not have dashboard access. If this was just changed, please Log Out and Log In again."
    ];
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$user = $_SESSION['user'];
$userId = (int)$user['id'];
$isAdmin = $user['role'] === 'admin';

if ($isAdmin) {
    // Admin Queries (Global)
    $totalProperties  = (int)$pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $activeListings   = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='active'")->fetchColumn();
    $totalInquiries   = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    $newInquiries     = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();

    // CRM Specific
    $activeLeads     = (int)$pdo->query("SELECT COUNT(*) FROM crm_contacts")->fetchColumn();
    $pipelineValue   = (float)$pdo->query("SELECT SUM(deal_value) FROM crm_deals WHERE status='active'")->fetchColumn();
    $wonThisMonth    = (float)$pdo->query("SELECT SUM(amount) FROM crm_transactions WHERE status='verified' AND MONTH(payment_date) = MONTH(CURDATE())")->fetchColumn();
    $pendingTasks    = (int)$pdo->query("SELECT COUNT(*) FROM crm_tasks WHERE status='pending'")->fetchColumn();

    $recentInquiries = $pdo->query(
        "SELECT i.id, i.name, i.email, i.status, i.created_at, p.title as property_title
           FROM inquiries i
      LEFT JOIN properties p ON p.id = i.property_id
          ORDER BY i.created_at DESC LIMIT 5"
    )->fetchAll();

    $recentProperties = $pdo->query(
        "SELECT id, title, location, price, status, created_at FROM properties ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    $byMonth = $pdo->query(
        "SELECT DATE_FORMAT(created_at,'%b') as month, COUNT(*) as count
           FROM inquiries
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY MONTH(created_at)
          ORDER BY created_at"
    )->fetchAll();
    
    $statuses = $pdo->query("SELECT status, COUNT(*) as cnt FROM properties GROUP BY status")->fetchAll();
} else {
    // Agent Queries (Filtered)
    $stmtProp = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE agent_id = ?");
    $stmtProp->execute([$userId]);
    $totalProperties = (int)$stmtProp->fetchColumn();

    $stmtPropAct = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE status='active' AND agent_id = ?");
    $stmtPropAct->execute([$userId]);
    $activeListings = (int)$stmtPropAct->fetchColumn();

    $stmtInq = $pdo->prepare("SELECT COUNT(i.id) FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE p.agent_id = ?");
    $stmtInq->execute([$userId]);
    $totalInquiries = (int)$stmtInq->fetchColumn();

    $stmtInqNew = $pdo->prepare("SELECT COUNT(i.id) FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE i.status='new' AND p.agent_id = ?");
    $stmtInqNew->execute([$userId]);
    $newInquiries = (int)$stmtInqNew->fetchColumn();

    // CRM Specific (Agent)
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

    $stmtRecInq = $pdo->prepare(
        "SELECT i.id, i.name, i.email, i.status, i.created_at, p.title as property_title
           FROM inquiries i
           JOIN properties p ON p.id = i.property_id
          WHERE p.agent_id = ?
          ORDER BY i.created_at DESC LIMIT 5"
    );
    $stmtRecInq->execute([$userId]);
    $recentInquiries = $stmtRecInq->fetchAll();

    $stmtRecProp = $pdo->prepare(
        "SELECT id, title, location, price, status, created_at FROM properties WHERE agent_id = ? ORDER BY created_at DESC LIMIT 5"
    );
    $stmtRecProp->execute([$userId]);
    $recentProperties = $stmtRecProp->fetchAll();

    $stmtMonth = $pdo->prepare(
        "SELECT DATE_FORMAT(i.created_at,'%b') as month, COUNT(i.id) as count
           FROM inquiries i
           JOIN properties p ON p.id = i.property_id
          WHERE i.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND p.agent_id = ?
          GROUP BY MONTH(i.created_at)
          ORDER BY i.created_at"
    );
    $stmtMonth->execute([$userId]);
    $byMonth = $stmtMonth->fetchAll();
    
    $stmtStatuses = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM properties WHERE agent_id = ? GROUP BY status");
    $stmtStatuses->execute([$userId]);
    $statuses = $stmtStatuses->fetchAll();
}

$chartLabels = json_encode(array_column($byMonth, 'month') ?: ['Jan']);
$chartData   = json_encode(array_values(array_column($byMonth, 'count') ?: [0]));

// Visitor Data (Global for all Studio)
$visits = $pdo->query("SELECT DATE_FORMAT(view_date, '%b %d') as date, views FROM page_views ORDER BY view_date DESC LIMIT 7")->fetchAll();
$visits = array_reverse($visits); // so oldest is first
$visitLabels = json_encode(array_column($visits, 'date') ?: ['Today']);
$visitData   = json_encode(array_values(array_column($visits, 'views') ?: [0]));

$vToday = (int)$pdo->query("SELECT views FROM page_views WHERE view_date = CURDATE()")->fetchColumn();
$v7Days = (int)$pdo->query("SELECT SUM(views) FROM page_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$vMonth = (int)$pdo->query("SELECT SUM(views) FROM page_views WHERE MONTH(view_date) = MONTH(CURDATE()) AND YEAR(view_date) = YEAR(CURDATE())")->fetchColumn();
$vTotal = (int)$pdo->query("SELECT SUM(views) FROM page_views")->fetchColumn();

// --- Live System Health Metrics ---
$diskFreeRaw = @disk_free_space(__DIR__) ?: 0;
$diskTotalRaw = @disk_total_space(__DIR__) ?: 0;
$diskFreeGB = $diskFreeRaw ? round($diskFreeRaw / (1024 * 1024 * 1024), 1) : 0;
$diskPct = $diskTotalRaw ? round((($diskTotalRaw - $diskFreeRaw) / $diskTotalRaw) * 100) : 0;

$memUsage = round(memory_get_usage(true) / (1024 * 1024), 1);
$phpLimit = ini_get('memory_limit');

$dbStatus = 'Optimal';
$dbPing = 0;
try {
    $t1 = microtime(true);
    $pdo->query('SELECT 1');
    $dbPing = round((microtime(true) - $t1) * 1000);
} catch (\Throwable $e) {
    $dbStatus = 'Error';
}

require_once '../includes/changelog-data.php';
$latestVersion = $entries[0]['version'] ?? 'N/A';

function adminPath(string $p): string { return BASE . 'admin/' . $p; }
function adminNavActive(string $page): string {
    return str_contains($_SERVER['SCRIPT_FILENAME'], $page)
        ? 'bg-background/5 text-accent'
        : 'text-background/50 hover:bg-background/5 hover:text-background';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{-webkit-font-smoothing:antialiased;}
        /* Bridge the gap for dropdowns to prevent losing hover state */
        .group:hover .absolute::before {
            content: "";
            position: absolute;
            top: -2rem;
            left: 0;
            right: 0;
            height: 2rem;
            z-index: -1;
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
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Realestate Portal</p>
                <h1 class="text-4xl font-serif font-light italic"><?= timeGreeting() ?>, <?= e($user['name']) ?>.</h1>
                <div class="flex items-center gap-3 mt-6">
                    <span class="flex items-center gap-2 px-3 py-1 bg-accent/10 rounded-full text-[9px] font-bold uppercase tracking-widest text-accent border border-accent/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span> System Operational
                    </span>
                    <span class="text-[9px] uppercase tracking-widest text-muted font-medium">Live Data</span>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <!-- CRM Overview Link -->
                <a href="<?= BASE ?>admin/crm.php" class="w-12 h-12 flex items-center justify-center bg-background rounded-2xl border border-sand/40 hover:border-accent hover:text-accent transition-all shadow-sm group relative" title="CRM Overview">
                    <svg class="w-5 h-5 text-muted group-hover:text-accent transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                </a>
                
                <!-- Notifications -->
                <div class="relative group">
                    <button class="w-12 h-12 flex items-center justify-center bg-background rounded-2xl border border-sand/40 hover:border-accent transition-all relative group shadow-sm">
                        <svg class="w-5 h-5 text-muted group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        <?php if ($newInquiries > 0): ?>
                            <span class="absolute top-3.5 right-3.5 w-2 h-2 bg-red-500 rounded-full ring-4 ring-background"></span>
                        <?php endif; ?>
                    </button>
                    <!-- Small Notifications Dropdown -->
                    <div class="absolute top-full right-0 mt-3 w-72 p-2 bg-background rounded-3xl shadow-2xl border border-sand/30 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-500 z-50">
                        <div class="px-5 py-4 border-b border-sand/20">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-foreground">Notifications</p>
                        </div>
                        <div class="py-2 max-h-60 overflow-y-auto">
                            <?php if ($newInquiries > 0): ?>
                                <a href="<?= BASE ?>admin/inquiries.php" class="block px-5 py-4 hover:bg-surface/50 rounded-2xl transition-all group/notif">
                                    <p class="text-[11px] font-bold mb-1 group-hover/notif:text-accent transition-colors"><?= $newInquiries ?> New Inquiries</p>
                                    <p class="text-[9px] text-muted uppercase tracking-widest italic">Awaiting advisory response</p>
                                </a>
                            <?php else: ?>
                                <div class="px-5 py-8 text-center">
                                    <p class="text-[10px] text-muted italic">All clear. No new alerts.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="relative group">
                    <button class="flex items-center gap-4 pl-2 pr-6 py-2 bg-background rounded-2xl border border-sand/40 hover:border-accent transition-all shadow-sm">
                        <div class="w-10 h-10 rounded-xl bg-accent flex items-center justify-center font-bold text-sm text-foreground overflow-hidden">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?= imgUrl($user['profile_picture']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="text-left hidden sm:block">
                            <p class="text-[10px] font-bold uppercase tracking-widest leading-none"><?= e($user['name']) ?></p>
                            <p class="text-[9px] text-muted uppercase tracking-widest mt-1"><?= e($user['role']) ?></p>
                        </div>
                        <svg class="w-3 h-3 text-muted group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <!-- Profile Dropdown -->
                    <div class="absolute top-full right-0 mt-3 w-56 p-2 bg-background rounded-3xl shadow-2xl border border-sand/30 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all duration-500 z-50">
                        <a href="<?= BASE ?>admin/profile.php" class="flex items-center gap-4 px-5 py-3.5 rounded-2xl hover:bg-surface/50 transition-all group/item">
                            <svg class="w-4 h-4 text-muted group-hover/item:text-accent transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <span class="text-[10px] uppercase tracking-[0.15em] font-bold text-muted group-hover/item:text-foreground">Settings</span>
                        </a>
                        <a href="<?= BASE ?>admin/crm.php" class="flex items-center gap-4 px-5 py-3.5 rounded-2xl hover:bg-surface/50 transition-all group/item">
                            <svg class="w-4 h-4 text-muted group-hover/item:text-accent transition-colors" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" /></svg>
                            <span class="text-[10px] uppercase tracking-[0.15em] font-bold text-muted group-hover/item:text-foreground">CRM Overview</span>
                        </a>
                        <div class="h-px bg-sand/20 mx-4 my-2"></div>
                        <a href="<?= BASE ?>auth/logout.php" class="flex items-center gap-4 px-5 py-3.5 rounded-2xl hover:bg-red-50 transition-all group/item">
                            <svg class="w-4 h-4 text-red-300 group-hover/item:text-red-500 transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            <span class="text-[10px] uppercase tracking-[0.15em] font-bold text-red-300 group-hover/item:text-red-500">Sign Out</span>
                        </a>
                    </div>
                </div>

                <a href="<?= BASE ?>admin/add-property.php" class="hidden min-[1600px]:flex px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
                    New Listing +
                </a>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-8 mb-16">
            <?php
            $stats = [
                ['Active Leads',   $activeLeads,     'Unique contacts in CRM'],
                ['Pipeline Value', formatPrice($pipelineValue), 'Potential revenue'],
                ['Won (Month)',    formatPrice($wonThisMonth),  'Total verified sales'],
                ['Pending Tasks',  $pendingTasks,    'Awaiting action', $pendingTasks > 0],
            ];
            foreach ($stats as $s):
            ?>
            <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-6"><?= $s[0] ?></p>
                <h3 class="text-3xl font-serif <?= !empty($s[3]) ? 'text-accent' : '' ?>"><?= $s[1] ?></h3>
                <p class="text-xs text-accent mt-4 font-medium uppercase tracking-widest"><?= $s[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Actions & Performance Feed -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
            <div class="lg:col-span-2 bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex justify-between items-center mb-10">
                    <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Quick Actions</h3>
                    <span class="text-[9px] text-muted uppercase tracking-widest font-medium">Studio Workflow</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <a href="<?= BASE ?>admin/add-property.php" class="flex flex-col items-center gap-4 p-6 bg-surface/30 rounded-3xl hover:bg-accent hover:text-white transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-accent group-hover:bg-white/20 group-hover:text-white shadow-sm transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold">New Listing</span>
                    </a>
                    <a href="<?= BASE ?>admin/listings.php" class="flex flex-col items-center gap-4 p-6 bg-surface/30 rounded-3xl hover:bg-accent hover:text-white transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-accent group-hover:bg-white/20 group-hover:text-white shadow-sm transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold">Manage Props</span>
                    </a>
                    <a href="<?= BASE ?>admin/settings.php" class="flex flex-col items-center gap-4 p-6 bg-surface/30 rounded-3xl hover:bg-accent hover:text-white transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-accent group-hover:bg-white/20 group-hover:text-white shadow-sm transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold">Studio Settings</span>
                    </a>
                    <a href="<?= BASE ?>admin/analytics.php" class="flex flex-col items-center gap-4 p-6 bg-surface/30 rounded-3xl hover:bg-accent hover:text-white transition-all group">
                        <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-accent group-hover:bg-white/20 group-hover:text-white shadow-sm transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold">Market Stats</span>
                    </a>
                </div>
            </div>
            <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground mb-8">System Health</h3>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full <?= $dbStatus === 'Optimal' ? 'bg-green-500' : 'bg-red-500' ?>"></div>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-muted">Database</span>
                        </div>
                        <span class="text-[11px] font-medium"><?= $dbStatus ?> (<?= $dbPing ?>ms)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full <?= $diskPct > 90 ? 'bg-red-500' : 'bg-green-500' ?>"></div>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-muted">Storage</span>
                        </div>
                        <span class="text-[11px] font-medium"><?= $diskFreeGB > 0 ? $diskFreeGB . ' GB Free' : 'N/A' ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-accent"></div>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-muted">PHP Mem</span>
                        </div>
                        <span class="text-[11px] font-medium"><?= $memUsage ?> MB / <?= $phpLimit === '-1' ? 'Max' : $phpLimit ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-accent animate-pulse"></div>
                            <span class="text-[11px] font-bold uppercase tracking-widest text-muted">Version</span>
                        </div>
                        <a href="<?= BASE ?>changelog" target="_blank" class="text-[11px] font-bold text-accent hover:underline"><?= explode(' —', $latestVersion)[0] ?></a>
                    </div>
                    <div class="pt-4 border-t border-sand/30">
                        <div class="flex items-center justify-between opacity-50 mb-4">
                            <span class="text-[9px] font-bold uppercase tracking-widest">WAMP Stack</span>
                            <span class="text-[9px] font-bold"><?= PHP_VERSION ?></span>
                        </div>
                        <a href="<?= BASE ?>actions/clear-cache.php" class="flex items-center justify-center gap-2 w-full py-3 bg-surface border border-sand/40 rounded-xl text-[9px] font-bold uppercase tracking-widest text-muted hover:bg-accent hover:text-white hover:border-accent transition-all">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Purge System Cache
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Visitors Area Chart -->
        <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40 mb-16">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Total Visitors</h3>
                    <p class="text-[10px] text-muted mt-1 uppercase tracking-widest">Site Traffic Information</p>
                </div>
                <span class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-muted">
                    <span class="w-3 h-3 rounded-full bg-[#E5E0D8] border-2 border-accent"></span> Unique Sessions
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="p-6 bg-surface/30 border border-sand/40 rounded-3xl">
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">Today</p>
                    <p class="text-3xl font-serif text-accent"><?= number_format($vToday) ?></p>
                </div>
                <div class="p-6 bg-surface/30 border border-sand/40 rounded-3xl">
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">Last 7 Days</p>
                    <p class="text-3xl font-serif text-accent"><?= number_format($v7Days) ?></p>
                </div>
                <div class="p-6 bg-surface/30 border border-sand/40 rounded-3xl">
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">This Month</p>
                    <p class="text-3xl font-serif text-accent"><?= number_format($vMonth) ?></p>
                </div>
                <div class="p-6 bg-surface/30 border border-sand/40 rounded-3xl">
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-2">Lifetime Total</p>
                    <p class="text-3xl font-serif text-accent"><?= number_format($vTotal) ?></p>
                </div>
            </div>
            <div class="h-[250px]">
                <canvas id="visitorChart"></canvas>
            </div>
        </div>

        <!-- Chart + Asset Health -->
        <div class="grid grid-cols-1 min-[1400px]:grid-cols-4 gap-8 mb-16">
            <div class="min-[1400px]:col-span-3 bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex justify-between items-center mb-12">
                    <div>
                        <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Inquiry Frequency</h3>
                        <p class="text-[10px] text-muted mt-1 uppercase tracking-widest">Monthly Volume · Last 6 Months</p>
                    </div>
                    <span class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-muted">
                        <span class="w-3 h-3 rounded-full bg-accent"></span> Current Year
                    </span>
                </div>
                <div class="h-[350px]">
                    <canvas id="dashChart"></canvas>
                </div>
            </div>
            <div class="min-[1400px]:col-span-1 space-y-8">
                <div class="bg-background p-8 rounded-[2.5rem] shadow-sm border border-sand/40">
                    <h3 class="text-[10px] uppercase tracking-[0.3em] font-bold text-foreground mb-8">Portfolio Breakdown</h3>
                    <div class="space-y-6">
                        <?php
                        $total    = max($totalProperties, 1);
                        $bars = ['active'=>['Active','bg-accent'],'draft'=>['Draft','bg-sand'],'sold'=>['Sold','bg-foreground/40']];
                        $counts = array_column($statuses,'cnt','status');
                        foreach ($bars as $key => [$label,$color]):
                            $cnt = (int)($counts[$key] ?? 0);
                            $pct = round($cnt / $total * 100);
                        ?>
                        <div>
                            <div class="flex justify-between text-[10px] uppercase tracking-widest font-bold mb-3">
                                <span class="text-muted"><?= $label ?></span>
                                <span><?= $pct ?>%</span>
                            </div>
                            <div class="w-full h-1.5 bg-surface rounded-full overflow-hidden">
                                <div class="h-full <?= $color ?> rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bg-accent text-background p-8 rounded-[2.5rem] shadow-lg shadow-accent/20">
                    <h3 class="text-[10px] uppercase tracking-[0.3em] font-bold mb-6">Studio Insight</h3>
                    <p class="text-sm font-serif italic mb-6">"Sustainability is no longer a luxury choice, but an architectural frequency."</p>
                    <a href="<?= BASE ?>admin/analytics.php" class="text-[9px] uppercase tracking-[0.2em] font-bold opacity-60 border-b border-background/40 pb-1">View Analytics</a>
                </div>
            </div>
        </div>

        <!-- Recent Inquiries -->
        <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden mb-8">
            <div class="p-10 border-b border-sand/40 flex justify-between items-center">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Recent Inquiries</h3>
                <a href="<?= BASE ?>admin/inquiries.php" class="text-[10px] uppercase tracking-widest font-bold text-accent underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface/30">
                        <tr>
                            <?php foreach (['Name','Email','Property','Status','Date'] as $th): ?>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted"><?= $th ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand/30">
                        <?php if (empty($recentInquiries)): ?>
                        <tr><td colspan="5" class="px-8 py-10 text-center text-muted text-sm italic">No inquiries yet.</td></tr>
                        <?php else: foreach ($recentInquiries as $inq):
                            $badgeCls = match($inq['status']) {
                                'new'     => 'bg-red-50 text-red-700 border-red-200',
                                'read'    => 'bg-amber-50 text-amber-700 border-amber-200',
                                'replied' => 'bg-green-50 text-green-700 border-green-200',
                                default   => 'bg-surface text-muted border-sand',
                            };
                        ?>
                        <tr class="hover:bg-surface/10 transition-colors">
                            <td class="px-8 py-6 text-sm font-medium"><?= e($inq['name']) ?></td>
                            <td class="px-8 py-6 text-sm text-muted"><?= e($inq['email']) ?></td>
                            <td class="px-8 py-6 text-sm text-muted"><?= e($inq['property_title'] ?: '—') ?></td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $badgeCls ?>"><?= e($inq['status']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-[11px] text-muted"><?= date('M j, Y', strtotime($inq['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Properties -->
        <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden">
            <div class="p-10 border-b border-sand/40 flex justify-between items-center">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Recent Properties</h3>
                <a href="<?= BASE ?>admin/listings.php" class="text-[10px] uppercase tracking-widest font-bold text-accent underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface/30">
                        <tr>
                            <?php foreach (['Title','Location','Price','Status','Added'] as $th): ?>
                            <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted"><?= $th ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand/30">
                        <?php if (empty($recentProperties)): ?>
                        <tr><td colspan="5" class="px-8 py-10 text-center text-muted text-sm italic">No properties yet.</td></tr>
                        <?php else: foreach ($recentProperties as $prop):
                            $sc = match($prop['status']) { 'active'=>'bg-green-50 text-green-700 border-green-200','sold'=>'bg-gray-100 text-gray-600 border-gray-200',default=>'bg-amber-50 text-amber-700 border-amber-200' };
                        ?>
                        <tr class="hover:bg-surface/10 transition-colors">
                            <td class="px-8 py-6 text-sm font-medium">
                                <a href="<?= BASE ?>admin/edit-property.php?id=<?= (int)$prop['id'] ?>" class="hover:text-accent transition-colors"><?= e($prop['title']) ?></a>
                            </td>
                            <td class="px-8 py-6 text-sm text-muted"><?= e($prop['location']) ?></td>
                            <td class="px-8 py-6 text-sm font-medium"><?= formatPrice((float)$prop['price']) ?></td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 text-[9px] uppercase font-bold tracking-widest rounded border <?= $sc ?>"><?= e($prop['status']) ?></span>
                            </td>
                            <td class="px-8 py-6 text-[11px] text-muted"><?= date('M j, Y', strtotime($prop['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

<script>
const ctx = document.getElementById('dashChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Inquiries',
            data: <?= $chartData ?>,
            backgroundColor: '#899178',
            hoverBackgroundColor: '#6E755F',
            borderRadius: 12,
            borderSkipped: false,
            barThickness: 36
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#2A2925', titleFont: { family: 'DM Sans', size: 10 }, bodyFont: { family: 'DM Sans', size: 12 }, padding: 12, displayColors: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(223,216,204,0.3)' }, ticks: { color: '#6D685C', font: { family: 'DM Sans', size: 10 } } },
            x: { grid: { display: false }, ticks: { color: '#6D685C', font: { family: 'DM Sans', size: 10 } } }
        }
    }
});

const vCtx = document.getElementById('visitorChart').getContext('2d');
const gradient = vCtx.createLinearGradient(0, 0, 0, 250);
gradient.addColorStop(0, 'rgba(137, 145, 120, 0.4)');
gradient.addColorStop(1, 'rgba(137, 145, 120, 0.0)');

new Chart(vCtx, {
    type: 'line',
    data: {
        labels: <?= $visitLabels ?>,
        datasets: [{
            label: 'Visitors',
            data: <?= $visitData ?>,
            backgroundColor: gradient,
            borderColor: '#899178',
            borderWidth: 2,
            pointBackgroundColor: '#FDFCF9',
            pointBorderColor: '#899178',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { backgroundColor: '#2A2925', titleFont: { family: 'DM Sans', size: 10 }, bodyFont: { family: 'DM Sans', size: 12 }, padding: 12, displayColors: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(223,216,204,0.3)' }, ticks: { color: '#6D685C', font: { family: 'DM Sans', size: 10 } } },
            x: { grid: { display: false }, ticks: { color: '#6D685C', font: { family: 'DM Sans', size: 10 } } }
        },
        interaction: { intersect: false, mode: 'index' }
    }
});
</script>
</body>
</html>
