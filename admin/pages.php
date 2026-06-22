<?php
// FILE: admin/pages.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo      = getPDO();
$settings = loadSettings($pdo);
$s = $settings; // shorthand
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Pages | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .form-reveal { opacity:0; transform:translateY(20px); animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards }
        @keyframes fadeIn { to { opacity:1; transform:none } }

        /* Content area */
        .editor-content {
            min-height: 380px;
            padding: 32px 40px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.85;
            color: #2A2925;
            font-weight: 300;
            outline: none;
            overflow-y: auto;
            border-radius: 0 0 24px 24px;
        }
        .editor-content:empty::before {
            content: attr(data-placeholder);
            color: rgba(109,104,92,0.45);
            pointer-events: none;
        }
        .editor-content h1 { font-size: 1.6em; font-weight: 600; margin: 1.2em 0 0.5em; font-family: 'Cormorant Garamond', serif; }
        .editor-content h2 { font-size: 1.35em; font-weight: 600; margin: 1em 0 0.4em; font-family: 'Cormorant Garamond', serif; }
        .editor-content h3 { font-size: 1.15em; font-weight: 600; margin: 0.8em 0 0.3em; }
        .editor-content p  { margin: 0 0 0.75em; }
        .editor-content ul, .editor-content ol { padding-left: 1.6em; margin: 0.5em 0 0.75em; }
        .editor-content li { margin-bottom: 0.3em; }
        .editor-content blockquote {
            border-left: 3px solid #899178;
            margin: 1em 0;
            padding: 0.5em 1.2em;
            background: rgba(137,145,120,0.06);
            border-radius: 0 12px 12px 0;
            color: #6D685C;
            font-style: italic;
        }
        .editor-content a { color: #899178; text-decoration: underline; }
        .editor-content hr { border: none; border-top: 1px solid rgba(223,216,204,0.6); margin: 1.5em 0; }
        .editor-content table { border-collapse: collapse; width: 100%; margin: 1em 0; }
        .editor-content td, .editor-content th {
            border: 1px solid rgba(223,216,204,0.6);
            padding: 8px 12px;
            font-size: 13px;
        }
        .editor-content th { background: rgba(244,240,230,0.5); font-weight: 600; }
        .editor-content pre, .editor-content code {
            font-family: 'Courier New', monospace;
            background: rgba(244,240,230,0.6);
            border-radius: 6px;
            padding: 2px 6px;
            font-size: 12px;
        }
        .editor-content pre { padding: 12px 16px; display: block; overflow-x: auto; }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-4xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Content Management</p>
            <h1 class="text-4xl font-serif font-light italic">Legal <span class="text-muted">Pages</span></h1>
        </div>

        <form id="pagesForm" method="POST" action="<?= BASE ?>actions/save-pages.php" class="space-y-10 form-reveal" style="animation-delay:.1s">
            <!-- Legal Pages -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <div class="flex items-center justify-between mb-10 border-b border-sand pb-4">
                    <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent">Privacy &amp; Terms</h2>
                    <p class="text-[10px] text-muted italic">Used in the footer and main navigation links.</p>
                </div>

                <div class="space-y-14">

                    <!-- Privacy Policy Editor -->
                    <div class="mb-10 pb-10 border-b border-sand/30">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Privacy Policy</label>
                        <?php 
                        $editor_name = 'settings[privacy_policy]';
                        $editor_value = $s['privacy_policy'] ?? '';
                        include '../components/editor/editor.php';
                        ?>
                    </div>

                    <!-- Terms of Use Editor -->
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Terms of Use</label>
                        <?php 
                        $editor_name = 'settings[terms_of_use]';
                        $editor_value = $s['terms_of_use'] ?? '';
                        include '../components/editor/editor.php';
                        ?>
                    </div>

                </div>
            </div>

            <!-- Page Meta -->
            <div class="bg-surface/40 p-6 rounded-2xl border border-sand/20 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-[11px] text-muted leading-relaxed">
                    Changes here will reflect instantly on the <a href="<?= BASE ?>public/privacy-policy.php" target="_blank" class="text-accent underline">Privacy Policy</a> and <a href="<?= BASE ?>public/terms-of-use.php" target="_blank" class="text-accent underline">Terms of Use</a> public pages.
                </p>
            </div>

            <!-- Save -->
            <div class="flex gap-4 pt-4 pb-20">
                <button type="submit"
                        class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    Save Legal Pages
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // Asset auto-init handled by editor.js
</script>

</body>
</html>
