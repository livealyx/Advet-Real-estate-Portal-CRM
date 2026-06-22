<?php
/**
 * Advet Modular Editor Component
 * 
 * @param string $editor_name  The name of the textarea input
 * @param string $editor_value The initial content of the editor
 */

// Define ROOT_PATH if not defined (fallback)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

// Load Assets Once per page
if (!defined('EDITOR_ASSETS_LOADED')) {
    ?>
    <link rel="stylesheet" href="<?= BASE ?>components/editor/editor.css">
    <script src="<?= BASE ?>components/editor/editor.js"></script>
    <?php
    define('EDITOR_ASSETS_LOADED', true);
}

// Generate unique ID for this instance
$inst_id = "ed_" . bin2hex(random_bytes(4));
$editor_name = $editor_name ?? 'description';
$editor_value = $editor_value ?? '';
?>

<div class="editor-wrapper" id="wrapper_<?= $inst_id ?>">
    <textarea name="<?= $editor_name ?>" id="textarea_<?= $inst_id ?>" class="editor-input hidden"><?= htmlspecialchars($editor_value) ?></textarea>
    
    <div class="editor-toolbar" id="toolbar_<?= $inst_id ?>" data-target="<?= $inst_id ?>">
        <!-- Heading Dropdown -->
        <div class="relative group mr-2">
            <button type="button" class="tb-btn !w-auto px-4 gap-3 bg-black/5 hover:bg-accent hover:text-white transition-all rounded-xl border border-black/5">
                <span id="lbl_<?= $inst_id ?>" class="text-[10px] font-bold uppercase tracking-widest">Paragraph</span>
                <svg class="w-3 h-3 opacity-40 group-hover:opacity-100 transition-opacity" viewBox="0 0 24 24" stroke-width="3"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="absolute top-full left-0 mt-3 bg-white/95 backdrop-blur-2xl border border-black/5 rounded-[1.5rem] shadow-2xl py-3 opacity-0 translate-y-4 pointer-events-none transition-all z-[100] min-w-[160px] group-focus-within:opacity-100 group-focus-within:translate-y-0 group-focus-within:pointer-events-auto">
                <div class="px-6 py-3 hover:bg-accent/10 hover:text-accent cursor-pointer text-[9px] font-bold uppercase tracking-[0.2em] transition-colors border-l-2 border-transparent hover:border-accent" onmousedown="event.preventDefault()" onclick="AdvetEditor.setBlock('p', 'Paragraph', '<?= $inst_id ?>', 'lbl_<?= $inst_id ?>')">Paragraph</div>
                <div class="px-6 py-3 hover:bg-accent/10 hover:text-accent cursor-pointer text-[9px] font-bold uppercase tracking-[0.2em] transition-colors border-l-2 border-transparent hover:border-accent" onmousedown="event.preventDefault()" onclick="AdvetEditor.setBlock('h1', 'Heading 1', '<?= $inst_id ?>', 'lbl_<?= $inst_id ?>')">Heading 1</div>
                <div class="px-6 py-3 hover:bg-accent/10 hover:text-accent cursor-pointer text-[9px] font-bold uppercase tracking-[0.2em] transition-colors border-l-2 border-transparent hover:border-accent" onmousedown="event.preventDefault()" onclick="AdvetEditor.setBlock('h2', 'Heading 2', '<?= $inst_id ?>', 'lbl_<?= $inst_id ?>')">Heading 2</div>
                <div class="px-6 py-3 hover:bg-accent/10 hover:text-accent cursor-pointer text-[9px] font-bold uppercase tracking-[0.2em] transition-colors border-l-2 border-transparent hover:border-accent" onmousedown="event.preventDefault()" onclick="AdvetEditor.setBlock('h3', 'Heading 3', '<?= $inst_id ?>', 'lbl_<?= $inst_id ?>')">Heading 3</div>
                <div class="px-6 py-3 hover:bg-accent/10 hover:text-accent cursor-pointer text-[9px] font-bold uppercase tracking-[0.2em] transition-colors border-l-2 border-transparent hover:border-accent" onmousedown="event.preventDefault()" onclick="AdvetEditor.setBlock('pre', 'Code Block', '<?= $inst_id ?>', 'lbl_<?= $inst_id ?>')">Code Block</div>
            </div>
        </div>

        <div class="tb-sep"></div>

        <button type="button" class="tb-btn" data-tip="Bold" onclick="AdvetEditor.exec('bold', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><path d="M6 4h8a4 4 0 0 1 0 8H6z" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 12h9a4 4 0 0 1 0 8H6z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Italic" onclick="AdvetEditor.exec('italic', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="19" y1="4" x2="10" y2="4" stroke-linecap="round"/><line x1="14" y1="20" x2="5" y2="20" stroke-linecap="round"/><line x1="15" y1="4" x2="9" y2="20" stroke-linecap="round"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Underline" onclick="AdvetEditor.exec('underline', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><path d="M6 3v7a6 6 0 0 0 12 0V3" stroke-linecap="round"/><line x1="4" y1="21" x2="20" y2="21" stroke-linecap="round"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Strikethrough" onclick="AdvetEditor.exec('strikeThrough', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><path d="M17.3 6.3a4 4 0 0 0-6.6 1.7" stroke-linecap="round"/><path d="M6.7 17.7a4 4 0 0 0 6.6-1.7" stroke-linecap="round"/><line x1="4" y1="12" x2="20" y2="12" stroke-linecap="round"/></svg>
        </button>

        <div class="tb-sep"></div>

        <button type="button" class="tb-btn" data-tip="Align Left" onclick="AdvetEditor.exec('justifyLeft', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="17" y1="10" x2="3" y2="10" stroke-linecap="round"/><line x1="21" y1="6" x2="3" y2="6" stroke-linecap="round"/><line x1="21" y1="14" x2="3" y2="14" stroke-linecap="round"/><line x1="17" y1="18" x2="3" y2="18" stroke-linecap="round"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Align Center" onclick="AdvetEditor.exec('justifyCenter', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="18" y1="10" x2="6" y2="10" stroke-linecap="round"/><line x1="21" y1="6" x2="3" y2="6" stroke-linecap="round"/><line x1="21" y1="14" x2="3" y2="14" stroke-linecap="round"/><line x1="18" y1="18" x2="6" y2="18" stroke-linecap="round"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Align Right" onclick="AdvetEditor.exec('justifyRight', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="21" y1="10" x2="7" y2="10" stroke-linecap="round"/><line x1="21" y1="6" x2="3" y2="6" stroke-linecap="round"/><line x1="21" y1="14" x2="3" y2="14" stroke-linecap="round"/><line x1="21" y1="18" x2="7" y2="18" stroke-linecap="round"/></svg>
        </button>

        <div class="tb-sep"></div>

        <button type="button" class="tb-btn" data-tip="Bullet List" onclick="AdvetEditor.exec('insertUnorderedList', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6" stroke-linecap="round"/><line x1="8" y1="12" x2="21" y2="12" stroke-linecap="round"/><line x1="8" y1="18" x2="21" y2="18" stroke-linecap="round"/><line x1="3" y1="6" x2="3.01" y2="6" stroke-linecap="round" stroke-width="4"/><line x1="3" y1="12" x2="3.01" y2="12" stroke-linecap="round" stroke-width="4"/><line x1="3" y1="18" x2="3.01" y2="18" stroke-linecap="round" stroke-width="4"/></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Ordered List" onclick="AdvetEditor.exec('insertOrderedList', '<?= $inst_id ?>')">
            <svg viewBox="0 0 24 24"><line x1="10" y1="6" x2="21" y2="6" stroke-linecap="round"/><line x1="10" y1="12" x2="21" y2="12" stroke-linecap="round"/><line x1="10" y1="18" x2="21" y2="18" stroke-linecap="round"/><text x="2" y="8" font-size="7" fill="currentColor" stroke="none" font-family="Inter,sans-serif">1.</text><text x="2" y="14" font-size="7" fill="currentColor" stroke="none" font-family="Inter,sans-serif">2.</text><text x="2" y="14" font-size="7" fill="currentColor" stroke="none" font-family="Inter,sans-serif">2.</text><text x="2" y="20" font-size="7" fill="currentColor" stroke="none" font-family="Inter,sans-serif">3.</text></svg>
        </button>
        <button type="button" class="tb-btn" data-tip="Blockquote" onclick="AdvetEditor.exec('formatBlock', '<?= $inst_id ?>', 'blockquote')">
            <svg viewBox="0 0 24 24"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <div class="tb-sep"></div>

        <!-- Hyperlink -->
        <div class="relative group">
            <button type="button" class="tb-btn" data-tip="Insert Link">
                <svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="absolute top-full left-0 mt-3 bg-white/95 backdrop-blur-2xl border border-black/5 rounded-[1.5rem] shadow-2xl p-5 opacity-0 translate-y-4 pointer-events-none transition-all z-[100] min-w-[280px] group-focus-within:opacity-100 group-focus-within:translate-y-0 group-focus-within:pointer-events-auto">
                <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-4">Insert Hyperlink</p>
                <div class="flex flex-col gap-4">
                    <input type="text" id="url_<?= $inst_id ?>" placeholder="https://example.com" class="form-input !py-2 !px-4 !text-[11px] !rounded-xl" onkeydown="if(event.key==='Enter') AdvetEditor.applyLink('<?= $inst_id ?>', 'url_<?= $inst_id ?>')">
                    <button type="button" class="w-full bg-accent text-white py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-accent/90 transition-colors" onclick="AdvetEditor.applyLink('<?= $inst_id ?>', 'url_<?= $inst_id ?>')">Apply Link</button>
                </div>
            </div>
        </div>

        <!-- Color Dropdown -->
        <div class="relative group">
            <button type="button" class="tb-btn" data-tip="Text Color">
                <svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="m6 16 6-12 6 12"/><path d="M8 12h8"/></svg>
            </button>
            <div class="absolute top-full left-0 mt-3 bg-white/95 backdrop-blur-2xl border border-black/5 rounded-[1.5rem] shadow-2xl p-5 opacity-0 translate-y-4 pointer-events-none transition-all z-[100] min-w-[220px] group-focus-within:opacity-100 group-focus-within:translate-y-0 group-focus-within:pointer-events-auto">
                <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-4">Select Palette</p>
                <div class="grid grid-cols-5 gap-3">
                    <?php 
                    $palette = ['#000000', '#2A2925', '#6D685C', '#899178', '#D4AF37', '#C41E3A', '#0047AB', '#228B22', '#702963', '#FF8C00'];
                    foreach($palette as $clr): ?>
                    <div class="w-7 h-7 rounded-full cursor-pointer border border-black/5 hover:scale-110 transition-transform shadow-sm" 
                         style="background: <?= $clr ?>" 
                         onmousedown="event.preventDefault()"
                         onclick="AdvetEditor.setColor('<?= $clr ?>', '<?= $inst_id ?>')"></div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-5 pt-4 border-t border-black/5">
                    <p class="text-[9px] font-bold uppercase tracking-widest text-muted mb-3">Custom Spectrum</p>
                    <div class="relative h-8 rounded-xl border border-black/5 overflow-hidden shadow-inner cursor-pointer group/spectrum transition-all hover:shadow-lg">
                        <div class="absolute inset-0" style="background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%)"></div>
                        <input type="color" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" 
                               oninput="AdvetEditor.setColor(this.value, '<?= $inst_id ?>', true)" 
                               onchange="AdvetEditor.setColor(this.value, '<?= $inst_id ?>', false)">
                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/spectrum:opacity-100 transition-opacity bg-black/5 pointer-events-none">
                            <span class="text-[8px] font-bold uppercase tracking-[0.2em] text-white drop-shadow-md">Open Spectrum Picker</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tb-sep"></div>

        <button type="button" class="tb-btn" data-tip="Clear Format" onclick="AdvetEditor.exec('removeFormat', '<?= $inst_id ?>'); AdvetEditor.exec('formatBlock', '<?= $inst_id ?>', 'p')">
            <svg viewBox="0 0 24 24"><path d="M4 7V4h16v3"/><path d="M5 20h5"/><path d="M13 4 8 20"/><path d="m15 15 5 5"/><path d="m20 15-5 5"/></svg>
        </button>

        <span class="ml-auto text-[9px] uppercase tracking-widest text-muted/50 font-medium word-count">0 words</span>
    </div>
    
    <div class="editor-content" id="<?= $inst_id ?>" contenteditable="true"></div>
</div>
