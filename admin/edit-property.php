<?php
// FILE: admin/edit-property.php
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
$userRole = $_SESSION['user']['role'];
$userId   = (int)$_SESSION['user']['id'];

$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE . 'admin/listings.php'); exit; }

$prop = $pdo->prepare("
    SELECT p.*, proj.title as project_name 
    FROM properties p 
    LEFT JOIN projects proj ON proj.id = p.project_id 
    WHERE p.id = ?
");
$prop->execute([$id]);
$p = $prop->fetch();
if (!$p || ($userRole === 'agent' && (int)$p['agent_id'] !== $userId)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Property not found or unauthorized.'];
    header('Location: ' . BASE . 'admin/listings.php'); exit;
}

$agents = [];
if ($userRole === 'admin') {
    $agents = $pdo->query("SELECT id, name FROM users WHERE role = 'agent' ORDER BY name")->fetchAll();
}

$gallery = json_decode($p['gallery_images'] ?? '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body{-webkit-font-smoothing:antialiased;} 
        .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}
        @keyframes fadeIn{to{opacity:1;transform:none}} 
        input:focus,textarea:focus,select:focus{outline:none;border-color:#899178;}

    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-4xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Edit Mode</p>
            <h1 class="text-4xl md:text-5xl font-serif font-light mb-4">Editing <span class="italic text-muted"><?= e($p['title']) ?></span></h1>
            <p class="text-sm text-muted">Modify the sanctuary details below. All changes will be reflected immediately.</p>
        </div>

        <form id="property-form" method="POST" action="<?= BASE ?>actions/save-property.php" enctype="multipart/form-data" class="space-y-10 form-reveal" style="animation-delay:.1s">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

            <!-- Basic Details -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Basic Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Property Title *</label>
                        <input type="text" name="title" required value="<?= e($p['title']) ?>"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Location *</label>
                        <input type="text" name="location" required value="<?= e($p['location']) ?>"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Asking Price *</label>
                        <input type="number" name="price" required min="0" step="0.01" value="<?= e($p['price']) ?>"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Status</label>
                        <select name="status" class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm appearance-none transition-all">
                            <?php foreach (['active','draft','sold'] as $st): ?>
                            <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Category *</label>
                        <select name="category" required class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm appearance-none transition-all cursor-pointer">
                            <?php foreach (['Flat/Apartment','Plot','Commercial'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($p['category'] ?? 'Flat/Apartment') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Listing Type *</label>
                        <select name="listing_type" required class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm appearance-none transition-all cursor-pointer">
                            <?php foreach (['Buy','Sell','Rent'] as $lt): ?>
                            <option value="<?= $lt ?>" <?= ($p['listing_type'] ?? 'Buy') === $lt ? 'selected' : '' ?>><?= $lt === 'Rent' ? 'Rent / Lease' : $lt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($_SESSION['user']['role'] === 'admin' && !empty($agents)): ?>
                <div class="mt-8 border-t border-sand/30 pt-8">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Assigned Agent (Optional)</label>
                    <select name="agent_id" class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm appearance-none transition-all cursor-pointer">
                        <option value="">-- No Agent (Studio Owned) --</option>
                        <?php foreach ($agents as $ag): ?>
                        <option value="<?= $ag['id'] ?>" <?= $p['agent_id'] == $ag['id'] ? 'selected' : '' ?>><?= e($ag['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Project Selection -->
                <div class="mt-8 border-t border-sand/30 pt-8 relative">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Associate with Project (Optional)</label>
                    <div class="relative group">
                        <div class="flex items-center bg-surface/30 border border-sand/30 rounded-2xl px-5 group-focus-within:border-accent transition-all">
                            <svg class="w-4 h-4 text-muted/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" id="project-search" placeholder="Search and select a project..."
                                   value="<?= e($p['project_name'] ?? '') ?>"
                                   <?= !empty($p['project_id']) ? 'readonly' : '' ?>
                                   class="w-full bg-transparent text-sm py-4 px-4 outline-none placeholder:text-muted/30 font-medium <?= !empty($p['project_id']) ? 'text-accent' : '' ?>">
                            <button type="button" id="clear-project" class="<?= empty($p['project_id']) ? 'hidden' : '' ?> text-muted/30 hover:text-accent">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="project_id" id="project-id-input" value="<?= (int)$p['project_id'] ?>">
                        
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
                    <?php foreach ([['Bedrooms','bedrooms'],['Bathrooms','bathrooms'],['Size (Sq.ft)','sqft'],['Balcony','balcony']] as [$label,$name]): ?>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1"><?= $label ?></label>
                        <input type="number" name="<?= $name ?>" min="0" value="<?= (int)$p[$name] ?>"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                    </div>
                    <?php endforeach; ?>
                    <div class="col-span-2 md:col-span-3">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Flat Type</label>
                        <div class="flex flex-wrap md:flex-nowrap bg-surface/30 border border-sand/30 rounded-[1.5rem] p-1.5 gap-1.5">
                            <?php foreach (['Raw','Semi-Furnished','Fully-Furnished'] as $ft): ?>
                            <label class="flex-1 min-w-[100px]">
                                <input type="radio" name="flat_type" value="<?= $ft ?>" class="hidden peer" <?= ($p['flat_type'] ?? 'Raw') === $ft ? 'checked' : '' ?>>
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
                    $editor_value = $p['description'] ?? ''; 
                    include '../components/editor/editor.php'; 
                    ?>
                </div>
            </div>

            <!-- Property Images -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Property Images</h2>

                <!-- Current Featured Image -->
                <?php if ($p['featured_image']): ?>
                <div class="mb-8">
                    <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-3">Current Featured Image</p>
                    <div class="flex items-start gap-6">
                        <img src="<?= imgUrl($p['featured_image']) ?>" alt="" class="w-40 h-28 object-cover rounded-2xl border border-sand/30">
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-muted">
                                <input type="checkbox" name="remove_featured" value="1" class="accent-accent">
                                <span>Remove featured image</span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-8">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">
                        <?= $p['featured_image'] ? 'Replace Featured Image' : 'Featured Image' ?>
                    </label>
                    <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-muted file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-surface file:text-foreground hover:file:bg-sand file:transition-all cursor-pointer">
                </div>

                <!-- Gallery Thumbnails -->
                <?php if (!empty($gallery)): ?>
                <div class="mb-8">
                    <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-4">Current Gallery Images</p>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                        <?php foreach ($gallery as $i => $img): ?>
                        <div class="relative group">
                            <img src="<?= imgUrl($img) ?>" alt="" class="w-full aspect-square object-cover rounded-2xl border border-sand/30">
                            <label class="absolute inset-0 flex items-end justify-center pb-2 cursor-pointer bg-black/0 group-hover:bg-black/30 rounded-2xl transition-all">
                                <span class="flex items-center gap-1.5 bg-white/90 px-2 py-1 rounded-full text-[9px] font-bold uppercase opacity-0 group-hover:opacity-100 transition-opacity">
                                    <input type="checkbox" name="remove_gallery[]" value="<?= e($img) ?>" class="accent-red-500"> Remove
                                </span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Add Gallery Images</label>
                    <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple
                           class="block w-full text-sm text-muted file:mr-4 file:py-3 file:px-6 file:rounded-2xl file:border-0 file:text-xs file:font-bold file:uppercase file:tracking-widest file:bg-surface file:text-foreground hover:file:bg-sand file:transition-all cursor-pointer">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 pt-4 pb-20">
                <button type="submit" class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    Save Changes
                </button>
                <a href="<?= BASE ?>admin/listings.php" class="px-10 py-5 border border-sand text-muted rounded-2xl text-xs font-bold uppercase tracking-[0.2em] hover:bg-surface transition-all text-center">
                    Cancel
                </a>
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
});
</script>
</body>
</html>
