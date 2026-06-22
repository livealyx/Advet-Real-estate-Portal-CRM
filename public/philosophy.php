<?php
// FILE: public/philosophy.php
session_start();
$solidNav  = true;
$pageTitle = 'Our Philosophy';
$pageDesc  = 'The art of real estate is a slow, methodical practice of editing out the noise — Advet Buildwell.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-24">

        <div class="max-w-4xl mx-auto px-6 sm:px-12 lg:px-16 text-center reveal mb-32">
            <p class="text-xs font-medium uppercase tracking-widest text-accent mb-6">Our Approach</p>
            <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8">
                Simple and <br><span class="italic text-muted">Practical Living.</span>
            </h1>
            <p class="text-lg text-muted font-light leading-relaxed max-w-2xl mx-auto">
                We believe a home should be comfortable and easy to live in. It should support your daily life without adding stress. Our goal is to help you find properties that truly fit your needs.
            </p>
        </div>

        <!-- Expert Curation Split -->
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-2 gap-16 items-center reveal mb-32" style="animation-delay: 0.2s;">
            <div class="aspect-square image-soft-clip w-full overflow-hidden">
                <img src="https://images.unsplash.com/photo-1598228723793-52759bba239c?auto=format&fit=crop&q=80&w=800"
                     alt="Detail of wood and stone textures"
                     class="w-full h-full object-cover">
            </div>
            <div>
                <svg class="w-8 h-8 text-accent mb-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8m18 0l-9-5-9 5m18 0v2H3V8"/>
                </svg>
                <h2 class="text-3xl font-serif mb-6">Expert Property Selection</h2>
                <div class="space-y-4 text-muted font-light leading-relaxed">
                    <p>We carefully select the properties we work with. Each listing is checked for quality, design, and location. Whether you are buying, selling, or renting, we make sure the property is the right match for you.</p>
                    <p>We combine market knowledge with customer needs to provide the best possible service in every deal.</p>
                </div>
            </div>
        </div>

        <!-- Principles -->
        <section class="bg-surface/30 py-32 mb-0">
            <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">
                <div class="text-center mb-20">
                    <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">Our Principles</p>
                    <h2 class="text-4xl sm:text-5xl font-serif font-light">Architecture of <span class="italic text-muted">thought.</span></h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <?php foreach ([
                        ['Keep It Simple','Good spaces don’t need to be complicated. Clean design and practical layouts work best.','https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&q=80&w=600'],
                        ['Focus on Environment','We consider factors like sunlight, ventilation, and surroundings before recommending any property.','https://images.unsplash.com/photo-1598228723793-52759bba239c?auto=format&fit=crop&q=80&w=600'],
                        ['Long-Term Value','We focus on properties that remain valuable and comfortable over time.','https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&q=80&w=600'],
                    ] as [$title, $desc, $img]): ?>
                    <div class="group">
                        <div class="aspect-[4/3] image-soft-clip overflow-hidden mb-8">
                            <img src="<?= e($img) ?>" alt="<?= e($title) ?>"
                                 class="w-full h-full object-cover grayscale transition-all duration-700 group-hover:grayscale-0 group-hover:scale-105">
                        </div>
                        <h3 class="text-2xl font-serif mb-4"><?= e($title) ?></h3>
                        <p class="text-muted font-light text-sm leading-relaxed"><?= e($desc) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Service Excellence Banner -->
        <section class="relative py-48 overflow-hidden group">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&q=80&w=2000"
                     alt="Excellence Banner"
                     class="w-full h-full object-cover transition-transform duration-[12s] group-hover:scale-110">
                <div class="absolute inset-0 bg-foreground/60 backdrop-blur-[2px]"></div>
            </div>
            <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 text-center">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Our Services</p>
                <h2 class="text-5xl md:text-7xl font-serif font-light text-background mb-12 leading-tight">
                    Buy. Sell. <span class="italic opacity-80">Rent.</span>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16 text-background/80 font-light">
                    <?php foreach ([['Property Buying','We help you find the right property, including options not easily available in the market.'],['Property Selling','We promote your property effectively to get you the best price.'],['Property Leasing','We connect tenants with suitable rental properties through a smooth and reliable process.']] as [$h,$d]): ?>
                    <div>
                        <h4 class="text-xl font-serif text-background mb-3"><?= $h ?></h4>
                        <p class="text-sm leading-relaxed mx-auto max-w-[280px]"><?= $d ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= BASE ?>contact" class="inline-flex items-center justify-center gap-3 bg-accent text-background px-10 py-5 rounded-full hover:bg-accent-dark transition-all transform hover:-translate-y-1 shadow-2xl font-medium">
                    Inquire About Services
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </section>
    </main>

<?php require_once '../includes/footer.php'; ?>
