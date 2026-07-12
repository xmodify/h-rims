@extends('layouts.app')

@section('content')

    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                สถิติการชดเชยค่าบริการ OP-LGO ท้องถิ่น
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

        <!-- Section 2: Tables with Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ OP-LGO
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

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs-modern mt-2" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab" aria-controls="search" aria-selected="true">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab" aria-controls="claim" aria-selected="false">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Claim -->
                <div class="tab-pane fade show active" id="search" role="tabpanel" aria-labelledby="search-tab">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th> 
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">ประสงค์เบิก</th>
                                    <th class="text-center">วัน-เวลา | Q</th>     
                                    <th class="text-center">HN</th>    
                                    <th class="text-center">CID</th>    
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center">CC</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center">ค่ารักษา</th> 
                                    <th class="text-center">ต้องชำระ</th>
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center">PPFS</th>
                                    <th class="text-center">EMS</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_paid_money = 0;
                                    $sum_rcpt_money = 0; 
                                    $sum_ppfs = 0; 
                                    $sum_ems = 0; 
                                    $sum_debtor = 0; 
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @elseif($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $row->request_funds == 'Y' ? '2' : '1' }}">
                                        @if($row->request_funds == 'Y')
                                            <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                        @endif
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-center small">{{$row->cid}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->paid_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->ppfs,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->ems_price,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->debtor,2) }}</td>         
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_paid_money += $row->paid_money;
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_ems += $row->ems_price; 
                                    $sum_debtor += $row->debtor; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="9" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_paid_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ems,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_debtor,2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="claim" role="tabpanel" aria-labelledby="claim-tab">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">#</th> 
                                    <th class="text-center" rowspan="2">สถานะ</th>
                                    <th class="text-center" rowspan="2">Error</th>
                                    <th class="text-center" rowspan="2">ประสงค์เบิก</th>
                                    <th class="text-center" rowspan="2">วัน-เวลา | Q</th>     
                                    <th class="text-center" rowspan="2">HN</th> 
                                    <th class="text-center" rowspan="2">CID</th> 
                                    <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" rowspan="2">PDX | ICD9</th>
                                    <th class="text-center" colspan="6">ค่ารักษา</th> 
                                    <th class="text-center bg-primary-soft" colspan="3">ข้อมูลการชดเชย</th>
                                    <th class="text-center bg-primary-soft" rowspan="2">REP NO.</th>
                                </tr>
                                <tr>
                                    <th class="text-center small">รวม</th>
                                    <th class="text-center small">ต้องชำระ</th>
                                    <th class="text-center small">ชำระเอง</th>
                                    <th class="text-center small">PPFS</th>
                                    <th class="text-center small">EMS</th>
                                    <th class="text-center small text-primary">เรียกเก็บ</th>
                                    <th class="text-center bg-primary-soft small px-1">ชดเชย LGO</th>
                                    <th class="text-center bg-primary-soft small px-1">ชดเชย PP</th>
                                    <th class="text-center bg-primary-soft small px-1">ผลต่าง</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_paid_money = 0;
                                    $sum_rcpt_money = 0;
                                    $sum_ppfs = 0; 
                                    $sum_ems = 0; 
                                    $sum_debtor = 0;  
                                    $sum_receive_total = 0; 
                                    $sum_receive_pp = 0; 
                                @endphp
                                 @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @elseif($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if(!empty($row->check_detail))
                                            @php
                                                $prefix = '';
                                                $badge_style = 'background-color: #dc3545; color: #fff;'; // Default red
                                                if (!empty($row->ec_status)) {
                                                    $first_char = substr($row->ec_status, 0, 1);
                                                    if (in_array($first_char, ['2', '3'])) {
                                                        $prefix = $first_char . '-';
                                                        if ($first_char === '3') {
                                                            $badge_style = 'background-color: #fd7e14; color: #fff;'; // Orange for 3
                                                        } else {
                                                            $badge_style = 'background-color: #f43f5e; color: #fff;'; // Rose red for 2
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <span class="badge fw-bold" style="font-size: 0.72rem; {{ $badge_style }}" title="พบข้อผิดพลาด e-Claim: {{ $row->check_detail }}">{{ $prefix }}{{ $row->check_detail }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $row->request_funds == 'Y' ? '2' : '1' }}">
                                        @if($row->request_funds == 'Y')
                                            <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                        @endif
                                    </td>
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                                    <td class="text-center small">{{$row->cid}}</td> 
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                    </td> 
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                                    <td class="text-end small">{{ number_format($row->paid_money,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small{{ $row->ppfs ? ' fw-bold' : '' }}">{{ number_format($row->ppfs,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->ems_price,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->debtor,2) }}</td> 
                                    <td class="text-end small text-success">{{ number_format($row->receive_total,2) }}</td>
                                    <td class="text-end small text-primary">{{ number_format($row->receive_pp,2) }}</td>
                                    @php $diff = ($row->receive_total+$row->receive_pp) - $row->debtor; @endphp
                                    <td class="text-end small fw-bold {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($diff, 2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_paid_money += $row->paid_money;
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_ppfs += $row->ppfs; 
                                    $sum_ems += $row->ems_price; 
                                    $sum_debtor += $row->debtor; 
                                    $sum_receive_total += $row->receive_total; 
                                    $sum_receive_pp += $row->receive_pp; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="9" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_paid_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ppfs,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_ems,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_debtor,2) }}</th>
                                    <th class="text-end small text-success">{{ number_format($sum_receive_total,2) }}</th>
                                    <th class="text-end small text-primary">{{ number_format($sum_receive_pp,2) }}</th>
                                    @php $total_diff = ($sum_receive_total+$sum_receive_pp) - $sum_debtor; @endphp
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
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Extension Info -->
    <div class="modal fade" id="ExtensionInfoModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow bg-white">
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
</script>

<style>
/* Custom pastel background for main tabs in lgo */
#search-tab {
    background-color: #fef2f2 !important; /* Soft pastel red/pink */
    color: #dc2626 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#search-tab.active {
    background-color: #dc2626 !important;
    color: #fff !important;
}

#claim-tab {
    background-color: #f0fdf4 !important; /* Soft pastel green */
    color: #166534 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#claim-tab.active {
    background-color: #166534 !important;
    color: #fff !important;
}
</style>

<script>
    const VISIT_DETAILS_URL = "{{ url('claim_op/lgo/visit_details') }}";

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
                const hasWarnings    = v.warnings && v.warnings.length > 0;

                function makeCellHtml(isValid, epDone, warn) {
                    if (!isValid) {
                        return `<button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else if (epDone) {
                        return `<button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    } else {
                        return `<button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('${vn}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>`;
                    }
                }

                const dataOrder = !v.is_valid ? 0 : (isEndpointDone && !hasWarnings ? 2 : 1);

                const searchRow = document.getElementById(`td-status-search-${vn}`);
                const claimRow  = document.getElementById(`td-status-claim-${vn}`);
                if (searchRow) {
                    searchRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                    searchRow.setAttribute('data-order', dataOrder);
                }
                if (claimRow) {
                    claimRow.innerHTML = makeCellHtml(v.is_valid, isEndpointDone, hasWarnings);
                    claimRow.setAttribute('data-order', dataOrder);
                }

                let endpointBtn = '';
                if (v.endpoint_valid) {
                    endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
                } else {
                    endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
                }

                // Calculate validation banner at the top
                let statusHtml = '';
                if (!v.is_valid) {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                        <i class="bi bi-x-octagon-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไข)</div>
                          <ul class="mb-0 ps-3 text-danger" style="color: #991b1b !important;">${v.errors.map(err => `<li>${err}</li>`).join('')}</ul>
                        </div>
                      </div>
                    </div>`;
                } else if (!isEndpointDone) {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                        <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่ยังไม่ปิดสิทธิ (สปสช.)</div>
                          <div class="text-muted">ข้อมูลผ่านเกณฑ์การตรวจสอบแล้ว แต่กรุณากดดึงข้อมูลหรือปิดสิทธิเพื่อส่งออก</div>
                        </div>
                      </div>
                    </div>`;
                } else {
                    statusHtml = `
                    <div class="col-12">
                      <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small mb-0" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                        <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                        <div>
                          <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลพร้อมส่งออก (ผ่านเกณฑ์และปิดสิทธิเรียบร้อย)</div>
                          <div class="text-muted">ข้อมูลถูกต้องครบถ้วนและทำการปิดสิทธิเรียบร้อยแล้ว</div>
                        </div>
                      </div>
                    </div>`;
                }

                let html = `
                <div class="row g-3">
                  <!-- Validation Status Banner -->
                  ${statusHtml}

                  <!-- คอลัมน์ที่ 1: ข้อมูลผู้ป่วย -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:40%">HN</th><td class="fw-bold">${visit.hn}</td></tr>
                          <tr><th class="text-muted">CID</th><td>${visit.cid ?? '-'}</td></tr>
                          <tr><th class="text-muted">ชื่อ-สกุล</th><td>${visit.ptname}</td></tr>
                          <tr><th class="text-muted">สิทธิ์</th><td>${visit.pttype ?? '-'}</td></tr>
                          <tr><th class="text-muted">เพศ/อายุ</th><td>${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : visit.sex)} / ${visit.age_y ?? '-'} ปี</td></tr>
                          <tr><th class="text-muted">ประสงค์เบิก</th><td>${visit.request_funds === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                          <tr><th class="text-muted">พร้อมส่ง</th><td>${visit.confirm_and_locked === 'Y' ? '<span class="badge bg-success py-0 px-2 fw-bold text-white"><i class="bi bi-check-circle-fill me-1"></i>Y</span>' : '<span class="badge bg-danger py-0 px-2 fw-bold text-white"><i class="bi bi-x-circle-fill me-1"></i>N</span>'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- คอลัมน์ที่ 2: ข้อมูลทางคลินิก -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:35%">วันที่</th><td>${visit.vstdate} ${visit.vsttime}</td></tr>
                          <tr><th class="text-muted">CC</th><td style="word-break: break-all;">${visit.cc ?? '-'}</td></tr>
                          <tr><th class="text-muted">PDX</th><td class="fw-bold text-danger">${visit.pdx ?? '-'}</td></tr>
                          <tr><th class="text-muted">SDX</th><td style="word-break: break-all;">${data.sec_diags.join(', ') || '-'}</td></tr>
                          <tr><th class="text-muted">ICD-9</th><td style="word-break: break-all;">${data.procedures.join(', ') || '-'}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- คอลัมน์ที่ 3: ข้อมูลการเงิน -->
                  <div class="col-md-4">
                    <div class="card border-0 bg-light-soft h-100">
                      <div class="card-body py-2 px-3">
                        <div class="fw-bold text-primary mb-2 small"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลการเงิน</div>
                        <table class="table table-sm table-borderless mb-0 small compact-info-table">
                          <tr><th class="text-muted" style="width:40%">ยอดค่ารักษา</th><td>${parseFloat(visit.income || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ต้องชำระ/เอง</th><td>${parseFloat(visit.paid_money || 0).toFixed(2)} / ${parseFloat(visit.rcpt_money || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ยอดเรียกเก็บ</th><td class="fw-bold text-primary">${parseFloat(visit.income - visit.rcpt_money).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชดเชย LGO</th><td class="text-success fw-bold">${parseFloat(visit.receive_total || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">ชดเชย PP</th><td class="text-info fw-bold">${parseFloat(visit.receive_pp || 0).toFixed(2)} บาท</td></tr>
                          <tr><th class="text-muted">สถานะปิดสิทธิ์</th><td>${endpointBtn}</td></tr>
                        </table>
                      </div>
                    </div>
                  </div>

                  <!-- ส่วนแท็บรายละเอียดรายการยา และ ค่าบริการรักษาพยาบาล -->
                  <div class="col-12 mt-3">
                    <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                      <li class="nav-item">
                        <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab"><i class="bi bi-capsule me-1"></i>รายการยา</button>
                      </li>
                      <li class="nav-item">
                        <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab"><i class="bi bi-list-check me-1"></i>ค่ารักษาพยาบาล</button>
                      </li>
                    </ul>
                    <div class="tab-content" id="modalDetailTabsContent">
                      <!-- แท็บรายการยา -->
                      <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" style="font-size: 12px;">
                        <table id="modal-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อยา/เวชภัณฑ์</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th>รหัสมาตรฐาน TMT</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let drugsList = items.filter(d => d.icode.startsWith('1'));
                                if (drugsList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการสั่งยาใน Visit นี้</td></tr>';
                                }
                                return drugsList.map(d => {
                                    let tmtDisplay = d.tmt_code 
                                        ? `<span class="badge bg-success fw-bold">${d.tmt_code}</span>`
                                        : `<span class="badge bg-secondary-soft text-secondary">ไม่มีรหัส TMT</span>`;
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${parseFloat(d.qty).toFixed(0)}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                      <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                      <td>${tmtDisplay}</td>
                                    </tr>`;
                                }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>

                      <!-- แท็บค่าบริการรักษาพยาบาล -->
                      <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" style="font-size: 12px;">
                        <table id="modal-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                          <thead class="table-dark">
                            <tr>
                              <th>ชื่อบริการ/ค่ารักษาพยาบาล</th>
                              <th class="text-center" width="10%">จำนวน</th>
                              <th class="text-end" width="12%">ราคารวม (บาท)</th>
                              <th class="text-center" width="15%">ประเภทการชำระ</th>
                              <th class="text-center" width="15%">สิทธิการรักษา</th>
                              <th>ADP Code</th>
                            </tr>
                          </thead>
                          <tbody>
                            ${(function() {
                                let servicesList = items.filter(d => !d.icode.startsWith('1'));
                                if (servicesList.length === 0) {
                                    return '<tr><td colspan="6" class="text-center text-muted py-3">ไม่พบรายการค่าบริการ/รักษาพยาบาลใน Visit นี้</td></tr>';
                                }
                                return servicesList.map(d => {
                                    let type = '';
                                    if (d.ppfs  === 'Y') type += '<span class="badge-type badge-ppfs me-1">PPFS</span>';
                                    if (d.ems === 'Y') type += '<span class="badge-type me-1" style="background:#cfe2ff;color:#084298;">EMS</span>';
                                    
                                    return `<tr>
                                      <td>
                                        <div class="fw-bold text-dark">${d.name ?? '-'} ${type}</div>
                                        <div class="text-muted small" style="font-size: 0.7rem;">icode: ${d.icode}</div>
                                      </td>
                                      <td class="text-center fw-bold">${parseFloat(d.qty).toFixed(0)}</td>
                                      <td class="text-end font-monospace">${parseFloat(d.sum_price).toFixed(2)}</td>
                                      <td class="text-center">${d.paids_name || d.paids || '-'}</td>
                                      <td class="text-center">${d.pttype_name || d.pttype || '-'}</td>
                                      <td><span class="badge bg-secondary-soft text-secondary fw-bold">${d.nhso_adp_code ?? '-'}</span></td>
                                    </tr>`;
                                }).join('');
                            })()}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>`;

                body.innerHTML = html;

                // Destroy existing DataTables if already initialized to prevent error
                if ($.fn.DataTable.isDataTable('#modal-drugs-table')) {
                    $('#modal-drugs-table').DataTable().destroy();
                }
                if ($.fn.DataTable.isDataTable('#modal-services-table')) {
                    $('#modal-services-table').DataTable().destroy();
                }

                // Initialize DataTable for Drugs
                if (items.filter(d => d.icode.startsWith('1')).length > 0) {
                    $('#modal-drugs-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        }
                    });
                }

                // Initialize DataTable for Services
                if (items.filter(d => !d.icode.startsWith('1')).length > 0) {
                    $('#modal-services-table').DataTable({
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        }
                    });
                }

                // Adjust column headers on tab change to prevent distorted columns
                $('#modalDetailTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });
            })
            .fail(function(xhr) {
                body.innerHTML = `<div class="text-danger py-4 text-center"><i class="bi bi-exclamation-triangle-fill me-2"></i>ไม่สามารถดึงข้อมูลได้: ${xhr.responseJSON?.error ?? 'ข้อผิดพลาดระบบ'}</div>`;
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
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        showDetails(vn);
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
                    title: 'กำลังส่งข้อมูล...',
                    text: 'กรุณารอสักครู่',
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
              title: 'รายชื่อผู้มารับบริการ OP-LGO อปท. รอส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
              title: 'รายชื่อผู้มารับบริการ OP-LGO อปท. ส่ง Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

      $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').on('shown.bs.tab shown.bs.pill', function () {
          dt_search.columns.adjust().draw(false);
          dt_claim.columns.adjust().draw(false);
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
            backgroundColor: 'rgba(185, 28, 28, 0.75)',
            borderColor: 'rgb(185, 28, 28)',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'ส่งเคลม',
            data: <?php echo json_encode($claim_sent_price); ?>,
            backgroundColor: 'rgba(234, 179, 8, 0.6)',
            borderColor: 'rgb(234, 179, 8)',
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
