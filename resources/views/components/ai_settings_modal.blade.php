@if(Auth::check() && Auth::user()->status === 'admin')
@php
    $aiConfig = [
        'provider' => \App\Services\Ai\AiService::getProvider(),
        'api_url' => \App\Services\Ai\AiService::getApiUrl(),
        'api_key' => \App\Services\Ai\AiService::getApiKey(),
        'model' => \App\Services\Ai\AiService::getModelName(),
        'model_hosfin' => \App\Services\Ai\AiService::getHosfinModelName(),
        'embed_model' => \App\Services\Ai\AiService::getEmbedModel(),
    ];
@endphp

<style>
    /* Ensure SweetAlert2 popup always displays in front of this modal and backdrops */
    .swal2-container {
        z-index: 99999 !important;
    }
</style>

<!-- Modal: AI & LLM Settings (Admin Only) -->
<div class="modal fade" id="aiSettingsModal" tabindex="-1" aria-labelledby="aiSettingsModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="aiSettingsModalLabel">
                    <i class="bi bi-gear-fill me-2 text-warning"></i> ตั้งค่า AI & LLM Connection
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="aiSettingsForm" onsubmit="handleSaveAiSettings(event)">
                @csrf
                <div class="modal-body p-4 bg-light bg-opacity-25">
                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                        <div id="modalBannerText">
                            ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือสลับไปใช้ Ollama ได้ทันที ค่าจะถูกบันทึกลงตาราง <code>main_setting</code> (เฉพาะ Admin)
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Provider Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ผู้ให้บริการ AI (AI Provider)</label>
                            <select class="form-select" id="settingProvider" name="ai_provider" onchange="handleProviderChange(this.value)">
                                <option value="gemini" {{ $aiConfig['provider'] === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                <option value="ollama" {{ $aiConfig['provider'] === 'ollama' ? 'selected' : '' }}>Ollama (Local Server)</option>
                                <option value="openai_compatible" {{ $aiConfig['provider'] === 'openai_compatible' ? 'selected' : '' }}>OpenAI / DeepSeek / Custom URL</option>
                            </select>
                        </div>

                        <!-- Base URL -->
                        <div class="col-md-6" id="wrapperApiUrl">
                            <label class="form-label fw-bold small text-muted" id="labelApiUrl">
                                AI Base URL
                                @if($aiConfig['provider'] === 'gemini')
                                    <span class="text-muted fw-normal">(Google Cloud Official API)</span>
                                @elseif($aiConfig['provider'] === 'ollama')
                                    <span class="text-primary fw-normal">(สำหรับ Ollama)</span>
                                @else
                                    <span class="text-primary fw-normal">(สำหรับ OpenAI / DeepSeek / Custom)</span>
                                @endif
                            </label>
                            <input type="text" class="form-control font-monospace small {{ $aiConfig['provider'] === 'gemini' ? 'bg-light' : '' }}" 
                                id="settingApiUrl" name="ai_api_url" 
                                value="{{ $aiConfig['api_url'] }}" 
                                placeholder="{{ $aiConfig['provider'] === 'gemini' ? 'https://generativelanguage.googleapis.com' : 'http://localhost:11434' }}"
                                {{ $aiConfig['provider'] === 'gemini' ? 'readonly' : '' }}>
                            <small class="text-muted" id="helpApiUrl">
                                @if($aiConfig['provider'] === 'gemini')
                                    <span class="text-success"><i class="bi bi-shield-check me-1"></i>เชื่อมต่อ Google Cloud Official API โดยตรง (ไม่ต้องแก้ไข)</span>
                                @elseif($aiConfig['provider'] === 'ollama')
                                    ระบุ URL ของ Ollama เช่น <code>http://localhost:11434</code> หรือ IP เครื่องในเครือข่าย
                                @else
                                    ระบุ Endpoint API เช่น <code>https://api.deepseek.com/v1</code>
                                @endif
                            </small>
                        </div>

                        <!-- API Key -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted">
                                AI API Key 
                                <span class="text-danger fw-normal" id="keyRequiredNote">(จำเป็นสำหรับ Gemini / DeepSeek)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-key-fill text-muted"></i></span>
                                <input type="password" class="form-control font-monospace" id="settingApiKey" name="ai_api_key" value="{{ $aiConfig['api_key'] ?? '' }}" placeholder="AIzaSy...">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleKeyVisibility()">
                                    <i class="bi bi-eye" id="keyPeekIcon"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="keyHelpText">สำหรับ Gemini ขอรับ Key ฟรีได้ที่ <a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none">Google AI Studio</a></small>
                        </div>

                        <!-- 1. HosFin Scope: Chat Model (Shown only when on HosFin page) -->
                        <div class="col-12 d-none" id="wrapperHosfinModel">
                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-graph-up-arrow text-success"></i> ชื่อโมเดลวิเคราะห์การเงิน (Chat Model สำหรับ HosFin)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingModelHosfin" name="ai_model_hosfin" 
                                value="{{ in_array($aiConfig['model_hosfin'], ['gemini-1.5-flash', 'gemini-2.5-flash'], true) ? 'gemini-3.6-flash' : $aiConfig['model_hosfin'] }}" 
                                placeholder="gemini-3.6-flash">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsHosfin">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">โมเดลสำหรับวิเคราะห์งบการเงิน, บิลเจ้าหนี้ AP และลูกหนี้ AR ในหน้า HosFin</small>
                        </div>

                        <!-- 2. RAG Scope: Chat Model (Shown when on RAG page or general pages) -->
                        <div class="col-md-6" id="wrapperRagModel">
                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-chat-dots-fill text-info"></i> ชื่อโมเดลตอบคำถาม (Chat Model)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingModelName" name="ai_model_name" 
                                value="{{ in_array($aiConfig['model'], ['gemini-1.5-flash', 'gemini-2.5-flash'], true) ? 'gemini-flash-latest' : $aiConfig['model'] }}" 
                                placeholder="gemini-flash-latest">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsRag">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">สำหรับค้นหาคู่มือ/ระเบียบในหน้า RAG Knowledge และถามทั่วไป</small>
                        </div>

                        <!-- 3. RAG Scope: Vector Embedding Model (Shown when on RAG page or general pages) -->
                        <div class="col-md-6" id="wrapperEmbedModel">
                            <label class="form-label fw-bold small text-muted d-flex align-items-center gap-1">
                                <i class="bi bi-vector-pen text-warning"></i> ชื่อโมเดลทำ Vector (Embedding Model)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingEmbedModel" name="ai_embed_model" 
                                value="{{ in_array($aiConfig['embed_model'], ['text-embedding-004', ''], true) ? 'gemini-embedding-001' : $aiConfig['embed_model'] }}" 
                                placeholder="gemini-embedding-001">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsEmbed">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">แปลงเอกสารเป็น Vector เพื่อการค้นหาความหมาย (Semantic Search)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info btn-sm px-3 rounded-pill fw-bold" id="btnTestAiConnection" onclick="handleTestAiFromModal()">
                        <span id="testModalSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                        <i class="bi bi-broadcast me-1" id="testModalIcon"></i> ทดสอบเชื่อมต่อทันที
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" id="btnSaveAiSettings" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">
                            <i class="bi bi-save-fill me-1"></i> บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Dynamically adapt modal title & visible inputs based on current page
    function updateModalScope(scope) {
        const isHosFin = (scope === 'hosfin' || (!scope && window.location.pathname.includes('hosfin')));
        window.currentModalScope = isHosFin ? 'hosfin' : 'rag';

        const titleEl = document.getElementById('aiSettingsModalLabel');
        const bannerEl = document.getElementById('modalBannerText');
        const hosfinEl = document.getElementById('wrapperHosfinModel');
        const ragEl = document.getElementById('wrapperRagModel');
        const embedEl = document.getElementById('wrapperEmbedModel');

        if (isHosFin) {
            if (titleEl) titleEl.innerHTML = '<i class="bi bi-gear-fill me-2 text-warning"></i> ตั้งค่า AI & LLM Connection (สำหรับ HosFin)';
            if (bannerEl) bannerEl.innerHTML = 'ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือระบุโมเดลสำหรับวิเคราะห์การเงิน <strong>HosFin</strong> ได้ทันที ค่าจะบันทึกลง <code>main_setting</code> (เฉพาะ Admin)';
            if (hosfinEl) hosfinEl.classList.remove('d-none');
            if (ragEl) ragEl.classList.add('d-none');
            if (embedEl) embedEl.classList.add('d-none');
        } else {
            if (titleEl) titleEl.innerHTML = '<i class="bi bi-gear-fill me-2 text-warning"></i> ตั้งค่า AI & LLM Connection (คลังความรู้ RAG)';
            if (bannerEl) bannerEl.innerHTML = 'ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือระบุโมเดลสำหรับ <strong>คลังความรู้ RAG</strong> ได้ทันที ค่าจะบันทึกลง <code>main_setting</code> (เฉพาะ Admin)';
            if (hosfinEl) hosfinEl.classList.add('d-none');
            if (ragEl) ragEl.classList.remove('d-none');
            if (embedEl) embedEl.classList.remove('d-none');
        }
    }

    // Global function to smoothly open AI Settings Modal (Admin Only)
    function openAiSettingsModal(scope) {
        updateModalScope(scope);

        const isHosFinModalOpen = (typeof $ !== 'undefined' && $('#hosFinAiModal').length && $('#hosFinAiModal').hasClass('show'));
        if (isHosFinModalOpen) {
            window._returnToHosFinModal = true;
            $('#hosFinAiModal').modal('hide');
        }
        setTimeout(() => {
            if (typeof $ !== 'undefined' && typeof $('#aiSettingsModal').modal === 'function') {
                $('#aiSettingsModal').modal('show');
            } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const el = document.getElementById('aiSettingsModal');
                if (el) bootstrap.Modal.getOrCreateInstance(el).show();
            }
        }, isHosFinModalOpen ? 300 : 0);
    }

    // Render presets dynamically matching the selected provider
    function renderPresets(provider) {
        const pHosfin = document.getElementById('presetsHosfin');
        const pRag = document.getElementById('presetsRag');
        const pEmbed = document.getElementById('presetsEmbed');

        if (provider === 'gemini') {
            if (pHosfin) {
                pHosfin.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosfinModelPreset('gemini-3.6-flash')">⭐ gemini-3.6-flash (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('gemini-flash-latest')">gemini-flash-latest</span>
                `;
            }
            if (pRag) {
                pRag.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setModelPreset('gemini-flash-latest')">⭐ gemini-flash-latest (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gemini-3.6-flash')">gemini-3.6-flash</span>
                `;
            }
            if (pEmbed) {
                pEmbed.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setEmbedPreset('gemini-embedding-001')">⭐ gemini-embedding-001</span>
                `;
            }
        } else if (provider === 'ollama') {
            if (pHosfin) {
                pHosfin.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosfinModelPreset('gemma4:e4b')">⭐ gemma4:e4b (ในเครื่อง)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('typhoon')">typhoon</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('llama3')">llama3</span>
                `;
            }
            if (pRag) {
                pRag.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setModelPreset('gemma4:e4b')">⭐ gemma4:e4b (ในเครื่อง)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('typhoon')">typhoon</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('llama3')">llama3</span>
                `;
            }
            if (pEmbed) {
                pEmbed.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setEmbedPreset('nomic-embed-text')">⭐ nomic-embed-text</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setEmbedPreset('bge-m3')">bge-m3</span>
                `;
            }
        } else { // openai_compatible
            if (pHosfin) {
                pHosfin.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosfinModelPreset('deepseek-chat')">⭐ deepseek-chat (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('gpt-4o-mini')">gpt-4o-mini</span>
                `;
            }
            if (pRag) {
                pRag.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setModelPreset('deepseek-chat')">⭐ deepseek-chat (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gpt-4o-mini')">gpt-4o-mini</span>
                `;
            }
            if (pEmbed) {
                pEmbed.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setEmbedPreset('bge-m3')">⭐ bge-m3</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setEmbedPreset('text-embedding-3-small')">text-embedding-3-small</span>
                `;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateModalScope();
        const provEl = document.getElementById('settingProvider');
        if (provEl) {
            handleProviderChange(provEl.value, false);
        }
        if (typeof $ !== 'undefined') {
            $('#aiSettingsModal').on('show.bs.modal', function () {
                updateModalScope();
                const pEl = document.getElementById('settingProvider');
                if (pEl) {
                    handleProviderChange(pEl.value, false);
                }
            });
            $('#aiSettingsModal').on('hidden.bs.modal', function () {
                if (window._returnToHosFinModal) {
                    window._returnToHosFinModal = false;
                    if (typeof $('#hosFinAiModal').modal === 'function') {
                        $('#hosFinAiModal').modal('show');
                    }
                }
            });
        }
    });

    function toggleKeyVisibility() {
        const input = document.getElementById('settingApiKey');
        const icon = document.getElementById('keyPeekIcon');
        if (!input || !icon) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    function handleProviderChange(val, isUserChange = true) {
        const urlInput = document.getElementById('settingApiUrl');
        const urlLabel = document.getElementById('labelApiUrl');
        const urlHelp = document.getElementById('helpApiUrl');
        const modelInput = document.getElementById('settingModelName');
        const modelHosfinInput = document.getElementById('settingModelHosfin');
        const embedInput = document.getElementById('settingEmbedModel');
        const note = document.getElementById('keyRequiredNote');
        const keyHelp = document.getElementById('keyHelpText');

        renderPresets(val);

        if (val === 'gemini') {
            if (urlLabel) urlLabel.innerHTML = 'AI Base URL <span class="text-muted fw-normal">(Google Cloud Official API)</span>';
            if (urlHelp) urlHelp.innerHTML = '<span class="text-success"><i class="bi bi-shield-check me-1"></i>เชื่อมต่อ Google Official Cloud API โดยตรง (ไม่ต้องแก้ไข)</span>';
            if (urlInput) {
                urlInput.placeholder = 'https://generativelanguage.googleapis.com';
                urlInput.readOnly = true;
                urlInput.classList.add('bg-light');
                if (isUserChange || !urlInput.value || urlInput.value.includes('localhost:11434')) {
                    urlInput.value = 'https://generativelanguage.googleapis.com';
                }
            }
            if (isUserChange || (modelHosfinInput && !modelHosfinInput.value.toLowerCase().includes('gemini'))) {
                if (modelHosfinInput) modelHosfinInput.value = 'gemini-3.6-flash';
            }
            if (isUserChange || (modelInput && !modelInput.value.toLowerCase().includes('gemini'))) {
                if (modelInput) modelInput.value = 'gemini-flash-latest';
            }
            if (isUserChange || (embedInput && !embedInput.value.toLowerCase().includes('gemini'))) {
                if (embedInput) embedInput.value = 'gemini-embedding-001';
            }
            if (note) note.textContent = '(จำเป็นสำหรับ Gemini)';
            if (keyHelp) keyHelp.innerHTML = 'สำหรับ Gemini ขอรับ API Key ฟรีได้ที่ <a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none fw-bold">Google AI Studio</a>';
        } else if (val === 'ollama') {
            if (urlLabel) urlLabel.innerHTML = 'AI Base URL <span class="text-primary fw-normal">(สำหรับ Ollama Local Server)</span>';
            if (urlHelp) urlHelp.innerHTML = 'ระบุ URL ของ Ollama เช่น <code>http://localhost:11434</code> หรือ IP เครื่องในเครือข่าย';
            if (urlInput) {
                urlInput.placeholder = 'http://localhost:11434';
                urlInput.readOnly = false;
                urlInput.classList.remove('bg-light');
                if (isUserChange || !urlInput.value || urlInput.value.includes('googleapis.com')) {
                    urlInput.value = 'http://localhost:11434';
                }
            }
            // If currently holding a cloud Gemini model name, auto-switch to Ollama model
            if (isUserChange || (modelHosfinInput && modelHosfinInput.value.toLowerCase().includes('gemini'))) {
                if (modelHosfinInput) modelHosfinInput.value = 'gemma4:e4b';
            }
            if (isUserChange || (modelInput && modelInput.value.toLowerCase().includes('gemini'))) {
                if (modelInput) modelInput.value = 'gemma4:e4b';
            }
            if (isUserChange || (embedInput && embedInput.value.toLowerCase().includes('gemini'))) {
                if (embedInput) embedInput.value = 'nomic-embed-text';
            }
            if (note) note.textContent = '(ไม่ต้องใช้สำหรับ Ollama)';
            if (keyHelp) keyHelp.textContent = 'Ollama ทำงานแบบ Local/Offline ภายในเครื่องหรือเครือข่าย ไม่จำเป็นต้องระบุ API Key';
        } else { // openai_compatible
            if (urlLabel) urlLabel.innerHTML = 'AI Base URL <span class="text-primary fw-normal">(สำหรับ OpenAI / DeepSeek / Custom)</span>';
            if (urlHelp) urlHelp.innerHTML = 'ระบุ Endpoint API เช่น <code>https://api.deepseek.com/v1</code>';
            if (urlInput) {
                urlInput.placeholder = 'https://api.deepseek.com/v1';
                urlInput.readOnly = false;
                urlInput.classList.remove('bg-light');
                if (isUserChange || !urlInput.value || urlInput.value.includes('localhost:11434') || urlInput.value.includes('googleapis.com')) {
                    urlInput.value = 'https://api.deepseek.com/v1';
                }
            }
            if (isUserChange || (modelHosfinInput && (modelHosfinInput.value.includes('gemini') || modelHosfinInput.value.includes('gemma')))) {
                if (modelHosfinInput) modelHosfinInput.value = 'deepseek-chat';
            }
            if (isUserChange || (modelInput && (modelInput.value.includes('gemini') || modelInput.value.includes('gemma')))) {
                if (modelInput) modelInput.value = 'deepseek-chat';
            }
            if (isUserChange || (embedInput && (embedInput.value.includes('gemini') || embedInput.value.includes('nomic')))) {
                if (embedInput) embedInput.value = 'bge-m3';
            }
            if (note) note.textContent = '(จำเป็น)';
            if (keyHelp) keyHelp.textContent = 'ระบุ API Key ของผู้ให้บริการ เช่น DeepSeek หรือ OpenAI';
        }
    }

    function setModelPreset(name) {
        const el = document.getElementById('settingModelName');
        if (el) el.value = name;
    }

    function setHosfinModelPreset(name) {
        const el = document.getElementById('settingModelHosfin');
        if (el) el.value = name;
    }

    function setEmbedPreset(name) {
        const el = document.getElementById('settingEmbedModel');
        if (el) el.value = name;
    }

    function handleSaveAiSettings(event) {
        event.preventDefault();
        const form = document.getElementById('aiSettingsForm');
        const formData = new FormData(form);
        const submitBtn = document.getElementById('btnSaveAiSettings');

        submitBtn.disabled = true;

        fetch('{{ route("admin.rag.settings.save") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            if (data.success) {
                $('#aiSettingsModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกการตั้งค่า AI สำเร็จ!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    if (typeof fetchHosFinAiAnalysis === 'function') {
                        // Automatically re-run HosFin AI analysis with new settings!
                        window.hosFinAnalysisLoaded = false;
                        if (window._returnToHosFinModal) {
                            window._returnToHosFinModal = false;
                            $('#hosFinAiModal').modal('show');
                        }
                        fetchHosFinAiAnalysis();
                    } else if (window.location.pathname.includes('rag-knowledge')) {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: data.message });
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
        });
    }

    function handleTestAiFromModal() {
        const btn = document.getElementById('btnTestAiConnection');
        const spinner = document.getElementById('testModalSpinner');
        const icon = document.getElementById('testModalIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        const form = document.getElementById('aiSettingsForm');
        const formData = form ? new FormData(form) : new FormData();
        formData.append('scope', window.currentModalScope || (window.location.pathname.includes('hosfin') ? 'hosfin' : 'rag'));

        fetch('{{ route("admin.main_setting.test_ai") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'เชื่อมต่อ AI สำเร็จ!',
                    html: `
                        <div class="text-start p-2">
                            <p class="mb-1 text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ผู้ให้บริการ: <strong>${data.provider}</strong></p>
                            <p class="mb-2 text-muted small">โมเดลที่ตอบ: <code>${data.model}</code></p>
                            <div class="bg-light p-3 small border rounded-3 text-dark">
                                <strong>การตอบกลับ:</strong><br>
                                "${data.response}"
                            </div>
                        </div>
                    `
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อไม่สำเร็จ',
                    html: `
                        <div class="text-start p-2 text-danger small">
                            <p class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> ข้อความแจ้งเตือน:</p>
                            <div class="bg-light p-2 border rounded-3 text-dark" style="word-break: break-all;">
                                ${data.message || data.error}
                            </div>
                            <small class="text-muted mt-2 d-block">*หากเพิ่งแก้ไข Key หรือ Provider อย่าลืมกด <strong>"บันทึกการตั้งค่า"</strong> ก่อนทดสอบครับ</small>
                        </div>
                    `
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ', text: err });
        });
    }
</script>
@else
<script>
    // Fallback for non-admin users
    function openAiSettingsModal() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'เฉพาะผู้ดูแลระบบ (Admin)',
                text: 'การตั้งค่า AI & LLM Connection ต้องใช้สิทธิ์ผู้ดูแลระบบ (Admin) เท่านั้น กรุณาติดต่อผู้ดูแลระบบ',
                confirmButtonColor: '#4f46e5'
            });
        } else {
            alert('การตั้งค่า AI & LLM Connection ต้องใช้สิทธิ์ผู้ดูแลระบบ (Admin) เท่านั้น');
        }
    }
</script>
@endif
