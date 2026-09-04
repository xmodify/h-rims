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
                    <div>
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
                                <tr class="vendor-row {{ $hasDebt ? '' : 'table-light opacity-75' }}">
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

                <!-- Bills Table -->
                <div class="table-responsive p-2">
                    <table class="table table-hover align-middle mb-0 w-100" id="billsTable">
                        <thead class="table-light">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3 text-center" style="width: 50px;">#</th>
                                <th>เลขที่บิล (ApAr)</th>
                                <th class="text-center" style="width: 110px;">วันที่บิล</th>
                                <th class="text-center" style="width: 100px;">ค้างชำระ</th>
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
                                <tr>
                                    <td class="ps-3 text-center fw-bold text-muted" data-order="{{ $bIdx }}">{{ $bIdx++ }}</td>
                                    <td class="fw-bold font-monospace text-primary">{{ $b->bill_no }}</td>
                                    <td class="text-center small text-nowrap" data-order="{{ $b->parsed_bill_date }}">{{ $b->thai_bill_date }}</td>
                                    <td class="text-center" data-order="{{ $b->aging_days }}">
                                        @if($b->is_paid)
                                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0.5">-</span>
                                        @elseif($b->aging_days > 90)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 fw-bold">
                                                {{ number_format($b->aging_days) }} วัน
                                            </span>
                                        @elseif($b->aging_days > 30)
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-2 py-0.5">
                                                {{ number_format($b->aging_days) }} วัน
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                                {{ number_format($b->aging_days) }} วัน
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
                                    <th class="text-center" style="width: 100px;">ค้างชำระ</th>
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
                    <i class="bi bi-info-circle me-1"></i> รายการบิลอ้างอิงจากระบบ HosFin GL
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
                            footer: true
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
                                    agingBadge = '<span class="badge ' + badgeClass + ' rounded-pill px-2 py-0.5">' + Number(b.aging_days).toLocaleString() + ' วัน</span>';
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
                                        exportOptions: { columns: ':visible', footer: true }
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
