@php
    $is_csop_licensed = \App\Services\LicenseVerificationService::isLicensed();
    $tabs = [
        ['id' => 'search', 'title' => 'รอส่ง Claim', 'icon' => 'bi-clock-history', 'badge_class' => 'bg-secondary', 'data' => $search, 'show_checkbox' => true],
        ['id' => 'claim', 'title' => 'ส่ง Claim แล้ว', 'icon' => 'bi-send-check', 'badge_class' => 'bg-success', 'data' => $claim, 'show_checkbox' => false],
        ['id' => 'warning', 'title' => 'ติด C', 'icon' => 'bi-exclamation-octagon', 'badge_class' => 'bg-danger', 'data' => $warning, 'show_checkbox' => true]
    ];
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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้มารับบริการ OP-CSOP กกต.
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
                            <button type="button" class="btn btn-outline-primary px-3 shadow-sm" onclick="$('#importFeedbackModal').modal('show');">
                                <i class="bi bi-file-earmark-zip me-1"></i> นำเข้าข้อมูลตอบกลับ
                            </button>
                            @if($is_csop_licensed)
                            <button type="button" class="btn btn-outline-success px-3 shadow-sm" onclick="exportSelectedCSOP()">
                                <i class="bi bi-box-arrow-up-fill me-1"></i> ส่งออก CSOP (.zip)
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab Headers -->
            <ul class="nav nav-tabs-modern mt-3 mb-0" id="pills-tab" role="tablist">
                @foreach($tabs as $index => $tab)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="{{ $tab['id'] }}-tab" data-bs-toggle="pill" data-bs-target="#{{ $tab['id'] }}-pane" type="button" role="tab">
                        <i class="bi {{ $tab['icon'] }} me-1"></i> {{ $tab['title'] }}
                        <span class="badge {{ $tab['badge_class'] }} ms-1 rounded-pill">{{ count($tab['data']) }}</span>
                    </button>
                </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-3">
            <div class="tab-content" id="myTabContent">
                @foreach($tabs as $index => $tab)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $tab['id'] }}-pane" role="tabpanel">
                    <div class="table-responsive">
                        <table id="t_{{ $tab['id'] }}" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    @if($is_csop_licensed && $tab['show_checkbox'])
                                    <th class="text-center" width="5%" style="min-width: 45px;"><input type="checkbox" class="select_all_claims"></th>
                                    @endif
                                    <th class="text-center">ตรวจสอบ</th>
                                    <th class="text-center" width="8%">InvoiceNo</th>
                                    <th class="text-center" width="10%">วัน-เวลา | Q</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">CID</th>
                                    <th class="text-center">ชื่อ-สกุล | สิทธิ</th>
                                    <th class="text-center" width="15%">อาการสำคัญ</th>
                                    <th class="text-end px-3" width="6%">PDX</th>
                                    <th class="text-start px-3" width="10%">SDX | ICD9</th>
                                    <th class="text-center">ค่ารักษา</th>
                                    <th class="text-center">ชำระเอง</th>
                                    <th class="text-center text-primary">เรียกเก็บ</th>
                                    @if($tab['id'] === 'warning')
                                        <th class="text-center" width="8%">Rep Error</th>
                                    @endif
                                    @if($tab['id'] !== 'search')
                                        <th class="text-center" width="8%">Rep Warning</th>
                                    @endif
                                    @if($tab['id'] === 'claim')
                                        <th class="text-center" width="8%">stm ชดเชย</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sum_income = 0;
                                    $sum_rcpt_money = 0;
                                    $sum_claim_price = 0;
                                @endphp
                                @foreach($tab['data'] as $row)
                                @php
                                    $has_invoice = (($row->csop_invno && $row->csop_invno !== '0') || ($row->debt_id_list && $row->debt_id_list !== '0')) ? 'true' : 'false';
                                @endphp
                                <tr data-has-error="{{ $row->rep_error ? 'true' : 'false' }}" data-has-invoice="{{ $has_invoice }}">
                                    @if($is_csop_licensed && $tab['show_checkbox'])
                                    <td class="text-center">
                                        <input type="checkbox" class="claim-select-check" value="{{ $row->vn }}" data-has-error="{{ $row->rep_error ? 'true' : 'false' }}">
                                    </td>
                                    @endif
                                    
                                    <td class="text-center" data-status="{{ $row->claim_status }}" data-order="{{ $row->claim_status === 'red' ? '2' : ($row->claim_status === 'yellow' ? '1' : '0') }}">
                                        <button class="btn btn-sm {{ $row->claim_status === 'green' ? 'btn-outline-success' : ($row->claim_status === 'yellow' ? 'btn-outline-warning' : 'btn-outline-danger') }} px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->vn }}')">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </td>
                                    <td class="text-center small">
                                        @php
                                            $invoice_no = !empty($row->csop_invno) ? $row->csop_invno : (!empty($row->debt_id_list) ? $row->debt_id_list : '');
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
                                    <td class="text-end fw-bold text-dark small px-3">{{ $row->pdx }}</td>
                                    <td class="text-start small px-3">
                                        <div class="text-dark">{{ $row->sdx }}</div>
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $row->icd9 }}</div>
                                    </td>
                                    <td class="text-end small">{{ number_format($row->income,2) }}</td>
                                    <td class="text-end small">{{ number_format($row->rcpt_money,2) }}</td>
                                    <td class="text-end fw-bold text-primary">{{ number_format($row->claim_price,2) }}</td>
                                    @if($tab['id'] === 'warning')
                                        <td class="text-center small">
                                            @if($row->rep_error)
                                                <span class="badge bg-danger-soft text-danger fw-bold cursor-pointer text-decoration-underline" style="cursor: pointer;" onclick="showRepDetails('{{ $row->vn }}')" title="คลิกเพื่อดูรายละเอียดข้อผิดพลาด">{{ $row->rep_error }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($tab['id'] !== 'search')
                                        <td class="text-center small">
                                            @if($row->rep_warning)
                                                <span class="badge bg-warning-soft text-warning fw-bold cursor-pointer text-decoration-underline" style="cursor: pointer;" onclick="showRepDetails('{{ $row->vn }}')" title="คลิกเพื่อดูรายละเอียดข้อแนะนำ">{{ $row->rep_warning }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if($tab['id'] === 'claim')
                                        <td class="text-end small fw-bold text-success">
                                            @if($row->stm_pay !== null)
                                                {{ number_format($row->stm_pay, 2) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @php
                                    $sum_income += $row->income;
                                    $sum_rcpt_money += $row->rcpt_money;
                                    $sum_claim_price += $row->claim_price;
                                @endphp
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light-soft">
                                <tr>
                                    <th colspan="{{ $is_csop_licensed && $tab['show_checkbox'] ? 10 : 9 }}" class="text-end text-muted small px-3">รวมงบประมาณที่ค้นพบ:</th>
                                    <th class="text-end small">{{ number_format($sum_income,2) }}</th>
                                    <th class="text-end small">{{ number_format($sum_rcpt_money,2) }}</th>
                                    <th class="text-end fw-bold text-primary">{{ number_format($sum_claim_price,2) }}</th>
                                    @if($tab['id'] === 'warning')
                                        <th></th>
                                    @endif
                                    @if($tab['id'] !== 'search')
                                        <th></th>
                                    @endif
                                    @if($tab['id'] === 'claim')
                                        <th></th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
