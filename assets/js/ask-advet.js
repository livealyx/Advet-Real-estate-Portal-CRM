// FILE: assets/js/ask-advet.js
// Advet AI Chat Widget — Kodee-Inspired Premium Logic

(function () {
    const chatWidget = document.getElementById('ai-chat-widget');
    if (!chatWidget) return;

    const toggleBtn         = document.getElementById('chat-toggle');
    const chatWindow        = document.getElementById('chat-window');
    const chatForm          = document.getElementById('ai-chat-form');
    const chatInput         = document.getElementById('chat-input');
    const messagesContainer = document.getElementById('messages-container');
    const sendBtn           = document.getElementById('send-btn');
    const welcomeScreen     = document.getElementById('chat-welcome-screen');
    const miniSuggestions   = document.getElementById('chat-suggestions-mini');
    const newChatBtn        = document.getElementById('btn-new-chat');
    const downloadChatBtn   = document.getElementById('btn-download-chat');

    let isOpen    = localStorage.getItem('advet_chat_open') === 'true';
    let isThinking = false;

    // ── Header Actions Logic ──────────────────────────────────────────
    const clearChatModal  = document.getElementById('clear-chat-modal');
    const btnConfirmClear = document.getElementById('btn-confirm-clear');
    const btnCancelClear  = document.getElementById('btn-cancel-clear');
    const modalCloseX     = document.getElementById('modal-close-x');

    newChatBtn?.addEventListener('click', () => {
        clearChatModal.classList.add('active');
    });

    const hideModal = () => clearChatModal.classList.remove('active');
    btnCancelClear?.addEventListener('click', hideModal);
    modalCloseX?.addEventListener('click', hideModal);

    btnConfirmClear?.addEventListener('click', async () => {
        // Clear backend session
        await fetch(ADVET_AI_PROXY + '?action=clear');

        localStorage.removeItem('advet_chat_id');
        messagesContainer.innerHTML = '';
        messagesContainer.appendChild(welcomeScreen);
        welcomeScreen.style.display = 'flex';
        miniSuggestions.style.display = 'none';
        chatInput.value = '';
        hideModal();
    });

    downloadChatBtn?.addEventListener('click', () => {
        const rows = messagesContainer.querySelectorAll('.msg-row');
        if (rows.length === 0) {
            alert("Nothing to download yet.");
            return;
        }
        let transcript = `Advet AI Chat Transcript - ${new Date().toLocaleString()}\n`;
        transcript += `========================================================\n\n`;
        
        rows.forEach(row => {
            const isUser = row.classList.contains('msg-row-user');
            const role = isUser ? 'User' : 'Advet AI';
            const text = row.querySelector('.msg-bubble')?.innerText || '';
            if (text) transcript += `[${role}]: ${text}\n\n`;
        });

        const blob = new Blob([transcript], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `advet-chat-transcript-${Date.now()}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // ── Open/Close State ─────────────────────────────────────────────
    const iconChat  = document.getElementById('toggle-icon-chat');
    const iconClose = document.getElementById('toggle-icon-close');

    function showChatIcon()  { iconChat.style.display = '';      iconClose.style.display = 'none'; }
    function showCloseIcon() { iconChat.style.display = 'none';  iconClose.style.display = ''; }

    function openChat() {
        isOpen = true;
        localStorage.setItem('advet_chat_open', 'true');
        chatWindow.classList.remove('chat-window-hidden');
        showCloseIcon();
        setTimeout(() => {
            scrollToBottom();
            chatInput.focus();
        }, 120);
    }

    function closeChat() {
        isOpen = false;
        localStorage.setItem('advet_chat_open', 'false');
        chatWindow.classList.add('chat-window-hidden');
        showChatIcon();
    }

    // Restore state on load
    if (isOpen) {
        chatWindow.classList.remove('chat-window-hidden');
        showCloseIcon();
        setTimeout(scrollToBottom, 200);
    }

    toggleBtn.addEventListener('click', () => {
        isOpen ? closeChat() : openChat();
    });

    document.getElementById('chat-close-btn')?.addEventListener('click', closeChat);

    // ── Textarea Auto-resize ──────────────────────────────────────────
    chatInput.addEventListener('input', function() {
        const accent = window.CHAT_ACCENT || '#6B38FB';
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        sendBtn.style.color = this.value.trim() ? accent : '#9CA3AF';
        // Simple hex-to-rgba for background
        const rgb = hexToRgb(accent);
        sendBtn.style.background = this.value.trim() ? `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1)` : '#F1F4F8';
    });

    // Helper for hex to rgb in JS
    function hexToRgb(hex) {
        hex = hex.replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(s => s + s).join('');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return { r, g, b };
    }

    // ── Quick Suggestion Chips ────────────────────────────────────────
    document.querySelectorAll('.welcome-suggest-item, .pill-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.dataset.prompt || btn.innerText;
            if (!prompt || isThinking) return;
            handleUserSubmit(prompt);
        });
    });

    // ── History Loader ────────────────────────────────────────────────
    async function loadHistory() {
        try {
            const lsId = localStorage.getItem('advet_chat_id');
            const resp = await fetch(ADVET_AI_PROXY + '?action=load&ls_id=' + (lsId || ''));
            const data = await resp.json();
            if (data.success) {
                if (data.ls_id) localStorage.setItem('advet_chat_id', data.ls_id);

                if (data.expired) {
                    localStorage.removeItem('advet_chat_id');
                    localStorage.setItem('advet_chat_id', data.ls_id);
                    return;
                }

                if (data.history && data.history.length > 0) {
                    welcomeScreen.style.display = 'none';
                    data.history.forEach(h => addMessage(h.content, h.role === 'user' ? 'user' : 'bot', false));
                }
            }
        } catch (e) {
            console.error('History Load Error:', e);
        }
    }
    loadHistory();

    // ── Form Submit ───────────────────────────────────────────────────
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message || isThinking) return;
        handleUserSubmit(message);
    });

    async function handleUserSubmit(message) {
        welcomeScreen.style.display = 'none';
        addMessage(message, 'user');
        chatInput.value = '';
        chatInput.style.height = 'auto';
        setThinking(true);

        try {
            const lsId = localStorage.getItem('advet_chat_id');
            const response = await fetch(ADVET_AI_PROXY, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message, ls_id: lsId })
            });
            const data = await response.json();

            if (data.success) {
                if (data.ls_id) localStorage.setItem('advet_chat_id', data.ls_id);
                addMessage(data.response, 'bot');
            } else {
                addMessage("I'm sorry, I encountered an error: " + (data.message || 'Unknown error'), 'bot');
            }
        } catch (err) {
            console.error('AI Chat Error:', err);
            addMessage("Something went wrong with the connection. Please try again.", 'bot');
        } finally {
            setThinking(false);
        }
    }

    // ── Add Message ───────────────────────────────────────────────────
    function addMessage(text, role, animate = true) {
        const isUser = role === 'user';
        const accent = window.CHAT_ACCENT || '#6B38FB';
        const row = document.createElement('div');
        row.className = `msg-row ${isUser ? 'msg-row-user' : 'msg-row-bot'}${animate ? ' msg-enter' : ''}`;

        if (isUser) {
            row.innerHTML = `<div class="msg-bubble msg-bubble-user">${escapeHtml(text)}</div>`;
        } else {
            row.innerHTML = `
                <div class="bot-identity">
                    <div class="bot-icon" style="color:${accent}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M17.3702 16.2713L20.465 15.2776C21.5834 14.9184 21.8652 13.4928 20.9647 12.749L18.5414 10.7472M17.3702 16.2713L15.6246 16.8319C14.8533 17.0796 14.1247 17.441 13.464 17.9038L12.1229 18.8432M17.3702 16.2713L17.4325 14.7768C17.4671 13.9453 17.636 13.1245 17.9329 12.345L18.5414 10.7472M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432M12.1229 18.8432L9.43738 20.7242C8.50033 21.3805 7.19058 20.7997 7.07263 19.6754L6.74168 16.5208M12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208M6.74168 16.5208L3.61989 15.7141C2.48271 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584M6.74168 16.5208L6.43349 13.5832L5.2878 11.0584M5.2878 11.0584L4.00706 8.23589C3.53226 7.18955 4.40146 6.03466 5.55954 6.17312L8.85203 6.56678M5.2878 11.0584L6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678M8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42828M8.85203 6.56678L10.7141 6.78942C11.5126 6.88489 12.3213 6.86164 13.1127 6.72045L14.7505 6.42828M14.7505 6.42828L17.9828 5.85166C19.1288 5.64722 20.0649 6.74666 19.657 7.81789L18.5414 10.7472M14.7505 6.42828L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208L3.61989 15.7141C2.48272 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584L6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42829L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="bot-name">Advet AI</span>
                </div>
                <div class="msg-bubble msg-bubble-bot">${parseMarkdown(text)}</div>
            `;
        }

        messagesContainer.appendChild(row);
        scrollToBottom();
    }

    // ── Typing Indicator (Multi-step) ──────────────────────────────────
    function setThinking(thinking) {
        isThinking = thinking;
        sendBtn.disabled = thinking;
        chatInput.disabled = thinking;
        const accent = window.CHAT_ACCENT || '#6B38FB';

        if (thinking) {
            const row = document.createElement('div');
            row.id = 'ai-thinking';
            row.className = 'msg-row msg-row-bot msg-enter';
            row.innerHTML = `
                <div class="bot-identity">
                    <div class="bot-icon" style="color:${accent}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.4" d="M17.3702 16.2713L20.465 15.2776C21.5834 14.9184 21.8652 13.4928 20.9647 12.749L18.5414 10.7472M17.3702 16.2713L15.6246 16.8319C14.8533 17.0796 14.1247 17.441 13.464 17.9038L12.1229 18.8432M17.3702 16.2713L17.4325 14.7768C17.4671 13.9453 17.636 13.1245 17.9329 12.345L18.5414 10.7472M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432M12.1229 18.8432L9.43738 20.7242C8.50033 21.3805 7.19058 20.7997 7.07263 19.6754L6.74168 16.5208M12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208M6.74168 16.5208L3.61989 15.7141C2.48271 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584M6.74168 16.5208L6.43349 13.5832L5.2878 11.0584M5.2878 11.0584L4.00706 8.23589C3.53226 7.18955 4.40146 6.03466 5.55954 6.17312L8.85203 6.56678M5.2878 11.0584L6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678M8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42828M8.85203 6.56678L10.7141 6.78942C11.5126 6.88489 12.3213 6.86164 13.1127 6.72045L14.7505 6.42828M14.7505 6.42828L17.9828 5.85166C19.1288 5.64722 20.0649 6.74666 19.657 7.81789L18.5414 10.7472M14.7505 6.42828L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M17.3702 16.2713L17.2401 19.3942C17.193 20.5254 15.9184 21.1841 14.942 20.5819L12.1229 18.8432L10.5594 17.8788C9.87224 17.455 9.12342 17.1362 8.33865 16.9335L6.74168 16.5208L3.61989 15.7141C2.48272 15.4203 2.11569 14.0149 2.96854 13.22L5.2878 11.0584L6.58864 9.84595C7.1925 9.28313 7.70325 8.63126 8.10245 7.91385L8.85203 6.56678L10.4099 3.76716C10.9595 2.7795 12.3882 2.73674 12.998 3.68971L14.7505 6.42829L15.6961 7.90606C16.1377 8.59606 16.6858 9.21445 17.3209 9.73905L18.5414 10.7472" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="bot-name">Advet AI</span>
                </div>
                <div class="thinking-steps">
                    <div class="step-item active" id="step-1">
                        <div class="step-icon"><div class="step-spinner" style="border-top-color:transparent; border-color:${accent}"></div></div>
                        <span>Analyzing your request</span>
                    </div>
                    <div class="step-item" id="step-2">
                        <div class="step-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <span>Identifying key details</span>
                    </div>
                    <div class="step-item" id="step-3">
                        <div class="step-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
                        <span>Finding relevant information</span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(row);
            scrollToBottom();

            // Simulate steps
            setTimeout(() => advanceStep('step-1', 'step-2'), 1000);
            setTimeout(() => advanceStep('step-2', 'step-3'), 2000);
        } else {
            document.getElementById('ai-thinking')?.remove();
        }
    }

    function advanceStep(prevId, nextId) {
        const prev = document.getElementById(prevId);
        const next = document.getElementById(nextId);
        const accent = window.CHAT_ACCENT || '#6B38FB';
        if (prev) {
            prev.classList.remove('active');
            prev.classList.add('done');
            prev.querySelector('.step-icon').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="color:#10B981"><polyline points="20 6 9 17 4 12"/></svg>';
        }
        if (next) {
            next.classList.add('active');
            next.querySelector('.step-icon').innerHTML = `<div class="step-spinner" style="border-top-color:transparent; border-color:${accent}"></div>`;
        }
    }

    // ── Utilities ─────────────────────────────────────────────────────
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function parseMarkdown(text) {
        if (!text) return '';
        const accent = window.CHAT_ACCENT || '#6B38FB';
        let str = String(text);
        str = str.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, url) => {
            const base = window.BASE_URL || '/';
            const fullUrl = url.trim().startsWith('http') ? url.trim() : base + url.trim();
            return `<a href="${fullUrl}" target="_blank" rel="noopener" style="color:${accent};font-weight:700;">${label}</a>`;
        });
        str = str.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
        str = str.replace(/\*(.*?)\*/g, '<i>$1</i>');
        str = str.replace(/\n/g, '<br>');
        return str;
    }

})();
