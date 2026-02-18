<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kyar Code | QR code generator</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= URL_BASE ?>/assets/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" crossorigin="anonymous"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;800&display=swap" rel="stylesheet">

    <script src="<?= URL_BASE ?>/assets/script.js"></script>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#0a0e1a] text-slate-200 min-h-screen flex flex-col lg:flex-row overflow-x-hidden" x-data="qrApp()">

    <div class="lg:hidden fixed top-0 left-0 right-0 bg-[#0f1419] z-30 border-b border-blue-900/30 shadow-2xl">
        <div class="p-4 pb-2">
            <h1 class="text-xl font-extrabold bg-gradient-to-r from-blue-400 to-blue-600 bg-clip-text text-transparent text-center">Kyar Code</h1>
            <p class="text-[8px] text-slate-500 uppercase tracking-widest font-bold text-center">Personalize como desejar</p>
        </div>
        
        <div class="flex items-center justify-center pb-4 px-4">
            <div class="relative glass rounded-3xl shadow-xl p-4" style="max-width: 280px; width: 100%;">
                <div class="qr-stage relative" :class="selectedEffect === '3d' ? 'qr-stage-3d-bg' : ''" :style="'border-radius: ' + bgBorderRadius + 'px; padding: 20px; min-height: 240px;'">
                    <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-20 rounded-[20px]">
                        <div class="w-6 h-6 border-4 border-blue-600 border-t-transparent rounded-full" style="animation: spinGlow 1s linear infinite;"></div>
                    </div>
                    <template x-if="qrImage && selectedEffect === '3d'">
                        <div class="qr-3d-wrapper" style="--card-size: 200px;" @mousemove="handle3DMove($event)" @mouseleave="reset3D($event)" @touchmove="handle3DMove($event)" @touchend="reset3D($event)" @touchstart="hideTouchHint()">
                            <div class="qr-shadow"></div>
                            <div class="qr-3d" id="qrCard" :style="'border-radius: ' + bgBorderRadius + 'px; background-color: ' + bg + ';'">
                                <img :src="qrImage" crossorigin="anonymous" class="w-full h-auto">
                                <template x-if="logoBase64 !== null && logoBase64 !== '' && logoBase64 !== undefined">
                                    <div class="qr-logo-overlay" :style="'width: ' + logoSize + '%; height: ' + logoSize + '%;'">
                                        <img :src="logoBase64" alt="Logo">
                                    </div>
                                </template>
                            </div>
                            <div id="touchHint" x-show="showTouchHint" class="touch-hint lg:hidden">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 11V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v0"></path>
                                    <path d="M14 10V4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v2"></path>
                                    <path d="M10 10.5V6a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v8"></path>
                                    <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15"></path>
                                </svg>
                            </div>
                        </div>
                    </template>
                    <template x-if="qrImage && selectedEffect !== '3d'">
                        <div class="flex flex-col justify-center items-center overflow-visible qr-container-wrapper" style="position: relative;">
                            <img :src="qrImage"
                                id="qr-to-print"
                                crossorigin="anonymous"
                                class="qr-image-2d transition-all duration-500"
                                :class="selectedEffect === 'glow' ? 'glow-neon-effect' : ''"
                                :style="selectedEffect === 'frame' ? 'border-radius: ' + bgBorderRadius + 'px; --neon-color: ' + glowColor + '; max-width: 200px; width: 100%; height: auto;' : 'border-radius: ' + bgBorderRadius + 'px; --neon-color: ' + glowColor + '; max-width: 200px; width: 100%; height: auto;'">
                            <template x-if="logoBase64 !== null && logoBase64 !== '' && logoBase64 !== undefined">
                                <div class="qr-logo-overlay" :style="'width: ' + (logoSize * (selectedEffect === 'frame' ? 1.4 : 3.5)) + 'px; height: ' + (logoSize * (selectedEffect === 'frame' ? 1.4 : 3.5)) + 'px;'">
                                    <img :src="logoBase64" alt="Logo">
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <p x-show="customText" 
                   id="customTextElement"
                   class="mt-4 text-slate-500 text-[8px] tracking-[0.3em] uppercase font-black italic text-center"
                   :style="'color: ' + textColor + '; font-size: ' + (textSize * 0.2) + 'px; text-shadow: 0 2px ' + (textShadowIntensity / 10) + 'px ' + textShadowColor + (textGlow > 0 ? ', 0 0 ' + (textGlow / 5) + 'px ' + textColor : '') + '; word-wrap: break-word; max-width: 100%; white-space: pre-line;'"
                   x-html="customText.replace(/\n/g, '<br>')"></p>
            </div>
        </div>
    </div>

    <aside class="w-full lg:w-96 bg-[#0f1419] p-6 border-r border-blue-900/30 shadow-2xl z-20 lg:overflow-y-auto pt-[420px] lg:pt-6" style="animation: slideInLeft 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); max-height: 100vh;">
        <div class="hidden lg:block mb-8" style="animation: fadeInDown 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <h1 class="text-2xl font-extrabold bg-gradient-to-r from-blue-400 to-blue-600 bg-clip-text text-transparent" style="animation: titleGlow 3s ease-in-out infinite;">QR Pro 3D</h1>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold" style="animation: fadeIn 1s ease-out 0.3s both;">Design Premium Ativo</p>
        </div>

        <div class="space-y-6 lg:overflow-y-auto lg:pr-2" style="max-height: calc(100vh - 140px); scrollbar-width: thin;">
            <div style="animation: fadeInUp 0.6s ease-out 0.1s both;">
                <label class="block text-sm font-bold mb-2 text-blue-300" style="transition: all 0.3s ease;">1. URL do Link</label>
                <input type="text" x-model="text" @input.debounce.1000ms="generateQR()"
                    class="w-full bg-slate-900/50 border border-blue-900/50 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Digite a URL aqui...">
            </div>

            <div style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">2. Efeitos Especiais</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="selectEffect('none')" :class="selectedEffect === 'none' ? 'btn-active border-slate-400' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.3s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                        NENHUM
                    </button>
                    <button @click="selectEffect('3d')" :class="selectedEffect === '3d' ? 'btn-active border-blue-500' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.4s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        EFEITO 3D
                    </button>
                    <button @click="selectEffect('glow')" :class="selectedEffect === 'glow' ? 'btn-active border-blue-400' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.5s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                        GLOW NEON
                    </button>
                    <button @click="selectEffect('frame')" :class="selectedEffect === 'frame' ? 'btn-active border-cyan-500' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.6s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="10" height="10"></rect></svg>
                        MOLDURA
                    </button>
                </div>
            </div>

            <template x-if="selectedEffect === '3d'">
                <div x-transition class="settings-panel bg-slate-900/50 p-4 rounded-xl border border-blue-500/30 space-y-4" style="animation: expandIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    <div>
                        <div class="flex justify-between mb-2">
                            <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Intensidade da Sombra</label>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="shadowIntensity + '%'"></span>
                        </div>
                        <input type="range" x-model.number="shadowIntensity" min="0" max="100" step="5" @input="updateVisuals()" class="slider-base">
                    </div>
                    <div>
                        <div class="flex justify-between mb-2">
                            <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Intensidade do Brilho (Gloss)</label>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="glowIntensity + '%'"></span>
                        </div>
                        <input type="range" x-model.number="glowIntensity" min="0" max="100" step="5" @input="updateVisuals()" class="slider-base">
                    </div>
                </div>
            </template>

            <template x-if="selectedEffect === 'glow'">
                <div x-transition class="settings-panel bg-slate-900/50 p-4 rounded-xl border border-blue-400/30 space-y-3" style="animation: expandIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    <div>
                        <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider block mb-2 no-shine">Cor do Brilho</label>
                        <input type="color" x-model="glowColor" class="w-full h-8 bg-transparent cursor-pointer">
                    </div>
                    <div>
                        <div class="flex justify-between mb-2">
                            <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Intensidade do Brilho</label>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="glowIntensity + '%'"></span>
                        </div>
                        <input type="range" x-model.number="glowIntensity" min="30" max="100" step="5" @input="updateVisuals()" class="slider-base">
                    </div>
                </div>
            </template>

            <template x-if="selectedEffect === 'frame'">
                <div x-transition class="settings-panel bg-slate-900/50 p-4 rounded-xl border border-cyan-500/30 space-y-3" style="animation: expandIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    <div>
                        <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider block mb-2 no-shine">Tipo de Moldura</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button @click="frameType = 'naruto'; generateQR()" :class="frameType === 'naruto' ? 'btn-active border-blue-500' : ''" class="btn-opt text-[9px]">NARUTO</button>
                            <button @click="frameType = 'batman'; generateQR()" :class="frameType === 'batman' ? 'btn-active border-blue-500' : ''" class="btn-opt text-[9px]">BATMAN</button>
                            <button @click="frameType = 'onepiece'; generateQR()" :class="frameType === 'onepiece' ? 'btn-active border-blue-500' : ''" class="btn-opt text-[9px]">ONE PIECE</button>
                        </div>
                    </div>
                </div>
            </template>

            <div style="animation: fadeInUp 0.6s ease-out 0.7s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">3. Padrão dos Módulos</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="moduleStyle = 'square'; generateQR()" :class="moduleStyle === 'square' ? 'btn-active border-blue-500' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.7s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        QUADRADO
                    </button>
                    <button @click="moduleStyle = 'rounded'; generateQR()" :class="moduleStyle === 'rounded' ? 'btn-active border-blue-500' : ''" class="btn-opt flex items-center justify-center gap-2" style="animation: bounceIn 0.6s ease-out 0.75s both;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/></svg>
                        REDONDO
                    </button>
                </div>
            </div>

            <div style="animation: fadeInUp 0.6s ease-out 0.9s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">4. Arredondamento</label>
                <div class="settings-panel bg-slate-900/50 p-4 rounded-xl border border-blue-900/50" style="transition: all 0.3s ease;">
                    <div class="flex justify-between mb-2">
                        <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Borda</label>
                        <span class="text-[10px] text-slate-400 font-mono" x-text="bgBorderRadius + 'px'"></span>
                    </div>
                    <input type="range" x-model.number="bgBorderRadius" min="0" max="120" step="5" class="slider-base">
                </div>
            </div>

            <div style="animation: fadeInUp 0.6s ease-out 0.95s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">5. Texto Personalizado</label>
                <div class="settings-panel bg-slate-900/50 p-4 rounded-xl border border-blue-900/50 space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider block mb-2 no-shine">Mensagem</label>
                        <textarea x-model="customText" 
                            placeholder="Ex: Meu QR Code&#10;Linha 2&#10;Linha 3" maxlength="255" rows="3"
                            class="w-full bg-slate-900/50 border border-blue-900/50 rounded-xl p-3 text-sm outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                        <p class="text-[9px] text-slate-500 mt-1 uppercase tracking-wider">Aparecerá abaixo do QR Code (pressione Enter para quebrar linha)</p>
                    </div>
                    
                    <template x-if="customText">
                        <div x-transition class="space-y-3 pt-2 border-t border-blue-900/30">
                            <div>
                                <div class="flex justify-between mb-2">
                                    <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Tamanho da Fonte</label>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="textSize + 'px'"></span>
                                </div>
                                <input type="range" x-model.number="textSize" min="24" max="72" step="2" class="slider-base">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-900 p-2 rounded-lg border border-blue-900/50 text-center">
                                    <label class="text-[9px] uppercase text-slate-500 block mb-1">Cor do Texto</label>
                                    <input type="color" x-model="textColor" class="w-full h-8 bg-transparent cursor-pointer">
                                </div>
                                <div class="bg-slate-900 p-2 rounded-lg border border-blue-900/50 text-center">
                                    <label class="text-[9px] uppercase text-slate-500 block mb-1">Cor da Sombra</label>
                                    <input type="color" x-model="textShadowColor" class="w-full h-8 bg-transparent cursor-pointer">
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-2">
                                    <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Intensidade da Sombra</label>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="textShadowIntensity + '%'"></span>
                                </div>
                                <input type="range" x-model.number="textShadowIntensity" min="0" max="100" step="10" class="slider-base">
                            </div>
                            
                            <div>
                                <div class="flex justify-between mb-2">
                                    <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Brilho (Glow)</label>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="textGlow + '%'"></span>
                                </div>
                                <input type="range" x-model.number="textGlow" min="0" max="100" step="10" class="slider-base">
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div style="animation: fadeInUp 0.6s ease-out 1s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">6. Cores do QR</label>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-900 p-2 rounded-lg border border-blue-900/50 text-center" style="animation: bounceIn 0.6s ease-out 0.8s both;">
                        <label class="text-[9px] uppercase text-slate-500 block mb-1">Módulos</label>
                        <input type="color" x-model="color" @change="generateQR()" class="w-full h-8 bg-transparent cursor-pointer">
                    </div>
                    <div class="bg-slate-900 p-2 rounded-lg border border-blue-900/50 text-center" style="animation: bounceIn 0.6s ease-out 0.9s both;">
                        <label class="text-[9px] uppercase text-slate-500 block mb-1">Fundo</label>
                        <input type="color" x-model="bg" @change="generateQR()" class="w-full h-8 bg-transparent cursor-pointer">
                    </div>
                </div>
                <p class="text-[9px] text-amber-400/80 mt-2 uppercase tracking-wider">Dica: Use módulos escuros e fundo claro para melhor leitura</p>
            </div>

            <div style="animation: fadeInUp 0.6s ease-out 1.05s both;">
                <label class="block text-sm font-bold mb-3 text-blue-300">7. Logo Central</label>
                <input type="file" id="logoInput" class="hidden" @change="handleFileSelect" accept="image/*">
                <button @click="document.getElementById('logoInput').click()"
                    class="upload-btn w-full border-2 border-dashed border-blue-900/50 rounded-xl p-4 text-center hover:border-blue-500 transition-all flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span class="text-xs text-slate-400" x-text="logoBase64 ? 'Trocar Logo' : 'Upload & Cortar'"></span>
                </button>
                <p class="text-[9px] text-amber-400/80 mt-2 uppercase tracking-wider">Teste o tamanho da logo: muito grande pode dificultar a leitura do QR</p>

                <template x-if="logoBase64">
                    <div class="mt-4 bg-slate-900/50 p-4 rounded-xl border border-blue-900/50">
                        <div class="flex items-center justify-center mb-4">
                            <div class="logo-preview-circle">
                                <img :src="logoBase64" alt="Logo Preview" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <div class="flex justify-between mb-2">
                            <label class="text-[10px] font-bold text-blue-300 uppercase tracking-wider no-shine">Tamanho da Logo</label>
                            <span class="text-[10px] text-slate-400 font-mono" x-text="logoSize + '%'"></span>
                        </div>
                        <input type="range" x-model.number="logoSize" min="10" :max="selectedEffect === 'frame' ? 25 : 32" step="1" @change="generateQR()" class="slider-base">
                        <button @click="logoBase64 = null; generateQR()" class="w-full mt-4 text-[10px] text-red-400 uppercase font-bold text-center hover:bg-red-500/10 rounded-lg py-2 transition-all">Remover Logo</button>
                    </div>
                </template>
            </div>

            <div class="grid grid-cols-1 gap-3" style="animation: fadeInUp 0.6s ease-out 1.1s both;">
                <button @click="downloadQR()" class="download-btn w-full bg-slate-700 hover:bg-slate-600 py-3 rounded-xl font-bold text-xs shadow-xl">
                    Baixar apenas QR Code
                </button>
                <button @click="downloadFullCard()" class="download-btn w-full bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-500 hover:to-blue-700 py-4 rounded-xl font-bold shadow-xl" style="animation: pulseGlow 2s ease-in-out infinite;">
                    Baixar Cartão Completo
                </button>
            </div>
        </div>
    </aside>

    <main class="hidden lg:flex flex-1 items-center justify-center p-4 lg:p-6 relative overflow-hidden min-h-[400px] lg:min-h-screen" style="animation: fadeIn 0.8s ease-out;">
    <div class="absolute w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[120px]" style="animation: floatingOrb 8s ease-in-out infinite;"></div>
    <div class="absolute w-[400px] h-[400px] bg-blue-800/10 rounded-full blur-[100px]" style="animation: floatingOrb 10s ease-in-out infinite reverse; animation-delay: 2s;"></div>
    <div class="relative z-10 glass rounded-[60px] shadow-2xl text-center transition-all duration-500"
        id="glassCard"
        :class="selectedEffect === 'frame' && ['naruto', 'batman', 'onepiece'].includes(frameType) ? 'p-6' : 'p-10'"
        :style="selectedEffect === 'frame' && ['naruto', 'batman', 'onepiece'].includes(frameType) ? 'max-width: 850px; width: 90vw;' : 'max-width: 600px; width: 100%;'">
        <div class="qr-stage relative" :class="selectedEffect === '3d' ? 'qr-stage-3d-bg' : ''" :style="'border-radius: ' + bgBorderRadius + 'px; padding: ' + (selectedEffect === 'frame' && ['naruto', 'batman', 'onepiece'].includes(frameType) ? '15px' : '60px 20px') + ';'">
            <div x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-20 rounded-[40px]" style="animation: fadeIn 0.3s ease-out;">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full" style="animation: spinGlow 1s linear infinite;"></div>
            </div>
            <template x-if="qrImage && selectedEffect === '3d'">
                <div class="qr-3d-wrapper" @mousemove="handle3DMove($event)" @mouseleave="reset3D($event)" @touchmove="handle3DMove($event)" @touchend="reset3D($event)">
                    <div class="qr-shadow"></div>
                    <div class="qr-3d" id="qrCard" :style="'border-radius: ' + bgBorderRadius + 'px; background-color: ' + bg + ';'">
                        <img :src="qrImage" crossorigin="anonymous" class="w-full h-auto">
                        <template x-if="logoBase64 !== null && logoBase64 !== '' && logoBase64 !== undefined">
                            <div class="qr-logo-overlay" :style="'width: ' + logoSize + '%; height: ' + logoSize + '%;'">
                                <img :src="logoBase64" alt="Logo">
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="qrImage && selectedEffect !== '3d'">
                <div class="flex flex-col justify-center items-center overflow-visible qr-container-wrapper" style="position: relative;">
                    <img :src="qrImage"
                        id="qr-to-print"
                        crossorigin="anonymous"
                        class="qr-image-2d transition-all duration-500"
                        :class="selectedEffect === 'glow' ? 'glow-neon-effect' : ''"
                        :style="selectedEffect === 'frame' ? 'border-radius: ' + bgBorderRadius + 'px; --neon-color: ' + glowColor + '; max-width: 550px; width: 100%; height: auto;' : 'border-radius: ' + bgBorderRadius + 'px; --neon-color: ' + glowColor + '; max-width: 350px; width: 100%; height: auto;'">
                    <template x-if="logoBase64 !== null && logoBase64 !== '' && logoBase64 !== undefined">
                        <div class="qr-logo-overlay" :style="'width: ' + (logoSize * (selectedEffect === 'frame' ? 2.24 : 3.5)) + 'px; height: ' + (logoSize * (selectedEffect === 'frame' ? 2.24 : 3.5)) + 'px;'">
                            <img :src="logoBase64" alt="Logo">
                        </div>
                    </template>
                </div>
            </template>
        </div>
        <p x-show="customText" 
           id="customTextElement"
           class="mt-8 text-slate-500 text-[10px] tracking-[0.4em] uppercase font-black italic text-center"
           :style="'color: ' + textColor + '; font-size: ' + (textSize * 0.28) + 'px; text-shadow: 0 2px ' + (textShadowIntensity / 10) + 'px ' + textShadowColor + (textGlow > 0 ? ', 0 0 ' + (textGlow / 5) + 'px ' + textColor : '') + '; word-wrap: break-word; max-width: 100%; white-space: pre-line;'"
           x-html="customText.replace(/\n/g, '<br>')"></p>
    </div>
</main>

    <div id="cropperModal" class="modal-overlay hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-sm">
        <div class="modal-content bg-slate-800 rounded-3xl p-6 max-w-2xl w-full border border-blue-500/30 shadow-2xl">
            <h3 class="text-lg font-bold mb-2 text-blue-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"></circle><circle cx="6" cy="18" r="3"></circle><line x1="20" y1="4" x2="8.12" y2="15.88"></line><line x1="14.47" y1="14.48" x2="20" y2="20"></line><line x1="8.12" y1="8.12" x2="12" y2="12"></line></svg>
                Ajuste sua Logo
            </h3>
            <p class="text-xs text-slate-400 mb-4 flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"></path><path d="M13 13l6 6"></path></svg>
                    Arraste para mover
                </span>
                <span class="flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                    Scroll para zoom
                </span>
            </p>
            <div class="max-h-[500px] overflow-hidden rounded-xl bg-black border-2 border-blue-500/20">
                <img id="imageToCrop" class="max-w-full">
            </div>
            <div class="flex gap-3 mt-6">
                <button @click="closeCropper()" class="modal-btn flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 rounded-xl font-bold transition-all">Cancelar</button>
                <button @click="applyCrop()" class="modal-btn flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-500 hover:to-blue-700 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Aplicar
                </button>
            </div>
        </div>
    </div>

    <div x-show="showColorPicker" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-sm lg:hidden">
        <div class="bg-slate-800 rounded-3xl p-6 max-w-sm w-full border border-blue-500/30 shadow-2xl">
            <h3 class="text-lg font-bold mb-4 text-blue-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
                Escolher Cor
            </h3>
            
            <div class="mb-6 flex items-center justify-center">
                <div class="w-24 h-24 rounded-2xl border-4 border-white shadow-xl" 
                     :style="'background-color: ' + currentPickerColor"></div>
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-slate-400 mb-2 block">Matiz</label>
                <input type="range" 
                       x-model.number="pickerHue" 
                       min="0" 
                       max="360" 
                       step="1"
                       class="w-full hue-slider"
                       style="background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);">
            </div>
            
            <div class="mb-4">
                <label class="text-xs text-slate-400 mb-2 block">Saturação: <span x-text="pickerSaturation + '%'"></span></label>
                <input type="range" 
                       x-model.number="pickerSaturation" 
                       min="0" 
                       max="100" 
                       step="1"
                       class="slider-base w-full">
            </div>
            
            <div class="mb-6">
                <label class="text-xs text-slate-400 mb-2 block">Brilho: <span x-text="pickerValue + '%'"></span></label>
                <input type="range" 
                       x-model.number="pickerValue" 
                       min="0" 
                       max="100" 
                       step="1"
                       class="slider-base w-full">
            </div>
            
            <div class="flex gap-3">
                <button @click="closeMobileColorPicker()" 
                        class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 rounded-xl font-bold transition-all">
                    Cancelar
                </button>
                <button @click="applyMobileColor()" 
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-500 hover:to-blue-700 rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Aplicar
                </button>
            </div>
        </div>
    </div>

</body>

</html>