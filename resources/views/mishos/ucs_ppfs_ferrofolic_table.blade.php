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
                        <i class="bi bi-people-fill text-primary me-2"></i>รายชื่อผู้รับบริการยาเสริมธาตุเหล็ก (Ferrofolic & Iron)
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
                            <button type="button" class="btn text-white fw-bold px-3 shadow-sm" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;" onclick="exportSelectedF16FDH('UCS_PPFS_FERROFOLIC')">
                                <i class="bi bi-box-arrow-up-right me-1"></i> ส่งออก 16 แฟ้ม
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
                        <span class="badge bg-success ms-1 rounded-pill">{{ count($claim) }}</span>
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body px-4 pb-4 pt-0">
            
            <div class="tab-content" id="myTabContent">
                <!-- Tab 1: Waiting for Claim (with 3 sub-tabs: Tablets, Syrup Preschool, Syrup School) -->
                <div class="tab-pane fade show active" id="search" role="tabpanel">
                    
                    <!-- Sub-tabs Navigation -->
                    <div class="d-flex align-items-center justify-content-between mb-3 pt-3 border-bottom pb-2">
                        <ul class="nav nav-pills gap-2" id="search-sub-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active px-3 py-1 fw-bold rounded-pill shadow-sm" id="tablet-tab" data-bs-toggle="pill" data-bs-target="#tablet" type="button" role="tab" style="font-size: 0.82rem;">
                                    <i class="bi bi-capsule me-1 text-danger"></i> ยาเม็ดเสริมธาตุเหล็ก (หญิง 13–45 ปี)
                                    <span class="badge bg-danger text-white rounded-pill ms-1">{{ count($search_tablet) }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1 fw-bold rounded-pill shadow-sm" id="syrup-preschool-tab" data-bs-toggle="pill" data-bs-target="#syrup_preschool" type="button" role="tab" style="font-size: 0.82rem;">
                                    <i class="bi bi-droplet-half me-1 text-primary"></i> ยาน้ำเสริมธาตุเหล็ก (เด็ก 2 ด.–5 ปี)
                                    <span class="badge bg-primary text-white rounded-pill ms-1">{{ count($search_syrup_preschool) }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-3 py-1 fw-bold rounded-pill shadow-sm" id="syrup-school-tab" data-bs-toggle="pill" data-bs-target="#syrup_school" type="button" role="tab" style="font-size: 0.82rem;">
                                    <i class="bi bi-person-badge me-1 text-info"></i> ยาน้ำ/ยาเสริมธาตุเหล็ก (เด็ก 6–12 ปี)
                                    <span class="badge bg-info text-dark rounded-pill ms-1">{{ count($search_syrup_school) }}</span>
                                </button>
                            </li>
                        </ul>
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i> ยาเม็ด หญิง 13–45 ปี (ADP 14001 เหมาจ่าย 80 บ.) | ยาน้ำเด็ก 2 ด.–5 ปี และ 6–12 ปี (เหมาจ่าย 50 บ./ปี)
                        </div>
                    </div>

                    <div class="tab-content" id="searchSubTabContent">
                        
                        <!-- Sub-tab 1: ยาเม็ดเสริมธาตุเหล็ก (หญิง 13-45 ปี) -->
                        <div class="tab-pane fade show active" id="tablet" role="tabpanel">
                            <div class="table-responsive">            
                                <table id="t_search_tablet" class="table table-modern w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                            <th class="text-center">#</th>
                                            <th class="text-center">ตรวจสอบ</th>
                                            <th class="text-center" width="8%">วันที่รับบริการ</th>
                                            <th class="text-center">Queue</th>
                                            <th class="text-center">ห้องตรวจ</th>
                                            <th class="text-center">HN</th>
                                            <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                            <th class="text-start" width="15%">สิทธิการรักษา</th>
                                            <th class="text-center">อายุ</th>
                                            <th class="text-start">รายการเรียกเก็บ</th>
                                            <th class="text-end">ค่ารักษาทั้งหมด</th>
                                            <th class="text-end">ชำระเอง</th>
                                            <th class="text-end text-primary">เรียกเก็บ</th>
                                            <th class="text-end text-success">ชดเชย</th>
                                            <th class="text-end">ส่วนต่าง</th>
                                            <th class="text-center" width="8%">Repno</th>
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
                                        @foreach($search_tablet as $row) 
                                        <tr>
                                            <td class="text-center" style="vertical-align: middle;">
                                                @if(!empty($row->can_export_fdh))
                                                    <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->vn ?? $row->seq }}" data-vn="{{ $row->vn ?? $row->seq }}">
                                                @else
                                                    <span class="badge bg-secondary-subtle text-muted border px-1" style="font-size: 0.65rem;" title="สิทธิ {{ $row->pttype ?? '-' }} ({{ $row->hipdata_code ?? 'Non-FDH' }}) ไม่สามารถส่งออก FDH กองทุน PPFS ได้ (เปิดให้เฉพาะสิทธิ UCS / STP)">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-muted small">{{ $count }}</td>
                                            <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->claim_valid ? 0 : ($row->endpoint_valid && empty($row->validation_warnings) ? 2 : 1) }}">
                                                @if(!$row->claim_valid)
                                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif(!empty($row->validation_warnings))
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีคำเตือน 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif($row->endpoint_valid)
                                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข 16 แฟ้ม + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="16 แฟ้มครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            
                                            <td class="text-center small">
                                                {{ DateThai($row->vstdate) }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                            </td>
                                            <td class="text-center small">{{ $row->oqueue }}</td>
                                            <td class="text-start small">
                                                <span class="badge bg-light text-dark border text-truncate" style="max-width: 130px; font-weight: 500;" title="{{ $row->main_dep_name ?? '-' }}">
                                                    {{ $row->main_dep_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                            <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                            <td class="text-start small text-muted">
                                                <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                                <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                            </td>
                                            <td class="text-center small">
                                                @if(isset($row->age_y) && $row->age_y == 0 && isset($row->age_m))
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $row->age_m }} ด.</span>
                                                @else
                                                    {{ $row->age_y ?? '-' }} ปี
                                                @endif
                                            </td>
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
                                            <td class="text-center small">{{ $row->repno ?? '-' }}</td>
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
                                    <tfoot>
                                        <tr class="fw-bold" style="background: #f8fafc;">
                                            <td colspan="11" class="text-end text-dark">รวมทั้งหมด (ยาเม็ด หญิง 13–45 ปี):</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_income, 2) }}</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_rcpt_money, 2) }}</td>
                                            <td class="text-end font-monospace text-primary">{{ number_format($sum_claim_price, 2) }}</td>
                                            <td class="text-end font-monospace text-success">{{ number_format($sum_receive_total, 2) }}</td>
                                            <td class="text-end font-monospace {{ ($sum_receive_total - $sum_claim_price) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($sum_receive_total - $sum_claim_price, 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-tab 2: ยาน้ำเสริมธาตุเหล็ก (เด็ก 2 ด.–5 ปี) -->
                        <div class="tab-pane fade" id="syrup_preschool" role="tabpanel">
                            <div class="table-responsive">            
                                <table id="t_search_syrup_preschool" class="table table-modern w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                            <th class="text-center">#</th>
                                            <th class="text-center">ตรวจสอบ</th>
                                            <th class="text-center" width="8%">วันที่รับบริการ</th>
                                            <th class="text-center">Queue</th>
                                            <th class="text-center">ห้องตรวจ</th>
                                            <th class="text-center">HN</th>
                                            <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                            <th class="text-start" width="15%">สิทธิการรักษา</th>
                                            <th class="text-center">อายุ</th>
                                            <th class="text-start">รายการเรียกเก็บ</th>
                                            <th class="text-end">ค่ารักษาทั้งหมด</th>
                                            <th class="text-end">ชำระเอง</th>
                                            <th class="text-end text-primary">เรียกเก็บ</th>
                                            <th class="text-end text-success">ชดเชย</th>
                                            <th class="text-end">ส่วนต่าง</th>
                                            <th class="text-center" width="8%">Repno</th>
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
                                        @foreach($search_syrup_preschool as $row) 
                                        <tr>
                                            <td class="text-center" style="vertical-align: middle;">
                                                @if(!empty($row->can_export_fdh))
                                                    <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->vn ?? $row->seq }}" data-vn="{{ $row->vn ?? $row->seq }}">
                                                @else
                                                    <span class="badge bg-secondary-subtle text-muted border px-1" style="font-size: 0.65rem;" title="สิทธิ {{ $row->pttype ?? '-' }} ({{ $row->hipdata_code ?? 'Non-FDH' }}) ไม่สามารถส่งออก FDH กองทุน PPFS ได้ (เปิดให้เฉพาะสิทธิ UCS / STP)">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-muted small">{{ $count }}</td>
                                            <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->claim_valid ? 0 : ($row->endpoint_valid && empty($row->validation_warnings) ? 2 : 1) }}">
                                                @if(!$row->claim_valid)
                                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif(!empty($row->validation_warnings))
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีคำเตือน 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif($row->endpoint_valid)
                                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข 16 แฟ้ม + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="16 แฟ้มครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            
                                            <td class="text-center small">
                                                {{ DateThai($row->vstdate) }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                            </td>
                                            <td class="text-center small">{{ $row->oqueue }}</td>
                                            <td class="text-start small">
                                                <span class="badge bg-light text-dark border text-truncate" style="max-width: 130px; font-weight: 500;" title="{{ $row->main_dep_name ?? '-' }}">
                                                    {{ $row->main_dep_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                            <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                            <td class="text-start small text-muted">
                                                <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                                <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                            </td>
                                            <td class="text-center small">
                                                @if(isset($row->age_y) && $row->age_y == 0 && isset($row->age_m))
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $row->age_m }} ด.</span>
                                                @else
                                                    {{ $row->age_y ?? '-' }} ปี
                                                @endif
                                            </td>
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
                                            <td class="text-center small">{{ $row->repno ?? '-' }}</td>
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
                                    <tfoot>
                                        <tr class="fw-bold" style="background: #f8fafc;">
                                            <td colspan="11" class="text-end text-dark">รวมทั้งหมด (ยาน้ำ เด็ก 2 ด.–5 ปี):</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_income, 2) }}</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_rcpt_money, 2) }}</td>
                                            <td class="text-end font-monospace text-primary">{{ number_format($sum_claim_price, 2) }}</td>
                                            <td class="text-end font-monospace text-success">{{ number_format($sum_receive_total, 2) }}</td>
                                            <td class="text-end font-monospace {{ ($sum_receive_total - $sum_claim_price) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($sum_receive_total - $sum_claim_price, 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Sub-tab 3: ยาน้ำ/ยาเสริมธาตุเหล็ก (เด็ก 6–12 ปี) -->
                        <div class="tab-pane fade" id="syrup_school" role="tabpanel">
                            <div class="table-responsive">            
                                <table id="t_search_syrup_school" class="table table-modern w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                            <th class="text-center">#</th>
                                            <th class="text-center">ตรวจสอบ</th>
                                            <th class="text-center" width="8%">วันที่รับบริการ</th>
                                            <th class="text-center">Queue</th>
                                            <th class="text-center">ห้องตรวจ</th>
                                            <th class="text-center">HN</th>
                                            <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                            <th class="text-start" width="15%">สิทธิการรักษา</th>
                                            <th class="text-center">อายุ</th>
                                            <th class="text-start">รายการเรียกเก็บ</th>
                                            <th class="text-end">ค่ารักษาทั้งหมด</th>
                                            <th class="text-end">ชำระเอง</th>
                                            <th class="text-end text-primary">เรียกเก็บ</th>
                                            <th class="text-end text-success">ชดเชย</th>
                                            <th class="text-end">ส่วนต่าง</th>
                                            <th class="text-center" width="8%">Repno</th>
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
                                        @foreach($search_syrup_school as $row) 
                                        <tr>
                                            <td class="text-center" style="vertical-align: middle;">
                                                @if(!empty($row->can_export_fdh))
                                                    <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->vn ?? $row->seq }}" data-vn="{{ $row->vn ?? $row->seq }}">
                                                @else
                                                    <span class="badge bg-secondary-subtle text-muted border px-1" style="font-size: 0.65rem;" title="สิทธิ {{ $row->pttype ?? '-' }} ({{ $row->hipdata_code ?? 'Non-FDH' }}) ไม่สามารถส่งออก FDH กองทุน PPFS ได้ (เปิดให้เฉพาะสิทธิ UCS / STP)">
                                                        <i class="bi bi-slash-circle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center text-muted small">{{ $count }}</td>
                                            <td class="text-center" id="td-status-search-{{ $row->seq }}" data-order="{{ !$row->claim_valid ? 0 : ($row->endpoint_valid && empty($row->validation_warnings) ? 2 : 1) }}">
                                                @if(!$row->claim_valid)
                                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif(!empty($row->validation_warnings))
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีคำเตือน 16 แฟ้ม | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @elseif($row->endpoint_valid)
                                                    <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข 16 แฟ้ม + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="16 แฟ้มครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </button>
                                                @endif
                                            </td>
                                            
                                            <td class="text-center small">
                                                {{ DateThai($row->vstdate) }}<br>
                                                <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                            </td>
                                            <td class="text-center small">{{ $row->oqueue }}</td>
                                            <td class="text-start small">
                                                <span class="badge bg-light text-dark border text-truncate" style="max-width: 130px; font-weight: 500;" title="{{ $row->main_dep_name ?? '-' }}">
                                                    {{ $row->main_dep_name ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                            <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                            <td class="text-start small text-muted">
                                                <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                                <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                            </td>
                                            <td class="text-center small">
                                                @if(isset($row->age_y) && $row->age_y == 0 && isset($row->age_m))
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $row->age_m }} ด.</span>
                                                @else
                                                    {{ $row->age_y ?? '-' }} ปี
                                                @endif
                                            </td>
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
                                            <td class="text-center small">{{ $row->repno ?? '-' }}</td>
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
                                    <tfoot>
                                        <tr class="fw-bold" style="background: #f8fafc;">
                                            <td colspan="11" class="text-end text-dark">รวมทั้งหมด (ยาน้ำ/ยาเสริมธาตุเหล็ก เด็ก 6–12 ปี):</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_income, 2) }}</td>
                                            <td class="text-end font-monospace text-dark">{{ number_format($sum_rcpt_money, 2) }}</td>
                                            <td class="text-end font-monospace text-primary">{{ number_format($sum_claim_price, 2) }}</td>
                                            <td class="text-end font-monospace text-success">{{ number_format($sum_receive_total, 2) }}</td>
                                            <td class="text-end font-monospace {{ ($sum_receive_total - $sum_claim_price) >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($sum_receive_total - $sum_claim_price, 2) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                
                <!-- Tab 2: Claims Sent -->
                <div class="tab-pane fade" id="claim" role="tabpanel">
                    <div class="table-responsive">            
                        <table id="t_claim" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center no-sort" width="45" style="width: 45px; min-width: 45px; max-width: 45px; vertical-align: middle;"><input type="checkbox" class="form-check-input select_all_f16" title="เลือกทั้งหมด"></th>
                                    <th class="text-center">#</th>
                                    <th class="text-center">ตรวจสอบ</th>
                                    <th class="text-center" width="8%">วันที่รับบริการ</th>
                                    <th class="text-center">Queue</th>
                                    <th class="text-center">ห้องตรวจ</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                    <th class="text-start" width="15%">สิทธิการรักษา</th>
                                    <th class="text-center">อายุ</th>
                                    <th class="text-start">รายการเรียกเก็บ</th>
                                    <th class="text-end">ค่ารักษาทั้งหมด</th>
                                    <th class="text-end">ชำระเอง</th>
                                    <th class="text-end text-primary">เรียกเก็บ</th>
                                    <th class="text-end text-success">ชดเชย</th>
                                    <th class="text-end">ส่วนต่าง</th>
                                    <th class="text-center" width="8%">Repno</th>
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
                                    <td class="text-center" style="vertical-align: middle;">
                                        @if(!empty($row->can_export_fdh))
                                            <input type="checkbox" class="form-check-input f16-select-item" value="{{ $row->vn ?? $row->seq }}" data-vn="{{ $row->vn ?? $row->seq }}">
                                        @else
                                            <span class="badge bg-secondary-subtle text-muted border px-1" style="font-size: 0.65rem;" title="สิทธิ {{ $row->pttype ?? '-' }} ({{ $row->hipdata_code ?? 'Non-FDH' }}) ไม่สามารถส่งออก FDH กองทุน PPFS ได้ (เปิดให้เฉพาะสิทธิ UCS / STP)">
                                                <i class="bi bi-slash-circle"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center text-muted small">{{ $count }}</td>
                                    <td class="text-center" id="td-status-claim-{{ $row->seq }}" data-order="{{ !$row->claim_valid ? 0 : ($row->endpoint_valid && empty($row->validation_warnings) ? 2 : 1) }}">
                                        @if(!$row->claim_valid)
                                            <button class="btn btn-sm btn-outline-danger px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ไม่ผ่านเงื่อนไข 16 แฟ้ม | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif(!empty($row->validation_warnings))
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="มีคำเตือน 16 แฟ้ม | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @elseif($row->endpoint_valid)
                                            <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข 16 แฟ้ม + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="16 แฟ้มครบ แต่ยังไม่ปิดสิทธิ สปสช. | คลิกดูรายละเอียด">
                                                <i class="bi bi-eye-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                    
                                    <td class="text-center small">
                                        {{ DateThai($row->vstdate) }}<br>
                                        <span class="text-muted" style="font-size: 0.75rem;">{{$row->vsttime}}</span>
                                    </td>
                                    <td class="text-center small">{{ $row->oqueue }}</td>
                                    <td class="text-start small">
                                        <span class="badge bg-light text-dark border text-truncate" style="max-width: 130px; font-weight: 500;" title="{{ $row->main_dep_name ?? '-' }}">
                                            {{ $row->main_dep_name ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center small text-primary fw-bold">{{$row->hn}}</td>
                                    <td class="text-start text-dark fw-bold small">{{$row->ptname}}</td>
                                    <td class="text-start small text-muted">
                                        <div class="text-truncate" style="max-width: 150px;" title="{{$row->pttype}}">{{$row->pttype}}</div>
                                        <div style="font-size: 0.7rem;">[{{$row->hospmain}}]</div>
                                    </td>
                                    <td class="text-center small">
                                        @if(isset($row->age_y) && $row->age_y == 0 && isset($row->age_m))
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $row->age_m }} ด.</span>
                                        @else
                                            {{ $row->age_y ?? '-' }} ปี
                                        @endif
                                    </td>
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
                                    <td class="text-center small">{{ $row->repno ?? '-' }}</td>
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
                            <tfoot>
                                <tr class="fw-bold" style="background: #f8fafc;">
                                    <td colspan="11" class="text-end text-dark">รวมทั้งหมด (ส่ง Claim แล้ว):</td>
                                    <td class="text-end font-monospace text-dark">{{ number_format($sum_income, 2) }}</td>
                                    <td class="text-end font-monospace text-dark">{{ number_format($sum_rcpt_money, 2) }}</td>
                                    <td class="text-end font-monospace text-primary">{{ number_format($sum_claim_price, 2) }}</td>
                                    <td class="text-end font-monospace text-success">{{ number_format($sum_receive_total, 2) }}</td>
                                    <td class="text-end font-monospace {{ ($sum_receive_total - $sum_claim_price) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($sum_receive_total - $sum_claim_price, 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>