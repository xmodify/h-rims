@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">  
        <div class="col-md-12"> 
            <div class="alert alert-success text-primary" role="alert">
                <strong>ข้อมูล Statement สิทธิเบิกจ่ายตรง อปท.LGO [OP-IP]</strong>
            </div> 
            <div class="card-body">
                <form id="importForm" onsubmit="simulateProcess(event)" action="{{ url('import/stm_lgo_save') }}" method="POST" enctype="multipart/form-data">
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
                    <div class="row"> 
                        @if ($message = Session::get('success'))
                        <div class="alert alert-success text-center">
                        <h5><strong>{{ $message }}</strong></h5>
                        </div>
                        @endif
                    </div>
                </form>
            </div> 
            <div class="card-body">
                <div style="overflow-x:auto;">   
                    <table id="stm_lgo" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Dep</th>
                                <th class="text-center">Filename</th> 
                                <th class="text-center">REP</th> 
                                <th class="text-center">จำนวนราย</th>             
                                <th class="text-center">AdjRW</th> 
                                <th class="text-center">อัตราจ่าย</th> 
                                <th class="text-center">เรียกเก็บค่ารักษา</th>  
                                <th class="text-center">ชดเชยค่ารักษา</th>                                                           
                                <th class="text-center">IPLG</th>
                                <th class="text-center">OPLG</th>
                                <th class="text-center">PALG</th>
                                <th class="text-center">INSTGL</th>
                                <th class="text-center">OTLG</th>
                                <th class="text-center">PP</th>
                                <th class="text-center">DRUG</th>
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_lgo as $row) 
                            <tr>
                                <td align="center">{{ $row->dep }}</td> 
                                <td align="right">{{ $row->stm_filename }}</td>
                                <td align="right">{{ $row->repno }}</td>                            
                                <td align="right">{{ number_format($row->count_no) }}</td>
                                <td align="right">{{ $row->adjrw }}</td> 
                                <td align="right">{{ number_format($row->payrate,2) }}</td>  
                                <td align="right">{{ number_format($row->charge_treatment,2) }}</td>  
                                <td align="right">{{ number_format($row->compensate_treatment,2) }}</td>  
                                <td align="right">{{ number_format($row->case_iplg,2) }}</td> 
                                <td align="right">{{ number_format($row->case_oplg,2) }}</td>   
                                <td align="right">{{ number_format($row->case_palg,2) }}</td>  
                                <td align="right">{{ number_format($row->case_inslg,2) }}</td>   
                                <td align="right">{{ number_format($row->case_otlg,2) }}</td>  
                                <td align="right">{{ number_format($row->case_pp,2) }}</td> 
                                <td align="right">{{ number_format($row->case_drug,2) }}</td> 
                            </tr>                
                            <?php $count++; ?>  
                            @endforeach   
                    </table>
                </div> 
            </div>
        </div> 
    </div> 
</div> 

@if (session('success'))
<script>
    Swal.fire({
        title: 'นำเข้าสำเร็จ!',
        text: '{{ session('success') }}',
        icon: 'success',
        confirmButtonText: 'ตกลง'
    });
</script>
@endif

@endsection

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
      $('#stm_lgo').DataTable({
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
              className: 'btn btn-success',
              title: 'ข้อมูล Statement สิทธิเบิกจ่ายตรง อปท.LGO [OP-IP]'
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

