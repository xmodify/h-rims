@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4 d-flex justify-content-between align-items-center">
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

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills nav-pills-custom mb-3 gap-2" id="subinsclTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active position-relative px-4 py-2-5 rounded-pill shadow-sm" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-panel" type="button" role="tab" aria-controls="all-panel" aria-selected="true">
                <i class="bi bi-list-ul me-2"></i> INSCL สปสช
                <span class="badge bg-secondary ms-2">{{ count($subinscl) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link position-relative px-4 py-2-5 rounded-pill shadow-sm" id="found-tab" data-bs-toggle="tab" data-bs-target="#found-panel" type="button" role="tab" aria-controls="found-panel" aria-selected="false">
                <i class="bi bi-check-circle-fill text-success me-2"></i> พบที่ HOSxP
                <span class="badge bg-success ms-2">{{ count($subinscl_found) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link position-relative px-4 py-2-5 rounded-pill shadow-sm" id="notfound-tab" data-bs-toggle="tab" data-bs-target="#notfound-panel" type="button" role="tab" aria-controls="notfound-panel" aria-selected="false">
                <i class="bi bi-x-circle-fill text-danger me-2"></i> ไม่พบที่ HOSxP
                <span class="badge bg-danger ms-2">{{ count($subinscl_notfound) }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="subinsclTabContent">
        <!-- Panel 1: All Subinscl -->
        <div class="tab-pane fade show active" id="all-panel" role="tabpanel" aria-labelledby="all-tab">
            <div class="card dash-card border-top-0">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="table-all" class="table table-modern w-100">
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
                                    <td class="text-center fw-bold text-primary">{{ $row->pttype ?: '-' }}</td>
                                    <td class="text-start small">{{$row->pttype_name ?: '-'}}</td>
                                    <td class="text-center small">{{$row->hipdata_code ?: '-'}}</td>             
                                </tr>
                                @endforeach                 
                            </tbody>
                        </table>         
                    </div>
                </div> 
            </div>
        </div>

        <!-- Panel 2: Found in HOSxP -->
        <div class="tab-pane fade" id="found-panel" role="tabpanel" aria-labelledby="found-tab">
            <div class="card dash-card border-top-0">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="table-found" class="table table-modern w-100">
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
                                @foreach($subinscl_found as $row) 
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

        <!-- Panel 3: Not Found in HOSxP -->
        <div class="tab-pane fade" id="notfound-panel" role="tabpanel" aria-labelledby="notfound-tab">
            <div class="card dash-card border-top-0">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="table-notfound" class="table table-modern w-100">
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
                                @foreach($subinscl_notfound as $row) 
                                <tr>
                                    <td class="text-center fw-bold text-dark">{{$row->code}}</td> 
                                    <td class="text-start">{{$row->name}}</td>                                
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{$row->maininscl}}</span></td>            
                                    <td class="text-center fw-bold text-muted">-</td>
                                    <td class="text-start small text-muted">-</td>
                                    <td class="text-center small text-muted">-</td>             
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

<style>
    .nav-pills-custom .nav-link {
        background: #fff;
        color: #64748b;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    .nav-pills-custom .nav-link:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    .nav-pills-custom .nav-link.active {
        background: #0284c7;
        color: #fff;
        border-color: #0284c7;
    }
    .nav-pills-custom .nav-link.active .badge {
        background: rgba(255, 255, 255, 0.2) !important;
        color: #fff !important;
    }
</style>

@endsection

@push('scripts')  
  <script>
    $(document).ready(function () {
      const dataTableConfig = {
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
      };

      $('#table-all').DataTable(dataTableConfig);
      $('#table-found').DataTable(dataTableConfig);
      $('#table-notfound').DataTable(dataTableConfig);
    });
  </script>
@endpush
