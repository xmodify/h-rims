@extends('layouts.app')

@section('content')
<style>
    .ap-card {
        border-radius: 14px;
        transition: all 0.25s ease-in-out;
        border: 1px solid #e2e8f0;
    }
    .ap-card:hover {
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
    /* Searchable Checkbox Dropdown Styling */
    .hover-bg {
        transition: background-color 0.15s ease;
    }
    .hover-bg:hover {
        background-color: #f1f5f9 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .account-options-list::-webkit-scrollbar {
        width: 6px;
    }
    .account-options-list::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 4px;
    }
    .account-options-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .account-options-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .account-filter-btn {
        min-width: 270px;
        max-width: 380px;
        background-color: #ffffff;
        border-color: #cbd5e1;
        font-size: 0.83rem;
    }
    .account-filter-btn:hover, .account-filter-btn:focus {
        border-color: #94a3b8;
        background-color: #f8fafc;
    }
    .account-search-input:focus {
        background-color: #ffffff !important;
        box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
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
                        <i class="bi bi-receipt-cutoff me-2 text-success"></i> รายงานทะเบียนเจ้าหนี้การค้า & บิลค้างชำระ (Accounts Payable)
                    </h5>
                    <small class="text-muted">ตรวจสอบยอดหนี้สินรอจ่ายชำระ รายชื่อบริษัทคู่ค้า และรายละเอียดบิลรายใบจากระบบ GL</small>
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

                    <a href="{{ url('hosfin/cash_register') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ffffff; border: 1.5px solid #059669; color: #059669;">
                        <i class="bi bi-cash-stack"></i> รับ-จ่าย (Cash)
                    </a>
                    <a href="{{ url('hosfin/ap_report') }}?budget_year={{ $budgetYear }}" class="btn rounded-pill px-3 d-flex align-items-center gap-1.5 shadow-sm" 
                       style="font-size: 0.85rem; height: 40px; font-weight: 700; background: #ef4444; border: 1.5px solid #ef4444; color: #ffffff;">
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

    @php
        $crossMismatchesCount = count($crossAccountDetails ?? []);
        $overpaidCount = count($overpaidBills ?? []);
        $totalOverpaidSum = isset($overpaidBills) && is_iterable($overpaidBills) ? collect($overpaidBills)->sum('overpaid_amount') : 0;
    @endphp

    @if($crossMismatchesCount > 0 || $overpaidCount > 0)
    <!-- Accounting Audit & Anomaly Alert Banner -->
    <div class="row px-3 mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" 
                 style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #f59e0b !important;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-warning bg-opacity-25 text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-shield-exclamation fs-3 text-warning"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 1.05rem;">
                                        <i class="bi bi-bell-fill text-warning me-1"></i> แจ้งเตือนการตรวจสอบทางบัญชี (Accounting Audit & Anomaly Detection)
                                    </h6>
                                    <span class="badge bg-warning text-dark border border-warning-subtle rounded-pill px-2.5 py-0.5" style="font-size: 0.75rem;">
                                        พบข้อสังเกต {{ $crossMismatchesCount + $overpaidCount }} รายการ
                                    </span>
                                </div>
                                <p class="text-secondary small mb-2 mb-lg-0" style="line-height: 1.5;">
                                    @if($crossMismatchesCount > 0)
                                        <span class="d-inline-block me-3">
                                            <i class="bi bi-arrow-left-right text-primary me-1"></i> <strong>ตัดจ่ายข้ามหมวดผังบัญชี (Cross-Account):</strong> 
                                            <span class="badge bg-white text-dark border px-2 py-0.5">{{ $crossMismatchesCount }} บิล</span>
                                            <span class="text-muted">(เช่น ตั้งหนี้ 134-ยา แต่ตัดจ่าย 135-เวชภัณฑ์ — <em>ระบบปรับกระทบยอดให้อัตโนมัติแล้ว</em>)</span>
                                        </span>
                                    @endif
                                    @if($overpaidCount > 0)
                                        <span class="d-inline-block mt-1 mt-md-0">
                                            <i class="bi bi-cash-coin text-danger me-1"></i> <strong>จ่ายเงินเกินยอดหนี้ (Overpaid):</strong> 
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5">{{ $overpaidCount }} บิล</span>
                                            <span class="text-muted">(ยอดจ่ายเกินรวม {{ number_format($totalOverpaidSum, 2) }} บาท)</span>
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-lg-auto">
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill shadow-sm px-3.5 py-2 d-inline-flex align-items-center gap-2" 
                                    data-bs-toggle="modal" data-bs-target="#apAuditModal" style="font-size: 0.86rem; border: 1px solid #d97706;">
                                <i class="bi bi-file-earmark-diff-fill text-dark"></i> ดูรายการ & แนวทางออกใบปรับปรุง (JV Guide)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 4 KPI Highlight Cards -->
    <div class="row g-3 px-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ap-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">ยอดหนี้ค้างจ่ายรวม (Unpaid)</span>
                            <div class="fw-black mt-1 text-danger" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalUnpaidSum, 2) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บาท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-danger bg-opacity-10 text-danger" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">ภาระหนี้สินคงค้าง</span>
                        <small class="text-muted" style="font-size: 0.73rem;">{{ number_format($totalUnpaidBillsCount) }} บิล</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ap-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">จำนวนบิลค้างชำระ</span>
                            <div class="fw-black mt-1 text-dark" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalUnpaidBillsCount) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">ใบ</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-danger bg-opacity-10 text-danger" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-receipt fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">รอการเบิกจ่ายชำระ</span>
                        <small class="text-muted" style="font-size: 0.73rem;">บริษัทคู่ค้า {{ number_format($totalVendorsCount) }} แห่ง</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ap-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">บริษัทคู่ค้าที่ค้างชำระ</span>
                            <div class="fw-black mt-1 text-primary" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalVendorsCount) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">บริษัท</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">ยา / เวชภัณฑ์ / จ้างเหมา</span>
                        <small class="text-muted" style="font-size: 0.73rem;">เฉลี่ย {{ $totalVendorsCount > 0 ? number_format($totalUnpaidSum / $totalVendorsCount, 0) : 0 }} บ./บริษัท</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 ap-card bg-white" style="border: 1.5px solid #e2e8f0 !important;">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.3px;">บิลที่ชำระครบแล้ว (Paid)</span>
                            <div class="fw-black mt-1 text-success" style="font-size: 1.5rem; font-family: monospace; font-weight: 900; line-height: 1.2;">
                                {{ number_format($totalPaidBillsCount) }}
                                <span style="font-size: 0.8rem; font-weight: 600;">ใบ</span>
                            </div>
                        </div>
                        <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success" style="width: 44px; height: 44px; display:flex; align-items:center; justify-content:center;">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">จ่ายชำระแล้วเรียบร้อย</span>
                        <small class="text-success fw-bold" style="font-size: 0.73rem;">{{ number_format($totalPaidSum, 2) }} บ.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="px-3 mb-3">
        <ul class="nav nav-pills p-1 bg-white rounded-pill shadow-sm d-inline-flex border" id="apTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'vendor' ? 'active' : '' }} rounded-pill fw-bold px-4 py-2" 
                        id="vendor-tab" data-bs-toggle="pill" data-bs-target="#vendor-tab-pane" type="button" role="tab" 
                        onclick="setTab('vendor')" style="font-size: 0.88rem;">
                    <i class="bi bi-building me-1"></i> สรุปยอดหนี้รายบริษัทคู่ค้า (Vendor Summary)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'bills' ? 'active' : '' }} rounded-pill fw-bold px-4 py-2" 
                        id="bills-tab" data-bs-toggle="pill" data-bs-target="#bills-tab-pane" type="button" role="tab" 
                        onclick="setTab('bills')" style="font-size: 0.88rem;">
                    <i class="bi bi-card-checklist me-1"></i> รายการบิลค้างชำระรายใบ (All Invoices)
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab Contents -->
    <div class="tab-content px-3" id="apTabContent">
        <!-- Tab 1: Vendors Summary -->
        <div class="tab-pane fade {{ $activeTab === 'vendor' ? 'show active' : '' }}" id="vendor-tab-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-columns-reverse me-2 text-danger"></i> ทะเบียนบริษัทคู่ค้าและยอดหนี้คงค้าง</h6>
                        <small class="text-muted">เรียงลำดับตามยอดหนี้คงค้างสูงสุด (Cr - Dr) สามารถคลิกหัวตารางเพื่อเรียงลำดับ หรือค้นหาได้ทันที</small>
                    </div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="d-flex align-items-center gap-1.5">
                            <label class="small text-muted fw-bold mb-0 text-nowrap" for="vendorAccountDropdownBtn">
                                <i class="bi bi-funnel-fill text-danger"></i> หมวดบัญชี:
                            </label>
                            <div class="dropdown account-filter-dropdown" id="vendorAccountDropdownWrapper">
                                <button class="btn btn-sm rounded-pill shadow-xs border-secondary-subtle fw-semibold dropdown-toggle text-start d-flex align-items-center justify-content-between gap-2 px-3 py-1.5 account-filter-btn" 
                                        type="button" 
                                        id="vendorAccountDropdownBtn" 
                                        data-bs-toggle="dropdown" 
                                        data-bs-auto-close="outside" 
                                        aria-expanded="false">
                                    <span class="text-truncate dropdown-label flex-grow-1">ทุกหมวดบัญชี (ทั้งหมด)</span>
                                </button>
                                <div class="dropdown-menu shadow-lg border-0 rounded-4 p-2 mt-1" aria-labelledby="vendorAccountDropdownBtn" style="min-width: 320px; max-width: 380px; font-size: 0.83rem;">
                                    <!-- Search Input -->
                                    <div class="p-1 mb-1">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-0 rounded-start-pill text-muted ps-2.5">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-0 rounded-end-pill account-search-input pe-2.5" placeholder="ค้นหารหัส หรือชื่อหมวดบัญชี...">
                                        </div>
                                    </div>
                                    <!-- Quick Actions Toolbar -->
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom pb-2">
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold btn-select-all text-danger" style="font-size: 0.78rem;">
                                            <i class="bi bi-check-all me-1"></i>เลือกทั้งหมด
                                        </button>
                                        <div class="small text-muted selected-count-text" style="font-size: 0.75rem;">
                                            เลือก <span class="selected-count-num fw-bold text-dark">{{ isset($accountChoices) ? $accountChoices->count() : 0 }}</span> / {{ isset($accountChoices) ? $accountChoices->count() : 0 }}
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted btn-deselect-all" style="font-size: 0.78rem;">
                                            <i class="bi bi-x-circle me-1"></i>ล้างค่า
                                        </button>
                                    </div>
                                    <!-- Scrollable Checkbox List -->
                                    <div class="account-options-list overflow-auto px-1" style="max-height: 260px;">
                                        @if(isset($accountChoices))
                                            @foreach($accountChoices as $acc)
                                                <label class="dropdown-item d-flex align-items-center gap-2 py-1.5 px-2 rounded-2 cursor-pointer hover-bg account-check-item mb-0.5" 
                                                       data-code="{{ $acc->account_code }}" 
                                                       data-name="{{ $acc->account_name }}"
                                                       data-search="{{ strtolower($acc->account_code . ' ' . $acc->account_name) }}">
                                                    <input class="form-check-input mt-0 flex-shrink-0 account-checkbox" 
                                                           type="checkbox" 
                                                           value="{{ $acc->account_code }}" 
                                                           data-name="{{ $acc->account_name }}" 
                                                           checked>
                                                    <div class="text-truncate flex-grow-1" style="line-height: 1.25;">
                                                        <span class="fw-bold text-dark font-monospace" style="font-size: 0.8rem;">{{ $acc->account_code }}</span>
                                                        <span class="text-muted d-block text-truncate" style="font-size: 0.75rem;">{{ $acc->account_name }}</span>
                                                    </div>
                                                    <span class="badge bg-light text-secondary border rounded-pill ms-auto flex-shrink-0" style="font-size: 0.7rem;">
                                                        {{ number_format($acc->bill_count) }} ใบ
                                                    </span>
                                                </label>
                                            @endforeach
                                        @endif
                                        <div class="text-center text-muted small py-3 no-account-found" style="display: none;">
                                            <i class="bi bi-search me-1"></i>ไม่พบหมวดบัญชีที่ค้นหา
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold">
                            หนี้คงค้างรวม {{ number_format($totalUnpaidSum, 2) }} บาท
                        </span>
                    </div>
                </div>
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="vendorTable">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th>ชื่อบริษัทคู่ค้า / เจ้าหนี้</th>
                                <th class="text-center">หมวดหมู่</th>
                                <th class="text-center">จำนวนบิลทั้งหมด</th>
                                <th class="text-center">บิลค้างจ่าย</th>
                                <th class="text-end">ยอดตั้งหนี้ (Cr)</th>
                                <th class="text-end">ยอดจ่ายแล้ว (Dr)</th>
                                <th class="text-end text-danger pe-3">หนี้คงเหลือ (ค้างชำระ)</th>
                                <th class="text-center" style="width: 100px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $vIdx = 1; @endphp
                            @foreach($vendorsSummary as $v)
                                @php
                                    $rem = (float)$v->remaining_debt;
                                    $hasDebt = $rem > 0.01;
                                @endphp
                                <tr class="vendor-row {{ $hasDebt ? '' : 'table-light opacity-75' }}" data-account-codes="{{ $v->account_codes ?? '' }}">
                                    <td class="ps-3 text-center fw-bold text-muted" data-order="{{ $vIdx }}">{{ $vIdx++ }}</td>
                                    <td>
                                        <div class="fw-bold text-dark vendor-name-cell">{{ $v->vendor_name }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1">
                                            {{ $v->category ?: 'ทั่วไป' }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold" data-order="{{ $v->total_bills }}">{{ number_format($v->total_bills) }}</td>
                                    <td class="text-center" data-order="{{ $v->unpaid_bills }}">
                                        @if($v->unpaid_bills > 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-bold">
                                                {{ number_format($v->unpaid_bills) }}
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">ครบแล้ว</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace" data-order="{{ $v->total_credit }}">{{ number_format($v->total_credit, 2) }}</td>
                                    <td class="text-end font-monospace text-success" data-order="{{ $v->total_debit }}">{{ number_format($v->total_debit, 2) }}</td>
                                    <td class="text-end font-monospace pe-3 fw-bold {{ $hasDebt ? 'text-danger fs-6' : 'text-muted' }}" data-order="{{ $rem }}">
                                        {{ number_format($rem, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 text-nowrap btn-view-vendor-bills shadow-sm"
                                                data-vendor="{{ $v->vendor_name }}"
                                                data-category="{{ $v->category ?: 'ทั่วไป' }}"
                                                data-total="{{ $v->total_bills }}"
                                                data-unpaid="{{ $v->unpaid_bills }}"
                                                data-remaining="{{ $v->remaining_debt }}">
                                            <i class="bi bi-receipt me-1"></i> ดูบิล
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold align-middle">
                                <th colspan="3" class="ps-3 text-center py-2.5 text-secondary">
                                    <i class="bi bi-calculator me-1"></i> รวมทั้งสิ้น (Total):
                                </th>
                                <th class="text-center font-monospace py-2.5" id="vtTotalBills">
                                    {{ number_format($vendorsSummary->sum('total_bills')) }}
                                </th>
                                <th class="text-center font-monospace py-2.5 text-danger" id="vtTotalUnpaidBills">
                                    {{ number_format($vendorsSummary->sum('unpaid_bills')) }}
                                </th>
                                <th class="text-end font-monospace py-2.5" id="vtTotalCredit">
                                    {{ number_format($vendorsSummary->sum('total_credit'), 2) }}
                                </th>
                                <th class="text-end font-monospace py-2.5 text-success" id="vtTotalDebit">
                                    {{ number_format($vendorsSummary->sum('total_debit'), 2) }}
                                </th>
                                <th class="text-end font-monospace py-2.5 text-danger pe-3 fs-6" id="vtTotalRemaining">
                                    {{ number_format($vendorsSummary->sum('remaining_debt'), 2) }}
                                </th>
                                <th class="text-center py-2.5"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: Individual Bills Detail -->
        <div class="tab-pane fade {{ $activeTab === 'bills' ? 'show active' : '' }}" id="bills-tab-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <!-- Clean Toolbar Header with Status Toggle -->
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-card-checklist text-primary"></i>
                            รายการบิลเจ้าหนี้การค้าทั้งหมด (All AP Invoices)
                        </h6>
                        <span class="text-muted small">
                            รวมบิลทั้งหมด {{ number_format($bills->count()) }} ใบ สามารถค้นหา จัดเรียงลำดับ และส่งออก Excel ได้ทันที
                        </span>
                    </div>
                    <!-- Status & Account Filter -->
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <!-- Account Category Filter -->
                        <div class="d-flex align-items-center gap-1.5">
                            <label class="small text-muted fw-bold mb-0 text-nowrap" for="billAccountDropdownBtn">
                                <i class="bi bi-funnel-fill text-primary"></i> หมวดบัญชี:
                            </label>
                            <div class="dropdown account-filter-dropdown" id="billAccountDropdownWrapper">
                                <button class="btn btn-sm rounded-pill shadow-xs border-secondary-subtle fw-semibold dropdown-toggle text-start d-flex align-items-center justify-content-between gap-2 px-3 py-1.5 account-filter-btn" 
                                        type="button" 
                                        id="billAccountDropdownBtn" 
                                        data-bs-toggle="dropdown" 
                                        data-bs-auto-close="outside" 
                                        aria-expanded="false">
                                    <span class="text-truncate dropdown-label flex-grow-1">ทุกหมวดบัญชี (ทั้งหมด)</span>
                                </button>
                                <div class="dropdown-menu shadow-lg border-0 rounded-4 p-2 mt-1" aria-labelledby="billAccountDropdownBtn" style="min-width: 320px; max-width: 380px; font-size: 0.83rem;">
                                    <!-- Search Input -->
                                    <div class="p-1 mb-1">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light border-0 rounded-start-pill text-muted ps-2.5">
                                                <i class="bi bi-search"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-0 rounded-end-pill account-search-input pe-2.5" placeholder="ค้นหารหัส หรือชื่อหมวดบัญชี...">
                                        </div>
                                    </div>
                                    <!-- Quick Actions Toolbar -->
                                    <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1 border-bottom pb-2">
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 fw-bold btn-select-all text-primary" style="font-size: 0.78rem;">
                                            <i class="bi bi-check-all me-1"></i>เลือกทั้งหมด
                                        </button>
                                        <div class="small text-muted selected-count-text" style="font-size: 0.75rem;">
                                            เลือก <span class="selected-count-num fw-bold text-dark">{{ isset($accountChoices) ? $accountChoices->count() : 0 }}</span> / {{ isset($accountChoices) ? $accountChoices->count() : 0 }}
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted btn-deselect-all" style="font-size: 0.78rem;">
                                            <i class="bi bi-x-circle me-1"></i>ล้างค่า
                                        </button>
                                    </div>
                                    <!-- Scrollable Checkbox List -->
                                    <div class="account-options-list overflow-auto px-1" style="max-height: 260px;">
                                        @if(isset($accountChoices))
                                            @foreach($accountChoices as $acc)
                                                <label class="dropdown-item d-flex align-items-center gap-2 py-1.5 px-2 rounded-2 cursor-pointer hover-bg account-check-item mb-0.5" 
                                                       data-code="{{ $acc->account_code }}" 
                                                       data-name="{{ $acc->account_name }}"
                                                       data-search="{{ strtolower($acc->account_code . ' ' . $acc->account_name) }}">
                                                    <input class="form-check-input mt-0 flex-shrink-0 account-checkbox" 
                                                           type="checkbox" 
                                                           value="{{ $acc->account_code }}" 
                                                           data-name="{{ $acc->account_name }}" 
                                                           checked>
                                                    <div class="text-truncate flex-grow-1" style="line-height: 1.25;">
                                                        <span class="fw-bold text-dark font-monospace" style="font-size: 0.8rem;">{{ $acc->account_code }}</span>
                                                        <span class="text-muted d-block text-truncate" style="font-size: 0.75rem;">{{ $acc->account_name }}</span>
                                                    </div>
                                                    <span class="badge bg-light text-secondary border rounded-pill ms-auto flex-shrink-0" style="font-size: 0.7rem;">
                                                        {{ number_format($acc->bill_count) }} ใบ
                                                    </span>
                                                </label>
                                            @endforeach
                                        @endif
                                        <div class="text-center text-muted small py-3 no-account-found" style="display: none;">
                                            <i class="bi bi-search me-1"></i>ไม่พบหมวดบัญชีที่ค้นหา
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Filter Pills -->
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small fw-bold me-1">สถานะ:</span>
                            <div class="btn-group btn-group-sm rounded-pill p-1 bg-light border shadow-sm" role="group">
                                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold active btn-secondary text-white btn-bill-status" data-status="all">
                                    บิลทั้งหมด <span class="badge bg-light text-dark rounded-pill ms-1">{{ number_format($bills->count()) }}</span>
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold text-dark btn-bill-status" data-status="unpaid">
                                    ค้างชำระ <span class="badge bg-danger text-white rounded-pill ms-1">{{ number_format($totalUnpaidBillsCount) }}</span>
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold text-dark btn-bill-status" data-status="paid">
                                    ชำระครบแล้ว <span class="badge bg-success text-white rounded-pill ms-1">{{ number_format($totalPaidBillsCount) }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bills Table -->
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="billsTable">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th>เลขที่บิล (ApAr)</th>
                                <th class="text-center" style="width: 110px;">วันที่บิล</th>
                                <th class="text-center" style="width: 110px;">ค้างชำระ (วัน)</th>
                                <th>บริษัทคู่ค้า / เจ้าหนี้</th>
                                <th>หมวดบัญชี</th>
                                <th class="text-end">ยอดตั้งหนี้ (Cr)</th>
                                <th class="text-end">ยอดจ่ายแล้ว (Dr)</th>
                                <th class="text-end text-danger pe-3">คงเหลือค้างจ่าย</th>
                                <th class="text-center" style="width: 90px;">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $bIdx = 1; @endphp
                            @foreach($bills as $b)
                                <tr class="bill-row" data-account-code="{{ $b->account_code }}">
                                    <td class="ps-3 text-center fw-bold text-muted" data-order="{{ $bIdx }}">{{ $bIdx++ }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $b->bill_no }}</td>
                                    <td class="text-center small text-nowrap" data-order="{{ $b->parsed_bill_date }}">{{ $b->thai_bill_date }}</td>
                                    <td class="text-center" data-order="{{ $b->aging_days }}">
                                        @if($b->is_paid)
                                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5">-</span>
                                        @elseif($b->aging_days > 90)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-bold">
                                                {{ number_format($b->aging_days) }}
                                            </span>
                                        @elseif($b->aging_days > 30)
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5">
                                                {{ number_format($b->aging_days) }}
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                                {{ number_format($b->aging_days) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $b->vendor_name }}</strong>
                                        @if($b->category)
                                            <span class="badge bg-light text-muted border rounded-pill ms-1">{{ $b->category }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $b->account_code }}</small><br>
                                        <span class="small text-dark">{{ $b->account_name }}</span>
                                    </td>
                                    <td class="text-end font-monospace" data-order="{{ $b->total_credit }}">{{ number_format($b->total_credit, 2) }}</td>
                                    <td class="text-end font-monospace text-success" data-order="{{ $b->total_debit }}">{{ number_format($b->total_debit, 2) }}</td>
                                    <td class="text-end font-monospace pe-3 fw-bold {{ $b->remaining_debt > 0.01 ? 'text-danger fs-6' : 'text-muted' }}" data-order="{{ $b->remaining_debt }}">
                                        {{ number_format($b->remaining_debt, 2) }}
                                    </td>
                                    <td class="text-center" data-order="{{ $b->is_paid ? 1 : 0 }}">
                                        @if($b->is_paid)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">ชำระครบ</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">ค้างจ่าย</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold align-middle">
                                <th colspan="6" class="ps-3 text-center py-2.5 text-secondary">
                                    <i class="bi bi-calculator me-1"></i> รวมทั้งสิ้น (Total):
                                </th>
                                <th class="text-end font-monospace py-2.5" id="btTotalCredit">
                                    {{ number_format($bills->sum('total_credit'), 2) }}
                                </th>
                                <th class="text-end font-monospace py-2.5 text-success" id="btTotalDebit">
                                    {{ number_format($bills->sum('total_debit'), 2) }}
                                </th>
                                <th class="text-end font-monospace py-2.5 text-danger pe-3 fs-6" id="btTotalRemaining">
                                    {{ number_format($bills->sum('remaining_debt'), 2) }}
                                </th>
                                <th class="text-center py-2.5"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: รายการบิลของบริษัทคู่ค้า (Vendor Bills Modal) -->
<div class="modal fade" id="vendorBillsModal" tabindex="-1" aria-labelledby="vendorBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-1" id="vendorBillsModalLabel">
                        <i class="bi bi-building text-primary"></i>
                        <span id="mvModalVendorTitle">รายการบิล</span>
                    </h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap small">
                        <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1" id="mvModalCategory">ทั่วไป</span>
                        <span class="text-muted">บิลทั้งหมด: <strong class="text-dark" id="mvModalTotal">0</strong> ใบ</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted">ค้างชำระ: <strong class="text-danger" id="mvModalUnpaid">0</strong> ใบ</span>
                        <span class="text-muted">|</span>
                        <span class="text-muted">หนี้ค้างรวม: <strong class="text-danger font-monospace" id="mvModalRemaining">0.00</strong> บาท</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div id="modalLoadingSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2 text-muted small">กำลังโหลดรายการบิลของบริษัทนี้...</div>
                </div>
                <div id="modalTableWrapper" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100" id="modalBillsTable">
                            <thead class="table-light">
                                <tr class="text-secondary small fw-bold">
                                    <th class="ps-3 text-center" style="width: 50px;">#</th>
                                    <th>เลขที่บิล (ApAr)</th>
                                    <th class="text-center" style="width: 110px;">วันที่บิล</th>
                                    <th class="text-center" style="width: 110px;">ค้างชำระ (วัน)</th>
                                    <th>หมวดบัญชี</th>
                                    <th class="text-end">ยอดตั้งหนี้ (Cr)</th>
                                    <th class="text-end">ยอดจ่ายแล้ว (Dr)</th>
                                    <th class="text-end text-danger pe-3">คงเหลือค้างจ่าย</th>
                                    <th class="text-center" style="width: 90px;">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="modalBillsTbody">
                            </tbody>
                            <tfoot class="table-light border-top border-2">
                                <tr class="fw-bold align-middle">
                                    <th colspan="5" class="ps-3 text-center py-2.5 text-secondary">
                                        <i class="bi bi-calculator me-1"></i> รวมของบริษัทนี้:
                                    </th>
                                    <th class="text-end font-monospace py-2.5" id="mvFooterCredit">0.00</th>
                                    <th class="text-end font-monospace py-2.5 text-success" id="mvFooterDebit">0.00</th>
                                    <th class="text-end font-monospace py-2.5 text-danger pe-3 fs-6" id="mvFooterRemaining">0.00</th>
                                    <th class="text-center py-2.5"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                <span class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> รายการบิลอ้างอิงจากโปรแกรม GL
                </span>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- AP Audit & Anomaly Modal -->
<div class="modal fade" id="apAuditModal" tabindex="-1" aria-labelledby="apAuditModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle p-2 bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-shield-exclamation fs-4"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="apAuditModalTitle">
                            ระบบตรวจสอบและแจ้งเตือนความผิดปกติของบัญชีเจ้าหนี้ (AP Audit & Reconciliation)
                        </h6>
                        <small style="color: #94a3b8; font-size: 0.78rem;">
                            ปีงบประมาณ {{ $budgetYear }} | ข้อสังเกตจากการกระทบยอด Subledger & GL พร้อมแนวทางออกใบสำคัญปรับปรุง (JV)
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Nav Tabs -->
            <div class="bg-light border-bottom px-4 pt-2">
                <ul class="nav nav-tabs border-0" id="auditTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold border-0 border-bottom border-3 border-warning text-dark py-2 px-3" 
                                id="audit-cross-tab" data-bs-toggle="tab" data-bs-target="#audit-cross-pane" type="button" role="tab" style="font-size: 0.88rem;">
                            <i class="bi bi-arrow-left-right text-primary me-1"></i> ตัดจ่ายข้ามผังบัญชี (Cross-Account)
                            <span class="badge bg-warning text-dark ms-1 rounded-pill">{{ count($crossAccountDetails ?? []) }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold border-0 text-secondary py-2 px-3" 
                                id="audit-overpaid-tab" data-bs-toggle="tab" data-bs-target="#audit-overpaid-pane" type="button" role="tab" style="font-size: 0.88rem;">
                            <i class="bi bi-cash-coin text-danger me-1"></i> ตัดจ่ายเงินเกินยอดหนี้ (Overpaid)
                            <span class="badge bg-danger-subtle text-danger ms-1 rounded-pill">{{ count($overpaidBills ?? []) }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold border-0 text-secondary py-2 px-3" 
                                id="audit-guide-tab" data-bs-toggle="tab" data-bs-target="#audit-guide-pane" type="button" role="tab" style="font-size: 0.88rem;">
                            <i class="bi bi-journal-check text-success me-1"></i> ขั้นตอนการออก JV สำหรับเจ้าหน้าที่
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <div class="tab-content" id="auditTabContent">
                    <!-- Tab 1: Cross-Account -->
                    <div class="tab-pane fade show active" id="audit-cross-pane" role="tabpanel">
                        <div class="alert alert-info border-0 rounded-3 p-3 mb-3 d-flex align-items-start gap-2.5 shadow-sm" style="background-color: #e0f2fe; color: #0369a1;">
                            <i class="bi bi-info-circle-fill fs-5 mt-0.5 flex-shrink-0"></i>
                            <div class="small" style="line-height: 1.5;">
                                <strong>คำชี้แจง:</strong> รายการด้านล่างคือบิลที่มีการตั้งหนี้ (Credit) ในผังบัญชีหนึ่ง และตัดจ่ายชำระ (Debit) ในอีกผังบัญชีหนึ่ง 
                                ทำให้งบทดลอง (Trial Balance) แสดงยอดค้างและยอดติดลบค้างข้ามผัง แม้ว่าในหน้ารายงานนี้ระบบจะคำนวณหักกลบ (Net) ให้อัตโนมัติแล้ว 
                                แต่เจ้าหน้าที่การเงินควรออกใบสำคัญปรับปรุง (JV) เพื่อล้างยอดแยกประเภทให้เป็น 0.00 บาท อย่างสมบูรณ์
                            </div>
                        </div>

                        <div class="table-responsive bg-white rounded-3 shadow-sm border">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center py-2.5" style="width: 40px;">#</th>
                                        <th class="py-2.5">เลขที่บิล (APAR) / บริษัท</th>
                                        <th class="py-2.5">รายการตั้งหนี้ (Cr)</th>
                                        <th class="py-2.5">รายการตัดจ่าย (Dr)</th>
                                        <th class="text-end py-2.5" style="width: 120px;">หนี้สุทธิ (Net)</th>
                                        <th class="py-2.5" style="min-width: 250px;">แนวทางออกใบสำคัญปรับปรุง (JV Guide)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($crossAccountDetails as $cIdx => $item)
                                        @php
                                            $isFullyPaid = abs($item['net_rem']) < 0.01;
                                            $firstCr = $item['cr_vouchers']->first();
                                            $firstDr = $item['dr_vouchers']->first();
                                            $hasDifferentAccounts = $firstCr && $firstDr && ($firstCr->account_code !== $firstDr->account_code);
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted fw-bold">{{ $cIdx + 1 }}</td>
                                            <td>
                                                <div class="fw-bold font-monospace text-primary">{{ $item['bill_no'] }}</div>
                                                <div class="text-dark small fw-semibold">{{ $item['vendor_name'] }}</div>
                                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">{{ $item['category'] }}</span>
                                            </td>
                                            <td>
                                                @forelse($item['cr_vouchers'] as $crV)
                                                    <div class="mb-1 pb-1 border-bottom border-light">
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">{{ $crV->voucher_no }}</span>
                                                        <small class="text-muted ms-1">{{ $crV->voucher_date }}</small><br>
                                                        <span class="font-monospace fw-semibold text-dark" style="font-size: 0.78rem;">{{ $crV->account_code }}</span>
                                                        <small class="text-muted d-block" style="font-size: 0.74rem;">{{ $crV->account_name }}</small>
                                                        <span class="fw-bold text-dark font-monospace">{{ number_format($crV->credit, 2) }}</span> บ.
                                                    </div>
                                                @empty
                                                    <span class="text-muted">-</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @forelse($item['dr_vouchers'] as $drV)
                                                    <div class="mb-1 pb-1 border-bottom border-light">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">{{ $drV->voucher_no }}</span>
                                                        <small class="text-muted ms-1">{{ $drV->voucher_date }}</small><br>
                                                        <span class="font-monospace fw-semibold text-dark" style="font-size: 0.78rem;">{{ $drV->account_code }}</span>
                                                        <small class="text-muted d-block" style="font-size: 0.74rem;">{{ $drV->account_name }}</small>
                                                        <span class="fw-bold text-success font-monospace">{{ number_format($drV->debit, 2) }}</span> บ.
                                                    </div>
                                                @empty
                                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5">ยังไม่มีการตัดจ่าย</span>
                                                @endforelse
                                            </td>
                                            <td class="text-end">
                                                @if($isFullyPaid)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 mb-1">
                                                        <i class="bi bi-check2 me-1"></i>ชำระครบแล้ว
                                                    </span>
                                                    <div class="font-monospace text-muted small">0.00 บ.</div>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 mb-1">ค้างชำระสุทธิ</span>
                                                    <div class="font-monospace fw-bold text-danger">{{ number_format($item['net_rem'], 2) }} บ.</div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($hasDifferentAccounts)
                                                    <div class="p-2 rounded bg-light border font-monospace small" style="background-color: #f1f5f9 !important;">
                                                        <div class="fw-bold text-secondary mb-1" style="font-family: inherit; font-size: 0.78rem;">
                                                            <i class="bi bi-pencil-fill text-warning me-1"></i> ออกใบสำคัญปรับปรุง (JV):
                                                        </div>
                                                        <div class="text-primary fw-bold" style="font-size: 0.78rem;">
                                                            Dr. {{ $firstCr->account_code }} 
                                                            <span class="text-muted fw-normal">({{ \Illuminate\Support\Str::limit($firstCr->account_name, 18) }})</span> 
                                                            <span class="float-end">{{ number_format(min($firstCr->credit, $firstDr->debit), 2) }}</span>
                                                        </div>
                                                        <div class="text-danger fw-bold ps-3" style="font-size: 0.78rem;">
                                                            Cr. {{ $firstDr->account_code }} 
                                                            <span class="text-muted fw-normal">({{ \Illuminate\Support\Str::limit($firstDr->account_name, 18) }})</span> 
                                                            <span class="float-end">{{ number_format(min($firstCr->credit, $firstDr->debit), 2) }}</span>
                                                        </div>
                                                        <div class="text-muted mt-1 pt-1 border-top" style="font-size: 0.72rem;">
                                                            ระบุ APAR: <strong>{{ $item['bill_no'] }}</strong>
                                                        </div>
                                                    </div>
                                                @elseif($item['dr_vouchers']->isEmpty())
                                                    <div class="p-2 rounded bg-light border small text-muted">
                                                        <i class="bi bi-layers text-info me-1"></i> บิลแยกรายการตั้งหนี้หลายหมวดบัญชีในใบเดียว (Split Items) — ผังบัญชีถูกต้องแล้ว
                                                    </div>
                                                @else
                                                    <div class="p-2 rounded bg-light border small text-muted">
                                                        <i class="bi bi-check-circle text-success me-1"></i> มีรายการปรับปรุงในระบบแล้ว
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                                ไม่พบรายการตัดจ่ายข้ามผังบัญชี ข้อมูลถูกต้องสมบูรณ์
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Overpaid Bills -->
                    <div class="tab-pane fade" id="audit-overpaid-pane" role="tabpanel">
                        <div class="alert alert-warning border-0 rounded-3 p-3 mb-3 d-flex align-items-start gap-2.5 shadow-sm" style="background-color: #fef3c7; color: #92400e;">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-0.5 flex-shrink-0"></i>
                            <div class="small" style="line-height: 1.5;">
                                <strong>คำชี้แจง:</strong> บิลด้านล่างมียอดตัดจ่าย (Debit) สูงกว่ายอดตั้งหนี้ (Credit) 
                                สาเหตุอาจเกิดจาก (1) เศษสตางค์จากการปัดทศนิยม, (2) มีการบันทึกใบเสร็จจ่ายเงินซ้ำ, หรือ (3) ยังไม่ได้บันทึกใบตั้งหนี้/ใบลดหนี้ (CN) 
                                กรุณาตรวจสอบและดำเนินการปรับปรุง
                            </div>
                        </div>

                        <div class="table-responsive bg-white rounded-3 shadow-sm border">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center py-2.5" style="width: 40px;">#</th>
                                        <th class="py-2.5">เลขที่บิล (APAR)</th>
                                        <th class="py-2.5">บริษัทคู่ค้า</th>
                                        <th class="text-end py-2.5">ยอดตั้งหนี้ (Cr)</th>
                                        <th class="text-end py-2.5">ยอดตัดจ่าย (Dr)</th>
                                        <th class="text-end py-2.5 text-danger fw-bold">ยอดจ่ายเกิน (Overpaid)</th>
                                        <th class="py-2.5" style="min-width: 250px;">การวินิจฉัย & แนวทางแก้ไข</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($overpaidBills as $oIdx => $ob)
                                        @php
                                            $isRounding = $ob->overpaid_amount <= 5.00;
                                        @endphp
                                        <tr>
                                            <td class="text-center text-muted fw-bold">{{ $oIdx + 1 }}</td>
                                            <td class="font-monospace fw-bold text-primary">{{ $ob->bill_no }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $ob->vendor_name ?: '-' }}</div>
                                                <small class="text-muted">{{ $ob->category ?: 'ทั่วไป' }}</small>
                                            </td>
                                            <td class="text-end font-monospace">{{ number_format($ob->total_cr, 2) }}</td>
                                            <td class="text-end font-monospace text-success">{{ number_format($ob->total_dr, 2) }}</td>
                                            <td class="text-end font-monospace fw-bold text-danger" style="font-size: 0.95rem;">
                                                +{{ number_format($ob->overpaid_amount, 2) }}
                                            </td>
                                            <td>
                                                @if($isRounding)
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0.5 mb-1">
                                                        <i class="bi bi-coin me-1"></i>เศษสตางค์ ({{ number_format($ob->overpaid_amount, 2) }} บ.)
                                                    </span>
                                                    <div class="small text-muted" style="font-size: 0.76rem;">
                                                        เกิดจากการปัดทศนิยมในระบบ สามารถออก JV ปรับปรุงเข้าบัญชีกำไร/ขาดทุนจากเศษสตางค์ได้
                                                    </div>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 mb-1">
                                                        <i class="bi bi-exclamation-octagon me-1"></i>จ่ายเกินยอดหนี้ ({{ number_format($ob->overpaid_amount, 2) }} บ.)
                                                    </span>
                                                    <div class="small text-danger fw-semibold" style="font-size: 0.76rem;">
                                                        ตรวจสอบใบสำคัญจ่ายว่ามีการบันทึกซ้ำ หรือยังไม่ได้บันทึกใบตั้งหนี้/ใบลดหนี้ให้ครบถ้วน
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                                                ไม่พบบิลที่มีการจ่ายเงินเกินยอดหนี้
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 3: Step-by-Step JV Guide -->
                    <div class="tab-pane fade" id="audit-guide-pane" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card h-100 border rounded-4 shadow-sm bg-white p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">1</div>
                                        <h6 class="fw-bold text-dark mb-0">เหตุผลที่ต้องออก JV</h6>
                                    </div>
                                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                                        ในระบบ HosFin Dashboard เราได้ทำการกระทบยอด (Net) ให้บิลที่มีการตัดจ่ายข้ามผังแสดงสถานะชำระครบแล้วเพื่อไม่ให้ยอดหนี้รวมบวมเกินจริง 
                                        อย่างไรก็ตาม ในงบทดลองของโรงพยาบาล (Trial Balance) บัญชี <code>2101...</code> แต่ละหมวดจะยังคงมียอดค้างและยอดติดลบข้ามกันอยู่ 
                                        การออก JV จะทำให้งบการเงินสะท้อนความเป็นจริง 100%
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border rounded-4 shadow-sm bg-white p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">2</div>
                                        <h6 class="fw-bold text-dark mb-0">ขั้นตอนการบันทึกใน GL</h6>
                                    </div>
                                    <ol class="text-muted small ps-3 mb-0" style="line-height: 1.7;">
                                        <li>เปิดโปรแกรม <strong>GL</strong> -> เมนู <em>สมุดรายวันทั่วไป</em></li>
                                        <li>กดปุ่ม <strong>"เพิ่มเอกสาร (JV)"</strong> ใส่วันที่งวดปัจจุบัน</li>
                                        <li><strong>เดบิต (Dr)</strong> รหัสผังที่มียอดค้างเกินจริง (เช่น ยา 134)</li>
                                        <li><strong>เครดิต (Cr)</strong> รหัสผังที่ถูกตัดจ่ายผิดไป (เช่น เวชภัณฑ์ 135)</li>
                                        <li><strong>สำคัญที่สุด:</strong> ต้องกรอกช่อง <code>APAR</code> ให้ตรงกับเลขที่บิลเดิม</li>
                                    </ol>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border rounded-4 shadow-sm bg-white p-3">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">3</div>
                                        <h6 class="fw-bold text-dark mb-0">การอัปเดตข้อมูล</h6>
                                    </div>
                                    <p class="text-muted small mb-0" style="line-height: 1.6;">
                                        หลังจากบันทึกใบสำคัญ JV ในโปรแกรม GL แล้ว ให้กลับมาที่หน้านี้แล้วกดปุ่ม <strong>"Sync GL"</strong> หรือนำเข้าไฟล์ GL ล่าสุด 
                                        ระบบจะดึงรายการ JV เข้ามาปรับล้างยอดหนี้ในผังย่อยและปิดข้อผิดพลาดให้โดยอัตโนมัติทันที
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top py-2.5 px-4 d-flex justify-content-between">
                <span class="text-muted small">
                    <i class="bi bi-shield-check text-success me-1"></i> ระบบตรวจสอบความถูกต้องอัตโนมัติโดย HosFin Audit Engine
                </span>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script>
    function setTab(tabName) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }

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

            var fmtInt = function (num) {
                return num.toLocaleString('th-TH');
            };

            // Independent Multi-select Account Filter states
            var allVendorAccountCodes = [];
            var selectedVendorAccounts = new Set();

            $('#vendorAccountDropdownWrapper .account-checkbox').each(function() {
                var c = $(this).val();
                if (c && allVendorAccountCodes.indexOf(c) === -1) {
                    allVendorAccountCodes.push(c);
                    selectedVendorAccounts.add(c);
                }
            });

            var allBillAccountCodes = [];
            var selectedBillAccounts = new Set();

            $('#billAccountDropdownWrapper .account-checkbox').each(function() {
                var c = $(this).val();
                if (c && allBillAccountCodes.indexOf(c) === -1) {
                    allBillAccountCodes.push(c);
                    selectedBillAccounts.add(c);
                }
            });

            // Custom search filter for DataTables (both Vendor Table and Bills Table - INDEPENDENT)
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (!settings.nTable) return true;

                // Vendor Table filter (Tab 1)
                if (settings.nTable.id === 'vendorTable') {
                    if (allVendorAccountCodes.length === 0 || selectedVendorAccounts.size === allVendorAccountCodes.length) {
                        return true;
                    }
                    if (selectedVendorAccounts.size === 0) {
                        return false;
                    }
                    var rowNode = settings.aoData[dataIndex].nTr;
                    var accCodesStr = $(rowNode).attr('data-account-codes') || '';
                    if (!accCodesStr) return false;
                    var rowCodes = accCodesStr.split(',');
                    for (var i = 0; i < rowCodes.length; i++) {
                        if (selectedVendorAccounts.has(rowCodes[i].trim())) {
                            return true;
                        }
                    }
                    return false;
                }

                // Bills Table filter (Tab 2)
                if (settings.nTable.id === 'billsTable') {
                    if (allBillAccountCodes.length === 0 || selectedBillAccounts.size === allBillAccountCodes.length) {
                        return true;
                    }
                    if (selectedBillAccounts.size === 0) {
                        return false;
                    }
                    var rowNode = settings.aoData[dataIndex].nTr;
                    var code = $(rowNode).attr('data-account-code') || '';
                    return selectedBillAccounts.has(code.trim());
                }

                return true;
            });

            // 1. Initialize Vendor Table DataTable
            if ($.fn.DataTable.isDataTable('#vendorTable')) {
                $('#vendorTable').DataTable().destroy();
            }

            var vendorDt = $('#vendorTable').DataTable({
                dom: commonDom,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel-fill text-white fs-6"></i> ส่งออก Excel',
                        className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white fw-bold',
                        title: 'ทะเบียนเจ้าหนี้การค้าและยอดหนี้คงค้าง_HosFin',
                        exportOptions: {
                            columns: ':visible',
                            footer: true
                        }
                    }
                ],
                language: {
                    search: "ค้นหา:",
                    searchPlaceholder: "พิมพ์ชื่อบริษัท หรือ หมวดหมู่...",
                    lengthMenu: "แสดง _MENU_ บริษัท",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ บริษัท",
                    infoEmpty: "ไม่พบข้อมูลบริษัท",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ บริษัท)",
                    zeroRecords: "ไม่พบข้อมูลบริษัทที่ตรงกับคำค้นหา",
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[7, 'desc']], // Column index 7 is หนี้คงเหลือ (ค้างชำระ)
                columnDefs: [
                    { targets: [0, 8], orderable: false } // # and จัดการ
                ],
                autoWidth: false,
                footerCallback: function (row, data, start, end, display) {
                    try {
                        var api = this.api();
                        var totalBills = api.column(3, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var unpaidBills = api.column(4, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalCredit = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalDebit = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalRemaining = api.column(7, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                        $('#vtTotalBills').html(fmtInt(totalBills || 0));
                        $('#vtTotalUnpaidBills').html(fmtInt(unpaidBills || 0));
                        $('#vtTotalCredit').html(fmt(totalCredit || 0));
                        $('#vtTotalDebit').html(fmt(totalDebit || 0));
                        $('#vtTotalRemaining').html(fmt(totalRemaining || 0));
                    } catch (e) {}
                }
            });

            // 2. Initialize Bills Table DataTable
            if ($.fn.DataTable.isDataTable('#billsTable')) {
                $('#billsTable').DataTable().destroy();
            }

            var billsDt = $('#billsTable').DataTable({
                dom: commonDom,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: '<i class="bi bi-file-earmark-excel-fill text-white fs-6"></i> ส่งออก Excel',
                        className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white fw-bold',
                        title: 'รายการบิลเจ้าหนี้การค้าทั้งหมด_HosFin',
                        exportOptions: {
                            columns: ':visible',
                            footer: true,
                            format: {
                                body: function (data, row, column, node) {
                                    var text = $('<div>').html(data).text().trim();
                                    // Column 3 is ค้างชำระ (วัน) - export pure integer
                                    if (column === 3) {
                                        if (text === '-' || text === '') return '';
                                        var cleanNum = text.replace(/วัน/g, '').replace(/,/g, '').trim();
                                        return isNaN(cleanNum) || cleanNum === '' ? '' : parseInt(cleanNum, 10);
                                    }
                                    return text;
                                }
                            }
                        }
                    }
                ],
                language: {
                    search: "ค้นหา:",
                    searchPlaceholder: "พิมพ์เลขที่บิล, บริษัท, หรือหมวดบัญชี...",
                    lengthMenu: "แสดง _MENU_ บิล",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ บิล",
                    infoEmpty: "ไม่พบรายการบิล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ บิล)",
                    zeroRecords: "ไม่พบรายการบิลที่ตรงกับคำค้นหา",
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                order: [[8, 'desc']], // Column index 8 is คงเหลือค้างจ่าย
                columnDefs: [
                    { targets: [0], orderable: false } // # column
                ],
                autoWidth: false,
                footerCallback: function (row, data, start, end, display) {
                    try {
                        var api = this.api();
                        var totalCredit = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalDebit = api.column(7, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                        var totalRemaining = api.column(8, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                        $('#btTotalCredit').html(fmt(totalCredit || 0));
                        $('#btTotalDebit').html(fmt(totalDebit || 0));
                        $('#btTotalRemaining').html(fmt(totalRemaining || 0));
                    } catch (e) {}
                }
            });

            // -------------------------------------------------------------
            // Independent Account Filter: Tab 1 (Vendor Summary)
            // -------------------------------------------------------------
            function updateVendorFilterState() {
                var total = allVendorAccountCodes.length;
                var count = selectedVendorAccounts.size;

                $('#vendorAccountDropdownWrapper .selected-count-num').text(count);

                var labelHtml = '';
                if (count === total && total > 0) {
                    labelHtml = 'ทุกหมวดบัญชี (ทั้งหมด)';
                } else if (count === 0) {
                    labelHtml = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>ไม่ได้เลือกหมวดบัญชี</span>';
                } else if (count === 1) {
                    var singleCode = Array.from(selectedVendorAccounts)[0];
                    var singleItem = $('#vendorAccountDropdownWrapper .account-checkbox[value="' + singleCode + '"]').first();
                    var singleName = singleItem.data('name') || '';
                    labelHtml = '<span class="fw-bold font-monospace">' + singleCode + '</span> - ' + singleName;
                } else {
                    labelHtml = 'เลือก <span class="badge bg-danger text-white rounded-pill px-2 py-0.5 ms-1">' + count + '</span> หมวดบัญชี';
                }

                $('#vendorAccountDropdownBtn .dropdown-label').html(labelHtml);
                if (vendorDt) vendorDt.draw();
            }

            $('#vendorAccountDropdownWrapper').on('change', '.account-checkbox', function() {
                var code = $(this).val();
                if ($(this).prop('checked')) {
                    selectedVendorAccounts.add(code);
                } else {
                    selectedVendorAccounts.delete(code);
                }
                updateVendorFilterState();
            });

            $('#vendorAccountDropdownWrapper').on('click', '.btn-select-all', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var menu = $(this).closest('.dropdown-menu');
                var visibleItems = menu.find('.account-check-item:not(.d-none) .account-checkbox');
                var totalItems = menu.find('.account-checkbox');

                if (visibleItems.length > 0 && visibleItems.length < totalItems.length) {
                    visibleItems.each(function() {
                        var val = $(this).val();
                        $(this).prop('checked', true);
                        selectedVendorAccounts.add(val);
                    });
                } else {
                    totalItems.prop('checked', true);
                    allVendorAccountCodes.forEach(function(c) {
                        selectedVendorAccounts.add(c);
                    });
                }
                updateVendorFilterState();
            });

            $('#vendorAccountDropdownWrapper').on('click', '.btn-deselect-all', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var menu = $(this).closest('.dropdown-menu');
                var visibleItems = menu.find('.account-check-item:not(.d-none) .account-checkbox');
                var totalItems = menu.find('.account-checkbox');

                if (visibleItems.length > 0 && visibleItems.length < totalItems.length) {
                    visibleItems.each(function() {
                        var val = $(this).val();
                        $(this).prop('checked', false);
                        selectedVendorAccounts.delete(val);
                    });
                } else {
                    totalItems.prop('checked', false);
                    selectedVendorAccounts.clear();
                }
                updateVendorFilterState();
            });

            // -------------------------------------------------------------
            // Independent Account Filter: Tab 2 (Bills Detail)
            // -------------------------------------------------------------
            function updateBillFilterState() {
                var total = allBillAccountCodes.length;
                var count = selectedBillAccounts.size;

                $('#billAccountDropdownWrapper .selected-count-num').text(count);

                var labelHtml = '';
                if (count === total && total > 0) {
                    labelHtml = 'ทุกหมวดบัญชี (ทั้งหมด)';
                } else if (count === 0) {
                    labelHtml = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle me-1"></i>ไม่ได้เลือกหมวดบัญชี</span>';
                } else if (count === 1) {
                    var singleCode = Array.from(selectedBillAccounts)[0];
                    var singleItem = $('#billAccountDropdownWrapper .account-checkbox[value="' + singleCode + '"]').first();
                    var singleName = singleItem.data('name') || '';
                    labelHtml = '<span class="fw-bold font-monospace">' + singleCode + '</span> - ' + singleName;
                } else {
                    labelHtml = 'เลือก <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 ms-1">' + count + '</span> หมวดบัญชี';
                }

                $('#billAccountDropdownBtn .dropdown-label').html(labelHtml);
                if (billsDt) billsDt.draw();
            }

            $('#billAccountDropdownWrapper').on('change', '.account-checkbox', function() {
                var code = $(this).val();
                if ($(this).prop('checked')) {
                    selectedBillAccounts.add(code);
                } else {
                    selectedBillAccounts.delete(code);
                }
                updateBillFilterState();
            });

            $('#billAccountDropdownWrapper').on('click', '.btn-select-all', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var menu = $(this).closest('.dropdown-menu');
                var visibleItems = menu.find('.account-check-item:not(.d-none) .account-checkbox');
                var totalItems = menu.find('.account-checkbox');

                if (visibleItems.length > 0 && visibleItems.length < totalItems.length) {
                    visibleItems.each(function() {
                        var val = $(this).val();
                        $(this).prop('checked', true);
                        selectedBillAccounts.add(val);
                    });
                } else {
                    totalItems.prop('checked', true);
                    allBillAccountCodes.forEach(function(c) {
                        selectedBillAccounts.add(c);
                    });
                }
                updateBillFilterState();
            });

            $('#billAccountDropdownWrapper').on('click', '.btn-deselect-all', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var menu = $(this).closest('.dropdown-menu');
                var visibleItems = menu.find('.account-check-item:not(.d-none) .account-checkbox');
                var totalItems = menu.find('.account-checkbox');

                if (visibleItems.length > 0 && visibleItems.length < totalItems.length) {
                    visibleItems.each(function() {
                        var val = $(this).val();
                        $(this).prop('checked', false);
                        selectedBillAccounts.delete(val);
                    });
                } else {
                    totalItems.prop('checked', false);
                    selectedBillAccounts.clear();
                }
                updateBillFilterState();
            });

            // Prevent dropdown from closing when interacting inside
            $(document).on('click', '.account-filter-dropdown .dropdown-menu', function(e) {
                e.stopPropagation();
            });

            // Live search in account dropdown (scoped to current dropdown)
            $(document).on('input', '.account-search-input', function() {
                var query = $(this).val().toLowerCase().trim();
                var menu = $(this).closest('.dropdown-menu');
                var items = menu.find('.account-check-item');
                var noFound = menu.find('.no-account-found');
                var visibleCount = 0;

                items.each(function() {
                    var searchStr = $(this).attr('data-search') || '';
                    if (!query || searchStr.indexOf(query) !== -1) {
                        $(this).removeClass('d-none').addClass('d-flex');
                        visibleCount++;
                    } else {
                        $(this).removeClass('d-flex').addClass('d-none');
                    }
                });

                if (visibleCount === 0) {
                    noFound.show();
                } else {
                    noFound.hide();
                }
            });

            // Status filter pill click handler for Tab 2
            $('.btn-bill-status').on('click', function() {
                $('.btn-bill-status').removeClass('active btn-secondary btn-danger btn-success text-white').addClass('text-dark');
                $(this).addClass('active');

                var status = $(this).data('status');
                if (status === 'all') {
                    $(this).removeClass('text-dark').addClass('btn-secondary text-white');
                    billsDt.column(9).search('').draw();
                } else if (status === 'unpaid') {
                    $(this).removeClass('text-dark').addClass('btn-danger text-white');
                    billsDt.column(9).search('ค้างจ่าย').draw();
                } else if (status === 'paid') {
                    $(this).removeClass('text-dark').addClass('btn-success text-white');
                    billsDt.column(9).search('ชำระครบ').draw();
                }
            });

            // 3. Handle Vendor Bills Modal ("ดูบิล" from Tab 1)
            $(document).on('click', '.btn-view-vendor-bills', function() {
                var btn = $(this);
                var vendorName = btn.data('vendor');
                var category = btn.data('category') || 'ทั่วไป';
                var total = btn.data('total') || 0;
                var unpaid = btn.data('unpaid') || 0;
                var rem = parseFloat(btn.data('remaining')) || 0;

                $('#mvModalVendorTitle').text(vendorName);
                $('#mvModalCategory').text(category);
                $('#mvModalTotal').text(Number(total).toLocaleString());
                $('#mvModalUnpaid').text(Number(unpaid).toLocaleString());
                $('#mvModalRemaining').text(rem.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

                $('#modalLoadingSpinner').show();
                $('#modalTableWrapper').hide();

                $('#vendorBillsModal').modal('show');

                // Fetch bills via AJAX
                $.ajax({
                    url: '{{ url("hosfin/ap_vendor_bills") }}',
                    type: 'GET',
                    data: { 
                        vendor: vendorName,
                        budget_year: '{{ $budgetYear }}'
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            if ($.fn.DataTable.isDataTable('#modalBillsTable')) {
                                $('#modalBillsTable').DataTable().destroy();
                            }

                            var rowsHtml = '';
                            var idx = 1;
                            res.bills.forEach(function(b) {
                                var isPaid = b.is_paid == 1;
                                var statusBadge = isPaid 
                                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">ชำระครบ</span>'
                                    : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">ค้างจ่าย</span>';
                                
                                var remClass = b.remaining_debt > 0.01 ? 'text-danger fw-bold fs-6' : 'text-muted';

                                var agingBadge = '<span class="badge bg-light text-muted border rounded-pill px-2 py-0.5">-</span>';
                                if (!isPaid && b.aging_days > 0) {
                                    var badgeClass = b.aging_days > 90 ? 'bg-danger-subtle text-danger border border-danger-subtle fw-bold' : (b.aging_days > 30 ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle');
                                    agingBadge = '<span class="badge ' + badgeClass + ' rounded-pill px-2 py-0.5">' + Number(b.aging_days).toLocaleString() + '</span>';
                                }

                                rowsHtml += '<tr>' +
                                    '<td class="ps-3 text-center text-muted fw-bold" data-order="' + idx + '">' + (idx++) + '</td>' +
                                    '<td class="fw-bold font-monospace text-primary">' + (b.bill_no || '-') + '</td>' +
                                    '<td class="text-center small text-nowrap" data-order="' + (b.parsed_bill_date || '') + '">' + (b.thai_bill_date || '-') + '</td>' +
                                    '<td class="text-center" data-order="' + (b.aging_days || 0) + '">' + agingBadge + '</td>' +
                                    '<td><small class="text-muted">' + (b.account_code || '') + '</small><br><span class="small text-dark">' + (b.account_name || '') + '</span></td>' +
                                    '<td class="text-end font-monospace" data-order="' + b.total_credit + '">' + Number(b.total_credit).toLocaleString('th-TH', {minimumFractionDigits: 2}) + '</td>' +
                                    '<td class="text-end font-monospace text-success" data-order="' + b.total_debit + '">' + Number(b.total_debit).toLocaleString('th-TH', {minimumFractionDigits: 2}) + '</td>' +
                                    '<td class="text-end font-monospace pe-3 ' + remClass + '" data-order="' + b.remaining_debt + '">' + Number(b.remaining_debt).toLocaleString('th-TH', {minimumFractionDigits: 2}) + '</td>' +
                                    '<td class="text-center" data-order="' + (isPaid ? 1 : 0) + '">' + statusBadge + '</td>' +
                                '</tr>';
                            });

                            $('#modalBillsTbody').html(rowsHtml);
                            $('#modalLoadingSpinner').hide();
                            $('#modalTableWrapper').show();

                            $('#modalBillsTable').DataTable({
                                dom: '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-2 border-bottom"<"d-flex align-items-center"l><"d-flex align-items-center gap-2"fB>>' +
                                     'rt' +
                                     '<"d-flex justify-content-between align-items-center flex-wrap gap-2 p-2 border-top"<"text-muted small"i><"pagination-sm"p>>',
                                buttons: [
                                    {
                                        extend: 'excelHtml5',
                                        text: '<i class="bi bi-file-earmark-excel-fill text-white fs-6"></i> ส่งออก Excel',
                                        className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white fw-bold',
                                        title: 'รายการบิล_' + vendorName + '_HosFin',
                                        exportOptions: { 
                                            columns: ':visible', 
                                            footer: true,
                                            format: {
                                                body: function (data, row, column, node) {
                                                    var text = $('<div>').html(data).text().trim();
                                                    // Column 3 is ค้างชำระ (วัน)
                                                    if (column === 3) {
                                                        if (text === '-' || text === '') return '';
                                                        var cleanNum = text.replace(/วัน/g, '').replace(/,/g, '').trim();
                                                        return isNaN(cleanNum) || cleanNum === '' ? '' : parseInt(cleanNum, 10);
                                                    }
                                                    return text;
                                                }
                                            }
                                        }
                                    }
                                ],
                                language: {
                                    search: "ค้นหาในบิล:",
                                    searchPlaceholder: "เลขที่บิล, หมวดบัญชี...",
                                    lengthMenu: "แสดง _MENU_ บิล",
                                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ บิล",
                                    infoEmpty: "ไม่พบรายการบิล",
                                    infoFiltered: "(กรองจากทั้งหมด _MAX_ บิล)",
                                    zeroRecords: "ไม่พบรายการบิลที่ตรงกับคำค้นหา",
                                    paginate: { previous: '<i class="bi bi-chevron-left"></i>', next: '<i class="bi bi-chevron-right"></i>' }
                                },
                                pageLength: 15,
                                lengthMenu: [[10, 15, 25, 50, -1], [10, 15, 25, 50, "ทั้งหมด"]],
                                order: [[7, 'desc']], // Column 7 is remaining debt
                                columnDefs: [{ targets: [0], orderable: false }],
                                autoWidth: false,
                                footerCallback: function (row, data, start, end, display) {
                                    var api = this.api();
                                    var totalCredit = api.column(5, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                                    var totalDebit = api.column(6, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                                    var totalRemaining = api.column(7, { search: 'applied' }).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                                    $('#mvFooterCredit').html(fmt(totalCredit));
                                    $('#mvFooterDebit').html(fmt(totalDebit));
                                    $('#mvFooterRemaining').html(fmt(totalRemaining));
                                }
                            });
                        }
                    },
                    error: function() {
                        $('#modalLoadingSpinner').html('<div class="text-danger py-4"><i class="bi bi-exclamation-triangle fs-3 d-block mb-2"></i>ไม่สามารถโหลดข้อมูลบิลได้ กรุณาลองใหม่อีกครั้ง</div>');
                    }
                });
            });

            // Adjust modal table columns when modal is fully visible
            $('#vendorBillsModal').on('shown.bs.modal', function () {
                if ($.fn.DataTable.isDataTable('#modalBillsTable')) {
                    $('#modalBillsTable').DataTable().columns.adjust().draw();
                }
            });

            // Re-adjust columns when tabs are toggled
            $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
                if ($.fn.dataTable.isDataTable('#vendorTable')) {
                    vendorDt.columns.adjust().draw();
                }
                if ($.fn.dataTable.isDataTable('#billsTable')) {
                    billsDt.columns.adjust().draw();
                }
            });
            // Tab state tracking
            window.currentTab = '{{ $activeTab }}';
            window.setTab = function(tab) {
                window.currentTab = tab;
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url.toString());
            };

            // Budget year change handler
            $('#select_budget_year').on('change', function() {
                var yr = $(this).val();
                window.location.href = "{{ url('hosfin/ap_report') }}?budget_year=" + yr + "&tab=" + (window.currentTab || 'vendor');
            });
        }
    });
</script>
@endsection
