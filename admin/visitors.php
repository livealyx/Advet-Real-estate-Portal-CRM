<?php
// FILE: admin/visitors.php
session_start();
require_once '../config/db.php';

// Only admins can view deep site telemetry
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();

// Overall basic aggregations for top metrics
$topOs      = $pdo->query("SELECT os, COUNT(id) as cnt FROM visitor_logs GROUP BY os ORDER BY cnt DESC LIMIT 1")->fetch() ?: ['os' => 'N/A', 'cnt' => 0];
$topBrowser = $pdo->query("SELECT browser, COUNT(id) as cnt FROM visitor_logs GROUP BY browser ORDER BY cnt DESC LIMIT 1")->fetch() ?: ['browser' => 'N/A', 'cnt' => 0];
$topDevice  = $pdo->query("SELECT device_type, COUNT(id) as cnt FROM visitor_logs GROUP BY device_type ORDER BY cnt DESC LIMIT 1")->fetch() ?: ['device_type' => 'N/A', 'cnt' => 0];

// Fetch recent sessions (limit to 100 for now to prevent massive page load on high traffic sites)
$logs = $pdo->query("SELECT ip_address, device_type, os, browser, page_url, visited_at FROM visitor_logs ORDER BY visited_at DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Telemetry | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <header class="flex justify-between items-end mb-12 form-reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Site Telemetry</p>
                <h1 class="text-4xl font-serif font-light italic">Visitor <span class="text-muted">Metrics</span></h1>
            </div>
            <span class="px-5 py-2 bg-accent/10 text-accent border border-accent/20 rounded-full text-[9px] font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span> Tracked Live
            </span>
        </header>

        <!-- Environment Highlights -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 form-reveal" style="animation-delay: 0.1s">
            <div class="p-8 bg-background border border-sand/40 rounded-[2.5rem] shadow-sm flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-surface flex items-center justify-center text-muted">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-1">Primary Device</p>
                    <p class="text-xl font-serif text-foreground"><?= e($topDevice['device_type']) ?></p>
                </div>
            </div>
            <div class="p-8 bg-background border border-sand/40 rounded-[2.5rem] shadow-sm flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-surface flex items-center justify-center text-muted">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-1">Leading OS</p>
                    <p class="text-xl font-serif text-foreground"><?= e($topOs['os']) ?></p>
                </div>
            </div>
            <div class="p-8 bg-background border border-sand/40 rounded-[2.5rem] shadow-sm flex items-center gap-6">
                <div class="w-14 h-14 rounded-2xl bg-surface flex items-center justify-center text-muted">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                </div>
                <div>
                    <p class="text-[9px] uppercase tracking-widest font-bold text-muted mb-1">Top Browser</p>
                    <p class="text-xl font-serif text-foreground"><?= e($topBrowser['browser']) ?></p>
                </div>
            </div>
        </div>

        <!-- Detailed Feed Table -->
        <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden form-reveal" style="animation-delay: 0.2s">
            <div class="p-8 border-b border-sand/30 flex justify-between items-center">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Detailed Session Log</h3>
                <span class="text-[9px] text-muted font-bold uppercase tracking-widest">Showing Last 100 Entries</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-surface/30">
                        <tr>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Date & Time</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">IP Address</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Device / OS</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Browser</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Entry Path</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand/30">
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="px-8 py-12 text-center text-sm text-muted">Awaiting traffic signals... no logs found yet.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($logs as $l): ?>
                        <tr class="hover:bg-surface/10 transition-colors">
                            <td class="px-8 py-5 text-xs font-medium text-foreground"><?= date('M j, Y — g:i A', strtotime($l['visited_at'])) ?></td>
                            <td class="px-8 py-5 text-sm font-serif text-muted"><?= e($l['ip_address']) ?></td>
                            <td class="px-8 py-5 text-xs text-foreground font-medium">
                                <?= e($l['device_type']) ?> <span class="text-muted/50 font-light mx-1">/</span> <?= e($l['os']) ?>
                            </td>
                            <td class="px-8 py-5 text-xs text-muted"><?= e($l['browser']) ?></td>
                            <td class="px-8 py-5 text-[10px] text-accent font-medium truncate max-w-xs">
                                <?= e($l['page_url']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
</body>
</html>
