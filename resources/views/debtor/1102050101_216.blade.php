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
        function toggle_kidney(source) {
            checkboxes = document.getElementsByName('checkbox_kidney[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle_cr(source) {
            checkboxes = document.getElementsByName('checkbox_cr[]');
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
    <script>
        function toggle_anywhere(source) {
            checkboxes = document.getElementsByName('checkbox_anywhere[]');
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
                1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR)
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_216') }}" enctype="multipart/form-data" class="m-0 d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="text" id="start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{ DateThai($start_date) }}" style="width: 120px;" readonly>
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                        <input type="text" id="end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{ DateThai($end_date) }}" style="width: 120px;" readonly>
                    </div>



                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="min-width: 220px; flex: 1;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="ค้นหา ชื่อ-สกุล, HN, AN">
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

    <!-- Main Dashboard Container -->
    <div class="card dash-card border-0" style="height: auto !important; overflow: visible !important;">
        
        <!-- Section: Tabs -->
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0">
            <ul class="nav nav-tabs-modern" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="debtor-tab" data-bs-toggle="pill" data-bs-target="#debtor-pane" type="button" role="tab">
                        <i class="bi bi-person-lines-fill me-1 text-success"></i> <span class="text-success fw-bold">รายการลูกหนี้</span>
                        <span class="ms-2 fw-bold text-success" id="badge-tab1">{{ number_format($count_tab1) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="kidney-tab" data-bs-toggle="pill" data-bs-target="#kidney-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ ฟอกไต
                        <span class="ms-2 fw-bold text-warning" id="badge-tab2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="cr-tab" data-bs-toggle="pill" data-bs-target="#cr-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ บริการเฉพาะ
                        <span class="ms-2 fw-bold text-warning" id="badge-tab3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anywhere-tab" data-bs-toggle="pill" data-bs-target="#anywhere-pane" type="button" role="tab">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ Anywhere
                        <span class="ms-2 fw-bold text-warning" id="badge-tab4"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: รายการลูกหนี้ -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 

            <form action="{{ url('debtor/1102050101_216_delete') }}" method="POST" enctype="multipart/form-data">
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
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_216_indiv_excel')}}" target="_blank">
                             <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                        </a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_216_daily_pdf')}}" target="_blank">
                             <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                        </a> 
                    </div>
                </div>
                <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width = "100%">
                    <thead>
                    <tr class="table-success align-middle">
                        <th class="text-left text-primary" colspan = "12">1102050101.216-ลูกหนี้ค่ารักษา UC-OP บริการเฉพาะ (CR) วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "9">การชดเชย</th>                                                 
                    </tr>
                    <tr class="table-success align-middle text-center">
                        <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th> 
                        <th class="text-center">วันที่</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">ชื่อ-สกุล</th>
                        <th class="text-center">สิทธิ</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">ค่ารักษาทั้งหมด</th>  
                        <th class="text-center">ชำระเอง</th>  
                        <th class="text-center">ฟอกไต</th>   
                        <th class="text-center">บริการเฉพาะ</th>
                        <th class="text-center">OP Anywhere</th> 
                        <th class="text-center">PPFS</th>       
                        <th class="text-center text-primary">ลูกหนี้</th>
                        <th class="text-center text-primary">ชดเชย</th> 
                        <th class="text-center" style="color: #9c27b0;">ปรับเพิ่ม</th>
                        <th class="text-center" style="color: #673ab7;">ปรับลด</th>
                        <th class="text-center">ยอดคงเหลือ</th>
                        <th class="text-center text-primary">REP</th> 
                        <th class="text-center text-primary">อายุหนี้</th>                         
                        <th class="text-center text-primary" width="5%">Action</th> 
                        <th class="text-center text-primary">Lock</th>                                       
                    </tr>
                    </thead>
                    @php 
                        $count = 1;
                        $sum_income = 0; $sum_rcpt_money = 0; $sum_kidney = 0;
                        $sum_cr = 0; $sum_anywhere = 0; $sum_ppfs = 0;
                        $sum_debtor = 0; $sum_receive = 0;
                        $s_adj_inc = 0; $s_adj_dec = 0; $s_balance = 0;
                    @endphp
                    <tbody>
                    @foreach($debtor as $row) 
                    @php 
                        $balance = $row->receive + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0) - $row->debtor;
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
                        <td align="right">{{ number_format($row->kidney,2) }}</td>
                        <td align="right">{{ number_format($row->cr,2) }}</td>
                        <td align="right">{{ number_format($row->anywhere,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
                        <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                        <td align="right" @if($row->receive > 0) style="color:green" 
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{ number_format($row->receive,2) }}
                        </td>
                        <td align="right" style="color: #9c27b0;">{{ number_format($row->adj_inc ?? 0, 2) }}</td>
                        <td align="right" style="color: #673ab7;">{{ number_format($row->adj_dec ?? 0, 2) }}</td>
                        <td align="right" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">{{ number_format($balance, 2) }}</td>
                        <td align="right">{{ $row->repno }} {{ $row->rid }}</td> 
                        <td align="center" @if($row->days < 90) style="background-color: #90EE90;"  
                            @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                            @else style="background-color: #FF7F7F;" @endif >
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
                                        data-repno="{{ $row->repno_manual ?? '' }}"
                                        data-adj-inc="{{ $row->adj_inc ?? 0 }}"
                                        data-adj-dec="{{ $row->adj_dec ?? 0 }}"
                                        data-adj-date="{{ $row->adj_date ?? date('Y-m-d') }}"
                                        data-adj-date-th="{{ !empty($row->adj_date) ? DateThai($row->adj_date) : DateThai(date('Y-m-d')) }}"
                                        data-adj-note="{{ $row->adj_note }}"
                                        data-update-url="{{ url('debtor/1102050101_216/update', $row->vn) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>                            
                        </td>
                        <td align="center">
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
                        $count++;
                        $sum_income += $row->income;
                        $sum_rcpt_money += $row->rcpt_money;
                        $sum_kidney += $row->kidney;
                        $sum_cr += $row->cr;
                        $sum_anywhere += $row->anywhere;
                        $sum_ppfs += $row->ppfs;
                        $sum_debtor += $row->debtor;
                        $sum_receive += $row->receive;
                        $s_adj_inc += $row->adj_inc ?? 0;
                        $s_adj_dec += $row->adj_dec ?? 0;
                        $s_balance += $balance;
                    @endphp
                    @endforeach 
                    </tbody>
                    
                    <tfoot>

                        <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_kidney,2) }}</td>
                            <td class="text-end">{{ number_format($sum_cr,2) }}</td>
                            <td class="text-end">{{ number_format($sum_anywhere,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs,2) }}</td>
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
            
            <!-- Tab 2: Confirm Kidney -->
            <div class="tab-pane fade" id="kidney-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_kidney') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-2">
                         <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_kidney()">ยืนยันลูกหนี้</button>
                         <div></div>
                    </div>
                    <div id="loading-tab2" class="text-center p-5 d-none">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-2 text-muted">กำลังดึงข้อมูลจาก HOSxP...</p>
                        <p class="small text-danger">โปรดรอซักครู่</p>
                    </div>
                    <div id="empty-tab2" class="text-center p-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="mt-2 text-muted">คลิกที่ Tab หรือกดปุ่มโหลดข้อมูลเพื่อแสดงรายการ</p>
                        <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="loadTab2()">โหลดข้อมูล HOSxP</button>
                    </div>
                    <table id="debtor_search_kidney" class="table table-bordered table-striped my-3 d-none" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "10">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) ฟอกไต วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_kidney(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>                        
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center" width="25%">รายการเรียกเก็บ</th>
                        </tr>
                        </thead>
                        <tbody id="kidney-table-body"></tbody>
                        <tfoot>
                            <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                <td colspan="6" class="text-end">รวม</td>
                                <td class="text-end" id="kidney-sum-income">0.00</td>
                                <td class="text-end" id="kidney-sum-rcpt">0.00</td>
                                <td class="text-end" id="kidney-sum-debtor" style="color:blue">0.00</td>
                                <td colspan="1"></td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            </div>
        
            <!-- Tab 3: Confirm CR -->
            <div class="tab-pane fade" id="cr-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_cr') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                     <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_cr()">ยืนยันลูกหนี้</button>
                        <div></div>
                    </div>
                    <div id="loading-tab3" class="text-center p-5 d-none">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-2 text-muted">กำลังดึงข้อมูลจาก HOSxP...</p>
                        <p class="small text-danger">โปรดรอซักครู่</p>
                    </div>
                    <div id="empty-tab3" class="text-center p-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="mt-2 text-muted">คลิกที่ Tab หรือกดปุ่มโหลดข้อมูลเพื่อแสดงรายการ</p>
                        <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="loadTab3()">โหลดข้อมูล HOSxP</button>
                    </div>
                    <table id="debtor_search_cr" class="table table-bordered table-striped my-3 d-none" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "11">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                                                         
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_cr(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>                        
                            <th class="text-center">เรียกเก็บ</th>
                            <th class="text-center" width="25%">รายการเรียกเก็บ</th>
                            <th class="text-center">ส่ง Claim</th>
                        </tr>
                        </thead>
                        <tbody id="cr-table-body"></tbody>
                        <tfoot>
                            <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                <td colspan="6" class="text-end">รวม</td>
                                <td class="text-end" id="cr-sum-income">0.00</td>
                                <td class="text-end" id="cr-sum-rcpt">0.00</td>
                                <td class="text-end" id="cr-sum-debtor" style="color:blue">0.00</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            </div>

            <!-- Tab 4: Confirm Anywhere -->
            <div class="tab-pane fade" id="anywhere-pane" role="tabpanel">
                <form action="{{ url('debtor/1102050101_216_confirm_anywhere') }}" method="POST" enctype="multipart/form-data">
                    @csrf  
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_anywhere()">ยืนยันลูกหนี้</button>
                        <div></div>
                    </div>
                    <div id="loading-tab4" class="text-center p-5 d-none">
                        <div class="spinner-border text-warning" role="status"></div>
                        <p class="mt-2 text-muted">กำลังดึงข้อมูลจาก HOSxP...</p>
                        <p class="small text-danger">โปรดรอซักครู่</p>
                    </div>
                    <div id="empty-tab4" class="text-center p-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="mt-2 text-muted">คลิกที่ Tab หรือกดปุ่มโหลดข้อมูลเพื่อแสดงรายการ</p>
                        <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="loadTab4()">โหลดข้อมูล HOSxP</button>
                    </div>
                    <table id="debtor_search_anywhere" class="table table-bordered table-striped my-3 d-none" width="100%">
                        <thead>
                        <tr class="table-secondary">
                            <th class="text-left text-primary" colspan = "14">ผู้มารับบริการ UC-OP บริการเฉพาะ (CR) OP Anywhere วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                                                          
                        </tr>
                        <tr class="table-secondary">
                            <th class="text-center"><input type="checkbox" onClick="toggle_anywhere(this)"> All</th>  
                            <th class="text-center" width="6%">วันที่</th>
                            <th class="text-center">HN</th>
                            <th class="text-center" width="10%">ชื่อ-สกุล</th>
                            <th class="text-center" width="10%">สิทธิ</th>
                            <th class="text-center">ICD10</th>
                            <th class="text-center">ค่ารักษาทั้งหมด</th>  
                            <th class="text-center">ชำระเอง</th>
                            <th class="text-center">กองทุนอื่น</th>   
                            <th class="text-center">PPFS</th>                         
                            <th class="text-center">ลูกหนี้</th>
                            <th class="text-center" width = "10%">รายการกองทุนอื่น</th> 
                            <th class="text-center" width = "10%">รายการ PPFS</th>
                            <th class="text-center">ส่ง Claim</th>
                        </tr>
                        </thead>
                        <tbody id="anywhere-table-body"></tbody>
                        <tfoot>
                            <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                <td colspan="6" class="text-end">รวม</td>
                                <td class="text-end" id="anywhere-sum-income">0.00</td>
                                <td class="text-end" id="anywhere-sum-rcpt">0.00</td>
                                <td class="text-end" id="anywhere-sum-other">0.00</td>
                                <td class="text-end" id="anywhere-sum-ppfs">0.00</td>
                                <td class="text-end" id="anywhere-sum-debtor" style="color:blue">0.00</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
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

    function confirmSubmit_kidney() {
        const selected = [...document.querySelectorAll('input[name="checkbox_kidney[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.querySelector('form[action*="confirm_kidney"]');
            if(f) f.submit(); 
        } });
    }

    function confirmSubmit_cr() {
        const selected = [...document.querySelectorAll('input[name="checkbox_cr[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.querySelector('form[action*="confirm_cr"]');
            if(f) f.submit(); 
        } });
    }

    function confirmSubmit_anywhere() {
        const selected = [...document.querySelectorAll('input[name="checkbox_anywhere[]"]:checked')].map(e => e.value);    
        if (selected.length === 0) { Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning'); return; }
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => { if (result.isConfirmed) { 
            const f = document.querySelector('form[action*="confirm_anywhere"]');
            if(f) f.submit(); 
        } });
    }

    function confirmLock(id) {
        Swal.fire({
            title: 'ยืนยัน?', text: "ต้องการ Lock รายการนี้ใช่หรือไม่?", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#0d6efd', cancelButtonColor: '#6c757d', confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050101_216/lock') }}/" + id;
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
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050101_216/unlock') }}/" + id;
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
                showLoading(); let f=document.createElement('form'); f.method='POST'; f.action="{{ url('debtor/1102050101_216_bulk_adj') }}";
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
    // 1. Initialize Datepicker Thai
    $('#start_date_picker, #end_date_picker').datepicker({
        format: 'd M yyyy', todayBtn: 'linked', todayHighlight: true, autoclose: true, language: 'th-th', thaiyear: true, zIndexOffset: 1050
    }).on('changeDate', function(e) {
        if (e.date) {
            var y = e.date.getFullYear(), m = ('0'+(e.date.getMonth()+1)).slice(-2), d = ('0'+e.date.getDate()).slice(-2);
            var dateStr = y + '-' + m + '-' + d;
            if (this.id == 'start_date_picker') { $('#start_date').val(dateStr); }
            if (this.id == 'end_date_picker') { $('#end_date').val(dateStr); }
        }
    });

    $('.datepicker_th').not('#start_date_picker, #end_date_picker, [id^="modal_"]').datepicker({
        format: 'd M yyyy', autoclose: true, language: 'th-th', thaiyear: true, todayBtn: 'linked', todayHighlight: true
    });

    var start_date_val = "{{ $start_date }}";
    var end_date_val = "{{ $end_date }}";
    
    function setInitialDate(pickerId, dateStr) {
        if(dateStr && dateStr !== '0000-00-00') {
            var p = dateStr.split('-');
            if(p.length === 3) {
                $(pickerId).val(''); 
                $(pickerId).datepicker('setDate', new Date(p[0], p[1]-1, p[2]));
            }
        }
    }
    setInitialDate('#start_date_picker', start_date_val);
    setInitialDate('#end_date_picker', end_date_val);

    // 2. Main Table DataTable
    if ($('#debtor').length) {
        $('#debtor').DataTable({
            dom: '<"row mb-3"<"col-md-6"l>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            ordering: true,
            language: { lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
        });
    }

    // 3. AJAX Functions
    function DateThai(dateStr) {
        if(!dateStr || dateStr === '0000-00-00') return '';
        const months = ["ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
        const p = dateStr.split('-');
        return parseInt(p[2]) + ' ' + months[parseInt(p[1])-1] + ' ' + (parseInt(p[0]) + 543);
    }

    function number_format(number, decimals) {
        if (number === null || isNaN(number)) return '0.00';
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(number);
    }

    var _tab2Loaded = false, _tab3Loaded = false, _tab4Loaded = false;


    window.loadTab2 = function() {
        if (_tab2Loaded) return;
        _tab2Loaded = true;
        $('#empty-tab2').addClass('d-none');
        $('#loading-tab2').removeClass('d-none');
        $('#debtor_search_kidney').addClass('d-none');
        $.ajax({
            url: "{{ url('debtor/1102050101_216_search_kidney_ajax') }}",
            type: "GET",
            data: { start_date: start_date_val, end_date: end_date_val },
            success: function(res) {
                $('#badge-tab2').text(number_format(res.length, 0));
                $('#loading-tab2').addClass('d-none');
                $('#debtor_search_kidney').removeClass('d-none');
                let html = '';
                let s1 = 0, s2 = 0, s3 = 0;
                res.forEach(r => {
                    html += `<tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_kidney[]" value="${r.vn}"></td>
                        <td align="center">${DateThai(r.vstdate)} ${r.vsttime.substring(0,5)}</td>
                        <td align="center">${r.hn}</td>
                        <td align="left">${r.ptname}</td>
                        <td align="right">${r.pttype} [${r.hospmain}]</td>
                        <td align="right">${r.pdx || ''}</td>
                        <td align="right">${number_format(r.income,2)}</td>
                        <td align="right">${number_format(r.rcpt_money,2)}</td>
                        <td align="right">${number_format(r.debtor,2)}</td>
                        <td align="left">${r.claim_list || ''}</td>
                    </tr>`;
                    s1 += parseFloat(r.income); s2 += parseFloat(r.rcpt_money); s3 += parseFloat(r.debtor);
                });
                $('#kidney-table-body').html(html);
                $('#kidney-sum-income').text(number_format(s1,2));
                $('#kidney-sum-rcpt').text(number_format(s2,2));
                $('#kidney-sum-debtor').text(number_format(s3,2));
                $('#debtor_search_kidney').DataTable({
                    destroy: true,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: 'รอยืนยันลูกหนี้ ฟอกไต' }],
                    language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
                });
            },
            error: function() {
                $('#loading-tab2').addClass('d-none');
                $('#empty-tab2').removeClass('d-none');
            }
        });
    }

    window.loadTab3 = function() {
        if (_tab3Loaded) return;
        _tab3Loaded = true;
        $('#empty-tab3').addClass('d-none');
        $('#loading-tab3').removeClass('d-none');
        $('#debtor_search_cr').addClass('d-none');
        $.ajax({
            url: "{{ url('debtor/1102050101_216_search_cr_ajax') }}",
            type: "GET",
            data: { start_date: start_date_val, end_date: end_date_val },
            success: function(res) {
                $('#badge-tab3').text(number_format(res.length, 0));
                $('#loading-tab3').addClass('d-none');
                $('#debtor_search_cr').removeClass('d-none');
                let html = '';
                let s1 = 0, s2 = 0, s3 = 0;
                res.forEach(r => {
                    html += `<tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_cr[]" value="${r.vn}"></td>
                        <td align="center">${DateThai(r.vstdate)} ${r.vsttime.substring(0,5)}</td>
                        <td align="center">${r.hn}</td>
                        <td align="left">${r.ptname}</td>
                        <td align="right">${r.pttype} [${r.hospmain}]</td>
                        <td align="right">${r.pdx || ''}</td>
                        <td align="right">${number_format(r.income,2)}</td>
                        <td align="right">${number_format(r.rcpt_money,2)}</td>
                        <td align="right">${number_format(r.debtor,2)}</td>
                        <td align="left">${r.claim_list || ''}</td>
                        <td align="center" style="color:green">${r.send_claim || ''}</td>
                    </tr>`;
                    s1 += parseFloat(r.income); s2 += parseFloat(r.rcpt_money); s3 += parseFloat(r.debtor);
                });
                $('#cr-table-body').html(html);
                $('#cr-sum-income').text(number_format(s1,2));
                $('#cr-sum-rcpt').text(number_format(s2,2));
                $('#cr-sum-debtor').text(number_format(s3,2));
                $('#debtor_search_cr').DataTable({
                    destroy: true,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: 'รอยืนยันลูกหนี้ บริการเฉพาะ' }],
                    language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
                });
            },
            error: function() {
                $('#loading-tab3').addClass('d-none');
                $('#empty-tab3').removeClass('d-none');
            }
        });
    }

    window.loadTab4 = function() {
        if (_tab4Loaded) return;
        _tab4Loaded = true;
        $('#empty-tab4').addClass('d-none');
        $('#loading-tab4').removeClass('d-none');
        $('#debtor_search_anywhere').addClass('d-none');
        $.ajax({
            url: "{{ url('debtor/1102050101_216_search_anywhere_ajax') }}",
            type: "GET",
            data: { start_date: start_date_val, end_date: end_date_val },
            success: function(res) {
                $('#badge-tab4').text(number_format(res.length, 0));
                $('#loading-tab4').addClass('d-none');
                $('#debtor_search_anywhere').removeClass('d-none');
                let html = '';
                let s1 = 0, s2 = 0, s3 = 0, s4 = 0, s5 = 0;
                res.forEach(r => {
                    html += `<tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_anywhere[]" value="${r.vn}"></td>
                        <td align="center">${DateThai(r.vstdate)} ${r.vsttime.substring(0,5)}</td>
                        <td align="center">${r.hn}</td>
                        <td align="left">${r.ptname}</td>
                        <td align="right">${r.pttype} [${r.hospmain}]</td>
                        <td align="right">${r.pdx || ''}</td>
                        <td align="right">${number_format(r.income,2)}</td>
                        <td align="right">${number_format(r.rcpt_money,2)}</td>
                        <td align="right">${number_format(r.other,2)}</td>
                        <td align="right">${number_format(r.ppfs,2)}</td>
                        <td align="right">${number_format(r.debtor,2)}</td>
                        <td align="left">${r.other_list || ''}</td>
                        <td align="left">${r.ppfs_list || ''}</td>
                        <td align="center" style="color:green">${r.send_claim || ''}</td>
                    </tr>`;
                    s1 += parseFloat(r.income); s2 += parseFloat(r.rcpt_money); s3 += parseFloat(r.other); s4 += parseFloat(r.ppfs); s5 += parseFloat(r.debtor);
                });
                $('#anywhere-table-body').html(html);
                $('#anywhere-sum-income').text(number_format(s1,2));
                $('#anywhere-sum-rcpt').text(number_format(s2,2));
                $('#anywhere-sum-other').text(number_format(s3,2));
                $('#anywhere-sum-ppfs').text(number_format(s4,2));
                $('#anywhere-sum-debtor').text(number_format(s5,2));
                $('#debtor_search_anywhere').DataTable({
                    destroy: true,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: 'รอยืนยันลูกหนี้ Anywhere' }],
                    language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
                });
            },
            error: function() {
                $('#loading-tab4').addClass('d-none');
                $('#empty-tab4').removeClass('d-none');
            }
        });
    }

    // Load background tabs sequentially to avoid hitting the DB too hard at once
    loadTab2();
    $(document).ajaxStop(function() {
        if (!_tab2Loaded) return; // Wait for tab 2
        if (!_tab3Loaded) loadTab3();
        else if (!_tab4Loaded) loadTab4();
    });
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
