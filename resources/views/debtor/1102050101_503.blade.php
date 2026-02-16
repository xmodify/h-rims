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
    <div class="page-header-box mt-2 mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="text-primary mb-0 fw-bold">
                <i class="bi bi-wallet2 me-2"></i>
                1102050101.503-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_503') }}" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="date" name="start_date" class="form-control border-start-0 rounded-0" value="{{ $start_date }}" style="width: 170px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="date" name="end_date" class="form-control border-start-0 rounded-end" value="{{ $end_date }}" style="width: 170px;">
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 220px;">
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
                    <form id="form-delete" action="{{ url('debtor/1102050101_503_delete') }}" method="POST" enctype="multipart/form-data">
                        @csrf   
                        @method('DELETE')
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                                <i class="bi bi-trash-fill me-1"></i> ลบรายการลูกหนี้
                            </button>
                            <div>
                                <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_503_indiv_excel')}}" target="_blank">
                                    <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                                </a>                
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_503_daily_pdf')}}" target="_blank">
                                    <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                                </a> 
                            </div>
                        </div>

                        <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-success">
                                <th class="text-left text-primary" colspan = "8">1102050101.503-ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                                <th class="text-center text-primary" colspan = "8">การชดเชย</th>                                                 
                            </tr>
                            <tr class="table-success">
                                <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th> 
                                <th class="text-center">วันที่</th>
                                <th class="text-center">HN</th>
                                <th class="text-center">ชื่อ-สกุล</th>
                                <th class="text-center">สิทธิ</th>
                                <th class="text-center">ICD10</th>
                                <th class="text-center">ค่ารักษาทั้งหมด</th>  
                                <th class="text-center">ชำระเอง</th> 
                                <th class="text-center text-primary">ลูกหนี้</th>
                                <th class="text-center text-primary">ชดเชย</th> 
                                <th class="text-center text-primary">ผลต่าง</th>                     
                                <th class="text-center text-primary" width="9%">สถานะ</th>
                                <th class="text-center text-primary">เลขที่ใบเสร็จ</th>                          
                                <th class="text-center text-primary">อายุหนี้</th>  
                                <th class="text-center text-primary" width="6%">Action</th>
                                <th class="text-center text-primary">Lock</th>                                       
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income = 0 ; ?>
                            <?php $sum_rcpt_money = 0 ; ?>
                            <?php $sum_debtor = 0 ; ?>
                            <?php $sum_receive = 0 ; ?>
                            @foreach($debtor as $row) 
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->vn}}"></td>   
                                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                <td align="right">{{ $row->pdx }}</td>                      
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>  
                                <td align="right" @if($row->receive > 0) style="color:green" 
                                    @elseif($row->receive < 0) style="color:red" @endif>
                                    {{ number_format($row->receive,2) }}
                                </td>
                                <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                                    @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                                    {{ number_format($row->receive-$row->debtor,2) }}
                                </td>                    
                                <td align="right">{{ $row->status ?? '' }}</td>  
                                <td align="right">{{ $row->repno ?? '' }}</td>                           
                                <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                                    @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                                    @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                                    {{ $row->days }} วัน
                                </td>  
                                <td align="center">         
                                    <button type="button" class="btn btn-outline-warning btn-sm px-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#receive-{{ str_replace(['/', '.'], '-', $row->vn) }}"> 
                                        <i class="bi bi-cash-stack"></i> ชดเชย
                                    </button>                            
                                </td> 
                                <td align="center" style="color:blue">
                            @if(Auth::user()->status == 'admin')
                                @if($row->debtor_lock == 'Y')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmUnlock('{{ $row->vn }}')">
                                        <i class="bi bi-unlock"></i> Unlock
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="confirmLock('{{ $row->vn }}')">
                                        <i class="bi bi-lock"></i> Lock
                                    </button>
                                @endif
                            @else
                                {{ $row->debtor_lock }}
                            @endif
                        </td>                            
                            <?php $count++; ?>
                            <?php $sum_income += $row->income ; ?>
                            <?php $sum_rcpt_money += $row->rcpt_money ; ?>
                            <?php $sum_debtor += $row->debtor ; ?> 
                            <?php $sum_receive += $row->receive ; ?>       
                            </tr>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td class="text-end">{{ number_format($sum_income,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                                    <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                                    <td class="text-end" style="color:red">
                                        {{ number_format($sum_receive - $sum_debtor, 2) }}
                                    </td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div> 

                <!-- Tab 2: รอยืนยันลูกหนี้ -->
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel">
                    <form id="form-confirm" action="{{ url('debtor/1102050101_503_confirm') }}" method="POST" enctype="multipart/form-data">
                        @csrf                
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                                <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                            </button>
                            <div></div>
                        </div>

                        <table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                            <thead>
                            <tr class="table-secondary">
                                <th class="text-left text-primary" colspan = "9">1102050101.503 ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                         
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
                                <th class="text-center">ลูกหนี้</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $count = 1 ; ?>
                            <?php $sum_income_search = 0; ?>
                            <?php $sum_rcpt_money_search = 0; ?>
                            <?php $sum_debtor_search = 0; ?>
                            @foreach($debtor_search as $row)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->vn}}"></td> 
                                <td align="right">{{ DateThai($row->vstdate) }} {{ $row->vsttime }}</td>
                                <td align="center">{{ $row->hn }}</td>
                                <td align="left">{{ $row->ptname }}</td>
                                <td align="left">{{ $row->pttype }} [{{ $row->hospmain }}]</td>
                                <td align="right">{{ $row->pdx }}</td>                  
                                <td align="right">{{ number_format($row->income,2) }}</td>
                                <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                                <td align="right">{{ number_format($row->debtor,2) }}</td>
                            <?php $count++; ?>
                            <?php $sum_income_search += $row->income; ?>
                            <?php $sum_rcpt_money_search += $row->rcpt_money; ?>
                            <?php $sum_debtor_search += $row->debtor; ?>
                            </tr>
                            @endforeach 
                            </tbody>
                            <tfoot>
                                <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                                    <td colspan="6" class="text-end">รวม</td>
                                    <td class="text-end">{{ number_format($sum_income_search,2) }}</td>
                                    <td class="text-end">{{ number_format($sum_rcpt_money_search,2) }}</td>
                                    <td class="text-end" style="color:blue">{{ number_format($sum_debtor_search,2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div> 

            </div>
        </div>
    </div>

    <!-- Modal บันทึกชดเชย -->
    @foreach($debtor as $row)
        <div id="receive-{{ str_replace(['/', '.'], '-', $row->vn) }}" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white border-0 py-3">
                        <h5 class="modal-title d-flex align-items-center">
                            <i class="bi bi-cash-stack me-2"></i>
                            รายการการชดเชยเงิน/ลูกหนี้ (VN/AN: {{ $row->vn }})
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>         
                    <form action="{{ url('debtor/1102050101_503/update', $row->vn) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4">
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="p-3 rounded-3 bg-primary-soft mb-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-7">
                                                <label class="text-muted small d-block">ชื่อ-สกุล</label>
                                                <span class="fw-bold text-primary fs-5">{{ $row->ptname }}</span>
                                            </div>
                                            <div class="col-md-5 text-md-end">
                                                <label class="text-muted small d-block">ยอดลูกหนี้คงเหลือ</label>
                                                <span class="fw-bold text-primary fs-5">{{ number_format($row->debtor, 2) }} บาท</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Left Column: การเรียกเก็บ -->
                                <div class="col-md-6 border-end">
                                    <h6 class="text-secondary fw-bold mb-3 d-flex align-items-center">
                                        <i class="bi bi-send-fill me-2 text-primary"></i> ข้อมูลการส่งเบิก (Charge)
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">วันที่เรียกเก็บ</label>
                                        <input type="date" class="form-control rounded-pill px-3" name="charge_date" value="{{ $row->charge_date ?? '' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เลขที่หนังสือเรียกเก็บ</label>
                                        <input type="text" class="form-control rounded-pill px-3" name="charge_no" value="{{ $row->charge_no ?? '' }}" placeholder="ระบุเลขที่หนังสือ">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">จำนวนเงินที่เรียกเก็บ</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control rounded-pill-start px-3" name="charge" value="{{ $row->charge ?? '' }}">
                                            <span class="input-group-text rounded-pill-end small bg-light">บาท</span>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">สถานะลูกหนี้</label>
                                        <select class="form-select rounded-pill px-3" name="status">                                                       
                                            <option value="ยืนยันลูกหนี้" @if (($row->status ?? '') == 'ยืนยันลูกหนี้') selected @endif>ยืนยันลูกหนี้</option>                                           
                                            <option value="อยู่ระหว่างเรียกเก็บ" @if (($row->status ?? '')  == 'อยู่ระหว่างเรียกเก็บ') selected @endif>อยู่ระหว่างเรียกเก็บ</option> 
                                            <option value="อยู่ระหว่างการขออุทธรณ์" @if (($row->status ?? '') == 'อยู่ระหว่างการขออุทธรณ์') selected @endif>อยู่ระหว่างการขออุทธรณ์</option>
                                            <option value="กระทบยอดแล้ว" @if (($row->status ?? '') == 'กระทบยอดแล้ว') selected @endif>กระทบยอดแล้ว</option>  
                                        </select> 
                                    </div>
                                </div>

                                <!-- Right Column: การชดเชย -->
                                <div class="col-md-6">
                                    <h6 class="text-secondary fw-bold mb-3 d-flex align-items-center">
                                        <i class="bi bi-wallet2 me-2 text-success"></i> ข้อมูลการชดเชย (Receive)
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">วันที่ชดเชย</label>
                                        <input type="date" class="form-control rounded-pill px-3 border-success-soft" name="receive_date" value="{{ $row->receive_date ?? '' }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">เลขที่หนังสือชดเชย</label>
                                        <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="receive_no" value="{{ $row->receive_no ?? '' }}" placeholder="ระบุเลขที่โอน">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">จำนวนเงินที่ได้รับ</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control rounded-pill-start px-3 border-success-soft" name="receive" value="{{ $row->receive ?? '' }}">
                                            <span class="input-group-text rounded-pill-end small bg-success-soft text-success border-success-soft">บาท</span>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">เลขที่ใบเสร็จ</label>
                                        <input type="text" class="form-control rounded-pill px-3 border-success-soft" name="repno" value="{{ $row->repno ?? ($row->repno_pp ?? '') }}" placeholder="ระบุเลขที่ใบเสร็จ">
                                    </div>
                                </div>
                            </div> 
                        </div>
                        <div class="modal-footer bg-light border-0 p-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm" onclick="showLoading()">
                                <i class="bi bi-save me-1"></i> บันทึกข้อมูล
                            </button>
                        </div>
                    </form>     
                </div>
            </div>
        </div>
    @endforeach
    <!-- end modal --> 
    
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
                text: 'กรุณารอสักครู่',
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
                    showLoading();
                    document.getElementById('form-delete').submit();
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
                    showLoading();
                    document.getElementById('form-confirm').submit();
                }
            });
        }
    </script>


    <script>
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
                    form.action = "{{ url('debtor/1102050101_503/unlock') }}/" + id;
                    
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
                    form.action = "{{ url('debtor/1102050101_503/lock') }}/" + id;
                    
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
        $(document).ready(function () {
            $('#debtor').DataTable({
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' + // Show รายการ
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' + // Info
                        '<"col-md-6"p>' + // Pagination
                    '>',            
                language: {
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    paginate: {
                    previous: "ก่อนหน้า",
                    next: "ถัดไป"
                    }
                }
            });
        });
    </script>
    <script>
        $(document).ready(function () {
        $('#debtor_search').DataTable({
            dom: '<"row mb-3"' +
                    '<"col-md-6"l>' + // Show รายการ
                    '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
                '>' +
                'rt' +
                '<"row mt-3"' +
                    '<"col-md-6"i>' + // Info
                    '<"col-md-6"p>' + // Pagination
                '>',
            buttons: [
                {
                extend: 'excelHtml5',
                text: 'Excel',
                className: 'btn btn-success btn-sm',
                title: '1102050101.503 ลูกหนี้ค่ารักษา คนต่างด้าวและแรงงานต่างด้าว OP นอก CUP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
                }
            ],
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: {
                previous: "ก่อนหน้า",
                next: "ถัดไป"
                }
            }
        });
        });
    </script>
@endpush
