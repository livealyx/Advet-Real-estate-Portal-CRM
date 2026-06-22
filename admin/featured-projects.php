<?php
// FILE: admin/featured-projects.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$projects = $pdo->query("SELECT * FROM featured_projects ORDER BY display_order ASC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Projects | Advet Studio</title>
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
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Homepage Curation</p>
                <h1 class="text-4xl font-serif font-light italic">Featured <span class="text-muted">Projects</span></h1>
            </div>
            <a href="<?= BASE ?>admin/add-featured-project.php" 
               class="px-8 py-4 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-accent transition-all shadow-lg transform hover:-translate-y-1">
                New Project +
            </a>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 form-reveal" style="animation-delay: 0.1s">
            <?php if (empty($projects)): ?>
                <div class="col-span-full py-20 text-center bg-background rounded-[2.5rem] border border-sand/40">
                    <p class="text-serif italic text-muted text-xl">No projects featured yet.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($projects as $p): ?>
            <div class="bg-background rounded-[2.5rem] border border-sand/40 overflow-hidden shadow-sm group">
                <div class="aspect-[4/3] relative overflow-hidden bg-surface">
                    <img src="<?= e(imgUrl($p['image_path'])) ?>" class="w-full h-full object-cover transition-transform duration-[5s] group-hover:scale-105">
                </div>
                <div class="p-8">
                    <h3 class="text-lg font-serif mb-2"><?= e($p['title']) ?></h3>
                    <p class="text-xs text-muted font-light leading-relaxed mb-6 line-clamp-2"><?= e($p['description']) ?></p>
                    
                    <div class="flex items-center justify-between pt-6 border-t border-sand/30">
                        <a href="<?= BASE ?>admin/add-featured-project.php?id=<?= $p['id'] ?>" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-accent-dark transition-colors">Edit</a>
                        <form method="POST" action="<?= BASE ?>actions/delete-featured-project.php" onsubmit="return confirm('Remove this project from highlights?');">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="text-[10px] font-bold uppercase tracking-widest text-red-400 hover:text-red-600 transition-colors">Remove</button>
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
