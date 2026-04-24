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
    <script>
        function toggle_iclaim(source) {
            checkboxes = document.getElementsByName('checkbox_iclaim[]');
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
                1102050102.107-ลูกหนี้ค่ารักษา ชําระเงิน IP
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" enctype="multipart/form-data" class="m-0 d-flex flex-wrap align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                        <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start"><i class="bi bi-calendar-event me-1"></i> วันที่</span>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="text" id="start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{ DateThai($start_date) }}" style="width: 120px;" placeholder="วว/ดด/ปปปป" readonly>
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                        <input type="text" id="end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{ DateThai($end_date) }}" style="width: 120px;" placeholder="วว/ดด/ปปปป" readonly>
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="min-width: 220px; flex: 1;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input id="search" type="text" class="form-control border-start-0" name="search" value="{{ $search }}" placeholder="ค้นหา ชื่อ-สกุล, HN, AN">
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
                        <span class="ms-2 fw-bold text-success">{{ count($debtor) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" onclick="loadTab2()">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ ชำระเงิน IP
                        <span class="ms-2 fw-bold text-warning" id="badge-tab2"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="iclaim-tab" data-bs-toggle="pill" data-bs-target="#iclaim-pane" type="button" role="tab" onclick="loadTab3()">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ iClaim
                        <span class="ms-2 fw-bold text-info" id="badge-tab3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body px-4 pb-4 pt-0">
            <div class="tab-content" id="myTabContent">
                
                <!-- Tab 1: รายการลูกหนี้ -->
                <div class="tab-pane fade show active" id="debtor-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_delete') }}" method="POST" enctype="multipart/form-data">
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
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050102_107_indiv_excel')}}" target="_blank">
                                     <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_107_daily_pdf')}}" target="_blank">
                                     <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                                </a> 
                            </div>
                        </div>
                        <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-success">
                                <th class="text-left text-primary" colspan="12">1102050102.107-ลูกหนี้ค่ารักษา ชําระเงิน IP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                                <th class="text-center text-primary" colspan="9">การชดเชย</th>                                                 
                            </tr>
                            <tr class="table-success">
                                <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">AN</th>
                                <th class="text-center">ชื่อ-สกุล</th>   
                                <th class="text-center">เบอร์โทร.</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">Admit</th> 
                                <th class="text-center">Discharge</th> 
                                <th class="text-center">ICD10</th> 
                                <th class="text-center">ค่าใช้จ่ายทั้งหมด</th> 
                                <th class="text-center">ต้องชำระเงิน</th> 
                                <th class="text-center">ชำระเอง</th>
                                <th class="text-center text-primary">ลูกหนี้</th>
                                <th class="text-center text-primary">ชดเชย</th> 
                                <th class="text-center text-primary">ปรับเพิ่ม</th>
                                <th class="text-center text-primary">ปรับลด</th>
                                <th class="text-center text-primary">ยอดคงเหลือ</th>
                                <th class="text-center text-primary">เลขที่ใบเสร็จ</th>    
                                <th class="text-center text-primary">อายุหนี้</th>   
                                <th class="text-center text-primary" width="6%">Action</th> 
                                <th class="text-center text-primary">Lock</th>   
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_paid_money  = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>
                            <?php $sum_debtor = 0 ; ?>
                            <?php $sum_receive  = 0 ; ?>
                            <?php $sum_adj_inc = 0 ; ?>
                            <?php $sum_adj_dec = 0 ; ?>
                            <?php $sum_balance = 0 ; ?>
                            @foreach($debtor as $row)
                            @php $balance = ($row->receive + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - $row->debtor; @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->an}}"></td> 
                                <td align="center">{{ $row->hn }}</td>
                                <td align="center">{{ $row->an }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="right">{{ $row->mobile_phone_number }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="right">{{ DateThai($row->regdate) }}</td>
                                <td align="right">{{ DateThai($row->dchdate) }}</td>           
                                <td align="center">{{ $row->pdx }}</td>     
                                <td align="right">{{ number_format($row->income,2) }}</td>   
                                <td align="right">{{ number_format($row->paid_money,2) }}</td>   
                                <td align="right">{{ number_format($row->rcpt_money,2) }} </td> 
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td> 
                                <td align="right" @if($row->receive > 0) style="color:green" 
                                    @elseif($row->receive < 0) style="color:red" @endif>
                                    {{ number_format($row->receive,2) }}
                                </td>
                                <td align="right" style="color:blue">{{ number_format($row->adj_inc,2) }}</td>
                                <td align="right" style="color:red">{{ number_format($row->adj_dec,2) }}</td>
                                <td align="right" @if($balance > 0.05) style="color:green"
                                    @elseif($balance < -0.05) style="color:red" @endif>
                                    {{ number_format($balance,2) }}
                                </td> 
                                <td align="center">{{ $row->repno ?? '' }} {{ $row->rcpno ?? '' }}</td>                    
                                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  
                                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                                    @else style="background-color: #FF7F7F;" @endif >
                                    {{ $row->days }} วัน
                                </td>   
                                <td align="right" width="9%">
                                    <button type="button" class="btn btn-warning btn-sm px-2 shadow-sm text-dark btn-edit-debtor"
                                        data-vn="{{ $row->an }}"
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
                                        data-update-url="{{ url('debtor/1102050102_107/update', $row->an) }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a class="btn btn-outline-info btn-sm" href="{{ url('debtor/1102050102_107/tracking', $row->an) }}" target="_blank">ติดตาม {{ $row->visit }}</a> 
                                </td> 
                                <td align="center">
                                    @if(Auth::user()->status == 'admin' || Auth::user()->allow_debtor_lock == 'Y')
                                        <button type="button" class="btn btn-sm btn-outline-{{$row->debtor_lock == 'Y' ? 'danger' : 'primary'}}" onclick="{{$row->debtor_lock == 'Y' ? 'confirmUnlock' : 'confirmLock'}}('{{ $row->an }}')">
                                            <i class="bi bi-{{$row->debtor_lock == 'Y' ? 'unlock' : 'lock'}}"></i>
                                        </button>
                                    @else
                                        {{ $row->debtor_lock }}
                                    @endif
                                </td>                         
                            </tr>
                            <?php $count++; ?>
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_paid_money  += $row->paid_money ; ?>
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                            <?php $sum_debtor += $row->debtor ; ?> 
                            <?php $sum_receive += $row->receive ; ?>
                            <?php $sum_adj_inc += $row->adj_inc ; ?>
                            <?php $sum_adj_dec += $row->adj_dec ; ?>
                            <?php $sum_balance += $balance; ?>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="9" class="text-end">รวม</td>
                                    <td class="text-end">{{ number_format($sum_income,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_paid_money,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                                    <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_adj_inc,2) }}</td>
                                    <td class="text-end" style="color:red">{{ number_format($sum_adj_dec,2) }}</td>
                                    <td class="text-end" style="color:@if($sum_balance > 0.05) green @elseif($sum_balance < -0.05) red @else black @endif">
                                        {{ number_format($sum_balance, 2) }}
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div>

                <!-- Tab 2: ชำระเงิน IP -->
                <div class="tab-pane fade" id="pay-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf                
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
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
                        <div class="table-responsive">
                            <table id="debtor_search" class="table table-bordered table-striped my-3 d-none" width="100%">
                                <thead>
                                    <tr class="table-secondary">
                                        <th class="text-left text-primary" colspan="18">1102050102.107-ลูกหนี้ค่ารักษา ชําระเงิน IP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th class="text-center"><input type="checkbox" onClick="toggle(this)"></th>
                                        <th class="text-center">ตึกผู้ป่วย</th>
                                        <th class="text-center">HN</th>
                                        <th class="text-center">AN</th>
                                        <th class="text-center">ชื่อ-สกุล</th>
                                        <th class="text-center">อายุ</th>
                                        <th class="text-center" width="8%">สิทธิ</th>
                                        <th class="text-center" width="6%">Admit</th>
                                        <th class="text-center" width="6%">Discharge</th>
                                        <th class="text-center">ICD10</th>
                                        <th class="text-center">AdjRW</th>
                                        <th class="text-center">ค่ารักษาทั้งหมด</th>
                                        <th class="text-center">ต้องชำระ</th>
                                        <th class="text-center">ชำระเอง</th>
                                        <th class="text-center">ลูกหนี้</th>
                                        <th class="text-center">ค้างชำระ</th>
                                        <th class="text-center">ฝากมัดจำ</th>
                                        <th class="text-center">ถอนมัดจำ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Content -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary text-end" style="font-weight:bold; font-size: 13px;">
                                        <td colspan="11" class="text-end">รวม</td>
                                        <td class="text-end" id="sum_income_search">0.00</td>
                                        <td class="text-end" id="sum_paid_money_search">0.00</td>
                                        <td class="text-end" id="sum_rcpt_money_search">0.00</td>
                                        <td class="text-end" style="color:blue" id="sum_debtor_search">0.00</td>
                                        <td class="text-end" id="sum_arrear">0.00</td>
                                        <td class="text-end" id="sum_deposit">0.00</td>
                                        <td class="text-end" id="sum_debit">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div>

                <!-- Tab 3: iClaim -->
                <div class="tab-pane fade" id="iclaim-pane" role="tabpanel"> 
                    <form action="{{ url('debtor/1102050102_107_confirm_iclaim') }}" method="POST" enctype="multipart/form-data"> 
                        @csrf                
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_iclaim()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้ (iClaim)
                            </button>
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
                            <button type="button" class="btn btn-warning btn-sm text-dark fw-bold" onclick="loadTab3()">โหลดข้อมูล iClaim</button>
                        </div>
                        <div class="table-responsive">
                            <table id="debtor_search_iclaim_table" class="table table-bordered table-striped my-3 d-none" width="100%">
                                <thead>
                                    <tr class="table-secondary">
                                        <th class="text-center" colspan="15">ผู้มารับบริการใช้ประกันชีวิต iClaim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>
                                    </tr>
                                    <tr class="table-secondary">
                                        <th class="text-center"><input type="checkbox" onClick="toggle_iclaim(this)"></th>
                                        <th class="text-center">ตึกผู้ป่วย</th>
                                        <th class="text-center">HN</th>
                                        <th class="text-center">AN</th>
                                        <th class="text-center">ชื่อ-สกุล</th>
                                        <th class="text-center">อายุ</th>
                                        <th class="text-center" width="15%">สิทธิ</th>
                                        <th class="text-center">Admit</th>
                                        <th class="text-center">Discharge</th>
                                        <th class="text-center">ICD10</th>
                                        <th class="text-center">AdjRW</th>
                                        <th class="text-center">ค่ารักษาทั้งหมด</th>
                                        <th class="text-center">ชำระเอง</th>
                                        <th class="text-center">กองทุนอื่น</th>
                                        <th class="text-center">ลูกหนี้</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic Content -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary text-end" style="font-weight:bold; font-size: 13px;">
                                        <td colspan="11" class="text-end">รวม</td>
                                        <td class="text-end" id="sum_ic_income">0.00</td>
                                        <td class="text-end" id="sum_ic_rcpt">0.00</td>
                                        <td class="text-end" id="sum_ic_other">0.00</td>
                                        <td class="text-end" style="color:blue" id="sum_ic_debtor">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Pills Tabs -->
        
        <!-- Modal บันทึกชดเชย -->
<!-- Single Debtor Modal -->
<div id="debtorModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title d-flex align-items-center"><i class="bi bi-cash-stack me-2"></i> รายการการชดเชยเงิน/ลูกหนี้ (AN: <span id="modal_vn"></span>)</h5>
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
        <!-- end modal -->
    </div>

<!-- สำเร็จ -->
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif
 <!-- กำลังโหลด -->
    <script>
        function showLoading() {
            Swal.fire({
                title: 'กำลังโหลด...',
                text: 'โปรดรอซักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }
        function fetchData() {
            showLoading();
        }
    </script>
<!-- ลบลูกหนี้ -->
    <script>
        function confirmDelete() { 
            const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning');
                return;
            }
            Swal.fire({
            title: 'ยืนยัน?',
            text: "ต้องการลบลูกหนี้รายการที่เลือกใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_delete') }}']").submit();
                }
            });
        }
    </script>
<!-- ยืนยันลูกหนี้ -->
    <script>
        function confirmSubmit() {
            const selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_confirm') }}']").submit();
                }
            });
        }
    </script>
    <script>
        function confirmSubmit_iclaim() {
            const selected = [...document.querySelectorAll('input[name="checkbox_iclaim[]"]:checked')].map(e => e.value);    
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะยืนยัน', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันลูกหนี้รายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector("form[action='{{ url('debtor/1102050102_107_confirm_iclaim') }}']").submit();
                }
            });
        }
    </script>


    <script>
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
                    showLoading(); let f=document.createElement('form'); f.method='POST'; f.action="{{ url('debtor/1102050102_107_bulk_adj') }}";
                    f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'_token', value:'{{csrf_token()}}'}));
                    f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'bulk_adj_note', value:r.value.note}));
                    f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'bulk_adj_date', value:r.value.date}));
                    sel.forEach(id=>f.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'checkbox_d[]', value:id})));
                    document.body.appendChild(f); f.submit();
                }
            });
        }
        function confirmUnlock(id) {
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการ Unlock รายการนี้ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ url('debtor/1102050102_107/unlock') }}/" + id;
                    
                    var csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        function confirmLock(id) {
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการ Lock รายการนี้ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ url('debtor/1102050102_107/lock') }}/" + id;
                    
                    var csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>


    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: '{{ session('error') }}',
                timer: 4000,
                showConfirmButton: false
            });
        </script>
    @endif
    @if (session('warning'))
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'แจ้งเตือน',
                text: '{{ session('warning') }}',
                timer: 4000,
                showConfirmButton: false
            });
        </script>
    @endif

@endsection

<!-- Modal -->


@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'd M yyyy', // Matches DateThai() helper output
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true,
                zIndexOffset: 1050
            });

            // Set initial values (ensures calendar is synced)
            var start_date_val = "{{ $start_date }}";
            var end_date_val = "{{ $end_date }}";
            if(start_date_val) {
                $('#start_date_display').datepicker('setDate', new Date(start_date_val));
            }
            if(end_date_val) {
                $('#end_date_display').datepicker('setDate', new Date(end_date_val));
            }

            // Sync Date Inputs (Generic Handler)
            $(document).on('changeDate', '.datepicker_th', function(e) {
                var date = e.date;
                var hiddenInput = $(this).prev('input[type="hidden"]');
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });
        });
    </script>
    <script>
        function loadCounts() {
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val();
            
            // Set spinners on badges
            $('#badge-tab2').html('<span class="spinner-border spinner-border-sm" role="status"></span>');
            $('#badge-tab3').html('<span class="spinner-border spinner-border-sm" role="status"></span>');

            $.get("{{ url('debtor/1102050102_107_counts_ajax') }}", { start_date, end_date }, function(res) {
                $('#badge-tab2').text(res.tab2);
                $('#badge-tab3').text(res.tab3);
            });
        }

        let tab2Loaded = false;
        function loadTab2() {
            if (tab2Loaded) return;
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val();
            
            $('#loading-tab2').removeClass('d-none');
            $('#debtor_search').addClass('d-none');
            $('#empty-tab2').addClass('d-none');

            // Set tab2Loaded to true immediately to prevent double clicks
            tab2Loaded = true;

            $.get("{{ url('debtor/1102050102_107_search_ajax') }}", { start_date, end_date }, function(data) {
                $('#loading-tab2').addClass('d-none');
                $('#debtor_search').removeClass('d-none');
                
                let html = '';
                let sum_inc = 0, sum_paid = 0, sum_rcpt = 0, sum_debt = 0, sum_arr = 0, sum_dep = 0, sum_deb = 0;
                
                data.forEach(row => {
                    const debtor = parseFloat(row.debtor) || 0;
                    sum_inc += parseFloat(row.income) || 0;
                    sum_paid += parseFloat(row.paid_money) || 0;
                    sum_rcpt += parseFloat(row.rcpt_money) || 0;
                    sum_debt += debtor;
                    sum_arr += parseFloat(row.arrear_amount) || 0;
                    sum_dep += parseFloat(row.deposit_amount) || 0;
                    sum_deb += parseFloat(row.debit_amount) || 0;

                    html += `<tr>
                        <td class="text-center"><input type="checkbox" name="checkbox[]" value="${row.an}"></td>
                        <td>${row.ward || ''}</td>
                        <td class="text-center">${row.hn}</td>
                        <td class="text-center">${row.an}</td>
                        <td>${row.ptname}</td>
                        <td class="text-center">${row.age_y}</td>
                        <td>${row.pttype}</td>
                        <td class="text-center">${thaiDate(row.regdate)}</td>
                        <td class="text-center">${thaiDate(row.dchdate)}</td>
                        <td class="text-center">${row.pdx || ''}</td>
                        <td class="text-end">${parseFloat(row.adjrw || 0).toFixed(2)}</td>
                        <td class="text-end">${formatMoney(row.income)}</td>
                        <td class="text-end">${formatMoney(row.paid_money)}</td>
                        <td class="text-end">${formatMoney(row.rcpt_money)}</td>
                        <td class="text-end text-primary fw-bold">${formatMoney(debtor)}</td>
                        <td class="text-end">${formatMoney(row.arrear_amount)}</td>
                        <td class="text-end">${formatMoney(row.deposit_amount)}</td>
                        <td class="text-end">${formatMoney(row.debit_amount)}</td>
                    </tr>`;
                });

                $('#debtor_search tbody').html(html);
                $('#sum_income_search').text(formatMoney(sum_inc));
                $('#sum_paid_money_search').text(formatMoney(sum_paid));
                $('#sum_rcpt_money_search').text(formatMoney(sum_rcpt));
                $('#sum_debtor_search').text(formatMoney(sum_debt));
                $('#sum_arrear').text(formatMoney(sum_arr));
                $('#sum_deposit').text(formatMoney(sum_dep));
                $('#sum_debit').text(formatMoney(sum_deb));

                if ($.fn.DataTable.isDataTable('#debtor_search')) {
                    $('#debtor_search').DataTable().destroy();
                }

                $('#debtor_search').removeClass('d-none').DataTable({
                    destroy: true,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: '1102050102.107-ลูกหนี้ IP รอยืนยัน' }],
                    language: { search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ", paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } }
                });
                
                tab2Loaded = true;
            }).fail(function() {
                tab2Loaded = false;
                $('#loading-tab2').addClass('d-none');
                $('#empty-tab2').removeClass('d-none');
            });
        }

        let tab3Loaded = false;
        function loadTab3() {
            if (tab3Loaded) return;
            const start_date = $('#start_date').val();
            const end_date = $('#end_date').val();
            
            $('#loading-tab3').removeClass('d-none');
            $('#debtor_search_iclaim_table').addClass('d-none');
            $('#empty-tab3').addClass('d-none');

            $.get("{{ url('debtor/1102050102_107_iclaim_ajax') }}", { start_date, end_date }, function(data) {
                $('#loading-tab3').addClass('d-none');
                $('#debtor_search_iclaim_table').removeClass('d-none');
                
                let html = '';
                let sum_inc = 0, sum_rcpt = 0, sum_other = 0, sum_debt = 0;
                
                data.forEach(row => {
                    sum_inc += parseFloat(row.income) || 0;
                    sum_rcpt += parseFloat(row.rcpt_money) || 0;
                    sum_other += parseFloat(row.other) || 0;
                    sum_debt += parseFloat(row.debtor) || 0;

                    html += `<tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_iclaim[]" value="${row.an}"></td>
                        <td>${row.ward || ''}</td>
                        <td class="text-center">${row.hn}</td>
                        <td class="text-center">${row.an}</td>
                        <td>${row.ptname}</td>
                        <td class="text-center">${row.age_y}</td>
                        <td>${row.pttype}</td>
                        <td class="text-center">${thaiDate(row.regdate)}</td>
                        <td class="text-center">${thaiDate(row.dchdate)}</td>
                        <td class="text-center">${row.pdx || ''}</td>
                        <td class="text-end">${parseFloat(row.adjrw || 0).toFixed(2)}</td>
                        <td class="text-end">${formatMoney(row.income)}</td>
                        <td class="text-end">${formatMoney(row.rcpt_money)}</td>
                        <td class="text-end">${formatMoney(row.other)}</td>
                        <td class="text-end text-primary fw-bold">${formatMoney(row.debtor)}</td>
                    </tr>`;
                });

                $('#debtor_search_iclaim_table tbody').html(html);
                $('#sum_ic_income').text(formatMoney(sum_inc));
                $('#sum_ic_rcpt').text(formatMoney(sum_rcpt));
                $('#sum_ic_other').text(formatMoney(sum_other));
                $('#sum_ic_debtor').text(formatMoney(sum_debt));

                if ($.fn.DataTable.isDataTable('#debtor_search_iclaim_table')) {
                    $('#debtor_search_iclaim_table').DataTable().destroy();
                }

                $('#debtor_search_iclaim_table').removeClass('d-none').DataTable({
                    destroy: true,
                    dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                    buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: '1102050102.107-ลูกหนี้ iClaim IP รอยืนยัน' }],
                    language: { search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ", paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } }
                });
                
                tab3Loaded = true;
            });
        }

        function formatMoney(val) {
            return parseFloat(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function thaiDate(dateStr) {
            if (!dateStr) return '';
            const months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
            const p = dateStr.split('-');
            if (p.length !== 3) return dateStr;
            return parseInt(p[2]) + " " + months[parseInt(p[1])] + " " + (parseInt(p[0]) + 543);
        }

        $(document).ready(function () {
            loadCounts();
            
            // Performance: Lazy load secondary tabs when clicked
            $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
                var targetId = $(e.target).attr("id");
                if (targetId === 'pay-tab') { loadTab2(); }
                else if (targetId === 'iclaim-tab') { loadTab3(); }
            });

            $('#debtor').DataTable({
                dom: '<"row mb-3"<"col-md-6"l>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',            
                language: { lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ", paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } }
            });
        });
    </script>
    <script id="single-modal-js">
    $(document).ready(function () {
        function initModalDatepicker(pickerId, hiddenId) {
            var $picker = $('#' + pickerId);
            if ($picker.length) {
                $picker.datepicker({
                    format: 'd M yyyy', autoclose: true, language: 'th-th', thaiyear: true, 
                    todayBtn: 'linked', todayHighlight: true, zIndexOffset: 1060
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
            balEl.css('color', raw < -0.05 ? 'red' : (raw > 0.05 ? 'green' : 'black'));
            
            function setPickerDate(pickerId, hiddenId, dateStr) {
                $(hiddenId).val(dateStr || '');
                if(dateStr && dateStr !== '0000-00-00') {
                    var p = dateStr.split('-');
                    if(p.length === 3) {
                        $(pickerId).val('');
                        $(pickerId).datepicker('setDate', new Date(p[0], p[1]-1, p[2]));
                    }
                } else { $(pickerId).val(''); }
            }

            setPickerDate('#modal_charge_date_picker', '#modal_charge_date', d.chargeDate);
            $('#modal_charge_no').val(d.chargeNo);
            $('#modal_charge').val(d.charge);
            $('#modal_status').val(d.status || 'ยืนยันลูกหนี้');
            setPickerDate('#modal_receive_date_picker', '#modal_receive_date', d.receiveDate);
            $('#modal_receive_no').val(d.receiveNo);
            $('#modal_receive').val(d.receive);
            $('#modal_repno').val(d.repno);
            $('#modal_adj_inc').val(d.adjInc || 0);
            $('#modal_adj_dec').val(d.adjDec || 0);
            setPickerDate('#modal_adj_date_picker', '#modal_adj_date', d.adjDate);
            $('#modal_adj_note').val(d.adjNote);
            
            var $modal = $('#debtorModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var myModal = bootstrap.Modal.getOrCreateInstance($modal[0]);
                myModal.show();
            } else if (typeof $.fn.modal === 'function') { $modal.modal('show'); }
        });
    });
    </script>
@endpush



