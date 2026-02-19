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
                1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_301') }}" enctype="multipart/form-data" class="m-0 d-flex flex-wrap align-items-center gap-2">
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

            <form action="{{ url('debtor/1102050101_301_delete') }}" method="POST" enctype="multipart/form-data">
                @csrf   
                @method('DELETE')
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                        <i class="bi bi-trash-fill me-1"></i> ลบรายการลูกหนี้
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAverageReceive">
                            <i class="bi bi-calculator me-1"></i> กระทบยอดแบบกลุ่ม
                        </button>
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_301_indiv_excel')}}" target="_blank">
                             <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                        </a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_301_daily_pdf')}}" target="_blank">
                             <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                        </a> 
                    </div>
                </div>
                <div class="table-responsive"><table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-success">
                        <th class="text-left text-primary" colspan = "10">1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "7">การชดเชย</th>                                                 
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
                        <th class="text-center">กองทุนอื่น</th> 
                        <th class="text-center">PPFS</th>       
                        <th class="text-center text-primary">ลูกหนี้</th>
                        <th class="text-center text-primary">ชดเชย</th>
                        <th class="text-center text-primary">ชดเชย PPFS</th>                        
                        <th class="text-center text-primary">ผลต่าง</th>
                        <th class="text-center text-primary">REP</th>  
                        <th class="text-center text-primary">อายุหนี้</th>
                        <th class="text-center text-primary">Lock</th>                                       
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php $sum_income = 0 ; ?>
                    <?php $sum_rcpt_money = 0 ; ?>
                    <?php $sum_other = 0 ; ?>
                    <?php $sum_ppfs = 0 ; ?>
                    <?php $sum_debtor = 0 ; ?>
                    <?php $sum_receive = 0 ; ?>
                    <?php $sum_receive_pp = 0 ; ?>
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
                        <td align="right">{{ number_format($row->other,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
                        <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td> 
                        <td align="right" @if($row->receive > 0) style="color:green" 
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{ number_format($row->receive,2) }}
                        </td> 
                        <td align="right" @if($row->receive_pp > 0) style="color:green" 
                            @elseif($row->receive_pp < 0) style="color:red" @endif>
                            {{ number_format($row->receive_pp,2) }}
                        </td>                        
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green"
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{ number_format($row->receive-$row->debtor,2) }}
                        </td>
                        <td align="right">{{ $row->repno }} {{ $row->repno_pp }}</td> 
                        <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                            @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                            @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                            {{ $row->days }} วัน
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
                    <?php $sum_other += $row->other ; ?> 
                    <?php $sum_ppfs += $row->ppfs ; ?> 
                    <?php $sum_debtor += $row->debtor ; ?> 
                    <?php $sum_receive += $row->receive ; ?> 
                    <?php $sum_receive_pp += $row->receive_pp ; ?>       
                    @endforeach 
                    </tr>   
                    
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_other,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                            <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                            <td class="text-end" style="color:green">{{ number_format($sum_receive_pp,2) }}</td>
                            <td class="text-end" style="color:red">
                                {{ number_format($sum_receive - $sum_debtor, 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table></div>
            </form>
                </div>
                
                <!-- Tab 2: รอยืนยัน -->
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel"> 

            <form action="{{ url('debtor/1102050101_301_confirm') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">ยืนยันลูกหนี้</button>
                    <div></div>
                </div>                
                <div class="table-responsive"><table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-secondary">
                        <th class="text-left text-primary" colspan = "13">1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                         
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
                        <th class="text-center">PPFS</th>                                     
                        <th class="text-center">ลูกหนี้</th>
                        <th class="text-center" width = "10%">รายการกองทุนอื่น</th> 
                        <th class="text-center" width = "10%">รายการ PPFS</th>
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php 
                        $sum_income_search = 0;
                        $sum_rcpt_money_search = 0;
                        $sum_other_search = 0;
                        $sum_ppfs_search = 0;
                        $sum_debtor_search = 0;
                    ?>
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
                        <td align="right">{{ number_format($row->other,2) }}</td>
                        <td align="right">{{ number_format($row->ppfs,2) }}</td>
                        <td align="right">{{ number_format($row->debtor,2) }}</td>
                        <td align="left" width = "15%">{{ $row->other_list }}</td>
                        <td align="left" width = "15%">{{ $row->ppfs_list }}</td>
                    <?php $count++; ?>
                    <?php 
                        $sum_income_search += $row->income;
                        $sum_rcpt_money_search += $row->rcpt_money;
                        $sum_other_search += $row->other;
                        $sum_ppfs_search += $row->ppfs;
                        $sum_debtor_search += $row->debtor;
                    ?>
                    @endforeach 
                    </tr> 
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="6" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income_search,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money_search,2) }}</td>
                            <td class="text-end">{{ number_format($sum_other_search,2) }}</td>
                            <td class="text-end">{{ number_format($sum_ppfs_search,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor_search,2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table></div>
            </form>
            </div>
        </div>
    </div>
</div>  

<!-- Modal กระทบยอด (AJAX Version) -->
    <div class="modal fade" id="modalAverageReceive" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">        
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">กระทบยอดแบบกลุ่ม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <form id="averageReceiveForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>วันที่เริ่มต้น</label>
                            <input type="hidden" name="date_start" id="date_start">
                            <input type="text" id="date_start_picker" class="form-control datepicker_th" readonly required>
                        </div>
                        <div class="mb-2">
                            <label>วันที่สิ้นสุด</label>
                            <input type="hidden" name="date_end" id="date_end">
                            <input type="text" id="date_end_picker" class="form-control datepicker_th" readonly required>
                        </div>
                        <div class="mb-2">
                            <label>เลขที่ใบเสร็จ</label>
                            <input type="text" name="repno" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>ยอดชดเชย (บาท)</label>
                            <input type="number" step="0.01" name="total_receive" class="form-control" required>
                        </div>
                        <!-- ข้อความผลลัพธ์ -->
                        <div id="avgResultMessage" class="mt-2 d-none"></div>
                        <!-- Loading -->
                        <div id="avgLoadingSpinner" class="text-center d-none">
                            <div class="spinner-border text-success"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-success" id="avgSubmitBtn">ยืนยัน</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        
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
                document.querySelector("form[action='{{ url('debtor/1102050101_301_delete') }}']").submit();
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
                document.querySelector("form[action='{{ url('debtor/1102050101_301_confirm') }}']").submit();
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
                    form.action = "{{ url('debtor/1102050101_301/unlock') }}/" + id;
                    
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
                    form.action = "{{ url('debtor/1102050101_301/lock') }}/" + id;
                    
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

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("averageReceiveForm");
            const modalEl = document.getElementById("modalAverageReceive");
            // เปิด modal  reset form
            modalEl.addEventListener("show.bs.modal", function () {
                form.reset();
            });
            // submit AJAX
            form.addEventListener("submit", function(e){
                e.preventDefault();
                let data = new FormData(form);
                fetch("{{ url('debtor/1102050101_301_average_receive') }}", {
                    method: "POST",
                    body: data // ห้ามใส่ headers
                })
                .then(res => res.json())
                .then(response => {
                    Swal.fire({
                        icon: response.status === "success" ? "success" : "error",
                        html: response.message,
                        confirmButtonText: "ตกลง",
                    }).then(() => {
                        // ? ปิด modal แบบไม่มี bsModal instance
                        $("#modalAverageReceive").modal("hide");
                        // ? reload หน้าเมื่อ modal ปิดจริง
                        $("#modalAverageReceive").on("hidden.bs.modal", function () {
                            location.reload();
                        });

                    });
                })
                .catch(err => {
                    Swal.fire("Error", "ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้", "error");
                });
            });
        });
    </script>
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
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    if(hiddenInput.length) {
                        hiddenInput.val(year + "-" + month + "-" + day);
                    }
                } else {
                    if(hiddenInput.length) {
                        hiddenInput.val('');
                    }
                }
            });
        });
    </script>
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
                title: '1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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

