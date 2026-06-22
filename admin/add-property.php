<?php
// FILE: admin/add-property.php
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
$agents = [];
if ($_SESSION['user']['role'] === 'admin') {
    $agents = $pdo->query("SELECT id, name FROM users WHERE role = 'agent' ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body{-webkit-font-smoothing:antialiased;} 
        .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}
        @keyframes fadeIn{to{opacity:1;transform:none}} 
        input:focus,textarea:focus,select:focus{outline:none;border-color:#899178;}
        /* Custom Dropdown */
        .studio-dropdown { position: relative; }
        .studio-dropdown.is-open { z-index: 50; }
        .studio-dropdown.is-open .dropdown-list {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        .studio-dropdown.is-open .dropdown-chevron {
            transform: rotate(180deg);
            color: #899178;
        }
        .studio-dropdown.is-open .dropdown-trigger {
            border-color: #899178;
            background-color: #fff;
        }
        .dropdown-item {
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            padding-left: 2rem;
            background-color: rgba(137, 145, 120, 0.05);
            color: #899178;
        }
        .dropdown-item.is-selected {
            background-color: #899178;
            color: #fff;
        }
        /* Hide number input spinners */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-4xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Property Management</p>
            <h1 class="text-4xl md:text-5xl font-serif font-light mb-4">Add New <span class="italic text-muted">Property.</span></h1>
            <p class="text-sm text-muted max-w-xl">Every listing must meet our basic quality standards for structure and usability.</p>
        </div>

        <form id="property-form" method="POST" action="<?= BASE ?>actions/save-property.php" enctype="multipart/form-data" class="space-y-10 form-reveal" style="animation-delay:.1s">

            <!-- Basic Details -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Basic Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Property Title *</label>
                        <input type="text" name="title" required placeholder="e.g. The Obsidian Villa"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Location *</label>
                        <input type="text" name="location" required placeholder="e.g. Silverlake Hills, CA"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Asking Price *</label>
                        <input type="number" name="price" required min="0" step="0.01" placeholder="0.00"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Status</label>
                        <div class="studio-dropdown" data-name="status">
                            <input type="hidden" name="status" value="active">
                            <div class="dropdown-trigger w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all flex items-center justify-between cursor-pointer hover:bg-surface/50">
                                <span class="current-value text-foreground font-medium">Active</span>
                                <svg class="dropdown-chevron w-4 h-4 text-muted/50 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <div class="dropdown-list absolute top-[calc(100%+0.5rem)] left-0 right-0 bg-white border border-sand/30 rounded-2xl shadow-xl opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50 overflow-hidden">
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer is-selected" data-value="active">Active</div>
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="draft">Draft</div>
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="sold">Sold</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Category *</label>
                        <div class="studio-dropdown" data-name="category">
                            <input type="hidden" name="category" value="Flat/Apartment" required>
                            <div class="dropdown-trigger w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all flex items-center justify-between cursor-pointer hover:bg-surface/50">
                                <span class="current-value text-foreground font-medium">Flat/Apartment</span>
                                <svg class="dropdown-chevron w-4 h-4 text-muted/50 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <div class="dropdown-list absolute top-[calc(100%+0.5rem)] left-0 right-0 bg-white border border-sand/30 rounded-2xl shadow-xl opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50 overflow-hidden">
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer is-selected" data-value="Flat/Apartment">Flat/Apartment</div>
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="Plot">Plot</div>
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="Commercial">Commercial</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Listing Type *</label>
                        <div class="studio-dropdown" data-name="listing_type">
                            <input type="hidden" name="listing_type" value="Buy" required>
                            <div class="dropdown-trigger w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all flex items-center justify-between cursor-pointer hover:bg-surface/50">
                                <span class="current-value text-foreground font-medium">Buy/Sell</span>
                                <svg class="dropdown-chevron w-4 h-4 text-muted/50 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                            </div>
                            <div class="dropdown-list absolute top-[calc(100%+0.5rem)] left-0 right-0 bg-white border border-sand/30 rounded-2xl shadow-xl opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50 overflow-hidden">
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer is-selected" data-value="Buy">Buy/Sell</div>
                                <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="Rent">Rent / Lease</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($_SESSION['user']['role'] === 'admin' && !empty($agents)): ?>
                <div class="mt-8 border-t border-sand/30 pt-8">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Assign to Agent (Optional)</label>
                    <div class="studio-dropdown" data-name="agent_id">
                        <input type="hidden" name="agent_id" value="">
                        <div class="dropdown-trigger w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all flex items-center justify-between cursor-pointer hover:bg-surface/50">
                            <span class="current-value text-foreground font-medium">-- No Agent (Studio Owned) --</span>
                            <svg class="dropdown-chevron w-4 h-4 text-muted/50 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                        <div class="dropdown-list absolute top-[calc(100%+0.5rem)] left-0 right-0 bg-white border border-sand/30 rounded-2xl shadow-xl opacity-0 translate-y-2 pointer-events-none transition-all duration-300 z-50 overflow-hidden max-h-64 overflow-y-auto">
                            <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer is-selected" data-value="">-- No Agent (Studio Owned) --</div>
                            <?php foreach ($agents as $ag): ?>
                            <div class="dropdown-item px-6 py-3.5 text-sm cursor-pointer" data-value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Project Selection -->
                <div class="mt-8 border-t border-sand/30 pt-8 relative">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Associate with Project (Optional)</label>
                    <div class="relative group">
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl px-5 group-focus-within:border-accent transition-all">
                            <svg class="w-4 h-4 text-muted/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="project-search" placeholder="Search and select a project..."
                                   class="w-full bg-transparent text-sm py-4 px-4 outline-none placeholder:text-muted/30 font-medium">
                            <button type="button" id="clear-project" class="hidden text-muted/30 hover:text-accent">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="project_id" id="project-id-input">
                        
                        <!-- Autocomplete Dropdown -->
                        <div id="project-results" class="absolute top-full left-0 right-0 mt-3 bg-white rounded-[2rem] shadow-2xl border border-sand/10 opacity-0 translate-y-4 pointer-events-none transition-all z-50 py-3 overflow-hidden">
                            <!-- Results injected via JS -->
                        </div>
                    </div>
                    <p class="mt-3 text-[10px] text-muted/50 italic ml-1">Linking to a project will show this property on the project's dedicated page.</p>
                </div>
            </div>

            <!-- Property Details -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Property Details</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Bedrooms</label>
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl overflow-hidden group focus-within:border-accent transition-all">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="minus" data-target="bedrooms">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                            </button>
                            <input type="number" name="bedrooms" id="bedrooms" min="0" max="30" value="0"
                                   class="w-full bg-transparent text-center text-sm py-4 outline-none font-bold text-foreground">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="plus" data-target="bedrooms">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Bathrooms</label>
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl overflow-hidden group focus-within:border-accent transition-all">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="minus" data-target="bathrooms">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                            </button>
                            <input type="number" name="bathrooms" id="bathrooms" min="0" max="30" value="0"
                                   class="w-full bg-transparent text-center text-sm py-4 outline-none font-bold text-foreground">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="plus" data-target="bathrooms">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Size (Sq.ft)</label>
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl overflow-hidden group focus-within:border-accent transition-all">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="minus" data-target="sqft" data-step="50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                            </button>
                            <input type="number" name="sqft" id="sqft" min="0" value="0"
                                   class="w-full bg-transparent text-center text-sm py-4 outline-none font-bold text-foreground">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="plus" data-target="sqft" data-step="50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Balcony</label>
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl overflow-hidden group focus-within:border-accent transition-all">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="minus" data-target="balcony">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                            </button>
                            <input type="number" name="balcony" id="balcony" min="0" max="10" value="0"
                                   class="w-full bg-transparent text-center text-sm py-4 outline-none font-bold text-foreground">
                            <button type="button" class="px-4 py-4 text-muted/40 hover:text-accent transition-all qty-btn" data-action="plus" data-target="balcony">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="col-span-2 md:col-span-3">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Flat Type</label>
                        <div class="flex flex-wrap md:flex-nowrap bg-surface/30 border border-sand/30 rounded-[1.5rem] p-1.5 gap-1.5">
                            <?php foreach (['Raw','Semi-Furnished','Fully-Furnished'] as $ft): ?>
                            <label class="flex-1 min-w-[100px]">
                                <input type="radio" name="flat_type" value="<?= $ft ?>" class="hidden peer" <?= $ft === 'Raw' ? 'checked' : '' ?>>
                                <div class="py-3.5 text-center text-[10px] font-bold uppercase tracking-[0.2em] rounded-2xl cursor-pointer transition-all duration-300 peer-checked:bg-foreground peer-checked:text-background peer-checked:shadow-lg hover:bg-sand/20">
                                    <?= $ft ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Additional Details</h2>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Property Description *</label>
                    <?php 
                    $editor_name = 'description';
                    $editor_value = ''; 
                    include '../components/editor/editor.php'; 
                    ?>
                </div>
            </div>

            <!-- Property Images -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Property Images</h2>
                <div class="space-y-8">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Featured Image <span class="text-muted/50 normal-case tracking-normal font-normal">(primary cover)</span></label>
                        <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/avif"
                               class="block w-full text-sm text-muted file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-surface file:text-foreground hover:file:bg-sand file:transition-all cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Gallery Images <span class="text-muted/50 normal-case tracking-normal font-normal">(multiple allowed)</span></label>
                        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp,image/avif" multiple
                               class="block w-full text-sm text-muted file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-surface file:text-foreground hover:file:bg-sand file:transition-all cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4 pb-20">
                <button type="submit" name="status_override" value="active"
                        class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    Publish Property
                </button>
                <button type="submit" name="status_override" value="draft"
                        class="px-10 py-5 border border-sand text-muted rounded-2xl text-xs font-bold uppercase tracking-[0.2em] hover:bg-surface transition-all">
                    Save to Drafts
                </button>
            </div>
        </form>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', () => {

    // Project Autocomplete Logic
    const pSearch = document.getElementById('project-search');
    const pResults = document.getElementById('project-results');
    const pIdInput = document.getElementById('project-id-input');
    const pClear = document.getElementById('clear-project');
    let pTimeout;

    pSearch.addEventListener('input', () => {
        clearTimeout(pTimeout);
        const q = pSearch.value.trim();
        if (q.length < 2) {
            pResults.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
            return;
        }

        pTimeout = setTimeout(() => {
            fetch(`<?= BASE ?>actions/search-projects.php?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    pResults.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(p => {
                            const item = document.createElement('div');
                            item.className = 'px-8 py-3.5 flex items-center gap-4 cursor-pointer hover:bg-surface transition-all';
                            item.innerHTML = `
                                <img src="${p.image}" class="w-10 h-10 rounded-xl object-cover border border-sand/20">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-widest text-foreground">${p.text}</p>
                                    <p class="text-[9px] text-muted uppercase tracking-wider">${p.subtext}</p>
                                </div>
                            `;
                            item.onclick = () => {
                                pSearch.value = p.text;
                                pIdInput.value = p.id;
                                pResults.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                                pClear.classList.remove('hidden');
                                pSearch.readOnly = true;
                                pSearch.classList.add('text-accent');
                            };
                            pResults.appendChild(item);
                        });
                        pResults.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    } else {
                        pResults.innerHTML = '<div class="px-8 py-4 text-xs text-muted italic">No projects found matching your search.</div>';
                        pResults.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    }
                });
        }, 300);
    });

    pClear.onclick = () => {
        pSearch.value = '';
        pIdInput.value = '';
        pSearch.readOnly = false;
        pSearch.classList.remove('text-accent');
        pClear.classList.add('hidden');
        pResults.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
    };

    // Close results when clicking outside
    document.addEventListener('click', (e) => {
        if (!pSearch.contains(e.target) && !pResults.contains(e.target)) {
            pResults.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
        }
    });

    // Custom Dropdown Logic
    document.querySelectorAll('.studio-dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const items = dropdown.querySelectorAll('.dropdown-item');
        const input = dropdown.querySelector('input');
        const currentValue = dropdown.querySelector('.current-value');

        trigger.onclick = (e) => {
            e.stopPropagation();
            // Close other dropdowns
            document.querySelectorAll('.studio-dropdown').forEach(d => {
                if (d !== dropdown) d.classList.remove('is-open');
            });
            dropdown.classList.toggle('is-open');
        };

        items.forEach(item => {
            item.onclick = (e) => {
                e.stopPropagation();
                const val = item.getAttribute('data-value');
                const text = item.textContent;
                
                input.value = val;
                currentValue.textContent = text;
                
                // Update selection state
                items.forEach(i => i.classList.remove('is-selected'));
                item.classList.add('is-selected');
                
                dropdown.classList.remove('is-open');
                input.dispatchEvent(new Event('change'));
            };
        });
    });

    // Close on click outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.studio-dropdown').forEach(d => d.classList.remove('is-open'));
    });
    // Quantity Control Logic (Plus/Minus Buttons)
    document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const action = btn.getAttribute('data-action');
            const step = parseInt(btn.getAttribute('data-step') || '1');
            const input = document.getElementById(targetId);
            
            if (!input) return;
            
            let currentVal = parseInt(input.value || '0');
            const min = parseInt(input.getAttribute('min') || '0');
            const max = parseInt(input.getAttribute('max') || '9999999');
            
            if (action === 'plus') {
                if (currentVal + step <= max) {
                    input.value = currentVal + step;
                }
            } else {
                if (currentVal - step >= min) {
                    input.value = currentVal - step;
                }
            }
            
            // Trigger change event for any dependent logic
            input.dispatchEvent(new Event('change'));
        });
    });
});
</script>
</body>
</html>
