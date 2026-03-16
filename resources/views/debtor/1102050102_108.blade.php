@extends('layouts.app')
    <script>
        function toggle_d(source) {
            checkbox = document.getElementsByName('checkbox_d[]');
            for (var i = 0; i < checkbox.length; i++) {
                checkbox[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle(source) {
            checkboxes = document.getElementsByName('checkbox[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>    
@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center flex-wrap">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050102_108') }}" enctype="multipart/form-data" class="m-0 d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="text" id="start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{ DateThai($start_date) }}" style="width: 120px;" placeholder="วว/ดด/ปปปป" readonly>
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                        <input type="text" id="end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{ DateThai($end_date) }}" style="width: 120px;" placeholder="วว/ดด/ปปปป" readonly>
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="min-width: 220px; flex: 1;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="ค้นหา ชื่อ-สกุล,HN">
                    </div>

                    <button onclick="fetchData()" type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                    <a href="{{ url('debtor/forget_search') }}" class="btn btn-warning btn-sm px-3 shadow-sm text-dark">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ต
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        
        <!-- Section: Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="debtor-tab" data-bs-toggle="pill" data-bs-target="#debtor-pane" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">รายการลูกหนี้</span>
                        <span class="badge bg-primary-soft text-primary ms-2">{{ count($debtor) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="confirm-tab" data-bs-toggle="pill" data-bs-target="#confirm-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้
                        <span class="badge bg-warning-soft text-warning ms-2">{{ count($debtor_search) }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: รายการลูกหนี้ -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 
                    <form id="form-delete" action="{{ url('debtor/1102050102_108_delete') }}" method="POST" enctype="multipart/form-data">
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
                            </div>
                            <div>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050102_108_indiv_excel')}}" target="_blank">
                                    <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_108_daily_pdf')}}" target="_blank">
                                    <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                                </a> 
                            </div>
                        </div>

                        <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-success">
                                <th class="text-left text-primary" colspan = "9">1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                                <th class="text-center text-primary" colspan = "10">การชดเชย</th>                                                 
                            </tr>
                            <tr class="table-success">
                                <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th> 
                                <th class="text-center" width="6%" >วันที่</th>
                                <th class="text-center">HN</th> 
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th> 
                                <th class="text-center">ชำระเอง</th> 
                                <th class="text-center">กองทุนอื่น</th> 
                                <th class="text-center text-primary">ลูกหนี้</th>
                                <th class="text-center text-primary">ชดเชย</th> 
                                <th class="text-center" style="color: #9c27b0;">ปรับเพิ่ม</th>
                                <th class="text-center" style="color: #673ab7;">ปรับลด</th>
                                <th class="text-center text-primary">ยอดคงเหลือ</th>
                                <th class="text-center text-primary">REP</th>
                                <th class="text-center text-primary">อายุหนี้</th> 
                                <th class="text-center text-primary" width="6%">Action</th>    
                                <th class="text-center text-primary">Lock</th>          
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>
                            <?php $sum_other = 0 ; ?>              
                            <?php $sum_debtor = 0 ; ?>
                            <?php $sum_receive = 0 ; ?>
                            <?php $s_adj_inc = 0; $s_adj_dec = 0; $s_balance = 0; ?>
                            @foreach($debtor as $row) 
                            @php 
                                $balance = ($row->receive + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - $row->debtor;
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>                    
                                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                <td align="right">{{ $row->pdx }}</td>                      
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->other,2) }}</td>
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                                <td align="right" @if($row->receive > 0) style="color:green" 
                                    @elseif($row->receive < 0) style="color:red" @endif>
                                    {{ number_format($row->receive,2) }}
                                </td>
                                <td align="right" style="color: #9c27b0;">{{ number_format($row->adj_inc ?? 0, 2) }}</td>
                                <td align="right" style="color: #673ab7;">{{ number_format($row->adj_dec ?? 0, 2) }}</td>
                                <td align="right" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">{{ number_format($balance, 2) }}</td>
                                <td align="right">{{ $row->repno ?? '' }}</td>
                                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                                    {{ $row->days }} วัน
                                </td>   
                                <td align="center">         
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
                                        data-repno="{{ $row->repno }}"
                                        data-adj-inc="{{ $row->adj_inc ?? 0 }}"
                                        data-adj-dec="{{ $row->adj_dec ?? 0 }}"
                                        data-adj-date="{{ $row->adj_date ?? date('Y-m-d') }}"
                                        data-adj-date-th="{{ !empty($row->adj_date) ? DateThai($row->adj_date) : DateThai(date('Y-m-d')) }}"
                                        data-adj-note="{{ $row->adj_note }}"
                                        data-update-url="{{ url('debtor/1102050102_108/update', $row->vn) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>                            
                                </td> 
                                <td align="center">
                                    @if(Auth::user()->status == 'admin' || Auth::user()->allow_debtor_lock == 'Y')
                                        <button type="button" class="btn btn-sm btn-outline-{{$row->debtor_lock == 'Y' ? 'danger' : 'primary'}}" onclick="{{$row->debtor_lock == 'Y' ? 'confirmUnlock' : 'confirmLock'}}('{{ $row->vn }}')">
                                            <i class="bi bi-{{$row->debtor_lock == 'Y' ? 'unlock' : 'lock'}}"></i>
                                        </button>
                                    @else
                                        {{ $row->status }}
                                    @endif
                                </td>             
                            <?php $count++; ?>
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>    
                            <?php $sum_other += $row->other ; ?>          
                            <?php $sum_debtor += $row->debtor ; ?> 
                            <?php $sum_receive += $row->receive ; ?>       
                            <?php 
                                $s_adj_inc += $row->adj_inc ?? 0;
                                $s_adj_dec += $row->adj_dec ?? 0;
                                $s_balance += $balance;
                            ?>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td class="text-end">{{ number_format($sum_income,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_other,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                                    <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                                    <td class="text-end" style="color: #9c27b0;">{{ number_format($s_adj_inc,2) }}</td>
                                    <td class="text-end" style="color: #673ab7;">{{ number_format($s_adj_dec,2) }}</td>
                                    <td class="text-end" style="color:@if($s_balance < -0.01) red @elseif($s_balance > 0.01) green @else black @endif">{{ number_format($s_balance, 2) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div> 

                <!-- Tab 2: รอยืนยันลูกหนี้ -->
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel">
                    <form id="form-confirm" action="{{ url('debtor/1102050102_108_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf                
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                            <div></div>
                        </div>

                        <div class="table-responsive"><table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-secondary">
                                <th class="text-left text-primary" colspan = "10">1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                         
                                <th class="text-center text-primary">Action</th>
                            </tr>
                            <tr class="table-secondary">
                                <th class="text-center"><input type="checkbox" onClick="toggle(this)"> All</th>   
                                <th class="text-center">วันที่</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                                <th class="text-center">ชำระเอง</th>   
                                <th class="text-center">กองทุนอื่น</th>                                      
                                <th class="text-center text-primary">ลูกหนี้</th>
                                <th class="text-center text-primary">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income_search = 0; ?>
                            <?php $sum_rcpt_money_search = 0; ?>
                            <?php $sum_other_search = 0; ?>
                            <?php $sum_debtor_search = 0; ?>
                            @foreach($debtor_search as $row)
                            @php $balance = -($row->debtor ?? 0); @endphp <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->vn}}"></td> 
                                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                <td align="right">{{ $row->pdx }}</td>                  
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->other,2) }}</td>
                                <td align="right">{{ number_format($row->debtor,2) }}</td>
                                <td align="center">         
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
                                        data-repno="{{ $row->repno }}"
                                        data-adj-inc="{{ $row->adj_inc ?? 0 }}"
                                        data-adj-dec="{{ $row->adj_dec ?? 0 }}"
                                        data-adj-date="{{ $row->adj_date ?? date('Y-m-d') }}"
                                        data-adj-date-th="{{ !empty($row->adj_date) ? DateThai($row->adj_date) : DateThai(date('Y-m-d')) }}"
                                        data-adj-note="{{ $row->adj_note }}"
                                        data-update-url="{{ url('debtor/1102050102_108/update', $row->vn) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>                            
                                </td> 
                            <?php $count++; ?>
                            <?php $sum_income_search += $row->income; ?>
                            <?php $sum_rcpt_money_search += $row->rcpt_money; ?>
                            <?php $sum_other_search += $row->other; ?>
                            <?php $sum_debtor_search += $row->debtor; ?>
                            </tr>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td class="text-end">{{ number_format($sum_income_search,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money_search,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_other_search,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor_search,2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table></div>
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
        const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการลบลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'ใช่, ลบเลย!', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.getElementById('form-delete') || document.querySelector('form[action*="delete"]');
            if(f) f.submit(); 
        } });
    }

    function confirmSubmit() {
        const selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.getElementById('form-confirm') || document.querySelector('form[action*="confirm"]');
            if(f) f.submit(); 
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
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050102_108/lock') }}/" + id;
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
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050102_108/unlock') }}/" + id;
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
                <div class="text-start">
                    <div class="mb-3"><label class="form-label small fw-bold">หมายเหตุการปรับปรุง</label><input type="text" id="blk_note" class="form-control rounded-pill" value="ปรับปรุงยอดเป็น 0"></div>
                    <div class="mb-3"><label class="form-label small fw-bold">วันที่ปรับปรุง</label><input type="text" id="blk_date_th" class="form-control rounded-pill datepicker_th" value="{{DateThai(date('Y-m-d'))}}" readonly><input type="hidden" id="blk_date" value="{{date('Y-m-d')}}"></div>
                </div>
            `,
            icon: 'info', showCancelButton: true, confirmButtonColor: '#ffc107', confirmButtonText: 'ยืนยัน',
            didOpen: () => { $('#blk_date_th').datepicker({ format: 'd M yyyy', autoclose: true, language: 'th-th', thaiyear: true, todayBtn: 'linked', todayHighlight: true }).on('changeDate', (e) => { if (e.date) { const y = e.date.getFullYear(), m=('0'+(e.date.getMonth()+1)).slice(-2), d=('0'+e.date.getDate()).slice(-2); $('#blk_date').val(y+'-'+m+'-'+d); } }); },
            preConfirm: () => { return { note: $('#blk_note').val(), date: $('#blk_date').val() } }
        }).then((r) => {
            if (r.isConfirmed) {
                showLoading(); let f=document.createElement('form'); f.method='POST'; f.action="{{ url('debtor/1102050102_108_bulk_adj') }}";
                f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'_token', value:'{{csrf_token()}}'}));
                f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'bulk_adj_note', value:r.value.note}));
                f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'bulk_adj_date', value:r.value.date}));
                sel.forEach(id=>f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'checkbox_d[]', value:id})));
                document.body.appendChild(f); f.submit();
            }
        });
    }
</script>
    
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
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: '1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: '1102050102.108-ลูกหนี้ค่ารักษา เบิกต้นสังกัด OP รอยืนยัน AE/OP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
});
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