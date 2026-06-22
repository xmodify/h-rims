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
                <i class="bi bi-clipboard-pulse text-warning me-2"></i>
                ตรวจสอบ Lab Catalog
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบความถูกต้องของรหัสแล็บ TMLT/LOINC และราคาระหว่าง HOSxP และ สปสช.</div>
        </div>
        <div class="d-flex gap-2">
            @if ($message = Session::get('success'))
                <div class="badge bg-success-soft text-success px-3 py-2 rounded-pill shadow-sm animate__animated animate__fadeIn">
                    <i class="bi bi-check-circle-fill me-1"></i> นำเข้าข้อมูลสำเร็จ: {{ $message }}
                </div>
            @endif
            @if ($errors->any())
                <div class="badge bg-danger-soft text-danger px-3 py-2 rounded-pill shadow-sm animate__animated animate__fadeIn">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Toolbar & Import Card -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-excel me-2 text-success"></i> นำเข้าไฟล์ Lab Catalog สปสช.</h6>
                    <form id="importForm" action="{{ url('check/labcat_nhso_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
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
                        <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso') }}">
                            <i class="bi bi-list-check me-1"></i> ทั้งหมด
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_non_nhso') }}">
                            <i class="bi bi-search me-1"></i> ไม่พบที่ สปสช.
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_price_notmatch_hosxp') }}">
                            <i class="bi bi-currency-dollar me-1"></i> ราคาไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_tmlt_notmatch_hosxp') }}">
                            <i class="bi bi-upc-scan me-1"></i> TMLT ไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_loinc_notmatch_hosxp') }}">
                            <i class="bi bi-hash me-1"></i> LOINC ไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_tmlt_missing_hosxp') }}">
                            <i class="bi bi-patch-question me-1"></i> ยังไม่ผูก TMLT
                        </a>
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/labcat_nhso_loinc_missing_hosxp') }}">
                            <i class="bi bi-patch-question me-1"></i> ยังไม่ผูก LOINC
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="labTab" role="tablist" style="border-bottom: 2px solid #dee2e6;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark px-4" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual-pane" type="button" role="tab" style="border-radius: 10px 10px 0 0;">
                <i class="bi bi-file-earmark-text text-primary me-2"></i>รายการ (I) 
                <span class="badge bg-primary ms-2 rounded-pill">{{ count($items_i) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark px-4" id="panel-tab" data-bs-toggle="tab" data-bs-target="#panel-pane" type="button" role="tab" style="border-radius: 10px 10px 0 0;">
                <i class="bi bi-collection text-success me-2"></i>ชุดการตรวจ (P) 
                <span class="badge bg-success ms-2 rounded-pill">{{ count($items_p) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="labTabContent">
        <!-- Tab 1: Individual -->
        <div class="tab-pane fade show active" id="individual-pane" role="tabpanel" aria-labelledby="individual-tab">
            <!-- Data Table Card -->
            <div class="card dash-card border-top-0">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="labTableI" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">สปสช.</th>   
                                    <th class="text-center" rowspan="2">รหัสแล็บ HOSxP</th>             
                                    <th class="text-center" rowspan="2" width="15%">ชื่อแล็บ HOSxP</th>             
                                    <th class="text-center" rowspan="2">รหัสเบิก (icode)</th>             
                                    <th class="text-center" colspan="2" style="background-color: #f8fafc; border-bottom-color: #cbd5e1 !important;">ชื่อรายการ</th>                   
                                    <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ราคา</th>                   
                                    <th class="text-center" colspan="2" style="background-color: #f0f9ff; border-bottom-color: #bae6fd !important;">รหัส TMLT</th> 
                                    <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">รหัส LOINC</th>                                      
                                </tr>
                                <tr>                    
                                    <th class="text-center small" style="background-color: #f8fafc" width="15%">HOSxP</th>   
                                    <th class="text-center small" style="background-color: #f8fafc" width="15%">สปสช.</th> 
                                    <th class="text-center small" style="background-color: #e0f2fe">HOSxP</th>   
                                    <th class="text-center small" style="background-color: #e0f2fe">สปสช.</th> 
                                    <th class="text-center small" style="background-color: #f0f9ff">HOSxP</th> 
                                    <th class="text-center small" style="background-color: #f0f9ff">สปสช.</th>
                                    <th class="text-center small" style="background-color: #f5f3ff">HOSxP</th> 
                                    <th class="text-center small" style="background-color: #f5f3ff">สปสช.</th>  
                                </tr>
                            </thead>                          
                            <tbody>
                                @foreach($items_i as $row)          
                                <tr>          
                                    <td class="text-center" data-order="{{ $row->chk_nhso_labcat == 'Y' ? 1 : 0 }}">
                                        @if($row->chk_nhso_labcat == 'Y')
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger text-opacity-25"></i>
                                        @endif
                                    </td>                 
                                    <td class="text-center fw-bold">{{ $row->lab_items_code }}</td>                          
                                    <td class="text-start small fw-bold text-dark">{{ $row->lab_items_name }}</td>                          
                                    <td class="text-center fw-bold text-muted">{{ $row->icode ?: '-' }}</td>                          
                                    <td class="text-start small text-dark">{{ $row->nondrug_name ?: '-' }}</td>                        
                                    <td class="text-start small text-secondary">{{ $row->name_nhso ?: '-' }}</td>                        
                                    <td class="text-end small">{{ $row->service_price !== null ? number_format($row->service_price, 2) : '-' }}</td>
                                    <td class="text-end small fw-bold {{ ($row->price_nhso !== null && $row->price_nhso != $row->service_price) ? 'text-danger' : 'text-success' }}">
                                        {{ $row->price_nhso !== null ? number_format($row->price_nhso, 2) : '-' }}
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->tmlt_code ?: '-' }}</td>
                                    <td class="text-center small fw-bold {{ ($row->tmlt_nhso !== null && $row->tmlt_nhso != $row->tmlt_code) ? 'text-danger' : 'text-primary' }}">
                                        {{ $row->tmlt_nhso ?: '-' }}
                                    </td>                                    
                                    <td class="text-center small text-muted">{{ $row->loinc_code ?: '-' }}</td>
                                    <td class="text-center small fw-bold {{ ($row->loinc_nhso !== null && $row->loinc_nhso != $row->loinc_code) ? 'text-danger' : 'text-info' }}">
                                        {{ $row->loinc_nhso ?: '-' }}
                                    </td>
                                </tr>      
                                @endforeach 
                            </tbody>
                        </table> 
                    </div>
                </div>
            </div>

            <!-- Unmapped Lab Items Table Card -->
            <div class="card dash-card mt-4 border-danger border-top border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title text-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>รายการแล็บเปิดใช้งาน แต่ยังไม่ผูกราคาใน HOSxP
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="labTableUnmappedI" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" width="15%">รหัสแล็บ HOSxP</th>
                                    <th class="text-start">ชื่อแล็บ HOSxP</th>
                                    <th class="text-center" width="20%">รหัสเบิก (icode)</th>
                                    <th class="text-center" width="20%">รหัส TMLT</th>
                                    <th class="text-center" width="20%">รหัส LOINC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items_unmapped_i as $row)
                                <tr>
                                    <td class="text-center fw-bold">{{ $row->lab_items_code }}</td>
                                    <td class="text-start small fw-bold text-dark">{{ $row->lab_items_name }}</td>
                                    <td class="text-center text-danger small fw-bold bg-danger bg-opacity-10">{{ $row->icode ?: 'ยังไม่ได้ระบุ' }}</td>
                                    <td class="text-center small text-muted">{{ $row->tmlt_code ?: '-' }}</td>
                                    <td class="text-center small text-muted">{{ $row->loinc_code ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Panel -->
        <div class="tab-pane fade" id="panel-pane" role="tabpanel" aria-labelledby="panel-tab">
            <!-- Data Table Card -->
            <div class="card dash-card border-top-0">
                <div class="card-body p-4">
                    <div class="table-responsive">            
                        <table id="labTableP" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" rowspan="2">สปสช.</th>   
                                    <th class="text-center" rowspan="2">รหัสแล็บ HOSxP</th>             
                                    <th class="text-center" rowspan="2" width="15%">ชื่อแล็บ HOSxP</th>             
                                    <th class="text-center" rowspan="2">รหัสเบิก (icode)</th>             
                                    <th class="text-center" colspan="2" style="background-color: #f8fafc; border-bottom-color: #cbd5e1 !important;">ชื่อรายการ</th>                   
                                    <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ราคา</th>                   
                                    <th class="text-center" colspan="2" style="background-color: #f0f9ff; border-bottom-color: #bae6fd !important;">รหัส TMLT</th> 
                                    <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">รหัส LOINC</th>                                      
                                </tr>
                                <tr>                    
                                    <th class="text-center small" style="background-color: #f8fafc" width="15%">HOSxP</th>   
                                    <th class="text-center small" style="background-color: #f8fafc" width="15%">สปสช.</th> 
                                    <th class="text-center small" style="background-color: #e0f2fe">HOSxP</th>   
                                    <th class="text-center small" style="background-color: #e0f2fe">สปสช.</th> 
                                    <th class="text-center small" style="background-color: #f0f9ff">HOSxP</th> 
                                    <th class="text-center small" style="background-color: #f0f9ff">สปสช.</th>
                                    <th class="text-center small" style="background-color: #f5f3ff">HOSxP</th> 
                                    <th class="text-center small" style="background-color: #f5f3ff">สปสช.</th>  
                                </tr>
                            </thead>                          
                            <tbody>
                                @foreach($items_p as $row)          
                                <tr>          
                                    <td class="text-center" data-order="{{ $row->chk_nhso_labcat == 'Y' ? 1 : 0 }}">
                                        @if($row->chk_nhso_labcat == 'Y')
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger text-opacity-25"></i>
                                        @endif
                                    </td>                 
                                    <td class="text-center fw-bold">{{ $row->lab_items_code }}</td>                          
                                    <td class="text-start small fw-bold text-dark">{{ $row->lab_items_name }}</td>                          
                                    <td class="text-center fw-bold text-muted">{{ $row->icode ?: '-' }}</td>                          
                                    <td class="text-start small text-dark">{{ $row->nondrug_name ?: '-' }}</td>                        
                                    <td class="text-start small text-secondary">{{ $row->name_nhso ?: '-' }}</td>                        
                                    <td class="text-end small">{{ $row->service_price !== null ? number_format($row->service_price, 2) : '-' }}</td>
                                    <td class="text-end small fw-bold {{ ($row->price_nhso !== null && $row->price_nhso != $row->service_price) ? 'text-danger' : 'text-success' }}">
                                        {{ $row->price_nhso !== null ? number_format($row->price_nhso, 2) : '-' }}
                                    </td> 
                                    <td class="text-center small text-muted">{{ $row->tmlt_code ?: '-' }}</td>
                                    <td class="text-center small fw-bold {{ ($row->tmlt_nhso !== null && $row->tmlt_nhso != $row->tmlt_code) ? 'text-danger' : 'text-primary' }}">
                                        {{ $row->tmlt_nhso ?: '-' }}
                                    </td>                                    
                                    <td class="text-center small text-muted">{{ $row->loinc_code ?: '-' }}</td>
                                    <td class="text-center small fw-bold {{ ($row->loinc_nhso !== null && $row->loinc_nhso != $row->loinc_code) ? 'text-danger' : 'text-info' }}">
                                        {{ $row->loinc_nhso ?: '-' }}
                                    </td>
                                </tr>      
                                @endforeach 
                            </tbody>
                        </table> 
                    </div>
                </div>
            </div>

            <!-- Unmapped Lab Items Table Card -->
            <div class="card dash-card mt-4 border-danger border-top border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title text-danger mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>ชุดการตรวจเปิดใช้งาน แต่ยังไม่ผูกราคาใน HOSxP
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="labTableUnmappedP" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" width="15%">รหัสแล็บ HOSxP</th>
                                    <th class="text-start">ชื่อแล็บ HOSxP</th>
                                    <th class="text-center" width="20%">รหัสเบิก (icode)</th>
                                    <th class="text-center" width="20%">รหัส TMLT</th>
                                    <th class="text-center" width="20%">รหัส LOINC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items_unmapped_p as $row)
                                <tr>
                                    <td class="text-center fw-bold">{{ $row->lab_items_code }}</td>
                                    <td class="text-start small fw-bold text-dark">{{ $row->lab_items_name }}</td>
                                    <td class="text-center text-danger small fw-bold bg-danger bg-opacity-10">{{ $row->icode ?: 'ยังไม่ได้ระบุ' }}</td>
                                    <td class="text-center small text-muted">{{ $row->tmlt_code ?: '-' }}</td>
                                    <td class="text-center small text-muted">{{ $row->loinc_code ?: '-' }}</td>
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
      @if ($errors->any())
        Swal.fire({
            title: 'เกิดข้อผิดพลาด!',
            text: '{!! $errors->first() !!}',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
      @endif

      const datatableConfig = {
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
              title: 'ตรวจสอบ Lab Catalog สปสช.'
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
        },
        orderCellsTop: true,
        order: [[3, 'asc']] // Sort by name (column index 3)
      };

      $('#labTableI').DataTable(datatableConfig);
      $('#labTableP').DataTable(datatableConfig);

      const datatableConfigUnmappedI = {
        ...datatableConfig,
        order: [[1, 'asc']],
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'รายการแล็บ Test เปิดใช้งานแต่ยังไม่ผูก Nondrug'
            }
        ]
      };
      const datatableConfigUnmappedP = {
        ...datatableConfig,
        order: [[1, 'asc']],
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'รายการแล็บ Profile เปิดใช้งานแต่ยังไม่ผูก Nondrug'
            }
        ]
      };
      $('#labTableUnmappedI').DataTable(datatableConfigUnmappedI);
      $('#labTableUnmappedP').DataTable(datatableConfigUnmappedP);
    });
  </script>
@endpush
