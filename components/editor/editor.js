/**
 * Advet Modular Editor Logic
 */
window.AdvetEditor = {
    savedRange: null,

    init: function() {
        document.querySelectorAll('.editor-wrapper:not(.initialized)').forEach(wrapper => {
            const toolbar = wrapper.querySelector('.editor-toolbar');
            const content = wrapper.querySelector('.editor-content');
            const input = wrapper.querySelector('.editor-input');
            const wc = wrapper.querySelector('.word-count');

            if (content && input) {
                // Load existing value
                content.innerHTML = input.value;
                this.updateWordCount(content, wc);

                // Sync events
                content.addEventListener('input', () => {
                    input.value = content.innerHTML;
                    this.updateWordCount(content, wc);
                });

                // Selection tracking
                const saveHandler = () => this.saveSel(content);
                content.addEventListener('keyup', saveHandler);
                content.addEventListener('click', saveHandler);
                content.addEventListener('blur', saveHandler);
                
                if(toolbar) {
                    toolbar.addEventListener('mousedown', (e) => {
                        // Don't save if clicking an input inside toolbar
                        if(e.target.tagName !== 'INPUT') {
                            this.saveSel(content);
                        }
                    });
                }

                wrapper.classList.add('initialized');
            }
        });
    },

    saveSel: function(content) {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            if (content.contains(range.commonAncestorContainer)) {
                this.savedRange = range;
            }
        }
    },

    restoreSel: function() {
        if (this.savedRange) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(this.savedRange);
        }
    },

    exec: function(cmd, targetId, val = null) {
        const editor = document.getElementById(targetId);
        if (!editor) return;
        
        editor.focus();
        this.restoreSel();
        document.execCommand(cmd, false, val);
        
        // Trigger input event to sync with textarea
        editor.dispatchEvent(new Event('input'));
    },

    setBlock: function(tag, label, targetId, btnId) {
        this.exec('formatBlock', targetId, tag);
        const labelEl = document.getElementById(btnId);
        if(labelEl) labelEl.textContent = label;
        document.activeElement.blur();
    },

    setColor: function(color, targetId, isLive = false) {
        this.exec('foreColor', targetId, color);
        if(!isLive) document.activeElement.blur();
    },

    applyLink: function(targetId, inputId) {
        const input = document.getElementById(inputId);
        if(input && input.value) {
            this.exec('createLink', targetId, input.value);
            input.value = '';
        }
        document.activeElement.blur();
    },

    updateWordCount: function(content, wc) {
        if (!wc) return;
        const text = content.innerText.trim();
        const words = text ? text.split(/\s+/).length : 0;
        wc.textContent = words + ' word' + (words !== 1 ? 's' : '');
    }
};

// Auto-init
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => AdvetEditor.init());
} else {
    AdvetEditor.init();
}
