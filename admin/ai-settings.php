<?php
// FILE: admin/ai-settings.php
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
$s = $settings;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Settings | Advet Studio</title>
    <script src="<?= BASE ?>assets/js/tailwind.min.js"></script>
    <?= getAdminTailwindConfig($settings) ?>
    <link href="<?= BASE ?>assets/css/fonts.css" rel="stylesheet">
    <style>body{-webkit-font-smoothing:antialiased;} .form-reveal{opacity:0;transform:translateY(20px);animation:fadeIn .8s cubic-bezier(.2,.8,.2,1) forwards}@keyframes fadeIn{to{opacity:1;transform:none}} input:focus,textarea:focus,select:focus{outline:none;border-color:#899178;}</style>
</head>
<body class="font-sans font-light min-h-screen bg-[#F4F1ED] flex" style="background-color: <?= $settings['theme_surface'] ?? '#F4F1ED' ?>">
<?php require_once '../includes/flash.php'; ?>
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<main class="flex-grow p-8 sm:p-12 overflow-y-auto">
    <div class="max-w-3xl mx-auto">
        <div class="mb-12 form-reveal">
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-accent mb-4">Advet Assistant</p>
            <h1 class="text-4xl font-serif font-light italic">Ask Advet <span class="text-muted">AI Configuration</span></h1>
        </div>

        <form method="POST" action="<?= BASE ?>actions/save-settings.php" class="space-y-10 form-reveal" style="animation-delay:.1s">
            <input type="hidden" name="redirect" value="admin/ai-settings.php">

            <!-- AI Status -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Status & Visibility</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">Chatbot Enabled</p>
                        <p class="text-xs text-muted mt-1">Enable or disable the Ask Advet AI chatbot across the platform.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="settings[ai_enabled]" value="0">
                        <input type="checkbox" name="settings[ai_enabled]" value="1"
                               <?= ($s['ai_enabled'] ?? '0') === '1' ? 'checked' : '' ?>
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-sand peer-focus:outline-none rounded-full peer peer-checked:bg-accent transition-all
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                    </label>
                </div>
            </div>

            <!-- Provider Selection -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">AI Engine Provider</h2>
                <div class="space-y-8">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Provider</label>
                        <select name="settings[ai_provider]" class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm appearance-none transition-all">
                            <option value="gemini" <?= ($s['ai_provider'] ?? 'gemini') === 'gemini' ? 'selected' : '' ?>>Google Gemini (Pro/Flash)</option>
                            <option value="openai" <?= ($s['ai_provider'] ?? '') === 'openai' ? 'selected' : '' ?>>OpenAI (GPT-4o/mini)</option>
                            <option value="openrouter" <?= ($s['ai_provider'] ?? '') === 'openrouter' ? 'selected' : '' ?>>OpenRouter (DeepSeek/Claude/etc)</option>
                            <option value="nvidia" <?= ($s['ai_provider'] ?? '') === 'nvidia' ? 'selected' : '' ?>>NVIDIA NIM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">API Key</label>
                        <input type="password" name="settings[ai_api_key]" value="<?= e($s['ai_api_key'] ?? '') ?>"
                               placeholder="sk-..."
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all">
                        <p class="text-[9px] text-muted mt-2 ml-1">Securely stored. Required for the chatbot to function.</p>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Model ID</label>
                        <input type="text" name="settings[ai_model]" value="<?= e($s['ai_model'] ?? 'gemini-1.5-flash') ?>"
                               placeholder="e.g. gemini-1.5-flash"
                               class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:border-accent">
                    </div>
                </div>
            </div>

            <!-- Bot Personality -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Chat Experience</h2>
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Chat Window Title</label>
                            <input type="text" name="settings[ai_chat_title]" value="<?= e($s['ai_chat_title'] ?? 'Ask Advet AI') ?>"
                                   class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:border-accent">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">Welcome Message</label>
                            <input type="text" name="settings[ai_welcome_msg]" value="<?= e($s['ai_welcome_msg'] ?? 'How can I assist you with your architectural or property inquiries today?') ?>"
                                   class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all focus:border-accent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-muted mb-4 ml-1">System Prompt / Instructions</label>
                        <textarea name="settings[ai_system_instruction]" rows="6"
                                  class="w-full px-6 py-4 bg-surface/30 border border-sand/30 rounded-2xl text-sm transition-all resize-none focus:border-accent leading-relaxed"><?= e($s['ai_system_instruction'] ?? '') ?></textarea>
                        <p class="text-[9px] text-muted mt-2 ml-1">Define the AI's identity, knowledge, and behavior. Use clear instructions.</p>
                    </div>
                </div>
            </div>

            <!-- Database Awareness (Context) -->
            <div class="bg-background p-8 md:p-12 rounded-[2.5rem] shadow-sm border border-sand/40">
                <h2 class="text-xs uppercase tracking-[0.3em] font-bold text-accent mb-10 border-b border-sand pb-4">Knowledge & Context</h2>
                <div class="p-6 bg-surface/40 rounded-2xl border border-sand/20">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium">Automatic Context</p>
                    </div>
                    <p class="text-xs text-muted leading-relaxed">
                        The AI automatically receives context from the <b>Properties</b>, <b>FAQ</b>, and <b>Our Stories</b> tables to provide accurate answers about the studio's portfolio and philosophy.
                    </p>
                </div>
            </div>

            <!-- Save -->
            <div class="flex gap-4 pt-4">
                <button type="submit"
                        class="flex-1 py-5 bg-foreground text-background rounded-2xl text-xs font-bold uppercase tracking-[0.2em] transform hover:-translate-y-1 transition-all shadow-xl">
                    Update AI Settings
                </button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
