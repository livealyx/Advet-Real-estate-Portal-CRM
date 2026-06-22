<?php
// FILE: faq.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$settings = loadSettings($pdo);
$siteName = $settings['site_name'] ?? 'Advet Buildwell';

// Fetch all active FAQs
$faqs = $pdo->query("SELECT * FROM faqs WHERE status='active' ORDER BY display_order ASC, created_at DESC")->fetchAll();

require_once '../includes/header.php';
?>

<section class="relative pt-48 pb-32 overflow-hidden bg-background">
    <div class="max-w-4xl mx-auto px-6 sm:px-12 lg:px-16 relative z-10">
        <header class="text-center mb-24 reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-6">Support Center</p>
            <h1 class="text-5xl md:text-7xl font-serif font-light leading-tight mb-8">
                Frequently Asked <br><span class="italic text-muted">Questions</span>
            </h1>
            <div class="w-12 h-px bg-sand/60 mx-auto mb-8"></div>
            <p class="text-muted text-lg font-light leading-relaxed max-w-2xl mx-auto">
                Find answers to common questions about our property curation process, buying strategies, and architectural philosophy.
            </p>
        </header>

        <?php if (empty($faqs)): ?>
            <div class="text-center py-20 reveal">
                <p class="text-muted font-light italic">No questions found at the moment. Please check back later or contact us directly.</p>
                <a href="<?= BASE ?>contact.php" class="inline-flex items-center gap-2 text-sm font-medium border-b border-muted pb-1 text-muted hover:text-foreground hover:border-foreground transition-colors mt-8">
                    Contact our studio
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10M17 7H7M17 7v10"/></svg>
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6 reveal">
                <?php foreach ($faqs as $i => $faq): ?>
                <div class="faq-item border border-sand/30 rounded-[2rem] bg-surface/10 overflow-hidden transition-all duration-500 hover:border-accent/30 shadow-sm" data-id="<?= $faq['id'] ?>">
                    <button class="faq-trigger w-full px-8 py-8 flex justify-between items-center text-left group gap-6 focus:outline-none">
                        <span class="text-xl font-serif group-hover:text-accent transition-colors"><?= e($faq['question']) ?></span>
                        <span class="faq-icon shrink-0 w-8 h-8 rounded-full bg-surface flex items-center justify-center transition-all duration-500 group-hover:bg-sand/40">
                            <svg class="w-4 h-4 text-muted transition-transform duration-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        </span>
                    </button>
                    <div class="faq-content max-h-0 overflow-hidden transition-all duration-500 ease-in-out">
                        <div class="px-8 pb-8 pt-2">
                            <div class="w-full h-px bg-sand/20 mb-6"></div>
                            <div class="text-muted font-light leading-relaxed prose prose-sm max-w-none">
                                <?= nl2br(e($faq['answer'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <script>
            document.querySelectorAll('.faq-trigger').forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const item = trigger.closest('.faq-item');
                    const content = item.querySelector('.faq-content');
                    const icon = item.querySelector('.faq-icon svg');
                    const isOpen = !content.classList.contains('max-h-0');

                    // Close all other items
                    document.querySelectorAll('.faq-content').forEach(c => {
                        c.style.maxHeight = null;
                        c.classList.add('max-h-0');
                    });
                    document.querySelectorAll('.faq-icon svg').forEach(s => s.classList.remove('rotate-45'));
                    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('bg-surface/30', 'border-accent/30'));

                    if (!isOpen) {
                        content.style.maxHeight = content.scrollHeight + "px";
                        content.classList.remove('max-h-0');
                        icon.classList.add('rotate-45');
                        item.classList.add('bg-surface/30', 'border-accent/30');
                    }
                });
            });
            </script>
        <?php endif; ?>

        <div class="mt-32 text-center reveal">
            <h3 class="text-2xl font-serif mb-8 italic">Still have questions?</h3>
            <a href="<?= BASE ?>contact.php" class="bg-foreground text-background px-12 py-5 rounded-full hover:bg-accent hover:text-white transition-all transform hover:-translate-y-1 shadow-xl font-bold text-[10px] uppercase tracking-widest">
                Get in touch
            </a>
        </div>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute top-1/4 -left-24 w-96 h-96 bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 -right-24 w-96 h-96 bg-sand/10 rounded-full blur-3xl pointer-events-none"></div>
</section>

<?php require_once '../includes/footer.php'; ?>
