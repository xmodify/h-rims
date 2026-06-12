@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="mb-0 text-primary fw-bold">
                <i class="bi bi-clock-history me-2"></i> Log Schedule
            </h4>
            <small class="text-muted">ประวัติการทำงานของตัวตั้งเวลาทำงาน (Task Scheduler) แยกตามงาน</small>
        </div>
        <div class="d-flex gap-2">
            <a href="" class="btn btn-outline-secondary btn-sm px-3 shadow-sm hover-scale">
                <i class="bi bi-arrow-clockwise me-1"></i> โหลดข้อมูลใหม่
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3 bg-white p-2 rounded-4 shadow-sm border" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-3 fw-bold" id="pills-nhso-tab" data-bs-toggle="pill" data-bs-target="#pills-nhso" type="button" role="tab" aria-controls="pills-nhso" aria-selected="true">
                <i class="bi bi-check-circle-fill me-1 text-primary"></i> Log สปสช.
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold" id="pills-fdh-tab" data-bs-toggle="pill" data-bs-target="#pills-fdh" type="button" role="tab" aria-controls="pills-fdh" aria-selected="false">
                <i class="bi bi-wallet-fill me-1 text-info"></i> Log FDH
            </button>
        </li>
        @if(($hospcode ?? '') === '00025' || ($hospital_code ?? '') === '00025')
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-3 fw-bold" id="pills-aopod-tab" data-bs-toggle="pill" data-bs-target="#pills-aopod" type="button" role="tab" aria-controls="pills-aopod" aria-selected="false">
                    <i class="bi bi-send-fill me-1 text-success"></i> Log AOPOD
                </button>
            </li>
        @endif
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="pills-tabContent">
        <!-- NHSO Log -->
        <div class="tab-pane fade show active" id="pills-nhso" role="tabpanel" aria-labelledby="pills-nhso-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-clock-history me-2"></i> NHSO Endpoint Scheduler Log
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(count($nhsoLogs) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px;">เวลา</th>
                                        <th style="width: 120px;">สถานะ</th>
                                        <th>รายละเอียดการทำงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nhsoLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">{{ $log['data']['message'] ?? 'ดึงข้อมูลสำเร็จ' }}</span>
                                                        <small class="text-muted mt-1">
                                                            ดึงทั้งหมด: <strong class="text-primary">{{ $log['data']['pulled_records'] ?? 0 }} รายการ</strong> | 
                                                            เพิ่มใหม่: <strong class="text-success">{{ $log['data']['inserted'] ?? 0 }} รายการ</strong> | 
                                                            อัปเดต: <strong class="text-info">{{ $log['data']['updated'] ?? 0 }} รายการ</strong>
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $log['raw'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                            ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- FDH Log -->
        <div class="tab-pane fade" id="pills-fdh" role="tabpanel" aria-labelledby="pills-fdh-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm rounded-4">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-info">
                        <i class="bi bi-clock-history me-2"></i> FDH Claim Status Scheduler Log
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(count($fdhLogs) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 200px;">เวลา</th>
                                        <th style="width: 120px;">สถานะ</th>
                                        <th>รายละเอียดการทำงาน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fdhLogs as $log)
                                        <tr>
                                            <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                            <td>
                                                @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log['data'])
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-semibold text-dark">{{ $log['data']['message'] ?? 'ตรวจสอบสถานะสำเร็จ' }}</span>
                                                        <small class="text-muted mt-1">
                                                            จำนวนวันที่ตรวจย้อนหลัง: <strong class="text-primary">{{ $log['data']['checked_days'] ?? 0 }} วัน</strong> | 
                                                            อัปเดตสถานะเคลม: <strong class="text-success">{{ $log['data']['updated_claims'] ?? 0 }} รายการ</strong>
                                                            @if(isset($log['data']['errors']) && $log['data']['errors'] > 0)
                                                                | พบข้อผิดพลาด: <strong class="text-danger">{{ $log['data']['errors'] }} รายการ</strong>
                                                            @endif
                                                        </small>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $log['raw'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                            ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- AOPOD Log -->
        @if(($hospcode ?? '') === '00025' || ($hospital_code ?? '') === '00025')
            <div class="tab-pane fade" id="pills-aopod" role="tabpanel" aria-labelledby="pills-aopod-tab" tabindex="0">
                <div class="card dash-card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold text-success">
                            <i class="bi bi-clock-history me-2"></i> AOPOD Send Scheduler Log
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        @if(count($aopodLogs) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 200px;">เวลา</th>
                                            <th style="width: 120px;">สถานะ</th>
                                            <th>รายละเอียดการทำงาน</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($aopodLogs as $log)
                                            <tr>
                                                <td class="fw-bold text-secondary text-nowrap">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                                <td>
                                                    @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">สำเร็จ</span>
                                                    @else
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">ล้มเหลว</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($log['data'])
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-semibold text-dark">
                                                                ส่งข้อมูลระหว่างวันที่ {{ isset($log['data']['start_date']) ? DateThai($log['data']['start_date']) : '-' }} ถึง {{ isset($log['data']['end_date']) ? DateThai($log['data']['end_date']) : '-' }} (รหัสรพ. {{ $log['data']['hospcode'] ?? '-' }})
                                                            </span>
                                                            <small class="text-muted mt-1">
                                                                ข้อมูลที่ได้รับ - 
                                                                OPD: <strong class="text-primary">{{ $log['data']['received']['opd'] ?? 0 }} รายการ</strong> | 
                                                                IPD: <strong class="text-info">{{ $log['data']['received']['ipd'] ?? 0 }} รายการ</strong> | 
                                                                IPD Bed: <strong class="text-success">{{ $log['data']['received']['ipd_bed'] ?? 0 }} รายการ</strong> | 
                                                                Hospital: <strong class="text-warning">{{ $log['data']['received']['hospital'] ?? 0 }} รายการ</strong>
                                                            </small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">{{ $log['raw'] }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-info-circle fs-1 d-block mb-2 text-secondary"></i>
                                ยังไม่มีประวัติการทำงานในขณะนี้ (No logs found)
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Scroll all consoles to bottom on load
        document.querySelectorAll('.log-console').forEach(function(consoleElem) {
            consoleElem.scrollTop = consoleElem.scrollHeight;
        });

        // Re-scroll when switching tabs
        const tabElList = document.querySelectorAll('button[data-bs-toggle="pill"]');
        tabElList.forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', event => {
                const targetId = event.target.getAttribute('data-bs-target');
                const activeConsole = document.querySelector(targetId + ' .log-console');
                if (activeConsole) {
                    activeConsole.scrollTop = activeConsole.scrollHeight;
                }
            });
        });
    });
</script>

<style>
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    .nav-pills .nav-link {
        color: #4a5568;
    }
    .nav-pills .nav-link.active {
        background-color: var(--nav-green, #0a4d2c) !important;
        color: #fff !important;
    }
</style>
@endsection
