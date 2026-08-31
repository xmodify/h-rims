@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card border-0 shadow-sm" style="border-top: 4px solid #0d6efd !important; border-radius: 14px;">
                <div class="card-body p-4">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/rep_sss_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-primary"></i> นำเข้าไฟล์ REP ประกันสังคม (Excel Only)</h6>
                            <p class="text-muted small">เลือกไฟล์ Excel (.xlsx, .xls) ได้ไม่จำกัดจำนวนไฟล์</p>
                        </div>
                        
                        <div class="input-group mb-0">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".xlsx,.xls" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-primary px-3.5" type="submit" style="border-radius: 0;">
                                <i class="bi bi-cloud-upload me-1.5"></i> นำเข้าข้อมูล
                            </button>
                            <button type="button" class="btn btn-primary px-3.5 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#eclaimRepBotModal" style="border-radius: 0 10px 10px 0; background: linear-gradient(135deg, #0284c7, #0369a1); border: none;">
                                <i class="bi bi-cloud-arrow-down-fill me-1.5"></i> ดึงจาก e-Claim
                            </button>
                        </div>

                        @if ($message = Session::get('rep_success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0">
                                <i class="bi bi-check-circle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Search -->
    <div class="page-header-box mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 bg-white shadow-sm" style="border-radius: 14px;">
        <div>
            <h5 class="text-dark mb-0 fw-bold text-truncate" style="max-width: 500px;">
                <i class="bi bi-cloud-arrow-down-fill text-primary me-2"></i>
                ข้อมูลการตรวจสอบเบื้องต้น (REP) สิทธิ์ประกันสังคม SSS [OP-IP]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <a href="{{ url('/import/rep_sss_detail_opd') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-person-badge me-1"></i> รายละเอียด OPD
                </a>
                <a href="{{ url('/import/rep_sss_detail_ipd') }}" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-hospital me-1"></i> รายละเอียด IPD
                </a>
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#chartModal" id="btnShowChart">
                    <i class="bi bi-bar-chart-fill me-1"></i> กราฟสรุปรายเดือน
                </button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#failChartModal" id="btnShowFailChart">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> วิเคราะห์รหัสข้อผิดพลาด C
                </button>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="form-check form-switch me-1 mb-0 bg-light px-3 py-1.5 rounded-pill border d-flex align-items-center gap-2 shadow-sm" style="cursor: pointer;">
                    <input class="form-check-input ms-0" type="checkbox" id="filterUnresolvedOnly" style="cursor: pointer;">
                    <label class="form-check-label small fw-bold text-danger mb-0 text-nowrap" for="filterUnresolvedOnly" style="cursor: pointer;">
                        <i class="bi bi-funnel-fill text-danger me-1"></i> เฉพาะไฟล์ที่ยังแก้ไม่ผ่าน
                    </label>
                </div>
                <span class="text-muted small text-nowrap">ปีงบประมาณ:</span>
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
    <div class="card dash-card border-0 shadow-sm mb-4" style="border-radius: 14px;">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="rep_sss_table" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" width="25%">ชื่อ File</th>
                            <th class="text-center">Dep</th>
                            <th class="text-center">เลขที่ REP</th>
                            <th class="text-center">จำนวนรายทั้งหมด</th>
                            <th class="text-center">ผ่าน (ราย)</th>
                            <th class="text-center">ไม่ผ่าน (ราย)</th>
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center">ชดเชยสุทธิ</th>
                            @if(Auth::user()->status == 'admin')
                                <th class="text-center" width="10%">การจัดการ</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $total_cid = 0;
                            $total_pass = 0;
                            $total_fail = 0;
                            $total_resolved = 0;
                            $total_charge = 0;
                            $total_receive = 0;
                        @endphp
                        @foreach($rep_sss as $row)
                        @php
                            $total_cid += $row->count_cid;
                            $total_pass += $row->count_pass;
                            $total_fail += $row->count_fail;
                            $total_resolved += $row->count_resolved;
                            $total_charge += $row->charge;
                            $total_receive += $row->receive_total;
                            
                            $unresolved = $row->count_fail - $row->count_resolved;
                            $resolved = $row->count_resolved;
                        @endphp
                        <tr data-unresolved="{{ $unresolved }}">
                            <td class="small fw-bold text-dark">
                                {{ $row->rep_filename }}
                                @if($row->is_appeal)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 10px; padding: 3px 6px;">
                                        <i class="bi bi-shield-fill-exclamation me-1"></i> อุทธรณ์
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(strtoupper($row->dep) === 'OP' || strtoupper($row->dep) === 'OPD')
                                    <span class="badge bg-info-subtle text-primary border border-info-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-person-fill me-0.5"></i> OPD
                                    </span>
                                @elseif(strtoupper($row->dep) === 'IP' || strtoupper($row->dep) === 'IPD')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold px-2 py-1" style="font-size: 11px;">
                                        <i class="bi bi-hospital me-0.5"></i> IPD
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1" style="font-size: 11px;">
                                        {{ $row->dep }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">{{ $row->repno }}</td>
                            <td class="text-end fw-bold">{{ number_format($row->count_cid) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->count_pass) }}</td>
                            <td class="text-center">
                                @if($row->count_fail > 0)
                                    <a href="javascript:void(0)" class="fail-link text-decoration-none d-inline-flex align-items-center justify-content-center gap-1"
                                       data-filename="{{ $row->rep_filename }}"
                                       data-type="{{ $row->dep }}"
                                       data-repno="{{ $row->repno }}">
                                        @if($unresolved == 0)
                                            <span class="badge bg-success-soft text-success border border-success-subtle rounded-pill px-2.5 py-1" style="background-color: rgba(25, 135, 84, 0.08); border: 1px solid rgba(25, 135, 84, 0.2) !important; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center;">
                                                <i class="bi bi-check-circle-fill me-1"></i> แก้ผ่านครบ {{ $resolved }}/{{ $row->count_fail }}
                                            </span>
                                        @else
                                            <span class="badge bg-danger rounded-pill px-2.5 py-1" style="font-size: 12px; font-weight: bold;">
                                                {{ $unresolved }}
                                            </span>
                                            @if($resolved > 0)
                                                <span class="badge bg-success-soft text-success border border-success-subtle rounded-pill px-2 py-0.5" style="background-color: rgba(25, 135, 84, 0.08); border: 1px solid rgba(25, 135, 84, 0.2) !important; font-size: 10px; font-weight: bold;">
                                                    <i class="bi bi-check-circle-fill"></i> แก้แล้ว {{ $resolved }}
                                                </span>
                                            @endif
                                        @endif
                                    </a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ number_format($row->charge,2) }}</td>
                            <td class="text-end text-primary fw-bold">{{ number_format($row->receive_total,2) }}</td>
                            @if(Auth::user()->status == 'admin')
                                <td class="text-center text-nowrap">
                                    <button type="button"
                                        class="btn btn-xs btn-outline-danger rounded-pill px-3 btn-action-delete"
                                        data-filename="{{ $row->rep_filename }}"
                                        data-type="rep_sss"
                                        title="ลบข้อมูลนำเข้า">
                                        <i class="bi bi-trash-fill me-1"></i> ลบ
                                    </button>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold bg-light" style="border-top: 2px solid #dee2e6 !important;">
                            <td colspan="3" class="text-center text-dark">รวมทั้งหมดของปีงบประมาณ</td>
                            <td class="text-end text-dark">{{ number_format($total_cid) }}</td>
                            <td class="text-end text-success">{{ number_format($total_pass) }}</td>
                            <td class="text-center">
                                @php
                                    $total_unresolved = $total_fail - $total_resolved;
                                @endphp
                                @if($total_fail > 0)
                                    <span class="text-danger fw-bold" style="font-size: 14px;">{{ number_format($total_unresolved) }}</span>
                                    @if($total_resolved > 0)
                                        <span class="text-success small fw-normal ms-1" style="font-size: 11px;">(แก้แล้ว {{ number_format($total_resolved) }})</span>
                                    @endif
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">{{ number_format($total_charge,2) }}</td>
                            <td class="text-end text-primary">{{ number_format($total_receive,2) }}</td>
                            @if(Auth::user()->status == 'admin')
                                <td></td>
                            @endif
                        </tr>
                    </tfoot>
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
                    <div class="icon-box bg-primary text-white mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                        <i class="bi bi-bar-chart-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="db_title">Dashboard</h5>
                        <div class="text-muted small" id="db_subtitle">ยอดชดเชยสุทธิรายเดือน REP สิทธิ์ประกันสังคม SSS</div>
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

{{-- Modal: Failed Details (Patient list only) --}}
<div class="modal fade" id="failDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-soft text-danger mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: rgba(239, 68, 68, 0.08); border-radius: 12px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="failModalTitle">รายละเอียดข้อมูลที่ไม่ผ่าน</h5>
                        <div class="text-muted small" id="failModalSubtitle">รายชื่อผู้ป่วยและสาเหตุข้อผิดพลาด (รหัส C)</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loading Spinner -->
                <div id="fail_loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
                </div>

                <!-- Patient List Table -->
                <div id="failTabContent">
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-modern table-hover align-middle w-100">
                            <thead class="sticky-top bg-white" style="z-index: 1;">
                                <tr>
                                    <th class="text-center" width="4%">ลำดับ</th>
                                    <th class="text-center" width="8%">HN</th>
                                    <th class="text-center" width="8%">AN</th>
                                    <th width="14%">ชื่อ-สกุล</th>
                                    <th class="text-center" width="10%">วันที่รับบริการ</th>
                                    <th class="text-start text-danger" width="30%">สาเหตุข้อผิดพลาด (รหัส C / แนวทางแก้ไข)</th>
                                    <th class="text-end" width="8%">เรียกเก็บ</th>
                                    <th class="text-end" width="8%">ชดเชย สปสช.</th>
                                    <th class="text-center" width="10%">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody id="failPatientsTableBody">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: C-Code Error Chart (Yearly Summary) --}}
<div class="modal fade" id="failChartModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-danger-soft text-danger mb-0 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background-color: rgba(220, 53, 69, 0.08); border-radius: 12px; color: #dc3545;">
                        <i class="bi bi-bar-chart-line-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="failChartModalTitle">วิเคราะห์รหัสข้อผิดพลาด (C Code)</h5>
                        <div class="text-muted small" id="failChartModalSubtitle">สรุปความถี่ของรหัสข้อผิดพลาดสะสมประจำปีงบประมาณ</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-6">
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-bold">
                            <i class="bi bi-exclamation-circle me-1"></i> รหัสข้อผิดพลาดที่พบสะสมในปีงบประมาณ: {{ $budget_year }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small text-nowrap">ประเภทการบริการ:</span>
                                <select class="form-select shadow-sm" id="fail_chart_filter_type" style="width: 160px; border-radius: 8px;">
                                    <option value="all" selected>ทั้งหมด (OP/IP)</option>
                                    <option value="OP">ผู้ป่วยนอก (OPD)</option>
                                    <option value="IP">ผู้ป่วยใน (IPD)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="c_code_loading_spinner" class="text-center py-5 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <div class="mt-2 text-muted">กำลังดึงข้อมูลและวิเคราะห์...</div>
                </div>

                <!-- Chart Container -->
                <div style="height: 430px; width: 100%;" id="c_code_chart_container">
                    <div id="yearlyCCodeChart" style="height: 100%; width: 100%;"></div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert: Success -->
@if (session('rep_success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: "{!! session('rep_success') !!}",
            icon: 'success',
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#0d6efd',
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
    

<!-- Modal: e-Claim REP Bot Automation -->
<div class="modal fade" id="eclaimRepBotModal" tabindex="-1" aria-labelledby="eclaimRepBotModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header text-white p-3 px-4 border-0" style="background: linear-gradient(135deg, #0f172a 0%, #0369a1 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white shadow-sm">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="eclaimRepBotModalLabel">
                            ดึงข้อมูลการตรวจสอบเบื้องต้น (REP SSS (สิทธิ์ประกันสังคม)) จาก e-Claim อัตโนมัติ
                        </h5>
                        <div class="small opacity-75 mt-0.5 d-flex align-items-center gap-2">
                            <span>ระบบเชื่อมต่อตรง eclaim.nhso.go.th</span>
                            <span class="badge rounded-pill bg-white text-dark py-1 px-2 fw-medium" style="font-size: 10.5px;">
                                <i class="bi bi-shield-check text-primary me-1"></i> ThaiD SSO Ready
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
                                <div id="eclaimRepAuthStatusIcon" class="badge rounded-circle p-2 bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" id="eclaimRepAuthStatusText">ยังไม่ได้เชื่อมต่อกับระบบ e-Claim</div>
                                    <div class="text-muted small" id="eclaimRepAuthStatusSub">ระบุ e-Claim Session Cookie (JSESSIONID) หรือกดซิงก์จาก Extension เพื่อเชื่อมต่อ</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" id="btnEclaimRepLoginPopup">
                                    <i class="bi bi-qr-code-scan me-1"></i> เข้าสู่ระบบ e-Claim (ThaiD)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="showEclaimExtensionGuide()" title="สำหรับติดตั้งหรือเปิดใช้ Extension บน Chrome">
                                    <i class="bi bi-puzzle me-1"></i> ส่วนเสริม Chrome
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 d-none" id="btnEclaimRepLogout">
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
                                <label class="form-label small fw-bold text-muted mb-1">ปี (พ.ศ.)</label>
                                <select class="form-select form-select-sm rounded-3" id="botRepBudgetYear">
                                    @foreach ($budget_year_select as $row)
                                        <option value="{{ $row->LEAVE_YEAR_ID }}" {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                            {{ $row->LEAVE_YEAR_NAME }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">งวดเดือน</label>
                                <select class="form-select form-select-sm rounded-3" id="botRepMonth">
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8" selected>สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">เลข REP (ระบุหรือไม่ก็ได้)</label>
                                <input type="text" class="form-control form-control-sm rounded-3" id="botRepNoFilter" placeholder="เช่น 690800077">
                            </div>
                            <div class="col-md-3 d-flex align-items-end pt-3">
                                <button class="btn btn-primary btn-sm w-100 rounded-3 py-1.5 fw-bold shadow-sm" type="button" id="btnBotRepSearch">
                                    <i class="bi bi-search me-1"></i> ค้นหาใน e-Claim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: REP Results List Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                        <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-list-task text-success"></i> รายการ REP ที่พบใน e-Claim (REP SSS (สิทธิ์ประกันสังคม))
                        </div>
                        <span class="badge bg-light text-dark border px-2.5 py-1" id="botRepCountBadge">พบ 0 รายการ</span>
                    </div>
                    <div class="table-responsive" style="max-height: 380px;">
                        <table class="table table-hover table-bordered align-middle mb-0 text-nowrap small" id="botRepTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" width="40">
                                        <input class="form-check-input" type="checkbox" id="checkAllBotRep">
                                    </th>
                                    <th class="text-center">วันที่นำส่ง</th>
                                    <th class="text-center">เลข REP</th>
                                    <th>ชื่อไฟล์ ECD / Excel</th>
                                    <th class="text-center">จำนวน</th>
                                    <th class="text-center">ผ่าน</th>
                                    <th class="text-center">ไม่ผ่าน</th>
                                    <th class="text-center">ผู้นำเข้า</th>
                                    <th class="text-center">สถานะใน RIMS</th>
                                </tr>
                            </thead>
                            <tbody id="botRepTableBody">
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>
                                        กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ REP
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer p-3 bg-white border-top d-flex justify-content-between">
                <div class="small fw-bold text-primary" id="selectedBotRepCount">เลือก 0 รายการ</div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" id="btnStartImportBotRep" disabled>
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> เริ่มนำเข้า RIMS ทันที
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    confirmButtonColor: '#0d6efd',
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

            var repTable = $('#rep_sss_table').DataTable({
                ordering: false,
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
                    title: 'ข้อมูล REP สิทธิ์ประกันสังคม SSS [OP-IP]'
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

            // Filter only unresolved files
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (settings.sTableId !== 'rep_sss_table') return true;
                    if ($('#filterUnresolvedOnly').is(':checked')) {
                        var rowNode = settings.aoData[dataIndex].nTr;
                        var unresolved = parseInt($(rowNode).attr('data-unresolved') || '0', 10);
                        return unresolved > 0;
                    }
                    return true;
                }
            );

            $('#filterUnresolvedOnly').on('change', function() {
                repTable.draw();
            });

            // --- Chart Modal Handling ---
            let monthlyChart = null;

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

                $('#db_subtitle').text(`ยอดชดเชยสุทธิรายเดือน REP สิทธิ์ประกันสังคม SSS ปีงบประมาณ: ${budgetYearText}`);

                $('#chart_container').addClass('d-none');
                $('#loading_spinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('import.rep_sss.chart-data') }}",
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
                    colors: ['#0d6efd', '#ef4444'],
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

            // --- Failed Details Modal Handling ---
            $(document).on('click', '.fail-link', function () {
                const filename = $(this).data('filename');
                const type = $(this).data('type');
                const repno = $(this).data('repno');

                // Update modal texts
                $('#failModalTitle').text(`รายละเอียดข้อมูลที่ไม่ผ่าน [${type}]`);
                $('#failModalSubtitle').text(`ไฟล์: ${filename} | เลขที่ REP: ${repno}`);

                // Clear previous table content
                $('#failPatientsTableBody').empty();

                // Show spinner, hide table content
                $('#fail_loading_spinner').removeClass('d-none');
                $('#failTabContent').addClass('d-none');

                // Open modal
                $('#failDetailsModal').modal('show');

                // AJAX Request
                $.ajax({
                    url: "{{ route('import.rep_sss.fail-details') }}",
                    method: "GET",
                    data: {
                        rep_filename: filename,
                        rep_type: type,
                        repno: repno
                    },
                    success: function (res) {
                        $('#fail_loading_spinner').addClass('d-none');
                        $('#failTabContent').removeClass('d-none');

                        // Populate patients table
                        if (res.patients && res.patients.length > 0) {
                            res.patients.forEach((p, idx) => {
                                let errorDetailsHtml = '';
                                if (p.error_details && p.error_details.length > 0) {
                                    errorDetailsHtml = p.error_details.map(d => {
                                        const hasDescOrGuide = (d.description && d.description.trim() !== '') || (d.guide && d.guide.trim() !== '');
                                        
                                        if (!hasDescOrGuide) {
                                            return `
                                                <div class="mb-1 text-center">
                                                    <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">
                                                        ${d.code}
                                                    </span>
                                                </div>
                                            `;
                                        }

                                        return `
                                            <div class="p-2 mb-1 rounded bg-light border text-start" style="font-size: 11px; line-height: 1.35;">
                                                <div class="mb-1">
                                                    <span class="badge bg-danger text-white fw-bold px-2 py-0.5" style="font-size: 11px;">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> ${d.code}
                                                    </span>
                                                </div>
                                                ${d.description ? `<div class="text-dark fw-semibold mb-1" style="font-size: 11px;"><i class="bi bi-info-circle text-primary me-1"></i>${d.description}</div>` : ''}
                                                ${d.guide ? `<div class="text-success fw-medium" style="font-size: 11px;"><i class="bi bi-lightbulb-fill text-warning me-1"></i><b>แนวทางแก้:</b> ${d.guide}</div>` : ''}
                                            </div>
                                        `;
                                    }).join('');
                                } else {
                                    errorDetailsHtml = `<span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">${p.error_code || '-'}</span>`;
                                }

                                const row = `
                                    <tr>
                                        <td class="text-center text-muted small">${idx + 1}</td>
                                        <td class="text-center fw-bold text-dark">${p.hn}</td>
                                        <td class="text-center">${p.an}</td>
                                        <td class="fw-bold">${p.pt_name}</td>
                                        <td class="text-center small">${p.service_date}</td>
                                        <td class="text-start">${errorDetailsHtml}</td>
                                        <td class="text-end text-muted">${p.charge_total}</td>
                                        <td class="text-end text-danger fw-bold">${p.net_compensate_nhso}</td>
                                        <td class="text-center">
                                            <span class="badge bg-${p.status_color}-soft text-${p.status_color} fw-bold" style="background-color: ${p.status_color == 'success' ? 'rgba(40, 167, 69, 0.08)' : 'rgba(220, 53, 69, 0.08)'}; border: 1px solid ${p.status_color == 'success' ? '#28a745' : '#dc3545'}; padding: 5px 10px; border-radius: 6px; font-size: 11px;">
                                                ${p.status_text}
                                            </span>
                                        </td>
                                    </tr>
                                `;
                                $('#failPatientsTableBody').append(row);
                            });
                        } else {
                            $('#failPatientsTableBody').append('<tr><td colspan="9" class="text-center text-muted py-4">ไม่พบข้อมูลรายชื่อที่ไม่ผ่าน</td></tr>');
                        }
                    },
                    error: function () {
                        $('#fail_loading_spinner').addClass('d-none');
                        $('#failTabContent').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลรายละเอียดได้', confirmButtonColor: '#d33' });
                    }
                });
            });

            // --- Yearly C-Code Summary Chart Modal Handling ---
            let yearlyCCodeChart = null;

            $('#failChartModal').on('shown.bs.modal', function () {
                loadCCodeChartData();
            });

            $('#fail_chart_filter_type').on('change', function () {
                loadCCodeChartData();
            });

            function loadCCodeChartData() {
                const budgetYear = $('select[name="budget_year"]').val();
                const serviceType = $('#fail_chart_filter_type').val();

                $('#c_code_chart_container').addClass('d-none');
                $('#c_code_loading_spinner').removeClass('d-none');

                if (yearlyCCodeChart) {
                    yearlyCCodeChart.destroy();
                    yearlyCCodeChart = null;
                }

                $.ajax({
                    url: "{{ route('import.rep_sss.c-code-chart-data') }}",
                    method: "GET",
                    data: {
                        budget_year: budgetYear,
                        type: serviceType
                    },
                    success: function (res) {
                        $('#c_code_loading_spinner').addClass('d-none');
                        $('#c_code_chart_container').removeClass('d-none');

                        if (res.labels && res.labels.length > 0) {
                            renderYearlyCCodeChart(res.labels, res.counts);
                        } else {
                            $('#yearlyCCodeChart').html('<div class="text-center text-muted py-5"><i class="bi bi-info-circle me-1"></i> ไม่มีข้อมูลรหัสข้อผิดพลาดสะสมในปีงบประมาณนี้</div>');
                        }
                    },
                    error: function () {
                        $('#c_code_loading_spinner').addClass('d-none');
                        $('#c_code_chart_container').removeClass('d-none');
                        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถดึงข้อมูลสถิติได้', confirmButtonColor: '#d33' });
                    }
                });
            }

            function renderYearlyCCodeChart(labels, counts) {
                const options = {
                    series: [{
                        name: 'พบสะสม (ราย)',
                        data: counts
                    }],
                    chart: {
                        type: 'bar',
                        height: 400,
                        toolbar: { show: true }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            horizontal: false,
                            columnWidth: '45%',
                            distributed: true,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    colors: ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#0d6efd', '#6610f2', '#d63384', '#00f5ff', '#32cd32', '#ba55d3'],
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toLocaleString('th-TH') + " ราย";
                        },
                        offsetY: -20,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        categories: labels,
                        title: {
                            text: 'รหัสข้อผิดพลาด (C Code)',
                            style: { fontWeight: 'bold' }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'จำนวนผู้ป่วย (ราย)',
                            style: { fontWeight: 'bold' }
                        },
                        labels: {
                            formatter: function (val) {
                                return Math.round(val).toLocaleString('th-TH');
                            }
                        }
                    },
                    legend: {
                        show: false
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val.toLocaleString('th-TH') + " ราย";
                            }
                        }
                    }
                };

                yearlyCCodeChart = new ApexCharts(document.querySelector("#yearlyCCodeChart"), options);
                yearlyCCodeChart.render();
            }
        });
    
        // Deletion handler for REP index
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
                        url: "{{ route('import.rep.delete') }}",
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
        // e-Claim REP Automation (sss)
        // ==========================================
        var repMaininscl = 'sss';
        var eclaimRepIsChecking = false;
        var eclaimRepIsConnected = false;

        function checkEclaimRepStatus(silent = false) {
            if (eclaimRepIsChecking) return;
            eclaimRepIsChecking = true;

            if (!silent && !eclaimRepIsConnected) {
                $('#eclaimRepAuthStatusIcon').removeClass('bg-success-subtle text-success bg-warning-subtle text-warning')
                    .addClass('bg-secondary-subtle text-secondary')
                    .html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                $('#eclaimRepAuthStatusText').text('กำลังตรวจสอบสถานะการเชื่อมต่อ e-Claim...');
                $('#eclaimRepAuthStatusSub').text('ระบบกำลังทดสอบ Session กับ eclaim.nhso.go.th');
                $('#btnBotRepSearch').prop('disabled', true);
            }

            $.ajax({
                url: "{{ route('import.eclaim-bot.status') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}" },
                success: function(res) {
                    eclaimRepIsChecking = false;
                    if (res && res.connected) {
                        eclaimRepIsConnected = true;
                        if (window.eclaimRetryTimer_checkEclaimRepStatus) {
                            clearInterval(window.eclaimRetryTimer_checkEclaimRepStatus);
                            window.eclaimRetryTimer_checkEclaimRepStatus = null;
                        }

                        $('#eclaimRepAuthStatusIcon').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary')
                            .addClass('bg-success-subtle text-success')
                            .html('<i class="bi bi-check-circle-fill fs-5"></i>');
                        $('#eclaimRepAuthStatusText').html('เชื่อมต่อสำเร็จ: <span class="text-primary">' + (res.user || 'ผู้ใช้งาน e-Claim') + '</span>');
                        $('#eclaimRepAuthStatusSub').html('สถานะ: ออนไลน์พร้อมดึงข้อมูล | เชื่อมต่อเมื่อ: ' + (res.connected_at || '{{ date("Y-m-d H:i:s") }}'));
                        $('#btnEclaimRepLogout').removeClass('d-none');
                        $('#btnBotRepSearch').prop('disabled', false);
                    } else {
                        eclaimRepIsConnected = false;
                        $('#eclaimRepAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                            .addClass('bg-warning-subtle text-warning')
                            .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                        $('#eclaimRepAuthStatusText').text('ยังไม่ได้เชื่อมต่อกับระบบ e-Claim หรือ Session หมดอายุ');
                        $('#eclaimRepAuthStatusSub').text(res.message || 'เปิดเว็บ e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใน Extension เพื่อเริ่มดึงข้อมูล');
                        $('#btnEclaimRepLogout').addClass('d-none');
                        $('#btnBotRepSearch').prop('disabled', true);

                        if (!window.eclaimRetryTimer_checkEclaimRepStatus && $('#eclaimRepBotModal').hasClass('show')) {
                            window.eclaimRetryTimer_checkEclaimRepStatus = setInterval(function() {
                                if ($('#eclaimRepBotModal').hasClass('show') && !eclaimRepIsConnected) {
                                    checkEclaimRepStatus(true);
                                } else {
                                    clearInterval(window.eclaimRetryTimer_checkEclaimRepStatus);
                                    window.eclaimRetryTimer_checkEclaimRepStatus = null;
                                }
                            }, 4000);
                        }
                    }
                },
                error: function() {
                    eclaimRepIsChecking = false;
                    eclaimRepIsConnected = false;
                    $('#eclaimRepAuthStatusIcon').removeClass('bg-success-subtle text-success bg-secondary-subtle text-secondary')
                        .addClass('bg-warning-subtle text-warning')
                        .html('<i class="bi bi-exclamation-triangle-fill fs-5"></i>');
                    $('#eclaimRepAuthStatusText').text('ไม่สามารถตรวจสอบสถานะการเชื่อมต่อ e-Claim ได้');
                    $('#eclaimRepAuthStatusSub').text('กรุณาเปิดหน้า e-Claim ใน Chrome แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" ใหม่อีกครั้ง');
                    $('#btnEclaimRepLogout').addClass('d-none');
                    $('#btnBotRepSearch').prop('disabled', true);
                }
            });
        }

        $('#eclaimRepBotModal').on('show.bs.modal', function () {
            checkEclaimRepStatus(false);
        });

        $('#eclaimRepBotModal').on('hidden.bs.modal', function () {
            if (window.eclaimRetryTimer_checkEclaimRepStatus) {
                clearInterval(window.eclaimRetryTimer_checkEclaimRepStatus);
                window.eclaimRetryTimer_checkEclaimRepStatus = null;
            }
        });

        $(window).on('focus', function () {
            if ($('#eclaimRepBotModal').hasClass('show') && !eclaimRepIsConnected) {
                checkEclaimRepStatus(true);
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

        $('#btnEclaimRepLoginPopup').on('click', function () {
            openEclaimThaidQrModal(checkEclaimRepStatus);
        });

        $('#btnEclaimRepLogout').on('click', function () {
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
                            checkEclaimRepStatus();
                            $('#botRepTableBody').html('<tr><td colspan="9" class="text-center py-5 text-muted"><div class="opacity-50 fs-3 mb-2"><i class="bi bi-cloud-arrow-down"></i></div>กดปุ่ม "ค้นหาใน e-Claim" เพื่อดึงรายการ REP</td></tr>');
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

        var urlParams = new URLSearchParams(window.location.search);
        var urlToken = urlParams.get('eclaim_token');
        if (urlToken) {
            localStorage.setItem('eclaim_session_token', urlToken);
            $.ajax({
                url: "{{ route('import.eclaim-bot.save-token') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    token: urlToken
                },
                success: function (res) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                    $('#eclaimRepBotModal').modal('show');
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ!',
                        text: 'เชื่อมต่อกับระบบ e-Claim เรียบร้อยแล้ว พร้อมค้นหาและนำเข้าข้อมูลได้ทันที',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    checkEclaimRepStatus();
                }
            });
        }

        $('#btnBotRepSearch').on('click', function () {
            var budgetYear = $('#botRepBudgetYear').val();
            var month = $('#botRepMonth').val();
            var repNo = $('#botRepNoFilter').val();

            $('#botRepTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted fw-bold">กำลังดึงข้อมูลการตรวจสอบเบื้องต้น (REP) จาก e-Claim สปสช. ...</div>
                    </td>
                </tr>
            `);

            $.ajax({
                url: "{{ route('import.eclaim-bot.rep-search') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    maininscl: repMaininscl,
                    budget_year: budgetYear,
                    month: month,
                    rep_no: repNo
                },
                success: function (res) {
                    if (res.status === 'success') {
                        renderBotRepTable(res.data);
                    } else {
                        $('#botRepTableBody').html(`
                            <tr>
                                <td colspan="9" class="text-center py-5 text-danger">
                                    <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                                    <strong>${res.message || 'ไม่พบข้อมูล'}</strong>
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function (xhr) {
                    var msg = 'เกิดข้อผิดพลาดในการค้นหาข้อมูล';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $('#botRepTableBody').html(`
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

        function renderBotRepTable(items) {
            $('#botRepCountBadge').text('พบ ' + items.length + ' รายการ');
            $('#checkAllBotRep').prop('checked', false);
            updateSelectedRepCount();

            if (!items || items.length === 0) {
                $('#botRepTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-3 mb-2 d-block opacity-50"></i>
                            ไม่พบรายการ REP ในงวดเดือนที่เลือก
                        </td>
                    </tr>
                `);
                return;
            }

            var html = '';
            items.forEach(function (item, idx) {
                var statusBadge = item.is_imported 
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>นำเข้าแล้ว (${item.imported_count} ราย)</span>`
                    : `<span class="badge bg-secondary-subtle text-secondary px-2 py-1"><i class="bi bi-dash-circle me-1"></i>ยังไม่เคยนำเข้า</span>`;

                var itemJson = encodeURIComponent(JSON.stringify(item));

                html += `
                    <tr>
                        <td class="text-center">
                            <input class="form-check-input bot-rep-check" type="checkbox" data-item="${itemJson}" ${item.is_imported ? '' : 'checked'}>
                        </td>
                        <td class="text-center small">${item.send_date}</td>
                        <td class="text-center fw-bold text-primary">${item.rep_no}</td>
                        <td>
                            <div class="fw-bold text-dark"><i class="bi bi-file-earmark-excel text-success me-1"></i> ${item.filename}</div>
                            <small class="text-muted" style="font-size: 11px;">ประเภท: ${item.import_type} | ตรวจสอบ: ${item.check_date}</small>
                        </td>
                        <td class="text-center fw-bold">${item.total.toLocaleString()}</td>
                        <td class="text-center text-success fw-bold">${item.pass.toLocaleString()}</td>
                        <td class="text-center ${item.fail > 0 ? 'text-danger fw-bold' : 'text-muted'}">${item.fail.toLocaleString()}</td>
                        <td class="text-center small text-muted">${item.importer || '-'}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>
                `;
            });

            $('#botRepTableBody').html(html);
            updateSelectedRepCount();
        }

        $('#checkAllBotRep').on('change', function () {
            var checked = $(this).prop('checked');
            $('.bot-rep-check').prop('checked', checked);
            updateSelectedRepCount();
        });

        $(document).on('change', '.bot-rep-check', function () {
            updateSelectedRepCount();
        });

        function updateSelectedRepCount() {
            var selected = $('.bot-rep-check:checked').length;
            $('#selectedBotRepCount').text('เลือก ' + selected + ' รายการ');
            $('#btnStartImportBotRep').prop('disabled', selected === 0);
        }

        $('#btnStartImportBotRep').on('click', function () {
            var selectedItems = [];
            $('.bot-rep-check:checked').each(function () {
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
                title: 'ยืนยันการนำเข้าข้อมูล REP?',
                text: 'ระบบจะดาวน์โหลดไฟล์ Excel ช่องสุดท้ายจาก e-Claim จำนวน ' + selectedItems.length + ' ไฟล์ และบันทึกลงระบบ RIMS',
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
                                    <span id="importRepProgressStatusText" class="small fw-bold text-dark text-truncate" style="max-width: 250px;">กำลังเริ่มต้น...</span>
                                    <span id="importRepProgressPercentText" class="small fw-bold text-success">0%</span>
                                </div>
                                <div class="progress" style="height: 22px; border-radius: 11px; background-color: #e2e8f0;">
                                    <div id="importRepProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; font-size: 11.5px; font-weight: bold;">0%</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                                    <span id="importRepTimer"><i class="bi bi-clock-history me-1"></i> เวลา: 0 วิ</span>
                                    <span id="importRepProgressDetail">สำเร็จ <b id="importRepSuccessCount" class="text-success">0</b> / ${total} ไฟล์</span>
                                </div>
                                <div class="alert alert-light border py-1.5 px-2 mt-2 mb-0 small text-muted" style="font-size: 11px;">
                                    <i class="bi bi-info-circle text-primary me-1"></i> เซิร์ฟเวอร์ e-Claim สปสช. จะใช้เวลาสร้างไฟล์ Excel ประมาณ 10-20 วินาที/ไฟล์
                                </div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: async () => {
                            var elapsedSec = 0;
                            var timerInterval = setInterval(function () {
                                elapsedSec++;
                                $('#importRepTimer').html('<i class="bi bi-clock-history me-1"></i> เวลา: ' + elapsedSec + ' วิ');
                            }, 1000);

                            for (var i = 0; i < total; i++) {
                                var item = selectedItems[i];
                                var itemLabel = item.rep_no ? ('REP: ' + item.rep_no) : ('ไฟล์ที่ ' + (i + 1));
                                
                                $('#importRepProgressStatusText').text(`กำลังดึง ${itemLabel} (${i + 1}/${total})`);
                                
                                try {
                                    var res = await $.ajax({
                                        url: "{{ route('import.eclaim-bot.rep-import') }}",
                                        method: "POST",
                                        data: {
                                            _token: "{{ csrf_token() }}",
                                            maininscl: repMaininscl,
                                            items: [item]
                                        }
                                    });
                                    
                                    if (res && res.status === 'success') {
                                        successCount++;
                                    } else {
                                        failedCount++;
                                    }
                                } catch (err) {
                                    console.error("Error importing REP item: ", item, err);
                                    failedCount++;
                                }
                                
                                var percent = Math.round(((i + 1) / total) * 100);
                                $('#importRepProgressBar').css('width', percent + '%').text(percent + '%');
                                $('#importRepProgressPercentText').text(percent + '%');
                                $('#importRepSuccessCount').text(successCount);
                            }

                            clearInterval(timerInterval);
                            
                            if (successCount > 0) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'นำเข้าสำเร็จ!',
                                    html: `นำเข้าข้อมูล REP จาก e-Claim สำเร็จรวม <b>${successCount}</b> ไฟล์ ${failedCount > 0 ? `<br><span class="text-danger small">(ไม่สำเร็จ ${failedCount} ไฟล์)</span>` : ''}`,
                                    confirmButtonText: 'ตกลง',
                                    confirmButtonColor: '#10b981'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ไม่สำเร็จ',
                                    text: 'ไม่สามารถดาวน์โหลดหรือนำเข้า REP ที่เลือกได้ กรุณาตรวจสอบ Session e-Claim',
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