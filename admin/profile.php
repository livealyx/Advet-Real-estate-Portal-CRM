<?php
// FILE: admin/profile.php
session_start();
require_once '../config/db.php';
// Allows both admin and agent to update their profile
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$settings = loadSettings($pdo);
$userId = (int)$_SESSION['user']['id'];

// Check/Add profile_picture column gracefully if it doesn't exist
try {
    $pdo->query("SELECT profile_picture FROM users LIMIT 1");
} catch (\Throwable $e) {
    // Column doesn't exist, let's create it on the fly
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL AFTER email");
    } catch (\Throwable $e2) {
        // Fallback silently if we somehow lack permissions
    }
}

// Fetch complete user data
$stmt = $pdo->prepare("SELECT name, email, role, profile_picture FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
if (!$userData) {
    header('Location: ' . BASE . 'auth/logout.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-2xl mx-auto">
        <header class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Account Settings</p>
            <h1 class="text-4xl font-serif font-light italic">My <span class="text-muted">Profile</span></h1>
        </header>

        <form action="<?= BASE ?>actions/save-profile.php" method="POST" enctype="multipart/form-data" class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 p-8 sm:p-12 form-reveal" style="animation-delay: 0.1s">
            
            <div class="flex items-center gap-8 mb-10 pb-10 border-b border-sand/40">
                <div class="relative group cursor-pointer w-24 h-24 rounded-full bg-surface border border-sand/50 overflow-hidden shrink-0 flex items-center justify-center">
                    <?php if (!empty($userData['profile_picture'])): ?>
                        <img id="profile-preview" src="<?= imgUrl($userData['profile_picture']) ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div id="profile-preview-placeholder" class="text-2xl font-serif text-accent"><?= substr($userData['name'], 0, 1) ?></div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-background/80 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-6 h-6 text-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    </div>
                    <input type="file" name="profile_picture" id="profile_picture" 
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" 
                           accept="image/jpeg, image/png, image/webp" 
                           data-uploader-mode="mini" 
                           data-preview-target="profile-preview">
                </div>
                <div>
                    <h3 class="text-lg font-serif">Profile Picture</h3>
                    <p class="text-xs text-muted mb-2">Click the circle to upload a new avatar. Max 2MB (JPG, PNG).</p>
                    <label for="profile_picture" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-foreground cursor-pointer transition-colors block">Upload Image</label>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Full Name</label>
                    <input type="text" name="name" value="<?= e($userData['name']) ?>" required
                           class="w-full bg-surface/30 border border-sand/40 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-accent transition-colors">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Email Base</label>
                    <input type="email" name="email" value="<?= e($userData['email']) ?>" required
                           class="w-full bg-surface/30 border border-sand/40 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-accent transition-colors">
                </div>
                <div class="pt-6">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Change Password (Optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password"
                           class="w-full bg-surface/30 border border-sand/40 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-accent transition-colors mb-2">
                </div>
            </div>

            <div class="mt-10 pt-8 border-t border-sand/40 flex justify-end gap-4">
                <?php $cancelUrl = in_array($_SESSION['user']['role'] ?? '', ['admin', 'agent']) ? BASE . 'admin/dashboard.php' : BASE . 'index.php'; ?>
                <a href="<?= $cancelUrl ?>" class="px-8 py-4 bg-surface text-muted rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-sand transition-colors">Cancel</a>
                <button type="submit" class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">Save Profile</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
