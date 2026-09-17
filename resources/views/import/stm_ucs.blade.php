@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9">
                <div class="card-body">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_ucs_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ STM (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-0">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-success px-3.5" type="submit" style="border-radius: 0;">
                                <i class="bi bi-cloud-upload me-1.5"></i> นำเข้าข้อมูล
                            </button>
                            <button type="button" class="btn btn-primary px-3.5 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#eclaimBotModal" style="border-radius: 0 10px 10px 0; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                <i class="bi bi-cloud-arrow-down-fill me-1.5"></i> ดึงจาก e-Claim
                            </button>
                        </div>

                        @if ($message = Session::get('stm_success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0 mt-3">
                                <i class="bi bi-check-circle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Search -->
    <div class="page-header-box">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-cloud-arrow-down-fill text-success me-2"></i>
                ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2">
                <a href="{{ url('/import/stm_ucs_detail_opd') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-person-badge me-1"></i> รายละเอียด OPD
                </a>
                <a href="{{ url('/import/stm_ucs_detail_ipd') }}" class="btn btn-danger btn-sm rounded-pill px-3">
                    <i class="bi bi-hospital me-1"></i> รายละเอียด IPD
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm fw-semibold" id="btnSyncFromSmartMoney" title="ดึงเลขที่ใบเสร็จล่าสุดที่การเงินลงไว้ใน Smart Money มาอัปเดต">
                    <i class="bi bi-wallet2 me-1"></i> ซิงก์ใบเสร็จจาก Smart Money
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="form-check form-switch me-1 mb-0 bg-light px-3 py-1.5 rounded-pill border d-flex align-items-center gap-2 shadow-sm" style="cursor: pointer;">
                    <input class="form-check-input ms-0" type="checkbox" id="filterUnreceiptedOnly" style="cursor: pointer;">
                    <label class="form-check-label small fw-bold text-danger mb-0 text-nowrap" for="filterUnreceiptedOnly" style="cursor: pointer;">
                        <i class="bi bi-funnel-fill text-danger me-1"></i> เฉพาะงวดที่ยังไม่ได้ออกใบเสร็จ
                    </label>
                </div>
                <span class="text-muted small">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm" name="budget_year" style="width: 160px; border-radius: 8px;">
                    @foreach ($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_ucs" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" width="17%">ชื่อ File</th>
                            <th class="text-center">Dep</th>
                            <th class="text-center">จำนวนราย</th>
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center">ชดเชยสุทธิ (STM)</th>
                            <th class="text-center">เลขงวด</th>
                            <th class="text-center">โอนจริง (Smart Money)</th>
                            <th class="text-center">สถานะ / รอโอน</th>
                            <th class="text-center">เลขที่ใบเสร็จ</th>
                            <th class="text-center">วันที่ออกใบเสร็จ</th>
                            <th class="text-center">ผู้ออกใบเสร็จ</th>
                            <th class="text-center" width="11%">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_ucs as $row)
                        @php
                            $stmTotal = (float)$row->receive_total;
                            $smTotal = isset($row->sm_net_total) && $row->sm_net_total !== null ? (float)$row->sm_net_total : null;
                            $diff = $smTotal !== null ? round($stmTotal - $smTotal, 2) : null;
                            $ipPay = (float)($row->ip_pay ?? 0);
                            $opPay = (float)($row->op_pay ?? 0);
                            $aePay = (float)($row->ae_pay ?? 0);
                            $instPay = (float)($row->inst_pay ?? 0);
                            $hcPay = (float)($row->hc_pay ?? 0);
                            $otherPay = (float)($row->other_pay ?? 0);
                            
                            $effectiveReceiptNo = !empty($row->sm_receive_no) ? $row->sm_receive_no : ($row->receive_no ?? '');
                            $effectiveReceiptDate = !empty($row->sm_receipt_date) ? $row->sm_receipt_date : ($row->receipt_date ?? '');
                            $hasReceipt = !empty($effectiveReceiptNo);

                            // Tooltip for pending subfunds
                            $tooltipSubfunds = [];
                            if ($aePay > 0) $tooltipSubfunds[] = "AE: " . number_format($aePay, 2);
                            if ($instPay > 0) $tooltipSubfunds[] = "INST: " . number_format($instPay, 2);
                            if ($hcPay > 0) $tooltipSubfunds[] = "HC: " . number_format($hcPay, 2);
                            if ($otherPay > 0) $tooltipSubfunds[] = "อื่นๆ: " . number_format($otherPay, 2);
                            $tooltipText = !empty($tooltipSubfunds) ? "<b>รอโอนกองทุนย่อย:</b><br>" . implode("<br>", $tooltipSubfunds) : "ยอดส่วนต่างกองทุนย่อย";
                        @endphp
                        <tr data-receipt="{{ $hasReceipt ? '1' : '0' }}">
                            <td class="small fw-bold text-dark text-break">{{ $row->stm_filename }}</td>
                            <td class="text-center">
                                @if($row->dep === 'OPD')
                                    <span class="badge bg-info-subtle text-primary border border-info-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-person-fill me-0.5"></i> OPD
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-hospital me-0.5"></i> IPD
                                    </span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($row->count_cid) }}</td>
                            <td class="text-end text-muted">{{ number_format($row->charge,2) }}</td>
                            <td class="text-end text-success fw-bold">
                                {{ number_format($stmTotal, 2) }}
                            </td>
                            <td class="text-center text-primary fw-bold">{{ $row->round_no }}</td>
                            <td class="text-end fw-semibold">
                                @if($smTotal !== null && $smTotal > 0)
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="text-teal fw-bold" style="color: #0d9488;">{{ number_format($smTotal, 2) }}</span>
                                        <button type="button" class="btn btn-xs btn-outline-teal rounded-circle p-0 btn-show-sm-batches ms-1" 
                                                style="width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; color: #0d9488; border-color: #0d9488; transition: all 0.2s ease;"
                                                data-round="{{ $row->round_no }}"
                                                data-filename="{{ $row->stm_filename }}"
                                                data-sm-total="{{ $smTotal }}"
                                                data-batches-json="{{ $row->sm_batches_json ?? '[]' }}"
                                                data-bs-toggle="tooltip" title="คลิกดูรายละเอียด Batch เงินโอน & เลขที่ใบเสร็จ">
                                            <i class="bi bi-eye-fill" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                    @if(!empty($row->sm_batches))
                                        <div class="text-muted cursor-pointer btn-show-sm-batches mt-0.5" 
                                             style="font-size: 10px; cursor: pointer;"
                                             data-round="{{ $row->round_no }}"
                                             data-filename="{{ $row->stm_filename }}"
                                             data-sm-total="{{ $smTotal }}"
                                             data-batches-json="{{ $row->sm_batches_json ?? '[]' }}"
                                             data-bs-toggle="tooltip" title="คลิกดูรายละเอียด Batch เงินโอน & เลขที่ใบเสร็จ">
                                            Batch: <span class="text-decoration-underline">{{ $row->sm_batches }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($smTotal !== null && $smTotal > 0)
                                    @if(abs($diff) < 0.01)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                            <i class="bi bi-check-circle-fill me-0.5"></i> โอนครบแล้ว
                                        </span>
                                    @elseif($diff > 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;" data-bs-toggle="tooltip" title="ยอดชดเชย STM: {{ number_format($stmTotal, 2) }} | โอนจริง SM: {{ number_format($smTotal, 2) }}">
                                            <i class="bi bi-hourglass-split me-0.5"></i> รอโอน {{ number_format($diff, 2) }}
                                        </span>
                                    @else
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" style="font-size: 11px;">
                                            <i class="bi bi-info-circle me-0.5"></i> โอน {{ number_format($smTotal, 2) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-dash-circle me-0.5"></i> รอนำเข้า SM
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($hasReceipt)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-0.5"></i> {{ $effectiveReceiptNo }}
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-clock-history me-0.5"></i> ยังไม่ออก
                                    </span>
                                @endif
                            </td>
                            <td class="text-center small">{{ !empty($effectiveReceiptDate) ? DateThai($effectiveReceiptDate) : '-' }}</td>
                            <td class="text-center small text-muted">{{ $row->receipt_by ?? '-' }}</td>
                            <td class="text-center text-nowrap">
                                <div class="d-flex justify-content-center align-items-center gap-1.5">
                                    @if(!empty($row->round_no))
                                        @if(Auth::user()->status == 'admin' || Auth::user()->allow_receipt == 'Y')
                                            <button type="button"
                                                class="btn btn-xs {{ $hasReceipt ? 'btn-outline-warning btn-edit-receipt' : 'btn-outline-success btn-new-receipt' }} rounded-pill px-2.5 py-1"
                                                data-round="{{ $row->round_no }}"
                                                data-receive="{{ $effectiveReceiptNo }}"
                                                data-date="{{ $effectiveReceiptDate }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#receiptModal"
                                                title="{{ $hasReceipt ? 'แก้ไข' : 'ออกใบเสร็จ' }}">
                                                <i class="bi {{ $hasReceipt ? 'bi-pencil-square' : 'bi-plus-circle' }} me-1"></i>{{ $hasReceipt ? 'แก้ไข' : 'ออกใบเสร็จ' }}
                                            </button>
                                        @endif
                                        
                                        @if(Auth::user()->status == 'admin')
                                            <button type="button"
                                                class="btn btn-xs btn-outline-danger rounded-circle p-1 btn-action-delete"
                                                data-filename="{{ $row->stm_filename }}"
                                                data-type="stm_ucs"
                                                title="ลบข้อมูลนำเข้า"
                                                style="width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @endif
                                    @endif
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
 


{{-- Modal: Monthly Summary Chart --}}
<div class="modal fade" id="chartModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-bg-1 mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: #0284c7; border-radius: 12px; color: white;">
                        <i class="bi bi-bar-chart-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="db_title">Dashboard</h5>
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน Statement ประกันสุขภาพ UCS</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                            <i class="bi bi-calendar3 me-1"></i> ข้อมูลรายงวดรับเงิน
                        </span>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
                                <select class="form-select shadow-sm text-center" id="modal_filter_budget_year" style="width: 170px; border-radius: 8px;">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                            {{ $row->LEAVE_YEAR_NAME }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>
                <div style="height: 450px; width: 100%;" id="chart_container">
                    <div id="monthlySummaryChart" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal ออกใบเสร็จ --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalTitle">
                    ออกใบเสร็จรับเงิน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="round_no">
                <div class="mb-2">
                    <label class="form-label">เลขที่ใบเสร็จ</label>
                    <input type="text" class="form-control" id="receive_no">
                </div>
                <div class="mb-2">
                    <label class="form-label">วันที่ออกใบเสร็จ</label>
                    <input type="hidden" id="receipt_date" name="receipt_date">
                    <input type="text" class="form-control datepicker_th" id="receipt_date_display" style="width: 120px;" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnSaveReceipt">
                    บันทึก
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>
{{-- End Modal --}}

{{-- Modal: Smart Money Batches Detail (รายละเอียดเงินโอนราย Batch & ใบเสร็จ) --}}
<div class="modal fade" id="smBatchesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 14px; color: white; box-shadow: 0 4px 12px rgba(13,148,136,0.3);">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">รายละเอียดเงินโอน Smart Money & เลขที่ใบเสร็จ</h5>
                        <div class="text-light-50 small mt-0.5" id="smBatchesModalSubtitle">งวดที่: - | ไฟล์: -</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Summary Card -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <span class="text-muted small">ยอดเงินโอนจริงรวมทุก Batch ในงวดนี้:</span>
                            <div class="fs-4 fw-bold text-teal mt-0.5" style="color: #0d9488;" id="smBatchesModalGrandTotal">0.00 บาท</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-teal-subtle text-teal border border-teal-subtle px-3 py-1.5 rounded-pill fw-bold" style="color: #0d9488; background-color: #f0fdfa; border-color: #ccfbf1;" id="smBatchesModalCountBadge">
                                0 Batches
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Batches Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-list-check text-teal me-2" style="color: #0d9488;"></i> รายการ Batch เงินโอนจาก สปสช.
                        </h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" width="5%">#</th>
                                    <th>วันที่โอน</th>
                                    <th class="text-center">Batch No.</th>
                                    <th>งวด / กองทุน</th>
                                    <th class="text-end">ยอดโอน (บาท)</th>
                                    <th class="text-center">เลขที่ใบเสร็จ</th>
                                    <th>วันที่ออก / ผู้ออก</th>
                                </tr>
                            </thead>
                            <tbody id="smBatchesModalTableBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-0 p-3 px-4 d-flex justify-content-between">
                <a href="{{ route('import.smart_money') }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> ไปยังระบบ Smart Money
                </a>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: e-Claim Automation Bot (ThaiD SSO & Direct Import) -->
<div class="modal fade" id="eclaimBotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0284c7, #0369a1); border-radius: 14px; color: white; box-shadow: 0 4px 12px rgba(2,132,199,0.4);">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">ดึงข้อมูล Statement (STM UCS) จาก e-Claim อัตโนมัติ</h5>
                        <div class="text-light-50 small mt-0.5 d-flex align-items-center gap-2">
                            <span>ระบบเชื่อมต่อตรง eclaim.nhso.go.th</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 10px;">
                                <i class="bi bi-shield-lock-fill me-1"></i> ThaiD SSO Ready
                            </span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-light">
                <!-- Section 1: e-Claim Session Connection (Cookie / Token) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div id="eclaimAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="eclaimAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ e-Claim</div>
                                    <div class="text-muted small" id="eclaimAuthStatusSub">เปิดเว็บ e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใน Extension เพื่อเชื่อมต่อ</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" id="btnEclaimLoginPopup">
                                    <i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ e-Claim (ThaiD)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="showEclaimExtensionGuide()" title="สำหรับติดตั้งหรือเปิดใช้ Extension บน Chrome">
                                    <i class="bi bi-puzzle me-1"></i> ส่วนเสริม Chrome
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnEclaimLogout">
                                    <i class="bi bi-box-arrow-right me-1"></i> ตัดการเชื่อมต่อ
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>
                <!-- Section 2: Search & Filter Box -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">ปีงบประมาณ</label>
                                <select class="form-select form-select-sm rounded-3" id="botBudgetYear">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}" {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                            {{ $row->LEAVE_YEAR_NAME }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">งวดเดือน</label>
                                <select class="form-select form-select-sm rounded-3" id="botMonth">
                                    <option value="10">ตุลาคม (ต้นปีงบ)</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                    <option value="01">มกราคม</option>
                                    <option value="02">กุมภาพันธ์</option>
                                    <option value="03">มีนาคม</option>
                                    <option value="04">เมษายน</option>
                                    <option value="05">พฤษภาคม</option>
                                    <option value="06">มิถุนายน</option>
                                    <option value="07">กรกฎาคม</option>
                                    <option value="08" selected>สิงหาคม</option>
                                    <option value="09">กันยายน (สิ้นปีงบ)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">ประเภทสิทธิ์</label>
                                <input type="text" class="form-control form-control-sm rounded-3 bg-light" value="UCS (ประกันสุขภาพถ้วนหน้า)" readonly>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm rounded-pill w-100 shadow-sm py-1.5" id="btnBotSearch">
                                    <i class="bi bi-search me-1"></i> ค้นหาใน e-Claim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Statement List Table Preview -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-list-check text-success me-2"></i> รายการ Statement ที่พบใน e-Claim
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-dark border px-2.5 py-1" id="botResultBadge">พบ 0 รายการ</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="botStatementTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%">
                                        <input class="form-check-input" type="checkbox" id="checkAllBot">
                                    </th>
                                    <th class="text-center">งวดที่ (Round)</th>
                                    <th>ชื่อไฟล์ STM</th>
                                    <th class="text-center">ประเภท</th>
                                    <th class="text-center">วันที่ออก</th>
                                    <th class="text-end">จำนวนราย</th>
                                    <th class="text-end">เรียกเก็บ</th>
                                    <th class="text-end">ชดเชยสุทธิ</th>
                                    <th class="text-center">สถานะใน RIMS</th>
                                </tr>
                            </thead>
                            <tbody id="botStatementTableBody">
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-0 p-3 px-4 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <span id="selectedCountText" class="fw-bold text-primary">เลือก 0 รายการ</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" id="btnStartImportBot" disabled>
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มนำเข้า RIMS ทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert: Success -->
@if (session('stm_success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: "{!! session('stm_success') !!}",
            icon: 'success',
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#673ab7',
            customClass: {
                confirmButton: 'btn btn-primary btn-sm px-4'
            },
            allowOutsideClick: false
        });
    });
</script>
@endif
<!-- SweetAlert: Error -->
@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: @json(session('error')),
                confirmButtonText: 'ปิด'
            });
        });
    </script>
@endif
    
@endsection

@push('scripts')
  <script>
        // Global helper for file upload form
        function showLoadingAlert() {
            Swal.fire({
                title: 'กำลังนำเข้าข้อมูล...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
        }

        window.simulateProcess = function(event) {
            event.preventDefault(); 
            const fileInput = document.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire({
                    title: 'แจ้งเตือน',
                    text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                    icon: 'warning',
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#673ab7',
                    customClass: {
                        confirmButton: 'btn btn-primary btn-sm px-4'
                    }
                });
                return;
            }
            
            showLoadingAlert();
            document.getElementById('importForm').submit();
        };

        $(document).ready(function () {
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'd M yyyy', // Matches DateThai() helper output
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true,
                zIndexOffset: 1050
            });

            // Sync Changes to Hidden Inputs for Backend (YYYY-MM-DD)
            $('.datepicker_th').on('changeDate', function(e) {
                var date = e.date;
                var targetId = $(this).attr('id').replace('_display', '');
                var hiddenInput = $('#' + targetId);
                
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear(); // Gregorian
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });

            // Event delegation for receipt modal buttons (needed because DataTable pagination redraws the DOM)
            $(document).on('click', '.btn-new-receipt, .btn-edit-receipt', function () {
                $('#round_no').val($(this).data('round'));
                $('#receive_no').val($(this).data('receive') || '');
                $('#receipt_date').val($(this).data('date') || '');
                
                var rDate = $(this).data('date');
                if(rDate) {
                    $('#receipt_date_display').datepicker('setDate', new Date(rDate));
                } else {
                    $('#receipt_date_display').datepicker('clearDates');
                }
            });

            // AJAX Save Receipt
            $('#btnSaveReceipt').on('click', function () {
                let round_no     = $('#round_no').val();
                let receive_no   = $('#receive_no').val();
                let receipt_date = $('#receipt_date').val();
                if (!receive_no || !receipt_date) {
                    Swal.fire('แจ้งเตือน','กรุณากรอกข้อมูลให้ครบ','warning');
                    return;
                }
                fetch("{{ url('import/stm_ucs_updateReceipt') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                        "Content-Type": "application/json",
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({
                        round_no: round_no,
                        receive_no: receive_no,
                        receipt_date: receipt_date
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ',
                            html: `
                                <p><strong>เลขที่ใบเสร็จ:</strong> ${res.receive_no}</p>
                                <p><strong>วันที่ออก:</strong> ${res.receipt_date}</p>
                            `,
                            confirmButtonText: 'ปิด'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', res.message, 'error');
                    }
                });
            });

            // Sync Receipts from Smart Money with Animated Progress %
            $('#btnSyncFromSmartMoney').on('click', function() {
                Swal.fire({
                    title: 'ซิงก์ใบเสร็จจาก Smart Money?',
                    text: 'ระบบจะดึงเลขที่และวันที่ออกใบเสร็จที่การเงินลงไว้ใน Smart Money มาอัปเดตเข้า Statement UCS',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    confirmButtonText: '<i class="bi bi-arrow-repeat me-1"></i> ใช่, เริ่มซิงก์ข้อมูล',
                    cancelButtonText: 'ยกเลิก',
                    customClass: { popup: 'rounded-4' }
                }).then((result) => {
                    if(result.isConfirmed) {
                        Swal.fire({
                            title: 'กำลังซิงก์เลขที่ใบเสร็จจาก Smart Money...',
                            html: `
                                <div class="p-2 text-start">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small text-muted fw-normal" id="syncStatusText">กำลังเชื่อมต่อฐานข้อมูล Smart Money...</span>
                                        <span class="badge bg-success fw-bold px-2.5 py-1" id="syncPercent" style="font-size: 13px; border-radius: 8px;">15%</span>
                                    </div>
                                    <div class="progress mb-3" style="height: 16px; border-radius: 8px; background-color: #e9ecef; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
                                        <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 15%; transition: width 0.3s ease;"></div>
                                    </div>
                                    <div class="p-2.5 rounded-3 bg-light border text-muted small" style="font-size: 11.5px; line-height: 1.5;">
                                        <i class="bi bi-info-circle text-success me-1"></i> ระบบกำลังรวบรวมเลขที่ใบเสร็จทุก Batch จาก Smart Money มาอัปเดตลง Statement UCS
                                    </div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-4' }
                        });

                        var syncInterval = setInterval(function() {
                            var $bar = $('#syncProgressBar');
                            if (!$bar.length) return;
                            var currentWidth = parseInt($bar[0].style.width) || 15;
                            if (currentWidth < 90) {
                                var nextWidth = currentWidth + Math.floor(Math.random() * 12) + 6;
                                if (nextWidth > 90) nextWidth = 90;
                                $bar.css('width', nextWidth + '%');
                                $('#syncPercent').text(nextWidth + '%');

                                if (nextWidth >= 30 && nextWidth < 60) {
                                    $('#syncStatusText').text('กำลังประมวลผลรวบรวมเลขที่ใบเสร็จรายงวด...');
                                } else if (nextWidth >= 60 && nextWidth < 85) {
                                    $('#syncStatusText').text('กำลังอัปเดตเลขที่ใบเสร็จลง Statement UCS...');
                                } else if (nextWidth >= 85) {
                                    $('#syncStatusText').text('กำลังตรวจสอบความถูกต้องและจัดเก็บข้อมูล...');
                                }
                            }
                        }, 250);

                        fetch("{{ route('import.smart_money.sync_all_stm') }}", {
                            method: "POST",
                            headers: {
                                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                                "Accept": "application/json"
                            }
                        })
                        .then(res => res.json())
                        .then(res => {
                            clearInterval(syncInterval);
                            $('#syncProgressBar').css('width', '100%');
                            $('#syncPercent').text('100%');
                            $('#syncStatusText').text('ซิงก์ข้อมูลเสร็จสมบูรณ์ 100%');

                            setTimeout(function() {
                                if(res.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'สำเร็จ',
                                        text: res.message,
                                        confirmButtonText: 'ตกลง',
                                        customClass: { popup: 'rounded-4' }
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'ผิดพลาด',
                                        text: res.message,
                                        confirmButtonText: 'ปิด',
                                        customClass: { popup: 'rounded-4' }
                                    });
                                }
                            }, 400);
                        })
                        .catch(err => {
                            clearInterval(syncInterval);
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                                confirmButtonText: 'ปิด',
                                customClass: { popup: 'rounded-4' }
                            });
                        });
                    }
                });
            });

            var stmTable = $('#stm_ucs').DataTable({
                ordering: false,   // 🔥 ปิด sorting
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' +
                        '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' +
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' +
                        '<"col-md-6"p>' +
                    '>',
                buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]'
                }
                ],
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    paginate: {
                        previous: "ก่อนหน้า",
                        next: "ถัดไป"
                    }
                }
            });

            // Initialize Bootstrap Tooltips
            function initTooltips() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            initTooltips();

            stmTable.on('draw', function() {
                initTooltips();
            });

            // Smart Money Batches Detail Modal Handler
            $(document).on('click', '.btn-show-sm-batches', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var round = $(this).data('round');
                var filename = $(this).data('filename');
                var smTotal = parseFloat($(this).data('sm-total')) || 0;
                var batchesRaw = $(this).data('batches-json');
                var batches = [];
                
                if (typeof batchesRaw === 'string') {
                    try {
                        batches = JSON.parse(batchesRaw) || [];
                    } catch(err) {
                        batches = [];
                    }
                } else if (Array.isArray(batchesRaw)) {
                    batches = batchesRaw;
                }

                $('#smBatchesModalSubtitle').html('งวดที่: <b>' + round + '</b> | ไฟล์: <b>' + filename + '</b>');
                $('#smBatchesModalGrandTotal').text(smTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' บาท');
                $('#smBatchesModalCountBadge').text(batches.length + ' Batches');

                var rows = '';
                if (batches.length === 0) {
                    rows = '<tr><td colspan="7" class="text-center py-4 text-muted">ไม่พบข้อมูล Batch ในระบบ Smart Money</td></tr>';
                } else {
                    batches.forEach(function(b, idx) {
                        var netAmt = parseFloat(b.net_amount || b.amount || 0);
                        var transferDate = b.transfer_date ? b.transfer_date : '-';
                        var receiptBadge = b.receive_no 
                            ? `<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1"><i class="bi bi-check-circle-fill me-0.5"></i> ${b.receive_no}</span>`
                            : `<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-0.5"></i> ยังไม่ออก</span>`;
                        
                        var receiptMeta = '-';
                        if (b.receive_no) {
                            var rDate = b.receipt_date || '-';
                            var rBy = b.receipt_by || '-';
                            receiptMeta = `<div class="small fw-semibold text-dark">${rDate}</div><div class="text-muted" style="font-size: 11px;">${rBy}</div>`;
                        }

                        rows += `<tr>
                            <td class="ps-3 fw-bold text-muted">${idx + 1}</td>
                            <td><div class="small fw-semibold text-dark">${transferDate}</div></td>
                            <td class="text-center"><span class="badge bg-secondary-subtle text-dark border px-2 py-1 font-monospace">${b.batch_no || '-'}</span></td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 13px;">${b.round_no || '-'}</div>
                                <div class="text-muted" style="font-size: 11px;">${b.fund_main || ''} ${b.fund_sub ? '- ' + b.fund_sub : ''}</div>
                                ${b.account_code ? `<div class="text-muted font-monospace" style="font-size: 10px;">ผัง: ${b.account_code}</div>` : ''}
                            </td>
                            <td class="text-end fw-bold text-teal" style="color: #0d9488;">
                                ${netAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                            <td class="text-center">${receiptBadge}</td>
                            <td>${receiptMeta}</td>
                        </tr>`;
                    });
                }

                $('#smBatchesModalTableBody').html(rows);

                var modalEl = document.getElementById('smBatchesModal');
                var modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            // Filter only unreceipted files
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (settings.sTableId !== 'stm_ucs') return true;
                    if ($('#filterUnreceiptedOnly').is(':checked')) {
                        var rowNode = settings.aoData[dataIndex].nTr;
                        var isReceipted = $(rowNode).attr('data-receipt') === '1';
                        return !isReceipted;
                    }
                    return true;
                }
            );

            $('#filterUnreceiptedOnly').on('change', function() {
                stmTable.draw();
            });

            // --- Chart Modal Handling ---
            let monthlyChart = null;

            // Load ApexCharts CDN dynamically if it isn't loaded
            if (typeof ApexCharts === 'undefined') {
                const chartScript = document.createElement('script');
                chartScript.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                chartScript.onload = function() {
                    initChartEvent();
                };
                document.head.appendChild(chartScript);
            } else {
                initChartEvent();
            }

            function initChartEvent() {
                $('#chartModal').on('shown.bs.modal', function () {
                    loadChartData();
                });

                $('#modal_filter_budget_year').on('change', function () {
                    loadChartData();
                });
            }

            function loadChartData() {
                const budgetYear = $('#modal_filter_budget_year').val();
                const budgetYearText = $('#modal_filter_budget_year option:selected').text().trim();

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายงวด Statement ประกันสุขภาพ UCS ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.stm_ucs.chart-data') }}",
                    method: "GET",
                    data: {
                        budget_year: budgetYear
                    },
                    success: function (res) {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        renderChart(res.labels, res.op_totals, res.ip_totals);
                    },
                    error: function () {
                        $('#loading_spinner').addClass('d-none');
                        $('#chart_container').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลกราฟได้', confirmButtonColor: '#d33' });
                    }
                });
            }

            function renderChart(labels, opTotals, ipTotals) {
                if (monthlyChart) {
                    monthlyChart.destroy();
                }

                const options = {
                    series: [
                        {
                            name: 'OP (ผู้ป่วยนอก)',
                            data: opTotals
                        },
                        {
                            name: 'IP (ผู้ป่วยใน)',
                            data: ipTotals
                        }
                    ],
                    chart: {
                        height: 430,
                        type: 'area',
                        toolbar: { show: false }
                    },
                    markers: { size: 4 },
                    colors: ['#10b981', '#ef4444'],
                    fill: {
                        type: "gradient",
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.3,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val ? new Intl.NumberFormat('th-TH').format(val) : '';
                        },
                        style: {
                            fontSize: '11px',
                            fontWeight: 'bold'
                        }
                    },
                    stroke: { 
                        curve: 'smooth', 
                        width: 2 
                    },
                    xaxis: {
                        categories: labels
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                return value.toLocaleString('th-TH') + ' ฿';
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return new Intl.NumberFormat('th-TH').format(val) + ' บาท';
                            }
                        }
                    }
                };

                monthlyChart = new ApexCharts(document.querySelector("#monthlySummaryChart"), options);
                monthlyChart.render();
            }
        });
    
        // Deletion handler for STM index
        $(document).on('click', '.btn-action-delete', function () {
            var btn = $(this);
            var filename = btn.data('filename');
            var type = btn.data('type');

            Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: 'คุณต้องการลบข้อมูลนำเข้าของไฟล์ ' + filename + ' ใช่หรือไม่? การลบนี้ไม่สามารถย้อนกลับได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังลบข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: "{{ route('import.stm.delete') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            filename: filename,
                            type: type
                        },
                        success: function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: res.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ผิดพลาด',
                                    text: res.message || 'เกิดข้อผิดพลาดในการลบข้อมูล',
                                    confirmButtonText: 'ปิด',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function (xhr) {
                            var errMsg = 'เกิดข้อผิดพลาดในการลบข้อมูล';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errMsg = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'ผิดพลาด',
                                text: errMsg,
                                confirmButtonText: 'ปิด',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });

        // ==========================================
        // e-Claim Automation (Session Cookie / Direct Sync)
        // ==========================================
        // 2) Check e-Claim Authentication Status
        // ==========================================
        var eclaimUcsIsChecking = false;
        var eclaimUcsIsConnected = false;

        function checkEclaimStatus(silent = false) {
            if (eclaimUcsIsChecking) return;
            eclaimUcsIsChecking = true;

            if (!silent && !eclaimUcsIsConnected) {
                $('#eclaimAuthStatusIcon').removeClass('bg-success-subtle text-success bg-warning-subtle text-warning')
                    .addClass('bg-secondary-subtle text-secondary')
                    .html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                $('#eclaimAuthStatusText').text('กำลังตรวจสอบสถานะการเชื่อมต่อ e-Claim...');
                $('#eclaimAuthStatusSub').text('ระบบกำลังทดสอบ Session กับ eclaim.nhso.go.th');
                $('#btnBotSearch').prop('disabled', true);
            }

            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function(res) {
                    eclaimUcsIsChecking = false;
                    if (res && res.connected) {
                        eclaimUcsIsConnected = true;
                        if (window.eclaimRetryTimer_checkEclaimStatus) {
                            clearInterval(window.eclaimRetryTimer_checkEclaimStatus);
                            window.eclaimRetryTimer_checkEclaimStatus = null;
                        }

                        $('#eclaimAuthStatusIcon').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary')
                            .addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#eclaimAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary">' + (res.user || 'ผู้ใช้งาน e-Claim') + '</span>');
                        $('#eclaimAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + (res.connected_at || '{{ date("Y-m-d H:i:s") }}'));
                        $('#btnEclaimLogout').removeClass('d-none');
                        $('#btnBotSearch').prop('disabled', false);
                    } else {
                        eclaimUcsIsConnected = false;
                        $('#eclaimAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                            .addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#eclaimAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ e-Claim หรือ Session หมดอายุ');
                        $('#eclaimAuthStatusSub').text(res.message || 'เปิดเว็บ e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใน Extension เพื่อเริ่มดึงข้อมูล');
                        $('#btnEclaimLogout').addClass('d-none');
                        $('#btnBotSearch').prop('disabled', true);

                        if (!window.eclaimRetryTimer_checkEclaimStatus && $('#eclaimBotModal').hasClass('show')) {
                            window.eclaimRetryTimer_checkEclaimStatus = setInterval(function() {
                                if ($('#eclaimBotModal').hasClass('show') && !eclaimUcsIsConnected) {
                                    checkEclaimStatus(true);
                                } else {
                                    clearInterval(window.eclaimRetryTimer_checkEclaimStatus);
                                    window.eclaimRetryTimer_checkEclaimStatus = null;
                                }
                            }, 4000);
                        }
                    }
                },
                error: function() {
                    eclaimUcsIsChecking = false;
                    eclaimUcsIsConnected = false;
                    $('#eclaimAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                        .addClass('bg-warning-subtle text-warning')
                        .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                    $('#eclaimAuthStatusText').text('ไม่สามารถตรวจสอบสถานะการเชื่อมต่อ e-Claim ได้');
                    $('#eclaimAuthStatusSub').text('กรุณาเปิดหน้า e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใหม่อีกครั้ง');
                    $('#btnEclaimLogout').addClass('d-none');
                    $('#btnBotSearch').prop('disabled', true);
                }
            });
        }

        $('#eclaimBotModal').on('show.bs.modal', function () {
            checkEclaimStatus(false);
        });

        $('#eclaimBotModal').on('hidden.bs.modal', function () {
            if (window.eclaimRetryTimer_checkEclaimStatus) {
                clearInterval(window.eclaimRetryTimer_checkEclaimStatus);
                window.eclaimRetryTimer_checkEclaimStatus = null;
            }
        });

        $(window).on('focus', function () {
            if ($('#eclaimBotModal').hasClass('show') && !eclaimUcsIsConnected) {
                checkEclaimStatus(true);
            }
        });

        window.showEclaimExtensionGuide = function() {
            var apiUrl = "{{ url('/api') }}";
            var zipUrl = "{{ url('downloads/eclaim_sync.zip') }}";
            
            Swal.fire({
                title: '<div class="d-flex align-items-center justify-content-center gap-2 text-primary fs-5 fw-bold"><i class="bi bi-puzzle-fill"></i> คู่มือการติดตั้ง RiMS Chrome Extension</div>',
                html: `
                    <div class="text-start small text-secondary mb-3" style="line-height: 1.6;">
                        <b class="text-dark">ขั้นตอนการใช้งาน:</b>
                        <ol class="ps-3 mb-3 mt-1">
                            <li>คลิกปุ่มสีน้ำเงินด้านล่างเพื่อ <b>ดาวน์โหลดไฟล์ eclaim_sync.zip</b></li>
                            <li>แตกไฟล์ zip ไว้ในโฟลเดอร์ที่ต้องการในเครื่อง</li>
                            <li>เปิด Chrome ไปที่ <code>chrome://extensions</code> เปิด <b>Developer mode</b> แล้วกด <b>Load unpacked</b> เลือกโฟลเดอร์ที่แตกไว้</li>
                            <li>เปิดส่วนเสริม นำ <b>Server API URL</b> ด้านล่างนี้ไปวางในช่อง URL แล้วกดบันทึก:</li>
                        </ol>
                    </div>
                    <div class="card bg-light border-0 p-3 mb-3 rounded-3 text-start shadow-sm">
                        <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-link-45deg"></i> Server API URL สำหรับคัดลอกไปวางใน Extension:</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm font-monospace bg-white fw-bold text-primary" id="rimsApiUrlInput" value="${apiUrl}" readonly>
                            <button class="btn btn-sm btn-primary px-3" type="button" id="btnCopyApiUrl" onclick="navigator.clipboard.writeText('${apiUrl}'); this.innerHTML='<i class=\\'bi bi-check-lg me-1\\'></i> คัดลอกแล้ว!'; setTimeout(() => { this.innerHTML='<i class=\\'bi bi-clipboard me-1\\'></i> คัดลอก'; }, 2000);">
                                <i class="bi bi-clipboard me-1"></i> คัดลอก
                            </button>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="${zipUrl}" class="btn btn-primary rounded-pill py-2 shadow-sm fw-bold" download>
                            <i class="bi bi-download me-1"></i> ดาวน์โหลดไฟล์ส่วนเสริม (eclaim_sync.zip)
                        </a>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'ปิดหน้าต่าง',
                confirmButtonColor: '#6c757d',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            });
        };

        $('#btnEclaimLoginPopup').on('click', function () {
            openEclaimThaidQrModal(checkEclaimStatus);
        });

        $('#btnEclaimLogout').on('click', function () {
            Swal.fire({
                title: 'ยืนยันตัดการเชื่อมต่อ?',
                text: 'ระบบจะล้าง Session e-Claim ออกจากระบบทุกหน้าจอ',
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
                            checkEclaimStatus();
                            $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted">กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ Statement</td></tr>');
                            Swal.fire({
                                icon: 'success',
                                title: 'ตัดการเชื่อมต่อแล้ว',
                                text: 'ตัดการเชื่อมต่อกับ e-Claim เรียบร้อย',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        // Auto-connect if URL has ?eclaim_token=...
        var urlParams = new URLSearchParams(window.location.search);
        var urlToken = urlParams.get('eclaim_token');
        if (urlToken) {
            $.ajax({
                url: "{{ route('import.eclaim-bot.save-token') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    token: urlToken
                },
                success: function (res) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                    $('#eclaimBotModal').modal('show');
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ!',
                        text: 'เชื่อมต่อกับระบบ e-Claim เรียบร้อยแล้ว พร้อมค้นหาและนำเข้าข้อมูลได้ทันที',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    checkEclaimStatus();
                }
            });
        }

        // 5) Search Statements in e-Claim
        $('#btnBotSearch').on('click', function () {
            var btn = $(this);
            var year = $('#botBudgetYear').val();
            var month = $('#botMonth').val();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังค้นหา...');
            $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>กำลังดึงข้อมูลจาก e-Claim...</td></tr>');

            $.ajax({
                url: "{{ route('import.eclaim-bot.search') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    budget_year: year,
                    month: month,
                    claim_type: 'stm_ucs'
                },
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> ค้นหาใน e-Claim');
                    if (res.status === 'success') {
                        $('#botResultBadge').text('พบ ' + res.count + ' รายการ');
                        renderBotStatementTable(res.data);
                    } else {
                        var errMsg = res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim';
                        $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-exclamation-octagon fs-4 d-block mb-1"></i>' + errMsg + '</td></tr>');
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> ค้นหาใน e-Claim');
                    var errMsg = 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-exclamation-octagon fs-4 d-block mb-1"></i>' + errMsg + '</td></tr>');
                }
            });
        });

        function renderBotStatementTable(data) {
            if (!data || data.length === 0) {
                $('#botStatementTableBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><i class="bi bi-info-circle fs-4 d-block mb-1"></i>ไม่พบข้อมูล Statement ในงวดเดือนที่เลือกจาก e-Claim</td></tr>');
                $('#btnStartImportBot').prop('disabled', true);
                return;
            }

            var html = '';
            data.forEach(function (row, idx) {
                var badgeStatus = row.is_imported
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1"><i class="bi bi-check-circle-fill me-1"></i>นำเข้าแล้ว (' + Number(row.imported_count).toLocaleString() + ' ราย)</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2.5 py-1"><i class="bi bi-clock-history me-1"></i>ยังไม่เคยนำเข้า</span>';

                var chargeDisplay = row.charge_total > 0 ? Number(row.charge_total).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-';
                var receiveDisplay = row.receive_total > 0 ? Number(row.receive_total).toLocaleString(undefined, {minimumFractionDigits: 2}) : '-';
                var countDisplay = row.count_cid && row.count_cid !== '-' ? Number(row.count_cid).toLocaleString() : '-';

                var itemJson = JSON.stringify(row).replace(/"/g, '&quot;');

                html += '<tr>' +
                    '<td class="text-center"><input class="form-check-input bot-row-check" type="checkbox" value="' + row.round_no + '" data-item="' + itemJson + '"></td>' +
                    '<td class="text-center fw-bold text-primary">' + row.round_no + '</td>' +
                    '<td class="fw-bold small text-dark">' + row.filename + '</td>' +
                    '<td class="text-center"><span class="badge ' + (row.type === 'OPD' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger') + ' rounded-pill px-2">' + row.type + '</span></td>' +
                    '<td class="text-center small text-muted">' + row.issue_date + '</td>' +
                    '<td class="text-end fw-bold">' + countDisplay + '</td>' +
                    '<td class="text-end text-muted">' + chargeDisplay + '</td>' +
                    '<td class="text-end text-success fw-bold">' + receiveDisplay + '</td>' +
                    '<td class="text-center">' + badgeStatus + '</td>' +
                '</tr>';
            });

            $('#botStatementTableBody').html(html);
            updateSelectedCount();
        }

        // Checkbox events
        $('#checkAllBot').on('change', function () {
            $('.bot-row-check').prop('checked', $(this).prop('checked'));
            updateSelectedCount();
        });

        $(document).on('change', '.bot-row-check', function () {
            updateSelectedCount();
        });

        function updateSelectedCount() {
            var selected = $('.bot-row-check:checked').length;
            $('#selectedCountText').text('เลือก ' + selected + ' รายการ');
            $('#btnStartImportBot').prop('disabled', selected === 0);
        }

        // 6) Trigger Auto Import from Bot
        $('#btnStartImportBot').on('click', function () {
            var selectedItems = [];
            $('.bot-row-check:checked').each(function () {
                var itemData = $(this).data('item');
                if (typeof itemData === 'string') {
                    try { itemData = JSON.parse(itemData); } catch(e) {}
                }
                selectedItems.push(itemData);
            });

            if (selectedItems.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกไฟล์ Statement อย่างน้อย 1 รายการ', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการนำเข้าข้อมูล?',
                text: 'ระบบจะทำการดาวน์โหลดไฟล์จาก e-Claim และนำเข้า Statement จำนวน ' + selectedItems.length + ' งวดเข้าสู่ฐานข้อมูล RIMS ทันที',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '🚀 เริ่มนำเข้าทันที',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    var total = selectedItems.length;
                    var successCount = 0;
                    var failedCount = 0;

                    Swal.fire({
                        title: 'กำลังดาวน์โหลดและนำเข้าข้อมูล...',
                        html: `
                            <div class="my-3 text-start">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span id="importStmProgressStatusText" class="small fw-bold text-dark text-truncate" style="max-width: 250px;">กำลังเริ่มต้น...</span>
                                    <span id="importStmProgressPercentText" class="small fw-bold text-success">0%</span>
                                </div>
                                <div class="progress" style="height: 22px; border-radius: 11px; background-color: #e2e8f0;">
                                    <div id="importStmProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-size: 11.5px; font-weight: bold;">0%</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                                    <span id="importStmTimer"><i class="bi bi-clock-history me-1"></i> เวลา: 0 วิ</span>
                                    <span id="importStmProgressDetail">สำเร็จ <b id="importStmSuccessCount" class="text-success">0</b> / ${total} ไฟล์</span>
                                </div>
                                <div class="alert alert-light border py-1.5 px-2 mt-2 mb-0 small text-muted" style="font-size: 11px;">
                                    <i class="bi bi-info-circle text-primary me-1"></i> ระบบกำลังดาวน์โหลด Statement Excel จาก e-Claim และประมวลผลเข้าฐานข้อมูล
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: async () => {
                            var elapsedSec = 0;
                            var timerInterval = setInterval(function () {
                                elapsedSec++;
                                $('#importStmTimer').html('<i class="bi bi-clock-history me-1"></i> เวลา: ' + elapsedSec + ' วิ');
                            }, 1000);

                            for (var i = 0; i < total; i++) {
                                var item = selectedItems[i];
                                var itemLabel = item.round_no ? ('งวดที่ ' + item.round_no) : (item.filename ? item.filename : ('ไฟล์ที่ ' + (i + 1)));
                                
                                $('#importStmProgressStatusText').text(`กำลังดึง ${itemLabel} (${i + 1}/${total})`);
                                
                                try {
                                    var res = await $.ajax({
                                        url: "{{ route('import.eclaim-bot.import') }}",
                                        method: "POST",
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            items: [item]
                                        }
                                    });
                                    
                                    if (res && res.status === 'success') {
                                        successCount++;
                                    } else {
                                        failedCount++;
                                    }
                                } catch (err) {
                                    console.error("Error importing STM item: ", item, err);
                                    failedCount++;
                                }
                                
                                var percent = Math.round(((i + 1) / total) * 100);
                                $('#importStmProgressBar').css('width', percent + '%').text(percent + '%');
                                $('#importStmProgressPercentText').text(percent + '%');
                                $('#importStmSuccessCount').text(successCount);
                            }

                            clearInterval(timerInterval);
                            
                            if (successCount > 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'นำเข้าสำเร็จ!',
                                    html: `นำเข้าข้อมูล Statement UCS จาก e-Claim สำเร็จรวม <b>${successCount}</b> ไฟล์ ${failedCount > 0 ? `<br><span class="text-danger small">(ไม่สำเร็จ ${failedCount} ไฟล์)</span>` : ''}`,
                                    confirmButtonText: 'ตกลง',
                                    confirmButtonColor: '#10b981'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ไม่สำเร็จ',
                                    text: 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement ที่เลือกได้ กรุณาตรวจสอบ Session e-Claim',
                                    confirmButtonText: 'ปิด',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        }
                    });
                }
            });
        });
    </script>
@endpush
