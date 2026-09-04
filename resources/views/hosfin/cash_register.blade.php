@extends('layouts.app')

@section('content')
<style>
    .page-header-box {
        background: #ffffff;
        border-radius: 1rem;
        padding: 1.25rem 1.75rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        border-left: 5px solid #059669;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .kpi-card {
        background: #ffffff;
        border-radius: 1rem;
        padding: 1.25rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.05);
    }
    .nav-tabs-custom {
        border-bottom: 2px solid #e2e8f0;
    }
    .nav-tabs-custom .nav-link {
        font-weight: 600;
        font-size: 0.88rem;
        color: #64748b;
        border: none;
        padding: 0.65rem 1.15rem;
        border-radius: 0.75rem 0.75rem 0 0;
        transition: all 0.2s;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #059669;
        background-color: #f1fdf6;
    }
    .nav-tabs-custom .nav-link.active {
        color: #059669;
        background-color: #ffffff;
        border-bottom: 3px solid #059669;
        font-weight: 700;
    }
    .table-custom thead th {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 700;
        font-size: 0.85rem;
        border-bottom: 2px solid #cbd5e1 !important;
        white-space: nowrap;
    }
    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }
    .accordion-day-toggle {
        cursor: pointer;
    }
    .accordion-day-toggle:hover {
        background-color: #ecfdf5 !important;
    }

    /* Modern DataTables Styling */
    .dataTables_length label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        margin-bottom: 0 !important;
        font-size: 0.85rem !important;
        color: #475569 !important;
        font-weight: 500 !important;
    }
    .dataTables_length select {
        border-radius: 8px !important;
        padding: 4px 10px !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        background-color: #ffffff !important;
        height: 35px !important;
        cursor: pointer !important;
    }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #059669;
        border-color: #059669;
        color: #ffffff;
    }
    .dataTables_wrapper .pagination .page-link {
        border-radius: 6px;
        margin: 0 2px;
        font-size: 0.82rem;
        color: #475569;
    }
</style>

<div class="container-fluid pt-2 pb-4 px-lg-5" style="background-color: #f8fafc; min-height: 100vh;">
    <!-- Back Button -->
    <div class="row">
        <div class="col-12 px-3 mb-1">
            <a href="{{ url('hosfin') }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; border-color: #cbd5e1; color: #475569; background-color: #fff;">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <!-- Header -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2" style="border-left-color: #059669 !important;">
                <div>
                    <h5 class="mb-0 fw-bold" style="color: #059669;">
                        <i class="bi bi-cash-stack me-2"></i> ทะเบียนรับ-จ่ายเงินสดและเงินฝากธนาคาร (Cash & Bank Register)
                    </h5>
                    <small class="text-muted">สมุดเงินสดและทะเบียนคุมยอดเงินฝากธนาคารทุกบัญชี เกณฑ์เงินสดจริง (Cash Basis) จากระบบ HosFin GL</small>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap ms-lg-auto mt-2 mt-lg-0">
                    <!-- Budget Year Dropdown -->
                    <div class="input-group shadow-sm me-1" style="width: auto;">
                        <span class="input-group-text bg-white text-muted fw-semibold" style="font-size: 0.85rem; height: 40px; border-color: #cbd5e1;">ปีงบประมาณ</span>
                        <select id="select_budget_year" class="form-select fw-bold text-dark" style="min-width: 105px; font-size: 0.85rem; height: 40px; border-color: #cbd5e1; cursor: pointer;">
                            @foreach($yearChoices as $yr)
                                <option value="{{ $yr }}" {{ $budgetYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- [รับ-จ่าย (Cash)] placed BEFORE [เจ้าหนี้ (AP)] -->
                    <a href="{{ url('hosfin/cash_register') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #059669; border: 1.5px solid #059669; color: #ffffff;">
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
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #d97706; color: #d97706;">
                        <i class="bi bi-pie-chart"></i> ต้นทุน (LC/MC/CC)
                    </a>
                    <a href="{{ url('hosfin/ratio_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #3b82f6; color: #2563eb;">
                        <i class="bi bi-graph-up-arrow"></i> อัตราส่วน
                    </a>
                    <a href="{{ url('hosfin/trial_balance') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #10b981; color: #059669;">
                        <i class="bi bi-file-earmark-spreadsheet"></i> งบทดลอง
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. 4 KPI Summary Cards (Cash Position) -->
    <div class="row px-3 mb-3">
        <!-- 1. Opening Balance -->
        <div class="col-xl-3 col-md-6 col-12 mb-3">
            <div class="kpi-card" style="border-left: 4px solid #64748b; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase">💼 ยอดยกมาต้นงวด (Opening)</span>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">ณ เริ่มต้นงวด</span>
                </div>
                <h4 class="fw-bold mb-1 text-dark" style="font-family: 'Outfit', sans-serif;">
                    {{ number_format($openingBalance, 2) }}
                    <small class="fs-6 fw-normal text-muted">บาท</small>
                </h4>
                <div class="text-muted small" style="font-size: 0.8rem;">
                    ก่อนวันที่ {{ date('d/m/Y', strtotime($periods[$selectedPeriod === 'all' ? array_key_first($periods) : $selectedPeriod]['start_date'])) }}
                </div>
            </div>
        </div>

        <!-- 2. Total Receipts (Inflow - Dr) -->
        <div class="col-xl-3 col-md-6 col-12 mb-3">
            <div class="kpi-card" style="border-left: 4px solid #10b981; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-success small fw-bold text-uppercase">🟢 ยอดรับรวม (Total Receipts - Dr)</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">เงินสดไหลเข้า</span>
                </div>
                <h4 class="fw-bold mb-1 text-success" style="font-family: 'Outfit', sans-serif;">
                    +{{ number_format($totalDr, 2) }}
                    <small class="fs-6 fw-normal text-muted">บาท</small>
                </h4>
                <div class="text-muted small" style="font-size: 0.8rem;">
                    รับชดเชย, เงินสดรับ, ดอกเบี้ย, เงินบริจาค
                </div>
            </div>
        </div>

        <!-- 3. Total Disbursements (Outflow - Cr) -->
        <div class="col-xl-3 col-md-6 col-12 mb-3">
            <div class="kpi-card" style="border-left: 4px solid #ef4444; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-danger small fw-bold text-uppercase">🔴 ยอดจ่ายรวม (Disbursements - Cr)</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1">เงินสดไหลออก</span>
                </div>
                <h4 class="fw-bold mb-1 text-danger" style="font-family: 'Outfit', sans-serif;">
                    -{{ number_format($totalCr, 2) }}
                    <small class="fs-6 fw-normal text-muted">บาท</small>
                </h4>
                <div class="text-muted small" style="font-size: 0.8rem;">
                    จ่ายค่ายา, ค่าจ้าง, สาธารณูปโภค, ซื้ออุปกรณ์
                </div>
            </div>
        </div>

        <!-- 4. Ending Balance (Net Cash) -->
        <div class="col-xl-3 col-md-6 col-12 mb-3">
            <div class="kpi-card" style="border-left: 4px solid #3b82f6; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-primary small fw-bold text-uppercase">🏦 ยอดคงเหลือสิ้นงวด (Ending)</span>
                    <span class="badge {{ $netCashFlow >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }} rounded-pill px-2 py-1">
                        สุทธิ {{ $netCashFlow >= 0 ? '+' : '' }}{{ number_format($netCashFlow, 2) }}
                    </span>
                </div>
                <h4 class="fw-bold mb-1 {{ $endingBalance >= 0 ? 'text-primary' : 'text-danger' }}" style="font-family: 'Outfit', sans-serif;">
                    {{ number_format($endingBalance, 2) }}
                    <small class="fs-6 fw-normal text-muted">บาท</small>
                </h4>
                <div class="text-muted small" style="font-size: 0.8rem;">
                    ยอดเงินสดและเงินในธนาคารคงเหลือจริง
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Visual Chart Section -->
    <div class="row px-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 bg-white p-3" style="border: 1px solid #e2e8f0 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-bar-chart-line text-success me-1"></i> 
                            {{ $selectedPeriod === 'all' ? 'กราฟเปรียบเทียบกระแสเงินสดรับ vs จ่าย รายเดือน (ปีงบ ' . $budgetYear . ')' : 'กราฟเปรียบเทียบกระแสเงินสดรับ vs จ่าย รายวัน (' . $selectedPeriodLabel . ')' }}
                        </h6>
                        <small class="text-muted">
                            <span class="text-success fw-semibold"><i class="bi bi-bar-chart-fill me-1"></i>แท่งเขียว = รายรับ (Dr)</span> &bull; 
                            <span class="text-danger fw-semibold"><i class="bi bi-bar-chart-fill me-1"></i>แท่งแดง = รายจ่าย (Cr)</span> 
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">[แกนซ้าย]</span> &nbsp;|&nbsp; 
                            <span class="text-primary fw-semibold"><i class="bi bi-graph-up me-1"></i>เส้นน้ำเงิน = ยอดคงเหลือสะสม</span> 
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">[แกนขวา]</span>
                        </small>
                    </div>
                </div>
                <div id="cashFlowChart" style="min-height: 330px;"></div>
            </div>
        </div>
    </div>

    <!-- 3. Month Navigation & Filters Card -->
    <div class="row px-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 bg-white" style="border: 1px solid #e2e8f0 !important;">
                
                <!-- 1. 12-Month Period Navigation Tabs (Top Header of Card) -->
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                    <ul class="nav nav-tabs nav-tabs-custom flex-nowrap overflow-auto" role="tablist" style="white-space: nowrap;">
                        <!-- Tab All -->
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link {{ $selectedPeriod === 'all' ? 'active' : '' }}" onclick="switchPeriod('all')">
                                <i class="bi bi-calendar-range me-1"></i> ภาพรวมทั้งปี {{ $budgetYear }}
                            </button>
                        </li>

                        <!-- 12 Month Tabs -->
                        @foreach($periods as $pKey => $pInfo)
                            @php
                                $hasTx = isset($monthlyRows[$pKey]) && $monthlyRows[$pKey]['voucher_count'] > 0;
                            @endphp
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link {{ $selectedPeriod === $pKey ? 'active' : '' }}" onclick="switchPeriod('{{ $pKey }}')">
                                    {{ $pInfo['label'] }}
                                    @if($hasTx)
                                        <span class="badge bg-success-subtle text-success rounded-pill ms-1" style="font-size: 0.7rem;">
                                            {{ $monthlyRows[$pKey]['voucher_count'] }}
                                        </span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- 2. Unified Filter Toolbar: บัญชี, ประเภท, ค้นหา, Excel -->
                <div class="card-body p-3 bg-light-subtle border-top rounded-bottom-4">
                    <form id="filterForm" method="GET" action="{{ url('hosfin/cash_register') }}" class="m-0">
                        <input type="hidden" name="budget_year" value="{{ $budgetYear }}">
                        <input type="hidden" name="period" id="input_period" value="{{ $selectedPeriod }}">

                        <div class="row g-2 align-items-center">
                            <!-- 1. เลือกบัญชีเงินสด / เงินฝากธนาคาร -->
                            <div class="col-xl-6 col-lg-6 col-md-12">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label text-muted mb-0 fw-bold small text-nowrap" for="select_account_code">
                                        <i class="bi bi-bank text-success me-1"></i> บัญชี:
                                    </label>
                                    <select name="account_code" id="select_account_code" class="form-select fw-bold border-1 bg-white shadow-xs" style="height: 38px; font-size: 0.88rem; border-color: #cbd5e1;" onchange="document.getElementById('filterForm').submit()">
                                        <option value="all" {{ $selectedAccount === 'all' ? 'selected' : '' }}>
                                            ✨ รวมทุกบัญชีเงินสดและธนาคาร (13 บัญชี)
                                        </option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->account_code }}" {{ $selectedAccount === $acc->account_code ? 'selected' : '' }}>
                                                {{ $acc->account_code }} : {{ $acc->account_name }} ({{ $acc->tx_count }} รายการ)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- 2. ประเภท: (All, Dr, Cr) -->
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6">
                                <div class="d-flex align-items-center gap-1.5">
                                    <label class="form-label text-muted mb-0 fw-bold small text-nowrap" for="select_table_tx_type">
                                        <i class="bi bi-funnel text-primary me-1"></i> ประเภท:
                                    </label>
                                    <select id="select_table_tx_type" class="form-select fw-bold border-1 bg-white shadow-xs" style="height: 38px; font-size: 0.85rem; border-color: #cbd5e1; cursor: pointer;">
                                        <option value="all" selected>✨ ทั้งหมด (รับ + จ่าย)</option>
                                        <option value="dr">🟢 เฉพาะรายรับ (Dr)</option>
                                        <option value="cr">🔴 เฉพาะรายจ่าย (Cr)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- 3. ปุ่ม Excel -->
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 d-flex justify-content-xl-end">
                                @php
                                    $exportParams = http_build_query([
                                        'budget_year' => $budgetYear,
                                        'period' => $selectedPeriod,
                                        'account_code' => $selectedAccount,
                                    ]);
                                @endphp
                                <a href="{{ url('hosfin/cash_register/export') }}?{{ $exportParams }}" 
                                   id="btn_table_export" class="btn btn-success rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-1.5 w-100" style="height: 38px; font-size: 0.85rem; max-width: 140px;" title="ส่งออกข้อมูลเป็น Excel (.xlsx)">
                                    <i class="bi bi-file-earmark-excel"></i> Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Main Data Table Card -->
    <div class="row px-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 bg-white" style="border: 1px solid #e2e8f0 !important;">
                
                <!-- Table Header: Title, Period & Ending Balance -->
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 rounded-top-4">
                    <!-- Left: Title & Subtitle -->
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            @if($viewMode === 'ledger')
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-list-check text-primary me-1"></i> ทะเบียนรายการละเอียด (Transaction Ledger)
                                </h6>
                                <span id="badgeTableCount" class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 font-monospace">
                                    {{ number_format(count($ledgerItems)) }} รายการ
                                </span>
                            @elseif($selectedPeriod === 'all')
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-calendar3-range text-success me-1"></i> สรุปกระแสเงินสดรับ-จ่าย รายเดือน (12 เดือน ปีงบประมาณ {{ $budgetYear }})
                                </h6>
                                <span id="badgeTableCount" class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 font-monospace">
                                    12 งวด
                                </span>
                            @else
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-calendar-check text-success me-1"></i> สรุปกระแสเงินสดรับ-จ่าย ประจำแต่ละวัน
                                </h6>
                                <span id="badgeTableCount" class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 font-monospace">
                                    {{ count($dailyRows) }} วัน
                                </span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $selectedPeriodLabel }} | {{ $selectedAccountName }}</small>
                    </div>

                    <!-- Right: Quick Stat Badge & Expand All Button -->
                    <div class="d-flex align-items-center gap-2">
                        @if($selectedPeriod !== 'all' && $viewMode !== 'ledger')
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1 fw-semibold shadow-xs d-flex align-items-center gap-1" id="btn_toggle_all_accordions" style="font-size: 0.78rem;">
                                <i class="bi bi-arrows-expand"></i> <span>คลี่ดูทุกวัน</span>
                            </button>
                        @endif
                        <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill font-monospace shadow-xs">
                            ยอดคงเหลือสิ้นงวด: <strong class="{{ $endingBalance >= 0 ? 'text-primary' : 'text-danger' }}">{{ number_format($endingBalance, 2) }} บ.</strong>
                        </span>
                    </div>
                </div>

                @if($viewMode === 'ledger')
                    <!-- ========================================================================= -->
                    <!-- MODE 1: DETAILED TRANSACTION LEDGER TABLE                                 -->
                    <!-- ========================================================================= -->
                    <!-- Opening Balance Banner Strip -->
                    <div class="px-4 py-2 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap" style="font-size: 0.86rem;">
                        <span class="text-secondary fw-semibold">
                            <i class="bi bi-arrow-right-circle me-1 text-primary"></i> ยอดยกมาต้นงวด (Opening Balance):
                        </span>
                        <span class="fw-bold text-dark font-monospace fs-6">
                            {{ number_format($openingBalance, 2) }} <small class="fw-normal text-muted">บาท</small>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table id="ledgerTable" class="table table-custom table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">วันที่</th>
                                    <th class="text-center" style="width: 120px;">เลขที่เอกสาร</th>
                                    <th style="width: 140px;">รหัสบัญชี</th>
                                    <th style="width: 180px;">ชื่อบัญชี</th>
                                    <th>รายการ / คำอธิบาย</th>
                                    <th class="text-end" style="width: 130px;">รายรับ (Dr)</th>
                                    <th class="text-end" style="width: 130px;">รายจ่าย (Cr)</th>
                                    <th class="text-end" style="width: 140px;">คงเหลือสะสม</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ledgerItems as $item)
                                    <tr data-type="{{ $item->tx_type }}">
                                        <td class="text-center fw-semibold text-secondary" style="font-size: 0.85rem;" data-order="{{ $item->voucher_date }}">
                                            {{ date('d/m/Y', strtotime($item->voucher_date)) }}
                                        </td>
                                        <td class="text-center fw-bold text-dark" style="font-size: 0.85rem;">
                                            <span class="badge bg-light text-dark border px-2 py-1 font-monospace">{{ $item->voucher_no }}</span>
                                        </td>
                                        <td class="text-secondary small font-monospace">{{ $item->account_code }}</td>
                                        <td class="small fw-semibold text-dark">{{ $item->account_name }}</td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $item->display_desc }}</span>
                                        </td>
                                        <td class="text-end font-monospace {{ $item->debit > 0 ? 'fw-bold text-success' : 'text-muted' }}" data-order="{{ $item->debit }}">
                                            {{ $item->debit > 0 ? number_format($item->debit, 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace {{ $item->credit > 0 ? 'fw-bold text-danger' : 'text-muted' }}" data-order="{{ $item->credit }}">
                                            {{ $item->credit > 0 ? number_format($item->credit, 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $item->running_balance >= 0 ? 'text-primary' : 'text-danger' }}" data-order="{{ $item->running_balance }}">
                                            {{ number_format($item->running_balance, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">รวมรายการที่แสดง (Filtered Total):</td>
                                    <td class="text-end text-success fs-6" id="footFilteredDr">+{{ number_format($totalDr, 2) }}</td>
                                    <td class="text-end text-danger fs-6" id="footFilteredCr">-{{ number_format($totalCr, 2) }}</td>
                                    <td class="text-end text-primary fs-6">{{ number_format($endingBalance, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                @elseif($selectedPeriod === 'all')
                    <!-- ========================================================================= -->
                    <!-- MODE 2: 12-MONTH SUMMARY TABLE (When period == 'all')                    -->
                    <!-- ========================================================================= -->
                    <div class="table-responsive">
                        <table id="monthlyTable" class="table table-custom table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 70px;">งวดที่</th>
                                    <th>เดือน / รอบบัญชี</th>
                                    <th class="text-end" style="width: 150px;">ยอดยกมาต้นเดือน</th>
                                    <th class="text-end" style="width: 150px;">รวมรายรับ (Dr)</th>
                                    <th class="text-end" style="width: 150px;">รวมรายจ่าย (Cr)</th>
                                    <th class="text-end" style="width: 150px;">รับ-จ่ายสุทธิ (+/-)</th>
                                    <th class="text-end" style="width: 160px;">ยอดคงเหลือสิ้นเดือน</th>
                                    <th class="text-center" style="width: 110px;">จำนวนใบสำคัญ</th>
                                    <th class="text-center" style="width: 130px;">การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyRows as $pKey => $mRow)
                                    <tr data-dr="{{ $mRow['dr'] }}" data-cr="{{ $mRow['cr'] }}">
                                        <td class="text-center fw-bold text-secondary">{{ $mRow['fiscal_month'] }}</td>
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-calendar-event me-1 text-muted"></i> {{ $mRow['label'] }}
                                        </td>
                                        <td class="text-end font-monospace text-secondary" data-order="{{ $mRow['opening_bal'] }}">{{ number_format($mRow['opening_bal'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-success" data-order="{{ $mRow['dr'] }}">
                                            {{ $mRow['dr'] > 0 ? '+' . number_format($mRow['dr'], 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-danger" data-order="{{ $mRow['cr'] }}">
                                            {{ $mRow['cr'] > 0 ? '-' . number_format($mRow['cr'], 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $mRow['net'] >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $mRow['net'] }}">
                                            {{ $mRow['net'] >= 0 ? '+' : '' }}{{ number_format($mRow['net'], 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary fs-6" data-order="{{ $mRow['ending_bal'] }}">{{ number_format($mRow['ending_bal'], 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $mRow['voucher_count'] > 0 ? 'bg-secondary' : 'bg-light text-muted border' }} rounded-pill px-2.5 py-1">
                                                {{ $mRow['voucher_count'] }} ใบ
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1 fw-semibold" onclick="switchPeriod('{{ $pKey }}')">
                                                <i class="bi bi-arrow-right-circle me-1"></i> ดูรายวัน
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">รวมทั้งปีงบประมาณ {{ $budgetYear }}:</td>
                                    <td class="text-end text-success fs-6">+{{ number_format($totalDr, 2) }}</td>
                                    <td class="text-end text-danger fs-6">-{{ number_format($totalCr, 2) }}</td>
                                    <td class="text-end {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                        {{ $netCashFlow >= 0 ? '+' : '' }}{{ number_format($netCashFlow, 2) }}
                                    </td>
                                    <td class="text-end text-primary fs-6">{{ number_format($endingBalance, 2) }}</td>
                                    <td class="text-center">{{ $totalVouchers }} ใบ</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                @else
                    <!-- ========================================================================= -->
                    <!-- MODE 3: DAILY SUMMARY TABLE (When specific month is chosen)              -->
                    <!-- ========================================================================= -->
                    <div class="table-responsive">
                        <table id="dailyTable" class="table table-custom align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 120px;">วันที่</th>
                                    <th class="text-end" style="width: 160px;">ยอดยกมาต้นวัน</th>
                                    <th class="text-end" style="width: 160px;">รายรับประจำวัน (Dr)</th>
                                    <th class="text-end" style="width: 160px;">รายจ่ายประจำวัน (Cr)</th>
                                    <th class="text-end" style="width: 160px;">รับ-จ่ายสุทธิ (+/-)</th>
                                    <th class="text-end" style="width: 170px;">ยอดคงเหลือสิ้นวัน</th>
                                    <th class="text-center" style="width: 130px;">จำนวนใบสำคัญ</th>
                                    <th class="text-center" style="width: 100px;">รายการย่อย</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dailyRows as $dDate => $dRow)
                                    @php
                                        $rowId = 'collapse_day_' . str_replace('-', '_', $dDate);
                                        $dayTxList = $dailyTransactionsByDate[$dDate] ?? [];
                                    @endphp
                                    <tr class="accordion-day-toggle" data-dr="{{ $dRow['dr'] }}" data-cr="{{ $dRow['cr'] }}" data-bs-toggle="collapse" data-bs-target="#{{ $rowId }}" aria-expanded="false">
                                        <td class="text-center fw-bold text-dark" data-order="{{ $dDate }}">
                                            <i class="bi bi-calendar-date me-1 text-success"></i> {{ date('d/m/Y', strtotime($dDate)) }}
                                        </td>
                                        <td class="text-end font-monospace text-secondary" data-order="{{ $dRow['opening_bal'] }}">{{ number_format($dRow['opening_bal'], 2) }}</td>
                                        <td class="text-end font-monospace fw-bold text-success" data-order="{{ $dRow['dr'] }}">
                                            {{ $dRow['dr'] > 0 ? '+' . number_format($dRow['dr'], 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-danger" data-order="{{ $dRow['cr'] }}">
                                            {{ $dRow['cr'] > 0 ? '-' . number_format($dRow['cr'], 2) : '-' }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold {{ $dRow['net'] >= 0 ? 'text-success' : 'text-danger' }}" data-order="{{ $dRow['net'] }}">
                                            {{ $dRow['net'] >= 0 ? '+' : '' }}{{ number_format($dRow['net'], 2) }}
                                        </td>
                                        <td class="text-end font-monospace fw-bold text-primary fs-6" data-order="{{ $dRow['ending_bal'] }}">{{ number_format($dRow['ending_bal'], 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-dark border rounded-pill px-2.5 py-1 font-monospace">
                                                {{ $dRow['voucher_count'] }} ใบ
                                            </span>
                                        </td>
                                        <td class="text-center text-muted">
                                            <i class="bi bi-chevron-down toggle-icon"></i>
                                        </td>
                                    </tr>

                                    <!-- Collapsible Sub-Table for Day's Transactions -->
                                    <tr class="collapse-row p-0">
                                        <td colspan="8" class="p-0 border-0">
                                            <div class="collapse bg-light p-3" id="{{ $rowId }}">
                                                <div class="card border-0 shadow-xs rounded-3">
                                                    <div class="card-header bg-white py-2 px-3 fw-bold small text-secondary d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-journal-text text-primary"></i>
                                                            <span>รายการใบสำคัญประจำวันที่ {{ date('d/m/Y', strtotime($dDate)) }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2 ms-auto">
                                                            <div class="input-group input-group-sm shadow-xs" style="width: 250px;">
                                                                <span class="input-group-text bg-white text-muted border-end-0 py-0" style="height: 30px;"><i class="bi bi-search"></i></span>
                                                                <input type="text" class="form-control form-control-sm border-start-0 border-end-0 py-0 sub-search-box" placeholder="ค้นหาเลขที่ / บัญชี / คำอธิบาย..." style="height: 30px; font-size: 0.8rem;">
                                                                <button class="btn btn-outline-secondary btn-sm btn-sub-clear d-none py-0" type="button" style="height: 30px;" title="ล้างการค้นหา"><i class="bi bi-x"></i></button>
                                                            </div>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill font-monospace sub-count-badge" style="font-size: 0.78rem;">
                                                                {{ count($dayTxList) }} รายการ
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0 small align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th class="text-center" style="width: 110px;">เลขที่เอกสาร</th>
                                                                    <th style="width: 140px;">รหัสบัญชี</th>
                                                                    <th style="width: 200px;">ชื่อบัญชี</th>
                                                                    <th>รายการ / คำอธิบาย</th>
                                                                    <th class="text-end" style="width: 120px;">รายรับ (Dr)</th>
                                                                    <th class="text-end" style="width: 120px;">รายจ่าย (Cr)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($dayTxList as $tx)
                                                                    <tr class="sub-tx-row" data-sub-type="{{ $tx->debit > 0 ? 'dr' : 'cr' }}">
                                                                        <td class="text-center fw-bold font-monospace">{{ $tx->voucher_no }}</td>
                                                                        <td class="text-secondary font-monospace">{{ $tx->account_code }}</td>
                                                                        <td class="fw-semibold text-dark">{{ $tx->account_name }}</td>
                                                                        <td>{{ $tx->display_desc }}</td>
                                                                        <td class="text-end font-monospace {{ $tx->debit > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                                                                            {{ $tx->debit > 0 ? number_format($tx->debit, 2) : '-' }}
                                                                        </td>
                                                                        <td class="text-end font-monospace {{ $tx->credit > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                                            {{ $tx->credit > 0 ? number_format($tx->credit, 2) : '-' }}
                                                                        </td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="6" class="text-center py-2 text-muted">ไม่มีรายการย่อย</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                                            ไม่พบรายการเคลื่อนไหวรับ-จ่ายในเดือนนี้
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td class="text-center">รวมทั้งสิ้น {{ $selectedPeriodLabel }}:</td>
                                    <td class="text-end text-muted">{{ number_format($openingBalance, 2) }}</td>
                                    <td class="text-end text-success fs-6">+{{ number_format($totalDr, 2) }}</td>
                                    <td class="text-end text-danger fs-6">-{{ number_format($totalCr, 2) }}</td>
                                    <td class="text-end {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                        {{ $netCashFlow >= 0 ? '+' : '' }}{{ number_format($netCashFlow, 2) }}
                                    </td>
                                    <td class="text-end text-primary fs-6">{{ number_format($endingBalance, 2) }}</td>
                                    <td class="text-center">{{ $totalVouchers }} ใบ</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

<!-- ApexCharts CDN/Local -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>

<script>
    function switchPeriod(period) {
        document.getElementById('input_period').value = period;
        document.getElementById('filterForm').submit();
    }

    document.getElementById('select_budget_year').addEventListener('change', function () {
        const year = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('budget_year', year);
        currentUrl.searchParams.set('period', 'all');
        window.location.href = currentUrl.toString();
    });

    // -------------------------------------------------------------
    // Unified DataTables & Table Toolbar Filter Logic
    // -------------------------------------------------------------
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
            // Silence DataTables alert popups and log to console instead
            $.fn.dataTable.ext.errMode = 'none';

            var currentFilterType = 'all';

            // Global custom search filter for Type (all, dr, cr)
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (currentFilterType === 'all') return true;

                // Case 1: Ledger Table
                if (settings.nTable.id === 'ledgerTable') {
                    var rowNode = settings.aoData[dataIndex].nTr;
                    var rowType = $(rowNode).attr('data-type') || '';
                    return rowType === currentFilterType;
                }

                // Case 2: Monthly Table
                if (settings.nTable.id === 'monthlyTable') {
                    var rowNode = settings.aoData[dataIndex].nTr;
                    var drVal = parseFloat($(rowNode).attr('data-dr') || 0);
                    var crVal = parseFloat($(rowNode).attr('data-cr') || 0);
                    if (currentFilterType === 'dr') return drVal > 0;
                    if (currentFilterType === 'cr') return crVal > 0;
                }

                return true;
            });

            // 1. Initialize Transaction Ledger DataTable
            var activeDt = null;
            if ($('#ledgerTable').length) {
                activeDt = $('#ledgerTable').DataTable({
                    dom: '<"d-none"l>rt<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top"<"text-muted small"i><"pagination-sm"p>>',
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                    ordering: true,
                    order: [[0, 'asc'], [1, 'asc']],
                    columnDefs: [
                        { targets: [0, 1], className: 'text-center' },
                        { targets: [5, 6, 7], className: 'text-end font-monospace' }
                    ],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                        infoEmpty: "ไม่พบรายการ",
                        infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                        zeroRecords: "ไม่พบรายการที่ตรงกับคำค้นหา",
                        paginate: {
                            previous: '<i class="bi bi-chevron-left"></i>',
                            next: '<i class="bi bi-chevron-right"></i>'
                        }
                    },
                    footerCallback: function(row, data, start, end, display) {
                        var api = this.api();
                        var intVal = function(i) {
                            if (typeof i === 'number') return i;
                            if (typeof i === 'string') {
                                var stripped = i.replace(/<[^>]+>/g, '').replace(/,/g, '').trim();
                                return parseFloat(stripped) || 0;
                            }
                            return 0;
                        };

                        var totalDr = api.column(5, { search: 'applied' }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);
                        var totalCr = api.column(6, { search: 'applied' }).data().reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        $('#footFilteredDr').html(totalDr > 0 ? '+' + totalDr.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-');
                        $('#footFilteredCr').html(totalCr > 0 ? '-' + totalCr.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-');
                        $('#badgeTableCount').text(api.rows({ search: 'applied' }).count().toLocaleString() + ' รายการ');
                    }
                });

                // Attach length selector to Top-Left slot
                $('#ledgerTable_length').removeClass('d-none').appendTo('#table_length_wrapper');
            }

            // 2. Daily Summary Table Filtering (Native Accordion Support without DataTables tn/18 column conflict)
            function filterDailyTable() {
                var type = currentFilterType;
                var visibleDays = 0;

                $('#dailyTable tbody tr.accordion-day-toggle').each(function() {
                    var dayRow = $(this);
                    var collapseRow = dayRow.next('tr.collapse-row');
                    var drVal = parseFloat(dayRow.attr('data-dr') || 0);
                    var crVal = parseFloat(dayRow.attr('data-cr') || 0);

                    // Type match
                    var matchType = true;
                    if (type === 'dr' && drVal <= 0) matchType = false;
                    if (type === 'cr' && crVal <= 0) matchType = false;

                    if (matchType) {
                        dayRow.show();
                        collapseRow.show();
                        visibleDays++;

                        if (type === 'dr') {
                            collapseRow.find('.sub-tx-row[data-sub-type="dr"]').show();
                            collapseRow.find('.sub-tx-row[data-sub-type="cr"]').hide();
                        } else if (type === 'cr') {
                            collapseRow.find('.sub-tx-row[data-sub-type="dr"]').hide();
                            collapseRow.find('.sub-tx-row[data-sub-type="cr"]').show();
                        } else {
                            collapseRow.find('.sub-tx-row').show();
                        }
                    } else {
                        dayRow.hide();
                        collapseRow.hide();
                    }
                });

                $('#badgeTableCount').text(visibleDays + ' วัน');
            }

            // 3. Initialize Monthly Table DataTable
            if ($('#monthlyTable').length) {
                activeDt = $('#monthlyTable').DataTable({
                    dom: 'rt',
                    paging: false,
                    ordering: true,
                    order: [[0, 'asc']],
                    columnDefs: [
                        { targets: [8], orderable: false }
                    ]
                });
            }

            // -------------------------------------------------------------
            // Connect Controls (ประเภทรายการ, คลี่ดูทุกวัน, ค้นหาในตารางย่อย, Excel)
            // -------------------------------------------------------------
            // Type Filter Dropdown Event
            $('#select_table_tx_type').on('change', function() {
                currentFilterType = $(this).val();
                if (activeDt) {
                    activeDt.draw();
                }
                if ($('#dailyTable').length) {
                    filterDailyTable();
                    $('.sub-search-box').trigger('keyup');
                }
                updateExportLink();
            });

            // Expand / Collapse all days toggle
            var allExpanded = false;
            $('#btn_toggle_all_accordions').on('click', function() {
                allExpanded = !allExpanded;
                if (allExpanded) {
                    $('#dailyTable .collapse').collapse('show');
                    $(this).find('span').text('ยุบทุกวัน');
                    $(this).find('i').attr('class', 'bi bi-arrows-collapse');
                } else {
                    $('#dailyTable .collapse').collapse('hide');
                    $(this).find('span').text('คลี่ดูทุกวัน');
                    $(this).find('i').attr('class', 'bi bi-arrows-expand');
                }
            });

            // Instant Search inside Sub-Table (ค้นหาในตารางรายย่อย)
            $(document).on('keyup', '.sub-search-box', function() {
                var input = $(this);
                var query = input.val().toLowerCase().trim();
                var card = input.closest('.card');
                var clearBtn = input.siblings('.btn-sub-clear');
                var badge = card.find('.sub-count-badge');
                var totalRows = card.find('tbody tr.sub-tx-row').length;
                var visibleRows = 0;

                if (query) {
                    clearBtn.removeClass('d-none');
                } else {
                    clearBtn.addClass('d-none');
                }

                card.find('tbody tr.sub-no-match').remove();

                card.find('tbody tr.sub-tx-row').each(function() {
                    var row = $(this);
                    var text = row.text().toLowerCase();
                    var rowType = row.attr('data-sub-type');

                    var matchType = true;
                    if (currentFilterType === 'dr' && rowType !== 'dr') matchType = false;
                    if (currentFilterType === 'cr' && rowType !== 'cr') matchType = false;

                    var matchQuery = (!query || text.indexOf(query) !== -1);

                    if (matchType && matchQuery) {
                        row.show();
                        visibleRows++;
                    } else {
                        row.hide();
                    }
                });

                if (query) {
                    badge.text('พบ ' + visibleRows + ' จาก ' + totalRows + ' รายการ');
                    if (visibleRows === 0) {
                        card.find('tbody').append('<tr class="sub-no-match"><td colspan="6" class="text-center py-3 text-muted small"><i class="bi bi-search me-1"></i> ไม่พบรายการที่ตรงกับคำค้นหา</td></tr>');
                    }
                } else {
                    badge.text(totalRows + ' รายการ');
                }
            });

            // Clear sub-table search
            $(document).on('click', '.btn-sub-clear', function() {
                var btn = $(this);
                btn.siblings('.sub-search-box').val('').trigger('keyup');
            });

            function updateExportLink() {
                var exportBtn = $('#btn_table_export');
                if (exportBtn.length) {
                    var exportUrl = new URL(exportBtn.attr('href'), window.location.origin);
                    exportUrl.searchParams.set('type', currentFilterType);
                    exportBtn.attr('href', exportUrl.toString());
                }
            }
        }

        // -------------------------------------------------------------
        // Render Cash Flow ApexChart (Dual Y-Axis for Prominent Bars)
        // -------------------------------------------------------------
        const labels = @json($chartLabels);
        const seriesDr = @json($chartDr);
        const seriesCr = @json($chartCr);
        const seriesBal = @json($chartBal);

        // คำนวณขอบเขตแกน Y แยกอิสระ (Dual Y-Axis):
        // แกนซ้าย: รายรับ vs รายจ่าย (Dr & Cr) -> แท่งสูงเด่นชัดเจนเหมือนรายเดือน ไม่โดนยอดสะสมกดให้เตี้ย
        // แกนขวา: ยอดคงเหลือสะสม (Ending Balance) -> เส้นกราฟแสดงทิศทางกระแสเงินสดรวม
        const validDr = Array.isArray(seriesDr) ? seriesDr.map(v => Number(v) || 0) : [];
        const validCr = Array.isArray(seriesCr) ? seriesCr.map(v => Number(v) || 0) : [];
        const validBal = Array.isArray(seriesBal) ? seriesBal.map(v => Number(v) || 0) : [];

        const maxFlowVal = Math.max(
            validDr.reduce((max, v) => Math.max(max, v), 0),
            validCr.reduce((max, v) => Math.max(max, v), 0),
            1000
        );
        const flowAxisMax = Math.ceil(maxFlowVal * 1.18);

        const maxBalVal = Math.max(
            validBal.reduce((max, v) => Math.max(max, v), 0),
            1000
        );
        const minBalVal = Math.min(
            validBal.reduce((min, v) => Math.min(min, v), 0),
            0
        );
        const balAxisMax = Math.ceil(maxBalVal * 1.15);
        const balAxisMin = minBalVal < 0 ? Math.floor(minBalVal * 1.15) : 0;

        const chartOptions = {
            series: [
                {
                    name: 'รายรับ (Inflow - Dr)',
                    type: 'column',
                    data: seriesDr
                },
                {
                    name: 'รายจ่าย (Outflow - Cr)',
                    type: 'column',
                    data: seriesCr
                },
                {
                    name: 'ยอดคงเหลือสะสม (Ending)',
                    type: 'line',
                    data: seriesBal
                }
            ],
            chart: {
                height: 330,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            plotOptions: {
                bar: {
                    columnWidth: '55%',
                    borderRadius: 3,
                    dataLabels: {
                        position: 'top',
                    }
                }
            },
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            markers: {
                size: 3,
                hover: {
                    size: 6
                }
            },
            colors: ['#10b981', '#ef4444', '#3b82f6'],
            fill: {
                opacity: [0.85, 0.85, 1]
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [2],
                formatter: function (val, opt) {
                    if (!val || Math.abs(val) < 1) return '';
                    if (Math.abs(val) >= 1000000) {
                        let m = val / 1000000;
                        return (m >= 10 ? m.toFixed(1) : m.toFixed(2)) + 'M';
                    }
                    if (Math.abs(val) >= 1000) {
                        let k = val / 1000;
                        return (k >= 100 ? k.toFixed(0) : k.toFixed(1)) + 'K';
                    }
                    return val.toLocaleString();
                },
                offsetY: -6,
                style: {
                    fontSize: '9px',
                    fontFamily: 'inherit',
                    fontWeight: 700
                },
                background: {
                    enabled: true,
                    foreColor: '#ffffff',
                    padding: 3,
                    borderRadius: 4,
                    borderWidth: 0,
                    opacity: 0.92
                }
            },
            grid: {
                padding: {
                    top: 25,
                    right: 25,
                    left: 15
                }
            },
            labels: labels,
            xaxis: {
                categories: labels,
                labels: {
                    rotate: -45,
                    style: { fontSize: '11px' }
                }
            },
            yaxis: [
                {
                    seriesName: 'รายรับ (Inflow - Dr)',
                    min: 0,
                    max: flowAxisMax,
                    title: { 
                        text: 'รายรับ - รายจ่าย (บาท)', 
                        style: { fontSize: '11px', color: '#059669', fontWeight: 600 } 
                    },
                    labels: {
                        style: { colors: '#059669', fontWeight: 500 },
                        formatter: function (val) {
                            if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val ? val.toLocaleString() : '0';
                        }
                    }
                },
                {
                    seriesName: 'รายรับ (Inflow - Dr)',
                    min: 0,
                    max: flowAxisMax,
                    show: false
                },
                {
                    opposite: true,
                    seriesName: 'ยอดคงเหลือสะสม (Ending)',
                    min: balAxisMin,
                    max: balAxisMax,
                    title: { 
                        text: 'ยอดคงเหลือสะสม (บาท)', 
                        style: { fontSize: '11px', color: '#2563eb', fontWeight: 600 } 
                    },
                    labels: {
                        style: { colors: '#2563eb', fontWeight: 500 },
                        formatter: function (val) {
                            if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val ? val.toLocaleString() : '0';
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val) {
                        return val !== undefined ? Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท' : '';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontWeight: 600
            }
        };

        const chart = new ApexCharts(document.querySelector("#cashFlowChart"), chartOptions);
        chart.render();
    });
</script>
@endsection
