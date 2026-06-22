class AdvetUploader {
    // Static references to the shared upload sheet
    static sheet = null;
    static list = null;
    static template = null;
    static footer = null;
    static counter = null;

    constructor(element, options = {}) {
        this.input = typeof element === 'string' ? document.getElementById(element) : element;
        if (!this.input || this.input.dataset.uploaderActive === "true") return;
        this.input.dataset.uploaderActive = "true";

        const script = document.querySelector('script[src*="uploader.js"]');
        const scriptPath = script ? script.src : '';
        const basePath = scriptPath.substring(0, scriptPath.indexOf('assets/js/uploader.js'));

        this.options = {
            dotCount: 10, // Matching the spec
            maxFiles: 10,
            asyncUrl: basePath + 'actions/async-upload.php',
            ...options
        };

        this.container = null;
        this.previewGrid = null;
        this.isCompUploading = false;

        this._init();
    }

    _init() {
        if (!this.input.parentNode) return;
        this.isMini = this.input.getAttribute('data-uploader-mode') === 'mini';
        this.previewTarget = this.input.getAttribute('data-preview-target');
        
        // If a preview target is provided, we assume the UI is handled externally
        // and we only provide the background logic and progress modal.
        if (this.previewTarget) {
            this._ensureSheet();
            this.input.addEventListener('change', () => {
                if (this.input.files.length) this._handleFiles(this.input.files);
            });
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'advet-uploader-container' + (this.isMini ? ' mini-wrapper' : '');
        
        const zone = document.createElement('div');
        zone.className = 'upload-zone' + (this.isMini ? ' mini' : '');
        zone.innerHTML = `
            <div class="icon-stack">
                <div class="icon-bg"></div>
                <div class="icon-fg">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M3 16L7.46967 11.5303C7.80923 11.1908 8.26978 11 8.75 11C9.23022 11 9.69077 11.1908 10.0303 11.5303L14 15.5M15.5 17L14 15.5M21 16L18.5303 13.5303C18.1908 13.1908 17.7302 13 17.25 13C16.7698 13 16.3092 13.1908 15.9697 13.5303L14 15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15.5 8C15.7761 8 16 7.77614 16 7.5C16 7.22386 15.7761 7 15.5 7M15.5 8C15.2239 8 15 7.77614 15 7.5C15 7.22386 15.2239 7 15.5 7M15.5 8V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3.69797 19.7472C2.5 18.3446 2.5 16.2297 2.5 12C2.5 7.77027 2.5 5.6554 3.69797 4.25276C3.86808 4.05358 4.05358 3.86808 4.25276 3.69797C5.6554 2.5 7.77027 2.5 12 2.5C16.2297 2.5 18.3446 2.5 19.7472 3.69797C19.9464 3.86808 20.1319 4.05358 20.302 4.25276C21.5 5.6554 21.5 7.77027 21.5 12C21.5 16.2297 21.5 18.3446 20.302 19.7472C20.1319 19.9464 19.9464 20.1319 19.7472 20.302C18.3446 21.5 16.2297 21.5 12 21.5C7.77027 21.5 5.6554 21.5 4.25276 20.302C4.05358 20.1319 3.86808 19.9464 3.69797 19.7472Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <span class="upload-text">Drop your image here, or <span>browse</span></span>
            <span class="upload-subtext">Supports: PNG, JPG, JPEG, WEBP</span>
            <div class="progress-dots"></div>
            <div class="upload-hint">Preparing curation...</div>
        `;

        const grid = document.createElement('div');
        grid.className = 'preview-grid';

        wrapper.appendChild(zone);
        wrapper.appendChild(grid);

        this.input.style.display = 'none';
        this.input.parentNode.insertBefore(wrapper, this.input.nextSibling);

        this.container = zone;
        this.previewGrid = grid;
        this.hint = zone.querySelector('.upload-hint');

        this._ensureSheet();

        zone.addEventListener('click', () => this.input.click());
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) this._handleFiles(e.dataTransfer.files);
        });

        this.input.addEventListener('change', () => {
            if (this.input.files.length) this._handleFiles(this.input.files);
        });
    }

    _ensureSheet() {
        if (AdvetUploader.sheet && AdvetUploader.list && AdvetUploader.template) return;
        AdvetUploader.sheet = document.getElementById('advet-upload-sheet');
        AdvetUploader.list = document.getElementById('upload-sheet-list');
        AdvetUploader.template = document.getElementById('upload-row-template');
        AdvetUploader.footer = document.getElementById('upload-sheet-footer-text');
        AdvetUploader.counter = document.getElementById('upload-sheet-counter');
    }

    async _handleFiles(fileList) {
        if (this.isCompUploading) return;
        this._ensureSheet();

        if (!AdvetUploader.sheet || !AdvetUploader.list) {
            console.error('AdvetUploader: Required UI elements not found.');
            return;
        }

        this.isCompUploading = true;
        const files = Array.from(fileList);
        
        try {
            AdvetUploader.list.innerHTML = ''; 
            AdvetUploader.sheet.classList.add('active');
            if (AdvetUploader.footer) AdvetUploader.footer.innerText = 'Initializing curation stream...';
            if (AdvetUploader.counter) AdvetUploader.counter.innerText = `0 / ${files.length}`;

            const rowElements = [];
            for (let i = 0; i < files.length; i++) {
                const row = this._createRowUI(files[i]);
                if (row) {
                    AdvetUploader.list.appendChild(row.element);
                    rowElements.push(row);
                }
            }

            let completedCount = 0;
            for (let i = 0; i < files.length; i++) {
                const row = rowElements[i];
                if (!row) continue;
                
                if (this.isMini && this.container) {
                    this.container.classList.add('uploading');
                    if (this.hint) {
                        this.hint.classList.add('active');
                        this.hint.innerText = 'SYNCING...';
                    }
                }

                try {
                    const path = await this._uploadFileWithRow(files[i], row);
                    if (path) {
                        this._updateRowState(row, 'done');
                        this._addPreview(files[i], path);
                    } else {
                        this._updateRowState(row, 'failed');
                    }
                } catch (err) {
                    this._updateRowState(row, 'failed');
                    console.error(err);
                }

                completedCount++;
                if (AdvetUploader.counter) AdvetUploader.counter.innerText = `${completedCount} / ${files.length}`;
            }

            if (AdvetUploader.footer) AdvetUploader.footer.innerText = 'All assets synced successfully.';
        } catch (err) {
            console.error('AdvetUploader Error:', err);
        } finally {
            setTimeout(() => {
                if (AdvetUploader.sheet) AdvetUploader.sheet.classList.remove('active');
                this.isCompUploading = false;
                if (this.isMini && this.container) {
                    this.container.classList.remove('uploading');
                    if (this.hint) this.hint.classList.remove('active');
                }
                this.input.value = '';
            }, 2000);
        }
    }

    _createRowUI(file) {
        const clone = AdvetUploader.template.content.cloneNode(true);
        const element = clone.querySelector('.row-item-container');
        const dots = element.querySelectorAll('.dot-item');
        
        element.querySelector('.row-filename').innerText = file.name;
        
        // Show local preview immediately if possible
        const previewImg = element.querySelector('.row-preview');
        const iconPlaceholder = element.querySelector('.row-icon-placeholder');
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImg.src = e.target.result;
            previewImg.classList.remove('hidden');
            iconPlaceholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        return {
            element,
            dots: Array.from(dots),
            statusText: element.querySelector('.row-status-text'),
            statusIconBg: element.querySelector('.row-status-icon-bg'),
            statusSvg: element.querySelector('.status-svg'),
            svgPath: element.querySelector('.svg-path')
        };
    }

    _uploadFileWithRow(file, row) {
        return new Promise((resolve) => {
            this._updateRowState(row, 'uploading');
            
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('file', file);

            xhr.open('POST', this.options.asyncUrl, true);

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    this._updateRowProgress(row, percent);
                }
            };

            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            this._appendHiddenInput(res.path);
                            resolve(res.path);
                        } else resolve(null);
                    } catch (e) { resolve(null); }
                } else resolve(null);
            };

            xhr.onerror = () => resolve(null);
            xhr.send(formData);
        });
    }

    _updateRowState(row, state) {
        const { dots, statusText, statusIconBg, statusSvg, svgPath } = row;

        // Reset
        statusIconBg.classList.remove('bg-sand/10', 'bg-blue-500/10', 'bg-green-500/10', 'bg-red-500/10', 'text-muted', 'text-blue-500', 'text-green-500', 'text-red-500');
        dots.forEach(d => d.classList.remove('bg-blue-500', 'bg-green-500', 'bg-red-500', 'dot-pulse', 'shadow-[0_0_8px_rgba(59,130,246,0.5)]'));

        if (state === 'waiting') {
            statusText.innerText = 'Waiting';
            statusIconBg.classList.add('bg-sand/10', 'text-muted');
            statusText.classList.remove('text-blue-500', 'text-green-500', 'text-red-500');
            svgPath.setAttribute('d', 'M12 4.5v15m7.5-7.5h-15'); // Plus
        } 
        else if (state === 'uploading') {
            statusText.innerText = 'Syncing...';
            statusText.classList.add('text-blue-500');
            statusIconBg.classList.add('bg-blue-500/10', 'text-blue-500');
            svgPath.setAttribute('d', 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99'); // Spinner
            statusSvg.classList.add('animate-spin');
        } 
        else if (state === 'done') {
            statusText.innerText = 'Done';
            statusText.classList.remove('text-blue-500');
            statusText.classList.add('text-green-500');
            statusIconBg.classList.add('bg-green-500/10', 'text-green-500');
            statusSvg.classList.remove('animate-spin');
            svgPath.setAttribute('d', 'M4.5 12.75l6 6 9-13.5'); // Check
            dots.forEach(d => {
                d.classList.add('bg-green-500');
                d.classList.remove('bg-sand/30', 'dark:bg-zinc-700');
            });
        } 
        else if (state === 'failed') {
            statusText.innerText = 'Failed';
            statusText.classList.add('text-red-500');
            statusIconBg.classList.add('bg-red-500/10', 'text-red-500');
            statusSvg.classList.remove('animate-spin');
            svgPath.setAttribute('d', 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'); // Error
            dots.forEach(d => {
                d.classList.add('bg-red-500');
                d.classList.remove('bg-sand/30', 'dark:bg-zinc-700');
            });
        }
    }

    _updateRowProgress(row, percent) {
        const activeCount = Math.floor((percent / 100) * this.options.dotCount);
        row.dots.forEach((dot, i) => {
            if (i < activeCount) {
                dot.classList.add('bg-blue-500', 'shadow-[0_0_8px_rgba(59,130,246,0.5)]');
                dot.classList.remove('bg-sand/30', 'dark:bg-zinc-700');
                if (i === activeCount - 1) dot.classList.add('dot-pulse');
                else dot.classList.remove('dot-pulse');
            }
        });
    }

    _addPreview(file, path = null) {
        const targetId = this.input.getAttribute('data-preview-target');
        const reader = new FileReader();
        reader.onload = (e) => {
            if (targetId) {
                const target = document.getElementById(targetId);
                const placeholder = document.getElementById(targetId + '-placeholder');
                
                if (target) {
                    if (this.input.hasAttribute('multiple')) {
                        // Multi-upload append logic
                        const item = document.createElement('div');
                        item.className = 'relative w-full aspect-[21/9] rounded-xl overflow-hidden group border border-sand/30 shadow-sm animate-in zoom-in-95 duration-500';
                        item.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'absolute top-2 right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all transform hover:scale-110 shadow-xl';
                        removeBtn.innerHTML = '✕';
                        removeBtn.onclick = (ev) => {
                            ev.stopPropagation();
                            if (path) {
                                const hidden = this.input.form.querySelector(`input[type="hidden"][value="${path}"]`);
                                if (hidden) hidden.remove();
                            }
                            item.remove();
                        };
                        item.appendChild(removeBtn);
                        target.appendChild(item);
                        if (placeholder) placeholder.style.display = 'none';
                    } else {
                        // Single upload replace logic
                        let targetImg = target;
                        
                        // If the target img doesn't exist yet (initial upload), create it
                        if (!targetImg) {
                            const container = document.getElementById(targetId + '-container');
                            if (container) {
                                targetImg = document.createElement('img');
                                targetImg.id = targetId;
                                // Apply some standard classes based on context
                                if (targetId.includes('logo')) targetImg.className = 'max-w-[60%] max-h-[60%] object-contain transition-transform duration-700';
                                else if (targetId.includes('favicon')) targetImg.className = 'max-w-[40%] max-h-[40%] object-contain';
                                else targetImg.className = 'max-w-full max-h-full object-contain';
                                
                                container.appendChild(targetImg);
                            }
                        }
                        
                        if (targetImg) {
                            targetImg.src = e.target.result;
                            targetImg.style.display = 'block';
                            if (placeholder) placeholder.style.display = 'none';
                        }
                    }
                }
            } else {
                const item = document.createElement('div');
                item.className = 'preview-item';
                item.style.backgroundImage = `url(${e.target.result})`;
                if (path) item.dataset.path = path;
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'remove-btn';
                btn.innerHTML = '✕';
                btn.onclick = (ev) => {
                    ev.stopPropagation();
                    if (typeof removeGalleryImage === 'function') {
                        removeGalleryImage(btn);
                    } else {
                        // Fallback if global function not found
                        if (item.dataset.path) {
                            const hidden = this.input.form.querySelector(`input[type="hidden"][value="${item.dataset.path}"]`);
                            if (hidden) hidden.remove();
                        }
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.8)';
                        setTimeout(() => item.remove(), 400);
                    }
                };
                item.appendChild(btn);
                this.previewGrid.appendChild(item);
            }
        };
        reader.readAsDataURL(file);
    }

    _appendHiddenInput(path) {
        if (!path) return;
        const name = this.input.name.replace('[]', '');
        const hiddenName = this.input.hasAttribute('multiple') || this.input.name.includes('[]') ? `async_${name}[]` : `async_${name}`;
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = hiddenName;
        hidden.value = path;
        this.input.form.appendChild(hidden);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const initAll = () => {
        // More robust selector to catch various image extension formats
        document.querySelectorAll('input[type="file"]').forEach(input => {
            const accept = (input.getAttribute('accept') || '').toLowerCase();
            const isImage = accept.includes('image') || 
                            accept.includes('.jpg') || 
                            accept.includes('.jpeg') || 
                            accept.includes('.png') || 
                            accept.includes('.webp') || 
                            accept.includes('.svg') || 
                            accept.includes('.ico');
            
            if (isImage && input.dataset.uploaderActive !== "true") {
                new AdvetUploader(input);
            }
        });
    };
    initAll();
});
