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
                1102050101.402-ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP
            </h4>
            <small class="text-muted">ข้อมูลวันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</small>
        </div>
        
        <div class="d-flex align-items-center gap-4">
            <!-- Filter Section -->
            <div class="filter-group">
                <form method="POST" action="{{ url('debtor/1102050101_402') }}" enctype="multipart/form-data" class="m-0 d-flex align-items-center gap-2">
                    @csrf
                    
                    <!-- Date Range -->
                    <div class="d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่</span>
                        <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                        <input type="text" id="start_date_display" class="form-control border-start-0 rounded-0 datepicker-th" value="{{ DateThai($start_date) }}" style="width: 170px;">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                        <input type="text" id="end_date_display" class="form-control border-start-0 rounded-end datepicker-th" value="{{ DateThai($end_date) }}" style="width: 170px;">
                    </div>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="width: 220px;">
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

            <form action="{{ url('debtor/1102050101_402_delete') }}" method="POST" enctype="multipart/form-data">
                @csrf   
                @method('DELETE')
                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                        <i class="bi bi-trash-fill me-1"></i> ลบรายการลูกหนี้
                    </button>
                    <div>
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_402_indiv_excel')}}" target="_blank">
                             <i class="bi bi-file-earmark-excel me-1"></i> ส่งออกรายตัว
                        </a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_402_daily_pdf')}}" target="_blank">
                             <i class="bi bi-printer me-1"></i> พิมพ์รายวัน
                        </a> 
                    </div>
                </div>
                <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-success">
                        <th class="text-left text-primary" colspan = "12">1102050101.402 ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "6">การชดเชย</th>                                                 
                    </tr>
                    <tr class="table-success">
                        <th class="text-center"><input type="checkbox" onClick="toggle_d(this)"> All</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">AN</th>
                        <th class="text-center">ชื่อ-สกุล</th>  
                        <th class="text-center">สิทธิ</th>
                        <th class="text-center">Admit</th>
                        <th class="text-center">Discharge</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">AdjRW</th>
                        <th class="text-center">ค่ารักษาทั้งหมด</th>  
                        <th class="text-center">ชำระเอง</th>
                        <th class="text-center">ฟอกไต</th>
                        <th class="text-center text-primary">ลูกหนี้</th>
                        <th class="text-center text-primary">ชดเชย</th>
                        <th class="text-center text-primary">ผลต่าง</th>
                        <th class="text-center text-primary">REP</th>
                        <th class="text-center text-primary">อายุหนี้</th>                
                        <th class="text-center text-primary">Lock</th> 
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php $sum_income = 0 ; ?>
                    <?php $sum_rcpt_money = 0 ; ?>
                    <?php $sum_kidney = 0 ; ?>
                    <?php $sum_debtor = 0 ; ?>
                    <?php $sum_receive  = 0 ; ?>
                    @foreach($debtor as $row)
                    <tr>
                        <td class="text-center"><input type="checkbox" name="checkbox_d[]" value="{{$row->an}}"></td> 
                        <td align="center">{{ $row->hn }}</td>
                        <td align="center">{{ $row->an }}</td>
                        <td align="left">{{ $row->ptname }}</td>
                        <td align="left">{{ $row->pttype }}</td>
                        <td align="right">{{ DateThai($row->regdate) }}</td>
                        <td align="right">{{ DateThai($row->dchdate) }}</td>
                        <td align="right">{{ $row->pdx }}</td>  
                        <td align="right">{{ $row->adjrw }}</td>                        
                        <td align="right">{{ number_format($row->income,2) }}</td>
                        <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                        <td align="right">{{ number_format($row->kidney,2) }}</td>
                        <td align="right" class="text-primary">{{ number_format($row->debtor,2) }}</td>
                        <td align="right" @if($row->receive > 0) style="color:green" 
                            @elseif($row->receive < 0) style="color:red" @endif>
                            {{ number_format($row->receive,2) }}
                        </td>
                        <td align="right" @if(($row->receive-$row->debtor) > 0) style="color:green" 
                            @elseif(($row->receive-$row->debtor) < 0) style="color:red" @endif>
                            {{ number_format($row->receive-$row->debtor,2) }}
                        </td>                                                
                        <td align="left">{{ $row->repno }} {{ $row->cipn_rid }} {{ $row->csop_rid }}</td>
                        <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  
                            @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" 
                            @else style="background-color: #FF7F7F;" @endif >
                            {{ $row->days }} วัน
                        </td>  
                        <td align="center" style="color:blue">
                            @if(Auth::user()->status == 'admin')
                                @if($row->debtor_lock == 'Y')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmUnlock('{{ $row->an }}')">
                                        <i class="bi bi-unlock"></i> Unlock
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="confirmLock('{{ $row->an }}')">
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
                    <?php $sum_kidney += $row->kidney ; ?> 
                    <?php $sum_debtor += $row->debtor ; ?> 
                    <?php $sum_receive += $row->receive ; ?>             
                    @endforeach 
                    </tr>   
                    <tfoot>
                        <tr class="table-success text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="9" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money,2) }}</td>
                            <td class="text-end">{{ number_format($sum_kidney,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor,2) }}</td>
                            <td class="text-end" style="color:green">{{ number_format($sum_receive,2) }}</td>
                            <td class="text-end" style="color:red">
                                {{ number_format($sum_receive - $sum_debtor, 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
                </div>
                
                <!-- Tab 2: รอยืนยันลูกหนี้ -->
                <div class="tab-pane fade" id="confirm-pane" role="tabpanel"> 
 
            <form action="{{ url('debtor/1102050101_402_confirm') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">
                        <i class="bi bi-check-circle me-1"></i> ยืนยันลูกหนี้
                    </button>
                    <div></div>
                </div>                
                <table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-secondary">
                        <th class="text-left text-primary" colspan = "17">1102050101.402-ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }} รอยืนยันลูกหนี้</th>                         
                    </tr>
                    <tr class="table-secondary">
                        <th class="text-center"><input type="checkbox" onClick="toggle(this)"> All</th>  
                        <th class="text-center">ตึกผู้ป่วย</th>
                        <th class="text-center">HN</th>
                        <th class="text-center">AN</th>
                        <th class="text-center">ชื่อ-สกุล</th>              
                        <th class="text-center">อายุ</th>
                        <th class="text-center">สิทธิ</th>
                        <th class="text-center">Admit</th>
                        <th class="text-center">Discharge</th>
                        <th class="text-center">ICD10</th>
                        <th class="text-center">AdjRW</th>
                        <th class="text-center">ค่ารักษาทั้งหมด</th>  
                        <th class="text-center">ชำระเอง</th>
                        <th class="text-center">ฟอกไต</th>
                        <th class="text-center">ลูกหนี้</th>
                        <th class="text-center">รายการฟอกไต</th> 
                        <th class="text-center">สถานะ</th>  
                    </tr>
                    </thead>
                    <?php $count = 1 ; ?>
                    <?php 
                        $sum_income_search = 0;
                        $sum_rcpt_money_search = 0;
                        $sum_kidney_search = 0;
                        $sum_debtor_search = 0;
                    ?>
                    @foreach($debtor_search as $row)
                    <tr>
                        <td class="text-center"><input type="checkbox" name="checkbox[]" value="{{$row->an}}"></td> 
                        <td align="right">{{$row->ward}}</td>
                        <td align="center">{{ $row->hn }}</td>
                        <td align="center">{{ $row->an }}</td>
                        <td align="left">{{ $row->ptname }}</td>
                        <td align="center">{{ $row->age_y }}</td>
                        <td align="left">{{ $row->pttype }}</td>
                        <td align="right">{{ DateThai($row->regdate) }}</td>
                        <td align="right">{{ DateThai($row->dchdate) }}</td>
                        <td align="right">{{ $row->pdx }}</td>      
                        <td align="right">{{ $row->adjrw }}</td>                        
                        <td align="right">{{ number_format($row->income,2) }}</td>
                        <td align="right">{{ number_format($row->rcpt_money,2) }}</td>
                        <td align="right">{{ number_format($row->kidney,2) }}</td>
                        <td align="right">{{ number_format($row->debtor,2) }}</td>
                        <td align="left">{{ $row->kidney_list }}</td>
                        <td align="left">{{ $row->ipt_coll_status_type_name }}</td>
                    <?php $count++; ?>
                    <?php 
                        $sum_income_search += $row->income;
                        $sum_rcpt_money_search += $row->rcpt_money;
                        $sum_kidney_search += $row->kidney;
                        $sum_debtor_search += $row->debtor;
                    ?>
                    @endforeach 
                    </tr> 
                    <tfoot>
                        <tr class="table-secondary text-end" style="font-weight:bold; font-size: 14px;">
                            <td colspan="11" class="text-end">รวม</td>
                            <td class="text-end">{{ number_format($sum_income_search,2) }}</td>
                            <td class="text-end">{{ number_format($sum_rcpt_money_search,2) }}</td>
                            <td class="text-end">{{ number_format($sum_kidney_search,2) }}</td>
                            <td class="text-end" style="color:blue">{{ number_format($sum_debtor_search,2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_402_delete') }}']").submit();
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
                    document.querySelector("form[action='{{ url('debtor/1102050101_402_confirm') }}']").submit();
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
                    form.action = "{{ url('debtor/1102050101_402/unlock') }}/" + id;
                    
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
                    form.action = "{{ url('debtor/1102050101_402/lock') }}/" + id;
                    
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
                title: '1102050101.402-ลูกหนี้ค่ารักษา-เบิกจ่ายตรง กรมบัญชีกลาง IP รอยืนยัน วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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



@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Datepicker Thai for Filter
            $('.datepicker-th').datepicker({
                format: 'dd/mm/yyyy',
                todayBtn: true,
                language: 'th',
                thaiyear: true,
                autoclose: true,
                todayHighlight: true
            });

            // Sync Start Date
            $('#start_date_display').change(function() {
                let buddhistDate = $(this).val();
                if (buddhistDate) {
                    let parts = buddhistDate.split('/');
                    let day = parts[0];
                    let month = parts[1];
                    let year = parseInt(parts[2]) - 543;
                    $('#start_date').val(year + '-' + month + '-' + day);
                } else {
                    $('#start_date').val('');
                }
            });

            // Sync End Date
            $('#end_date_display').change(function() {
                let buddhistDate = $(this).val();
                if (buddhistDate) {
                    let parts = buddhistDate.split('/');
                    let day = parts[0];
                    let month = parts[1];
                    let year = parseInt(parts[2]) - 543;
                    $('#end_date').val(year + '-' + month + '-' + day);
                } else {
                    $('#end_date').val('');
                }
            });
        });
    </script>
@endpush
