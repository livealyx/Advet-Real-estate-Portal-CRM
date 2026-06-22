<?php
// FILE: public/post-property.php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

// Authentication check
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php?redirect=' . urlencode(navPath('post-property.php')));
    exit;
}

// Role restriction (Optional: allow only agents and admins)
if (!in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unauthorized access. Only agents can post properties.'];
    header('Location: ' . BASE . 'index.php');
    exit;
}

$pdo = getPDO();
$siteSettings = loadSettings($pdo);
$pageTitle = "Post Your Property";

$agents = [];
if ($_SESSION['user']['role'] === 'admin') {
    $agents = $pdo->query("SELECT id, name FROM users WHERE role = 'agent' ORDER BY name")->fetchAll();
}

// Additional head assets for the property form
$extraHead = '
<style>
    .post-mesh-bg {
        position: fixed;
        inset: 0;
        z-index: -1;
        background: radial-gradient(circle at 0% 0%, rgba(var(--hero-accent-rgb, 137, 145, 120), 0.15) 0%, transparent 50%),
                    radial-gradient(circle at 100% 100%, rgba(var(--hero-accent-rgb, 137, 145, 120), 0.1) 0%, transparent 50%),
                    #FDFCF9;
    }
    .step-card {
        background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 2.5rem;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03);
    }
    .form-input {
        background: rgba(255, 255, 255, 0.4);
        border: 1.5px solid rgba(0, 0, 0, 0.05);
        border-radius: 1.2rem;
        padding: 1rem 1.5rem;
        width: 100%;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .form-input:focus {
        background: rgba(255, 255, 255, 0.9);
        border-color: var(--hero-accent);
        box-shadow: 0 10px 25px -10px rgba(var(--hero-accent-rgb, 137, 145, 120), 0.2);
        outline: none;
        transform: translateY(-2px);
    }
    .form-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        color: #777;
        margin-bottom: 0.75rem;
        margin-left: 0.5rem;
    }
    /* Stepper Customization */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; margin: 0; 
    }
    input[type=number] { -moz-appearance: textfield; }
    
    .stepper-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: transparent;
    }
    .stepper-btn {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 1rem;
        background: #fff;
        color: #111;
        transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        cursor: pointer;
    }
    .stepper-btn:hover {
        border-color: var(--hero-accent);
        background: var(--hero-accent);
        color: #fff;
        box-shadow: 0 10px 20px rgba(var(--hero-accent-rgb, 137, 145, 120), 0.2);
    }
    .stepper-btn:active {
        transform: scale(0.9);
    }
    .stepper-display {
        flex: 1;
        height: 48px;
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s ease;
    }
    .stepper-container:focus-within .stepper-display {
        background: #fff;
        border-color: var(--hero-accent);
        box-shadow: inset 0 0 0 1px var(--hero-accent);
    }
    .stepper-input {
        background: transparent;
        border: none;
        text-align: center;
        width: 100%;
        font-family: serif;
        font-size: 1.2rem;
        font-weight: 500;
        color: #111;
        outline: none;
    }

    /* Simple Premium Styles */
    .detail-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 1.5rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }
    .detail-card:hover {
        border-color: rgba(0, 0, 0, 0.1);
        box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.05);
    }

    /* Studio Custom Dropdown */
    .studio-dropdown {
        position: relative;
        cursor: pointer;
    }
    .dropdown-trigger {
        background: rgba(255, 255, 255, 0.4);
        border: 1.5px solid rgba(0, 0, 0, 0.05);
        border-radius: 1.2rem;
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .studio-dropdown:hover .dropdown-trigger {
        background: rgba(255, 255, 255, 0.8);
        border-color: rgba(0, 0, 0, 0.1);
    }
    .studio-dropdown.is-open .dropdown-trigger {
        background: #fff;
        border-color: var(--hero-accent);
        box-shadow: 0 10px 30px -10px rgba(var(--hero-accent-rgb), 0.15);
    }
    .dropdown-chevron {
        transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        color: rgba(0, 0, 0, 0.3);
    }
    .studio-dropdown.is-open .dropdown-chevron {
        transform: rotate(180deg);
        color: var(--hero-accent);
    }
    .studio-dropdown.is-open {
        z-index: 1000;
    }
    .dropdown-list {
        position: absolute;
        top: calc(100% + 0.75rem);
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(25px);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 1.5rem;
        overflow: hidden;
        z-index: 100;
        opacity: 0;
        transform: translateY(10px) scale(0.98);
        pointer-events: none;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.1);
    }
    .studio-dropdown.is-open .dropdown-list {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: all;
    }
    .dropdown-item {
        padding: 1.2rem 1.5rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #555;
        cursor: pointer;
        position: relative;
        z-index: 101;
    }
    .dropdown-item:hover {
        background: rgba(var(--hero-accent-rgb), 0.05);
        color: var(--hero-accent);
        padding-left: 1.75rem;
    }
    .dropdown-item.is-selected {
        background: var(--hero-accent);
        color: #fff;
    }
</style>
';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="post-mesh-bg"></div>

<main class="relative pt-32 pb-20 px-6 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-16 reveal flex flex-col items-center text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent/5 border border-accent/10 mb-6 shadow-sm">
                <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
                <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-accent">Property Submission</p>
            </div>
            <h1 class="text-5xl md:text-7xl font-serif font-light tracking-tight mb-6 text-foreground">
                List Your <span class="italic text-muted/60">Property.</span>
            </h1>
            <p class="text-base md:text-lg text-muted/70 max-w-2xl font-light leading-relaxed">
                Complete the technical specifications below to initialize and deploy your real estate asset onto the platform architecture.
            </p>
        </div>

        <form action="<?= BASE ?>actions/save-property.php" method="POST" enctype="multipart/form-data" class="space-y-12">
            <!-- Hidden Fields for Flow Control -->
            <input type="hidden" name="return_url" value="<?= navPath('post-property.php') ?>">
            <input type="hidden" name="success_redirect" value="<?= navPath('profile.php') ?>">

            <!-- Step 1: Basic Details -->
            <section class="step-card p-8 md:p-12 reveal" style="animation-delay: 0.1s;">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-black/5">
                    <span class="w-10 h-10 flex items-center justify-center bg-accent text-white rounded-full font-bold text-sm">01</span>
                    <h2 class="text-xl font-serif font-light italic">Basic Details</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="md:col-span-2">
                        <label class="form-label">Property Title</label>
                        <input type="text" name="title" required placeholder="e.g. The Ethereal Heights Penthouse" class="form-input">
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Location / Area</label>
                        <input type="text" name="location" required placeholder="e.g. Malibu Coastline, CA" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Asking Price (₹)</label>
                        <input type="number" name="price" required placeholder="0.00" class="form-input">
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <div class="studio-dropdown" data-name="status">
                            <input type="hidden" name="status" value="draft">
                            <div class="dropdown-trigger">
                                <span class="current-value">Draft</span>
                                <svg class="dropdown-chevron w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m19 9-7 7-7-7"/></svg>
                            </div>
                            <div class="dropdown-list">
                                <div class="dropdown-item" data-value="active">Active</div>
                                <div class="dropdown-item is-selected" data-value="draft">Draft</div>
                                <div class="dropdown-item" data-value="sold">Sold</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Category *</label>
                        <div class="studio-dropdown" data-name="category">
                            <input type="hidden" name="category" value="Flat/Apartment" required>
                            <div class="dropdown-trigger">
                                <span class="current-value">Flat/Apartment</span>
                                <svg class="dropdown-chevron w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m19 9-7 7-7-7"/></svg>
                            </div>
                            <div class="dropdown-list">
                                <div class="dropdown-item is-selected" data-value="Flat/Apartment">Flat/Apartment</div>
                                <div class="dropdown-item" data-value="Plot">Plot</div>
                                <div class="dropdown-item" data-value="Commercial">Commercial</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Listing Type *</label>
                        <div class="studio-dropdown" data-name="listing_type">
                            <input type="hidden" name="listing_type" value="Buy" required>
                            <div class="dropdown-trigger">
                                <span class="current-value">Buy/Sell</span>
                                <svg class="dropdown-chevron w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m19 9-7 7-7-7"/></svg>
                            </div>
                            <div class="dropdown-list">
                                <div class="dropdown-item is-selected" data-value="Buy">Buy/Sell</div>
                                <div class="dropdown-item" data-value="Rent">Rent / Lease</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($_SESSION['user']['role'] === 'admin' && !empty($agents)): ?>
                    <div class="md:col-span-2">
                        <label class="form-label">Assign to Agent (Optional)</label>
                        <div class="studio-dropdown" data-name="agent_id">
                            <input type="hidden" name="agent_id" value="">
                            <div class="dropdown-trigger">
                                <span class="current-value">-- No Agent (Studio Owned) --</span>
                                <svg class="dropdown-chevron w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m19 9-7 7-7-7"/></svg>
                            </div>
                            <div class="dropdown-list">
                                <div class="dropdown-item is-selected" data-value="">-- No Agent (Studio Owned) --</div>
                                <?php foreach ($agents as $ag): ?>
                                <div class="dropdown-item" data-value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="md:col-span-2">
                        <label class="form-label">Project Association (Optional)</label>
                        <div class="relative group">
                            <div class="flex items-center form-input group-focus-within:border-accent transition-all px-4">
                                <svg class="w-4 h-4 text-muted/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                <input type="text" id="project-search" placeholder="Search projects..." class="bg-transparent w-full outline-none px-4 py-1">
                                <button type="button" id="clear-project" class="hidden text-muted/30 hover:text-accent">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <input type="hidden" name="project_id" id="project-id-input">
                            <div id="project-results" class="absolute top-full left-0 right-0 mt-3 bg-white rounded-2xl shadow-2xl border border-black/5 opacity-0 translate-y-4 pointer-events-none transition-all z-50 py-3 overflow-hidden">
                                <!-- Results injected via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 2: Property Details -->
            <section class="step-card p-8 md:p-12 reveal" style="animation-delay: 0.2s;">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-black/5">
                    <span class="w-10 h-10 flex items-center justify-center bg-accent text-white rounded-full font-bold text-sm">02</span>
                    <h2 class="text-xl font-serif font-light italic">Property Details</h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <!-- Bedrooms -->
                    <div class="detail-card space-y-4">
                        <div class="flex items-center text-muted">
                            <span class="text-[10px] font-bold uppercase tracking-widest">Bedrooms</span>
                        </div>
                        <div class="stepper-container">
                            <button type="button" class="stepper-btn stepper-minus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 12H6"/></svg>
                            </button>
                            <div class="stepper-display">
                                <input type="number" name="bedrooms" value="0" min="0" placeholder="0" class="stepper-input">
                            </div>
                            <button type="button" class="stepper-btn stepper-plus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 6v12M6 12h12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Bathrooms -->
                    <div class="detail-card space-y-4">
                        <div class="flex items-center text-muted">
                            <span class="text-[10px] font-bold uppercase tracking-widest">Bathrooms</span>
                        </div>
                        <div class="stepper-container">
                            <button type="button" class="stepper-btn stepper-minus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 12H6"/></svg>
                            </button>
                            <div class="stepper-display">
                                <input type="number" name="bathrooms" value="0" min="0" placeholder="0" class="stepper-input">
                            </div>
                            <button type="button" class="stepper-btn stepper-plus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 6v12M6 12h12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Sq. Ft -->
                    <div class="detail-card space-y-4">
                        <div class="flex items-center text-muted">
                            <span class="text-[10px] font-bold uppercase tracking-widest">Square Feet</span>
                        </div>
                        <div class="stepper-container">
                            <button type="button" class="stepper-btn stepper-minus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 12H6"/></svg>
                            </button>
                            <div class="stepper-display">
                                <input type="number" name="sqft" value="0" min="0" step="10" placeholder="0" class="stepper-input">
                            </div>
                            <button type="button" class="stepper-btn stepper-plus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 6v12M6 12h12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Balconies -->
                    <div class="detail-card space-y-4">
                        <div class="flex items-center text-muted">
                            <span class="text-[10px] font-bold uppercase tracking-widest">Balconies</span>
                        </div>
                        <div class="stepper-container">
                            <button type="button" class="stepper-btn stepper-minus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 12H6"/></svg>
                            </button>
                            <div class="stepper-display">
                                <input type="number" name="balcony" value="0" min="0" placeholder="0" class="stepper-input">
                            </div>
                            <button type="button" class="stepper-btn stepper-plus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 6v12M6 12h12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Furnishing -->
                    <div class="md:col-span-2 detail-card space-y-4">
                        <div class="flex items-center text-muted">
                            <span class="text-[10px] font-bold uppercase tracking-widest">Furnishing Status</span>
                        </div>
                        <div class="flex bg-black/5 p-1 rounded-xl">
                            <?php foreach(['Raw', 'Semi-Furnished', 'Fully-Furnished'] as $f): ?>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="flat_type" value="<?= $f ?>" class="hidden peer" <?= $f === 'Raw' ? 'checked' : '' ?>>
                                <div class="py-3 text-[9px] uppercase tracking-widest font-bold text-center rounded-lg transition-all peer-checked:bg-white peer-checked:shadow-sm peer-checked:text-accent">
                                    <?= $f ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 3: Additional Details -->
            <section class="step-card p-8 md:p-12 reveal" style="animation-delay: 0.3s;">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-black/5">
                    <span class="w-10 h-10 flex items-center justify-center bg-accent text-white rounded-full font-bold text-sm">03</span>
                    <h2 class="text-xl font-serif font-light italic">Additional Details</h2>
                </div>

                <div class="space-y-10">
                    <div>
                        <label class="form-label">Property Description *</label>
                        <?php 
                        $editor_name = 'description';
                        $editor_value = ''; // Set initial value if needed
                        include dirname(__DIR__) . '/components/editor/editor.php'; 
                        ?>
                    </div>
                </div>
            </section>

            <!-- Step 4: Property Images -->
            <section class="step-card p-8 md:p-12 reveal" style="animation-delay: 0.4s;">
                <div class="flex items-center gap-4 mb-10 pb-6 border-b border-black/5">
                    <span class="w-10 h-10 flex items-center justify-center bg-accent text-white rounded-full font-bold text-sm">04</span>
                    <h2 class="text-xl font-serif font-light italic">Property Images</h2>
                </div>

                <div class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="form-label">Featured Image (Cover)</label>
                        <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp,image/avif" 
                               class="hidden">
                    </div>
                    <div class="space-y-4">
                        <label class="form-label">Gallery Images (Optional)</label>
                        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp,image/avif" multiple 
                               class="hidden">
                    </div>
                </div>

                    <div class="pt-10 flex flex-col sm:flex-row gap-4">
                        <button type="submit" name="status_override" value="active" class="flex-1 py-5 bg-accent text-white font-bold uppercase tracking-[0.3em] text-xs rounded-2xl shadow-xl shadow-accent/20 hover:bg-black hover:shadow-black/20 transform hover:-translate-y-1 transition-all duration-500">
                            Publish Property
                        </button>
                        <button type="submit" name="status_override" value="draft" class="flex-1 py-5 bg-white border border-black/5 text-muted font-bold uppercase tracking-[0.3em] text-xs rounded-2xl hover:bg-black/5 transition-all">
                            Save as Draft
                        </button>
                    </div>
                </div>
            </section>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load modular editor scripts
    <?php include dirname(__DIR__) . '/components/editor/editor-scripts.php'; ?>

    // Numeric Steppers Logic
    document.querySelectorAll('.stepper-container').forEach(container => {
        const input = container.querySelector('input');
        const minus = container.querySelector('.stepper-minus');
        const plus  = container.querySelector('.stepper-plus');
        const step  = parseInt(input.getAttribute('step')) || 1;

        minus.onclick = () => {
            const val = parseInt(input.value) || 0;
            if (val > 0) input.value = val - step;
            input.dispatchEvent(new Event('change'));
        };
        plus.onclick = () => {
            const val = parseInt(input.value) || 0;
            input.value = val + step;
            input.dispatchEvent(new Event('change'));
        };
    });

    // Project Search Logic
    const searchInput = document.getElementById('project-search');
    const resultsContainer = document.getElementById('project-results');
    const idInput = document.getElementById('project-id-input');
    const clearBtn = document.getElementById('clear-project');
    let debounceTimer;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            hideResults();
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`<?= BASE ?>actions/search-projects.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        showResults(data);
                    } else {
                        hideResults();
                    }
                });
        }, 300);
    });

    function showResults(projects) {
        resultsContainer.innerHTML = projects.map(p => `
            <div class="px-6 py-4 hover:bg-black/5 cursor-pointer flex items-center gap-4 transition-all" onclick="selectProject(${p.id}, '${p.text.replace(/'/g, "\\'")}')">
                <img src="${p.image}" class="w-10 h-10 rounded-xl object-cover border border-sand/20">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-foreground">${p.text}</p>
                    <p class="text-[9px] text-muted uppercase tracking-wider">${p.subtext}</p>
                </div>
            </div>
        `).join('');
        resultsContainer.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
        resultsContainer.classList.add('opacity-100', 'translate-y-0');
    }

    function hideResults() {
        resultsContainer.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
        resultsContainer.classList.remove('opacity-100', 'translate-y-0');
    }

    window.selectProject = function(id, title) {
        searchInput.value = title;
        idInput.value = id;
        searchInput.readOnly = true;
        clearBtn.classList.remove('hidden');
        hideResults();
    };

    clearBtn.onclick = () => {
        searchInput.value = '';
        idInput.value = '';
        searchInput.readOnly = false;
        clearBtn.classList.add('hidden');
    }

    // Close results on click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            hideResults();
        }
    });
    // Custom Dropdown Logic
    document.querySelectorAll('.studio-dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const list = dropdown.querySelector('.dropdown-list');
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
});
</script>

<?php 
require_once dirname(__DIR__) . '/includes/upload-sheet.php';
require_once dirname(__DIR__) . '/includes/footer.php'; 
?>
