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
                    <i class="bi bi-people-fill text-primary me-2"></i>บริการแพทย์แผนไทย นวด,อบ,ประคบ และการดูแลมารดาหลังคลอด
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
                        <a href="{{ url('mishos/ucs_healthmed_procedure/export') }}?budget_year={{ $budget_year }}&start_date={{ $start_date }}&end_date={{ $end_date }}" class="btn btn-primary px-3 shadow-sm ms-2" target="_blank" title="ดาวน์โหลดไฟล์ Excel แยก 7 แท็บชีต">
                            <i class="bi bi-file-earmark-excel me-1"></i> Excel รวมทุกแท็บ
                        </a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $tabs = [
                [
                    'id' => 'postpartum',
                    'title' => 'ดูแลมารดาหลังคลอด',
                    'icon' => 'bi-heart-fill',
                    'color_style' => 'background-color: #ec4899; color: white;',
                    'data' => $postpartum_list
                ],
                [
                    'id' => 'compress',
                    'title' => 'ประคบ',
                    'icon' => 'bi-flower1',
                    'color_style' => 'background-color: #f97316; color: white;',
                    'data' => $compress_list
                ],
                [
                    'id' => 'massage',
                    'title' => 'นวด',
                    'icon' => 'bi-hand-thumbs-up',
                    'color_style' => 'background-color: #3b82f6; color: white;',
                    'data' => $massage_list
                ],
                [
                    'id' => 'massage_and_compress',
                    'title' => 'นวดและประคบ',
                    'icon' => 'bi-plus-circle',
                    'color_style' => 'background-color: #06b6d4; color: white;',
                    'data' => $massage_and_compress_list
                ],
                [
                    'id' => 'poultice',
                    'title' => 'พอกเข่า',
                    'icon' => 'bi-capsule',
                    'color_style' => 'background-color: #10b981; color: white;',
                    'data' => $poultice_list
                ],
                [
                    'id' => 'steam',
                    'title' => 'อบสมุนไพร',
                    'icon' => 'bi-wind',
                    'color_style' => 'background-color: #eab308; color: black;',
                    'data' => $steam_list
                ],
                [
                    'id' => 'herbs',
                    'title' => 'การใช้ยาจากสมุนไพร',
                    'icon' => 'bi-droplet',
                    'color_style' => 'background-color: #6b7280; color: white;',
                    'data' => $herbs_list
                ]
            ];
        @endphp

        <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
            @foreach($tabs as $index => $tab)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="{{ $tab['id'] }}-tab" data-bs-toggle="pill" data-bs-target="#{{ $tab['id'] }}" type="button" role="tab">
                    <i class="bi {{ $tab['icon'] }} me-1"></i> {{ $tab['title'] }}
                    <span class="badge rounded-pill ms-1" style="{{ $tab['color_style'] }}">{{ count($tab['data']) }}</span>
                </button>
            </li>
            @endforeach
        </ul>
    </div>
    <div class="card-body px-4 pb-4 pt-0">
        <div class="tab-content" id="myTabContent">
            @foreach($tabs as $index => $tab)
            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $tab['id'] }}" role="tabpanel">
                <div class="table-responsive">            
                    <table id="t_{{ $tab['id'] }}" class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">ตรวจสอบ</th>
                                <th class="text-center" width="8%">วันที่รับบริการ</th>
                                <th class="text-center">Queue</th>
                                <th class="text-center">HN</th>
                                <th class="text-start" width="12%">ชื่อ-สกุล</th>
                                <th class="text-start" width="15%">สิทธิการรักษา</th>
                                <th class="text-start" width="20%">หัตถการ</th>
                                <th class="text-start" width="20%">รายการเรียกเก็บ</th>
                                <th class="text-end" width="10%">ยอดเรียกเก็บ</th>
                            </tr>
                        </thead> 
                        <tbody> 
                            @php 
                                $count = 1; 
                            @endphp
                            @foreach($tab['data'] as $row) 
                            <tr>
                                <td class="text-center text-muted small">{{ $count }}</td>
                                <td class="text-center" id="td-status-{{ $tab['id'] }}-{{ $row->seq }}" data-order="{{ $row->endpoint_valid ? 1 : 0 }}">
                                    @if($row->endpoint_valid)
                                        <button class="btn btn-sm btn-outline-success px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ผ่านเงื่อนไข + ปิดสิทธิแล้ว | ดูรายละเอียด">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-warning px-2 py-1 border-2 d-flex align-items-center justify-content-center" style="font-size:0.7rem; height: 26px; min-height: 26px; margin: 0 auto;" onclick="showDetails('{{ $row->seq }}')" title="ยังไม่ปิดสิทธิ | คลิกดูรายละเอียด">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
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
                                <td class="text-start small text-muted">{{ $row->claim_list }}</td>
                                <td class="text-start small text-muted">{{ $row->claim_billing_list }}</td>
                                <td class="text-end small fw-bold text-primary">{{ number_format($row->claim_billing_price, 2) }}</td>
                            </tr>
                            @php 
                                $count++; 
                            @endphp
                            @endforeach                 
                        </tbody>
                        <tfoot>
                            <tr class="bg-light-soft">
                                <th colspan="9" class="text-end small text-muted px-3">ยอดเรียกเก็บรวม:</th>
                                <th class="text-end small fw-bold text-primary">
                                    @php
                                        $sum_billing_price = array_sum(array_column($tab['data'], 'claim_billing_price'));
                                    @endphp
                                    {{ number_format($sum_billing_price, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>