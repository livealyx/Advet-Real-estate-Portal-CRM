<?php
// FILE: public/all-properties.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();

// Fetch ALL properties regardless of status (active/sold)
// and regardless of category restrictions
$sql = "SELECT * FROM properties ORDER BY created_at DESC";
$properties = $pdo->query($sql)->fetchAll();

$solidNav  = true;
$pageTitle = 'All Properties';
$pageDesc  = 'View our complete portfolio of architectural sanctuaries at Advet Buildwell.';
require_once '../includes/header.php';
?>

<main class="flex-grow pt-40 pb-32">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">

        <!-- Page Header -->
        <div class="mb-16 reveal">
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">All Properties</p>
            <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight">Property <span class="italic text-muted">Categories</span></h1>
            <p class="text-sm text-muted mt-6 max-w-2xl font-light leading-relaxed">
                A complete list of all properties, including available and sold listings.
            </p>
        </div>

        <!-- Count Indicator -->
        <div class="flex items-center gap-4 mb-12 reveal" style="animation-delay: 0.2s">
            <span class="px-4 py-1.5 bg-surface border border-sand/30 rounded-full text-[10px] font-bold uppercase tracking-widest text-muted">
                <?= count($properties) ?> Total Assets
            </span>
        </div>

        <!-- Properties Grid -->
        <?php if (empty($properties)): ?>
            <div class="text-center py-32 bg-surface/30 rounded-[3rem] border border-sand/20">
                <svg class="w-12 h-12 text-sand mx-auto mb-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <h3 class="text-3xl font-serif font-light mb-4 italic">Portfolio empty.</h3>
                <p class="text-muted font-light mb-8">We are currently curating new architectural opportunities.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-16">
                <?php foreach ($properties as $i => $prop): ?>
                <a href="<?= BASE ?>property/<?= e($prop['slug']) ?>" class="group cursor-pointer <?= ($i % 3 === 1) ? 'lg:mt-12' : '' ?>">
                    <div class="relative w-full aspect-[4/3] image-soft-clip mb-6 overflow-hidden">
                        <img src="<?= imgUrl($prop['featured_image']) ?>"
                             alt="<?= e($prop['title']) ?>"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        
                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="bg-foreground text-background text-[9px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm">For <?= e($prop['listing_type']) ?></span>
                        </div>
                        
                        <div class="absolute top-4 right-4 flex gap-2">
                            <?php if ($prop['status'] === 'active'): ?>
                                <span class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-[9px] font-bold uppercase tracking-widest shadow-sm">Active</span>
                            <?php elseif ($prop['status'] === 'sold'): ?>
                                <span class="bg-foreground text-background px-4 py-2 rounded-full text-[9px] font-bold uppercase tracking-widest shadow-sm">Sold</span>
                            <?php else: ?>
                                <span class="bg-surface/90 backdrop-blur-sm px-4 py-2 rounded-full text-[9px] font-bold uppercase tracking-widest shadow-sm text-muted"><?= ucfirst($prop['status']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-serif mb-2 group-hover:text-accent transition-colors"><?= e($prop['title']) ?></h3>
                            <p class="text-muted font-light text-sm flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-8-7.5-8-12a8 8 0 1116 0c0 4.5-8 12-8 12z"/><path d="M12 11a2 2 0 100-4 2 2 0 000 4z"/></svg>
                                <?= e($prop['location']) ?>
                            </p>
                        </div>
                        <div class="text-right flex flex-col items-end shrink-0 ml-4">
                            <span class="text-sm font-medium text-foreground"><?= formatPrice((float)$prop['price']) ?></span>
                            <span class="text-[10px] text-muted mt-1 uppercase tracking-widest font-bold"><?= e($prop['category']) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
