@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-person-badge-fill text-primary me-2"></i>
                สิทธิการรักษา สปสช (NHSO Sub-Insurance Classes)
            </h5>
            <div class="text-muted small mt-1">เปรียบเทียบรหัสสิทธิการรักษาของ สปสช กับระบบ HOSxP ภายในโรงพยาบาล</div>
        </div>
        <div>
            <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">
                <i class="bi bi-info-circle me-1"></i> ข้อมูลอ้างอิงมาตรฐาน
            </span>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="subinscl" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" colspan="3">ข้อมูล สปสช (NHSO)</th>
                            <th class="text-center" colspan="3" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ข้อมูล HOSxP</th>                
                        </tr>
                        <tr>
                            <th class="text-center">CODE</th>  
                            <th class="text-center">NAME</th>
                            <th class="text-center">MAININSCL</th>  
                            <th class="text-center" style="background-color: #f0f9ff">PTTYPE</th>     
                            <th class="text-center" style="background-color: #f0f9ff">PTTYPE_NAME</th>
                            <th class="text-center" style="background-color: #f0f9ff">Hipdata_code</th> 
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($subinscl as $row) 
                        <tr>
                            <td class="text-center fw-bold text-dark">{{$row->code}}</td> 
                            <td class="text-start">{{$row->name}}</td>                                
                            <td class="text-center"><span class="badge bg-light text-dark border">{{$row->maininscl}}</span></td>            
                            <td class="text-center fw-bold text-primary">{{ $row->pttype }}</td>
                            <td class="text-start small">{{$row->pttype_name}}</td>
                            <td class="text-center small">{{$row->hipdata_code}}</td>             
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
      $('#subinscl').DataTable({
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
              title: 'สิทธิการรักษา สปสช'
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


