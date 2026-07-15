<!-- Month Tabs Navigation -->
<div class="col-12 px-0 mt-3">
    <ul class="nav nav-tabs custom-ipd-tabs border-0 shadow-sm" id="monthTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-yearly" data-bs-toggle="tab" data-bs-target="#content-yearly" type="button" role="tab" aria-controls="content-yearly" aria-selected="true">
                <i class="bi bi-calendar-range-fill me-1"></i> รวมทั้งปี
            </button>
        </li>
        @foreach ($months_list as $m)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-{{ $m }}" data-bs-toggle="tab" data-bs-target="#content-{{ $m }}" type="button" role="tab" aria-controls="content-{{ $m }}" aria-selected="false">
                    {{ $months_names[$m] }}
                </button>
            </li>
        @endforeach
    </ul>
</div>

<!-- Tab Contents -->
<div class="tab-content col-12 px-0 mt-3" id="monthTabsContent">
    <!-- TAB: รวมทั้งปี -->
    <div class="tab-pane fade show active" id="content-yearly" role="tabpanel" aria-labelledby="tab-yearly">
        <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 4px solid #0d6efd !important; overflow: hidden;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-grid-3x3-gap-fill me-2"></i> รายได้ตามหมวดค่ารักษา OPD ยอดรวมทั้งปี</h6>
                <button onclick="exportTableToExcel('table-yearly', 'รายได้ OPD หมวดค่ารักษา ยอดรวมทั้งปี')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive w-100">
                    <table class="table table-hover align-middle mb-0 w-100" id="table-yearly" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>กลุ่มหมวดค่ารักษา</th>
                                <th>UCS</th>
                                <th>กรมบัญชีกลาง</th>
                                <th>อปท.</th>
                                <th>ต้นสังกัด</th>
                                <th>ประกันสังคม</th>
                                <th>ต่างด้าว</th>
                                <th>อื่นๆ</th>
                                <th>ยอดรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $sums = ['ucs'=>0, 'ofc'=>0, 'lgo'=>0, 'gov'=>0, 'sss'=>0, 'immigrant'=>0, 'others'=>0, 'total'=>0];
                            @endphp
                            @foreach($yearly_data as $row)
                            <tr>
                                <td class="fw-bold">{{ $row->group_id }}. {{ $row->group_name }}</td>
                                <td align="right">{{ number_format($row->ucs, 2) }}</td>
                                <td align="right">{{ number_format($row->ofc, 2) }}</td>
                                <td align="right">{{ number_format($row->lgo, 2) }}</td>
                                <td align="right">{{ number_format($row->gov, 2) }}</td>
                                <td align="right">{{ number_format($row->sss, 2) }}</td>
                                <td align="right">{{ number_format($row->immigrant, 2) }}</td>
                                <td align="right">{{ number_format($row->others, 2) }}</td>
                                <td align="right" class="fw-bold text-primary">{{ number_format($row->total, 2) }}</td>
                            </tr>
                            @php
                                $sums['ucs'] += $row->ucs; $sums['ofc'] += $row->ofc; $sums['lgo'] += $row->lgo; $sums['gov'] += $row->gov;
                                $sums['sss'] += $row->sss; $sums['immigrant'] += $row->immigrant; $sums['others'] += $row->others; $sums['total'] += $row->total;
                            @endphp
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold text-end">
                                <td align="center">รวมทั้งหมด</td>
                                <td>{{ number_format($sums['ucs'], 2) }}</td>
                                <td>{{ number_format($sums['ofc'], 2) }}</td>
                                <td>{{ number_format($sums['lgo'], 2) }}</td>
                                <td>{{ number_format($sums['gov'], 2) }}</td>
                                <td>{{ number_format($sums['sss'], 2) }}</td>
                                <td>{{ number_format($sums['immigrant'], 2) }}</td>
                                <td>{{ number_format($sums['others'], 2) }}</td>
                                <td class="text-primary">{{ number_format($sums['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: รายเดือนแต่ละเดือน -->
    @foreach ($months_list as $m)
    <div class="tab-pane fade" id="content-{{ $m }}" role="tabpanel" aria-labelledby="tab-{{ $m }}">
        <div class="card shadow-sm border-0" style="border-radius: 12px; border-top: 4px solid #198754 !important; overflow: hidden;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-3">
                <h6 class="mb-0 fw-bold text-success"><i class="bi bi-grid-3x3-gap-fill me-2"></i> รายได้ตามหมวดค่ารักษา OPD ประจำเดือน {{ $months_names[$m] }}</h6>
                <button onclick="exportTableToExcel('table-{{ $m }}', 'รายได้ OPD หมวดค่ารักษา เดือน {{ $months_names[$m] }}')" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive w-100">
                    <table class="table table-hover align-middle mb-0 w-100" id="table-{{ $m }}" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th>กลุ่มหมวดค่ารักษา</th>
                                <th>UCS</th>
                                <th>กรมบัญชีกลาง</th>
                                <th>อปท.</th>
                                <th>ต้นสังกัด</th>
                                <th>ประกันสังคม</th>
                                <th>ต่างด้าว</th>
                                <th>อื่นๆ</th>
                                <th>ยอดรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $m_sums = ['ucs'=>0, 'ofc'=>0, 'lgo'=>0, 'gov'=>0, 'sss'=>0, 'immigrant'=>0, 'others'=>0, 'total'=>0];
                            @endphp
                            @foreach($report_data[$m] as $row)
                            <tr>
                                <td class="fw-bold">{{ $row->group_id }}. {{ $row->group_name }}</td>
                                <td align="right">{{ number_format($row->ucs, 2) }}</td>
                                <td align="right">{{ number_format($row->ofc, 2) }}</td>
                                <td align="right">{{ number_format($row->lgo, 2) }}</td>
                                <td align="right">{{ number_format($row->gov, 2) }}</td>
                                <td align="right">{{ number_format($row->sss, 2) }}</td>
                                <td align="right">{{ number_format($row->immigrant, 2) }}</td>
                                <td align="right">{{ number_format($row->others, 2) }}</td>
                                <td align="right" class="fw-bold text-success">{{ number_format($row->total, 2) }}</td>
                            </tr>
                            @php
                                $m_sums['ucs'] += $row->ucs; $m_sums['ofc'] += $row->ofc; $m_sums['lgo'] += $row->lgo; $m_sums['gov'] += $row->gov;
                                $m_sums['sss'] += $row->sss; $m_sums['immigrant'] += $row->immigrant; $m_sums['others'] += $row->others; $m_sums['total'] += $row->total;
                            @endphp
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold text-end">
                                <td align="center">รวมทั้งหมด</td>
                                <td>{{ number_format($m_sums['ucs'], 2) }}</td>
                                <td>{{ number_format($m_sums['ofc'], 2) }}</td>
                                <td>{{ number_format($m_sums['lgo'], 2) }}</td>
                                <td>{{ number_format($m_sums['gov'], 2) }}</td>
                                <td>{{ number_format($m_sums['sss'], 2) }}</td>
                                <td>{{ number_format($m_sums['immigrant'], 2) }}</td>
                                <td>{{ number_format($m_sums['others'], 2) }}</td>
                                <td class="text-success">{{ number_format($m_sums['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
