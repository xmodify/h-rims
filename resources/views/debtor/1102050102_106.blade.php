@extends('layouts.app')

@section('content')
    <!-- Page Header & Logic Filters -->
    <div class="page-header-box mt-2 mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center flex-wrap">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050102.106-ลูกหนี้ค่ารักษา ชําระเงิน OP
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050102_106') }}" enctype="multipart/form-data" class="m-0 d-flex flex-wrap align-items-center gap-2">
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
                        <span class="ms-2 fw-bold text-success" id="badge-tab1">{{ number_format($count_tab1) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pay-tab" data-bs-toggle="pill" data-bs-target="#pay-pane" type="button" role="tab" onclick="loadTab2()">
                        <i class="bi bi-check-circle me-1"></i> รอยืนยันลูกหนี้ ชำระเงิน OP
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
                    <form id="form-delete" action="{{ url('debtor/1102050102_106_delete') }}" method="POST" enctype="multipart/form-data">
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
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="openAdjModal()">
                                     <i class="bi bi-journal-text me-1"></i> ประวัติปรับปรุง
                                </button>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050102_106_indiv_excel')}}" target="_blank">
                                    <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050102_106_daily_pdf')}}" target="_blank">
                                    <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                                </a> 
                            </div>
                        </div>

                        <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-success">
                                <th class="text-left text-primary" colspan = "10">1102050102.106-ลูกหนี้ค่ารักษา ชําระเงิน OP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                                <th class="text-center text-primary" colspan = "10">การชดเชย</th>                                                 
                            </tr>
                            <tr class="table-success">
                                <th class="text-center" style="width: 70px; min-width: 70px; max-width: 70px;">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <input type="checkbox" onClick="toggle_d(this)"> <span>All</span>
                                    </div>
                                </th> 
                                <th class="text-center" width="6%" >วันที่</th>
                                <th class="text-center">HN</th> 
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">เบอร์โทร</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th> 
                                <th class="text-center">ต้องชำระ</th>  
                                <th class="text-center">ชำระเอง</th> 
                                <th class="text-center text-primary">ลูกหนี้</th>
                                <th class="text-center text-primary">ชดเชย</th> 
                                <th class="text-center text-primary">ปรับเพิ่ม</th>
                                <th class="text-center text-primary">ปรับลด</th>
                                <th class="text-center text-primary">ยอดคงเหลือ</th>
                                <th class="text-center text-primary">เลขที่ใบเสร็จ</th>    
                                <th class="text-center text-primary">อายุหนี้</th> 
                                <th class="text-center text-primary" style="width: 75px; min-width: 75px; max-width: 75px;">ติดตาม</th> 
                                <th class="text-center text-primary" style="width: 55px; min-width: 55px; max-width: 55px;" title="แก้ไข"><i class="bi bi-pencil-square" style="font-size: 1.1rem; vertical-align: middle;"></i></th>    
                                <th class="text-center text-primary" style="width: 55px; min-width: 55px; max-width: 55px;" title="ล็อค"><i class="bi bi-lock-fill" style="font-size: 1.1rem; vertical-align: middle;"></i></th>          
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_paid_money = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>              
                            <?php $sum_debtor = 0 ; ?>
                            <?php $sum_receive = 0 ; ?>
                            <?php $sum_adj_inc = 0 ; ?>
                            <?php $sum_adj_dec = 0 ; ?>
                            <?php $sum_balance = 0 ; ?>
                            @foreach($debtor as $row)
                            @php $balance = ($row->receive + ($row->adj_inc ?? 0) - ($row->adj_dec ?? 0)) - $row->debtor; @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>                    
                                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="center">{{ $row->mobile_phone_number }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="right">{{ $row->pdx }}</td>                      
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->paid_money,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>             
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                                <td align="right" @if($row->receive > 0) style="color:green" 
                                    @elseif($row->receive < 0) style="color:red" @endif>
                                    {{ number_format($row->receive,2) }}
                                </td>
                                <td align="right" style="color:blue">{{ number_format($row->adj_inc,2) }}</td>
                                <td align="right" style="color:red">{{ number_format($row->adj_dec,2) }}</td>
                                <td align="right" @if(($row->receive + $row->adj_inc - $row->adj_dec - $row->debtor) > 0) style="color:green"
                                    @elseif(($row->receive + $row->adj_inc - $row->adj_dec - $row->debtor) < 0) style="color:red" @endif>
                                    {{ number_format($row->receive + $row->adj_inc - $row->adj_dec - $row->debtor,2) }}
                                </td> 
                                <td align="center">{{ $row->repno ?? '' }} {{ $row->rcpno ?? '' }}</td>                  
                                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                                    {{ $row->days }} วัน
                                </td>   
                                <td align="center" data-order="{{ $row->visit ?? 0 }}" style="width: 75px; min-width: 75px; max-width: 75px;">
                                    <a href="javascript:void(0)" onclick="openTrackingModal('{{ $row->vn }}', '{{ $row->ptname }}', '{{ $row->hn }}', '{{ number_format($row->debtor, 2) }}')" class="text-decoration-none d-inline-flex gap-1 align-items-center" title="ติดตามแล้ว {{ $row->visit ?? 0 }} ครั้ง">
                                        @php $vCount = $row->visit ?? 0; @endphp
                                        @if($vCount == 0)
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                        @elseif($vCount == 1)
                                            <i class="bi bi-circle-fill text-success" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                        @elseif($vCount == 2)
                                            <i class="bi bi-circle-fill text-warning" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle-fill text-warning" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                                        @else
                                            <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                                            <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                                        @endif
                                    </a>
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
                                        data-update-url="{{ url('debtor/1102050102_106/update', $row->vn) }}">
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
                            <?php $count++; ?>
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_paid_money += $row->paid_money ; ?>    
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>          
                            <?php $sum_debtor += $row->debtor ; ?> 
                            <?php $sum_receive += $row->receive ; ?>       
                            <?php $sum_adj_inc += $row->adj_inc ; ?>
                            <?php $sum_adj_dec += $row->adj_dec ; ?>
                            <?php $sum_balance += ($row->receive + $row->adj_inc - $row->adj_dec - $row->debtor); ?>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td class="text-end">รวม</td><td></td><td></td><td></td><td></td><td></td><td></td>
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
                                    <td></td><td></td><td></td><td></td><td></td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div> 

                <!-- Tab 2: ชำระเงิน OP -->
                <div class="tab-pane fade" id="pay-pane" role="tabpanel">
                    <form id="form-confirm" action="{{ url('debtor/1102050102_106_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf                
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                            <div></div>
                        </div>

                        <div class="table-responsive">
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
                            <table id="debtor_search" class="table table-bordered table-striped my-3 d-none" width="100%">
                                <thead>
                                <tr class="table-secondary">
                                    <th class="text-left text-primary" colspan = "14">ผู้มารับบริการรอชําระเงิน OP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                         
                                </tr>
                                <tr class="table-secondary">
                                    <th class="text-center" style="width: 70px; min-width: 70px; max-width: 70px;">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="checkbox" onClick="toggle(this)"> <span>All</span>
                                        </div>
                                    </th>   
                                    <th class="text-center">วันที่</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">ชื่อ-สกุล</th>
                                    <th class="text-center">เบอร์โทร</th>
                                    <th class="text-center">สิทธิ</th>
                                    <th class="text-center">ICD10</th>
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
                                </tbody>
                                <tfoot>
                                    <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                        <td class="text-end">รวม</td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td id="sum_income_search" class="text-end">0.00</td>
                                        <td id="sum_paid_money_search" class="text-end">0.00</td>
                                        <td id="sum_rcpt_money_search" class="text-end">0.00</td>
                                        <td id="sum_debtor_search" class="text-end" style="color:blue">0.00</td>
                                        <td id="sum_arrear" class="text-end">0.00</td>
                                        <td id="sum_deposit" class="text-end">0.00</td>
                                        <td id="sum_debit" class="text-end">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div> 

                <!-- Tab 3: iClaim -->
                <div class="tab-pane fade" id="iclaim-pane" role="tabpanel">
                    <form id="form-confirm-iclaim" action="{{ url('debtor/1102050102_106_confirm_iclaim') }}" method="POST" enctype="multipart/form-data"> 
                        @csrf                
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit_iclaim()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                            <div></div>
                        </div>

                        <div class="table-responsive">
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
                            <table id="debtor_search_iclaim_table" class="table table-bordered table-striped my-3 d-none" width="100%">
                                <thead>
                                <tr class="table-secondary">
                                    <th class="text-left text-primary" colspan = "11">ผู้มารับบริการใช้ประกันชีวิต iClaim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>      
                                </tr>
                                <tr class="table-secondary">
                                    <th class="text-center" style="width: 70px; min-width: 70px; max-width: 70px;">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <input type="checkbox" onClick="toggle_iclaim(this)"> <span>All</span>
                                        </div>
                                    </th>  
                                    <th class="text-center" width="6%">วันที่</th>
                                    <th class="text-center">Queue</th>
                                    <th class="text-center">HN</th>
                                    <th class="text-center">ชื่อ-สกุล</th>
                                    <th class="text-center">สิทธิ</th>
                                    <th class="text-center">ICD10</th>
                                    <th class="text-center">ค่ารักษาทั้งหมด</th>  
                                    <th class="text-center">ชำระเอง</th> 
                                    <th class="text-center">กองทุนอื่น</th> 
                                    <th class="text-center">ลูกหนี้</th>  
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                        <td class="text-end">รวม</td><td></td><td></td><td></td><td></td><td></td><td></td>
                                        <td id="sum_income_iclaim" class="text-end">0.00</td>
                                        <td id="sum_rcpt_money_iclaim" class="text-end">0.00</td>
                                        <td id="sum_other_iclaim" class="text-end">0.00</td>
                                        <td id="sum_debtor_iclaim" class="text-end" style="color:blue">0.00</td>
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
        Swal.fire({ title: 'กำลังโหลด...', text: 'โปรดรอซักครู่', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
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
                    url: "{{ url('debtor/1102050102_106_delete') }}",
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
        if ($.fn.DataTable.isDataTable('#debtor_search')) {
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
                    url: "{{ url('debtor/1102050102_106_confirm') }}",
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
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050102_106/lock') }}/" + id;
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
                let f = document.createElement('form'); f.method = 'POST'; f.action = "{{ url('debtor/1102050102_106/unlock') }}/" + id;
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
                    <div class="mb-3"><label class="form-label small fw-bold">หมายเหตุการปรับปรุง</label><input type="text" id="blk_note" class="form-control rounded-pill" value="ปรับปรุงยอดเป็น 0"></div>
                    <div class="mb-3"><label class="form-label small fw-bold">วันที่ปรับปรุง</label><input type="text" id="blk_date_th" class="form-control rounded-pill datepicker_th" value="{{DateThai(date('Y-m-d'))}}" readonly><input type="hidden" id="blk_date" value="{{date('Y-m-d')}}"></div>
                </div>
            `,
            icon: 'info', showCancelButton: true, confirmButtonColor: '#ffc107', confirmButtonText: 'ยืนยัน',
            didOpen: () => { $('#blk_date_th').datepicker({ format: 'd M yyyy', autoclose: true, language: 'th-th', thaiyear: true, todayBtn: 'linked', todayHighlight: true }).on('changeDate', (e) => { if (e.date) { const y = e.date.getFullYear(), m=('0'+(e.date.getMonth()+1)).slice(-2), d=('0'+e.date.getDate()).slice(-2); $('#blk_date').val(y+'-'+m+'-'+d); } }); },
            preConfirm: () => { return { note: $('#blk_note').val(), date: $('#blk_date') .val() } }
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
                            <div id="adj-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning text-dark fw-bold" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div id="adj-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        sendNextAdjChunk();
                    }
                });

                function sendNextAdjChunk() {
                    if (currentChunkIndex >= chunks.length) {
                        Swal.fire({
                            title: 'สำเร็จ!',
                            text: `ปรับปรุงยอดจำนวน ${totalAdjusted} รายการเรียบร้อยแล้ว`,
                            icon: 'success',
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            location.reload();
                        });
                        return;
                    }

                    const chunk = chunks[currentChunkIndex];

                    $.ajax({
                        url: "{{ url('debtor/1102050102_106_bulk_adj') }}",
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
                                progressBar.setAttribute('aria-valuenow', percent);
                                progressBar.innerText = percent + '%';
                            }
                            if (progressText) {
                                progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                            }

                            sendNextAdjChunk();
                        },
                        error: function(xhr) {
                            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปรับปรุงยอดบางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
                        }
                    });
                }
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

<!-- History Adjustment Modal -->
<div id="adjLogModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-dark border-0 py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-journal-text me-2"></i> ประวัติการปรับปรุงยอดลูกหนี้ 1102050102.106-ลูกหนี้ค่ารักษา ชําระเงิน OP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <!-- ส่วนตัวกรองช่วงวันที่ค้นหาประวัติ -->
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

                <!-- ตารางประวัติปรับยอด -->
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
                            <!-- ข้อมูลจะถูกดึงเข้าแบบ AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tracking Modal -->
<div id="trackingModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-telephone-outbound-fill me-2"></i> รายละเอียดการติดตามลูกหนี้ค่ารักษา ชําระเงิน OP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <!-- Patient Info Header -->
                <div class="card border-0 bg-light-soft mb-2">
                    <div class="card-body py-2 px-4">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div><span class="text-muted small">ชื่อ-สกุล:</span> <strong id="track_modal_ptname" class="text-primary">-</strong></div>
                                <div class="mt-1">
                                    <span class="text-muted small">เลขบัตรประชาชน:</span> <strong id="track_modal_cid" class="small">-</strong> 
                                    <span class="text-muted small ms-2">HN:</span> <strong id="track_modal_hn" class="small">-</strong>
                                </div>
                                <div class="mt-1"><span class="text-muted small">เบอร์โทร:</span> <strong id="track_modal_phone" class="small">-</strong></div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div>
                                    <span class="text-muted small">วันที่รับบริการ:</span> <strong id="track_modal_vstdate" class="small">-</strong> 
                                    <span class="text-muted small ms-2">เวลา:</span> <strong id="track_modal_vsttime" class="small">-</strong>
                                </div>
                                <div class="mt-1"><span class="text-muted small">สิทธิการรักษา:</span> <strong id="track_modal_pttype" class="small">-</strong></div>
                                <div class="mt-1"><span class="text-muted small">ลูกหนี้ค่ารักษา:</span> <strong class="text-danger fw-bold fs-5"><span id="track_modal_debtor">-</span> บาท</strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Section -->
                <div id="tracking_history_section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold small text-dark"><i class="bi bi-clock-history me-1"></i> ประวัติการติดตาม</div>
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" id="btn_new_tracking">
                            <i class="bi bi-plus-circle me-1"></i> บันทึกติดตาม
                        </button>
                    </div>
                    <div class="table-responsive mb-3" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-sm table-hover small" id="track_history_table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center">ครั้งที่</th>
                                    <th class="text-center">วันที่ติดตาม</th>
                                    <th class="text-center">การติดตาม</th>
                                    <th class="text-center">เลขที่หนังสือ</th>
                                    <th>เจ้าหน้าที่</th>
                                    <th>หมายเหตุ</th>
                                    <th class="text-center" style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Insert/Edit Form Section -->
                <div id="tracking_form_section" style="display: none;">
                    <div class="fw-bold mb-2 small text-success" id="tracking_form_title"><i class="bi bi-plus-circle-fill me-1"></i> บันทึกข้อมูลการติดตามครั้งใหม่</div>
                    <form action="{{ url('debtor/1102050102_106/tracking_insert') }}" method="POST" id="track_insert_form">
                        @csrf
                        <input type="hidden" name="_method" id="track_form_method" value="POST">
                        <input type="hidden" name="vn" id="track_modal_vn_hidden">
                        <div class="row g-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">วันที่ติดตาม</label>
                                <input type="hidden" name="tracking_date" id="track_date" value="{{ date('Y-m-d') }}">
                                <input type="text" id="track_date_picker" class="form-control form-control-sm rounded-pill datepicker_th" value="{{ DateThai(date('Y-m-d')) }}" readonly required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">การติดตาม</label>
                                <select class="form-select form-select-sm rounded-pill" name="tracking_type" required>
                                    <option value="โทรศัพท์">โทรศัพท์</option>
                                    <option value="ส่งเอกสาร">ส่งเอกสาร</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">เลขที่หนังสือ</label>
                                <input type="text" class="form-control form-control-sm rounded-pill" name="tracking_no" placeholder="เช่น นร 0023.2/...">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small fw-bold">เจ้าหน้าที่ผู้ติดต่อ</label>
                                <input type="text" class="form-control form-control-sm rounded-pill" name="tracking_officer" value="{{ Auth::user()->name ?? '' }}" required>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label small fw-bold">หมายเหตุ</label>
                                <input type="text" class="form-control form-control-sm rounded-pill" name="tracking_note" placeholder="ผลการติดต่อ หรือ รายละเอียดเพิ่มเติม">
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0 p-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-4" id="btn_cancel_tracking">ยกเลิก</button>
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> บันทึกการติดตาม
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
    // Keep track of current logs globally in view context
    window.currentTrackingLogs = [];

    window.openTrackingModal = function(vn, ptname, hn, debtorAmt) {
        // Reset modal view states
        $('#tracking_history_section').show();
        $('#tracking_form_section').hide();
        
        $('#track_modal_vn_hidden').val(vn);
        $('#track_modal_ptname').text(ptname);
        $('#track_modal_hn').text(hn);
        $('#track_modal_debtor').text(debtorAmt);
        
        loadTrackingHistory(vn);
        $('#trackingModal').modal('show');
    };

    function loadTrackingHistory(vn) {
        $('#track_history_table tbody').html(`
            <tr>
                <td colspan="7" class="text-center p-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="text-muted small ms-2">กำลังดึงประวัติการติดตาม...</span>
                </td>
            </tr>
        `);

        // Load dynamic tracking data
        $.ajax({
            url: `{{ url('debtor/1102050102_106/tracking') }}/${vn}`,
            type: 'GET',
            success: function(res) {
                if (res.debtor) {
                    $('#track_modal_cid').text(res.debtor.cid || '-');
                    $('#track_modal_phone').text(res.debtor.mobile_phone_number || '-');
                    $('#track_modal_vstdate').text(formatThaiDate(res.debtor.vstdate));
                    $('#track_modal_vsttime').text(res.debtor.vsttime || '-');
                    $('#track_modal_pttype').text(res.debtor.pttype || '-');
                    
                    const debtorAmt = parseFloat(res.debtor.debtor || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    $('#track_modal_debtor').text(debtorAmt);
                }
                
                window.currentTrackingLogs = res.tracking || [];
                updateMainTableRowTracking(vn, window.currentTrackingLogs.length);
                
                if (res.tracking && res.tracking.length > 0) {
                    let html = '';
                    res.tracking.forEach((t, i) => {
                        html += `
                            <tr>
                                <td class="text-center">${i + 1}</td>
                                <td class="text-center">${formatThaiDate(t.tracking_date)}</td>
                                <td class="text-center">
                                    <span class="badge ${t.tracking_type === 'โทรศัพท์' ? 'bg-success-soft text-success' : 'bg-primary-soft text-primary'} py-1 px-2">
                                        <i class="bi ${t.tracking_type === 'โทรศัพท์' ? 'bi-telephone-fill' : 'bi-envelope-paper-fill'} me-1"></i> ${t.tracking_type}
                                    </span>
                                </td>
                                <td class="text-center">${t.tracking_no || '-'}</td>
                                <td>${t.tracking_officer || ''}</td>
                                <td>${t.tracking_note || ''}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        ${t.tracking_type === 'ส่งเอกสาร' ? `
                                            <a href="{{ url('debtor/1102050102_106/tracking_print') }}/${t.tracking_id}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1 border-0" title="พิมพ์หนังสือทวงหนี้">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        ` : ''}
                                        <button type="button" class="btn btn-sm btn-outline-warning py-0 px-1 border-0" onclick="editTrackingLog(${i})" title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 border-0" onclick="deleteTrackingLog(${i})" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#track_history_table tbody').html(html);
                } else {
                    $('#track_history_table tbody').html(`
                        <tr>
                            <td colspan="7" class="text-center p-3 text-muted">ยังไม่มีประวัติการติดตามสำหรับเคสนี้</td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#track_history_table tbody').html(`
                    <tr>
                        <td colspan="7" class="text-center p-3 text-danger">เกิดข้อผิดพลาดในการดึงประวัติ</td>
                    </tr>
                `);
            }
        });
    }

    function updateMainTableRowTracking(vn, visitCount) {
        const rowCheckbox = $(`input[name="checkbox_d[]"][value="${vn}"]`);
        if (rowCheckbox.length) {
            const row = rowCheckbox.closest('tr');
            const cell = row.find('td').eq(17);
            
            let dotsHtml = '';
            if (visitCount == 0) {
                dotsHtml = `
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                `;
            } else if (visitCount == 1) {
                dotsHtml = `
                    <i class="bi bi-circle-fill text-success" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                `;
            } else if (visitCount == 2) {
                dotsHtml = `
                    <i class="bi bi-circle-fill text-warning" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle-fill text-warning" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle text-muted" style="font-size: 0.85rem;"></i>
                `;
            } else {
                dotsHtml = `
                    <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                    <i class="bi bi-circle-fill text-danger" style="font-size: 0.85rem;"></i>
                `;
            }
            
            cell.attr('data-order', visitCount);
            const anchor = cell.find('a');
            anchor.attr('title', `ติดตามแล้ว ${visitCount} ครั้ง`);
            anchor.html(dotsHtml);
            
            if ($.fn.DataTable.isDataTable('#debtor')) {
                const table = $('#debtor').DataTable();
                table.cell(cell).invalidate();
            }
        }
    }

    window.editTrackingLog = function(index) {
        if (!window.currentTrackingLogs || !window.currentTrackingLogs[index]) return;
        const log = window.currentTrackingLogs[index];
        
        // Set form action & method
        $('#track_insert_form').attr('action', `{{ url('debtor/1102050102_106/tracking_update') }}/${log.tracking_id}`);
        $('#track_form_method').val('PUT');
        
        // Populate values
        $('#track_date').val(log.tracking_date);
        if (log.tracking_date) {
            const dateParts = log.tracking_date.split('-');
            if (dateParts.length === 3) {
                $('#track_date_picker').datepicker('setDate', new Date(dateParts[0], dateParts[1] - 1, dateParts[2]));
            }
        }
        
        $('select[name="tracking_type"]').val(log.tracking_type);
        $('input[name="tracking_no"]').val(log.tracking_no || '');
        $('input[name="tracking_officer"]').val(log.tracking_officer || '');
        $('input[name="tracking_note"]').val(log.tracking_note || '');
        
        $('#tracking_form_title').html('<i class="bi bi-pencil-square me-1"></i> แก้ไขข้อมูลการติดตาม');
        
        // Switch section
        $('#tracking_history_section').hide();
        $('#tracking_form_section').show();
    };

    window.deleteTrackingLog = function(index) {
        if (!window.currentTrackingLogs || !window.currentTrackingLogs[index]) return;
        const log = window.currentTrackingLogs[index];
        
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบข้อมูลการติดตามรายการนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ต้องการลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('debtor/1102050102_106/tracking_delete') }}/${log.tracking_id}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบสำเร็จ',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            const vn = $('#track_modal_vn_hidden').val();
                            loadTrackingHistory(vn);
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
                    }
                });
            }
        });
    };

    $(document).ready(function() {
        $('#track_date_picker').on('changeDate', function(e) {
            if (e.date) {
                const y = e.date.getFullYear(), m = ('0' + (e.date.getMonth() + 1)).slice(-2), d = ('0' + e.date.getDate()).slice(-2);
                $('#track_date').val(y + '-' + m + '-' + d);
            }
        });

        // Toggle to new tracking form
        $('#btn_new_tracking').on('click', function() {
            // Reset to Insert mode
            $('#track_insert_form').attr('action', `{{ url('debtor/1102050102_106/tracking_insert') }}`);
            $('#track_form_method').val('POST');
            
            const today = new Date();
            const y = today.getFullYear(), m = ('0' + (today.getMonth() + 1)).slice(-2), d = ('0' + today.getDate()).slice(-2);
            $('#track_date').val(y + '-' + m + '-' + d);
            $('#track_date_picker').datepicker('setDate', today);
            
            $('select[name="tracking_type"]').val('โทรศัพท์');
            $('input[name="tracking_no"]').val('');
            $('input[name="tracking_officer"]').val('{{ Auth::user()->name ?? "" }}');
            $('input[name="tracking_note"]').val('');
            
            $('#tracking_form_title').html('<i class="bi bi-plus-circle-fill me-1"></i> บันทึกข้อมูลการติดตามครั้งใหม่');
            
            $('#tracking_history_section').hide();
            $('#tracking_form_section').show();
        });

        // Cancel and show history
        $('#btn_cancel_tracking').on('click', function() {
            $('#tracking_form_section').hide();
            $('#tracking_history_section').show();
        });

        // Submit via AJAX
        $('#track_insert_form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            const data = form.serialize();
            
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        const vn = $('#track_modal_vn_hidden').val();
                        loadTrackingHistory(vn);
                        
                        $('#tracking_form_section').hide();
                        $('#tracking_history_section').show();
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
                    }
                },
                error: function() {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
                }
            });
        });
    });

    window.openAdjModal = function() {
        $('#adjLogModal').modal('show');
        updateAdjPdfUrl();
        loadAdjLogs();
    }

    window.updateAdjPdfUrl = function() {
        const start = $('#adj_start_date').val();
        const end = $('#adj_end_date').val();
        const url = `{{ url('debtor/adjust_log/1102050102_106') }}?start_date=${start}&end_date=${end}&export_type=pdf`;
        $('#btn-adj-print-pdf').attr('href', url);
    }

    window.loadAdjLogs = function() {
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
            url: `{{ url('debtor/adjust_log/1102050102_106') }}`,
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
                                <td class="text-center" style="white-space: nowrap;">${formatThaiDate(row.adj_date)}</td>
                                <td class="text-center" style="white-space: nowrap;">${formatThaiDate(row.vstdate)}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="text-start">${row.ptname}</td>
                                <td class="text-end">${parseFloat(row.debtor || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end">${parseFloat(row.receive || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end fw-bold" style="color: purple;">${parseFloat(row.adj_inc || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end fw-bold" style="color: blue;">${parseFloat(row.adj_dec || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
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

    // Initialize adjustment datepickers
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
    if ($.fn.DataTable.isDataTable('#debtor_search')) {
        let table = $('#debtor_search').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox[]"]').prop('checked', source.checked);
    }
};

window.toggle_iclaim = function(source) {
    if ($.fn.DataTable.isDataTable('#debtor_search_iclaim_table')) {
        let table = $('#debtor_search_iclaim_table').DataTable();
        let rows = table.rows({ page: 'current' }).nodes();
        $(rows).find('input[name="checkbox_iclaim[]"]').prop('checked', source.checked);
    } else {
        $('input[name="checkbox_iclaim[]"]').prop('checked', source.checked);
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
                // Clear existing value to prevent 'แวบ ๆ' (briefly showing BE then AD)
                $(pickerId).val(''); 
                $(pickerId).datepicker('setDate', new Date(parts[0], parts[1]-1, parts[2]));
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
            lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
            columnDefs: [
                { orderable: false, targets: [0, 18] }
            ],
            language: {
                lengthMenu: 'แสดง _MENU_ รายการ',
                info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ',
                paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' }
            }
        });
    }

    // Auto-load background data immediately (Eager Background Load)
    loadTab2();
    loadTab3();
    
    // Performance: Tab click still works but data will be already loaded
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
        var targetId = $(e.target).attr("id");
        if (targetId === 'pay-tab') { if(!tab2Loaded) loadTab2(); }
        else if (targetId === 'iclaim-tab') { if(!tab3Loaded) loadTab3(); }
    });
});

function loadInitialCounts() {
    // OBSOLETE: Replaced by eager load of full data
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
        balEl.text(d.balance).removeClass('text-success text-danger');
        if(parseFloat(d.balanceRaw) > 0.05) balEl.addClass('text-success');
        else if(parseFloat(d.balanceRaw) < -0.05) balEl.addClass('text-danger');

        $('#modal_charge_date').val(d.chargeDate);
        $('#modal_charge_date_picker').val(d.chargeDateTh);
        $('#modal_charge_no').val(d.chargeNo);
        $('#modal_charge').val(d.charge);
        $('#modal_status').val(d.status);
        $('#modal_receive_date').val(d.receiveDate);
        $('#modal_receive_date_picker').val(d.receiveDateTh);
        $('#modal_receive_no').val(d.receiveNo);
        $('#modal_receive').val(d.receive);
        $('#modal_repno').val(d.repno);
        $('#modal_adj_inc').val(d.adjInc);
        $('#modal_adj_dec').val(d.adjDec);
        $('#modal_adj_date').val(d.adjDate);
        $('#modal_adj_date_picker').val(d.adjDateTh);
        $('#modal_adj_note').val(d.adjNote);
        
        $('#debtorModal').modal('show');
    });
});

let tab2Loaded = false;
let tab3Loaded = false;
function loadTab2() {
    if (tab2Loaded) return;
    $('#empty-tab2').addClass('d-none');
    $('#loading-tab2').removeClass('d-none');
    
    // Set flag early to prevent double requests
    tab2Loaded = true;

    $.ajax({
        url: "{{ url('debtor/1102050102_106_search_ajax') }}",
        data: { start_date: $('#start_date').val(), end_date: $('#end_date').val() },
        success: function(res) {
            $('#loading-tab2').addClass('d-none');
            $('#debtor_search').removeClass('d-none');
            $('#badge-tab2').text(res.length);
            $('#loading-tab2').addClass('d-none');
            $('#debtor_search').removeClass('d-none');
            $('#badge-tab2').text(res.length);
            
            let html = '';
            let s_inc = 0, s_paid = 0, s_rcpt = 0, s_debt = 0, s_arr = 0, s_dep = 0, s_deb = 0;
            res.forEach(r => {
                let d = r.paid_money - r.rcpt_money;
                html += `<tr>
                    <td class="text-center"><input type="checkbox" name="checkbox[]" value="${r.vn}"></td>
                    <td align="right">${thaiDate(r.vstdate)} ${r.vsttime}</td>
                    <td align="center">${r.hn}</td>
                    <td align="left">${r.ptname}</td>
                    <td align="center">${r.mobile_phone_number || ''}</td>
                    <td align="left">${r.pttype}</td>
                    <td align="right">${r.pdx || ''}</td>
                    <td align="right">${formatMoney(r.income)}</td>
                    <td align="right">${formatMoney(r.paid_money)}</td>
                    <td align="right">${formatMoney(r.rcpt_money)}</td>
                    <td align="right">${formatMoney(d)}</td>
                    <td align="right">${formatMoney(r.arrear_amount)}</td>
                    <td align="right">${formatMoney(r.deposit_amount)}</td>
                    <td align="right">${formatMoney(r.debit_amount)}</td>
                </tr>`;
                s_inc += parseFloat(r.income || 0);
                s_paid += parseFloat(r.paid_money || 0);
                s_rcpt += parseFloat(r.rcpt_money || 0);
                s_debt += d;
                s_arr += parseFloat(r.arrear_amount || 0);
                s_dep += parseFloat(r.deposit_amount || 0);
                s_deb += parseFloat(r.debit_amount || 0);
            });
            $('#debtor_search tbody').html(html);
            $('#sum_income_search').text(formatMoney(s_inc));
            $('#sum_paid_money_search').text(formatMoney(s_paid));
            $('#sum_rcpt_money_search').text(formatMoney(s_rcpt));
            $('#sum_debtor_search').text(formatMoney(s_debt));
            $('#sum_arrear').text(formatMoney(s_arr));
            $('#sum_deposit').text(formatMoney(s_dep));
            $('#sum_debit').text(formatMoney(s_deb));

            $('#debtor_search').DataTable({
                destroy: true,
                dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: 'รอยืนยืนลูกหนี้ ชำระเงิน OP' }],
                lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
                columnDefs: [
                    { orderable: false, targets: 0 }
                ],
                language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
            });
        },
        error: function() { 
            tab2Loaded = false;
            $('#loading-tab2').addClass('d-none'); 
            $('#empty-tab2').removeClass('d-none');
            $('#badge-tab2').text('!').addClass('bg-danger text-white');
            Swal.fire('Error', 'ไม่สามารถดึงข้อมูลได้ โปรดลองอีกครั้ง', 'error'); 
        }
    });
}

function loadTab3() {
    if (tab3Loaded) return;
    $('#empty-tab3').addClass('d-none');
    $('#loading-tab3').removeClass('d-none');
    tab3Loaded = true;

    $.ajax({
        url: "{{ url('debtor/1102050102_106_iclaim_ajax') }}",
        data: { start_date: $('#start_date').val(), end_date: $('#end_date').val() },
        success: function(res) {
            $('#loading-tab3').addClass('d-none');
            $('#debtor_search_iclaim_table').removeClass('d-none');
            $('#badge-tab3').text(res.length);

            let html = '';
            let s_inc = 0, s_rcpt = 0, s_oth = 0, s_debt = 0;
            res.forEach(r => {
                html += `<tr>
                    <td class="text-center"><input type="checkbox" name="checkbox_iclaim[]" value="${r.vn}"></td>
                    <td align="center">${thaiDate(r.vstdate)} ${r.vsttime}</td>
                    <td align="center">${r.oqueue}</td>
                    <td align="center">${r.hn}</td>
                    <td align="left">${r.ptname}</td>
                    <td align="left">${r.pttype}</td>
                    <td align="right">${r.pdx || ''}</td>
                    <td align="right">${formatMoney(r.income)}</td>
                    <td align="right">${formatMoney(r.rcpt_money)}</td>
                    <td align="right">${formatMoney(r.other)}</td>
                    <td align="right">${formatMoney(r.debtor)}</td>
                </tr>`;
                s_inc += parseFloat(r.income || 0);
                s_rcpt += parseFloat(r.rcpt_money || 0);
                s_oth += parseFloat(r.other || 0);
                s_debt += parseFloat(r.debtor || 0);
            });
            $('#debtor_search_iclaim_table tbody').html(html);
            $('#sum_income_iclaim').text(formatMoney(s_inc));
            $('#sum_rcpt_money_iclaim').text(formatMoney(s_rcpt));
            $('#sum_other_iclaim').text(formatMoney(s_oth));
            $('#sum_debtor_iclaim').text(formatMoney(s_debt));

            $('#debtor_search_iclaim_table').DataTable({
                destroy: true,
                dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
                buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm', title: 'รอยืนยืนลูกหนี้ iClaim' }],
                lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "ทั้งหมด"]],
                columnDefs: [
                    { orderable: false, targets: 0 }
                ],
                language: { search: 'ค้นหา:', lengthMenu: 'แสดง _MENU_ รายการ', info: 'แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ', paginate: { previous: 'ก่อนหน้า', next: 'ถัดไป' } }
            });
        },
        error: function() { 
            tab3Loaded = false;
            $('#loading-tab3').addClass('d-none'); 
            $('#empty-tab3').removeClass('d-none');
            Swal.fire('Error', 'ไม่สามารถดึงข้อมูลได้ โปรดลองอีกครั้ง', 'error'); 
        }
    });
}

function formatMoney(n) { return parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

function thaiDate(dateStr) {
    if(!dateStr) return '';
    const months = ["ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
    const d = new Date(dateStr);
    return d.getDate() + ' ' + months[d.getMonth()] + ' ' + (d.getFullYear() + 543);
}

function confirmSubmit_iclaim() {
    let selected = [];
    if ($.fn.DataTable.isDataTable('#debtor_search_iclaim_table')) {
        let table = $('#debtor_search_iclaim_table').DataTable();
        let cells = table.cells().nodes();
        $(cells).find('input[name="checkbox_iclaim[]"]:checked').each(function() {
            selected.push($(this).val());
        });
    } else {
        selected = [...document.querySelectorAll('input[name="checkbox_iclaim[]"]:checked')].map(e => e.value);
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
            title: 'กำลังยืนยันลูกหนี้ iClaim...',
            html: `
                <div class="progress mb-2" style="height: 25px;">
                    <div id="iclaim-confirm-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="iclaim-confirm-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                sendNextIclaimChunk();
            }
        });
        
        function sendNextIclaimChunk() {
            if (currentChunkIndex >= chunks.length) {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: `ยืนยันลูกหนี้ iClaim จำนวน ${total} เรียบร้อยแล้ว`,
                    icon: 'success',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    location.reload();
                });
                return;
            }
            
            const chunk = chunks[currentChunkIndex];
            
            $.ajax({
                url: "{{ url('debtor/1102050102_106_confirm_iclaim') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    checkbox_iclaim: chunk
                },
                success: function(res) {
                    currentChunkIndex++;
                    const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                    const percent = Math.round((processedCount / total) * 100);
                    
                    const progressBar = document.getElementById('iclaim-confirm-progress-bar');
                    const progressText = document.getElementById('iclaim-confirm-progress-text');
                    if (progressBar) {
                        progressBar.style.width = percent + '%';
                        progressBar.setAttribute('aria-valuenow', percent);
                        progressBar.innerText = percent + '%';
                    }
                    if (progressText) {
                        progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                    }
                    
                    sendNextIclaimChunk();
                },
                error: function(xhr) {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถยืนยันลูกหนี้บางรายการได้ กรุณาลองใหม่อีกครั้ง', 'error');
                }
            });
        }
    } });
}
</script>
@endpush
