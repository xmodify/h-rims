@extends('layouts.app')

@section('content')
<style>
  .nav-tabs-custom {
    border-bottom: 2px solid #e2e8f0;
  }
  .nav-tabs-custom .nav-link {
    border: none;
    color: #64748b;
    font-weight: 500;
    padding: 10px 16px;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
  }
  .nav-tabs-custom .nav-link:hover {
    color: #0f172a;
    border-bottom-color: #cbd5e1;
  }
  .nav-tabs-custom .nav-link.active {
    color: #0284c7;
    border-bottom-color: #0284c7;
    background: none;
  }
  .status-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-left: 5px;
    vertical-align: middle;
  }
  .table-custom th {
    background-color: #f8fafc !important;
    color: #475569;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0 !important;
  }
  .table-custom td {
    vertical-align: middle;
  }
  .badge-danger {
    background-color: #fee2e2;
    color: #ef4444;
    border: 1px solid #fca5a5;
  }
  .badge-warning {
    background-color: #fef3c7;
    color: #d97706;
    border: 1px solid #fcd34d;
  }
  .badge-success {
    background-color: #dcfce7;
    color: #16a34a;
    border: 1px solid #86efac;
  }
  .card-trend {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  }
  .math-formula {
    font-family: 'Courier New', Courier, monospace;
    font-size: 0.85rem;
    color: #475569;
  }
  .ratio-group-header {
    background-color: #f1f5f9 !important;
    color: #1e293b;
    font-weight: 700;
    font-size: 0.95rem;
  }
  .hover-bg {
    transition: background-color 0.15s ease-in-out;
  }
  .hover-bg:hover {
    background-color: #f1f5f9;
  }
  .shadow-xs {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  }
  .table-row-selected {
    background-color: rgba(2, 132, 199, 0.06) !important;
  }
</style>

<div class="container-fluid pt-2 pb-4 px-lg-5" style="background-color: #f8fafc;">
    <div class="row">
        <!-- Back Button -->
        <div class="col-12 px-3 mb-1">
            <a href="{{ url('hosfin') }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; border-color: #cbd5e1; color: #475569; background-color: #fff;">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <!-- Header -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important;">
                <div>
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-graph-up text-success me-2"></i> วิเคราะห์อัตราส่วนทางการเงิน (Ratio Analysis)
                    </h5>
                    <small class="text-muted">คำนวณอัตราส่วนสภาพคล่อง ประสิทธิภาพการดำเนินงาน และกำไรของโรงพยาบาล</small>
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-wrap ms-lg-auto mt-2 mt-lg-0">
                    <!-- Budget Year Dropdown -->
                    <div class="input-group shadow-sm me-1" style="width: auto;">
                        <span class="input-group-text bg-white text-muted fw-semibold" style="font-size: 0.85rem; height: 40px; border-color: #cbd5e1;">ปีงบประมาณ</span>
                        <select id="select_budget_year" class="form-select fw-bold text-dark" style="min-width: 105px; font-size: 0.85rem; height: 40px; border-color: #cbd5e1; cursor: pointer;">
                            @php
                                $currentYear = date('Y') + 543;
                                $yearChoices = range($currentYear + 1, $currentYear - 3);
                                rsort($yearChoices);
                            @endphp
                            @foreach($yearChoices as $yr)
                                <option value="{{ $yr }}" {{ $budgetYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <a href="{{ url('hosfin/cash_register') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #059669; color: #059669;">
                        <i class="bi bi-cash-stack"></i> รับ-จ่าย (Cash)
                    </a>
                    <a href="{{ url('hosfin/ap_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #ef4444; color: #dc2626;">
                        <i class="bi bi-receipt-cutoff"></i> เจ้าหนี้ (AP)
                    </a>
                    <a href="{{ url('hosfin/ar_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #0284c7; color: #0369a1;">
                        <i class="bi bi-wallet2"></i> ลูกหนี้ (AR)
                    </a>
                    <a href="{{ url('hosfin/cost_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #d97706; color: #b45309;">
                        <i class="bi bi-pie-chart"></i> ต้นทุน (LC/MC/CC)
                    </a>
                    <a href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #3b82f6; border: 1.5px solid #3b82f6; color: #ffffff;">
                        <i class="bi bi-graph-up-arrow"></i> อัตราส่วน
                    </a>
                    <a href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669;">
                        <i class="bi bi-file-earmark-spreadsheet"></i> งบทดลอง
                    </a>

                    <!-- Settings button -->
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1 shadow-sm text-nowrap rounded-pill px-3" style="height: 40px; font-size: 0.85rem; background: #fff;" onclick="openMappingsModal()" title="ตรวจสอบการจับคู่ผังบัญชี">
                        <i class="bi bi-gear text-secondary"></i> ผังบัญชี
                    </button>
                </div>
            </div>
        </div>

        <!-- Check if data exists -->
        @if(count($importedPeriods) == 0)
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-body p-5 text-center">
                        <img src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' fill='%2394a3b8' class='bi bi-folder-x' viewBox='0 0 16 16'><path d='M.54 3.87.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3h3.982a2 2 0 0 1 1.992 2.181L15.546 8H14.54l.265-2.91A1 1 0 0 0 13.81 4H9.828a3 3 0 0 1-2.12-.879l-.83-.828A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981L1.55 8H.54z'/><path d='M11.5 15a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.707-5.354 1.647 1.646a.5.5 0 0 1-.708.708L11.5 10.707l-1.646 1.647a.5.5 0 0 1-.708-.708l1.647-1.646-1.647-1.646a.5.5 0 0 1 .708-.708l1.646 1.647 1.646-1.647a.5.5 0 0 1 .708.708z'/></svg>" alt="No Data" class="mb-3" style="opacity: 0.5;">
                        <h5 class="text-muted fw-bold">ไม่พบข้อมูลนำเข้างบทดลองในปีงบประมาณ {{ $budgetYear }}</h5>
                        <p class="text-secondary small mb-4">กรุณานำเข้าข้อมูลไฟล์งบทดลองของเดือนที่ต้องการวิเคราะห์ก่อนเรียกรายงาน</p>
                        <a href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}" class="btn btn-success rounded-pill px-4">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> ไปหน้าพัฒนางบทดลองเพื่อนำเข้า
                        </a>
                    </div>
                </div>
            </div>
        @else
            @php
                $categories = [
                    'LIQUID' => ['label' => '1. อัตราส่วนวิเคราะห์สภาพคล่อง (Liquidity Ratios)', 'codes' => ['100', '101', '102', '103', '104', '105', '105.1']],
                    'ACTIVITY' => ['label' => '2. อัตราส่วนวิเคราะห์ประสิทธิภาพการดำเนินงาน (Activity Ratios)', 'codes' => ['260', '261', '262', '263', '264']],
                    'PROFIT' => ['label' => '3. อัตราส่วนวิเคราะห์ความสามารถในการทำกำไร (Profitability Ratios)', 'codes' => ['302', '303', '304', '305', '306', '307', '310', '311', '312', '313', '314', '315', '316', '320', '321', '333', '334', 'NI']],
                ];
            @endphp

            <!-- Dynamic Trend Chart Section with Multi-Ratio Comparison Checkboxes -->
            <div class="col-12 mb-4">
                <div class="card card-trend border-0 shadow-sm rounded-4" style="border: 1.5px solid #e2e8f0 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                    <i class="bi bi-graph-up text-primary"></i> กราฟแนวโน้มแสดงความเคลื่อนไหวและเปรียบเทียบอัตราส่วนการเงิน
                                </h6>
                                <small class="text-muted">เลือกทำเครื่องหมายถูก (Checkbox) เพื่อพล็อตเปรียบเทียบหลายอัตราส่วนพร้อมกันในรอบปีงบประมาณ {{ $budgetYear }}</small>
                            </div>

                            <!-- Comparison Controls Toolbar -->
                            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                <!-- Quick Presets Button Group -->
                                <div class="btn-group btn-group-sm shadow-xs" role="group" aria-label="Quick Presets">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyPreset('liquidity')" title="เปรียบเทียบ Current, Quick, Cash Ratio">
                                        <i class="bi bi-droplet-fill text-primary"></i> สภาพคล่อง
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyPreset('profit')" title="เปรียบเทียบ Operating, Net, EBITDA Margin">
                                        <i class="bi bi-pie-chart-fill text-danger"></i> กำไร
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="applyPreset('activity')" title="เปรียบเทียบ ระยะเวลาเรียกเก็บหนี้/ชำระหนี้">
                                        <i class="bi bi-clock-history text-warning"></i> เก็บหนี้
                                    </button>
                                </div>

                                <!-- Checkbox Multi-select Dropdown -->
                                <div class="dropdown" id="compareDropdownContainer">
                                    <button class="btn btn-primary btn-sm dropdown-toggle rounded-pill px-3 shadow-sm d-flex align-items-center gap-1.5" 
                                            type="button" id="compareDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" 
                                            style="font-size: 0.85rem; font-weight: 700;">
                                        <i class="bi bi-check2-square"></i>
                                        <span>เลือกกราฟเปรียบเทียบ</span>
                                        <span class="badge bg-white text-primary rounded-pill ms-1 fw-bold" id="selectedCountBadge">1</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3 rounded-4" style="width: 400px; max-height: 480px; overflow-y: auto; z-index: 1060; border: 1.5px solid #cbd5e1 !important;">
                                        <!-- Dropdown header -->
                                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="fw-bold text-dark small"><i class="bi bi-ui-checks-grid me-1 text-primary"></i> ติ๊กเลือกอัตราส่วนเพื่อเปรียบเทียบ</span>
                                            <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none p-0 small" onclick="clearAllMetrics()">
                                                <i class="bi bi-arrow-counterclockwise"></i> รีเซ็ต
                                            </button>
                                        </div>

                                        <!-- Search inside dropdown -->
                                        <div class="mb-2">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                                <input type="text" class="form-control border-start-0" id="searchRatioCheckbox" placeholder="ค้นหาชื่อ หรือ รหัส..." style="font-size: 0.82rem;">
                                            </div>
                                        </div>

                                        <!-- Checkbox List grouped by category -->
                                        <div id="ratioCheckboxList">
                                            @foreach($categories as $catKey => $catInfo)
                                                <div class="category-checkbox-group mb-2.5">
                                                    <div class="fw-bold text-secondary text-uppercase mb-1 px-1" style="font-size: 0.72rem; letter-spacing: 0.3px;">
                                                        {{ $catInfo['label'] }}
                                                    </div>
                                                    @foreach($catInfo['codes'] as $code)
                                                        @if(isset($ratios[$code]))
                                                            @php $r = $ratios[$code]; @endphp
                                                            <div class="form-check ratio-checkbox-item py-1 px-2 rounded hover-bg" data-code="{{ $code }}" data-name="{{ strtolower($code . ' ' . $r['name']) }}">
                                                                <input class="form-check-input chart-metric-checkbox" type="checkbox" value="{{ $code }}" id="chk_{{ str_replace('.', '_', $code) }}" style="cursor: pointer;">
                                                                <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="chk_{{ str_replace('.', '_', $code) }}" style="cursor: pointer; font-size: 0.82rem;">
                                                                    <span class="text-dark">
                                                                        <strong class="text-primary font-monospace">{{ $code }}</strong> - {{ $r['name'] }}
                                                                    </span>
                                                                    <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.7rem;">{{ $r['unit'] }}</span>
                                                                </label>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- DataLabels switch -->
                                <div class="form-check form-switch mb-0 ms-1 d-flex align-items-center gap-1" title="เปิด/ปิด การแสดงตัวเลขบนจุดกราฟ">
                                    <input class="form-check-input" type="checkbox" id="toggleDataLabels" checked style="cursor: pointer;">
                                    <label class="form-check-label small text-muted text-nowrap" for="toggleDataLabels" style="cursor: pointer; font-size: 0.78rem;">ตัวเลขบนกราฟ</label>
                                </div>
                            </div>
                        </div>

                        <!-- Active Selected Ratio Badges Bar -->
                        <div class="d-flex align-items-center gap-1.5 flex-wrap pt-2 mt-1 border-top" id="activeRatioBadgesBar" style="border-color: #f1f5f9 !important;">
                            <span class="text-muted small fw-bold me-1" style="font-size: 0.78rem;">
                                <i class="bi bi-layers-half text-primary me-1"></i> รายการเปรียบเทียบ:
                            </span>
                            <div class="d-flex align-items-center gap-1.5 flex-wrap" id="activeBadgesContainer">
                                <!-- Javascript renders active pills here -->
                            </div>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" id="axisInfoBadge" style="font-size: 0.72rem; display: none;"></span>
                            </div>
                        </div>

                        <!-- Chart Canvas -->
                        <div class="chart-container mt-3" style="position: relative; height: 320px; width:100%">
                            <canvas id="ratioTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Tabs & Table -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <ul class="nav nav-tabs nav-tabs-custom" id="periodTabs" role="tablist">
                            <!-- All year tab -->
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedPeriod === 'all' ? 'active' : '' }}" 
                                   href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}&period=all">
                                    รวมทั้งปี
                                </a>
                            </li>
                            <!-- Monthly tabs -->
                            @foreach($periods as $p)
                                @php
                                    $hasData = in_array($p['period'], $importedPeriods);
                                @endphp
                                <li class="nav-item">
                                    <a class="nav-link {{ $selectedPeriod === $p['period'] ? 'active' : '' }}" 
                                       href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}&period={{ $p['period'] }}">
                                        {{ $p['label'] }}
                                         @if($hasData)
                                             <span class="status-dot bg-success" title="มีข้อมูลนำเข้าแล้ว"></span>
                                         @else
                                             <span class="status-dot bg-secondary bg-opacity-25" title="ยังไม่มีข้อมูล"></span>
                                         @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Data table layout -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                @if($selectedPeriod === 'all')
                                    <i class="bi bi-calculator me-1 text-primary"></i> รายงานวิเคราะห์อัตราส่วนการเงินสะสมประจำปีงบประมาณ {{ $budgetYear }}
                                @else
                                    <i class="bi bi-calendar3 me-1 text-primary"></i> รายงานวิเคราะห์อัตราส่วนการเงินประจำเดือน {{ collect($periods)->firstWhere('period', $selectedPeriod)['label'] ?? $selectedPeriod }}
                                @endif
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" id="searchRatioTable" class="form-control" placeholder="ค้นหาอัตราส่วน..." style="font-size: 0.85rem;">
                                </div>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-sm text-nowrap d-inline-flex align-items-center gap-1" style="flex-shrink: 0;" onclick="exportExcel()">
                                    <i class="bi bi-file-earmark-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border">
                            <table id="ratio_report_table" class="table table-hover table-custom mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 55px;" title="เลือกเพื่อพล็อตกราฟเปรียบเทียบ">กราฟ</th>
                                        <th class="text-center" style="width: 75px;">รหัส</th>
                                        <th style="min-width: 250px;">ชื่ออัตราส่วนทางการเงิน</th>
                                        <th style="min-width: 300px;">สูตรการคำนวณและตัวเลขรายละเอียดของยอดเงิน</th>
                                        <th class="text-end" style="width: 140px;">ผลลัพธ์</th>
                                        <th class="text-center" style="width: 90px;">หน่วย</th>
                                        <th class="text-center" style="width: 140px;">เกณฑ์ประเมิน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $catKey => $catInfo)
                                        <tr class="ratio-group-header">
                                            <td colspan="7">{{ $catInfo['label'] }}</td>
                                        </tr>
                                        @foreach($catInfo['codes'] as $code)
                                            @if(isset($ratios[$code]))
                                                @php
                                                    $r = $ratios[$code];
                                                    // Determine health check status
                                                    $statusLabel = '-';
                                                    $statusBadge = '';
                                                    if ($code === '100') {
                                                        if ($r['value'] >= 1.5) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        elseif ($r['value'] >= 1.0) { $statusLabel = '🟡 เฝ้าระวัง'; $statusBadge = 'badge-warning'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '101') {
                                                        if ($r['value'] >= 1.0) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '102') {
                                                        if ($r['value'] >= 0.8) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '104' || $code === '105') {
                                                        if ($r['value'] >= 0) { $statusLabel = '🟢 ปกติ (บวก)'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต (ติดลบ)'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '260') {
                                                        if ($r['value'] <= 90) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        elseif ($r['value'] <= 180) { $statusLabel = '🟡 เฝ้าระวัง'; $statusBadge = 'badge-warning'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '261' || $code === '262' || $code === '263') {
                                                        if ($r['value'] <= 60) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '264') {
                                                        if ($r['value'] <= 60) { $statusLabel = '🟢 ปกติ'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '307' || $code === '320' || $code === '321') {
                                                        if ($r['value'] >= 0) { $statusLabel = '🟢 มีกำไร'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต (ขาดทุน)'; $statusBadge = 'badge-danger'; }
                                                    } elseif ($code === '333' || $code === '334') {
                                                        if ($r['value'] >= 0) { $statusLabel = '🟢 ปกติ (บวก)'; $statusBadge = 'badge-success'; }
                                                        else { $statusLabel = '🔴 วิกฤต (ติดลบ)'; $statusBadge = 'badge-danger'; }
                                                    }
                                                @endphp
                                                <tr id="tbl_row_{{ str_replace('.', '_', $code) }}">
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center mb-0">
                                                            <input class="form-check-input table-ratio-checkbox" type="checkbox" value="{{ $code }}" id="tbl_chk_{{ str_replace('.', '_', $code) }}" style="cursor: pointer; width: 18px; height: 18px;" title="เลือกเพื่อเปรียบเทียบในกราฟ">
                                                        </div>
                                                    </td>
                                                    <td class="text-center fw-bold text-secondary ratio-code-cell">{{ $code }}</td>
                                                    <td class="ratio-name-cell">
                                                        <div class="fw-bold text-dark">{{ $r['name'] }}</div>
                                                        <small class="text-muted math-formula">{{ $r['numerator_name'] }} ÷ {{ $r['denominator_name'] }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-secondary">{{ $r['numerator_name'] }}:</span>
                                                                <span class="fw-bold text-dark">{{ number_format($r['num_value'], 2) }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-0">
                                                                <span class="text-secondary">{{ $r['denominator_name'] }}:</span>
                                                                <span class="fw-bold text-dark">{{ number_format($r['den_value'], 2) }}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold text-dark" style="font-size: 1.05rem;">
                                                        {{ number_format($r['value'], $r['precision']) }}
                                                    </td>
                                                    <td class="text-center text-secondary small">{{ $r['unit'] }}</td>
                                                    <td class="text-center">
                                                        @if(!empty($statusBadge))
                                                            <span class="badge px-3 py-2 rounded-pill {{ $statusBadge }}" style="font-size: 0.8rem;">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mappings Setup Modal -->
<div class="modal fade" id="mappingsModal" tabindex="-1" aria-labelledby="mappingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold" id="mappingsModalLabel">
                    <i class="bi bi-list-ul me-2 text-warning"></i> รายงานการจับคู่ผังบัญชี (Account Mappings Report)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Header Filters and Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 bg-white rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <!-- Group Category Filter -->
                        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 500px;">
                            <label class="fw-bold text-dark mb-0 text-nowrap" style="font-size: 0.9rem;"><i class="bi bi-funnel-fill text-primary me-1"></i> กรองตามหมวด/กลุ่มบัญชี:</label>
                            <select id="filter_group_code" class="form-select fw-semibold" style="font-size: 0.9rem;">
                                <!-- Loaded via AJAX -->
                            </select>
                        </div>
                        
                        <!-- Search and Action -->
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="max-width: 250px;">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchMappingQuery" class="form-control border-start-0" placeholder="ค้นหาโดยกลุ่ม รหัสบัญชี...">
                            </div>
                            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm" onclick="printMappings()">
                                <i class="bi bi-printer"></i> พิมพ์รายงาน
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-responsive" style="max-height: 440px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #fff;">
                    <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 120px; padding: 12px 16px;">รหัสกลุ่ม</th>
                                <th style="padding: 12px 16px;">ชื่อกลุ่มรายละเอียดประกอบงบ</th>
                                <th style="width: 180px; padding: 12px 16px;">รหัสผังบัญชี</th>
                                <th style="padding: 12px 16px;">ชื่อผังบัญชี</th>
                            </tr>
                        </thead>
                        <tbody id="mappingsTableBody">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination controls -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <small class="text-muted fw-semibold" id="mappingPaginationInfo">กำลังโหลด...</small>
                    <nav aria-label="Mapping Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1" id="mappingPaginationButtons">
                            <!-- Page items loaded via AJAX -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script>
    // Selected budget year change
    document.getElementById('select_budget_year').addEventListener('change', function() {
        window.location.href = "{{ url('hosfin/ratio_report') }}?budget_year=" + this.value + "&period={{ $selectedPeriod }}";
    });

    // Excel Export
    // Excel Export
    function exportExcel() {
        const periodLabel = "{{ $selectedPeriod === 'all' ? 'รวมทั้งปี' : (collect($periods)->firstWhere('period', $selectedPeriod)['label'] ?? $selectedPeriod) }}";
        const filename = `วิเคราะห์อัตราส่วนการเงินประจำปีงบประมาณ_${{ $budgetYear }}_${periodLabel}`;
        exportTableToExcel('ratio_report_table', filename);
    }

    function exportTableToExcel(tableId, filename = '') {
        if (typeof XLSX === 'undefined') {
            let script = document.createElement('script');
            script.src = "{{ asset('assets/vendor/xlsx.full.min.js') }}";
            script.onload = function() {
                doExportTableToExcel(tableId, filename);
            };
            document.head.appendChild(script);
        } else {
            doExportTableToExcel(tableId, filename);
        }
    }

    function doExportTableToExcel(tableId, filename) {
        var dataType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8';
        var tableSelect = document.getElementById(tableId);
        var wb = XLSX.utils.table_to_book(tableSelect, {sheet: "Sheet1"});
        var wbout = XLSX.write(wb, {bookType: 'xlsx', type: 'binary'});
        
        function s2ab(s) {
            var buf = new ArrayBuffer(s.length);
            var view = new Uint8Array(buf);
            for (var i=0; i<s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
            return buf;
        }
        
        var blob = new Blob([s2ab(wbout)], {type: dataType});
        filename = filename ? filename + '.xlsx' : 'excel_data.xlsx';
        
        var downloadLink = document.createElement("a");
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = filename;
        downloadLink.click();
    }

    // Chart.js Configuration & Multi-Ratio Comparison
    @if(count($importedPeriods) > 0)
        const chartDataMap = @json($chartData);
        const chartLabels = Object.keys(chartDataMap); // Monthly period labels
        const ratioMetaMap = @json($ratios);
        
        let chart = null;
        let selectedMetrics = ['100']; // default to Current Ratio

        // 12 Modern Harmonious Line Colors for multi-comparison
        const palette = [
            { border: '#10b981', bg: 'rgba(16, 185, 129, 0.15)' }, // Emerald Green
            { border: '#0284c7', bg: 'rgba(2, 132, 199, 0.15)' },   // Sky Blue
            { border: '#f59e0b', bg: 'rgba(245, 158, 11, 0.15)' },  // Amber
            { border: '#ef4444', bg: 'rgba(239, 68, 68, 0.15)' },   // Red
            { border: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.15)' },  // Purple
            { border: '#06b6d4', bg: 'rgba(6, 182, 212, 0.15)' },   // Cyan
            { border: '#ec4899', bg: 'rgba(236, 72, 153, 0.15)' },  // Pink
            { border: '#6366f1', bg: 'rgba(99, 102, 241, 0.15)' },  // Indigo
            { border: '#14b8a6', bg: 'rgba(20, 184, 166, 0.15)' },  // Teal
            { border: '#f97316', bg: 'rgba(249, 115, 22, 0.15)' },  // Orange
            { border: '#64748b', bg: 'rgba(100, 116, 139, 0.15)' }, // Slate
            { border: '#84cc16', bg: 'rgba(132, 204, 22, 0.15)' }   // Lime
        ];

        // Custom plugin to draw vertical dashed line on hover
        const hoverLinePlugin = {
            id: 'hoverLine',
            afterDraw: (chartInstance) => {
                if (chartInstance.tooltip?._active?.length) {
                    const activePoint = chartInstance.tooltip._active[0];
                    const ctx = chartInstance.ctx;
                    const x = activePoint.element.x;
                    const topY = chartInstance.scales.y.top;
                    const bottomY = chartInstance.scales.y.bottom;
                    
                    ctx.save();
                    ctx.beginPath();
                    ctx.moveTo(x, topY);
                    ctx.lineTo(x, bottomY);
                    ctx.lineWidth = 1.5;
                    ctx.strokeStyle = '#64748b';
                    ctx.setLineDash([3, 3]);
                    ctx.stroke();
                    ctx.restore();
                }
            }
        };

        function updateChart(metricsArray) {
            if (Array.isArray(metricsArray)) {
                selectedMetrics = metricsArray.filter(c => ratioMetaMap[c] !== undefined);
            } else if (typeof metricsArray === 'string') {
                selectedMetrics = [metricsArray];
            }
            if (selectedMetrics.length === 0) {
                selectedMetrics = ['100'];
            }

            // 1. Sync Dropdown Checkboxes
            document.querySelectorAll('.chart-metric-checkbox').forEach(chk => {
                chk.checked = selectedMetrics.includes(chk.value);
            });

            // 2. Sync Table Checkboxes and Row Highlight
            document.querySelectorAll('.table-ratio-checkbox').forEach(chk => {
                const isChecked = selectedMetrics.includes(chk.value);
                chk.checked = isChecked;
                const row = chk.closest('tr');
                if (row) {
                    if (isChecked) {
                        row.classList.add('table-row-selected');
                    } else {
                        row.classList.remove('table-row-selected');
                    }
                }
            });

            // 3. Update Badges Count
            const countBadge = document.getElementById('selectedCountBadge');
            if (countBadge) countBadge.innerText = selectedMetrics.length;
            const activeCount = document.getElementById('activeRatioCount');
            if (activeCount) activeCount.innerText = selectedMetrics.length;

            // 4. Render Active Badge Pills
            const badgesContainer = document.getElementById('activeBadgesContainer');
            if (badgesContainer) {
                badgesContainer.innerHTML = '';
                selectedMetrics.forEach((code, idx) => {
                    const meta = ratioMetaMap[code] || { name: code, unit: '' };
                    const color = palette[idx % palette.length];
                    const pill = document.createElement('span');
                    pill.className = 'badge d-inline-flex align-items-center gap-1.5 py-1 px-2.5 rounded-pill shadow-xs me-1 mb-1';
                    pill.style.backgroundColor = color.bg;
                    pill.style.color = color.border;
                    pill.style.border = `1.5px solid ${color.border}`;
                    pill.style.fontSize = '0.8rem';
                    pill.style.fontWeight = '600';
                    pill.innerHTML = `
                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background-color:${color.border};"></span>
                        <span>${code} - ${meta.name}</span>
                        <span class="text-muted small">(${meta.unit})</span>
                        <i class="bi bi-x-circle-fill ms-1 text-danger opacity-75 hover-opacity-100" style="cursor: pointer; font-size: 0.85rem;" title="นำออกจากการเปรียบเทียบ" onclick="toggleMetric('${code}', false)"></i>
                    `;
                    badgesContainer.appendChild(pill);
                });
            }

            // 5. Units & Dual Scales Handling (Smart Axis)
            const showDataLabels = document.getElementById('toggleDataLabels') ? document.getElementById('toggleDataLabels').checked : true;
            const isSingle = selectedMetrics.length === 1;

            const distinctUnits = [...new Set(selectedMetrics.map(c => ratioMetaMap[c]?.unit || ''))];
            const hasSecondaryAxis = distinctUnits.length >= 2;
            const primaryUnit = distinctUnits[0] || '';
            const secondaryUnit = distinctUnits[1] || '';

            const axisInfoBadge = document.getElementById('axisInfoBadge');
            if (axisInfoBadge) {
                if (hasSecondaryAxis) {
                    axisInfoBadge.style.display = '';
                    axisInfoBadge.innerHTML = `<i class="bi bi-arrows-expand text-primary me-1"></i> แกนซ้าย: <strong>${primaryUnit}</strong> | แกนขวา: <strong>${secondaryUnit}</strong>`;
                } else {
                    axisInfoBadge.style.display = 'none';
                }
            }

            // 6. Build Chart Datasets
            const datasets = selectedMetrics.map((code, idx) => {
                const meta = ratioMetaMap[code] || { name: code, unit: '', precision: 2 };
                const dataValues = chartLabels.map(label => chartDataMap[label][code] !== undefined ? chartDataMap[label][code] : 0.0);
                const color = palette[idx % palette.length];
                const isSecondary = hasSecondaryAxis && meta.unit !== primaryUnit;

                if (isSingle) {
                    // Single Metric Style: Gradient fill + Dynamic color
                    return {
                        label: `${code} - ${meta.name}`,
                        unit: meta.unit,
                        data: dataValues,
                        borderWidth: 3.5,
                        yAxisID: 'y',
                        fill: {
                            target: 'origin',
                            above: 'rgba(16, 185, 129, 0.15)',
                            below: 'rgba(239, 68, 68, 0.15)'
                        },
                        tension: 0.35,
                        pointBackgroundColor: (context) => {
                            const val = context.raw ?? 0;
                            return val < 0 ? '#ef4444' : '#10b981';
                        },
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        segment: {
                            borderColor: (ctxSegment) => {
                                const val = ctxSegment.p0.parsed.y;
                                return val < 0 ? '#ef4444' : '#10b981';
                            }
                        }
                    };
                } else {
                    // Multiple Metrics Style: Crisp colored lines
                    return {
                        label: `${code} - ${meta.name}`,
                        unit: meta.unit,
                        data: dataValues,
                        borderWidth: 2.8,
                        borderColor: color.border,
                        backgroundColor: color.border,
                        yAxisID: isSecondary ? 'y1' : 'y',
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: color.border,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1.5,
                        pointRadius: 4.5,
                        pointHoverRadius: 7
                    };
                }
            });

            const ctx = document.getElementById('ratioTrendChart').getContext('2d');
            if (chart) {
                chart.destroy();
            }

            chart = new Chart(ctx, {
                type: 'line',
                plugins: [ChartDataLabels, hoverLinePlugin],
                data: {
                    labels: chartLabels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    layout: {
                        padding: {
                            top: isSingle ? 24 : 26,
                            bottom: 12,
                            left: 10,
                            right: hasSecondaryAxis ? 20 : 15
                        }
                    },
                    plugins: {
                        legend: {
                            display: !isSingle,
                            position: 'top',
                            align: 'center',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                borderRadius: 4,
                                usePointStyle: false,
                                padding: 15,
                                font: { size: 11, weight: 'bold' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    const unit = context.dataset.unit ? ' ' + context.dataset.unit : '';
                                    const val = context.raw !== undefined && context.raw !== null ? Number(context.raw).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-';
                                    return ' ' + context.dataset.label + ': ' + val + unit;
                                }
                            }
                        },
                        datalabels: {
                            display: function(context) {
                                return showDataLabels;
                            },
                            clamp: true,
                            offset: 4,
                            align: function(context) {
                                const val = context.dataset.data[context.dataIndex];
                                if (val < 0) return 'bottom';
                                if (isSingle) return 'top';
                                return (context.datasetIndex % 2 === 0) ? 'top' : 'bottom';
                            },
                            anchor: function(context) {
                                const val = context.dataset.data[context.dataIndex];
                                if (val < 0) return 'start';
                                if (isSingle) return 'end';
                                return (context.datasetIndex % 2 === 0) ? 'end' : 'start';
                            },
                            backgroundColor: function(context) {
                                if (isSingle) {
                                    const val = context.dataset.data[context.dataIndex];
                                    return val < 0 ? '#ef4444' : '#10b981';
                                }
                                return context.dataset.borderColor || '#0284c7';
                            },
                            borderRadius: 4,
                            color: '#ffffff',
                            font: {
                                weight: 'bold',
                                size: 9
                            },
                            formatter: function(value) {
                                if (value === null || value === undefined || isNaN(value)) return '';
                                if (Math.abs(value) < 50) {
                                    return Number(value).toFixed(2);
                                } else {
                                    return Number(value).toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
                                }
                            },
                            padding: {
                                top: 2.5,
                                bottom: 2.5,
                                left: 4.5,
                                right: 4.5
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: primaryUnit ? `หน่วย (${primaryUnit})` : '',
                                color: '#64748b',
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                color: function(context) {
                                    if (context.tick && context.tick.value === 0) {
                                        return '#475569';
                                    }
                                    return '#f1f5f9';
                                },
                                lineWidth: function(context) {
                                    if (context.tick && context.tick.value === 0) {
                                        return 2;
                                    }
                                    return 1;
                                }
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                callback: function(value) {
                                    if (Math.abs(value) >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    }
                                    if (Math.abs(value) >= 1000) {
                                        return (value / 1000).toFixed(0) + 'K';
                                    }
                                    return value.toLocaleString();
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: hasSecondaryAxis,
                            position: 'right',
                            title: {
                                display: hasSecondaryAxis,
                                text: secondaryUnit ? `หน่วย (${secondaryUnit})` : '',
                                color: '#64748b',
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11 },
                                callback: function(value) {
                                    if (Math.abs(value) >= 1000000) {
                                        return (value / 1000000).toFixed(1) + 'M';
                                    }
                                    if (Math.abs(value) >= 1000) {
                                        return (value / 1000).toFixed(0) + 'K';
                                    }
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748b',
                                font: { size: 11, weight: '500' }
                            }
                        }
                    }
                }
            });
        }

        // Global Helper Functions for Checkbox and Presets
        window.toggleMetric = function(code, forceState) {
            const exists = selectedMetrics.includes(code);
            const newState = forceState !== undefined ? forceState : !exists;
            if (newState && !exists) {
                selectedMetrics.push(code);
            } else if (!newState && exists) {
                selectedMetrics = selectedMetrics.filter(c => c !== code);
            }
            if (selectedMetrics.length === 0) {
                selectedMetrics = ['100']; // default fallback
            }
            updateChart(selectedMetrics);
        };

        window.applyPreset = function(preset) {
            if (preset === 'liquidity') {
                updateChart(['100', '101', '102']);
            } else if (preset === 'profit') {
                updateChart(['302', '303', '304', '307']);
            } else if (preset === 'activity') {
                updateChart(['260', '261', '262', '264']);
            } else if (preset === 'return') {
                updateChart(['310', '311', '321']);
            } else {
                updateChart(['100']);
            }
        };

        window.clearAllMetrics = function() {
            updateChart(['100']);
        };

        // Event Listeners for Checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chart with Current Ratio
            updateChart('100');

            // Listen to checkbox changes inside dropdown and table
            document.addEventListener('change', function(e) {
                if (e.target && (e.target.classList.contains('chart-metric-checkbox') || e.target.classList.contains('table-ratio-checkbox'))) {
                    const code = e.target.value;
                    toggleMetric(code, e.target.checked);
                }
            });

            // Explicit listener for DataLabels switch
            const toggleDataLabels = document.getElementById('toggleDataLabels');
            if (toggleDataLabels) {
                toggleDataLabels.addEventListener('change', function() {
                    updateChart(selectedMetrics);
                });
            }

            // Filter inside dropdown
            const searchRatioCheckbox = document.getElementById('searchRatioCheckbox');
            if (searchRatioCheckbox) {
                searchRatioCheckbox.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    document.querySelectorAll('.ratio-checkbox-item').forEach(item => {
                        const name = item.getAttribute('data-name') || '';
                        item.style.display = name.includes(q) ? '' : 'none';
                    });
                });
            }
        });

        // Search Ratio Table client-side filter
        const searchInput = document.getElementById('searchRatioTable');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const table = document.querySelector('.table-custom');
                if (!table) return;

                const rows = table.querySelectorAll('tbody tr');
                let currentHeaderRow = null;
                let visibleRowsInGroup = 0;

                rows.forEach(row => {
                    if (row.classList.contains('ratio-group-header')) {
                        if (currentHeaderRow) {
                            currentHeaderRow.style.display = visibleRowsInGroup === 0 ? 'none' : '';
                        }
                        currentHeaderRow = row;
                        visibleRowsInGroup = 0;
                        return;
                    }

                    const codeCell = row.querySelector('.ratio-code-cell');
                    const code = codeCell ? codeCell.innerText.toLowerCase() : '';
                    
                    const nameDiv = row.querySelector('.ratio-name-cell div');
                    const name = nameDiv ? nameDiv.innerText.toLowerCase() : '';
                    
                    const formulaSmall = row.querySelector('.ratio-name-cell small');
                    const formula = formulaSmall ? formulaSmall.innerText.toLowerCase() : '';

                    if (code.includes(query) || name.includes(query) || formula.includes(query)) {
                        row.style.display = '';
                        visibleRowsInGroup++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (currentHeaderRow) {
                    currentHeaderRow.style.display = visibleRowsInGroup === 0 ? 'none' : '';
                }
            });
        }
    @endif

    // Mappings Viewer AJAX pagination and print
    let currentMappingPage = 1;
    let searchMappingTimeout = null;

    function openMappingsModal() {
        $('#mappingsModal').modal('show');
        loadGroupDropdownAndMappings(1);
    }

    function loadGroupDropdownAndMappings(page = 1) {
        currentMappingPage = page;
        const groupSelect = document.getElementById('filter_group_code');
        const selectedGroup = groupSelect ? groupSelect.value : '';
        const q = document.getElementById('searchMappingQuery').value.trim();
        
        const tbody = document.getElementById('mappingsTableBody');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>กำลังโหลดข้อมูล...</td></tr>`;

        fetch(`{{ url('hosfin/mappings') }}?page=${page}&group_code=${selectedGroup}&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Populate Group dropdown if empty
                    if (groupSelect.children.length === 0) {
                        let optionsHtml = '<option value="">-- แสดงทั้งหมด (Show All) --</option>';
                        data.groups.forEach(g => {
                            optionsHtml += `<option value="${g.group_code}">${g.group_code} - ${g.group_name}</option>`;
                        });
                        groupSelect.innerHTML = optionsHtml;
                    }
                    
                    // Render mappings
                    if (data.mappings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบข้อมูลการจับคู่ผังบัญชี</td></tr>`;
                    } else {
                        let rowsHtml = '';
                        data.mappings.forEach(row => {
                            const accName = row.account_name ? row.account_name : '-';
                            rowsHtml += `
                                <tr>
                                    <td class="fw-semibold text-secondary" style="padding: 10px 16px;">${row.group_code}</td>
                                    <td class="text-dark" style="padding: 10px 16px;">${row.group_name}</td>
                                    <td class="fw-bold text-dark" style="padding: 10px 16px;">${row.account_code}</td>
                                    <td class="text-secondary" style="padding: 10px 16px;">${accName}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = rowsHtml;
                    }

                    // Render pagination stats
                    document.getElementById('mappingPaginationInfo').innerText = `แสดงรายการที่ ${data.mappings.length > 0 ? (data.current_page - 1) * 25 + 1 : 0} ถึง ${(data.current_page - 1) * 25 + data.mappings.length} จากทั้งหมด ${data.total} รายการ`;

                    // Render page numbers like a real datatable
                    renderPaginationButtons(data.current_page, data.last_page);
                }
            });
    }

    function renderPaginationButtons(currentPage, lastPage) {
        const container = document.getElementById('mappingPaginationButtons');
        let html = '';

        if (lastPage <= 1) {
            container.innerHTML = '';
            return;
        }

        // Previous page button
        html += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${currentPage - 1})" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </button>
            </li>
        `;

        // Page number buttons
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(lastPage, currentPage + 2);

        if (startPage > 1) {
            html += `<li class="page-item"><button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(1)">1</button></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${currentPage === i ? 'active' : ''}">
                    <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${i})">${i}</button>
                </li>
            `;
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${lastPage})">${lastPage}</button></li>`;
        }

        // Next page button
        html += `
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <button type="button" class="page-link" onclick="loadGroupDropdownAndMappings(${currentPage + 1})" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </button>
            </li>
        `;

        container.innerHTML = html;
    }

    function printMappings() {
        const groupSelect = document.getElementById('filter_group_code');
        const selectedGroup = groupSelect.value;
        const groupText = selectedGroup ? groupSelect.options[groupSelect.selectedIndex].text : 'ทั้งหมด';
        const q = document.getElementById('searchMappingQuery').value.trim();

        // Load all matching items (without pagination) using print=1 parameter
        fetch(`{{ url('hosfin/mappings') }}?print=1&group_code=${selectedGroup}&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let printWindow = window.open('', '_blank');
                    let html = `
                        <html>
                        <head>
                            <title>รายงานการจับคู่ผังบัญชี - กลุ่ม: ${groupText}</title>
                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
                            <style>
                                @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap');
                                body { font-family: 'Sarabun', sans-serif; padding: 40px; background-color: #fff; }
                                th, td { padding: 10px 14px; font-size: 0.95rem; }
                                h3 { font-weight: 700; color: #0d6efd; }
                                @media print {
                                    .no-print { display: none !important; }
                                    body { padding: 0; }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3>รายงานการจับคู่ผังบัญชี (Account Mappings Report)</h3>
                                <button class="btn btn-primary no-print" onclick="window.print()"><i class="bi bi-printer"></i> พิมพ์ (Print)</button>
                            </div>
                            <div class="mb-4">
                                <p class="mb-1 text-muted"><strong>ตัวกรองกลุ่มบัญชี:</strong> ${groupText}</p>
                                ${q ? `<p class="mb-1 text-muted"><strong>คำค้นหา:</strong> ${q}</p>` : ''}
                                <p class="mb-0 text-muted">พิมพ์ ณ วันที่: ${new Date().toLocaleDateString('th-TH')} เวลา ${new Date().toLocaleTimeString('th-TH')} | จำนวนทั้งหมด: ${data.mappings.length} รายการ</p>
                            </div>
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 120px;">รหัสกลุ่ม</th>
                                        <th>ชื่อกลุ่มรายละเอียดประกอบงบ</th>
                                        <th style="width: 200px;">รหัสผังบัญชี</th>
                                        <th>ชื่อผังบัญชี</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    if (data.mappings.length === 0) {
                        html += `<tr><td colspan="4" class="text-center py-4">ไม่พบข้อมูลการจับคู่ผังบัญชี</td></tr>`;
                    } else {
                        data.mappings.forEach(row => {
                            const accName = row.account_name ? row.account_name : '-';
                            html += `
                                <tr>
                                    <td class="fw-semibold">${row.group_code}</td>
                                    <td>${row.group_name}</td>
                                    <td class="fw-bold">${row.account_code}</td>
                                    <td>${accName}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    html += `
                                </tbody>
                            </table>
                        </body>
                        </html>
                    `;
                    printWindow.document.write(html);
                    printWindow.document.close();
                }
            });
    }

    // Bind event listeners
    document.getElementById('filter_group_code').addEventListener('change', function() {
        loadGroupDropdownAndMappings(1);
    });

    document.getElementById('searchMappingQuery').addEventListener('input', function() {
        clearTimeout(searchMappingTimeout);
        searchMappingTimeout = setTimeout(() => {
            loadGroupDropdownAndMappings(1);
        }, 300);
    });
</script>
@endsection
