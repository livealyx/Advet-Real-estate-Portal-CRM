<?php
// FILE: public/project-detail.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: projects.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? AND status='active'");
$stmt->execute([$slug]);
$p = $stmt->fetch();

if (!$p) { header('Location: projects.php'); exit; }

$projectId = (int)$p['id'];

// Fetch Images
$stmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order");
$stmt->execute([$projectId]);
$gallery = $stmt->fetchAll();

// Fetch Units
$stmt = $pdo->prepare("SELECT * FROM project_units WHERE project_id = ? ORDER BY display_order");
$stmt->execute([$projectId]);
$units = $stmt->fetchAll();

// Calculate unique unit types for widget
$unitTypes = array_unique(array_column($units, 'unit_type'));
$unitTypesStr = !empty($unitTypes) ? implode(', ', $unitTypes) : 'TBA';

$pageTitle = $p['title'];
$pageDesc = $p['meta_description'] ?: $p['description'];
$pageKeywords = $p['project_type'] . ", " . $p['location'] . ", new project, " . ($settings['site_name'] ?? 'Advet Buildwell');

$extraHead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/fslightbox/3.4.1/index.min.js"></script>';

require_once '../includes/header.php';
?>

<main class="bg-background">
    <!-- 1. Header Section -->
    <section class="pt-48 pb-20 border-b border-sand/30">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 flex flex-col md:flex-row justify-between items-end gap-10">
            <div class="reveal">
                <nav class="flex items-center gap-3 text-[10px] uppercase tracking-[0.3em] text-accent mb-6">
                    <a href="projects.php" class="hover:text-foreground transition-colors">Projects</a>
                    <span class="opacity-30">/</span>
                    <span class="text-muted"><?= e($p['project_type']) ?></span>
                </nav>
                <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight"><?= e($p['title']) ?></h1>
                <div class="flex items-center gap-2 mt-6 text-muted text-sm tracking-wide">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= e($p['location']) ?>
                </div>
            </div>
            <div class="flex flex-col items-end gap-4 reveal" style="animation-delay: 0.1s">
                <div class="text-right">
                    <p class="text-[9px] uppercase tracking-[0.4em] text-accent mb-1 font-bold">Investment Starts</p>
                    <p class="text-3xl font-medium"><?= e($p['price_min']) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] uppercase tracking-[0.4em] text-muted mb-1 font-bold">Spatial Range</p>
                    <p class="text-lg font-light text-muted italic"><?= e($p['area_min']) ?> - <?= e($p['area_max']) ?> sq.ft</p>
                </div>
                <a href="#inquiry" class="mt-4 px-10 py-5 bg-foreground text-background rounded-full text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Contact Seller</a>
            </div>
        </div>
    </section>

    <!-- 2. Hero Image -->
    <section class="reveal" style="animation-delay: 0.2s">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 pt-16">
            <div class="relative aspect-[21/9] rounded-[3rem] overflow-hidden shadow-2xl group">
                <a data-fslightbox="gallery" href="<?= imgUrl($p['cover_image']) ?>">
                    <img src="<?= imgUrl($p['cover_image']) ?>" class="w-full h-full object-cover transition-transform duration-[3s] group-hover:scale-105">
                </a>
                <div class="absolute bottom-10 right-10 bg-white/20 backdrop-blur-md px-6 py-3 rounded-full text-[9px] font-bold uppercase tracking-[0.3em] text-white border border-white/20">
                    Hero Architecture
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Key Widgets -->
    <section class="py-24">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 reveal" style="animation-delay: 0.3s">
                <!-- Widget 1: Type -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M5 21V7a2 2 0 012-2h4a2 2 0 012 2v14M13 21V3a2 2 0 012-2h4a2 2 0 012 2v18M7 9h2M7 13h2M7 17h2M15 5h2M15 9h2M15 13h2M15 17h2"/></svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Project Type</p>
                    <p class="text-xs font-medium"><?= e($p['project_type']) ?></p>
                </div>
                <!-- Widget 2: Price -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 21.5C16.4783 21.5 18.7175 21.5 20.1088 20.1088C21.5 18.7175 21.5 16.4783 21.5 12C21.5 7.52165 21.5 5.28248 20.1088 3.89124C18.7175 2.5 16.4783 2.5 12 2.5C7.52166 2.5 5.28249 2.5 3.89124 3.89124C2.5 5.28249 2.5 7.52166 2.5 12C2.5 16.4783 2.5 18.7175 3.89124 20.1088C5.28248 21.5 7.52165 21.5 12 21.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 10H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 7H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 7H11C12.6569 7 14 8.34315 14 10C14 11.6569 12.6569 13 11 13H8L14 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Investment</p>
                    <p class="text-xs font-medium"><?= e($p['price_min']) ?> - <?= e($p['price_max']) ?></p>
                </div>
                <!-- Widget 3: Area -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 3H3v18h18V3zM9 3v18M15 3v18M3 9h18M3 15h18"/></svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Spatial Range</p>
                    <p class="text-xs font-medium"><?= e($p['area_min']) ?> - <?= e($p['area_max']) ?> sq.ft</p>
                </div>
                <!-- Widget 4: Status -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13.6177 21.367C13.1841 21.773 12.6044 22 12.0011 22C11.3978 22 10.8182 21.773 10.3845 21.367C6.41302 17.626 1.09076 13.4469 3.68627 7.37966C5.08963 4.09916 8.45834 2 12.0011 2C15.5439 2 18.9126 4.09916 20.316 7.37966C22.9082 13.4393 17.599 17.6389 13.6177 21.367Z"/><path d="M15.5 11C15.5 12.933 13.933 14.5 12 14.5C10.067 14.5 8.5 12.933 8.5 11C8.5 9.067 10.067 7.5 12 7.5C13.933 7.5 15.5 9.067 15.5 11Z"/></svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Geography</p>
                    <?php 
                        $locParts = explode(',', $p['location']);
                        $city = trim(end($locParts));
                    ?>
                    <p class="text-xs font-medium"><?= e($city) ?></p>
                </div>
                <!-- Widget 5: Possession -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Timeline</p>
                    <p class="text-xs font-medium"><?= e($p['possession_status']) ?></p>
                </div>
                <!-- Widget 6: Units -->
                <div class="bg-surface/40 backdrop-blur-sm p-6 rounded-3xl border border-sand/30 hover:shadow-xl transition-all">
                    <div class="p-2 bg-background/60 rounded-xl text-accent/60 mb-4 w-fit">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 3h2M11 7h2M11 11h2M11 15h2M11 19h2M7 3v18M17 3v18M3 7h18"/></svg>
                    </div>
                    <p class="text-[8px] uppercase tracking-[0.3em] text-muted mb-1 font-bold">Unit Sets</p>
                    <p class="text-xs font-medium"><?= e($unitTypesStr) ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Overview & Units -->
    <!-- 4. Overview & Units -->
    <section class="pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 grid grid-cols-1 lg:grid-cols-12 gap-20">
            <!-- Left: Overview & Inventory -->
            <div class="lg:col-span-7 reveal">
                <header class="mb-12">
                    <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">The Narrative</p>
                    <h2 class="text-4xl font-serif font-light italic">Project <span class="text-muted">Overview</span></h2>
                </header>
                <div class="prose prose-stone max-w-none text-muted font-light leading-relaxed text-lg">
                    <?= $p['description'] ?>
                </div>

            </div>

            <!-- Right: Sticky CTA Only -->
            <div class="lg:col-span-5 gallery-sidebar mt-10 lg:mt-0">
                <div id="inquiry" class="sticky top-40 bg-foreground text-background p-10 rounded-[2.5rem] shadow-2xl reveal" style="animation-delay: 0.5s">
                    <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-6">Concierge</p>
                    <h3 class="text-3xl font-serif font-light mb-8 italic">Interested in <span class="text-sand">this vision?</span></h3>
                    
                    <form action="<?= navPath('actions/submit-inquiry.php') ?>" method="POST" class="space-y-6">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        <div>
                            <input type="text" name="name" required placeholder="Full Name" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm focus:border-accent transition-all placeholder:text-white/20">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <input type="email" name="email" required placeholder="Email" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm focus:border-accent transition-all placeholder:text-white/20">
                            <input type="tel" name="phone" placeholder="Phone" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm focus:border-accent transition-all placeholder:text-white/20">
                        </div>
                        <div>
                            <textarea name="message" required placeholder="I'm interested in knowing more about <?= e($p['title']) ?>..." class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-sm h-32 focus:border-accent transition-all placeholder:text-white/20"></textarea>
                        </div>
                        <button type="submit" class="w-full py-5 bg-accent text-white rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl shadow-accent/20">
                            Request Portfolio
                        </button>
                    </form>

                    <div class="mt-8 pt-8 border-t border-white/10 flex flex-wrap gap-6 justify-center">
                        <?php 
                            $callNum = !empty($p['agent_phone']) ? $p['agent_phone'] : ($settings['contact_phone'] ?? '#');
                            $waNum = !empty($p['agent_whatsapp']) ? $p['agent_whatsapp'] : ($settings['contact_whatsapp'] ?? '');
                            $waClean = preg_replace('/\D/','', $waNum);
                        ?>
                        <a href="tel:<?= e($callNum) ?>" class="flex items-center gap-3 text-[10px] uppercase tracking-widest text-white/60 hover:text-white transition-colors">
                            <svg class="w-3 h-3 text-accent" fill="currentColor" viewBox="0 0 24 24"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/></svg>
                            Call Agent
                        </a>
                        <a href="https://wa.me/+<?= e($waClean) ?>" target="_blank" class="flex items-center gap-3 text-[10px] uppercase tracking-widest text-white/60 hover:text-white transition-colors">
                            <svg class="w-3 h-3 text-accent" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.94 3.659 1.437 5.706 1.438h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. Unit Inventory (Full Width Sheet) -->
    <section class="reveal pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <header class="mb-12 flex justify-between items-end">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Architecture</p>
                    <h2 class="text-4xl font-serif font-light italic">Unit <span class="text-muted">Inventory</span></h2>
                </div>
                <p class="text-[10px] text-muted italic hidden sm:block"><?= count($units) ?> Configurations Available</p>
            </header>

            <div class="space-y-4">
                <!-- Table Header -->
                <div class="hidden md:grid grid-cols-12 gap-8 px-10 py-5 text-[10px] uppercase tracking-widest font-bold text-muted/40">
                    <div class="col-span-4">Configuration</div>
                    <div class="col-span-3">Area</div>
                    <div class="col-span-3 text-right">Investment</div>
                    <div class="col-span-2 text-right">Status</div>
                </div>

                <?php if (empty($units)): ?>
                    <div class="bg-surface/30 rounded-[3rem] border border-sand/30 p-20 text-center text-muted italic text-sm">
                        Inventory list is being finalized.
                    </div>
                <?php else: foreach ($units as $u): ?>
                    <div class="bg-surface/20 backdrop-blur-sm rounded-[2.5rem] border border-sand/30 p-8 sm:p-10 hover:bg-white hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-500 group">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                            <div class="md:col-span-4">
                                <p class="text-[9px] uppercase tracking-widest text-accent mb-2 font-bold md:hidden">Configuration</p>
                                <h4 class="text-2xl font-serif font-light text-foreground group-hover:text-accent transition-colors"><?= e($u['unit_type']) ?></h4>
                            </div>
                            <div class="md:col-span-3">
                                <p class="text-[9px] uppercase tracking-widest text-muted mb-2 font-bold md:hidden">Spatial Size</p>
                                <div class="flex items-center gap-3 text-lg text-muted italic font-light">
                                    <svg class="w-5 h-5 opacity-20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 3H3v18h18V3zM9 3v18M15 3v18M3 9h18M3 15h18"/></svg>
                                    <?= e($u['size']) ?> <span class="text-[12px] uppercase not-italic">sq.ft</span>
                                </div>
                            </div>
                            <div class="md:col-span-3 md:text-right">
                                <p class="text-[9px] uppercase tracking-widest text-muted mb-2 font-bold md:hidden">Investment</p>
                                <p class="text-xl font-medium text-foreground"><?= e($u['price']) ?></p>
                            </div>
                            <div class="md:col-span-2 flex md:justify-end">
                                <?php $uc = match($u['availability']) { 'Available'=>'text-green-600 bg-green-50 border-green-100', 'Limited'=>'text-amber-600 bg-amber-50 border-amber-100', default=>'text-red-600 bg-red-50 border-red-100' }; ?>
                                <span class="px-6 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest border <?= $uc ?> shadow-sm">
                                    <?= e($u['availability']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </section>
    </section>

    <!-- 5. Visual Portfolio (Full Width Immersive) -->
    <section class="reveal pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <header class="mb-12">
                <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Space & Form</p>
                <h2 class="text-4xl font-serif font-light italic">Visual <span class="text-muted">Portfolio</span></h2>
            </header>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($gallery as $gi): ?>
                    <a data-fslightbox="gallery" href="<?= imgUrl($gi['image_path']) ?>" class="group relative aspect-[4/3] rounded-[3rem] overflow-hidden shadow-2xl border border-sand/30 bg-surface">
                        <img src="<?= imgUrl($gi['image_path']) ?>" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <div class="p-4 bg-white/20 backdrop-blur-md rounded-full text-white border border-white/20">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 5. Interactive Location Strategy -->
    <section class="pb-32" id="geography">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <header class="mb-12 text-left">
                <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Location Strategy</p>
                <h2 class="text-5xl font-serif font-light italic">The <span class="text-muted">Geography</span></h2>
            </header>
            
            <div style="background: white; border-radius: 40px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1); border: 1px solid rgba(223, 216, 204, 0.5);">
                <div style="width: 100%; height: 550px; position: relative; background: #f8f8f8;">
                    <iframe 
                        width="100%" 
                        height="100%" 
                        frameborder="0" 
                        src="https://maps.google.com/maps?q=<?= rawurlencode(trim($p['location'])) . ', India' ?>&t=&z=15&ie=UTF8&iwloc=A&output=embed"
                        style="border:0;" 
                        allowfullscreen=""
                        loading="lazy">
                    </iframe>
                </div>
                
                <div style="padding: 40px; display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                            <div style="width: 30px; height: 1px; background: #899178;"></div>
                            <span style="font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; color: #899178;">Strategic coordinates</span>
                        </div>
                        <h3 style="font-family: serif; font-size: 1.875rem; color: #2A2925; font-style: italic; margin-bottom: 5px;"><?= e($p['location']) ?></h3>
                        <p style="font-size: 10px; color: #6D685C; text-transform: uppercase; letter-spacing: 2px;">Elite Connectivity Corridor</p>
                    </div>
                    <div>
                        <a href="https://maps.google.com/maps?q=<?= rawurlencode(trim($p['location'])) . ', India' ?>" 
                           target="_blank" 
                           style="display: inline-flex; align-items: center; gap: 12px; padding: 18px 35px; background: #899178; color: white; border-radius: 15px; text-decoration: none; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s; box-shadow: 0 10px 20px -5px rgba(137, 145, 120, 0.4);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            Explore in Maps
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Related Properties Section -->
    <section class="pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <header class="mb-12 flex justify-between items-end reveal">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Discover More</p>
                    <h2 class="text-4xl font-serif font-light italic">Related <span class="text-muted">Properties</span></h2>
                </div>
                <div class="hidden sm:flex gap-4">
                    <button id="prevRelated" class="w-12 h-12 rounded-full border border-sand/30 flex items-center justify-center text-muted hover:bg-white hover:shadow-lg transition-all disabled:opacity-30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button id="nextRelated" class="w-12 h-12 rounded-full border border-sand/30 flex items-center justify-center text-muted hover:bg-white hover:shadow-lg transition-all disabled:opacity-30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </header>

            <!-- Horizontal Scroll Container -->
            <div id="relatedPropertiesContainer" class="flex gap-8 overflow-x-auto pb-12 snap-x snap-mandatory scroll-smooth hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                <!-- Skeletons / Loading State -->
                <?php for($i=0; $i<4; $i++): ?>
                <div class="related-skeleton min-w-[300px] sm:min-w-[350px] w-full sm:w-[350px] animate-pulse">
                    <div class="aspect-[4/3] bg-surface/50 rounded-[2.5rem] mb-6"></div>
                    <div class="h-6 bg-surface/50 rounded-full w-3/4 mb-4"></div>
                    <div class="h-4 bg-surface/50 rounded-full w-1/2"></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Empty State (Hidden by default) -->
            <div id="noRelatedProperties" class="hidden text-center py-20 bg-surface/20 rounded-[3rem] border border-dashed border-sand/50">
                <p class="text-muted italic font-serif">No similar listings were discovered at this coordinate.</p>
            </div>
        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('relatedPropertiesContainer');
    const noRelated = document.getElementById('noRelatedProperties');
    const prevBtn = document.getElementById('prevRelated');
    const nextBtn = document.getElementById('nextRelated');

    // Fetch related properties
    const params = new URLSearchParams({
        type: 'project',
        id: <?= json_encode($projectId) ?>,
        location: <?= json_encode($p['location']) ?>,
        category: <?= json_encode($p['project_type'] === 'Plot' ? 'Plot' : 'Flat/Apartment') ?>,
        price: '0'
    });

    console.log('Fetching related properties with params:', params.toString());

    fetch('<?= BASE ?>actions/get-related-properties.php?' + params.toString())
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw err; });
            }
            return res.json();
        })
        .then(response => {
            console.log('Related properties response:', response);
            if (response.success && response.data.length > 0) {
                renderProperties(response.data);
            } else {
                console.warn('No related properties found or success is false');
                container.classList.add('hidden');
                noRelated.classList.remove('hidden');
            }
        })
        .catch(err => {
            console.error('Failed to load related properties:', err);
            container.classList.add('hidden');
            noRelated.classList.remove('hidden');
        });

    function renderProperties(properties) {
        container.innerHTML = '';
        properties.forEach(prop => {
            const card = `
                <div class="group min-w-[300px] sm:min-w-[380px] w-full sm:w-[380px] snap-start flex flex-col bg-white rounded-[2.5rem] border border-sand/20 overflow-hidden hover:shadow-2xl transition-all duration-500">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="${prop.image}" class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105" alt="${prop.title}">
                        <div class="absolute top-5 left-5 flex gap-2">
                            <span class="bg-foreground text-background text-[8px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full">For ${prop.listing_type}</span>
                        </div>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <div class="flex justify-between items-start mb-4 gap-4">
                            <h3 class="text-xl font-serif group-hover:text-accent transition-colors line-clamp-1">${prop.title}</h3>
                            <span class="text-sm font-bold text-accent whitespace-nowrap">${prop.price}</span>
                        </div>
                        <p class="text-[10px] text-muted font-light flex items-center gap-1.5 mb-4 uppercase tracking-widest">
                            <svg class="w-3 h-3 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-8-7.5-8-12a8 8 0 1116 0c0 4.5-8 12-8 12z"/><circle cx="12" cy="11" r="2"/></svg>
                            ${prop.location}
                        </p>
                        <p class="text-xs text-muted/70 font-light line-clamp-2 mb-8 leading-relaxed italic">
                            ${prop.description}
                        </p>
                        <div class="mt-auto pt-6 border-t border-sand/20 flex justify-between items-center">
                            <div class="flex gap-4 text-[9px] uppercase font-bold tracking-widest text-muted/60">
                                <span>${prop.bedrooms} BD</span>
                                <span>${prop.sqft} SQFT</span>
                            </div>
                            <a href="${prop.url}" class="text-[9px] font-bold uppercase tracking-[0.2em] text-foreground hover:text-accent flex items-center gap-2 transition-colors">
                                View Details
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', card);
        });

        updateButtons();
    }

    // Carousel Logic
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => {
            container.scrollBy({ left: -400, behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', () => {
            container.scrollBy({ left: 400, behavior: 'smooth' });
        });
        container.addEventListener('scroll', updateButtons);
    }

    function updateButtons() {
        if (!prevBtn || !nextBtn) return;
        prevBtn.disabled = container.scrollLeft <= 0;
        nextBtn.disabled = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
    }
});
</script>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
#relatedPropertiesContainer {
    -webkit-overflow-scrolling: touch;
}
</style>


<style>
.map-wrapper iframe {
    width: 100% !important;
    height: 100% !important;
    border: 0 !important;
    filter: grayscale(1);
    transition: all 1s cubic-bezier(0.4, 0, 0.2, 1);
}
.group:hover .map-wrapper iframe {
    filter: grayscale(0);
}
.gallery-sidebar { height: fit-content; }
.unit-row td { border-color: rgba(223, 216, 204, 0.1); }
.prose h2 { font-family: serif; font-style: italic; font-weight: 300; font-size: 2rem; margin-top: 2rem; margin-bottom: 1rem; color: var(--foreground); }
.prose p { margin-bottom: 1.5rem; }
.prose ul { list-style-type: none; padding-left: 0; }
.prose li { position: relative; padding-left: 1.5rem; margin-bottom: 0.5rem; }
.prose li::before { content: '•'; position: absolute; left: 0; color: var(--hero-accent); font-weight: bold; }
</style>

<?php require_once '../includes/footer.php'; ?>
