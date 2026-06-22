<?php
// FILE: public/projects.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

// Filters
$type = $_GET['type'] ?? '';
$status = $_GET['possession'] ?? '';

$sql = "SELECT * FROM projects WHERE status='active'";
$args = [];

if ($type) {
    $sql .= " AND project_type = ?";
    $args[] = $type;
}
if ($status) {
    $sql .= " AND possession_status = ?";
    $args[] = $status;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$projects = $stmt->fetchAll();

$pageTitle = 'Exclusive Projects';
$pageDesc = 'Explore our portfolio of elite residential and commercial developments.';
require_once '../includes/header.php';
?>

<section class="relative pt-48 pb-32 overflow-hidden bg-background">
     <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 relative z-10">
        <header class="text-center mb-20 reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-6">Global Portfolio</p>
            <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8">
                Masterpiece <span class="italic text-muted">Projects.</span>
            </h1>
            <p class="text-muted text-lg font-light leading-relaxed max-w-2xl mx-auto">
                Discover architectural excellence and lifestyle-defining spaces curated for the most discerning residents.
            </p>
        </header>

        <!-- Filters -->
        <div class="flex flex-wrap justify-center gap-6 mb-16 reveal" style="animation-delay: 0.2s">
            <a href="projects.php" class="px-8 py-3 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all <?= !$type && !$status ? 'bg-accent text-white shadow-lg shadow-accent/20' : 'bg-surface text-muted hover:bg-sand/40' ?>">All Vision</a>
            <a href="?type=Apartment" class="px-8 py-3 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all <?= $type === 'Apartment' ? 'bg-accent text-white' : 'bg-surface text-muted hover:bg-sand/40' ?>">Apartments</a>
            <a href="?type=Villa" class="px-8 py-3 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all <?= $type === 'Villa' ? 'bg-accent text-white' : 'bg-surface text-muted hover:bg-sand/40' ?>">Villas</a>
            <a href="?possession=Ready" class="px-8 py-3 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all <?= $status === 'Ready' ? 'bg-accent text-white' : 'bg-surface text-muted hover:bg-sand/40' ?>">Ready to Move</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="py-32 text-center text-muted italic font-light reveal">
                No projects found matching your criteria. Explore our other offerings.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10 reveal">
                <?php foreach ($projects as $p): ?>
                    <a href="<?= navPath('projects/' . $p['slug']) ?>" class="group">
                        <div class="relative overflow-hidden rounded-[2rem] bg-surface aspect-[4/5] shadow-sm group-hover:shadow-2xl transition-all duration-700">
                            <img src="<?= imgUrl($p['cover_image']) ?>" class="w-full h-full object-cover transition-transform duration-[2s] group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-80 group-hover:opacity-100 transition-opacity"></div>
                            
                            <!-- Badges -->
                            <div class="absolute top-6 left-6 flex gap-2">
                                <span class="bg-white/90 backdrop-blur-md px-4 py-1.5 rounded-full text-[8px] font-bold uppercase tracking-widest text-foreground shadow-sm"><?= e($p['project_type']) ?></span>
                                <span class="bg-accent/90 backdrop-blur-md px-4 py-1.5 rounded-full text-[8px] font-bold uppercase tracking-widest text-white shadow-sm"><?= e($p['possession_status']) ?></span>
                            </div>

                            <!-- Content -->
                            <div class="absolute inset-x-0 bottom-0 p-10 translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                                <h3 class="text-3xl font-serif text-white mb-3"><?= e($p['title']) ?></h3>
                                <div class="flex items-center gap-2 text-white/70 text-[10px] uppercase tracking-widest mb-4">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <?= e($p['location']) ?>
                                </div>
                                <div class="flex items-end justify-between border-t border-white/20 pt-6">
                                    <div>
                                        <p class="text-[8px] font-bold uppercase tracking-widest text-accent mb-1">Starting From</p>
                                        <p class="text-xl font-medium text-white"><?= e($p['price_min']) ?></p>
                                    </div>
                                    <span class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white backdrop-blur-sm group-hover:bg-accent group-hover:scale-110 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Background Elements -->
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-accent/5 rounded-full blur-[120px] -z-10 translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-sand/10 rounded-full blur-[120px] -z-10 -translate-x-1/2 translate-y-1/2"></div>
</section>

<?php require_once '../includes/footer.php'; ?>
