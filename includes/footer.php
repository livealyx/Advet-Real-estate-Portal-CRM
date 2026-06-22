<?php
// FILE: includes/footer.php
if (session_status() === PHP_SESSION_NONE) session_start();

// siteBase() is already defined in header.php (loaded first).
// Fallback in case footer is ever loaded standalone:
if (!function_exists('siteBase')) {
    function siteBase(): string {
        $docRoot    = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
        $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        $dir = dirname($scriptFile);
        while ($dir !== $docRoot && strlen($dir) > strlen($docRoot)) {
            if (file_exists($dir . '/index.php') && file_exists($dir . '/config/db.php')) break;
            $dir = dirname($dir);
        }
        $rel = ltrim(str_replace($docRoot, '', $dir), '/');
        return '/' . ($rel ? $rel . '/' : '');
    }
}
if (!isset($BASE)) $BASE = siteBase();

function footerPath(string $page): string {
    return $GLOBALS['BASE'] . ltrim($page, '/');
}

// Ensure settings are loaded
if (!isset($siteSettings) || empty($siteSettings)) {
    try {
        $pdo = getPDO();
        $siteSettings = loadSettings($pdo);
    } catch (\Throwable $e) {
        $siteSettings = [];
    }
}

if (!isset($siteName)) $siteName = $siteSettings['site_name'] ?? 'Advet Buildwell';
?>
    <!-- Footer -->
    <footer class="bg-foreground text-background pt-32 pb-12 w-full mt-auto">
        <div class="max-w-7xl mx-auto px-6 sm:px-12 lg:px-16">

            <!-- Main Footer CTA -->
            <div class="mb-32 text-center md:text-left flex flex-col md:flex-row justify-between items-center md:items-end gap-12">
                <div class="max-w-xl">
                    <h2 class="text-4xl sm:text-6xl font-serif font-light mb-8">Ready to come <span class="italic text-sand">home</span>?</h2>
                    <p class="text-surface/70 text-lg font-light">
                        Let's start a conversation about your next chapter. We build only a select number of homes each year to ensure uncompromising quality.
                    </p>
                </div>
                <div class="shrink-0">
                    <a href="<?= footerPath('contact.php') ?>" class="inline-flex items-center gap-3 bg-surface text-foreground px-10 py-5 rounded-full font-medium hover:bg-white transition-all transform hover:-translate-y-1 shadow-xl">
                        Connect with <?= e($siteName) ?>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Footer Links Grid -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12 lg:gap-8 mb-24 border-t border-surface/10 pt-16">
                <!-- Pages 2-Column Area -->
                <div class="md:col-span-5 grid grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-xs font-medium uppercase tracking-widest text-surface/30 mb-6">Explore</h4>
                        <ul class="space-y-4 text-sm">
                            <li><a href="<?= footerPath('index') ?>" class="text-surface/60 hover:text-surface transition-colors">Home</a></li>
                            <li><a href="<?= footerPath('about') ?>" class="text-surface/60 hover:text-surface transition-colors">About</a></li>
                            <li><a href="<?= footerPath('philosophy') ?>" class="text-surface/60 hover:text-surface transition-colors">Philosophy</a></li>
                            <li><a href="<?= footerPath('properties') ?>" class="text-surface/60 hover:text-surface transition-colors">Properties</a></li>
                            <li><a href="<?= footerPath('commercial') ?>" class="text-surface/60 hover:text-surface transition-colors">Commercial</a></li>
                            <li><a href="<?= footerPath('projects') ?>" class="text-surface/60 hover:text-surface transition-colors">Projects</a></li>
                            <li><a href="<?= footerPath('land-measurement-units') ?>" class="text-surface/60 hover:text-surface transition-colors">Measurement Units</a></li>
                            <li><a href="<?= footerPath('rent-receipt') ?>" class="text-surface/60 hover:text-surface transition-colors">Rent Receipt</a></li>
                            <li><a href="<?= footerPath('gallery') ?>" class="text-surface/60 hover:text-surface transition-colors">Gallery</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-xs font-medium uppercase tracking-widest text-surface/30 mb-6">Engage</h4>
                        <ul class="space-y-4 text-sm">
                            <li><a href="<?= footerPath('stories') ?>" class="text-surface/60 hover:text-surface transition-colors">Stories</a></li>
                            <li><a href="<?= footerPath('testimonials') ?>" class="text-surface/60 hover:text-surface transition-colors">Testimonials</a></li>
                            <li><a href="<?= footerPath('reviews') ?>" class="text-surface/60 hover:text-surface transition-colors">Give Reviews</a></li>
                            <li><a href="<?= footerPath('faq') ?>" class="text-surface/60 hover:text-surface transition-colors">FAQ</a></li>
                            <li><a href="<?= footerPath('auth/login') ?>" class="text-surface/60 hover:text-surface transition-colors">Member Login</a></li>
                            <li><a href="<?= footerPath('contact') ?>" class="text-surface/60 hover:text-surface transition-colors">Contact</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Social Links -->
                <div class="md:col-span-2">
                    <h4 class="text-xs font-medium uppercase tracking-widest text-surface/30 mb-6">Social</h4>
                    <ul class="space-y-4 text-sm">
                        <?php 
                        $socials = [
                            'instagram' => ['label' => 'Instagram', 'url' => 'https://instagram.com/'],
                            'facebook'  => ['label' => 'Facebook',  'url' => 'https://facebook.com/'],
                            'linkedin'  => ['label' => 'LinkedIn',  'url' => 'https://www.linkedin.com/company/'],
                            'youtube'   => ['label' => 'YouTube',   'url' => 'https://youtube.com/@'],
                            'socialvynk'=> ['label' => 'Socialvynk', 'url' => 'https://socialvynk.space/'],
                        ];
                        foreach ($socials as $key => $data):
                            $val = $siteSettings['social_'.$key] ?? '';
                            if ($val):
                        ?>
                        <li>
                            <a href="<?= preg_match('/^http/', $val) ? e($val) : $data['url'].e($val) ?>" 
                               target="_blank" class="text-surface/60 hover:text-surface transition-colors">
                                <?= $data['label'] ?>
                            </a>
                        </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>

                <!-- Studio & Newsletter Stacked -->
                <div class="md:col-span-5 space-y-12">
                    <div>
                        <h4 class="text-xs font-medium uppercase tracking-widest text-surface/30 mb-6">Studio</h4>
                        <p class="text-surface/60 font-light text-sm leading-relaxed">
                            <?= nl2br(e($siteSettings['studio_address'] ?? "1042 Minimalist Way\nLos Angeles, CA 90026")) ?><br><br>
                            <?php $email = $siteSettings['contact_email'] ?? 'hello@advetbuildwell.com'; ?>
                            <a href="mailto:<?= e($email) ?>" class="border-b border-surface/20 hover:border-surface transition-colors"><?= e($email) ?></a>
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="text-xs font-medium uppercase tracking-widest text-surface/30 mb-6">Field Notes</h4>
                        <p class="text-surface/60 font-light text-sm leading-relaxed mb-6">
                            Join our mailing list for off-market listings and architectural insights delivered to your inbox.
                        </p>
                        <form method="POST" action="<?= footerPath('actions/subscribe-newsletter.php') ?>" class="relative group">
                            <input type="hidden" name="redirect_back" value="1">
                            <input type="email" name="email" placeholder="Email Address"
                                   class="w-full bg-surface/5 border border-surface/10 rounded-full pl-6 pr-32 py-4 text-sm text-surface focus:outline-none focus:border-accent transition-all placeholder:text-surface/20">
                            <button type="submit" class="absolute right-2 top-2 bottom-2 bg-surface text-foreground px-6 py-2 rounded-full text-[10px] font-bold uppercase tracking-widest hover:bg-white transition-all">
                                Submit
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Copyright Line -->
            <div class="flex flex-col md:flex-row justify-between items-center text-xs text-surface/30 font-light gap-4 border-t border-surface/10 pt-8">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-accent opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l8 7L12 21l-8-11 8-7z"/>
                    </svg>
                    <?= !empty($siteSettings['site_copyright']) ? e($siteSettings['site_copyright']) : e($siteName) . ' &copy; ' . date('Y') . '. Handcrafted for human emotion.' ?>
                </div>
                <div class="flex gap-8">
                    <a href="<?= footerPath('privacy-policy.php') ?>" class="hover:text-surface transition-colors">Privacy Policy</a>
                    <a href="<?= footerPath('terms-of-use.php') ?>" class="hover:text-surface transition-colors">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>
    <?php include_once __DIR__ . '/chat-widget.php'; ?>
</body>
</html>
