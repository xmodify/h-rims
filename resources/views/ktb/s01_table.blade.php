    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <!-- Section 1: Chart -->
        <div class="px-4 pt-2 pb-0 border-bottom">
            <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">
                <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                สถิติการเรียกเก็บรายเดือน ปีงบประมาณ {{ $budget_year }} (ตามสเปก KTB)
            </h6>
            <div style="height: 280px; width: 100%;">
                <canvas id="sum_month"></canvas>
            </div>
        </div>

        <!-- Section 2: Table -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้รับบริการ {{ $page_title }}
                    </h6>
                    <span class="badge bg-primary rounded-pill">{{ count($search) }} รายการ</span>
                    <span class="text-muted small ms-2">
                        (วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }})
                    </span>
                </div>
                
                <div class="filter-group">
                    <form id="form_indiv" method="POST" enctype="multipart/form-data" class="m-0 d-flex align-items-center flex-wrap gap-1">
                        @csrf            
                        <span class="fw-bold text-muted small text-nowrap me-1">เลือกวันที่รับบริการ:</span>
                        <div class="input-group input-group-sm">
                            <input type="hidden" name="budget_year" value="{{ $budget_year }}">
                            <!-- Start Date -->
                            <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                            <input type="text" id="start_date_picker" class="form-control datepicker_th text-center" readonly style="width: 110px; cursor: pointer;">
                            
                            <span class="input-group-text bg-white border-start-0 border-end-0">ถึง</span>

                            <!-- End Date -->
                            <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                            <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 110px; cursor: pointer;">

                            <button type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                            <button type="button" class="btn text-white fw-bold px-3 shadow-sm" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;" onclick="exportSelectedF16KTB()">
                                <i class="bi bi-box-arrow-up-right me-1"></i> ส่งออก 16 แฟ้ม KTB
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body px-4 pb-4 pt-2">
            <div class="table-responsive">            
                <table id="t_search" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;">
                                <input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด">
                            </th>
                            <th class="text-center" width="50">#</th>
                            <th class="text-center" width="70">ตรวจสอบ</th>
                            <th class="text-center" width="110">เบิก/ส่ง</th>
                            <th class="text-center" width="130">วัน-เวลา | Q</th>     
                            <th class="text-center" width="90">HN</th>    
                            <th class="text-start" width="160">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-start">รายการต้องเรียกเก็บ</th>
                            <th class="text-end" width="100">ค่ารักษา</th> 
                            <th class="text-end" width="100">ชำระเอง</th>
                            <th class="text-end text-primary" width="100">เรียกเก็บ</th> 
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
                            <td class="text-center" style="vertical-align: middle;">
                                <input type="checkbox" class="form-check-input chk_f16_visit" value="{{ $row->seq }}">
                            </td>
                            <td class="text-center text-muted small">{{ $count }}</td>
                            <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->is_valid ? 0 : (($row->endpoint_valid && empty($row->validation_warnings)) ? 2 : 1) }}">
                                @if(!$row->is_valid)
                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="🔴 ไม่ผ่านเกณฑ์ KTB | คลิกดูสัญญาณชีพและผลตรวจ"><i class="bi bi-eye-fill"></i></button>
                                @elseif($row->endpoint_valid && empty($row->validation_warnings))
                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="🟢 ข้อมูลครบ + ปิดสิทธิ สปสช. แล้ว | ดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                @else
                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="🟡 พบข้อควรระวัง / ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด"><i class="bi bi-eye-fill"></i></button>
                                @endif
                            </td>
                            <td class="text-start ps-2">
                                <div class="d-flex flex-column align-items-start gap-1" style="font-size: 0.72rem;">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="text-muted">ประสงค์เบิก:</span>
                                        @if($row->request_funds == 'Y')
                                            <i class="bi bi-check-circle-fill text-success" title="ประสงค์เบิก Y"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger" title="ไม่ประสงค์เบิก N"></i>
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="text-muted">Authen:</span>
                                        @if($row->auth_code == 'Y')
                                            <i class="bi bi-check-circle-fill text-success" title="มีรหัส Authen"></i>
                                        @else
                                            <i class="bi bi-dash-circle text-muted" title="ไม่มี Authen"></i>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-start">
                                <div class="small fw-bold">{{ DateThai($row->vstdate) }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                            </td>
                            <td class="text-center">
                                <a href="#" class="text-primary fw-bold text-decoration-none" onclick="showDetails('{{ $row->seq }}')">{{ $row->hn }}</a>
                            </td>
                            <td class="text-start">
                                <div class="fw-bold text-dark small text-truncate" style="max-width: 150px;">{{ $row->ptname }}</div>
                                <div class="text-muted small text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</div>
                            </td>
                            <td class="text-start small text-muted" style="font-size: 0.75rem;">
                                {{ $row->claim_list ?: ($row->adp_codes ?: 'บริการส่งเสริมป้องกันโรค') }}
                            </td>
                            <td class="text-end small">{{ number_format($row->income, 2) }}</td>
                            <td class="text-end small">{{ number_format($row->rcpt_money, 2) }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price, 2) }}</td>
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
                            <th class="text-end small">{{ number_format($sum_income, 2) }}</th>
                            <th class="text-end small">{{ number_format($sum_rcpt_money, 2) }}</th>
                            <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
