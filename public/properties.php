<?php
// FILE: public/listings.php
session_start();
require_once '../config/db.php';

$pdo     = getPDO();

$cacheKey = AdvetCache::generateKey('properties', $_GET);
$cachedContent = AdvetCache::get($cacheKey);
if ($cachedContent) {
    echo $cachedContent;
    exit;
}

ob_start();

$cat    = $_GET['cat']    ?? 'all';
$type   = $_GET['type']   ?? 'all';
$search = trim($_GET['search'] ?? '');
$minP   = (float)($_GET['min_price'] ?? 0);
$maxP   = (float)($_GET['max_price'] ?? 0);
$beds   = (int)($_GET['bedrooms']    ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

// Properties.php is for Home, Flat/Apartment, Plot and Commercial categories
$conditions = ["status IN ('active', 'sold')", "category IN ('Home', 'Flat/Apartment', 'Plot', 'Commercial')"];
$args       = [];

if ($cat !== 'all') {
    if ($cat === 'Flat/Apartment') {
        $conditions[] = "category IN ('Flat/Apartment', 'Home')";
    } else {
        $conditions[] = "category = ?";
        $args[] = $cat;
    }
}
if ($type !== 'all') {
    if ($type === 'Buy') {
        $conditions[] = "listing_type IN ('Buy', 'Sell')";
    } else {
        $conditions[] = "listing_type = ?";
        $args[] = $type;
    }
}

if ($search !== '') {
    $conditions[] = "(title LIKE ? OR location LIKE ?)";
    $args[] = '%' . $search . '%';
    $args[] = '%' . $search . '%';
}
if ($minP > 0) {
    $conditions[] = "price >= ?";
    $args[] = (float)$minP;
}
if ($maxP > 0) {
    $conditions[] = "price <= ?";
    $args[] = (float)$maxP;
}
if ($beds > 0) {
    $conditions[] = "bedrooms >= ?";
    $args[] = (int)$beds;
}

$where = 'WHERE ' . implode(' AND ', $conditions);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM properties $where");
$countStmt->execute($args);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$sql = "SELECT * FROM properties $where ORDER BY created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$properties = $stmt->fetchAll();

$solidNav  = true;
$pageTitle = 'Properties';
$pageDesc  = 'Browse Advet Buildwell\'s curated collection of active property listings.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">

            <!-- Page Header -->
            <div class="mb-16 reveal">
                <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">All Properties</p>
                <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight">Property <span class="italic text-muted">Listings</span></h1>
            </div>

            <!-- Redesigned Filter Command Bar -->
            <div class="sticky top-24 z-50 mb-24 reveal reveal-delay-1 max-w-5xl mx-auto">
                <div class="bg-white/70 backdrop-blur-2xl border border-white/40 rounded-[2.5rem] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.08)] p-3 md:p-4">
                    <form method="GET" action="<?= BASE ?>properties" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
                        
                        <!-- Search Section -->
                        <div class="flex-grow flex items-center bg-surface/50 rounded-2xl px-5 border border-sand/20 group focus-within:bg-white focus-within:shadow-sm transition-all">
                            <svg class="w-4 h-4 text-muted/40 group-focus-within:text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search neighborhood or project..."
                                   class="w-full bg-transparent text-sm py-4 px-4 outline-none placeholder:text-muted/30 font-medium">
                            <?php if($search): ?>
                                <a href="?<?= http_build_query(['cat'=>$cat,'type'=>$type,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>" class="text-muted/30 hover:text-accent">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Controls Wrapper -->
                        <div class="flex flex-wrap md:flex-nowrap items-center gap-3">
                            
                            <!-- Archetype Segment -->
                            <div class="flex bg-surface/50 p-1.5 rounded-2xl border border-sand/20">
                                <?php foreach(['all' => 'All', 'Flat/Apartment' => 'Residences', 'Plot' => 'Plots', 'Commercial' => 'Commercial'] as $key => $label): ?>
                                <a href="?<?= http_build_query(['cat'=>$key,'type'=>$type,'search'=>$search,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>" 
                                   class="px-5 py-2.5 rounded-xl text-[10px] uppercase font-bold tracking-widest transition-all <?= $cat === $key ? 'bg-white shadow-sm text-accent' : 'text-muted/60 hover:text-muted' ?>">
                                    <?= $label ?>
                                </a>
                                <?php endforeach; ?>
                            </div>

                            <!-- Protocol Dropdown -->
                            <div class="relative group">
                                <button type="button" class="h-full bg-surface/50 px-6 py-4 rounded-2xl border border-sand/20 text-[10px] font-bold uppercase tracking-widest text-muted flex items-center gap-4 hover:bg-white hover:shadow-sm transition-all">
                                    <span><?= $type === 'all' ? 'Transaction' : ($type === 'Rent' ? 'Rent/Lease' : ($type === 'Buy' ? 'Buy/Sell' : $type)) ?></span>
                                    <svg class="w-2.5 h-2.5 opacity-40 transition-transform group-hover:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div class="absolute top-full left-0 lg:right-0 lg:left-auto mt-3 w-56 bg-white rounded-3xl shadow-2xl border border-sand/10 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all z-50 py-3 overflow-hidden">
                                    <?php foreach(['all' => 'Any Protocol', 'Buy' => 'Buy/Sell', 'Rent' => 'Rent/Lease'] as $key => $label): ?>
                                    <a href="?<?= http_build_query(['cat'=>$cat,'type'=>$key,'search'=>$search,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>" 
                                       class="block px-8 py-3.5 text-[10px] uppercase tracking-widest font-bold transition-all <?= $type === $key ? 'text-accent bg-accent/5' : 'text-muted/60 hover:text-accent hover:bg-surface' ?>">
                                        <?= $label ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Advanced Settings Trigger (Desktop Only for visual balance) -->
                            <div class="relative group">
                                <button type="button" class="h-full bg-foreground text-background px-6 py-4 rounded-2xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-3 hover:bg-accent transition-all shadow-lg shadow-foreground/10 group-hover:scale-[1.02]">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/></svg>
                                    Filters
                                </button>
                                
                                <div class="absolute top-full right-0 mt-3 w-80 bg-white rounded-[2.5rem] shadow-2xl border border-sand/10 opacity-0 translate-y-4 pointer-events-none group-hover:opacity-100 group-hover:translate-y-0 group-hover:pointer-events-auto transition-all z-50 p-8">
                                    <div class="space-y-8">
                                        <div>
                                            <p class="text-[9px] uppercase tracking-[0.2em] font-bold text-muted/40 mb-5">Price Range</p>
                                            <div class="flex gap-3">
                                                <input type="number" name="min_price" value="<?= $minP ?: '' ?>" placeholder="Min" class="w-full px-5 py-3.5 bg-surface rounded-xl text-xs outline-none focus:bg-white border border-transparent focus:border-sand/40 transition-all font-medium">
                                                <input type="number" name="max_price" value="<?= $maxP ?: '' ?>" placeholder="Max" class="w-full px-5 py-3.5 bg-surface rounded-xl text-xs outline-none focus:bg-white border border-transparent focus:border-sand/40 transition-all font-medium">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <p class="text-[9px] uppercase tracking-[0.2em] font-bold text-muted/40 mb-5">Minimal Beds</p>
                                            <div class="flex flex-wrap gap-2">
                                                <?php for($i=0;$i<=4;$i++): ?>
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="bedrooms" value="<?= $i ?>" <?= $beds == $i ? 'checked':'' ?> class="hidden peer">
                                                    <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface text-[10px] font-bold peer-checked:bg-accent peer-checked:text-white transition-all text-muted/60 peer-checked:shadow-sm"><?= $i === 0 ? 'Any' : $i.'+' ?></span>
                                                </label>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <div class="pt-4 border-t border-sand/20">
                                            <button type="submit" class="w-full py-4 bg-foreground text-background text-[10px] font-bold uppercase tracking-widest rounded-2xl hover:bg-accent transition-all">Apply Refinements</button>
                                            <a href="<?= BASE ?>properties" class="block text-center mt-4 text-[9px] uppercase tracking-widest font-bold text-muted/40 hover:text-accent">Reset all parameters</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="cat" value="<?= e($cat) ?>">
                        <input type="hidden" name="type" value="<?= e($type) ?>">
                    </form>
                </div>
            </div>

            <!-- Results Count -->
            <p class="text-xs uppercase tracking-widest text-muted mb-10">
                <?= $totalRows ?> <?= $totalRows === 1 ? 'Property' : 'Properties' ?> Found
                <?= $search ? '· "' . e($search) . '"' : '' ?>
            </p>

            <!-- Properties Grid -->
            <?php if (empty($properties)): ?>
            <div class="text-center py-32 bg-surface/30 rounded-[3rem] border border-sand/20">
                <svg class="w-12 h-12 text-sand mx-auto mb-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <h3 class="text-3xl font-serif font-light mb-4 italic">No properties found.</h3>
                <p class="text-muted font-light mb-8">Try changing your filters to see more results.</p>
                <a href="<?= BASE ?>properties" class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors">Clear all filters</a>
            </div>
            <?php else: ?>
            
            <!-- Featured Section (Magicbricks Highlight) -->
            <?php if ($page === 1 && !empty($properties)): ?>
            <div class="mb-20">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-serif font-light">Featured <span class="italic text-muted">Collection</span></h2>
                    <div class="h-px bg-sand/30 flex-grow mx-8 hidden sm:block"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php 
                    $featured = array_slice($properties, 0, 3);
                    foreach ($featured as $prop): 
                    ?>
                    <a href="<?= BASE ?>property/<?= e($prop['slug']) ?>" class="group block relative bg-white rounded-[2.5rem] overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500 border border-sand/30">
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="<?= imgUrl($prop['featured_image']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute top-4 left-4 flex gap-2 flex-wrap">
                                <span class="bg-accent text-white text-[9px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg">Featured</span>
                                <span class="bg-white/90 backdrop-blur-sm text-foreground text-[9px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm"><?= e($prop['category']) ?></span>
                                <span class="bg-foreground text-background text-[9px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm"><?= e($prop['listing_type']) ?></span>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-serif group-hover:text-accent transition-colors"><?= e($prop['title']) ?></h3>
                                <span class="text-sm font-bold text-accent whitespace-nowrap"><?= formatPrice((float)$prop['price']) ?></span>
                            </div>
                            <p class="text-xs text-muted mb-6 flex items-center gap-2">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-8-7.5-8-12a8 8 0 1116 0c0 4.5-8 12-8 12z"/><circle cx="12" cy="11" r="2"/></svg>
                                <?= e($prop['location']) ?>
                            </p>
                            <div class="pt-6 border-t border-sand/30 flex justify-between items-center">
                                <div class="flex gap-4 text-[10px] uppercase font-bold tracking-widest text-muted">
                                    <span><?= (int)$prop['bedrooms'] ?> BD</span>
                                    <span><?= (int)$prop['bathrooms'] ?> BA</span>
                                    <span><?= number_format((int)$prop['sqft']) ?> SQFT</span>
                                </div>
                                <svg class="w-5 h-5 opacity-0 group-hover:opacity-100 -translate-x-4 group-hover:translate-x-0 transition-all text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex items-center justify-between mb-8 mt-16">
                <h2 class="text-xl font-serif font-light text-muted">Refined <span class="italic">Selects</span></h2>
                <span class="text-[10px] font-bold uppercase tracking-widest text-muted/50"><?= $totalRows ?> Total Results</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-16">
                <?php 
                $regular = ($page === 1) ? array_slice($properties, 3) : $properties;
                foreach ($regular as $i => $prop): 
                ?>
                <a href="<?= BASE ?>property/<?= e($prop['slug']) ?>" class="group cursor-pointer <?= ($i % 3 === 1) ? 'md:mt-10' : '' ?>">
                    <div class="relative w-full aspect-[4/3] image-soft-clip mb-6 overflow-hidden">
                        <img src="<?= imgUrl($prop['featured_image']) ?>"
                             alt="<?= e($prop['title']) ?>"
                             loading="lazy"
                             class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="bg-foreground text-background text-[9px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-full shadow-sm">For <?= e($prop['listing_type']) ?></span>
                        </div>
                        <?php if ($prop['status'] === 'active'): ?>
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-xs font-medium shadow-sm">
                            Active
                        </div>
                        <?php elseif ($prop['status'] === 'sold'): ?>
                        <div class="absolute top-4 right-4 bg-foreground text-background px-4 py-2 rounded-full text-xs font-medium shadow-sm">
                            Sold
                        </div>
                        <?php endif; ?>
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
                            <span class="text-xs text-muted mt-1"><?= (int)$prop['bedrooms'] ?> bd · <?= (int)$prop['bathrooms'] ?> ba · <?= number_format((int)$prop['sqft']) ?> sqft</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="mt-24 flex items-center justify-center gap-3">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&<?= http_build_query(['cat'=>$cat,'type'=>$type,'search'=>$search,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>"
                   class="flex items-center gap-2 px-6 py-3 border border-sand rounded-full text-sm text-muted hover:bg-surface transition-all">
                    &larr; Prev
                </a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&<?= http_build_query(['cat'=>$cat,'type'=>$type,'search'=>$search,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>"
                   class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-medium transition-all
                          <?= $i === $page ? 'bg-foreground text-background' : 'border border-sand text-muted hover:bg-surface' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&<?= http_build_query(['cat'=>$cat,'type'=>$type,'search'=>$search,'min_price'=>$minP,'max_price'=>$maxP,'bedrooms'=>$beds]) ?>"
                   class="flex items-center gap-2 px-6 py-3 border border-sand rounded-full text-sm text-muted hover:bg-surface transition-all">
                    Next &rarr;
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </main>

<?php 
require_once '../includes/footer.php'; 
$output = ob_get_clean();
AdvetCache::set($cacheKey, $output, AdvetCache::getTTL('listing'));
echo $output;
?>
