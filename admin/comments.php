<?php
// FILE: admin/comments.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo = getPDO();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cid = (int)$_POST['id'];
    if ($_POST['action'] === 'approve') {
        $pdo->prepare("UPDATE story_comments SET status = 'approved' WHERE id = ?")->execute([$cid]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Comment approved.'];
    } elseif ($_POST['action'] === 'spam') {
        $pdo->prepare("UPDATE story_comments SET status = 'spam' WHERE id = ?")->execute([$cid]);
        $_SESSION['flash'] = ['type' => 'update', 'msg' => 'Comment marked as spam.'];
    } elseif ($_POST['action'] === 'delete') {
        $pdo->prepare("DELETE FROM story_comments WHERE id = ?")->execute([$cid]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Comment deleted.'];
    }
    header('Location: ' . BASE . 'admin/comments.php'); exit;
}

// Fetch comments with story titles
$comments = $pdo->query("
    SELECT c.*, s.title as story_title, s.slug as story_slug 
    FROM story_comments c
    JOIN stories s ON s.id = c.story_id
    ORDER BY c.created_at DESC
")->fetchAll();

$pageTitle = "Story Comments";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Comments | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
    <?php require_once '../includes/flash.php'; ?>
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="flex-grow p-8 sm:p-12 overflow-y-auto">
        <div class="max-w-6xl mx-auto">
            <header class="mb-12 form-reveal">
                <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Interactions</p>
                <h1 class="text-4xl font-serif font-light italic">Story <span class="text-muted">Comments</span></h1>
            </header>

            <div class="bg-background rounded-[2.5rem] shadow-sm border border-sand/40 overflow-hidden form-reveal" style="animation-delay: 0.1s">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-surface/30">
                            <tr>
                                <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">User</th>
                                <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Comment</th>
                                <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Story</th>
                                <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted">Status</th>
                                <th class="px-8 py-5 text-[10px] uppercase tracking-widest font-bold text-muted text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sand/30">
                            <?php if (empty($comments)): ?>
                                <tr><td colspan="5" class="px-8 py-12 text-center text-sm text-muted italic">No comments to moderate.</td></tr>
                            <?php else: foreach ($comments as $c): 
                                $statusCls = match($c['status']) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'spam'     => 'bg-red-50 text-red-700 border-red-200',
                                    default    => 'bg-surface text-muted'
                                };
                            ?>
                            <tr class="hover:bg-surface/10 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold"><?= e($c['user_name']) ?></div>
                                    <div class="text-[10px] text-muted"><?= e($c['user_email']) ?></div>
                                    <div class="text-[9px] text-muted opacity-60 mt-1"><?= date('M j, Y H:i', strtotime($c['created_at'])) ?></div>
                                </td>
                                <td class="px-8 py-6 max-w-md">
                                    <div class="text-sm text-muted line-clamp-3 leading-relaxed"><?= e($c['comment_text']) ?></div>
                                </td>
                                <td class="px-8 py-6">
                                    <a href="<?= BASE ?>story/<?= $c['story_slug'] ?>" target="_blank" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:underline"><?= e($c['story_title']) ?></a>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-2.5 py-1 text-[8px] font-bold uppercase tracking-widest rounded border <?= $statusCls ?>">
                                        <?= e($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <?php if ($c['status'] !== 'approved'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="px-3 py-1.5 bg-emerald-500/10 text-emerald-600 rounded text-[9px] font-bold uppercase tracking-widest hover:bg-emerald-500 hover:text-white transition-all">Approve</button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($c['status'] !== 'spam'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="action" value="spam" class="px-3 py-1.5 bg-amber-500/10 text-amber-600 rounded text-[9px] font-bold uppercase tracking-widest hover:bg-amber-500 hover:text-white transition-all">Spam</button>
                                        </form>
                                        <?php endif; ?>

                                        <form method="POST" onsubmit="return confirm('Delete this comment?')">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="action" value="delete" class="px-3 py-1.5 bg-red-500/10 text-red-600 rounded text-[9px] font-bold uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script> document.addEventListener('DOMContentLoaded', () => { document.querySelectorAll('.form-reveal').forEach((el, i) => { el.style.animationDelay = (i * 0.1) + 's'; el.style.opacity = '1'; }); }); </script>
</body>
</html>
