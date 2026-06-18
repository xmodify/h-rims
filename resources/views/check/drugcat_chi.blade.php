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
                ตรวจสอบ Drug Catalog สกส.
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบความถูกต้องของรหัสยาและราคาระหว่าง HOSxP และ สกส. (CSMBS)</div>
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
                    <form id="importForm" action="{{ url('check/drugcat_chi_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf  
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" name="file" type="file" required style="border-radius: 10px 0 0 10px;">
                            <button type="button" onclick="handleImportSubmit(event)" class="btn btn-success px-4" style="border-radius: 0 10px 10px 0;">
                                <i class="bi bi-cloud-upload me-1"></i> นำเข้า
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-3 text-muted opacity-25">
                    
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-arrow-down me-2 text-primary"></i> ส่งออกไฟล์ Drug Catalog สกส.</h6>
                    <div class="row g-2 align-items-end">
                        <div class="col-sm-4">
                            <label for="seq_no" class="form-label small text-muted mb-1">งวดที่ส่ง (Sequence)</label>
                            <input type="text" id="seq_no" class="form-control text-center fw-bold" value="001" placeholder="001" maxlength="3" style="border-radius: 8px; height: 38px;">
                        </div>
                        <div class="col-sm-8 d-flex flex-column gap-2">
                            <button type="button" onclick="exportData('new')" class="btn btn-primary btn-sm rounded-pill w-100" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="bi bi-plus-circle me-1"></i> ส่งออกรายการใหม่ (A)
                            </button>
                            <button type="button" onclick="exportData('edit')" class="btn btn-warning btn-sm rounded-pill w-100" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="bi bi-pencil-square me-1"></i> ส่งออกรายการแก้ไขข้อมูลยา (E)
                            </button>
                            <button type="button" onclick="exportData('update')" class="btn btn-info btn-sm rounded-pill w-100 text-white" style="height: 38px; display: inline-flex; align-items: center; justify-content: center; background-color: #0dcaf0; border-color: #0dcaf0;">
                                <i class="bi bi-currency-dollar me-1"></i> ส่งออกรายการแก้ไขราคา (U)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card dash-card h-100">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-funnel me-2 text-primary"></i> ตัวกรองข้อมูล</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-outline-primary btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi') }}">
                            <i class="bi bi-list-check me-1"></i> ทั้งหมด
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_non_nhso') }}">
                            <i class="bi bi-search me-1"></i> ไม่พบที่ สกส.
                        </a>  
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_price_notmatch_hosxp') }}">
                            <i class="bi bi-currency-dollar me-1"></i> ราคาไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_tmt_notmatch_hosxp') }}">
                            <i class="bi bi-upc-scan me-1"></i> TMT ไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_code24_notmatch_hosxp') }}">
                            <i class="bi bi-hash me-1"></i> 24 หลักไม่ตรง
                        </a> 
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_herb') }}">
                            <i class="bi bi-leaf me-1"></i> ยาสมุนไพร
                        </a>
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_ised_notmatch_hosxp') }}">
                            <i class="bi bi-exclamation-triangle me-1"></i> บัญชียา ED/NED ไม่ตรง
                        </a>
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_code24_missing_hosxp') }}">
                            <i class="bi bi-patch-question me-1"></i> ยังไม่ผูก 24 หลัก
                        </a>
                        <a class="btn btn-outline-purple btn-sm rounded-pill px-3" href="{{ url('check/drugcat_chi_tmt_missing_hosxp') }}">
                            <i class="bi bi-patch-question me-1"></i> ยังไม่ผูก TMT
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
                            <th class="text-center" rowspan="2" style="width: 40px; vertical-align: middle;"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                            <th class="text-center" rowspan="2" style="width: 80px; vertical-align: middle;">ตรวจสอบ</th>
                            <th class="text-center" rowspan="2">สกส.</th>   
                            <th class="text-center" rowspan="2">รหัส HOSxP</th>             
                            <th class="text-center" rowspan="2" width="22%">ชื่อยา</th>                   
                            <th class="text-center" rowspan="2">หน่วยนับ</th>
                            <th class="text-center" colspan="2" style="background-color: #e0f2fe; border-bottom-color: #bae6fd !important;">ราคา</th>                   
                            <th class="text-center" colspan="2" style="background-color: #f0f9ff; border-bottom-color: #bae6fd !important;">รหัส TMT</th> 
                            <th class="text-center" colspan="2" style="background-color: #eef2ff; border-bottom-color: #c7d2fe !important;">ยาสมุนไพร</th>                  
                            <th class="text-center" colspan="2" style="background-color: #f5f3ff; border-bottom-color: #ddd6fe !important;">รหัส 24 หลัก</th>                                      
                            <th class="text-center" colspan="2" style="background-color: #fff7ed; border-bottom-color: #ffedd5 !important;">บัญชียา (ED)</th>                                      
                        </tr>
                        <tr>                    
                            <th class="text-center small" style="background-color: #e0f2fe">HOSxP</th>   
                            <th class="text-center small" style="background-color: #e0f2fe">สกส.</th> 
                            <th class="text-center small" style="background-color: #f0f9ff">HOSxP</th> 
                            <th class="text-center small" style="background-color: #f0f9ff">สกส.</th>
                            <th class="text-center small" style="background-color: #eef2ff">TTMT</th> 
                            <th class="text-center small" style="background-color: #eef2ff">HERB</th>   
                            <th class="text-center small" style="background-color: #f5f3ff">HOSxP</th> 
                            <th class="text-center small" style="background-color: #f5f3ff">สกส.</th>  
                            <th class="text-center small" style="background-color: #fff7ed">HOSxP</th> 
                            <th class="text-center small" style="background-color: #fff7ed">สกส.</th>  
                        </tr>
                    </thead>                          
                    <tbody>
                        @foreach($drug as $row)          
                        <tr>          
                            <td class="text-center" style="vertical-align: middle;">
                                <input type="checkbox" name="selected_drugs[]" value="{{ $row->icode }}" class="form-check-input drug-checkbox">
                            </td>
                            @php
                                $has_error = empty($row->icode) || empty($row->code_tmt_hos) || empty($row->code_24_hos) || (strlen($row->code_24_hos) != 24) || empty($row->price_hos) || ($row->price_hos <= 0) || empty($row->GenericName) || empty($row->TradeName) || empty($row->DosageForm) || empty($row->units);
                            @endphp
                            <td class="text-center" style="vertical-align: middle;" data-order="{{ $has_error ? 0 : 1 }}">
                                <button type="button" class="btn btn-sm p-0 border-0 bg-transparent" onclick="showCompletenessModal('{{ $row->icode }}', '{{ addslashes($row->dname) }}', '{{ $row->code_tmt_hos }}', '{{ $row->code_24_hos }}', '{{ $row->price_hos }}', '{{ $row->ised_hos }}', '{{ addslashes($row->GenericName) }}', '{{ addslashes($row->TradeName) }}', '{{ addslashes($row->DosageForm) }}', '{{ addslashes($row->units) }}')">
                                    <i class="bi bi-eye-fill {{ $has_error ? 'text-danger' : 'text-success' }}" style="font-size: 1.15rem;"></i>
                                </button>
                            </td>
                            <td class="text-center" data-order="{{ $row->chk_nhso_drugcat == 'Y' ? 1 : 0 }}">
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
                            <td class="text-center small text-muted">
                                {{ $row->ised_hos }} @if($row->drugaccount) ({{ $row->drugaccount }}) @endif
                            </td>
                            @php
                                $mapped_ised_nhso = (isset($row->ised_nhso) && preg_match('/^E/i', $row->ised_nhso)) ? 'E' : 'N';
                            @endphp
                            <td class="text-center small fw-bold {{ $mapped_ised_nhso != $row->ised_hos ? 'text-danger' : 'text-success' }}">
                                {{ $row->ised_nhso ?: '-' }}
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

<!-- Modal for checking completeness -->
<div class="modal fade" id="completenessModal" tabindex="-1" aria-labelledby="completenessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-light border-bottom-0" style="border-radius: 15px 15px 0 0;">
                <h6 class="modal-title fw-bold text-dark" id="completenessModalLabel">
                    <i class="bi bi-shield-check text-primary me-2"></i> ตรวจสอบความสมบูรณ์ของข้อมูลยา
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 p-3 bg-light rounded-3">
                    <div class="fw-bold text-dark small" id="modalDrugName">ชื่อยา: -</div>
                    <div class="text-muted small mt-1" id="modalDrugCode">รหัส HOSxP: -</div>
                </div>
                
                <div class="list-group list-group-flush" id="checkListContainer">
                    <!-- Check items will be dynamically generated here -->
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Export Preview -->
<div class="modal fade" id="previewExportModal" tabindex="-1" aria-labelledby="previewExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header bg-light border-bottom-0" style="border-radius: 15px 15px 0 0;">
                <h6 class="modal-title fw-bold text-dark" id="previewExportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i> ตรวจสอบโครงสร้างข้อมูลก่อนส่งออก (Preview)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small border-0 d-flex align-items-center" style="background-color: rgba(13, 202, 240, 0.1); color: #055160; border-radius: 8px;">
                    <i class="bi bi-info-circle-fill me-2"></i> 
                    <span>แสดงตัวอย่างข้อมูลตามโครงสร้างไฟล์ ว 246 จำนวน <strong id="previewCount">0</strong> รายการที่เลือก</span>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover small text-nowrap" id="previewTable">
                        <thead class="bg-light sticky-top" style="top: 0;">
                            <tr>
                                <th>HOSPDRUGCODE</th>
                                <th>PRODUCTCAT</th>
                                <th>TMTID</th>
                                <th>SPECPREP</th>
                                <th>GENERICNAME</th>
                                <th>TRADENAME</th>
                                <th>DFSCODE</th>
                                <th>DOSAGEFORM</th>
                                <th>STRENGTH</th>
                                <th>CONTENT</th>
                                <th>UNITPRICE</th>
                                <th>DISTRIBUTOR</th>
                                <th>MANUFACTURER</th>
                                <th>ISED</th>
                                <th>NDC24</th>
                                <th>PACKSIZE</th>
                                <th>PACKPRICE</th>
                                <th>UPDATEFLAG</th>
                                <th>DATECHANGE</th>
                                <th>DATEUPDATE</th>
                                <th>DATEEFFECTIVE</th>
                                <th>Reimbprice</th>
                            </tr>
                        </thead>
                        <tbody id="previewTableBody">
                            <!-- Data rows will be dynamically appended here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success rounded-pill px-4" id="confirmExportBtn">
                    <i class="bi bi-check-circle me-1"></i> ยืนยันส่งออกไฟล์ Excel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')  
  <script>
    let currentExportType = '';
    let currentCheckedBoxes = [];

    function showCompletenessModal(icode, name, tmt, ndc24, price, ised, generic, trade, dosage, units) {
        document.getElementById('modalDrugName').innerText = 'ชื่อยา: ' + name;
        document.getElementById('modalDrugCode').innerText = 'รหัส HOSxP: ' + icode;
        
        const container = document.getElementById('checkListContainer');
        container.innerHTML = '';
        
        const fields = [
            { label: 'รหัสยาโรงพยาบาล (icode)', value: icode, check: !!icode },
            { label: 'รหัส TMT ID', value: tmt, check: !!tmt },
            { label: 'รหัส 24 หลัก (NDC24)', value: ndc24, check: !!ndc24 && ndc24.length === 24, err: ndc24 && ndc24.length !== 24 ? 'ต้องยาว 24 หลัก' : 'ห้ามว่าง' },
            { label: 'ราคายา (UnitPrice)', value: price ? parseFloat(price).toFixed(2) + ' บาท' : '-', check: !!price && parseFloat(price) > 0 },
            { label: 'ชื่อสามัญ (Generic Name)', value: generic, check: !!generic },
            { label: 'ชื่อการค้า (Trade Name)', value: trade, check: !!trade },
            { label: 'รูปแบบยา (Dosage Form)', value: dosage, check: !!dosage },
            { label: 'หน่วยนับ (Content)', value: units, check: !!units }
        ];
        
        fields.forEach(f => {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2';
            
            const leftDiv = document.createElement('div');
            leftDiv.className = 'd-flex flex-column';
            const labelSpan = document.createElement('span');
            labelSpan.className = 'small fw-semibold text-dark';
            labelSpan.innerText = f.label;
            const valSpan = document.createElement('span');
            valSpan.className = 'small text-muted';
            valSpan.innerText = f.value || '(ว่าง)';
            
            leftDiv.appendChild(labelSpan);
            leftDiv.appendChild(valSpan);
            
            const badge = document.createElement('span');
            if (f.check) {
                badge.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
            } else {
                const errMsg = f.err || 'ห้ามว่าง';
                badge.innerHTML = `<span class="badge bg-danger-soft text-danger me-2" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">${errMsg}</span><i class="bi bi-x-circle-fill text-danger fs-5"></i>`;
            }
            
            item.appendChild(leftDiv);
            item.appendChild(badge);
            container.appendChild(item);
        });
        
        $('#completenessModal').modal('show');
    }

    function exportData(type) {
        // Find checked checkboxes using DataTable API to catch checkboxes across all pages
        const table = $('#drug').DataTable();
        const checkedBoxes = [];
        table.$('.drug-checkbox:checked').each(function() {
            checkedBoxes.push(this.value);
        });

        if (checkedBoxes.length === 0) {
            Swal.fire({
                title: 'แจ้งเตือน',
                text: 'กรุณาเลือกรายการยาที่ต้องการส่งออกอย่างน้อย 1 รายการ หรือเลือกทั้งหมด',
                icon: 'warning',
                confirmButtonText: 'ตกลง'
            });
            return;
        }

        currentExportType = type;
        currentCheckedBoxes = checkedBoxes;

        // Show loading alert while loading preview
        Swal.fire({
            title: 'กำลังเตรียมข้อมูลตัวอย่าง...',
            text: 'กรุณารอสักครู่',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading()
            }
        });

        // Fetch preview data
        fetch('{{ url("check/drugcat_chi_export_preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                type: type,
                icodes: checkedBoxes
            })
        })
        .then(response => response.json())
        .then(res => {
            Swal.close();
            if (res.success) {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#previewTable')) {
                    $('#previewTable').DataTable().destroy();
                }

                document.getElementById('previewCount').innerText = res.data.length;
                const tbody = document.getElementById('previewTableBody');
                tbody.innerHTML = '';
                
                res.data.forEach(row => {
                    const tr = document.createElement('tr');
                    
                    const cells = [
                        row.HospDrugCode,
                        row.ProductCat,
                        row.TMTID,
                        row.SpecPrep,
                        row.GenericName,
                        row.TradeName,
                        row.DFSCode,
                        row.DosageForm,
                        row.Strength,
                        row.Content,
                        row.UnitPrice,
                        row.Distributor,
                        row.Manufacturer,
                        row.ISED,
                        row.NDC24,
                        row.Packsize,
                        row.Packprice,
                        row.UpdateFlag,
                        row.DateChange,
                        row.DateUpdate,
                        row.DateEffective,
                        row.Reimbprice
                    ];
                    
                    cells.forEach(val => {
                        const td = document.createElement('td');
                        td.innerText = val !== null ? val : '';
                        tr.appendChild(td);
                    });
                    
                    tbody.appendChild(tr);
                });
                
                // Show modal
                $('#previewExportModal').modal('show');
                
                // Initialize DataTable
                $('#previewTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    scrollX: true,
                    language: {
                        search: "ค้นหาในตารางพรีวิว:",
                        lengthMenu: "แสดง _MENU_ รายการ",
                        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                        paginate: {
                            previous: "ก่อนหน้า",
                            next: "ถัดไป"
                        }
                    }
                });
            } else {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message || 'ไม่สามารถดึงข้อมูลตัวอย่างได้',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        })
        .catch(err => {
            Swal.close();
            Swal.fire({
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้: ' + err.message,
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        });
    }

    // Adjust DataTable column widths when modal is fully shown
    $(document).ready(function() {
        $('#previewExportModal').on('shown.bs.modal', function () {
            if ($.fn.DataTable.isDataTable('#previewTable')) {
                $('#previewTable').DataTable().columns.adjust().draw();
            }
        });
    });

    // Set up click handler for confirmExportBtn
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('confirmExportBtn').addEventListener('click', function() {
            if (currentCheckedBoxes.length === 0 || !currentExportType) return;
            
            let seq = document.getElementById('seq_no').value.trim() || '001';
            seq = seq.padStart(3, '0');
            
            let baseUrl = '{{ url("check/drugcat_chi_export_new") }}';
            if (currentExportType === 'edit') {
                baseUrl = '{{ url("check/drugcat_chi_export_edit") }}';
            } else if (currentExportType === 'update') {
                baseUrl = '{{ url("check/drugcat_chi_export_update") }}';
            }
            
            // Close the preview modal
            $('#previewExportModal').modal('hide');
            
            // Create a temporary form to submit via POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = baseUrl + '/' + seq;
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            currentCheckedBoxes.forEach(code => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'icodes[]';
                input.value = code;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    });

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

      const table = $('#drug').DataTable({
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
              title: 'ตรวจสอบ Drug Catalog สกส.',
              exportOptions: {
                  columns: ':gt(1)'
              }
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
        columnDefs: [
            { orderable: false, targets: [0] } // disable sorting on checkbox column only
        ],
        orderCellsTop: true,
        order: [[4, 'asc']] // sort by name (column index 4 now)
      });

      // Handle "Check All" checkbox click
      $('#checkAll').on('click', function() {
         const rows = table.rows({ 'search': 'applied' }).nodes();
         $('input[name="selected_drugs[]"]', rows).prop('checked', this.checked);
      });

      // Update "Check All" when individual checkboxes are checked/unchecked
      $('#drug tbody').on('change', 'input[name="selected_drugs[]"]', function() {
         if(!this.checked) {
            const el = $('#checkAll').get(0);
            if(el && el.checked && ('indeterminate' in el)) {
               el.checked = false;
            }
         }
      });
    });
  </script>
@endpush
