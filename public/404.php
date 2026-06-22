<?php
// FILE: public/404.php
session_start();
require_once '../config/db.php';

$solidNav  = true;
$pageTitle = '404 — Not Found';
require_once '../includes/header.php';
?>

<main class="flex-grow flex items-center justify-center min-h-[85vh] px-6 relative bg-[#FDFCF9] overflow-hidden">
    
    <!-- Sophisticated Light Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[70vw] h-[70vw] bg-accent/5 rounded-full blur-[100px]"></div>
    </div>

    <!-- Main Content -->
    <div class="max-w-3xl w-full text-center relative z-10 reveal mx-auto">
        <div class="mb-12 relative">
            <!-- Spectral 404 that won't cause overlap issues -->
            <h1 class="text-[12rem] md:text-[18rem] font-serif italic text-accent/5 leading-none select-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[-1]">404</h1>
            
            <p class="text-[10px] font-bold uppercase tracking-[0.6em] text-accent/60 mt-12 mb-6 block">Page Not Found</p>
            <h2 class="text-6xl md:text-8xl font-serif italic text-foreground font-light mb-8 leading-tight">Oops!</h2>
        </div>

        <p class="text-base text-muted font-light leading-relaxed mb-16 max-w-sm mx-auto">
            The page you are looking for does not exist or has been removed.
        </p>

        <!-- Recovery Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
            <a href="<?= BASE ?>" class="px-10 py-5 bg-foreground text-background text-[10px] font-bold uppercase tracking-widest rounded-2xl hover:bg-accent transition-all shadow-xl hover:-translate-y-1">
                Back to Origin
            </a>
            <a href="<?= BASE ?>properties" class="px-10 py-5 border border-sand text-muted text-[10px] font-bold uppercase tracking-widest rounded-2xl hover:bg-surface transition-all">
                View Portfolio
            </a>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
