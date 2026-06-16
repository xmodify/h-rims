@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ SS-OP ประกันสังคม PPFS
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

        <!-- Section 2: Tables & Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ SS-OP ประกันสังคม PPFS
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
                            <button type="button" class="btn btn-outline-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#ExtensionInfoModal">
                                <i class="bi bi-puzzle-fill me-1"></i> ดึง E-Claim ด้วย Extension
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
                                            {{-- แดง: ข้อมูลไม่ครบ (priority สูงสุด) --}}
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            {{-- เหลือง: มี warnings --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีข้อมูลเตือน | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            {{-- เขียว: ข้อมูลครบ + ปิดสิทธิแล้ว --}}
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            {{-- เหลือง: ข้อมูลครบ แต่ยังไม่ปิดสิทธิ --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด">
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
                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:200px;">{{ $row->claim_list }}</td>
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
                                    <th colspan="7" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
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
                                    <th class="text-center" rowspan="2">รายการต้องเรียกเก็บ</th>
                                    <th class="text-center" colspan="4">ค่ารักษา</th>                                     
                                    <th class="text-center bg-primary-soft" colspan="5">ข้อมูลการชดเชย</th>
                                </tr>
                                <tr>                                    
                                    <th class="text-center small">รวม</th>
                                    <th class="text-center small">ชำระเอง</th>                                                                  
                                    <th class="text-center small">PPFS</th>
                                    <th class="text-center small">E-Claim</th>
                                    <th class="text-center bg-primary-soft small px-1">Rep NHSO</th> 
                                    <th class="text-center bg-primary-soft small px-1 text-nowrap">Error</th> 
                                    <th class="text-center bg-primary-soft small px-1">STM ชดเชย</th> 
                                    <th class="text-center bg-primary-soft small px-1">ผลต่าง</th> 
                                    <th class="text-center bg-primary-soft small px-1 text-nowrap">REP No.</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_ppfs = 0; 
                                    $sum_rep_nhso = 0; 
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            {{-- แดง: ข้อมูลไม่ครบ --}}
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                             {{-- เหลือง: มี warnings --}}
                                             <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีข้อมูลเตือน | คลิกดูรายละเอียด">
                                                 <i class="bi bi-eye-fill"></i>
                                             </button>
                                        @elseif($row->endpoint_valid)
                                            {{-- เขียว: ข้อมูลครบ + ปิดสิทธิแล้ว --}}
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            {{-- เหลือง: ข้อมูลครบ แต่ยังไม่ปิดสิทธิ --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด">
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
                                    <td class="text-start small text-muted" style="font-size:0.7rem; max-width:200px;">{{ $row->claim_list }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>                                      
                                    <td class="text-end small">{{ number_format($row->ppfs,2) }}</td> 
                                    <td class="text-center">
                                        @if(substr($row->ec_status, 0, 1) == '0')
                                            <span class="badge bg-secondary-soft text-secondary py-0" style="font-size: 0.65rem;" title="{{ $row->ec_status }}">{{ $row->ec_status }}</span>
                                        @elseif(substr($row->ec_status, 0, 1) == '1')
                                            <span class="badge bg-warning-soft text-warning py-0" style="font-size: 0.65rem;" title="{{ $row->ec_status }}">{{ $row->ec_status }}</span>
                                        @elseif(substr($row->ec_status, 0, 1) == '2' || substr($row->ec_status, 0, 1) == 'M')
                                            <span class="badge bg-danger-soft text-danger py-0" style="font-size: 0.65rem;" title="{{ $row->ec_status }}">{{ $row->ec_status }}</span>
                                        @elseif(substr($row->ec_status, 0, 1) == '3')
                                            <span class="badge bg-orange-soft text-orange py-0" style="font-size: 0.65rem;" title="{{ $row->ec_status }}">{{ $row->ec_status }}</span>
                                        @elseif(substr($row->ec_status, 0, 1) == '4')    
                                            <span class="badge bg-primary-soft text-primary py-0" style="font-size: 0.65rem;" title="{{ $row->ec_status }}">{{ $row->ec_status }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end small text-primary">{{ number_format($row->rep_nhso,2) }}</td>
                                    <td class="text-center small text-muted">{{ $row->rep_error }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    @php $diff = $row->receive_total - $row->ppfs; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td>
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_rep_nhso += $row->rep_nhso;
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="7" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th></th>
                                    <th class="text-end small text-primary">{{ number_format($sum_rep_nhso,2) }}</th>
                                    <th></th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    @php $total_diff = $sum_receive_total - $sum_ppfs; @endphp
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

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="detailsModalLabel">
                        <i class="bi bi-file-earmark-medical me-2"></i>รายละเอียดการรับบริการ & การตรวจสอบ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="detailsModalBody">
                    <!-- Content loaded dynamically via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Extension Info -->
    <div class="modal fade" id="ExtensionInfoModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-puzzle-fill text-warning me-2"></i> วิธีติดตั้งและใช้งาน Chrome Extension</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4 text-start">
              <h6 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-1-circle"></i> ขั้นตอนที่ 1 : ติดตั้งส่วนเสริม (ทำครั้งเดียว)</h6>
              <ol class="mb-4 text-muted small lh-lg">
                  <li>ดาวน์โหลดไฟล์ส่วนเสริมลงในเครื่องคอมพิวเตอร์ของคุณ <br/><a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-sm btn-outline-primary mt-1 mb-2"><i class="bi bi-download"></i> ดาวน์โหลด eclaim_sync.zip (เวอร์ชั่นล่าสุด)</a><br/> จากนั้น<b>แตกไฟล์ (Extract / Unzip)</b> ลงในโฟลเดอร์ให้เรียบร้อย (เช่น สร้างโฟลเดอร์ชื่อ <code>eclaim_sync</code> บน Desktop)</li>
                  <li>เปิด Google Chrome และพิมพ์ที่ช่อง URL ด้านบน: <code class="bg-light p-1 text-primary">chrome://extensions/</code> แล้วกด Enter</li>
                  <li>ที่มุมขวาบนของหน้าจอ ให้คลิกเปิดสวิตช์ <b>โหมดนักพัฒนาซอฟต์แวร์ (Developer mode)</b></li>
                  <li>คลิกปุ่ม <b>โหลดส่วนขยายที่ยังไม่ได้แพ็ก (Load unpacked)</b> (มุมซ้ายบน) แล้วคลิกเลือกโฟลเดอร์ <code>eclaim_sync</code> ที่แตกไฟล์ไว้</li>
              </ol>

              <h6 class="fw-bold mb-3 text-warning border-bottom pb-2"><i class="bi bi-gear-fill me-1"></i> ขั้นตอนที่ 2 : ตั้งค่าการส่งข้อมูล (ทำครั้งเดียว)</h6>
              <div class="mb-4 text-muted small">
                  <p class="mb-1">เมื่อติดตั้งแล้ว ให้ตั้งค่าที่อยู่ในการส่งข้อมูล (API URL) ดังนี้:</p>
                  <div class="bg-light p-3 rounded-3 border">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                           <span class="fw-bold text-dark">URL ที่ต้องคัดลอก:</span>
                           <button class="btn btn-xs btn-primary py-0" onclick="copyToClipboard('{{ url('api') }}')">คัดลอก</button>
                      </div>
                      <code id="apiUrlPath" class="text-break text-danger fw-bold">{{ url('api') }}</code>
                  </div>
                  <ol class="mt-2 lh-lg">
                      <li>คลิกที่ไอคอน Extension <b>"RiMS E-Claim Sync"</b></li>
                      <li>คลิกที่ไอคอน <b>⚙️ (ฟันเฟือง)</b> มุมขวาบนของหน้าต่างป๊อปอัป</li>
                      <li><b>คัดลอก URL ด้านบนไปวาง</b> ในช่อง RiMS API URL จากนั้นกด <b>บันทึกการตั้งค่า</b></li>
                  </ol>
              </div>
              
              <h6 class="fw-bold mb-3 text-success border-bottom pb-2"><i class="bi bi-2-circle"></i> ขั้นตอนที่ 3 : วิธีการดึงข้อมูล (ทำรายวัน)</h6>
              <ol class="mb-4 text-muted small lh-lg">
                  <li>ให้ใช้ Google Chrome เปิดหน้าเว็บ <a href="https://eclaim.nhso.go.th/Client" target="_blank" class="text-decoration-underline fw-bold">E-Claim สปสช.</a> และ Login เข้าสู่ระบบ</li>
                  <li>เปิดเข้าสู่หน้าระบบและค้นหาช่วงวันที่ต้องการ เมื่อมีข้อมูลรายชื่อผู้ป่วยแสดงในตารางเรียบร้อยแล้ว</li>
                  <li>คลิกที่ <b>ไอคอนส่วนขยาย "RiMS E-Claim Sync"</b> แล้วกดปุ่ม <b>"ดึงข้อมูลเข้าสู่ RiMS"</b> </li>
                  <li>รอให้ระบบทำการกวาดตารางและส่งข้อมูลเข้าฐานข้อมูลของโรงพยาบาลจนขึ้นข้อความสำเร็จ</li>
              </ol>

              <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem">
                 <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <b>หมายเหตุ:</b> หากข้อมูลมีหลายหน้า ต้องคลิกเปลี่ยนหน้า และกดปุ่มซิงค์ทีละหน้า
              </div>
          </div>
          <div class="modal-footer border-0 bg-light">
              <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
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
  function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
          Swal.fire({
              icon: 'success',
              title: 'คัดลอกแล้ว!',
              text: 'นำไปวางในช่อง RiMS API URL ในหน้าตั้งค่าของ Extension ได้เลย',
              timer: 2000,
              showConfirmButton: false
          });
      });
  }



  function alertAlreadyClosed(source) {
      Swal.fire({
          icon: 'info',
          title: 'ปิดสิทธิเรียบร้อยแล้ว',
          text: 'รายการนี้ปิดสิทธิโดย ' + source + ' เรียบร้อยแล้ว ไม่จำเป็นต้องส่งซ้ำอีกครั้ง',
          confirmButtonText: 'รับทราบ',
          confirmButtonColor: '#0d6efd'
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
    const VISIT_DETAILS_URL = "{{ url('claim_op/sss_ppfs/visit_details') }}";

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
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="มีข้อมูลเตือน | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else if (epDone) {
                        return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
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
                    endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว</span>`;
                } else {
                    endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
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

                // Validation summary bar
                if (!v.is_valid) {
                    html += `
                  <div class="col-12">
                    <div class="alert alert-danger py-2 mb-0 small">
                      <strong><i class="bi bi-x-octagon-fill me-1"></i>เงื่อนไขที่ไม่ผ่าน:</strong>
                      <ul class="mb-0 mt-1 ps-3">`;
                    v.errors.forEach(function(err) { html += `<li>${err}</li>`; });
                    html += `</ul></div></div>`;
                }

                // Items table
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

      // Table 1: Waiting for Claim
      var dt_search = $('#t_search').DataTable({
        autoWidth: false,
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
              title: 'รายชื่อผู้มารับบริการ SS-OP ประกันสังคม PPFS รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
      var dt_claim = $('#t_claim').DataTable({
        autoWidth: false,
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
              title: 'รายชื่อผู้มารับบริการ SS-OP ประกันสังคม PPFS ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

      // แก้ตารางไม่เต็ม card เมื่อสลับ tab
      $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function () {
          dt_search.columns.adjust().draw(false);
          dt_claim.columns.adjust().draw(false);
      });

      // Restore active tab from localStorage
      var activeTab = localStorage.getItem('active_tab');
      if (activeTab) {
          var tabEl = document.querySelector(`button[data-bs-target="${activeTab}"]`);
          if (tabEl) {
              tabEl.click();
          }
          localStorage.removeItem('active_tab');
      }
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
