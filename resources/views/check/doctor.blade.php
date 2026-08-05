@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-person-badge-fill text-primary me-2"></i>
                ตรวจสอบข้อมูลบุคลากรทางการแพทย์ (Medical Personnel Validation)
            </h5>
            <div class="text-muted small mt-1">ตรวจสอบความสมบูรณ์และถูกต้องของข้อมูลแพทย์/บุคลากรทางการแพทย์ (ตาราง doctor) เพื่อใช้ในการส่งออกเคลม</div>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill">
                <i class="bi bi-people-fill me-1"></i> ทั้งหมด {{ count($doctors) }} รายการ
            </span>
        </div>
    </div>

    @php
        $activeDocs = [];
        $inactiveDocs = [];
        $invalidLicenseDocs = [];
        $invalidCidDocs = [];

        foreach($doctors as $doc) {
            $lic = trim($doc->licenseno ?? '');
            $isLicValid = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
            
            $cid = trim($doc->cid ?? '');
            $isCidValid = (!empty($cid) && strlen($cid) === 13);
            
            $doc_errors = [];
            if (empty($lic)) {
                $doc_errors[] = 'ไม่มีเลขใบประกอบวิชาชีพ';
            } elseif (!$isLicValid) {
                $doc_errors[] = "เลขใบประกอบฯ '{$lic}' รูปแบบไม่ถูกต้อง (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลข)";
            }
            
            if (empty($cid)) {
                $doc_errors[] = 'ไม่มีเลขบัตรประชาชน';
            } elseif (!$isCidValid) {
                $doc_errors[] = 'เลขบัตรประชาชนต้องยาว 13 หลัก';
            }

            $doc->doc_errors = $doc_errors;
            $doc->is_lic_valid = $isLicValid;
            $doc->is_cid_valid = $isCidValid;

            if ($doc->active === 'Y') {
                $activeDocs[] = $doc;
            } else {
                $inactiveDocs[] = $doc;
            }

            if (empty($lic) || !$isLicValid) {
                $invalidLicenseDocs[] = $doc;
            }

            if (empty($cid) || !$isCidValid) {
                $invalidCidDocs[] = $doc;
            }
        }
    @endphp

    <!-- Card Tabs -->
    <ul class="nav nav-tabs row g-3 border-0 mb-4" id="doctorTabs" role="tablist">
        <li class="nav-item col-md-3" role="presentation">
            <button class="nav-link w-100 active p-0 border-0 bg-transparent" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-tab-pane" type="button" role="tab" aria-controls="active-tab-pane" aria-selected="true">
                <div class="card border-0 shadow-sm rounded-3 bg-success-soft text-success p-3 text-start transition-card tab-card-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-check-fill fs-2 me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">แพทย์ที่เปิดใช้งาน</h6>
                            <h4 class="mb-0 fw-bold">{{ count($activeDocs) }} <span class="fs-6 fw-normal">ราย</span></h4>
                        </div>
                    </div>
                </div>
            </button>
        </li>
        <li class="nav-item col-md-3" role="presentation">
            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="inactive-tab" data-bs-toggle="tab" data-bs-target="#inactive-tab-pane" type="button" role="tab" aria-controls="inactive-tab-pane" aria-selected="false">
                <div class="card border-0 shadow-sm rounded-3 bg-secondary-soft text-secondary p-3 text-start transition-card tab-card-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-x-fill fs-2 me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">แพทย์ที่ปิดใช้งาน</h6>
                            <h4 class="mb-0 fw-bold">{{ count($inactiveDocs) }} <span class="fs-6 fw-normal">ราย</span></h4>
                        </div>
                    </div>
                </div>
            </button>
        </li>
        <li class="nav-item col-md-3" role="presentation">
            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="invalid-license-tab" data-bs-toggle="tab" data-bs-target="#invalid-license-tab-pane" type="button" role="tab" aria-controls="invalid-license-tab-pane" aria-selected="false">
                <div class="card border-0 shadow-sm rounded-3 bg-danger-soft text-danger p-3 text-start transition-card tab-card-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-card-heading fs-2 me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">เลขใบประกอบฯ ไม่ถูกต้อง</h6>
                            <h4 class="mb-0 fw-bold">{{ count($invalidLicenseDocs) }} <span class="fs-6 fw-normal">ราย</span></h4>
                        </div>
                    </div>
                </div>
            </button>
        </li>
        <li class="nav-item col-md-3" role="presentation">
            <button class="nav-link w-100 p-0 border-0 bg-transparent" id="invalid-cid-tab" data-bs-toggle="tab" data-bs-target="#invalid-cid-tab-pane" type="button" role="tab" aria-controls="invalid-cid-tab-pane" aria-selected="false">
                <div class="card border-0 shadow-sm rounded-3 bg-warning-soft text-warning-dark p-3 text-start transition-card tab-card-item">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-credit-card-2-front fs-2 me-3"></i>
                        <div>
                            <h6 class="mb-0 fw-bold">เลขบัตรประชาชนไม่ถูกต้อง</h6>
                            <h4 class="mb-0 fw-bold">{{ count($invalidCidDocs) }} <span class="fs-6 fw-normal">ราย</span></h4>
                        </div>
                    </div>
                </div>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="doctorTabsContent">
        @php
            $tabConfigs = [
                [
                    'id' => 'active-tab-pane',
                    'active' => true,
                    'title' => 'รายชื่อแพทย์ที่เปิดใช้งาน',
                    'data' => $activeDocs,
                    'table_id' => 'table-active'
                ],
                [
                    'id' => 'inactive-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่ปิดใช้งาน',
                    'data' => $inactiveDocs,
                    'table_id' => 'table-inactive'
                ],
                [
                    'id' => 'invalid-license-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขใบประกอบวิชาชีพไม่ถูกต้อง',
                    'data' => $invalidLicenseDocs,
                    'table_id' => 'table-invalid-license'
                ],
                [
                    'id' => 'invalid-cid-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขบัตรประชาชนไม่ถูกต้อง',
                    'data' => $invalidCidDocs,
                    'table_id' => 'table-invalid-cid'
                ]
            ];
        @endphp

        @foreach($tabConfigs as $tab)
        <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['id'] }}" role="tabpanel" aria-labelledby="{{ str_replace('-pane', '', $tab['id']) }}">
            <!-- Doctor Table Card -->
            <div class="card dash-card mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-table me-2"></i> {{ $tab['title'] }} (จำนวน {{ count($tab['data']) }} รายการ)
                    </h6>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="table-responsive">            
                        <table id="{{ $tab['table_id'] }}" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center" width="8%">รหัส</th>
                                    <th class="text-start" width="22%">ชื่อ - สกุล</th>
                                    <th class="text-center" width="15%">เลขใบประกอบวิชาชีพ</th>  
                                    <th class="text-center" width="15%">เลขบัตรประชาชน</th>
                                    <th class="text-center" width="10%">สภาวิชาชีพ</th>
                                    <th class="text-center" width="8%">เพศ</th>
                                    <th class="text-center" width="10%">วันเกิด</th>
                                    <th class="text-center" width="8%">สถานะ</th>
                                    <th class="text-center" width="10%">ผลการตรวจสอบ</th>
                                </tr>
                            </thead> 
                            <tbody> 
                                @foreach($tab['data'] as $row) 
                                @php
                                    $lic = trim($row->licenseno ?? '');
                                    $isLicValid = $row->is_lic_valid;
                                    
                                    $cid = trim($row->cid ?? '');
                                    $isCidValid = $row->is_cid_valid;
                                    
                                    $doc_errors = $row->doc_errors;
                                    
                                    if (empty($doc_errors)) {
                                        $statusHtml = '<span class="badge bg-success-soft text-success"><i class="bi bi-check-circle-fill me-1"></i>ข้อมูลปกติ</span>';
                                    } else {
                                        $tooltipText = implode(' | ', $doc_errors);
                                        $statusHtml = '<span class="badge bg-danger-soft text-danger cursor-pointer" data-bs-toggle="tooltip" data-bs-placement="top" title="'.$tooltipText.'"><i class="bi bi-exclamation-triangle-fill me-1"></i>พบข้อผิดพลาด</span>';
                                    }

                                    $sexText = '-';
                                    if ($row->sex == '1') {
                                        $sexText = 'ชาย';
                                    } elseif ($row->sex == '2') {
                                        $sexText = 'หญิง';
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center fw-bold text-muted">{{ $row->code }}</td>
                                    <td class="text-start font-medium text-dark">{{ $row->name }}</td>
                                    <td class="text-center">
                                        @if (empty($lic))
                                            <span class="text-danger small fw-bold">ไม่มีข้อมูล</span>
                                        @elseif (!$isLicValid)
                                            <span class="text-danger fw-bold" title="รูปแบบผิดกฎ (S15)">{{ $lic }} <i class="bi bi-x-circle-fill ms-1"></i></span>
                                        @else
                                            <span class="fw-bold text-success">{{ $lic }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-muted small">
                                        @if (empty($cid))
                                            <span class="text-warning small">ไม่มีข้อมูล</span>
                                        @elseif (!$isCidValid)
                                            <span class="text-danger" title="ความยาวไม่ใช่ 13 หลัก">{{ $cid }} <i class="bi bi-x-circle-fill ms-1"></i></span>
                                        @else
                                            {{ $cid }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (empty($row->council_code))
                                            <span class="text-muted small">-</span>
                                        @else
                                            <span class="badge bg-light text-dark border">{{ $row->council_code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center small">{{ $sexText }}</td>
                                    <td class="text-center text-muted small">
                                        {{ $row->birth_date ? DateThai($row->birth_date) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($row->active === 'Y')
                                            <span class="badge bg-success-soft text-success rounded-pill px-2">Active</span>
                                        @else
                                            <span class="badge bg-secondary-soft text-secondary rounded-pill px-2">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{!! $statusHtml !!}</td>
                                </tr>
                                @endforeach                 
                            </tbody>
                        </table>         
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('styles')
<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.08) !important; color: #198754 !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.08) !important; color: #dc3545 !important; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.08) !important; color: #ffc107 !important; }
    .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.08) !important; color: #6c757d !important; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.08) !important; color: #0d6efd !important; }
    .text-warning-dark { color: #a1770b !important; }
    .cursor-pointer { cursor: pointer; }
    
    /* Premium Tab-Card styles */
    .tab-card-item {
        border: 2px solid transparent !important;
        transition: all 0.2s ease-in-out;
        opacity: 0.7;
    }
    .tab-card-item:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    .nav-link.active .tab-card-item {
        opacity: 1 !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08) !important;
    }
    .nav-link.active .bg-success-soft { border: 2px solid #198754 !important; }
    .nav-link.active .bg-secondary-soft { border: 2px solid #6c757d !important; }
    .nav-link.active .bg-danger-soft { border: 2px solid #dc3545 !important; }
    .nav-link.active .bg-warning-soft { border: 2px solid #b58105 !important; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        // Initialize DataTables for each table independently
        const tableIds = ['#table-active', '#table-inactive', '#table-invalid-license', '#table-invalid-cid'];
        tableIds.forEach(id => {
            $(id).DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[0, 'asc']], 
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                }
            });
        });

        // Re-adjust columns on tab click (important for DataTables inside hidden tabs)
        $(document).on('shown.bs.tab', '#doctorTabs button[data-bs-toggle="tab"]', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
@endsection
