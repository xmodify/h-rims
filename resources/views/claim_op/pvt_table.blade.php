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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ OP-PVT
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
                                    <th class="text-center bg-primary-soft small px-1">ชดเชย PVT</th>
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
