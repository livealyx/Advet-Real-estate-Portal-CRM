<?php
// FILE: public/property-detail.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$id   = (int)($_GET['id'] ?? 0);
$slug = $_GET['slug'] ?? null;

if (!$id && !$slug) { header('Location: ' . BASE . 'properties'); exit; }

if ($slug) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as agent_name, u.email as agent_email, u.profile_picture as agent_photo, 
               u.phone as agent_phone, u.agency_name, u.is_verified,
               proj.title as project_name, proj.slug as project_slug
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        LEFT JOIN projects proj ON proj.id = p.project_id
        WHERE p.slug = ? AND p.status = 'active'
    ");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as agent_name, u.email as agent_email, u.profile_picture as agent_photo, 
               u.phone as agent_phone, u.agency_name, u.is_verified
        FROM properties p
        LEFT JOIN users u ON u.id = p.agent_id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$id]);
}
$prop = $stmt->fetch();

if (!$prop) {
    $solidNav  = true;
    $pageTitle = 'Property Not Found';
    require_once '../includes/header.php';
    echo '<main class="flex-grow flex items-center justify-center pt-48 pb-32 px-6">
            <div class="text-center max-w-md">
              <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-6">Error 404</p>
              <h1 class="text-5xl font-serif italic mb-6">Sanctuary <span class="text-muted">Absent.</span></h1>
              <p class="text-sm text-muted font-light mb-12 leading-relaxed">This exclusive listing may have been moved or acquired. Please explore our other curated offerings.</p>
              <a href="' . BASE . 'properties" class="inline-block px-10 py-4 bg-foreground text-background text-[10px] font-bold uppercase tracking-widest rounded-2xl hover:scale-105 transition-all">Back to Portfolio</a>
            </div>
          </main>';
    require_once '../includes/footer.php';
    exit;
}

$gallery = json_decode($prop['gallery_images'] ?? '[]', true) ?: [];
$solidNav  = true;
$pageTitle = $prop['title'];
$pageDesc  = $prop['description'] ? substr(strip_tags($prop['description']), 0, 160) : 'View this curated property at Advet Buildwell.';
require_once '../includes/header.php';
?>

<style>
    .property-hero-clip { clip-path: inset(0 0 0 0 round 3rem); }
    .glass-card { background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(223, 216, 204, 0.3); }
    .text-balance { text-wrap: balance; }
</style>

<main class="flex-grow pt-32 pb-32 bg-[#F9F8F6]">
    
    <!-- Cinematic Gallery Header -->
    <section class="max-w-[1440px] mx-auto px-6 lg:px-12 mb-20 reveal">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Main Stage -->
            <div class="lg:col-span-8 relative aspect-[16/10] lg:aspect-auto lg:h-[750px] property-hero-clip overflow-hidden group">
                <img src="<?= imgUrl($prop['featured_image']) ?>" 
                     class="w-full h-full object-cover transition-transform duration-[12s] group-hover:scale-105"
                     alt="<?= e($prop['title']) ?>">
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
            </div>
            
            <!-- Side Frames -->
            <div class="hidden lg:grid lg:col-span-4 grid-rows-2 gap-6">
                <?php 
                $previews = array_slice($gallery, 0, 2);
                for($i=0; $i<2; $i++):
                    $img = $previews[$i] ?? 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&q=80&w=800';
                ?>
                <div class="relative rounded-[2.5rem] overflow-hidden group h-full">
                    <img src="<?= imgUrl($img) ?>" 
                         class="w-full h-full object-cover transition-transform duration-[10s] group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-500"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Content Architecture -->
    <section class="max-w-7xl mx-auto px-6 lg:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-24">
            
            <!-- Information Core -->
            <div class="lg:col-span-8">
                <!-- Branding & Location -->
                <div class="mb-12 reveal" style="animation-delay: 0.2s">
                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <?php if (!empty($prop['project_name'])): ?>
                        <a href="<?= BASE ?>public/project-detail.php?slug=<?= e($prop['project_slug']) ?>" class="group flex items-center gap-3 px-4 py-1.5 bg-accent/10 border border-accent/20 text-accent text-[9px] font-bold uppercase tracking-[0.3em] rounded-full hover:bg-accent hover:text-white transition-all">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A4.833 4.833 0 0 1 12 9a4.833 4.833 0 0 1-7.5 1.332V21"/></svg>
                            Part of <?= e($prop['project_name']) ?>
                        </a>
                        <?php endif; ?>
                        <span class="px-4 py-1.5 bg-accent/10 text-accent text-[9px] font-bold uppercase tracking-[0.3em] rounded-full">Active Listing</span>
                        <span class="px-4 py-1.5 bg-foreground text-background text-[9px] font-bold uppercase tracking-[0.3em] rounded-full">For <?= e($prop['listing_type']) ?></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-sand"></span>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-muted"><?= e($prop['location']) ?></p>
                    </div>
                    <h1 class="text-5xl md:text-8xl font-serif italic font-light mb-8 text-balance leading-[1.1]"><?= e($prop['title']) ?></h1>
                </div>

                <!-- Attributes Dashboard -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-20 reveal" style="animation-delay: 0.3s">
                    <?php
                    $attributes = [
                        ['icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>', 'label' => 'Total Area', 'val' => number_format((int)$prop['sqft']).' <span class="text-[10px]">sqft</span>'],
                        ['icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>', 'label' => 'Bedrooms', 'val' => (int)$prop['bedrooms'].' <span class="text-[10px]">Suites</span>'],
                        ['icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>', 'label' => 'Bathrooms', 'val' => (int)$prop['bathrooms'].' <span class="text-[10px]">Baths</span>'],
                        ['icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>', 'label' => 'Flat Type', 'val' => $prop['flat_type'] ?: 'Raw'],
                    ];
                    foreach($attributes as $attr):
                    ?>
                    <div class="bg-white border border-sand/40 p-8 rounded-[2.5rem] shadow-sm hover:shadow-md transition-all">
                        <div class="text-accent mb-6 opacity-60"><?= $attr['icon'] ?></div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-muted/60 mb-2"><?= $attr['label'] ?></p>
                        <p class="text-xl font-serif"><?= $attr['val'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Property Description -->
                <div class="mb-20 reveal" style="animation-delay: 0.4s">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-10 pb-4 border-b border-sand/30 inline-block">Property Description</h3>
                    <div class="prose-luxury text-lg text-muted font-light leading-relaxed max-w-3xl">
                        <?php if ($prop['description']): ?>
                            <?= $prop['description'] ?>
                        <?php else: ?>
                            <p>An unparalleled manifestation of architectural intent, this residence harmonizes spatial intelligence with an uncompromising material palette. Each corridor serves as a curated gallery, designed to elevate the everyday into a sequence of deliberate experiences.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Secondary Attributes (Optional) -->
                <?php if ($prop['balcony']): ?>
                <div class="mb-20 reveal" style="animation-delay: 0.5s">
                    <div class="inline-flex items-center gap-6 px-10 py-6 bg-surface/30 rounded-3xl border border-sand/20">
                        <svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-1">Additional Metrics</p>
                            <p class="text-sm font-medium">Equipped with <?= (int)$prop['balcony'] ?> Private Balcony Spaces</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Transaction Control (Sticky Sidebar) -->
            <div class="lg:col-span-4">
                <div class="sticky top-40 space-y-8 reveal" style="animation-delay: 0.6s">
                    
                    <!-- Pricing Card -->
                    <div class="glass-card p-12 rounded-[3.5rem] shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-accent/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
                        
                        <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-muted mb-4">Capital Investment</p>
                        <h2 class="text-5xl font-serif italic mb-10"><?= formatPrice((float)$prop['price']) ?></h2>
                        
                        <div class="space-y-4 mb-12">
                            <?php 
                            $stats = [
                                'Transaction Type' => $prop['listing_type'] === 'Rent' ? 'For Lease' : 'For '.$prop['listing_type'],
                                'Portfolio Status' => ucfirst($prop['status']),
                                'Property ID' => 'ADV-'.str_pad($prop['id'], 5, '0', STR_PAD_LEFT),
                                'Archetype' => e($prop['category'])
                            ];
                            foreach($stats as $sk => $sv):
                            ?>
                            <div class="flex justify-between items-center py-3 border-b border-sand/30">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted/60"><?= $sk ?></span>
                                <span class="text-sm font-medium text-foreground"><?= $sv ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <a href="#consultation-form" class="group flex items-center justify-between w-full p-6 bg-foreground text-background rounded-2xl hover:bg-neutral-800 transition-all duration-500 shadow-xl">
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] ml-2">Request Consultation</span>
                            <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-white/20 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </a>
                    </div>

                    <!-- Agent Information Card -->
                    <div class="glass-card p-10 rounded-[3rem] shadow-xl border border-sand/30 overflow-hidden group">
                        <p class="text-[9px] font-bold uppercase tracking-[0.3em] text-accent mb-8">Exclusive Advisor</p>
                        
                        <?php if ($prop['agent_name']): ?>
                            <div class="flex items-center gap-6 mb-8">
                                <div class="relative">
                                    <div class="w-16 h-16 rounded-2xl overflow-hidden border border-sand/40">
                                        <?php if ($prop['agent_photo']): ?>
                                            <img src="<?= imgUrl($prop['agent_photo']) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-surface flex items-center justify-center text-accent font-serif text-xl"><?= substr($prop['agent_name'], 0, 1) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($prop['is_verified']): ?>
                                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-accent text-white rounded-full flex items-center justify-center border-2 border-white shadow-sm" title="Verified Agent">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="text-lg font-serif italic leading-tight"><?= e($prop['agent_name']) ?></h4>
                                    <?php if ($prop['agency_name']): ?>
                                        <p class="text-[10px] text-muted uppercase tracking-widest mt-1"><?= e($prop['agency_name']) ?></p>
                                    <?php else: ?>
                                        <p class="text-[10px] text-muted uppercase tracking-widest mt-1">Advet Elite Partner</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-4 mb-8">
                                <?php if ($prop['agent_phone']): ?>
                                <a href="tel:<?= e($prop['agent_phone']) ?>" class="flex items-center gap-4 group/item">
                                    <div class="w-8 h-8 rounded-xl bg-surface/50 flex items-center justify-center text-muted group-hover/item:text-accent transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </div>
                                    <span class="text-xs font-medium text-muted group-hover/item:text-foreground transition-colors"><?= e($prop['agent_phone']) ?></span>
                                </a>
                                <?php endif; ?>
                                <a href="mailto:<?= e($prop['agent_email']) ?>" class="flex items-center gap-4 group/item">
                                    <div class="w-8 h-8 rounded-xl bg-surface/50 flex items-center justify-center text-muted group-hover/item:text-accent transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <span class="text-xs font-medium text-muted group-hover/item:text-foreground transition-colors"><?= e($prop['agent_email']) ?></span>
                                </a>
                            </div>

                            <a href="#consultation-form" class="block text-center py-4 bg-accent/10 border border-accent/20 text-accent text-[9px] font-bold uppercase tracking-widest rounded-2xl hover:bg-accent hover:text-white transition-all">
                                Contact Agent
                            </a>
                        <?php else: ?>
                            <div class="py-6 text-center italic text-muted text-sm font-serif">
                                Agent information not available.
                            </div>
                            <a href="#consultation-form" class="block text-center py-4 bg-foreground text-background text-[9px] font-bold uppercase tracking-widest rounded-2xl hover:bg-neutral-800 transition-all">
                                Connect with Studio
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Trust Tag -->
                    <div class="flex items-center gap-4 px-8 py-6 border border-sand/40 rounded-3xl">
                        <div class="w-10 h-10 rounded-full bg-sand/20 flex items-center justify-center text-accent">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-muted leading-relaxed">Verified by Advet <br>Quality Assurance Protocol</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Artistic Gallery Collection -->
    <?php if (count($gallery) > 2): ?>
    <section class="max-w-[1440px] mx-auto px-6 lg:px-12 mt-40 reveal">
        <div class="mb-16 flex items-center justify-between">
            <h2 class="text-3xl font-serif font-light italic">Spatial <span class="text-muted">Perspectives</span></h2>
            <div class="h-px bg-sand/30 flex-grow mx-12 hidden md:block"></div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-muted"><?= count($gallery) ?> Frames</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-8">
            <?php foreach (array_slice($gallery, 2) as $idx => $gImg): ?>
            <div class="group relative overflow-hidden rounded-[2rem] aspect-[4/5] <?= ($idx % 3 == 0) ? 'md:col-span-2 md:aspect-auto md:h-full' : '' ?>">
                <img src="<?= imgUrl($gImg) ?>" 
                     loading="lazy"
                     class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-110">
                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-8">
                    <p class="text-white text-[9px] font-bold uppercase tracking-widest translate-y-4 group-hover:translate-y-0 transition-transform">Perspective <?= $idx + 3 ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Consultation Protocol -->
    <section id="consultation-form" class="max-w-4xl mx-auto px-6 mt-40 reveal">
        <div class="bg-white border border-sand/40 rounded-[3.5rem] p-12 md:p-20 shadow-xl relative overflow-hidden">
            <div class="absolute -top-32 -left-32 w-64 h-64 bg-accent/5 rounded-full blur-3xl"></div>
            
            <div class="text-center mb-16">
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-6">Contact Property</p>
                <h2 class="text-4xl md:text-6xl font-serif font-light italic mb-8">Ask about this <span class="text-muted">property.</span></h2>
                <p class="text-sm text-muted font-light max-w-lg mx-auto leading-relaxed italic">Our advisors will respond to your inquiry within 24 business hours.</p>
            </div>

            <form method="POST" action="<?= BASE ?>actions/submit-inquiry.php" class="space-y-8">
                <input type="hidden" name="property_id" value="<?= (int)$prop['id'] ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-muted/60 ml-2">Full Name *</label>
                        <input type="text" name="name" required placeholder="Your name"
                               class="w-full px-8 py-5 bg-surface/30 border border-sand/20 rounded-2xl text-sm focus:bg-white focus:border-accent outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[9px] font-bold uppercase tracking-widest text-muted/60 ml-2">Email *</label>
                        <input type="email" name="email" required placeholder="email@example.com"
                               class="w-full px-8 py-5 bg-surface/30 border border-sand/20 rounded-2xl text-sm focus:bg-white focus:border-accent outline-none transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-muted/60 ml-2">Phone</label>
                    <input type="tel" name="phone" placeholder="+91 000-000-0000"
                           class="w-full px-8 py-5 bg-surface/30 border border-sand/20 rounded-2xl text-sm focus:bg-white focus:border-accent outline-none transition-all">
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-bold uppercase tracking-widest text-muted/60 ml-2">Message *</label>
                    <textarea name="message" required rows="4" placeholder="How can we help you with this property?"
                              class="w-full px-8 py-5 bg-surface/30 border border-sand/20 rounded-2xl text-sm focus:bg-white focus:border-accent outline-none transition-all resize-none"></textarea>
                </div>

                <div class="flex justify-center pt-8">
                    <button type="submit" class="px-16 py-6 bg-foreground text-background text-[10px] font-bold uppercase tracking-[0.3em] rounded-2xl hover:bg-neutral-800 transition-all shadow-2xl hover:scale-[1.02] transform">
                        Submit Inquiry
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Related Properties Section -->
    <section class="pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
            <header class="mb-12 flex justify-between items-end reveal">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-4">Discover More</p>
                    <h2 class="text-4xl font-serif font-light italic">Related <span class="text-muted">Listings</span></h2>
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

            <div id="relatedPropertiesContainer" class="flex gap-8 overflow-x-auto pb-12 snap-x snap-mandatory scroll-smooth hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                <!-- Skeletons -->
                <?php for($i=0; $i<4; $i++): ?>
                <div class="related-skeleton min-w-[300px] sm:min-w-[350px] w-full sm:w-[350px] animate-pulse">
                    <div class="aspect-[4/3] bg-surface/50 rounded-[2.5rem] mb-6"></div>
                    <div class="h-6 bg-surface/50 rounded-full w-3/4 mb-4"></div>
                    <div class="h-4 bg-surface/50 rounded-full w-1/2"></div>
                </div>
                <?php endfor; ?>
            </div>

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

    const params = new URLSearchParams({
        type: 'property',
        id: <?= json_encode((int)$prop['id']) ?>,
        location: <?= json_encode($prop['location']) ?>,
        category: <?= json_encode($prop['category']) ?>,
        price: <?= json_encode((float)$prop['price']) ?>
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

    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => container.scrollBy({ left: -400, behavior: 'smooth' }));
        nextBtn.addEventListener('click', () => container.scrollBy({ left: 400, behavior: 'smooth' }));
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
#relatedPropertiesContainer { -webkit-overflow-scrolling: touch; }
</style>


<?php require_once '../includes/footer.php'; ?>
