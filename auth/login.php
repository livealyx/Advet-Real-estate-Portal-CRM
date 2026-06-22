<?php
// FILE: auth/login.php
session_start();
require_once '../config/db.php';
if (!empty($_SESSION['user'])) {
    $role = strtolower($_SESSION['user']['role'] ?? '');
    $target = in_array($role, ['admin', 'agent']) ? BASE . 'admin/dashboard.php' : BASE . 'index.php';
    header('Location: ' . $target);
    exit;
}
$solidNav = true;
$pageTitle = 'Member Login';
$pageDesc = 'Sign in to your Advet Buildwell member portal.';
require_once '../includes/header.php';
?>
<main class="flex-grow flex items-center justify-center px-6 pt-48 pb-24 bg-surface/30">
    <div class="w-full max-w-md auth-card">
        <div class="bg-background p-10 sm:p-12 rounded-[2rem] shadow-sm border border-sand/50">
            <div class="text-center mb-10">
                <h1 class="text-4xl font-serif font-light mb-2">Login</h1>
                <p class="text-sm text-muted">Welcome back to Advet Buildwell</p>
            </div>

            <form method="POST" action="<?= BASE ?>actions/auth-handler.php?action=login" class="space-y-6">
                <?php if (!empty($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?= e($_GET['redirect']) ?>">
                <?php endif; ?>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Email
                        Address</label>
                    <input id="login_email" type="email" name="email" placeholder="email@example.com" required
                        class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                </div>
                <div>
                    <label
                        class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Password</label>
                    <input id="login_password" type="password" name="password" placeholder="••••••••" required
                        class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                </div>
                <div class="flex justify-between items-center text-[11px] font-medium tracking-wide uppercase px-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="remember" class="w-3 h-3 rounded border-sand/50 accent-[#899178]">
                        <span class="text-muted group-hover:text-foreground">Remember me</span>
                    </label>
                    <a href="<?= BASE ?>auth/forgot-password.php"
                        class="text-accent/60 hover:text-accent transition-colors">Forgot?</a>
                </div>
                <button type="submit" id="login_submit"
                    class="w-full py-5 bg-foreground text-background rounded-2xl text-sm font-medium hover:bg-neutral-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    Sign In
                </button>
            </form>

            <div class="mt-12 pt-8 border-t border-sand/30 text-center">
                <p class="text-xs text-muted">New to Advet?</p>
                <a href="<?= BASE ?>auth/register.php"
                    class="inline-block mt-4 text-sm font-serif italic text-accent hover:text-accent-dark transition-colors">
                    Apply for Access <span class="not-italic">→</span>
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>