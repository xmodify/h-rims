@php
    $is_f16_licensed = \App\Services\LicenseVerificationService::isModuleLicensed('export_f16_fdh') || \App\Services\LicenseVerificationService::isModuleLicensed('export_f16_eclaim');
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

        <!-- Section 2: Tabs & Tables -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ UC-IP นอก CUP
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
                            <button type="button" class="btn btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importHubModal">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> นำเข้าข้อมูล
                            </button>
                            @if($is_f16_licensed)
                            <button type="button" class="btn text-white fw-bold px-3 shadow-sm" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;" onclick="exportSelectedF16FDH('UCS_IP_OUTCUP')">
                                <i class="bi bi-box-arrow-up-right me-1"></i> ส่งออก 16 แฟ้ม
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                     <span class="badge bg-secondary ms-1 rounded-pill">{{ count($search) }}</span></button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                     <span class="badge bg-success ms-1 rounded-pill">{{ count($claim) }}</span></button>
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
                                    @if($is_f16_licensed)
                                    <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                    @endif
                                    <th class="text-center">FDH</th>
                                    <th class="text-center">ความพร้อม</th>
                                    <th class="text-center">ตึก</th>
                                    <th class="text-center">Admit</th>
                                    <th class="text-center">D/C</th>
                                    <th class="text-center">Refer</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">AN</th>
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="15%">วินิจฉัยแพทย์</th>
                                    <th class="text-center">ICD10/ICD9</th>
                                    <th class="text-center">AdjRW</th>
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
                                    @if($is_f16_licensed)
                                    <td class="text-center" style="vertical-align: middle;">
                                        <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->an }}">
                                    </td>
                                    @endif
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-success px-2 py-0 border-2 fw-bold" style="font-size: 0.7rem;" onclick="checkFdh('{{ $row->hn }}','{{ $row->an }}')">FDH</button>
                                    </td>
                                    <td class="text-start ps-3" data-order="{{ $row->data_ok == 'Y' ? '2' : '1' }}">
                                        <div class="d-flex flex-column align-items-start gap-1">
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">Authen:</span>
                                                @if($row->auth_code == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="Authen Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="Authen N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">สรุป Chart:</span>
                                                @if($row->dch_sum == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="สรุป Chart Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="สรุป Chart N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">พร้อมส่ง:</span>
                                                @if($row->data_ok == 'Y')
                                                    <i class="bi bi-check-circle-fill text-success" title="พร้อมส่ง Y"></i>
                                                @else
                                                    <i class="bi bi-x-circle-fill text-danger" title="พร้อมส่ง N"></i>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                <span class="text-muted">สถานะ:</span>
                                                <span class="text-dark fw-bold">{{ $row->ipt_coll_status_type_name ?: '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center small">{{$row->ward}}</td>
                                    <td class="text-center small">
                                        <div>{{ DateThai($row->regdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ substr($row->regtime, 0, 5) }} น.</div>
                                    </td>
                                    <td class="text-center small">
                                        <div>{{ DateThai($row->dchdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ substr($row->dchtime, 0, 5) }} น.</div>
                                    </td>
                                    <td class="text-end small">{{ $row->refer }}</td>
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-center small">{{$row->an}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}} ({{ $row->age_y }} ปี)</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}} [{{ $row->hospmain }}]</div>
                                    </td> 
                                    <td class="text-start small text-muted text-wrap">{{ $row->diag_text_list }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->icd10 }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-center small">{{ $row->adjrw }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price,2) }}</td> 
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
                                    <th colspan="{{ $is_f16_licensed ? 13 : 12 }}" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_claim_price,2) }}</th>
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
                                    @if($is_f16_licensed)
                                    <th class="text-center no-sort" rowspan="2" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                    @endif
                                    <th class="text-center" rowspan="2">Error (REP)</th>
                                    <th class="text-center" rowspan="2">ตึก</th>
                                    <th class="text-center" rowspan="2">Admit</th>
                                    <th class="text-center" rowspan="2">D/C</th>
                                    <th class="text-center" rowspan="2">Refer</th>
                                    <th class="text-center" rowspan="2">HN</th>
                                    <th class="text-center" rowspan="2">AN</th>
                                    <th class="text-center" rowspan="2">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" rowspan="2">ICD10/ICD9</th>
                                    <th class="text-center" rowspan="2">AdjRW</th>
                                    <th class="text-center" colspan="3">ค่ารักษา</th>                                     
                                    <th class="text-center bg-primary-soft" colspan="6">ข้อมูลการชดเชย</th>
                                </tr>
                                <tr>                                    
                                    <th class="text-center small">รวมทั้งหมด</th>
                                    <th class="text-center small">ชำระเอง</th>                                                                  
                                    <th class="text-center text-primary small">รวมส่งเคลม</th>
                                    <th class="text-center bg-primary-soft small">อัตรา/Rw</th> 
                                    <th class="text-center bg-primary-soft small">ชดเชย Rw</th>
                                    <th class="text-center bg-primary-soft small">ชดเชย Other</th>
                                    <th class="text-center bg-primary-soft small">ชดเชยทั้งหมด</th> 
                                    <th class="text-center bg-primary-soft small">ส่วนต่าง</th> 
                                    <th class="text-center bg-primary-soft small">REP No.</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $sum_income = 0; 
                                    $sum_rcpt_money = 0; 
                                    $sum_claim_price = 0; 
                                    $sum_receive_rw = 0; 
                                    $sum_receive_total = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    @if($is_f16_licensed)
                                    <td class="text-center" style="vertical-align: middle;">
                                        <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->an }}">
                                    </td>
                                    @endif
                                    <td class="text-center small" data-order="{{ $row->rep_error ?: '-' }}">
                                         @if(!empty($row->rep_error))
                                             <span class="badge bg-danger fw-bold" style="font-size: 0.72rem;" title="ติด C (ข้อผิดพลาด REP): {{ $row->rep_error }}">
                                                 C: {{ $row->rep_error }}
                                             </span>
                                         @else
                                             <span class="text-muted">-</span>
                                         @endif
                                     </td>
                                    <td class="text-center small">{{$row->ward}}</td>
                                    <td class="text-center small">
                                        <div>{{ DateThai($row->regdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ substr($row->regtime, 0, 5) }} น.</div>
                                    </td>
                                    <td class="text-center small">
                                        <div>{{ DateThai($row->dchdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ substr($row->dchtime, 0, 5) }} น.</div>
                                    </td>
                                    <td class="text-end small">{{ $row->refer }}</td>
                                    <td class="text-center fw-bold text-primary small">{{$row->hn}}</td>
                                    <td class="text-center small">{{$row->an}}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{$row->ptname}} ({{ $row->age_y }} ปี)</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}} [{{ $row->hospmain }}]</div>
                                    </td> 
                                    <td class="text-center small">
                                        <div class="fw-bold text-dark">{{ $row->icd10 }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{$row->icd9}}</div>
                                    </td>
                                    <td class="text-center small">{{ $row->adjrw }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price,2) }}</td> 
                                    <td class="text-end small">{{ number_format($row->fund_ip_payrate,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->receive_ip_compensate_pay,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->receive_total-$row->receive_ip_compensate_pay,2) }}</td>
                                    <td class="text-end small fw-bold {{ $row->receive_total > 0 ? 'text-success' : ($row->receive_total < 0 ? 'text-danger' : 'text-dark') }}">{{ number_format($row->receive_total,2) }}</td>
                                    <td class="text-end small fw-bold {{ ($row->receive_total-$row->claim_price) > 0 ? 'text-success' : (($row->receive_total-$row->claim_price) < 0 ? 'text-danger' : 'text-dark') }}">
                                        {{ number_format($row->receive_total-$row->claim_price,2) }}
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->repno }}</td> 
                                </tr>
                                @php 
                                    $count++; 
                                    $sum_income += $row->income; 
                                    $sum_rcpt_money += $row->rcpt_money; 
                                    $sum_claim_price += $row->claim_price; 
                                    $sum_receive_rw += $row->receive_ip_compensate_pay; 
                                    $sum_receive_total += $row->receive_total; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="{{ $is_f16_licensed ? 11 : 10 }}" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_claim_price,2) }}</th>
                                    <th></th>
                                    <th class="text-end small">{{ number_format($sum_receive_rw,2)}}</th>
                                    <th class="text-end small">{{ number_format($sum_receive_total-$sum_receive_rw,2)}}</th>
                                    <th class="text-end small fw-bold {{ $sum_receive_total > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total,2) }}</th>
                                    <th class="text-end small fw-bold {{ ($sum_receive_total-$sum_claim_price) > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sum_receive_total-$sum_claim_price, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 
            </div>
        </div>
    </div>