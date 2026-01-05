@extends('layouts.app')
 
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">  
        <div class="col-md-12"> 
            
            <div class="card-body"> 
                <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_ofc_save') }}" method="POST" enctype="multipart/form-data">
                    @csrf      
                    <div class="row mb-2">            
                        <div class="col"></div>
                            <div class="col-md-5">
                                <div class="mb-3 mt-3">
                                {{-- <input class="form-control form-control-lg" id="formFileLg" name="file" type="file" multiple required> --}}
                                <input class="form-control form-control-lg" id="formFileLg" type="file" name="files[]" multiple accept=".xlsx,.xls" required>
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            </div>
                            </div>
                        <div class="col"></div>
                    </div>
                    <div class="row mb-2">            
                        <div align="center">
                            <button type="submit" onclick="simulateProcess()"
                                class="mb-3 me-2 btn-icon btn-shadow btn-dashed btn btn-outline-primary">
                                <i class="fa-solid fa-cloud-arrow-up me-2" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="UP STM"></i>นำเข้า STM
                            </button> 
                        </div>
                    </div>                    
                </form>
                <div class="row justify-content-center">      
                    <div class="col-md-12">
                        <form method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">                          
                            <div class="col-md-9" align="left"></div>
                            <div class="col-lg-3 d-flex justify-content-lg-end">
                                <div class="d-flex align-items-center gap-2">
                                <select class="form-select" name="budget_year">
                                    @foreach ($budget_year_select as $row)
                                    <option value="{{ $row->LEAVE_YEAR_ID }}"
                                        {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                                        {{ $row->LEAVE_YEAR_NAME }}
                                    </option> 
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary">{{ __('ค้นหา') }}</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>    
                </div>
            </div> 

            <div class="alert alert-success text-primary" role="alert">
                <strong>ข้อมูล Statement เบิกจ่ายตรงกรมบัญชีกลาง OFC | กทม.BKK | ขสมก.BMT [OP-IP]</strong>
            </div>             
            
            <div class="card-body">
                <div style="overflow-x:auto;">   
                    <table id="stm_ofc" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Filename</th> 
                                <th class="text-center">Dep</th>                                
                                <th class="text-center">จำนวน REP</th> 
                                <th class="text-center">จำนวนราย</th> 
                                <th class="text-center">AdjRW</th>         
                                <th class="text-center">ค่าห้อง</th>
                                <th class="text-center">ค่าอวัยวะ</th>                  
                                <th class="text-center">ค่ายา</th> 
                                <th class="text-center">ค่ารักษา</th>
                                <th class="text-center">ค่ารถ</th>                     
                                <th class="text-center">ค่าบริการอื่นๆ</th>
                                <th class="text-center">เรียกเก็บ</th> 
                                <th class="text-center">พึงรับทั้งหมด</th>
                                <th class="text-center">เลขที่เอกสาร</th>
                                <th class="text-center">ออกใบเสร็จ</th>
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_ofc as $row)          
                            <tr>
                                <td align="right">{{ $row->stm_filename }}</td>
                                <td align="center">{{ $row->dep }}</td>                                
                                <td align="right">{{ number_format($row->repno) }}</td>
                                <td align="right">{{ number_format($row->count_cid) }}</td>
                                <td align="right">{{ number_format($row->sum_adjrw,4) }}</td>
                                <td align="right">{{ number_format($row->sum_receive_room,2) }}</td>  
                                <td align="right">{{ number_format($row->sum_receive_instument,2) }}</td>  
                                <td align="right">{{ number_format($row->sum_receive_drug,2) }}</td> 
                                <td align="right">{{ number_format($row->sum_receive_treatment,2) }}</td>   
                                <td align="right">{{ number_format($row->sum_receive_car,2) }}</td>  
                                <td align="right">{{ number_format($row->sum_receive_other,2) }}</td> 
                                <td align="right">{{ number_format($row->sum_charge,2) }}</td>   
                                <td align="right" class="text-success">{{ number_format($row->sum_receive_total,2) }}</td>  
                                <td align="right" class="text-primary">{{ $row->round_no }}</td>
                                <td class="text-end">
                                    @if(!empty($row->round_no))
                                        {{ $row->receive_no }} 
                                        <button type="button"
                                            class="btn btn-sm {{ $row->receive_no ? 'btn-warning btn-edit-receipt' : 'btn-danger btn-new-receipt' }}"
                                            data-round="{{ $row->round_no }}"
                                            data-receive="{{ $row->receive_no }}"
                                            data-date="{{ $row->receipt_date }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiptModal">
                                            {{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}
                                        </button>
                                     @endif
                                </td>      
                            </tr>                
                            <?php $count++; ?>  
                            @endforeach   
                    </table>
                </div> 
            </div>
        </div> 
    </div> 
</div> 
{{-- Modal ออกใบเสร็จ --}}
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="receiptModalTitle">
                    ออกใบเสร็จรับเงิน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="round_no">
                <div class="mb-2">
                    <label class="form-label">เลขที่ใบเสร็จ</label>
                    <input type="text" class="form-control" id="receive_no">
                </div>
                <div class="mb-2">
                    <label class="form-label">วันที่ออกใบเสร็จ</label>
                    <input type="date" class="form-control" id="receipt_date">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnSaveReceipt">
                    บันทึก
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    ยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>
{{-- End Modal --}}

<!-- SweetAlert: Success -->
@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'นำเข้าสำเร็จ',
                text: @json(session('success')),
                confirmButtonText: 'ปิด'
            }).then(() => {
                location.reload();
            });
        });
    </script>
@endif
<!-- SweetAlert: Error -->
@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: @json(session('error')),
                confirmButtonText: 'ปิด'
            });
        });
    </script>
@endif

@endsection
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /* ===== เปิด modal (ออกใหม่ / แก้ไข) ===== */
            document.querySelectorAll('.btn-new-receipt, .btn-edit-receipt')
                .forEach(btn => {
                    btn.addEventListener('click', function () {

                        document.getElementById('round_no').value =
                            this.dataset.round;

                        document.getElementById('receive_no').value =
                            this.dataset.receive ?? '';

                        document.getElementById('receipt_date').value =
                            this.dataset.date ?? '';
                    });
                });
            /* ===== บันทึก (AJAX) ===== */
            document.getElementById('btnSaveReceipt')
                .addEventListener('click', function () {

                    let round_no     = document.getElementById('round_no').value;
                    let receive_no   = document.getElementById('receive_no').value;
                    let receipt_date = document.getElementById('receipt_date').value;
                    if (!receive_no || !receipt_date) {
                        Swal.fire('แจ้งเตือน','กรุณากรอกข้อมูลให้ครบ','warning');
                        return;
                    }
                    fetch("{{ url('import/stm_ofc_updateReceipt') }}", {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name=\"csrf-token\"]')
                                .getAttribute('content'),
                            "Content-Type": "application/json",
                            "Accept": "application/json"
                        },
                        body: JSON.stringify({
                            round_no: round_no,
                            receive_no: receive_no,
                            receipt_date: receipt_date
                        })
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'บันทึกสำเร็จ',
                                html: `
                                    <p><strong>เลขที่ใบเสร็จ:</strong> ${res.receive_no}</p>
                                    <p><strong>วันที่ออก:</strong> ${res.receipt_date}</p>
                                `,
                                confirmButtonText: 'ปิด'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('ผิดพลาด', res.message, 'error');
                        }
                    });
                });

        });
    </script>

    <script>
        function showLoadingAlert() {
            Swal.fire({
                title: 'กำลังนำเข้าข้อมูล...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });
        }

        function simulateProcess(event) {

                // ป้องกันฟอร์มส่งออกไปก่อนเวลา
            event.preventDefault(); 

            const fileInput = document.querySelector('input[type="file"]');
                    // ตรวจสอบว่าไม่ได้เลือกไฟล์
            if (!fileInput.files || fileInput.files.length === 0) {
                Swal.fire({
                    title: 'แจ้งเตือน',
                    text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง'
                });
                return; // ❌ หยุดการทำงาน ไม่ส่งฟอร์ม
            }
                // ✅ ตรวจสอบจำนวนไฟล์เกิน 5
            if (fileInput.files.length > 5) {
                Swal.fire({
                    title: 'แจ้งเตือน',
                    text: 'เลือกไฟล์ได้ไม่เกิน 5 ไฟล์',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
                return; // ❌ หยุดการทำงาน
            }

            showLoadingAlert();
            document.getElementById('importForm').submit();
        }
    </script>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#stm_ofc').DataTable({
                ordering: false,   // 🔥 ปิด sorting
                dom: '<"row mb-3"' +
                        '<"col-md-6"l>' +
                        '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' +
                    '>' +
                    'rt' +
                    '<"row mt-3"' +
                        '<"col-md-6"i>' +
                        '<"col-md-6"p>' +
                    '>',
                buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success',
                    title: 'ข้อมูล Statement ประกันสุขภาพ UCS [OP-IP]'
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
