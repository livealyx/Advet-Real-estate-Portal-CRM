<?php
// FILE: public/story-detail.php
session_start();
require_once '../config/db.php';

$pdo = getPDO();
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . BASE . 'stories.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM stories WHERE slug = ? AND published_at IS NOT NULL AND published_at <= NOW()");
$stmt->execute([$slug]);
$story = $stmt->fetch();

if (!$story) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Story not found or not published.'];
    header('Location: ' . BASE . 'stories.php');
    exit;
}

$pageTitle = $story['meta_title'] ?: $story['title'];
$pageDesc  = $story['meta_description'] ?: ($story['excerpt'] ?: 'Read the latest journal entry from Advet Buildwell.');
$pageKeywords = $story['meta_keywords'] ?? '';

// Fetch all approved comments for this story
$stmt = $pdo->prepare("SELECT * FROM story_comments WHERE story_id = ? AND status = 'approved' ORDER BY created_at ASC");
$stmt->execute([$story['id']]);
$comments = $stmt->fetchAll();

require_once '../includes/header.php';
?>

    <main class="flex-grow pt-40 pb-32">
        <article class="max-w-4xl mx-auto px-6 sm:px-12 lg:px-16 reveal">
            <!-- Header -->
            <header class="text-center mb-16">
                <p class="text-xs font-medium uppercase tracking-[0.3em] text-accent mb-6">Field Notes / Journal</p>
                <h1 class="text-5xl md:text-6xl font-serif font-light leading-tight mb-8">
                    <?= e($story['title']) ?>
                </h1>
                <div class="flex items-center justify-center gap-4 text-sm font-bold text-muted uppercase tracking-widest">
                    <time datetime="<?= $story['published_at'] ?>"><?= date('F j, Y', strtotime($story['published_at'])) ?></time>
                </div>
            </header>

            <!-- Cover Image -->
            <?php if ($story['cover_image']): ?>
            <div class="w-full aspect-[2/1] md:aspect-[21/9] image-soft-clip overflow-hidden mb-20">
                <img src="<?= imgUrl($story['cover_image']) ?>"
                     alt="<?= e($story['title']) ?>"
                     class="w-full h-full object-cover">
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="prose prose-lg prose-stone max-w-3xl mx-auto font-light text-muted leading-relaxed prose-headings:font-serif prose-headings:font-light prose-headings:text-foreground prose-a:text-accent prose-a:no-underline hover:prose-a:underline">
                <?= $story['content'] // Content is HTML, outputting directly ?>
            </div>
            
            <!-- Comments Section -->
            <section class="max-w-3xl mx-auto mt-24">
                <h3 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-12 border-b border-sand pb-4">
                    Responses (<?= count($comments) ?>)
                </h3>
                
                <div class="space-y-12 mb-20">
                    <?php if (empty($comments)): ?>
                        <p class="text-sm italic text-muted opacity-60">Be the first to share your thoughts on this architectural frequency.</p>
                    <?php else: foreach ($comments as $c): ?>
                        <div class="flex gap-6 items-start animate-fade-in">
                            <div class="w-12 h-12 rounded-full bg-surface border border-sand/40 flex items-center justify-center shrink-0 uppercase text-[10px] font-bold text-muted">
                                <?= substr($c['user_name'] ?? 'A', 0, 1) ?>
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="text-sm font-bold uppercase tracking-widest"><?= e($c['user_name']) ?></h4>
                                    <span class="text-[9px] text-muted tracking-widest"><?= date('M j, Y', strtotime($c['created_at'])) ?></span>
                                </div>
                                <div class="text-sm leading-relaxed text-muted bg-surface/30 p-6 rounded-2xl border border-sand/30">
                                    <?= nl2br(e($c['comment_text'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Comment Form -->
                <div class="bg-background border border-sand/40 rounded-[2.5rem] p-8 md:p-12 shadow-sm">
                    <p class="text-[10px] uppercase tracking-widest font-bold text-muted mb-6">Leave a Reply</p>
                    <form action="<?= BASE ?>actions/submit-comment.php" method="POST" class="space-y-6">
                        <input type="hidden" name="story_id" value="<?= $story['id'] ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php if (empty($_SESSION['user'])): ?>
                            <input type="text" name="user_name" placeholder="Your Name *" required
                                   class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                            <input type="email" name="user_email" placeholder="Email Address *" required
                                   class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all">
                            <?php else: ?>
                            <div class="col-span-2 px-6 py-4 bg-accent/5 border border-accent/20 rounded-2xl text-xs text-accent italic">
                                Logged in as: <span class="font-bold uppercase tracking-widest pl-1"><?= e($_SESSION['user']['name']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <textarea name="comment_text" rows="5" placeholder="Share your architectural insight..." required
                                  class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm focus:outline-none focus:border-accent transition-all leading-relaxed"></textarea>
                        
                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="px-10 py-5 bg-foreground text-background rounded-full text-xs font-bold uppercase tracking-widest hover:bg-black transition-all shadow-xl hover:-translate-y-1">
                                Post Comment
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Back Link -->
            <div class="max-w-3xl mx-auto mt-20 pt-10 border-t border-sand/30 flex justify-between items-center">
                <a href="<?= BASE ?>stories.php" class="inline-flex items-center gap-2 text-sm font-medium uppercase tracking-widest text-[#899178] hover:text-[#6E755F] transition-colors">
                    ← Back to Journal
                </a>
            </div>
        </article>
    </main>

<?php require_once '../includes/footer.php'; ?>
