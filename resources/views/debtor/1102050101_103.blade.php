@extends('layouts.app')
@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center flex-wrap">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_103') }}" class="m-0 d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="text" id="start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{ DateThai($start_date) }}" style="width: 120px;" readonly>
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                        <input type="text" id="end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{ DateThai($end_date) }}" style="width: 120px;" readonly>
                    </div>
                    <div class="input-group input-group-sm" style="min-width: 220px; flex: 1;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="ค้นหา ชื่อ-สกุล, HN">
                    </div>
                    <button onclick="showLoading()" type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                    <a href="{{ url('debtor/forget_search') }}" class="btn btn-warning btn-sm px-3 shadow-sm text-dark">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ต
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="debtor-tab" data-bs-toggle="pill" data-bs-target="#debtor-pane" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">รายการลูกหนี้</span>
                        <span id="badge-tab1" class="text-success fw-bold ms-2">{{ number_format($count_tab1) }}</span>
                    </button>
                </li>       
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="confirm-tab" data-bs-toggle="pill" data-bs-target="#confirm-pane" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i> <span>รอยืนยันลูกหนี้</span>
                        <span id="badge-tab2" class="text-warning fw-bold ms-2">
                             <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 
                    <form id="form-delete" action="{{ url('debtor/1102050101_103_delete') }}" method="POST">
                        @csrf   
                        @method('DELETE')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                    <i class="bi bi-trash-fill me-1"></i> ลบรายการลูกหนี้
                                </button>
                                <button type="button" class="btn btn-warning btn-sm px-3 shadow-sm text-dark fw-bold" onclick="bulkAdjust()">
                                    <i class="bi bi-tools me-1"></i> ปรับปรุงยอดเป็น 0
                                </button>
                            </div>                            <div>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="openAdjModal()">
                                     <i class="bi bi-journal-text me-1"></i> ประวัติปรับปรุง
                                </button>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_103_indiv_excel')}}" target="_blank">
                                     <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_103_daily_pdf')}}" target="_blank">
                                     <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                                </a> 
                            </div>

                        </div>
                        <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-primary align-middle">
                                <th colspan="8" class="text-center">1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th>
                                <th colspan="9" class="text-center">การชดเชย</th>
                            </tr>
                            <tr class="table-primary align-middle">
                                <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> ALL</th> 
                                <th class="text-center">วันที่</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                                <th class="text-center">ชำระเอง</th>                    
                                <th class="text-center">ลูกหนี้</th>
                                <th class="text-center">ชดเชย</th> 
                                <th class="text-center" style="color: #9c27b0;">ปรับเพิ่ม</th>
                                <th class="text-center" style="color: #673ab7;">ปรับลด</th>
                                <th class="text-center">ยอดคงเหลือ</th>
                                <th class="text-center">REP</th>                            
                                <th class="text-center">อายุหนี้</th>   
                                <th class="text-center text-primary" style="width: 55px; min-width: 55px; max-width: 55px;" title="แก้ไข"><i class="bi bi-pencil-square" style="font-size: 1.1rem; vertical-align: middle;"></i></th>
                                <th class="text-center text-primary" style="width: 55px; min-width: 55px; max-width: 55px;" title="ล็อค"><i class="bi bi-lock-fill" style="font-size: 1.1rem; vertical-align: middle;"></i></th>                                       
                            </tr>
                            </thead>
                            @php 
                                $s_inc=0; $s_rcp=0; $s_deb=0; $s_rec=0; $s_adj_inc=0; $s_adj_dec=0; $s_balance=0; 
                            @endphp
                            @foreach($debtor as $row) 
                            @php
                                $balance = ($row->receive + $row->adj_inc - $row->adj_dec) - $row->debtor;
                            @endphp
                            <tr class="align-middle">
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>   
                                <td align="center">{{ DateThai($row->vstdate) }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="center">{{ $row->pdx }}</td>                  
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                                <td align="right" style="color:{{$row->receive >= 0 ? 'green' : 'red'}}">{{ number_format($row->receive,2) }}</td>
                                <td align="right" style="color: #9c27b0;">{{ number_format($row->adj_inc ?? 0, 2) }}</td>
                                <td align="right" style="color: #673ab7;">{{ number_format($row->adj_dec ?? 0, 2) }}</td>
                                <td align="right" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">{{ number_format($balance,2) }}</td>         
                                <td align="center"><small>{{ $row->repno }}</small></td>
                                <td align="center" @if($row->days < 90) style="background-color: #90EE90;" @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" @else style="background-color: #FF7F7F;" @endif >
                                    {{ $row->days }} วัน
                                </td>      
                                <td align="center" style="width: 55px; min-width: 55px; max-width: 55px;">         
                                    <button type="button" class="btn btn-warning btn-sm px-2 shadow-sm text-dark btn-edit-debtor"
                                        data-vn="{{ $row->vn }}"
                                        data-ptname="{{ $row->ptname }}"
                                        data-balance="{{ number_format($balance,2) }}"
                                        data-balance-raw="{{ $balance }}"
                                        data-charge-date="{{ $row->charge_date }}"
                                        data-charge-date-th="{{ !empty($row->charge_date) ? DateThai($row->charge_date) : '' }}"
                                        data-charge-no="{{ $row->charge_no }}"
                                        data-charge="{{ $row->charge }}"
                                        data-status="{{ $row->status }}"
                                        data-receive-date="{{ $row->receive_date }}"
                                        data-receive-date-th="{{ !empty($row->receive_date) ? DateThai($row->receive_date) : '' }}"
                                        data-receive-no="{{ $row->receive_no }}"
                                        data-receive="{{ $row->receive_manual ?? 0 }}"
                                        data-repno="{{ $row->repno_manual ?? '' }}"
                                        data-adj-inc="{{ $row->adj_inc ?? 0 }}"
                                        data-adj-dec="{{ $row->adj_dec ?? 0 }}"
                                        data-adj-date="{{ $row->adj_date ?? date('Y-m-d') }}"
                                        data-adj-date-th="{{ !empty($row->adj_date) ? DateThai($row->adj_date) : DateThai(date('Y-m-d')) }}"
                                        data-adj-note="{{ $row->adj_note }}"
                                        data-update-url="{{ url('debtor/1102050101_103/update', $row->vn) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>                            
                                </td> 
                                <td align="center" data-order="{{ $row->debtor_lock == 'Y' ? 1 : 0 }}" style="width: 55px; min-width: 55px; max-width: 55px;">
                                    @if(Auth::user()->status == 'admin' || Auth::user()->allow_debtor_lock == 'Y')
                                        <button type="button" class="btn btn-sm btn-outline-{{$row->debtor_lock == 'Y' ? 'danger' : 'primary'}}" onclick="{{$row->debtor_lock == 'Y' ? 'confirmUnlock' : 'confirmLock'}}('{{ $row->vn }}')">
                                            <i class="bi bi-{{$row->debtor_lock == 'Y' ? 'unlock' : 'lock'}}"></i>
                                        </button>
                                    @else
                                        {{ $row->debtor_lock }}
                                    @endif
                                </td>                          
                            </tr>
                            @php 
                                $s_inc+=$row->income; $s_rcp+=$row->rcpt_money;
                                $s_deb+=$row->debtor; $s_rec+=$row->receive; 
                                $s_adj_inc+=$row->adj_inc; $s_adj_dec+=$row->adj_dec;
                                $s_balance+=$balance;
                            @endphp
                            @endforeach 
                            <tfoot>                        
                                <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                    <td class="text-end">รวม</td><td></td><td></td><td></td><td></td><td></td>
                                    <td>{{ number_format($s_inc,2) }}</td>
                                    <td>{{ number_format($s_rcp,2) }}</td>
                                    <td style="color:blue">{{ number_format($s_deb,2) }}</td>
                                    <td style="color:green">{{ number_format($s_rec,2) }}</td>
                                    <td style="color: #9c27b0;">{{ number_format($s_adj_inc,2) }}</td>
                                    <td style="color: #673ab7;">{{ number_format($s_adj_dec,2) }}</td>
                                    <td style="color:@if($s_balance < -0.01) red @elseif($s_balance > 0.01) green @else black @endif">{{ number_format($s_balance, 2) }}</td>
                                    <td></td><td></td><td></td><td></td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div>
                
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel"> 
                    <form id="form-confirm" action="{{ url('debtor/1102050101_103_confirm') }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                        </div>                
                        <div class="table-responsive">
                            <div id="loading-tab2" class="text-center p-5 d-none">
                                <div class="spinner-border text-warning" role="status"></div>
                                <p class="mt-2 text-muted">กำลังดึงข้อมูลจาก HOSxP...</p>
                                <p class="small text-danger">โปรดรอซักครู่</p>
                            </div>
                            <div id="empty-tab2" class="text-center p-5">
                                <i class="bi bi-search fs-1 text-muted"></i>
                                <p class="mt-2">คลิกที่ Tab หรือกดปุ่มค้นหาเพื่อโหลดข้อมูล</p>
                                <button type="button" class="btn btn-warning btn-sm" onclick="loadTab2()">โหลดข้อมูล HOSxP</button>
                            </div>
                            <table id="debtor_search_table" class="table table-bordered table-striped my-3 d-none" width="100%">
                                <thead>
                                    <tr class="table-secondary">
                                        <th class="text-left text-primary" colspan="9">
                                            1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} 
                                        </th>                         
                                    </tr>
                                    <tr class="table-secondary">
                                        <th class="text-center"><input type="checkbox" onClick="toggle(this)"> All</th> 
                                        <th class="text-center">วันที่</th>
                                        <th class="text-center">HN</th>
                                        <th class="text-center">ชื่อ-สกุล</th>
                                        <th class="text-center">สิทธิ</th>
                                        <th class="text-center">ICD10</th>
                                        <th class="text-center text-primary">ค่ารักษาทั้งหมด</th>  
                                        <th class="text-center text-primary">ชำระเอง</th>                    
                                        <th class="text-center text-primary">ลูกหนี้</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                        <td class="text-end">รวม</td><td></td><td></td><td></td><td></td><td></td>
                                        <td id="sum_income_search">0.00</td>
                                        <td id="sum_rcpt_money_search">0.00</td>
                                        <td id="sum_debtor_search" style="color:blue">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<style>
    /* Datepicker Thai Styling */
    .datepicker table tfoot tr th { 
        padding: 8px !important; 
        background: #f8f9fa !important; 
        color: #0d6efd !important; 
        cursor: pointer !important;
        font-weight: bold !important;
        border-top: 1px solid #dee2e6 !important;
        text-align: center !important;
    }
    .datepicker table tfoot tr th:hover { 
        background: #e9ecef !important; 
        text-decoration: underline !important;
    }
    .datepicker .datepicker-switch:hover, .datepicker .prev:hover, .datepicker .next:hover, .datepicker tfoot tr th:hover {
        background: #e9ecef !important;
    }
    .datepicker-dropdown {
        z-index: 1100 !important;
    }
</style>

<script>
    function showLoading() {
        Swal.fire({ title: 'กำลังโหลด...', text: 'กรุณารอสักครู่', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    }
    function fetchData() { showLoading(); }

    function confirmDelete() { 
        let selected = [];
        if ($.fn.DataTable.isDataTable('#debtor')) {
            let table = $('#debtor').DataTable();
            let cells = table.cells().nodes();
            $(cells).find('input[name="checkbox_d[]"]:checked').each(function() {
                selected.push($(this).val());
            });
        } else {
            selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);
        }

        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: `ต้องการลบลูกหนี้จำนวน ${selected.length} รายการที่เลือกใช่หรือไม่?`, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'ใช่, ลบเลย!', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const chunkSize = 100;
            const chunks = [];
            for (let i = 0; i < selected.length; i += chunkSize) {
                chunks.push(selected.slice(i, i + chunkSize));
            }
            
            let currentChunkIndex = 0;
            const total = selected.length;
            let totalDeleted = 0;
            let totalLocked = 0;
            
            Swal.fire({
                title: 'กำลังลบรายการลูกหนี้...',
                html: `
                    <div class="progress mb-2" style="height: 25px;">
                        <div id="delete-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="delete-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    sendNextDeleteChunk();
                }
            });
            
            function sendNextDeleteChunk() {
                if (currentChunkIndex >= chunks.length) {
                    let alertText = `ลบรายการลูกหนี้จำนวน ${totalDeleted} รายการเรียบร้อยแล้ว`;
                    if (totalLocked > 0) {
                        alertText += ` (ข้ามรายการที่ถูกล็อค ${totalLocked} รายการ)`;
                    }
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: alertText,
                        icon: totalLocked === total ? 'error' : (totalLocked > 0 ? 'warning' : 'success'),
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        location.reload();
                    });
                    return;
                }
                
                const chunk = chunks[currentChunkIndex];
                
                $.ajax({
                    url: "{{ url('debtor/1102050101_103_delete') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE',
                        checkbox_d: chunk
                    },
                    success: function(res) {
                        currentChunkIndex++;
                        totalDeleted += (res.deleted || 0);
                        totalLocked += (res.locked || 0);
                        
                        const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                        const percent = Math.round((processedCount / total) * 100);
                        
                        const progressBar = document.getElementById('delete-progress-bar');
                        const progressText = document.getElementById('delete-progress-text');
                        if (progressBar) {
                            progressBar.style.width = percent + '%';
                            progressBar.setAttribute('aria-valuenow', percent);
                            progressBar.innerText = percent + '%';
                        }
                        if (progressText) {
                            progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                        }
                        
                        sendNextDeleteChunk();
                    },
                    error: function(xhr) {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบลูกหนี้บางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
                    }
                });
            }
        } });
    }

    function confirmSubmit() {
        let selected = [];
        if ($.fn.DataTable.isDataTable('#debtor_search_table')) {
            let table = $('#debtor_search_table').DataTable();
            let cells = table.cells().nodes();
            $(cells).find('input[name="checkbox[]"]:checked').each(function() {
                selected.push($(this).val());
            });
        } else if ($.fn.DataTable.isDataTable('#debtor_search')) {
            let table = $('#debtor_search').DataTable();
            let cells = table.cells().nodes();
            $(cells).find('input[name="checkbox[]"]:checked').each(function() {
                selected.push($(this).val());
            });
        } else {
            selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);
        }

        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: `ต้องการยืนยันลูกหนี้จำนวน ${selected.length} รายการที่เลือกใช่หรือไม่?`, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const chunkSize = 10;
            const chunks = [];
            for (let i = 0; i < selected.length; i += chunkSize) {
                chunks.push(selected.slice(i, i + chunkSize));
            }
            
            let currentChunkIndex = 0;
            const total = selected.length;
            
            Swal.fire({
                title: 'กำลังยืนยันลูกหนี้...',
                html: `
                    <div class="progress mb-2" style="height: 25px;">
                        <div id="confirm-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    <div id="confirm-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    sendNextChunk();
                }
            });
            
            function sendNextChunk() {
                if (currentChunkIndex >= chunks.length) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: `ยืนยันลูกหนี้จำนวน ${total} เรียบร้อยแล้ว`,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        location.reload();
                    });
                    return;
                }
                
                const chunk = chunks[currentChunkIndex];
                
                $.ajax({
                    url: "{{ url('debtor/1102050101_103_confirm') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        checkbox: chunk
                    },
                    success: function(res) {
                        currentChunkIndex++;
                        const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                        const percent = Math.round((processedCount / total) * 100);
                        
                        const progressBar = document.getElementById('confirm-progress-bar');
                        const progressText = document.getElementById('confirm-progress-text');
                        if (progressBar) {
                            progressBar.style.width = percent + '%';
                            progressBar.setAttribute('aria-valuenow', percent);
                            progressBar.innerText = percent + '%';
                        }
                        if (progressText) {
                            progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                        }
                        
                        sendNextChunk();
                    },
                    error: function(xhr) {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถยืนยันลูกหนี้บางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
                    }
                });
            }
        } });
    }

    function toggle_ae(source) {
        checkboxes = document.getElementsByName('checkbox_ae[]');
        for (var i = 0; i < checkboxes.length; i++) { checkboxes[i].checked = source.checked; }
    }

    function confirmSubmit_ae() {
        const selected = [...document.querySelectorAll('input[name="checkbox_ae[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.querySelector('form[action*="confirm_ae"]');
            if(f) f.submit(); 
        } });
    }

    function confirmLock(id) {
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการ Lock รายการนี้ใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050101_103/lock') }}/" + id;
                f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'_token', value:'{{ csrf_token() }}'}));
                document.body.appendChild(f); f.submit();
            }
        });
    }

    function confirmUnlock(id) {
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการ Unlock รายการนี้ใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050101_103/unlock') }}/" + id;
                f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'_token', value:'{{ csrf_token() }}'}));
                document.body.appendChild(f); f.submit();
            }
        });
    }

    function bulkAdjust() {
        const sel = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e=>e.value);
        if(!sel.length) { Swal.fire('แจ้งเตือน','กรุณาเลือกรายการ','warning'); return; }
        Swal.fire({
            title: 'ปรับปรุงยอดเป็น 0',
            html: `
                <div class="text-center mb-3" style="font-size: 16px; color: #6c757d;">จำนวน ${sel.length} รายการ</div>
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">หมายเหตุการปรับปรุง</label>
                        <input type="text" id="blk_note" class="form-control rounded-pill" value="ปรับปรุงยอดเป็น 0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">วันที่ปรับปรุง</label>
                        <input type="text" id="blk_date_th" class="form-control rounded-pill datepicker_th" value="{{DateThai(date('Y-m-d'))}}" readonly>
                        <input type="hidden" id="blk_date" value="{{date('Y-m-d')}}">
                    </div>
                </div>
            `,
            icon: 'info', showCancelButton: true, confirmButtonColor: '#ffc107', confirmButtonText: 'ยืนยัน',
            didOpen: () => { $('#blk_date_th').datepicker({ format: 'd M yyyy', autoclose: true, language: 'th-th', thaiyear: true, todayBtn: 'linked', todayHighlight: true }).on('changeDate', (e) => { if (e.date) { const y = e.date.getFullYear(), m=('0'+(e.date.getMonth()+1)).slice(-2), d=('0'+e.date.getDate()).slice(-2); $('#blk_date').val(y+'-'+m+'-'+d); } }); },
            preConfirm: () => { return { note: $('#blk_note').val(), date: $('#blk_date').val() } }
        }).then((r) => {
            if (r.isConfirmed) {
                const chunkSize = 100;
                const chunks = [];
                for (let i = 0; i < sel.length; i += chunkSize) {
                    chunks.push(sel.slice(i, i + chunkSize));
                }

                let currentChunkIndex = 0;
                const total = sel.length;
                let totalAdjusted = 0;

                Swal.fire({
                    title: 'กำลังปรับปรุงยอดเป็น 0...',
                    html: `
                        <div class="progress mb-2" style="height: 25px;">
                            <div id="adj-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning text-dark fw-bold" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                        <div id="adj-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { sendNextAdjChunk(); }
                });

                function sendNextAdjChunk() {
                    if (currentChunkIndex >= chunks.length) {
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: `ปรับปรุงยอดจำนวน ${totalAdjusted} รายการเรียบร้อยแล้ว`,
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        }).then(() => { location.reload(); });
                        return;
                    }

                    const chunk = chunks[currentChunkIndex];

                    $.ajax({
                        url: "{{ url('debtor/1102050101_103_bulk_adj') }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            checkbox_d: chunk,
                            bulk_adj_note: r.value.note,
                            bulk_adj_date: r.value.date
                        },
                        success: function(res) {
                            currentChunkIndex++;
                            totalAdjusted += (res.adjusted_count || 0);

                            const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                            const percent = Math.round((processedCount / total) * 100);

                            const progressBar = document.getElementById('adj-progress-bar');
                            const progressText = document.getElementById('adj-progress-text');
                            if (progressBar) {
                                progressBar.style.width = percent + '%';
                                progressBar.innerText = percent + '%';
                            }
                            if (progressText) {
                                progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                            }

                            sendNextAdjChunk();
                        },
                        error: function() {
                            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปรับปรุงยอดได้สำเร็จ', 'error');
                        }
                    });
                }
            }
        });
    }
</script>
    
<!-- History Adjustment Modal -->
<div id="adjLogModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-dark border-0 py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center"><i class="bi bi-journal-text me-2"></i> ประวัติการปรับปรุงยอดลูกหนี้ 1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Filter Section inside Modal -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-5 d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่ปรับยอด</span>
                        <input type="text" id="adj_start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{DateThai(date('Y-m-01'))}}" readonly>
                        <input type="hidden" id="adj_start_date" value="{{date('Y-m-01')}}">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="text" id="adj_end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{DateThai(date('Y-m-t'))}}" readonly>
                        <input type="hidden" id="adj_end_date" value="{{date('Y-m-t')}}">
                    </div>
                    <div class="col-md-7 d-flex gap-2">
                        <button type="button" class="btn btn-info text-dark fw-bold px-3 shadow-sm" onclick="loadAdjLogs()">
                            <i class="bi bi-search me-1"></i> ค้นหา
                        </button>
                        <a id="btn-adj-print-pdf" class="btn btn-danger fw-bold px-3 shadow-sm" href="#" target="_blank">
                            <i class="bi bi-file-pdf me-1"></i> พิมพ์ใบแนบปรับปรุง (PDF)
                        </a>
                    </div>
                </div>

                <!-- Table Section inside Modal -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="adj_logs_table" width="100%">
                        <thead>
                            <tr class="table-info align-middle text-center" style="font-size: 13px;">
                                <th>ลำดับ</th>
                                <th>วันที่ปรับปรุง</th>
                                <th>วันที่บริการ</th>
                                <th>HN</th>
                                <th>ชื่อ-สกุล</th>
                                <th>ยอดลูกหนี้</th>
                                <th>ยอดชดเชย</th>
                                <th>ปรับเพิ่ม</th>
                                <th>ปรับลด</th>
                                <th>เหตุผลการปรับปรุง</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <!-- Dynamically loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAdjModal() {
        $('#adjLogModal').modal('show');
        // Init PDF print URL
        updateAdjPdfUrl();
        loadAdjLogs();
    }

    function updateAdjPdfUrl() {
        const start = $('#adj_start_date').val();
        const end = $('#adj_end_date').val();
        const url = `{{ url('debtor/adjust_log/1102050101_103') }}?start_date=${start}&end_date=${end}&export_type=pdf`;
        $('#btn-adj-print-pdf').attr('href', url);
    }

    function loadAdjLogs() {
        const start = $('#adj_start_date').val();
        const end = $('#adj_end_date').val();
        
        if ($.fn.DataTable.isDataTable('#adj_logs_table')) {
            $('#adj_logs_table').DataTable().destroy();
        }
        
        $('#adj_logs_table tbody').html(`
            <tr>
                <td colspan="10" class="text-center p-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="text-muted small mt-2">กำลังดึงข้อมูลประวัติการปรับปรุงยอด...</div>
                </td>
            </tr>
        `);

        $.ajax({
            url: `{{ url('debtor/adjust_log/1102050101_103') }}`,
            type: 'GET',
            data: {
                start_date: start,
                end_date: end,
                export_type: 'json'
            },
            success: function(res) {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    res.data.forEach((row, index) => {
                        html += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td class="text-center">${formatThaiDate(row.adj_date)}</td>
                                <td class="text-center">${formatThaiDate(row.vstdate)}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="text-start">${row.ptname}</td>
                                <td class="text-end">${parseFloat(row.debtor || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end">${parseFloat(row.receive || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end text-purple fw-bold" style="color: purple;">${parseFloat(row.adj_inc || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end text-primary fw-bold" style="color: blue;">${parseFloat(row.adj_dec || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-start">${row.adj_note || ''}</td>
                            </tr>
                        `;
                    });
                    $('#adj_logs_table tbody').html(html);
                    
                    $('#adj_logs_table').DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            infoEmpty: "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
                            zeroRecords: "ไม่พบข้อมูลที่ตรงกัน",
                            paginate: {
                                first: "หน้าแรก",
                                last: "หน้าสุดท้าย",
                                next: "ถัดไป",
                                previous: "ก่อนหน้า"
                            }
                        },
                        columnDefs: [
                            { orderable: false, targets: 0 }
                        ]
                    });
                } else {
                    $('#adj_logs_table tbody').html(`
                        <tr>
                            <td colspan="10" class="text-center p-4 text-muted">ไม่พบข้อมูลการปรับปรุงยอดในช่วงวันที่ระบุ</td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#adj_logs_table tbody').html(`
                    <tr>
                        <td colspan="10" class="text-center p-4 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td>
                    </tr>
                `);
            }
        });
    }

    function formatThaiDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear() + 543}`;
    }

    $(document).ready(function() {
        $('#adj_start_date_picker').datepicker({
            format: 'd M yyyy',
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            todayBtn: 'linked',
            todayHighlight: true
        }).on('changeDate', function(e) {
            if (e.date) {
                const y = e.date.getFullYear(), m = ('0' + (e.date.getMonth() + 1)).slice(-2), d = ('0' + e.date.getDate()).slice(-2);
                $('#adj_start_date').val(y + '-' + m + '-' + d);
                updateAdjPdfUrl();
            }
        });

        $('#adj_end_date_picker').datepicker({
            format: 'd M yyyy',
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            todayBtn: 'linked',
            todayHighlight: true
        }).on('changeDate', function(e) {
            if (e.date) {
                const y = e.date.getFullYear(), m = ('0' + (e.date.getMonth() + 1)).slice(-2), d = ('0' + e.date.getDate()).slice(-2);
                $('#adj_end_date').val(y + '-' + m + '-' + d);
                updateAdjPdfUrl();
            }
        });
    });
</script>
@endpush

<!-- Single Debtor Modal -->
<div id="debtorModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title d-flex align-items-center"><i class="bi bi-cash-stack me-2"></i> รายการการชดเชยเงิน/ลูกหนี้ (VN: <span id="modal_vn"></span>)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="debtorModalForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-3 text-start">
                    <div class="row g-2">
                        <div class="col-md-12">
                            <div class="p-2 rounded-3 bg-primary-soft mb-1">
                                <div class="row align-items-center">
                                    <div class="col-md-7"><label class="text-muted small d-block">ชื่อ-สกุล</label><span id="modal_ptname" class="fw-bold text-primary fs-6"></span></div>
                                    <div class="col-md-5 text-md-end"><label class="text-muted small d-block">ส่วนต่างลูกหนี้คงเหลือ</label><span id="modal_balance" class="fw-bold fs-6"></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 border-end">
                            <h6 class="text-secondary fw-bold mb-2 d-flex align-items-center small"><i class="bi bi-send-fill me-2 text-primary"></i> ข้อมูลการส่งเบิก (Charge)</h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">วันที่เรียกเก็บ</label>
                                    <input type="hidden" name="charge_date" id="modal_charge_date">
                                    <input type="text" id="modal_charge_date_picker" class="form-control form-control-sm rounded-pill px-3 datepicker_th" placeholder="วว/ดด/ปปปป" readonly>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">เลขที่หนังสือ</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" name="charge_no" id="modal_charge_no" placeholder="ระบุเลขที่หนังสือ">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">จำนวนเงินเรียกเก็บ</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="charge" id="modal_charge">
                                        <span class="input-group-text rounded-pill-end small bg-light">บาท</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">สถานะลูกหนี้</label>
                                    <select class="form-select form-select-sm rounded-pill px-3" name="status" id="modal_status">
                                        <option value="ยืนยันลูกหนี้">ยืนยันลูกหนี้</option>
                                        <option value="อยู่ระหว่างเรียกเก็บ">อยู่ระหว่างเรียกเก็บ</option>
                                        <option value="อยู่ระหว่างการขออุทธรณ์">อยู่ระหว่างการขออุทธรณ์</option>
                                        <option value="กระทบยอดแล้ว">กระทบยอดแล้ว</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-secondary fw-bold mb-2 d-flex align-items-center small"><i class="bi bi-wallet2 me-2 text-success"></i> ข้อมูลการชดเชย (Receive)</h6>
                            <div class="row g-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">วันที่ชดเชย</label>
                                    <input type="hidden" name="receive_date" id="modal_receive_date">
                                    <input type="text" id="modal_receive_date_picker" class="form-control form-control-sm rounded-pill px-3 datepicker_th" placeholder="วว/ดด/ปปปป" readonly>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label mb-1 small fw-bold">เลขที่หนังสือชดเชย</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" name="receive_no" id="modal_receive_no" placeholder="ระบุเลขที่โอน">
                                </div>
                                <div class="col-md-6 mb-0">
                                    <label class="form-label mb-1 small fw-bold">จำนวนเงินที่ได้รับ</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="receive" id="modal_receive">
                                        <span class="input-group-text rounded-pill-end small bg-light">บาท</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-0">
                                    <label class="form-label mb-1 small fw-bold">เลขที่ใบเสร็จ</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" name="repno" id="modal_repno" placeholder="ระบุเลขที่ใบเสร็จ">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <hr class="my-2">
                            <h6 class="fw-bold mb-2 d-flex align-items-center small" style="color:#ffc107">
                                <i class="bi bi-tools me-2"></i> ปรับปรุงยอดรายคน (Adjustment)
                            </h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small fw-bold text-success">ปรับเพิ่ม (+)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm rounded-pill px-3" name="adj_inc" id="modal_adj_inc">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small fw-bold text-danger">ปรับลด (-)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm rounded-pill px-3" name="adj_dec" id="modal_adj_dec">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small fw-bold text-muted">วันที่ปรับปรุง</label>
                                    <input type="hidden" name="adj_date" id="modal_adj_date">
                                    <input type="text" id="modal_adj_date_picker" class="form-control form-control-sm rounded-pill px-3 datepicker_th" placeholder="วว/ดด/ปปปป" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label mb-1 small fw-bold text-muted">หมายเหตุ</label>
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3" name="adj_note" id="modal_adj_note" placeholder="ระบุเหตุผล">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" onclick="showLoading()">
                        <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
window.toggle_d = function(source) {
    if ($.fn.DataTable.isDataTable('#debtor')) {
        let table = $('#debtor').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox_d[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox_d[]"]').prop('checked', source.checked);
    }
};

window.toggle = function(source) {
    if ($.fn.DataTable.isDataTable('#debtor_search_table')) {
        let table = $('#debtor_search_table').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox[]"]').prop('checked', source.checked);
    } else if ($.fn.DataTable.isDataTable('#debtor_search')) {
        let table = $('#debtor_search').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox[]"]').prop('checked', source.checked);
    }
};

$(document).ready(function() {
    // 1. Initialize Datepicker Thai for Filter with Today button
    // language: 'th-th' and thaiyear: true work together for BE year display.
    $('#start_date_picker, #end_date_picker').datepicker({
        format: 'd M yyyy',
        todayBtn: 'linked',
        todayHighlight: true,
        autoclose: true,
        language: 'th-th',
        thaiyear: true,
        zIndexOffset: 1050
    }).on('changeDate', function(e) {
        if (e.date) {
            var y = e.date.getFullYear();
            var m = ('0'+(e.date.getMonth()+1)).slice(-2);
            var d = ('0'+e.date.getDate()).slice(-2);
            var dateStr = y + '-' + m + '-' + d;
            if (this.id == 'start_date_picker') { $('#start_date').val(dateStr); }
            if (this.id == 'end_date_picker') { $('#end_date').val(dateStr); }
        }
    });

    // Initialize other datepickers (if any)
    $('.datepicker_th').not('#start_date_picker, #end_date_picker, [id^="modal_"]').datepicker({
        format: 'd M yyyy',
        autoclose: true,
        language: 'th-th',
        thaiyear: true,
        todayBtn: 'linked',
        todayHighlight: true
    });

    // 2. Set initial values
    var start_date_val = "{{ $start_date }}";
    var end_date_val = "{{ $end_date }}";
    
    function setInitialDate(pickerId, dateStr) {
        if(dateStr && dateStr !== '0000-00-00') {
            var parts = dateStr.split('-');
            if(parts.length === 3) {
                var d = new Date(parts[0], parts[1]-1, parts[2]);
                // Clear existing value to prevent 'แวบ ๆ' (briefly showing BE then AD)
                $(pickerId).val(''); 
                $(pickerId).datepicker('setDate', d);
            }
        }
    }
    setInitialDate('#start_date_picker', start_date_val);
    setInitialDate('#end_date_picker', end_date_val);

    // 3. DataTable for main table
    if ($('#debtor').length) {
        $('#debtor').DataTable({
            lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
            dom: '<"row mb-3"<"col-md-6"l>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            ordering: true,
            language: {
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
            }
        });
    }

    // 4. DataTable for search/confirm table
    if ($('#debtor_search').length) {
        $('#debtor_search').DataTable({
            lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: '1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
                }
            ],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
            }
        });
    }

    // 5. DataTable for AE table
    if ($('#debtor_search_ae').length) {
        $('#debtor_search_ae').DataTable({
            lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: '1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ รอยืนยัน AE/OP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
                }
            ],
            language: {
                search: 'ค้นหา:',
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
            }
        });
    }

    // Auto-load background
    loadTab2();
});

function loadTab2() {
    $('#badge-tab2').html('<span class="spinner-border spinner-border-sm" role="status"></span>');
    $('#empty-tab2').addClass('d-none');
    $('#loading-tab2').removeClass('d-none');
    
    $.ajax({
        url: "{{ url('debtor/1102050101_103_search_ajax') }}",
        data: { start_date: $('#start_date').val(), end_date: $('#end_date').val() },
        success: function(res) {
            $('#loading-tab2').addClass('d-none');
            $('#debtor_search_table').removeClass('d-none');
            $('#badge-tab2').text(res.length).removeClass('badge bg-warning text-white').addClass('text-warning fw-bold');
            
            let html = '';
            let s_inc = 0, s_rcpt = 0, s_debt = 0;
            res.forEach(r => {
                let d = parseFloat(r.debtor) || 0;
                s_inc += parseFloat(r.income) || 0;
                s_rcpt += parseFloat(r.rcpt_money) || 0;
                s_debt += d;

                html += `<tr>
                    <td class="text-center"><input type="checkbox" name="checkbox[]" value="${r.vn}"></td>
                    <td align="center">${thaiDate(r.vstdate)} ${r.vsttime || ''}</td>
                    <td align="center">${r.hn}</td>
                    <td align="left">${r.ptname}</td>
                    <td align="left">${r.pttype}</td>
                    <td align="center">${r.pdx || ''}</td>
                    <td align="right">${formatMoney(r.income)}</td>
                    <td align="right">${formatMoney(r.rcpt_money)}</td>
                    <td align="right" class="text-primary">${formatMoney(d)}</td>
                </tr>`;
            });
            $('#debtor_search_table tbody').html(html);
            $('#sum_income_search').text(formatMoney(s_inc));
            $('#sum_rcpt_money_search').text(formatMoney(s_rcpt));
            $('#sum_debtor_search').text(formatMoney(s_debt));

            if ($.fn.DataTable.isDataTable('#debtor_search_table')) {
                $('#debtor_search_table').DataTable().destroy();
            }
            $('#debtor_search_table').DataTable({
                lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
                dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm' }],
                language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
            });
        }
    });
}

function thaiDate(dateStr) {
    if(!dateStr || dateStr === '0000-00-00') return '';
    const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    const d = new Date(dateStr);
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + (d.getFullYear() + 543).toString().substr(-2);
}

function formatMoney(num) {
    return parseFloat(num).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<script id="single-modal-js">
$(document).ready(function () {
    console.log('Debtor Single Modal System Initialized');
    
    function initModalDatepicker(pickerId, hiddenId) {
        var $picker = $('#' + pickerId);
        if ($picker.length) {
            $picker.datepicker({
                format: 'd M yyyy', 
                autoclose: true, 
                language: 'th-th', 
                thaiyear: true, 
                todayBtn: 'linked',
                todayHighlight: true,
                zIndexOffset: 1060
            }).on('changeDate', function(e) {
                if (e.date) {
                    var d = e.date, y = d.getFullYear(), m = ('0'+(d.getMonth()+1)).slice(-2), day = ('0'+d.getDate()).slice(-2);
                    $(hiddenId).val(y + '-' + m + '-' + day);
                }
            });
        }
    }
    initModalDatepicker('modal_charge_date_picker', '#modal_charge_date');
    initModalDatepicker('modal_receive_date_picker', '#modal_receive_date');
    initModalDatepicker('modal_adj_date_picker', '#modal_adj_date');

    $(document).on('click', '.btn-edit-debtor', function() {
        var d = $(this).data();
        var updateUrl = $(this).attr('data-update-url');
        
        $('#debtorModalForm').attr('action', updateUrl);
        $('#modal_vn').text(d.vn);
        $('#modal_ptname').text(d.ptname);
        
        var balEl = $('#modal_balance');
        balEl.text(d.balance + ' บาท');
        var raw = parseFloat(d.balanceRaw);
        balEl.css('color', raw < -0.01 ? 'red' : (raw > 0.01 ? 'green' : 'black'));
        
        function setPickerDate(pickerId, hiddenId, dateStr) {
            $(hiddenId).val(dateStr || '');
            if(dateStr && dateStr !== '0000-00-00') {
                var p = dateStr.split('-');
                if(p.length === 3) {
                    // Force refresh by clearing first
                    $(pickerId).val('');
                    $(pickerId).datepicker('setDate', new Date(p[0], p[1]-1, p[2]));
                }
            } else {
                $(pickerId).val(''); 
            }
        }

        setPickerDate('#modal_charge_date_picker', '#modal_charge_date', d.chargeDate);
        $('#modal_charge_no').val(d.chargeNo || '');
        $('#modal_charge').val(d.charge || '');
        $('#modal_status').val(d.status || 'ยืนยันลูกหนี้');
        
        setPickerDate('#modal_receive_date_picker', '#modal_receive_date', d.receiveDate);
        $('#modal_receive_no').val(d.receiveNo || '');
        $('#modal_receive').val(d.receive || '');
        $('#modal_repno').val(d.repno || '');
        
        $('#modal_adj_inc').val(d.adjInc || 0);
        $('#modal_adj_dec').val(d.adjDec || 0);
        setPickerDate('#modal_adj_date_picker', '#modal_adj_date', d.adjDate);
        $('#modal_adj_note').val(d.adjNote || '');
        
        var $modal = $('#debtorModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                var myModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                myModal.show();
            } catch(e) {
                if (typeof $modal.modal === 'function') { $modal.modal('show'); }
            }
        } else if (typeof $.fn.modal === 'function') {
            $modal.modal('show');
        }
    });
});
</script>
@endpush
