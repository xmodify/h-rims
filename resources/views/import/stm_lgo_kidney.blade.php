@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">  
        <div class="col-md-12">
            <div class="alert alert-success text-primary" role="alert">
                <strong>ข้อมูล Statement สิทธิเบิกจ่ายตรง อปท.LGO [ฟอกไต HD]</strong>
            </div>

            <div class="card-body">
                <form action="{{ url('import/stm_lgo_kidney_save') }}" method="POST" enctype="multipart/form-data">
                    @csrf      
                    <div class="row mb-2">            
                        <div class="col"></div>
                            <div class="col-md-5">
                                <div class="mb-3 mt-3">
                                <input class="form-control form-control-lg" id="formFileLg" name="file"
                                    type="file" required>
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
                    <table id="stm_lgo_kidney" class="table table-bordered table-striped my-3">
                        <thead>
                            <tr class="table-primary">
                                <th class="text-center">Filename</th> 
                                <th class="text-center">REP</th> 
                                <th class="text-center">จำนวน</th> 
                                <th class="text-center">ชดเชยค่ารักษา</th> 
                            </tr>     
                            </thead> 
                            <?php $count = 1 ; ?>  
                            @foreach($stm_lgo_kidney as $row) 
                            <tr>
                                <td align="right">{{ $row->stm_filename }}</td>
                                <td align="right">{{ $row->repno }}</td>                            
                                <td align="right">{{ number_format($row->count_no) }}</td>                                   
                                <td align="right">{{ number_format($row->compensate_kidney,2) }}</td>
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

    function simulateProcess() {
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

        showLoadingAlert();
        document.getElementById('importForm').submit();
    }
</script>

@push('scripts')
  <script>
    $(document).ready(function () {
      $('#stm_lgo_kidney').DataTable({
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
              title: 'ข้อมูล Statement สิทธิเบิกจ่ายตรง อปท.LGO [ฟอกไต HD]'
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
