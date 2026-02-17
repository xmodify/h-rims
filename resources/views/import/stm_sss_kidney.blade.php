@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-9">
                <div class="card-body">
                    <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_sss_kidney_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark"><i class="bi bi-file-earmark-zip me-2 text-primary"></i> นำเข้าไฟล์ STM SSS Kidney (.zip)</h6>
                            <p class="text-muted small">เลือกไฟล์ .zip ที่ได้รับจากระบบ (ฟอกไต ประกันสังคม)</p>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" type="file" name="files[]" multiple accept=".zip" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-primary px-4" type="submit" style="border-radius: 0 10px 10px 0;">
                                <i class="bi bi-cloud-upload me-2"></i> นำเข้าข้อมูล
                            </button>
                        </div>

                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0">
                                <i class="bi bi-check-circle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header & Search -->
    <div class="page-header-box">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-cloud-arrow-down-fill text-success me-2"></i>
                ข้อมูล Statement ประกันสังคม SSS [ฟอกไต]
            </h5>
            <div class="text-muted small mt-1">ปีงบประมาณประจำปัจจุบัน: {{ $budget_year }}</div>
            <div class="mt-2">
                <a href="{{ url('/import/stm_sss_kidneydetail') }}" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-text me-1"></i> รายละเอียด
                </a>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">ปีงบประมาณ:</span>
                <select class="form-select form-select-sm" name="budget_year" style="width: 160px; border-radius: 8px;">
                    @foreach ($budget_year_select as $row)
                        <option value="{{ $row->LEAVE_YEAR_ID }}"
                            {{ (int)$budget_year === (int)$row->LEAVE_YEAR_ID ? 'selected' : '' }}>
                            {{ $row->LEAVE_YEAR_NAME }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_sss_kidney" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">FileName</th> 
                            <th class="text-center">Station</th> 
                            <th class="text-center">จำนวน</th>                      
                            <th class="text-center">ฟอกเลือดล้างไต</th> 
                            <th class="text-center">ยา EPOETIN</th> 
                            <th class="text-center">ฉีดยา EPOETIN</th>
                            <th class="text-center">ชดเชยรวม</th>  
                            <th class="text-center">เลขงวด</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_sss_kidney as $row)
                        <tr>
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->station }}</span></td>
                            <td class="text-end fw-bold">{{ number_format($row->count_no) }}</td> 
                            <td class="text-end">{{ number_format($row->amount,2) }}</td>
                            <td class="text-end">{{ number_format($row->epopay,2) }}</td>
                            <td class="text-end">{{ number_format($row->epoadm,2) }}</td>
                            <td class="text-end text-success fw-bold">{{ number_format($row->receive_total,2) }}</td>
                            <td class="text-center text-primary fw-bold">{{ $row->round_no }}</td>
                            <td class="text-center">
                                @if(!empty($row->round_no))
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <small class="text-muted me-1">{{ $row->receive_no }}</small>
                                        <button type="button"
                                            class="btn btn-sm {{ $row->receive_no ? 'btn-outline-warning btn-edit-receipt' : 'btn-outline-danger btn-new-receipt' }} rounded-pill px-3"
                                            data-round="{{ $row->round_no }}"
                                            data-receive="{{ $row->receive_no }}"
                                            data-date="{{ $row->receipt_date }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiptModal"
                                            title="{{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}">
                                            <i class="bi {{ $row->receive_no ? 'bi-pencil-square' : 'bi-plus-circle' }} me-1"></i>
                                            {{ $row->receive_no ? 'แก้ไข' : 'ออกใบเสร็จ' }}
                                        </button>
                                    </div>
                                @endif
                            </td>     
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
                    <input type="hidden" id="receipt_date" name="receipt_date">
                    <input type="text" class="form-control datepicker_th" id="receipt_date_display" style="width: 120px;" readonly>
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

                        if(this.dataset.date) {
                            $('#receipt_date_display').datepicker('setDate', new Date(this.dataset.date));
                        } else {
                            $('#receipt_date_display').datepicker('clearDates');
                        }
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
                    fetch("{{ url('import/stm_sss_kidney_updateReceipt') }}", {
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
            // Initialize Datepicker Thai
            $('.datepicker_th').datepicker({
                format: 'd M yyyy',
                todayBtn: "linked",
                todayHighlight: true,
                autoclose: true,
                language: 'th-th',
                thaiyear: true,
                zIndexOffset: 1050
            });

            // Sync Changes to Hidden Inputs
            $('.datepicker_th').on('changeDate', function(e) {
                var date = e.date;
                var targetId = $(this).attr('id').replace('_display', '');
                var hiddenInput = $('#' + targetId);
                if(date) {
                    var day = ("0" + date.getDate()).slice(-2);
                    var month = ("0" + (date.getMonth() + 1)).slice(-2);
                    var year = date.getFullYear();
                    hiddenInput.val(year + "-" + month + "-" + day);
                } else {
                    hiddenInput.val('');
                }
            });

            $('#stm_sss_kidney').DataTable({
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