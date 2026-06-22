<?php
// FILE: includes/chat-widget.php
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = getPDO();
$settings = loadSettings($pdo);
if (($settings['ai_enabled'] ?? '0') !== '1') return; // Don't render if disabled

$chatTitle  = $settings['ai_chat_title'] ?? 'Ask Advet AI';
$welcomeMsg = $settings['ai_welcome_msg'] ?? 'How can I assist you with your architectural or property inquiries today?';
$accent     = $settings['accent_color'] ?? '#899178';
?>
<style>
/* ════════════════════════════════════════════════
   ADVET AI — KODEE-INSPIRED DESIGN SYSTEM
   ════════════════════════════════════════════════ */

#ai-chat-widget {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 9999;
    font-family: 'Outfit', sans-serif !important;
}

/* ── Launcher ────────────────────────────────── */
#chat-toggle {
    width: auto;
    height: 3.5rem;
    padding: 0 1.5rem;
    border-radius: 1.25rem;
    background: <?= $accent ?>;
    color: #FFFFFF;
    border: 1px solid rgba(255, 255, 255, 0.1);
    cursor: pointer;
    box-shadow: 0 8px 32px rgba(<?= hexToRgb($accent, true) ?>, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    white-space: nowrap;
}
.chat-toggle-inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.chat-toggle-text {
    font-weight: 700;
    font-size: 0.95rem;
    letter-spacing: 0.01em;
}
#chat-toggle:hover {
    transform: translateY(-4px) scale(1.03);
    background: <?= adjustBrightness($accent, -20) ?>;
    box-shadow: 0 12px 40px rgba(<?= hexToRgb($accent, true) ?>, 0.35);
}
#chat-toggle:active { transform: scale(0.95); }

/* ── Chat Window ─────────────────────────────── */
#chat-window {
    position: absolute;
    bottom: calc(100% + 1rem);
    right: 0;
    width: min(400px, calc(100vw - 2.5rem));
    height: min(680px, calc(100vh - 8rem));
    background: #FFFFFF;
    border-radius: 1.5rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transform-origin: bottom right;
    transition: transform 0.4s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.3s ease;
}
#chat-window.chat-window-hidden {
    display: none;
    opacity: 0;
    transform: scale(0.9) translateY(20px);
}

/* ── Header ──────────────────────────────────── */
#chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: #FFFFFF;
    border-bottom: 1px solid #F1F4F8;
    z-index: 10;
}
.chat-header-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1A1D21;
    margin: 0;
}
.chat-header-actions {
    display: flex;
    gap: 0.25rem;
}
.chat-action-btn {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    border: none;
    background: transparent;
    color: #4B5563;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
.chat-action-btn:hover {
    background: #F3F4F6;
    color: #111827;
}

/* ── Messages Container ──────────────────────── */
#messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    background: #FFFFFF;
    scroll-behavior: smooth;
}
#messages-container::-webkit-scrollbar { width: 4px; }
#messages-container::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }

/* ── Welcome Screen ──────────────────────────── */
#chat-welcome-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem 1rem;
    flex: 1;
}
.welcome-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, <?= $accent ?>, <?= adjustBrightness($accent, -20) ?>);
    border-radius: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 25px rgba(<?= hexToRgb($accent, true) ?>, 0.3);
}
.welcome-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1A1D21;
    margin-bottom: 0.5rem;
}
.welcome-subtitle {
    font-size: 0.95rem;
    color: #4B5563;
    margin-bottom: 2rem;
}
.welcome-suggestions {
    width: 100%;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2.5rem;
}
.welcome-suggest-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #FFFFFF;
    border: 1px solid #F1F4F8;
    border-radius: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    color: #1A1D21;
    font-weight: 600;
    font-size: 0.9rem;
}
.welcome-suggest-item:hover {
    background: #F9FAFB;
    border-color: #E5E7EB;
    transform: translateX(4px);
}
.welcome-suggest-item svg { color: #4B5563; }

.welcome-pills {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}
.pill-btn {
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    border: 1px solid #F1F4F8;
    background: transparent;
    font-size: 0.85rem;
    font-weight: 600;
    color: #4B5563;
    cursor: pointer;
    transition: all 0.2s;
}
.pill-btn.active {
    background: rgba(<?= hexToRgb($accent, true) ?>, 0.08);
    color: <?= $accent ?>;
    border-color: rgba(<?= hexToRgb($accent, true) ?>, 0.2);
}
.pill-btn:hover:not(.active) { background: #F9FAFB; }

/* ── Message Styles ──────────────────────────── */
.msg-row { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }
.msg-row-bot { align-items: flex-start; }
.msg-row-user { align-items: flex-end; }

.bot-identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.bot-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bot-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1A1D21;
}

.msg-bubble {
    max-width: 85%;
    padding: 0.875rem 1.125rem;
    font-size: 0.9375rem;
    line-height: 1.5;
    border-radius: 1.25rem;
}
.msg-bubble-bot {
    background: #FFFFFF;
    color: #2A2E33;
    border-radius: 0.25rem 1.25rem 1.25rem 1.25rem;
    border: 1px solid transparent;
}
.msg-bubble-user {
    background: #F0F2F5;
    color: #1A1D21;
    border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
}

.bot-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.bot-action-icon {
    width: 1.75rem;
    height: 1.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9CA3AF;
    cursor: pointer;
    transition: color 0.2s;
}
.bot-action-icon:hover { color: <?= $accent ?>; }

/* ── Thinking State (Multi-step) ─────────────── */
.thinking-steps {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.5rem;
    padding-left: 0.5rem;
}
.step-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #6B7280;
    opacity: 0.6;
    transition: opacity 0.3s;
}
.step-item.active { opacity: 1; color: #111827; }
.step-item.done { opacity: 1; color: #111827; }
.step-icon {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.step-spinner {
    width: 12px;
    height: 12px;
    border: 2px solid <?= $accent ?>;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Mini Suggestions ────────────────────────── */
#chat-suggestions-mini {
    padding: 1rem 1.5rem;
    border-top: 1px solid #F1F4F8;
    background: #FFFFFF;
}
.suggestions-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #9CA3AF;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}
.suggestions-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.mini-suggest-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #1A1D21;
    cursor: pointer;
    transition: color 0.2s;
}
.mini-suggest-item:hover { color: <?= $accent ?>; }
.mini-suggest-item svg { color: #9CA3AF; flex-shrink: 0; }

/* ── Footer & Input ──────────────────────────── */
#chat-footer {
    padding: 1rem 1.5rem 1.25rem;
    background: #FFFFFF;
    border-top: 1px solid #F1F4F8;
}
.chat-input-container {
    display: flex;
    gap: 0.75rem;
    background: #FFFFFF;
    border: 1.5px solid #E1E4E8;
    border-radius: 1.25rem;
    padding: 0.75rem 1.25rem;
    transition: all 0.2s;
    align-items: flex-end;
}
.chat-input-container:focus-within {
    border-color: <?= $accent ?>;
    box-shadow: 0 0 0 4px rgba(<?= hexToRgb($accent, true) ?>, 0.1);
}
#chat-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 0.9375rem;
    color: #1A1D21;
    outline: none;
    resize: none;
    max-height: 120px;
    font-family: inherit;
    padding: 0;
    line-height: 1.5;
}
#send-btn {
    background: #F1F4F8;
    color: #9CA3AF;
    border: none;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
#send-btn:hover { background: <?= $accent ?>; color: #FFFFFF; }
#send-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.chat-disclaimer {
    margin-top: 0.75rem;
    font-size: 0.75rem;
    color: #9CA3AF;
    text-align: center;
}

/* ── Custom Modal ────────────────────────────── */
.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(2px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    z-index: 100;
    animation: fadeIn 0.2s ease;
}
.modal-overlay.active { display: flex; }

.modal-content {
    background: #FFFFFF;
    border-radius: 1.5rem;
    width: 100%;
    max-width: 340px;
    padding: 2.5rem 1.5rem 2rem;
    position: relative;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    transform: scale(0.9);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-overlay.active .modal-content { transform: scale(1); }

.modal-x-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #4B5563;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}
.modal-x-btn:hover { background: #F3F4F6; color: #111827; }

.modal-body { text-align: center; margin-bottom: 2rem; }
.modal-title { font-size: 1.25rem; font-weight: 800; color: #111827; margin-bottom: 0.75rem; }
.modal-text { font-size: 0.95rem; color: #4B5563; line-height: 1.5; }

.modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
}

.btn-text {
    background: transparent;
    border: none;
    color: <?= $accent ?>;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    padding: 0.75rem 1rem;
    transition: opacity 0.2s;
}
.btn-text:hover { opacity: 0.8; }

.btn-solid {
    background: <?= $accent ?>;
    color: #FFFFFF;
    border: none;
    border-radius: 0.8rem;
    padding: 0.75rem 1.5rem;
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: transform 0.2s, background 0.2s;
}
.btn-solid:hover { background: <?= adjustBrightness($accent, -20) ?>; }
.btn-solid:active { transform: scale(0.95); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* ── Animation ───────────────────────────────── */
.msg-enter {
    animation: fadeUp 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 480px) {
    #ai-chat-widget { bottom: 1rem; right: 1rem; }
    #chat-window {
        width: calc(100vw - 2rem);
        height: calc(100vh - 8rem);
        bottom: calc(100% + 1rem);
    }
}
</style>

<!-- ░░ Advet AI — Kodee-Inspired Premium Chat Widget ░░ -->
<div id="ai-chat-widget" style="font-family:'Outfit',sans-serif!important;">


    <!-- ── Launcher Bubble ── -->
    <button id="chat-toggle" aria-label="Open Advet AI Chat">
        <span id="toggle-icon-chat" class="chat-toggle-inner">
            <span class="chat-toggle-icon-wrap">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.4" d="M17.3702 16.2713L20.465 15.2776C21.5834 14.9184 21.8652 13.4928 20.9647 12.749L18.5414 10.7472M17.3702 16.2713L15.6246 16.8319C14.8533 17.0796 14.1247 17.441 13.464 17.9038L12.1229 18.8432M17.3702 16.2713L17.4325 14.7768C17.4671 13.9453 17.636 13.1245 17.9329 12.345L18.5414 10.7472M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432M12.1229 18.8432L9.43738 20.7242C8.50033 21.3805 7.19058 20.7997 7.07263 19.6754L6.74168 16.5208M12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208M6.74168 16.5208L3.61989 15.7141C2.48271 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584M6.74168 16.5208L6.43349 13.5832L5.2878 11.0584M5.2878 11.0584L4.00706 8.23589C3.53226 7.18955 4.40146 6.03466 5.55954 6.17312L8.85203 6.56678M5.2878 11.0584L6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678M8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42828M8.85203 6.56678L10.7141 6.78942C11.5126 6.88489 12.3213 6.86164 13.1127 6.72045L14.7505 6.42828M14.7505 6.42828L17.9828 5.85166C19.1288 5.64722 20.0649 6.74666 19.657 7.81789L18.5414 10.7472M14.7505 6.42828L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208M6.74168 16.5208L3.61989 15.7141C2.48272 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584M6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42829L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="chat-toggle-text">Ask Advet</span>
        </span>
        <span id="toggle-icon-close" class="chat-toggle-inner chat-toggle-close" style="display:none;">
            <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </span>
    </button>

    <!-- ── Chat Window ── -->
    <div id="chat-window" class="chat-window-hidden">

        <!-- Header -->
        <div id="chat-header">
            <div class="chat-header-title-wrap">
                <h3 class="chat-header-title"><?= e($chatTitle) ?></h3>
            </div>
            <div class="chat-header-actions">
                <button id="btn-download-chat" class="chat-action-btn" aria-label="Download Transcript" title="Download Transcript">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M20.4999 10.5V10C20.4999 6.22876 20.4999 4.34315 19.3284 3.17157C18.1568 2 16.2712 2 12.4999 2H11.5C7.72883 2 5.84323 2 4.67166 3.17156C3.50009 4.34312 3.50007 6.22872 3.50004 9.99993L3.5 14.5C3.49997 17.7874 3.49996 19.4312 4.40788 20.5375C4.57412 20.7401 4.75986 20.9258 4.96242 21.0921C6.06877 22 7.71249 22 10.9999 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path opacity="0.4" d="M7.5 7H16.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path opacity="0.4" d="M7.5 12H13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M20.5 20L20.5 17C20.5 15.5706 19.1569 14 17.5 14C15.8431 14 14.5 15.5706 14.5 17L14.5 20.5C14.5 21.3284 15.1716 22 16 22C16.8284 22 17.5 21.3284 17.5 20.5V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button id="btn-new-chat" class="chat-action-btn" aria-label="New Chat" title="New Chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16.4249 4.60509L17.4149 3.6151C18.2351 2.79497 19.5648 2.79497 20.3849 3.6151C21.205 4.43524 21.205 5.76493 20.3849 6.58507L19.3949 7.57506M16.4249 4.60509L9.76558 11.2644C9.25807 11.772 8.89804 12.4078 8.72397 13.1041L8 16L10.8959 15.276C11.5922 15.102 12.228 14.7419 12.7356 14.2344L19.3949 7.57506M16.4249 4.60509L19.3949 7.57506" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <path opacity="0.4" d="M18.9999 13.5C18.9999 16.7875 18.9999 18.4312 18.092 19.5376C17.9258 19.7401 17.7401 19.9258 17.5375 20.092C16.4312 21 14.7874 21 11.4999 21H11C7.22876 21 5.34316 21 4.17159 19.8284C3.00003 18.6569 3 16.7712 3 13V12.5C3 9.21252 3 7.56879 3.90794 6.46244C4.07417 6.2599 4.2599 6.07417 4.46244 5.90794C5.56879 5 7.21252 5 10.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button id="chat-close-btn" class="chat-action-btn" aria-label="Collapse">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div id="messages-container">
            <!-- Welcome Screen (Initial State) -->
            <div id="chat-welcome-screen">
                <div class="welcome-logo">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M17.3702 16.2713L20.465 15.2776C21.5834 14.9184 21.8652 13.4928 20.9647 12.749L18.5414 10.7472M17.3702 16.2713L15.6246 16.8319C14.8533 17.0796 14.1247 17.441 13.464 17.9038L12.1229 18.8432M17.3702 16.2713L17.4325 14.7768C17.4671 13.9453 17.636 13.1245 17.9329 12.3449L18.5414 10.7472M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432M12.1229 18.8432L9.43738 20.7242C8.50033 21.3805 7.19058 20.7997 7.07263 19.6754L6.74168 16.5208M12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208M6.74168 16.5208L3.61989 15.7141C2.48271 15.4203 2.11569 14.0148 2.96854 13.22L5.2878 11.0583M6.74168 16.5208L6.43349 13.5832L5.2878 11.0583M5.2878 11.0583L4.00706 8.23588C3.53226 7.18954 4.40146 6.03465 5.55954 6.17311L8.85203 6.56677M5.2878 11.0583L6.58864 9.84594C7.1925 9.28312 7.70325 8.63125 8.10245 7.91384L8.85203 6.56677M8.85203 6.56677L10.4099 3.76715C10.9595 2.77949 12.3882 2.73673 12.998 3.6897L14.7505 6.42827M8.85203 6.56677L10.7141 6.78941C11.5126 6.88488 12.3213 6.86163 13.1127 6.72044L14.7505 6.42827M14.7505 6.42827L17.9828 5.85165C19.1288 5.64721 20.0649 6.74665 19.657 7.81788L18.5414 10.7472M14.7505 6.42827L15.6961 7.90605C16.1377 8.59605 16.6858 9.21444 17.3209 9.73904L18.5414 10.7472" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208L3.61989 15.7141C2.48272 15.4203 2.11569 14.0148 2.96854 13.22L5.2878 11.0583L6.58864 9.84594C7.1925 9.28312 7.70325 8.63125 8.10245 7.91384L8.85203 6.56677L10.4099 3.76715C10.9595 2.77949 12.3882 2.73673 12.998 3.6897L14.7505 6.42828L15.6961 7.90605C16.1377 8.59605 16.6858 9.21444 17.3209 9.73904L18.5414 10.7472" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="welcome-title">Hello 👋</h2>
                <p class="welcome-subtitle">How can I help you today?</p>
                
                <div class="welcome-suggestions">
                    <button class="welcome-suggest-item" data-prompt="Show me available properties">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                        <span>Show me available properties</span>
                    </button>
                    <button class="welcome-suggest-item" data-prompt="What are your price ranges?">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                        <span>What are your price ranges?</span>
                    </button>
                    <button class="welcome-suggest-item" data-prompt="Schedule a site visit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                        <span>Schedule a site visit</span>
                    </button>
                </div>

                <div class="welcome-pills">
                    <button class="pill-btn active">Products</button>
                    <button class="pill-btn">Pricing</button>
                    <button class="pill-btn">Support</button>
                </div>
            </div>
        </div>

        <!-- Suggestions Section (Conditional) -->
        <div id="chat-suggestions-mini" style="display:none;">
            <div class="suggestions-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Suggestions
            </div>
            <div class="suggestions-list"></div>
        </div>

        <!-- Input Footer -->
        <div id="chat-footer">
            <form id="ai-chat-form" autocomplete="off">
                <div class="chat-input-container">
                    <textarea id="chat-input" placeholder="Ask Advet anything..." rows="1"></textarea>
                    <div class="chat-input-actions">
                        <button type="submit" id="send-btn" aria-label="Send message">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M12 19V5M5 12l7-7 7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
            <p class="chat-disclaimer">Advet can make mistakes. Double-check replies.</p>
        </div>

        <!-- Custom Clear Chat Modal -->
        <div id="clear-chat-modal" class="modal-overlay">
            <div class="modal-content">
                <button id="modal-close-x" class="modal-x-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
                <div class="modal-body">
                    <h4 class="modal-title">Clear chat</h4>
                    <p class="modal-text">After clearing history you won’t be able to access previous chats.</p>
                </div>
                <div class="modal-footer">
                    <button id="btn-cancel-clear" class="btn-text">Cancel</button>
                    <button id="btn-confirm-clear" class="btn-solid">Clear chat</button>
                </div>
            </div>
        </div>
    </div><!-- /chat-window -->
</div><!-- /ai-chat-widget -->
</div><!-- /ai-chat-widget -->

<script>
    window.BASE_URL = "<?= BASE ?>";
    window.CHAT_ACCENT = "<?= $accent ?>";
    const ADVET_AI_PROXY = "<?= BASE ?>actions/chat-proxy.php";
</script>
<script src="<?= BASE ?>assets/js/ask-advet.js?v=<?= time() ?>"></script>

