@if(Auth::check() && Auth::user()->status === 'admin')
@php
    $hosfinConfig = [
        'provider' => \App\Services\Ai\AiService::getProvider('hosfin'),
        'api_url' => \App\Services\Ai\AiService::getApiUrl('hosfin'),
        'api_key' => \App\Services\Ai\AiService::getApiKey('hosfin'),
        'model' => \App\Services\Ai\AiService::getModelName('hosfin'),
    ];
    $ragConfig = [
        'provider' => \App\Services\Ai\AiService::getProvider('rag'),
        'api_url' => \App\Services\Ai\AiService::getApiUrl('rag'),
        'api_key' => \App\Services\Ai\AiService::getApiKey('rag'),
        'model' => \App\Services\Ai\AiService::getModelName('rag'),
        'embed_model' => \App\Services\Ai\AiService::getEmbedModel(),
    ];
    $hosxpConfig = [
        'provider' => \App\Services\Ai\AiService::getProvider('hosxp'),
        'api_url' => \App\Services\Ai\AiService::getApiUrl('hosxp'),
        'api_key' => \App\Services\Ai\AiService::getApiKey('hosxp'),
        'model' => \App\Services\Ai\AiService::getModelName('hosxp'),
    ];
    $initialScope = request()->is('*hosfin*') ? 'hosfin' : (request()->is('*hosxp*') || request()->is('*mrec*') ? 'hosxp' : 'rag');
    $aiConfig = ($initialScope === 'hosfin') ? $hosfinConfig : (($initialScope === 'hosxp') ? $hosxpConfig : $ragConfig);
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
                    <!-- Scope Switcher Tabs (HosFin vs RAG vs HOSxP) -->
                    <div class="d-flex justify-content-center mb-3">
                        <div class="p-1 bg-white rounded-pill border shadow-sm d-inline-flex gap-1" role="tablist">
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold {{ $initialScope === 'hosfin' ? 'btn-success text-white shadow-sm' : 'btn-light text-muted border-0' }}" id="btnScopeHosfin" onclick="switchModalScope('hosfin')">
                                <i class="bi bi-graph-up-arrow me-1"></i> การเงิน (HosFin)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold {{ $initialScope === 'rag' ? 'btn-primary text-white shadow-sm' : 'btn-light text-muted border-0' }}" id="btnScopeRag" onclick="switchModalScope('rag')">
                                <i class="bi bi-book-half me-1"></i> คลังความรู้ (RAG)
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold {{ $initialScope === 'hosxp' ? 'text-white shadow-sm' : 'btn-light text-muted border-0' }}" style="{{ $initialScope === 'hosxp' ? 'background-color: #6366f1;' : '' }}" id="btnScopeHosxp" onclick="switchModalScope('hosxp')">
                                <i class="bi bi-database-check me-1"></i> ตรวจสอบ HOSxP
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                        <div id="modalBannerText">
                            ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือระบุโมเดล ค่าจะบันทึกลง <code>main_setting</code> (เฉพาะ Admin)
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-muted mb-0">
                                    AI API Key 
                                    <span class="text-danger fw-normal" id="keyRequiredNote">(จำเป็นสำหรับ Gemini / DeepSeek)</span>
                                </label>
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-0 fw-bold shadow-sm" id="btnFetchModels" onclick="fetchAvailableModels(false)" title="ค้นหาและดึงโมเดลจริงจาก API">
                                    <span id="fetchModelsSpinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                                    <i class="bi bi-search me-1" id="fetchModelsIcon"></i> ค้นหาโมเดลที่ใช้งานได้
                                </button>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-key-fill text-muted"></i></span>
                                <input type="password" class="form-control font-monospace" id="settingApiKey" name="ai_api_key" value="{{ $aiConfig['api_key'] ?? '' }}" placeholder="AIzaSy...">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleKeyVisibility()">
                                    <i class="bi bi-eye" id="keyPeekIcon"></i>
                                </button>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted" id="keyHelpText">สำหรับ Gemini ขอรับ Key ฟรีได้ที่ <a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none">Google AI Studio</a></small>
                                <small id="fetchModelsStatus" class="text-success fw-bold small"></small>
                            </div>
                        </div>

                        <!-- 1. HosFin Scope: Chat Model (Shown when HosFin scope active) -->
                        <div class="col-12 {{ $initialScope === 'hosfin' ? '' : 'd-none' }}" id="wrapperHosfinModel">
                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-graph-up-arrow text-success"></i> ชื่อโมเดลวิเคราะห์การเงิน (Chat Model สำหรับ HosFin)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingModelHosfin" name="ai_model_hosfin" 
                                value="{{ in_array($hosfinConfig['model'] ?? '', ['gemini-1.5-flash', 'gemini-2.5-flash'], true) ? 'gemini-3.7-flash' : ($hosfinConfig['model'] ?? 'gemini-3.7-flash') }}" 
                                placeholder="gemini-3.7-flash">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsHosfin">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">โมเดลสำหรับวิเคราะห์งบการเงิน, บิลเจ้าหนี้ AP และลูกหนี้ AR ในหน้า HosFin</small>
                        </div>

                        <!-- 2. RAG Scope: Chat Model (Shown when RAG scope active) -->
                        <div class="col-md-6 {{ $initialScope === 'rag' ? '' : 'd-none' }}" id="wrapperRagModel">
                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-chat-dots-fill text-info"></i> ชื่อโมเดลตอบคำถาม (Chat Model)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingModelName" name="ai_model_name" 
                                value="{{ in_array($ragConfig['model'] ?? '', ['gemini-1.5-flash', 'gemini-2.5-flash'], true) ? 'gemini-3.7-flash' : ($ragConfig['model'] ?? 'gemini-3.7-flash') }}" 
                                placeholder="gemini-3.7-flash">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsRag">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">สำหรับค้นหาคู่มือ/ระเบียบในหน้า RAG Knowledge และถามทั่วไป</small>
                        </div>

                        <!-- 3. RAG Scope: Vector Embedding Model (Shown when RAG scope active) -->
                        <div class="col-md-6 {{ $initialScope === 'rag' ? '' : 'd-none' }}" id="wrapperEmbedModel">
                            <label class="form-label fw-bold small text-muted d-flex align-items-center gap-1">
                                <i class="bi bi-vector-pen text-warning"></i> ชื่อโมเดลทำ Vector (Embedding Model)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingEmbedModel" name="ai_embed_model" 
                                value="{{ in_array($ragConfig['embed_model'] ?? '', ['text-embedding-004', ''], true) ? 'gemini-embedding-001' : ($ragConfig['embed_model'] ?? 'gemini-embedding-001') }}" 
                                placeholder="gemini-embedding-001">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsEmbed">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">แปลงเอกสารเป็น Vector เพื่อการค้นหาความหมาย (Semantic Search)</small>
                        </div>

                        <!-- 4. HOSxP Scope: Chat Model (Shown when HOSxP scope active) -->
                        <div class="col-md-6 {{ $initialScope === 'hosxp' ? '' : 'd-none' }}" id="wrapperHosxpModel">
                            <label class="form-label fw-bold small text-dark d-flex align-items-center gap-1">
                                <i class="bi bi-database-check" style="color: #6366f1;"></i> ชื่อโมเดลตรวจสอบ HOSxP (Chat Model)
                            </label>
                            <input type="text" class="form-control font-monospace small" id="settingModelHosxp" name="ai_model_hosxp" 
                                value="{{ $hosxpConfig['model'] ?? 'gemini-3.7-flash' }}" 
                                placeholder="gemini-3.7-flash">
                            <div class="mt-1 d-flex gap-1 flex-wrap" id="presetsHosxp">
                                <!-- Dynamic badges inserted by JS -->
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.78rem;">โมเดลสำหรับตรวจสอบความถูกต้องของข้อมูลพื้นฐาน HOSxP (แพทย์, ค่ารักษา, สิทธิ)</small>
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
    window.aiScopesConfig = {
        hosfin: @json($hosfinConfig),
        rag: @json($ragConfig),
        hosxp: @json($hosxpConfig)
    };

    // Dynamically switch modal scope (HosFin vs RAG vs HOSxP)
    function switchModalScope(scope, syncCurrent = true) {
        if (syncCurrent && window.currentModalScope && window.aiScopesConfig && window.aiScopesConfig[window.currentModalScope]) {
            const cur = window.aiScopesConfig[window.currentModalScope];
            const provEl = document.getElementById('settingProvider');
            const urlEl = document.getElementById('settingApiUrl');
            const keyEl = document.getElementById('settingApiKey');
            if (provEl) cur.provider = provEl.value;
            if (urlEl) cur.api_url = urlEl.value;
            if (keyEl) cur.api_key = keyEl.value;
            if (window.currentModalScope === 'hosfin') {
                const hModel = document.getElementById('settingModelHosfin');
                if (hModel) cur.model = hModel.value;
            } else if (window.currentModalScope === 'hosxp') {
                const xModel = document.getElementById('settingModelHosxp');
                if (xModel) cur.model = xModel.value;
            } else {
                const rModel = document.getElementById('settingModelName');
                const eModel = document.getElementById('settingEmbedModel');
                if (rModel) cur.model = rModel.value;
                if (eModel) cur.embed_model = eModel.value;
            }
        }

        window.currentModalScope = scope;

        // Update tab buttons
        const btnHosfin = document.getElementById('btnScopeHosfin');
        const btnRag = document.getElementById('btnScopeRag');
        const btnHosxp = document.getElementById('btnScopeHosxp');
        if (btnHosfin && btnRag && btnHosxp) {
            btnHosfin.className = 'btn btn-sm rounded-pill px-3 fw-bold btn-light text-muted border-0';
            btnHosfin.style.backgroundColor = '';
            btnRag.className = 'btn btn-sm rounded-pill px-3 fw-bold btn-light text-muted border-0';
            btnRag.style.backgroundColor = '';
            btnHosxp.className = 'btn btn-sm rounded-pill px-3 fw-bold btn-light text-muted border-0';
            btnHosxp.style.backgroundColor = '';

            if (scope === 'hosfin') {
                btnHosfin.className = 'btn btn-sm rounded-pill px-3 fw-bold btn-success text-white shadow-sm';
            } else if (scope === 'hosxp') {
                btnHosxp.className = 'btn btn-sm rounded-pill px-3 fw-bold text-white shadow-sm';
                btnHosxp.style.backgroundColor = '#6366f1';
            } else {
                btnRag.className = 'btn btn-sm rounded-pill px-3 fw-bold btn-primary text-white shadow-sm';
            }
        }

        // Keep modal title clean and unified (No suffix)
        const titleEl = document.getElementById('aiSettingsModalLabel');
        if (titleEl) {
            titleEl.innerHTML = '<i class="bi bi-gear-fill me-2 text-warning"></i> ตั้งค่า AI & LLM Connection';
        }

        // Clean banner text
        const bannerEl = document.getElementById('modalBannerText');
        if (bannerEl) {
            bannerEl.innerHTML = 'ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือระบุโมเดล ค่าจะบันทึกลง <code>main_setting</code> (เฉพาะ Admin)';
        }

        // Toggle field visibility
        const hosfinEl = document.getElementById('wrapperHosfinModel');
        const ragEl = document.getElementById('wrapperRagModel');
        const embedEl = document.getElementById('wrapperEmbedModel');
        const hosxpEl = document.getElementById('wrapperHosxpModel');
        if (hosfinEl && ragEl && embedEl && hosxpEl) {
            hosfinEl.classList.add('d-none');
            ragEl.classList.add('d-none');
            embedEl.classList.add('d-none');
            hosxpEl.classList.add('d-none');

            if (scope === 'hosfin') {
                hosfinEl.classList.remove('d-none');
            } else if (scope === 'hosxp') {
                hosxpEl.classList.remove('d-none');
            } else {
                ragEl.classList.remove('d-none');
                embedEl.classList.remove('d-none');
            }
        }

        // Populate fields with scope config
        const cfg = window.aiScopesConfig ? window.aiScopesConfig[scope] : null;
        if (cfg) {
            const provEl = document.getElementById('settingProvider');
            if (provEl) {
                provEl.value = cfg.provider || 'gemini';
                handleProviderChange(provEl.value, false);
            }
            const urlEl = document.getElementById('settingApiUrl');
            if (urlEl) urlEl.value = cfg.api_url || '';
            const keyEl = document.getElementById('settingApiKey');
            if (keyEl) keyEl.value = cfg.api_key || '';

            if (scope === 'hosfin') {
                const hModelEl = document.getElementById('settingModelHosfin');
                if (hModelEl && cfg.model) hModelEl.value = cfg.model;
            } else if (scope === 'hosxp') {
                const xModelEl = document.getElementById('settingModelHosxp');
                if (xModelEl && cfg.model) xModelEl.value = cfg.model;
            } else {
                const rModelEl = document.getElementById('settingModelName');
                if (rModelEl && cfg.model) rModelEl.value = cfg.model;
                const eModelEl = document.getElementById('settingEmbedModel');
                if (eModelEl && cfg.embed_model) eModelEl.value = cfg.embed_model;
            }
        }
    }

    // Adapt modal scope helper
    function updateModalScope(scope) {
        const targetScope = scope || (window.location.pathname.includes('hosfin') ? 'hosfin' : 'rag');
        switchModalScope(targetScope, false);
    }

    // Global function to smoothly open AI Settings Modal (Admin Only)
    function openAiSettingsModal(scope) {
        const targetScope = scope || (window.location.pathname.includes('hosfin') ? 'hosfin' : 'rag');
        switchModalScope(targetScope, false);

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

            // If Gemini is active and API key exists, automatically fetch models first!
            const prov = document.getElementById('settingProvider');
            if (prov && prov.value === 'gemini') {
                fetchAvailableModels(true);
            }
        }, isHosFinModalOpen ? 300 : 0);
    }

    // Render presets dynamically matching the selected provider
    function renderPresets(provider) {
        const pHosfin = document.getElementById('presetsHosfin');
        const pRag = document.getElementById('presetsRag');
        const pEmbed = document.getElementById('presetsEmbed');
        const pHosxp = document.getElementById('presetsHosxp');

        if (provider === 'gemini') {
            if (pHosfin) {
                pHosfin.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosfinModelPreset('gemini-3.7-flash')">⭐ gemini-3.7-flash (แนะนำ เสถียรสุด)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('gemini-3.5-flash-lite')">⚡ gemini-3.5-flash-lite (ตอบไว)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosfinModelPreset('gemini-flash-latest')">gemini-flash-latest</span>
                `;
            }
            if (pRag) {
                pRag.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setModelPreset('gemini-3.7-flash')">⭐ gemini-3.7-flash (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gemini-3.5-flash-lite')">⚡ gemini-3.5-flash-lite (ตอบไว)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gemini-flash-latest')">gemini-flash-latest</span>
                `;
            }
            if (pHosxp) {
                pHosxp.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosxpModelPreset('gemini-3.7-flash')">⭐ gemini-3.7-flash (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosxpModelPreset('gemini-3.5-flash-lite')">⚡ gemini-3.5-flash-lite (ตอบไว)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosxpModelPreset('gemini-flash-latest')">gemini-flash-latest</span>
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
            if (pHosxp) {
                pHosxp.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosxpModelPreset('gemma4:e4b')">⭐ gemma4:e4b (ในเครื่อง)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosxpModelPreset('typhoon')">typhoon</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosxpModelPreset('llama3')">llama3</span>
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
            if (pHosxp) {
                pHosxp.innerHTML = `
                    <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setHosxpModelPreset('deepseek-chat')">⭐ deepseek-chat (แนะนำ)</span>
                    <span class="badge bg-light text-dark border small" role="button" onclick="setHosxpModelPreset('gpt-4o-mini')">gpt-4o-mini</span>
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
                if (!window.currentModalScope) {
                    updateModalScope();
                }
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
        const modelHosxpInput = document.getElementById('settingModelHosxp');
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
                if (modelHosfinInput) modelHosfinInput.value = 'gemini-3.7-flash';
            }
            if (isUserChange || (modelHosxpInput && !modelHosxpInput.value.toLowerCase().includes('gemini'))) {
                if (modelHosxpInput) modelHosxpInput.value = 'gemini-3.7-flash';
            }
            if (isUserChange || (modelInput && !modelInput.value.toLowerCase().includes('gemini'))) {
                if (modelInput) modelInput.value = 'gemini-3.7-flash';
            }
            if (isUserChange || (embedInput && !embedInput.value.toLowerCase().includes('gemini'))) {
                if (embedInput) embedInput.value = 'gemini-embedding-001';
            }
            if (note) note.textContent = '(จำเป็นสำหรับ Gemini)';
            if (keyHelp) keyHelp.innerHTML = 'สำหรับ Gemini ขอรับ API Key ฟรีได้ที่ <a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none fw-bold">Google AI Studio</a>';

            // Auto-fetch Gemini models when selected!
            if (isUserChange) {
                fetchAvailableModels(true);
            }
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
            if (isUserChange || (modelHosxpInput && modelHosxpInput.value.toLowerCase().includes('gemini'))) {
                if (modelHosxpInput) modelHosxpInput.value = 'gemma4:e4b';
            }
            if (isUserChange || (modelInput && modelInput.value.toLowerCase().includes('gemini'))) {
                if (modelInput) modelInput.value = 'gemma4:e4b';
            }
            if (isUserChange || (embedInput && embedInput.value.toLowerCase().includes('gemini'))) {
                if (embedInput) embedInput.value = 'nomic-embed-text';
            }
            if (note) note.textContent = '(ไม่ต้องใช้สำหรับ Ollama)';
            if (keyHelp) keyHelp.textContent = 'Ollama ทำงานแบบ Local/Offline ภายในเครื่องหรือเครือข่าย ไม่จำเป็นต้องระบุ API Key';

            if (isUserChange) {
                fetchAvailableModels(true);
            }
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
            if (isUserChange || (modelHosxpInput && (modelHosxpInput.value.includes('gemini') || modelHosxpInput.value.includes('gemma')))) {
                if (modelHosxpInput) modelHosxpInput.value = 'deepseek-chat';
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

    function setHosxpModelPreset(name) {
        const el = document.getElementById('settingModelHosxp');
        if (el) el.value = name;
    }

    function setEmbedPreset(name) {
        const el = document.getElementById('settingEmbedModel');
        if (el) el.value = name;
    }

    // Fetch available models from Google Gemini API or Ollama
    function fetchAvailableModels(isSilent = false) {
        const provider = document.getElementById('settingProvider').value;
        const apiKey = document.getElementById('settingApiKey').value;
        const apiUrl = document.getElementById('settingApiUrl').value;
        const btn = document.getElementById('btnFetchModels');
        const spinner = document.getElementById('fetchModelsSpinner');
        const icon = document.getElementById('fetchModelsIcon');
        const status = document.getElementById('fetchModelsStatus');

        if (provider === 'gemini' && !apiKey) {
            if (!isSilent && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาระบุ API Key',
                    text: 'กรุณากรอก Google Gemini API Key ก่อนเพื่อค้นหาโมเดลที่ใช้งานได้ครับ'
                });
            }
            return;
        }

        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (status) status.innerHTML = '<span class="text-muted"><i class="spinner-border spinner-border-sm me-1"></i>กำลังค้นหาโมเดล...</span>';

        fetch('{{ route("admin.main_setting.fetch_models") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ai_provider: provider,
                ai_api_key: apiKey,
                ai_api_url: apiUrl
            })
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');

            if (data.success && data.chat_models && data.chat_models.length > 0) {
                if (status) status.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>พบ ${data.chat_models.length} โมเดลพร้อมใช้</span>`;

                renderFetchedPresets(data);

                // Auto-fill with recommended model if current input is empty or has old/deprecated model
                const hosfinInput = document.getElementById('settingModelHosfin');
                const ragInput = document.getElementById('settingModelName');
                const embedInput = document.getElementById('settingEmbedModel');

                if (data.recommended_chat) {
                    if (hosfinInput && (!hosfinInput.value || hosfinInput.value.includes('1.5-flash') || hosfinInput.value.includes('2.5-flash') || hosfinInput.value.includes('3.5-flash'))) {
                        hosfinInput.value = data.recommended_chat;
                    }
                    if (ragInput && (!ragInput.value || ragInput.value.includes('1.5-flash') || ragInput.value.includes('2.5-flash') || ragInput.value.includes('3.5-flash'))) {
                        ragInput.value = data.recommended_chat;
                    }
                }
                if (data.recommended_embed && embedInput && (!embedInput.value || embedInput.value.includes('text-embedding-004'))) {
                    embedInput.value = data.recommended_embed;
                }

                if (!isSilent && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ค้นหาโมเดลสำเร็จ!',
                        html: `
                            <div class="text-start p-2 small">
                                <p class="text-success fw-bold mb-1"><i class="bi bi-stars me-1"></i> ${data.message}</p>
                                <p class="text-muted mb-2">ระบบได้เพิ่มปุ่มลัดรายชื่อโมเดลทั้งหมดที่ Key นี้เข้าถึงได้ลงในแบบฟอร์มแล้ว</p>
                                <div class="bg-light p-2 border rounded-3 mb-2">
                                    <strong>โมเดลตอบคำถามแนะนำ:</strong> <code>${data.recommended_chat}</code><br>
                                    <strong>โมเดลเวกเตอร์แนะนำ:</strong> <code>${data.recommended_embed || '-'}</code>
                                </div>
                                <small class="text-muted">*สามารถคลิกเลือกชื่อโมเดลที่ต้องการได้จากปุ่มด้านล่างช่องกรอก</small>
                            </div>
                        `
                    });
                }
            } else {
                if (status) status.innerHTML = `<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>ค้นหาไม่สำเร็จ</span>`;
                if (!isSilent && typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'ค้นหาโมเดลไม่สำเร็จ',
                        text: data.message || 'ไม่สามารถดึงรายชื่อโมเดลได้'
                    });
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            if (icon) icon.classList.remove('d-none');
            if (status) status.innerHTML = '';
            if (!isSilent && typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
            }
        });
    }

    function renderFetchedPresets(data) {
        const pHosfin = document.getElementById('presetsHosfin');
        const pRag = document.getElementById('presetsRag');
        const pEmbed = document.getElementById('presetsEmbed');

        if (data.chat_models && data.chat_models.length > 0) {
            const topModels = data.chat_models.slice(0, 8);

            const buildChips = function(isHosfin) {
                return topModels.map(m => {
                    const isRec = (m.name === data.recommended_chat);
                    const cls = isRec ? 'badge bg-success bg-opacity-10 text-success border border-success' : 'badge bg-light text-dark border';
                    const fn = isHosfin ? `setHosfinModelPreset('${m.name}')` : `setModelPreset('${m.name}')`;
                    const label = isRec ? `⭐ ${m.name} (แนะนำ)` : (m.name.includes('lite') ? `⚡ ${m.name}` : m.name);
                    return `<span class="${cls} small" role="button" onclick="${fn}" title="${m.display_name}">${label}</span>`;
                }).join(' ');
            };

            if (pHosfin) pHosfin.innerHTML = buildChips(true);
            if (pRag) pRag.innerHTML = buildChips(false);
        }

        if (data.embed_models && data.embed_models.length > 0 && pEmbed) {
            pEmbed.innerHTML = data.embed_models.map(m => {
                const isRec = (m.name === data.recommended_embed);
                const cls = isRec ? 'badge bg-success bg-opacity-10 text-success border border-success' : 'badge bg-light text-dark border';
                const label = isRec ? `⭐ ${m.name} (แนะนำ)` : m.name;
                return `<span class="${cls} small" role="button" onclick="setEmbedPreset('${m.name}')">${label}</span>`;
            }).join(' ');
        }
    }

    function handleSaveAiSettings(event) {
        event.preventDefault();
        const form = document.getElementById('aiSettingsForm');
        const formData = new FormData(form);
        const scope = window.currentModalScope || (window.location.pathname.includes('hosfin') ? 'hosfin' : 'rag');
        formData.append('scope', scope);
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
                // Update local scope cache
                if (window.aiScopesConfig && window.aiScopesConfig[scope]) {
                    window.aiScopesConfig[scope].provider = formData.get('ai_provider');
                    window.aiScopesConfig[scope].api_url = formData.get('ai_api_url');
                    window.aiScopesConfig[scope].api_key = formData.get('ai_api_key');
                    if (scope === 'hosfin') {
                        window.aiScopesConfig[scope].model = formData.get('ai_model_hosfin');
                    } else if (scope === 'hosxp') {
                        window.aiScopesConfig[scope].model = formData.get('ai_model_hosxp');
                    } else {
                        window.aiScopesConfig[scope].model = formData.get('ai_model_name');
                        window.aiScopesConfig[scope].embed_model = formData.get('ai_embed_model');
                    }
                }

                $('#aiSettingsModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกการตั้งค่า AI สำเร็จ!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    if (scope === 'hosfin' && typeof fetchHosFinAiAnalysis === 'function') {
                        // Automatically re-run HosFin AI analysis with new settings!
                        window.hosFinAnalysisLoaded = false;
                        if (window._returnToHosFinModal) {
                            window._returnToHosFinModal = false;
                            $('#hosFinAiModal').modal('show');
                        }
                        fetchHosFinAiAnalysis();
                    } else if (window.location.pathname.includes('main_setting') || window.location.pathname.includes('rag-knowledge')) {
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
