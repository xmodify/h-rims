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
    <div class="container-fluid">        
        <form method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row" >
                    <label class="col-md-2 col-form-label text-md-end my-1">{{ __('วันที่') }}</label>
                <div class="col-md-2">
                    <input type="date" name="start_date" class="form-control my-1" placeholder="Date" value="{{ $start_date }}" >
                </div>
                    <label class="col-md-1 col-form-label text-md-end my-1">{{ __('ถึง') }}</label>
                <div class="col-md-2">
                    <input type="date" name="end_date" class="form-control my-1" placeholder="Date" value="{{ $end_date }}" >
                </div>
                    <label class="col-md-1 col-form-label text-md-end my-1">{{ __('ค้นหา ชื่อ-สกุล,HN') }}</label>
                <div class="col-md-2" >
                    <input id="search" type="text" class="form-control my-1" name="search" value="{{ $search }}" >
                </div>
                <div class="col-md-1" >
                    <button onclick="fetchData()" type="submit" class="btn btn-primary my-1 ">{{ __('ค้นหา') }}</button>
                    <a class="btn btn-warning my-1 text-primary" href="{{ url('debtor/forget_search') }}">Reset</a>
                </div> 
            </div>
        </form> 
        <div style="overflow-x:auto;">
            <form action="{{ url('debtor/1102050101_301_delete') }}" method="POST" enctype="multipart/form-data">
                @csrf   
                @method('DELETE')
                <table id="debtor" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-success">
                        <th class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">ลบลูกหนี้</button>
                        </th>
                        <th class="text-left text-primary" colspan = "9">1102050101.301-ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</th> 
                        <th class="text-center text-primary" colspan = "7">การชดเชย
                            <button type="button" class="btn btn-success btn-sm float-end" data-bs-toggle="modal" data-bs-target="#modalAverageReceive">
                                กระทบยอดแบบกลุ่ม
                            </button> 
                        </th>                                                 
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
                        <td align="right">{{ $row->repno_pp }}</td> 
                        <td align="right" @if($row->days < 90) style="background-color: #90EE90;"  {{-- เขียวอ่อน --}}
                            @elseif($row->days >= 90 && $row->days <= 365) style="background-color: #FFFF99;" {{-- เหลือง --}}
                            @else style="background-color: #FF7F7F;" {{-- แดง --}} @endif >
                            {{ $row->days }} วัน
                        </td> 
                        <td align="center" style="color:blue">{{ $row->debtor_lock }}</td>                            
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
                </table>
            </form>
            <table class="table table-bordered ">
                <thead>
                <tr class="table-primary" >
                    <th class="text-center">รหัสผังบัญชี</th>
                    <th class="text-center">ชื่อผังบัญชี</th>
                    <th class="text-center">ค่ารักษาพยาบาล</th>
                    <th class="text-center">ชำระเอง</th>
                    <th class="text-center">กองทุนอื่น</th>
                    <th class="text-center">PPFS</th>
                    <th class="text-center">ลูกหนี้</th>
                    <th class="text-center">ชดเชย</th> 
                    <th class="text-center">ชดเชย PPFS</th>   
                    <th class="text-center">ผลต่าง</th> 
                    <th class="text-center">รายงาน</th>                
                </tr>
                </thead>
                <tr>
                    <td class="text-primary" align="right">1102050101.301</td>
                    <td class="text-primary" align="left">ลูกหนี้ค่ารักษา ประกันสังคม OP-เครือข่าย</td>
                    <td class="text-primary" align="right">{{ number_format($sum_income,2)}}</td>
                    <td class="text-primary" align="right">{{ number_format($sum_rcpt_money,2)}}</td>
                    <td class="text-primary" align="right">{{ number_format($sum_other,2)}}</td>
                    <td class="text-primary" align="right">{{ number_format($sum_ppfs,2)}}</td>
                    <td class="text-primary" align="right"><strong>{{ number_format($sum_debtor,2)}}</strong></td>
                    <td align="right" @if($sum_receive > 0) style="color:green"
                        @elseif($sum_receive < 0) style="color:red" @endif>
                        <strong>{{ number_format($sum_receive,2)}}</strong>
                    </td>
                    <td align="right" @if($sum_receive_pp > 0) style="color:green"
                        @elseif($sum_receive_pp < 0) style="color:red" @endif>
                        <strong>{{ number_format($sum_receive_pp,2)}}</strong>
                    </td>
                    <td align="right" @if(($sum_receive-$sum_debtor) > 0) style="color:green"
                        @elseif(($sum_receive-$sum_debtor) < 0) style="color:red" @endif>
                        <strong>{{ number_format($sum_receive-$sum_debtor,2)}}</strong>
                    </td>
                    <td align="center">
                        <a class="btn btn-outline-success btn-sm" href="{{ url('debtor/1102050101_301_indiv_excel')}}" target="_blank">ส่งออกรายตัว</a>                
                        <a class="btn btn-outline-primary btn-sm" href="{{ url('debtor/1102050101_301_daily_pdf')}}" target="_blank">พิมพ์รายวัน</a> 
                    </td>                    
                </tr>
            </table>
        </div> 
        <hr>
        <div style="overflow-x:auto;">
            <form action="{{ url('debtor/1102050101_301_confirm') }}" method="POST" enctype="multipart/form-data">
                @csrf                
                <table id="debtor_search" class="table table-bordered table-striped my-3" width="100%">
                    <thead>
                    <tr class="table-secondary">
                        <th class="text-center">
                            <button type="button" class="btn btn-outline-success btn-sm"  onclick="confirmSubmit()">ยืนยันลูกหนี้</button></th>
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
                    @endforeach 
                </tr>   
                </table>
            </form>
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
                            <input type="date" name="date_start" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label>วันที่สิ้นสุด</label>
                            <input type="date" name="date_end" class="form-control" required>
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

@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("averageReceiveForm");
            const modalEl = document.getElementById("modalAverageReceive");
            // เปิด modal → reset form
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
                        // ✅ ปิด modal แบบไม่มี bsModal instance
                        $("#modalAverageReceive").modal("hide");
                        // ✅ reload หน้าเมื่อ modal ปิดจริง
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



