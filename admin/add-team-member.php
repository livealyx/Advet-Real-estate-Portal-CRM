<?php
// FILE: admin/add-team-member.php
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
$member = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();
}

$title = $member ? 'Edit Profile' : 'New Team Member';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <?= getAdminTailwindConfig(isset($settings) ? $settings : []) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>
        body{-webkit-font-smoothing:antialiased;} 
        .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}
        @keyframes fadeIn{to{opacity:1;transform:none}} 
        input:focus,textarea:focus,select:focus{outline:none;border-color:#899178;}

        /* Rich Text Editor */
        .editor-wrapper { border:1px solid rgba(223,216,204,0.4); border-radius:20px; background:rgba(244,240,230,0.2); transition:all 0.3s; }
        .editor-wrapper:focus-within { border-color:#899178; background:rgba(244,240,230,0.4); }
        .editor-toolbar { display:flex; align-items:center; gap:2px; padding:8px 12px; background:rgba(253,252,249,0.85); backdrop-filter:blur(8px); border-bottom:1px solid rgba(223,216,204,0.3); border-radius:20px 20px 0 0; flex-wrap:wrap; position:sticky; top:0; z-index:10; }
        .tb-btn { display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; color:#6D685C; cursor:pointer; transition:all 0.2s; position:relative; }
        .tb-btn:hover { background:rgba(137,145,120,0.1); color:#2A2925; }
        .tb-btn svg { width:13px; height:13px; stroke:currentColor; stroke-width:1.8; }
        .tb-sep { width:1px; height:14px; background:rgba(223,216,204,0.6); margin:0 4px; }
        .tb-btn::after { content:attr(data-tip); position:absolute; top:calc(100% + 7px); left:50%; transform:translateX(-50%); background:#2A2925; color:#FDFCF9; font-size:9px; font-family:'Inter',sans-serif; letter-spacing:0.05em; white-space:nowrap; padding:3px 8px; border-radius:6px; pointer-events:none; opacity:0; z-index:100; transition:opacity 0.18s; }
        .tb-btn:hover::after { opacity:1; }
        .editor-content { min-height:150px; padding:20px; font-family:'Inter',sans-serif; font-size:13px; line-height:1.6; color:#2A2925; font-weight:300; outline:none; border-radius:0 0 20px 20px; }
        .editor-content p { margin-bottom:0.8em; }
    </style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">

<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-4xl mx-auto">
        
        <header class="mb-12 form-reveal">
            <a href="<?= BASE ?>admin/team.php" class="text-[10px] font-bold uppercase tracking-widest text-accent hover:text-accent-dark flex items-center gap-2 mb-6">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to team
            </a>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Profile Editor</p>
            <h1 class="text-4xl font-serif font-light italic"><?= $member ? 'Refine' : 'Add' ?> <span class="text-muted">Team Member</span></h1>
        </header>

        <form id="team-form" method="POST" action="<?= BASE ?>actions/save-team-member.php" enctype="multipart/form-data" class="space-y-8 form-reveal" style="animation-delay: 0.1s">
            <?php if ($id): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left: Profile Photo -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-background p-8 rounded-[2.5rem] shadow-sm border border-sand/40">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-6 text-center">Profile Photo</label>
                        <div class="aspect-square rounded-[2rem] overflow-hidden bg-surface mb-6 border border-sand/30 shadow-inner">
                            <img id="img-preview" src="<?= ($member && $member['image_path']) ? imgUrl($member['image_path']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=400' ?>" 
                                 class="w-full h-full object-cover">
                        </div>
                        <input type="file" name="image" id="image-input" 
                               class="block w-full text-[10px] text-sand file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-accent hover:file:bg-sand transition-all">
                    </div>

                    <div class="bg-background p-8 rounded-[2.5rem] shadow-sm border border-sand/40">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-6">Display Order</label>
                        <input type="number" name="display_order" value="<?= $member['display_order'] ?? 0 ?>"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all shadow-inner">
                    </div>
                </div>

                <!-- Right: Detailed Info -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-6">
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-foreground pb-4 border-b border-sand/30 mb-2">Fundamental Identity</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Full Name</label>
                                <input type="text" name="name" required value="<?= e($member['name'] ?? '') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm shadow-inner transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Professional Focus</label>
                                <input type="text" name="designation" placeholder="e.g. Lead Architect" value="<?= e($member['designation'] ?? '') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm shadow-inner transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Email Identifier</label>
                                <input type="email" name="email" value="<?= e($member['email'] ?? '') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm shadow-inner transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Communication Line</label>
                                <input type="text" name="phone" value="<?= e($member['phone'] ?? '') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm shadow-inner transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1">Narrative / Bio</label>
                            <textarea name="bio" id="bio_input" class="hidden"><?= htmlspecialchars($member['bio'] ?? '') ?></textarea>
                            
                            <div class="editor-wrapper">
                                <div class="editor-toolbar" data-target="bio_editor">
                                    <button type="button" class="tb-btn" data-tip="Bold" onclick="execFmt('bold','bio_editor')"><svg viewBox="0 0 24 24" fill="none"><path d="M6 4h8a4 4 0 0 1 0 8H6z" stroke-linecap="round"/><path d="M6 12h9a4 4 0 0 1 0 8H6z" stroke-linecap="round"/></svg></button>
                                    <button type="button" class="tb-btn" data-tip="Italic" onclick="execFmt('italic','bio_editor')"><svg viewBox="0 0 24 24" fill="none"><line x1="15" y1="4" x2="9" y2="20" stroke-linecap="round"/></svg></button>
                                    <div class="tb-sep"></div>
                                    <button type="button" class="tb-btn" data-tip="List" onclick="execFmt('insertUnorderedList','bio_editor')"><svg viewBox="0 0 24 24" fill="none"><line x1="9" y1="6" x2="20" y2="6" stroke-linecap="round"/><circle cx="4" cy="6" r="1" fill="currentColor"/></svg></button>
                                    <button type="button" class="tb-btn" data-tip="Link" onclick="insertLink('bio_editor')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-linecap="round"/></svg></button>
                                    <div class="tb-sep"></div>
                                    <button type="button" class="tb-btn" data-tip="Clear" onclick="clearF( 'bio_editor')"><svg viewBox="0 0 24 24" fill="none"><path d="M6 19l7-14" stroke-linecap="round"/></svg></button>
                                </div>
                                <div class="editor-content" id="bio_editor" contenteditable="true" data-placeholder="Tell their story..." data-input="bio_input"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="bg-background p-10 rounded-[2.5rem] shadow-sm border border-sand/40 space-y-6">
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-foreground pb-4 border-b border-sand/30 mb-2">Digital Presence</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <?php 
                            $socials = [
                                'facebook_url' => 'Facebook',
                                'x_url' => 'X (Twitter)',
                                'instagram_url' => 'Instagram',
                                'whatsapp_number' => 'WhatsApp (Digits)',
                                'threads_url' => 'Threads',
                                'socialvynk_url' => 'Socialvynk'
                            ];
                            foreach ($socials as $fld => $lbl): ?>
                            <div>
                                <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-2 ml-1"><?= $lbl ?></label>
                                <input type="text" name="<?= $fld ?>" value="<?= e($member[$fld] ?? '') ?>"
                                       class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-[11px] font-mono shadow-inner">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full py-6 bg-foreground text-background rounded-[2rem] text-[10px] font-bold uppercase tracking-[0.3em] transform hover:-translate-y-1 transition-all shadow-xl">
                        <?= $member ? 'Update Profile' : 'Publish Member' ?>
                    </button>

                </div>
            </div>
        </form>

    </div>
</main>
<script>
function getE(id){return document.getElementById(id);}
function execFmt(cmd,eI){getE(eI).focus();document.execCommand(cmd,false,null);sync(eI);}
function insertLink(eI){const u=prompt('URL:','https://');if(u){getE(eI).focus();document.execCommand('createLink',false,u);sync(eI);}}
function clearF(eI){getE(eI).focus();document.execCommand('removeFormat');sync(eI);}
function sync(eI){
    const e = getE(eI); const input = getE(e.dataset.input);
    input.value = e.innerHTML;
}
document.addEventListener('DOMContentLoaded', () => {
    // Image Preview logic
    const imgInp = getE('image-input');
    const imgPre = getE('img-preview');
    if (imgInp && imgPre) {
        imgInp.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => imgPre.src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    const e = getE('bio_editor'); const input = getE(e.dataset.input); const raw = input.value;
    if (raw.trim().startsWith('<') || raw.includes('<p')) { e.innerHTML = raw; }
    else if (raw.trim()) { e.innerHTML = raw.split('\n').filter(l=>l.trim()).map(l=>'<p>'+l+'</p>').join(''); }
    sync('bio_editor');
    e.addEventListener('input', () => sync('bio_editor'));
    document.getElementById('team-form').addEventListener('submit', () => sync('bio_editor'));
});
</script>
</body>
</html>
