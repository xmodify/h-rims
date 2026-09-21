@extends('layouts.app')

@section('content')
<style>
    /* DataTables Excel Export Button Styling */
    div.dt-buttons .dt-button.buttons-excel,
    .dt-buttons button.buttons-excel,
    .dt-buttons .btn-export-excel,
    button.dt-button.buttons-excel {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
        background-color: #059669 !important;
        color: #ffffff !important;
        border: none !important;
        font-weight: 600 !important;
        border-radius: 50rem !important;
        padding: 0.35rem 1.1rem !important;
        font-size: 0.85rem !important;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25) !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
    }
    div.dt-buttons .dt-button.buttons-excel:hover,
    .dt-buttons button.buttons-excel:hover,
    .dt-buttons .btn-export-excel:hover,
    button.dt-button.buttons-excel:hover {
        background: linear-gradient(135deg, #047857 0%, #059669 100%) !important;
        background-color: #047857 !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.35) !important;
    }
    div.dt-buttons .dt-button.buttons-excel i,
    .dt-buttons button.buttons-excel i {
        color: #ffffff !important;
    }

    /* Action Dropdown Styling */
    .smart-action-dropdown .dropdown-menu {
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08) !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
        min-width: 220px;
        padding: 6px;
        z-index: 1055 !important;
    }
    .smart-action-dropdown .dropdown-item {
        font-size: 12px !important;
        padding: 6px 12px !important;
        border-radius: 8px !important;
        margin-bottom: 2px;
        transition: all 0.15s ease;
    }
    .smart-action-dropdown .dropdown-item:hover {
        background-color: #f1f5f9;
    }
    .smart-action-dropdown .dropdown-header {
        font-size: 10px !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px;
        padding: 4px 12px;
        color: #94a3b8;
    }
    #smartMoneyTable td {
        vertical-align: middle;
    }
</style>
<div class="container-fluid py-3 px-4">
    {{-- Top Breadcrumb / Title Bar --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box shadow-sm" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 14px; color: white;">
                <i class="bi bi-wallet2 fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">ระบบบริหารการเบิกจ่ายเงินกองทุนหลักประกันสุขภาพ (Smart Money Transfer)</h4>
                <div class="text-muted small">
                    <span>จัดการข้อมูลเงินโอน สปสช., ตรวจสอบยอดผังบัญชี และออกใบเสร็จรับเงิน</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 ms-2">
                        <i class="bi bi-shield-check me-1"></i> Auto-Sync to STM
                    </span>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- ThaiD Connection Status Button on Main Page --}}
            <button type="button" class="btn btn-sm rounded-pill px-3 shadow-sm fw-semibold d-inline-flex align-items-center gap-1.5 btn-outline-secondary" id="mainThaidStatusBtn" title="สถานะการเชื่อมต่อ ThaiD SSO สำหรับดึงข้อมูล สปสช. (คลิกเพื่อเข้าสู่ระบบ/เปลี่ยนบัญชี)">
                <span class="spinner-border spinner-border-sm text-secondary" id="mainThaidSpinner" style="width: 12px; height: 12px;"></span>
                <span id="mainThaidStatusText">ThaiD: ตรวจสอบสถานะ...</span>
            </button>

            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm fw-normal" id="btnOpenTrendChartModal" data-bs-toggle="modal" data-bs-target="#smtTrendChartModal" title="ดูกราฟแนวโน้มเงินโอน 12 เดือน และเปรียบเทียบรายกองทุน">
                <i class="bi bi-graph-up-arrow me-1"></i> กราฟแนวโน้ม 12 เดือน
            </button>

            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm fw-normal" id="btnSmartSyncStm" title="ส่งข้อมูลเลขที่ใบเสร็จจาก Smart Money ไปอัปเดตยัง Statement (STM ทุกสิทธิ์)">
                <i class="bi bi-send-check me-1"></i> ซิงก์ใบเสร็จไปยัง STM
            </button>

            @if($hasBotLicense)
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-normal" data-bs-toggle="modal" data-bs-target="#smtBotModal">
                    <i class="bi bi-robot me-1"></i> ดึงจาก SMTF
                </button>
            @endif
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">
        {{-- Card 1: Total Transfer --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-2.5 px-3" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold" style="font-size: 12px;">ยอดเงินโอนเข้าบัญชีรวม</span>
                    <div class="badge bg-primary-subtle text-primary rounded-circle p-1.5">
                        <i class="bi bi-cash-stack fs-6"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-1" style="color: #059669 !important; font-weight: 800; font-size: 1.55rem;">{{ number_format($total_net_amount, 2) }} <span class="fs-6 text-muted fw-normal">บาท</span></h4>
                <div class="text-muted small" style="font-size: 11.5px;">
                    <i class="bi bi-layers me-1 text-primary"></i> ทั้งหมด <strong>{{ number_format($total_count) }}</strong> รายการ/Batch
                </div>
            </div>
        </div>

        {{-- Card 2: Issued Receipts --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-2.5 px-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-success small fw-bold" style="font-size: 12px;">ออกใบเสร็จรับเงินแล้ว</span>
                    <div class="badge bg-success-subtle text-success rounded-circle p-1.5">
                        <i class="bi bi-check-circle-fill fs-6"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-success mb-1" style="font-weight: 800; font-size: 1.55rem;">{{ number_format($issued_amount, 2) }} <span class="fs-6 text-muted fw-normal">บาท</span></h4>
                <div class="text-success small" style="font-size: 11.5px;">
                    <i class="bi bi-file-earmark-check me-1"></i> ออกแล้ว <strong>{{ number_format($issued_count) }}</strong> รายการ
                </div>
            </div>
        </div>

        {{-- Card 3: Pending Receipts --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-2.5 px-3" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-warning small fw-bold" style="font-size: 12px;">ยังไม่ได้ออกใบเสร็จ</span>
                    <div class="badge bg-warning-subtle text-warning rounded-circle p-1.5">
                        <i class="bi bi-clock-history fs-6"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-warning mb-1" style="font-weight: 800; font-size: 1.55rem;">{{ number_format($pending_amount, 2) }} <span class="fs-6 text-muted fw-normal">บาท</span></h4>
                <div class="text-warning small" style="font-size: 11.5px;">
                    <i class="bi bi-hourglass-split me-1"></i> รอออกใบเสร็จ <strong>{{ number_format($pending_count) }}</strong> รายการ
                </div>
            </div>
        </div>

        {{-- Card 4: Quick Filter Form (ปีงบประมาณ + ช่วงวันที่ รวมแถวเดียวกัน) --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-2.5 px-3 bg-white">
                <form method="GET" action="{{ route('import.smart_money') }}" id="filterForm">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-dark" style="font-size: 11.5px;"><i class="bi bi-funnel-fill text-primary me-1"></i>เลือกช่วงข้อมูล</span>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-2.5 py-0.5 fw-semibold shadow-xs" style="font-size: 10.5px;">
                            <i class="bi bi-search me-0.5"></i> ค้นหา
                        </button>
                    </div>

                    {{-- แถวเดียวกัน: ปีงบประมาณ (ซ้าย) + ช่วงวันที่ (ขวา) --}}
                    <div class="row g-1 align-items-center">
                        <div class="col-5">
                            <select class="form-select form-select-sm rounded-3 shadow-none fw-semibold text-dark px-1.5 py-0.5" name="budget_year" id="budget_year_select" style="font-size: 11px; height: 28px;">
                                @foreach ($budget_year_select as $row)
                                    <option value="{{ $row->LEAVE_YEAR_ID }}" 
                                        data-begin="{{ $row->DATE_BEGIN }}" 
                                        data-end="{{ $row->DATE_END }}" 
                                        {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                        {{ $row->LEAVE_YEAR_NAME }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-7">
                            <div class="input-group input-group-sm">
                                <input type="hidden" name="start_date" id="filter_start_date" value="{{ $start_date }}">
                                <input type="text" class="form-control form-control-sm rounded-start-3 shadow-none datepicker_th font-monospace px-1 text-center py-0.5" 
                                    id="filter_start_date_picker" 
                                    placeholder="เริ่มต้น" 
                                    style="font-size: 10px; height: 28px; cursor: pointer;" readonly>
                                <span class="input-group-text bg-light text-muted px-1" style="font-size: 9.5px;">-</span>
                                <input type="hidden" name="end_date" id="filter_end_date" value="{{ $end_date }}">
                                <input type="text" class="form-control form-control-sm rounded-end-3 shadow-none datepicker_th font-monospace px-1 text-center py-0.5" 
                                    id="filter_end_date_picker" 
                                    placeholder="สิ้นสุด" 
                                    style="font-size: 10px; height: 28px; cursor: pointer;" readonly>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="receipt_status" id="receipt_status_input" value="{{ $receipt_status }}">
                    <input type="hidden" name="account_code" id="account_code_input" value="{{ $account_filter }}">
                </form>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        {{-- Card Header & Filter Tabs --}}
        <div class="card-header bg-white p-3 border-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                {{-- Status Pills & Active Date Range Display --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="d-flex align-items-center gap-1 bg-light p-1 rounded-pill">
                        <a href="{{ route('import.smart_money', array_merge(request()->query(), ['receipt_status' => 'all'])) }}"
                            class="btn btn-sm rounded-pill px-3 fw-semibold {{ $receipt_status === 'all' ? 'btn-primary shadow-sm' : 'btn-light text-muted' }}">
                            ทั้งหมด ({{ number_format($total_count) }})
                        </a>
                        <a href="{{ route('import.smart_money', array_merge(request()->query(), ['receipt_status' => 'pending'])) }}"
                            class="btn btn-sm rounded-pill px-3 fw-semibold {{ $receipt_status === 'pending' ? 'btn-warning text-dark shadow-sm' : 'btn-light text-muted' }}">
                            🔴 ยังไม่ออกใบเสร็จ ({{ number_format($pending_count) }})
                        </a>
                        <a href="{{ route('import.smart_money', array_merge(request()->query(), ['receipt_status' => 'issued'])) }}"
                            class="btn btn-sm rounded-pill px-3 fw-semibold {{ $receipt_status === 'issued' ? 'btn-success text-white shadow-sm' : 'btn-light text-muted' }}">
                            🟢 ออกใบเสร็จแล้ว ({{ number_format($issued_count) }})
                        </a>
                    </div>

                    {{-- แสดงวันที่ที่เลือกหลัง tab ออกใบเสร็จแล้ว (ข้อความเรียบง่าย ไม่มีพื้นหลัง) --}}
                    <div class="d-inline-flex align-items-center ms-lg-3 text-secondary" style="font-size: 13.5px;">
                        <i class="bi bi-calendar2-range text-success fs-6 me-2"></i>
                        <span class="text-muted me-1.5">ช่วงวันที่ :</span>
                        <strong class="text-dark">{{ !empty($start_date) ? DateThai($start_date) : '-' }}</strong>
                        <span class="text-muted mx-2">ถึง</span>
                        <strong class="text-dark">{{ !empty($end_date) ? DateThai($end_date) : '-' }}</strong>
                    </div>
                </div>

                {{-- Account Code Filter & Search --}}
                <div class="d-flex align-items-center gap-2">
                    @if($account_codes->isNotEmpty())
                        <select class="form-select form-select-sm rounded-pill shadow-none" style="min-width: 180px;" onchange="filterAccountCode(this.value)">
                            <option value="">-- ทุกผังบัญชี --</option>
                            @foreach($account_codes as $code)
                                <option value="{{ $code }}" {{ $account_filter === $code ? 'selected' : '' }}>
                                    {{ $code }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table --}}
        {{-- Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smartMoneyTable" style="width: 100%;">
                    <thead class="table-light">
                        <tr class="text-nowrap small text-muted">
                            <th class="text-center" style="width: 3%;">ลำดับ</th>
                            <th class="text-center" style="width: 8%;">วันที่โอน</th>
                            <th class="text-center" style="width: 7%;">BatchNo.</th>
                            <th class="text-start" style="width: 12%;">งวด/เลขที่เบิกจ่าย</th>
                            <th class="text-start" style="width: 10%;">รหัสผังบัญชี</th>
                            <th class="text-start" style="width: 20%;">กองทุน / กองทุนย่อย</th>
                            <th class="text-end" style="width: 8%;">จำนวนเงิน</th>
                            <th class="text-end" style="width: 7%;">รายการหัก</th>
                            <th class="text-end" style="width: 8%;">เงินโอนเข้าบัญชี</th>
                            <th class="text-center" style="width: 7%;">เลขที่ใบเสร็จ</th>
                            <th class="text-center" style="width: 6%;">วันที่ออก</th>
                            <th class="text-center" style="width: 5%;">ผู้ออก</th>
                            <th class="text-center" style="width: 8%;">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $index => $row)
                        @php
                            $deductTotal = (float)$row->total_deduct_amount;
                            $offsetTotal = (float)$row->total_offset_amount;
                        @endphp
                        <tr id="row-batch-{{ $row->batch_no }}">
                            <td class="text-center small text-muted" data-order="{{ $index + 1 }}">{{ $index + 1 }}</td>
                            <td class="text-center small text-nowrap" data-order="{{ $row->transfer_date ? $row->transfer_date : '' }}">
                                {{ !empty($row->transfer_date) ? DateThai($row->transfer_date) : '-' }}
                            </td>
                            <td class="text-center" data-order="{{ $row->batch_no }}">
                                <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 fw-bold font-monospace">{{ $row->batch_no }}</span>
                            </td>
                            <td class="text-start small" data-order="{{ implode(',', $row->round_nos) }}">
                                @if(count($row->round_nos) === 1)
                                    <span class="text-primary">{{ $row->round_nos[0] }}</span>
                                @elseif(count($row->round_nos) > 1)
                                    <span class="text-primary">{{ count($row->round_nos) }} งวด</span>
                                    <div class="text-muted small text-truncate" style="max-width: 140px;" title="{{ implode(', ', $row->round_nos) }}">
                                        {{ implode(', ', $row->round_nos) }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-start small" data-order="{{ implode(',', $row->account_codes) }}">
                                @if(count($row->account_codes) === 1)
                                    <span class="badge bg-light text-dark border font-monospace">{{ $row->account_codes[0] }}</span>
                                @elseif(count($row->account_codes) > 1)
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace" title="{{ implode(', ', $row->account_codes) }}">
                                        {{ count($row->account_codes) }} ผังบัญชี
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-start small" data-order="{{ $row->fund_mains[0] ?? '' }}">
                                @if($row->items->count() > 1)
                                    <div class="d-flex align-items-center gap-1.5 mb-1">
                                        <button type="button"
                                            class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 btn-toggle-subrows shadow-xs flex-shrink-0"
                                            data-batch="{{ $row->batch_no }}"
                                            data-count="{{ $row->items->count() }}"
                                            title="คลิกเพื่อเปิด/ปิดดูรายละเอียดทั้ง {{ $row->items->count() }} ผังย่อย">
                                            <i class="bi bi-diagram-3-fill me-1 text-primary"></i> {{ $row->items->count() }} ผังย่อย
                                        </button>
                                        <span class="fw-semibold text-dark text-truncate" style="max-width: 150px;" title="{{ count($row->fund_mains) > 1 ? implode(', ', $row->fund_mains) : ($row->fund_mains[0] ?? '-') }}">
                                            {{ count($row->fund_mains) > 1 ? 'หลายกองทุน (' . count($row->fund_mains) . ')' : ($row->fund_mains[0] ?? '-') }}
                                        </span>
                                    </div>
                                    <div class="text-muted small text-truncate" style="max-width: 220px;" title="{{ implode(', ', $row->fund_subs) }}">
                                        {{ count($row->fund_subs) > 1 ? count($row->fund_subs) . ' กองทุนย่อย' : ($row->fund_subs[0] ?? '-') }}
                                    </div>
                                @else
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="{{ $row->fund_mains[0] ?? '' }}">{{ $row->fund_mains[0] ?? '-' }}</div>
                                    <div class="text-muted small text-truncate" style="max-width: 220px;" title="{{ $row->fund_subs[0] ?? '' }}">{{ $row->fund_subs[0] ?? '-' }}</div>
                                @endif
                            </td>
                            <td class="text-end small text-muted" data-order="{{ (float)$row->total_amount }}">
                                {{ number_format($row->total_amount, 2) }}
                            </td>
                            <td class="text-end small" data-order="{{ $deductTotal }}">
                                @if($deductTotal > 0)
                                    <span class="text-danger fw-semibold font-monospace" title="รายการหักจากยอดโอนเงิน">-{{ number_format($deductTotal, 2) }}</span>
                                @elseif($offsetTotal > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border fw-normal" title="มีจำนวนเงินรอหักกลบ {{ number_format($offsetTotal, 2) }} บาท (ยังไม่ได้หักในงวดนี้)" style="font-size: 10px;">
                                        รอหัก {{ number_format($offsetTotal, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success" data-order="{{ (float)$row->total_net_amount }}">
                                {{ number_format($row->total_net_amount, 2) }}
                            </td>
                            <td class="text-center" data-order="{{ $row->receive_no ?: '0' }}">
                                @if(!empty($row->receive_no))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-0.5"></i> {{ $row->receive_no }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-clock-history me-0.5"></i> ยังไม่ออก
                                    </span>
                                @endif
                            </td>
                            <td class="text-center small text-nowrap" data-order="{{ $row->receipt_date ? $row->receipt_date : '' }}">
                                {{ !empty($row->receive_no) && !empty($row->receipt_date) ? DateThai($row->receipt_date) : '-' }}
                            </td>
                            <td class="text-center small text-muted text-truncate" style="max-width: 110px;" title="{{ !empty($row->receive_no) && !empty($row->receipt_by) ? $row->receipt_by : '-' }}" data-order="{{ $row->receipt_by ?? '' }}">
                                {{ !empty($row->receive_no) && !empty($row->receipt_by) ? $row->receipt_by : '-' }}
                            </td>
                            <td class="text-center text-nowrap py-1.5">
                                <div class="dropdown smart-action-dropdown">
                                    <button class="btn btn-xs btn-outline-primary dropdown-toggle rounded-pill px-2.5 py-1 shadow-xs fw-semibold"
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            data-bs-auto-close="true"
                                            aria-expanded="false" 
                                            style="font-size: 11.5px;">
                                        <i class="bi bi-gear-fill me-1"></i> ทำรายการ
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 border-0 py-1.5">
                                        {{-- 1. ออกใบเสร็จรับเงิน / แก้ไขเลขที่ใบเสร็จ --}}
                                        @if(Auth::user()->status == 'admin' || Auth::user()->allow_receipt == 'Y')
                                            <li>
                                                <a href="javascript:void(0);" 
                                                   class="dropdown-item d-flex align-items-center {{ $row->receive_no ? 'btn-edit-receipt text-warning' : 'btn-new-receipt text-success' }}"
                                                   data-batch="{{ $row->batch_no }}"
                                                   data-round="{{ implode(', ', $row->round_nos) }}"
                                                   data-receive="{{ $row->receive_no }}"
                                                   data-date="{{ $row->receipt_date }}"
                                                   data-net="{{ number_format($row->total_net_amount, 2) }}"
                                                   data-account="{{ implode(', ', $row->account_codes) }}">
                                                    <i class="bi {{ $row->receive_no ? 'bi-pencil-square text-warning' : 'bi-receipt text-success' }} me-2 fs-6"></i>
                                                    <span class="fw-semibold">{{ $row->receive_no ? 'แก้ไขเลขที่ใบเสร็จ' : 'ออกใบเสร็จรับเงิน' }}</span>
                                                </a>
                                            </li>
                                        @endif

                                        {{-- 2. รายละเอียดการโอนเงิน (รายบุคคล) --}}
                                        <li>
                                            <a href="javascript:void(0);" 
                                               class="dropdown-item d-flex align-items-center text-primary btn-view-patient-detail"
                                               data-batch="{{ $row->batch_no }}"
                                               data-subfund="">
                                                <i class="bi bi-person-lines-fill text-primary me-2 fs-6"></i>
                                                <span>รายละเอียดการโอนเงิน (รายคน)</span>
                                            </a>
                                        </li>

                                        {{-- 3. ดาวน์โหลดหนังสือ (แสดงเฉพาะรายการที่มีเอกสารจริงจาก สปสช.) --}}
                                        @php
                                            $hasPaym = !empty($row->file_name) && !str_starts_with($row->file_name, 'NHSO_SMT_') && !str_ends_with(strtolower($row->file_name), '.xlsx');
                                            $hasWait = !empty($row->file_name_wait);
                                            $hasDebt = !empty($row->file_name_debt);
                                        @endphp
                                        @if($hasPaym || $hasWait || $hasDebt)
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li class="dropdown-header">
                                                <i class="bi bi-file-earmark-arrow-down me-1"></i> ดาวน์โหลดหนังสือ (สปสช.)
                                            </li>
                                            
                                            {{-- ใบแจ้งโอนเงิน (PAYM) --}}
                                            @if($hasPaym)
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center text-danger" 
                                                       href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=PAYM" 
                                                       target="_blank">
                                                        <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-6"></i>
                                                        <span>ใบแจ้งโอนเงิน</span>
                                                    </a>
                                                </li>
                                            @endif

                                            {{-- ใบแจ้งชะลอโอนเงิน (WAIT) --}}
                                            @if($hasWait)
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center text-warning" 
                                                       href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=WAIT" 
                                                       target="_blank">
                                                        <i class="bi bi-pause-circle-fill text-warning me-2 fs-6"></i>
                                                        <span class="text-dark">ใบแจ้งชะลอการโอนเงิน</span>
                                                    </a>
                                                </li>
                                            @endif

                                            {{-- ใบแจ้งจำนวนเงินรอหักกลบ (DEBT) --}}
                                            @if($hasDebt)
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center" 
                                                       style="color: #6d28d9;"
                                                       href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=DEBT" 
                                                       target="_blank">
                                                        <i class="bi bi-receipt-cutoff me-2 fs-6" style="color: #6d28d9;"></i>
                                                        <span>ใบแจ้งจำนวนเงินรอหักกลบ</span>
                                                    </a>
                                                </li>
                                            @endif
                                        @endif

                                        {{-- 4. Delete Batch (Admin Only) --}}
                                        @if(Auth::user()->status == 'admin')
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <a href="javascript:void(0);" 
                                                   class="dropdown-item d-flex align-items-center text-danger btn-delete-batch"
                                                   data-batch="{{ $row->batch_no }}">
                                                    <i class="bi bi-trash3-fill text-danger me-2 fs-6"></i>
                                                    <span>ลบข้อมูล Batch นี้</span>
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
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

{{-- Hidden Subrow Templates for DataTables Child Rows --}}
<div id="subrow-templates" class="d-none">
    @foreach($batches as $row)
    <div id="subtable-{{ $row->batch_no }}">
        <div class="p-3 bg-light-subtle border-top border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1">
                        <i class="bi bi-diagram-3-fill me-1"></i> รายละเอียดตามผังบัญชี
                    </span>
                    <span class="fw-bold text-dark">Batch No. <span class="text-primary font-monospace">{{ $row->batch_no }}</span></span>
                    <span class="badge bg-secondary-subtle text-secondary border">ทั้งหมด {{ $row->items->count() }} รายการ</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @php
                        $subHasPaym = !empty($row->file_name) && !str_starts_with($row->file_name, 'NHSO_SMT_') && !str_ends_with(strtolower($row->file_name), '.xlsx');
                        $subHasWait = !empty($row->file_name_wait);
                        $subHasDebt = !empty($row->file_name_debt);
                    @endphp
                    @if($subHasPaym)
                        <a href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=PAYM"
                            target="_blank"
                            class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-0.5 shadow-xs"
                            title="เปิดดู/พิมพ์ ใบแจ้งโอนเงิน สปสช. (PAYM Voucher PDF)">
                            <i class="bi bi-file-earmark-pdf-fill me-1"></i> ใบแจ้งโอน (PDF)
                        </a>
                    @endif
                    @if($subHasWait)
                        <a href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=WAIT"
                            target="_blank"
                            class="btn btn-sm btn-outline-warning rounded-pill px-2.5 py-0.5 shadow-xs text-dark"
                            title="เปิดดู/พิมพ์ ใบแจ้งชะลอโอนเงิน (WAIT Voucher PDF)">
                            <i class="bi bi-pause-circle-fill me-1 text-warning"></i> ชะลอโอน (PDF)
                        </a>
                    @endif
                    @if($subHasDebt)
                        <a href="{{ route('import.smart_money.download_paym', $row->batch_no) }}?type=DEBT"
                            target="_blank"
                            class="btn btn-sm rounded-pill px-2.5 py-0.5 shadow-xs"
                            style="color: #6d28d9; background-color: #ede9fe; border: 1px solid #ddd6fe;"
                            title="เปิดดู/พิมพ์ ใบแจ้งจำนวนเงินรอหักกลบ (DEBT Voucher PDF)">
                            <i class="bi bi-receipt-cutoff me-1" style="color: #6d28d9;"></i> รอหักกลบ (PDF)
                        </a>
                    @endif
                    <div class="text-muted small">
                        <span>ยอดเงินโอนเข้าบัญชีสุทธิรวม: </span>
                        <strong class="text-success fs-6">{{ number_format($row->total_net_amount, 2) }}</strong> บาท
                    </div>
                </div>
            </div>
            <div class="table-responsive bg-white rounded-3 border shadow-sm">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12.5px;">
                    <thead class="table-light text-muted small">
                        <tr class="text-nowrap">
                            <th class="text-center" width="4%">#</th>
                            <th class="text-start" width="16%">งวด / เลขที่เบิกจ่าย</th>
                            <th class="text-start" width="13%">รหัสผังบัญชี</th>
                            <th class="text-start" width="18%">กองทุน</th>
                            <th class="text-start" width="19%">กองทุนย่อย</th>
                            <th class="text-end" width="10%">จำนวนเงิน</th>
                            <th class="text-end" width="9%">รายการหัก</th>
                            <th class="text-end" width="11%">เงินโอนเข้าบัญชี</th>
                            <th class="text-center" width="10%">เลขที่ใบเสร็จ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($row->items as $subIdx => $sub)
                        <tr>
                            <td class="text-center text-muted small">{{ $subIdx + 1 }}</td>
                            <td class="text-start fw-bold text-primary font-monospace">{{ $sub->round_no ?: '-' }}</td>
                            <td class="text-start"><span class="badge bg-light text-dark border font-monospace">{{ $sub->account_code }}</span></td>
                            <td class="text-start text-dark text-truncate" style="max-width: 170px;" title="{{ $sub->fund_main }}">{{ $sub->fund_main }}</td>
                            <td class="text-start text-muted text-truncate" style="max-width: 200px;" title="{{ $sub->fund_sub }}">{{ $sub->fund_sub }}</td>
                            <td class="text-end fw-semibold text-dark">{{ number_format($sub->amount, 2) }}</td>
                            <td class="text-end">
                                @if($sub->deduct_amount > 0)
                                    <span class="text-danger fw-semibold font-monospace">-{{ number_format($sub->deduct_amount, 2) }}</span>
                                @elseif($sub->offset_amount > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border fw-normal" title="มีจำนวนเงินรอหักกลบ {{ number_format($sub->offset_amount, 2) }} บาท" style="font-size: 9.5px;">
                                        รอหัก {{ number_format($sub->offset_amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted opacity-50">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success">{{ number_format($sub->net_amount, 2) }}</td>
                            <td class="text-center">
                                @if(!empty($sub->receive_no))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-0.5"></i> {{ $sub->receive_no }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5" style="font-size: 11px;">
                                        ยังไม่ออก
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end text-muted small pe-3">
                                รวมยอดทั้งหมดใน Batch No. <span class="font-monospace text-dark">{{ $row->batch_no }}</span> :
                            </td>
                            <td class="text-end text-dark">{{ number_format($row->total_amount, 2) }}</td>
                            <td class="text-end text-danger">
                                @if($row->total_deduct_amount > 0)
                                    -{{ number_format($row->total_deduct_amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end text-success">{{ number_format($row->total_net_amount, 2) }}</td>
                            <td class="text-center small text-muted">บาท</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Modal 1: ออกใบเสร็จรับเงิน --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-3 px-4" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 10px;">
                        <i class="bi bi-receipt fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="receiptModalTitle">ออกใบเสร็จรับเงิน</h5>
                        <div class="text-white-50 small">บันทึกเลขที่ใบเสร็จและซิงก์เข้า Statement (STM)</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="modal_batch_no">
                <input type="hidden" id="modal_round_no">

                <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Batch No:</span>
                        <span class="fw-bold text-dark font-monospace" id="display_batch_no">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">งวด/เลขที่เบิกจ่าย:</span>
                        <span class="fw-bold text-primary" id="display_round_no">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">รหัสผังบัญชี:</span>
                        <span class="fw-bold text-secondary font-monospace" id="display_account">-</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="text-muted small">ยอดเงินโอนเข้าบัญชี:</span>
                        <span class="fw-bold text-success fs-5" id="display_net_amount">0.00 บาท</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark small mb-1">
                        <i class="bi bi-hash text-primary me-1"></i> เลขที่ใบเสร็จรับเงิน <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control form-control-lg rounded-3 shadow-none border-primary" id="receive_no" placeholder="เช่น 75/14 หรือ 58/99" autofocus>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-dark small mb-1">
                        <i class="bi bi-calendar-event text-primary me-1"></i> วันที่ออกใบเสร็จ <span class="text-danger">*</span>
                    </label>
                    <input type="hidden" id="receipt_date" name="receipt_date">
                    <input type="text" class="form-control rounded-3 shadow-none datepicker_th" id="receipt_date_display" placeholder="เลือกวันที่..." readonly>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-white">
                <button type="button" class="btn btn-light px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm" id="btnSaveReceipt">
                    <i class="bi bi-check-circle-fill me-1"></i> บันทึกใบเสร็จ
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal 4: รายละเอียดผู้ป่วยรายบุคคล (Patient Details Modal) --}}
<div class="modal fade" id="patientDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable" style="max-width: 96vw; width: 1360px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            {{-- Modal Header --}}
            <div class="modal-header text-white p-3 px-4" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 12px;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0 text-white">รายละเอียดผู้ป่วยรายบุคคล</h5>
                            <span class="badge bg-white text-dark font-monospace px-2 py-0.5" id="pmodal_batch_badge">Batch -</span>
                        </div>
                        <div class="text-white-50 small mt-0.5" id="pmodal_batch_subtitle">ข้อมูลการขอเบิกชดเชยค่ารักษาพยาบาลรายบุคคล</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body p-3 p-md-4 bg-light">
                {{-- Section 1: ThaiD Session Connection Status (Top Card) --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div id="pmodalAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="pmodalAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ ThaiD</div>
                                    <div class="text-muted small" id="pmodalAuthStatusSub">สแกน QR Code ด้วยแอป ThaiD เพื่อเข้าสู่ระบบก่อนดึงข้อมูลรายคน</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" id="pmodalBtnThaidLogin">
                                    <i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm fw-semibold d-none" id="pmodalBtnLogout">
                                    <i class="bi bi-box-arrow-right me-1"></i> ตัดการเชื่อมต่อ
                                </button>
                                <a href="https://smt.nhso.go.th/smtf/#/home/budget/summary" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm" title="เปิดหน้าเว็บ Smart Money Transfer ในแท็บใหม่">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดเว็บ Smart Money Transfer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Batch Summary Info Banner --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <span class="text-muted small">วันที่โอน:</span>
                                <div class="fw-bold text-dark" id="pmodal_transfer_date">-</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small">งวด / เลขที่เบิกจ่าย:</span>
                                <div class="fw-bold text-primary text-truncate" id="pmodal_round_no">-</div>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small">รหัสผังบัญชี:</span>
                                <div class="fw-bold text-secondary font-monospace text-truncate" id="pmodal_account_code">-</div>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="text-muted small" id="pmodal_amount_title">ยอดรายการที่แสดง:</span>
                                <div class="fw-bold text-success fs-5" id="pmodal_net_amount">0.00 บาท</div>
                                <div class="text-muted small" id="pmodal_batch_total_hint" style="font-size: 11px;">(ยอดรวมทั้ง Batch: 0.00 บาท)</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar: Search, Sub-fund Filter, Stats & Actions --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 600px;">
                                <div class="input-group input-group-sm flex-grow-1">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-start-0 shadow-none" id="pmodal_search_input" placeholder="ค้นหา HN, AN, เลขบัตร, ชื่อผู้ป่วย, REP_NO, กองทุน...">
                                </div>
                                <select class="form-select form-select-sm rounded-pill shadow-none" id="pmodal_subfund_filter" style="min-width: 180px; max-width: 260px; font-size: 12px;">
                                    <option value="">-- ทุกผังย่อยใน Batch --</option>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 text-nowrap" id="pmodal_btn_search">ค้นหา</button>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-light text-dark border px-2.5 py-1.5" id="pmodal_stats_badge">
                                    <i class="bi bi-people me-1 text-primary"></i> 0 รายการ (0.00 บาท)
                                </span>

                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" id="pmodal_btn_sync_smt" title="ดึงข้อมูลผู้ป่วยรายบุคคลจาก สปสช. (SMT) อัตโนมัติ">
                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> ดึงรายคนจาก SMT
                                </button>

                                <label for="pmodal_upload_file" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm fw-semibold mb-0" style="cursor: pointer;" title="นำเข้าไฟล์ Excel รายบุคคลสำหรับ Batch นี้">
                                    <i class="bi bi-upload me-1"></i> นำเข้าไฟล์ Excel
                                    <input type="file" id="pmodal_upload_file" class="d-none" accept=".xlsx, .xls, .csv">
                                </label>

                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-semibold" id="pmodal_btn_export">
                                    <i class="bi bi-file-earmark-excel-fill me-1"></i> ส่งออก Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Patient Table --}}
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="table-responsive" style="max-height: 480px;">
                        <table class="table table-hover table-bordered align-middle mb-0 small" id="pmodal_table" style="width: 100%;">
                            <thead class="table-light sticky-top">
                                <tr class="text-muted small text-nowrap">
                                    <th class="text-center" style="width: 40px;">ลำดับ</th>
                                    <th class="text-center" style="width: 90px;">วันที่โอน</th>
                                    <th class="text-center" style="width: 85px;">HN</th>
                                    <th class="text-center" style="width: 95px;">AN / SEQ</th>
                                    <th class="text-center" style="width: 80px;">ประเภท</th>
                                    <th class="text-center" style="width: 110px;">เลขบัตรประชาชน</th>
                                    <th class="text-start" style="min-width: 140px;">ชื่อ-สกุล</th>
                                    <th class="text-center" style="width: 90px;">วันที่รับบริการ</th>
                                    <th class="text-end" style="width: 100px;">ยอดชดเชยสุทธิ</th>
                                    <th class="text-center" style="width: 90px;">REP_NO</th>
                                    <th class="text-start" style="min-width: 150px;">กองทุนย่อย</th>
                                </tr>
                            </thead>
                            <tbody id="pmodal_table_body">
                                <tr>
                                    <td colspan="11" class="text-center py-5 text-muted">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="mt-2 fw-semibold">กำลังโหลดข้อมูล...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Table Pagination --}}
                    <div class="card-footer bg-white p-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="text-muted small" id="pmodal_page_info">แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ</div>
                        <div class="d-flex align-items-center gap-1" id="pmodal_pagination_controls"></div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer p-3 bg-white border-top d-flex justify-content-between">
                <a href="#" id="pmodal_fullpage_link" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดดูหน้าต่างเต็ม
                </a>
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal 3: ดึงข้อมูลจาก สปสช. (Smart Money Transfer) --}}
<div class="modal fade" id="smtBotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 96vw; width: 1320px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #059669, #10b981); border-radius: 14px; color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.4);">
                        <i class="bi bi-cloud-arrow-down-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="smtBotModalLabel">
                            ดึงข้อมูลเงินโอน Smart Money Transfer จาก สปสช.
                        </h5>
                        <div class="text-light-50 small mt-1 d-flex flex-wrap align-items-center gap-2">
                            <span>เชื่อมต่อตรง smt.nhso.go.th</span>
                            <span class="badge rounded-pill bg-light bg-opacity-25 text-white py-1 px-2.5 fw-medium border border-white-50" style="font-size: 11px;">
                                <i class="bi bi-hospital me-1 text-info"></i> รหัสหน่วยบริการ: <strong>{{ DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989' }}</strong>
                            </span>
                            <a href="https://smt.nhso.go.th/smtf/#/home/budget/summary" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-2.5 py-0.5 text-decoration-none shadow-none" style="font-size: 11px;" title="เปิดหน้าเว็บ Smart Money Transfer ในแท็บใหม่">
                                <i class="bi bi-box-arrow-up-right me-1"></i> เว็บไซต์ SMT
                            </a>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Search & Filter Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="bi bi-calendar-event text-primary me-1"></i> ตั้งแต่วันที่
                                </label>
                                <input type="hidden" id="bot_start_date" name="bot_start_date">
                                <input type="text" class="form-control form-control-sm rounded-3 datepicker_th" id="bot_start_date_picker" placeholder="วว/ดด/ปปปป" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="bi bi-calendar-event text-primary me-1"></i> ถึงวันที่
                                </label>
                                <input type="hidden" id="bot_end_date" name="bot_end_date">
                                <input type="text" class="form-control form-control-sm rounded-3 datepicker_th" id="bot_end_date_picker" placeholder="วว/ดด/ปปปป" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">เลข Batch / งวด / ผังบัญชี (ระบุหรือไม่ก็ได้)</label>
                                <input type="text" class="form-control form-control-sm rounded-3" id="botSmtKeywordFilter" placeholder="เช่น 3271, 6908_IP, 1102...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end pt-3">
                                <button class="btn btn-primary btn-sm w-100 rounded-3 py-1.5 fw-bold shadow-sm" type="button" id="btnBotSmtSearch">
                                    <i class="bi bi-search me-1"></i> ค้นหาใน Smart Money Transfer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Smart Money Results List Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-list-task text-success"></i> รายการเงินโอน Smart Money Transfer ที่พบใน สปสช. (เปรียบเทียบกับฐานข้อมูล RiMS)
                        </div>
                        <span class="badge bg-light text-dark border px-2.5 py-1" id="botSmtCountBadge">พบ 0 รายการ</span>
                    </div>
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover table-bordered align-middle mb-0 small" id="botSmtTable" style="width: 100%;">
                            <thead class="table-light sticky-top">
                                <tr class="text-muted small">
                                    <th class="text-center" style="width: 38px;">
                                        <input class="form-check-input" type="checkbox" id="checkAllBotSmt">
                                    </th>
                                    <th class="text-center text-nowrap" style="width: 95px;">วันที่โอน</th>
                                    <th class="text-center text-nowrap" style="width: 85px;">BatchNo.</th>
                                    <th class="text-center text-nowrap" style="width: 135px;">งวด / เลขที่เบิกจ่าย</th>
                                    <th class="text-center text-nowrap" style="width: 120px;">รหัสผังบัญชี</th>
                                    <th class="text-start">กองทุน / กองทุนย่อย</th>
                                    <th class="text-end text-nowrap" style="width: 130px;">เงินโอนเข้าบัญชี (บาท)</th>
                                    <th class="text-center text-nowrap" style="width: 130px;">ดาวน์โหลดหนังสือ</th>
                                    <th class="text-center text-nowrap" style="width: 160px;">สถานะใน RiMS</th>
                                </tr>
                            </thead>
                            <tbody id="botSmtTableBody">
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>
                                        กดปุ่ม "ค้นหาใน Smart Money Transfer" เพื่อดึงรายการเงินโอน
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer p-3 bg-white border-top d-flex justify-content-between">
                <div class="small fw-bold text-primary" id="selectedBotSmtCount">เลือก 0 รายการ</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold shadow-sm" id="btnStartImportBotSmt" disabled>
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มนำเข้า RiMS ทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal 5: กราฟแนวโน้มเงินโอน 12 เดือน (Smart Money 12-Month Trends Chart) --}}
<div class="modal fade" id="smtTrendChartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-centered" style="max-width: 95vw; width: 1420px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            {{-- Modal Header: Dark Indigo / Violet Gradient --}}
            <div class="modal-header p-3 px-4 text-white d-flex align-items-center justify-content-between flex-wrap gap-2" 
                 style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" 
                         style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px);">
                        <i class="bi bi-graph-up-arrow fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="smtTrendModalTitle" style="font-size: 1.15rem; letter-spacing: -0.01em;">
                                📈 กราฟแนวโน้มเงินโอน 12 เดือน: ระบบ Smart Money
                            </h5>
                            <div class="d-inline-flex align-items-center gap-1">
                                <select class="form-select form-select-sm rounded-pill fw-bold bg-warning text-dark border-0 py-0.5 ps-2.5 pe-4 shadow-xs" 
                                        id="trendSelectBudgetYear" onchange="loadTrendChartData(this.value)" 
                                        style="font-size: 0.78rem; cursor: pointer; width: auto;">
                                    @foreach($budget_year_select as $by)
                                        <option value="{{ $by->LEAVE_YEAR_ID }}" {{ $by->LEAVE_YEAR_ID == $budget_year ? 'selected' : '' }}>
                                            ปีงบ {{ $by->LEAVE_YEAR_ID }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="small text-white-50" style="font-size: 0.75rem;">
                            เปรียบเทียบแนวโน้มยอดเงินโอนจริงรายเดือน 12 งวด (ต.ค. – ก.ย.)
                        </div>
                    </div>
                </div>

                {{-- Mode Switch Buttons (หมวดหลัก vs กองทุน/รายการย่อย) --}}
                <div class="d-flex align-items-center gap-2 ms-auto me-2">
                    <div class="p-1 rounded-pill d-inline-flex shadow-sm" style="background: rgba(15, 23, 42, 0.45); border: 1px solid rgba(255, 255, 255, 0.2);">
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" 
                                id="btnTrendModeSub" onclick="switchTrendChartMode('sub')"
                                style="font-size: 0.78rem; background-color: #10b981; color: #ffffff;">
                            <i class="bi bi-list-nested me-1"></i> รายการกองทุน (<span id="countSubFunds">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-nowrap" 
                                id="btnTrendModeMain" onclick="switchTrendChartMode('main')"
                                style="font-size: 0.78rem; background-color: transparent; color: #cbd5e1;">
                            <i class="bi bi-collection me-1"></i> กองทุนหลัก (<span id="countMainFunds">0</span>)
                        </button>
                    </div>
                </div>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- Modal Body: 2-Column Responsive Layout (Fixed Sidebar + Fluid Chart) --}}
            <div class="modal-body p-0 d-flex flex-row" style="background-color: #f8fafc; height: calc(88vh - 120px); min-height: 560px; max-height: 820px; overflow: hidden;">
                {{-- LEFT COLUMN: Sidebar (Search & Checklist) --}}
                <div class="bg-white border-end d-flex flex-column" style="width: 380px; min-width: 380px; max-width: 380px; flex-shrink: 0; height: 100%; overflow: hidden;">
                    <div class="p-3 border-bottom bg-slate-50 flex-shrink-0">
                        {{-- Search Input --}}
                        <div class="input-group input-group-sm mb-2.5 shadow-xs">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="trendSearchInput" class="form-control border-start-0 ps-0" 
                                   placeholder="ค้นหาชื่อกองทุน / รายการเงินโอน..." oninput="filterTrendFundList(this.value)">
                            <button class="btn btn-outline-secondary bg-white border-start-0" type="button" onclick="clearTrendSearch()" title="ล้างการค้นหา">
                                <i class="bi bi-x-lg text-muted"></i>
                            </button>
                        </div>

                        {{-- Selection Status & Action --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted" style="font-size: 0.76rem;">
                                เลือกแล้ว: <strong class="text-primary font-monospace fs-6" id="trendSelectedCount">1</strong> 
                                <span class="text-muted" id="trendListTotalCount" style="font-size: 0.72rem;">รายการ</span>
                            </div>
                            <div class="d-flex gap-1.5">
                                <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-1 shadow-xs fw-normal" onclick="selectGrandTotalOnly()" title="เลือกดูยอดรวมทั้งหมด" style="font-size: 0.72rem;">
                                    <i class="bi bi-star-fill text-warning me-1"></i> ยอดรวม
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1 shadow-xs fw-normal" onclick="clearTrendSelection()" title="ล้างการเลือกทั้งหมด" style="font-size: 0.72rem;">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> ล้างที่เลือก
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Scrollable Checkbox List --}}
                    <div class="p-2 overflow-y-auto flex-grow-1" id="trendChecklistContainer" style="overflow-y: auto;">
                        {{-- Populated dynamically by JS --}}
                    </div>
                </div>

                {{-- RIGHT COLUMN: Chart & KPI Area --}}
                <div class="flex-grow-1 p-3 p-md-4 d-flex flex-column" style="min-width: 0; height: 100%; overflow-y: auto;">
                    {{-- Top KPI Metric Strip --}}
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 p-2.5 rounded-3 bg-white border shadow-sm flex-shrink-0">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div>
                                <span class="small text-muted" style="font-size: 0.72rem;">ยอดเงินโอนรวมทั้งปี (ที่เลือก):</span>
                                <div class="fw-bold text-success font-monospace" id="kpiTrendTotalSum" style="font-size: 1.15rem;">
                                    0.00 บ.
                                </div>
                            </div>
                            <div class="border-start ps-3">
                                <span class="small text-muted" style="font-size: 0.72rem;">เฉลี่ยต่องวด (12 เดือน):</span>
                                <div class="fw-bold text-primary font-monospace" id="kpiTrendMonthlyAvg" style="font-size: 1.05rem;">
                                    0.00 บ.
                                </div>
                            </div>
                            <div class="border-start ps-3">
                                <span class="small text-muted" style="font-size: 0.72rem;">เดือนที่โอนสูงสุด (Peak):</span>
                                <div class="fw-bold text-danger font-monospace" id="kpiTrendPeakMonth" style="font-size: 1.05rem;">
                                    -
                                </div>
                            </div>
                            <div class="border-start ps-3 d-none d-xl-block">
                                <span class="small text-muted" style="font-size: 0.72rem;">จำนวนงวดโอนทั้งหมด:</span>
                                <div class="fw-bold text-secondary font-monospace" id="kpiTrendTotalBatches" style="font-size: 0.95rem;">
                                    0 งวด
                                </div>
                            </div>
                        </div>

                        {{-- Download Chart Image Action --}}
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-xs fw-normal d-inline-flex align-items-center gap-1.5" 
                                    onclick="downloadTrendChartPng()" title="บันทึกภาพกราฟเป็นไฟล์รูปภาพ PNG" style="font-size: 0.78rem;">
                                <i class="bi bi-camera me-1"></i> บันทึกภาพกราฟ
                            </button>
                        </div>
                    </div>

                    {{-- Chart Canvas Container --}}
                    <div class="bg-white rounded-3 border p-3 shadow-sm position-relative flex-grow-1 d-flex flex-column justify-content-center" style="min-height: 420px;">
                        <div id="trendChartEmptyState" class="position-absolute top-50 start-50 translate-middle text-center text-muted p-4 d-none" style="max-width: 440px;">
                            <div class="rounded-circle bg-slate-50 d-inline-flex align-items-center justify-content-center mb-3 shadow-xs" style="width: 64px; height: 64px; border: 2px dashed #cbd5e1;">
                                <i class="bi bi-graph-up-arrow fs-2 text-primary opacity-75"></i>
                            </div>
                            <div class="fw-bold fs-6 text-dark mb-1">ยังไม่ได้เลือกรายการเพื่อดูกราฟ</div>
                            <div class="small text-secondary" style="line-height: 1.6;">
                                กรุณาติ๊กเลือกกองทุนหลักหรือรายการเงินโอนจากแถบเมนูด้านซ้าย<br>
                                เพื่อแสดงกราฟเส้นแนวโน้มยอดเงินโอน 12 เดือน
                            </div>
                        </div>
                        <div id="trendChartLoading" class="position-absolute top-50 start-50 translate-middle text-center text-muted p-4">
                            <div class="spinner-border text-primary mb-2" role="status"></div>
                            <div class="small text-muted">กำลังประมวลผลข้อมูลกราฟ 12 เดือน...</div>
                        </div>
                        <div style="height: 100%; min-height: 380px; width: 100%; position: relative;">
                            <canvas id="canvasSmartMoneyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center border-top">
                <div class="small text-muted" style="font-size: 0.74rem;">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    <strong>เส้นกราฟ</strong> = ยอดเงินโอนเข้าบัญชีจริงรายเดือน 12 งวด (ต.ค. - ก.ย.) | ชี้ที่จุดบนกราฟเพื่อดูรายละเอียดยอดเงินและจำนวนงวด | สามารถเลือกหลายกองทุนเพื่อเปรียบเทียบแนวโน้มได้
                </div>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4 fw-normal" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#smartMoneyTable').DataTable({
            ordering: true,
            order: [], // retain server-provided order initially
            columnDefs: [
                { orderable: false, targets: [12] } // column 12 (การจัดการ) is not orderable
            ],
            pageLength: 25,
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel-fill me-1 text-white"></i> Export Excel',
                    className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold text-white btn-export-excel',
                    title: 'รายงานการโอนเงิน_SmartMoney_' + "{{ $budget_year }}"
                }
            ],
            language: {
                search: "ค้นหาด่วน:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                infoEmpty: "ไม่มีข้อมูล",
                infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                zeroRecords: "ไม่พบข้อมูลที่ตรงกับคำค้นหา",
                paginate: {
                    first: "หน้าแรก",
                    previous: "ก่อนหน้า",
                    next: "ถัดไป",
                    last: "หน้าสุดท้าย"
                }
            }
        });

        // Expand/Collapse Child Rows (Option 2: Master-Detail)
        $('#smartMoneyTable tbody').on('click', '.btn-toggle-subrows', function (e) {
            e.stopPropagation();
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var batchNo = $(this).data('batch');
            var count = $(this).data('count');
            var template = $('#subtable-' + batchNo);

            if (!template.length) return;

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown table-active');
                $(this).html('<i class="bi bi-diagram-3-fill me-1 text-primary"></i> ' + count + ' ผังย่อย');
                $(this).removeClass('btn-primary text-white').addClass('btn-outline-primary');
            } else {
                row.child(template.html(), 'p-0 bg-light-subtle').show();
                tr.addClass('shown table-active');
                $(this).html('<i class="bi bi-chevron-up me-1"></i> ซ่อนผังย่อย');
                $(this).removeClass('btn-outline-primary').addClass('btn-primary text-white');
            }
        });

        // Initialize Datepicker Thai
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1055
        });

        var thaiMonthsShort = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];

        function toThaiDateString(d) {
            if (!d) return '';
            if (d instanceof Date) {
                var day = d.getDate();
                var month = thaiMonthsShort[d.getMonth() + 1] || '';
                var year = d.getFullYear() + 543;
                return day + ' ' + month + ' ' + year;
            }
            var str = d.toString().trim();
            if (str.indexOf('-') !== -1) {
                var p = str.split('-');
                if (p.length === 3) {
                    var y = parseInt(p[0], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[1], 10);
                    var dayNum = parseInt(p[2], 10);
                    return dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            if (str.indexOf('/') !== -1) {
                var p = str.split('/');
                if (p.length === 3) {
                    var y = parseInt(p[2], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[1], 10);
                    var dayNum = parseInt(p[0], 10);
                    return dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            return str;
        }

        function formatThaiDateTime(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.toString().trim().split(' ');
            var datePart = parts[0];
            var timePart = parts[1] || '';
            
            var dFormatted = datePart;
            if (datePart.indexOf('-') !== -1) {
                var p = datePart.split('-');
                if (p.length === 3) {
                    var y = parseInt(p[0], 10);
                    if (y < 2400) y += 543;
                    var m = parseInt(p[1], 10);
                    var dayNum = parseInt(p[2], 10);
                    dFormatted = dayNum + ' ' + (thaiMonthsShort[m] || m) + ' ' + y;
                }
            }
            return dFormatted + (timePart ? ' ' + timePart + ' น.' : '');
        }

        // Main Filter Datepickers Initialization & Sync with Budget Year
        var curStartDate = "{{ $start_date }}";
        var curEndDate = "{{ $end_date }}";

        if (curStartDate) {
            var sParts = curStartDate.split('-');
            if (sParts.length === 3) {
                $('#filter_start_date_picker').datepicker('setDate', new Date(parseInt(sParts[0], 10), parseInt(sParts[1], 10) - 1, parseInt(sParts[2], 10)));
            }
        }
        if (curEndDate) {
            var eParts = curEndDate.split('-');
            if (eParts.length === 3) {
                $('#filter_end_date_picker').datepicker('setDate', new Date(parseInt(eParts[0], 10), parseInt(eParts[1], 10) - 1, parseInt(eParts[2], 10)));
            }
        }

        $('#filter_start_date_picker').on('changeDate', function(e) {
            if (e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#filter_start_date').val(year + '-' + month + '-' + day);
            } else {
                $('#filter_start_date').val('');
            }
        });

        $('#filter_end_date_picker').on('changeDate', function(e) {
            if (e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#filter_end_date').val(year + '-' + month + '-' + day);
            } else {
                $('#filter_end_date').val('');
            }
        });

        // When Budget Year is changed: update datepickers from data-begin / data-end and submit
        $('#budget_year_select').on('change', function() {
            var opt = $(this).find('option:selected');
            var beginDate = opt.data('begin');
            var endDate = opt.data('end');

            if (beginDate && endDate) {
                $('#filter_start_date').val(beginDate);
                $('#filter_end_date').val(endDate);

                var sP = beginDate.split('-');
                if (sP.length === 3) {
                    $('#filter_start_date_picker').datepicker('setDate', new Date(parseInt(sP[0], 10), parseInt(sP[1], 10) - 1, parseInt(sP[2], 10)));
                }
                var eP = endDate.split('-');
                if (eP.length === 3) {
                    $('#filter_end_date_picker').datepicker('setDate', new Date(parseInt(eP[0], 10), parseInt(eP[1], 10) - 1, parseInt(eP[2], 10)));
                }
            }
            $('#filterForm').submit();
        });

        // Set default dates for ThaiD Bot modal (First day of current month to today in Thai year)
        var now = new Date();
        var firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);

        $('#bot_start_date_picker').datepicker('setDate', firstDayOfMonth);
        $('#bot_end_date_picker').datepicker('setDate', now);

        var startMonth = ("0" + (firstDayOfMonth.getMonth() + 1)).slice(-2);
        var endMonth = ("0" + (now.getMonth() + 1)).slice(-2);
        var endDay = ("0" + now.getDate()).slice(-2);

        $('#bot_start_date').val(firstDayOfMonth.getFullYear() + '-' + startMonth + '-01');
        $('#bot_end_date').val(now.getFullYear() + '-' + endMonth + '-' + endDay);

        $('#bot_start_date_picker').on('changeDate', function(e) {
            if(e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#bot_start_date').val(year + '-' + month + '-' + day);
            } else {
                $('#bot_start_date').val('');
            }
        });

        $('#bot_end_date_picker').on('changeDate', function(e) {
            if(e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#bot_end_date').val(year + '-' + month + '-' + day);
            } else {
                $('#bot_end_date').val('');
            }
        });

        $('#receipt_date_display').on('changeDate', function(e) {
            if(e.date) {
                var day = ("0" + e.date.getDate()).slice(-2);
                var month = ("0" + (e.date.getMonth() + 1)).slice(-2);
                var year = e.date.getFullYear();
                $('#receipt_date').val(year + '-' + month + '-' + day);
            } else {
                $('#receipt_date').val('');
            }
        });

        $('#smtBotModal').on('show.bs.modal', function () {
            $('#botSmtTableBody').html('<tr><td colspan="9" class="text-center py-5 text-muted"><div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>กดปุ่ม "ค้นหาใน Smart Money Transfer" เพื่อดึงรายการเงินโอน</td></tr>');
            $('#botSmtCountBadge').text('พบ 0 รายการ');
            $('#selectedBotSmtCount').text('เลือก 0 รายการ');
            $('#btnStartImportBotSmt').prop('disabled', true);
            $('#checkAllBotSmt').prop('checked', false);
        });

        // Search in Smart Money & Compare with DB
        $('#btnBotSmtSearch').on('click', function() {
            var startDate = $('#bot_start_date').val() || $('#bot_start_date_picker').val();
            var endDate = $('#bot_end_date').val() || $('#bot_end_date_picker').val();
            var keyword = $('#botSmtKeywordFilter').val();

            $('#botSmtTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted fw-bold">กำลังดึงข้อมูลเงินโอน Smart Money Transfer จาก สปสช. แบบ Real-time ...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: "{{ route('import.smart_money.search_bot') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    start_date: startDate,
                    end_date: endDate,
                    keyword: keyword
                },
                success: function(res) {
                    if (res.status === 'success') {
                        renderBotSmtTable(res.data, res.batch_count);
                    } else {
                        $('#botSmtTableBody').html(`
                            <tr>
                                <td colspan="9" class="text-center py-5 text-danger">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                                    <strong>${res.message || 'ไม่พบข้อมูล'}</strong>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr) {
                    var msg = 'เกิดข้อผิดพลาดในการค้นหาข้อมูล';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (xhr.status === 401) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ข้อผิดพลาดการเชื่อมต่อ',
                            text: msg,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#0d6efd'
                        });
                    }
                    $('#botSmtTableBody').html(`
                        <tr>
                            <td colspan="9" class="text-center py-5 text-danger">
                                <i class="bi bi-x-circle-fill fs-3 mb-2 d-block"></i>
                                <strong>${msg}</strong>
                            </td>
                        </tr>
                    `);
                }
            });
        });

        // Render Bot Search Results Table
        function renderBotSmtTable(items, batchCount) {
            var bCount = batchCount || items.length;
            if (bCount !== items.length) {
                $('#botSmtCountBadge').html(`พบ <strong>${bCount}</strong> Batch (${items.length} รายการผังบัญชี)`);
            } else {
                $('#botSmtCountBadge').html(`พบ <strong>${items.length}</strong> รายการ`);
            }
            $('#checkAllBotSmt').prop('checked', false);
            updateSelectedSmtCount();

            if (!items || items.length === 0) {
                $('#botSmtTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 mb-2 d-block opacity-50"></i>
                            ไม่พบรายการเงินโอน Smart Money Transfer ในช่วงวันที่ที่เลือก
                        </td>
                    </tr>
                `);
                return;
            }

            var html = '';
            items.forEach(function(item, idx) {
                var statusBadge = '';
                var isChecked = false;

                if (!item.is_imported) {
                    statusBadge = `<span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="bi bi-dash-circle me-1"></i>ยังไม่นำเข้า</span>`;
                    isChecked = true;
                } else if (item.has_receipt && item.receive_no) {
                    statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="วันที่ใบเสร็จ: ${item.receipt_date || '-'} | ผู้ออก: ${item.receipt_by || '-'}"><i class="bi bi-check-circle-fill me-1"></i>นำเข้าแล้ว (${item.receive_no})</span>`;
                    isChecked = false;
                } else {
                    statusBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>นำเข้าแล้ว (รอใบเสร็จ)</span>`;
                    isChecked = false;
                }

                // Render Vouchers Download Badges
                var docsHtml = '<div class="d-flex justify-content-center gap-1 flex-wrap">';
                if (item.download_paym_file) {
                    var paymUrl = "{{ url('import/smart-money/download-paym') }}/" + encodeURIComponent(item.batch_no) + "?type=PAYM&token=" + encodeURIComponent(item.download_paym_file);
                    docsHtml += `<a href="${paymUrl}" target="_blank" class="badge bg-danger-subtle text-danger border border-danger-subtle px-1.5 py-1 text-decoration-none shadow-2xs" title="คลิกดู/พิมพ์ ใบแจ้งโอนเงิน (PAYM)"><i class="bi bi-file-earmark-pdf-fill me-0.5"></i>โอน</a>`;
                }
                if (item.download_wait_file) {
                    var waitUrl = "{{ url('import/smart-money/download-paym') }}/" + encodeURIComponent(item.batch_no) + "?type=WAIT&token=" + encodeURIComponent(item.download_wait_file);
                    docsHtml += `<a href="${waitUrl}" target="_blank" class="badge bg-warning-subtle text-dark border border-warning-subtle px-1.5 py-1 text-decoration-none shadow-2xs" title="คลิกดู/พิมพ์ ใบแจ้งชะลอโอนเงิน (WAIT)"><i class="bi bi-pause-circle-fill text-warning me-0.5"></i>ชะลอ</a>`;
                }
                if (item.download_debt_file) {
                    var debtUrl = "{{ url('import/smart-money/download-paym') }}/" + encodeURIComponent(item.batch_no) + "?type=DEBT&token=" + encodeURIComponent(item.download_debt_file);
                    docsHtml += `<a href="${debtUrl}" target="_blank" class="badge border px-1.5 py-1 text-decoration-none shadow-2xs" style="background-color: #ede9fe; color: #6d28d9; border-color: #ddd6fe !important;" title="คลิกดู/พิมพ์ ใบแจ้งจำนวนเงินรอหักกลบ (DEBT)"><i class="bi bi-receipt-cutoff me-0.5" style="color: #6d28d9;"></i>หักกลบ</a>`;
                }
                if (!item.download_paym_file && !item.download_wait_file && !item.download_debt_file) {
                    docsHtml += '<span class="text-muted opacity-50">-</span>';
                }
                docsHtml += '</div>';

                var itemJson = encodeURIComponent(JSON.stringify(item));

                html += `
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input bot-smt-check" type="checkbox" data-item="${itemJson}" ${isChecked ? 'checked' : ''}>
                        </td>
                        <td class="text-center text-nowrap small">${item.transfer_date_thai}</td>
                        <td class="text-center text-nowrap fw-bold text-primary">${item.batch_no}</td>
                        <td class="text-center text-nowrap font-monospace small">${item.round_no}</td>
                        <td class="text-center text-nowrap font-monospace small text-secondary">${item.account_code}</td>
                        <td class="text-start">
                            <div class="fw-semibold text-dark lh-sm">${item.fund_main || '-'}</div>
                            <div class="text-muted small lh-1 mt-0.5" style="font-size: 11px;">${item.fund_sub || ''}</div>
                        </td>
                        <td class="text-end text-nowrap fw-bold text-success">${item.net_amount_formatted}</td>
                        <td class="text-center text-nowrap">${docsHtml}</td>
                        <td class="text-center text-nowrap">${statusBadge}</td>
                    </tr>
                `;
            });

            $('#botSmtTableBody').html(html);
            updateSelectedSmtCount();
        }

        // Toggle Select All
        $('#checkAllBotSmt').on('change', function () {
            var checked = $(this).prop('checked');
            $('.bot-smt-check').prop('checked', checked);
            updateSelectedSmtCount();
        });

        // Toggle Single Item
        $(document).on('change', '.bot-smt-check', function () {
            updateSelectedSmtCount();
        });

        // Update Counter
        function updateSelectedSmtCount() {
            var selected = $('.bot-smt-check:checked').length;
            $('#selectedBotSmtCount').text('เลือก ' + selected + ' รายการ');
            $('#btnStartImportBotSmt').prop('disabled', selected === 0);
        }

        // Import Selected Batches to RiMS with Progress Percentage (%)
        $('#btnStartImportBotSmt').on('click', function () {
            var selectedItems = [];
            $('.bot-smt-check:checked').each(function () {
                var raw = $(this).attr('data-item');
                if (raw) {
                    try {
                        selectedItems.push(JSON.parse(decodeURIComponent(raw)));
                    } catch (e) {
                        console.error("Parse item error: ", e);
                    }
                }
            });

            if (selectedItems.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกอย่างน้อย 1 รายการเพื่อนำเข้า', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการนำเข้าข้อมูล Smart Money Transfer?',
                html: `
                    <div class="text-start p-3 bg-light rounded-4 mb-3 small">
                        <div class="mb-2">📄 <strong>จำนวนรายการที่เลือก:</strong> <span class="text-primary fw-bold fs-6">${selectedItems.length}</span> รายการ</div>
                        <div class="text-muted">🛡️ ระบบจะบันทึกข้อมูลยอดเงินโอนและผังบัญชีเข้าสู่ RiMS (ท่านสามารถกดดูหรือดึงข้อมูลรายบุคคลเพิ่มเติมได้ที่ปุ่ม 'รายบุคคล' ของแต่ละงวด)</div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '🚀 เริ่มนำเข้าทันที',
                cancelButtonText: 'ยกเลิก',
                customClass: { popup: 'rounded-4' }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    var totalItems = selectedItems.length;

                    Swal.fire({
                        title: 'กำลังนำเข้าข้อมูล Smart Money Transfer...',
                        html: `
                            <div class="text-center p-2">
                                <!-- Progress Percentage Header -->
                                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                    <span class="small text-muted fw-semibold" id="smtProgressStep">กำลังเตรียมข้อมูล...</span>
                                    <span class="badge bg-success fs-6 fw-bold px-3 py-1 shadow-sm" id="smtProgressPercent" style="border-radius: 12px;">0%</span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="progress mb-3 shadow-inner" style="height: 22px; border-radius: 12px; background-color: #e9ecef; overflow: hidden; padding: 2px;">
                                    <div id="smtProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; border-radius: 10px; font-weight: bold; font-size: 12px; transition: width 0.25s ease;">
                                    </div>
                                </div>

                                <!-- Live Status Card -->
                                <div class="text-start bg-light rounded-4 p-3 border border-light-subtle shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted">📊 ความคืบหน้า:</span>
                                        <span class="fw-bold text-primary font-monospace" id="smtProgressCount">0 / ${totalItems} รายการ</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-dark small mb-2" id="smtProgressCurrentItem">
                                        <span class="spinner-border spinner-border-sm text-success flex-shrink-0" role="status"></span>
                                        <span class="text-truncate fw-semibold" id="smtProgressStatusText">กำลังเริ่มต้นการนำเข้า...</span>
                                    </div>
                                    <div class="pt-2 border-top d-flex justify-content-between text-muted" style="font-size: 11px;">
                                        <span>🔗 ซิงก์ใบเสร็จ: <strong class="text-info" id="smtLiveStmCount">0</strong> รายการ</span>
                                        <span class="text-muted"><i class="bi bi-info-circle me-1"></i> ดึงรายคนได้ที่ปุ่ม "รายบุคคล"</span>
                                    </div>
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-4' }
                    });

                    var totalInserted = 0;
                    var totalUpdated = 0;
                    var totalSyncedDetails = 0;
                    var totalSyncedStm = 0;
                    var failedBatches = [];

                    // Process item by item for smooth real-time % updates
                    for (var i = 0; i < totalItems; i++) {
                        var item = selectedItems[i];
                        var batchNo = item.batch_no || '-';
                        var roundNo = item.round_no || '-';
                        var fundName = item.fund_main || item.account_code || '';

                        var currentNumber = i + 1;
                        $('#smtProgressStep').text(`กำลังนำเข้ารายการที่ ${currentNumber} จาก ${totalItems}`);
                        $('#smtProgressStatusText').html(`Batch <strong>${batchNo}</strong> (งวด: <strong>${roundNo}</strong>) - กำลังบันทึกข้อมูลเข้าสู่ฐานข้อมูล...`);

                        try {
                            var res = await $.ajax({
                                url: "{{ route('import.smart_money.import_bot') }}",
                                method: "POST",
                                timeout: 180000,
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    items: [item]
                                }
                            });

                            if (res && res.status === 'success') {
                                totalInserted += (res.inserted || 0);
                                totalUpdated += (res.updated || 0);
                                totalSyncedDetails += (res.synced_details || 0);
                                totalSyncedStm += (res.synced_stm || 0);
                            } else {
                                failedBatches.push(`Batch ${batchNo} (${res.message || 'ผิดพลาด'})`);
                            }
                        } catch (err) {
                            console.error("Import chunk error:", err);
                            var errText = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : 'เชื่อมต่อล้มเหลว';
                            failedBatches.push(`Batch ${batchNo} (${errText})`);
                        }

                        // Update progress bar
                        var pct = Math.round((currentNumber / totalItems) * 100);
                        $('#smtProgressBar').css('width', pct + '%');
                        $('#smtProgressPercent').text(pct + '%');
                        $('#smtProgressCount').text(`${currentNumber} / ${totalItems} รายการ`);
                        $('#smtLiveStmCount').text(totalSyncedStm.toLocaleString());
                    }

                    // Complete
                    $('#smtProgressStatusText').html(`<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ประมวลผลเสร็จสิ้น 100%</span>`);
                    await new Promise(r => setTimeout(r, 600));

                    Swal.fire({
                        icon: failedBatches.length === 0 ? 'success' : 'warning',
                        title: failedBatches.length === 0 ? 'นำเข้าข้อมูลสำเร็จ 100%' : 'นำเข้าเสร็จสิ้น (มีบางรายการไม่สำเร็จ)',
                        html: `
                            <div class="text-start p-3 bg-light rounded-4 small">
                                <div class="mb-1">📄 <strong>รายการทั้งหมดที่เลือก:</strong> <strong>${totalItems}</strong> รายการ</div>
                                <div class="text-success mb-1">➕ <strong>เพิ่มรายการใหม่:</strong> <strong>${totalInserted}</strong> รายการ</div>
                                <div class="text-primary mb-1">🔄 <strong>อัปเดตข้อมูลเดิม:</strong> <strong>${totalUpdated}</strong> รายการ</div>
                                ${totalSyncedStm > 0 ? `<div class="text-info mb-1">🔗 <strong>ซิงก์เลขที่ใบเสร็จเข้า STM:</strong> <strong>${totalSyncedStm.toLocaleString()}</strong> รายการ</div>` : ''}
                                ${failedBatches.length > 0 ? `<div class="text-danger mt-2 pt-2 border-top">⚠️ <strong>รายการที่ไม่สำเร็จ:</strong><br>${failedBatches.join('<br>')}</div>` : ''}
                                <div class="text-muted mt-2 pt-2 border-top" style="font-size: 11px;">
                                    🛡️ ป้องกันข้อมูลซ้ำ และคงเลขที่ใบเสร็จเดิมไว้ 100% (สามารถกดดูรายคนได้ที่ปุ่ม 'รายบุคคล')
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'ตกลง',
                        customClass: { popup: 'rounded-4' }
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        });

        // Open Receipt Modal
        $(document).on('click', '.btn-new-receipt, .btn-edit-receipt', function() {
            var batch = $(this).data('batch');
            var round = $(this).data('round');
            var receive = $(this).data('receive') || '';
            var date = $(this).data('date') || '';
            var net = $(this).data('net') || '0.00';
            var account = $(this).data('account') || '-';

            $('#modal_batch_no').val(batch);
            $('#modal_round_no').val(round);
            $('#display_batch_no').text(batch);
            $('#display_round_no').text(round);
            $('#display_account').text(account);
            $('#display_net_amount').text(net + ' บาท');

            $('#receive_no').val(receive);
            $('#receipt_date').val(date);

            if(date) {
                $('#receipt_date_display').datepicker('setDate', new Date(date));
            } else {
                $('#receipt_date_display').datepicker('setDate', new Date());
            }

            $('#receiptModal').modal('show');
            setTimeout(function() { $('#receive_no').focus(); }, 500);
        });

        // Save Receipt via AJAX
        $('#btnSaveReceipt').on('click', function() {
            var batch_no = $('#modal_batch_no').val();
            var receive_no = $('#receive_no').val();
            var receipt_date = $('#receipt_date').val();

            if(!receive_no || !receipt_date) {
                Swal.fire('แจ้งเตือน', 'กรุณาระบุเลขที่ใบเสร็จและวันที่ออกใบเสร็จให้ครบถ้วน', 'warning');
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...');

            fetch("{{ route('import.smart_money.update_receipt') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    batch_no: batch_no,
                    receive_no: receive_no,
                    receipt_date: receipt_date
                })
            })
            .then(res => res.json())
            .then(res => {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i> บันทึกใบเสร็จ');
                if(res.status === 'success') {
                    $('#receiptModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        html: `
                            <p class="mb-1"><strong>เลขที่ใบเสร็จ:</strong> ${res.receive_no}</p>
                            <p class="mb-1"><strong>วันที่ออก:</strong> ${res.receipt_date}</p>
                            <p class="mb-0 text-success small">ซิงก์ข้อมูลไปยังตาราง STM แล้ว (${res.stm_synced_count} แถว)</p>
                        `,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('ผิดพลาด', res.message || 'ไม่สามารถบันทึกได้', 'error');
                }
            })
            .catch(err => {
                btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i> บันทึกใบเสร็จ');
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            });
        });

        // Push Receipts from Smart Money to STM Tables (One-Way: Smart Money -> STM)
        $('#btnSmartSyncStm').on('click', function() {
            Swal.fire({
                title: 'ซิงก์ใบเสร็จไปยัง Statement (STM)?',
                text: 'ระบบจะรวบรวมเลขที่และวันที่ออกใบเสร็จจาก Smart Money ทุก Batch ส่งไปอัปเดตยังตาราง Statement UCS และ LGO แบบทางเดียว (Smart Money ➔ STM)',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: '<i class="bi bi-send-check me-1"></i> ใช่, เริ่มซิงก์ข้อมูล',
                cancelButtonText: 'ยกเลิก',
                customClass: { popup: 'rounded-4' }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังส่งเลขที่ใบเสร็จไปยัง Statement (STM)...',
                        html: `
                            <div class="p-2 text-start">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted fw-normal" id="pushSyncStatusText">กำลังเชื่อมต่อฐานข้อมูล Smart Money...</span>
                                    <span class="badge bg-success fw-bold px-2.5 py-1" id="pushSyncPercent" style="font-size: 13px; border-radius: 8px;">15%</span>
                                </div>
                                <div class="progress mb-3" style="height: 16px; border-radius: 8px; background-color: #e9ecef; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                                    <div id="pushSyncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 15%; transition: width 0.3s ease;"></div>
                                </div>
                                <div class="p-2.5 rounded-3 bg-light border text-muted small" style="font-size: 11.5px; line-height: 1.5;">
                                    <i class="bi bi-info-circle text-success me-1"></i> ระบบจะรวบรวมเลขที่ใบเสร็จทุก Batch จาก Smart Money ไปอัปเดตยัง Statement UCS และ LGO (Smart Money ➔ STM)
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-4' }
                    });

                    var pushInterval = setInterval(function() {
                        var $bar = $('#pushSyncProgressBar');
                        if (!$bar.length) return;
                        var currentWidth = parseInt($bar[0].style.width) || 15;
                        if (currentWidth < 90) {
                            var nextWidth = currentWidth + Math.floor(Math.random() * 10) + 5;
                            if (nextWidth > 90) nextWidth = 90;
                            $bar.css('width', nextWidth + '%');
                            $('#pushSyncPercent').text(nextWidth + '%');

                            if (nextWidth >= 25 && nextWidth < 55) {
                                $('#pushSyncStatusText').text('กำลังรวบรวมเลขที่ใบเสร็จรายงวดจาก Smart Money...');
                            } else if (nextWidth >= 55 && nextWidth < 80) {
                                $('#pushSyncStatusText').text('กำลังส่งและอัปเดตเลขที่ใบเสร็จไปยังตาราง Statement ทุกสิทธิ์...');
                            } else if (nextWidth >= 80) {
                                $('#pushSyncStatusText').text('กำลังตรวจสอบความถูกต้องและจัดเก็บข้อมูล...');
                            }
                        }
                    }, 300);

                    fetch("{{ route('import.smart_money.sync_all_stm') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                            "Accept": "application/json"
                        }
                    })
                    .then(res => res.json())
                    .then(res => {
                        clearInterval(pushInterval);
                        $('#pushSyncProgressBar').css('width', '100%');
                        $('#pushSyncPercent').text('100%');
                        $('#pushSyncStatusText').text('ซิงก์ข้อมูลเสร็จสมบูรณ์ 100%');

                        setTimeout(function() {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ซิงก์ใบเสร็จไปยัง Statement สำเร็จ',
                                    text: res.message,
                                    confirmButtonText: 'ตกลง',
                                    confirmButtonColor: '#10b981',
                                    customClass: { popup: 'rounded-4' }
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ผิดพลาด',
                                    text: res.message || 'ไม่สามารถซิงก์ข้อมูลได้',
                                    customClass: { popup: 'rounded-4' }
                                });
                            }
                        }, 400);
                    })
                    .catch(err => {
                        clearInterval(twoWayInterval);
                        Swal.fire({
                            icon: 'error',
                            title: 'ผิดพลาด',
                            text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์',
                            customClass: { popup: 'rounded-4' }
                        });
                    });
                }
            });
        });

        // Patient Details Modal Management
        var currentModalBatchNo = '';
        window.currentModalBatchNo = '';
        var currentModalPage = 1;
        var currentModalSubfund = '';
        var pmodalThaidConnected = false;

        function checkPatientModalThaidStatus(silent = false) {
            if (!silent && !pmodalThaidConnected) {
                $('#pmodalAuthStatusIcon').removeClass('bg-success-subtle text-success bg-warning-subtle text-warning')
                    .addClass('bg-secondary-subtle text-secondary')
                    .html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                $('#pmodalAuthStatusText').text('กำลังตรวจสอบสถานะการเชื่อมต่อ ThaiD...');
                $('#pmodalAuthStatusSub').text('ระบบกำลังทดสอบ Session กับ สปสช.');
            }

            $('#mainThaidSpinner').removeClass('d-none');

            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { 
                    _token: "{{ csrf_token() }}",
                    auth_type: 'access_token'
                },
                success: function(res) {
                    $('#mainThaidSpinner').addClass('d-none');
                    if (res && res.connected) {
                        pmodalThaidConnected = true;
                        var userName = res.user || 'ผู้ใช้งาน ThaiD';

                        // 1. Update Main Page Header Button
                        $('#mainThaidStatusBtn').removeClass('btn-outline-secondary btn-outline-warning text-dark').addClass('btn-outline-success')
                            .attr('title', 'เชื่อมต่อ ThaiD แล้ว: ' + userName + ' (คลิกเพื่อเปลี่ยนบัญชี / เชื่อมต่อใหม่)');
                        $('#mainThaidStatusText').html('<i class="bi bi-shield-check text-success me-1"></i> ThaiD: <span class="text-dark fw-bold">' + userName + '</span>');

                        // 2. Update Patient Modal Status Card
                        $('#pmodalAuthStatusIcon').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary')
                            .addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#pmodalAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary fw-bold">' + userName + '</span>');
                        $('#pmodalAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + (res.connected_at ? formatThaiDateTime(res.connected_at) : ''));
                        $('#pmodalBtnThaidLogin').html('<i class="bi bi-arrow-repeat me-1"></i> เชื่อมต่อใหม่').addClass('btn-primary').removeClass('btn-outline-primary');
                        $('#pmodalBtnLogout').removeClass('d-none');
                    } else {
                        pmodalThaidConnected = false;

                        // 1. Update Main Page Header Button
                        $('#mainThaidStatusBtn').removeClass('btn-outline-success btn-outline-secondary').addClass('btn-outline-warning text-dark')
                            .attr('title', 'ยังไม่ได้เข้าสู่ระบบ ThaiD (คลิกเพื่อสแกน QR Code เข้าสู่ระบบ)');
                        $('#mainThaidStatusText').html('<i class="bi bi-qr-code-scan text-primary me-1"></i> เข้าสู่ระบบ ThaiD');

                        // 2. Update Patient Modal Status Card
                        $('#pmodalAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                            .addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#pmodalAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ ThaiD');
                        $('#pmodalAuthStatusSub').text('สแกน QR Code ด้วยแอป ThaiD เพื่อเข้าสู่ระบบก่อนดึงข้อมูลรายคน');
                        $('#pmodalBtnThaidLogin').html('<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)').addClass('btn-primary').removeClass('btn-outline-primary');
                        $('#pmodalBtnLogout').addClass('d-none');
                    }
                },
                error: function() {
                    pmodalThaidConnected = false;
                    $('#mainThaidSpinner').addClass('d-none');
                    $('#mainThaidStatusBtn').removeClass('btn-outline-success btn-outline-secondary').addClass('btn-outline-warning text-dark');
                    $('#mainThaidStatusText').html('<i class="bi bi-qr-code-scan text-primary me-1"></i> เข้าสู่ระบบ ThaiD');

                    $('#pmodalAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                        .addClass('bg-warning-subtle text-warning')
                        .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                    $('#pmodalAuthStatusText').text('ยังไม่ได้เชื่อมต่อ ThaiD');
                    $('#pmodalAuthStatusSub').text('กดปุ่ม "เข้าสู่ระบบ (ThaiD)" เพื่อเชื่อมต่อ');
                    $('#pmodalBtnThaidLogin').html('<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)').addClass('btn-primary').removeClass('btn-outline-primary');
                    $('#pmodalBtnLogout').addClass('d-none');
                }
            });
        }

        // Check ThaiD Status on initial page load
        checkPatientModalThaidStatus(true);

        $('#mainThaidStatusBtn').on('click', function() {
            openEclaimThaidQrModal(function() {
                checkPatientModalThaidStatus();
            });
        });

        $('#pmodalBtnThaidLogin').on('click', function() {
            openEclaimThaidQrModal(function() {
                checkPatientModalThaidStatus();
            });
        });

        $('#pmodalBtnLogout').on('click', function () {
            Swal.fire({
                title: 'ยืนยันตัดการเชื่อมต่อ?',
                text: 'ระบบจะล้าง Session e-Claim / ThaiD ออกจากระบบ',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ตัดการเชื่อมต่อ',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    localStorage.removeItem('eclaim_session_token');
                    $.ajax({
                        url: "{{ route('import.eclaim-bot.logout') }}",
                        method: "POST",
                        data: { _token: "{{ csrf_token() }}" },
                        success: function () {
                            checkPatientModalThaidStatus();
                            Swal.fire({
                                icon: 'success',
                                title: 'ตัดการเชื่อมต่อแล้ว',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        function loadPatientModalData(batchNo, page = 1, search = '', subfund = '') {
            currentModalBatchNo = batchNo;
            window.currentModalBatchNo = batchNo;
            currentModalPage = page;
            currentModalSubfund = subfund || '';
            checkPatientModalThaidStatus(true);

            $('#pmodal_table_body').html(`
                <tr>
                    <td colspan="11" class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 fw-semibold">กำลังโหลดข้อมูลผู้ป่วย...</div>
                    </td>
                </tr>
            `);

            var url = "{{ url('import/smart-money/api/detail') }}/" + encodeURIComponent(batchNo) + "?page=" + page;
            if (search) {
                url += "&search=" + encodeURIComponent(search);
            }
            if (subfund) {
                url += "&sub_fund=" + encodeURIComponent(subfund);
            }

            fetch(url, {
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                    "Accept": "application/json"
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    // 1. Update Header Info
                    var b = res.batch;
                    var st = res.stats;
                    $('#pmodal_batch_badge').text('Batch No. ' + b.batch_no);
                    $('#pmodal_transfer_date').text(b.transfer_date_thai || '-');
                    $('#pmodal_round_no').text(b.round_nos.join(', ') || '-');
                    $('#pmodal_account_code').text(b.account_codes.join(', ') || '-');
                    
                    // Show dynamic displayed/filtered amount
                    $('#pmodal_net_amount').text(st.total_amount_formatted + ' บาท');

                    // Batch Total comparison hint
                    if (Math.abs(st.total_amount - b.total_net_amount) > 0.01) {
                        $('#pmodal_batch_total_hint').html(`<span class="text-secondary">ยอดรวมทั้ง Batch: <strong>${b.total_net_amount_formatted} บาท</strong></span>`);
                    } else {
                        $('#pmodal_batch_total_hint').html(`<span class="text-muted small">(ตรงกับยอดรวมทั้ง Batch: ${b.total_net_amount_formatted} บาท)</span>`);
                    }

                    $('#pmodal_fullpage_link').attr('href', "{{ url('import/smart-money/detail') }}/" + encodeURIComponent(b.batch_no));

                    // 1.1 Populate Sub-fund Filter dropdown
                    var totalCount = (res.stats && res.stats.total_count) ? res.stats.total_count : (res.data ? res.data.length : 0);
                    var optsHtml = '<option value="">-- ทุกผังย่อย / ทุกรายการ (' + totalCount + ' รายการ) --</option>';

                    if (b.sub_funds_meta && b.sub_funds_meta.length > 0) {
                        optsHtml += '<optgroup label="📂 ผังบัญชีกองทุน">';
                        b.sub_funds_meta.forEach(function(sf) {
                            var val = sf.fund_sub || sf.account_code;
                            var isSel = (subfund && (subfund === val || subfund === sf.account_code || subfund === sf.round_no || subfund === sf.fund_main)) ? 'selected' : '';
                            var fundTitle = (sf.fund_main && sf.fund_sub && sf.fund_main !== sf.fund_sub) 
                                ? sf.fund_main + ' - ' + sf.fund_sub 
                                : (sf.fund_sub || sf.fund_main || sf.account_code);
                            var label = (sf.round_no ? sf.round_no + ' : ' : '') + fundTitle + ' (' + sf.net_amount_formatted + ' บ.)';
                            optsHtml += `<option value="${val}" ${isSel}>${label}</option>`;
                        });
                        optsHtml += '</optgroup>';
                    }

                    if (res.detail_sub_funds && res.detail_sub_funds.length > 0) {
                        optsHtml += '<optgroup label="📋 รายการบริการย่อย (Clinical Services)">';
                        res.detail_sub_funds.forEach(function(df) {
                            if (!df.key) return;
                            var isSel = (subfund && subfund === df.key) ? 'selected' : '';
                            var label = df.label + ' [' + df.count + ' รายการ | ' + df.total_amount_formatted + ' บ.]';
                            optsHtml += `<option value="${df.key}" ${isSel}>${label}</option>`;
                        });
                        optsHtml += '</optgroup>';
                    }

                    $('#pmodal_subfund_filter').html(optsHtml);
                    if (subfund) {
                        $('#pmodal_subfund_filter').val(subfund);
                    }

                    // 2. Update Stats Badge
                    $('#pmodal_stats_badge').html(`<i class="bi bi-people me-1 text-primary"></i> <strong>${st.total_count}</strong> รายการ (${st.total_amount_formatted} บาท)`);

                    // 3. Render Patient Rows
                    if (!res.data || res.data.length === 0) {
                        var emptyMsg = subfund 
                            ? `<div class="fw-semibold text-dark mt-2">ยังไม่มีข้อมูลรายคนของผัง <strong>"${subfund}"</strong> ในระบบ</div><div class="small text-muted mt-1">ท่านสามารถกดปุ่ม <span class="badge bg-primary text-white">ดึงรายคนจาก SMT</span> หรือ <span class="badge bg-outline-primary text-primary border">นำเข้าไฟล์ Excel</span> เพื่อดึงข้อมูลเพิ่มได้</div>`
                            : `<div class="fw-semibold text-dark mt-2">ยังไม่พบรายการรายบุคคลใน Batch นี้</div><div class="small text-muted mt-1">ท่านสามารถกดปุ่ม <span class="badge bg-primary text-white">ดึงรายคนจาก SMT</span> หรือ <span class="badge bg-outline-primary text-primary border">นำเข้าไฟล์ Excel</span> เพื่อนำเข้าข้อมูล</div>`;

                        $('#pmodal_table_body').html(`
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 mb-1 d-block opacity-40"></i>
                                    ${emptyMsg}
                                </td>
                            </tr>
                        `);
                    } else {
                        var html = '';
                        var startIdx = ((res.pagination.current_page - 1) * res.pagination.per_page) + 1;
                        res.data.forEach(function(d, idx) {
                            var typeBadge = d.pt_type === 'ผู้ป่วยใน' 
                                ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">IPD</span>'
                                : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">OPD</span>';

                            var anSeqDisplay = (d.an && d.an !== '-') 
                                ? `<span class="fw-bold font-monospace text-dark">${d.an}</span>` 
                                : `<span class="font-monospace text-muted small">${d.seq_no || '-'}</span>`;

                            var isNapNo = d.cid && (d.cid.startsWith('D4-') || d.cid.startsWith('NAP') || (d.cid.length === 14 && d.cid.includes('-')));
                            var isHivFund = (d.main_fund && d.main_fund.startsWith('NAP')) || (d.sub_fund && (d.sub_fund.includes('CD4') || d.sub_fund.includes('VL') || d.sub_fund.includes('ยาต้าน') || d.sub_fund.includes('HIV')));

                            var hnDisplay = (d.hn && d.hn.trim() !== '' && d.hn !== '-')
                                ? `<span class="fw-bold font-monospace text-primary">${d.hn}</span>`
                                : (isNapNo || isHivFund ? '<span class="text-muted small fst-italic" title="สงวนสิทธิ์ไม่ระบุ HN ใน Statement กองทุน NAP">-</span>' : '<span class="text-muted opacity-50">-</span>');

                            var cidDisplay = (d.cid && d.cid.trim() !== '' && d.cid !== '-')
                                ? (isNapNo ? `<span class="badge bg-secondary-subtle text-dark border font-monospace" title="รหัสประจำตัวผู้รับบริการ NAP (สปสช.)">${d.cid}</span>` : `<span class="font-monospace text-dark">${d.cid}</span>`)
                                : '<span class="text-muted opacity-50">-</span>';

                            var ptNameDisplay = (d.pt_name && d.pt_name.trim() !== '' && d.pt_name !== '-')
                                ? `<span class="fw-semibold text-dark">${d.pt_name}</span>`
                                : (isNapNo || isHivFund 
                                    ? `<span class="badge bg-light text-secondary border font-normal fw-normal py-1 px-2" title="สปสช. ปิดบังชื่อผู้ป่วยใน Statement กองทุนเอดส์ (NAP) เพื่อรักษาความลับผู้ป่วย"><i class="bi bi-shield-lock-fill text-muted me-1"></i>สงวนชื่อ (กองทุนเอดส์ NAP)</span>`
                                    : '<span class="text-muted opacity-50">-</span>');

                            html += `
                                <tr>
                                    <td class="text-center text-muted small">${startIdx + idx}</td>
                                    <td class="text-center text-nowrap small">${d.transfer_date_thai}</td>
                                    <td class="text-center text-nowrap">${hnDisplay}</td>
                                    <td class="text-center text-nowrap">${anSeqDisplay}</td>
                                    <td class="text-center text-nowrap">${typeBadge}</td>
                                    <td class="text-center text-nowrap">${cidDisplay}</td>
                                    <td class="text-start text-nowrap">${ptNameDisplay}</td>
                                    <td class="text-center text-nowrap small">${d.vstdate_thai}</td>
                                    <td class="text-end fw-bold text-success text-nowrap">${d.receive_total_formatted}</td>
                                    <td class="text-center font-monospace small text-nowrap">${d.repno || '-'}</td>
                                    <td class="text-start small text-truncate" style="max-width: 180px;" title="${d.sub_fund_desc || d.sub_fund}">
                                        <div class="fw-semibold text-dark text-truncate">${d.sub_fund || '-'}</div>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#pmodal_table_body').html(html);
                    }

                    // 4. Render Pagination
                    var pg = res.pagination;
                    var from = ((pg.current_page - 1) * pg.per_page) + 1;
                    var to = Math.min(pg.current_page * pg.per_page, pg.total);
                    $('#pmodal_page_info').text(`แสดง ${pg.total > 0 ? from : 0} ถึง ${to} จากทั้งหมด ${pg.total} รายการ`);

                    var pagHtml = '';
                    if (pg.last_page > 1) {
                        pagHtml += `<button class="btn btn-xs btn-outline-secondary ${pg.current_page === 1 ? 'disabled' : ''}" onclick="changeModalPage(${pg.current_page - 1})"><i class="bi bi-chevron-left"></i></button>`;
                        for (var i = Math.max(1, pg.current_page - 2); i <= Math.min(pg.last_page, pg.current_page + 2); i++) {
                            pagHtml += `<button class="btn btn-xs ${i === pg.current_page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="changeModalPage(${i})">${i}</button>`;
                        }
                        pagHtml += `<button class="btn btn-xs btn-outline-secondary ${pg.current_page === pg.last_page ? 'disabled' : ''}" onclick="changeModalPage(${pg.current_page + 1})"><i class="bi bi-chevron-right"></i></button>`;
                    }
                    $('#pmodal_pagination_controls').html(pagHtml);

                } else {
                    $('#pmodal_table_body').html(`
                        <tr>
                            <td colspan="11" class="text-center py-5 text-danger">
                                <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                                ${res.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'}
                            </td>
                        </tr>
                    `);
                }
            })
            .catch(err => {
                $('#pmodal_table_body').html(`
                    <tr>
                        <td colspan="11" class="text-center py-5 text-danger">
                            <i class="bi bi-x-circle-fill fs-3 mb-2 d-block"></i>
                            เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์
                        </td>
                    </tr>
                `);
            });
        }

        window.changeModalPage = function(p) {
            var search = $('#pmodal_search_input').val();
            var subfund = $('#pmodal_subfund_filter').val() || currentModalSubfund;
            loadPatientModalData(currentModalBatchNo, p, search, subfund);
        };

        // Open Patient Modal via click
        $(document).on('click', '.btn-view-patient-detail', function() {
            var batchNo = $(this).data('batch');
            var subfund = $(this).data('subfund') || '';
            $('#pmodal_search_input').val('');
            $('#patientDetailModal').modal('show');
            checkPatientModalThaidStatus();
            loadPatientModalData(batchNo, 1, '', subfund);
        });

        // Search in Patient Modal
        $('#pmodal_btn_search').on('click', function() {
            var search = $('#pmodal_search_input').val();
            var subfund = $('#pmodal_subfund_filter').val() || '';
            loadPatientModalData(currentModalBatchNo, 1, search, subfund);
        });

        $('#pmodal_search_input').on('keypress', function(e) {
            if (e.which === 13) {
                var search = $(this).val();
                var subfund = $('#pmodal_subfund_filter').val() || '';
                loadPatientModalData(currentModalBatchNo, 1, search, subfund);
            }
        });

        // Subfund Dropdown Filter Change
        $('#pmodal_subfund_filter').on('change', function() {
            var search = $('#pmodal_search_input').val();
            var subfund = $(this).val() || '';
            loadPatientModalData(currentModalBatchNo, 1, search, subfund);
        });

        // Export Patient Excel
        $('#pmodal_btn_export').on('click', function() {
            if (!currentModalBatchNo) return;
            var search = $('#pmodal_search_input').val();
            var subfund = $('#pmodal_subfund_filter').val() || '';
            var exportUrl = "{{ url('import/smart-money/detail') }}/" + encodeURIComponent(currentModalBatchNo) + "?export=excel";
            if (search) {
                exportUrl += "&search=" + encodeURIComponent(search);
            }
            if (subfund) {
                exportUrl += "&sub_fund=" + encodeURIComponent(subfund);
            }
            window.location.href = exportUrl;
        });

        // Upload Detail File from inside Patient Modal with Progress Percentage (%)
        $('#pmodal_upload_file').on('change', function() {
            if (!this.files || this.files.length === 0) return;

            var file = this.files[0];
            var formData = new FormData();
            formData.append('detail_excel', file);
            formData.append('batch_no', currentModalBatchNo);

            Swal.fire({
                title: 'กำลังนำเข้าไฟล์รายบุคคล...',
                html: `
                    <div class="text-center p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-semibold text-truncate me-2">${file.name}</span>
                            <span class="badge bg-teal text-white fw-bold px-2 py-1" id="pmodalUploadPct" style="background-color: #0d9488;">0%</span>
                        </div>
                        <div class="progress mb-2" style="height: 14px; border-radius: 7px; background-color: #e2e8f0; overflow: hidden;">
                            <div id="pmodalUploadBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background-color: #0d9488; transition: width 0.3s ease;"></div>
                        </div>
                        <div class="text-muted small text-start" id="pmodalUploadStatus" style="font-size: 11px;">
                            กำลังอัปโหลดและประมวลผลข้อมูลผู้ป่วย...
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { popup: 'rounded-4' }
            });

            var pmodalInterval = setInterval(function() {
                var currentWidth = parseInt($('#pmodalUploadBar')[0]?.style.width) || 0;
                if (currentWidth < 85) {
                    var nextWidth = currentWidth + Math.floor(Math.random() * 15) + 5;
                    if (nextWidth > 85) nextWidth = 85;
                    $('#pmodalUploadBar').css('width', nextWidth + '%');
                    $('#pmodalUploadPct').text(nextWidth + '%');
                }
            }, 300);

            fetch("{{ route('import.smart_money.import_detail') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                clearInterval(pmodalInterval);
                $('#pmodalUploadBar').css('width', '100%');
                $('#pmodalUploadPct').text('100%');
                $('#pmodalUploadStatus').text('เสร็จสิ้น 100%');

                setTimeout(function() {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'นำเข้าสำเร็จ 100%',
                            text: res.message,
                            confirmButtonText: 'ตกลง',
                            customClass: { popup: 'rounded-4' }
                        }).then(() => {
                            loadPatientModalData(currentModalBatchNo, 1, '', currentModalSubfund);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ผิดพลาด',
                            text: res.message || 'ไม่สามารถนำเข้าข้อมูลได้',
                            customClass: { popup: 'rounded-4' }
                        });
                    }
                }, 400);
            })
            .catch(err => {
                clearInterval(pmodalInterval);
                Swal.fire({
                    icon: 'error',
                    title: 'ผิดพลาด',
                    text: 'เกิดข้อผิดพลาดในการนำเข้าไฟล์',
                    customClass: { popup: 'rounded-4' }
                });
            });
        });

        // Sync Patient Detail from SMT (Live Download strictly on-demand)
        window.syncPatientDetailFromSmt = function(batchNo) {
            batchNo = batchNo || window.currentModalBatchNo || currentModalBatchNo;
            if (!batchNo) return;

            // Check ThaiD Status First
            if (!pmodalThaidConnected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เชื่อมต่อ ThaiD',
                    text: 'กรุณาเข้าสู่ระบบด้วย ThaiD เพื่อดึงข้อมูลรายบุคคลจาก สปสช. อัตโนมัติ',
                    confirmButtonText: '<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#0d6efd',
                    customClass: { popup: 'rounded-4' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        openEclaimThaidQrModal(function() {
                            checkPatientModalThaidStatus();
                            syncPatientDetailFromSmt(batchNo);
                        });
                    }
                });
                return;
            }

            Swal.fire({
                title: 'กำลังดึงข้อมูลรายคน...',
                html: `
                    <div class="text-center p-3">
                        <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                        <div class="fw-bold text-dark mb-1">กำลังเชื่อมต่อและดึงข้อมูลรายบุคคลจาก SMT...</div>
                        <div class="small text-muted">ระบบกำลังดาวน์โหลดรายงานรายบุคคลจาก สปสช. กรุณารอสักครู่</div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: { popup: 'rounded-4' }
            });

            $.ajax({
                url: "{{ url('import/smart-money/sync-detail') }}/" + encodeURIComponent(batchNo),
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    if (res && res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ดึงข้อมูลสำเร็จ!',
                            html: `
                                <div class="text-start p-3 bg-light rounded-4 small">
                                    <div class="text-success fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i> ${res.message}</div>
                                    <div class="text-muted">จำนวนผู้ป่วย: <strong>${(res.count || 0).toLocaleString()}</strong> รายการ</div>
                                    <div class="text-muted">ยอดเงินรวม: <strong>${res.total_amount_formatted || '0.00'}</strong> บาท</div>
                                </div>
                            `,
                            customClass: { popup: 'rounded-4' }
                        }).then(() => {
                            loadPatientModalData(batchNo, 1, '', currentModalSubfund);
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ไม่สามารถดึงข้อมูลได้',
                            text: res.message || 'ไม่พบรายงานรายบุคคลในระบบ',
                            customClass: { popup: 'rounded-4' }
                        });
                    }
                },
                error: function(xhr) {
                    var msg = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (xhr.status === 401) {
                        checkPatientModalThaidStatus();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Session ThaiD หมดอายุ',
                            text: msg,
                            confirmButtonText: '<i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ (ThaiD)',
                            showCancelButton: true,
                            cancelButtonText: 'ยกเลิก',
                            confirmButtonColor: '#0d6efd',
                            customClass: { popup: 'rounded-4' }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                openEclaimThaidQrModal(function() {
                                    checkPatientModalThaidStatus();
                                    syncPatientDetailFromSmt(batchNo);
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: msg,
                            customClass: { popup: 'rounded-4' }
                        });
                    }
                }
            });
        };

        $('#pmodal_btn_sync_smt').on('click', function() {
            if (!currentModalBatchNo) return;
            syncPatientDetailFromSmt(currentModalBatchNo);
        });

        // Delete Batch
        $(document).on('click', '.btn-delete-batch', function() {
            var batch_no = $(this).data('batch');
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: `ต้องการลบข้อมูล Batch No. ${batch_no} หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'ใช่, ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if(result.isConfirmed) {
                    fetch("{{ route('import.smart_money.delete') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({ batch_no: batch_no })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if(res.status === 'success') {
                            Swal.fire('ลบสำเร็จ', 'ข้อมูลถูกลบเรียบร้อยแล้ว', 'success').then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    });
                }
            });
        });
    });

    function previewSelectedFile(input, targetId) {
        var id = targetId || 'file_selected_name';
        if (input.files && input.files[0]) {
            $('#' + id).removeClass('d-none').html('<i class="bi bi-file-earmark-check me-1"></i> ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)');
        }
    }

    function previewSelectedMultipleFiles(input, targetId) {
        var id = targetId || 'detail_file_selected_name';
        if (input.files && input.files.length > 0) {
            if (input.files.length === 1) {
                $('#' + id).removeClass('d-none').html('<i class="bi bi-file-earmark-check me-1"></i> ' + input.files[0].name + ' (' + (input.files[0].size / 1024).toFixed(1) + ' KB)');
            } else {
                var fileBadges = [];
                for (var i = 0; i < input.files.length; i++) {
                    var f = input.files[i];
                    var sz = (f.size / 1024).toFixed(1) + ' KB';
                    fileBadges.push('<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace" style="font-size: 11px;"><i class="bi bi-file-earmark-excel me-1"></i>' + f.name + ' (' + sz + ')</span>');
                }
                $('#' + id).removeClass('d-none').html(
                    '<div class="fw-bold text-success small mb-1"><i class="bi bi-check2-circle me-1"></i> เลือกทั้งหมด ' + input.files.length + ' ไฟล์พร้อมนำเข้า:</div>' +
                    '<div class="d-flex flex-wrap gap-1 mt-1">' + fileBadges.join('') + '</div>'
                );
            }
        }
    }

    function filterAccountCode(accountCode) {
        var url = new URL(window.location.href);
        if(accountCode) {
            url.searchParams.set('account_code', accountCode);
        } else {
            url.searchParams.delete('account_code');
        }
        window.location.href = url.toString();
    }

    // =========================================================================
    // SMART MONEY 12-MONTH TREND CHART LOGIC (Interactive Multi-Fund Chart)
    // =========================================================================
    var trendChartInstance = null;
    var trendDataStore = null;
    var trendCurrentMode = 'sub'; // 'sub' (รายการกองทุนย่อย) or 'main' (กองทุนหลัก)
    var trendSearchQuery = '';
    var trendSelectedKeys = new Set(['grand_total']); // Default: grand_total

    // Color Palette for Multi-Line Comparison
    const trendColorPalette = [
        '#2563eb', // Royal Blue
        '#059669', // Emerald Green
        '#ea580c', // Vibrant Orange / Amber
        '#7c3aed', // Purple
        '#db2777', // Pink / Magenta
        '#0891b2', // Cyan
        '#16a34a', // Forest Green
        '#4f46e5', // Indigo
        '#e11d48', // Rose
        '#ca8a04', // Gold
        '#0d9488', // Teal
        '#9333ea', // Deep Violet
    ];

    // Custom DataLabels Plugin: Renders floating rounded pill badges above each data point
    const smtTrendDataLabelsPlugin = {
        id: 'smtTrendDataLabelsPlugin',
        afterDatasetsDraw(chart) {
            // Show data label badges when up to 6 lines are displayed
            if (chart.data.datasets.length > 6) return;
            const ctx = chart.ctx;
            ctx.save();
            ctx.font = '600 10.5px "Prompt", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';

            chart.data.datasets.forEach((dataset, datasetIdx) => {
                const meta = chart.getDatasetMeta(datasetIdx);
                if (meta.hidden) return;

                meta.data.forEach((element, pointIdx) => {
                    const val = dataset.data[pointIdx];
                    if (val === null || val === undefined || isNaN(val)) return;

                    let text = '';
                    const absVal = Math.abs(val);
                    if (absVal >= 1000000) {
                        text = (val / 1000000).toFixed(2) + 'M';
                    } else if (absVal >= 10000) {
                        text = (val / 1000).toFixed(1) + 'k';
                    } else if (absVal > 0) {
                        text = Number(val).toLocaleString(undefined, {maximumFractionDigits: 0});
                    } else {
                        text = '0';
                    }

                    const x = element.x;
                    // Stagger y position slightly if multiple lines to prevent overlap
                    const yOffset = (datasetIdx % 2 === 0) ? -16 : -30;
                    const y = element.y + yOffset;

                    const metrics = ctx.measureText(text);
                    const boxW = Math.max(metrics.width + 12, 28);
                    const boxH = 18;

                    // Shadow for premium floating pill effect
                    ctx.save();
                    ctx.shadowColor = 'rgba(15, 23, 42, 0.14)';
                    ctx.shadowBlur = 6;
                    ctx.shadowOffsetY = 2;

                    // Draw floating rounded pill badge background
                    ctx.fillStyle = '#ffffff';
                    ctx.beginPath();
                    if (typeof ctx.roundRect === 'function') {
                        ctx.roundRect(x - (boxW / 2), y - (boxH / 2), boxW, boxH, 6);
                    } else {
                        ctx.rect(x - (boxW / 2), y - (boxH / 2), boxW, boxH);
                    }
                    ctx.fill();
                    ctx.restore();

                    // Stroke pill border
                    ctx.strokeStyle = dataset.borderColor;
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    if (typeof ctx.roundRect === 'function') {
                        ctx.roundRect(x - (boxW / 2), y - (boxH / 2), boxW, boxH, 6);
                    } else {
                        ctx.rect(x - (boxW / 2), y - (boxH / 2), boxW, boxH);
                    }
                    ctx.stroke();

                    // Text label inside pill
                    ctx.fillStyle = dataset.borderColor;
                    ctx.fillText(text, x, y + 0.5);
                });
            });
            ctx.restore();
        }
    };

    $('#smtTrendChartModal').on('shown.bs.modal', function() {
        if (!trendDataStore) {
            loadTrendChartData();
        } else {
            if (trendChartInstance) trendChartInstance.resize();
        }
    });

    function loadTrendChartData(year) {
        const budgetYear = year || $('#trendSelectBudgetYear').val() || '{{ $budget_year }}';
        $('#trendChartLoading').removeClass('d-none');
        $('#trendChartEmptyState').addClass('d-none');

        fetch("{{ route('import.smart_money.trend_data') }}?budget_year=" + encodeURIComponent(budgetYear), {
            headers: {
                "Accept": "application/json"
            }
        })
        .then(res => res.json())
        .then(res => {
            $('#trendChartLoading').addClass('d-none');
            if (res.status === 'success') {
                trendDataStore = res;
                $('#badgeTrendBudgetYear').text('ปีงบประมาณ ' + res.budget_year);
                $('#countSubFunds').text(res.sub_funds.length);
                $('#countMainFunds').text(res.main_funds.length);

                // Reset selection to grand_total
                trendSelectedKeys = new Set(['grand_total']);
                
                renderTrendChecklist();
                updateTrendChartAndKpi();
            } else {
                Swal.fire('ผิดพลาด', res.message || 'ไม่สามารถโหลดข้อมูลกราฟได้', 'error');
            }
        })
        .catch(err => {
            $('#trendChartLoading').addClass('d-none');
            console.error('Error loading trend data:', err);
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อโหลดข้อมูลกราฟ', 'error');
        });
    }

    function switchTrendChartMode(mode) {
        trendCurrentMode = mode;
        if (mode === 'sub') {
            $('#btnTrendModeSub').css({'background-color': '#10b981', 'color': '#ffffff'});
            $('#btnTrendModeMain').css({'background-color': 'transparent', 'color': '#cbd5e1'});
        } else {
            $('#btnTrendModeMain').css({'background-color': '#4f46e5', 'color': '#ffffff'});
            $('#btnTrendModeSub').css({'background-color': 'transparent', 'color': '#cbd5e1'});
        }
        renderTrendChecklist();
        updateTrendChartAndKpi();
    }

    function filterTrendFundList(val) {
        trendSearchQuery = (val || '').toLowerCase().trim();
        renderTrendChecklist();
    }

    function clearTrendSearch() {
        $('#trendSearchInput').val('');
        trendSearchQuery = '';
        renderTrendChecklist();
    }

    function selectGrandTotalOnly() {
        trendSelectedKeys = new Set(['grand_total']);
        renderTrendChecklist();
        updateTrendChartAndKpi();
    }

    function clearTrendSelection() {
        trendSelectedKeys = new Set();
        renderTrendChecklist();
        updateTrendChartAndKpi();
    }

    function toggleTrendFundSelection(key) {
        if (key === 'grand_total') {
            trendSelectedKeys = new Set(['grand_total']);
        } else {
            trendSelectedKeys.delete('grand_total');
            if (trendSelectedKeys.has(key)) {
                trendSelectedKeys.delete(key);
            } else {
                trendSelectedKeys.add(key);
            }
        }
        renderTrendChecklist();
        updateTrendChartAndKpi();
    }

    function renderTrendChecklist() {
        if (!trendDataStore) return;
        const container = document.getElementById('trendChecklistContainer');
        if (!container) return;

        const list = trendCurrentMode === 'sub' ? trendDataStore.sub_funds : trendDataStore.main_funds;
        let html = '';

        // 1. Always show Grand Total Item at top
        const isGrandSelected = trendSelectedKeys.has('grand_total');
        const grandTotal = trendDataStore.grand_total;
        
        html += `
            <div class="trend-item-card p-2.5 rounded-3 border mb-2 cursor-pointer transition-all ${isGrandSelected ? 'bg-primary-subtle border-primary shadow-xs' : 'bg-white'}"
                 onclick="toggleTrendFundSelection('grand_total')" style="cursor: pointer; transition: all 0.2s ease;">
                <div class="d-flex align-items-start gap-2">
                    <div class="form-check mt-0.5">
                        <input class="form-check-input" type="checkbox" ${isGrandSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleTrendFundSelection('grand_total')">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                            <span class="badge bg-primary text-white rounded-pill px-2 py-0.5" style="font-size: 10px;">
                                <i class="bi bi-star-fill text-warning me-0.5"></i> ยอดรวมทุกกองทุน
                            </span>
                            <span class="fw-bold text-success font-monospace" style="font-size: 12.5px;">
                                ${Number(grandTotal.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บ.
                            </span>
                        </div>
                        <div class="small fw-semibold text-dark text-truncate" title="${grandTotal.fund_main}" style="font-size: 11.5px;">
                            ${grandTotal.fund_main}
                        </div>
                        <div class="text-muted d-flex align-items-center justify-content-between mt-1" style="font-size: 10.5px;">
                            <span>${grandTotal.batch_count} งวดโอน</span>
                            <span class="text-secondary">เฉลี่ย ${Number(grandTotal.monthly_avg).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0})} บ./ด.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 my-2 px-1">
                <hr class="flex-grow-1 my-0 text-muted opacity-25">
                <span class="text-muted small fw-bold" style="font-size: 10px;">รายการเงินโอนแยกตามกองทุน</span>
                <hr class="flex-grow-1 my-0 text-muted opacity-25">
            </div>
        `;

        // 2. Filter list by Search
        let visibleCount = 0;
        let colorIdx = 1;

        list.forEach((item) => {
            const name = (trendCurrentMode === 'sub' ? (item.fund_main + ' ' + item.fund_sub) : item.fund_name).toLowerCase();
            const matchesSearch = !trendSearchQuery || name.includes(trendSearchQuery);

            if (!matchesSearch) return;
            visibleCount++;

            const isSelected = trendSelectedKeys.has(item.key);
            const title = trendCurrentMode === 'sub' ? item.fund_sub : item.fund_name;
            const subtitle = trendCurrentMode === 'sub' ? item.fund_main : (item.sub_count + ' รายการย่อย');
            const itemColor = trendColorPalette[colorIdx % trendColorPalette.length];
            colorIdx++;

            html += `
                <div class="trend-item-card p-2 rounded-3 border mb-1.5 cursor-pointer transition-all ${isSelected ? 'bg-light-subtle border-primary shadow-2xs' : 'bg-white'}"
                     onclick="toggleTrendFundSelection('${item.key}')" style="cursor: pointer; transition: all 0.15s ease;">
                    <div class="d-flex align-items-start gap-2">
                        <div class="form-check mt-0.5">
                            <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleTrendFundSelection('${item.key}')">
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                <div class="d-flex align-items-center gap-1.5 overflow-hidden flex-grow-1 min-w-0">
                                    <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 9px; height: 9px; background-color: ${itemColor};"></span>
                                    <span class="small fw-semibold text-dark text-truncate" title="${title}" style="font-size: 11.5px;">
                                        ${title}
                                    </span>
                                </div>
                                <span class="fw-bold text-dark font-monospace text-nowrap ms-1" style="font-size: 11.5px;">
                                    ${Number(item.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})} บ.
                                </span>
                            </div>
                            <div class="text-muted d-flex align-items-center justify-content-between mt-1" style="font-size: 10px;">
                                <span class="text-truncate text-secondary" style="max-width: 180px;" title="${subtitle}">${subtitle}</span>
                                <span class="badge bg-light text-secondary border px-1.5">${item.batch_count} งวด</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        if (visibleCount === 0) {
            html += `
                <div class="text-center text-muted py-4 small">
                    <i class="bi bi-search fs-3 text-secondary opacity-50 mb-1 d-block"></i>
                    ไม่พบรายการกองทุนที่ตรงกับคำค้นหา
                </div>
            `;
        }

        container.innerHTML = html;
        $('#trendSelectedCount').text(trendSelectedKeys.size);
        $('#trendListTotalCount').text('/ ' + list.length + ' รายการ');
    }

    function updateTrendChartAndKpi() {
        if (!trendDataStore) return;
        const canvas = document.getElementById('canvasSmartMoneyTrendChart');
        if (!canvas) return;

        const labels = trendDataStore.labels;
        const isGrand = trendSelectedKeys.has('grand_total');
        const list = trendCurrentMode === 'sub' ? trendDataStore.sub_funds : trendDataStore.main_funds;

        let datasets = [];
        let combinedMonthlySums = array_fill_zero(12);
        let totalSum = 0;
        let totalBatches = 0;

        if (isGrand) {
            const grand = trendDataStore.grand_total;
            totalSum = grand.total_amount;
            totalBatches = grand.batch_count;
            combinedMonthlySums = [...grand.monthly_amounts];

            // Area Gradient
            const ctx = canvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 380);
            gradient.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
            gradient.addColorStop(0.6, 'rgba(37, 99, 235, 0.05)');
            gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

            datasets.push({
                label: 'ยอดเงินโอนรวมทุกกองทุน (บาท)',
                data: grand.monthly_amounts,
                borderColor: '#2563eb',
                backgroundColor: gradient,
                fill: true,
                borderWidth: 3,
                tension: 0.38,
                pointRadius: 5,
                pointHoverRadius: 8,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2.5,
                batches: grand.monthly_batches
            });
        } else if (trendSelectedKeys.size > 0) {
            let colorIdx = 0;
            trendSelectedKeys.forEach(key => {
                const item = list.find(x => x.key === key);
                if (item) {
                    totalSum += item.total_amount;
                    totalBatches += item.batch_count;
                    const itemColor = trendColorPalette[colorIdx % trendColorPalette.length];
                    colorIdx++;

                    for (let i = 0; i < 12; i++) {
                        combinedMonthlySums[i] += item.monthly_amounts[i];
                    }

                    const label = trendCurrentMode === 'sub' ? item.fund_sub : item.fund_name;

                    datasets.push({
                        label: label,
                        data: item.monthly_amounts,
                        borderColor: itemColor,
                        backgroundColor: 'transparent',
                        fill: false,
                        borderWidth: 2.8,
                        tension: 0.38,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: itemColor,
                        pointBorderWidth: 2.5,
                        batches: item.monthly_batches
                    });
                }
            });
        }

        // Empty state check
        if (datasets.length === 0) {
            $('#trendChartEmptyState').removeClass('d-none');
            if (trendChartInstance) {
                trendChartInstance.destroy();
                trendChartInstance = null;
            }
            $('#kpiTrendTotalSum').text('0.00 บ.');
            $('#kpiTrendMonthlyAvg').text('0.00 บ.');
            $('#kpiTrendPeakMonth').text('-');
            $('#kpiTrendTotalBatches').text('0 งวด');
            return;
        } else {
            $('#trendChartEmptyState').addClass('d-none');
        }

        // Calculate KPI
        const monthlyAvg = totalSum / 12;
        const peakVal = Math.max(...combinedMonthlySums);
        const peakIdx = combinedMonthlySums.indexOf(peakVal);
        const peakLabel = (peakIdx >= 0 && peakVal > 0) ? (labels[peakIdx] + ' (' + formatCompactMillions(peakVal) + ')') : '-';

        $('#kpiTrendTotalSum').text(Number(totalSum).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.');
        $('#kpiTrendMonthlyAvg').text(Number(monthlyAvg).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บ.');
        $('#kpiTrendPeakMonth').text(peakLabel);
        $('#kpiTrendTotalBatches').text(Number(totalBatches).toLocaleString() + ' งวด');

        // Draw Chart.js with floating data labels plugin
        if (trendChartInstance) {
            trendChartInstance.destroy();
        }

        const ctx = canvas.getContext('2d');
        trendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            plugins: [smtTrendDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: datasets.length > 1,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            boxHeight: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11.5, family: 'Prompt, sans-serif' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        padding: 12,
                        cornerRadius: 10,
                        titleFont: { size: 13, weight: 'bold', family: 'Prompt, sans-serif' },
                        bodyFont: { size: 12, family: 'Prompt, sans-serif' },
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                const ds = context.dataset;
                                const batch = (ds.batches && ds.batches[context.dataIndex]) ? ds.batches[context.dataIndex] : 0;
                                return ' ' + context.dataset.label + ': ' + Number(val).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท (' + batch + ' งวด)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11, weight: 'bold', family: 'Prompt, sans-serif' }, color: '#475569' }
                    },
                    y: {
                        beginAtZero: true,
                        grace: '20%', // ensure floating value badges at top peaks are never clipped
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: {
                            font: { size: 11, family: 'Prompt, sans-serif' },
                            color: '#64748b',
                            callback: function(val) {
                                return formatCompactMillions(val);
                            }
                        }
                    }
                }
            }
        });
    }

    function formatCompactMillions(val) {
        if (!val || val === 0) return '0.0';
        if (Math.abs(val) >= 1000000) {
            return (val / 1000000).toFixed(2) + 'M';
        }
        if (Math.abs(val) >= 1000) {
            return (val / 1000).toFixed(1) + 'k';
        }
        return Number(val).toLocaleString();
    }

    function array_fill_zero(len) {
        return new Array(len).fill(0);
    }

    function downloadTrendChartPng() {
        if (!trendChartInstance) {
            Swal.fire('ข้อความ', 'ไม่มีกราฟที่สามารถบันทึกได้', 'info');
            return;
        }
        const canvas = document.getElementById('canvasSmartMoneyTrendChart');
        if (!canvas) return;

        // Create a temporary canvas with white background
        const tmpCanvas = document.createElement('canvas');
        tmpCanvas.width = canvas.width;
        tmpCanvas.height = canvas.height;
        const tmpCtx = tmpCanvas.getContext('2d');

        tmpCtx.fillStyle = '#ffffff';
        tmpCtx.fillRect(0, 0, tmpCanvas.width, tmpCanvas.height);
        tmpCtx.drawImage(canvas, 0, 0);

        const imageUri = tmpCanvas.toDataURL('image/png');
        const link = document.createElement('a');
        const year = (trendDataStore && trendDataStore.budget_year) ? trendDataStore.budget_year : '2568';
        link.download = `SmartMoney_Trend_12Months_${year}.png`;
        link.href = imageUri;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endpush
