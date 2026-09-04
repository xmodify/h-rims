@extends('layouts.app')

@section('content')
<style>
    .ar-card {
        border-radius: 14px;
        transition: all 0.25s ease-in-out;
        border: 1px solid #e2e8f0;
    }
    .ar-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -6px rgba(0,0,0,0.08) !important;
    }
    .btn-action-custom {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.85rem;
        height: 42px;
        font-weight: 700;
    }
    .btn-action-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    /* DataTables Modern Styling */
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        padding: 5px 14px;
        border: 1px solid #cbd5e1;
        font-size: 0.88rem;
        outline: none;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 10px;
        padding: 4px 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.88rem;
    }
    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: #0284c7;
        border-color: #0284c7;
    }
    .dataTables_wrapper .pagination .page-link {
        border-radius: 8px;
        margin: 0 2px;
        font-size: 0.85rem;
    }
    /* Period Navigation Tabs */
    .nav-tabs-custom {
        border-bottom: 2px solid #e2e8f0;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 600;
        font-size: 0.86rem;
        padding: 10px 15px;
        transition: all 0.2s ease-in-out;
        background: transparent;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #0284c7;
        border-bottom-color: #bae6fd;
    }
    .nav-tabs-custom .nav-link.active {
        color: #0284c7;
        border-bottom-color: #0284c7;
        font-weight: 700;
        background: transparent;
    }
    .status-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-left: 4px;
        vertical-align: middle;
    }
    table.dataTable thead th {
        border-bottom: 2px solid #e2e8f0 !important;
        font-size: 0.84rem;
        white-space: nowrap;
    }
    .dt-buttons {
        margin-left: 6px;
    }
    .dt-buttons .btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        background-color: #16a34a !important;
        border-color: #16a34a !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-radius: 50rem !important;
        padding: 6px 16px !important;
        font-size: 0.85rem !important;
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2) !important;
        transition: all 0.2s ease-in-out !important;
    }
    .dt-buttons .btn:hover {
        background-color: #15803d !important;
        border-color: #15803d !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(22, 163, 74, 0.35) !important;
    }
    table.dataTable tfoot th {
        background-color: #f8fafc !important;
        font-weight: 800 !important;
        border-top: 2px solid #cbd5e1 !important;
        border-bottom: 2px solid #cbd5e1 !important;
        white-space: nowrap;
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
            <div class="page-header-box mt-2" style="border-left-color: #10b981 !important;">
                <div>
                    <h5 class="text-primary mb-0 fw-bold">
                        <i class="bi bi-wallet2 me-2 text-success"></i> รายงานลูกหนี้ค่ารักษาพยาบาลแยกตามสิทธิ (Accounts Receivable)
                    </h5>
                    <small class="text-muted">ติดตามยอดเรียกเก็บ การรับชดเชย และลูกหนี้ค้างท่อแยกตามสิทธิกองทุนหลักจากระบบบัญชี GL</small>
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

                    <a href="{{ url('hosfin/ap_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #ef4444; color: #dc2626;">
                        <i class="bi bi-receipt-cutoff"></i> เจ้าหนี้ (AP)
                    </a>
                    <a href="{{ url('hosfin/ar_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #0284c7; border: 1.5px solid #0284c7; color: #ffffff;">
                        <i class="bi bi-wallet2"></i> ลูกหนี้ (AR)
                    </a>
                    <a href="{{ url('hosfin/cost_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #d97706; color: #b45309;">
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

    <!-- Period Tabs Navigation -->
    <div class="row px-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4 bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-header bg-white border-0 pt-3 pb-0 px-4">
                    <ul class="nav nav-tabs nav-tabs-custom flex-nowrap overflow-auto" id="arPeriodTabs" role="tablist" style="white-space: nowrap;">
                        <!-- All year tab -->
                        <li class="nav-item">
                            <a class="nav-link {{ $selectedPeriod === 'all' ? 'active' : '' }}" 
                               href="{{ url('hosfin/ar_report') }}?budget_year={{ $budgetYear }}&period=all">
                                <i class="bi bi-calendar-range me-1"></i> ภาพรวมทั้งปี (สะสม)
                            </a>
                        </li>
                        <!-- Monthly tabs -->
                        @foreach($periods as $p)
                            @php
                                $hasData = in_array($p['fiscal_month'], $existingMonths);
                            @endphp
                            <li class="nav-item">
                                <a class="nav-link {{ $selectedPeriod === $p['period'] ? 'active' : '' }}" 
                                   href="{{ url('hosfin/ar_report') }}?budget_year={{ $budgetYear }}&period={{ $p['period'] }}">
                                    {{ $p['label'] }}
                                    @if($hasData)
                                        <span class="status-dot bg-success" title="มีข้อมูลรายการ"></span>
                                    @else
                                        <span class="status-dot bg-secondary bg-opacity-25" title="ยังไม่มีข้อมูล"></span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body py-2 px-4 bg-light bg-opacity-50 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-top: 1px solid #f1f5f9 !important;">
                    <div class="small text-muted d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>กำลังแสดงผล: <strong class="text-dark">{{ $selectedPeriodLabel }}</strong></span>
                        @if($selectedPeriod === 'all')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">รวม 1 ผังบัญชี ต่อ 1 รายการสะสมทั้งปี</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">เฉพาะรายการงวดประจำเดือน</span>
                        @endif
                    </div>
                    <div class="small text-muted">
                        ปีงบประมาณ <strong>{{ $budgetYear }}</strong> (ต.ค. {{ substr((string)($budgetYear - 1), -2) }} - ก.ย. {{ substr((string)$budgetYear, -2) }})
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Highlight Cards -->
    <div class="row g-3 px-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ar-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">
                                {{ $selectedPeriod === 'all' ? 'ยอดตั้งเบิกระหว่างปีนี้ (Billed)' : 'ยอดตั้งเบิกงวดนี้ (Billed)' }}
                            </span>
                            <div class="fw-black mt-1 text-success" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalBilled, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            {{ $selectedPeriod === 'all' ? 'ยอดตั้งหนี้ปีนี้' : 'ยอดตั้งหนี้ประจำงวด' }}
                        </span>
                        @if($selectedPeriod === 'all')
                            <small class="text-muted" style="font-size: 0.73rem;">(รวมยอดยกมา: {{ number_format(($totalBilled + $totalOb) / 1000000, 2) }}M)</small>
                        @else
                            <small class="text-muted" style="font-size: 0.73rem;">รวมทุกสิทธิการรักษา</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ar-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">
                                {{ $selectedPeriod === 'all' ? 'ชดเชยที่รับเงินแล้วปีนี้ (Collected)' : 'ชดเชยที่รับเงินงวดนี้ (Collected)' }}
                            </span>
                            <div class="fw-black mt-1 text-primary" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalCollected, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            {{ $selectedPeriod === 'all' ? 'เงินโอนสะสมปีนี้' : 'เงินโอนประจำงวด' }}
                        </span>
                        <small class="text-muted" style="font-size: 0.73rem;">
                            {{ $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0 }}% ของยอดตั้งเบิกปีนี้
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ar-card" style="background: #f0f9ff; border: 1.5px solid #bae6fd !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">
                                {{ $selectedPeriod === 'all' ? 'ลูกหนี้ค้างรับสุทธิคงเหลือ (Net Outstanding)' : 'ลูกหนี้ค้างรับงวดนี้ (Outstanding)' }}
                            </span>
                            <div class="fw-black mt-1 text-sky" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2; color: #0284c7;">
                                {{ number_format($totalOutstanding, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-white shadow-xs text-info" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-info text-dark rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                            {{ $selectedPeriod === 'all' ? 'รอการชดเชยสุทธิ' : 'รอการชดเชยงวดนี้' }}
                        </span>
                        @if($selectedPeriod === 'all')
                            <small class="text-muted" style="font-size: 0.73rem;">
                                ยกมา {{ number_format($totalOb / 1000000, 2) }}M + ปีนี้ {{ number_format($totalOutstandingYear / 1000000, 2) }}M
                            </small>
                        @else
                            <small class="text-muted" style="font-size: 0.73rem;">
                                {{ $totalBilled > 0 ? round(($totalOutstanding / $totalBilled) * 100, 1) : 0 }}% ค้างท่อ
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ar-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">อัตราจัดเก็บรายได้ (Collection Rate)</span>
                            <div class="fw-black mt-1 text-dark" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ $collectionRate }}
                                <span style="font-size: 0.8rem; font-weight: 600;">%</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-speedometer2 fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">ประสิทธิภาพการจัดเก็บ</span>
                        <small class="text-muted" style="font-size: 0.73rem;">เป้าหมาย >= 85%</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary by Rights Group Cards -->
    <div class="row g-3 px-3 mb-4">
        @foreach($typeSummaries as $ts)
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100 border" 
                     style="cursor: pointer; transition: all 0.2s ease;" 
                     onclick="if(window.filterDebtorType) filterDebtorType('{{ $ts->debtor_type }}')" 
                     title="คลิกเพื่อกรองเฉพาะสิทธิ {{ $ts->debtor_type }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-tag-fill text-primary me-1"></i> {{ $ts->debtor_type ?: 'ไม่ระบุสิทธิ' }}
                        </h6>
                        <span class="badge bg-light text-muted border rounded-pill">{{ $ts->account_count }} ผังบัญชี</span>
                    </div>
                    <div class="small text-muted mb-2">
                        @if($selectedPeriod === 'all')
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.78rem;">
                                <span>ยอดยกมา (OB):</span>
                                <span class="font-monospace text-dark">{{ number_format($ts->ob_balance, 2) }} บ.</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.78rem;">
                                <span>ตั้งเบิกปีนี้:</span>
                                <span class="font-monospace text-dark">{{ number_format($ts->total_billed, 2) }} บ.</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.78rem;">
                                <span>ชดเชยปีนี้:</span>
                                <span class="font-monospace text-success fw-semibold">{{ number_format($ts->total_collected, 2) }} บ.</span>
                            </div>
                        @else
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                                <span>ตั้งเบิกงวดนี้:</span>
                                <span class="font-monospace text-dark">{{ number_format($ts->total_billed, 2) }} บ.</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.8rem;">
                                <span>ชดเชยงวดนี้:</span>
                                <span class="font-monospace text-success fw-semibold">{{ number_format($ts->total_collected, 2) }} บ.</span>
                            </div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="small fw-bold text-dark">{{ $selectedPeriod === 'all' ? 'คงค้างสุทธิ:' : 'ค้างรับงวดนี้:' }}</span>
                        <span class="font-monospace fw-bold fs-6 text-primary">{{ number_format($ts->outstanding_balance, 2) }} บาท</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Accounts Table -->
    <div class="row px-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-primary"></i> ทะเบียนผังบัญชีลูกหนี้ค่ารักษาพยาบาล ({{ $selectedPeriodLabel }})</h6>
                        <small class="text-muted">
                            @if($selectedPeriod === 'all')
                                รวมยอดสะสมตลอดทั้งปี 1 ผังบัญชี ต่อ 1 รายการ (รวม {{ count($debtors) }} ผังบัญชี ไม่ซ้ำซ้อน)
                            @else
                                ข้อมูลรายการเคลื่อนไหวเฉพาะงวด {{ $selectedPeriodLabel }} (รวม {{ count($debtors) }} ผังบัญชี)
                            @endif
                        </small>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold">
                            ลูกหนี้คงค้างสุทธิรวม {{ number_format($totalOutstanding, 2) }} บาท
                        </span>
                    </div>
                </div>

                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="debtorsTable">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th>รหัสบัญชี</th>
                                <th>ชื่อผังบัญชีลูกหนี้</th>
                                <th class="text-center">สิทธิกองทุน</th>
                                @if($selectedPeriod === 'all')
                                    <th class="text-end">ยอดยกมา OB (บาท)</th>
                                    <th class="text-end">ยอดตั้งเบิกปีนี้ (บาท)</th>
                                    <th class="text-end">ชดเชยที่รับแล้วปีนี้ (บาท)</th>
                                    <th class="text-end text-primary pe-3">ลูกหนี้คงค้างสุทธิ (บาท)</th>
                                @else
                                    <th class="text-end">ยอดตั้งเบิกงวดนี้ (บาท)</th>
                                    <th class="text-end">ชดเชยที่รับแล้วงวดนี้ (บาท)</th>
                                    <th class="text-end text-primary pe-3">ลูกหนี้คงค้างงวดนี้ (บาท)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php $dIdx = 1; @endphp
                            @foreach($debtors as $d)
                                @php
                                    $ob = (float)($d->ob_balance ?? 0);
                                    $billed = (float)$d->total_billed;
                                    $collected = (float)$d->total_collected;
                                    $out = (float)$d->outstanding_balance;
                                @endphp
                                <tr>
                                    <td class="ps-3 text-center fw-bold text-muted" data-order="{{ $dIdx }}">{{ $dIdx++ }}</td>
                                    <td class="font-monospace fw-bold text-primary">{{ $d->account_code }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $d->account_name }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1">
                                            {{ $d->debtor_type ?: 'ทั่วไป' }}
                                        </span>
                                        @if($selectedPeriod === 'all' && isset($d->month_count) && $d->month_count > 0)
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5 ms-1" style="font-size: 0.7rem;" title="มีข้อมูลเคลื่อนไหว {{ $d->month_count }} งวดเดือน">
                                                {{ $d->month_count }} งวด
                                            </span>
                                        @endif
                                    </td>
                                    @if($selectedPeriod === 'all')
                                        <td class="text-end font-monospace text-muted" data-order="{{ $ob }}">{{ number_format($ob, 2) }}</td>
                                    @endif
                                    <td class="text-end font-monospace" data-order="{{ $billed }}">{{ number_format($billed, 2) }}</td>
                                    <td class="text-end font-monospace text-success" data-order="{{ $collected }}">{{ number_format($collected, 2) }}</td>
                                    <td class="text-end font-monospace pe-3 fw-bold {{ $out > 0.01 ? 'text-primary fs-6' : ($out < -0.01 ? 'text-danger' : 'text-muted') }}" data-order="{{ $out }}">
                                        {{ number_format($out, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end fw-bold py-2.5 ps-3">รวมทั้งหมด (ตามที่กรอง):</th>
                                @if($selectedPeriod === 'all')
                                    <th class="text-end font-monospace py-2.5 text-muted" id="footTotalOb">{{ number_format($totalOb, 2) }}</th>
                                @endif
                                <th class="text-end font-monospace py-2.5" id="footTotalBilled">{{ number_format($totalBilled, 2) }}</th>
                                <th class="text-end font-monospace py-2.5 text-success" id="footTotalCollected">{{ number_format($totalCollected, 2) }}</th>
                                <th class="text-end font-monospace py-2.5 text-primary pe-3 fs-6" id="footTotalOutstanding">{{ number_format($totalOutstanding, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
            var commonDom = '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-bottom"<"d-flex align-items-center"l><"d-flex align-items-center gap-2"fB>>' +
                            'rt' +
                            '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 border-top"<"text-muted small"i><"pagination-sm"p>>';

            var intVal = function (i) {
                if (typeof i === 'number') return i;
                if (typeof i === 'string') {
                    var stripped = i.replace(/<[^>]+>/g, '').replace(/,/g, '').trim();
                    return parseFloat(stripped) || 0;
                }
                return 0;
            };

            var fmt = function (num) {
                return num.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };

            if ($.fn.DataTable.isDataTable('#debtorsTable')) {
                $('#debtorsTable').DataTable().destroy();
            }

            var isAllPeriod = {{ $selectedPeriod === 'all' ? 'true' : 'false' }};
            var defaultOrderCol = isAllPeriod ? 7 : 6;

            var debtorDt = $('#debtorsTable').DataTable({
                dom: commonDom,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel-fill text-white fs-6"></i> ส่งออก Excel',
                        className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white fw-bold',
                        title: 'ทะเบียนลูกหนี้ค่ารักษาพยาบาล_HosFin_AR',
                        exportOptions: {
                            columns: ':visible',
                            footer: true
                        }
                    }
                ],
                language: {
                    search: "ค้นหา:",
                    searchPlaceholder: "พิมพ์ชื่อ หรือ รหัสบัญชี...",
                    lengthMenu: "แสดง _MENU_ ผังบัญชี",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ ผังบัญชี",
                    infoEmpty: "ไม่พบข้อมูลลูกหนี้",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ ผังบัญชี)",
                    zeroRecords: "ไม่พบข้อมูลลูกหนี้ที่ตรงกับคำค้นหา",
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[defaultOrderCol, 'desc']],
                columnDefs: [
                    { targets: [0], orderable: false }
                ],
                autoWidth: false,
                footerCallback: function (row, data, start, end, display) {
                    try {
                        var api = this.api();
                        if (isAllPeriod) {
                            var totalOb = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var totalBilled = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var totalCollected = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var totalOutstanding = api.column(7, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                            $('#footTotalOb').html(fmt(totalOb || 0));
                            $('#footTotalBilled').html(fmt(totalBilled || 0));
                            $('#footTotalCollected').html(fmt(totalCollected || 0));
                            $('#footTotalOutstanding').html(fmt(totalOutstanding || 0));
                        } else {
                            var totalBilled = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var totalCollected = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                            var totalOutstanding = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                            $('#footTotalBilled').html(fmt(totalBilled || 0));
                            $('#footTotalCollected').html(fmt(totalCollected || 0));
                            $('#footTotalOutstanding').html(fmt(totalOutstanding || 0));
                        }
                    } catch (e) {}
                }
            });

            // Quick filter by debtor type when clicking the summary cards
            window.filterDebtorType = function(type) {
                if (!type || type === 'all') {
                    debtorDt.column(3).search('').draw();
                } else {
                    debtorDt.column(3).search(type).draw();
                }
            };
            // Budget year change handler
            $('#select_budget_year').on('change', function() {
                var yr = $(this).val();
                window.location.href = "{{ url('hosfin/ar_report') }}?budget_year=" + yr + "&period=all";
            });
        }
    });
</script>
@endsection
