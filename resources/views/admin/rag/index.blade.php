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
            @if(Auth::check() && Auth::user()->status === 'admin')
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#aiSettingsModal">
                <i class="bi bi-gear-fill me-1"></i> ตั้งค่า AI & Key
            </button>
            @endif
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
                <button type="button" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#categoriesModal" onclick="loadCategoriesTable()">
                    <i class="bi bi-tags me-1"></i> จัดการหมวดหมู่
                </button>
                <button type="button" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มเอกสาร
                </button>
            </div>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="ragDocumentsTable" class="table table-hover table-striped align-middle mb-0 w-100" style="font-size: 0.88rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 5%;">#</th>
                            <th style="width: 28%;">ชื่อเอกสาร / ไฟล์</th>
                            <th style="width: 14%;">หมวดหมู่</th>
                            <th class="text-center" style="width: 8%;">ประเภท</th>
                            <th class="text-end" style="width: 10%;">ขนาด</th>
                            <th class="text-center" style="width: 11%;">ย่อหน้า</th>
                            <th class="text-center" style="width: 11%;">สถานะ</th>
                            <th class="text-center" style="width: 13%;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $doc->id }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $doc->title }}</div>
                                    <small class="text-muted font-monospace"><i class="bi bi-file-earmark me-1"></i>{{ $doc->filename }}</small>
                                </td>
                                <td>
                                    @if($doc->category)
                                        <span class="badge rounded-pill px-2.5 py-1 text-white fw-normal shadow-sm" style="background-color: {{ $doc->category->color }}; font-size: 0.78rem;">
                                            {{ $doc->category->name }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 fw-normal" style="font-size: 0.78rem;">
                                            ทั่วไป
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2.5 py-1 text-uppercase fw-bold">
                                        .{{ $doc->file_type }}
                                    </span>
                                </td>
                                <td class="text-end text-muted">
                                    {{ number_format($doc->file_size / 1024, 1) }} KB
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info border px-2.5 py-1 rounded-pill fw-bold">
                                        {{ number_format($doc->chunk_count) }} Chunks
                                    </span>
                                </td>
                                <td class="text-center">
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
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info rounded-pill px-2.5 me-1 shadow-sm" title="ดูย่อหน้าความรู้" onclick="viewDocChunks({{ $doc->id }}, '{{ addslashes($doc->title) }}')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning rounded-pill px-2.5 me-1 shadow-sm" title="ประมวลผล Vector ใหม่" onclick="reindexDoc({{ $doc->id }})">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger rounded-pill px-2.5 shadow-sm" title="ลบเอกสาร" onclick="deleteDoc({{ $doc->id }}, '{{ addslashes($doc->title) }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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

<!-- Modal: AI & LLM Settings (Provided globally via components.ai_settings_modal) -->


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
    /* DataTables Custom Polish */
    #ragDocumentsTable_wrapper .dataTables_filter input {
        border-radius: 20px;
        padding: 0.35rem 0.85rem;
        border: 1px solid #cbd5e1;
        outline: none;
        transition: all 0.2s;
        font-size: 0.85rem;
    }
    #ragDocumentsTable_wrapper .dataTables_filter input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.15);
    }
    #ragDocumentsTable_wrapper .dataTables_length select {
        border-radius: 8px;
        padding: 0.3rem 1.8rem 0.3rem 0.6rem;
        border: 1px solid #cbd5e1;
        font-size: 0.85rem;
    }
    #ragDocumentsTable thead th {
        font-weight: 600;
        font-size: 0.84rem;
        color: #475569;
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
</style>

<script>
    // Initialize DataTables
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $('#ragDocumentsTable').length > 0) {
            $('#ragDocumentsTable').DataTable({
                language: {
                    search: "ค้นหาเอกสาร:",
                    searchPlaceholder: "พิมพ์ชื่อเอกสาร, หมวดหมู่, ชนิดไฟล์...",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีเอกสารในระบบ",
                    infoFiltered: "(ค้นหาจากทั้งหมด _MAX_ รายการ)",
                    paginate: {
                        first: "หน้าแรก",
                        previous: "ก่อนหน้า",
                        next: "ถัดไป",
                        last: "หน้าสุดท้าย"
                    },
                    zeroRecords: '<div class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-2 opacity-50"></i>ไม่พบเอกสารที่ค้นหา</div>'
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[0, 'desc']], // Sort by ID descending
                columnDefs: [
                    { targets: [0, 3, 5, 6, 7], className: 'text-center align-middle' },
                    { targets: [4], className: 'text-end align-middle' },
                    { targets: [1, 2], className: 'align-middle' },
                    { targets: [7], orderable: false } // Actions column not sortable
                ],
                responsive: true
            });
        }
        // Auto-sync provider display on page load and modal open
        const currentProviderEl = document.getElementById('settingProvider');
        if (currentProviderEl) {
            handleProviderChange(currentProviderEl.value, false);
        }
        $('#aiSettingsModal').on('show.bs.modal', function () {
            const provEl = document.getElementById('settingProvider');
            if (provEl) {
                handleProviderChange(provEl.value, false);
            }
        });
    });

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
