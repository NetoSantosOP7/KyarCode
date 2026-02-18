document.addEventListener('alpine:init', () => {
    Alpine.data('qrApp', () => ({
        text: '',
        color: '#0f172a',
        bg: '#ffffff',

        selectedEffect: 'none',
        frameType: 'naruto',
        glowColor: '#a78bfa',
        glowIntensity: 60,
        shadowIntensity: 75,

        bgBorderRadius: 24,
        moduleStyle: 'square',

        qrImage: '',
        logoBase64: null,
        logoSize: 18,

        customText: '',
        textSize: 48,
        textColor: '#ffffff',
        textShadowColor: '#000000',
        textShadowIntensity: 30,
        textGlow: 0,

        loading: false,
        cropper: null,
        abortController: null,
        qrCache: new Map(),

        showTouchHint: true,
        touchHintInterval: null,
        showColorPicker: false,
        currentColorTarget: null,
        pickerHue: 0,
        pickerSaturation: 100,
        pickerValue: 100,

        init() {
            this.generateQR();
            this.updateVisuals();

            if (window.innerWidth <= 1024) {
                this.startTouchHint();
            }
            if (window.innerWidth <= 1024) {
                document.addEventListener('click', (e) => {
                    const colorInput = e.target.closest('input[type="color"]');
                    if (colorInput) {
                        e.preventDefault();
                        this.openMobileColorPicker(colorInput);
                    }
                });
            }
        },

        startTouchHint() {
            this.touchHintInterval = setInterval(() => {
                if (this.showTouchHint && this.selectedEffect === '3d') {
                    const hint = document.getElementById('touchHint');
                    if (hint) {
                        hint.classList.remove('hidden');
                        hint.classList.add('animate-touch-hint');

                        setTimeout(() => {
                            hint.classList.add('hidden');
                            hint.classList.remove('animate-touch-hint');
                        }, 3000);
                    }
                }
            }, 10000);
        },

        hideTouchHint() {
            this.showTouchHint = false;
            if (this.touchHintInterval) {
                clearInterval(this.touchHintInterval);
            }
            const hint = document.getElementById('touchHint');
            if (hint) {
                hint.classList.add('hidden');
            }
        },

        openMobileColorPicker(input) {
            this.currentColorTarget = input;
            const currentColor = input.value;

            const hsv = this.hexToHSV(currentColor);
            this.pickerHue = hsv.h;
            this.pickerSaturation = 100;
            this.pickerValue = 100;

            this.showColorPicker = true;
        },

        closeMobileColorPicker() {
            this.showColorPicker = false;
            this.currentColorTarget = null;
        },

        applyMobileColor() {
            if (this.currentColorTarget) {
                const hexColor = this.hsvToHex(this.pickerHue, this.pickerSaturation, this.pickerValue);
                const targetModel = this.currentColorTarget.getAttribute('x-model');

                if (targetModel) {
                    this[targetModel] = hexColor;

                    if (targetModel === 'color' || targetModel === 'bg' || targetModel === 'glowColor') {
                        this.generateQR();
                    }
                }
            }
            this.closeMobileColorPicker();
        },

        hexToHSV(hex) {
            const r = parseInt(hex.slice(1, 3), 16) / 255;
            const g = parseInt(hex.slice(3, 5), 16) / 255;
            const b = parseInt(hex.slice(5, 7), 16) / 255;

            const max = Math.max(r, g, b);
            const min = Math.min(r, g, b);
            const delta = max - min;

            let h = 0;
            if (delta !== 0) {
                if (max === r) h = ((g - b) / delta) % 6;
                else if (max === g) h = (b - r) / delta + 2;
                else h = (r - g) / delta + 4;
                h = Math.round(h * 60);
                if (h < 0) h += 360;
            }

            const s = max === 0 ? 0 : (delta / max) * 100;
            const v = max * 100;

            return { h, s, v };
        },

        hsvToHex(h, s, v) {
            s = s / 100;
            v = v / 100;

            const c = v * s;
            const x = c * (1 - Math.abs(((h / 60) % 2) - 1));
            const m = v - c;

            let r = 0, g = 0, b = 0;
            if (h >= 0 && h < 60) { r = c; g = x; b = 0; }
            else if (h >= 60 && h < 120) { r = x; g = c; b = 0; }
            else if (h >= 120 && h < 180) { r = 0; g = c; b = x; }
            else if (h >= 180 && h < 240) { r = 0; g = x; b = c; }
            else if (h >= 240 && h < 300) { r = x; g = 0; b = c; }
            else if (h >= 300 && h < 360) { r = c; g = 0; b = x; }

            r = Math.round((r + m) * 255);
            g = Math.round((g + m) * 255);
            b = Math.round((b + m) * 255);

            return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
        },

        get currentPickerColor() {
            return this.hsvToHex(this.pickerHue, this.pickerSaturation, this.pickerValue);
        },

        selectEffect(eff) {
            this.selectedEffect = eff;

            if (eff === 'frame' && this.logoSize > 25) {
                this.logoSize = 25;
            }

            if (eff !== 'frame') {
                this.generateQR();
            }
        },

        updateVisuals() {
            document.documentElement.style.setProperty('--shadow-opacity', this.shadowIntensity / 100);
            document.documentElement.style.setProperty('--gloss-opacity', this.glowIntensity / 100);
            document.documentElement.style.setProperty('--neon-intensity', (this.glowIntensity / 50));
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                const img = document.getElementById('imageToCrop');
                img.src = event.target.result;
                const modal = document.getElementById('cropperModal');
                modal.classList.remove('hidden');
                setTimeout(() => modal.classList.add('modal-show'), 10);

                if (this.cropper) this.cropper.destroy();
                this.cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: true,
                    center: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: true,
                    zoomOnWheel: true,
                    wheelZoomRatio: 0.1
                });
            };
            reader.readAsDataURL(file);
        },

        closeCropper() {
            const modal = document.getElementById('cropperModal');
            modal.classList.remove('modal-show');
            setTimeout(() => modal.classList.add('hidden'), 300);
        },

        applyCrop() {
            const canvas = this.cropper.getCroppedCanvas({ width: 400, height: 400 });
            const rounded = document.createElement('canvas');
            const ctx = rounded.getContext('2d', { alpha: true });
            rounded.width = rounded.height = 400;

            ctx.beginPath();
            ctx.arc(200, 200, 200, 0, Math.PI * 2);
            ctx.clip();
            ctx.drawImage(canvas, 0, 0, 400, 400);

            this.logoBase64 = rounded.toDataURL('image/png');
            this.closeCropper();
            this.generateQR();
        },

        async generateQR() {
            const currentConfig = {
                text: String(this.text || ''),
                color: String(this.color || ''),
                bg: String(this.bg || ''),
                bgBorderRadius: Number(this.bgBorderRadius || 0),
                moduleStyle: String(this.moduleStyle || 'square'),
                effect: String(this.selectedEffect || ''),
                glowColor: this.selectedEffect === 'glow' ? String(this.glowColor || '') : 'none',
                glowIntensity: this.selectedEffect === 'glow' ? Number(this.glowIntensity || 0) : 50,
                frameType: this.selectedEffect === 'frame' ? String(this.frameType || '') : 'none',
                logoSize: Number(this.logoSize || 0),
                logoBase64: String(this.logoBase64 || ''),
                customText: String(this.customText || ''),
                textSize: Number(this.textSize || 0),
                textColor: String(this.textColor || ''),
                textShadowColor: String(this.textShadowColor || ''),
                textShadowIntensity: Number(this.textShadowIntensity || 0),
                textGlow: Number(this.textGlow || 0)
            };

            const currentHash = JSON.stringify(currentConfig);

            if (this.qrCache.has(currentHash)) {
                const cachedImage = this.qrCache.get(currentHash);
                this.qrImage = cachedImage;
                return;
            }


            if (this.abortController) {
                this.abortController.abort();
            }

            this.abortController = new AbortController();

            if (this.generateTimeout) clearTimeout(this.generateTimeout);
            this.loading = true;

            try {
                const formData = new FormData();
                const params = {
                    text: this.text,
                    color: this.color,
                    bg: this.bg,
                    bgBorderRadius: this.bgBorderRadius,
                    moduleStyle: this.moduleStyle,
                    effect: this.selectedEffect,
                    glowColor: this.selectedEffect === 'glow' ? this.glowColor : 'none',
                    glowIntensity: this.selectedEffect === 'glow' ? this.glowIntensity : 50,
                    frameType: this.selectedEffect === 'frame' ? this.frameType : 'none',
                    logoSize: this.logoSize,
                    customText: this.customText,
                    textSize: parseInt(this.textSize),
                    textColor: this.textColor,
                    textShadowColor: this.textShadowColor,
                    textShadowIntensity: parseInt(this.textShadowIntensity),
                    textGlow: parseInt(this.textGlow)
                };

                Object.entries(params).forEach(([key, value]) => formData.append(key, value));
                if (this.logoBase64) formData.append('logo', this.logoBase64);

                const res = await fetch('index.php?url=generate', {
                    method: 'POST',
                    body: formData,
                    signal: this.abortController.signal
                });

                const responseText = await res.text();
                
                if (!res.ok) {
                    throw new Error('Failed to generate QR: ' + responseText);
                }
                
                if (!responseText.startsWith('data:image/')) {
                    throw new Error('Invalid image format received');
                }
                
                this.qrImage = responseText;

                if (this.qrCache.size >= 50) {
                    const firstKey = this.qrCache.keys().next().value;
                    this.qrCache.delete(firstKey);
                }
                this.qrCache.set(currentHash, this.qrImage);

                this.abortController = null;
            } catch (e) {
                if (e.name === 'AbortError') {
                } else {
                    console.error('Erro ao gerar QR:', e);
                }
            } finally {
                this.loading = false;
            }
        },

        handle3DMove(e) {
            if (this.showTouchHint) {
                this.hideTouchHint();
            }

            const wrapper = e.currentTarget;
            const card = wrapper.querySelector('.qr-3d');
            const shadow = wrapper.querySelector('.qr-shadow');
            if (!card) return;

            const rect = wrapper.getBoundingClientRect();

            let clientX, clientY;
            if (e.type === 'touchmove') {
                e.preventDefault();
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }

            const mouseX = clientX - rect.left;
            const mouseY = clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const deltaX = mouseX - centerX;
            const deltaY = mouseY - centerY;

            const rx = `${(deltaY / rect.height) * -30}deg`;
            const ry = `${(deltaX / rect.width) * 30}deg`;
            const glossX = `${(mouseX / rect.width) * 100}%`;
            const glossY = `${(mouseY / rect.height) * 100}%`;

            requestAnimationFrame(() => {
                card.style.setProperty('--rx', rx);
                card.style.setProperty('--ry', ry);
                card.style.setProperty('--gloss-x', glossX);
                card.style.setProperty('--gloss-y', glossY);

                if (shadow) {
                    const shadowX = (deltaX / rect.width) * -15;
                    const shadowY = (deltaY / rect.height) * -8;
                    shadow.style.transform = `translateX(calc(-50% + ${shadowX}px)) translateY(${shadowY}px)`;
                }
            });
        },

        reset3D(e) {
            const card = e.currentTarget.querySelector('.qr-3d');
            const shadow = e.currentTarget.querySelector('.qr-shadow');
            if (!card) return;

            requestAnimationFrame(() => {
                card.style.setProperty('--rx', '0deg');
                card.style.setProperty('--ry', '0deg');
                card.style.setProperty('--gloss-x', '50%');
                card.style.setProperty('--gloss-y', '50%');
                if (shadow) shadow.style.transform = 'translateX(-50%)';
            });
        },

        async downloadQR() {
            if (!this.qrImage) {
                alert('Gere um QR Code primeiro!');
                return;
            }

            const animations = document.querySelectorAll('.qr-3d, .qr-shadow');
            animations.forEach(el => el.style.animation = 'none');

            const textElement = document.getElementById('customTextElement');
            const originalMarginTop = textElement ? textElement.style.marginTop : null;
            if (textElement && this.customText) {
                textElement.style.marginTop = '20px';
            }

            try {
                const scale = 3;
                const targetElement = this.selectedEffect === '3d'
                    ? document.querySelector('#qrCard')
                    : document.querySelector('#qr-to-print');
                const containerPadding = this.selectedEffect === '3d' ? 40 : 100;

                if (!targetElement) throw new Error('Elemento não encontrado');

                const rect = targetElement.getBoundingClientRect();
                const canvas = document.createElement('canvas');
                canvas.width = (rect.width + containerPadding * 2) * scale;
                canvas.height = (rect.height + containerPadding * 2) * scale;

                const ctx = canvas.getContext('2d', { alpha: true });
                ctx.scale(scale, scale);
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const img = new Image();
                img.crossOrigin = 'anonymous';

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = this.qrImage;
                });

                const drawX = containerPadding;
                const drawY = containerPadding;
                const drawW = rect.width;
                const drawH = rect.height;

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = drawW;
                tempCanvas.height = drawH;
                const tempCtx = tempCanvas.getContext('2d', { alpha: true });

                tempCtx.beginPath();
                this.roundRect(tempCtx, 0, 0, drawW, drawH, this.bgBorderRadius);
                tempCtx.clip();

                if (this.selectedEffect === '3d') {
                    tempCtx.fillStyle = this.bg;
                    tempCtx.fillRect(0, 0, drawW, drawH);
                }
                tempCtx.drawImage(img, 0, 0, drawW, drawH);

                if (this.selectedEffect === 'glow') {
                    const glowIntensity = this.glowIntensity / 100;
                    const glowColor = this.glowColor;
                    const r = parseInt(glowColor.slice(1, 3), 16);
                    const g = parseInt(glowColor.slice(3, 5), 16);
                    const b = parseInt(glowColor.slice(5, 7), 16);

                    for (let i = 15; i > 0; i--) {
                        const blurSize = (glowIntensity * 80 * i) / 15;
                        const alpha = Math.min(0.9, (glowIntensity * 0.9) / Math.sqrt(i));
                        ctx.shadowColor = `rgba(${r}, ${g}, ${b}, ${alpha})`;
                        ctx.shadowBlur = blurSize;
                        ctx.drawImage(tempCanvas, drawX, drawY);
                    }
                    ctx.shadowColor = 'transparent';
                    ctx.shadowBlur = 0;
                    ctx.strokeStyle = glowColor;
                    ctx.lineWidth = 5;
                    ctx.beginPath();
                    this.roundRect(ctx, drawX, drawY, drawW, drawH, this.bgBorderRadius);
                    ctx.stroke();
                }

                if (this.selectedEffect === '3d') {
                    const shadowIntensity = this.shadowIntensity / 100;
                    const shadowOffset = shadowIntensity * 35;

                    for (let i = 6; i > 0; i--) {
                        ctx.shadowColor = `rgba(0, 0, 0, ${(shadowIntensity * 0.8) / i})`;
                        ctx.shadowBlur = (shadowOffset * 2.5) / i;
                        ctx.shadowOffsetX = (shadowOffset * 0.8) / i;
                        ctx.shadowOffsetY = (shadowOffset * 1.2) / i;
                        ctx.drawImage(tempCanvas, drawX, drawY);
                    }
                    ctx.shadowColor = 'transparent';
                    ctx.shadowBlur = ctx.shadowOffsetX = ctx.shadowOffsetY = 0;
                }

                ctx.drawImage(tempCanvas, drawX, drawY);

                if (this.selectedEffect === '3d') {
                    const glossIntensity = this.glowIntensity / 100;
                    const gradient = ctx.createRadialGradient(
                        drawX + drawW * 0.5, drawY + drawH * 0.3, 0,
                        drawX + drawW * 0.5, drawY + drawH * 0.3, drawW * 0.6
                    );
                    gradient.addColorStop(0, `rgba(255, 255, 255, ${glossIntensity * 0.8})`);
                    gradient.addColorStop(0.4, `rgba(255, 255, 255, ${glossIntensity * 0.3})`);
                    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
                    ctx.save();
                    ctx.beginPath();
                    this.roundRect(ctx, drawX, drawY, drawW, drawH, this.bgBorderRadius);
                    ctx.clip();
                    ctx.fillStyle = gradient;
                    ctx.fillRect(drawX, drawY, drawW, drawH);
                    ctx.restore();
                }

                if (this.logoBase64) {
                    const logoImg = new Image();
                    logoImg.crossOrigin = 'anonymous';

                    await new Promise((resolve, reject) => {
                        logoImg.onload = resolve;
                        logoImg.onerror = reject;
                        logoImg.src = this.logoBase64;
                    });

                    let logoSize;
                    if (this.selectedEffect === '3d') {
                        logoSize = (drawW * this.logoSize) / 100;
                    } else {
                        const multiplier = this.selectedEffect === 'frame' ? 2.24 : 3.5;
                        logoSize = this.logoSize * multiplier;
                    }

                    const logoX = drawX + (drawW - logoSize) / 2;
                    const logoY = drawY + (drawH - logoSize) / 2;

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, logoSize / 2, 0, Math.PI * 2);
                    ctx.fillStyle = 'white';
                    ctx.fill();
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.1)';
                    ctx.shadowBlur = 8;
                    ctx.shadowOffsetX = 0;
                    ctx.shadowOffsetY = 2;
                    ctx.fill();
                    ctx.restore();

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, (logoSize / 2) * 0.92, 0, Math.PI * 2);
                    ctx.clip();
                    ctx.drawImage(logoImg, logoX, logoY, logoSize, logoSize);
                    ctx.restore();
                }

                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `qr-pro-${Date.now()}.png`;
                    link.href = url;
                    link.click();
                    URL.revokeObjectURL(url);
                    animations.forEach(el => el.style.animation = '');

                    if (textElement && originalMarginTop !== null) {
                        textElement.style.marginTop = originalMarginTop || '';
                    }
                }, 'image/png', 1.0);

            } catch (err) {
                console.error('Erro ao gerar imagem:', err);
                alert('Erro ao gerar imagem do QR Code.');
                animations.forEach(el => el.style.animation = '');

                if (textElement && originalMarginTop !== null) {
                    textElement.style.marginTop = originalMarginTop || '';
                }
            }
        },

        roundRect(ctx, x, y, width, height, radius) {
            if (typeof ctx.roundRect === 'function') {
                ctx.roundRect(x, y, width, height, radius);
            } else {
                ctx.beginPath();
                ctx.moveTo(x + radius, y);
                ctx.lineTo(x + width - radius, y);
                ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                ctx.lineTo(x + width, y + height - radius);
                ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                ctx.lineTo(x + radius, y + height);
                ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                ctx.lineTo(x, y + radius);
                ctx.quadraticCurveTo(x, y, x + radius, y);
                ctx.closePath();
            }
        },

        async downloadFullCard() {
            if (!this.qrImage) {
                alert('Gere um QR Code primeiro!');
                return;
            }

            try {
                const scale = 4;

                let previewCard = null;
                let qrSize = 320;

                if (window.innerWidth <= 1024) {
                    previewCard = document.querySelector('.lg\\:hidden .qr-stage');
                } else {
                    previewCard = document.querySelector('main .qr-stage');
                }

                if (previewCard) {
                    if (this.selectedEffect === '3d') {
                        const qrWrapper = previewCard.querySelector('.qr-3d-wrapper');
                        if (qrWrapper) {
                            const computedStyle = window.getComputedStyle(qrWrapper);
                            const cardSizeVar = computedStyle.getPropertyValue('--card-size').trim();
                            qrSize = parseInt(cardSizeVar) || qrWrapper.offsetWidth || 320;
                        }
                    } else {
                        const qrImg = previewCard.querySelector('.qr-image-2d');
                        if (qrImg) {
                            qrSize = qrImg.offsetWidth || 320;
                        }
                    }
                }

                qrSize = Math.max(160, Math.min(qrSize, 600));

                let cardWidth;
                if (this.selectedEffect === '3d') {

                    const cardPaddingTotal = 60;
                    const bg3dPaddingTotal = 100;
                    cardWidth = qrSize + cardPaddingTotal + bg3dPaddingTotal + 40;
                } else {
                    cardWidth = qrSize * 1.875;
                }

                let textHeight = 0;
                let textLines = [];
                let actualTextSize = this.textSize * 0.28;

                if (this.customText) {
                    if (window.innerWidth <= 1024) {
                        actualTextSize = this.textSize * 0.2;
                    } else {
                        actualTextSize = this.textSize * 0.28;
                    }

                    const manualLines = this.customText.split('\n');

                    const tempCanvas = document.createElement('canvas');
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.font = `italic bold ${actualTextSize}px Plus Jakarta Sans`;

                    const maxWidth = (cardWidth - 60) * 0.85;

                    manualLines.forEach(manualLine => {
                        if (!manualLine.trim()) {
                            textLines.push('');
                            return;
                        }

                        const words = manualLine.toUpperCase().split(' ');
                        let currentLine = words[0] || '';

                        for (let i = 1; i < words.length; i++) {
                            const testLine = currentLine + ' ' + words[i];
                            const metrics = tempCtx.measureText(testLine);
                            if (metrics.width > maxWidth && currentLine.length > 0) {
                                textLines.push(currentLine);
                                currentLine = words[i];
                            } else {
                                currentLine = testLine;
                            }
                        }
                        if (currentLine.length > 0) {
                            textLines.push(currentLine);
                        }
                    });

                    const lineHeight = actualTextSize * 1.3;
                    textHeight = textLines.length > 0 ? (textLines.length * lineHeight) + 80 : 0;
                }

                const baseHeight = cardWidth;
                const cardHeight = baseHeight + textHeight;

                const canvas = document.createElement('canvas');
                canvas.width = cardWidth * scale;
                canvas.height = cardHeight * scale;
                const ctx = canvas.getContext('2d', {
                    alpha: true,
                    willReadFrequently: false,
                    desynchronized: false
                });

                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';

                ctx.scale(scale, scale);

                ctx.fillStyle = '#0a0e1a';
                ctx.fillRect(0, 0, cardWidth, cardHeight);

                const cardPadding = 30;
                const cardInnerX = cardPadding;
                const cardInnerY = cardPadding;
                const cardInnerWidth = cardWidth - cardPadding * 2;
                const cardInnerHeight = cardHeight - cardPadding * 2;

                ctx.fillStyle = '#1a202c';
                this.roundRect(ctx, cardInnerX, cardInnerY, cardInnerWidth, cardInnerHeight, 40);
                ctx.fill();

                ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
                ctx.lineWidth = 1;
                ctx.stroke();

                if (this.selectedEffect === '3d') {
                    const bg3dPadding = 50;
                    const bg3dX = cardInnerX + bg3dPadding;
                    const bg3dY = cardInnerY + bg3dPadding;
                    const bg3dWidth = cardInnerWidth - bg3dPadding * 2;
                    const bg3dHeight = qrSize + 60;

                    ctx.save();
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
                    ctx.shadowBlur = 20;
                    ctx.shadowOffsetY = 10;

                    const gradient3d = ctx.createLinearGradient(bg3dX, bg3dY, bg3dX + bg3dWidth, bg3dY + bg3dHeight);
                    gradient3d.addColorStop(0, '#ffffffff');
                    gradient3d.addColorStop(1, '#e6e9edff');

                    ctx.fillStyle = gradient3d;
                    ctx.beginPath();
                    this.roundRect(ctx, bg3dX, bg3dY, bg3dWidth, bg3dHeight, 30);
                    ctx.fill();
                    ctx.restore();

                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    this.roundRect(ctx, bg3dX, bg3dY, bg3dWidth, bg3dHeight, 30);
                    ctx.stroke();
                }

                const img = new Image();
                img.crossOrigin = 'anonymous';
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = this.qrImage;
                });

                const qrX = (cardWidth - qrSize) / 2;

                let qrY;
                if (this.selectedEffect === '3d') {
                    const bg3dPadding = 50;
                    const bg3dTopMargin = 30;
                    qrY = cardInnerY + bg3dPadding + bg3dTopMargin;
                } else {
                    qrY = cardInnerY + ((cardInnerHeight - textHeight - qrSize) / 2);
                }

                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = qrSize * scale;
                tempCanvas.height = qrSize * scale;
                const tempCtx = tempCanvas.getContext('2d', {
                    alpha: true,
                    willReadFrequently: false
                });

                tempCtx.imageSmoothingEnabled = true;
                tempCtx.imageSmoothingQuality = 'high';
                tempCtx.scale(scale, scale);

                tempCtx.beginPath();
                this.roundRect(tempCtx, 0, 0, qrSize, qrSize, this.bgBorderRadius);
                tempCtx.clip();
                tempCtx.drawImage(img, 0, 0, qrSize, qrSize);

                if (this.selectedEffect === 'glow') {
                    const glowIntensity = this.glowIntensity / 100;
                    const r = parseInt(this.glowColor.slice(1, 3), 16);
                    const g = parseInt(this.glowColor.slice(3, 5), 16);
                    const b = parseInt(this.glowColor.slice(5, 7), 16);

                    for (let i = 25; i > 0; i--) {
                        const blurSize = (glowIntensity * 100 * i) / 15;
                        const alpha = Math.min(1, (glowIntensity * 1.5) / Math.sqrt(i));

                        ctx.shadowColor = `rgba(${r}, ${g}, ${b}, ${alpha})`;
                        ctx.shadowBlur = blurSize;
                        ctx.shadowOffsetX = 0;
                        ctx.shadowOffsetY = 0;
                        ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, qrX, qrY, qrSize, qrSize);
                    }

                    ctx.shadowColor = 'transparent';
                    ctx.shadowBlur = 0;

                    ctx.strokeStyle = this.glowColor;
                    ctx.lineWidth = 5;
                    ctx.beginPath();
                    this.roundRect(ctx, qrX, qrY, qrSize, qrSize, this.bgBorderRadius);
                    ctx.stroke();
                }

                if (this.selectedEffect === '3d') {
                    const shadowIntensity = this.shadowIntensity / 100;
                    const shadowSpread = 40;

                    for (let i = 8; i > 0; i--) {
                        const alpha = (shadowIntensity * 0.15) / i;
                        const blur = (shadowSpread * 3) / i;
                        const offsetY = (shadowSpread * 1.5) / i;

                        ctx.shadowColor = `rgba(0, 0, 0, ${alpha})`;
                        ctx.shadowBlur = blur;
                        ctx.shadowOffsetX = 0;
                        ctx.shadowOffsetY = offsetY;
                        ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, qrX, qrY, qrSize, qrSize);
                    }

                    ctx.shadowColor = 'transparent';
                    ctx.shadowBlur = 0;
                    ctx.shadowOffsetX = 0;
                    ctx.shadowOffsetY = 0;
                }

                ctx.drawImage(tempCanvas, 0, 0, tempCanvas.width, tempCanvas.height, qrX, qrY, qrSize, qrSize);

                if (this.logoBase64) {
                    const logoImg = new Image();
                    logoImg.crossOrigin = 'anonymous';
                    await new Promise((resolve, reject) => {
                        logoImg.onload = resolve;
                        logoImg.onerror = reject;
                        logoImg.src = this.logoBase64;
                    });

                    const adjustedLogoSize = this.selectedEffect === 'frame' ? this.logoSize * 0.7 : this.logoSize;
                    const logoSizeCalc = (qrSize * adjustedLogoSize) / 100;
                    const logoX = qrX + (qrSize - logoSizeCalc) / 2;
                    const logoY = qrY + (qrSize - logoSizeCalc) / 2;

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(logoX + logoSizeCalc / 2, logoY + logoSizeCalc / 2, logoSizeCalc / 2, 0, Math.PI * 2);
                    ctx.fillStyle = 'white';
                    ctx.shadowColor = 'rgba(0, 0, 0, 0.15)';
                    ctx.shadowBlur = 10;
                    ctx.shadowOffsetY = 2;
                    ctx.fill();
                    ctx.restore();

                    ctx.save();
                    ctx.beginPath();
                    ctx.arc(logoX + logoSizeCalc / 2, logoY + logoSizeCalc / 2, (logoSizeCalc / 2) * 0.92, 0, Math.PI * 2);
                    ctx.clip();
                    ctx.drawImage(logoImg, logoX, logoY, logoSizeCalc, logoSizeCalc);
                    ctx.restore();
                }

                if (this.customText && textLines.length > 0) {
                    ctx.font = `italic bold ${actualTextSize}px Plus Jakarta Sans`;
                    ctx.textAlign = 'center';

                    const r = parseInt(this.textColor.slice(1, 3), 16);
                    const g = parseInt(this.textColor.slice(3, 5), 16);
                    const b = parseInt(this.textColor.slice(5, 7), 16);

                    const lineHeight = actualTextSize * 1.3;
                    const textStartY = qrY + qrSize + 60;

                    textLines.forEach((line, index) => {
                        const yPos = textStartY + (index * lineHeight);

                        if (this.textGlow > 0) {
                            const glowAlpha = this.textGlow / 100;
                            for (let i = 5; i > 0; i--) {
                                ctx.shadowColor = `rgba(${r}, ${g}, ${b}, ${glowAlpha / (i * 0.8)})`;
                                ctx.shadowBlur = (this.textGlow / 2) * i;
                                ctx.fillStyle = this.textColor;
                                ctx.fillText(line, cardWidth / 2, yPos);
                            }
                            ctx.shadowColor = 'transparent';
                            ctx.shadowBlur = 0;
                        }

                        if (this.textShadowIntensity > 0) {
                            const shadowR = parseInt(this.textShadowColor.slice(1, 3), 16);
                            const shadowG = parseInt(this.textShadowColor.slice(3, 5), 16);
                            const shadowB = parseInt(this.textShadowColor.slice(5, 7), 16);
                            ctx.shadowColor = `rgba(${shadowR}, ${shadowG}, ${shadowB}, ${this.textShadowIntensity / 100})`;
                            ctx.shadowBlur = this.textShadowIntensity / 8;
                            ctx.shadowOffsetY = 3;
                        }

                        ctx.fillStyle = this.textColor;
                        ctx.fillText(line, cardWidth / 2, yPos);

                        ctx.shadowColor = 'transparent';
                        ctx.shadowBlur = 0;
                        ctx.shadowOffsetY = 0;
                    });
                }

                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `card-completo-${Date.now()}.png`;
                    link.href = url;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                }, 'image/png');

            } catch (err) {
                console.error('Erro:', err);
                alert('Erro ao gerar imagem do card completo.');
            }
        }
    }));
});