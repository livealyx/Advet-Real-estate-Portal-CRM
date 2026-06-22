<?php
// FILE: public/spaces.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();

$cat  = $_GET['cat'] ?? 'all';
$type = $_GET['type'] ?? 'all';

// Spaces.php is now specifically for Commercial properties
$where = ["status = 'active'", "category = 'Commercial'"];
$params = [];

if ($type !== 'all') {
    $where[] = "listing_type = ?";
    $params[] = $type;
}

$whereClause = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT id, title, slug, location, price, bedrooms, bathrooms, sqft, featured_image, category, listing_type
                         FROM properties WHERE $whereClause
                     ORDER BY created_at DESC LIMIT 12");
$stmt->execute($params);
$activeProps = $stmt->fetchAll();

$solidNav  = true;
$pageTitle = 'Commercial Spaces';
$pageDesc  = 'Curated commercial environments and rational spaces.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-24">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 reveal">

            <!-- Header + Filter Bar -->
            <div class="flex flex-col lg:flex-row justify-between items-end mb-16 border-b border-sand/30 pb-12 gap-10">
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-accent mb-3">Portfolio</p>
                    <h1 class="text-5xl font-serif font-light mb-4">Commercial <span class="italic text-muted">Spaces</span></h1>
                    <p class="text-muted font-light">Curated commercial environments and rational spaces.</p>
                </div>
                
                <form method="GET" class="flex flex-wrap gap-8 items-center">
                    <div class="flex flex-col gap-2">
                        <label class="text-[9px] uppercase tracking-widest font-bold text-muted/60">Protocol</label>
                        <div class="flex gap-4">
                            <?php foreach(['all' => 'All', 'Buy' => 'Buy', 'Sell' => 'Sell', 'Rent' => 'Lease'] as $key => $label): ?>
                            <a href="?type=<?= $key ?>" 
                               class="text-xs font-medium <?= $type === $key ? 'border-b border-foreground text-foreground' : 'text-muted hover:text-foreground' ?> transition-all pb-1"><?= $label ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Active Properties (Dynamic) -->
            <?php if (!empty($activeProps)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-20 mb-24">
                <?php foreach ($activeProps as $i => $p): ?>
                <a href="<?= BASE ?>property/<?= e($p['slug']) ?>" class="group cursor-pointer <?= $i % 2 !== 0 ? 'md:mt-24' : '' ?>">
                    <div class="relative w-full aspect-[4/3] image-soft-clip mb-6 overflow-hidden">
                        <img src="<?= imgUrl($p['featured_image']) ?>"
                             alt="<?= e($p['title']) ?>"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        <div class="absolute top-4 right-4 flex flex-col gap-2 items-end">
                            <span class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-[9px] font-bold uppercase tracking-widest text-foreground shadow-sm">
                                <?= e($p['category']) ?>
                            </span>
                            <span class="bg-accent/90 backdrop-blur-sm px-4 py-2 rounded-full text-[9px] font-bold uppercase tracking-widest text-background shadow-sm">
                                <?= e($p['listing_type'] === 'Rent' ? 'Lease' : $p['listing_type']) ?>
                            </span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-serif mb-1 group-hover:text-accent transition-colors"><?= e($p['title']) ?></h3>
                    <p class="text-muted font-light text-sm mb-4"><?= e($p['location']) ?></p>
                    <div class="flex gap-4 mb-4 flex-wrap">
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= (int)$p['bedrooms'] ?> Beds</span>
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= (int)$p['bathrooms'] ?> Baths</span>
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= number_format((int)$p['sqft']) ?> sqft</span>
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium text-accent"><?= formatPrice((float)$p['price']) ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <!-- Fallback static showcase when no DB properties exist yet -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-20 mb-24">
                <?php foreach ([
                    ['The Courtyard House','Silverlake Hills, CA','3 Beds','3.5 Baths','3,200 sqft','Available Spring 2026','https://images.unsplash.com/photo-1628624747186-a941c476b7ef?auto=format&fit=crop&q=80&w=800','','Centered around an ancient oak tree, this home merges interior and exterior living spaces seamlessly.'],
                    ['The Cliffside Retreat','Big Sur, CA','4 Beds','4 Baths','4,500 sqft','Sold','https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&q=80&w=800','md:mt-24','A cantilevered marvel that suspends you over the Pacific Ocean.'],
                ] as $sp): ?>
                <div class="group <?= $sp[7] ?>">
                    <div class="relative w-full aspect-[4/3] image-soft-clip mb-6 overflow-hidden">
                        <img src="<?= e($sp[6]) ?>" alt="<?= e($sp[0]) ?>" class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        <span class="absolute top-4 right-4 bg-white/90 px-4 py-2 rounded-full text-xs font-medium"><?= e($sp[5]) ?></span>
                    </div>
                    <h3 class="text-2xl font-serif mb-1 group-hover:text-accent transition-colors"><?= e($sp[0]) ?></h3>
                    <p class="text-muted font-light text-sm mb-4"><?= e($sp[1]) ?></p>
                    <div class="flex gap-4 mb-4">
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= $sp[2] ?></span>
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= $sp[3] ?></span>
                        <span class="bg-surface px-3 py-1 rounded-lg text-xs font-medium"><?= $sp[4] ?></span>
                    </div>
                    <p class="font-light text-muted text-sm line-clamp-2"><?= e($sp[8]) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Property Categories Gallery -->
            <?php
            $archetypes = $pdo->query("SELECT * FROM space_archetypes ORDER BY display_order ASC, created_at DESC")->fetchAll();
            if (!empty($archetypes)):
            ?>
            <div class="pt-32 border-t border-sand/50">
                <div class="mb-20">
                    <p class="text-xs font-medium uppercase tracking-[0.4em] text-accent mb-6">Property Categories</p>
                    <h2 class="text-5xl font-serif font-light mb-6 italic text-foreground">Curated by Atmosphere.</h2>
                    <p class="text-muted font-light max-w-2xl text-lg leading-relaxed">Each space we steward belongs to a distinct architectural dialogue. Discover the archetype that resonates with your professional and personal rhythm.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php foreach ($archetypes as $a): ?>
                    <div class="group relative aspect-square image-soft-clip overflow-hidden">
                        <img src="<?= imgUrl($a['image_path']) ?>" alt="<?= e($a['title']) ?>"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-foreground/20 group-hover:bg-foreground/40 transition-colors"></div>
                        <div class="absolute inset-0 flex flex-col justify-end p-8 text-background">
                            <h4 class="text-2xl font-serif mb-2"><?= e($a['title']) ?></h4>
                            <p class="text-xs uppercase tracking-widest opacity-80 group-hover:translate-x-2 transition-transform duration-500 line-clamp-1"><?= e($a['description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Service Excellence Banner -->
        <section class="relative py-48 overflow-hidden group mt-32">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&q=80&w=2000"
                     alt="Excellence Banner"
                     class="w-full h-full object-cover transition-transform duration-[12s] group-hover:scale-110">
                <div class="absolute inset-0 bg-foreground/60 backdrop-blur-[2px]"></div>
            </div>
            <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 text-center">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Service Excellence</p>
                <h2 class="text-5xl md:text-7xl font-serif font-light text-background mb-12 leading-tight">
                    Buy. Sell. <span class="italic opacity-80">Lease.</span>
                </h2>
                <a href="<?= BASE ?>contact.php" class="inline-flex items-center justify-center gap-3 bg-accent text-background px-10 py-5 rounded-full hover:bg-accent-dark transition-all transform hover:-translate-y-1 shadow-2xl font-medium">
                    Inquire About Services
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </section>
    </main>

<?php require_once '../includes/footer.php'; ?>
