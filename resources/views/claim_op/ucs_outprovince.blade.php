@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ UC-OP ต่างจังหวัด
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

        <!-- Section 2: Tabs & Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด
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
                            <button onclick="checkFdhBulk(event)" type="button" class="btn btn-info text-white px-3 shadow-sm" title="ดึงสถานะ FDH ตามช่วงเวลาที่เลือก (ทีละ 1 วัน)">
                                <i class="bi bi-arrow-repeat me-1"></i> ดึง FDH
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Claim -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th> 
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">เบิก/ส่ง</th>
                                    <th class="text-center">วัน-เวลา | Q</th>     
                                    <th class="text-center">HN</th>    
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center">Project</th>
                                    <th class="text-center">ค่ารักษา</th> 
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center">กองทุนอื่น</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_other_price = 0;
                                    $sum_claim_price = 0;
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ $row->endpoint == 'Y' ? '2' : '1' }}">
                                        @if($row->endpoint == 'Y')
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height:26px; min-height:26px; margin:0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height:26px; min-height:26px; margin:0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ยังไม่ปิดสิทธิ สปสช. | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size:0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size:0.72rem;">
                                                <span class="text-muted">พร้อมส่ง:</span>
                                                @if($row->confirm_and_locked == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="พร้อมส่ง Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ยังไม่พร้อมส่ง N"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size:0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width:150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width:150px;" title="{{$row->pttype}} [{{$row->hospmain}}]">{{$row->pttype}} [{{$row->hospmain}}]</div>
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->project }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->other_price,2) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price, 2) }}</td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_other_price += $row->other_price;
                                    $sum_claim_price += $row->claim_price;
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="7" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_other_price,2) }}</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div>  
                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">#</th>  
                                    <th class="text-center" rowspan="2">สถานะ</th>
                                    <th class="text-center" rowspan="2">เบิก/ส่ง</th>
                                    <th class="text-center" rowspan="2" width="10%">วัน-เวลา | Q</th>     
                                    <th class="text-center" rowspan="2">HN</th> 
                                    <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" rowspan="2">Project</th>
                                    <th class="text-center" colspan="4">ค่ารักษา</th> 
                                    <th class="text-center bg-primary-soft" colspan="3">ข้อมูลการชดเชย</th>
                                </tr>
                                <tr>
                                    <th class="text-center small">รวม</th>
                                    <th class="text-center small">ชำระเอง</th>
                                    <th class="text-center small">กองทุนอื่น</th>
                                    <th class="text-center small text-primary">เรียกเก็บ</th>
                                    <th class="text-center bg-primary-soft small">STM ชดเชย</th> 
                                    <th class="text-center bg-primary-soft small">ผลต่าง</th> 
                                    <th class="text-center bg-primary-soft small">REP No.</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_other_price = 0;
                                    $sum_claim_price = 0;
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ $row->endpoint == 'Y' ? '2' : '1' }}">
                                        @if($row->endpoint == 'Y')
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height:26px; min-height:26px; margin:0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height:26px; min-height:26px; margin:0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ยังไม่ปิดสิทธิ สปสช. | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size:0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size:0.72rem;">
                                                <span class="text-muted">พร้อมส่ง:</span>
                                                @if($row->confirm_and_locked == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="พร้อมส่ง Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ยังไม่พร้อมส่ง N"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size:0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width:150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width:150px;" title="{{$row->pttype}} [{{$row->hospmain}}]">{{$row->pttype}} [{{$row->hospmain}}]</div>
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->project }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->other_price,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price, 2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    @php $diff = $row->receive_total - $row->claim_price; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_other_price += $row->other_price;
                                    $sum_claim_price += $row->claim_price;
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="7" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_other_price,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_claim_price, 2) }}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    @php $total_diff = $sum_receive_total - $sum_claim_price; @endphp
                                    <th class="text-end small fw-bold {{ $total_diff > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($total_diff, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 
            </div>
        </div>
    </div>
</div>

{{-- ── Details Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-clipboard2-pulse-fill me-2"></i>รายละเอียดการรับบริการ</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<style>
.spin { animation: spin 1s linear infinite; display: inline-block; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.badge-type { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.badge-ppfs  { background:#fff3cd; color:#856404; }
.badge-uc_cr { background:#cce5ff; color:#004085; }
.badge-herb  { background:#d4edda; color:#155724; }
</style>

<script>
const VISIT_DETAILS_URL = "{{ url('claim_op/ucs_outprovince/visit_details') }}";

function showDetails(vn) {
    const body = document.getElementById('detailsModalBody');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
    $('#detailsModal').modal('show');

    $.get(VISIT_DETAILS_URL, { vn: vn })
        .done(function(data) {
            const visit = data.visit;
            const items = data.items;
            const v     = data.validation;
            const isEndpointDone = v.endpoint_valid === true;

            function makeCellHtml(epDone) {
                if (epDone) {
                    return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem;height:26px;margin:0 auto;" onclick="showDetails('${vn}')" title="ปิดสิทธิแล้ว"><i class="bi bi-eye-fill"></i></button>`;
                } else {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem;height:26px;margin:0 auto;" onclick="showDetails('${vn}')" title="ยังไม่ปิดสิทธิ สปสช."><i class="bi bi-eye-fill"></i></button>`;
                }
            }
            const dataOrder = isEndpointDone ? '2' : '1';
            const searchRow = document.getElementById(`td-status-search-${vn}`);
            const claimRow  = document.getElementById(`td-status-claim-${vn}`);
            if (searchRow) { searchRow.innerHTML = makeCellHtml(isEndpointDone); searchRow.setAttribute('data-order', dataOrder); }
            if (claimRow)  { claimRow.innerHTML  = makeCellHtml(isEndpointDone); claimRow.setAttribute('data-order', dataOrder); }

            let endpointBtn = isEndpointDone
                ? `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`
                : `<button onclick="pullNhsoData('${visit.vstdate}','${visit.cid}','${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;

            let fdhBtn = visit.fdh_status
                ? `<div class="d-inline-flex gap-2 align-items-center"><span class="badge bg-success py-1 px-2 text-wrap" style="max-width:180px;">${visit.fdh_status}</span><button onclick="checkFdh('${visit.hn}','${vn}')" class="btn btn-outline-success btn-sm py-0 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึงอีกครั้ง</button></div>`
                : `<div class="d-inline-flex gap-2 align-items-center"><span class="badge bg-secondary py-1 px-2">ยังไม่ได้ส่งเคลม</span><button onclick="checkFdh('${visit.hn}','${vn}')" class="btn btn-outline-info btn-sm py-0 px-2 fw-bold text-dark" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึง/ส่ง FDH</button></div>`;

            let html = `
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card border-0 bg-light-soft h-100">
                  <div class="card-body py-2 px-3">
                    <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                    <table class="table table-sm table-borderless mb-0 small">
                      <tr><th class="text-muted" style="width:35%">HN</th><td class="fw-bold">${visit.hn}</td></tr>
                      <tr><th class="text-muted">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                      <tr><th class="text-muted">สิทธิ์</th><td>${visit.pttype ?? '-'}</td></tr>
                      <tr><th class="text-muted">เพศ/อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : visit.sex)} / ${visit.age_y ?? '-'} ปี</td></tr>
                      <tr><th class="text-muted">สถานะปิดสิทธิ</th><td>${endpointBtn}</td></tr>
                      <tr><th class="text-muted">สถานะ FDH</th><td>${fdhBtn}</td></tr>
                    </table>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-0 bg-light-soft h-100">
                  <div class="card-body py-2 px-3">
                    <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                    <table class="table table-sm table-borderless mb-0 small">
                      <tr><th class="text-muted" style="width:35%">วันที่</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                      <tr><th class="text-muted">CC</th><td>${visit.cc ?? '-'}</td></tr>
                      <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${visit.pdx ?? '-'}</td></tr>
                      <tr><th class="text-muted">SDX</th><td>${data.sec_diags.join(', ') || '-'}</td></tr>
                      <tr><th class="text-muted">ICD-9</th><td>${data.procedures.join(', ') || '-'}</td></tr>
                    </table>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <div class="fw-bold small text-dark mb-2"><i class="bi bi-list-check me-1"></i>รายการ (opitemrece ทุกรายการ)</div>
                <div class="table-responsive">
                  <table class="table table-sm table-hover small mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>icode</th><th>รายการ</th><th>ประเภท</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end">ราคา/หน่วย</th>
                        <th class="text-end">รวม</th>
                      </tr>
                    </thead><tbody>`;

            items.forEach(function(item) {
                let type = '';
                if (item.ppfs   === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                if (item.uc_cr  === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                if (item.herb32 === 'Y') type += '<span class="badge-type badge-herb me-1">Herb</span>';
                if (item.kidney === 'Y') type += '<span class="badge-type" style="background:#f8d7da;color:#721c24;">Kidney</span>';
                html += `<tr>
                    <td class="text-muted">${item.icode}</td>
                    <td>${item.name ?? '-'}</td>
                    <td>${type || '<span class="text-muted">-</span>'}</td>
                    <td class="text-center">${item.qty}</td>
                    <td class="text-end">${parseFloat(item.unitprice).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                    <td class="text-end fw-bold">${parseFloat(item.sum_price).toLocaleString('th-TH',{minimumFractionDigits:2})}</td>
                </tr>`;
            });

            html += `</tbody></table></div></div></div>`;
            body.innerHTML = html;
        })
        .fail(function() {
            body.innerHTML = '<div class="alert alert-warning">ไม่สามารถโหลดข้อมูลได้</div>';
        });
}
</script>

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

{{-- ✅ FDH Check Claim ------------------------------------------------------------ --}}
<script>
  function checkFdh(hn, seq) {

      Swal.fire({
          title: 'กำลังตรวจสอบสถานะ...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
      });

      $.ajax({
          url: "{{ url('/api/fdh/check-claim-indiv') }}",
          type: "POST",
          data: {
              hn: hn,
              seq: seq,
              _token: "{{ csrf_token() }}"
          },
          success: function (res) {

              // ------------------------------
              // ✔ FDH ตอบสำเร็จ (200)
              // ------------------------------
              if (res.status === 200) {
                  Swal.fire({
                      icon: 'success',
                      title: 'ตรวจสอบสำเร็จ',
                      text: 'พบข้อมูลในระบบ FDH',
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      fetchData();
                      $('#form_indiv').submit();
                  });
                  return;
              }

              // ------------------------------
              // ✔ ไม่พบข้อมูล FDH (404)
              // ------------------------------
              if (res.status === 404 || res.status === 500) {
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบข้อมูลในระบบ FDH',
                      text: res.body?.message_th ?? "ไม่มีรายการนี้ส่ง"
                  });
                  return;
              }

              // ------------------------------
              // ✔ ปัญหาฝั่งระบบ หรือ token/validate
              // ------------------------------
              if (res.status === 400) {
                  Swal.fire({
                      icon: 'error',
                      title: 'เกิดข้อผิดพลาด',
                      text: res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้'
                  });
                  return;
              }
          },

          error: function () {
              Swal.fire({
                  icon: 'error',
                  title: 'การเชื่อมต่อล้มเหลว',
                  text: 'ไม่สามารถเรียก API ได้ (Network Error)'
              });
          }
      });
  }

  function pullNhsoData(vstdate, cid, vn) {
      Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading()
          }
      });

      fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
              "Accept": "application/json"
          },
          body: JSON.stringify({
              vstdate: vstdate,
              cid: cid
          })
      })
          .then(async response => {
              const data = await response.json();
              if (!response.ok) {
                  throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
              }
              return data;
          })
          .then(data => {
              if (data.found) {
                  Swal.fire({
                      icon: 'success',
                      title: 'พบข้อมูลปิดสิทธิ',
                      text: data.message,
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      if (vn) {
                          showDetails(vn);
                      } else {
                          location.reload();
                      }
                  });
              } else {
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                      text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                      showCancelButton: true,
                      confirmButtonColor: '#3085d6',
                      cancelButtonColor: '#6c757d',
                      confirmButtonText: 'ปิดสิทธิเลย',
                      cancelButtonText: 'ยกเลิก'
                  }).then(result => {
                      if (result.isConfirmed) {
                          pushNhsoData(cid, vstdate, vn);
                      }
                  });
              }
          })
          .catch(error => {
              Swal.fire({
                  icon: 'error',
                  title: 'เกิดข้อผิดพลาด',
                  text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
              });
          });
  }

  function pushNhsoData(cid, vstdate, vn) {
      Swal.fire({
          title: 'ยืนยันการส่งข้อมูล?',
          text: "ระบบจะดึงข้อมูลจาก HOSxP และส่งไปปิดสิทธิที่ สปสช.",
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'ตกลง, ส่งข้อมูล!',
          cancelButtonText: 'ยกเลิก'
      }).then((result) => {
          if (result.isConfirmed) {
              Swal.fire({
                  title: 'กำลังดำเนินการ...',
                  allowOutsideClick: false,
                  didOpen: () => {
                      Swal.showLoading()
                  }
              });

              $.ajax({
                  url: "{{ route('api.nhso.push_indiv') }}",
                  type: "POST",
                  data: {
                      _token: "{{ csrf_token() }}",
                      cid: cid,
                      vstdate: vstdate
                  },
                  success: function(response) {
                      if (response.status == 'success') {
                          Swal.fire({
                              icon: 'success',
                              title: 'สำเร็จ!',
                              text: 'ปิดสิทธิเรียบร้อยแล้ว',
                              timer: 1500,
                              showConfirmButton: false
                          }).then(() => {
                              if (vn) {
                                  showDetails(vn);
                              } else {
                                  location.reload();
                              }
                          });
                      } else {
                          Swal.fire({
                              icon: 'error',
                              title: 'ไม่สำเร็จ',
                              text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                          });
                      }
                  },
                  error: function(xhr) {
                      let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                      if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                      Swal.fire({
                          icon: 'error',
                          title: 'เกิดข้อผิดพลาด',
                          text: msg
                      });
                  }
              });
          }
      });
  }
</script>

@endsection

@push('scripts')
  <script>
    $(document).ready(function () {

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


      $('#t_search').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
        }
      });

      $('#t_claim').DataTable({
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
              title: 'รายชื่อผู้มารับบริการ UC-OP ต่างจังหวัด ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
        }
      });
    });
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
            backgroundColor: 'rgba(249, 115, 22, 0.6)',
            borderColor: 'rgb(249, 115, 22)',
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