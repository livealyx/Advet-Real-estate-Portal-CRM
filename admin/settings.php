<?php
// FILE: admin/settings.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo      = getPDO();
$settings = loadSettings($pdo);
$s = $settings; // shorthand
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($s) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .form-reveal { opacity: 0; transform: translateY(20px); animation: fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards; }
        @keyframes fadeIn { to { opacity: 1; transform: none; } }
        
        /* Custom Scrollbar for the whole page */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(137, 145, 120, 0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(137, 145, 120, 0.4); }

        /* Glassmorphism effects */
        .glass-card {
            background: rgba(var(--tw-colors-background-rgb), 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(var(--tw-colors-sand-rgb), 0.4);
        }

        /* Tab Transition */
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: tabFade .5s ease-out; }
        @keyframes tabFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

        /* Custom Toggle - Redesigned for Visibility */
        .toggle-checkbox:checked + .toggle-state { background-color: var(--tw-colors-accent-DEFAULT, #899178); }
        .toggle-checkbox:checked + .toggle-state .toggle-knob { transform: translateX(24px); }
        .toggle-state { background-color: rgba(var(--tw-colors-sand-rgb, 223, 216, 204), 0.6); }

        /* Custom Dropdown States */
        .dropdown-list.active { opacity: 1; transform: translateY(0); pointer-events: auto; }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-background flex text-foreground">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-6 sm:p-10 lg:p-16 overflow-y-auto">
    <div class="max-w-5xl mx-auto">
        
        <!-- Header Section -->
        <div class="mb-16 form-reveal flex flex-col md:flex-row md:items-end justify-between gap-8">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Settings</p>
                <h1 class="text-5xl lg:text-6xl font-serif font-light leading-none">General <span class="italic text-muted">Settings</span></h1>
                <p class="text-sm text-muted mt-6 max-w-md font-light leading-relaxed">Manage your website settings and basic configurations.</p>
            </div>
            
            <!-- Tab Navigation -->
            <nav class="flex bg-surface/50 p-1.5 rounded-[1.5rem] border border-sand/30 shadow-inner">
                <button onclick="switchTab('branding')" class="tab-btn active px-6 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest transition-all" data-tab="branding">Branding</button>
                <button onclick="switchTab('presence')" class="tab-btn px-6 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest transition-all text-muted/60 hover:text-muted" data-tab="presence">Presence</button>
                <button onclick="switchTab('system')" class="tab-btn px-6 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest transition-all text-muted/60 hover:text-muted" data-tab="system">System</button>
            </nav>
        </div>

        <form method="POST" action="<?= BASE ?>actions/save-settings.php" enctype="multipart/form-data" class="space-y-12 form-reveal" style="animation-delay:.1s" id="settingsForm">
            
            <!-- TAB: BRANDING -->
            <div id="tab-branding" class="tab-content active space-y-12">
                
                <!-- Logo & Favicon Bento -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Visual Identity Card: Redesigned for High-End Curation -->
                    <div class="lg:col-span-2 bg-white dark:bg-zinc-950 p-10 lg:p-14 rounded-[3.5rem] border border-sand/40 shadow-[0_20px_50px_rgba(0,0,0,0.02)] relative overflow-hidden group">
                        <!-- Abstract Background Element -->
                        <div class="absolute -top-24 -right-24 w-96 h-96 bg-accent/5 rounded-full blur-[100px] pointer-events-none group-hover:bg-accent/10 transition-all duration-1000"></div>
                        
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-16">
                                <div>
                                    <h2 class="text-[10px] uppercase tracking-[0.4em] font-black text-accent mb-2">Brand Assets</h2>
                                    <h3 class="text-3xl font-serif font-light text-foreground leading-tight">Design <span class="italic text-muted">Settings</span></h3>
                                </div>
                                <div class="flex items-center gap-3 px-5 py-2.5 bg-accent/[0.08] backdrop-blur-sm rounded-full border border-accent/15 shadow-[inset_0_1px_1px_rgba(255,255,255,0.4)] transition-all hover:bg-accent/10 group-hover:scale-105 duration-500">
                                    <div class="relative w-1.5 h-1.5">
                                        <div class="absolute inset-0 bg-accent rounded-full animate-ping opacity-40"></div>
                                        <div class="relative w-full h-full bg-accent rounded-full"></div>
                                    </div>
                                    <span class="text-[8px] font-black uppercase tracking-[0.25em] text-accent">Active System</span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-16">
                                <!-- Studio Icon Vault -->
                                <div class="space-y-8">
                                    <div class="flex justify-between items-center">
                                        <label class="text-[9px] uppercase tracking-[0.2em] font-bold text-muted/60">Studio Icon</label>
                                        <span class="text-[8px] font-medium text-muted/40 italic">SVG / PNG / JPG</span>
                                    </div>
                                    <div class="relative group/logo w-fit">
                                        <div class="w-40 h-40 rounded-[2.5rem] bg-surface/50 border border-sand/30 flex items-center justify-center overflow-hidden transition-all duration-500 group-hover/logo:border-accent/40 group-hover/logo:shadow-2xl group-hover/logo:shadow-accent/5 group-hover/logo:-translate-y-1 shadow-inner" id="logoPreview-container">
                                            <?php if (!empty($s['site_logo'])): ?>
                                                <img src="<?= imgUrl($s['site_logo']) ?>" class="max-w-[55%] max-h-[55%] object-contain transition-transform duration-1000 group-hover/logo:scale-110" id="logoPreview">
                                            <?php else: ?>
                                                <div id="logoPreview-placeholder" class="opacity-20 transition-opacity group-hover/logo:opacity-40">
                                                    <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 3l8 7L12 21l-8-11 8-7z"/><path d="M12 3v18"/></svg>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="site_logo" id="logoInput" data-preview-target="logoPreview" accept=".png,.jpg,.jpeg,.svg" class="hidden">
                                        <button type="button" onclick="document.getElementById('logoInput').click()" 
                                                class="absolute bottom-2 right-2 w-12 h-12 bg-foreground text-background rounded-2xl flex items-center justify-center shadow-2xl hover:scale-110 active:scale-95 transition-all border-4 border-background z-20 group-hover/logo:bg-accent group-hover/logo:text-white">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </button>
                                    </div>
                                    <p class="text-[8px] text-muted/50 leading-relaxed max-w-[160px]">Recommended: Minimum 512px square with transparent background.</p>
                                </div>

                                <!-- Favicon Vault -->
                                <div class="space-y-8">
                                    <div class="flex justify-between items-center">
                                        <label class="text-[9px] uppercase tracking-[0.2em] font-bold text-muted/60">Favicon Layer</label>
                                        <span class="text-[8px] font-medium text-muted/40 italic">ICO / PNG</span>
                                    </div>
                                    <div class="relative group/fav w-fit">
                                        <div class="w-40 h-40 rounded-[2.5rem] bg-surface/50 border border-sand/30 flex items-center justify-center overflow-hidden transition-all duration-500 group-hover/fav:border-accent/40 group-hover/fav:shadow-2xl group-hover/fav:shadow-accent/5 group-hover/fav:-translate-y-1 shadow-inner" id="faviconPreview-container">
                                            <?php if (!empty($s['site_favicon'])): ?>
                                                <img src="<?= imgUrl($s['site_favicon']) ?>" class="max-w-[35%] max-h-[35%] object-contain transition-transform duration-1000 group-hover/fav:scale-110" id="faviconPreview">
                                            <?php else: ?>
                                                <div id="faviconPreview-placeholder" class="text-[10px] font-black tracking-tighter text-muted opacity-20 group-hover/fav:opacity-40">.ICO</div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="site_favicon" id="faviconInput" data-preview-target="faviconPreview" accept=".ico,.png,.svg" class="hidden">
                                        <button type="button" onclick="document.getElementById('faviconInput').click()" 
                                                class="absolute bottom-2 right-2 w-12 h-12 bg-foreground text-background rounded-2xl flex items-center justify-center shadow-2xl hover:scale-110 active:scale-95 transition-all border-4 border-background z-20 group-hover/fav:bg-accent group-hover/fav:text-white">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </button>
                                    </div>
                                    <p class="text-[8px] text-muted/50 leading-relaxed max-w-[160px]">The browser tab identity. Small scale clarity is essential.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Naming Card: Redesigned for Consistency -->
                    <div class="bg-surface/30 p-10 lg:p-14 rounded-[3.5rem] border border-sand/40 flex flex-col justify-between relative overflow-hidden group">
                        <div class="relative z-10 space-y-10">
                            <div>
                                <h2 class="text-[9px] uppercase tracking-[0.4em] font-black text-accent mb-8">Nomenclature</h2>
                                
                                <div class="space-y-10">
                                    <div>
                                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Global Site Title</label>
                                        <input type="text" name="settings[site_name]" value="<?= e($s['site_name'] ?? 'Advet Buildwell') ?>"
                                               class="w-full px-0 bg-transparent border-b border-sand focus:border-accent py-2 text-2xl font-serif transition-all focus:placeholder-transparent"
                                               placeholder="Enter site title">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Editorial Tagline</label>
                                        <input type="text" name="settings[site_tagline]" value="<?= e($s['site_tagline'] ?? 'Architectural Curation') ?>"
                                               class="w-full px-0 bg-transparent border-b border-sand focus:border-accent py-2 text-sm font-medium tracking-wide text-muted transition-all"
                                               placeholder="The narrative hook">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">UI Logo Text</label>
                                        <input type="text" name="settings[site_logo_text]" value="<?= e($s['site_logo_text'] ?? ($s['site_name'] ?? 'Advet Buildwell')) ?>"
                                               class="w-full px-0 bg-transparent border-b border-sand focus:border-accent py-2 text-xs font-black tracking-[0.2em] uppercase transition-all"
                                               placeholder="Alternative branding">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-12 pt-10 border-t border-sand/20 relative z-10">
                            <div class="flex items-start gap-4">
                                <div class="w-1.5 h-1.5 rounded-full bg-accent mt-1.5 animate-pulse"></div>
                                <p class="text-[9px] text-muted/60 leading-relaxed uppercase tracking-[0.1em]">These identifiers drive SEO meta-protocols and primary navigation anchors across the ecosystem.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Appearance -->
                <div class="bg-background p-10 lg:p-16 rounded-[3.5rem] border border-sand/40 shadow-sm relative overflow-hidden">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12">
                        <div>
                            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-4">Homepage Banner Images</h2>
                            <p class="text-muted text-sm font-light max-w-sm">Main images shown on the homepage. Use high-quality images only.</p>
                        </div>
                        <div class="flex gap-4">
                            <button type="button" onclick="document.getElementById('heroInput').click()" 
                                    class="px-8 py-4 bg-accent text-white rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl shadow-accent/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 4v16m8-8H4"/></svg>
                                Add New Image
                            </button>
                            <button type="button" onclick="clearHero()" 
                                    class="px-6 py-4 bg-surface border border-sand text-muted rounded-2xl text-[10px] font-bold uppercase hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all">
                                Clear All
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6" id="heroPreviewGrid">
                        <?php 
                        $sliderImages = json_decode($s['site_hero_slider_images'] ?? '[]', true) ?: [];
                        if (!empty($sliderImages)): 
                            foreach($sliderImages as $idx => $img): 
                        ?>
                            <div class="relative w-full aspect-[4/5] rounded-[2rem] overflow-hidden group border border-sand/30 shadow-lg">
                                <img src="<?= imgUrl($img) ?>" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
                                <input type="hidden" name="existing_slider_images[]" value="<?= e($img) ?>">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <button type="button" onclick="this.closest('.relative').remove()" class="w-full py-2 bg-white/20 backdrop-blur-md rounded-xl text-[8px] font-bold uppercase text-white hover:bg-red-500/80 transition-colors">Discard</button>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else: 
                        ?>
                            <div class="col-span-full py-24 bg-surface/30 rounded-[3rem] border border-dashed border-sand/50 flex flex-col items-center justify-center text-center">
                                <div class="w-20 h-20 rounded-full bg-background flex items-center justify-center mb-6 shadow-sm border border-sand/20">
                                    <svg class="w-8 h-8 text-muted/20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                </div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-muted/40">Archive Empty</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="site_hero_slider_images[]" id="heroInput" data-preview-target="heroPreviewGrid" multiple accept=".png,.jpg,.jpeg,.webp" class="hidden">
                    
                    <!-- Environment Metrics -->
                    <div class="mt-12 pt-8 border-t border-sand/10 flex items-center gap-12">
                        <div class="flex items-center gap-4">
                            <div class="w-1.5 h-1.5 rounded-full bg-accent"></div>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-muted/60">Server Uplink: <span class="text-accent"><?= ini_get('upload_max_filesize') ?></span></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-1.5 h-1.5 rounded-full bg-accent"></div>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-muted/60">Post Capacity: <span class="text-accent"><?= ini_get('post_max_size') ?></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PRESENCE -->
            <div id="tab-presence" class="tab-content space-y-12">
                
                <!-- Studio Coordinates -->
                <div class="bg-background p-10 lg:p-14 rounded-[3.5rem] border border-sand/40 shadow-sm">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-12 flex items-center gap-3 border-b border-sand pb-6">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        Contact Details
                    </h2>
                    
                    <div class="space-y-10">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Office Address</label>
                            <textarea name="settings[studio_address]" rows="3"
                                      class="w-full px-8 py-6 bg-surface/30 border border-sand/40 rounded-[2rem] text-sm transition-all focus:bg-background focus:ring-4 focus:ring-accent/5 resize-none"><?= e($s['studio_address'] ?? "1042 Minimalist Way\nLos Angeles, CA 90026") ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-3">
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted ml-1">Mobile Number</label>
                                <input type="text" name="settings[studio_phone]" value="<?= e($s['studio_phone'] ?? '+1 (424) 000-0000') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background">
                            </div>
                            <div class="space-y-3">
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted ml-1">Phone Number</label>
                                <input type="text" name="settings[studio_landline]" value="<?= e($s['studio_landline'] ?? '') ?>"
                                       placeholder="+01 (000) 000-0000"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background">
                            </div>
                            <div class="space-y-3">
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted ml-1">Email Address</label>
                                <input type="email" name="settings[contact_email]" value="<?= e($s['contact_email'] ?? 'info@advetbuildwell.com') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background font-medium">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operating Rhythms -->
                <div class="bg-surface/20 p-10 lg:p-14 rounded-[3.5rem] border border-sand/40">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-12 flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Working Hours
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="p-8 bg-background rounded-[2.5rem] border border-sand/30 shadow-sm">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-4 opacity-40">Mon — Fri</p>
                            <input type="text" name="settings[hours_mon_fri]" value="<?= e($s['hours_mon_fri'] ?? '9:00 AM – 6:00 PM') ?>"
                                   class="w-full bg-transparent border-none p-0 text-sm font-medium focus:ring-0">
                        </div>
                        <div class="p-8 bg-background rounded-[2.5rem] border border-sand/30 shadow-sm">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-4 opacity-40">Saturday</p>
                            <input type="text" name="settings[hours_sat]" value="<?= e($s['hours_sat'] ?? '10:00 AM – 4:00 PM') ?>"
                                   class="w-full bg-transparent border-none p-0 text-sm font-medium focus:ring-0">
                        </div>
                        <div class="p-8 bg-background rounded-[2.5rem] border border-sand/30 shadow-sm">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-4 opacity-40">Sunday</p>
                            <input type="text" name="settings[hours_sun]" value="<?= e($s['hours_sun'] ?? 'By Appointment') ?>"
                                   class="w-full bg-transparent border-none p-0 text-sm font-medium focus:ring-0">
                        </div>
                    </div>
                </div>

                <!-- Social Ecosystem -->
                <div class="bg-background p-10 lg:p-14 rounded-[3.5rem] border border-sand/40 shadow-sm">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-6">Social Media</h2>
                    <p class="text-[10px] text-muted italic mb-10 ml-1 uppercase tracking-widest">Add your social media links. Leave blank if not used.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group/social relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-accent/30 font-bold text-xs uppercase tracking-tighter transition-colors group-focus-within/social:text-accent">INSTA /</span>
                            <input type="text" name="settings[social_instagram]" value="<?= e($s['social_instagram'] ?? '') ?>"
                                   placeholder="@handle"
                                   class="w-full pl-24 pr-8 py-5 bg-surface/30 border border-sand/30 rounded-[1.5rem] text-sm transition-all focus:bg-background">
                        </div>
                        <div class="group/social relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-accent/30 font-bold text-xs uppercase tracking-tighter transition-colors group-focus-within/social:text-accent">LINKED /</span>
                            <input type="text" name="settings[social_linkedin]" value="<?= e($s['social_linkedin'] ?? '') ?>"
                                   placeholder="company-id"
                                   class="w-full pl-24 pr-8 py-5 bg-surface/30 border border-sand/30 rounded-[1.5rem] text-sm transition-all focus:bg-background">
                        </div>
                        <div class="group/social relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-accent/30 font-bold text-xs uppercase tracking-tighter transition-colors group-focus-within/social:text-accent">FB /</span>
                            <input type="text" name="settings[social_facebook]" value="<?= e($s['social_facebook'] ?? '') ?>"
                                   class="w-full pl-24 pr-8 py-5 bg-surface/30 border border-sand/30 rounded-[1.5rem] text-sm transition-all focus:bg-background">
                        </div>
                        <div class="group/social relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-accent/30 font-bold text-xs uppercase tracking-tighter transition-colors group-focus-within/social:text-accent">TUBE /</span>
                            <input type="text" name="settings[social_youtube]" value="<?= e($s['social_youtube'] ?? '') ?>"
                                   class="w-full pl-24 pr-8 py-5 bg-surface/30 border border-sand/30 rounded-[1.5rem] text-sm transition-all focus:bg-background">
                        </div>
                        <div class="md:col-span-2 group/social relative">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-accent/40 font-bold text-xs uppercase tracking-tighter transition-colors group-focus-within/social:text-accent">SOCIALVYNK /</span>
                            <input type="text" name="settings[social_socialvynk]" value="<?= e($s['social_socialvynk'] ?? '') ?>"
                                   class="w-full pl-36 pr-8 py-6 bg-surface border border-accent/20 rounded-[1.5rem] text-sm font-bold text-accent transition-all focus:bg-background">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SYSTEM -->
            <div id="tab-system" class="tab-content space-y-12">
                
                <!-- Communication & Newsletter -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-background p-10 rounded-[3rem] border border-sand/40 shadow-sm space-y-10">
                        <div>
                            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8">Communications</h3>
                            <div class="flex items-center justify-between p-6 bg-surface/20 rounded-3xl border border-sand/20 transition-all hover:border-accent/20">
                                <div class="max-w-[200px]">
                                    <p class="text-xs font-bold uppercase tracking-widest leading-relaxed">Inquiry Notifications</p>
                                    <p class="text-[9px] text-muted mt-2 leading-relaxed">Broadcast alerts for every new direct inquiry.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="settings[inquiry_notifications]" value="0">
                                    <input type="checkbox" name="settings[inquiry_notifications]" value="1"
                                           <?= ($s['inquiry_notifications'] ?? '0') === '1' ? 'checked' : '' ?>
                                           class="sr-only peer toggle-checkbox">
                                    <div class="w-14 h-8 rounded-full transition-all duration-300 toggle-state flex items-center px-1">
                                        <div class="w-6 h-6 bg-white rounded-full shadow-sm transition-all duration-300 toggle-knob"></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8">Newsletter Stream</h3>
                            <div class="space-y-6">
                                <div class="flex items-center justify-between p-6 bg-surface/20 rounded-3xl border border-sand/20">
                                    <p class="text-xs font-bold uppercase tracking-widest">Active Broadcasting</p>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="settings[newsletter_enabled]" value="0">
                                        <input type="checkbox" name="settings[newsletter_enabled]" value="1"
                                               <?= ($s['newsletter_enabled'] ?? '1') === '1' ? 'checked' : '' ?>
                                               class="sr-only peer toggle-checkbox">
                                        <div class="w-14 h-8 rounded-full transition-all duration-300 toggle-state flex items-center px-1">
                                            <div class="w-6 h-6 bg-white rounded-full shadow-sm transition-all duration-300 toggle-knob"></div>
                                        </div>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Cadence / Frequency</label>
                                    <div class="relative" id="freqDropdown">
                                        <!-- Custom Trigger -->
                                        <button type="button" 
                                                onclick="toggleDropdown('freqDropdown')"
                                                class="w-full flex items-center justify-between pl-8 pr-8 py-5 bg-surface/40 border border-sand/30 rounded-[1.5rem] text-sm transition-all focus:ring-4 focus:ring-accent/5 cursor-pointer font-medium text-foreground outline-none group">
                                            <span id="freqSelectedText"><?php 
                                                $freqs = ['weekly'=>'Weekly Dispatch','biweekly'=>'Bi-Weekly Updates','monthly'=>'Monthly Compendium'];
                                                echo $freqs[$s['newsletter_frequency'] ?? 'weekly'];
                                            ?></span>
                                            <svg class="w-4 h-4 text-accent transition-transform group-[.active]:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                                        </button>
                                        
                                        <!-- Custom List -->
                                        <div class="absolute left-0 right-0 top-full mt-3 bg-background/80 backdrop-blur-2xl border border-sand/40 rounded-[2rem] shadow-2xl overflow-hidden opacity-0 translate-y-4 pointer-events-none transition-all z-50 dropdown-list">
                                            <?php foreach ($freqs as $val => $lab): ?>
                                                <div onclick="selectFreq('<?= $val ?>', '<?= $lab ?>')" 
                                                     class="px-8 py-5 text-sm font-medium text-muted hover:text-accent hover:bg-accent/5 cursor-pointer transition-all border-b border-sand/10 last:border-0">
                                                    <?= $lab ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <input type="hidden" name="settings[newsletter_frequency]" id="frequencyInput" value="<?= e($s['newsletter_frequency'] ?? 'weekly') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-background p-10 rounded-[3rem] border border-sand/40 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8">Security & Protocol</h3>
                            <div class="flex items-center justify-between p-8 bg-surface/20 rounded-[2.5rem] border border-sand/20 hover:border-red-200/50 transition-all group/sec">
                                <div class="max-w-[180px]">
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted group-hover/sec:text-red-500 transition-colors">MFA Protocol</p>
                                    <p class="text-[9px] text-muted mt-2 leading-relaxed italic">Secondary authentication required for administrative entry.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="settings[mfa_enabled]" value="0">
                                    <input type="checkbox" name="settings[mfa_enabled]" value="1"
                                           <?= ($s['mfa_enabled'] ?? '0') === '1' ? 'checked' : '' ?>
                                           class="sr-only peer toggle-checkbox">
                                    <div class="w-14 h-8 rounded-full transition-all duration-300 toggle-state flex items-center px-1">
                                        <div class="w-6 h-6 bg-white rounded-full shadow-sm transition-all duration-300 toggle-knob"></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="mt-8">
                             <div class="p-8 bg-accent/5 rounded-[2.5rem] border border-accent/10">
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-accent mb-4">Currency Matrix</label>
                                <div class="flex items-center gap-4">
                                    <button type="button" onclick="setCurrency('USD')" id="btn-USD" class="curr-btn flex-1 py-4 rounded-xl text-[10px] font-bold transition-all <?= ($s['currency'] ?? 'USD') === 'USD' ? 'bg-accent text-white shadow-lg' : 'bg-background text-muted border border-sand/40' ?>">USD ($)</button>
                                    <button type="button" onclick="setCurrency('INR')" id="btn-INR" class="curr-btn flex-1 py-4 rounded-xl text-[10px] font-bold transition-all <?= ($s['currency'] ?? '') === 'INR' ? 'bg-accent text-white shadow-lg' : 'bg-background text-muted border border-sand/40' ?>">INR (₹)</button>
                                </div>
                                <input type="hidden" name="settings[currency]" id="currencyInput" value="<?= e($s['currency'] ?? 'USD') ?>">
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Signatures -->
                <div class="bg-background p-10 lg:p-14 rounded-[3.5rem] border border-sand/40 shadow-sm">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10">Footer Signatures</h2>
                    <div class="space-y-8">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Copyright Line</label>
                            <input type="text" name="settings[site_copyright]" value="<?= e($s['site_copyright'] ?? '') ?>"
                                   placeholder="© <?= date('Y') ?> Advet Buildwell. All rights reserved."
                                   class="w-full px-8 py-5 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all italic focus:bg-background">
                            <p class="text-[8px] text-muted mt-4 ml-1 uppercase tracking-widest opacity-50">Appears at the absolute nadir of the digital interface.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Footer Save -->
            <div class="pt-12 sticky bottom-0 z-10">
                <div class="bg-background/40 backdrop-blur-3xl p-6 rounded-[2.5rem] border border-white/20 shadow-2xl flex gap-6 items-center">
                    <div class="hidden md:block flex-grow px-4">
                        <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-accent">Status: <span class="text-muted opacity-60">Standing By</span></p>
                    </div>
                    <button type="submit"
                            class="flex-grow md:flex-none md:min-w-[300px] py-6 bg-foreground text-background rounded-[1.5rem] text-[10px] font-bold uppercase tracking-[0.3em] transform hover:-translate-y-1 active:translate-y-0 transition-all shadow-xl shadow-foreground/10 hover:shadow-foreground/20">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
function switchTab(tab) {
    // Hide all
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active', 'text-foreground', 'bg-background', 'shadow-sm');
        b.classList.add('text-muted/60');
    });
    
    // Show target
    const target = document.getElementById('tab-' + tab);
    if (target) target.classList.add('active');
    
    // Update button
    const btn = document.querySelector(`[data-tab="${tab}"]`);
    if (btn) {
        btn.classList.add('active', 'text-foreground', 'bg-background', 'shadow-sm');
        btn.classList.remove('text-muted/60');
    }

    // Save choice for persistence if needed
    localStorage.setItem('settings_tab', tab);
}

function setCurrency(val) {
    document.getElementById('currencyInput').value = val;
    document.querySelectorAll('.curr-btn').forEach(b => {
        b.classList.remove('bg-accent', 'text-white', 'shadow-lg');
        b.classList.add('bg-background', 'text-muted', 'border', 'border-sand/40');
    });
    const active = document.getElementById('btn-' + val);
    active.classList.add('bg-accent', 'text-white', 'shadow-lg');
    active.classList.remove('bg-background', 'text-muted', 'border', 'border-sand/40');
}

function toggleDropdown(id) {
    const el = document.getElementById(id);
    const list = el.querySelector('.dropdown-list');
    const btn = el.querySelector('button');
    list.classList.toggle('active');
    btn.classList.toggle('active');
}

function selectFreq(val, lab) {
    document.getElementById('frequencyInput').value = val;
    document.getElementById('freqSelectedText').innerText = lab;
    const dropdown = document.getElementById('freqDropdown');
    dropdown.querySelector('.dropdown-list').classList.remove('active');
    dropdown.querySelector('button').classList.remove('active');
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#freqDropdown')) {
        const dropdown = document.getElementById('freqDropdown');
        if(dropdown) {
            dropdown.querySelector('.dropdown-list').classList.remove('active');
            dropdown.querySelector('button').classList.remove('active');
        }
    }
});

function clearHero() {
    if (confirm('Are you sure you want to purge the hero sequence?')) {
        const grid = document.getElementById('heroPreviewGrid');
        grid.innerHTML = '<input type="hidden" name="clear_hero" value="1">';
        document.getElementById('heroInput').value = '';
    }
}

// Persist tab on refresh
document.addEventListener('DOMContentLoaded', () => {
    const lastTab = localStorage.getItem('settings_tab') || 'branding';
    switchTab(lastTab);
});
</script>
</body>
</html>
