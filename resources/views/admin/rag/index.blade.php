@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="mb-0 text-success fw-bold">
                <i class="bi bi-robot me-2"></i> คลังความรู้ AI อัจฉริยะ (AI Knowledge Base & RAG)
            </h4>
            <small class="text-muted">ระบบอัปโหลดเอกสารระเบียบ/คู่มือ, สกัดข้อมูลย่อหน้าความรู้ (Vector) และถาม-ตอบด้วย AI แบบอ้างอิงเอกสารจริง</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#categoriesModal" onclick="loadCategoriesTable()">
                <i class="bi bi-tags-fill me-1"></i> จัดการหมวดหมู่
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#aiSettingsModal">
                <i class="bi bi-gear-fill me-1"></i> ตั้งค่า AI & Key
            </button>
            <button type="button" class="btn btn-success btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> อัปโหลดเอกสารใหม่
            </button>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card dash-card accent-1 border-0 shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">เอกสารในคลังความรู้</span>
                        <h3 class="fw-bold mb-0 text-primary mt-1">{{ number_format($totalDocs) }} <span class="fs-6 fw-normal text-muted">ไฟล์</span></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="bi bi-files fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dash-card accent-2 border-0 shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">ย่อหน้าความรู้ (Vector Chunks)</span>
                        <h3 class="fw-bold mb-0 text-success mt-1">{{ number_format($totalChunks) }} <span class="fs-6 fw-normal text-muted">ชิ้น</span></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="bi bi-layers-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dash-card accent-3 border-0 shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">ผู้ให้บริการ AI (Provider)</span>
                        <h4 class="fw-bold mb-0 text-info mt-1 text-uppercase">{{ $aiConfig['provider'] }}</h4>
                        <div class="d-flex align-items-center gap-1 mt-1">
                            @if($aiConfig['is_active'])
                                <span class="badge bg-success bg-opacity-10 text-success border small fw-bold">🟢 เปิด Copilot</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border small fw-bold">⚪ ปิดใช้งาน</span>
                            @endif
                            <small class="text-muted ms-1">{{ $aiConfig['has_key'] ? 'มี Key' : 'ไม่มี Key' }}</small>
                        </div>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="bi bi-cpu-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dash-card accent-4 border-0 shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold">โมเดล AI ที่ใช้งาน</span>
                        <div class="fw-bold text-dark mt-1 text-truncate" style="max-width: 170px;" title="{{ $aiConfig['model'] }}">
                            {{ $aiConfig['model'] }}
                        </div>
                        <small class="text-muted text-truncate d-block" style="max-width: 170px;" title="Vector: {{ $aiConfig['embed_model'] }}">
                            Embed: {{ $aiConfig['embed_model'] }}
                        </small>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="bi bi-robot fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3 Connected Contexts Banner -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0f9ff 100%); border-left: 4px solid #10b981 !important;">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white p-2 rounded-3 shadow-sm">
                    <i class="bi bi-diagram-3-fill fs-4"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">RiMS Copilot Multi-System Context Architecture (3 เสาหลัก)</h6>
                    <small class="text-muted">ผสานความรู้จากคู่มือสเปกราชการ ร่วมกับฐานข้อมูลจริงของ HOSxP และระบบบัญชี HosFin เพื่อการตรวจสอบและวิเคราะห์ที่แม่นยำ</small>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge bg-white text-success border shadow-sm px-3 py-2 rounded-pill small">
                    <i class="bi bi-book-half me-1"></i> 1. RAG ระเบียบเบิกจ่ายกองทุน
                </span>
                <span class="badge bg-white text-primary border shadow-sm px-3 py-2 rounded-pill small">
                    <i class="bi bi-hospital me-1"></i> 2. Master Data HOSxP (ค่ารักษา/กองทุน)
                </span>
                <span class="badge bg-white text-info border shadow-sm px-3 py-2 rounded-pill small">
                    <i class="bi bi-cash-stack me-1"></i> 3. งบทดลอง HosFin (11 งวด)
                </span>
            </div>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
        <span class="text-muted small fw-bold me-1"><i class="bi bi-funnel-fill me-1 text-primary"></i> หมวดหมู่:</span>
        <a href="{{ route('admin.rag.index') }}" class="badge rounded-pill text-decoration-none px-3 py-2 {{ empty($selectedCategory) ? 'bg-dark text-white' : 'bg-light text-dark border' }}">
            ทั้งหมด ({{ number_format($totalDocs) }})
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('admin.rag.index', ['category_id' => $cat->id]) }}" 
               class="badge rounded-pill text-decoration-none px-3 py-2 {{ $selectedCategory == $cat->id ? 'text-white shadow-sm' : 'bg-light text-dark border' }}"
               style="{{ $selectedCategory == $cat->id ? 'background-color: ' . $cat->color . ' !important;' : '' }}">
                <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $cat->color }};"></span>
                {{ $cat->name }} ({{ $cat->documents_count }})
            </a>
        @endforeach
    </div>

    <!-- Documents List Table Card -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-folder2-open me-2 text-primary"></i> รายการเอกสารในคลังความรู้
                </h5>
                <small class="text-muted">เอกสารทั้งหมดที่ถูกแปลงเป็น Vector สำหรับระบบ RAG</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#categoriesModal" onclick="loadCategoriesTable()">
                    <i class="bi bi-tags me-1"></i> จัดการหมวดหมู่
                </button>
                <button type="button" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มเอกสาร
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width: 5%;">#</th>
                            <th style="width: 30%;">ชื่อเอกสาร / ไฟล์</th>
                            <th style="width: 15%;">หมวดหมู่</th>
                            <th style="width: 8%;">ประเภท</th>
                            <th style="width: 10%;">ขนาด</th>
                            <th style="width: 12%;">ย่อหน้า</th>
                            <th style="width: 10%;">สถานะ</th>
                            <th class="text-end pe-4" style="width: 10%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr>
                                <td class="ps-4 fw-bold text-muted">{{ $doc->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $doc->title }}</div>
                                    <small class="text-muted font-monospace"><i class="bi bi-file-earmark me-1"></i>{{ $doc->filename }}</small>
                                </td>
                                <td>
                                    @if($doc->category)
                                        <span class="badge rounded-pill px-2.5 py-1 text-white fw-normal" style="background-color: {{ $doc->category->color }}; font-size: 0.78rem;">
                                            {{ $doc->category->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 fw-normal" style="font-size: 0.78rem;">
                                            ทั่วไป
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2.5 py-1 text-uppercase fw-bold">
                                        .{{ $doc->file_type }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ number_format($doc->file_size / 1024, 1) }} KB
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border px-2.5 py-1 rounded-pill fw-bold">
                                        {{ number_format($doc->chunk_count) }} Chunks
                                    </span>
                                </td>
                                <td>
                                    @if($doc->status === 'completed')
                                        <span class="badge bg-success rounded-pill px-2.5 py-1 text-white fw-bold">
                                            <i class="bi bi-check-circle me-1"></i> พร้อมใช้งาน
                                        </span>
                                    @elseif($doc->status === 'processing')
                                        <span class="badge bg-primary rounded-pill px-2.5 py-1 text-white fw-bold">
                                            <i class="spinner-border spinner-border-sm me-1"></i> กำลังประมวลผล
                                        </span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-2.5 py-1 text-white fw-bold" title="{{ $doc->error_message }}">
                                            <i class="bi bi-x-circle me-1"></i> ล้มเหลว
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info rounded-pill px-2.5 me-1" title="ดูย่อหน้าความรู้" onclick="viewDocChunks({{ $doc->id }}, '{{ addslashes($doc->title) }}')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning rounded-pill px-2.5 me-1" title="ประมวลผล Vector ใหม่" onclick="reindexDoc({{ $doc->id }})">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger rounded-pill px-2.5" title="ลบเอกสาร" onclick="deleteDoc({{ $doc->id }}, '{{ addslashes($doc->title) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                                    ยังไม่มีเอกสารในคลังความรู้ AI คลิกปุ่ม <strong>"อัปโหลดเอกสารใหม่"</strong> เพื่อเริ่มต้น
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($documents->hasPages())
                <div class="p-3 border-top d-flex justify-content-end">
                    {{ $documents->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Upload Document -->
<div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold" id="uploadDocModalLabel">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> อัปโหลดเอกสารเข้าคลังความรู้ AI
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadDocForm" onsubmit="handleUploadDoc(event)" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="docCategory" class="form-label fw-bold">หมวดหมู่เอกสาร</label>
                        <select class="form-select" id="docCategory" name="category_id">
                            <option value="">-- ไม่ระบุ (เอกสารทั่วไป) --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="docTitle" class="form-label fw-bold">ชื่อหัวข้อเอกสาร (ไม่บังคับ)</label>
                        <input type="text" class="form-control" id="docTitle" name="title" placeholder="เช่น คู่มือการทำงานปุ่ม Upgrade Structure 2569">
                        <small class="text-muted">หากไม่ระบุ ระบบจะใช้ชื่อไฟล์ต้นฉบับโดยอัตโนมัติ</small>
                    </div>

                    <!-- Drag & Drop Upload Zone -->
                    <div class="upload-dropzone p-4 text-center border border-2 border-dashed rounded-3 bg-light mb-3" id="dropzoneBox" onclick="chooseUploadFile()">
                        <i class="bi bi-file-earmark-arrow-up text-success display-4 mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</h6>
                        <small class="text-muted d-block mb-3">รองรับไฟล์: <strong>.pdf, .docx, .txt, .md, .json, .csv</strong> (สูงสุด 50MB)</small>
                        <input type="file" class="d-none" id="fileInput" name="document" accept=".pdf,.docx,.txt,.md,.json,.csv" required onchange="handleFileSelected(this)">
                        <button type="button" class="btn btn-outline-success btn-sm px-4 rounded-pill" onclick="event.stopPropagation(); chooseUploadFile();">
                            <i class="bi bi-folder2-open me-1"></i> เลือกไฟล์
                        </button>
                        <div id="selectedFileName" class="mt-3 fw-bold text-primary small d-none"></div>
                    </div>

                    <!-- Upload & Embedding Progress Bar -->
                    <div id="uploadProgressBox" class="d-none mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span id="uploadProgressText">กำลังอัปโหลดและประมวลผล Vector...</span>
                            <span id="uploadPercent">100%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" id="btnSubmitUpload" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">
                        <i class="bi bi-check2-circle me-1"></i> เริ่มอัปโหลดและประมวลผล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Document Chunks -->
<div class="modal fade" id="chunksModal" tabindex="-1" aria-labelledby="chunksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold text-dark" id="chunksModalLabel">
                    <i class="bi bi-layers me-2 text-primary"></i> ย่อหน้าความรู้ (Chunks) ของเอกสาร
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <div id="chunksLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">กำลังโหลดข้อมูลย่อหน้าความรู้...</p>
                </div>
                <div id="chunksListContainer" class="row g-3 d-none"></div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: AI & LLM Settings -->
<div class="modal fade" id="aiSettingsModal" tabindex="-1" aria-labelledby="aiSettingsModalLabel" aria-hidden="true">
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
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            ปรับเปลี่ยนผู้ให้บริการ AI, Key หรือสลับไปใช้ Ollama ได้ทันที ค่าจะถูกบันทึกลงตาราง <code>main_setting</code>
                        </div>
                    </div>

                    <!-- RiMS Copilot Active Switch -->
                    <div class="card border rounded-3 p-3 mb-3 bg-white shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">
                                    <i class="bi bi-power me-2 {{ $aiConfig['is_active'] ? 'text-success' : 'text-muted' }}"></i> 
                                    เปิดใช้งาน RiMS Copilot (ปุ่มหุ่นยนต์ 🤖)
                                </h6>
                                <small class="text-muted">เปิดหรือปิดการแสดงปุ่มหุ่นยนต์ลอยตัวที่มุมขวาล่างของทุกหน้าจอในระบบ</small>
                            </div>
                            <div class="form-check form-switch fs-4 mb-0">
                                <input class="form-check-input pointer" type="checkbox" role="switch" id="settingAiActive" name="ai_active" value="Y" {{ $aiConfig['is_active'] ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ผู้ให้บริการ AI (AI Provider)</label>
                            <select class="form-select" id="settingProvider" name="ai_provider" onchange="handleProviderChange(this.value)">
                                <option value="gemini" {{ $aiConfig['provider'] === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                <option value="ollama" {{ $aiConfig['provider'] === 'ollama' ? 'selected' : '' }}>Ollama</option>
                                <option value="openai_compatible" {{ $aiConfig['provider'] === 'openai_compatible' ? 'selected' : '' }}>OpenAI / DeepSeek / Custom URL</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">AI Base URL (สำหรับ Ollama)</label>
                            <input type="text" class="form-control font-monospace small" id="settingApiUrl" name="ai_api_url" value="{{ $aiConfig['api_url'] }}" placeholder="http://localhost:11434">
                        </div>

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
                            <small class="text-muted">สำหรับ Gemini ขอรับ Key ฟรีได้ที่ <a href="https://aistudio.google.com/" target="_blank" class="text-decoration-none">Google AI Studio</a></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อโมเดลตอบคำถาม (Chat Model)</label>
                            <input type="text" class="form-control font-monospace small" id="settingModelName" name="ai_model_name" value="{{ in_array($aiConfig['model'], ['gemini-1.5-flash', 'gemini-2.5-flash'], true) ? 'gemini-3.6-flash' : $aiConfig['model'] }}" placeholder="gemini-3.6-flash">
                            <div class="mt-1 d-flex gap-1 flex-wrap">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setModelPreset('gemini-3.6-flash')">⭐ gemini-3.6-flash (แนะนำ)</span>
                                <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gemini-flash-latest')">gemini-flash-latest</span>
                                <span class="badge bg-light text-dark border small" role="button" onclick="setModelPreset('gemma4:e4b')">gemma4:e4b</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">ชื่อโมเดลทำ Vector (Embedding Model)</label>
                            <input type="text" class="form-control font-monospace small" id="settingEmbedModel" name="ai_embed_model" value="{{ in_array($aiConfig['embed_model'], ['text-embedding-004', ''], true) ? 'gemini-embedding-001' : $aiConfig['embed_model'] }}" placeholder="gemini-embedding-001">
                            <div class="mt-1 d-flex gap-1 flex-wrap">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success small" role="button" onclick="setEmbedPreset('gemini-embedding-001')">⭐ gemini-embedding-001 (Google แนะนำ)</span>
                                <span class="badge bg-light text-dark border small" role="button" onclick="setEmbedPreset('nomic-embed-text')">nomic-embed-text (Ollama)</span>
                                <span class="badge bg-light text-dark border small" role="button" onclick="setEmbedPreset('bge-m3')">bge-m3</span>
                            </div>
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

<!-- Categories Management Modal -->
<div class="modal fade" id="categoriesModal" tabindex="-1" aria-labelledby="categoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="categoriesModalLabel">
                    <i class="bi bi-tags-fill me-2"></i> จัดการหมวดหมู่เอกสารคลังความรู้
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-25">
                <!-- Add / Edit Category Form -->
                <div class="card border rounded-3 p-3 mb-4 bg-white shadow-sm">
                    <h6 class="fw-bold text-dark mb-3" id="categoryFormTitle">
                        <i class="bi bi-plus-circle-fill text-success me-1"></i> เพิ่มหมวดหมู่ใหม่
                    </h6>
                    <form id="categoryForm" onsubmit="handleSaveCategory(event)">
                        <input type="hidden" id="catId" name="id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="catName" class="form-label fw-bold small text-muted">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="catName" name="name" required placeholder="เช่น ระเบียบสิทธิข้าราชการ">
                            </div>
                            <div class="col-md-3">
                                <label for="catColor" class="form-label fw-bold small text-muted">สีป้ายกำกับ (Badge)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1" id="catColor" name="color" value="#198754" title="เลือกสีหมวดหมู่" style="width: 50px; height: 38px;">
                                    <div class="d-flex gap-1">
                                        <span class="d-inline-block rounded-circle pointer border" style="width: 20px; height: 20px; background-color: #198754;" onclick="setCatColorPreset('#198754')"></span>
                                        <span class="d-inline-block rounded-circle pointer border" style="width: 20px; height: 20px; background-color: #0d6efd;" onclick="setCatColorPreset('#0d6efd')"></span>
                                        <span class="d-inline-block rounded-circle pointer border" style="width: 20px; height: 20px; background-color: #b45309;" onclick="setCatColorPreset('#b45309')"></span>
                                        <span class="d-inline-block rounded-circle pointer border" style="width: 20px; height: 20px; background-color: #6f42c1;" onclick="setCatColorPreset('#6f42c1')"></span>
                                        <span class="d-inline-block rounded-circle pointer border" style="width: 20px; height: 20px; background-color: #d97706;" onclick="setCatColorPreset('#d97706')"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="catSortOrder" class="form-label fw-bold small text-muted">ลำดับการแสดง</label>
                                <input type="number" class="form-control" id="catSortOrder" name="sort_order" value="0" min="0">
                            </div>
                            <div class="col-12">
                                <label for="catDesc" class="form-label fw-bold small text-muted">คำอธิบายหมวดหมู่ (ไม่บังคับ)</label>
                                <input type="text" class="form-control" id="catDesc" name="description" placeholder="เช่น แนวทางการเบิกจ่ายและตอบข้อซักถามกรมบัญชีกลาง">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-light btn-sm px-3 rounded-pill border d-none" id="btnCancelEditCat" onclick="resetCategoryForm()">
                                ยกเลิกแก้ไข
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold" id="btnSaveCategory">
                                <i class="bi bi-save me-1"></i> บันทึกหมวดหมู่
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Categories Table -->
                <div class="card border rounded-3 bg-white shadow-sm overflow-hidden">
                    <div class="card-header bg-light py-2 px-3 border-0 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-dark"><i class="bi bi-list-check me-1"></i> หมวดหมู่ทั้งหมดที่มีในระบบ</span>
                        <span id="catTotalBadge" class="badge bg-secondary rounded-pill">0 หมวด</span>
                    </div>
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="ps-3" style="width: 35%;">ชื่อหมวดหมู่</th>
                                    <th style="width: 35%;">คำอธิบาย</th>
                                    <th class="text-center" style="width: 15%;">เอกสาร</th>
                                    <th class="text-end pe-3" style="width: 15%;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="categoryTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">กำลังโหลดข้อมูลหมวดหมู่...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<style>
    .border-dashed {
        border-style: dashed !important;
    }
    .upload-dropzone {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .upload-dropzone:hover, .upload-dropzone.dragover {
        background-color: #e8f5e9 !important;
        border-color: #2e7d32 !important;
    }
    .hover-scale {
        transition: transform 0.2s;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
    }
    .pointer {
        cursor: pointer;
    }
</style>

<script>
    // Toggle API Key Peek
    function toggleKeyVisibility() {
        const input = document.getElementById('settingApiKey');
        const icon = document.getElementById('keyPeekIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    // Provider Presets with Auto-Matching Models
    function handleProviderChange(val) {
        const urlInput = document.getElementById('settingApiUrl');
        const modelInput = document.getElementById('settingModelName');
        const embedInput = document.getElementById('settingEmbedModel');
        const note = document.getElementById('keyRequiredNote');

        if (val === 'gemini') {
            urlInput.value = 'https://generativelanguage.googleapis.com';
            modelInput.value = 'gemini-3.6-flash';
            embedInput.value = 'gemini-embedding-001';
            note.textContent = '(จำเป็นสำหรับ Gemini)';
        } else if (val === 'ollama') {
            urlInput.value = 'http://localhost:11434';
            modelInput.value = 'gemma4:e4b';
            embedInput.value = 'nomic-embed-text';
            note.textContent = '(ไม่ต้องใช้สำหรับ Ollama)';
        } else {
            urlInput.value = 'https://api.deepseek.com/v1';
            modelInput.value = 'deepseek-chat';
            embedInput.value = 'bge-m3';
            note.textContent = '(จำเป็น)';
        }
    }

    function setModelPreset(name) {
        document.getElementById('settingModelName').value = name;
        // Auto-match embedding model based on selected chat model
        if (name.includes('gemini')) {
            document.getElementById('settingEmbedModel').value = 'gemini-embedding-001';
        } else if (name.includes('gemma') || name.includes('ollama') || name.includes('llama')) {
            document.getElementById('settingEmbedModel').value = 'nomic-embed-text';
        }
    }

    function setEmbedPreset(name) {
        document.getElementById('settingEmbedModel').value = name;
    }

    // Save AI Settings via AJAX
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
                    title: 'บันทึกสำเร็จ!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
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

    // Test AI Connection from Modal
    function handleTestAiFromModal() {
        const btn = document.getElementById('btnTestAiConnection');
        const spinner = document.getElementById('testModalSpinner');
        const icon = document.getElementById('testModalIcon');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        icon.classList.add('d-none');

        fetch('{{ route("admin.main_setting.test_ai") }}', {
            method: 'POST',
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
                            <p class="mb-2 text-muted small">โมเดล: <code>${data.model}</code></p>
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
                            <div class="bg-light p-2 border rounded-3" style="word-break: break-all;">
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
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้: ' + err });
        });
    }

    // Trigger file input click safely
    function chooseUploadFile() {
        const input = document.getElementById('fileInput');
        if (input) {
            input.click();
        }
    }

    // Handle File Selection
    function handleFileSelected(input) {
        const nameDiv = document.getElementById('selectedFileName');
        if (input.files && input.files[0]) {
            nameDiv.textContent = '📄 ไฟล์ที่เลือก: ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)';
            nameDiv.classList.remove('d-none');
        } else {
            nameDiv.classList.add('d-none');
        }
    }

    // Drag & Drop Setup
    const dropzone = document.getElementById('dropzoneBox');
    const fileInput = document.getElementById('fileInput');

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            fileInput.files = files;
            handleFileSelected(fileInput);
        }
    });

    // Handle Document Upload Form
    function handleUploadDoc(event) {
        event.preventDefault();
        const form = document.getElementById('uploadDocForm');
        const formData = new FormData(form);
        const submitBtn = document.getElementById('btnSubmitUpload');
        const progressBox = document.getElementById('uploadProgressBox');

        submitBtn.disabled = true;
        progressBox.classList.remove('d-none');

        fetch('{{ route("admin.rag.upload") }}', {
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
            progressBox.classList.add('d-none');

            if (data.success) {
                $('#uploadDocModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'อัปโหลดและประมวลผลสำเร็จ!',
                    text: data.message,
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'อัปโหลดไม่สำเร็จ',
                    text: data.message
                });
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            progressBox.classList.add('d-none');
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์เพื่ออัปโหลดไฟล์ได้: ' + err
            });
        });
    }

    // Delete Document
    function deleteDoc(id, title) {
        Swal.fire({
            title: 'ยืนยันการลบเอกสาร?',
            text: `ต้องการลบ '${title}' และย่อหน้าความรู้ทั้งหมดหรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('admin/rag-knowledge') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                    }
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
                });
            }
        });
    }

    // Re-index Document
    function reindexDoc(id) {
        Swal.fire({
            title: 'ประมวลผล Vector ใหม่?',
            text: 'ระบบจะสกัดข้อความและคำนวณพิกัด Vector ใหม่จากไฟล์ต้นฉบับ',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ดำเนินการ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังประมวลผล...',
                    text: 'กรุณารอสักครู่ ระบบกำลังสร้าง Vector ใหม่',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch(`{{ url('admin/rag-knowledge') }}/${id}/reindex`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                    }
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
                });
            }
        });
    }

    // View Document Chunks in Modal
    function viewDocChunks(id, title) {
        const modalTitle = document.getElementById('chunksModalLabel');
        const loading = document.getElementById('chunksLoading');
        const listContainer = document.getElementById('chunksListContainer');

        modalTitle.innerHTML = `<i class="bi bi-layers me-2 text-primary"></i> ย่อหน้าความรู้: <strong>${title}</strong>`;
        loading.classList.remove('d-none');
        listContainer.classList.add('d-none');
        listContainer.innerHTML = '';
        $('#chunksModal').modal('show');

        fetch(`{{ url('admin/rag-knowledge') }}/${id}/chunks`)
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            listContainer.classList.remove('d-none');

            if (data.success && data.chunks && data.chunks.length > 0) {
                data.chunks.forEach((chunk, index) => {
                    const col = document.createElement('div');
                    col.className = 'col-12';
                    const hasVector = chunk.embedding && chunk.embedding.length > 0;
                    col.innerHTML = `
                        <div class="card border rounded-3 shadow-none p-3 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">
                                    Chunk #${index + 1} ${chunk.page_number ? `(หน้า ${chunk.page_number})` : ''}
                                </span>
                                <span class="badge ${hasVector ? 'bg-success' : 'bg-warning'} rounded-pill small">
                                    ${hasVector ? '🟢 มี Vector (' + chunk.embedding.length + ' มิติ)' : '🟡 ยังไม่มี Vector'}
                                </span>
                            </div>
                            <p class="mb-0 text-dark small font-monospace" style="white-space: pre-wrap; line-height: 1.6;">${chunk.content}</p>
                        </div>
                    `;
                    listContainer.appendChild(col);
                });
            } else {
                listContainer.innerHTML = '<div class="col-12 text-center text-muted py-4">ไม่พบย่อหน้าความรู้ในเอกสารนี้</div>';
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            listContainer.classList.remove('d-none');
            listContainer.innerHTML = `<div class="col-12 text-danger text-center py-4">เกิดข้อผิดพลาดในการโหลด: ${err}</div>`;
        });
    }

    // Category Management
    function setCatColorPreset(color) {
        document.getElementById('catColor').value = color;
    }

    function resetCategoryForm() {
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catDesc').value = '';
        document.getElementById('catColor').value = '#198754';
        document.getElementById('catSortOrder').value = '0';
        document.getElementById('categoryFormTitle').innerHTML = '<i class="bi bi-plus-circle-fill text-success me-1"></i> เพิ่มหมวดหมู่ใหม่';
        document.getElementById('btnSaveCategory').innerHTML = '<i class="bi bi-save me-1"></i> บันทึกหมวดหมู่';
        document.getElementById('btnCancelEditCat').classList.add('d-none');
    }

    function editCategory(id, name, desc, color, sortOrder) {
        document.getElementById('catId').value = id;
        document.getElementById('catName').value = name;
        document.getElementById('catDesc').value = desc || '';
        document.getElementById('catColor').value = color || '#198754';
        document.getElementById('catSortOrder').value = sortOrder || 0;
        document.getElementById('categoryFormTitle').innerHTML = `<i class="bi bi-pencil-square text-primary me-1"></i> แก้ไขหมวดหมู่: <strong>${name}</strong>`;
        document.getElementById('btnSaveCategory').innerHTML = '<i class="bi bi-check2-circle me-1"></i> บันทึกการแก้ไข';
        document.getElementById('btnCancelEditCat').classList.remove('d-none');
        document.getElementById('catName').focus();
    }

    function loadCategoriesTable() {
        const tbody = document.getElementById('categoryTableBody');
        const badge = document.getElementById('catTotalBadge');

        fetch(`{{ route('admin.rag.categories.list') }}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.categories) {
                badge.textContent = `${data.categories.length} หมวด`;
                if (data.categories.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">ยังไม่มีหมวดหมู่ในระบบ</td></tr>';
                    return;
                }
                tbody.innerHTML = '';
                data.categories.forEach(cat => {
                    const tr = document.createElement('tr');
                    const descText = cat.description ? cat.description : '<span class="text-muted fst-italic">-</span>';
                    tr.innerHTML = `
                        <td class="ps-3">
                            <span class="badge rounded-pill px-2.5 py-1 text-white fw-normal" style="background-color: ${cat.color}; font-size: 0.82rem;">
                                ${cat.name}
                            </span>
                        </td>
                        <td class="small text-muted text-truncate" style="max-width: 200px;" title="${cat.description || ''}">${descText}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border rounded-pill">${cat.documents_count || 0} ไฟล์</span>
                        </td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-outline-primary btn-sm px-2 py-0 rounded-circle" title="แก้ไข" onclick="editCategory(${cat.id}, '${cat.name.replace(/'/g, "\\'")}', '${(cat.description || '').replace(/'/g, "\\'")}', '${cat.color}', ${cat.sort_order})">
                                <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-0 rounded-circle ms-1" title="ลบ" onclick="deleteCategory(${cat.id}, '${cat.name.replace(/'/g, "\\'")}')">
                                <i class="bi bi-trash-fill" style="font-size: 0.75rem;"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-danger">เกิดข้อผิดพลาด: ${err}</td></tr>`;
        });
    }

    function handleSaveCategory(e) {
        e.preventDefault();
        const form = document.getElementById('categoryForm');
        const formData = new FormData(form);

        fetch(`{{ route('admin.rag.categories.save') }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: data.message,
                    timer: 1200,
                    showConfirmButton: false
                });
                resetCategoryForm();
                loadCategoriesTable();
                // Reload page after brief pause to update filters and upload dropdown
                setTimeout(() => {
                    window.location.reload();
                }, 1300);
            } else {
                Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: data.message });
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
        });
    }

    function deleteCategory(id, name) {
        Swal.fire({
            title: 'ยืนยันการลบหมวดหมู่?',
            html: `ต้องการลบหมวดหมู่ <strong>${name}</strong> หรือไม่?<br><small class="text-muted">*เอกสารที่อยู่ในหมวดนี้จะไม่ถูกลบ แต่จะถูกเปลี่ยนเป็น "ทั่วไป"</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('admin/rag-knowledge/categories') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', text: data.message, timer: 1200, showConfirmButton: false });
                        loadCategoriesTable();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1300);
                    } else {
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message });
                    }
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: err });
                });
            }
        });
    }
</script>
@endsection
