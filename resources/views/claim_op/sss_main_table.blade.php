@php
    $is_ssop_licensed = \App\Services\LicenseService::isLicensed();
@endphp

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
            <!-- Filter & Selection Section -->
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3 border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-muted small text-nowrap"><i class="bi bi-funnel-fill text-secondary me-1"></i>ตัวกรองข้อมูล:</span>
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <input type="radio" class="btn-check" name="rep_filter" id="rep_filter_all" value="all" checked autocomplete="off" onchange="applyRepFilter()">
                        <label class="btn btn-outline-secondary px-3 fw-bold" for="rep_filter_all">แสดงทั้งหมด</label>

                        <input type="radio" class="btn-check" name="rep_filter" id="rep_filter_error" value="error" autocomplete="off" onchange="applyRepFilter()">
                        <label class="btn btn-outline-danger px-3 fw-bold" for="rep_filter_error">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> เฉพาะ REP Error
                        </label>

                        <input type="radio" class="btn-check" name="rep_filter" id="rep_filter_has_invoice" value="has_invoice" autocomplete="off" onchange="applyRepFilter()">
                        <label class="btn btn-outline-success px-3 fw-bold" for="rep_filter_has_invoice">
                            <i class="bi bi-file-earmark-check-fill me-1"></i> เฉพาะมี Invoice
                        </label>

                        <input type="radio" class="btn-check" name="rep_filter" id="rep_filter_no_invoice" value="no_invoice" autocomplete="off" onchange="applyRepFilter()">
                        <label class="btn btn-outline-warning px-3 fw-bold" for="rep_filter_no_invoice">
                            <i class="bi bi-file-earmark-x-fill me-1"></i> เฉพาะไม่มี Invoice
                        </label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">            
                <table id="t_claim" class="table table-modern w-100">
                    <thead>
                        <tr>
                            @if($is_ssop_licensed)
                            <th class="text-center" width="3%"><input type="checkbox" id="select_all_claims"></th>
                            @endif
                                                  
                            <th class="text-center">ตรวจสอบ</th>                      
                            <th class="text-center" width="8%">InvoiceNo</th>                      
                            <th class="text-center" width="10%">วัน-เวลา | Q</th>     
                            <th class="text-center">HN</th>    
                            <th class="text-center">CID</th>    
                            <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                            <th class="text-center" width="15%">อาการสำคัญ</th>
                            <th class="text-center" width="8%">โรคเรื้อรัง</th>
                            <th class="text-center" width="8%">ยาโรคเรื้อรัง</th>
                            <th class="text-end px-3" width="6%">PDX</th>
                            <th class="text-start px-3" width="10%">SDX | ICD9</th>
                            <th class="text-center">ค่ารักษา</th> 
                            <th class="text-center">ชำระเอง</th>                               
                            <th class="text-center text-primary">เรียกเก็บ</th>
                            <th class="text-center" width="8%">Rep Error</th>
                            <th class="text-center" width="8%">Rep Warning</th>
                            <th class="text-center" width="8%">stm ชดเชย</th> 
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
                        @php
                            $has_invoice = (($row->sss_invno && $row->sss_invno !== '0') || ($row->debt_id_list && $row->debt_id_list !== '0')) ? 'true' : 'false';
                        @endphp
                        <tr data-has-error="{{ $row->rep_error ? 'true' : 'false' }}" data-has-invoice="{{ $has_invoice }}">
                            @if($is_ssop_licensed)
                            <td class="text-center">
                                <input type="checkbox" class="claim-select-check" value="{{ $row->vn }}" data-has-error="{{ $row->rep_error ? 'true' : 'false' }}">
                            </td>
                            @endif
                            
                            <td class="text-center" data-status="{{ $row->claim_status }}" data-order="{{ $row->claim_status === 'red' ? '2' : ($row->claim_status === 'yellow' ? '1' : '0') }}">
                                @if($row->claim_status === 'green')
                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ความพร้อม: พร้อมส่งออกและปิดสิทธิแล้ว (คลิกเพื่อดูรายละเอียด)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @elseif($row->claim_status === 'yellow')
                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ความพร้อม: ผ่านเกณฑ์แต่ยังไม่ได้ปิดสิทธิ (คลิกเพื่อตรวจสอบ/ปิดสิทธิ)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')" title="ความพร้อม: ไม่ผ่านเกณฑ์ (คลิกเพื่อดูหัวข้อที่ต้องแก้ไข)">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-center small">
                                @php
                                    $invoice_no = !empty($row->sss_invno) ? $row->sss_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
                                @endphp
                                @if($invoice_no && $invoice_no !== '0')
                                    <span class="badge bg-success-soft text-success fw-bold">{{ $invoice_no }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-start">
                                <div class="small fw-bold text-nowrap">{{ DateThai($row->vstdate) }}</div>
                                <div class="text-muted text-nowrap" style="font-size: 0.7rem;">เวลา {{$row->vsttime}} | Q: {{ $row->oqueue }}</div>
                            </td>            
                            <td class="text-center fw-bold text-primary small">{{$row->hn}}</td> 
                            <td class="text-center small text-muted text-nowrap">{{$row->cid}}</td> 
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

                            <td class="text-end fw-bold text-dark small px-3">{{ $row->pdx }}</td>
                            <td class="text-start small px-3">
                                <div class="text-dark">{{ $row->sdx }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">{{ $row->icd9 }}</div>
                            </td>
                            <td class="text-end small">{{ number_format($row->income,2) }}</td>              
                            <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td>
                            <td class="text-center small">
                                @if($row->rep_error)
                                    <button class="btn btn-link p-0 badge bg-danger-soft text-danger fw-bold border-0" onclick="showRepDetails('{{ $row->vn }}')" title="คลิกเพื่อดูรายละเอียดข้อผิดพลาด">{{ $row->rep_error }}</button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center small">
                                @if($row->rep_warning)
                                    <button class="btn btn-link p-0 badge bg-warning-soft text-warning fw-bold border-0" onclick="showRepDetails('{{ $row->vn }}')" title="คลิกเพื่อดูรายละเอียดข้อแนะนำ">{{ $row->rep_warning }}</button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end small fw-bold text-success">
                                @if($row->stm_pay !== null)
                                    {{ number_format($row->stm_pay, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td> 
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
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>          
        </div>
    </div>
