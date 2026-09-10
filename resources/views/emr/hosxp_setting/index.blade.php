@extends('layouts.app')

@section('content')
<style>
    /* Main Setting Style Navigation & Layout */
    .sticky-setting-sidebar {
        position: sticky;
        top: 85px;
        z-index: 10;
    }
    .setting-nav-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.7rem 0.85rem;
        margin-bottom: 0.4rem;
        border: none;
        background: transparent;
        border-radius: 0.75rem;
        text-align: left;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        color: #334155;
        text-decoration: none;
    }
    .setting-nav-btn:hover {
        background-color: #f1f5f9;
        color: #0f172a;
        transform: translateX(3px);
    }
    .setting-nav-btn.active {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(13, 110, 253, 0.3);
    }
    .setting-nav-btn.active .setting-nav-icon {
        background-color: rgba(255, 255, 255, 0.22) !important;
        color: #ffffff !important;
    }
    .setting-nav-btn.active .setting-nav-title {
        color: #ffffff !important;
        font-weight: 700;
    }
    .setting-nav-btn.active .setting-nav-sub {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    .setting-nav-btn.active .setting-nav-badge {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border: none !important;
    }
    .setting-nav-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 1.1rem;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }
    .setting-nav-title {
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        margin-bottom: 2px;
    }
    .setting-nav-sub {
        font-size: 0.725rem;
        color: #64748b;
        line-height: 1.2;
    }
    .setting-nav-badge {
        font-size: 0.725rem;
        padding: 0.25rem 0.6rem;
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        flex-shrink: 0;
    }
    .tab-kpi-card {
        border-radius: 1rem;
        background: #ffffff;
        transition: all 0.2s ease;
    }
    .tab-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08) !important;
    }
    .data-table-modern th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.85rem 1rem;
    }
    .data-table-modern td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.875rem;
    }
    .data-table-modern tbody tr:hover {
        background-color: #f8fafc;
    }
    .code-badge {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        padding: 0.15rem 0.45rem;
        font-size: 0.8rem;
    }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.08) !important; color: #198754 !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.08) !important; color: #dc3545 !important; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.08) !important; color: #ffc107 !important; }
    .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.08) !important; color: #6c757d !important; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.08) !important; color: #0d6efd !important; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.08) !important; color: #0dcaf0 !important; }
    .text-warning-dark { color: #a1770b !important; }
    .text-info-dark { color: #087990 !important; }
    .cursor-pointer { cursor: pointer; }

    /* Doctor Tab-Card styles matching check/doctor */
    .tab-card-item {
        border: 2px solid transparent !important;
        transition: all 0.2s ease-in-out;
        opacity: 0.75;
        cursor: pointer;
    }
    .tab-card-item:hover {
        opacity: 0.95;
        transform: translateY(-2px);
    }
    .nav-link.active .tab-card-item {
        opacity: 1 !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08) !important;
    }
    .nav-link.active .bg-success-soft { border: 2px solid #198754 !important; }
    .nav-link.active .bg-secondary-soft { border: 2px solid #6c757d !important; }
    .nav-link.active .bg-danger-soft { border: 2px solid #dc3545 !important; }
    .nav-link.active .bg-warning-soft { border: 2px solid #b58105 !important; }
</style>

<div class="container-fluid mt-4 px-lg-4">
    <!-- Page Header (Clean, styled like Main Setting) -->
    <div class="page-header-box mb-4 p-3 p-md-4 rounded-4 bg-white shadow-sm border w-100 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="p-2.5 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                <i class="bi bi-server fs-4"></i>
            </span>
            <div>
                <h5 class="mb-1 text-dark fw-bold">
                    ข้อมูลพื้นฐาน HOSxP
                </h5>
                <small class="text-muted">ตรวจสอบความถูกต้องและครบถ้วนของข้อมูลแพทย์, ค่ารักษาพยาบาล (nondrugitems) และสิทธิการรักษา (pttype, สิทธิ สปสช)</small>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2 small">
                <i class="bi bi-clock-history text-primary me-1"></i> เชื่อมต่อ HOSxP MySQL Live
            </span>
        </div>
    </div>

    @if(!$hosxpAlive)
        <div class="alert alert-danger rounded-4 p-4 mb-4 shadow-sm d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-2 text-danger"></i>
            <div>
                <h5 class="fw-bold mb-1">ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ได้</h5>
                <p class="mb-2 text-muted">{{ $errorMessage ?? 'กรุณาตรวจสอบการตั้งค่า Connection hosxp ใน config/database.php และไฟล์ .env' }}</p>
            </div>
        </div>
    @else
        <!-- Main Layout: Left Sidebar + Right Tab Content -->
        <div class="row g-4 mb-5">
            <!-- Left Sidebar (3 Tabs) -->
            <div class="col-xl-3 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-setting-sidebar bg-white overflow-hidden">
                    <!-- Sidebar Header -->
                    <div class="p-3 border-bottom bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small text-uppercase tracking-wider">
                                <i class="bi bi-grid-fill me-1 text-primary"></i> หมวดหมู่ข้อมูลพื้นฐาน
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small">
                                4 หมวด
                            </span>
                        </div>
                    </div>

                    <!-- Category Nav List -->
                    <div class="p-2">
                        <div class="nav flex-column nav-pills" role="tablist">
                            <!-- Tab 1: Doctor -->
                            <a href="{{ route('emr.hosxp_setting', ['tab' => 'doctor']) }}" 
                               class="nav-link setting-nav-btn {{ $activeTab === 'doctor' ? 'active' : '' }}">
                                <div class="d-flex align-items-center text-truncate me-2">
                                    <span class="setting-nav-icon bg-primary-subtle text-primary me-2.5">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="setting-nav-title text-truncate">แพทย์และบุคลากร</div>
                                        <div class="setting-nav-sub text-truncate">เลข ว., สภาวิชาชีพ, เลข 13 หลัก</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ number_format($stats['doctor']['active'] ?? 0) }}
                                </span>
                            </a>

                            <!-- Tab 2: Nondrugitems -->
                            <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems']) }}" 
                               class="nav-link setting-nav-btn {{ $activeTab === 'nondrugitems' ? 'active' : '' }}">
                                <div class="d-flex align-items-center text-truncate me-2">
                                    <span class="setting-nav-icon bg-success-subtle text-success me-2.5">
                                        <i class="bi bi-capsule-pill"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="setting-nav-title text-truncate">ค่ารักษาพยาบาล</div>
                                        <div class="setting-nav-sub text-truncate">หมวดรายได้, รหัสมาตรฐาน ADP</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ number_format($stats['nondrugitems']['active'] ?? 0) }}
                                </span>
                            </a>

                            <!-- Tab 3: Pttype -->
                            <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype']) }}" 
                               class="nav-link setting-nav-btn {{ $activeTab === 'pttype' ? 'active' : '' }}">
                                <div class="d-flex align-items-center text-truncate me-2">
                                    <span class="setting-nav-icon me-2.5" style="background-color: #ede9fe; color: #7c3aed;">
                                        <i class="bi bi-shield-check"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="setting-nav-title text-truncate">สิทธิการรักษา HOSxP</div>
                                        <div class="setting-nav-sub text-truncate">ตาราง pttype, ตรวจสอบ PROVIS</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ number_format($stats['pttype']['active'] ?? 0) }}
                                </span>
                            </a>

                            <!-- Tab 4: NHSO Subinscl -->
                            <a href="{{ route('emr.hosxp_setting', ['tab' => 'nhso_subinscl']) }}" 
                               class="nav-link setting-nav-btn {{ $activeTab === 'nhso_subinscl' ? 'active' : '' }}">
                                <div class="d-flex align-items-center text-truncate me-2">
                                    <span class="setting-nav-icon me-2.5" style="background-color: #e0f2fe; color: #0284c7;">
                                        <i class="bi bi-person-vcard-fill"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="setting-nav-title text-truncate">สิทธิการรักษา สปสช.</div>
                                        <div class="setting-nav-sub text-truncate">เปรียบเทียบรหัส สปสช กับ HOSxP</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ number_format($stats['nhso_subinscl']['total'] ?? 0) }}
                                </span>
                            </a>
                        </div>
                    </div>

                    <!-- Sidebar Footer Notice -->
                    <div class="p-3 m-2 mt-0 bg-light rounded-3 border">
                        <div class="small fw-bold text-dark mb-1">
                            <i class="bi bi-shield-check text-primary me-1"></i> ความพร้อมก่อนส่งออก
                        </div>
                        <div class="text-muted" style="font-size: 0.78rem; line-height: 1.45;">
                            ตรวจสอบข้อมูลให้ถูกต้องครบถ้วนเพื่อความพร้อมในการส่งออกข้อมูล 43 แฟ้ม และการส่งเคลม FDH MOPH / e-Claim
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Content Area -->
            <div class="col-xl-9 col-lg-8">
                @if($activeTab === 'doctor')
                    <!-- Card Tabs matching check/doctor -->
                    <ul class="nav nav-tabs row g-3 border-0 mb-4" id="doctorTabs" role="tablist">
                        <li class="nav-item col-md-3" role="presentation">
                            <button class="nav-link w-100 active p-0 border-0 bg-transparent" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tab-pane" type="button" role="tab" aria-controls="active-tab-pane" aria-selected="true">
                                <div class="card border-0 shadow-sm rounded-4 bg-success-soft text-success p-3 text-start transition-card tab-card-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-check-fill fs-2 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold">แพทย์ที่เปิดใช้งาน</h6>
                                            <h4 class="mb-0 fw-bold">{{ count($activeDocs ?? []) }} <span class="fs-6 fw-normal">ราย</span></h4>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item col-md-3" role="presentation">
                            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive-tab-pane" type="button" role="tab" aria-controls="inactive-tab-pane" aria-selected="false">
                                <div class="card border-0 shadow-sm rounded-4 bg-secondary-soft text-secondary p-3 text-start transition-card tab-card-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-x-fill fs-2 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold">แพทย์ที่ปิดใช้งาน</h6>
                                            <h4 class="mb-0 fw-bold">{{ count($inactiveDocs ?? []) }} <span class="fs-6 fw-normal">ราย</span></h4>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item col-md-3" role="presentation">
                            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="invalid-license-tab" data-bs-toggle="tab" data-bs-target="#invalid-license-tab-pane" type="button" role="tab" aria-controls="invalid-license-tab-pane" aria-selected="false">
                                <div class="card border-0 shadow-sm rounded-4 bg-danger-soft text-danger p-3 text-start transition-card tab-card-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-card-heading fs-2 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold">เลขใบประกอบฯ ไม่ถูกต้อง</h6>
                                            <h4 class="mb-0 fw-bold">{{ count($invalidLicenseDocs ?? []) }} <span class="fs-6 fw-normal">ราย</span></h4>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item col-md-3" role="presentation">
                            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="invalid-cid-tab" data-bs-toggle="tab" data-bs-target="#invalid-cid-tab-pane" type="button" role="tab" aria-controls="invalid-cid-tab-pane" aria-selected="false">
                                <div class="card border-0 shadow-sm rounded-4 bg-warning-soft text-warning-dark p-3 text-start transition-card tab-card-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card-2-front fs-2 me-3"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold">เลขบัตรประชาชนไม่ถูกต้อง</h6>
                                            <h4 class="mb-0 fw-bold">{{ count($invalidCidDocs ?? []) }} <span class="fs-6 fw-normal">ราย</span></h4>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </li>
                    </ul>

                    <!-- Doctor Tab Contents -->
                    <div class="tab-content" id="doctorTabsContent">
                        @foreach($doctorTabConfigs as $tab)
                        <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['id'] }}" role="tabpanel" aria-labelledby="{{ str_replace('-pane', '', $tab['id']) }}">
                            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold text-primary">
                                        <i class="bi bi-table me-2"></i> {{ $tab['title'] }} (จำนวน {{ count($tab['data']) }} รายการ)
                                    </h6>
                                </div>
                                <div class="card-body p-4 pt-0">
                                    <div class="table-responsive">
                                        <table id="{{ $tab['table_id'] }}" class="table table-modern w-100 align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 8%;">รหัส</th>
                                                    <th class="text-start" style="width: 20%;">ชื่อ - สกุล</th>
                                                    <th class="text-start" style="width: 12%;">ตำแหน่ง</th>
                                                    <th class="text-center" style="width: 13%;">เลขใบประกอบวิชาชีพ</th>  
                                                    <th class="text-center" style="width: 13%;">เลขบัตรประชาชน</th>
                                                    <th class="text-center" style="width: 10%;">สภาวิชาชีพ</th>
                                                    <th class="text-center" style="width: 6%;">เพศ</th>
                                                    <th class="text-center" style="width: 8%;">วันเกิด</th>
                                                    <th class="text-center" style="width: 7%;">สถานะ</th>
                                                    <th class="text-center" style="width: 70px;">ผลการตรวจสอบ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tab['data'] as $row)
                                                    @php
                                                        $lic = trim($row->licenseno ?? '');
                                                        $isLicValid = $row->is_lic_valid ?? false;
                                                        $cid = trim($row->cid ?? '');
                                                        $isCidValid = $row->is_cid_valid ?? false;
                                                        $doc_errors = $row->doc_errors ?? [];

                                                        $councilMap = [
                                                            '01' => 'แพทยสภา',
                                                            '02' => 'สภาการพยาบาล',
                                                            '03' => 'สภาเภสัชกรรม',
                                                            '04' => 'ทันตแพทยสภา',
                                                            '05' => 'สภากายภาพบำบัด',
                                                            '06' => 'สภาเทคนิคการแพทย์',
                                                            '07' => 'สัตวแพทยสภา',
                                                            '08' => 'สภาการแพทย์แผนไทย'
                                                        ];
                                                        $councilName = !empty($row->council_code) && isset($councilMap[$row->council_code]) ? $councilMap[$row->council_code] : null;

                                                        if (empty($doc_errors)) {
                                                            $orderVal = 2;
                                                            $statusSearchText = 'ปกติ สมบูรณ์ ผ่าน';
                                                            $statusHtml = '<span class="d-none">2</span><span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px;" title="ข้อมูลปกติ / สมบูรณ์"><i class="bi bi-eye-fill fs-6"></i></span>';
                                                        } else {
                                                            $orderVal = 1;
                                                            $statusSearchText = 'ผิดพลาด ข้อผิดพลาด ไม่ผ่าน ' . implode(' ', $doc_errors);
                                                            $docDetails = [
                                                                'เลขใบอนุญาต' => $lic ?: 'ไม่ได้ระบุ',
                                                                'เลขบัตรประชาชน' => $cid ?: 'ไม่ได้ระบุ',
                                                                'ตำแหน่ง' => $row->position_name ?? ($row->position ?? '-'),
                                                                'สาขาความเชี่ยวชาญ' => $row->spclty_name ?? '-',
                                                                'คลินิกประจำ' => $row->clinic_name ?? '-',
                                                                'สภาวิชาชีพ' => $councilName ?: ($row->council_code ?? '-'),
                                                                'สถานะ' => (($row->active ?? '') === 'Y') ? 'Active (เปิดใช้งาน)' : 'Inactive'
                                                            ];
                                                            $statusHtml = '<span class="d-none">1</span><button type="button" class="btn btn-outline-danger p-0 rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs btn-open-validation" style="width: 32px; height: 32px;" '
                                                                . 'data-category="แพทย์และบุคลากร (Doctor)" '
                                                                . 'data-code="'.e($row->code ?? '').'" '
                                                                . 'data-name="'.e($row->name ?? '').'" '
                                                                . 'data-errors="'.e(json_encode($doc_errors)).'" '
                                                                . 'data-details="'.e(json_encode($docDetails)).'" '
                                                                . 'title="พบข้อผิดพลาด (คลิกดูสาเหตุ)">'
                                                                . '<i class="bi bi-eye-fill fs-6"></i></button>';
                                                        }

                                                        $sexText = '-';
                                                        if (($row->sex ?? '') == '1') {
                                                            $sexText = 'ชาย';
                                                        } elseif (($row->sex ?? '') == '2') {
                                                            $sexText = 'หญิง';
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td class="text-center fw-bold text-muted">{{ $row->code }}</td>
                                                        <td class="text-start font-medium text-dark">{{ $row->name }}</td>
                                                        <td class="text-start small text-dark">{{ $row->position_name ?? '-' }}</td>
                                                        <td class="text-center">
                                                            @if (empty($lic))
                                                                <span class="text-danger small fw-bold">ไม่มีข้อมูล</span>
                                                            @elseif (!$isLicValid)
                                                                <span class="text-danger fw-bold" title="รูปแบบผิดกฎ (S15)">{{ $lic }} <i class="bi bi-x-circle-fill ms-1"></i></span>
                                                            @else
                                                                <span class="fw-bold text-success">{{ $lic }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center text-muted small">
                                                            @if (empty($cid))
                                                                <span class="text-warning small">ไม่มีข้อมูล</span>
                                                            @elseif (!$isCidValid)
                                                                <span class="text-danger" title="ความยาวไม่ใช่ 13 หลัก">{{ $cid }} <i class="bi bi-x-circle-fill ms-1"></i></span>
                                                            @else
                                                                {{ $cid }}
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if (empty($row->council_code))
                                                                <span class="text-muted small">-</span>
                                                            @else
                                                                <span class="badge bg-light text-dark border small" title="{{ $row->council_code }}{{ $councilName ? ' - ' . $councilName : '' }}">
                                                                    {{ $row->council_code }}{{ $councilName ? ' - ' . $councilName : '' }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center small">{{ $sexText }}</td>
                                                        <td class="text-center text-muted small">
                                                            {{ !empty($row->birth_date) && function_exists('DateThai') ? DateThai($row->birth_date) : ($row->birth_date ?? '-') }}
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($row->active === 'Y')
                                                                <span class="badge bg-success-soft text-success rounded-pill px-2">Active</span>
                                                            @else
                                                                <span class="badge bg-secondary-soft text-secondary rounded-pill px-2">Inactive</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center" data-order="{{ $orderVal }}" data-sort="{{ $orderVal }}" data-search="{{ $statusSearchText }}">{!! $statusHtml !!}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    @if($activeTab === 'nondrugitems')
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'all']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card {{ $filter === 'all' ? 'border-primary ring-1' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">รายการทั้งหมด</span>
                                            <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-card-list"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-dark mb-1">{{ number_format($stats['nondrugitems']['total'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">หมวดค่ารักษาในฐานข้อมูล</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'active']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-success {{ $filter === 'active' ? 'border-success' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">เปิดใช้งาน (Active)</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check2"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-success mb-1">{{ number_format($stats['nondrugitems']['active'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">รายการที่มีสถานะใช้งาน</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'mapped_adp']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-info {{ $filter === 'mapped_adp' ? 'border-info' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ผูกรหัส ADP แล้ว</span>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill"><i class="bi bi-link-45deg"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-info mb-1">{{ number_format($stats['nondrugitems']['mapped_adp'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">พร้อมส่งเคลม e-Claim/FDH</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'missing_adp']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-danger {{ $filter === 'missing_adp' ? 'border-danger' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ขาดรหัส ADP</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-exclamation-octagon"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-danger mb-1">{{ number_format($stats['nondrugitems']['missing_adp'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">ยังไม่ระบุ nhso_adp_code</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @elseif($activeTab === 'pttype')
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'all']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card {{ $filter === 'all' ? 'border-primary ring-1' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">สิทธิทั้งหมด</span>
                                            <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-shield"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-dark mb-1">{{ number_format($stats['pttype']['total'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">สิทธิการรักษาในระบบ HOSxP</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'active']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-success {{ $filter === 'active' ? 'border-success' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">เปิดใช้งาน (Active)</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check2"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-success mb-1">{{ number_format($stats['pttype']['active'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">สิทธิที่เปิดให้ลงทะเบียน</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'inactive']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-secondary {{ $filter === 'inactive' ? 'border-secondary' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ปิดใช้งาน (Inactive)</span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill"><i class="bi bi-eye-slash"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-secondary mb-1">{{ number_format($stats['pttype']['inactive'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">สิทธิที่ปิดการใช้งาน</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'invalid']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-danger {{ $filter === 'invalid' ? 'border-danger' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">พบข้อผิดพลาด</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-exclamation-octagon-fill"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-danger mb-1">{{ number_format($stats['pttype']['invalid'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">รหัสไม่ตรง/ไม่ผูกมาตรฐาน</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @elseif($activeTab === 'nhso_subinscl')
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nhso_subinscl', 'filter' => 'all']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card {{ $filter === 'all' ? 'border-primary ring-1' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">สิทธิ สปสช ทั้งหมด</span>
                                            <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-list-ul"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-dark mb-1">{{ number_format($stats['nhso_subinscl']['total'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">รหัสมาตรฐาน INSCL สปสช</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nhso_subinscl', 'filter' => 'found']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-success {{ $filter === 'found' ? 'border-success' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">พบที่ HOSxP</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><i class="bi bi-check-circle-fill"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-success mb-1">{{ number_format($stats['nhso_subinscl']['found'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">มีรหัสตรงกับตาราง pttype</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'nhso_subinscl', 'filter' => 'notfound']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-danger {{ $filter === 'notfound' ? 'border-danger' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ไม่พบที่ HOSxP</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-x-circle-fill"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-danger mb-1">{{ number_format($stats['nhso_subinscl']['notfound'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">ยังไม่มีในตาราง pttype</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-info">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small fw-semibold">อัตราความครอบคลุม</span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill"><i class="bi bi-percent"></i></span>
                                    </div>
                                    <h3 class="fw-bold text-info-dark mb-1">{{ number_format($stats['nhso_subinscl']['match_rate'] ?? 0, 1) }}%</h3>
                                    <div class="text-muted" style="font-size: 0.75rem;">สัดส่วนสิทธิที่พบในระบบ</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- 2. MAIN DATA TABLE CARD FOR NONDRUGITEMS & PTTYPE -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                        <!-- Filter Toolbar -->
                        <div class="p-3 px-4 bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <!-- Filter Pills -->
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="small text-muted me-2 fw-semibold">กรองข้อมูล:</span>
                                @if($activeTab === 'nondrugitems')
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'all']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">ทั้งหมด</a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'active']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-success text-white' : 'btn-light text-muted' }}">เปิดใช้งาน (Active)</a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'mapped_adp']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'mapped_adp' ? 'btn-info text-white' : 'btn-light text-muted' }}">
                                       <i class="bi bi-link-45deg me-1"></i>ผูกรหัส ADP แล้ว ({{ number_format($stats['nondrugitems']['mapped_adp']) }})
                                    </a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'nondrugitems', 'filter' => 'missing_adp']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_adp' ? 'btn-danger text-white fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-exclamation-circle me-1"></i>ยังไม่ผูกรหัส ADP ({{ $stats['nondrugitems']['missing_adp'] }})
                                    </a>
                                @elseif($activeTab === 'pttype')
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'active']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-success text-white fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-check-circle me-1"></i>เปิดใช้งาน (Active) ({{ number_format($stats['pttype']['active'] ?? 0) }})
                                    </a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'inactive']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'inactive' ? 'btn-secondary text-white fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-eye-slash me-1"></i>ปิดใช้งาน ({{ number_format($stats['pttype']['inactive'] ?? 0) }})
                                    </a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'invalid']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'invalid' ? 'btn-danger text-white fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-exclamation-octagon me-1"></i>พบข้อผิดพลาด ({{ number_format($stats['pttype']['invalid'] ?? 0) }})
                                    </a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'all']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">
                                       ทั้งหมด ({{ number_format($stats['pttype']['total'] ?? 0) }})
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Table Content Area -->
                        <div class="card-body p-4 pt-3">
                            <div class="table-responsive">
                                @if($activeTab === 'nondrugitems')
                                    {{-- 2. Nondrugitems Table --}}
                                    <table id="table-nondrugitems" class="table data-table-modern w-100 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 100px;">รหัส icode</th>
                                                <th class="text-start">ชื่อรายการค่ารักษาพยาบาล</th>
                                                <th class="text-start" style="width: 180px;">หมวดรายได้</th>
                                                <th class="text-center" style="width: 130px;">รหัส ADP Code</th>
                                                <th class="text-center" style="width: 90px;">ADP Type</th>
                                                <th class="text-end" style="width: 110px;">ราคา OPD</th>
                                                <th class="text-center" style="width: 95px;">สถานะ</th>
                                                <th class="text-center" style="width: 70px;">ผลการตรวจสอบ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($records as $item)
                                                <tr>
                                                    <td class="text-center"><span class="code-badge fw-bold">{{ $item->icode }}</span></td>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $item->name }}</div>
                                                        @if(!empty($item->billcode))
                                                            <small class="text-muted">Billcode: <code>{{ $item->billcode }}</code></small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border">
                                                            [{{ $item->income }}] {{ $item->income_name ?? 'ไม่ระบุชื่อหมวด' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if(!empty($item->nhso_adp_code))
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success font-monospace fs-7">{{ $item->nhso_adp_code }}</span>
                                                        @else
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">⚠️ ยังไม่ได้ใส่ ADP</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if(!empty($item->nhso_adp_type_id))
                                                            <small class="text-muted">{{ $item->nhso_adp_type_id }}</small>
                                                        @else
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-dark">
                                                        {{ number_format($item->price, 2) }} บ.
                                                    </td>
                                                    <td class="text-center">
                                                        @if($item->istatus === 'Y')
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center" data-order="{{ $item->is_valid ? 2 : 1 }}" data-sort="{{ $item->is_valid ? 2 : 1 }}" data-search="{{ $item->is_valid ? 'ปกติ สมบูรณ์ ผ่าน' : 'ผิดพลาด ข้อผิดพลาด ไม่ผ่าน ' . implode(' ', $item->item_errors ?? []) }}">
                                                        @if($item->is_valid)
                                                            <span class="d-none">2</span>
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px;" title="ข้อมูลปกติ / สมบูรณ์">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </span>
                                                        @else
                                                            <span class="d-none">1</span>
                                                            @php
                                                                $itemDetails = [
                                                                    'หมวดรายได้' => ($item->income_name ?? '') ?: (($item->income ?? '') ?: 'ไม่ได้ระบุ'),
                                                                    'ราคา OPD' => number_format($item->price ?? 0, 2) . ' บ.',
                                                                    'รหัส ADP' => ($item->nhso_adp_code ?? '') ?: 'ยังไม่ผูก',
                                                                    'ADP Type' => ($item->nhso_adp_type_id ?? '') ?: '-',
                                                                    'สถานะ' => (($item->istatus ?? '') === 'Y') ? 'Active (เปิดใช้งาน)' : 'Inactive'
                                                                ];
                                                            @endphp
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger p-0 rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs btn-open-validation"
                                                                    style="width: 32px; height: 32px;"
                                                                    data-category="ค่ารักษาพยาบาล"
                                                                    data-code="{{ $item->icode }}"
                                                                    data-name="{{ $item->name }}"
                                                                    data-errors='@json($item->item_errors ?? [])'
                                                                    data-details='@json($itemDetails)'
                                                                    title="พบข้อผิดพลาด (คลิกดูสาเหตุ)">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                @elseif($activeTab === 'pttype')
                                    {{-- 3. Pttype Table (สิทธิการรักษา HOSxP) --}}
                                    @php
                                        $validCodes = [
                                            'UCS', 'WEL', 'OFC', 'LGO', 'SSS', 'STP', 'NHS', 'BKK', 'BMT', 'SRT', 'KKT', 'PTY',
                                            'A1', 'CSH', 'A9', 'INS', 'GOF', 'NRD', 'NRH', 'SSI', 'PVT', 'FWF'
                                        ];
                                    @endphp
                                    <table id="table-pttype" class="table data-table-modern w-100 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center" colspan="7">ตาราง pttype (HOSxP)</th>
                                                <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ตาราง PROVIS_INSTYPE</th>                
                                                <th class="text-center" style="background-color: #f5f5f5; width: 75px;">ตรวจสอบ</th>
                                            </tr>
                                            <tr>
                                                <th class="text-center" style="width: 75px;">สปสช</th>  
                                                <th class="text-center" style="width: 65px;">รหัส</th>
                                                <th class="text-start">ชื่อสิทธิ</th>  
                                                <th class="text-start" style="width: 140px;">ประเภท</th>     
                                                <th class="text-center" style="width: 70px;">Eclaim</th>
                                                <th class="text-center" style="width: 85px;">Hipdata</th> 
                                                <th class="text-start" style="width: 110px;">กลุ่มราคา</th>
                                                <th class="text-start" style="background-color: #f0f9ff">ชื่อสิทธิ</th>
                                                <th class="text-center" style="background-color: #f0f9ff; width: 85px;">รหัสส่งออก</th>
                                                <th class="text-center" style="background-color: #fafafa; width: 75px;">ผลการตรวจ</th>
                                            </tr>
                                        </thead> 
                                        <tbody> 
                                            @foreach($records as $row) 
                                                <tr>
                                                    <td class="text-center">
                                                        @if(!empty($row->nhso_subinscl))
                                                            <span class="badge bg-light text-dark border">{{ $row->nhso_subinscl }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td> 
                                                    <td class="text-center fw-bold"><span class="code-badge fw-bold">{{ $row->pttype }}</span></td>                                
                                                    <td class="text-start">
                                                        <div class="fw-bold text-dark">{{ $row->name }}</div>
                                                    </td>            
                                                    <td class="text-start small text-muted">{{ $row->paidst ?: '-' }}</td>
                                                    <td class="text-center">
                                                        @if($row->export_eclaim === 'Y')
                                                            <span class="badge bg-success-soft text-success">Y</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border">{{ $row->export_eclaim ?: 'N' }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if(empty($row->hipdata_code))
                                                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>ว่าง (ไม่ได้ระบุ)</span>
                                                        @elseif(!in_array(strtoupper(trim($row->hipdata_code)), $validCodes))
                                                            <span class="badge bg-danger" title="รหัสไม่ตรงกับระบบเรียกเก็บ">{{ $row->hipdata_code }} <i class="bi bi-x-circle ms-1"></i></span>
                                                        @else
                                                            <span class="badge bg-success-soft text-success">{{ $row->hipdata_code }}</span>
                                                        @endif
                                                    </td> 
                                                    <td class="text-start small text-muted">{{ $row->pttype_price_group_name ?: '-' }}</td>
                                                    <td class="text-start small">{{ $row->pi_name ?: '-' }}</td>  
                                                    <td class="text-center font-monospace small text-muted">{{ $row->pi_pttype_std_code ?: '-' }}</td>
                                                    <td class="text-center" data-order="{{ $row->is_valid ? 2 : 1 }}" data-sort="{{ $row->is_valid ? 2 : 1 }}" data-search="{{ $row->is_valid ? 'ปกติ สมบูรณ์ ผ่าน' : 'ผิดพลาด ข้อผิดพลาด ไม่ผ่าน ' . implode(' ', $row->item_errors ?? []) }}">
                                                        @if($row->is_valid)
                                                            <span class="d-none">2</span>
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px;" title="ข้อมูลปกติ / สมบูรณ์">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </span>
                                                        @else
                                                            <span class="d-none">1</span>
                                                            @php
                                                                $ptDetails = [
                                                                    'รหัสสิทธิ HOSxP' => $row->pttype,
                                                                    'ชื่อสิทธิ' => $row->name,
                                                                    'สิทธิ สปสช (subinscl)' => ($row->nhso_subinscl ?? '') ?: 'ไม่ได้ระบุ',
                                                                    'ประเภท (paidst)' => ($row->paidst ?? '') ?: '-',
                                                                    'Hipdata Code' => ($row->hipdata_code ?? '') ?: 'ว่าง',
                                                                    'รหัสส่งออก HOSxP' => ($row->pttype_std_code ?? '') ?: 'ว่าง',
                                                                    'กลุ่มราคา' => ($row->pttype_price_group_name ?? '') ?: '-',
                                                                    'ตาราง PROVIS' => ($row->pi_name ?? '') ?: 'ไม่ได้เชื่อม',
                                                                    'รหัสส่งออก PROVIS' => ($row->pi_pttype_std_code ?? '') ?: '-',
                                                                    'ส่งออก e-Claim' => (($row->export_eclaim ?? '') === 'Y') ? 'ส่งออก (Y)' : 'ไม่ส่งออก (N)',
                                                                    'สถานะ' => (($row->isuse ?? '') === 'Y') ? 'Active (เปิดใช้งาน)' : 'Inactive (ปิดใช้งาน)'
                                                                ];
                                                            @endphp
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger p-0 rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs btn-open-validation"
                                                                    style="width: 32px; height: 32px;"
                                                                    data-category="สิทธิการรักษา HOSxP (pttype)"
                                                                    data-code="{{ $row->pttype }}"
                                                                    data-name="{{ $row->name }}"
                                                                    data-errors='@json($row->item_errors ?? [])'
                                                                    data-details='@json($ptDetails)'
                                                                    title="พบข้อผิดพลาด: {{ $row->status_text }} (คลิกดูสาเหตุ)">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach                 
                                        </tbody>
                                    </table>
                                @elseif($activeTab === 'nhso_subinscl')
                                    {{-- 4. NHSO Subinscl Table --}}
                                    <table id="table-nhso-subinscl" class="table data-table-modern w-100 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center" colspan="3">ข้อมูล สปสช (NHSO)</th>
                                                <th class="text-center" colspan="3" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ข้อมูล HOSxP</th>
                                                <th class="text-center" rowspan="2" style="width: 70px;">ผลการตรวจ</th>
                                            </tr>
                                            <tr>
                                                <th class="text-center" style="width: 80px;">CODE</th>
                                                <th class="text-start">NAME</th>
                                                <th class="text-center" style="width: 110px;">MAININSCL</th>
                                                <th class="text-center" style="width: 90px; background-color: #f0f9ff;">PTTYPE</th>
                                                <th class="text-start" style="background-color: #f0f9ff;">PTTYPE_NAME</th>
                                                <th class="text-center" style="width: 100px; background-color: #f0f9ff;">HIPDATA_CODE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($records as $row)
                                                <tr>
                                                    <td class="text-center"><span class="code-badge fw-bold">{{ $row->code }}</span></td>
                                                    <td><div class="fw-bold text-dark">{{ $row->name }}</div></td>
                                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->maininscl ?: '-' }}</span></td>
                                                    <td class="text-center">
                                                        @if(!empty($row->pttype))
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary font-monospace">{{ $row->pttype }}</span>
                                                        @else
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">ไม่พบ</span>
                                                        @endif
                                                    </td>
                                                    <td class="small">{{ $row->pttype_name ?: '-' }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($row->hipdata_code))
                                                            <code class="text-muted">{{ $row->hipdata_code }}</code>
                                                        @else
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center" data-order="{{ $row->is_valid ? 2 : 1 }}" data-sort="{{ $row->is_valid ? 2 : 1 }}" data-search="{{ $row->is_valid ? 'ปกติ สมบูรณ์ ผ่าน พบ' : 'ผิดพลาด ไม่พบ ข้อผิดพลาด' }}">
                                                        @if($row->is_valid)
                                                            <span class="d-none">2</span>
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 32px; height: 32px;" title="พบสิทธิใน HOSxP">
                                                                <i class="bi bi-check-lg fs-6"></i>
                                                            </span>
                                                        @else
                                                            <span class="d-none">1</span>
                                                            @php
                                                                $rowDetails = [
                                                                    'รหัส CODE' => $row->code,
                                                                    'ชื่อสิทธิ สปสช' => $row->name,
                                                                    'หมวดหลัก (MAININSCL)' => $row->maininscl ?: '-',
                                                                    'รหัส PTTYPE' => $row->pttype ?: 'ไม่พบใน HOSxP',
                                                                    'ชื่อสิทธิ HOSxP' => $row->pttype_name ?: '-',
                                                                    'รหัส HIPDATA' => $row->hipdata_code ?: '-'
                                                                ];
                                                            @endphp
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger p-0 rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs btn-open-validation"
                                                                    style="width: 32px; height: 32px;"
                                                                    data-category="สิทธิการรักษา สปสช (NHSO)"
                                                                    data-code="{{ $row->code }}"
                                                                    data-name="{{ $row->name }}"
                                                                    data-errors='@json($row->item_errors ?? [])'
                                                                    data-details='@json($rowDetails)'
                                                                    title="ไม่พบรหัสใน HOSxP (คลิกดูรายละเอียด)">
                                                                <i class="bi bi-eye-fill fs-6"></i>
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>

                        <!-- Pagination Footer -->
                        @if(method_exists($records, 'links'))
                            <div class="p-3 px-4 bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="small text-muted">
                                    แสดงข้อมูล {{ $records->firstItem() ?? 0 }} ถึง {{ $records->lastItem() ?? 0 }} จากทั้งหมด {{ number_format($records->total()) }} รายการ
                                </div>
                                <div>
                                    {{ $records->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Modal รายละเอียดสาเหตุของข้อผิดพลาด (Data Validation Detail Modal) -->
<div class="modal fade" id="validationDetailModal" tabindex="-1" aria-labelledby="validationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle bg-white bg-opacity-20 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-shield-exclamation fs-4 text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="validationDetailModalLabel">
                            รายละเอียดสาเหตุของข้อผิดพลาด (Validation Details)
                        </h5>
                        <small class="text-white-50" id="modalValCategory">ระบบตรวจสอบความสมบูรณ์ของข้อมูลพื้นฐาน HOSxP</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Record Header Card -->
                <div class="card border-0 shadow-xs rounded-3 bg-white p-3 mb-3 border-start border-4 border-danger">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                        <div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill mb-1" id="modalValCategoryBadge" style="font-size: 0.72rem;">
                                หมวดข้อมูล
                            </span>
                            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 flex-wrap">
                                <span class="font-monospace text-primary" id="modalValCode">#CODE</span>
                                <span id="modalValName">ชื่อรายการ</span>
                            </h5>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-3 py-1.5 fw-bold" id="modalValErrorCountBadge" style="font-size: 0.80rem;">
                            พบ 1 ข้อผิดพลาด
                        </span>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="row g-2 pt-2 border-top small" id="modalValDetailsContainer">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Validation Issues Section -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.90rem;">
                            <i class="bi bi-x-circle-fill text-danger"></i> สาเหตุและเงื่อนไขที่ไม่ผ่านเกณฑ์:
                        </h6>
                        <small class="text-muted" style="font-size: 0.74rem;">ตรวจสอบอัตโนมัติจากโครงสร้าง HOSxP</small>
                    </div>

                    <div class="d-flex flex-column gap-2.5" id="modalValErrorsList">
                        <!-- Error Cards populated dynamically -->
                    </div>
                </div>

                <!-- Guidance Box -->
                <div class="p-3 rounded-3 bg-white border d-flex align-items-start gap-2.5 shadow-xs">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                        <i class="bi bi-lightbulb-fill fs-6"></i>
                    </div>
                    <div class="small">
                        <strong class="text-dark d-block mb-0.5">คำแนะนำในการแก้ไขข้อมูล:</strong>
                        <span class="text-muted">
                            สามารถเข้าไปแก้ไขหรือผูกรหัสข้อมูลให้ถูกต้องได้ในโปรแกรม HOSxP เมื่อบันทึกเสร็จแล้ว ระบบ RiMS จะดึงข้อมูลล่าสุดมาตรวจสอบใหม่อัตโนมัติ
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-2.5 px-4 d-flex justify-content-between align-items-center border-top">
                <small class="text-muted" style="font-size: 0.74rem;">
                    <i class="bi bi-check2-circle text-success me-1"></i> พร้อมรองรับการเพิ่มกฎและเกณฑ์การ Validate เพิ่มเติม
                </small>
                <button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" data-bs-dismiss="modal">
                    ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        @if($activeTab === 'doctor')
            const docTableIds = ['#table-active', '#table-inactive', '#table-invalid-license', '#table-invalid-cid'];
            docTableIds.forEach(function (id) {
                if ($(id).length && !$.fn.DataTable.isDataTable(id)) {
                    $(id).DataTable({
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[0, 'asc']], 
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                        },
                        drawCallback: function () {
                            initTooltips();
                        }
                    });
                }
            });

            // Re-adjust columns on tab click (important for DataTables inside hidden tabs)
            $(document).on('shown.bs.tab', '#doctorTabs button[data-bs-toggle="tab"]', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });

        @elseif($activeTab === 'nondrugitems')
            if ($('#table-nondrugitems').length && !$.fn.DataTable.isDataTable('#table-nondrugitems')) {
                $('#table-nondrugitems').DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[0, 'asc']],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        infoEmpty: "ไม่พบข้อมูล",
                        infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                        zeroRecords: "ไม่พบข้อมูลที่ตรงกับคำค้นหา",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    },
                    drawCallback: function () {
                        initTooltips();
                    }
                });
            }
        @elseif($activeTab === 'pttype')
            if ($('#table-pttype').length && !$.fn.DataTable.isDataTable('#table-pttype')) {
                $('#table-pttype').DataTable({
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel',
                            className: 'btn btn-sm btn-success',
                            title: 'สิทธิการรักษา HOSxP'
                        }
                    ],
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[1, 'asc']],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        infoEmpty: "ไม่พบข้อมูล",
                        infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                        zeroRecords: "ไม่พบข้อมูลที่ตรงกับคำค้นหา",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    },
                    drawCallback: function () {
                        initTooltips();
                    }
                });
            }
        @elseif($activeTab === 'nhso_subinscl')
            if ($('#table-nhso-subinscl').length && !$.fn.DataTable.isDataTable('#table-nhso-subinscl')) {
                $('#table-nhso-subinscl').DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[0, 'asc']],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        infoEmpty: "ไม่พบข้อมูล",
                        infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                        zeroRecords: "ไม่พบข้อมูลที่ตรงกับคำค้นหา",
                        paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                    },
                    drawCallback: function () {
                        initTooltips();
                    }
                });
            }
        @endif

        // Extensible Error Guidance Map
        const errorGuidanceMap = {
            'ยังไม่ผูกรหัส ADP': {
                desc: 'รายการค่ารักษาพยาบาลนี้ยังไม่มีการระบุรหัสมาตรฐาน nhso_adp_code ของ สปสช.',
                action: 'เข้าเมนู HOSxP > ระบบห้องยา/การเงิน > รายการค่ารักษาพยาบาล (nondrugitems) แล้วเลือกผูกรหัส ADP Code และ ADP Type ให้ตรงกับสิทธิการเบิก'
            },
            'ยังไม่ระบุหมวดรายได้ (income)': {
                desc: 'รายการนี้ยังไม่มีการกำหนดหมวดรายได้หลัก (ฟิลด์ income ในตาราง nondrugitems เป็นค่าว่าง)',
                action: 'เข้าเมนู nondrugitems ใน HOSxP เพื่อเลือกหมวดรายได้ให้ตรงกับ 16 หมวดมาตรฐานของกระทรวง'
            },
            'เลข ว. ไม่ถูกต้อง': {
                desc: 'เลขที่ใบประกอบวิชาชีพเวชกรรมไม่ขึ้นต้นด้วย "ว." หรือรูปแบบตัวเลขไม่ถูกต้อง',
                action: 'เข้าเมนูข้อมูลแพทย์ (doctor) ใน HOSxP แก้ไขเลขใบอนุญาตให้มีคำนำหน้า ว. ตามด้วยเลข 4-6 หลัก'
            },
            'เลขใบอนุญาตไม่ถูกต้อง': {
                desc: 'เลขที่ใบประกอบวิชาชีพไม่อยู่ในเกณฑ์มาตรฐานตามสภาวิชาชีพที่สังกัด',
                action: 'ตรวจสอบคำนำหน้าใบอนุญาต เช่น ว. (แพทย์), พ. (พยาบาล), ภ. (เภสัชกร) ให้ถูกต้อง'
            },
            'เลขบัตรประชาชน 13 หลักไม่ถูกต้อง': {
                desc: 'เลขประจำตัวประชาชน 13 หลักไม่ถูกต้องตามสูตร Check Digit ของกรมการปกครอง',
                action: 'ตรวจสอบสำเนาบัตรประชาชนของเจ้าหน้าที่ แล้วแก้ไขให้ถูกต้องครบ 13 หลักในตาราง doctor'
            },
            'ขาดรหัสมาตรฐาน 4 หลัก (std_code)': {
                desc: 'สิทธิการรักษานี้ยังไม่ได้ผูกรหัสมาตรฐาน 4 หลัก (pttype_std_code)',
                action: 'เข้าเมนูตั้งค่าสิทธิการรักษา (pttype) ใน HOSxP แล้วระบุรหัสมาตรฐาน 4 หลักเพื่อใช้ส่งออก e-Claim/FDH'
            },
            'ขาดรหัส HIPDATA': {
                desc: 'ยังไม่มีการระบุรหัสกลุ่มสิทธิ HIPDATA สำหรับเชื่อมโยงระบบข้อมูลสุขภาพ',
                action: 'เข้าเมนูตั้งค่าสิทธิการรักษา (pttype) ใน HOSxP แล้วระบุรหัส hipdata_code ให้ครบถ้วน'
            },
            'ไม่ได้เชื่อมรหัสมาตรฐาน (nhso_code)': {
                desc: 'สิทธินี้ยังไม่ได้ผูกรหัสมาตรฐาน nhso_code เพื่อเชื่อมโยงกับตาราง provis_instype',
                action: 'เข้าเมนูตั้งค่าสิทธิการรักษา (pttype) ใน HOSxP แล้วระบุรหัส nhso_code ให้ตรงกับมาตรฐาน'
            },
            'ไม่ได้ระบุรหัสส่งออกใน HOSxP': {
                desc: 'ยังไม่มีการระบุรหัสส่งออกมาตรฐาน (pttype_std_code) ในระบบ HOSxP',
                action: 'เข้าเมนูตั้งค่าสิทธิการรักษา (pttype) ใน HOSxP แล้วระบุรหัสส่งออก 4 หลัก (pttype_std_code)'
            },
            'สิทธิหลักประกันสุขภาพ (UCS) รหัสส่งออกต้องเป็น 0100': {
                desc: 'สิทธิกลุ่มหลักประกันสุขภาพถ้วนหน้า (UCS) ต้องใช้รหัสส่งออกตามมาตรฐานกระทรวงฯ คือ 0100 เท่านั้น',
                action: 'แก้ไขรหัสส่งออก (pttype_std_code) ของสิทธินี้ในตาราง pttype ให้เป็น 0100'
            },
            'รหัสส่งออกไม่ตรงกัน': {
                desc: 'รหัสส่งออกของสิทธิใน HOSxP ไม่ตรงกับรหัสกลุ่มของตารางมาตรฐาน provis_instype',
                action: 'ตรวจสอบและปรับรหัส pttype_std_code ใน HOSxP ให้ตรงกับ pi.pttype_std_code ในตาราง provis_instype'
            },
            'รหัส Hipdata ไม่ถูกต้อง': {
                desc: 'รหัส Hipdata ไม่อยู่ในกลุ่มรหัสมาตรฐาน 22 สิทธิของระบบเบิกจ่าย',
                action: 'แก้ไขรหัส hipdata_code ใน HOSxP ให้เป็นรหัสมาตรฐาน เช่น UCS, WEL, OFC, LGO, SSS, STP, ฯลฯ'
            },
            'รหัส Hipdata ว่าง (ไม่ได้ระบุ)': {
                desc: 'ยังไม่มีการระบุรหัสกลุ่มสิทธิ hipdata_code',
                action: 'ระบุรหัส hipdata_code ในตาราง pttype ให้ถูกต้อง'
            },
            'ไม่พบรหัสสิทธินี้ในตาราง pttype ของ HOSxP': {
                desc: 'รหัสสิทธิย่อย สปสช (Sub-Insurance Class) นี้ยังไม่มีการตั้งค่ารหัสสิทธิที่ตรงกันในตาราง pttype ของ HOSxP',
                action: 'เข้าเมนูตั้งค่าสิทธิการรักษา (pttype) ใน HOSxP แล้วเพิ่มรหัสสิทธินี้ หรือตรวจสอบการจับคู่รหัสระหว่าง สปสช กับ HOSxP'
            }
        };

        // Open Validation Details Modal
        $(document).on('click', '.btn-open-validation', function () {
            const $btn = $(this);
            const category = $btn.data('category') || 'ข้อมูลพื้นฐาน HOSxP';
            const code = $btn.data('code') || '-';
            const name = $btn.data('name') || '-';
            let errors = $btn.data('errors') || [];
            let details = $btn.data('details') || {};

            if (typeof errors === 'string') {
                try { errors = JSON.parse(errors); } catch(e) { errors = [errors]; }
            }
            if (typeof details === 'string') {
                try { details = JSON.parse(details); } catch(e) { details = {}; }
            }

            $('#modalValCategory').text(category);
            $('#modalValCategoryBadge').text(category);
            $('#modalValCode').text('#' + code);
            $('#modalValName').text(name);
            $('#modalValErrorCountBadge').text(`พบ ${errors.length} ข้อผิดพลาด`);

            // Render Metadata Grid
            const $detailsContainer = $('#modalValDetailsContainer').empty();
            Object.keys(details).forEach(key => {
                $detailsContainer.append(`
                    <div class="col-sm-6 col-md-4">
                        <span class="text-muted d-block" style="font-size: 0.72rem;">${key}:</span>
                        <strong class="text-dark font-monospace" style="font-size: 0.82rem;">${details[key]}</strong>
                    </div>
                `);
            });

            // Render Error Cards List
            const $errorsList = $('#modalValErrorsList').empty();
            if (errors.length === 0) {
                $errorsList.append(`
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-0 py-2.5 rounded-3">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>ไม่พบข้อผิดพลาด ข้อมูลผ่านเกณฑ์การตรวจสอบเรียบร้อยแล้ว</div>
                    </div>
                `);
            } else {
                errors.forEach((err, idx) => {
                    const guidance = errorGuidanceMap[err] || {
                        desc: 'ข้อมูลในรายการนี้ไม่ผ่านเกณฑ์การตรวจสอบความสมบูรณ์',
                        action: 'กรุณาตรวจสอบและปรับปรุงข้อมูลในฐานข้อมูล HOSxP ตามมาตรฐาน'
                    };

                    $errorsList.append(`
                        <div class="card border border-danger-subtle rounded-3 bg-white p-3 shadow-xs">
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" style="width: 28px; height: 28px; font-size: 0.80rem;">
                                    ${idx + 1}
                                </div>
                                <div class="w-100">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                                        <strong class="text-danger" style="font-size: 0.88rem;">${err}</strong>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill" style="font-size: 0.68rem;">ต้องแก้ไข</span>
                                    </div>
                                    <p class="text-muted small mb-2" style="font-size: 0.80rem;">${guidance.desc}</p>
                                    <div class="p-2 px-2.5 rounded-2 bg-light border small text-secondary" style="font-size: 0.76rem;">
                                        <i class="bi bi-wrench-adjustable text-primary me-1"></i>
                                        <strong>วิธีแก้ไขใน HOSxP:</strong> ${guidance.action}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });
            }

            const modalEl = document.getElementById('validationDetailModal');
            if (modalEl) {
                const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
                if (bs && bs.Modal) {
                    try {
                        const modal = bs.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                        return;
                    } catch (e) {
                        console.warn('Bootstrap modal error:', e);
                    }
                }
                if (window.jQuery && typeof $('#validationDetailModal').modal === 'function') {
                    $('#validationDetailModal').modal('show');
                    return;
                }
                // Fallback direct display
                $(modalEl).addClass('show').css('display', 'block');
                $('body').addClass('modal-open');
                if (!$('#valModalBackdrop').length) {
                    $('body').append('<div class="modal-backdrop fade show" id="valModalBackdrop"></div>');
                }
            }
        });

        // Safe dismiss handler for fallback modal
        $(document).on('click', '#validationDetailModal [data-bs-dismiss="modal"], #valModalBackdrop', function() {
            $('#validationDetailModal').removeClass('show').css('display', 'none');
            $('body').removeClass('modal-open');
            $('#valModalBackdrop').remove();
        });

        function initTooltips() {
            if (typeof bootstrap !== 'undefined') {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            } else if ($.fn.tooltip) {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        }

        initTooltips();
    });
</script>
@endpush
