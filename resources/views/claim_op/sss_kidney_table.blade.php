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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ SSS ฟอกไต
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
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab list matching ucs_kidney -->
        <div class="px-4 mb-2">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-hourglass-split me-1"></i>รอชดเชย
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-check-circle-fill me-1"></i>ชดเชยแล้ว
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Compensation -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>                      
                                    <th class="text-center">วัน-เวลา | Q</th>  
                                    <th class="text-center">HN</th> 
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="10%">อาการสำคัญ</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center small">ค่ารักษา</th>  
                                    <th class="text-center small">ชำระเอง</th> 
                                    <th class="text-center small">รายการที่เรียกเก็บ</th>
                                    <th class="text-center small">เรียกเก็บ</th>
                                    <th class="text-center text-primary small">ชดเชย</th> 
                                    <th class="text-center text-primary small">ผลต่าง</th> 
                                    <th class="text-center text-primary small">REP</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0;  
                                    $sum_claim_price = 0;           
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($search as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>                   
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">
                                            {{$row->pttype}}
                                        </div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-start small text-muted text-wrap" style="max-width: 120px;">{{ $row->claim_list }}</td>  
                                    <td class="text-end small fw-bold">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-end small fw-bold @if($row->receive_total > 0) text-success @elseif($row->receive_total < 0) text-danger @endif">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold @if($row->receive_total-$row->claim_price > 0) text-success @elseif($row->receive_total-$row->claim_price < 0) text-danger @endif">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
                                    </td>
                                    <td class="text-center small text-primary">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="6" class="text-end text-muted small px-3">รวมงบประมาณที่รอชดเชย:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th></th>
                                    <th class="text-end small fw-bold">{{ number_format($sum_claim_price,2) }}</th>
                                    <th class="text-end small fw-bold @if($sum_receive_total > 0) text-success @elseif($sum_receive_total < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total,2) }}
                                    </th>
                                    <th class="text-end small fw-bold @if($sum_receive_total-$sum_claim_price > 0) text-success @elseif($sum_receive_total-$sum_claim_price < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total-$sum_claim_price,2) }}
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Compensated -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>                      
                                    <th class="text-center">วัน-เวลา | Q</th>  
                                    <th class="text-center">HN</th> 
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="10%">อาการสำคัญ</th>
                                    <th class="text-center">PDX | ICD9</th>
                                    <th class="text-center small">ค่ารักษา</th>  
                                    <th class="text-center small">ชำระเอง</th> 
                                    <th class="text-center small">รายการที่เรียกเก็บ</th>
                                    <th class="text-center small">เรียกเก็บ</th>
                                    <th class="text-center text-primary small">ชดเชย</th> 
                                    <th class="text-center text-primary small">ผลต่าง</th> 
                                    <th class="text-center text-primary small">REP</th> 
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0;  
                                    $sum_claim_price = 0;           
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center text-muted small">{{ $count }}</td>                   
                                    <td class="text-start">
                                        <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                                    </td>            
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">
                                            {{$row->pttype}}
                                        </div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->cc }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->pdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-start small text-muted text-wrap" style="max-width: 120px;">{{ $row->claim_list }}</td>  
                                    <td class="text-end small fw-bold">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-end small fw-bold @if($row->receive_total > 0) text-success @elseif($row->receive_total < 0) text-danger @endif">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold @if($row->receive_total-$row->claim_price > 0) text-success @elseif($row->receive_total-$row->claim_price < 0) text-danger @endif">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
                                    </td>
                                    <td class="text-center small text-primary">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="6" class="text-end text-muted small px-3">รวมงบประมาณที่ชดเชยแล้ว:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th></th>
                                    <th class="text-end small fw-bold">{{ number_format($sum_claim_price,2) }}</th>
                                    <th class="text-end small fw-bold @if($sum_receive_total > 0) text-success @elseif($sum_receive_total < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total,2) }}
                                    </th>
                                    <th class="text-end small fw-bold @if($sum_receive_total-$sum_claim_price > 0) text-success @elseif($sum_receive_total-$sum_claim_price < 0) text-danger @endif">
                                        {{ number_format($sum_receive_total-$sum_claim_price,2) }}
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
