<?php
// FILE: admin/add-project.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if (!in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'index.php'); exit;
}
$pdo = getPDO();
$settings = loadSettings($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = null;
$images = [];
$units = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
    
    if ($project) {
        $stmt = $pdo->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY display_order");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM project_units WHERE project_id = ? ORDER BY display_order");
        $stmt->execute([$id]);
        $units = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit' : 'Add' ?> Project | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body{-webkit-font-smoothing:antialiased;}
        .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}
        @keyframes fadeIn{to{opacity:1;transform:none}}
        .unit-row:hover .delete-unit { opacity: 1; }
        
        .studio-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23C5A267'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.25em;
            padding-right: 2.5rem !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .studio-select:focus {
            background-position: right 0.7rem center;
            color: var(--hero-accent);
        }
        
        /* Immediate removal for gallery images */
        .preview-item.marking-for-removal {
            opacity: 0 !important;
            transform: scale(0.8) !important;
            pointer-events: none !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-5xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4"><?= $id ? 'Edit' : 'Add' ?> Project</p>
            <h1 class="text-4xl font-serif font-light mb-4">
                <?= $id ? 'Refine' : 'Project' ?> <span class="italic text-muted">Details.</span>
            </h1>
            <p class="text-sm text-muted">Enter the basic information and configuration of the project.</p>
        </div>


        <form id="project-form" method="POST" action="<?= BASE ?>actions/save-project.php" enctype="multipart/form-data" class="space-y-12 form-reveal" style="animation-delay:.1s">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Project Title (Required separate focus) -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4">Project Title *</label>
                <input type="text" name="title" value="<?= e($project['title'] ?? '') ?>" required placeholder="e.g. Elysian Heights"
                        class="w-full px-8 py-5 bg-surface/30 border border-sand/30 rounded-2xl text-lg font-serif focus:border-accent transition-all">
            </section>

            <!-- 3. Overview -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8 border-b border-sand pb-4">Project Description</h2>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3">Detailed Description</label>
                    <?php 
                    $editor_name = 'description';
                    $editor_value = $project['description'] ?? '';
                    include '../components/editor/editor.php';
                    ?>
                </div>
            </section>

            <!-- 4. Unit Configurations -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex justify-between items-center mb-8 border-b border-sand pb-4">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent">Unit Details</h2>
                    <button type="button" onclick="addUnitRow()" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-accent/80 transition-all">+ Add Unit</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="units-table">
                        <thead>
                            <tr class="text-left text-[9px] uppercase tracking-[0.2em] text-muted/60 border-b border-sand">
                                <th class="pb-3 pl-4">Type</th>
                                <th class="pb-3">Size (sq.ft)</th>
                                <th class="pb-3">Price</th>
                                <th class="pb-3">Availability</th>
                                <th class="pb-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="units-body" class="divide-y divide-sand/20">
                            <?php if (empty($units)): ?>
                                <!-- JS will add initial row -->
                            <?php else: foreach ($units as $u): ?>
                                <tr class="unit-row group">
                                    <td class="py-4 pl-4"><input type="text" name="unit_type[]" value="<?= e($u['unit_type']) ?>" placeholder="2BHK" class="bg-transparent text-sm font-medium focus:outline-none w-full"></td>
                                    <td class="py-4"><input type="text" name="unit_size[]" value="<?= e($u['size']) ?>" placeholder="950" class="bg-transparent text-sm focus:outline-none w-full"></td>
                                    <td class="py-4"><input type="text" name="unit_price[]" value="<?= e($u['price']) ?>" placeholder="₹45L" class="bg-transparent text-sm focus:outline-none w-full"></td>
                                    <td class="py-4 text-xs">
                                        <select name="unit_availability[]" class="bg-transparent border-none focus:outline-none studio-select">
                                            <option value="Available" <?= $u['availability'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Limited" <?= $u['availability'] === 'Limited' ? 'selected' : '' ?>>Limited</option>
                                            <option value="Sold Out" <?= $u['availability'] === 'Sold Out' ? 'selected' : '' ?>>Sold Out</option>
                                        </select>
                                    </td>
                                    <td class="py-4 text-center">
                                        <button type="button" onclick="this.closest('tr').remove()" class="delete-unit opacity-0 group-hover:opacity-100 text-red-300 hover:text-red-500 transition-all">✕</button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 5. Visual Assets -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8 border-b border-sand pb-4">Project Images</h2>
                <div class="space-y-10">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4">Main Image</label>
                        <div class="relative w-40 h-28 rounded-2xl overflow-hidden border border-sand/40 bg-surface/50 flex items-center justify-center group" id="cover-container">
                            <img id="cover-preview" src="<?= !empty($project['cover_image']) ? imgUrl($project['cover_image']) : '' ?>" 
                                 class="w-full h-full object-cover <?= empty($project['cover_image']) ? 'hidden' : '' ?>">
                            
                            <div id="cover-preview-placeholder" class="text-center p-4 <?= !empty($project['cover_image']) ? 'hidden' : '' ?>">
                                <svg class="w-6 h-6 mx-auto text-accent/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-muted">Cover Image</span>
                            </div>

                            <!-- Remove Button for Hero -->
                            <button type="button" onclick="clearHeroCover()" id="hero-remove-btn" 
                                    class="absolute top-2 right-2 w-6 h-6 bg-foreground/80 text-background rounded-full items-center justify-center text-[10px] z-20 hover:bg-accent transition-all <?= empty($project['cover_image']) ? 'hidden' : 'flex' ?>">
                                ✕
                            </button>

                            <input type="file" name="cover_image" id="cover_image_input" accept="image/*" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   data-uploader-mode="mini" 
                                   data-preview-target="cover-preview"
                                   onchange="document.getElementById('hero-remove-btn').classList.replace('hidden','flex')">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4">Additional Images (Multiple allowed)</label>
                        <?php if (!empty($images)): ?>
                            <div class="preview-grid mb-8">
                                <?php foreach ($images as $img): ?>
                                    <div class="preview-item group" style="background-image: url('<?= imgUrl($img['image_path']) ?>')">
                                        <input type="checkbox" name="remove_images[]" value="<?= $img['id'] ?>" class="hidden">
                                        <button type="button" onclick="removeGalleryImage(this)" class="remove-btn group-hover:opacity-100 group-hover:scale-100 transition-all">
                                            ✕
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="gallery_images[]" multiple accept="image/*">
                    </div>
                </div>
            </section>
            
            <!-- 6. Call to Action (CTA) Configuration -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8 border-b border-sand pb-4">Call to Action</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Call Agent -->
                    <div class="space-y-4">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted px-2">Call Agent Number</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-accent/40 group-focus-within:text-accent transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-6 15h9M12 18.75h.008v.008H12v-.008z"/></svg>
                            </div>
                            <input type="text" name="agent_phone" value="<?= e($project['agent_phone'] ?? '') ?>" placeholder="+91 999 000 1122" 
                                   class="w-full pl-14 pr-6 py-5 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent transition-all">
                        </div>
                    </div>
                    <!-- WhatsApp -->
                    <div class="space-y-4">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted px-2">WhatsApp Number</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none text-accent/40 group-focus-within:text-accent transition-colors">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                            </div>
                            <input type="text" name="agent_whatsapp" value="<?= e($project['agent_whatsapp'] ?? '') ?>" placeholder="+91 999 000 1122" 
                                   class="w-full pl-14 pr-6 py-5 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:border-accent transition-all">
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- 7. Master Identity Grid (Editable Cards) -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 bg-white/40 backdrop-blur-sm border border-sand/30 rounded-[2.5rem] p-10 form-reveal">
                
                <!-- Card 1: Project Type (Custom Studio Select) -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/20 hover:border-accent/40 shadow-sm transition-all group relative" id="project-type-card">
                    <div class="flex items-center gap-4 mb-4 cursor-pointer" onclick="toggleCustomSelect('type-options')">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 21h18M5 21V7a2 2 0 012-2h4a2 2 0 012 2v14M13 21V3a2 2 0 012-2h4a2 2 0 012 2v18M7 9h2M7 13h2M7 17h2M15 5h2M15 9h2M15 13h2M15 17h2"/></svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Project Type</label>
                            <div class="flex items-center justify-between">
                                <span id="selected-type-label" class="text-sm font-medium text-foreground"><?= $project['project_type'] ?? 'Select Type' ?></span>
                                <svg class="w-4 h-4 text-accent/40 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden Input -->
                    <input type="hidden" name="project_type" id="project_type_input" value="<?= $project['project_type'] ?? 'Apartment' ?>">

                    <!-- Custom Options List -->
                    <div id="type-options" class="hidden absolute top-full left-0 w-full mt-2 bg-white/90 backdrop-blur-xl border border-sand/30 rounded-2xl shadow-2xl z-50 overflow-hidden transform origin-top transition-all py-2">
                        <?php foreach (['Apartment','Villa','Society','Plot','Flats'] as $t): ?>
                            <div class="px-6 py-3 text-sm font-medium text-muted hover:text-accent hover:bg-accent/5 cursor-pointer transition-colors" 
                                 onclick="selectProjectType('<?= $t ?>')">
                                <?= $t ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card 2: Area Range -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/20 hover:border-accent/40 shadow-sm transition-all group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 3H3v18h18V3zM9 3v18M15 3v18M3 9h18M3 15h18"/></svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Area Range (Sq.ft)</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="area_min" value="<?= e($project['area_min'] ?? '') ?>" placeholder="Min" class="w-16 bg-transparent text-sm font-medium focus:outline-none">
                                <span class="text-xs text-muted/40">—</span>
                                <input type="text" name="area_max" value="<?= e($project['area_max'] ?? '') ?>" placeholder="Max" class="w-16 bg-transparent text-sm font-medium focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Location -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/30 shadow-sm transition-all group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M13.6177 21.367C13.1841 21.773 12.6044 22 12.0011 22C11.3978 22 10.8182 21.773 10.3845 21.367C6.41302 17.626 1.09076 13.4469 3.68627 7.37966C5.08963 4.09916 8.45834 2 12.0011 2C15.5439 2 18.9126 4.09916 20.316 7.37966C22.9082 13.4393 17.599 17.6389 13.6177 21.367Z"/><path d="M15.5 11C15.5 12.933 13.933 14.5 12 14.5C10.067 14.5 8.5 12.933 8.5 11C8.5 9.067 10.067 7.5 12 7.5C13.933 7.5 15.5 9.067 15.5 11Z"/></svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Location</label>
                            <input type="text" name="location" value="<?= e($project['location'] ?? '') ?>" required placeholder="e.g. Sector 18, Rajasthan" class="w-full bg-transparent text-sm font-medium focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Card 4: Price Range -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/20 hover:border-accent/40 shadow-sm transition-all group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 21.5C16.4783 21.5 18.7175 21.5 20.1088 20.1088C21.5 18.7175 21.5 16.4783 21.5 12C21.5 7.52165 21.5 5.28248 20.1088 3.89124C18.7175 2.5 16.4783 2.5 12 2.5C7.52166 2.5 5.28249 2.5 3.89124 3.89124C2.5 5.28249 2.5 7.52166 2.5 12C2.5 16.4783 2.5 18.7175 3.89124 20.1088C5.28248 21.5 7.52165 21.5 12 21.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 10H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 7H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 7H11C12.6569 7 14 8.34315 14 10C14 11.6569 12.6569 13 11 13H8L14 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Price Range</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="price_min" value="<?= e($project['price_min'] ?? '') ?>" placeholder="min" class="w-16 bg-transparent text-sm font-medium focus:outline-none">
                                <span class="text-xs text-muted/40">—</span>
                                <input type="text" name="price_max" value="<?= e($project['price_max'] ?? '') ?>" placeholder="max" class="w-16 bg-transparent text-sm font-medium focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Possession Status (Custom Studio Select) -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/20 hover:border-accent/40 shadow-sm transition-all group relative" id="status-card">
                    <div class="flex items-center gap-4 mb-4 cursor-pointer" onclick="toggleCustomSelect('status-options')">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Status</label>
                            <div class="flex items-center justify-between">
                                <span id="selected-status-label" class="text-sm font-medium text-foreground"><?= $project['possession_status'] ?? 'Select Status' ?></span>
                                <svg class="w-4 h-4 text-accent/40 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden Input -->
                    <input type="hidden" name="possession_status" id="possession_status_input" value="<?= $project['possession_status'] ?? 'Ongoing Projects' ?>">

                    <!-- Custom Options List -->
                    <div id="status-options" class="hidden absolute top-full left-0 w-full mt-2 bg-white/90 backdrop-blur-xl border border-sand/30 rounded-2xl shadow-2xl z-50 overflow-hidden transform origin-top transition-all py-2">
                        <?php foreach (['Ready','Under Construction','New Launch','Ongoing Projects'] as $s): ?>
                            <div class="px-6 py-3 text-sm font-medium text-muted hover:text-accent hover:bg-accent/5 cursor-pointer transition-colors" 
                                 onclick="selectStatus('<?= $s ?>')">
                                <?= $s ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card 6: Unit Types (Summary) -->
                <div class="bg-background/60 p-6 rounded-3xl border border-sand/20 hover:border-accent/40 shadow-sm transition-all group">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-2 bg-surface rounded-xl text-accent/60 group-hover:bg-accent group-hover:text-white transition-all">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M11 3h2M11 7h2M11 11h2M11 15h2M11 19h2M7 3v18M17 3v18M3 7h18"/></svg>
                        </div>
                        <div class="flex-grow">
                            <label class="block text-[9px] uppercase tracking-widest text-muted font-bold mb-1">Configurations</label>
                            <p id="view-units-summary" class="text-xs font-medium text-foreground italic">
                                <?php 
                                    $types = array_unique(array_column($units, 'unit_type'));
                                    echo !empty($types) ? implode(', ', $types) : 'Auto-generating...';
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 7. Search Optimization -->
            <section class="bg-background p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-8 border-b border-sand pb-4">Search Optimization</h2>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3">SEO Title</label>
                        <input type="text" name="meta_title" value="<?= e($project['meta_title'] ?? '') ?>" placeholder="Search engine title..."
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3">Meta Description</label>
                        <textarea name="meta_description" class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm h-32 transition-all focus:border-accent"><?= e($project['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </section>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4 pb-20">
                <button type="submit" name="save_action" value="publish"
                        class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    <?= $id ? 'Update Project' : 'Launch Project' ?>
                </button>
                <button type="submit" name="save_action" value="draft"
                        class="px-10 py-5 border border-sand text-muted rounded-2xl text-xs font-bold uppercase tracking-[0.2em] hover:bg-surface transition-all">
                    Save as Archive
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Gallery Management
function removeGalleryImage(btn) {
    const item = btn.closest('.preview-item');
    if (!item) return;

    // 1. Mark for removal (CSS transition)
    item.classList.add('marking-for-removal');

    // 2. Handle Existing Image (PHP)
    const checkbox = item.querySelector('input[type="checkbox"]');
    if (checkbox) checkbox.checked = true;

    // 3. Handle Newly Uploaded Image (AJAX)
    const path = item.dataset.path;
    if (path) {
        // Find the hidden input created by uploader.js
        const hidden = document.querySelector(`input[type="hidden"][value="${path}"]`);
        if (hidden) hidden.remove();
    }

    // 4. Final Cleanup
    setTimeout(() => {
        if (path) item.remove(); // Fully remove new images
        else item.style.display = 'none'; // Keep existing in DOM for checkbox
    }, 400);
}

// Hero Cover Management
function clearHeroCover() {
    const input = document.getElementById('cover_image_input');
    const preview = document.getElementById('cover-preview');
    const placeholder = document.getElementById('cover-preview-placeholder');
    const btn = document.getElementById('hero-remove-btn');
    
    input.value = '';
    preview.src = '';
    preview.classList.add('hidden');
    placeholder.classList.remove('hidden');
    btn.classList.replace('flex','hidden');
    
    // Also remove any hidden input added by the async uploader for this specific field
    const hidden = document.querySelector(`input[type="hidden"][name="async_cover_image"]`);
    if(hidden) hidden.remove();
}

// Custom Select Logic
function toggleCustomSelect(id) {
    const el = document.getElementById(id);
    const cardId = id.includes('type') ? 'project-type-card' : 'status-card';
    el.classList.toggle('hidden');
    // Close on outside click
    const closer = (e) => {
        if (!e.target.closest('#' + cardId)) {
            el.classList.add('hidden');
            document.removeEventListener('click', closer);
        }
    };
    setTimeout(() => document.addEventListener('click', closer), 10);
}

function selectProjectType(val) {
    document.getElementById('project_type_input').value = val;
    document.getElementById('selected-type-label').innerText = val;
    document.getElementById('type-options').classList.add('hidden');
    updatePreview();
}

function selectStatus(val) {
    document.getElementById('possession_status_input').value = val;
    document.getElementById('selected-status-label').innerText = val;
    document.getElementById('status-options').classList.add('hidden');
    updatePreview();
}

// Live Preview Sync
const inputs = {
    title: document.getElementsByName('title')[0],
    type: document.getElementsByName('project_type')[0],
    p_min: document.getElementsByName('price_min')[0],
    p_max: document.getElementsByName('price_max')[0],
    a_min: document.getElementsByName('area_min')[0],
    a_max: document.getElementsByName('area_max')[0],
    loc: document.getElementsByName('location')[0],
    pos: document.getElementsByName('possession_status')[0]
};

const views = {
    type: document.getElementById('view-type'),
    area: document.getElementById('view-area'),
    location: document.getElementById('view-location'),
    price: document.getElementById('view-price'),
    status: document.getElementById('view-status'),
    units: document.getElementById('view-units')
};

const updatePreview = () => {
    views.type.innerText = inputs.type.value || 'Apartment';
    views.area.innerText = `${inputs.a_min.value || 0} - ${inputs.a_max.value || 0} Sq.ft.`;
    views.location.innerText = inputs.loc.value || 'Select Location';
    views.price.innerText = `${inputs.p_min.value || '₹0'} - ${inputs.p_max.value || '₹0'}`;
    views.status.innerText = inputs.pos.value || 'Ongoing Projects';
    
    // Sync Units
    const unitInputs = document.getElementsByName('unit_type[]');
    const types = Array.from(unitInputs).map(i => i.value).filter(v => v.trim() !== '');
    const uniqueTypes = [...new Set(types)];
    const summary = document.getElementById('view-units-summary');
    if(summary) summary.innerText = uniqueTypes.length > 0 ? uniqueTypes.join(', ') : 'Auto-generating...';
};

Object.values(inputs).forEach(el => {
    if(el) el.addEventListener('input', updatePreview);
});

// Watch for unit row additions/removals
document.addEventListener('input', (e) => {
    if(e.target.name === 'unit_type[]') updatePreview();
});
document.addEventListener('click', (e) => {
    if(e.target.classList.contains('delete-unit')) setTimeout(updatePreview, 10);
});

function addUnitRow() {
    const tbody = document.getElementById('units-body');
    const row = document.createElement('tr');
    row.className = 'unit-row group';
    row.innerHTML = `
        <td class="py-4 pl-4"><input type="text" name="unit_type[]" placeholder="e.g. 3BHK" class="bg-transparent text-sm font-medium focus:outline-none w-full"></td>
        <td class="py-4"><input type="text" name="unit_size[]" placeholder="e.g. 1550" class="bg-transparent text-sm focus:outline-none w-full"></td>
        <td class="py-4"><input type="text" name="unit_price[]" placeholder="e.g. ₹85L" class="bg-transparent text-sm focus:outline-none w-full"></td>
        <td class="py-4 text-xs">
            <select name="unit_availability[]" class="bg-transparent border-none focus:outline-none">
                <option value="Available">Available</option>
                <option value="Limited">Limited</option>
                <option value="Sold Out">Sold Out</option>
            </select>
        </td>
        <td class="py-4 text-center">
            <button type="button" onclick="this.closest('tr').remove()" class="delete-unit opacity-0 group-hover:opacity-100 text-red-300 hover:text-red-500 transition-all">✕</button>
        </td>
    `;
    tbody.appendChild(row);
    updatePreview();
}

// Add an initial row if none exists
window.addEventListener('load', () => {
    if (document.querySelectorAll('.unit-row').length === 0) {
        addUnitRow();
    }
});
</script>
</body>
</html>
