<?php
// FILE: public/privacy-policy.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);

$solidNav  = true;
$pageTitle = 'Privacy Policy';
$pageDesc  = 'Privacy Policy for ' . e($settings['site_name'] ?? 'Advet Buildwell');
require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-32">
        <article class="max-w-4xl mx-auto px-6 sm:px-12 lg:px-16 reveal">
            <!-- Header -->
            <header class="text-center mb-16">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Legal Terms</p>
                <h1 class="text-5xl md:text-6xl font-serif font-light leading-tight mb-8 border-b border-sand/30 pb-8">
                    Privacy <span class="italic text-muted">Policy</span>
                </h1>
            </header>

            <!-- Content -->
            <div class="prose prose-lg prose-stone max-w-3xl mx-auto font-light text-muted leading-relaxed prose-headings:font-serif prose-headings:font-light prose-headings:text-foreground prose-a:text-accent prose-a:no-underline hover:prose-a:underline">
                <?php if (empty($settings['privacy_policy'])): ?>
                    <p>No privacy policy has been configured yet.</p>
                <?php else: ?>
                    <?= $settings['privacy_policy'] // Content is raw HTML from admin settings ?>
                <?php endif; ?>
            </div>
            
            <!-- Back Link -->
            <div class="max-w-3xl mx-auto mt-20 pt-10 border-t border-sand/30 flex justify-center items-center">
                <a href="<?= BASE ?>index.php" class="inline-flex items-center gap-2 text-sm font-medium uppercase tracking-widest text-[#899178] hover:text-[#6E755F] transition-colors">
                    ← Return to Home
                </a>
            </div>
        </article>
    </main>

<?php require_once '../includes/footer.php'; ?>
