@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-shield-check text-success me-2"></i>
                จัดการสิทธิการรักษา (Pttype Management)
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบและตรวจสอบความสอดคล้องของรหัสสิทธิการรักษาในระบบ</div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success-soft text-success px-3 py-2 rounded-pill">
                <i class="bi bi-check-circle me-1"></i> เปิดใช้งานอยู่
            </span>
        </div>
    </div>

    <!-- Active Pttype Card -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-primary">
                <i class="bi bi-table me-2"></i> สิทธิการรักษาที่เปิดใช้งาน
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="pttype" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" colspan="8">ตาราง pttype (HOSxP)</th>
                            <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ตาราง provis_instype</th>                
                        </tr>
                        <tr>
                            <th class="text-center">สปสช</th>  
                            <th class="text-center">รหัส</th>
                            <th class="text-center">ชื่อสิทธิ</th>  
                            <th class="text-center">ประเภท</th>     
                            <th class="text-center">Eclaim</th>
                            <th class="text-center">Hipdata</th> 
                            <th class="text-center">ส่งออก</th>
                            <th class="text-center">กลุ่มราคา</th>
                            <th class="text-center" style="background-color: #f0f9ff">ชื่อสิทธิ</th>
                            <th class="text-center" style="background-color: #f0f9ff">รหัสส่งออก</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($pttype as $row) 
                        <tr>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{$row->nhso_subinscl}}</span></td> 
                            <td class="text-center fw-bold">{{$row->pttype}}</td>                                
                            <td class="text-start">{{$row->name}}</td>            
                            <td class="text-start small">{{ $row->paidst }}</td>
                            <td class="text-center">{{$row->export_eclaim}}</td>
                            <td class="text-center">{{$row->hipdata_code}}</td> 
                            <td class="text-center">{{$row->pttype_std_code}}</td>
                            <td class="text-start small text-muted">{{$row->pttype_price_group_name}}</td>
                            <td class="text-start small">{{$row->pi_name}}</td>  
                            <td class="text-center small">{{$row->pi_pttype_std_code}}</td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table>         
            </div>
        </div> 
    </div>

    <!-- Inactive Pttype Card -->
    <div class="card dash-card border-top-0">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="mb-0 fw-bold text-secondary">
                <i class="bi bi-eye-slash me-2"></i> สิทธิการรักษาที่ปิดใช้งาน
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="pttype_close" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" colspan="8">ตาราง pttype (HOSxP)</th>
                            <th class="text-center" colspan="2">ตาราง provis_instype</th>                
                        </tr>
                        <tr>
                            <th class="text-center text-secondary">สปสช</th>  
                            <th class="text-center text-secondary">รหัส</th>
                            <th class="text-center text-secondary">ชื่อสิทธิ</th>  
                            <th class="text-center text-secondary">ประเภท</th>     
                            <th class="text-center text-secondary">Eclaim</th>
                            <th class="text-center text-secondary">Hipdata</th> 
                            <th class="text-center text-secondary">ส่งออก</th>
                            <th class="text-center text-secondary">กลุ่มราคา</th>
                            <th class="text-center text-secondary">ชื่อสิทธิ</th>
                            <th class="text-center text-secondary">รหัสส่งออก</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($pttype_close as $row) 
                        <tr class="opacity-75">
                            <td class="text-center">{{$row->nhso_subinscl}}</td> 
                            <td class="text-center">{{$row->pttype}}</td>                                
                            <td class="text-start">{{$row->name}}</td>            
                            <td class="text-start small">{{ $row->paidst }}</td>
                            <td class="text-center">{{$row->export_eclaim}}</td>
                            <td class="text-center">{{$row->hipdata_code}}</td> 
                            <td class="text-center">{{$row->pttype_std_code}}</td>
                            <td class="text-start small text-muted">{{$row->pttype_price_group_name}}</td>
                            <td class="text-start small">{{$row->pi_name}}</td>  
                            <td class="text-center small">{{$row->pi_pttype_std_code}}</td>
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
      $('#pttype').DataTable({
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
              title: 'สิทธิการรักษา ที่เปิดใช้งาน'
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
      $('#pttype_close').DataTable({
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
              title: 'สิทธิการรักษา ที่ปิดใช้งาน'
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

