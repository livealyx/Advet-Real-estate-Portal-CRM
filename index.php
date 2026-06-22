<?php
// FILE: index.php  ← ROOT HOMEPAGE
session_start();
require_once 'config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);
$siteName = $settings['site_name'] ?? 'Advet Buildwell';
$siteTagline = $settings['site_tagline'] ?? 'Human-Centric Spaces';
$pageDesc = e($siteName) . ' — ' . e($siteTagline);

$cacheKey = AdvetCache::generateKey('homepage', []);
$cachedContent = AdvetCache::get($cacheKey);
if ($cachedContent) {
    echo $cachedContent;
    exit;
}

ob_start();

// Fetch 3 featured active properties for the hero showcase
$featuredProps = $pdo->query(
    "SELECT id, title, slug, location, price, bedrooms, bathrooms, featured_image
       FROM properties
      WHERE status = 'active'
      ORDER BY created_at DESC
      LIMIT 3"
)->fetchAll();

$latest = $featuredProps[0] ?? null;

// Fetch 2 latest published stories for journal teaser
$latestStories = $pdo->query(
    "SELECT id, title, slug, excerpt, cover_image
       FROM stories
      WHERE published_at IS NOT NULL AND published_at <= NOW()
      ORDER BY published_at DESC
      LIMIT 2"
)->fetchAll();

// Fetch 2 latest approved testimonials
$testimonialsOverview = $pdo->query(
    "SELECT name, affiliation, content, rating
       FROM testimonials
      WHERE status = 'approved'
      ORDER BY created_at DESC
      LIMIT 2"
)->fetchAll();

// Fetch 3 featured projects for neighborhood spotlight
$featuredProjects = $pdo->query("SELECT * FROM featured_projects ORDER BY display_order ASC LIMIT 3")->fetchAll();

// Fetch 3 featured commercial properties (archetypes)
$featuredCommercial = $pdo->query("SELECT * FROM space_archetypes ORDER BY display_order ASC LIMIT 3")->fetchAll();

$siteAccent = $settings['accent_color'] ?? '#899178';
$isHomepage = true;

$extraHead = '
    <link rel="stylesheet" href="' . BASE . 'assets/css/hero-redesign.css?v=' . filemtime(__DIR__ . "/assets/css/hero-redesign.css") . '">
    <style>
        :root {
            --hero-accent: ' . $siteAccent . ' !important;
        }
    </style>
';

require_once 'includes/header.php';
?>


<!-- Premium Hero Redesign Section -->
<?php 
$sliderImagesRaw = json_decode($settings['site_hero_slider_images'] ?? '[]', true);
if (!is_array($sliderImagesRaw)) $sliderImagesRaw = [];
$heroImages = !empty($sliderImagesRaw) ? $sliderImagesRaw : [$settings['site_hero_image'] ?? 'assets/images/new_hero.png'];
$heroImagesJson = json_encode(array_map('imgUrl', $heroImages));
?>
<section class="hero-premium" id="hero-slider">
    <!-- Dual Layer Cross-Fade Backgrounds -->
    <div id="hero-layer-1" class="hero-bg-layer active" style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('<?= imgUrl($heroImages[0]) ?>');"></div>
    <div id="hero-layer-2" class="hero-bg-layer" style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('<?= imgUrl($heroImages[0]) ?>');"></div>

    <!-- Main Hero Content -->
    <div class="hero-content relative z-10">
        <div class="hero-main-title">
            <h1>#NayaGharNayiZindagi <br> <span style="font-style: italic; font-weight: 300; opacity: 0.8;">shuru karein</span></h1>
        </div>

        <!-- Redesigned Search Card -->
        <div class="search-card-container">
            <form action="<?= BASE ?>properties" method="GET" class="search-card">
                <div class="search-card-top">
                    <div class="search-card-title">Find The Best Place</div>
                    <div class="search-toggle-group">
                        <input type="radio" id="type-buy" name="type" value="Buy" class="hidden" checked>
                        <label for="type-buy" class="search-toggle active" onclick="updateToggle(this)">Buy/Sell</label>

                        <input type="radio" id="type-rent" name="type" value="Rent" class="hidden">
                        <label for="type-rent" class="search-toggle" onclick="updateToggle(this)">Rent/Lease</label>
                    </div>
                </div>

                <div class="search-card-bottom">
                    <div class="filter-columns">
                        <!-- Filter: Category -->
                        <div class="filter-col">
                            <label class="filter-label">Looking For</label>
                            <div class="filter-select-wrapper custom-dropdown" data-id="cat-filter">
                                <span class="filter-icon">
                                    <img src="https://cdn.hugeicons.com/icons/home-03-twotone-rounded.svg?v=1.0.0" alt="Home Icon">
                                </span>
                                <div class="selected-display">Select Property Type</div>
                                <svg class="dropdown-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                                
                                <div class="custom-dropdown-menu">
                                    <div class="custom-dropdown-item" data-value="" data-title="All Types" data-desc="Explore all sanctuaries">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/home-03-twotone-rounded.svg?v=1.0.0" alt="Home Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">All Types</span>
                                            <span class="custom-item-desc">Explore all sanctuaries</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Flat/Apartment" data-title="Flat/Apartment" data-desc="Elevated urban living">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/building-05-twotone-rounded.svg?v=1.0.0" alt="Apartment Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Flat/Apartment</span>
                                            <span class="custom-item-desc">Elevated urban living</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Plot" data-title="Plot" data-desc="Secure your own land">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/land-plot-twotone-rounded.svg?v=1.0.0" alt="Plot Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Plot</span>
                                            <span class="custom-item-desc">Secure your own land</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Commercial" data-title="Commercial" data-desc="Prime business locations">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/building-02-twotone-rounded.svg?v=1.0.0" alt="Commercial Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Commercial</span>
                                            <span class="custom-item-desc">Prime business locations</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="cat" id="cat-hidden-input" value="">
                            </div>
                        </div>

                        <!-- Filter: Price Range -->
                        <div class="filter-col">
                            <label class="filter-label">Price</label>
                            <div class="filter-select-wrapper custom-dropdown" data-id="price-filter">
                                <span class="filter-icon">
                                    <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                </span>
                                <div class="selected-display">Choose Price Range</div>
                                <svg class="dropdown-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                                
                                <div class="custom-dropdown-menu">
                                    <div class="custom-dropdown-item" data-value="" data-title="All Prices" data-desc="Show all price points">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">All Prices</span>
                                            <span class="custom-item-desc">Show all price points</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="0,2000000" data-title="Below ₹20 Lacs" data-desc="Affordable sanctuaries">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Below ₹20 Lacs</span>
                                            <span class="custom-item-desc">Affordable sanctuaries</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="2000000,3000000" data-title="₹20 - ₹30 Lacs" data-desc="Mid-range options">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">₹20 - ₹30 Lacs</span>
                                            <span class="custom-item-desc">Mid-range options</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="3000000,5000000" data-title="₹30 - ₹50 Lacs" data-desc="Premium living">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">₹30 - ₹50 Lacs</span>
                                            <span class="custom-item-desc">Premium living</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="5000000,999999999" data-title="Above ₹50 Lacs" data-desc="Luxury estates">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/auto-conversations-twotone-rounded.svg?v=1.0.0" alt="Price Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Above ₹50 Lacs</span>
                                            <span class="custom-item-desc">Luxury estates</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="min_price" id="min_price">
                                <input type="hidden" name="max_price" id="max_price">
                            </div>
                        </div>

                        <!-- Filter: Location -->
                        <div class="filter-col">
                            <label class="filter-label">Locations</label>
                            <div class="filter-select-wrapper custom-dropdown" data-id="loc-filter">
                                <span class="filter-icon">
                                    <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                </span>
                                <div class="selected-display">Select A Location</div>
                                <svg class="dropdown-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                                
                                <div class="custom-dropdown-menu">
                                    <div class="custom-dropdown-item" data-value="" data-title="All Locations" data-desc="Search everywhere">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">All Locations</span>
                                            <span class="custom-item-desc">Search everywhere</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Bhiwadi" data-title="Bhiwadi" data-desc="The industrial hub">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Bhiwadi</span>
                                            <span class="custom-item-desc">The industrial hub</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Dharuhera" data-title="Dharuhera" data-desc="Modern living outskirts">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Dharuhera</span>
                                            <span class="custom-item-desc">Modern living outskirts</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Thara, Bhiwadi" data-title="Thara, Bhiwadi" data-desc="Premium residential zone">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Thara, Bhiwadi</span>
                                            <span class="custom-item-desc">Premium residential zone</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Salarpur, Bhiwadi" data-title="Salarpur, Bhiwadi" data-desc="Upcoming urban area">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Salarpur, Bhiwadi</span>
                                            <span class="custom-item-desc">Upcoming urban area</span>
                                        </div>
                                    </div>
                                    <div class="custom-dropdown-item" data-value="Tapukara, Bhiwadi" data-title="Tapukara, Bhiwadi" data-desc="Rapidly developing sector">
                                        <div class="custom-item-icon">
                                            <img src="https://cdn.hugeicons.com/icons/location-01-twotone-rounded.svg?v=1.0.0" alt="Location Icon">
                                        </div>
                                        <div class="custom-item-info">
                                            <span class="custom-item-title">Tapukara, Bhiwadi</span>
                                            <span class="custom-item-desc">Rapidly developing sector</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="search" id="loc-hidden-input" value="">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-search-large">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        Search Properties
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="hero-progress-container">
        <div class="hero-progress-bar" id="hero-progress"></div>
    </div>

    <script>
        function updateToggle(label) {
            const labels = label.parentElement.querySelectorAll('.search-toggle');
            labels.forEach(l => l.classList.remove('active'));
            label.classList.add('active');
        }

        // Reusable Custom Dropdown Logic
        document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
            const display = dropdown.querySelector('.selected-display');
            const items = dropdown.querySelectorAll('.custom-dropdown-item');
            const id = dropdown.getAttribute('data-id');

            dropdown.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close other dropdowns
                document.querySelectorAll('.custom-dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.remove('active');
                });
                dropdown.classList.toggle('active');
            });

            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const value = item.getAttribute('data-value');
                    const title = item.getAttribute('data-title');
                    
                    display.innerText = title;
                    
                    items.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');

                    // Update hidden inputs based on dropdown ID
                    if (id === 'cat-filter') {
                        document.getElementById('cat-hidden-input').value = value;
                    } else if (id === 'price-filter') {
                        if (value) {
                            const parts = value.split(',');
                            document.getElementById('min_price').value = parts[0];
                            document.getElementById('max_price').value = parts[1];
                        } else {
                            document.getElementById('min_price').value = '';
                            document.getElementById('max_price').value = '';
                        }
                    } else if (id === 'loc-filter') {
                        document.getElementById('loc-hidden-input').value = value;
                    }
                    
                    dropdown.classList.remove('active');
                });
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-dropdown').forEach(d => d.classList.remove('active'));
        });

        function updatePriceRange(select) {
            const val = select.value;
            if (val) {
                const parts = val.split(',');
                document.getElementById('min_price').value = parts[0];
                document.getElementById('max_price').value = parts[1];
            } else {
                document.getElementById('min_price').value = '';
                document.getElementById('max_price').value = '';
            }
        }

        // Sync with the mobile drawer already in header.php
        document.getElementById('hero-mobile-btn')?.addEventListener('click', () => {
            const mob = document.getElementById('mobile-menu');
            if (mob) {
                mob.classList.remove('translate-x-full');
                mob.classList.add('translate-x-0');
            }
        });

        // Enhanced Hero Slider Logic with Smooth Dual-Layer Cross-Fade
        const sliderImages = <?= $heroImagesJson ?>;
        if (sliderImages.length > 1) {
            let currentIndex = 0;
            const progress = document.getElementById('hero-progress');
            const layer1 = document.getElementById('hero-layer-1');
            const layer2 = document.getElementById('hero-layer-2');
            const duration = 6000; // 6 seconds

            function startAutoplay() {
                // Initialize first progress fill
                setTimeout(() => {
                    progress.style.width = '100%';
                }, 100);

                setInterval(() => {
                    // 1. Reset progress (instant)
                    progress.style.transition = 'none';
                    progress.style.width = '0%';
                    
                    // 2. Identify Active & Next Layers
                    const activeLayer = layer1.classList.contains('active') ? layer1 : layer2;
                    const nextLayer = activeLayer === layer1 ? layer2 : layer1;
                    
                    // 3. Prepare Next Layer
                    currentIndex = (currentIndex + 1) % sliderImages.length;
                    nextLayer.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('${sliderImages[currentIndex]}')`;
                    
                    // 4. Trigger Cross-Fade
                    activeLayer.classList.remove('active');
                    nextLayer.classList.add('active');

                    // 5. Force Reflow & Start Next Progress Fill
                    void progress.offsetWidth;
                    progress.style.transition = `width ${duration}ms linear`;
                    progress.style.width = '100%';
                }, duration);
            }

            window.addEventListener('load', startAutoplay);
        }
    </script>
</section>


<!-- Philosophy Break -->
<section id="philosophy" class="py-24 bg-surface/50">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <svg class="w-10 h-10 text-accent mx-auto mb-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 11.9896V14.5C3 17.7998 3 19.4497 4.02513 20.4749C5.05025 21.5 6.70017 21.5 10 21.5H14C17.2998 21.5 18.9497 21.5 19.9749 20.4749C21 19.4497 21 17.7998 21 14.5V11.9896C21 10.3083 21 9.46773 20.6441 8.74005C20.2882 8.01237 19.6247 7.49628 18.2976 6.46411L16.2976 4.90855C14.2331 3.30285 13.2009 2.5 12 2.5C10.7991 2.5 9.76689 3.30285 7.70242 4.90855L5.70241 6.46411C4.37533 7.49628 3.71179 8.01237 3.3559 8.74005C3 9.46773 3 10.3083 3 11.9896Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M18 15C18 13.8954 17.1046 13 16 13C14.8954 13 14 13.8954 14 15C14 16.1046 14.8954 17 16 17C17.1046 17 18 16.1046 18 15Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        <h2 class="text-3xl md:text-5xl font-serif font-light leading-tight mb-8">
            Find a Home <span class="italic text-muted">That Fits You</span>
        </h2>
        <p class="text-muted text-lg font-light leading-relaxed">
            At Advet Buildwell, we believe your home should be comfortable and peaceful. We list properties that focus on natural light, good materials, and practical layouts. Every property in our portfolio is carefully selected.
        </p>
    </div>
</section>

<!-- Curated Properties (Dynamic from DB) -->
<section id="properties" class="py-32 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 w-full">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
        <div>
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Property Portfolio</p>
            <h2 class="text-4xl sm:text-5xl font-serif font-light">Featured <span
                    class="italic text-muted">Properties</span></h2>
        </div>
        <a href="<?= BASE ?>properties"
            class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors">
            View all properties
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 17l10-10M17 7H7M17 7v10" />
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-16">
        <?php if (empty($featuredProps)): ?>
            <div class="col-span-2 text-center text-muted py-16">
                <p class="text-2xl font-serif italic mb-4">No properties listed yet.</p>
                <p class="text-sm">Check back soon as we curate new sanctuaries.</p>
            </div>
        <?php else: ?>
            <?php foreach ($featuredProps as $i => $prop): ?>
                <a href="<?= BASE ?>property/<?= e($prop['slug']) ?>"
                    class="group cursor-pointer <?= $i % 2 !== 0 ? 'md:mt-24' : '' ?>">
                    <div class="relative w-full aspect-[4/3] image-soft-clip mb-6 overflow-hidden">
                        <img src="<?= imgUrl($prop['featured_image']) ?>" alt="<?= e($prop['title']) ?>"
                            class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                        <div
                            class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-full text-xs font-medium shadow-sm">
                            Active
                        </div>
                    </div>
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-serif mb-2 group-hover:text-accent transition-colors">
                                <?= e($prop['title']) ?></h3>
                            <p class="text-muted font-light text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s-8-7.5-8-12a8 8 0 1116 0c0 4.5-8 12-8 12z" />
                                    <path d="M12 11a2 2 0 100-4 2 2 0 000 4z" />
                                </svg>
                                <?= e($prop['location']) ?>
                            </p>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <span class="bg-surface px-3 py-1 rounded-lg text-sm font-medium mb-2">
                                <?= (int) $prop['bedrooms'] ?> Beds, <?= (int) $prop['bathrooms'] ?> Baths
                            </span>
                            <span class="text-sm font-medium text-accent"><?= formatPrice((float) $prop['price']) ?></span>
                            <span
                                class="text-sm border-b border-transparent group-hover:border-foreground transition-colors mt-1">View
                                details</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Real Estate Service Pillars -->
<section class="border-y border-sand/30 bg-surface/30">
    <div
        class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-sand/40 text-center">
        <div class="py-16 px-8 flex flex-col items-center group">
            <div
                class="w-16 h-16 rounded-full bg-surface flex items-center justify-center mb-6 group-hover:bg-sand transition-colors">
                <svg class="w-6 h-6 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="M21 21l-4.35-4.35" />
                    <path d="M11 8v6M8 11h6" />
                </svg>
            </div>
            <h4 class="text-xl font-serif mb-3">Property Buying</h4>
            <p class="text-muted font-light text-sm leading-relaxed">Expert guidance in finding and buying high-value
                properties that align with your lifestyle and investment goals.</p>
        </div>
        <div class="py-16 px-8 flex flex-col items-center group">
            <div
                class="w-16 h-16 rounded-full bg-surface flex items-center justify-center mb-6 group-hover:bg-sand transition-colors">
                <svg class="w-6 h-6 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" />
                    <line x1="7" y1="7" x2="7.01" y2="7" />
                </svg>
            </div>
            <h4 class="text-xl font-serif mb-3">Property Selling</h4>
            <p class="text-muted font-light text-sm leading-relaxed">Tailored marketing and negotiation strategies to
                sell your property at its maximum market potential.</p>
        </div>
        <div class="py-16 px-8 flex flex-col items-center group">
            <div
                class="w-16 h-16 rounded-full bg-surface flex items-center justify-center mb-6 group-hover:bg-sand transition-colors">
                <svg class="w-6 h-6 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3-3.5 3.5z" />
                </svg>
            </div>
            <h4 class="text-xl font-serif mb-3">Property Leasing</h4>
            <p class="text-muted font-light text-sm leading-relaxed">Connecting tenants with quality rental properties through a smooth and managed process.</p>
        </div>
    </div>
</section>

<!-- The Process -->
<section class="py-32 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 w-full text-center md:text-left">
    <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Our Process</p>
    <h2 class="text-4xl sm:text-5xl font-serif font-light mb-16">The Journey <span class="italic text-muted">to
            Home</span></h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-4 relative">
        <div class="hidden md:block absolute top-[28px] left-0 w-full h-[1px] bg-sand"></div>
        <?php foreach ([['01', 'Discovery', 'We dive deep into your goals to align our search or marketing strategy with your unique vision.'], ['02', 'Strategy', 'Utilizing market intelligence to find off-market gems or position your asset for the right audience.'], ['03', 'Negotiation', 'We handle the complexities of the deal, securing the best possible terms through precise negotiation.'], ['04', 'Possession', 'A seamless transition from signature to key handover, ensuring your new chapter begins effortlessly.']] as $step): ?>
            <div class="relative z-10 flex flex-col items-center md:items-start group">
                <div
                    class="w-14 h-14 rounded-full bg-surface border-4 border-background flex items-center justify-center text-accent font-serif text-xl mb-6 group-hover:bg-sand transition-colors">
                    <?= $step[0] ?></div>
                <h4 class="text-lg font-medium mb-2"><?= $step[1] ?></h4>
                <p class="text-sm font-light text-muted leading-relaxed max-w-[250px]"><?= $step[2] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Impact Quote (Light Re-imagining) -->
<section class="bg-sand/5 py-32 border-y border-sand/20 relative overflow-hidden">
    <div
        class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/50 via-transparent to-transparent opacity-50">
    </div>
    <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">
        <svg class="w-10 h-10 text-accent/30 mx-auto mb-8" viewBox="0 0 24 24" fill="currentColor">
            <path
                d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
        </svg>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-serif font-light leading-snug mb-8 text-foreground/80">
            “Architecture is not just about buildings, it is a way of life.”
        </h2>
        <div class="w-12 h-px bg-accent/20 mx-auto mb-8"></div>
        <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-muted">— AR Raman</p>
    </div>
</section>

<!-- Advet By The Numbers -->
<section class="py-24 bg-surface/30 border-b border-sand/20">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
        <?php foreach ([['12', 'Years Experience'], ['₹10,000+ Cr', 'Total Portfolio Value'], ['98%', 'Client Retention'], ['240+', 'Properties Sold']] as $stat): ?>
            <div>
                <p class="text-4xl md:text-5xl font-serif text-accent mb-2"><?= $stat[0] ?></p>
                <p class="text-xs uppercase tracking-widest text-muted font-medium"><?= $stat[1] ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured Commercial Properties -->
<?php if (!empty($featuredCommercial)): ?>
<section class="py-32 bg-[#FDFCFB]">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
            <div>
                <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Commercial Properties</p>
                <h2 class="text-4xl sm:text-5xl font-serif font-light">Featured <span class="italic text-muted">Commercial</span></h2>
            </div>
            <a href="<?= BASE ?>properties?cat=Commercial"
                class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors">
                Explore commercial
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17l10-10M17 7H7M17 7v10" />
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($featuredCommercial as $i => $c): ?>
                <div class="group relative aspect-[3/4] image-soft-clip overflow-hidden cursor-pointer">
                    <img src="<?= imgUrl($c['image_path']) ?>" alt="<?= e($c['title']) ?>"
                        class="w-full h-full object-cover transition-transform duration-[10s] group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-foreground/60 via-transparent to-transparent opacity-80"></div>
                    <div class="absolute bottom-8 left-8 right-8 text-white">
                        <h4 class="text-2xl font-serif mb-2"><?= e($c['title']) ?></h4>
                        <p class="text-white/80 text-xs font-light line-clamp-2"><?= e($c['description']) ?></p>
                        <div class="mt-6 w-8 h-px bg-accent/50 group-hover:w-16 transition-all duration-500"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Neighborhood Spotlight -->
<section class="py-32 bg-background overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
        <div class="mb-16">
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Local Expertise</p>
            <h2 class="text-4xl sm:text-5xl font-serif font-light">Featured <span
                    class="italic text-muted">Projects</span></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $offsets = ['', 'md:mt-12', 'md:-mt-6'];
            foreach ($featuredProjects as $i => $p):
                $offset = $offsets[$i] ?? '';
                ?>
                <div class="group relative aspect-[4/5] image-soft-clip overflow-hidden cursor-pointer <?= $offset ?>">
                    <img src="<?= imgUrl($p['image_path']) ?>" alt="<?= e($p['title']) ?>"
                        class="w-full h-full object-cover transition-transform duration-[10s] group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-foreground/80 via-transparent to-transparent"></div>
                    <div class="absolute bottom-8 left-8 right-8 text-white">
                        <h4 class="text-2xl font-serif mb-2"><?= e($p['title']) ?></h4>
                        <p class="text-white/70 text-sm font-light"><?= e($p['description']) ?></p>
                        <div
                            class="mt-4 flex items-center gap-2 text-xs font-medium uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">
                            View Project
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-24 bg-surface/20">
    <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
        <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-24">
            <div class="relative">
                <div class="aspect-[4/5] image-soft-clip overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1554469384-e58fac16e23a?auto=format&fit=crop&q=80&w=800"
                        alt="Client Home" class="w-full h-full object-cover grayscale opacity-50">
                </div>
            </div>
            <div>
                <div class="flex justify-between items-end mb-6">
                    <p class="text-xs font-medium uppercase tracking-widest text-accent">Testimonials</p>
                </div>
                <h2 class="text-4xl md:text-6xl font-serif font-light mb-12 leading-tight">Quiet confidence <br><span
                        class="italic text-muted">loudly heard.</span></h2>

                <?php if (empty($testimonialsOverview)): ?>
                    <p class="text-muted font-light italic">No perspectives shared recently.</p>
                <?php else: ?>
                    <div class="space-y-12">
                        <?php foreach ($testimonialsOverview as $t): ?>
                            <div class="border-l-2 border-accent/20 pl-8 transition-colors hover:border-accent">
                                <div class="flex gap-1 mb-3">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-3 h-3 <?= $i <= $t['rating'] ? 'text-accent' : 'text-sand' ?> fill-current"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-xl font-serif italic mb-4">"<?= e($t['content']) ?>"</p>
                                <cite class="text-xs uppercase tracking-widest text-muted font-medium">—
                                    <?= e($t['name']) ?>        <?= $t['affiliation'] ? ', ' . e($t['affiliation']) : '' ?></cite>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <a href="<?= BASE ?>testimonials.php"
                    class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors mt-12">
                    Read all testimonials
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 17l10-10M17 7H7M17 7v10" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Journal Teaser (Dynamic from DB) -->
<section class="py-32 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 w-full">
    <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
        <div>
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Field Notes</p>
            <h2 class="text-4xl sm:text-5xl font-serif font-light">From the <span
                    class="italic text-muted">Journal</span></h2>
        </div>
        <a href="<?= BASE ?>stories.php"
            class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors">
            Read all stories
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 17l10-10M17 7H7M17 7v10" />
            </svg>
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <?php if (empty($latestStories)): ?>
            <p class="text-muted font-light italic col-span-2">No stories published yet.</p>
        <?php else: ?>
            <?php foreach ($latestStories as $story): ?>
                <a href="<?= BASE ?>story-detail.php?slug=<?= e($story['slug']) ?>"
                    class="group flex flex-col md:flex-row gap-8 items-center md:items-start cursor-pointer">
                    <div class="w-full md:w-48 aspect-square image-soft-clip overflow-hidden shrink-0">
                        <img src="<?= imgUrl($story['cover_image']) ?>" alt="<?= e($story['title']) ?>"
                            class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105">
                    </div>
                    <div>
                        <h3 class="text-2xl font-serif mb-2 group-hover:text-accent transition-colors"><?= e($story['title']) ?>
                        </h3>
                        <p class="text-muted font-light text-sm line-clamp-3"><?= e($story['excerpt']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Service Excellence Banner (Light Re-imagining) -->
<section class="relative py-48 overflow-hidden bg-[#FDFCFB] border-t border-sand/20 group">
    <div class="absolute inset-0 z-0 opacity-10">
        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&q=80&w=2000"
            alt="Excellence Mirror"
            class="w-full h-full object-cover transition-transform duration-[12s] group-hover:scale-110">
    </div>
    <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 text-center">
        <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-6">Service Excellence</p>
        <h2 class="text-5xl md:text-7xl font-serif font-light text-foreground mb-12 leading-tight">
            Buy. Sell. <br><span class="italic text-muted">A Legacy.</span>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16 text-muted/80 font-light">
            <?php foreach ([['Property Buying', 'We help you find the right property, including exclusive options not easily available in the market.'], ['Property Selling', 'We market your property effectively and help you get the best possible price.'], ['Property Leasing', 'We connect tenants with suitable rental properties through a smooth and reliable process.']] as $svc): ?>
                <div
                    class="bg-white/50 backdrop-blur-sm p-8 rounded-[2.5rem] border border-sand/10 hover:border-accent/20 transition-all">
                    <h4 class="text-xl font-serif text-foreground mb-3"><?= $svc[0] ?></h4>
                    <p class="text-sm leading-relaxed mx-auto max-w-[280px]"><?= $svc[1] ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?= BASE ?>contact.php"
            class="inline-flex items-center justify-center gap-3 bg-foreground text-background px-12 py-5 rounded-full hover:bg-accent hover:text-white transition-all transform hover:-translate-y-1 shadow-[0_20px_40px_-5px_rgba(0,0,0,0.1)] font-bold text-[10px] uppercase tracking-widest">
            Inquire About Services
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
        </a>
    </div>
</section>

<?php 
require_once 'includes/footer.php'; 
$output = ob_get_clean();
AdvetCache::set($cacheKey, $output, AdvetCache::getTTL('listing'));
echo $output;
?>