<?php
// FILE: admin/system.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();

// Fetch last 15 cron logs
$logs = $pdo->query("SELECT * FROM cron_logs ORDER BY executed_at DESC LIMIT 15")->fetchAll();

// Check if sitemap exists
$sitemapPath = __DIR__ . '/../sitemap.xml';
$sitemapExists = file_exists($sitemapPath);
$sitemapTime = $sitemapExists ? date('M j, Y — g:i A', filemtime($sitemapPath)) : 'Never';

$cronUrl = 'http://' . $_SERVER['HTTP_HOST'] . BASE . 'actions/generate-sitemap.php?key=studiocron123';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System & Maintenance | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-5xl mx-auto">
        
        <header class="flex justify-between items-end mb-12 form-reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Technical Operations</p>
                <h1 class="text-4xl font-serif font-light italic">System <span class="text-muted">& SEO</span></h1>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12 form-reveal" style="animation-delay: 0.1s">
            
            <!-- Sitemap Control -->
            <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-accent">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A2 2 0 013 15.485V5.242a2 2 0 011.553-1.94l6-1.5a2 2 0 011.894 1.94v10.243a2 2 0 01-1.553 1.94L9 20zm0 0l6 3 6-3V11.5a2 2 0 00-1.553-1.94L15 8.358m0 0V2.742a2 2 0 011.553-1.94l6 1.5a2 2 0 011.894 1.94v10.243a2 2 0 01-1.553 1.94L15 21z"/></svg>
                    </div>
                    <h2 class="text-lg font-serif">Sitemap Indexing</h2>
                </div>
                
                <div class="space-y-4 mb-10">
                    <div class="flex justify-between text-xs">
                        <span class="text-muted">Status:</span>
                        <span class="<?= $sitemapExists ? 'text-green-600' : 'text-red-500' ?> font-bold uppercase tracking-widest"><?= $sitemapExists ? 'Active' : 'Missing' ?></span>
                    </div>
                    <div class="flex justify-between text-xs border-t border-sand/30 pt-4">
                        <span class="text-muted">Last Generated:</span>
                        <span class="font-medium"><?= $sitemapTime ?></span>
                    </div>
                </div>

                <a href="<?= BASE ?>actions/generate-sitemap.php?force=1" 
                   class="block w-full text-center py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-lg">
                    Regenerate Sitemap
                </a>
                <p class="text-[9px] text-muted/60 mt-4 italic text-center text-pretty">Updates sitemap.xml with all latest properties and stories for Google Search Console.</p>
            </div>

            <!-- Cronjob Config -->
            <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-accent">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="text-lg font-serif">Cron Engine</h2>
                </div>

                <p class="text-xs text-muted mb-6 leading-relaxed">To automate sitemap updates and maintenance, schedule a GET request to the following endpoint daily:</p>
                
                <div class="p-4 bg-surface rounded-2xl border border-sand/30 mb-8 overflow-hidden">
                    <code class="text-[10px] break-all text-accent"><?= $cronUrl ?></code>
                </div>

                <div class="flex flex-col gap-3">
                    <button onclick="document.getElementById('cronInstructions').classList.toggle('hidden')" 
                            class="text-[10px] font-bold text-accent uppercase tracking-widest hover:underline text-left">
                        Setup Instructions &raquo;
                    </button>
                    <div id="cronInstructions" class="hidden mt-4 p-6 bg-surface/50 rounded-2xl border border-sand/30 space-y-4">
                        <div class="space-y-2">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted">For cPanel / Linux:</p>
                            <code class="block p-3 bg-foreground text-background rounded-lg text-[9px] break-all">curl -s "<?= $cronUrl ?>" > /dev/null 2>&1</code>
                        </div>
                        <div class="space-y-2">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted">Frequency:</p>
                            <p class="text-[10px] text-muted font-light leading-relaxed">We recommend setting this to <strong>Once Per Day</strong> (0 0 * * *) to keep your sitemap optimized for search engines.</p>
                        </div>
                        <div class="space-y-2 pt-2 border-t border-sand/30">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted">For WAMP / Windows:</p>
                            <p class="text-[10px] text-muted font-light leading-relaxed">Use Windows Task Scheduler to run a PowerShell command:</p>
                            <code class="block p-3 bg-foreground text-background rounded-lg text-[9px] break-all">Invoke-WebRequest -Uri "<?= $cronUrl ?>"</code>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Task Logs -->
        <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden form-reveal" style="animation-delay: 0.2s">
            <div class="p-8 border-b border-sand/30">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-foreground">Task Execution Log</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-surface/30">
                        <tr>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Time</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Task</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Status</th>
                            <th class="px-8 py-4 text-[9px] uppercase tracking-widest font-bold text-muted">Result Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand/30">
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="px-8 py-12 text-center text-sm text-muted italic">No tasks have been executed yet.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach ($logs as $l): ?>
                        <tr>
                            <td class="px-8 py-4 text-xs font-medium text-foreground"><?= date('M j — g:i A', strtotime($l['executed_at'])) ?></td>
                            <td class="px-8 py-4 text-[10px] font-bold uppercase tracking-widest text-accent"><?= e($l['task_name']) ?></td>
                            <td class="px-8 py-4">
                                <span class="px-2 py-1 bg-green-50 text-green-600 text-[8px] font-bold uppercase rounded-md border border-green-100"><?= e($l['status']) ?></span>
                            </td>
                            <td class="px-8 py-4 text-xs text-muted max-w-xs truncate"><?= e($l['message']) ?></td>
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
