<?php
// FILE: public/about.php
session_start();
require_once '../config/db.php';
$solidNav  = true;
$pageTitle = 'About Us';
$pageDesc  = 'Advet Buildwell — a collective of advisors, architects, and advocates dedicated to the human experience of space.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-24">
        <!-- Hero Section -->
        <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32 reveal">
            <div class="max-w-4xl">
                <p class="text-xs font-medium uppercase tracking-widest text-accent mb-6">About Us</p>
                <h1 class="text-5xl sm:text-7xl font-serif font-light leading-tight mb-12">
                    Finding the Right <br><span class="italic text-muted">Property for You.</span>
                </h1>
                <p class="text-xl text-muted font-light leading-relaxed max-w-2xl">
                    Advet Buildwell is a real estate company focused on helping people find the right property. Our team includes experienced advisors who guide you through buying, selling, and renting. We believe the right home or property can improve your lifestyle and comfort.
                </p>
            </div>
        </section>

        <!-- Our Story -->
        <section class="bg-surface/30 py-32 mb-32">
            <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 grid grid-cols-1 md:grid-cols-2 gap-24 items-center">
                <div class="relative aspect-[4/5] image-soft-clip overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1554469384-e58fac16e23a?auto=format&fit=crop&q=80&w=800"
                         alt="Advet Minimal Studio"
                         class="w-full h-full object-cover transition-transform duration-[10s] hover:scale-105 grayscale opacity-70">
                </div>
                <div>
                    <h2 class="text-4xl font-serif font-light mb-8">Founded in <span class="italic text-muted">2014.</span></h2>
                    <div class="space-y-6 text-muted font-light leading-relaxed">
                        <p>Advet Buildwell started with a simple goal: to make real estate clear and straightforward. Instead of focusing only on numbers and listings, we focus on quality properties and real customer needs.</p>
                        <p>Today, we work with clients who value good design, proper planning, and long-term value. Whether it’s buying a home, selling a property, or managing rentals, we provide a personal and reliable approach.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Service Hero Banner -->
        <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32">
            <div class="relative h-[500px] image-soft-clip overflow-hidden group">
                <img src="https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&q=80&w=2000"
                     alt="Service Hero"
                     class="w-full h-full object-cover transition-transform duration-[10s] group-hover:scale-105">
                <div class="absolute inset-0 bg-foreground/50 backdrop-blur-[1px]"></div>
                <div class="absolute inset-0 flex flex-col justify-center px-12 md:px-24">
                    <p class="text-xs font-medium uppercase tracking-[0.4em] text-accent mb-6">Our Expertise</p>
                    <h2 class="text-white text-4xl md:text-6xl font-serif font-light mb-12 leading-tight">
                        Real Estate <br><span class="italic opacity-80">Services.</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-white/80 font-light">
                        <?php foreach ([['Buying','Helping you find the right property based on your needs and budget.'],['Selling','Marketing your property and helping you get the best price.'],['Leasing','Managing rentals and connecting tenants with suitable properties.']] as [$title,$sub]): ?>
                        <div class="border-l border-accent/40 pl-6">
                            <h4 class="text-lg font-serif text-white mb-2"><?= $title ?></h4>
                            <p class="text-[10px] leading-relaxed uppercase tracking-wider"><?= $sub ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-32">
            <div class="text-center mb-20">
                <p class="text-xs font-medium uppercase tracking-widest text-accent mb-4">The Personnel</p>
                <h2 class="text-4xl sm:text-5xl font-serif font-light">Discerning <span class="italic text-muted">Advisors</span></h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <?php
                $team = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC LIMIT 3")->fetchAll();
                $offsets = ['', 'md:mt-12', 'md:-mt-6'];
                foreach ($team as $i => $m):
                    $offset = $offsets[$i] ?? '';
                ?>
                <div class="group <?= $offset ?>">
                    <div class="aspect-[3/4] image-soft-clip overflow-hidden mb-6 bg-surface shadow-lg">
                        <img src="<?= $m['image_path'] ? imgUrl($m['image_path']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=400' ?>" 
                             alt="<?= e($m['name']) ?>"
                             class="w-full h-full object-cover grayscale transition-all group-hover:grayscale-0 group-hover:scale-105 duration-700">
                    </div>
                    <h3 class="text-2xl font-serif mb-1 uppercase tracking-tight"><?= e($m['name']) ?></h3>
                    <p class="text-xs uppercase tracking-widest text-accent font-medium mb-4"><?= e($m['designation']) ?></p>
                    <div class="text-sm text-muted font-light leading-relaxed line-clamp-3 prose prose-sm prose-stone max-w-none">
                        <?= $m['bio'] ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Values Banner -->
        <section class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 mb-24 reveal">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-4 relative text-center md:text-left">
                <div class="hidden md:block absolute top-[28px] left-0 w-full h-[1px] bg-sand"></div>
                <?php foreach ([['I','Honesty','Absolute transparency in every valuation and contract.'],['II','Restraint','We represent only what we truly believe in, keeping our portfolio small.'],['III','Connection','We prioritize the relationship over the closing date.'],['IV','Quality','Uncompromising focus on architectural and structural excellence.']] as [$n,$v,$d]): ?>
                <div class="relative z-10 flex flex-col items-center md:items-start group">
                    <div class="w-14 h-14 rounded-full bg-surface border-4 border-background flex items-center justify-center text-accent font-serif text-xl mb-6"><?= $n ?></div>
                    <h4 class="text-lg font-medium mb-2"><?= $v ?></h4>
                    <p class="text-sm font-light text-muted leading-relaxed max-w-[200px]"><?= $d ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

<?php require_once '../includes/footer.php'; ?>
