<?php
// FILE: admin/add-featured-project.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$id = $_GET['id'] ?? null;
$project = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM featured_projects WHERE id = ?");
    $stmt->execute([$id]);
    $project = $stmt->fetch();
}

$title = $project ? 'Edit Project' : 'New Featured Project';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}} input:focus,textarea:focus{outline:none;border-color:#899178;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-3xl mx-auto">
        
        <header class="mb-12 form-reveal">
            <a href="<?= BASE ?>admin/featured-projects.php" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-accent-dark flex items-center gap-2 mb-6">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to projects
            </a>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Curation</p>
            <h1 class="text-4xl font-serif font-light italic"><?= $project ? 'Edit' : 'Create' ?> <span class="text-muted">Featured Project</span></h1>
        </header>

        <form method="POST" action="<?= BASE ?>actions/save-featured-project.php" enctype="multipart/form-data" class="bg-background p-12 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-8 form-reveal" style="animation-delay: 0.1s">
            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-8">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Project Title</label>
                    <input type="text" name="title" required value="<?= e($project['title'] ?? '') ?>"
                           placeholder="e.g. Silverlake Hills"
                           class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all shadow-inner">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Description / Subtitle</label>
                    <textarea name="description" rows="3" required
                              placeholder="Describe the essence of this project..."
                              class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all shadow-inner h-32"><?= e($project['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Project Image</label>
                    <?php if ($project && $project['image_path']): ?>
                        <div class="mb-4 aspect-video rounded-2xl overflow-hidden border border-sand/30">
                            <img src="<?= imgUrl($project['image_path']) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" 
                           class="block w-full text-sm text-sand file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-accent hover:file:bg-sand transition-all">
                </div>

                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Display Order (Lower shows first)</label>
                    <input type="number" name="display_order" value="<?= $project['display_order'] ?? 0 ?>"
                           class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all shadow-inner">
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-5 bg-foreground text-background rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl mt-4">
                <?= $project ? 'Update Highlight' : 'Publish Highlight' ?>
            </button>
        </form>

    </div>
</main>
</body>
</html>
