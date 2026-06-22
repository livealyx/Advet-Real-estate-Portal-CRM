<?php
// FILE: admin/theme.php
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
    <title>Theme Settings | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($s) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}} input:focus,textarea:focus,select:focus{outline:none;border-color:var(--tw-colors-accent-DEFAULT, #899178);}</style>
</head>
<body class="font-sans font-light min-h-screen flex" style="background-color: <?= $s['theme_surface'] ?? '#F4F0E6' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-3xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Design Control</p>
            <h1 class="text-4xl font-serif font-light italic">Theme <span class="text-muted">Colors</span></h1>
        </div>

        <form method="POST" action="<?= BASE ?>actions/save-theme.php" class="space-y-10 form-reveal" style="animation-delay:.1s">

            <!-- Core Colors -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="flex items-center text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">
                    <svg class="mb-0.5 inline-block w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.461-.669-.461-1.093 0-.859.697-1.594 1.562-1.594H18c2.2 0 4-1.8 4-4 0-5.5-4.5-10-10-10Z"/></svg>
                    Core Colors
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Background</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_background]" id="bgPicker"
                                   value="<?= e($s['theme_background'] ?? '#FDFCF9') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="bgHex" value="<?= e($s['theme_background'] ?? '#FDFCF9') ?>"
                                   placeholder="#FDFCF9"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('bgPicker').value=this.value">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Foreground</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_foreground]" id="fgPicker"
                                   value="<?= e($s['theme_foreground'] ?? '#2A2925') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="fgHex" value="<?= e($s['theme_foreground'] ?? '#2A2925') ?>"
                                   placeholder="#2A2925"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('fgPicker').value=this.value">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Surface</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_surface]" id="surPicker"
                                   value="<?= e($s['theme_surface'] ?? '#F4F0E6') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="surHex" value="<?= e($s['theme_surface'] ?? '#F4F0E6') ?>"
                                   placeholder="#F4F0E6"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('surPicker').value=this.value">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Muted</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_muted]" id="mutPicker"
                                   value="<?= e($s['theme_muted'] ?? '#6D685C') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="mutHex" value="<?= e($s['theme_muted'] ?? '#6D685C') ?>"
                                   placeholder="#6D685C"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('mutPicker').value=this.value">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Sand</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_sand]" id="sndPicker"
                                   value="<?= e($s['theme_sand'] ?? '#DFD8CC') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="sndHex" value="<?= e($s['theme_sand'] ?? '#DFD8CC') ?>"
                                   placeholder="#DFD8CC"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('sndPicker').value=this.value">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accent System -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="flex items-center text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">
                    <svg class="mb-0.5 inline-block w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21.5V12C12 6.477 16.477 2 22 2c0 8.019-4.73 15.111-12 18.258L12 21.5z"/><path d="M12 12c-5.523 0-10 4.477-10 10 8.019 0 15.111-4.73 18.258-12H12z"/></svg>
                    Brand Accent System
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Accent</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[accent_color]" id="accPicker"
                                   value="<?= e($s['accent_color'] ?? '#899178') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="accHex" value="<?= e($s['accent_color'] ?? '#899178') ?>"
                                   placeholder="#899178"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('accPicker').value=this.value">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-3 ml-1">Accent Dark</label>
                        <div class="flex items-center gap-4">
                            <input type="color" name="settings[theme_accent_dark]" id="accDPicker"
                                   value="<?= e($s['theme_accent_dark'] ?? '#6E755F') ?>"
                                   class="w-14 h-14 rounded-2xl border border-sand/30 cursor-pointer bg-surface/30 p-1">
                            <input type="text" id="accDHex" value="<?= e($s['theme_accent_dark'] ?? '#6E755F') ?>"
                                   placeholder="#6E755F"
                                   class="flex-1 px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all font-mono"
                                   oninput="document.getElementById('accDPicker').value=this.value">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save -->
            <div class="flex gap-4 pt-4">
                <button type="submit"
                        class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    Save Theme
                </button>
            </div>
        </form>
    </div>
</main>

<script>
const pickers = ['bg','fg','sur','mut','snd','acc','accD'];
pickers.forEach(p => {
    document.getElementById(p+'Picker').addEventListener('input', function(){
        document.getElementById(p+'Hex').value = this.value;
    });
});
</script>
</body>
</html>
