<?php
// FILE: admin/add-faq.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
$faq = [
    'question'      => '',
    'answer'        => '',
    'display_order' => 0,
    'status'        => 'active',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM faqs WHERE id = ?');
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $faq = $fetched;
    }
}
$settings = loadSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit FAQ' : 'New FAQ' ?> | Admin</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4 cursor-pointer hover:text-accent-dark transition-colors" onclick="location.href='faq.php'">← Back to FAQ</p>
            <h1 class="text-4xl font-serif font-light italic">
                <?= $id ? 'Edit' : 'New' ?> <span class="text-muted">FAQ</span>
            </h1>
        </div>
        <button form="faq-form" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            Save FAQ
        </button>
    </header>

    <form id="faq-form" action="<?= BASE ?>actions/save-faq.php" method="POST" class="max-w-4xl space-y-12 mb-20">
        <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-8">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">FAQ Content</h2>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Question *</label>
                <textarea name="question" rows="3" required
                       class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-lg focus:outline-none focus:border-accent font-serif transition-all"
                       placeholder="e.g. How do I contact support?"><?= e($faq['question']) ?></textarea>
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Answer *</label>
                <textarea name="answer" rows="6" required
                          class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light text-muted transition-all leading-relaxed"
                          placeholder="Provide a detailed answer..."><?= e($faq['answer']) ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Display Order</label>
                    <input type="number" name="display_order" value="<?= $faq['display_order'] ?>"
                           class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all">
                </div>
                <div>
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Status</label>
                    <select name="status" class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all">
                        <option value="active" <?= $faq['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="draft" <?= $faq['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
            </div>
        </div>
    </form>
</main>
</body>
</html>
