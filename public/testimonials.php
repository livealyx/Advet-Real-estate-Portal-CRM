<?php
// FILE: public/testimonials.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();

$stmt = $pdo->query("
    SELECT name, affiliation, experience_type, content, rating, created_at
      FROM testimonials
     WHERE status = 'approved'
  ORDER BY created_at DESC
");
$testimonials = $stmt->fetchAll();

$solidNav  = true;
$pageTitle = 'Testimonials';
$pageDesc  = 'Read feedback and testimonials from those who have experienced Advet Buildwell first-hand.';
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-32">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 reveal">
            <!-- Header -->
            <header class="text-center mb-24">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Testimonials</p>
                <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8">
                    Quiet confidence <br><span class="italic text-muted">loudly heard.</span>
                </h1>
                <p class="text-lg text-muted font-light leading-relaxed max-w-2xl mx-auto">
                    The true measure of a masterpiece is not in its form, but in the emotion it invokes. Hear directly from those who have dwelled within Advet's curated spaces.
                </p>
                <a href="<?= BASE ?>reviews.php" class="inline-flex mt-10 items-center justify-center gap-3 px-8 py-3 bg-surface text-foreground rounded-full text-xs font-bold uppercase tracking-widest border border-sand hover:bg-sand transition-all">
                    Share Your Experience
                </a>
            </header>

            <?php if (empty($testimonials)): ?>
                <div class="text-center py-16">
                    <p class="text-xl font-serif text-muted italic">There are no testimonials published yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
                    <?php foreach ($testimonials as $t): ?>
                        <div class="bg-surface/30 p-10 rounded-[2.5rem] border border-sand/30 hover:border-accent/40 transition-colors">
                            <div class="flex gap-1 mb-6">
                                <?php for($i=1; $i<=5; $i++): ?>
                                <svg class="w-4 h-4 <?= $i <= $t['rating'] ? 'text-accent' : 'text-sand' ?> fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                            <p class="text-lg font-serif italic text-foreground leading-relaxed mb-8">
                                "<?= e($t['content']) ?>"
                            </p>
                            <div class="border-t border-sand/50 pt-6">
                                <p class="text-sm font-bold uppercase tracking-widest text-muted"><?= e($t['name']) ?></p>
                                <?php if ($t['affiliation']): ?>
                                    <p class="text-xs uppercase tracking-widest text-[#899178] mt-1"><?= e($t['affiliation']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
