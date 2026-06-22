<?php
// FILE: public/team.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$solidNav  = true;
$pageTitle = 'The Collective';
$pageDesc = 'Meet the visionaries, architects, and curators behind Advet Buildwell - redefining minimal living through intentional design.';

$team = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, name ASC")->fetchAll();

require_once '../includes/header.php';
?>

    <section class="pt-48 pb-32 max-w-7xl mx-auto px-6 sm:px-12 lg:px-16 w-full">
        <div class="mb-24 reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.5em] text-accent mb-6">About Our Team</p>
            <h1 class="text-5xl sm:text-7xl md:text-8xl font-serif font-light leading-[1.05] text-foreground mb-12 tracking-tight">
                Experienced <br><span class="italic text-muted">Professionals.</span>
            </h1>
            <p class="text-lg md:text-xl text-muted max-w-2xl font-light leading-relaxed">
                We are a team focused on helping you find the right property for your needs. We don’t just manage properties—we help you choose spaces that suit your lifestyle.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-12 gap-y-24">
            <?php if (empty($team)): ?>
                <div class="col-span-full py-20 text-center">
                    <p class="text-2xl font-serif italic text-muted">Our collective is growing. Check back soon.</p>
                </div>
            <?php else: ?>
                <?php foreach ($team as $i => $m): 
                    $isOffset = ($i % 2 !== 0);
                ?>
                <div class="group reveal <?= $isOffset ? 'md:mt-32' : '' ?>">
                    <div class="relative aspect-[4/5] image-soft-clip overflow-hidden mb-8 shadow-2xl">
                        <img src="<?= $m['image_path'] ? imgUrl($m['image_path']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=600' ?>" 
                             alt="<?= e($m['name']) ?>" 
                             class="w-full h-full object-cover transition-transform duration-[8s] group-hover:scale-105 filter grayscale-[20%] group-hover:grayscale-0">
                        <div class="absolute inset-0 bg-gradient-to-t from-foreground/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <div>
                                <h3 class="text-3xl font-serif group-hover:text-accent transition-colors"><?= e($m['name']) ?></h3>
                                <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-accent mt-2"><?= e($m['designation'] ?: 'Principal') ?></p>
                            </div>
                        </div>
                        
                        <div class="text-sm text-muted font-light leading-relaxed line-clamp-3 prose prose-sm prose-stone">
                            <?= $m['bio'] ?: 'Dedicated to finding the balance between form and function, ensuring every space resonates with its owner.' ?>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-sand/30">
                            <?php if ($m['whatsapp_number']): ?>
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $m['whatsapp_number']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="WhatsApp">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 13.4876 3.36032 14.8913 4 16.1272L3 21L7.8728 20C9.10866 20.6397 10.5124 21 12 21Z"/><path d="M8.5 11.5C8.5 11.5 9 10 10.5 10C12 10 12 11.5 12 11.5L12.5 13.5C12.5 13.5 12.5 15 14 15C15.5 15 16 13.5 16 13.5"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['email']): ?>
                            <a href="mailto:<?= e($m['email']) ?>" class="text-muted hover:text-accent transition-colors" title="Email">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12C22 15.7712 22 17.6569 20.8284 18.8284C19.6569 20 17.7712 20 14 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12Z"/><path d="M6 8L10.3757 11.2818C11.3259 11.9944 12.6741 11.9944 13.6243 11.2818L18 8"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['facebook_url']): ?>
                            <a href="https://facebook.com/<?= e($m['facebook_url']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="Facebook">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 2H14C12.6739 2 11.4021 2.52678 10.4645 3.46447C9.52678 4.40215 9 5.67392 9 7V10H6V14H9V22H13V14H16L17 10H13V7C13 6.73478 13.1054 6.48043 13.2929 6.29289C13.4804 6.10536 13.7348 6 14 6H17V2Z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['x_url']): ?>
                            <a href="https://x.com/<?= e($m['x_url']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="X (Twitter)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M16.8871 3.25049H19.646L13.6193 10.138L20.7093 19.5005H15.1565L10.8066 13.813L5.83002 19.5005H3.06992L9.51656 12.1321L2.7915 3.25049H8.48512L12.4201 8.45549L16.8871 3.25049ZM15.9184 17.8505H17.447L7.68536 4.81924H6.04536L15.9184 17.8505Z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['instagram_url']): ?>
                            <a href="https://instagram.com/<?= e($m['instagram_url']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="Instagram">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['threads_url']): ?>
                            <a href="https://threads.net/@<?= e($m['threads_url']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="Threads">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 13.5C11.1716 13.5 10.5 12.8284 10.5 12C10.5 11.1716 11.1716 10.5 12 10.5C12.8284 10.5 13.5 11.1716 13.5 12C13.5 12.8284 12.8284 13.5 12 13.5Z"/><path d="M17.5 12C17.5 17.5 15.5 20.5 12 20.5C8.5 20.5 6.5 17.5 6.5 12C6.5 6.5 8.5 3.5 12 3.5C15.5 3.5 17.5 6.5 17.5 12Z"/></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($m['socialvynk_url']): ?>
                            <a href="https://socialvynk.space/<?= e($m['socialvynk_url']) ?>" target="_blank" class="text-muted hover:text-accent transition-colors" title="Socialvynk">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3.6 9H20.4"/><path d="M3.6 15H20.4"/><path d="M11.5 3C11.5 3 8 7.5 8 12C8 16.5 11.5 21 11.5 21"/><path d="M12.5 3C12.5 3 16 7.5 16 12C16 16.5 12.5 21 12.5 21"/></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Narrative Section -->
        <div class="mt-48 pt-24 border-t border-sand/30 reveal">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-24 items-center">
                <div>
                    <h2 class="text-4xl font-serif font-light mb-8">Our <span class="italic text-accent">Approach.</span></h2>
                    <div class="space-y-6 text-muted font-light leading-relaxed">
                        <p>Our team includes real estate experts, architects, and designers who work together to provide better property solutions. We aim to make every deal smooth and beneficial for our clients.</p>
                        <p>We go beyond basic property listings by understanding what you need in a home or investment, and guide you accordingly.</p>
                    </div>
                </div>
                <div class="aspect-video image-soft-clip overflow-hidden shadow-xl">
                    <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=1200" alt="Studio Life" class="w-full h-full object-cover grayscale opacity-50">
                </div>
            </div>
        </div>
    </section>

<?php require_once '../includes/footer.php'; ?>
