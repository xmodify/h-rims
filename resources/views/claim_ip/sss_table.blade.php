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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ IP-SSS ประกันสังคม
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
                            <button type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                            <button type="button" class="btn btn-outline-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importFeedbackModal">
                                <i class="bi bi-file-earmark-zip me-1"></i> นำเข้าข้อมูลตอบกลับ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="search-tab" data-bs-toggle="pill" data-bs-target="#search" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> รอส่ง Claim
                        <span class="badge bg-secondary ms-1 rounded-pill">{{ count($search) }}</span>
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="claim-tab" data-bs-toggle="pill" data-bs-target="#claim" type="button" role="tab">
                        <i class="bi bi-send-check me-1"></i> ส่ง Claim แล้ว
                        <span class="badge bg-secondary ms-1 rounded-pill">{{ count($claim) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="warning-tab" data-bs-toggle="pill" data-bs-target="#warning" type="button" role="tab">
                        <i class="bi bi-exclamation-octagon me-1"></i> ติด C
                        <span class="badge bg-danger text-white ms-1 rounded-pill">{{ count($warning) }}</span>
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
                                    <th class="text-center" width="10%">ความพร้อม</th>
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
                                    <td class="text-start ps-3" data-order="{{ $row->auth_code == 'Y' ? '2' : '1' }}">
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
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
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
                                    <th colspan="11" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
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
                                    <th class="text-center" width="10%">ความพร้อม</th>
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
                                    <th class="text-center" width="8%">REP ERROR</th>
                                    <th class="text-center" width="8%">REP WARNING</th>
                                    <th class="text-center" width="8%">STM ชดเชย</th>
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
                                <tr>
                                    <td class="text-start ps-3" data-order="{{ $row->auth_code == 'Y' ? '2' : '1' }}">
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
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
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
                                    <td class="text-center small">
                                        @if(!empty($row->rep_error))
                                            <button class="btn btn-link p-0 badge bg-danger-soft text-danger fw-bold border-0" onclick="showRepDetails('{{ $row->an }}')" title="คลิกเพื่อดูรายละเอียดข้อผิดพลาด">{{ $row->rep_error }}</button>
                                            @if(!empty($row->rep_repno))
                                                <div class="text-muted" style="font-size: 0.65rem; margin-top: 2px;" title="เลขที่ตอบรับ: {{ $row->rep_repno }} ({{ DateThai($row->rep_rep_date) }})">
                                                    REP: #{{ $row->rep_repno }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if(!empty($row->rep_warning))
                                            <button class="btn btn-link p-0 badge bg-warning-soft text-warning fw-bold border-0" onclick="showRepDetails('{{ $row->an }}')" title="คลิกเพื่อดูรายละเอียดข้อแนะนำ">{{ $row->rep_warning }}</button>
                                            @if(!empty($row->rep_repno))
                                                <div class="text-muted" style="font-size: 0.65rem; margin-top: 2px;" title="เลขที่ตอบรับ: {{ $row->rep_repno }} ({{ DateThai($row->rep_rep_date) }})">
                                                    REP: #{{ $row->rep_repno }}
                                                </div>
                                            @endif
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
                                    <th colspan="11" class="text-end text-muted small px-3">รวมงบประมาณที่ส่งเบิก:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($sum_claim_price,2) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div> 

                <!-- Tab 3: Claims with Warnings -->
                <div class="tab-pane fade" id="warning" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_warning" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" width="10%">ความพร้อม</th>
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
                                    <th class="text-center" width="8%">REP ERROR</th>
                                    <th class="text-center" width="8%">REP WARNING</th>
                                    <th class="text-center" width="8%">STM ชดเชย</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $w_count = 1; 
                                    $w_sum_income = 0; 
                                    $w_sum_rcpt_money = 0; 
                                    $w_sum_claim_price = 0; 
                                @endphp
                                @foreach($warning as $row) 
                                <tr>
                                    <td class="text-start ps-3" data-order="{{ $row->auth_code == 'Y' ? '2' : '1' }}">
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
                                                <span class="text-muted">สถานะ:</span>
                                                <span class="text-dark fw-bold">{{ $row->ipt_coll_status_type_name ?: '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center small">{{ $row->ward }}</td>
                                    <td class="text-center small" data-order="{{ $row->regdate }}">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>{{ DateThai($row->regdate) }}</span>
                                            <span class="text-muted" style="font-size: 0.75rem;">{{ substr($row->regtime, 0, 5) }} น.</span>
                                        </div>
                                    </td>
                                    <td class="text-center small" data-order="{{ $row->dchdate }}">
                                        <div class="d-flex flex-column align-items-center">
                                            <span>{{ DateThai($row->dchdate) }}</span>
                                            <span class="text-muted" style="font-size: 0.75rem;">{{ substr($row->dchtime, 0, 5) }} น.</span>
                                        </div>
                                    </td>
                                    <td class="text-center small">{{ $row->refer ?: '-' }}</td>
                                    <td class="text-center small">
                                        <a href="javascript:void(0)" onclick="loadPatientDetail('{{ $row->hn }}')" class="fw-bold text-decoration-none">{{ $row->hn }}</a>
                                    </td>
                                    <td class="text-center small fw-bold">{{ $row->an }}</td>
                                    <td class="text-start small">
                                        <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $row->pttype }}</div>
                                    </td>
                                    <td class="text-start small text-truncate" style="max-width: 150px;" title="{{ $row->diag_text_list }}">{{ $row->diag_text_list }}</td>
                                    <td class="text-center small">
                                        <div class="fw-bold">{{ $row->icd10 }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $row->icd9 ?: '-' }}</div>
                                    </td>
                                    <td class="text-center small">{{ $row->adjrw }}</td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price,2) }}</td>
                                    <td class="text-center small">
                                        @if(!empty($row->rep_error))
                                            <button class="btn btn-link p-0 badge bg-danger-soft text-danger fw-bold border-0" onclick="showRepDetails('{{ $row->an }}')" title="คลิกเพื่อดูรายละเอียดข้อผิดพลาด">{{ $row->rep_error }}</button>
                                            @if(!empty($row->rep_repno))
                                                <div class="text-muted" style="font-size: 0.65rem; margin-top: 2px;" title="เลขที่ตอบรับ: {{ $row->rep_repno }} ({{ DateThai($row->rep_rep_date) }})">
                                                    REP: #{{ $row->rep_repno }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">
                                        @if(!empty($row->rep_warning))
                                            <button class="btn btn-link p-0 badge bg-warning-soft text-warning fw-bold border-0" onclick="showRepDetails('{{ $row->an }}')" title="คลิกเพื่อดูรายละเอียดข้อแนะนำ">{{ $row->rep_warning }}</button>
                                            @if(!empty($row->rep_repno))
                                                <div class="text-muted" style="font-size: 0.65rem; margin-top: 2px;" title="เลขที่ตอบรับ: {{ $row->rep_repno }} ({{ DateThai($row->rep_rep_date) }})">
                                                    REP: #{{ $row->rep_repno }}
                                                </div>
                                            @endif
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
                                    $w_count++; 
                                    $w_sum_income += $row->income; 
                                    $w_sum_rcpt_money += $row->rcpt_money; 
                                    $w_sum_claim_price += $row->claim_price; 
                                @endphp
                                @endforeach                 
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="11" class="text-end text-muted small px-3">รวมงบประมาณที่ติด C:</th>
                                    <th class="text-end small">{{ number_format($w_sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($w_sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary small">{{ number_format($w_sum_claim_price,2) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>          
                </div>
            </div>
        </div>
    </div>