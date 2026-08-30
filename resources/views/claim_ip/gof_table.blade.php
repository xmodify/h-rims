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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ IP-GOF เบิกต้นสังกัด
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

                            <button type="submit" class="btn btn-success px-3 shadow-sm">
                                <i class="bi bi-table me-1"></i> โหลด indiv
                            </button>
                            <button type="button" class="btn btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importHubModal">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> นำเข้าข้อมูล
                            </button>
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
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">ตึก</th>
                                    <th class="text-center">ADMIT</th>
                                    <th class="text-center">D/C</th>
                                    <th class="text-center">REFER</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">AN</th>
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="15%">วินิจฉัยแพทย์</th>
                                    <th class="text-center">ICD10/ICD9</th>
                                    <th class="text-center">ADJRW</th>
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
                                    <td class="text-center">
                                        @if(!$row->is_valid)
                                            <button type="button" class="btn btn-outline-danger btn-xs py-0 px-1" title="ข้อมูลไม่ครบถ้วน (คลิกเพื่อดูรายละเอียด)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @elseif($row->is_valid && !$row->auth_valid)
                                            <button type="button" class="btn btn-outline-warning btn-xs py-0 px-1 text-dark" title="ข้อมูลพร้อมส่ง (ยังไม่มีเลข Authen)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-xs py-0 px-1" title="ข้อมูลพร้อมสมบูรณ์ (คลิกเพื่อดูรายละเอียด)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ $row->ward }}</td>
                                    <td class="text-center small">
                                        <div>{{ DateThaiShort($row->regdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ !empty($row->regtime) ? substr($row->regtime, 0, 5).' น.' : '' }}</div>
                                    </td>
                                    <td class="text-center small">
                                        <div>{{ DateThaiShort($row->dchdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ !empty($row->dchtime) ? substr($row->dchtime, 0, 5).' น.' : '' }}</div>
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->refer ?? '-' }}</td>
                                    <td class="text-center fw-bold text-primary small">{{ $row->hn }}</td>
                                    <td class="text-center small">{{ $row->an }}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{ $row->ptname }} ({{ $row->age_y ?? '-' }} ปี)</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</div>
                                    </td>
                                    <td class="text-start small text-truncate" style="max-width: 160px;" title="{{ $row->diag_text_list }}">{{ $row->diag_text_list }}</td>
                                    <td class="text-center small">
                                        @if(!empty($row->icd10))
                                            <div class="fw-bold text-dark">{{ $row->icd10 }}</div>
                                        @endif
                                        @if(!empty($row->icd9))
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $row->icd9 }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ !empty($row->adjrw) ? number_format($row->adjrw, 4) : '-' }}</td>
                                    <td class="text-end small">{{ number_format($row->income, 2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money, 2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price, 2) }}</td>
                                </tr>
                                @php
                                    $count++;
                                    $sum_income += (float)$row->income;
                                    $sum_rcpt_money += (float)$row->rcpt_money;
                                    $sum_claim_price += (float)$row->claim_price;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold bg-light">
                                    <td colspan="11" class="text-end text-dark">รวมทั้งสิ้น ({{ count($search) }} รายการ) :</td>
                                    <td class="text-end text-dark">{{ number_format($sum_income, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($sum_rcpt_money, 2) }}</td>
                                    <td class="text-end text-primary">{{ number_format($sum_claim_price, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Tab 2: Claimed -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">สถานะ</th>
                                    <th class="text-center">ตึก</th>
                                    <th class="text-center">ADMIT</th>
                                    <th class="text-center">D/C</th>
                                    <th class="text-center">REFER</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">AN</th>
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="15%">วินิจฉัยแพทย์</th>
                                    <th class="text-center">ICD10/ICD9</th>
                                    <th class="text-center">ADJRW</th>
                                    <th class="text-center">ค่ารักษา</th>  
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @php 
                                    $count = 1; 
                                    $c_sum_income = 0; 
                                    $c_sum_rcpt_money = 0; 
                                    $c_sum_claim_price = 0; 
                                @endphp
                                @foreach($claim as $row) 
                                <tr>
                                    <td class="text-center">
                                        @if(!$row->is_valid)
                                            <button type="button" class="btn btn-outline-danger btn-xs py-0 px-1" title="ข้อมูลไม่ครบถ้วน (คลิกเพื่อดูรายละเอียด)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @elseif($row->is_valid && !$row->auth_valid)
                                            <button type="button" class="btn btn-outline-warning btn-xs py-0 px-1 text-dark" title="ข้อมูลพร้อมส่ง (ยังไม่มีเลข Authen)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-xs py-0 px-1" title="ข้อมูลพร้อมสมบูรณ์ (คลิกเพื่อดูรายละเอียด)" onclick="showDetails('{{ $row->an }}')">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ $row->ward }}</td>
                                    <td class="text-center small">
                                        <div>{{ DateThaiShort($row->regdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ !empty($row->regtime) ? substr($row->regtime, 0, 5).' น.' : '' }}</div>
                                    </td>
                                    <td class="text-center small">
                                        <div>{{ DateThaiShort($row->dchdate) }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">{{ !empty($row->dchtime) ? substr($row->dchtime, 0, 5).' น.' : '' }}</div>
                                    </td>
                                    <td class="text-center small text-muted">{{ $row->refer ?? '-' }}</td>
                                    <td class="text-center fw-bold text-primary small">{{ $row->hn }}</td>
                                    <td class="text-center small">{{ $row->an }}</td>
                                    <td class="text-start">
                                        <div class="text-dark fw-bold small text-truncate" style="max-width: 150px;">{{ $row->ptname }} ({{ $row->age_y ?? '-' }} ปี)</div>
                                        <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</div>
                                    </td>
                                    <td class="text-start small text-truncate" style="max-width: 160px;" title="{{ $row->diag_text_list }}">{{ $row->diag_text_list }}</td>
                                    <td class="text-center small">
                                        @if(!empty($row->icd10))
                                            <div class="fw-bold text-dark">{{ $row->icd10 }}</div>
                                        @endif
                                        @if(!empty($row->icd9))
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $row->icd9 }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ !empty($row->adjrw) ? number_format($row->adjrw, 4) : '-' }}</td>
                                    <td class="text-end small">{{ number_format($row->income, 2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money, 2) }}</td>
                                    <td class="text-end fw-bold text-primary small">{{ number_format($row->claim_price, 2) }}</td>
                                </tr>
                                @php
                                    $count++;
                                    $c_sum_income += (float)$row->income;
                                    $c_sum_rcpt_money += (float)$row->rcpt_money;
                                    $c_sum_claim_price += (float)$row->claim_price;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold bg-light">
                                    <td colspan="11" class="text-end text-dark">รวมทั้งสิ้น ({{ count($claim) }} รายการ) :</td>
                                    <td class="text-end text-dark">{{ number_format($c_sum_income, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($c_sum_rcpt_money, 2) }}</td>
                                    <td class="text-end text-primary">{{ number_format($c_sum_claim_price, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>