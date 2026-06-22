<?php
// FILE: admin/team.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$team = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC, name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-6xl mx-auto">
        
        <header class="flex justify-between items-end mb-12 form-reveal">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Personnel Management</p>
                <h1 class="text-4xl font-serif font-light italic">Our <span class="text-muted">Collective</span></h1>
            </div>
            <a href="<?= BASE ?>admin/add-team-member.php" 
               class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-accent transition-all shadow-lg transform hover:-translate-y-1">
                Add Member +
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 form-reveal" style="animation-delay: 0.1s">
            <?php if (empty($team)): ?>
                <div class="col-span-full py-20 text-center bg-background rounded-[2.5rem] border border-sand/40">
                    <p class="text-serif italic text-muted text-xl">No team members listed yet.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($team as $m): ?>
            <div class="bg-background rounded-[2.5rem] border border-sand/40 overflow-hidden shadow-sm group">
                <div class="aspect-square relative overflow-hidden bg-surface">
                    <img src="<?= $m['image_path'] ? imgUrl($m['image_path']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=400' ?>" 
                         class="w-full h-full object-cover transition-transform duration-[5s] group-hover:scale-105">
                </div>
                <div class="p-8">
                    <div class="mb-4">
                        <h3 class="text-lg font-serif"><?= e($m['name']) ?></h3>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-accent"><?= e($m['designation'] ?: 'Principal') ?></p>
                    </div>
                    
                    <div class="space-y-2 mb-6">
                        <p class="text-[11px] text-muted flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?= e($m['email']) ?>
                        </p>
                        <p class="text-[11px] text-muted flex items-center gap-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <?= e($m['phone']) ?>
                        </p>
                    </div>
                    
                    <div class="flex items-center justify-between pt-6 border-t border-sand/30">
                        <a href="<?= BASE ?>admin/add-team-member.php?id=<?= $m['id'] ?>" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-accent-dark transition-colors">Edit Profile</a>
                        <form method="POST" action="<?= BASE ?>actions/delete-team-member.php" onsubmit="return confirm('Remove team member?');">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-600 transition-colors">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>
</body>
</html>
