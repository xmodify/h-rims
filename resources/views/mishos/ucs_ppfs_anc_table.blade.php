    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Chart -->
        <div class="px-4 pt-2 pb-0 border-bottom">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติการรับบริการรายเดือน
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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้รับบริการฝากครรภ์ (ANC)
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
                            <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                            <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                            
                            <input type="text" id="start_date_picker" class="form-control datepicker_th" value="{{ $start_date }}" style="width: 120px;" readonly>
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>
                            <input type="text" id="end_date_picker" class="form-control datepicker_th" value="{{ $end_date }}" style="width: 120px;" readonly>
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
                        <i class="bi bi-clock-history me-1"></i> รายการรับบริการ
                    </button>
                </li>       
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Search -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_search" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">ตรวจสอบ</th>
                                    <th class="text-center">ส่ง Claim</th>
                                    <th class="text-center" width="8%">วันที่รับบริการ</th>
                                    <th class="text-center">Queue</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                    <th class="text-start" width="15%">สิทธิการรักษา</th>
                                    <th class="text-center">ANC</th>
                                    <th class="text-start">รายการเรียกเก็บ</th>
                                    <th class="text-end">ค่ารักษาทั้งหมด</th>
                                    <th class="text-end">ชำระเอง</th>
                                    <th class="text-end text-primary">เรียกเก็บ</th>
                                    <th class="text-end text-success">ชดเชย</th>
                                    <th class="text-end">ส่วนต่าง</th>
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
                                    <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                        @if(!$row->is_valid)
                                            {{-- แดง: ข้อมูลไม่ครบ (priority สูงสุด) --}}
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            {{-- เหลือง: มี warnings (ins_ucs) --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มี Instrument ไม่อยู่ในประกาศ UCS | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            {{-- เขียว: ข้อมูลครบ + ปิดสิทธิแล้ว --}}
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            {{-- เหลือง: ข้อมูลครบ แต่ยังไม่ปิดสิทธิ --}}
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ข้อมูลครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center" data-order="{{ $row->claim == 'Y' ? 1 : 0 }}">
                                        @if($row->claim && $row->claim == 'Y')
                                            <span class="badge bg-success-soft text-success p-2 rounded-circle" title="ส่งเคลมแล้ว"><i class="bi bi-check-circle-fill" style="font-size: 0.9rem;"></i></span>
                                        @else
                                            <span class="badge bg-secondary-soft text-secondary p-2 rounded-circle" title="ยังไม่ได้ส่ง"><i class="bi bi-dash-circle-fill" style="font-size: 0.9rem;"></i></span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        {{ DateThai($row->vstdate) }}<br>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                    </td>
                                    <td class="text-center small">{{ $row->oqueue }}</td>
                                    <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                    <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                    <td class="text-start small text-muted">
                                        <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                        <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                    </td>
                                    <td class="text-center small text-primary fw-bold">{{ $row->anc_service_number }}</td>
                                    <td class="text-start small text-muted">{{$row->claim_list}}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end small fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total,2) }}
                                    </td>
                                    <td class="text-end small fw-bold {{ ($row->receive_total-$row->claim_price) > 0 ? 'text-success' : (($row->receive_total-$row->claim_price) < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
                                    </td>
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
                                    <th colspan="10" class="text-end small text-muted px-3">รวมทั้งหมด:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2)}}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2)}}</th>
                                    <th class="text-end small fw-bold text-primary">{{ number_format($sum_claim_price,2)}}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : ($sum_receive_total < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($sum_receive_total,2)}}
                                    </th>
                                    <th class="text-end small fw-bold {{ ($sum_receive_total-$sum_claim_price) > 0 ? 'text-success' : (($sum_receive_total-$sum_claim_price) < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ number_format($sum_receive_total-$sum_claim_price,2)}}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 
            </div>
        </div>
    </div>
</div>      

