<?php
/**
 * FILE: admin/cache-settings.php
 * Premium Cache Management Dashboard
 */
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$stats = AdvetCache::getStats();

$pageTitle = 'Cache Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Advet Admin</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-background flex">

<?php require_once './partials/sidebar.php'; ?>

<main class="flex-grow p-8 md:p-12">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mb-3">System Performance</p>
                <h1 class="text-4xl font-serif font-light leading-tight">Cache <span class="italic text-muted text-3xl">Management</span></h1>
            </div>
            <div class="flex gap-4">
                <a href="<?= BASE ?>actions/clear-cache.php?type=all&redirect=admin/cache-settings.php" 
                   class="px-8 py-3.5 bg-foreground text-background text-[10px] font-bold uppercase tracking-widest rounded-2xl hover:bg-black transition-all shadow-xl">
                    Purge All Cache
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Status Card -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-10 border border-sand/30 shadow-sm relative overflow-hidden group">
                    <h3 class="text-xl font-serif mb-8 flex items-center gap-3">
                        Live Status
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest <?= $stats['enabled'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $stats['enabled'] ? 'Optimized' : 'Bypass Mode' ?>
                        </span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                        <div class="p-6 bg-surface/50 rounded-3xl border border-sand/20">
                            <p class="text-[10px] uppercase tracking-widest text-muted mb-2">Cached Entries</p>
                            <p class="text-3xl font-serif"><?= number_format($stats['count']) ?></p>
                        </div>
                        <div class="p-6 bg-surface/50 rounded-3xl border border-sand/20">
                            <p class="text-[10px] uppercase tracking-widest text-muted mb-2">Storage Size</p>
                            <p class="text-3xl font-serif"><?= round($stats['size'] / 1024, 2) ?> <span class="text-sm">KB</span></p>
                        </div>
                        <div class="p-6 bg-surface/50 rounded-3xl border border-sand/20">
                            <p class="text-[10px] uppercase tracking-widest text-muted mb-2">Last Purge</p>
                            <p class="text-xl font-serif mt-1">
                                <?= $stats['last_cleared'] ? date('H:i, j M', $stats['last_cleared']) : 'Never' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Config Card -->
                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] p-10 border border-sand/30 shadow-sm">
                    <h3 class="text-xl font-serif mb-10">System Configuration</h3>
                    
                    <form action="<?= BASE ?>actions/save-settings.php" method="POST" class="space-y-10">
                        <input type="hidden" name="redirect" value="admin/cache-settings.php">
                        
                        <div class="flex items-center justify-between p-6 bg-surface/30 rounded-[2rem] border border-sand/10">
                            <div>
                                <p class="text-sm font-bold text-foreground">Global Cache Engine</p>
                                <p class="text-xs text-muted">Serve static snapshots of key pages to reduce server load.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="settings[cache_enabled]" value="0">
                                <input type="checkbox" name="settings[cache_enabled]" value="1" <?= $stats['enabled'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-14 h-8 bg-sand rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-accent shadow-inner"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Properties List TTL (Seconds)</label>
                                <input type="number" name="settings[cache_ttl_listing]" 
                                       value="<?= e($settings['cache_ttl_listing'] ?? 600) ?>" 
                                       class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all font-medium">
                                <p class="text-[10px] text-muted/60 mt-3 px-1 italic">Default: 600s (10 Minutes)</p>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Property Detail TTL (Seconds)</label>
                                <input type="number" name="settings[cache_ttl_detail]" 
                                       value="<?= e($settings['cache_ttl_detail'] ?? 1800) ?>" 
                                       class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all font-medium">
                                <p class="text-[10px] text-muted/60 mt-3 px-1 italic">Default: 1800s (30 Minutes)</p>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-sand/20 flex justify-end">
                            <button type="submit" class="px-12 py-5 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-accent transition-all shadow-xl shadow-foreground/10 transform hover:-translate-y-0.5">
                                Update Cache Protocol
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Side Intel -->
            <div class="space-y-8">
                <div class="bg-accent/5 rounded-[2.5rem] p-10 border border-accent/20">
                    <h4 class="text-sm uppercase tracking-widest font-bold text-accent mb-6">Cache Protocol</h4>
                    <p class="text-sm text-accent/80 leading-relaxed mb-6 italic">
                        "Speed is a feature, but freshness is a requirement. Our curation engine automatically purges cache whenever content is updated."
                    </p>
                    <ul class="space-y-4">
                        <li class="flex gap-3 text-xs text-muted/70">
                            <svg class="w-4 h-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Atomic Invalidation on Save
                        </li>
                        <li class="flex gap-3 text-xs text-muted/70">
                            <svg class="w-4 h-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Role-Based Differentiation
                        </li>
                        <li class="flex gap-3 text-xs text-muted/70">
                            <svg class="w-4 h-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Pagination & Filter Tracking
                        </li>
                    </ul>
                </div>

                <div class="bg-surface/30 rounded-[2.5rem] p-10 border border-sand/30">
                    <h4 class="text-sm uppercase tracking-widest font-bold text-foreground mb-6">Security & integrity</h4>
                    <p class="text-xs text-muted leading-relaxed">
                        Cache files are protected with serialized hash keys. Admin-only access ensures that invalidation can only be triggered by authorized personnel. 
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>
