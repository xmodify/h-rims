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

    <!-- Stats summary cards -->
    <div class="row mb-4">
        @php
            $activeCount = 0;
            $inactiveCount = 0;
            $invalidLicenseCount = 0;
            $invalidCidCount = 0;

            foreach($doctors as $doc) {
                if ($doc->active === 'Y') {
                    $activeCount++;
                } else {
                    $inactiveCount++;
                }

                $lic = trim($doc->licenseno ?? '');
                $isLicValid = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
                if (empty($lic) || !$isLicValid) {
                    $invalidLicenseCount++;
                }

                $cid = trim($doc->cid ?? '');
                if (empty($cid) || strlen($cid) !== 13) {
                    $invalidCidCount++;
                }
            }
        @endphp
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-success-soft text-success p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-check-fill fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">แพทย์ที่เปิดใช้งาน</h6>
                        <h4 class="mb-0 fw-bold">{{ $activeCount }} <span class="fs-6 fw-normal">ราย</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-secondary-soft text-secondary p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-x-fill fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">แพทย์ที่ปิดใช้งาน</h6>
                        <h4 class="mb-0 fw-bold">{{ $inactiveCount }} <span class="fs-6 fw-normal">ราย</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-danger-soft text-danger p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-card-heading fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">เลขใบประกอบฯ ไม่ถูกต้อง</h6>
                        <h4 class="mb-0 fw-bold">{{ $invalidLicenseCount }} <span class="fs-6 fw-normal">ราย</span></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-warning-soft text-warning-dark p-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card-2-front fs-2 me-3"></i>
                    <div>
                        <h6 class="mb-0 fw-bold">เลขบัตรประชาชนไม่ถูกต้อง</h6>
                        <h4 class="mb-0 fw-bold">{{ $invalidCidCount }} <span class="fs-6 fw-normal">ราย</span></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor Table Card -->
    <div class="card dash-card mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-primary">
                <i class="bi bi-table me-2"></i> บัญชีรายชื่อแพทย์และบุคลากร (doctor)
            </h6>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="table-responsive">            
                <table id="doctor-table" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" width="8%">รหัส</th>
                            <th class="text-start" width="22%">ชื่อ - สกุล</th>
                            <th class="text-center" width="12%">เลขใบประกอบวิชาชีพ</th>  
                            <th class="text-center" width="12%">เลขบัตรประชาชน</th>
                            <th class="text-center" width="10%">สภาวิชาชีพ</th>
                            <th class="text-center" width="8%">เพศ</th>
                            <th class="text-center" width="10%">วันเกิด</th>
                            <th class="text-center" width="8%">สถานะ</th>
                            <th class="text-center" width="10%">ผลการตรวจสอบ</th>
                        </tr>
                    </thead> 
                    <tbody> 
                        @foreach($doctors as $row) 
                        @php
                            $lic = trim($row->licenseno ?? '');
                            $isLicValid = preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);
                            
                            $cid = trim($row->cid ?? '');
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

@push('styles')
<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107 !important; }
    .bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1) !important; color: #6c757d !important; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1) !important; color: #0d6efd !important; }
    .text-warning-dark { color: #b58105 !important; }
    .cursor-pointer { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function () {
        $('#doctor-table').DataTable({
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[7, 'desc'], [8, 'asc']], // Order by active, then errors
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
            }
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
