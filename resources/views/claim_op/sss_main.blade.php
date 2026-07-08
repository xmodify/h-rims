@php
    $is_ssop_licensed = \App\Services\LicenseService::isLicensed();
@endphp
@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ SS-OP ประกันสังคม เครือข่าย
            </h4>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section 1: Chart Data (Budget Year) -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                    @csrf
                    <span class="fw-bold text-muted small text-nowrap me-2">เลือกปีงบประมาณ</span>
                    <div class="input-group input-group-sm">
                        <input type="hidden" name="start_date" value="{{ $start_date }}">
                        <input type="hidden" name="end_date" value="{{ $end_date }}">
                        <select class="form-select" name="budget_year" style="width: 160px;">
                            @foreach ($budget_year_select as $row)
                              <option value="{{ $row->LEAVE_YEAR_ID }}"
                                {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                {{ $row->LEAVE_YEAR_NAME }}
                              </option>
                            @endforeach
                        </select>
                        <button type="submit" onclick="fetchData()" class="btn btn-primary px-3 shadow-sm">
                            <i class="bi bi-graph-up me-1"></i> โหลดกราฟ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Chart -->
        <div class="px-4 pt-2 pb-0 border-bottom">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติการเรียกเก็บและชดเชยรายเดือน ปีงบประมาณ {{ $budget_year }}
            </h6>
            <div style="height: 300px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ SS-OP ประกันสังคม เครือข่าย
                    </h6>
                    <span class="text-muted small">
                        วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
                    </span>
                </div>
                
                <div class="filter-group">
                    <form id="form_indiv" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center">
                        @csrf            
                        <span class="fw-bold text-muted small text-nowrap me-2">เลือกวันที่รับบริการ</span>
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="budget_year" value="{{ $budget_year }}">
                            <!-- Start Date -->
                            <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                            <input type="text" id="start_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>

                            <!-- End Date -->
                            <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                            <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">

                            <button onclick="fetchData()" type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                            <button type="button" class="btn btn-outline-primary px-3 shadow-sm" onclick="$('#importFeedbackModal').modal('show'); loadFeedbackList();">
                                <i class="bi bi-file-earmark-zip me-1"></i> นำเข้าข้อมูลตอบกลับ
                            </button>
                            @if($is_ssop_licensed)
                            <button type="button" class="btn btn-outline-success px-3 shadow-sm" onclick="exportSelectedSSOP()">
                                <i class="bi bi-box-arrow-up-fill me-1"></i> ส่งออก SSOP (.zip)
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="table-responsive">            
                <table id="t_claim" class="table table-modern w-100">
                    <thead>
                        <tr>
                            @if($is_ssop_licensed)
                            <th class="text-center" width="3%"><input type="checkbox" id="select_all_claims"></th>
                            @endif
                                                  
                            <th class="text-center">ตรวจสอบ</th>                      
                            <th class="text-center">วัน-เวลา | Q</th>     
                            <th class="text-center">HN</th>    
                            <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-center" width="15%">อาการสำคัญ</th>
                            <th class="text-center" width="8%">โรคเรื้อรัง</th>
                            <th class="text-center" width="8%">ยาโรคเรื้อรัง</th>
                            <th class="text-center" width="9%">NCD Match</th>
                            <th class="text-end px-3" width="6%">PDX</th>
                            <th class="text-start px-3" width="10%">SDX | ICD9</th>
                            <th class="text-center">ค่ารักษา</th> 
                            <th class="text-center">ชำระเอง</th>                               
                            <th class="text-center text-primary">เรียกเก็บ</th> 
                        </tr>
                    </thead> 
                    <tbody> 
                        @php 
                            $count = 1; 
                            $sum_income = 0; 
                            $sum_rcpt_money = 0; 
                            $sum_claim_price = 0; 
                        @endphp
                        @foreach($claim as $row) 
                        <tr>
                            @if($is_ssop_licensed)
                            <td class="text-center">
                                <input type="checkbox" class="claim-select-check" value="{{ $row->vn }}">
                            </td>
                            @endif
                            
                            <td class="text-center" data-status="{{ $row->claim_status }}" data-order="{{ $row->claim_status === 'red' ? '1' : '0' }}">
                                @if($row->claim_status === 'green')
                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ความพร้อม: ข้อมูลพร้อมส่งออก (คลิกเพื่อดูรายละเอียด)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ความพร้อม: ข้อมูลไม่ผ่านเกณฑ์ (คลิกเพื่อดูหัวข้อที่ต้องแก้ไข)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-start">
                                <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                            </td>            
                            <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                            <td class="text-start">
                                <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                            </td> 
                            <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                            <td class="text-center">
                                @if($row->is_ncd)
                                    <span class="d-none">Y</span><i class="bi bi-check-circle-fill text-success" title="เป็นโรคเรื้อรัง"></i>
                                @else
                                    <span class="d-none"></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->has_chronic_drug)
                                    <span class="d-none">Y</span><i class="bi bi-check-circle-fill text-success" title="ได้รับยาโรคเรื้อรัง"></i>
                                @else
                                    <span class="d-none"></span>
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $row->chronic_status }}">
                                @if($row->chronic_status === 'green')
                                    <span class="badge bg-success" style="font-size:0.7rem; min-width:65px;"><i class="bi bi-check-circle-fill"></i> สอดคล้อง</span>
                                @elseif($row->chronic_status === 'red')
                                    <span class="badge bg-danger" style="font-size:0.7rem; min-width:65px;"><i class="bi bi-exclamation-triangle-fill"></i> ไม่สอดคล้อง</span>
                                @else
                                    <span class="badge bg-secondary" style="font-size:0.7rem; min-width:65px;">ทั่วไป</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark small px-3">{{ $row->pdx }}</td>
                            <td class="text-start small px-3">
                                <div class="text-dark">{{ $row->sdx }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">{{ $row->icd9 }}</div>
                            </td>
                            <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                            <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td> 
                        </tr>
                        @php 
                            $count++; 
                            $sum_income += $row->income; 
                            $sum_rcpt_money += $row->rcpt_money; 
                            $sum_claim_price += $row->claim_price; 
                        @endphp
                        @endforeach                 
                    </tbody>
                    <tfoot class="bg-light-soft">
                        <tr>
                            <th colspan="{{ $is_ssop_licensed ? 11 : 10 }}" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                            <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                            <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                            <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>          
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="detailsModalBody">
                    <!-- Dynamic Content -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal นำเข้าและตรวจสอบผลตอบกลับ -->
    <div class="modal fade" id="importFeedbackModal" tabindex="-1" aria-labelledby="importFeedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title font-weight-bold" id="importFeedbackModalLabel">
                        <i class="bi bi-file-earmark-zip me-2"></i>นำเข้าและตรวจสอบผลตอบกลับ สกส. (SSOP / โรคเรื้อรัง / STM)
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- ส่วนหัวการนำเข้าและผลตอบกลับแบบกระชับ -->
                    <div class="p-3 mb-4 bg-light rounded border shadow-sm">
                        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center justify-content-md-start">
                            <!-- ปุ่มที่ 1: นำเข้าข้อมูล REP -->
                            <div>
                                <input type="file" id="zip_file_rep" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('rep')">
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_rep').click()">
                                    <i class="bi bi-file-earmark-arrow-up fs-5"></i> นำเข้าข้อมูล REP
                                </button>
                            </div>
                            <!-- ปุ่มที่ 2: นำเข้าข้อมูล STM -->
                            <div>
                                <input type="file" id="zip_file_stm" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('stm')">
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_stm').click()">
                                    <i class="bi bi-file-earmark-check fs-5"></i> นำเข้าข้อมูล STM
                                </button>
                            </div>
                            <!-- ปุ่มที่ 3: นำเข้าข้อมูลโรคเรื้อรัง -->
                            <div>
                                <input type="file" id="zip_file_chronic" style="display: none;" accept=".zip" multiple onchange="uploadSssZip('chronic')">
                                <button type="button" class="btn btn-info text-white px-4 py-2 fw-bold shadow-sm d-flex align-items-center gap-2" onclick="document.getElementById('zip_file_chronic').click()">
                                    <i class="bi bi-file-medical fs-5"></i> นำเข้าข้อมูลโรคเรื้อรัง
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs" id="feedbackTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-danger" id="tab21-tab" data-bs-toggle="tab" data-bs-target="#tab21-panel" type="button" role="tab" aria-controls="tab21-panel" aria-selected="true">
                                ตอนที่ 2.1 ผู้ป่วยอยู่ในบัญชีโรคเรื้อรังแล้ว (Dx หรือ Drug ไม่ตรง)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-warning" id="tab22-tab" data-bs-toggle="tab" data-bs-target="#tab22-panel" type="button" role="tab" aria-controls="tab22-panel" aria-selected="false">
                                ตอนที่ 2.2 ผู้ป่วยยังไม่อยู่ในบัญชีโรคเรื้อรัง
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content border border-top-0 p-3 bg-white rounded-bottom" id="feedbackTabsContent">
                        <!-- Tab 2.1 -->
                        <div class="tab-pane fade show active" id="tab21-panel" role="tabpanel" aria-labelledby="tab21-tab">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle" id="table-feedback-21">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th>วันที่รับบริการ</th>
                                            <th>HN</th>
                                            <th>ชื่อ-สกุล</th>
                                            <th>เลขบัตรประชาชน</th>
                                            <th>รหัสวินิจฉัย (Dx)</th>
                                            <th>รหัสยา (Drug)</th>
                                            <th>ไฟล์อ้างอิง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="feedback-21-body" class="small">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 2.2 -->
                        <div class="tab-pane fade" id="tab22-panel" role="tabpanel" aria-labelledby="tab22-tab">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle" id="table-feedback-22">
                                    <thead class="table-dark small">
                                        <tr>
                                            <th>วันที่รับบริการ</th>
                                            <th>HN</th>
                                            <th>ชื่อ-สกุล</th>
                                            <th>เลขบัตรประชาชน</th>
                                            <th>รหัสวินิจฉัย (Dx)</th>
                                            <th>รหัสยา (Drug)</th>
                                            <th>ไฟล์อ้างอิง</th>
                                        </tr>
                                    </thead>
                                    <tbody id="feedback-22-body" class="small">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">กำลังโหลดข้อมูล...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSOP Export Conditions Modal -->
    <div class="modal fade" id="ssopExportModal" tabindex="-1" aria-labelledby="ssopExportModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="ssopExportModalLabel">
                        <i class="bi bi-box-arrow-up-fill me-2"></i> เงื่อนไขการส่งออกข้อมูล SSOP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="export_session_id" class="form-label fw-bold">เลขรอบการส่งออก (Session ID)</label>
                        <input type="number" class="form-control form-control-lg fw-bold text-center" id="export_session_id" placeholder="ตัวอย่าง 6906" min="1" max="99999">
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> ระบบจะสุ่มเลขรอบเริ่มต้นให้ แนะนำให้ปรับแต่งไม่ให้ซ้ำกับรอบที่ส่งไปแล้ว</div>
                    </div>
                    <div class="mb-3">
                        <label for="export_station_id" class="form-label fw-bold">รหัสเครื่องส่ง (Station ID)</label>
                        <input type="text" class="form-control form-control-lg fw-bold text-center" id="export_station_id" value="01" placeholder="ตัวอย่าง 01" maxlength="5">
                        <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle-fill me-1"></i> โดยทั่วไปใช้รหัสเครื่องหลักคือ 01</div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-success px-4" onclick="previewSSOPExport()">
                        <i class="bi bi-eye me-1"></i> ดำเนินการและพรีวิวข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SSOP Export Preview Modal -->
    <div class="modal fade" id="ssopPreviewModal" tabindex="-1" aria-labelledby="ssopPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold" id="ssopPreviewModalLabel">
                        <i class="bi bi-file-earmark-check-fill me-2"></i> ตรวจสอบความถูกต้องของข้อมูลก่อนส่งออก SSOP
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Tabs Header -->
                    <ul class="nav nav-tabs mb-3" id="previewTab" role="tablist" style="font-size: 0.85rem;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-primary" id="prev-billtran-tab" data-bs-toggle="tab" data-bs-target="#prev-billtran" type="button" role="tab" aria-controls="prev-billtran" aria-selected="true">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> BILLTRAN
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-primary" id="prev-billitems-tab" data-bs-toggle="tab" data-bs-target="#prev-billitems-panel" type="button" role="tab" aria-controls="prev-billitems-panel" aria-selected="false">
                                <i class="bi bi-list-stars me-1"></i> BillItems
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-success" id="prev-billdisp-tab" data-bs-toggle="tab" data-bs-target="#prev-billdisp" type="button" role="tab" aria-controls="prev-billdisp" aria-selected="false">
                                <i class="bi bi-capsule me-1"></i> BILLDISP
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-success" id="prev-dispenseditems-tab" data-bs-toggle="tab" data-bs-target="#prev-dispenseditems-panel" type="button" role="tab" aria-controls="prev-dispenseditems-panel" aria-selected="false">
                                <i class="bi bi-capsules me-1"></i> DispensedItems
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-info" id="prev-opservices-tab" data-bs-toggle="tab" data-bs-target="#prev-opservices" type="button" role="tab" aria-controls="prev-opservices" aria-selected="false">
                                <i class="bi bi-clipboard-pulse me-1"></i> OPServices
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-info" id="prev-opdx-tab" data-bs-toggle="tab" data-bs-target="#prev-opdx-panel" type="button" role="tab" aria-controls="prev-opdx-panel" aria-selected="false">
                                <i class="bi bi-activity me-1"></i> OPDx
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content" id="previewTabContent">
                        <!-- Tab 1: BILLTRAN -->
                        <div class="tab-pane fade show active" id="prev-billtran" role="tabpanel" aria-labelledby="prev-billtran-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-billtran">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>Type</th>
                                            <th>DocNo</th>
                                            <th>InvDate</th>
                                            <th>Hcode</th>
                                            <th>InvNo</th>
                                            <th>SubID</th>
                                            <th>HN</th>
                                            <th>MemberNo</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">Paid</th>
                                            <th>PaidExtra</th>
                                            <th>Status</th>
                                            <th>CID</th>
                                            <th>Name</th>
                                            <th>MainHosp</th>
                                            <th>Spclty</th>
                                            <th class="text-end">ClaimAmt</th>
                                            <th>ExtraAmt</th>
                                            <th>PaidOther</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-billtran-tbody"></tbody>
                                </table>
                            </div>
                            <div class="card border-0 bg-light">
                                <div class="card-header border-0 bg-light p-0">
                                    <button class="btn btn-sm btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#raw-billtran-collapse">
                                        <span><i class="bi bi-file-earmark-code me-1"></i> ดูไฟล์ข้อความดิบ BILLTRAN.txt (Raw XML)</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="raw-billtran-collapse">
                                    <div class="card-body p-2 position-relative">
                                        <button class="btn btn-xs btn-secondary position-absolute end-0 top-0 m-2 btn-copy-xml" data-target="preview-billtran-raw" style="font-size: 0.7rem; z-index:10;"><i class="bi bi-clipboard"></i> Copy</button>
                                        <textarea class="form-control text-monospace bg-dark text-light p-3 small" id="preview-billtran-raw" rows="8" readonly style="font-family: Consolas, monospace; font-size:0.75rem;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: BillItems -->
                        <div class="tab-pane fade" id="prev-billitems-panel" role="tabpanel" aria-labelledby="prev-billitems-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-billitems">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>InvoiceNo</th>
                                            <th>SvDate</th>
                                            <th>BillGr</th>
                                            <th>ItemCode</th>
                                            <th>TMTID</th>
                                            <th>ItemName</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">UnitPrice</th>
                                            <th class="text-end">ChargeAmt</th>
                                            <th class="text-end">ClaimUP</th>
                                            <th class="text-end">ClaimAmt</th>
                                            <th>DispID/RefID</th>
                                            <th>ClaimCat</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-billitems-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 3: BILLDISP -->
                        <div class="tab-pane fade" id="prev-billdisp" role="tabpanel" aria-labelledby="prev-billdisp-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-billdisp">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>Hcode</th>
                                            <th>DispID</th>
                                            <th>PrescNo</th>
                                            <th>HN</th>
                                            <th>CID</th>
                                            <th>DispDate</th>
                                            <th>DispTime</th>
                                            <th>LicenseNo</th>
                                            <th>ItemType</th>
                                            <th class="text-end">UnitPrice</th>
                                            <th class="text-end">TotalAmt</th>
                                            <th>PaidAmt</th>
                                            <th>ExtraAmt</th>
                                            <th>Location</th>
                                            <th>Plan</th>
                                            <th class="text-center">Qty</th>
                                            <th>VisitNo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-billdisp-tbody"></tbody>
                                </table>
                            </div>
                            <div class="card border-0 bg-light">
                                <div class="card-header border-0 bg-light p-0">
                                    <button class="btn btn-sm btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#raw-billdisp-collapse">
                                        <span><i class="bi bi-file-earmark-code me-1"></i> ดูไฟล์ข้อความดิบ BILLDISP.txt (Raw XML)</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="raw-billdisp-collapse">
                                    <div class="card-body p-2 position-relative">
                                        <button class="btn btn-xs btn-secondary position-absolute end-0 top-0 m-2 btn-copy-xml" data-target="preview-billdisp-raw" style="font-size: 0.7rem; z-index:10;"><i class="bi bi-clipboard"></i> Copy</button>
                                        <textarea class="form-control text-monospace bg-dark text-light p-3 small" id="preview-billdisp-raw" rows="8" readonly style="font-family: Consolas, monospace; font-size:0.75rem;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: DispensedItems -->
                        <div class="tab-pane fade" id="prev-dispenseditems-panel" role="tabpanel" aria-labelledby="prev-dispenseditems-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-dispenseditems">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>DispID</th>
                                            <th>PrdCat</th>
                                            <th>icode</th>
                                            <th>TMTID</th>
                                            <th>PackSize</th>
                                            <th>DrugName</th>
                                            <th>Unit</th>
                                            <th>SigCode</th>
                                            <th>SigText</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">UnitPrice</th>
                                            <th class="text-end">ChargeAmt</th>
                                            <th class="text-end">ClaimUP</th>
                                            <th class="text-end">ClaimAmt</th>
                                            <th>ClaimCat</th>
                                            <th>RefID</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-dispenseditems-tbody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 5: OPServices -->
                        <div class="tab-pane fade" id="prev-opservices" role="tabpanel" aria-labelledby="prev-opservices-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-opservices">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th>InvoiceNo</th>
                                            <th>VisitNo</th>
                                            <th>CareType</th>
                                            <th>Hcode</th>
                                            <th>HN</th>
                                            <th>CID</th>
                                            <th>PtType</th>
                                            <th>Clinic</th>
                                            <th>ReferIn</th>
                                            <th>ReferOut</th>
                                            <th>Expire</th>
                                            <th>DoctorLic</th>
                                            <th>ServiceSub</th>
                                            <th>StartDT</th>
                                            <th>EndDT</th>
                                            <th>Ex1</th>
                                            <th>Ex2</th>
                                            <th>Ex3</th>
                                            <th>PaidAmt</th>
                                            <th>Eligible</th>
                                            <th>Ex4</th>
                                            <th>Seq</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-opservices-tbody"></tbody>
                                </table>
                            </div>
                            <div class="card border-0 bg-light">
                                <div class="card-header border-0 bg-light p-0">
                                    <button class="btn btn-sm btn-outline-secondary w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#raw-opservices-collapse">
                                        <span><i class="bi bi-file-earmark-code me-1"></i> ดูไฟล์ข้อความดิบ OPServices.txt (Raw XML)</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                                <div class="collapse" id="raw-opservices-collapse">
                                    <div class="card-body p-2 position-relative">
                                        <button class="btn btn-xs btn-secondary position-absolute end-0 top-0 m-2 btn-copy-xml" data-target="preview-opservices-raw" style="font-size: 0.7rem; z-index:10;"><i class="bi bi-clipboard"></i> Copy</button>
                                        <textarea class="form-control text-monospace bg-dark text-light p-3 small" id="preview-opservices-raw" rows="8" readonly style="font-family: Consolas, monospace; font-size:0.75rem;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 6: OPDx -->
                        <div class="tab-pane fade" id="prev-opdx-panel" role="tabpanel" aria-labelledby="prev-opdx-tab">
                            <div class="mb-3">
                                <table class="table table-hover table-striped align-middle mb-0 text-nowrap small w-100" id="table-prev-opdx">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Class</th>
                                            <th>SVRefID (VN)</th>
                                            <th>DiagType</th>
                                            <th>DiagCodeType</th>
                                            <th>DiagCode</th>
                                        </tr>
                                    </thead>
                                    <tbody id="preview-opdx-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                    <button type="button" class="btn btn-success px-4" onclick="triggerActualDownload()">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> ยืนยันส่งออก
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
  function showLoading() {
      Swal.fire({
          title: 'กำลังโหลด...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });
  }
  function fetchData() {
      showLoading();
  }
</script>

@endsection

@push('scripts')  
  <script>
    var shouldReloadOnModalClose = false;

    $(document).ready(function () {
      // Reload main page only when the import modal is closed and we have successfully uploaded files
      $('#importFeedbackModal').on('hidden.bs.modal', function () {
          if (shouldReloadOnModalClose) {
              location.reload();
          }
      });

      // Adjust DataTables column width on tab change (fix display bugs in hidden tabs)
      $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
          $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
      });

      // Initialize Datepicker Thai
      $('.datepicker_th').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th', 
          thaiyear: true,
          zIndexOffset: 1050
      });

      // Set initial values for Datepickers
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";

      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes from Picker to Hidden Input
      $('#start_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            $('#start_date').val(year + "-" + month + "-" + day);
          }
      });

      $('#end_date_picker').on('changeDate', function(e) {
          var date = e.date;
          if(date) {
            var day = ("0" + date.getDate()).slice(-2);
            var month = ("0" + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            $('#end_date').val(year + "-" + month + "-" + day);
          }
      });

      $.fn.dataTable.ext.order['dom-status'] = function (settings, col) {
          return this.api().column(col, {order:'index'}).nodes().map(function (td, i) {
              var status = $(td).attr('data-status');
              var sort = settings.aaSorting[0];
              var dir = (sort && sort[0] === col) ? sort[1] : 'desc';
              
              if (status === 'red') {
                  return dir === 'desc' ? 2 : -1;
              } else if (status === 'green') {
                  return dir === 'desc' ? 1 : -2;
              } else {
                  return 0;
              }
          });
      };

      $('#t_claim').DataTable({
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
        order: [[{{ $is_ssop_licensed ? 1 : 0 }}, 'desc']],
        columnDefs: [
            { targets: {{ $is_ssop_licensed ? 1 : 0 }}, orderDataType: 'dom-status', orderSequence: ['desc', 'asc'] }
        ],
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
              className: 'btn btn-success btn-sm shadow-sm',
              title: 'รายชื่อผู้มารับบริการ SS-OP ประกันสังคม เครือข่าย วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}',
              exportOptions: {
                  columns: {!! $is_ssop_licensed ? '[2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]' : '[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]' !!},
                  format: {
                      body: function (data, row, column, node) {
                          var $cell = $(node);
                          var chronicColIdx = {{ $is_ssop_licensed ? 5 : 4 }};
                          var drugColIdx = {{ $is_ssop_licensed ? 6 : 5 }};
                          if (column === chronicColIdx || column === drugColIdx) {
                              return $cell.find('.d-none').text().trim() === 'Y' ? 'Y' : '';
                          }
                          var cleanText = $cell.text().replace(/\s+/g, ' ').trim();
                          return cleanText;
                      }
                  }
              }
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
    });

    function showDetails(vn) {
        const body = document.getElementById('detailsModalBody');
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        $('#detailsModal').modal('show');

        $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const drugs = data.drugs;
                const diagnoses = data.diagnoses;
                
                let pdxBadge = visit.is_pdx_ncd 
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> เป็นโรคเรื้อรัง (NCD)</span>' 
                    : '<span class="badge bg-secondary ms-2"><i class="bi bi-info-circle"></i> ไม่ใช่โรคเรื้อรัง</span>';

                let hasChronicDrug = drugs.some(d => d.is_chronic);
                let drugBadge = hasChronicDrug
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> ได้รับยาโรคเรื้อรัง</span>'
                    : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่พบยาโรคเรื้อรัง</span>';

                let validationStatusHtml = '';
                if (visit.is_ncd && visit.has_matching_category) {
                    validationStatusHtml = '<div class="alert alert-success d-flex align-items-center mb-3"><i class="bi bi-check-circle-fill me-2 fs-5"></i> ข้อมูลสอดคล้อง (มีรหัสวินิจฉัยและยาโรคเรื้อรังครบถ้วนตรงตามกลุ่มโรค)</div>';
                } else if (visit.is_exempted_ncd) {
                    validationStatusHtml = '<div class="alert alert-success d-flex align-items-center mb-3"><i class="bi bi-check-circle-fill me-2 fs-5"></i> ข้อมูลสอดคล้อง (ได้รับการยกเว้นเกณฑ์การจ่ายยาสำหรับกลุ่มโรคตับ/หัวใจล้มเหลว/มะเร็ง/โรคเลือด/ทาลัสซีเมีย)</div>';
                } else if (visit.is_ncd || hasChronicDrug) {
                    validationStatusHtml = '<div class="alert alert-danger d-flex align-items-center mb-3"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> ข้อมูลไม่สอดคล้อง (กรุณาตรวจสอบการจับคู่รหัสโรคและยา หรือการส่งข้อมูล)</div>';
                } else {
                    validationStatusHtml = '<div class="alert alert-secondary d-flex align-items-center mb-3"><i class="bi bi-info-circle-fill me-2 fs-5"></i> ทั่วไป (ไม่เข้าเกณฑ์โรคเรื้อรัง ปกส.)</div>';
                }

                let pdxList = [];
                let sdxList = [];
                let icd9List = [];

                diagnoses.forEach(d => {
                    let badge = d.is_chronic 
                        ? '<span class="badge bg-success ms-1 small"><i class="bi bi-check-circle-fill me-1"></i>โรคเรื้อรัง</span>' 
                        : '';
                    if (d.diagtype == '1') {
                        pdxList.push(`<strong class="text-danger">${d.icd10}</strong>${badge}`);
                    } else if (d.diagtype == '2') {
                        icd9List.push(`<span class="text-dark">${d.icd10}</span>`);
                    } else {
                        sdxList.push(`<span class="text-dark fw-bold">${d.icd10}</span>${badge}`);
                    }
                });

                let pdxHtml = pdxList.join(', ') || '-';
                let sdxHtml = sdxList.join(', ') || '-';
                let icd9Html = icd9List.join(', ') || '-';

                let html = `
                    ${validationStatusHtml}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-person-fill me-1"></i> ข้อมูลผู้ป่วย</h6>
                            <table class="table table-sm table-bordered">
                                <tr><th class="bg-light" width="30%">HN</th><td>${visit.hn}</td></tr>
                                <tr><th class="bg-light">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                                <tr><th class="bg-light">เลขบัตร</th><td>${visit.cid}</td></tr>
                                <tr><th class="bg-light">สิทธิการรักษา</th><td>${visit.pttype_name}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-clipboard-pulse me-1"></i> ข้อมูลบริการ</h6>
                            <table class="table table-sm table-bordered">
                                <tr><th class="bg-light" width="30%">วันที่รับบริการ</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                                <tr><th class="bg-light">อาการสำคัญ</th><td>${visit.cc || '-'}</td></tr>
                                <tr><th class="bg-light">PDX</th><td>${pdxHtml}</td></tr>
                                <tr><th class="bg-light">SDX</th><td>${sdxHtml}</td></tr>
                                <tr><th class="bg-light">ICD-9</th><td>${icd9Html}</td></tr>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h6 class="fw-bold text-primary mb-2 d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-capsule-therapeutic me-1"></i> รายการยาที่ได้รับและตรวจสอบ TMT</span>
                            ${drugBadge}
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ชื่อยา</th>
                                        <th class="text-center" width="10%">จำนวน</th>
                                        <th class="text-end" width="15%">ราคา</th>
                                        <th class="text-center" width="20%">รหัส TMT</th>
                                        <th class="text-center" width="15%">สถานะ ปกส.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${drugs.map(d => `
                                        <tr class="${d.is_chronic ? 'table-success-soft' : ''}">
                                            <td>${d.name}</td>
                                            <td class="text-center">${d.qty}</td>
                                            <td class="text-end">${parseFloat(d.sum_price).toFixed(2)}</td>
                                            <td class="text-center"><code>${d.tmtid || '-'}</code></td>
                                            <td class="text-center">
                                                ${d.is_chronic 
                                                    ? '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> ยาโรคเรื้อรัง</span>' 
                                                    : '<span class="text-muted small">-</span>'}
                                            </td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="5" class="text-muted text-center py-3">ไม่พบรายการจ่ายยา</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                body.innerHTML = html;
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>เกิดข้อผิดพลาด: ${xhr.responseJSON?.error || 'ไม่สามารถโหลดข้อมูลได้'}</div>`;
            });
    }

    async function uploadSssZip(type) {
        let inputId = 'zip_file_rep';
        let url = "{{ url('claim_op/sss_rep_import') }}";
        let title = 'นำเข้าข้อมูล REP';
        let isChronic = false;
        
        if (type === 'chronic') {
            inputId = 'zip_file_chronic';
            url = "{{ url('claim_op/sss_chronic_import') }}";
            title = 'นำเข้าข้อมูลโรคเรื้อรัง';
            isChronic = true;
        } else if (type === 'stm') {
            inputId = 'zip_file_stm';
            url = "{{ url('claim_op/sss_stm_import') }}";
            title = 'นำเข้าข้อมูล STM';
        }

        const fileInput = document.getElementById(inputId);
        if (!fileInput.files || fileInput.files.length === 0) {
            return;
        }
        
        const files = Array.from(fileInput.files);
        const totalFiles = files.length;

        // Validate file types before starting upload
        for (let i = 0; i < totalFiles; i++) {
            const file = files[i];
            const nameUpper = file.name.toUpperCase();
            
            // Detect actual type
            let detectedType = null;
            if (nameUpper.includes('BIL') || nameUpper.includes('SOCDBIL')) {
                detectedType = 'rep';
            } else if (nameUpper.includes('STM') || nameUpper.includes('SOGNSTM')) {
                detectedType = 'stm';
            } else if (nameUpper.includes('ACD') || nameUpper.includes('SOCDACD') || nameUpper.includes('REPACD') || nameUpper.includes('REPACDP') || nameUpper.includes('CHRONIC')) {
                detectedType = 'chronic';
            } else if (nameUpper.includes('REP')) {
                detectedType = 'rep';
            }

            if (type !== detectedType) {
                let expectedText = 'REP';
                if (type === 'chronic') expectedText = 'โรคเรื้อรัง';
                if (type === 'stm') expectedText = 'การจ่ายเงิน (STM)';

                Swal.fire({
                    icon: 'error',
                    title: 'เลือกไฟล์ผิดประเภท',
                    text: `ไฟล์ "${file.name}" ไม่ใช่ไฟล์${expectedText} กรุณาเลือกไฟล์ให้ถูกต้อง`
                });
                fileInput.value = '';
                return;
            }
        }

        let successCount = 0;
        let failCount = 0;
        let learnedTpu = 0;
        let learnedDx = 0;
        let errorMessages = [];

        Swal.fire({
            title: `กำลัง${title}...`,
            html: `<div class="progress mb-3" style="height: 22px;">
                      <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                   </div>
                   <div id="import-progress-text" class="small text-muted fw-bold">กำลังเตรียมอัปโหลด...</div>`,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        for (let i = 0; i < totalFiles; i++) {
            const file = files[i];
            const percent = Math.round((i / totalFiles) * 100);
            
            // Update progress UI
            $('#import-progress-bar').css('width', percent + '%').attr('aria-valuenow', percent).text(percent + '%');
            $('#import-progress-text').html(`กำลังนำเข้าไฟล์ที่ ${i + 1} จาก ${totalFiles}: <br><span class="text-primary">${file.name}</span>`);

            const formData = new FormData();
            formData.append('zip_file', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await $.ajax({
                    url: url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false
                });

                successCount++;
                if (isChronic && response.message) {
                    const tpuMatch = response.message.match(/เรียนรู้รหัสยาใหม่ (\d+)/);
                    const dxMatch = response.message.match(/รหัสโรคใหม่ (\d+)/);
                    if (tpuMatch) learnedTpu += parseInt(tpuMatch[1]);
                    if (dxMatch) learnedDx += parseInt(dxMatch[1]);
                }
                if (response.warnings && response.warnings.length > 0) {
                    response.warnings.forEach(warn => {
                        errorMessages.push(`⚠️ ไฟล์ ${file.name} - ${warn}`);
                    });
                }
            } catch (xhr) {
                failCount++;
                let err = `ไฟล์ ${file.name}`;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    err += `: ${xhr.responseJSON.message}`;
                } else {
                    err += ': เกิดข้อผิดพลาดในการเชื่อมต่อ';
                }
                errorMessages.push(err);
            }
        }

        // Set progress to 100% when finished
        $('#import-progress-bar').css('width', '100%').attr('aria-valuenow', 100).text('100%').removeClass('bg-primary').addClass('bg-success');
        $('#import-progress-text').text('เสร็จสิ้นการทำงาน');

        // Show final report
        let reportHtml = `<div class="text-start p-2 border rounded bg-light small mb-2">
            <div class="mb-1 text-success">✔️ สำเร็จ: <strong>${successCount} ไฟล์</strong></div>`;
        if (failCount > 0) {
            reportHtml += `<div class="mb-1 text-danger">❌ ล้มเหลว: <strong>${failCount} ไฟล์</strong></div>`;
        }
        if (isChronic) {
            reportHtml += `<div class="mb-1 text-dark">💊 เรียนรู้รหัสยาใหม่สะสม: <strong>${learnedTpu} รายการ</strong></div>
                <div class="text-dark">🦠 เรียนรู้รหัสโรคใหม่สะสม: <strong>${learnedDx} รหัส</strong></div>`;
        }
        reportHtml += `</div>`;
        
        if (errorMessages.length > 0) {
            reportHtml += `<div class="text-start"><strong class="text-danger small">รายละเอียดข้อผิดพลาด / คำเตือน:</strong>
            <div class="text-danger mt-1 small p-2 border rounded bg-white" style="max-height: 120px; overflow-y: auto;">
                ${errorMessages.join('<br>')}
            </div></div>`;
        }

        Swal.fire({
            icon: (failCount === 0 && errorMessages.length === 0) ? 'success' : (successCount > 0 ? 'warning' : 'error'),
            title: `นำเข้าไฟล์เสร็จสิ้น`,
            html: reportHtml,
            confirmButtonText: 'ตกลง'
        }).then(() => {
            fileInput.value = '';
            if (successCount > 0) {
                shouldReloadOnModalClose = true;
                loadFeedbackList();
            }
        });
    }

    function loadFeedbackList() {
        const body21 = document.getElementById('feedback-21-body');
        const body22 = document.getElementById('feedback-22-body');
        
        // Destroy existing DataTables if initialized
        if ($.fn.DataTable.isDataTable('#table-feedback-21')) {
            $('#table-feedback-21').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#table-feedback-22')) {
            $('#table-feedback-22').DataTable().destroy();
        }

        body21.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</td></tr>';
        body22.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</td></tr>';

        $.get("{{ url('claim_op/sss_chronic_feedback_list') }}")
            .done(function(data) {
                // Populate Tab 2.1
                let html21 = '';
                if (data.list21 && data.list21.length > 0) {
                    data.list21.forEach(row => {
                        html21 += `
                            <tr>
                                <td>${formatThaiShortDate(row.dttran)}</td>
                                <td><strong>${row.hn}</strong></td>
                                <td>${row.ptname || '-'}</td>
                                <td><code>${row.pid || '-'}</code></td>
                                <td><span class="badge bg-danger text-light">${row.dx || '-'}</span></td>
                                <td><span class="badge bg-warning text-dark">${row.drug || '-'}</span></td>
                                <td class="text-muted small">${row.rep_file}</td>
                            </tr>
                        `;
                    });
                }
                body21.innerHTML = html21 || '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการผลตอบกลับประเภท 2.1</td></tr>';

                // Populate Tab 2.2
                let html22 = '';
                if (data.list22 && data.list22.length > 0) {
                    data.list22.forEach(row => {
                        html22 += `
                            <tr>
                                <td>${formatThaiShortDate(row.dttran)}</td>
                                <td><strong>${row.hn}</strong></td>
                                <td>${row.ptname || '-'}</td>
                                <td><code>${row.pid || '-'}</code></td>
                                <td><span class="badge bg-danger text-light">${row.dx || '-'}</span></td>
                                <td><span class="badge bg-warning text-dark">${row.drug || '-'}</span></td>
                                <td class="text-muted small">${row.rep_file}</td>
                            </tr>
                        `;
                    });
                }
                body22.innerHTML = html22 || '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการผลตอบกลับประเภท 2.2</td></tr>';

                // Re-initialize DataTables with pageLength 10
                const dtConfig = {
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                    language: {
                        search: "ค้นหา:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: {
                            previous: "ก่อนหน้า",
                            next: "ถัดไป"
                        }
                    }
                };

                if (data.list21 && data.list21.length > 0) {
                    $('#table-feedback-21').DataTable(dtConfig);
                }
                if (data.list22 && data.list22.length > 0) {
                    $('#table-feedback-22').DataTable(dtConfig);
                }

                // Force column adjustment after rendering
                setTimeout(() => {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }, 150);
            })
            .fail(function() {
                body21.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                body22.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            });
    }

    function formatThaiShortDate(dateStr) {
        if (!dateStr) return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        
        const year = parseInt(parts[0]) + 543;
        const month = parseInt(parts[1]);
        const day = parseInt(parts[2]);
        
        const shortMonths = [
            '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
            'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
        ];
        
        const shortYear = year.toString().slice(-2);
        return `${day} ${shortMonths[month]} ${shortYear}`;
    }

    // Select all / Deselect all claims
    $(document).on('change', '#select_all_claims', function() {
        $('.claim-select-check').prop('checked', this.checked);
    });

    function exportSelectedSSOP() {
        var selectedVns = [];
        $('.claim-select-check:checked').each(function() {
            selectedVns.push($(this).val());
        });

        if (selectedVns.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'คำเตือน',
                text: 'กรุณาเลือกอย่างน้อย 1 รายการเพื่อส่งออก',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Set default random session ID
        var randomSess = Math.floor(Math.random() * (9999 - 1000 + 1)) + 1000;
        $('#export_session_id').val(randomSess);
        $('#export_station_id').val('01');

        // Open modal
        $('#ssopExportModal').modal('show');
    }

    function previewSSOPExport() {
        var selectedVns = [];
        $('.claim-select-check:checked').each(function() {
            selectedVns.push($(this).val());
        });

        var sessionId = $('#export_session_id').val().trim();
        var stationId = $('#export_station_id').val().trim();

        if (!sessionId) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก Session ID' });
            return;
        }
        if (!stationId) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก Station ID' });
            return;
        }

        $('#ssopExportModal').modal('hide');

        Swal.fire({
            title: 'กำลังประมวลผลข้อมูล...',
            text: 'กรุณารอสักครู่ขณะระบบดึงและตรวจสอบข้อมูลพรีวิว',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Destroy existing DataTables if initialized to prevent error
        if ($.fn.DataTable.isDataTable('#table-prev-billtran')) { $('#table-prev-billtran').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-billitems')) { $('#table-prev-billitems').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-billdisp')) { $('#table-prev-billdisp').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-dispenseditems')) { $('#table-prev-dispenseditems').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-opservices')) { $('#table-prev-opservices').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-opdx')) { $('#table-prev-opdx').DataTable().destroy(); }

        $.ajax({
            url: "{{ url('claim_op/sss_export_preview') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                vns: selectedVns,
                session_id: sessionId,
                station_id: stationId
            },
            success: function(response) {
                console.log("AJAX Success response:", response);
                Swal.close();
                if (response.success) {
                    // 1. Populate BILLTRAN & BillItems Table & Raw XML
                    $('#preview-billtran-raw').val(response.billtran_raw);
                    var html1 = '';
                    response.billtran_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        
                        html1 += `<tr>
                            <td>${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td>${fields[2] || ''}</td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td class="text-end fw-bold">${fields[8] || '0.00'}</td>
                            <td class="text-end text-muted">${fields[9] || '0.00'}</td>
                            <td>${fields[10] || ''}</td>
                            <td><span class="badge bg-success">${fields[11] || ''}</span></td>
                            <td>${fields[12] || ''}</td>
                            <td class="fw-bold">${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td>${fields[15] || ''}</td>
                            <td class="text-end text-primary fw-bold">${fields[16] || '0.00'}</td>
                            <td>${fields[17] || ''}</td>
                            <td>${fields[18] || '0.00'}</td>
                        </tr>`;
                    });
                    $('#preview-billtran-tbody').html(html1);

                    var html_items = '';
                    response.billitems_table.forEach(function(fields) {
                        if (fields.length < 5) return;
                        html_items += `<tr>
                            <td class="fw-bold">${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td><span class="badge bg-info">${fields[2] || ''}</span></td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td class="text-center fw-bold">${fields[6] || '0'}</td>
                            <td class="text-end">${fields[7] || '0.00'}</td>
                            <td class="text-end fw-bold">${fields[8] || '0.00'}</td>
                            <td class="text-end text-muted">${fields[9] || '0.00'}</td>
                            <td class="text-end fw-bold text-success">${fields[10] || '0.00'}</td>
                            <td>${fields[11] || ''}</td>
                            <td>${fields[12] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-billitems-tbody').html(html_items);

                    // 2. Populate BILLDISP & DispensedItems Table & Raw XML
                    $('#preview-billdisp-raw').val(response.billdisp_raw);
                    var html2 = '';
                    response.billdisp_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        html2 += `<tr>
                            <td>${fields[0] || ''}</td>
                            <td class="fw-bold text-primary">${fields[1] || ''}</td>
                            <td>${fields[2] || ''}</td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td><span class="badge bg-secondary">${fields[8] || ''}</span></td>
                            <td class="text-end">${fields[9] || '0.00'}</td>
                            <td class="text-end fw-bold">${fields[10] || '0.00'}</td>
                            <td>${fields[11] || '0.00'}</td>
                            <td>${fields[12] || '0.00'}</td>
                            <td>${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td class="text-center fw-bold">${fields[15] || '0'}</td>
                            <td>${fields[16] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-billdisp-tbody').html(html2);

                    var html_dispensed = '';
                    response.dispenseditems_table.forEach(function(fields) {
                        if (fields.length < 5) return;
                        html_dispensed += `<tr>
                            <td class="fw-bold text-primary">${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td>${fields[2] || ''}</td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td><small class="text-muted">${fields[8] || ''}</small></td>
                            <td class="text-center fw-bold">${fields[9] || '0'}</td>
                            <td class="text-end">${fields[10] || '0.00'}</td>
                            <td class="text-end fw-bold">${fields[11] || '0.00'}</td>
                            <td class="text-end text-muted">${fields[12] || '0.00'}</td>
                            <td class="text-end fw-bold text-success">${fields[13] || '0.00'}</td>
                            <td>${fields[14] || ''}</td>
                            <td>${fields[15] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-dispenseditems-tbody').html(html_dispensed);

                    // 3. Populate OPServices & OPDx Table & Raw XML
                    $('#preview-opservices-raw').val(response.opservices_raw);
                    var html3 = '';
                    response.opservices_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        html3 += `<tr>
                            <td>${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td><span class="badge bg-info">${fields[2] || ''}</span></td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td>${fields[8] || ''}</td>
                            <td>${fields[9] || ''}</td>
                            <td>${fields[10] || ''}</td>
                            <td>${fields[11] || ''}</td>
                            <td>${fields[12] || ''}</td>
                            <td>${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td>${fields[15] || ''}</td>
                            <td>${fields[16] || ''}</td>
                            <td>${fields[17] || ''}</td>
                            <td>${fields[18] || '0.00'}</td>
                            <td><span class="badge bg-success">${fields[19] || ''}</span></td>
                            <td>${fields[20] || ''}</td>
                            <td>${fields[21] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-opservices-tbody').html(html3);

                    var html_opdx = '';
                    response.opdx_table.forEach(function(fields) {
                        if (fields.length < 4) return;
                        var typeBadge = fields[2] == '1' 
                            ? '<span class="badge bg-danger">โรคหลัก (PDX)</span>'
                            : '<span class="badge bg-secondary">โรคร่วม/อื่น ๆ</span>';
                        html_opdx += `<tr>
                            <td>${fields[0] || ''}</td>
                            <td class="fw-bold">${fields[1] || ''}</td>
                            <td>${typeBadge}</td>
                            <td><span class="badge bg-info">${fields[3] || ''}</span></td>
                            <td class="fw-bold text-dark">${fields[4] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-opdx-tbody').html(html_opdx);

                    // Initialize DataTables for Preview Tables
                    const prevDtConfig = {
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        },
                        scrollY: "300px",
                        scrollCollapse: true,
                        scrollX: true,
                        autoWidth: false
                    };
                    $('#table-prev-billtran').DataTable(prevDtConfig);
                    $('#table-prev-billitems').DataTable(prevDtConfig);
                    $('#table-prev-billdisp').DataTable(prevDtConfig);
                    $('#table-prev-dispenseditems').DataTable(prevDtConfig);
                    $('#table-prev-opservices').DataTable(prevDtConfig);
                    $('#table-prev-opdx').DataTable(prevDtConfig);

                    // Open Preview Modal
                    $('#ssopPreviewModal').modal('show');
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: response.error || 'ไม่สามารถประมวลผลข้อมูลพรีวิวได้' });
                }
            },
            error: function(xhr) {
                console.error("AJAX Error details:", xhr.status, xhr.statusText, xhr.responseText);
                Swal.close();
                var msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : ('เกิดข้อผิดพลาดในการเชื่อมต่อ (สถานะ: ' + xhr.status + ' ' + xhr.statusText + ')');
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg });
            }
        });
    }

    function triggerActualDownload() {
        var selectedVns = [];
        $('.claim-select-check:checked').each(function() {
            selectedVns.push($(this).val());
        });

        var sessionId = $('#export_session_id').val().trim();
        var stationId = $('#export_station_id').val().trim();

        $('#ssopPreviewModal').modal('hide');

        Swal.fire({
            title: 'กำลังสร้างไฟล์...',
            text: 'กรุณารอสักครู่ขณะสร้างไฟล์ Zip เพื่อดาวน์โหลด',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Standard POST form submit to trigger file download
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ url('claim_op/sss_export_ssop') }}";
        
        // CSRF Token
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = "{{ csrf_token() }}";
        form.appendChild(csrfInput);

        // Session ID & Station ID
        var sessInput = document.createElement('input');
        sessInput.type = 'hidden';
        sessInput.name = 'session_id';
        sessInput.value = sessionId;
        form.appendChild(sessInput);

        var statInput = document.createElement('input');
        statInput.type = 'hidden';
        statInput.name = 'station_id';
        statInput.value = stationId;
        form.appendChild(statInput);

        // Selected VNs
        selectedVns.forEach(function(vn) {
            var vnInput = document.createElement('input');
            vnInput.type = 'hidden';
            vnInput.name = 'vns[]';
            vnInput.value = vn;
            form.appendChild(vnInput);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(function() {
            Swal.close();
        }, 2000);
    }

    // Copy XML text to clipboard
    $(document).on('click', '.btn-copy-xml', function() {
        var targetId = $(this).attr('data-target');
        var textarea = document.getElementById(targetId);
        textarea.select();
        document.execCommand('copy');
        
        var $btn = $(this);
        $btn.html('<i class="bi bi-check2"></i> Copied!').removeClass('btn-secondary').addClass('btn-success');
        setTimeout(function() {
            $btn.html('<i class="bi bi-clipboard"></i> Copy').removeClass('btn-success').addClass('btn-secondary');
        }, 2000);
    });

    // Auto adjust columns for hidden tables inside preview modal on shown
    $('#ssopPreviewModal').on('shown.bs.modal', function () {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    $('#previewTab button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });

    // Custom showDetails function to display SSOP validation checks on the eye button
    window.showDetails = function(vn) {
        const body = document.getElementById('detailsModalBody');
        if (!body) return;
        body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
        $('#detailsModal').modal('show');

        $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
            .done(function(data) {
                const visit = data.visit;
                const diagnoses = data.diagnoses;
                const drugs = data.drugs;

                // 1. Run Validation Checks
                const errors = [];
                const invoice_no = visit.sss_invno ? visit.sss_invno : (visit.debt_id_list ? visit.debt_id_list : '');
                
                // BILLTRAN checks (Treat 0 or '0' as empty)
                if (!invoice_no || invoice_no === '0' || invoice_no === 0 || invoice_no === '0.00') {
                    errors.push("ไม่พบเลขใบแจ้งหนี้ (InvoiceNo) กรุณากดออกใบแจ้งหนี้ใน HOSxP");
                }
                if (!visit.cid || visit.cid.length !== 13) {
                    errors.push("เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก");
                }
                if (!visit.hn) {
                    errors.push("ไม่พบ HN");
                }

                // OPServices checks
                let has_pdx = false;
                diagnoses.forEach(function(d) {
                    if (d.diagtype == '1') {
                        has_pdx = true;
                    }
                });
                if (!has_pdx) {
                    errors.push("ไม่พบรหัสวินิจฉัยโรคหลัก (PDX) กรุณาบันทึกแพทย์ผู้ตรวจโรค");
                }

                // Claim money checks (uc_money <= 0)
                const uc_money = parseFloat(visit.uc_money || 0);
                if (uc_money <= 0) {
                    errors.push("ยอดเงินเรียกเก็บ (uc_money) น้อยกว่าหรือเท่ากับ 0 บาท (ไม่มีค่าใช้จ่ายที่จะเรียกเก็บ)");
                }



                // Status Badge
                const isValid = errors.length === 0;
                const statusBadge = isValid
                    ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> พร้อมส่งออก (ผ่านเกณฑ์)</span>'
                    : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่านเกณฑ์ (' + errors.length + ' ข้อ)</span>';

                let warningHtml = '';
                if (errors.length > 0) {
                    warningHtml = `
                    <div class="col-12">
                      <div class="alert alert-danger py-2 px-3 mb-1 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">เงื่อนไขที่ไม่พร้อมส่งออก (สาเหตุที่รูปตาขึ้นสีแดง):</div>
                          <ul class="mb-0 ps-3">
                            ${errors.map(err => `<li>${err}</li>`).join('')}
                          </ul>
                        </div>
                      </div>
                    </div>`;
                }

                let pdx = visit.pdx || '-';
                let sec_diags = [];
                let procedures = [];

                diagnoses.forEach(function(d) {
                    if (d.diagtype == '2') {
                        procedures.push(d.icd10);
                    } else if (d.diagtype != '1') {
                        sec_diags.push(d.icd10);
                    }
                });

                let html = `
                <div class="row g-3">
                  ${warningHtml}

                  <!-- Patient & Financial Info -->
                  <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วยและสิทธิ์การเงิน</div>
                        <table class="table table-sm table-borderless mb-0 small">
                          <tr><th class="text-muted" style="width:35%">HN</th><td class="fw-bold text-dark">${visit.hn}</td></tr>
                          <tr><th class="text-muted">CID</th><td class="text-dark">${visit.cid ?? '-'}</td></tr>
                          <tr><th class="text-muted">ชื่อ-สกุล</th><td class="text-dark">${visit.ptname}</td></tr>
                          <tr><th class="text-muted">สิทธิ์การรักษา</th><td class="text-dark">${visit.pttype_name ?? '-'}</td></tr>
                          <tr><th class="text-muted">เลขใบแจ้งหนี้</th><td class="fw-bold ${invoice_no && invoice_no !== '0' ? 'text-success' : 'text-danger'}">${invoice_no && invoice_no !== '0' ? invoice_no : 'ไม่มี (VN: ' + vn + ')'}</td></tr>
                          <tr><th class="text-muted">รวมค่ารักษา</th><td class="text-dark">${parseFloat(visit.income).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชำระเงินจริง</th><td class="text-dark">${parseFloat(visit.rcpt_money).toFixed(2)} บาท</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- Clinical Info -->
                  <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                        <table class="table table-sm table-borderless mb-0 small">
                          <tr><th class="text-muted" style="width:35%">วันที่รับบริการ</th><td class="text-dark">${visit.vstdate} ${visit.vsttime}</td></tr>
                          <tr><th class="text-muted">CC</th><td class="text-dark">${visit.cc ?? '-'}</td></tr>
                          <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${pdx}</td></tr>
                          <tr><th class="text-muted">SDX</th><td class="text-dark">${sec_diags.join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-9</th><td class="text-dark">${procedures.join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>`;

                // Drugs Section
                let drugsList = drugs.filter(d => d.icode.startsWith('1'));
                let servicesList = drugs.filter(d => !d.icode.startsWith('1'));

                let drugsRows = '';
                if (drugsList.length === 0) {
                    drugsRows = '<tr><td colspan="4" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน Visit นี้</td></tr>';
                } else {
                    drugsList.forEach(function(d) {
                        let tmtDisplay = d.tmtid 
                            ? `<span class="badge bg-success fw-bold">${d.tmtid}</span>`
                            : `<span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> ไม่มีรหัส TMT</span>`;
                            
                        let sigtext = d.drugusage_text ? d.drugusage_text.trim() : '';
                        let prdcatInt = parseInt(d.sks_product_category_id);
                        if (prdcatInt >= 1 && prdcatInt <= 5) {
                            if (!sigtext) {
                                sigtext = 'ตามแพทย์สั่ง';
                            }
                        }

                        drugsRows += `<tr>
                          <td>
                            <div class="fw-bold text-dark">${d.name}</div>
                            <div class="text-muted small mb-1" style="font-size: 0.75rem;"><i class="bi bi-info-circle me-1"></i>วิธีใช้: ${sigtext || '-'}</div>
                            <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                          </td>
                          <td class="text-center fw-bold">${d.qty}</td>
                          <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                          <td>${tmtDisplay}</td>
                        </tr>`;
                    });
                }

                let servicesRows = '';
                if (servicesList.length === 0) {
                    servicesRows = '<tr><td colspan="4" class="text-center text-muted py-3">ไม่พบรายการค่าบริการ/รักษาพยาบาลใน Visit นี้</td></tr>';
                } else {
                    servicesList.forEach(function(d) {
                        servicesRows += `<tr>
                          <td>
                            <div class="fw-bold text-dark">${d.name}</div>
                            <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                          </td>
                          <td class="text-center fw-bold">${d.qty}</td>
                          <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                          <td><span class="text-muted">-</span></td>
                        </tr>`;
                    });
                }

                html += `
                  <!-- Split Tabs for Drugs and Services -->
                  <div class="col-12 mt-3">
                    <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                      <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab" aria-controls="modal-drugs-panel" aria-selected="true">
                          <i class="bi bi-capsule me-1"></i>รายการสั่งจ่ายยา
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab" aria-controls="modal-services-panel" aria-selected="false">
                          <i class="bi bi-list-check me-1"></i>ค่าบริการและค่ารักษาพยาบาล
                        </button>
                      </li>
                    </ul>
                    <div class="tab-content" id="modalDetailTabsContent">
                      <!-- Drugs Panel -->
                      <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" aria-labelledby="modal-drugs-tab">
                        <table class="table table-sm table-hover align-middle mb-0 small border">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อยา/เวชภัณฑ์</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="15%">ราคารวม (บาท)</th>
                              <th width="25%">รหัสมาตรฐาน TMT</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${drugsRows}
                          </tbody>
                        </table>
                      </div>
                      <!-- Services Panel -->
                      <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" aria-labelledby="modal-services-tab">
                        <table class="table table-sm table-hover align-middle mb-0 small border">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อบริการ/ค่ารักษาพยาบาล</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="15%">ราคารวม (บาท)</th>
                              <th width="25%">รหัสอ้างอิง</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${servicesRows}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>`;

                body.innerHTML = html;
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="alert alert-danger mb-0">เกิดข้อผิดพลาดในการโหลดรายละเอียด: ${xhr.statusText}</div>`;
            });
    };
  </script>
@endpush

<script src="{{ asset('assets/vendor/chart.js/chart.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js') }}"></script>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    new Chart(document.querySelector('#sum_month'), {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($month); ?>,
        datasets: [
          {
            label: 'เรียกเก็บ',
            data: <?php echo json_encode($claim_price); ?>,
            backgroundColor: 'rgba(185, 28, 28, 0.75)',
            borderColor: 'rgb(185, 28, 28)',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'ชดเชย',
            data: <?php echo json_encode($receive_total); ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1,
            borderRadius: 4
          }
        ]
      }, 
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            labels: {
                usePointStyle: true,
                boxWidth: 6
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + context.formattedValue + ' บาท';
              }
            }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#000',
            font: {
              weight: 'bold',
              size: 10
            },
            formatter: (value) => value > 0 ? value.toLocaleString() : ''
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return value.toLocaleString();
              }
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  });
</script>
