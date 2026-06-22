<?php
// FILE: auth/forgot-password.php
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'index.php');
    exit;
}
$solidNav = true;
$pageTitle = 'Forgot Password';
$pageDesc  = 'Recover your Advet Buildwell account access.';
require_once '../includes/header.php';
?>
    <main class="flex-grow flex items-center justify-center px-6 pt-48 pb-24 bg-surface/30">
        <div class="w-full max-w-md auth-card">
            <div class="bg-background p-10 sm:p-12 rounded-[2rem] shadow-sm border border-sand/50">
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-serif font-light mb-2">Recover</h1>
                    <p class="text-sm text-muted">Enter your email and we'll send a reset link.</p>
                </div>

                <form method="POST" action="<?= BASE ?>actions/auth-handler.php?action=forgot_request" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Email Address</label>
                        <input type="email" name="email" placeholder="your@email.com" required
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    
                    <button type="submit"
                            class="w-full py-5 bg-foreground text-background rounded-2xl text-sm font-medium hover:bg-neutral-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        Send Reset Link
                    </button>
                    
                    <div class="text-center pt-2">
                        <a href="<?= BASE ?>auth/login.php" class="text-[10px] uppercase tracking-widest font-bold text-accent/60 hover:text-accent transition-colors">
                            ← Back to Login
                        </a>
                    </div>
                </form>

                <div class="mt-12 pt-8 border-t border-sand/30 text-center">
                    <p class="text-[10px] text-muted italic">Need immediate help? <a href="<?= BASE ?>contact.php" class="text-accent not-italic font-bold underline">Contact Support</a></p>
                </div>
            </div>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
