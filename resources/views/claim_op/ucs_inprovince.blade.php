@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ UC-OP ในจังหวัด
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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ UC-OP ในจังหวัด
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
                                    <th class="text-center">รายการต้องเรียกเก็บ</th>  
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
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
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
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->project }}</td>
                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:180px;">{{ $row->claim_list }}</td>
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
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
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
                                    <th class="text-center" rowspan="2">รายการต้องเรียกเก็บ</th>
                                    <th class="text-center" colspan="5">ค่ารักษา</th>                                     
                                    <th class="text-center bg-primary-soft" colspan="3">ข้อมูลการชดเชย</th>
                                </tr>
                                <tr>                                    
                                    <th class="text-center small">รวม</th>
                                    <th class="text-center small">ชำระเอง</th>                                                                  
                                    <th class="text-center small">บริการเฉพาะ</th>
                                    <th class="text-center small">PPFS</th>
                                    <th class="text-center small">สมุนไพร</th>
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
                                    $sum_uc_cr = 0; 
                                    $sum_ppfs = 0; 
                                    $sum_herb = 0; 
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->confirm_and_locked == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">ประสงค์เบิก:</span>
                                                @if($row->request_funds == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
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
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}}</div>
                                        <div class="small fw-bold">Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->project }}</td>
                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:180px;">{{ $row->claim_list }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>                                      
                                    <td class="text-end small">{{ number_format($row->uc_cr,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->ppfs,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->herb,2) }}</td> 
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    @php $diff = $row->receive_total - $row->uc_cr - $row->ppfs - $row->herb; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_uc_cr += $row->uc_cr; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_herb += $row->herb; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="8" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_uc_cr,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_herb,2) }}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    @php $total_diff = $sum_receive_total - $sum_uc_cr - $sum_ppfs - $sum_herb; @endphp
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

{{-- ── Validation Modal ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>เงื่อนไขที่ไม่ผ่านเกณฑ์การเคลม</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="validationModalBody">
                <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
            </div>
            <div class="modal-footer border-0">
                <small class="text-muted me-auto"><i class="bi bi-info-circle me-1"></i>กรุณาแก้ไขข้อมูลที่ HOSxP แล้วโหลดหน้าใหม่</small>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Details Modal ─────────────────────────────────────────────────────── --}}
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
const VISIT_DETAILS_URL = "{{ url('claim_op/ucs_inprovince/visit_details') }}";

// ── Validation Modal ──────────────────────────────────────────────────────
function showValidation(vn) {
    const body = document.getElementById('validationModalBody');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
    $('#validationModal').modal('show');

    $.get(VISIT_DETAILS_URL, { vn: vn })
        .done(function(data) {
            const v = data.validation;
            const visit = data.visit;
            let html = `
                <div class="mb-3 p-3 rounded" style="background:#f8f9fa;border-left:4px solid #dc3545;">
                    <div class="fw-bold small text-dark mb-1">${visit.ptname} &nbsp;|&nbsp; HN: ${visit.hn} &nbsp;|&nbsp; VN: ${vn}</div>
                    <div class="text-muted" style="font-size:0.75rem;">วันที่: ${visit.vstdate} เวลา: ${visit.vsttime}</div>
                </div>`;
            if (v.is_valid) {
                html += '<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>ผ่านเงื่อนไขทั้งหมด</div>';
            } else {
                html += '<div class="alert alert-danger p-2 mb-2"><strong><i class="bi bi-x-octagon-fill me-1"></i>พบปัญหาที่ต้องแก้ไขจำนวน ' + v.errors.length + ' รายการ</strong></div>';
                html += '<ul class="list-group list-group-flush">';
                v.errors.forEach(function(err) {
                    html += `<li class="list-group-item list-group-item-danger py-2 small"><i class="bi bi-x-circle-fill me-2"></i>${err}</li>`;
                });
                html += '</ul>';
            }
            body.innerHTML = html;
        })
        .fail(function() {
            body.innerHTML = '<div class="alert alert-warning">ไม่สามารถโหลดข้อมูลได้</div>';
        });
}

// ── Details Modal ─────────────────────────────────────────────────────────
function showDetails(vn) {
    const body = document.getElementById('detailsModalBody');
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
    $('#detailsModal').modal('show');

    $.get(VISIT_DETAILS_URL, { vn: vn })
        .done(function(data) {
            const visit = data.visit;
            const items = data.items;
            const v     = data.validation;

            const statusBadge = v.is_valid
                ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> ผ่านเงื่อนไข</span>'
                : '<span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน ' + v.errors.length + ' รายการ</span>';

            const isEndpointDone = v.endpoint_valid === true;
            const hasWarnings    = v.warnings && v.warnings.length > 0;

            function makeCellHtml(isValid, epDone, warn) {
                if (!isValid) {
                    return `<button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else if (warn) {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else if (epDone) {
                    return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                } else {
                    return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                }
            }

            const dataOrder = !v.is_valid ? '0' : (isEndpointDone && !hasWarnings ? '2' : '1');

            const searchRow = document.getElementById(`td-status-search-${vn}`);
            const claimRow  = document.getElementById(`td-status-claim-${vn}`);
            if (searchRow) {
                searchRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                searchRow.setAttribute('data-order', dataOrder);
                $('#t_search').DataTable().cell(searchRow).invalidate().draw(false);
            }
            if (claimRow) {
                claimRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                claimRow.setAttribute('data-order', dataOrder);
                $('#t_claim').DataTable().cell(claimRow).invalidate().draw(false);
            }

            let endpointBtn = '';
            if (v.endpoint_valid) {
                endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
            } else {
                endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
            }

            let fdhBtn = '';
            if (visit.fdh_status) {
                fdhBtn = `
                    <div class="d-inline-flex gap-2 align-items-center">
                        <span class="badge bg-success py-1 px-2 text-wrap" style="max-width:180px;">${visit.fdh_status}</span>
                        <button onclick="checkFdh('${visit.hn}', '${vn}')" class="btn btn-outline-success btn-sm py-0 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึงอีกครั้ง</button>
                    </div>`;
            } else {
                fdhBtn = `
                    <div class="d-inline-flex gap-2 align-items-center">
                        <span class="badge bg-secondary py-1 px-2">ยังไม่ได้ส่งเคลม</span>
                        <button onclick="checkFdh('${visit.hn}', '${vn}')" class="btn btn-outline-info btn-sm py-0 px-2 fw-bold text-dark" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>ดึง/ส่ง FDH</button>
                    </div>`;
            }

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
                      <tr><th class="text-muted">ประสงค์เบิก</th><td>${visit.request_funds === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                      <tr><th class="text-muted">พร้อมส่ง</th><td>${visit.confirm_and_locked === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
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
              </div>`;

            if (!v.is_valid) {
                html += `
              <div class="col-12">
                <div class="alert alert-danger py-2 mb-0 small">
                  <strong><i class="bi bi-x-octagon-fill me-1"></i>เงื่อนไขที่ไม่ผ่าน:</strong>
                  <ul class="mb-0 mt-1 ps-3">`;
                v.errors.forEach(function(err) { html += `<li>${err}</li>`; });
                html += `</ul></div></div>`;
            }

            html += `
              <div class="col-12">
                <div class="fw-bold small text-dark mb-2"><i class="bi bi-list-check me-1"></i>รายการเรียกเก็บ ${statusBadge}</div>
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
                if (item.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                if (item.uc_cr === 'Y') type += '<span class="badge-type badge-uc_cr me-1">UC_CR</span>';
                if (item.herb32=== 'Y') type += '<span class="badge-type badge-herb me-1">Herb</span>';
                html += `<tr>
                    <td class="text-muted">${item.icode}</td>
                    <td>${item.name ?? '-'}</td>
                    <td>${type}</td>
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
              const isSearchTab = $(`#td-status-search-${seq}`).length > 0;

              if (res.status === 200) {
                  Swal.fire({
                      icon: 'success',
                      title: 'ตรวจสอบสำเร็จ',
                      text: 'พบข้อมูลในระบบ FDH',
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      if (isSearchTab) {
                          localStorage.setItem('active_tab', '#claim');
                          fetchData();
                          $('#form_indiv').submit();
                      } else {
                          showDetails(seq);
                      }
                  });
                  return;
              }

              if (res.status === 404 || res.status === 500) {
                  const statusText = res.body?.message_th ?? "ไม่มีรายการนี้ส่ง";
                  Swal.fire({
                      icon: 'warning',
                      title: 'ไม่พบข้อมูลในระบบ FDH',
                      text: statusText
                  }).then(() => {
                      showDetails(seq);
                  });
                  return;
              }

              if (res.status === 400) {
                  const statusText = res.body?.message ?? res.error ?? 'ไม่สามารถตรวจสอบได้';
                  Swal.fire({
                      icon: 'error',
                      title: 'เกิดข้อผิดพลาด',
                      text: statusText
                  }).then(() => {
                      showDetails(seq);
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

  async function checkFdhBulk(event) {
      if (event) event.preventDefault();

      const searchItems = {!! json_encode(array_map(function($row) {
          return [
              'hn' => $row->hn,
              'seq' => $row->seq,
              'an' => ''
          ];
      }, $search)) !!};

      const claimItems = {!! json_encode(array_map(function($row) {
          return [
              'hn' => $row->hn,
              'seq' => $row->seq,
              'an' => ''
          ];
      }, $claim)) !!};

      const items = [...searchItems, ...claimItems];

      if (!items || items.length === 0) {
          Swal.fire({ icon: 'warning', title: 'ไม่พบรายการผู้ป่วยในหน้านี้', confirmButtonColor: '#0dcaf0' });
          return;
      }

      const confirm = await Swal.fire({
          title: 'ยืนยันการดึง FDH?',
          html: `ดึงสถานะ FDH สำหรับผู้ป่วยในหน้านี้จำนวน <b>${items.length}</b> รายการ`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'ดึงข้อมูล',
          cancelButtonText: 'ยกเลิก',
          confirmButtonColor: '#0dcaf0'
      });
      if (!confirm.isConfirmed) return;

      const chunkSize = 10;
      const totalItems = items.length;
      let totalUpdated = 0;
      let totalErrors = 0;
      let overallSuccess = true;

      Swal.fire({
          title: 'กำลังดึงสถานะ FDH...',
          html: `
              <div id="fdh-progress-text" class="mb-2">กำลังเตรียมข้อมูล...</div>
              <div class="progress" style="height: 22px;">
                  <div id="fdh-progress-bar"
                       class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                       role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
              </div>
              <div id="fdh-progress-sub" class="mt-2 text-muted small"></div>
          `,
          allowOutsideClick: false,
          showConfirmButton: false
      });

      for (let i = 0; i < totalItems; i += chunkSize) {
          const chunk = items.slice(i, i + chunkSize);
          const percent = Math.round((i / totalItems) * 100);

          const pText = document.getElementById('fdh-progress-text');
          const pBar  = document.getElementById('fdh-progress-bar');
          const pSub  = document.getElementById('fdh-progress-sub');
          if (pText) pText.innerHTML = `กำลังดึงข้อมูล <b>${i + chunk.length}/${totalItems}</b> รายการ`;
          if (pBar)  { pBar.style.width = `${percent}%`; pBar.innerHTML = `${percent}%`; pBar.setAttribute('aria-valuenow', percent); }
          if (pSub)  pSub.textContent = totalErrors > 0 ? `⚠️ พบข้อผิดพลาด ${totalErrors} รายการ` : '';

          try {
              const res = await fetch("{{ url('/api/fdh/check-chunk') }}", {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': "{{ csrf_token() }}"
                  },
                  body: JSON.stringify({ items: chunk })
              });
              const data = await res.json();
              if (data.success) {
                  totalUpdated += parseInt(data.updated_count) || 0;
                  totalErrors += parseInt(data.errors_count) || 0;
                  if (parseInt(data.errors_count) > 0) {
                      overallSuccess = false;
                  }
              } else {
                  overallSuccess = false;
                  totalErrors += chunk.length;
              }
          } catch (err) {
              overallSuccess = false;
              totalErrors += chunk.length;
          }
      }

      const pBarFinal = document.getElementById('fdh-progress-bar');
      if (pBarFinal) { pBarFinal.style.width = '100%'; pBarFinal.innerHTML = '100%'; pBarFinal.setAttribute('aria-valuenow', 100); }

      const summaryHtml = `
          <div class="text-start p-2">
              <b>สถานะ:</b> ${overallSuccess ? '✅ สำเร็จทั้งหมด' : '⚠️ เสร็จสิ้น แต่มีข้อผิดพลาดบางส่วน'}<br>
              <b>รายการทั้งหมด:</b> ${totalItems} รายการ<br>
              <b>ดึงข้อมูลสำเร็จ:</b> <span class="badge bg-success text-white">${totalUpdated}</span> รายการ<br>
              <b>เกิดข้อผิดพลาด:</b> <span class="badge bg-danger text-white">${totalErrors}</span> รายการ
          </div>
      `;

      await Swal.fire({
          icon: overallSuccess ? 'success' : 'warning',
          title: 'ดึงสถานะ FDH เสร็จสิ้น',
          html: summaryHtml,
          confirmButtonText: 'โหลดข้อมูล',
          confirmButtonColor: '#0dcaf0'
      });

      localStorage.setItem('active_tab', '#search');
      fetchData();
      $('#form_indiv').submit();
  }
</script>

@endsection

@push('scripts')
  <script>
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

      // Set initial values (ensures calendar is synced)
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes to Hidden Inputs for Backend (YYYY-MM-DD)
      $('.datepicker_th').on('changeDate', function(e) {
          var date = e.date;
          var targetId = $(this).attr('id').replace('_picker', '');
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

      // Active tab restoration from localStorage
      const savedTab = localStorage.getItem('active_tab');
      if (savedTab) {
          const tabBtn = document.querySelector(`button[data-bs-target="${savedTab}"]`);
          if (tabBtn) {
              // Deactivate all
              document.querySelectorAll('.nav-tabs-modern .nav-link').forEach(btn => {
                  btn.classList.remove('active');
                  const target = document.querySelector(btn.getAttribute('data-bs-target'));
                  if (target) {
                      target.classList.remove('show', 'active');
                  }
              });
              // Activate saved
              tabBtn.classList.add('active');
              const target = document.querySelector(savedTab);
              if (target) {
                  target.classList.add('show', 'active');
              }
          }
          localStorage.removeItem('active_tab');
      }

      // Save active tab on click
      document.querySelectorAll('.nav-tabs-modern .nav-link').forEach(btn => {
          btn.addEventListener('click', function() {
              localStorage.setItem('active_tab_click', this.getAttribute('data-bs-target'));
          });
      });

      // Maintain clicked active tab across manual reloads
      const clickedTab = localStorage.getItem('active_tab_click');
      if (clickedTab) {
          const tabBtn = document.querySelector(`button[data-bs-target="${clickedTab}"]`);
          if (tabBtn) {
              document.querySelectorAll('.nav-tabs-modern .nav-link').forEach(btn => {
                  btn.classList.remove('active');
                  const target = document.querySelector(btn.getAttribute('data-bs-target'));
                  if (target) target.classList.remove('show', 'active');
              });
              tabBtn.classList.add('active');
              const target = document.querySelector(clickedTab);
              if (target) target.classList.add('show', 'active');
          }
      }

      // Table 1: Waiting for Claim
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
              className: 'btn btn-success btn-sm',
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

      // Table 2: Sent Claim
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
              className: 'btn btn-success btn-sm',
              title: 'รายชื่อผู้มารับบริการ UC-OP ในจังหวัด ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
