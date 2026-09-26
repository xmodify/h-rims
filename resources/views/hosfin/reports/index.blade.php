@extends('layouts.app')

@section('content')
<style>

  .report-card {
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    overflow: hidden;
    position: relative;
  }
  .report-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px -6px rgba(139, 92, 246, 0.15);
    border-color: #c4b5fd;
  }
  .report-card .card-icon-wrapper {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.25s ease;
  }
  .report-card:hover .card-icon-wrapper {
    transform: scale(1.08);
  }

  /* Table Style */
  .table-service-report {
    border: 1px solid #cbd5e1;
    font-size: 0.92rem;
    border-collapse: separate;
    border-spacing: 0;
  }
  .table-service-report thead th {
    background-color: #3b6287 !important;
    color: #ffffff !important;
    font-weight: 700;
    padding: 10px 14px;
    border-bottom: 2px solid #28445e;
    white-space: nowrap;
  }
  .table-service-report tbody td {
    padding: 7px 14px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
  }
  .table-service-report tbody tr:nth-child(even) {
    background-color: #f8fafc;
  }
  .table-service-report tbody tr:hover {
    background-color: #f1f5f9;
  }
  .table-service-report .input-amt {
    font-size: 0.95rem;
    font-weight: 700;
    border: 1.5px solid #94a3b8;
    border-radius: 4px;
    padding: 4px 10px;
    background-color: #ffffff;
    transition: all 0.2s;
  }
  .table-service-report .input-amt:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    background-color: #eff6ff;
  }
  .btn-copy-mini {
    padding: 3px 8px;
    font-size: 0.75rem;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    transition: all 0.15s ease;
  }
  .btn-copy-mini:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
  }
  .datepicker {
    z-index: 1600 !important;
    font-family: inherit;
  }
  .hover-shadow {
    transition: all 0.2s ease-in-out;
  }
  .hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
  }
  .hfa-cat-btn.active {
    background-color: #0d9488 !important;
    color: #ffffff !important;
    border-color: #0d9488 !important;
    font-weight: 700;
  }
  .tb-scroll-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }
  .tb-scroll-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }
</style>

<div class="container-fluid pt-2 pb-5 px-lg-5" style="background-color: #f8fafc; min-height: 90vh;">
    <div class="row">
        <!-- Back Button -->
        <div class="col-12 px-3 mb-1">
            <a href="{{ url('hosfin') }}{{ isset($budgetYear) ? '?budget_year='.$budgetYear : '' }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 d-inline-flex align-items-center gap-2" style="font-size: 0.85rem; border-color: #cbd5e1; color: #475569; background-color: #fff;" title="ย้อนกลับ HosFin Dashboard">
                <i class="bi bi-arrow-left"></i> ย้อนกลับ
            </a>
        </div>

        <!-- Header Banner -->
        <div class="col-12 px-3 mb-3">
            <div class="page-header-box mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2" 
                 style="border-left: 4px solid #8b5cf6 !important; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); padding: 16px 22px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div>
                    <h5 class="mb-0 fw-bold d-flex align-items-center gap-2" style="color: #7c3aed;">
                        <i class="bi bi-file-earmark-bar-graph fs-4"></i> ศูนย์รวมรายงานการเงินและข้อมูลบริการ (Reports Hub)
                    </h5>
                    <small class="text-muted">รายงานข้อมูลบริการประกอบงบ (HOSxP) และระบบข้อมูลการเงินการคลัง (HFA Open-API)</small>
                </div>

                @include('hosfin.partials.header_nav')
            </div>
        </div>

        <!-- Reports Grid Catalog -->
        <div class="col-12 px-3">
            <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-grid-fill text-primary" style="font-size: 1.05rem;"></i> เลือกรายงานที่ต้องการ
            </h5>

            <div class="row g-3">
                <!-- Report Card 1: รายงานข้อมูลบริการประกอบงบ -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 report-card p-3 shadow-xs" 
                         style="border-top: 4px solid #3b82f6 !important; cursor: default;">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="card-icon-wrapper" style="background: #eff6ff; color: #2563eb;">
                                <i class="bi bi-clipboard2-data-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge rounded-pill px-2 py-0.5" style="font-size: 0.72rem; background-color: #dbeafe; color: #1d4ed8; font-weight: 600;">
                                        <i class="bi bi-database-fill-gear me-0.5"></i> HOSxP Source
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.72rem;">Hospital Information System</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 fs-6">รายงานข้อมูลบริการประกอบงบ</h6>
                                <p class="text-muted small mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                                    รหัสบัญชี 11-45 ข้อมูลผู้ป่วยนอก (OPD), ผู้ป่วยใน (IPD), วันนอน, เบาหวาน-ความดัน และ SumAdjRW
                                </p>
                            </div>
                        </div>

                        <!-- Inside Action Links -->
                        <div class="d-flex flex-column gap-2 pt-2 mt-auto border-top">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm rounded-3 py-2 px-3 d-flex justify-content-between align-items-center text-start w-100 shadow-none hover-shadow" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#serviceDataModal" 
                                    style="border-color: #bfdbfe; background-color: #eff6ff;">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; background-color: #dbeafe; color: #2563eb;">
                                        <i class="bi bi-clipboard2-pulse-fill fs-6"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">1. ข้อมูลบริการประกอบงบ (รายเดือน / ไตรมาส)</div>
                                        <div class="text-muted" style="font-size: 0.73rem;">ประมวลผลดึงข้อมูลสดจาก HOSxP พร้อมส่งออกไฟล์ Excel</div>
                                    </div>
                                </div>
                                <span class="badge bg-primary rounded-pill px-2.5 py-1">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดหน้าต่างรายงาน
                                </span>
                            </button>

                            <div class="d-flex align-items-center justify-content-between px-3 py-2 rounded-3" style="background-color: #f8fafc; border: 1px dashed #cbd5e1; min-height: 48px;">
                                <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.78rem;">
                                    <i class="bi bi-check2-circle text-success fs-6"></i>
                                    <span>ตรวจสอบข้อมูลบริการประกอบงบการเงินและคำนวณ SumAdjRW</span>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">HOSxP Live Data</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Card 2: ระบบข้อมูลการเงินการคลัง (HFA) -->
                <div class="col-12 col-lg-6">
                    <div class="card h-100 report-card p-3 shadow-xs" 
                         style="border-top: 4px solid #0d9488 !important; cursor: default;">
                        <div class="d-flex align-items-start gap-3 mb-2">
                            <div class="card-icon-wrapper" style="background: #f0fdfa; color: #0d9488;">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge rounded-pill px-2 py-0.5" style="font-size: 0.72rem; background-color: #ccfbf1; color: #0f766e; font-weight: 600;">
                                        <i class="bi bi-link-45deg me-0.5"></i> HFA Open-API
                                    </span>
                                    <span class="text-muted small" style="font-size: 0.72rem;">Health Financial Analysis</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1 fs-6">ระบบข้อมูลการเงินการคลัง (HFA)</h6>
                                <p class="text-muted small mb-0" style="font-size: 0.82rem; line-height: 1.45;">
                                    รายงานข้อมูลบริการ 54 รายการ และงบทดลอง 803 บัญชี พร้อมส่งออก Excel และเชื่อมโยงส่ง API เข้าสู่ระบบ HFA
                                </p>
                            </div>
                        </div>

                        <!-- Inside Action Links -->
                        <div class="d-flex flex-column gap-2 pt-2 mt-auto border-top">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm rounded-3 py-2 px-3 d-flex justify-content-between align-items-center text-start w-100 shadow-none hover-shadow" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#hfaServiceModal" 
                                    style="border-color: #99f6e4; background-color: #f0fdfa;">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; background-color: #ccfbf1; color: #0d9488;">
                                        <i class="bi bi-person-lines-fill fs-6"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">1. ข้อมูลบริการ (HFA Service)</div>
                                        <div class="text-muted" style="font-size: 0.73rem;">54 รายการ (OPV, OPH, IPA, IPH, IPS, IPL) 9 สิทธิ</div>
                                    </div>
                                </div>
                                <span class="badge rounded-pill px-2.5 py-1" style="background-color: #0d9488; color: #fff;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดหน้าต่างรายงาน
                                </span>
                            </button>

                            <button type="button" 
                                    class="btn btn-outline-success btn-sm rounded-3 py-2 px-3 d-flex justify-content-between align-items-center text-start w-100 shadow-none hover-shadow" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#hfaTrialBalanceModal" 
                                    style="border-color: #bfdbfe; background-color: #eff6ff;">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; background-color: #dbeafe; color: #2563eb;">
                                        <i class="bi bi-file-earmark-spreadsheet-fill fs-6"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;">2. ข้อมูลงบทดลอง (HFA Trial Balance)</div>
                                        <div class="text-muted" style="font-size: 0.73rem;">งบทดลองมาตรฐาน 803 บัญชี ตรวจสอบดุลบัญชี</div>
                                    </div>
                                </div>
                                <span class="badge bg-primary rounded-pill px-2.5 py-1">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดหน้าต่างรายงาน
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: รายงานข้อมูลบริการประกอบงบ (ดึงจาก HOSxP) -->
<!-- ========================================================================= -->
<div class="modal fade" id="serviceDataModal" tabindex="-1" aria-labelledby="serviceDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #2b4c7e 0%, #1e3a5f 100%);">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2 mb-0" id="serviceDataModalLabel">
                        <i class="bi bi-clipboard-data-fill text-warning"></i> 
                        รายงานข้อมูลบริการประกอบงบ
                    </h5>
                    <small class="text-white-50" style="font-size: 0.82rem;">
                        ประมวลผลดึงข้อมูลจาก HOSxP
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Filter Section: ช่วงวันที่ และ ไตรมาส + ปุ่มประมวลผล -->
                <div class="card border-0 shadow-sm rounded-3 mb-3 p-3" style="background: #ffffff; border-left: 4px solid #3b82f6 !important;">
                    <div class="row g-3 align-items-end">
                        <!-- Date Range (Monthly) -->
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                                <i class="bi bi-calendar-range text-primary me-1"></i> ช่วงวันที่ (ข้อมูลประจำเดือน):
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary" style="font-size: 0.8rem;"><i class="bi bi-calendar-event me-1 text-primary"></i>จาก</span>
                                <input type="hidden" id="filter_start_date" value="{{ $defaultStartDate }}">
                                <input type="text" class="form-control datepicker_th text-center fw-bold" id="filter_start_date_picker" readonly style="background-color: #fff; cursor: pointer;" placeholder="วว/ดด/ปปปป">
                                <span class="input-group-text bg-light text-secondary" style="font-size: 0.8rem;">ถึง</span>
                                <input type="hidden" id="filter_end_date" value="{{ $defaultEndDate }}">
                                <input type="text" class="form-control datepicker_th text-center fw-bold" id="filter_end_date_picker" readonly style="background-color: #fff; cursor: pointer;" placeholder="วว/ดด/ปปปป">
                            </div>
                        </div>

                        <!-- Budget Year & Quarter -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.84rem;">
                                <i class="bi bi-pie-chart text-primary me-1"></i> ไตรมาส (ยอดสะสมไตรมาส):
                            </label>
                            <div class="input-group input-group-sm">
                                <select class="form-select fw-bold" id="filter_budget_year" style="max-width: 105px;">
                                    @foreach($budgetYearChoices as $by)
                                        <option value="{{ $by }}" {{ $budgetYear == $by ? 'selected' : '' }}>{{ $by }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select fw-bold" id="filter_quarter">
                                    <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>ไตรมาส 1 (ต.ค.-ธ.ค.)</option>
                                    <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>ไตรมาส 2 (ม.ค.-มี.ค.)</option>
                                    <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>ไตรมาส 3 (เม.ย.-มิ.ย.)</option>
                                    <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>ไตรมาส 4 (ก.ค.-ก.ย.)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Process Button -->
                        <div class="col-12 col-md-3 text-end">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm w-100 d-flex align-items-center justify-content-center gap-1.5" 
                                    id="btnProcessHosxp" 
                                    style="height: 36px; font-size: 0.88rem; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none;">
                                <i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล
                            </button>
                        </div>
                    </div>

                    <!-- Quarter Info Hint -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 pt-2 border-top" style="font-size: 0.76rem;">
                        <span class="text-muted">
                            <i class="bi bi-info-circle me-1 text-primary"></i> 
                            รายการ 11-15, 21-25, 31, 41-45 คำนวณจาก <strong>ช่วงวันที่</strong> | รายการ 16, 32 คำนวณจาก <strong>ไตรมาส</strong>
                        </span>
                        <span id="quarterInfoBadge" class="badge bg-light text-secondary border">
                            {{ $quarterDates['label'] ?? '' }}
                        </span>
                    </div>
                </div>

                <!-- Table Form Container -->
                <div id="modalTableContainer">
                    <div class="table-responsive rounded shadow-xs mb-3 bg-white" style="border: 1px solid #cbd5e1;">
                        <table class="table table-hover table-service-report align-middle mb-0" id="tblServiceData">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 110px;">รหัสบัญชี</th>
                                    <th>ชื่อบัญชี</th>
                                    <th class="text-center" style="width: 260px;">จำนวน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accounts as $code => $acc)
                                    <tr id="row_{{ $code }}">
                                        <td class="text-center fw-bold text-secondary" style="font-family: monospace; font-size: 0.95rem;">
                                            {{ $code }}
                                        </td>
                                        <td class="fw-bold text-dark" style="font-size: 0.88rem;">
                                            {{ $acc['name'] }}
                                            @if($acc['type'] === 'quarterly')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-1.5 py-0.2 ms-1" style="font-size: 0.68rem;">ไตรมาส</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center gap-1.5 justify-content-end">
                                                <input type="text" 
                                                       class="form-control text-end input-amt" 
                                                       id="amt_{{ $code }}" 
                                                       placeholder="0"
                                                       style="height: 32px;"
                                                       onfocus="this.select();">
                                                <button type="button" 
                                                        class="btn btn-copy-mini shadow-xs" 
                                                        onclick="copySingleValue('{{ $code }}')"
                                                        title="คัดลอกตัวเลข">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Status on the left -->
                <div>
                    <span class="text-muted small" id="lastUpdatedStatus">
                        <i class="bi bi-info-circle me-1"></i> กดปุ่ม "ประมวลผล" เพื่อดึงข้อมูลสดจาก HOSxP
                    </span>
                </div>

                <!-- Buttons on the right -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnPrintReport" style="height: 36px;">
                        <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
                    </button>

                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnExportExcel" style="height: 36px;">
                        <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 fw-bold" data-bs-dismiss="modal" style="height: 36px;">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: ข้อมูลบริการสำหรับ HFA (54 รายการ) -->
<!-- ========================================================================= -->
<div class="modal fade" id="hfaServiceModal" tabindex="-1" aria-labelledby="hfaServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #0d9488 0%, #1e3a8a 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(255,255,255,0.15);">
                        <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="hfaServiceModalLabel">ข้อมูลบริการสำหรับ HFA (Health Financial Analysis)</h5>
                        <p class="mb-0 text-white-50 small" style="font-size: 0.82rem;">
                            ข้อมูลการให้บริการ 54 รายการ (OPV, OPH, IPA, IPH, IPS, IPL) จำแนก 9 สิทธิการรักษา
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Filter Controls Bar: ช่วงวันที่ (ข้อมูลประจำเดือน) แบบรายงานประกอบงบ -->
            <!-- Filter Section: ปีงบประมาณ & งวดที่ต้องการนำเข้า (แบบ HFA) + ช่วงวันที่คำนวณอัตโนมัติ -->
            <div class="px-4 py-3 border-bottom" style="background-color: #f8fafc;">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #ffffff; border-left: 4px solid #0d9488 !important;">
                    <div class="row g-3 align-items-start">
                        <!-- Budget Year (Compact Width) -->
                        <div class="col-12 col-sm-auto" style="width: 140px;">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-calendar-check me-1" style="color: #0d9488;"></i>ปีงบประมาณ:
                            </label>
                            <select id="hfa_service_fiscal_year" class="form-select form-select-sm fw-bold text-primary shadow-xs" style="border-color: #cbd5e1;">
                                @foreach($budgetYearChoices as $byChoice)
                                    <option value="{{ $byChoice }}" {{ $byChoice == $budgetYear ? 'selected' : '' }}>{{ $byChoice }}</option>
                                @endforeach
                            </select>
                            <div id="hfa_fy_hint" class="text-muted mt-1 text-nowrap" style="font-size: 0.71rem;">
                                (งวด ต.ค.{{ $budgetYear - 1 }} - ก.ย.{{ $budgetYear }})
                            </div>
                        </div>

                        <!-- Period (Compact Width) -->
                        <div class="col-12 col-sm-auto" style="width: 230px;">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-calendar3 me-1" style="color: #0d9488;"></i>งวดที่ต้องการนำเข้า:
                            </label>
                            <select id="hfa_service_period" class="form-select form-select-sm fw-bold shadow-xs" style="border-color: #cbd5e1;">
                                <!-- Generated dynamically based on selected fiscal year -->
                            </select>
                            <div class="mt-1 d-flex align-items-center gap-1.5 flex-wrap" style="font-size: 0.72rem;">
                                <span class="badge px-2 py-0.5 rounded" style="background-color: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1;">
                                    <i class="bi bi-calendar2-range me-1"></i><span id="hfa_period_date_range_label">-</span>
                                </span>
                            </div>
                            <!-- Hidden ISO Date inputs for backend query -->
                            <input type="hidden" id="hfa_service_start_date" value="{{ $defaultStartDate }}">
                            <input type="hidden" id="hfa_service_end_date" value="{{ $defaultEndDate }}">
                        </div>

                        <!-- Process Button (Placed right beside Period dropdown) -->
                        <div class="col-12 col-sm-auto">
                            <label class="form-label d-none d-sm-block mb-1" style="font-size: 0.83rem; visibility: hidden;">&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" 
                                    id="btnProcessHfaService" 
                                    style="height: 33px; font-size: 0.86rem; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border: none; white-space: nowrap;">
                                <i class="bi bi-play-circle-fill fs-6"></i> ประมวลผลจาก HOSxP
                            </button>
                            <div class="mt-1 d-none d-sm-block" style="font-size: 0.72rem; visibility: hidden;">&nbsp;</div>
                        </div>

                        <!-- Status Message (Right beside button) -->
                        <div class="col-12 col-md align-self-center mt-1 mt-md-0 ps-md-2">
                            <div id="hfaServiceStatus" class="small text-muted text-break">
                                พร้อมประมวลผล
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Quick Filters -->
                <div class="d-flex align-items-center gap-1.5 flex-wrap mt-2.5 pt-2 border-top">
                    <span class="text-muted small me-1" style="font-size: 0.78rem;"><i class="bi bi-funnel-fill text-teal me-1"></i>ตัวกรอง:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary hfa-cat-btn active py-0.5 px-2.5 rounded-pill" data-cat="all" style="font-size: 0.78rem;">ทั้งหมด (54)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="OPV" style="font-size: 0.78rem;">OPV ครั้ง OPD (9)</button>
                    <button type="button" class="btn btn-sm btn-outline-primary hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="OPH" style="font-size: 0.78rem;">OPH คน OPD (9)</button>
                    <button type="button" class="btn btn-sm btn-outline-success hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="IPA" style="font-size: 0.78rem;">IPA ครั้ง IPD (9)</button>
                    <button type="button" class="btn btn-sm btn-outline-success hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="IPH" style="font-size: 0.78rem;">IPH คน IPD (9)</button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="IPS" style="font-size: 0.78rem;">IPS SumAdjRW (9)</button>
                    <button type="button" class="btn btn-sm btn-outline-danger hfa-cat-btn py-0.5 px-2.5 rounded-pill" data-cat="IPL" style="font-size: 0.78rem;">IPL วันนอน (9)</button>
                </div>
            </div>

            <!-- Modal Body Table -->
            <div class="modal-body p-3">
                <div class="table-responsive rounded border">
                    <table class="table table-hover table-service-report mb-0" id="hfaServiceTable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 120px;">Code_SerV</th>
                                <th class="text-center" style="width: 100px;">ประเภท</th>
                                <th>รายการบริการ (Item)</th>
                                <th class="text-center" style="width: 160px;">สิทธิการรักษา (Rights)</th>
                                <th class="text-end" style="width: 170px;">จำนวน / ค่า (Amount)</th>
                                <th class="text-center" style="width: 100px;">หน่วยนับ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hfaServiceDefs as $code => $def)
                            @php
                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                if ($def['rights'] == 'UC') $badgeClass = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                                elseif ($def['rights'] == 'SSS') $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                elseif ($def['rights'] == 'OFC') $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                                elseif ($def['rights'] == 'LGO') $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                elseif ($def['rights'] == 'STP') $badgeClass = 'bg-secondary-subtle text-dark border border-secondary-subtle';
                                elseif ($def['rights'] == 'FWF') $badgeClass = 'bg-dark-subtle text-dark border border-dark-subtle';
                                elseif ($def['rights'] == 'ชำระเงินเอง') $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                elseif ($def['rights'] == 'รวมทั้งหมดทุกสิทธิ') $badgeClass = 'bg-primary text-white';
                            @endphp
                            <tr class="hfa-service-row" data-code="{{ $code }}" data-cat="{{ substr($code, 0, 3) }}" data-rights="{{ $def['rights'] }}" data-label="{{ $def['label'] }}">
                                <td class="text-center font-monospace fw-bold">
                                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace" style="font-size: 0.85rem;">{{ $code }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $def['type'] === 'OPD' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }} px-2 py-1" style="font-size: 0.78rem;">
                                        {{ $def['type'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size: 0.9rem;">{{ $def['item'] }}</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">{{ $def['label'] }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $badgeClass }} px-2.5 py-1" style="font-size: 0.8rem;">
                                        {{ $def['rights'] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <input type="text" 
                                           class="form-control form-control-sm text-end input-amt hfa-service-input" 
                                           id="hfa_amt_{{ $code }}" 
                                           name="items[{{ $code }}]" 
                                           value="0" 
                                           style="font-family: 'Consolas', monospace; font-size: 0.92rem;">
                                </td>
                                <td class="text-muted text-center small">
                                    @if(str_starts_with($code, 'OPV') || str_starts_with($code, 'IPA')) ครั้ง
                                    @elseif(str_starts_with($code, 'OPH') || str_starts_with($code, 'IPH')) คน
                                    @elseif(str_starts_with($code, 'IPS')) SumAdjRW
                                    @elseif(str_starts_with($code, 'IPL')) วัน
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer: ส่งออก Excel, ส่ง API, ปิด -->
            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="text-muted small" id="hfaServiceFooterHint">
                        <i class="bi bi-file-earmark-excel text-success me-1"></i>ชื่อไฟล์ส่งออก: <code id="hfa_export_filename_preview" class="text-teal fw-bold font-monospace" style="color: #0d9488 !important; font-size: 0.88rem;">ข้อมูลบริการ_งวด_xxxxxx_(ดด.ปปปป).xlsx</code>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnExportHfaServiceExcel" style="height: 36px;">
                        <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnSendHfaServiceApi" style="height: 36px; background-color: #0d9488; border-color: #0d9488;">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> ส่ง API
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 fw-bold" data-bs-dismiss="modal" style="height: 36px;">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: ข้อมูลงบทดลองสำหรับ HFA (803 บัญชี) -->
<!-- ========================================================================= -->
<div class="modal fade" id="hfaTrialBalanceModal" tabindex="-1" aria-labelledby="hfaTrialBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(255,255,255,0.15);">
                        <i class="bi bi-file-earmark-spreadsheet-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="hfaTrialBalanceModalLabel">ข้อมูลงบทดลองสำหรับ HFA (Health Financial Analysis)</h5>
                        <p class="mb-0 text-white-50 small" style="font-size: 0.82rem;">
                            งบทดลองมาตรฐาน 803 บัญชี ตรวจสอบความสมดุลเดบิต-เครดิต เพื่อนำส่งระบบ HFA
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Filter Section: ปีงบประมาณ & งวดที่ต้องการนำเข้า (แบบ HFA) -->
            <div class="px-4 py-3 border-bottom" style="background-color: #f8fafc;">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #ffffff; border-left: 4px solid #1e3a8a !important;">
                    <div class="row g-3 align-items-start">
                        <!-- Budget Year (Compact Width) -->
                        <div class="col-12 col-sm-auto" style="width: 140px;">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-calendar-check me-1" style="color: #1e3a8a;"></i>ปีงบประมาณ:
                            </label>
                            <select id="hfa_tb_fiscal_year" class="form-select form-select-sm fw-bold text-primary shadow-xs" style="border-color: #cbd5e1;">
                                @foreach($budgetYearChoices as $byChoice)
                                    <option value="{{ $byChoice }}" {{ $byChoice == $budgetYear ? 'selected' : '' }}>{{ $byChoice }}</option>
                                @endforeach
                            </select>
                            <div id="hfa_tb_fy_hint" class="text-muted mt-1 text-nowrap" style="font-size: 0.71rem;">
                                (งวด ต.ค.{{ $budgetYear - 1 }} - ก.ย.{{ $budgetYear }})
                            </div>
                        </div>

                        <!-- Period (Compact Width) -->
                        <div class="col-12 col-sm-auto" style="width: 230px;">
                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.83rem;">
                                <i class="bi bi-calendar3 me-1" style="color: #1e3a8a;"></i>งวดที่ต้องการนำเข้า:
                            </label>
                            <select id="hfa_tb_period" class="form-select form-select-sm fw-bold shadow-xs" style="border-color: #cbd5e1;">
                                <!-- Generated dynamically based on selected fiscal year -->
                            </select>
                            <div class="mt-1 d-flex align-items-center gap-1.5 flex-wrap" style="font-size: 0.72rem;">
                                <span class="badge px-2 py-0.5 rounded" style="background-color: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe;">
                                    <i class="bi bi-calendar2-range me-1"></i><span id="hfa_tb_period_date_range_label">-</span>
                                </span>
                            </div>
                            <!-- Hidden inputs for backend query -->
                            <input type="hidden" id="hfa_tb_month" value="8">
                        </div>

                        <!-- Process Button (Placed right beside Period dropdown) -->
                        <div class="col-12 col-sm-auto">
                            <label class="form-label d-none d-sm-block mb-1" style="font-size: 0.83rem; visibility: hidden;">&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm d-flex align-items-center gap-1.5" 
                                    id="btnProcessHfaTb" 
                                    style="height: 33px; font-size: 0.86rem; background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 100%); border: none; white-space: nowrap;">
                                <i class="bi bi-play-circle-fill fs-6"></i> ดึงข้อมูลงบทดลอง
                            </button>
                            <div class="mt-1 d-none d-sm-block" style="font-size: 0.72rem; visibility: hidden;">&nbsp;</div>
                        </div>

                        <!-- Status Message (Right beside button) -->
                        <div class="col-12 col-md align-self-center mt-1 mt-md-0 ps-md-2">
                            <div id="hfaTbStatus" class="small text-muted text-break">
                                พร้อมดึงข้อมูลงบทดลอง
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trial Balance Summary Badges / Cards -->
                <div class="row g-2 mt-2 pt-2 border-top">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between shadow-2xs">
                            <div>
                                <span class="text-muted small" style="font-size: 0.72rem;">จำนวนบัญชี</span>
                                <div class="fw-bold text-dark fs-6" id="hfa_tb_count">-</div>
                            </div>
                            <i class="bi bi-list-ol text-secondary fs-4"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between shadow-2xs">
                            <div>
                                <span class="text-muted small" style="font-size: 0.72rem;">รวมเดบิต (DR)</span>
                                <div class="fw-bold text-primary fs-6" id="hfa_tb_sum_dr">0.00</div>
                            </div>
                            <i class="bi bi-plus-circle text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between shadow-2xs">
                            <div>
                                <span class="text-muted small" style="font-size: 0.72rem;">รวมเครดิต (CR)</span>
                                <div class="fw-bold text-success fs-6" id="hfa_tb_sum_cr">0.00</div>
                            </div>
                            <i class="bi bi-dash-circle text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between shadow-2xs">
                            <div>
                                <span class="text-muted small" style="font-size: 0.72rem;">ผลต่าง (Dr - Cr)</span>
                                <div class="fw-bold text-dark fs-6" id="hfa_tb_diff">0.00</div>
                            </div>
                            <div id="hfa_tb_balance_badge">
                                <span class="badge bg-secondary rounded-pill px-2 py-1" style="font-size: 0.75rem;">รอประมวลผล</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Body Table -->
            <div class="modal-body p-3">
                <!-- Search Box Row (Aligned Right) -->
                <div class="d-flex justify-content-between align-items-center mb-2.5">
                    <div class="text-muted small fw-semibold">
                        <i class="bi bi-table me-1 text-primary"></i>รายการบัญชีมาตรฐาน (803 บัญชี)
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 270px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="hfaTbSearch" class="form-control border-start-0 ps-0 shadow-none" placeholder="ค้นหารหัส / ชื่อบัญชี...">
                    </div>
                </div>

                <div class="table-responsive rounded border tb-scroll-container" style="max-height: 440px; overflow-y: auto;">
                    <table class="table table-hover table-service-report mb-0" id="hfaTbTable">
                        <thead style="position: sticky; top: 0; z-index: 5;">
                            <tr>
                                <th class="text-center" style="width: 140px;">รหัสบัญชี</th>
                                <th>ชื่อบัญชี</th>
                                <th class="text-end" style="width: 140px;">ยอดยกมา (BF)</th>
                                <th class="text-end" style="width: 140px;">เดบิต (DR)</th>
                                <th class="text-end" style="width: 140px;">เครดิต (CR)</th>
                                <th class="text-end" style="width: 140px;">ยอดยกไป (CF)</th>
                            </tr>
                        </thead>
                        <tbody id="hfaTbTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-arrow-up-circle fs-2 d-block mb-2 text-secondary"></i>
                                    กรุณากดปุ่ม <strong>"ดึงข้อมูลงบทดลอง"</strong> เพื่อแสดงรายการบัญชีและตรวจสอบความสมดุล
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer: ส่งออก Excel, ส่ง API, ปิด -->
            <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div>
                        <span class="text-muted small" id="hfaTbFooterHint">
                            <i class="bi bi-file-earmark-excel text-success me-1"></i>ชื่อไฟล์ส่งออก: <code id="hfa_tb_export_filename_preview" class="fw-bold font-monospace" style="color: #1e3a8a !important; font-size: 0.88rem;">ข้อมูลการเงิน_งวด_xxxxxx_(ดด.ปปปป).xlsx</code>
                        </span>
                    </div>
                    <div class="text-muted small" style="font-size: 0.76rem;">
                        <i class="bi bi-info-circle me-1"></i> ตรวจสอบงบทดลองก่อนส่ง API (ระบบจะอนุญาตให้ส่งเมื่อเดบิตเท่ากับเครดิตเท่านั้น)
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnExportHfaTbExcel" style="height: 36px;">
                        <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-xs d-flex align-items-center gap-1.5" id="btnSendHfaTbApi" style="height: 36px; background-color: #1e293b; border-color: #1e293b;">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> ส่ง API
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 fw-bold" data-bs-dismiss="modal" style="height: 36px;">
                        ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    
    // -------------------------------------------------------------
    // Initialize Thai Datepicker (ปฏิทินไทย / พ.ศ.)
    // -------------------------------------------------------------
    if (typeof $.fn.datepicker !== 'undefined') {
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1600
        });

        function syncDatepickers() {
            var pairs = [
                { hidden: '#filter_start_date', picker: '#filter_start_date_picker' },
                { hidden: '#filter_end_date', picker: '#filter_end_date_picker' }
            ];

            pairs.forEach(function(pair) {
                var val = $(pair.hidden).val();
                if (val && $(pair.picker).length) {
                    var parts = val.split('-');
                    if (parts.length === 3) {
                        $(pair.picker).datepicker('setDate', new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2])));
                    }
                }
            });
        }

        syncDatepickers();

        $('#serviceDataModal').on('shown.bs.modal', function () {
            syncDatepickers();
        });

        $('#hfaServiceModal').on('shown.bs.modal', function () {
            syncDatepickers();
        });

        $('.datepicker_th').on('changeDate', function(e) {
            var date = e.date;
            var targetId = $(this).attr('id').replace('_picker', '');
            var hiddenInput = $('#' + targetId);
            if (date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear(); // คริสต์ศักราช สำหรับ Query Backend
                hiddenInput.val(year + "-" + month + "-" + day);
            } else {
                hiddenInput.val('');
            }
        });
    }

    // -------------------------------------------------------------
    // Calculate quarter start/end dates when quarter or year changes
    // -------------------------------------------------------------
    function updateQuarterDates() {
        const by = parseInt(document.getElementById('filter_budget_year').value);
        const q = parseInt(document.getElementById('filter_quarter').value);
        const ceYear = by - 543;
        
        let qStart = '', qEnd = '', qLabel = '';
        if (q === 1) {
            qStart = (ceYear - 1) + '-10-01';
            qEnd = (ceYear - 1) + '-12-31';
            qLabel = `ไตรมาส 1 (1 ต.ค. - 31 ธ.ค. ${by - 1})`;
        } else if (q === 2) {
            qStart = ceYear + '-01-01';
            qEnd = ceYear + '-03-31';
            qLabel = `ไตรมาส 2 (1 ม.ค. - 31 มี.ค. ${by})`;
        } else if (q === 3) {
            qStart = ceYear + '-04-01';
            qEnd = ceYear + '-06-30';
            qLabel = `ไตรมาส 3 (1 เม.ย. - 30 มิ.ย. ${by})`;
        } else {
            qStart = ceYear + '-07-01';
            qEnd = ceYear + '-09-30';
            qLabel = `ไตรมาส 4 (1 ก.ค. - 30 ก.ย. ${by})`;
        }

        const badge = document.getElementById('quarterInfoBadge');
        if (badge) {
            badge.textContent = qLabel;
        }
        return { qStart, qEnd, qLabel };
    }

    document.getElementById('filter_quarter').addEventListener('change', updateQuarterDates);
    document.getElementById('filter_budget_year').addEventListener('change', updateQuarterDates);

    // -------------------------------------------------------------
    // Process from HOSxP (ดึงข้อมูลและแสดงผลค้างที่ Modal ทันที)
    // -------------------------------------------------------------
    document.getElementById('btnProcessHosxp').addEventListener('click', function() {
        const btn = this;
        const startDate = document.getElementById('filter_start_date').value;
        const endDate = document.getElementById('filter_end_date').value;
        const budgetYear = document.getElementById('filter_budget_year').value;
        const quarter = document.getElementById('filter_quarter').value;
        const { qStart, qEnd } = updateQuarterDates();

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาระบุช่วงวันที่',
                text: 'กรุณาเลือกวันที่เริ่มต้นและสิ้นสุดสำหรับคำนวณข้อมูลประจำเดือน',
                confirmButtonColor: '#3b82f6'
            });
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังประมวลผล...';
        document.getElementById('lastUpdatedStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> กำลังดึงข้อมูลจาก HOSxP...';

        fetch('{{ url("hosfin/reports/process_service_data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                budget_year: budgetYear,
                quarter: quarter,
                quarter_start: qStart,
                quarter_end: qEnd
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล';

            if (res.success) {
                const data = res.data;

                // Populate each account code input
                for (const [code, val] of Object.entries(data)) {
                    const inputEl = document.getElementById(`amt_${code}`);
                    if (inputEl) {
                        if (code.startsWith('4')) {
                            inputEl.value = parseFloat(val).toFixed(4);
                        } else {
                            inputEl.value = parseInt(val).toLocaleString();
                        }
                        // Subtle highlight
                        inputEl.classList.add('bg-warning-subtle');
                        setTimeout(() => inputEl.classList.remove('bg-warning-subtle'), 1000);
                    }
                }

                const now = new Date();
                const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                const localTimestamp = `${now.getDate()} ${thaiMonths[now.getMonth()]} ${now.getFullYear() + 543} เวลา ${now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' })} น.`;
                const fetchTime = res.fetch_datetime || localTimestamp;

                document.getElementById('lastUpdatedStatus').innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึงข้อมูลสำเร็จ:</span> <span class="text-dark fw-bold ms-1"><i class="bi bi-clock-history text-secondary me-1"></i>${fetchTime}</span>`;

                Swal.fire({
                    icon: 'success',
                    title: 'ประมวลผลสำเร็จ',
                    html: `ดึงข้อมูลเรียบร้อยแล้ว (${startDate} ถึง ${endDate})<div class="mt-2 pt-2 border-top text-muted small"><i class="bi bi-clock-history me-1 text-primary"></i> วันที่และเวลาที่ดึง: <strong class="text-dark">${fetchTime}</strong></div>`,
                    timer: 2200,
                    showConfirmButton: false
                });

            } else {
                document.getElementById('lastUpdatedStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> ประมวลผลไม่สำเร็จ`;
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message || 'ไม่สามารถประมวลผลข้อมูลได้',
                    confirmButtonColor: '#3b82f6'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill fs-6"></i> ประมวลผล';
            document.getElementById('lastUpdatedStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> การเชื่อมต่อขัดข้อง`;
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                text: err.message,
                confirmButtonColor: '#3b82f6'
            });
        });
    });

    // -------------------------------------------------------------
    // Copy Single Value
    // -------------------------------------------------------------
    window.copySingleValue = function(code) {
        const inp = document.getElementById(`amt_${code}`);
        if (inp && inp.value !== '') {
            const rawVal = inp.value.replace(/,/g, '').trim();
            navigator.clipboard.writeText(rawVal).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `คัดลอกรหัส ${code}: ${rawVal}`,
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }
    };

    // -------------------------------------------------------------
    // Print Report (พิมพ์รายงาน)
    // -------------------------------------------------------------
    document.getElementById('btnPrintReport').addEventListener('click', function() {
        const hospitalName = `{{ DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?? 'โรงพยาบาล' }}`.replace(/^"|"$/g, '');
        const startPicker = document.getElementById('filter_start_date_picker');
        const endPicker = document.getElementById('filter_end_date_picker');
        const startDisplay = startPicker ? startPicker.value : (document.getElementById('filter_start_date')?.value || '');
        const endDisplay = endPicker ? endPicker.value : (document.getElementById('filter_end_date')?.value || '');
        const quarterInfo = document.getElementById('quarterInfoBadge') ? document.getElementById('quarterInfoBadge').innerText : '';
        const now = new Date();
        const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const printTimestamp = `${now.getDate()} ${thaiMonths[now.getMonth()]} ${now.getFullYear() + 543} เวลา ${now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' })} น.`;

        let tableRowsHtml = '';
        @foreach($accounts as $code => $acc)
            {
                const inp = document.getElementById('amt_{{ $code }}');
                const amtVal = inp ? (inp.value || '0') : '0';
                tableRowsHtml += `
                    <tr>
                        <td style="text-align: center; font-family: monospace; font-weight: bold; font-size: 13px;">{{ $code }}</td>
                        <td style="text-align: left; font-size: 13px;">{{ $acc['name'] }}</td>
                        <td style="text-align: right; font-weight: bold; font-size: 13px;">${amtVal}</td>
                    </tr>
                `;
            }
        @endforeach

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.print();
            return;
        }

        printWindow.document.open();
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <title>รายงานข้อมูลบริการประกอบงบ - ${hospitalName}</title>
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
                    @page {
                        size: A4 portrait;
                        margin: 12mm 15mm 15mm 15mm;
                    }
                    html, body {
                        height: 100%;
                    }
                    body {
                        font-family: 'Sarabun', sans-serif;
                        color: #1e293b;
                        margin: 0;
                        padding: 12px;
                        font-size: 13px;
                        display: flex;
                        flex-direction: column;
                        min-height: 100vh;
                        box-sizing: border-box;
                    }
                    .content-wrapper {
                        flex: 1 0 auto;
                    }
                    .page-footer {
                        flex-shrink: 0;
                        margin-top: auto;
                        padding-top: 6px;
                        border-top: 1px dotted #94a3b8;
                        font-size: 11px;
                        color: #64748b;
                        text-align: left;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 16px;
                        border-bottom: 2px solid #334155;
                        padding-bottom: 10px;
                    }
                    .header h2 {
                        margin: 0 0 4px 0;
                        font-size: 20px;
                        font-weight: 700;
                        color: #0f172a;
                    }
                    .header h3 {
                        margin: 0 0 6px 0;
                        font-size: 16px;
                        font-weight: 600;
                        color: #334155;
                    }
                    .header .meta {
                        font-size: 12.5px;
                        color: #475569;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    th, td {
                        border: 1px solid #94a3b8;
                        padding: 6px 10px;
                        font-size: 12.5px;
                    }
                    th {
                        background-color: #f1f5f9;
                        font-weight: 700;
                        text-align: center;
                        color: #0f172a;
                    }
                    tr:nth-child(even) td {
                        background-color: #f8fafc;
                    }
                    .signature-section {
                        margin-top: 40px;
                        display: flex;
                        justify-content: space-between;
                        page-break-inside: avoid;
                    }
                    .signature-box {
                        text-align: center;
                        width: 240px;
                    }
                    .signature-box p {
                        margin: 4px 0;
                    }
                    .no-print-bar {
                        text-align: center;
                        margin-bottom: 15px;
                    }
                    @media print {
                        .no-print-bar { display: none !important; }
                        body {
                            padding: 0;
                            padding-bottom: 25px;
                        }
                        .page-footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            margin-top: 0;
                            background: #ffffff;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="no-print-bar">
                    <button onclick="window.print()" style="padding: 7px 20px; font-size: 14px; font-weight: bold; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer;">พิมพ์รายงาน (Print)</button>
                    <button onclick="window.close()" style="padding: 7px 18px; font-size: 14px; background: #64748b; color: #fff; border: none; border-radius: 6px; cursor: pointer; margin-left: 8px;">ปิดหน้าต่าง</button>
                </div>

                <div class="content-wrapper">
                    <div class="header">
                        <h2>${hospitalName}</h2>
                        <h3>รายงานข้อมูลบริการประกอบงบ</h3>
                        <div class="meta">
                            ช่วงวันที่: <strong>${startDisplay}</strong> ถึง <strong>${endDisplay}</strong> 
                            ${quarterInfo ? ` | ${quarterInfo}` : ''}
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">รหัสบัญชี</th>
                                <th style="width: 55%; text-align: left;">ชื่อบัญชี</th>
                                <th style="width: 30%; text-align: right;">จำนวน</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRowsHtml}
                        </tbody>
                    </table>

                    <div class="signature-section">
                        <div class="signature-box">
                            <br><br>
                            <p>ลงชื่อ........................................................</p>
                            <p>(........................................................)</p>
                            <p style="font-weight: 500;">ผู้จัดทำรายงาน</p>
                            <p>วันที่ ......./......./.......</p>
                        </div>
                        <div class="signature-box">
                            <br><br>
                            <p>ลงชื่อ........................................................</p>
                            <p>(........................................................)</p>
                            <p style="font-weight: 500;">ผู้ตรวจสอบ / รับรอง</p>
                            <p>วันที่ ......./......./.......</p>
                        </div>
                    </div>
                </div>

                <div class="page-footer">
                    วันที่พิมพ์รายงาน: <strong>${printTimestamp}</strong>
                </div>

                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 400);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    });

    // -------------------------------------------------------------
    // Export Excel
    // -------------------------------------------------------------
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        const startDate = document.getElementById('filter_start_date').value;
        const endDate = document.getElementById('filter_end_date').value;

        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);

        const inputs = document.querySelectorAll('#serviceDataModal .input-amt');
        inputs.forEach(inp => {
            const code = inp.id.replace('amt_', '');
            const rawVal = inp.value.replace(/,/g, '').trim();
            if (rawVal !== '') {
                params.append(`items[${code}]`, rawVal);
            }
        });

        window.location.href = '{{ url("hosfin/reports/export_excel") }}?' + params.toString();
    });

    // =============================================================
    // Helper: Form Submit for File Download (POST/GET)
    // =============================================================
    function submitDownloadForm(actionUrl, params) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = actionUrl;
        form.style.display = 'none';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        for (const [key, val] of Object.entries(params)) {
            if (typeof val === 'object' && val !== null) {
                for (const [subKey, subVal] of Object.entries(val)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `${key}[${subKey}]`;
                    input.value = subVal;
                    form.appendChild(input);
                }
            } else {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = val;
                form.appendChild(input);
            }
        }
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // =============================================================
    // HFA SECTION 1: ข้อมูลบริการ (54 รายการ)
    // =============================================================
    
    // Category tabs filter
    $('.hfa-cat-btn').on('click', function() {
        $('.hfa-cat-btn').removeClass('active');
        $(this).addClass('active');
        applyHfaServiceFilter();
    });

    function applyHfaServiceFilter() {
        const activeCat = $('.hfa-cat-btn.active').data('cat') || 'all';
        const searchInput = document.getElementById('hfaServiceSearch');
        const q = searchInput ? searchInput.value.toLowerCase().trim() : '';

        $('#hfaServiceTable tbody tr.hfa-service-row').each(function() {
            const row = $(this);
            const rowCat = row.data('cat');
            const code = String(row.attr('data-code') || row.data('code') || '').toLowerCase();
            const rights = String(row.attr('data-rights') || row.data('rights') || '').toLowerCase();
            const label = String(row.attr('data-label') || row.data('label') || '').toLowerCase();

            let matchCat = (activeCat === 'all' || rowCat === activeCat);
            let matchText = (!q || code.includes(q) || rights.includes(q) || label.includes(q));

            if (matchCat && matchText) {
                row.show();
            } else {
                row.hide();
            }
        });
    }

    function populateHfaServicePeriods(selectedPeriod = null) {
        const fyEl = document.getElementById('hfa_service_fiscal_year');
        const periodSelect = document.getElementById('hfa_service_period');
        const hintEl = document.getElementById('hfa_fy_hint');
        if (!fyEl || !periodSelect) return;

        const fy = parseInt(fyEl.value) || 2569;
        const priorYr = fy - 1;
        if (hintEl) {
            hintEl.textContent = `(งวดข้อมูล ต.ค.${priorYr} - ก.ย.${fy})`;
        }

        const thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const periods = [
            { inst: 1, m: 10, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 2, m: 11, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 3, m: 12, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 4, m: 1,  yr: fy,      ceYr: fy - 543 },
            { inst: 5, m: 2,  yr: fy,      ceYr: fy - 543 },
            { inst: 6, m: 3,  yr: fy,      ceYr: fy - 543 },
            { inst: 7, m: 4,  yr: fy,      ceYr: fy - 543 },
            { inst: 8, m: 5,  yr: fy,      ceYr: fy - 543 },
            { inst: 9, m: 6,  yr: fy,      ceYr: fy - 543 },
            { inst: 10, m: 7, yr: fy,      ceYr: fy - 543 },
            { inst: 11, m: 8, yr: fy,      ceYr: fy - 543 },
            { inst: 12, m: 9, yr: fy,      ceYr: fy - 543 }
        ];

        const now = new Date();
        const curMonth = now.getMonth() + 1;
        const curInst = (curMonth >= 10) ? (curMonth - 9) : (curMonth + 3);
        const defaultPeriodCode = `${fy}${String(curInst).padStart(2, '0')}`;

        periodSelect.innerHTML = '';
        let targetSelected = selectedPeriod || defaultPeriodCode;
        let foundSelected = false;

        periods.forEach(p => {
            const pCode = `${fy}${String(p.inst).padStart(2, '0')}`;
            const opt = document.createElement('option');
            opt.value = pCode;
            opt.dataset.month = p.m;
            opt.dataset.ceYear = p.ceYr;
            opt.dataset.fiscalYear = fy;
            opt.dataset.inst = p.inst;
            opt.textContent = `${pCode} : ${thaiMonthsShort[p.m]}${p.yr}`;
            if (pCode === targetSelected) {
                opt.selected = true;
                foundSelected = true;
            }
            periodSelect.appendChild(opt);
        });

        if (!foundSelected && periodSelect.options.length) {
            periodSelect.selectedIndex = periodSelect.options.length - 1;
        }

        onHfaServicePeriodChange();
    }

    function onHfaServicePeriodChange() {
        const periodSelect = document.getElementById('hfa_service_period');
        if (!periodSelect || !periodSelect.selectedOptions.length) return;
        const opt = periodSelect.selectedOptions[0];
        const month = parseInt(opt.dataset.month);
        const ceYear = parseInt(opt.dataset.ceYear);
        const padM = String(month).padStart(2, '0');
        const lastDay = new Date(ceYear, month, 0).getDate();

        const startIso = `${ceYear}-${padM}-01`;
        const endIso = `${ceYear}-${padM}-${String(lastDay).padStart(2, '0')}`;

        $('#hfa_service_start_date').val(startIso);
        $('#hfa_service_end_date').val(endIso);

        const thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const thaiYr = ceYear + 543;
        const rangeText = `1 ${thaiMonthsShort[month]} ${thaiYr} ถึง ${lastDay} ${thaiMonthsShort[month]} ${thaiYr}`;
        const rangeLabelEl = document.getElementById('hfa_period_date_range_label');
        if (rangeLabelEl) {
            rangeLabelEl.textContent = rangeText;
        }

        const pCode = periodSelect.value;
        const monthLabel = `${thaiMonthsShort[month]}${thaiYr}`;
        const fnPreview = document.getElementById('hfa_export_filename_preview');
        if (fnPreview && pCode) {
            fnPreview.textContent = `ข้อมูลบริการ_งวด_${pCode}_(${monthLabel}).xlsx`;
        }
    }

    $('#hfa_service_fiscal_year').on('change', function() {
        populateHfaServicePeriods();
    });

    $('#hfa_service_period').on('change', function() {
        onHfaServicePeriodChange();
    });

    // Populate periods on load
    populateHfaServicePeriods();

    function formatIsoDateToThai(isoDate) {
        if (!isoDate) return '';
        const p = isoDate.split('-');
        if (p.length === 3) {
            const thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            const day = parseInt(p[2]);
            const m = parseInt(p[1]);
            const y = parseInt(p[0]) + 543;
            return `${day} ${thaiMonthsShort[m]} ${y}`;
        }
        return isoDate;
    }

    // Process HFA Service Data from HOSxP
    document.getElementById('btnProcessHfaService').addEventListener('click', function() {
        const btn = this;
        const startDate = document.getElementById('hfa_service_start_date').value;
        const endDate = document.getElementById('hfa_service_end_date').value;
        const fiscalYear = document.getElementById('hfa_service_fiscal_year')?.value;
        const periodSelect = document.getElementById('hfa_service_period');
        const periodCode = periodSelect?.value;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาระบุงวดที่ต้องการนำเข้า',
                text: 'กรุณาเลือกงวดข้อมูลที่ต้องการประมวลผล'
            });
            return;
        }

        const dParts = startDate.split('-');
        const ceYear = parseInt(dParts[0]) || new Date().getFullYear();
        const month = parseInt(dParts[1]) || (new Date().getMonth() + 1);
        const dateDesc = `ช่วงวันที่ ${formatIsoDateToThai(startDate)} ถึง ${formatIsoDateToThai(endDate)}`;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังดึงข้อมูล...';
        document.getElementById('hfaServiceStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> กำลังดึงสถิติบริการ 54 รายการจาก HOSxP...';

        fetch('{{ url("hosfin/reports/hfa/service/process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                fiscal_year: fiscalYear,
                month: month,
                period: periodCode,
                start_date: startDate,
                end_date: endDate
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> ประมวลผลจาก HOSxP';

            if (res.success) {
                const data = res.data;
                for (const [code, val] of Object.entries(data)) {
                    const inputEl = document.getElementById(`hfa_amt_${code}`);
                    if (inputEl) {
                        if (code.startsWith('IPS')) {
                            inputEl.value = parseFloat(val).toFixed(4);
                        } else {
                            inputEl.value = parseInt(val).toLocaleString();
                        }
                        inputEl.classList.add('bg-success-subtle');
                        setTimeout(() => inputEl.classList.remove('bg-success-subtle'), 1000);
                    }
                }

                const fetchTime = res.fetch_datetime || new Date().toLocaleTimeString('th-TH');
                const periodLabel = periodSelect?.selectedOptions[0]?.text || periodCode;
                document.getElementById('hfaServiceStatus').innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึงข้อมูลสำเร็จ:</span> ${dateDesc} <span class="text-muted ms-1">(เมื่อ ${fetchTime})</span>`;

                Swal.fire({
                    icon: 'success',
                    title: 'ดึงข้อมูลบริการสำเร็จ',
                    html: `ดึงข้อมูลบริการ 54 รายการ <b>งวด ${periodLabel}</b><br><span class="text-success fw-bold">${dateDesc}</span><br><small class="text-muted"><i class="bi bi-clock-history me-1"></i> ${fetchTime}</small>`,
                    timer: 2500,
                    showConfirmButton: false
                });

            } else {
                document.getElementById('hfaServiceStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> ประมวลผลไม่สำเร็จ`;
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message || 'ไม่สามารถดึงข้อมูลบริการได้',
                    confirmButtonColor: '#0d9488'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> ประมวลผลจาก HOSxP';
            document.getElementById('hfaServiceStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> การเชื่อมต่อขัดข้อง`;
            Swal.fire({
                icon: 'error',
                title: 'การเชื่อมต่อขัดข้อง',
                text: err.message,
                confirmButtonColor: '#0d9488'
            });
        });
    });

    // Export HFA Service Excel
    document.getElementById('btnExportHfaServiceExcel').addEventListener('click', function() {
        const startDate = document.getElementById('hfa_service_start_date').value;
        const endDate = document.getElementById('hfa_service_end_date').value;
        const fiscalYear = document.getElementById('hfa_service_fiscal_year')?.value;
        const periodCode = document.getElementById('hfa_service_period')?.value;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาระบุช่วงวันที่',
                text: 'กรุณาเลือกตั้งแต่วันที่ และถึงวันที่ ให้ครบถ้วน'
            });
            return;
        }

        const dParts = startDate.split('-');
        const ceYear = parseInt(dParts[0]) || new Date().getFullYear();
        const month = parseInt(dParts[1]) || (new Date().getMonth() + 1);

        const items = {};
        document.querySelectorAll('.hfa-service-input').forEach(inp => {
            const code = inp.id.replace('hfa_amt_', '');
            const rawVal = inp.value.replace(/,/g, '').trim();
            items[code] = rawVal !== '' ? rawVal : '0';
        });

        submitDownloadForm('{{ url("hosfin/reports/hfa/service/export") }}', {
            fiscal_year: fiscalYear,
            month: month,
            period: periodCode,
            start_date: startDate,
            end_date: endDate,
            items: items
        });
    });

    // Send HFA Service API
    document.getElementById('btnSendHfaServiceApi').addEventListener('click', function() {
        const startDate = document.getElementById('hfa_service_start_date').value;
        const endDate = document.getElementById('hfa_service_end_date').value;
        const fiscalYear = document.getElementById('hfa_service_fiscal_year')?.value;
        const periodSelect = document.getElementById('hfa_service_period');
        const periodCode = periodSelect ? periodSelect.value : '';
        const periodLabel = periodSelect && periodSelect.selectedOptions.length ? periodSelect.selectedOptions[0].text : periodCode;

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาระบุช่วงวันที่',
                text: 'กรุณาเลือกตั้งแต่วันที่ และถึงวันที่ ให้ครบถ้วน'
            });
            return;
        }

        const dParts = startDate.split('-');
        const ceYear = parseInt(dParts[0]) || new Date().getFullYear();
        const month = parseInt(dParts[1]) || (new Date().getMonth() + 1);

        const items = {};
        let filledCount = 0;
        document.querySelectorAll('.hfa-service-input').forEach(inp => {
            const code = inp.id.replace('hfa_amt_', '');
            const rawVal = inp.value.replace(/,/g, '').trim();
            items[code] = rawVal !== '' ? rawVal : '0';
            if (parseFloat(items[code]) > 0) filledCount++;
        });

        Swal.fire({
            title: 'กำลังตรวจสอบสิทธิ์และเชื่อมต่อ Token FDH...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบขอ Access Token จาก FDH ด้วยบัญชีผู้ใช้งานของท่าน',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("hosfin.reports.hfa.check_token") }}')
            .then(res => res.json())
            .then(tokenRes => {
                if (!tokenRes.has_token) {
                    const errorMsg = tokenRes.message || 'ไม่สามารถขอ Access Token จาก FDH ได้ กรุณาตรวจสอบ FDH User หรือ Password';
                    Swal.fire({
                        icon: 'error',
                        title: 'เชื่อมต่อ Token FDH ไม่สำเร็จ',
                        html: `
                            <div class="text-start p-3 bg-light rounded border small">
                                <p class="text-danger fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-1"></i> ข้อผิดพลาด:</p>
                                <p class="mb-2 text-dark">${errorMsg}</p>
                                <hr class="my-2">
                                <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i> กรุณาตรวจสอบ <b>FDH User, FDH Pass และ FDH Secret Key</b> ในหน้าแก้ไขข้อมูลส่วนตัว (Profile) ของท่าน</p>
                            </div>
                        `,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'รับทราบ'
                    });
                    return;
                }

                Swal.fire({
                    title: '<span class="fw-bold">ยืนยันส่งข้อมูลบริการเข้าสู่ HFA ผ่าน API?</span>',
                    html: `
                        <div class="p-3 bg-light rounded border text-start">
                            <div class="mb-2"><strong>ประเภทข้อมูล:</strong> <span class="badge text-white" style="background-color: #0d9488;">ข้อมูลบริการ 54 รายการ</span></div>
                            <div class="mb-2"><strong>งวดที่ต้องการส่ง:</strong> <span class="badge bg-primary fs-6">${periodLabel}</span></div>
                            <div class="mb-2"><strong>ช่วงวันที่:</strong> <span class="text-dark">${formatIsoDateToThai(startDate)} ถึง ${formatIsoDateToThai(endDate)}</span></div>
                            <hr class="my-2">
                            <div class="mb-1"><strong>ผู้ส่งข้อมูล:</strong> <span class="text-dark fw-bold">${tokenRes.user_name || 'ผู้ใช้งาน'}</span></div>
                            <div class="mb-2"><strong>FDH User:</strong> <span class="text-primary fw-bold font-monospace">${tokenRes.fdh_user || '-'}</span></div>
                            <div class="text-success small pt-1 border-top">
                                <i class="bi bi-shield-check me-1"></i> ตรวจสอบ Token FDH ผ่านแล้ว พร้อมส่งข้อมูลไปยัง https://hfa.one.th
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d9488',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-cloud-arrow-up-fill me-1"></i> 🚀 ยืนยันส่ง API ทันที',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'กำลังส่งข้อมูลบริการ...',
                            html: 'ระบบกำลังสร้างไฟล์ Excel และเชื่อมโยง API HFA กรุณารอสักครู่',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('{{ url("hosfin/reports/hfa/service/send_api") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                fiscal_year: fiscalYear,
                                month: month,
                                period: periodCode,
                                start_date: startDate,
                                end_date: endDate,
                                token: tokenRes.token,
                                items: items
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ส่งข้อมูลบริการ HFA สำเร็จ',
                                    html: `<div class="alert alert-success mt-2 text-start small mb-0">${res.message || 'ส่งข้อมูลบริการเข้าสู่ระบบ HFA เรียบร้อยแล้ว'}</div>`,
                                    confirmButtonColor: '#0d9488'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ส่งข้อมูลไม่สำเร็จ',
                                    html: `<div class="alert alert-danger mt-2 text-start small mb-0">${res.message || 'เซิร์ฟเวอร์ HFA ปฏิเสธคำขอหรือเกิดข้อผิดพลาด'}</div>`,
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'การเชื่อมต่อเซิร์ฟเวอร์ขัดข้อง',
                                text: err.message,
                                confirmButtonColor: '#ef4444'
                            });
                        });
                    }
                });
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อ Token FDH ไม่สำเร็จ',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อตรวจสอบ Token: ' + err.message,
                    confirmButtonColor: '#ef4444'
                });
            });
    });

    // =============================================================
    // HFA SECTION 2: ข้อมูลงบทดลอง (803 บัญชี)
    // =============================================================
    window.hfaTbLoadedData = null;
    window.hfaTbBalanced = false;

    // Search filter for Trial Balance
    $('#hfaTbSearch').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        $('#hfaTbTableBody tr.hfa-tb-row').each(function() {
            const row = $(this);
            const code = String(row.attr('data-code') || row.data('code') || '').toLowerCase();
            const name = String(row.attr('data-name') || row.data('name') || '').toLowerCase();
            if (!q || code.includes(q) || name.includes(q)) {
                row.show();
            } else {
                row.hide();
            }
        });
    });

    // Trial Balance Periods management
    function populateHfaTbPeriods(selectedPeriod = null) {
        const fyEl = document.getElementById('hfa_tb_fiscal_year');
        const periodSelect = document.getElementById('hfa_tb_period');
        const hintEl = document.getElementById('hfa_tb_fy_hint');
        if (!fyEl || !periodSelect) return;

        const fy = parseInt(fyEl.value) || 2569;
        const priorYr = fy - 1;
        if (hintEl) {
            hintEl.textContent = `(งวดข้อมูล ต.ค.${priorYr} - ก.ย.${fy})`;
        }

        const thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const periods = [
            { inst: 1, m: 10, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 2, m: 11, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 3, m: 12, yr: priorYr, ceYr: priorYr - 543 },
            { inst: 4, m: 1,  yr: fy,      ceYr: fy - 543 },
            { inst: 5, m: 2,  yr: fy,      ceYr: fy - 543 },
            { inst: 6, m: 3,  yr: fy,      ceYr: fy - 543 },
            { inst: 7, m: 4,  yr: fy,      ceYr: fy - 543 },
            { inst: 8, m: 5,  yr: fy,      ceYr: fy - 543 },
            { inst: 9, m: 6,  yr: fy,      ceYr: fy - 543 },
            { inst: 10, m: 7, yr: fy,      ceYr: fy - 543 },
            { inst: 11, m: 8, yr: fy,      ceYr: fy - 543 },
            { inst: 12, m: 9, yr: fy,      ceYr: fy - 543 }
        ];

        const now = new Date();
        const curMonth = now.getMonth() + 1;
        const curInst = (curMonth >= 10) ? (curMonth - 9) : (curMonth + 3);
        const defaultPeriodCode = `${fy}${String(curInst).padStart(2, '0')}`;

        periodSelect.innerHTML = '';
        let targetSelected = selectedPeriod || defaultPeriodCode;
        let foundSelected = false;

        periods.forEach(p => {
            const pCode = `${fy}${String(p.inst).padStart(2, '0')}`;
            const opt = document.createElement('option');
            opt.value = pCode;
            opt.dataset.month = p.m;
            opt.dataset.ceYear = p.ceYr;
            opt.dataset.fiscalYear = fy;
            opt.dataset.inst = p.inst;
            opt.textContent = `${pCode} : ${thaiMonthsShort[p.m]}${p.yr}`;
            if (pCode === targetSelected) {
                opt.selected = true;
                foundSelected = true;
            }
            periodSelect.appendChild(opt);
        });

        if (!foundSelected && periodSelect.options.length) {
            periodSelect.selectedIndex = periodSelect.options.length - 1;
        }

        onHfaTbPeriodChange();
    }

    function onHfaTbPeriodChange() {
        const periodSelect = document.getElementById('hfa_tb_period');
        if (!periodSelect || !periodSelect.selectedOptions.length) return;
        const opt = periodSelect.selectedOptions[0];
        const month = parseInt(opt.dataset.month);
        const ceYear = parseInt(opt.dataset.ceYear);
        const padM = String(month).padStart(2, '0');
        const lastDay = new Date(ceYear, month, 0).getDate();

        $('#hfa_tb_month').val(month);

        const thaiMonthsShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const thaiYr = ceYear + 543;
        const rangeText = `1 ${thaiMonthsShort[month]} ${thaiYr} ถึง ${lastDay} ${thaiMonthsShort[month]} ${thaiYr}`;
        const rangeLabelEl = document.getElementById('hfa_tb_period_date_range_label');
        if (rangeLabelEl) {
            rangeLabelEl.textContent = rangeText;
        }

        const pCode = periodSelect.value;
        const monthLabel = `${thaiMonthsShort[month]}${thaiYr}`;
        const fnPreview = document.getElementById('hfa_tb_export_filename_preview');
        if (fnPreview && pCode) {
            fnPreview.textContent = `ข้อมูลการเงิน_งวด_${pCode}_(${monthLabel}).xlsx`;
        }
    }

    $('#hfa_tb_fiscal_year').on('change', function() {
        populateHfaTbPeriods();
    });

    $('#hfa_tb_period').on('change', function() {
        onHfaTbPeriodChange();
    });

    // Populate TB periods on load
    populateHfaTbPeriods();

    // Process HFA Trial Balance
    document.getElementById('btnProcessHfaTb').addEventListener('click', function() {
        const btn = this;
        const fiscalYear = document.getElementById('hfa_tb_fiscal_year').value;
        const periodSelect = document.getElementById('hfa_tb_period');
        const periodCode = periodSelect ? periodSelect.value : '';
        const periodLabel = periodSelect && periodSelect.selectedOptions.length ? periodSelect.selectedOptions[0].text : periodCode;
        const month = document.getElementById('hfa_tb_month').value;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> กำลังดึงงบ...';
        document.getElementById('hfaTbStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> กำลังประมวลผลข้อมูลงบทดลอง...';

        fetch('{{ url("hosfin/reports/hfa/tb/process") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                fiscal_year: fiscalYear,
                period: periodCode,
                month: month
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> ดึงข้อมูลงบทดลอง';

            if (res.success && res.data) {
                window.hfaTbLoadedData = res.data;
                const summary = res.summary || {};
                window.hfaTbBalanced = summary.is_balanced;

                // Update summary stats
                document.getElementById('hfa_tb_count').textContent = (summary.count || 0).toLocaleString();
                document.getElementById('hfa_tb_sum_dr').textContent = (summary.sum_dr || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('hfa_tb_sum_cr').textContent = (summary.sum_cr || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                document.getElementById('hfa_tb_diff').textContent = (summary.net_diff || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const badgeEl = document.getElementById('hfa_tb_balance_badge');
                if (summary.is_balanced) {
                    badgeEl.innerHTML = '<span class="badge bg-success rounded-pill px-2.5 py-1" style="font-size: 0.75rem;"><i class="bi bi-check-circle-fill me-1"></i> สมดุล</span>';
                } else {
                    badgeEl.innerHTML = '<span class="badge bg-danger rounded-pill px-2.5 py-1" style="font-size: 0.75rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i> ไม่สมดุล</span>';
                }

                // Render table rows
                let rowsHtml = '';
                res.data.forEach(item => {
                    const bf = (item.bf || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const dr = (item.debit || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const cr = (item.credit || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const cf = (item.cf || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    rowsHtml += `
                        <tr class="hfa-tb-row" data-code="${item.account_code}" data-name="${item.account_name}">
                            <td class="text-center font-monospace fw-bold"><span class="badge bg-light text-dark border px-2 py-0.5">${item.account_code}</span></td>
                            <td class="text-dark fw-medium">${item.account_name}</td>
                            <td class="text-end font-monospace">${bf}</td>
                            <td class="text-end font-monospace text-primary fw-semibold">${dr}</td>
                            <td class="text-end font-monospace text-success fw-semibold">${cr}</td>
                            <td class="text-end font-monospace fw-bold text-dark">${cf}</td>
                        </tr>
                    `;
                });
                document.getElementById('hfaTbTableBody').innerHTML = rowsHtml;

                const fetchTime = res.fetch_datetime || new Date().toLocaleTimeString('th-TH');
                document.getElementById('hfaTbStatus').innerHTML = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> โหลดงบทดลองสำเร็จ:</span> งวด <b>${res.period_code || res.period}</b> (${summary.count} บัญชี) ${summary.is_balanced ? '<span class="badge bg-success ms-1">งบดุล</span>' : '<span class="badge bg-danger ms-1">งบไม่ดุล</span>'}`;

                Swal.fire({
                    icon: summary.is_balanced ? 'success' : 'warning',
                    title: summary.is_balanced ? 'โหลดข้อมูลงบทดลองสำเร็จ' : 'งบทดลองไม่สมดุล',
                    html: `โหลดข้อมูลงบทดลอง <b>งวด ${periodLabel}</b> จำนวน <b>${summary.count}</b> บัญชี<br>ผลต่างเดบิต-เครดิต: <b>${(summary.net_diff || 0).toFixed(2)}</b> บาท`,
                    timer: 2000,
                    showConfirmButton: false
                });

            } else {
                document.getElementById('hfaTbStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> ${res.message || 'ไม่พบข้อมูลงบทดลอง'}`;
                document.getElementById('hfaTbTableBody').innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-danger">
                            <i class="bi bi-exclamation-octagon fs-2 d-block mb-2 text-danger"></i>
                            <div class="fw-bold fs-6 mb-1">${res.message || 'ไม่พบข้อมูลงบทดลองสำหรับงวดที่เลือก'}</div>
                            <div class="text-muted small mb-3">กรุณานำเข้าไฟล์บัญชีหน่วยงาน HFO (.zip) หรือไฟล์ Excel งบทดลอง ในระบบ HosFin ก่อน</div>
                            <a href="{{ url('hosfin/trial_balance') }}" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs">
                                <i class="bi bi-box-arrow-up-right me-1"></i> ไปยังหน้านำเข้าข้อมูลบัญชี HFO / งบทดลอง
                            </a>
                        </td>
                    </tr>
                `;
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบข้อมูลงบทดลอง',
                    html: `<div class="text-start small text-muted mb-2">${res.message || 'ไม่พบข้อมูลงบทดลองในงวดที่เลือก'}</div><div class="alert alert-warning text-start small mb-0"><i class="bi bi-info-circle-fill me-1"></i>กรุณานำเข้าไฟล์บัญชีหน่วยงาน <b>HFO (.zip)</b> หรือไฟล์ <b>Excel งบทดลอง</b> ในระบบ HosFin ก่อนจึงจะสามารถส่งออกหรือส่ง API ได้</div>`,
                    showCancelButton: true,
                    confirmButtonColor: '#1e3a8a',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-box-arrow-up-right me-1"></i> ไปหน้านำเข้า HosFin (HFO)',
                    cancelButtonText: 'ปิด'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open('{{ url("hosfin/trial_balance") }}', '_blank');
                    }
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-play-circle-fill"></i> ดึงข้อมูลงบทดลอง';
            document.getElementById('hfaTbStatus').innerHTML = `<i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> การเชื่อมต่อขัดข้อง`;
            Swal.fire({
                icon: 'error',
                title: 'การเชื่อมต่อขัดข้อง',
                text: err.message,
                confirmButtonColor: '#1e3a8a'
            });
        });
    });

    // Export HFA Trial Balance Excel
    document.getElementById('btnExportHfaTbExcel').addEventListener('click', function() {
        const fiscalYear = document.getElementById('hfa_tb_fiscal_year').value;
        const periodSelect = document.getElementById('hfa_tb_period');
        const periodCode = periodSelect ? periodSelect.value : '';
        const month = document.getElementById('hfa_tb_month').value;

        window.location.href = `{{ url("hosfin/reports/hfa/tb/export") }}?fiscal_year=${fiscalYear}&period=${periodCode}&month=${month}`;
    });

    // Send HFA Trial Balance API
    document.getElementById('btnSendHfaTbApi').addEventListener('click', function() {
        const fiscalYear = document.getElementById('hfa_tb_fiscal_year').value;
        const periodSelect = document.getElementById('hfa_tb_period');
        const periodCode = periodSelect ? periodSelect.value : '';
        const periodLabel = periodSelect && periodSelect.selectedOptions.length ? periodSelect.selectedOptions[0].text : periodCode;
        const month = document.getElementById('hfa_tb_month').value;

        if (!window.hfaTbLoadedData || window.hfaTbLoadedData.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาดึงข้อมูลงบทดลองก่อน',
                text: 'โปรดกดปุ่ม "ดึงข้อมูลงบทดลอง" เพื่อตรวจสอบความสมดุลเดบิต-เครดิตก่อนส่ง API',
                confirmButtonColor: '#1e3a8a'
            });
            return;
        }

        if (!window.hfaTbBalanced) {
            const diff = document.getElementById('hfa_tb_diff').textContent;
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถส่ง API ได้: งบไม่ดุล',
                html: `ยอดรวมเดบิตและเครดิตไม่เท่ากัน (ผลต่าง <b>${diff}</b> บาท)<br><span class="text-danger small">ระบบ HFA กำหนดให้เดบิตต้องเท่ากับเครดิตเท่านั้นจึงจะนำส่งได้</span>`,
                confirmButtonColor: '#ef4444'
            });
            return;
        }

        Swal.fire({
            title: 'กำลังตรวจสอบสิทธิ์และเชื่อมต่อ Token FDH...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบขอ Access Token จาก FDH ด้วยบัญชีผู้ใช้งานของท่าน',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("hosfin.reports.hfa.check_token") }}')
            .then(res => res.json())
            .then(tokenRes => {
                if (!tokenRes.has_token) {
                    const errorMsg = tokenRes.message || 'ไม่สามารถขอ Access Token จาก FDH ได้ กรุณาตรวจสอบ FDH User หรือ Password';
                    Swal.fire({
                        icon: 'error',
                        title: 'เชื่อมต่อ Token FDH ไม่สำเร็จ',
                        html: `
                            <div class="text-start p-3 bg-light rounded border small">
                                <p class="text-danger fw-bold mb-2"><i class="bi bi-exclamation-octagon-fill me-1"></i> ข้อผิดพลาด:</p>
                                <p class="mb-2 text-dark">${errorMsg}</p>
                                <hr class="my-2">
                                <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i> กรุณาตรวจสอบ <b>FDH User, FDH Pass และ FDH Secret Key</b> ในหน้าแก้ไขข้อมูลส่วนตัว (Profile) ของท่าน</p>
                            </div>
                        `,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'รับทราบ'
                    });
                    return;
                }

                Swal.fire({
                    title: '<span class="fw-bold">ยืนยันส่งข้อมูลงบทดลองเข้าสู่ HFA ผ่าน API?</span>',
                    html: `
                        <div class="p-3 bg-light rounded border text-start">
                            <div class="mb-2"><strong>ประเภทข้อมูล:</strong> <span class="badge text-white" style="background-color: #1e3a8a;">งบทดลอง 803 บัญชี</span> <span class="badge bg-success ms-1"><i class="bi bi-check-circle me-1"></i>งบดุลสมบูรณ์</span></div>
                            <div class="mb-2"><strong>งวดที่ต้องการส่ง:</strong> <span class="badge bg-primary fs-6">${periodLabel}</span></div>
                            <hr class="my-2">
                            <div class="mb-1"><strong>ผู้ส่งข้อมูล:</strong> <span class="text-dark fw-bold">${tokenRes.user_name || 'ผู้ใช้งาน'}</span></div>
                            <div class="mb-2"><strong>FDH User:</strong> <span class="text-primary fw-bold font-monospace">${tokenRes.fdh_user || '-'}</span></div>
                            <div class="text-success small pt-1 border-top">
                                <i class="bi bi-shield-check me-1"></i> ตรวจสอบ Token FDH ผ่านแล้ว พร้อมส่งข้อมูลไปยัง https://hfa.one.th
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#1e3a8a',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="bi bi-cloud-arrow-up-fill me-1"></i> 🚀 ยืนยันส่ง API ทันที',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'กำลังส่งข้อมูลงบทดลอง...',
                            html: 'ระบบกำลังเตรียมไฟล์ Excel งบทดลองและเชื่อมโยง API HFA กรุณารอสักครู่',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch('{{ url("hosfin/reports/hfa/tb/send_api") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                fiscal_year: fiscalYear,
                                period: periodCode,
                                month: month,
                                token: tokenRes.token
                            })
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'ส่งข้อมูลงบทดลองสำเร็จ',
                                    html: `<div class="alert alert-success mt-2 text-start small mb-0">${res.message || 'ส่งข้อมูลงบทดลองเข้าสู่ระบบ HFA เรียบร้อยแล้ว'}</div>`,
                                    confirmButtonColor: '#1e3a8a'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ส่งข้อมูลไม่สำเร็จ',
                                    html: `<div class="alert alert-danger mt-2 text-start small mb-0">${res.message || 'เซิร์ฟเวอร์ HFA ปฏิเสธคำขอหรือเกิดข้อผิดพลาด'}</div>`,
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'การเชื่อมต่อเซิร์ฟเวอร์ขัดข้อง',
                                text: err.message,
                                confirmButtonColor: '#ef4444'
                            });
                        });
                    }
                });
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อ Token FDH ไม่สำเร็จ',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อตรวจสอบ Token: ' + err.message,
                    confirmButtonColor: '#ef4444'
                });
            });
    });
});
</script>
@endsection
