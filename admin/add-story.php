<?php
// FILE: admin/add-story.php
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
$story = [
    'title'        => '',
    'slug'         => '',
    'excerpt'      => '',
    'content'      => '',
    'cover_image'      => '',
    'meta_title'       => '',
    'meta_description' => '',
    'meta_keywords'    => '',
    'published_at'     => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = ?');
    $stmt->execute([$id]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $story = $fetched;
        if ($story['published_at']) {
            $story['published_at'] = date('Y-m-d\TH:i', strtotime($story['published_at']));
        }
    }
}

// Reuse logic from pages.php for the toolbar
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Edit Story' : 'New Story' ?> | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; }
        .form-reveal { opacity:0; transform:translateY(20px); animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards }
        @keyframes fadeIn { to { opacity:1; transform:none } }

        /* ── Rich Text Editor ─────────────────────────────────────── */
        .editor-wrapper {
            border: 1px solid rgba(223,216,204,0.4);
            border-radius: 24px;
            background: rgba(244,240,230,0.2);
            transition: all 0.3s;
        }
        .editor-wrapper:focus-within {
            border-color: #899178;
            background: rgba(244,240,230,0.4);
            box-shadow: 0 10px 40px rgba(137,145,120,0.06);
        }

        .editor-toolbar {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 10px 16px;
            background: rgba(253,252,249,0.85);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(223,216,204,0.3);
            border-radius: 24px 24px 0 0;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 10;
            overflow: visible;
        }
        .tb-sep { width: 1px; height: 18px; background: rgba(223,216,204,0.6); margin: 0 4px; }
        
        .tb-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: #6D685C;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            overflow: visible;
        }
        .tb-btn:hover {
            background: rgba(137,145,120,0.1);
            color: #2A2925;
            transform: translateY(-1px);
        }
        .tb-btn svg { width: 15px; height: 15px; stroke: currentColor; stroke-width: 1.8; }
        
        .tb-select {
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 500;
            color: #6D685C;
            background: transparent;
            border: 1px solid rgba(223,216,204,0.4);
            border-radius: 8px;
            padding: 4px 8px;
            cursor: pointer;
            outline: none;
        }

        .tb-color-btn { position: relative; }
        .tb-color-btn input[type=color] {
            position: absolute; inset: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }

        /* Tooltip */
        .tb-btn::after {
            content: attr(data-tip);
            position: absolute; top: calc(100% + 7px); left: 50%; transform: translateX(-50%);
            background: #2A2925; color: #FDFCF9; font-size: 9px; font-family: 'Inter', sans-serif;
            letter-spacing: 0.05em; white-space: nowrap; padding: 3px 8px; border-radius: 6px;
            pointer-events: none; opacity: 0; z-index: 100; transition: opacity 0.18s;
        }
        .tb-btn::before {
            content: ''; position: absolute; top: calc(100% + 2px); left: 50%; transform: translateX(-50%);
            border: 4px solid transparent; border-bottom-color: #2A2925;
            pointer-events: none; opacity: 0; z-index: 100; transition: opacity 0.18s;
        }
        .tb-btn:hover::after, .tb-btn:hover::before { opacity: 1; }

        .editor-content {
            min-height: 450px;
            padding: 40px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            line-height: 1.8;
            color: #2A2925;
            font-weight: 300;
            outline: none;
            overflow-y: auto;
            border-radius: 0 0 24px 24px;
        }
        .editor-content h1 { font-size: 2em; font-family: 'Cormorant Garamond', serif; font-style: italic; margin: 1.2em 0 0.5em; }
        .editor-content h2 { font-size: 1.5em; font-family: 'Cormorant Garamond', serif; font-style: italic; margin: 1em 0 0.4em; }
        .editor-content p { margin-bottom: 1.2em; }
        .editor-content blockquote {
            border-left: 3px solid #899178; margin: 1.5em 0; padding: 1.2em 2em;
            background: rgba(137,145,120,0.05); font-style: italic; border-radius: 0 16px 16px 0;
        }
        .editor-content img { border-radius: 20px; max-width: 100%; margin: 2em auto; display: block; }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <header class="flex flex-wrap justify-between items-end mb-12 gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4 cursor-pointer hover:text-accent-dark transition-colors" onclick="history.back()">← Back to Stories</p>
            <h1 class="text-4xl font-serif font-light italic">
                <?= $id ? 'Edit' : 'New' ?> <span class="text-muted">Story</span>
            </h1>
        </div>
        <button form="story-form" class="px-8 py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl hover:-translate-y-1 transition-all">
            Save Journal Entry
        </button>
    </header>

    <form id="story-form" action="<?= BASE ?>actions/save-story.php" method="POST" enctype="multipart/form-data" class="max-w-4xl space-y-12 mb-20">
        <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?= $id ?>">
        <?php endif; ?>

        <!-- Content -->
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-8">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Story Content</h2>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Title *</label>
                <input type="text" name="title" value="<?= e($story['title']) ?>" required
                       class="w-full px-6 py-5 border border-sand/40 bg-surface/30 rounded-2xl text-lg focus:outline-none focus:border-accent font-serif transition-all"
                       placeholder="e.g. The Imperfection of Plaster">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Excerpt (Short description)</label>
                <textarea name="excerpt" rows="3"
                          class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light text-muted transition-all leading-relaxed"
                          placeholder="A brief summary for previews..."><?= e($story['excerpt']) ?></textarea>
            </div>

            <!-- Rich Text Editor for Content -->
            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Content *</label>
                <textarea name="content" id="story_content_input" class="hidden"><?= htmlspecialchars($story['content']) ?></textarea>

                <div class="editor-wrapper shadow-sm">
                    <div class="editor-toolbar" id="story-toolbar" data-target="story_editor">
                        <select class="tb-select mr-2" onchange="execBlock(this,'story_editor')">
                            <option value="div">Paragraph</option>
                            <option value="h1">Heading 1</option>
                            <option value="h2">Heading 2</option>
                            <option value="h3">Heading 3</option>
                            <option value="pre">Code Block</option>
                        </select>
                        <div class="tb-sep"></div>
                        <button type="button" class="tb-btn" data-tip="Bold" onclick="execFmt('bold','story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 4h8a4 4 0 0 1 0 8H6z" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 12h9a4 4 0 0 1 0 8H6z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="tb-btn" data-tip="Italic" onclick="execFmt('italic','story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><line x1="19" y1="4" x2="10" y2="4" stroke-linecap="round"/><line x1="14" y1="20" x2="5" y2="20" stroke-linecap="round"/><line x1="15" y1="4" x2="9" y2="20" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" class="tb-btn" data-tip="Underline" onclick="execFmt('underline','story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 3v7a6 6 0 0 0 12 0V3" stroke-linecap="round"/><line x1="4" y1="21" x2="20" y2="21" stroke-linecap="round"/></svg>
                        </button>
                        <div class="tb-sep"></div>
                        <button type="button" class="tb-btn" data-tip="Align Left" onclick="execFmt('justifyLeft','story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><line x1="3" y1="6" x2="21" y2="6" stroke-linecap="round"/><line x1="3" y1="12" x2="15" y2="12" stroke-linecap="round"/><line x1="3" y1="18" x2="18" y2="18" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" class="tb-btn" data-tip="Bullet List" onclick="execFmt('insertUnorderedList','story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><line x1="9" y1="6" x2="20" y2="6" stroke-linecap="round"/><line x1="9" y1="12" x2="20" y2="12" stroke-linecap="round"/><line x1="9" y1="18" x2="20" y2="18" stroke-linecap="round"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                        </button>
                        <button type="button" class="tb-btn" data-tip="Blockquote" onclick="insertBlockquote('story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="tb-sep"></div>
                        <button type="button" class="tb-btn" data-tip="Link" onclick="insertLink('story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" class="tb-btn tb-color-btn" data-tip="Text Color">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 7L4 19"/><path d="M15 7L20 19"/><path d="M6.5 13h11"/><path d="M5 19.5c0-.5.2-1 1-1 1 0 1 1 2 1s1-1 2-1 1 1 2 1 1-1 2-1 1 1 2 1"/></svg>
                            <input type="color" value="#2A2925" oninput="document.execCommand('foreColor',false,this.value)">
                        </button>
                        <div class="tb-sep"></div>
                        <button type="button" class="tb-btn" data-tip="Clear" onclick="clearFmt('story_editor')">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 19l7-14" stroke-linecap="round"/><path d="M18 19l-7-14" stroke-linecap="round"/></svg>
                        </button>
                        <span class="ml-auto text-[9px] uppercase tracking-widest text-muted/50 font-medium" id="story-wc">0 words</span>
                    </div>
                    <div class="editor-content" id="story_editor" contenteditable="true" data-placeholder="Tell your story..." data-input="story_content_input" data-wc="story-wc"></div>
                </div>
            </div>
        </div>

        <!-- Meta -->
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-8">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">SEO Settings</h2>
            
            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Meta Title</label>
                <input type="text" name="meta_title" value="<?= e($story['meta_title']) ?>"
                       class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all"
                       placeholder="If blank, Story Title will be used.">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Meta Description</label>
                <textarea name="meta_description" rows="3"
                          class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light text-muted transition-all leading-relaxed"
                          placeholder="A concise summary for search engine results..."><?= e($story['meta_description']) ?></textarea>
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Meta Keywords</label>
                <input type="text" name="meta_keywords" value="<?= e($story['meta_keywords']) ?>"
                       class="w-full px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all"
                       placeholder="e.g. architecture, plaster, design, minimalist">
            </div>
        </div>

        <!-- Visual & Publish Info -->
        <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-8">
            <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Visual & Publish Info</h2>

            <div class="relative w-40 h-28 rounded-2xl overflow-hidden border border-sand/40 bg-surface/50 flex items-center justify-center group" id="cover-container">
                <img id="cover-preview" src="<?= !empty($story['cover_image']) ? imgUrl($story['cover_image']) : '' ?>" 
                     class="w-full h-full object-cover <?= empty($story['cover_image']) ? 'hidden' : '' ?>">
                
                <div id="cover-preview-placeholder" class="text-center p-4 <?= !empty($story['cover_image']) ? 'hidden' : '' ?>">
                    <svg class="w-6 h-6 mx-auto text-accent/40 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-[8px] font-bold uppercase tracking-widest text-muted">Cover Image</span>
                </div>

                <!-- Remove Button -->
                <button type="button" onclick="clearStoryCover()" id="story-remove-btn" 
                        class="absolute top-2 right-2 w-6 h-6 bg-foreground/80 text-background rounded-full items-center justify-center text-[10px] z-20 hover:bg-accent transition-all <?= empty($story['cover_image']) ? 'hidden' : 'flex' ?>">
                    ✕
                </button>

                <input type="file" name="cover_image" id="cover_image_input" accept="image/jpeg,image/png,image/webp"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       data-uploader-mode="mini" 
                       data-preview-target="cover-preview"
                       onchange="document.getElementById('story-remove-btn').classList.replace('hidden','flex')">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Publish Date (Leave blank to save as Draft)</label>
                <input type="datetime-local" name="published_at" value="<?= $story['published_at'] ?>"
                       class="w-full md:w-1/2 px-6 py-4 border border-sand/40 bg-surface/30 rounded-2xl text-sm focus:outline-none focus:border-accent font-light transition-all">
                <p class="text-[10px] text-muted/60 mt-2 uppercase tracking-widest ml-1">Setting a future date will keep it 'Scheduled'.</p>
            </div>
        </div>

        <!-- Sticky Mobile Save -->
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-background border-t border-sand/30 md:hidden z-40">
            <button type="submit" class="w-full py-4 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-widest shadow-xl">
                Save
            </button>
        </div>
    </form>
</main>

<script>
function getEditor(id) { return document.getElementById(id); }
function execFmt(cmd, eId) { getEditor(eId).focus(); document.execCommand(cmd, false, null); sync(eId); }
function execBlock(sel, eId) { getEditor(eId).focus(); document.execCommand('formatBlock', false, sel.value); sel.value = 'div'; sync(eId); }
function insertBlockquote(eId) { getEditor(eId).focus(); document.execCommand('formatBlock', false, 'blockquote'); sync(eId); }
function insertLink(eId) { const url = prompt('URL:','https://'); if(url){ getEditor(eId).focus(); document.execCommand('createLink', false, url); sync(eId); } }
function clearFmt(eId) { getEditor(eId).focus(); document.execCommand('removeFormat'); document.execCommand('formatBlock',false,'div'); sync(eId); }

function sync(eId) {
    const editor = getEditor(eId);
    const input = document.getElementById(editor.dataset.input);
    const wc = document.getElementById(editor.dataset.wc);
    input.value = editor.innerHTML;
    const words = editor.innerText.trim() ? editor.innerText.trim().split(/\s+/).length : 0;
    wc.textContent = words + ' word' + (words!==1?'s':'');
}

document.addEventListener('DOMContentLoaded', function() {
    const editor = getEditor('story_editor');
    const input = document.getElementById(editor.dataset.input);
    const raw = input.value;
    if (raw.trim().startsWith('<') || raw.includes('<p')) { editor.innerHTML = raw; }
    else if (raw.trim()) { editor.innerHTML = raw.split('\n').filter(l=>l.trim()).map(l=>'<p>'+l+'</p>').join(''); }
    sync('story_editor');
    editor.addEventListener('input', () => sync('story_editor'));
    document.getElementById('story-form').addEventListener('submit', () => { input.value = editor.innerHTML; });

    // Hero Cover Management
    window.clearStoryCover = function() {
        const fileInput = document.getElementById('cover_image_input');
        const preview = document.getElementById('cover-preview');
        const placeholder = document.getElementById('cover-preview-placeholder');
        const btn = document.getElementById('story-remove-btn');
        
        fileInput.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        btn.classList.replace('flex','hidden');
        
        // Add a hidden input to tell the server to remove the cover
        const removeInput = document.createElement('input');
        removeInput.type = 'hidden';
        removeInput.name = 'remove_cover';
        removeInput.value = '1';
        fileInput.form.appendChild(removeInput);

        // Also remove any hidden input added by the async uploader for this specific field
        const asyncHidden = document.querySelector(`input[type="hidden"][name="async_cover_image"]`);
        if(asyncHidden) asyncHidden.remove();
    }
});
</script>
</body>
</html>
