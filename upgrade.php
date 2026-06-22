<?php
// FILE: upgrade.php
// A secure utility to run migration SQL on the live server.
// Usage: Visit domain.com/upgrade.php after uploading. 
// IMPORTANT: Delete this file immediately after successful execution.

session_start();
require_once __DIR__ . '/config/db.php';

// Simple Auth Check: Only Admins can run this
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
        <title>Restricted Access | Advet Studio</title>
        <style>body{background:#F4F1ED; font-family:"Inter",sans-serif;}</style>
    </head>
    <body class="flex items-center justify-center min-h-screen p-6 text-[#2A2925]">
        <div class="max-w-md w-full bg-white/60 backdrop-blur-xl border border-[#DFD8CC] p-12 rounded-[2.5rem] shadow-2xl text-center">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-[#899178] mb-6">Security Protocol</p>
            <h2 class="text-3xl font-serif italic mb-6">Restricted <span class="text-[#6D685C]">Access</span></h2>
            <p class="text-sm text-[#6D685C] leading-relaxed mb-10">Please authenticate as an administrator to execute system-level migrations.</p>
            <a href="auth/login.php" class="inline-block px-10 py-4 bg-[#2A2925] text-[#FDFCF9] rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:scale-105 transition-all shadow-xl">Authenticate Now</a>
        </div>
    </body>
    </html>');
}

$pdo = getPDO();
$sqlFile = __DIR__ . '/migrate.sql';

if (!file_exists($sqlFile)) {
    die("Migration file (migrate.sql) not found in the root directory.");
}

$sql = file_get_contents($sqlFile);
$error = null;
$success = false;

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Note: exec() can run multiple queries if the driver supports it. 
    $pdo->exec($sql);
    $success = true;
} catch (PDOException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Migration | Advet Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@1,300;1,400&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { background-color: #F4F1ED; font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
        .font-serif { font-family: "Cormorant Garamond", serif; }
        .glass { background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(223, 216, 204, 0.4); }
        .reveal { opacity: 0; transform: translateY(20px); animation: reveal 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
        @keyframes reveal { to { opacity: 1; transform: translateY(0); } }
        .draw-check { stroke-dasharray: 100; stroke-dashoffset: 100; animation: draw 1s 0.5s ease-out forwards; }
        @keyframes draw { to { stroke-dashoffset: 0; } }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-[#2A2925]">

    <div class="max-w-2xl w-full glass rounded-[3rem] p-12 md:p-16 shadow-2xl relative overflow-hidden reveal">
        <!-- Floating Accent -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#899178]/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-[#B4A28B]/10 rounded-full blur-3xl"></div>

        <div class="relative z-10 text-center">
            
            <?php if ($success): ?>
                <!-- Success State -->
                <div class="mb-10 inline-flex items-center justify-center w-24 h-24 rounded-full bg-[#899178]/10 border border-[#899178]/20">
                    <svg class="w-10 h-10 text-[#899178]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path class="draw-check" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-[#899178] mb-4">Evolution Complete</p>
                <h1 class="text-4xl md:text-5xl font-serif italic mb-6">Database <span class="text-[#6D685C]">Restored.</span></h1>
                
                <p class="text-sm text-[#6D685C] leading-relaxed max-w-md mx-auto mb-12">
                    The architectural refinement was successful. All schemas, CRM modules, and branding protocols have been synchronized with the latest platform core.
                </p>

                <div class="p-8 bg-[#2A2925] rounded-[2rem] text-left mb-12">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 w-8 h-8 rounded-full bg-red-400/20 flex items-center justify-center text-red-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-red-400 mb-1">Critical Security Protocol</p>
                            <p class="text-[11px] text-[#FDFCF9]/60 leading-relaxed italic">
                                To prevent unauthorized system manipulation, you must manually delete <code class="text-white">upgrade.php</code> and <code class="text-white">migrate.sql</code> from your root directory immediately.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="admin/dashboard.php" class="px-10 py-5 bg-[#2A2925] text-[#FDFCF9] rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl">Studio Dashboard</a>
                    <a href="admin/settings.php" class="px-10 py-5 border border-[#2A2925]/10 text-[#2A2925] rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/40 transition-all">Brand Settings</a>
                </div>

            <?php else: ?>
                <!-- Error State -->
                <div class="mb-10 inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-50 border border-red-100">
                    <svg class="w-10 h-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-red-400 mb-4">Migration Interrupted</p>
                <h1 class="text-4xl md:text-5xl font-serif italic mb-6">Synchronization <span class="text-red-300">Failed.</span></h1>
                
                <div class="bg-red-50/50 border border-red-100 rounded-2xl p-6 text-left mb-10 overflow-hidden font-mono text-[10px] leading-relaxed text-red-700">
                    <?= htmlspecialchars($error) ?>
                </div>

                <p class="text-xs text-[#6D685C] mb-10 leading-relaxed italic">
                    Verify your database credentials in <code class="text-[#2A2925] font-bold">config/db.php</code> and ensure the SQL syntax in your migration file is compatible with your server environment.
                </p>

                <a href="upgrade.php" class="inline-block px-10 py-5 bg-[#2A2925] text-[#FDFCF9] rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl">Retry Migration</a>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>
