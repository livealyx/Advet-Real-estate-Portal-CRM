<?php
// FILE: auth/register.php
session_start();
if (!empty($_SESSION['user'])) { header('Location: ' . BASE . 'index.php'); exit; }
$solidNav  = true;
$pageTitle = 'Apply for Access';
$pageDesc  = 'Create your Advet Buildwell member account.';
require_once '../includes/header.php';
?>
    <main class="flex-grow flex items-center justify-center px-6 pt-48 pb-24 bg-surface/30">
        <div class="w-full max-w-md auth-card">
            <div class="bg-background p-10 sm:p-12 rounded-[2rem] shadow-sm border border-sand/50">
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-serif font-light mb-2">Apply for Access</h1>
                    <p class="text-sm text-muted">Join a community that values intentional living.</p>
                </div>

                <form method="POST" action="<?= BASE ?>actions/auth-handler.php?action=register" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Full Name</label>
                        <input id="reg_name" type="text" name="name" placeholder="Your name" required
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Email Address</label>
                        <input id="reg_email" type="email" name="email" placeholder="email@example.com" required
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Password</label>
                        <input id="reg_password" type="password" name="password" placeholder="Min. 8 characters" required minlength="8"
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Confirm Password</label>
                        <input id="reg_confirm" type="password" name="confirm_password" placeholder="••••••••" required
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    <button type="submit" id="reg_submit"
                            class="w-full py-5 bg-foreground text-background rounded-2xl text-sm font-medium hover:bg-neutral-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        Create Account
                    </button>
                </form>

                <div class="mt-12 pt-8 border-t border-sand/30 text-center">
                    <p class="text-xs text-muted">Already a member?</p>
                    <a href="<?= BASE ?>auth/login.php" class="inline-block mt-4 text-sm font-serif italic text-accent hover:text-accent-dark transition-colors">
                        Sign In <span class="not-italic">→</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
