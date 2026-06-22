<?php
/**
 * FILE: includes/upload-sheet.php
 * Premium Segmented Upload Progress Modal
 * Uses Tailwind CSS for styling and Vanilla JS for state management.
 */
?>
<div id="advet-upload-sheet" 
     class="fixed inset-0 z-[9999] flex items-center justify-center invisible opacity-0 transition-all duration-500 pointer-events-none"
     aria-modal="true" 
     role="dialog">
    
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-foreground/30 backdrop-blur-xl transition-opacity duration-500" id="upload-sheet-backdrop"></div>
    
    <!-- Modal Card -->
    <div class="relative w-[90%] max-w-md bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-2xl border border-sand/30 dark:border-white/10 overflow-hidden transform scale-90 translate-y-10 transition-all duration-500 ease-out" id="upload-sheet-card">
        
        <!-- Header -->
        <div class="px-8 pt-8 pb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-serif text-foreground dark:text-white leading-tight">Syncing Visuals</h3>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-accent mt-1">Gallery Curation in Progress</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-surface dark:bg-zinc-800 flex items-center justify-center border border-sand/20">
                    <svg class="w-5 h-5 text-accent animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Files List Scroll Area -->
        <div id="upload-sheet-list" class="px-8 py-4 space-y-4 max-h-[350px] overflow-y-auto custom-scrollbar">
            <!-- Rows will be injected here via JavaScript -->
        </div>

        <!-- Dynamic Footer -->
        <div class="px-8 py-6 bg-surface/30 dark:bg-black/20 border-t border-sand/10 dark:border-white/5">
            <div class="flex items-center justify-between">
                <span id="upload-sheet-footer-text" class="text-[10px] font-bold uppercase tracking-widest text-muted dark:text-zinc-500">
                    Establishing secure uplink...
                </span>
                <span id="upload-sheet-counter" class="text-[10px] font-bold text-accent">0 / 0</span>
            </div>
        </div>
    </div>
</div>

<template id="upload-row-template">
    <div class="flex items-center gap-4 group animate-in slide-in-from-bottom-2 duration-300 row-item-container">
        <!-- Icon/Preview Square -->
        <div class="w-12 h-12 rounded-xl bg-surface dark:bg-zinc-800 flex items-center justify-center shrink-0 border border-sand/20 overflow-hidden relative">
            <img class="w-full h-full object-cover hidden row-preview" src="" alt="">
            <div class="row-icon-placeholder text-accent">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
            </div>
        </div>

        <!-- Meta and Progress -->
        <div class="flex-grow min-w-0">
            <div class="flex justify-between items-center mb-1.5">
                <h4 class="text-xs font-semibold truncate text-foreground dark:text-zinc-200 row-filename">image.jpg</h4>
                <span class="text-[9px] font-black uppercase tracking-tighter row-status-text">Waiting</span>
            </div>
            
            <!-- Segmented Dot Bar (10 dots) -->
            <div class="flex gap-1 row-dot-bar">
                <?php for($i=0; $i<10; $i++): ?>
                <div class="w-1.5 h-1.5 rounded-full bg-sand/30 dark:bg-zinc-700 transition-all duration-300 dot-item"></div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- End Status Icon -->
        <div class="shrink-0 row-status-icon-container">
            <div class="w-5 h-5 rounded-full flex items-center justify-center bg-sand/10 dark:bg-zinc-800 text-muted transition-colors duration-300 row-status-icon-bg">
                <svg class="w-3.5 h-3.5 status-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path class="svg-path" stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
        </div>
    </div>
</template>

<style>
/* Custom animations for the progress sheet */
#advet-upload-sheet.active {
    visibility: visible;
    opacity: 1;
    pointer-events: auto;
}
#advet-upload-sheet.active #upload-sheet-card {
    transform: scale(1) translateY(0);
}

.dot-pulse {
    animation: dotPulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes dotPulse {
    0%, 100% { opacity: 1; transform: scale(1.3); }
    50% { opacity: .4; transform: scale(0.9); }
}

.custom-scrollbar { scrollbar-width: thin; scrollbar-color: rgba(137, 145, 120, 0.4) transparent; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(137, 145, 120, 0.4); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(137, 145, 120, 0.8); }
</style>

<link rel="stylesheet" href="<?= BASE ?>assets/css/uploader.css">
<script src="<?= BASE ?>assets/js/uploader.js"></script>
