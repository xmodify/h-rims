@extends('layouts.app')

@section('content')
<style>
    /* Premium Design System for HOSxP Master Data */
    .master-header-gradient {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
        color: #ffffff;
        border-radius: 1.25rem;
        position: relative;
        overflow: hidden;
    }
    .master-header-gradient::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(255, 255, 255, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .stat-kpi-card {
        border-radius: 1rem;
        border: 1px solid rgba(226, 232, 240, 0.8);
        background: #ffffff;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    }
    .stat-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        border-color: #cbd5e1;
    }
    .master-tab-btn {
        border: none;
        background: transparent;
        color: #64748b;
        font-weight: 600;
        padding: 0.85rem 1.5rem;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .master-tab-btn:hover {
        color: #1e293b;
        background-color: rgba(241, 245, 249, 0.6);
    }
    .master-tab-btn.active {
        color: #4f46e5;
        border-bottom-color: #4f46e5;
        background-color: #ffffff;
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
    .copilot-panel {
        border-radius: 1rem;
        border: 1px solid #e0e7ff;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }
    .chat-bubble-ai {
        background-color: #f1f5f9;
        border-left: 4px solid #6366f1;
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        font-size: 0.9rem;
        line-height: 1.6;
    }
    .chat-bubble-ai pre {
        background-color: #1e1e2e;
        color: #cdd6f4;
        padding: 0.85rem;
        border-radius: 0.5rem;
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
        overflow-x: auto;
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- Top Header Banner -->
    <div class="master-header-gradient p-4 p-md-5 mb-4 shadow-sm position-relative">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-15 text-white small fw-bold mb-3">
                    <i class="bi bi-shield-check text-warning"></i>
                    <span>งานเวชระเบียน • Medical Records Master Data Hub</span>
                </div>
                <h2 class="fw-bold mb-2 text-white">ข้อมูลพื้นฐาน HOSxP (Master Data)</h2>
                <p class="mb-0 text-white-50 fs-6">
                    ตรวจสอบความถูกต้อง ความครบถ้วนของข้อมูลแพทย์/บุคลากร, หมวดค่ารักษาพยาบาล และสิทธิการรักษา เพื่อความพร้อมในการออกรายงาน 43 แฟ้ม และการส่งเคลม FDH / e-Claim
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <div class="d-inline-flex flex-column align-items-lg-end gap-2">
                    <button type="button" class="btn btn-warning px-4 py-2.5 rounded-pill shadow-sm fw-bold text-dark d-flex align-items-center gap-2" onclick="openCopilotDrawer()">
                        <i class="bi bi-robot fs-5"></i>
                        <span>ปรึกษา RiMS Copilot</span>
                    </button>
                    <div class="small text-white-50">
                        โมเดล AI ที่ใช้: <span class="badge bg-white bg-opacity-20 text-white font-monospace">{{ $aiModelInfo['model'] ?? 'gemini-3.7-flash' }}</span>
                    </div>
                </div>
            </div>
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
        <!-- Summary KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- 1. Doctors KPI -->
            <div class="col-12 col-md-4">
                <div class="stat-kpi-card p-3 p-xl-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">แพทย์และบุคลากร (Doctor)</span>
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-person-badge-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-bold text-dark">{{ number_format($stats['doctor']['active'] ?? 0) }}</span>
                        <span class="text-muted small">คน (Active)</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>ทั้งหมด: <strong>{{ number_format($stats['doctor']['total'] ?? 0) }}</strong></span>
                        @if(($stats['doctor']['missing_council'] ?? 0) > 0)
                            <span class="text-warning fw-bold"><i class="bi bi-exclamation-circle me-1"></i>ยังไม่ระบุสภา: {{ $stats['doctor']['missing_council'] }}</span>
                        @else
                            <span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>สมบูรณ์ 100%</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Nondrugitems KPI -->
            <div class="col-12 col-md-4">
                <div class="stat-kpi-card p-3 p-xl-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">ค่ารักษาพยาบาล (Non-Drug)</span>
                        <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                            <i class="bi bi-capsule-pill fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-bold text-dark">{{ number_format($stats['nondrugitems']['active'] ?? 0) }}</span>
                        <span class="text-muted small">รายการ (Active)</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>ผูกรหัส ADP แล้ว: <strong>{{ number_format($stats['nondrugitems']['mapped_adp'] ?? 0) }}</strong></span>
                        @if(($stats['nondrugitems']['missing_adp'] ?? 0) > 0)
                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>ขาดรหัส ADP: {{ $stats['nondrugitems']['missing_adp'] }}</span>
                        @else
                            <span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>ผูกครบทุกรายการ</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 3. Pttype KPI -->
            <div class="col-12 col-md-4">
                <div class="stat-kpi-card p-3 p-xl-4 h-100 position-relative">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase">สิทธิการรักษา (Pttype)</span>
                        <div class="rounded-3 p-2" style="background-color: #ede9fe; color: #7c3aed;">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-bold text-dark">{{ number_format($stats['pttype']['active'] ?? 0) }}</span>
                        <span class="text-muted small">สิทธิ (เปิดใช้งาน)</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>ทั้งหมด: <strong>{{ number_format($stats['pttype']['total'] ?? 0) }}</strong></span>
                        @if(($stats['pttype']['missing_std'] ?? 0) > 0)
                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>ขาดรหัสมาตรฐาน: {{ $stats['pttype']['missing_std'] }}</span>
                        @else
                            <span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>ผูกรหัสครบถ้วน</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Workspace Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
            <!-- Navigation Tabs Bar -->
            <div class="px-4 pt-3 bg-light bg-opacity-50 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <ul class="nav nav-tabs border-bottom-0 gap-1" id="masterHubTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'doctor']) }}" 
                           class="master-tab-btn d-flex align-items-center gap-2 text-decoration-none {{ $activeTab === 'doctor' ? 'active' : '' }}">
                            <i class="bi bi-person-badge fs-5"></i>
                            <span>แพทย์และบุคลากรทางการแพทย์</span>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary ms-1">{{ number_format($stats['doctor']['active'] ?? 0) }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'nondrugitems']) }}" 
                           class="master-tab-btn d-flex align-items-center gap-2 text-decoration-none {{ $activeTab === 'nondrugitems' ? 'active' : '' }}">
                            <i class="bi bi-capsule-pill fs-5"></i>
                            <span>ค่ารักษาพยาบาล (nondrugitems)</span>
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success ms-1">{{ number_format($stats['nondrugitems']['active'] ?? 0) }}</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'pttype']) }}" 
                           class="master-tab-btn d-flex align-items-center gap-2 text-decoration-none {{ $activeTab === 'pttype' ? 'active' : '' }}">
                            <i class="bi bi-shield-check fs-5"></i>
                            <span>สิทธิการรักษา (pttype)</span>
                            <span class="badge rounded-pill ms-1" style="background-color: #ede9fe; color: #7c3aed;">{{ number_format($stats['pttype']['active'] ?? 0) }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Top Right Quick Info -->
                <div class="d-none d-md-flex align-items-center gap-2 pb-2">
                    <span class="badge bg-light text-muted border px-3 py-1.5 rounded-pill small">
                        <i class="bi bi-database text-primary me-1"></i> เชื่อมต่อ HOSxP MySQL Live
                    </span>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="p-3 px-4 bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Filter Pills -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="small text-muted me-2 fw-semibold">กรองข้อมูล:</span>
                    @if($activeTab === 'doctor')
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'doctor', 'filter' => 'all', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">ทั้งหมด</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'doctor', 'filter' => 'active', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-primary' : 'btn-light text-muted' }}">เฉพาะ Active</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'doctor', 'filter' => 'missing_council', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_council' ? 'btn-warning text-dark fw-bold' : 'btn-light text-muted' }}">
                           <i class="bi bi-exclamation-circle me-1"></i>ยังไม่ระบุสภา ({{ $stats['doctor']['missing_council'] }})
                        </a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'doctor', 'filter' => 'inactive', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'inactive' ? 'btn-secondary' : 'btn-light text-muted' }}">ปิดใช้งาน (Inactive)</a>
                    @elseif($activeTab === 'nondrugitems')
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'nondrugitems', 'filter' => 'all', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">ทั้งหมด</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'nondrugitems', 'filter' => 'active', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-success' : 'btn-light text-muted' }}">เปิดใช้งาน (Active)</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'nondrugitems', 'filter' => 'missing_adp', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_adp' ? 'btn-danger text-white fw-bold' : 'btn-light text-muted' }}">
                           <i class="bi bi-exclamation-circle me-1"></i>ยังไม่ผูกรหัส ADP ({{ $stats['nondrugitems']['missing_adp'] }})
                        </a>
                    @elseif($activeTab === 'pttype')
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'pttype', 'filter' => 'all', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'all' ? 'btn-dark' : 'btn-light text-muted' }}">ทั้งหมด</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'pttype', 'filter' => 'active', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'active' ? 'btn-primary' : 'btn-light text-muted' }}">เปิดใช้งาน (Active)</a>
                        <a href="{{ route('mrec.hosxp_master', ['tab' => 'pttype', 'filter' => 'missing_std', 'search' => $search]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $filter === 'missing_std' ? 'btn-danger text-white fw-bold' : 'btn-light text-muted' }}">
                           <i class="bi bi-exclamation-circle me-1"></i>ยังไม่ผูกรหัสมาตรฐาน
                        </a>
                    @endif
                </div>

                <!-- Search Input Form -->
                <form action="{{ route('mrec.hosxp_master') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="input-group input-group-sm" style="width: 280px;">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0 ps-0" 
                               placeholder="ค้นหาชื่อ, รหัส..." value="{{ $search }}">
                        @if(!empty($search))
                            <a href="{{ route('mrec.hosxp_master', ['tab' => $activeTab, 'filter' => $filter]) }}" class="btn btn-light border" title="ล้างการค้นหา">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary px-3 fw-bold">ค้นหา</button>
                    </div>
                </form>
            </div>

            <!-- Table Content Area -->
            <div class="table-responsive">
                @if($activeTab === 'doctor')
                    {{-- 1. Doctor Table --}}
                    <table class="table data-table-modern mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 90px;">รหัสแพทย์</th>
                                <th>ชื่อ - สกุล บุคลากร</th>
                                <th>เลขที่ใบอนุญาต (licenseno)</th>
                                <th>สภาวิชาชีพ (council)</th>
                                <th>เลข 13 หลัก (CID)</th>
                                <th>แผนก / ตำแหน่ง</th>
                                <th style="width: 100px;">สถานะ</th>
                                <th class="text-end" style="width: 90px;">ถาม AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $doc)
                                <tr>
                                    <td><span class="code-badge fw-bold">{{ $doc->code }}</span></td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $doc->name }}</div>
                                        @if(!empty($doc->ename))
                                            <small class="text-muted">{{ $doc->ename }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($doc->licenseno) && $doc->licenseno !== '-')
                                            <span class="badge bg-light text-dark border font-monospace">{{ $doc->licenseno }}</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border border-warning">ไม่มีเลขใบอนุญาต</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($doc->council_code))
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info font-monospace">สภา: {{ $doc->council_code }}</span>
                                        @else
                                            <span class="badge bg-light text-muted border">ไม่ระบุ</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted small">{{ !empty($doc->cid) ? substr($doc->cid, 0, 3) . 'XXXXXXX' . substr($doc->cid, -3) : '-' }}</code>
                                    </td>
                                    <td>
                                        <div class="small text-dark">{{ $doc->department ?: '-' }}</div>
                                        <div class="small text-muted">{{ $doc->jobposition ?: '' }}</div>
                                    </td>
                                    <td>
                                        @if($doc->active === 'Y')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle p-1" style="width: 32px; height: 32px;" 
                                                title="ถาม AI เกี่ยวกับแพทย์ท่านนี้" 
                                                onclick="askCopilotAbout('doctor', 'รหัสแพทย์ {{ $doc->code }} ({{ $doc->name }}) licenseno: {{ $doc->licenseno }} council_code: {{ $doc->council_code }} ข้อมูลถูกต้องตามมาตรฐานการส่งออก 43 แฟ้มหรือไม่?')">
                                            <i class="bi bi-robot"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ไม่พบข้อมูลแพทย์หรือบุคลากรตามเงื่อนไขที่ระบุ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($activeTab === 'nondrugitems')
                    {{-- 2. Nondrugitems Table --}}
                    <table class="table data-table-modern mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 95px;">รหัส icode</th>
                                <th>ชื่อรายการค่ารักษาพยาบาล</th>
                                <th>หมวดรายได้ (income)</th>
                                <th>รหัส ADP Code</th>
                                <th>ADP Type</th>
                                <th class="text-end" style="width: 110px;">ราคา OPD</th>
                                <th style="width: 100px;">สถานะ</th>
                                <th class="text-end" style="width: 90px;">ถาม AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $item)
                                <tr>
                                    <td><span class="code-badge fw-bold">{{ $item->icode }}</span></td>
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
                                    <td>
                                        @if(!empty($item->nhso_adp_code))
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success font-monospace fs-7">{{ $item->nhso_adp_code }}</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">⚠️ ยังไม่ได้ใส่ ADP</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($item->nhso_adp_type_id))
                                            <small class="text-muted">{{ $item->nhso_adp_type_id }} - {{ $item->nhso_adp_type_name ?? '' }}</small>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        {{ number_format($item->price, 2) }} บ.
                                    </td>
                                    <td>
                                        @if($item->istatus === 'Y')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle p-1" style="width: 32px; height: 32px;" 
                                                title="ถาม AI วิเคราะห์รหัส ADP ของรายการนี้" 
                                                onclick="askCopilotAbout('nondrugitems', 'รายการ icode {{ $item->icode }}: {{ $item->name }} หมวด income: {{ $item->income }} แนะนำรหัส nhso_adp_code และ nhso_adp_type ที่ถูกต้องให้หน่อยครับ')">
                                            <i class="bi bi-robot"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ไม่พบข้อมูลค่ารักษาพยาบาลตามเงื่อนไขที่ระบุ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($activeTab === 'pttype')
                    {{-- 3. Pttype Table --}}
                    <table class="table data-table-modern mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 80px;">รหัสสิทธิ</th>
                                <th>ชื่อสิทธิการรักษา</th>
                                <th>กลุ่มสิทธิ (pcode)</th>
                                <th>รหัสมาตรฐาน 4 หลัก (std_code)</th>
                                <th>HIPDATA</th>
                                <th>paidst</th>
                                <th>ส่งออก e-Claim</th>
                                <th style="width: 90px;">สถานะ</th>
                                <th class="text-end" style="width: 90px;">ถาม AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $pt)
                                <tr>
                                    <td><span class="code-badge fw-bold">{{ $pt->pttype }}</span></td>
                                    <td><div class="fw-bold text-dark">{{ $pt->name }}</div></td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $pt->pcode ?: '-' }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($pt->pttype_std_code))
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary font-monospace">{{ $pt->pttype_std_code }}</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">⚠️ ขาดรหัสมาตรฐาน</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $pt->hipdata_code ?: '-' }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-muted border">{{ $pt->paidst ?: '-' }}</span>
                                    </td>
                                    <td>
                                        @if($pt->export_eclaim === 'Y')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">เปิด (Y)</span>
                                        @else
                                            <span class="badge bg-light text-muted border">ปิด (N)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pt->isuse === 'Y')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">ใช้งาน</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-pill">ไม่ใช้</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle p-1" style="width: 32px; height: 32px;" 
                                                title="ถาม AI ตรวจสอบการผูกสิทธินี้" 
                                                onclick="askCopilotAbout('pttype', 'สิทธิการรักษา {{ $pt->pttype }} ({{ $pt->name }}) pcode: {{ $pt->pcode }} std_code: {{ $pt->pttype_std_code }} hipdata: {{ $pt->hipdata_code }} ผูกรหัสถูกต้องตามเกณฑ์ สปสช./FDH หรือไม่?')">
                                            <i class="bi bi-robot"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        ไม่พบข้อมูลสิทธิการรักษาตามเงื่อนไขที่ระบุ
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
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

<!-- Offcanvas / Modal: RiMS Copilot for HOSxP Master Data -->
<div class="modal fade" id="copilotModal" tabindex="-1" aria-labelledby="copilotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #312e81 0%, #4338ca 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 p-1.5 bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="copilotModalLabel">RiMS Copilot • ผู้ช่วยข้อมูลพื้นฐาน HOSxP</h5>
                        <small class="text-white-50">ขับเคลื่อนด้วย HosxpContextService + RAG Knowledge</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-30">
                <!-- Suggested Prompt Chips -->
                <div class="mb-3">
                    <small class="text-muted fw-bold d-block mb-2">💡 ตัวอย่างคำถามด่วน:</small>
                    <div class="d-flex flex-wrap gap-1.5">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 bg-white" onclick="setCopilotQuestion('สรุปรายการค่ารักษาพยาบาล (nondrugitems) ที่ยังไม่ได้ใส่รหัส ADP ให้หน่อย')">
                            ค่ารักษาที่ยังไม่ผูก ADP
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 bg-white" onclick="setCopilotQuestion('ตรวจสอบความสมบูรณ์ของข้อมูลแพทย์และบุคลากรในระบบ HOSxP สำหรับแฟ้ม PROVIDER')">
                            ความสมบูรณ์ข้อมูลแพทย์
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 bg-white" onclick="setCopilotQuestion('ตรวจสอบสิทธิการรักษาที่เปิดใช้งานว่ามีตัวไหนยังไม่ผูกรหัสมาตรฐาน pttype_std_code บ้าง')">
                            สิทธิที่ยังไม่ผูกรหัสมาตรฐาน
                        </button>
                    </div>
                </div>

                <!-- Input Box -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">พิมพ์คำถาม หรือข้อสงสัยเกี่ยวกับข้อมูล HOSxP:</label>
                    <textarea class="form-control rounded-3" id="copilotQuestionInput" rows="3" placeholder="เช่น ขอคำแนะนำรหัส ADP สำหรับรายการ ค่าบริการตรวจคลื่นไฟฟ้าหัวใจ หรือ ช่วยเขียน SQL ตรวจสอบแพทย์ที่ไม่มีเลข ว."></textarea>
                </div>

                <!-- AI Response Card -->
                <div id="copilotResponseWrapper" class="d-none">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-primary"><i class="bi bi-stars me-1"></i>คำตอบและการวิเคราะห์จาก RiMS Copilot:</span>
                        <span id="copilotModelBadge" class="badge bg-light text-muted border font-monospace small"></span>
                    </div>
                    <div id="copilotResponseText" class="chat-bubble-ai mb-2"></div>
                </div>

                <!-- Loading Spinner -->
                <div id="copilotLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted small">กำลังวิเคราะห์ข้อมูล HOSxP ร่วมกับคลังความรู้ RAG...</div>
                </div>
            </div>
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnSubmitCopilot" onclick="sendCopilotQuery()">
                    <i class="bi bi-send-fill me-1"></i> ส่งคำถามให้ AI วิเคราะห์
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openCopilotDrawer() {
        const modal = new bootstrap.Modal(document.getElementById('copilotModal'));
        modal.show();
    }

    function setCopilotQuestion(q) {
        document.getElementById('copilotQuestionInput').value = q;
        sendCopilotQuery();
    }

    function askCopilotAbout(category, query) {
        document.getElementById('copilotQuestionInput').value = query;
        openCopilotDrawer();
        setTimeout(() => {
            sendCopilotQuery(category);
        }, 400);
    }

    function sendCopilotQuery(category = '{{ $activeTab }}') {
        const input = document.getElementById('copilotQuestionInput');
        const q = input.value.trim();
        if (!q) {
            alert('กรุณาพิมพ์คำถามก่อนครับ');
            return;
        }

        const btn = document.getElementById('btnSubmitCopilot');
        const loading = document.getElementById('copilotLoading');
        const respWrapper = document.getElementById('copilotResponseWrapper');
        const respText = document.getElementById('copilotResponseText');
        const modelBadge = document.getElementById('copilotModelBadge');

        btn.disabled = true;
        loading.classList.remove('d-none');
        respWrapper.classList.add('d-none');

        fetch('{{ route("mrec.hosxp_master.copilot_ask") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                query: q,
                tab: category
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            loading.classList.add('d-none');

            if (data.success) {
                respWrapper.classList.remove('d-none');
                modelBadge.textContent = (data.provider || 'gemini') + ' • ' + (data.model || 'flash');
                
                // Format markdown-like code blocks & bold text simply
                let html = data.answer
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Code blocks ```sql ... ```
                html = html.replace(/```(?:sql)?([\s\S]*?)```/g, function(match, code) {
                    return '<pre><code>' + code.trim() + '</code></pre>';
                });

                // Bold **text**
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                // Bullet points
                html = html.replace(/\n• (.*?)/g, '<br>• $1');
                html = html.replace(/\n- (.*?)/g, '<br>• $1');
                html = html.replace(/\n/g, '<br>');

                respText.innerHTML = html;
            } else {
                alert(data.message || 'เกิดข้อผิดพลาดในการประมวลผล');
            }
        })
        .catch(err => {
            btn.disabled = false;
            loading.classList.add('d-none');
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ AI: ' + err);
        });
    }
</script>
@endsection
