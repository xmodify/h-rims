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
                    ข้อมูลพื้นฐาน HOSxP (Master Data & Setting)
                </h5>
                <small class="text-muted">ตรวจสอบความถูกต้องและครบถ้วนของข้อมูลแพทย์, ค่ารักษาพยาบาล (nondrugitems) และสิทธิการรักษา (pttype)</small>
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
                                3 หมวด
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
                                        <div class="setting-nav-title text-truncate">ค่ารักษาพยาบาล (Non-Drug)</div>
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
                                        <div class="setting-nav-title text-truncate">สิทธิการรักษา (Pttype)</div>
                                        <div class="setting-nav-sub text-truncate">รหัสสิทธิมาตรฐาน, รหัสส่งออก</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill setting-nav-badge">
                                    {{ number_format($stats['pttype']['active'] ?? 0) }}
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
                                                    <th class="text-center" style="width: 13%;">ผลการตรวจสอบ</th>
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

                                                        if (empty($doc_errors)) {
                                                            $statusHtml = '<span class="badge bg-success-soft text-success"><i class="bi bi-eye-fill me-1"></i>ข้อมูลปกติ</span>';
                                                        } else {
                                                            $tooltipText = implode(' | ', $doc_errors);
                                                            $statusHtml = '<span class="badge bg-danger-soft text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="'.e($tooltipText).' (คลิกเพื่อปรึกษา Copilot)" onclick="consultCopilotForRecord(\'doctor\', \''.$row->code.'\', \''.addslashes($row->name).'\', \''.addslashes($tooltipText).'\')"><i class="bi bi-eye-slash-fill me-1"></i>พบข้อผิดพลาด</span>';
                                                        }

                                                        $sexText = '-';
                                                        if (($row->sex ?? '') == '1') {
                                                            $sexText = 'ชาย';
                                                        } elseif (($row->sex ?? '') == '2') {
                                                            $sexText = 'หญิง';
                                                        }

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
                                                        <td class="text-center">{!! $statusHtml !!}</td>
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
                                        <div class="text-muted" style="font-size: 0.75rem;">สิทธิการรักษาในระบบ</div>
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
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'missing_std']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-danger {{ $filter === 'missing_std' ? 'border-danger' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ขาดรหัสมาตรฐาน</span>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill"><i class="bi bi-exclamation-triangle"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-danger mb-1">{{ number_format($stats['pttype']['missing_std'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">ขาด pttype_std_code</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6 col-md-3">
                                <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'missing_hip']) }}" class="text-decoration-none">
                                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100 tab-kpi-card border-start border-4 border-warning {{ $filter === 'missing_hip' ? 'border-warning' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">ขาดรหัส Hipdata</span>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill"><i class="bi bi-database-exclamation"></i></span>
                                        </div>
                                        <h3 class="fw-bold text-warning mb-1">{{ number_format($stats['pttype']['missing_hip'] ?? 0) }}</h3>
                                        <div class="text-muted" style="font-size: 0.75rem;">ขาด hipdata_code</div>
                                    </div>
                                </a>
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
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'all']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">ทั้งหมด</a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'active']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-primary text-white' : 'btn-light text-muted' }}">เปิดใช้งาน (Active)</a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'missing_std']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_std' ? 'btn-danger text-white fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-exclamation-circle me-1"></i>ยังไม่ผูกรหัสมาตรฐาน ({{ $stats['pttype']['missing_std'] }})
                                    </a>
                                    <a href="{{ route('emr.hosxp_setting', ['tab' => 'pttype', 'filter' => 'missing_hip']) }}" 
                                       class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_hip' ? 'btn-warning text-dark fw-bold' : 'btn-light text-muted' }}">
                                       <i class="bi bi-database-exclamation me-1"></i>ขาด Hipdata ({{ $stats['pttype']['missing_hip'] }})
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
                                                <th class="text-center" style="width: 125px;">ผลการตรวจสอบ</th>
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
                                                    <td class="text-center">
                                                        @if($item->is_valid)
                                                            <span class="badge bg-success-soft text-success"><i class="bi bi-eye-fill me-1"></i>สมบูรณ์</span>
                                                        @else
                                                            <span class="badge bg-danger-soft text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                  title="{{ implode(' | ', $item->item_errors ?? []) }} (คลิกเพื่อปรึกษา Copilot)"
                                                                  onclick="consultCopilotForRecord('nondrugitems', '{{ $item->icode }}', '{{ addslashes($item->name) }}', '{{ addslashes(implode(' | ', $item->item_errors ?? [])) }}')">
                                                                <i class="bi bi-eye-slash-fill me-1"></i>พบข้อผิดพลาด
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                @elseif($activeTab === 'pttype')
                                    {{-- 3. Pttype Table --}}
                                    <table id="table-pttype" class="table data-table-modern w-100 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 80px;">รหัสสิทธิ</th>
                                                <th class="text-start">ชื่อสิทธิการรักษา</th>
                                                <th class="text-center" style="width: 100px;">กลุ่มสิทธิ (pcode)</th>
                                                <th class="text-center" style="width: 140px;">รหัสมาตรฐาน 4 หลัก (std_code)</th>
                                                <th class="text-center" style="width: 100px;">HIPDATA</th>
                                                <th class="text-center" style="width: 90px;">paidst</th>
                                                <th class="text-center" style="width: 90px;">ส่งออก e-Claim</th>
                                                <th class="text-center" style="width: 90px;">สถานะ</th>
                                                <th class="text-center" style="width: 125px;">ผลการตรวจสอบ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($records as $pt)
                                                <tr>
                                                    <td class="text-center"><span class="code-badge fw-bold">{{ $pt->pttype }}</span></td>
                                                    <td><div class="fw-bold text-dark">{{ $pt->name }}</div></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark border font-monospace">{{ $pt->pcode ?: '-' }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if(!empty($pt->pttype_std_code))
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary font-monospace">{{ $pt->pttype_std_code }}</span>
                                                        @else
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">⚠️ ขาดรหัสมาตรฐาน</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <code class="text-muted">{{ $pt->hipdata_code ?: '-' }}</code>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-muted border">{{ $pt->paidst ?: '-' }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($pt->export_eclaim === 'Y')
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">เปิด (Y)</span>
                                                        @else
                                                            <span class="badge bg-light text-muted border">ปิด (N)</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($pt->isuse === 'Y')
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">ใช้งาน</span>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">ไม่ใช้</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($pt->is_valid)
                                                            <span class="badge bg-success-soft text-success"><i class="bi bi-eye-fill me-1"></i>ข้อมูลปกติ</span>
                                                        @else
                                                            <span class="badge bg-danger-soft text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                  title="{{ implode(' | ', $pt->item_errors ?? []) }} (คลิกเพื่อปรึกษา Copilot)"
                                                                  onclick="consultCopilotForRecord('pttype', '{{ $pt->pttype }}', '{{ addslashes($pt->name) }}', '{{ addslashes(implode(' | ', $pt->item_errors ?? [])) }}')">
                                                                <i class="bi bi-eye-slash-fill me-1"></i>พบข้อผิดพลาด
                                                            </span>
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

<script>
    window.askCopilotAbout = function(category, query) {
        if (typeof window.openAiChatWithPrompt === 'function') {
            window.openAiChatWithPrompt(query, true);
        } else if (typeof toggleAiChatbot === 'function') {
            toggleAiChatbot();
        }
    };

    window.consultCopilotForRecord = function(type, code, name, errorText) {
        let prompt = '';
        if (type === 'nondrugitems') {
            prompt = `ขอคำปรึกษาเกี่ยวกับรายการค่ารักษาพยาบาล รหัส icode ${code} (${name}): ตรวจพบปัญหา "${errorText}" ควรเลือกผูกรหัส NHSO ADP Code หมวดใด และมีคำแนะนำการตั้งค่าใน HOSxP อย่างไร`;
        } else if (type === 'doctor') {
            prompt = `ขอคำปรึกษาเกี่ยวกับข้อมูลแพทย์/บุคลากร รหัส ${code} (${name}): ตรวจพบปัญหา "${errorText}" ข้อมูลที่ถูกต้องตามมาตรฐาน 43 แฟ้ม/สภาวิชาชีพต้องเป็นอย่างไร และต้องเข้าไปแก้ไขในเมนูใดของ HOSxP`;
        } else if (type === 'pttype') {
            prompt = `ขอคำปรึกษาเกี่ยวกับสิทธิการรักษา รหัส ${code} (${name}): ตรวจพบปัญหา "${errorText}" ควรผูกรหัสมาตรฐาน 4 หลัก (std_code) และ HIPDATA อย่างไรให้ถูกต้องตามเกณฑ์ สปสช./FDH`;
        } else {
            prompt = `ขอคำปรึกษาเกี่ยวกับรหัส ${code} (${name}): ตรวจพบปัญหา "${errorText}"`;
        }

        if (typeof window.openAiChatWithPrompt === 'function') {
            window.openAiChatWithPrompt(prompt, true);
        } else if (typeof toggleAiChatbot === 'function') {
            toggleAiChatbot();
        }
    };
</script>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        @if($activeTab === 'doctor')
            const docTableIds = ['#table-active', '#table-inactive', '#table-invalid-license', '#table-invalid-cid'];
            docTableIds.forEach(function (id) {
                if ($(id).length && !$.fn.DataTable.isDataTable(id)) {
                    $(id).DataTable({
                        pageLength: 25,
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
