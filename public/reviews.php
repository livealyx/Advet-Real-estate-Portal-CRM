<?php
// FILE: public/reviews.php
session_start();
$solidNav  = true;
$pageTitle = 'Share Your Experience';
$pageDesc  = 'Feedback form for Advet Buildwell clients.';
require_once '../includes/header.php';

$name = $_SESSION['user']['name'] ?? '';
?>

    <main class="flex-grow pt-40 pb-32">
        <div class="max-w-4xl mx-auto px-6 sm:px-12 lg:px-16 reveal">
            <!-- Header -->
            <header class="text-center mb-16">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Give Reviews</p>
                <h1 class="text-5xl md:text-6xl font-serif font-light leading-tight mb-8">
                    Share your <span class="italic text-muted">review.</span>
                </h1>
                <p class="text-lg text-muted font-light leading-relaxed max-w-2xl mx-auto">
                    We value the voices of those who dwell in our spaces. Share your experience with Advet Buildwell, and help us continue to refine our craft.
                </p>
            </header>

            <form action="<?= BASE ?>actions/submit-feedback.php" method="POST" class="bg-background border border-sand/40 rounded-[2.5rem] p-8 md:p-12 shadow-sm space-y-8 relative overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Your Name *</label>
                        <input type="text" name="name" required value="<?= e($name) ?>"
                               class="w-full px-6 py-4 bg-surface border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all"
                               placeholder="Jane D.">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Affiliation / Property (Optional)</label>
                        <input type="text" name="affiliation" 
                               class="w-full px-6 py-4 bg-surface border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all"
                               placeholder="e.g. Architect, or The Haven">
                    </div>
                </div>
                
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-6 ml-1">Experience Type *</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Buying -->
                        <label class="cursor-pointer group">
                            <input type="radio" name="experience_type" value="buying" class="hidden peer" required checked>
                            <div class="h-full p-6 bg-surface/50 border border-sand/30 rounded-[2rem] flex flex-col items-center text-center gap-4 transition-all duration-300 peer-checked:border-accent peer-checked:bg-accent/5 peer-checked:shadow-sm hover:border-accent/40">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-sand/20 flex items-center justify-center text-accent transition-transform duration-500 group-hover:scale-110 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted group-hover:text-foreground transition-colors">Buying</span>
                            </div>
                        </label>

                        <!-- Selling -->
                        <label class="cursor-pointer group">
                            <input type="radio" name="experience_type" value="selling" class="hidden peer">
                            <div class="h-full p-6 bg-surface/50 border border-sand/30 rounded-[2rem] flex flex-col items-center text-center gap-4 transition-all duration-300 peer-checked:border-accent peer-checked:bg-accent/5 peer-checked:shadow-sm hover:border-accent/40">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-sand/20 flex items-center justify-center text-accent transition-transform duration-500 group-hover:scale-110 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted group-hover:text-foreground transition-colors">Selling</span>
                            </div>
                        </label>

                        <!-- Leasing -->
                        <label class="cursor-pointer group">
                            <input type="radio" name="experience_type" value="lease" class="hidden peer">
                            <div class="h-full p-6 bg-surface/50 border border-sand/30 rounded-[2rem] flex flex-col items-center text-center gap-4 transition-all duration-300 peer-checked:border-accent peer-checked:bg-accent/5 peer-checked:shadow-sm hover:border-accent/40">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-sand/20 flex items-center justify-center text-accent transition-transform duration-500 group-hover:scale-110 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted group-hover:text-foreground transition-colors">Leasing</span>
                            </div>
                        </label>

                        <!-- Other -->
                        <label class="cursor-pointer group">
                            <input type="radio" name="experience_type" value="other" class="hidden peer">
                            <div class="h-full p-6 bg-surface/50 border border-sand/30 rounded-[2rem] flex flex-col items-center text-center gap-4 transition-all duration-300 peer-checked:border-accent peer-checked:bg-accent/5 peer-checked:shadow-sm hover:border-accent/40">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-sand/20 flex items-center justify-center text-accent transition-transform duration-500 group-hover:scale-110 shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z"/></svg>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted group-hover:text-foreground transition-colors">General</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-1 ml-1">Rating *</label>
                    <div class="flex flex-row-reverse justify-end gap-2 star-rating">
                        <?php for($i=5; $i>=1; $i--): ?>
                        <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" class="hidden peer" <?= $i === 5 ? 'checked' : '' ?>>
                        <label for="star<?= $i ?>" class="cursor-pointer text-sand peer-checked:text-accent peer-hover:text-accent hover:text-accent transition-all">
                            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <style>
                        .star-rating label:hover ~ label { color: #899178 !important; }
                    </style>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Your Story / Review *</label>
                    <textarea name="content" rows="6" required
                              class="w-full px-6 py-4 bg-surface border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all leading-relaxed"
                              placeholder="Tell us about the emotional and architectural experience of your journey with Advet..."></textarea>
                </div>

                <div class="pt-4 border-t border-sand/30 flex justify-end">
                    <button type="submit" class="px-10 py-4 bg-foreground text-background rounded-full text-xs font-bold uppercase tracking-widest hover:bg-accent transition-all shadow-xl hover:-translate-y-1">
                        Submit Review
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-12">
                <a href="<?= BASE ?>testimonials.php" class="text-xs uppercase tracking-widest font-bold text-accent hover:text-accent-dark transition-colors inline-block border-b border-accent/30 pb-1">
                    Read Testimonials →
                </a>
            </div>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
