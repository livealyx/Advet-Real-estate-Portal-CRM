<?php
// FILE: auth/reset-password.php
session_start();
require_once '../config/db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid or missing reset token.'];
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token has expired or is invalid.'];
    header('Location: ' . BASE . 'auth/forgot-password.php'); exit;
}

$pageTitle = 'Set New Password';
$pageDesc  = 'Finalize your account recovery.';
$solidNav  = true;
require_once '../includes/header.php';
?>
    <main class="flex-grow flex items-center justify-center px-6 pt-48 pb-24 bg-surface/30">
        <div class="w-full max-w-md auth-card">
            <div class="bg-background p-10 sm:p-12 rounded-[2rem] shadow-sm border border-sand/50">
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-serif font-light mb-2">Restoration</h1>
                    <p class="text-sm text-muted">Create a new, strong password for your sanctuary.</p>
                </div>

                <form method="POST" action="<?= BASE ?>actions/auth-handler.php?action=reset_password" class="space-y-6">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">New Password</label>
                        <input type="password" name="password" placeholder="••••••••" required minlength="8"
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-medium text-muted mb-2 ml-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required minlength="8"
                               class="w-full px-6 py-4 bg-surface/50 border border-sand/30 rounded-2xl text-sm transition-all focus:bg-background focus:border-accent">
                    </div>
                    
                    <button type="submit"
                            class="w-full py-5 bg-foreground text-background rounded-2xl text-sm font-medium hover:bg-neutral-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </main>

<?php require_once '../includes/footer.php'; ?>
