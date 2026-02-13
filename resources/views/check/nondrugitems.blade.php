@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-briefcase-medical text-primary me-2"></i>
                จัดการค่ารักษาพยาบาล (Non-Drug Items)
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบและจัดการข้อมูลค่ารักษาพยาบาล (ไม่ใช่ยา) ในระบบ HOSxP</div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">
                <i class="bi bi-info-circle me-1"></i> รายการมาตรฐานโรงพยาบาล
            </span>
        </div>
    </div>

    <!-- Active Items Card -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-success">
                <i class="bi bi-check-circle-fill me-2"></i> ค่ารักษาพยาบาลที่เปิดใช้งาน
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="nondrugitems" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">หมวดค่ารักษาพยาบาล</th>  
                            <th class="text-center">รหัส</th>
                            <th class="text-center">ชื่อรายการ</th>  
                            <th class="text-center">ราคา</th>     
                            <th class="text-center">Billcode</th>
                            <th class="text-center">ADP Code</th> 
                            <th class="text-center">ADP Name</th>
                            <th class="text-center">ADP Type</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($nondrugitems as $row) 
                        <tr>
                            <td class="text-start small text-muted lh-1">{{$row->income}}</td> 
                            <td class="text-center fw-bold">{{$row->icode}}</td>                                
                            <td class="text-start fw-bold text-dark">{{$row->name}}</td>            
                            <td class="text-end text-primary fw-bold">{{number_format($row->price,2)}}</td>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{$row->billcode}}</span></td>
                            <td class="text-center small">{{$row->nhso_adp_code}}</td> 
                            <td class="text-start small text-muted">{{$row->nhso_adp_code_name}}</td>
                            <td class="text-center"><span class="badge bg-info-soft text-info">{{$row->nhso_adp_type_name}}</span></td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>         
            </div>
        </div> 
    </div>

    <!-- Inactive Items Card -->
    <div class="card dash-card">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-secondary">
                <i class="bi bi-slash-circle me-2"></i> ค่ารักษาพยาบาลที่ปิดใช้งาน
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="nondrugitems_non" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center text-secondary">หมวดค่ารักษาพยาบาล</th>  
                            <th class="text-center text-secondary">รหัส</th>
                            <th class="text-center text-secondary">ชื่อรายการ</th>  
                            <th class="text-center text-secondary">ราคา</th>     
                            <th class="text-center text-secondary">Billcode</th>
                            <th class="text-center text-secondary">ADP Code</th> 
                            <th class="text-center text-secondary">ADP Name</th>
                            <th class="text-center text-secondary">ADP Type</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($nondrugitems_non as $row) 
                        <tr class="opacity-75">
                            <td class="text-start small text-muted lh-1">{{$row->income}}</td> 
                            <td class="text-center">{{$row->icode}}</td>                                
                            <td class="text-start">{{$row->name}}</td>            
                            <td class="text-end fw-bold text-secondary">{{number_format($row->price,2)}}</td>
                            <td class="text-center">{{$row->billcode}}</td>
                            <td class="text-center small">{{$row->nhso_adp_code}}</td> 
                            <td class="text-start small text-muted">{{$row->nhso_adp_code_name}}</td>
                            <td class="text-center small">{{$row->nhso_adp_type_name}}</td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>         
            </div>
        </div> 
    </div>
</div>

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {
      $('#nondrugitems').DataTable({
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
              title: 'ค่ารักษาพยาบาล ที่เปิดใช้งาน'
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
  <script>
    $(document).ready(function () {
      $('#nondrugitems_non').DataTable({
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
              title: 'ค่ารักษาพยาบาล ที่ปิดใช้งาน'
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

