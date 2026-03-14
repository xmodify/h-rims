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
                            </div>
                            <div>
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
                                <th class="text-center" width="5%">Action</th>
                                <th class="text-center">LOCK</th>                                       
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
                                <td align="center">         
                                    <button type="button" class="btn btn-warning btn-sm px-2 shadow-sm text-dark" data-bs-toggle="modal" data-bs-target="#receive-{{ str_replace('/', '-', $row->vn) }}"> 
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
                                $s_inc+=$row->income; $s_rcp+=$row->rcpt_money;
                                $s_deb+=$row->debtor; $s_rec+=$row->receive; 
                                $s_adj_inc+=$row->adj_inc; $s_adj_dec+=$row->adj_dec;
                                $s_balance+=$balance;
                            @endphp
                            @endforeach 
                            <tfoot>                        
                                <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td>{{ number_format($s_inc,2) }}</td>
                                    <td>{{ number_format($s_rcp,2) }}</td>
                                    <td style="color:blue">{{ number_format($s_deb,2) }}</td>
                                    <td style="color:green">{{ number_format($s_rec,2) }}</td>
                                    <td style="color: #9c27b0;">{{ number_format($s_adj_inc,2) }}</td>
                                    <td style="color: #673ab7;">{{ number_format($s_adj_dec,2) }}</td>
                                    <td style="color:@if($s_balance < -0.01) red @elseif($s_balance > 0.01) green @else black @endif">{{ number_format($s_balance, 2) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div>
                
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel"> 
                    <form id="form-confirm" action="{{ url('debtor/1102050101_103_confirm') }}" method="POST">
                        @csrf
                        <div class="mb-2 mt-3">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                        </div>                
                        <div class="table-responsive"><table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-secondary">
                                <th class="text-left text-primary" colspan="9">
                                    1102050101.103-ลูกหนี้ค่าตรวจสุขภาพ หน่วยงานภาครัฐ รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} 
                                    (จำนวน: {{ count($debtor_search) }} รายการ)
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
                            @php 
                                $sum_income_search = 0;
                                $sum_rcpt_money_search = 0;
                                $sum_debtor_search = 0;
                            @endphp
                            @foreach($debtor_search as $row)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->vn}}"></td> 
                                <td align="center">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }}</td>
                                <td align="center">{{ $row->pdx }}</td>
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->debtor,2) }}</td>
                            </tr>
                            @php 
                                $sum_income_search += $row->income;
                                $sum_rcpt_money_search += $row->rcpt_money;
                                $sum_debtor_search += $row->debtor;
                            @endphp
                            @endforeach 
                            <tfoot>
                                <tr class="table-success text-end fw-bold" style="font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td>{{ number_format($sum_income_search, 2) }}</td>
                                    <td>{{ number_format($sum_rcpt_money_search, 2) }}</td>
                                    <td style="color:blue">{{ number_format($sum_debtor_search, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @foreach($debtor as $row)
        @php
            $balance = ($row->receive + $row->adj_inc - $row->adj_dec) - $row->debtor;
        @endphp
        <div id="receive-{{ str_replace('/', '-', $row->vn) }}" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white border-0 py-3">
                        <h5 class="modal-title d-flex align-items-center"><i class="bi bi-cash-stack me-2"></i> รายการการชดเชยเงิน/ลูกหนี้ (VN: {{ $row->vn }})</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>         
                    <form action="{{ url('debtor/1102050101_103/update', $row->vn) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-body p-3 text-start">
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <div class="p-2 rounded-3 bg-primary-soft mb-1">
                                        <div class="row align-items-center">
                                            <div class="col-md-7"><label class="text-muted small d-block">ชื่อ-สกุล</label><span class="fw-bold text-primary fs-6">{{ $row->ptname }}</span></div>
                                            <div class="col-md-5 text-md-end"><label class="text-muted small d-block">ส่วนต่างลูกหนี้คงเหลือ</label><span class="fw-bold fs-6" style="color:@if($balance < -0.01) red @elseif($balance > 0.01) green @else black @endif">{{ number_format($balance, 2) }} บาท</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 border-end">
                                    <h6 class="text-secondary fw-bold mb-2 d-flex align-items-center small"><i class="bi bi-send-fill me-2 text-primary"></i> ข้อมูลการส่งเบิก (Charge)</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">วันที่เรียกเก็บ</label>
                                            <input type="hidden" name="charge_date" id="charge_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ $row->charge_date }}">
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3 datepicker_th" data-hidden-id="charge_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ !empty($row->charge_date) ? DateThai($row->charge_date) : '' }}" placeholder="วว/ดด/ปปปป" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">เลขที่หนังสือ</label>
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3" name="charge_no" value="{{ $row->charge_no }}" placeholder="ระบุเลขที่หนังสือ">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">จำนวนเงินเรียกเก็บ</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="charge" value="{{ $row->charge }}">
                                                <span class="input-group-text rounded-pill-end small bg-light">บาท</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">สถานะลูกหนี้</label>
                                            <select class="form-select form-select-sm rounded-pill px-3" name="status">                                                       
                                                <option value="ยืนยันลูกหนี้" @if($row->status == 'ยืนยันลูกหนี้') selected @endif>ยืนยันลูกหนี้</option>                                           
                                                <option value="อยู่ระหว่างเรียกเก็บ" @if($row->status == 'อยู่ระหว่างเรียกเก็บ') selected @endif>อยู่ระหว่างเรียกเก็บ</option> 
                                                <option value="อยู่ระหว่างการขออุทธรณ์" @if($row->status == 'อยู่ระหว่างการขออุทธรณ์') selected @endif>อยู่ระหว่างการขออุทธรณ์</option>
                                                <option value="กระทบยอดแล้ว" @if($row->status == 'กระทบยอดแล้ว') selected @endif>กระทบยอดแล้ว</option>  
                                            </select> 
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-bold mb-2 d-flex align-items-center small"><i class="bi bi-wallet2 me-2 text-success"></i> ข้อมูลการชดเชย (Receive)</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">วันที่ชดเชย</label>
                                            <input type="hidden" name="receive_date" id="receive_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ $row->receive_date }}">
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3 datepicker_th" data-hidden-id="receive_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ !empty($row->receive_date) ? DateThai($row->receive_date) : '' }}" placeholder="วว/ดด/ปปปป" readonly>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label mb-1 small fw-bold">เลขที่หนังสือชดเชย</label>
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3" name="receive_no" value="{{ $row->receive_no }}" placeholder="ระบุเลขที่โอน">
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label mb-1 small fw-bold">จำนวนเงินที่ได้รับ</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="receive" value="{{ $row->receive }}">
                                                <span class="input-group-text rounded-pill-end small bg-light">บาท</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-0">
                                            <label class="form-label mb-1 small fw-bold">เลขที่ใบเสร็จ</label>
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3" name="repno" value="{{ $row->repno }}" placeholder="ระบุเลขที่ใบเสร็จ">
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
                                            <input type="number" step="0.01" class="form-control form-control-sm rounded-pill px-3" name="adj_inc" value="{{ $row->adj_inc ?? 0 }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1 small fw-bold text-danger">ปรับลด (-)</label>
                                            <input type="number" step="0.01" class="form-control form-control-sm rounded-pill px-3" name="adj_dec" value="{{ $row->adj_dec ?? 0 }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1 small fw-bold text-muted">วันที่ปรับปรุง</label>
                                            <input type="hidden" name="adj_date" id="adj_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ $row->adj_date ?? date('Y-m-d') }}">
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3 datepicker_th" data-hidden-id="adj_date_{{ str_replace('/', '-', $row->vn) }}" value="{{ !empty($row->adj_date) ? DateThai($row->adj_date) : DateThai(date('Y-m-d')) }}" placeholder="วว/ดด/ปปปป" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label mb-1 small fw-bold text-muted">หมายเหตุ</label>
                                            <input type="text" class="form-control form-control-sm rounded-pill px-3" name="adj_note" value="{{ $row->adj_note }}" placeholder="ระบุเหตุผล">
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
    @endforeach

    @if (session('success')) <script>Swal.fire({icon:'success',title:'สำเร็จ',text:'{{session('success')}}',timer:2000,showConfirmButton:false});</script> @endif
    <script>
        function showLoading() { Swal.fire({title:'กำลังโหลด...',text:'กรุณารอสักครู่',allowOutsideClick:false,didOpen:()=>{Swal.showLoading();}}); }
        function confirmDelete() {
            const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่จะลบ', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการลบรายการที่เลือกใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-delete').submit();
                }
            });
        }
        function confirmSubmit() {
            const selected = [...document.querySelectorAll('input[name="checkbox[]"]:checked')].map(e => e.value);
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการ', 'warning');
                return;
            }
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการยืนยันรายการที่เลือกใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-confirm').submit();
                }
            });
        }
        function confirmUnlock(id) {
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการ Unlock ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ url('debtor/1102050101_103/unlock') }}/" + id;
                    let token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';
                    form.appendChild(token);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        function confirmLock(id) {
            Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการ Lock ใช่หรือไม่?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ url('debtor/1102050101_103/lock') }}/" + id;
                    let token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';
                    form.appendChild(token);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        function bulkAdjust() {
            const selected = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e => e.value);
            if (selected.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่ต้องการปรับปรุง', 'warning');
                return;
            }

            Swal.fire({
                title: 'ปรับปรุงยอดเป็น 0',
                html: `
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">หมายเหตุการปรับปรุง</label>
                            <input type="text" id="blk_note" class="form-control rounded-pill" value="ปรับปรุงยอดเป็น 0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">วันที่ปรับปรุง</label>
                            <input type="text" id="blk_date_th" class="form-control rounded-pill datepicker_th" value="{{ DateThai(date('Y-m-d')) }}" readonly>
                            <input type="hidden" id="blk_date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div style="background-color: #e3f2fd; border-left: 4px solid #007bff; padding: 10px; border-radius: 4px;">
                            <p style="color: #0056b3; margin-bottom: 0; font-size: 14px;">
                                <i class="bi bi-info-circle-fill me-1"></i> ระบบจะปรับเพิ่ม หรือ ปรับลดเพื่อให้ยอดคงเหลือเป็น 0 เฉพาะรายการที่ Lock แล้วเท่านั้น
                            </p>
                        </div>
                    </div>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'Cancel',
                didOpen: () => {
                    $('#blk_date_th').datepicker({
                        format: 'dd/mm/yyyy',
                        autoclose: true,
                        language: 'th',
                        thaiyear: true,
                        todayBtn: true,
                        todayHighlight: true
                    }).on('changeDate', function(e) {
                        if (e.date) {
                            const y = e.date.getFullYear();
                            const m = ('0' + (e.date.getMonth() + 1)).slice(-2);
                            const d = ('0' + e.date.getDate()).slice(-2);
                            $('#blk_date').val(y + '-' + m + '-' + d);
                        }
                    });
                },
                preConfirm: () => {
                    return {
                        note: $('#blk_note').val(),
                        date: $('#blk_date').val()
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    if (typeof showLoading === 'function') { showLoading(); }
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ url('debtor/1102050101_103_bulk_adj') }}";
                    form.appendChild(Object.assign(document.createElement('input'), {type: 'hidden', name: '_token', value: '{{ csrf_token() }}'}));
                    form.appendChild(Object.assign(document.createElement('input'), {type: 'hidden', name: 'bulk_adj_note', value: result.value.note}));
                    form.appendChild(Object.assign(document.createElement('input'), {type: 'hidden', name: 'bulk_adj_date', value: result.value.date}));
                    selected.forEach(id => {
                        form.appendChild(Object.assign(document.createElement('input'), {type: 'hidden', name: 'checkbox_d[]', value: id}));
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection

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
                $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
            }
            if(end_date_val) {
                $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
            }

            // Initialize values for inputs with data-date (e.g. Modals)
            $('.datepicker_th').each(function() {
                var dateVal = $(this).data('date');
                if(dateVal) {
                    $(this).datepicker('setDate', new Date(dateVal));
                }
            });

            // Sync Date Inputs (Generic Handler for all datepicker_th inputs)
            $(document).on('changeDate', '.datepicker_th', function(e) {
                var date = e.date;
                var hiddenInput = $(this).prev('input[type="hidden"]');
                // Support both previous sibling and data-hidden-id
                var hiddenId = $(this).data('hidden-id');
                var targetHidden = hiddenId ? $('#' + hiddenId) : (hiddenInput.length ? hiddenInput : null);

                if(date && targetHidden) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    targetHidden.val(year + "-" + month + "-" + day);
                } else if(targetHidden) {
                    targetHidden.val('');
                }
            });

            // DataTables Initialization
            $('#debtor').DataTable({ 
                dom: '<"row mb-3"<"col-md-6"l>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>', 
                language: { 
                    lengthMenu: "แสดง _MENU_ รายการ", 
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ", 
                    paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } 
                } 
            });

            $('#debtor_search').DataTable({ 
                dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>', 
                buttons: [{ extend: 'excelHtml5', text: 'Excel', className: 'btn btn-success btn-sm' }], 
                language: { 
                    search: "ค้นหา:", 
                    lengthMenu: "แสดง _MENU_ รายการ", 
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ", 
                    paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } 
                } 
            });
        });
    </script>
@endpush
