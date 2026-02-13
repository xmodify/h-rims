@extends('layouts.app')    
    
@section('content')
    <style>
        .btn-outline-purple {
            color: #6f42c1;
            border: 1px solid #6f42c1;
            background-color: transparent;
        }
        .btn-outline-purple:hover {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
    </style>

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-capsule text-primary me-2"></i>
                ตรวจสอบ Drug Catalog
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบความถูกต้องของรหัสยาและราคาระหว่าง HOSxP และ NHSO</div>
        </div>
        <div class="d-flex gap-2">
            @if ($message = Session::get('success'))
                <div class="badge bg-success-soft text-success px-3 py-2 rounded-pill shadow-sm animate__animated animate__fadeIn">
                    <i class="bi bi-check-circle-fill me-1"></i> นำเข้าข้อมูลสำเร็จ: {{ $message }}
                </div>
            @endif
        </div>
    </div>

    <!-- Toolbar & Import Card -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ Drug Catalog NHSO</h6>
                    <form id="importForm" action="{{ url('check/drug_cat_nhso_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf  
                        <div class="input-group">
                            <input class="form-control" id="formFile" name="file" type="file" required style="border-radius: 10px 0 0 10px;">
                            <button type="button" onclick="handleImportSubmit(event)" class="btn btn-success px-4" style="border-radius: 0 10px 10px 0;">
                                <i class="bi bi-cloud-upload me-1"></i> นำเข้า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-funnel me-2 text-primary"></i> ตัวกรองข้อมูล</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="{{ url('check/drug_cat') }}">
                            <i class="bi bi-list-check me-1"></i> ทั้งหมด
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drug_cat_non_nhso') }}">
                            <i class="bi bi-search me-1"></i> ไม่พบที่ NHSO
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drug_cat_nhso_price_notmatch_hosxp') }}">
                            <i class="bi bi-currency-dollar me-1"></i> ราคาไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drug_cat_nhso_tmt_notmatch_hosxp') }}">
                            <i class="bi bi-upc-scan me-1"></i> TMT ไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drug_cat_nhso_code24_notmatch_hosxp') }}">
                            <i class="bi bi-hash me-1"></i> 24 หลักไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drug_cat_herb') }}">
                            <i class="bi bi-leaf me-1"></i> ยาสมุนไพร
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0">
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="drug" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" rowspan="2">NHSO</th>   
                            <th class="text-center" rowspan="2">รหัส HOSxP</th>             
                            <th class="text-center" rowspan="2" width="25%">ชื่อยา</th>                   
                            <th class="text-center" rowspan="2">หน่วยนับ</th>
                            <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ราคา</th>                   
                            <th class="text-center" colspan="2" style="background-color: #f0f9ff; border-bottom-color: #bae6fd !important;">รหัส TMT</th> 
                            <th class="text-center" colspan="2" style="background-color: #eef2ff; border-bottom-color: #c7d2fe !important;">ยาสมุนไพร</th>                  
                            <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">รหัส 24 หลัก</th>                                      
                        </tr>
                        <tr>                    
                            <th class="text-center small" style="background-color: #e0f2fe">HOSxP</th>   
                            <th class="text-center small" style="background-color: #e0f2fe">NHSO</th> 
                            <th class="text-center small" style="background-color: #f0f9ff">HOSxP</th> 
                            <th class="text-center small" style="background-color: #f0f9ff">NHSO</th>
                            <th class="text-center small" style="background-color: #eef2ff">TTMT</th> 
                            <th class="text-center small" style="background-color: #eef2ff">HERB</th>   
                            <th class="text-center small" style="background-color: #f5f3ff">HOSxP</th> 
                            <th class="text-center small" style="background-color: #f5f3ff">NHSO</th>  
                        </tr>
                    </thead>                          
                    <tbody>
                        @foreach($drug as $row)          
                        <tr>          
                            <td class="text-center">
                                @if($row->chk_nhso_drugcat == 'Y')
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                @else
                                    <i class="bi bi-x-circle-fill text-danger text-opacity-25"></i>
                                @endif
                            </td>                 
                            <td class="text-center fw-bold">{{ $row->icode }}</td>                          
                            <td class="text-start small fw-bold text-dark">{{ $row->dname }}</td>                        
                            <td class="text-start small text-muted">{{ $row->units }}</td>
                            <td class="text-end small">{{ number_format($row->price_hos,2) }}</td>
                            <td class="text-end small fw-bold {{ $row->price_nhso != $row->price_hos ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row->price_nhso,2) }}
                            </td> 
                            <td class="text-center small text-muted">{{ $row->code_tmt_hos }}</td>
                            <td class="text-center small fw-bold {{ $row->code_tmt_nhso != $row->code_tmt_hos ? 'text-danger' : 'text-primary' }}">
                                {{ $row->code_tmt_nhso }}
                            </td>                                    
                            <td class="text-center small text-muted">{{ $row->ttmt_code }}</td>
                            <td class="text-center small"><span class="badge {{ $row->herb == 'Y' ? 'bg-success-soft text-success' : 'bg-light text-muted' }}">{{ $row->herb }}</span></td>
                            <td class="text-center small text-muted">{{ $row->code_24_hos }}</td>
                            <td class="text-center small fw-bold {{ $row->code_24_nhso != $row->code_24_hos ? 'text-danger' : 'text-info' }}">
                                {{ $row->code_24_nhso }}
                            </td>
                        </tr>      
                        @endforeach 
                    </tbody>
                </table> 
            </div>
        </div>
    </div>
</div>
<br>

@endsection

@push('scripts')  
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

    function handleImportSubmit(e) {
        const fileInput = document.getElementById('formFile');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                title: 'แจ้งเตือน',
                text: 'กรุณาเลือกไฟล์ก่อนนำเข้า',
                icon: 'warning',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        showLoadingAlert();
        document.getElementById('importForm').submit();
    }

    $(document).ready(function () {
      @if (session('success'))
        Swal.fire({
            title: 'นำเข้าสำเร็จ!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonText: 'ตกลง'
        });
      @endif

      $('#drug').DataTable({
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
              title: 'ตรวจสอบ Drug Catalog'
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
