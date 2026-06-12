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
            <div class="card dash-card border-0 shadow-sm">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-terminal-fill me-2"></i> NHSO Endpoint Scheduler Log
                    </h6>
                </div>
                <div class="card-body p-0">
                    <pre class="log-console" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 0 0 16px 16px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; margin-bottom: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $nhsoLog }}</pre>
                </div>
            </div>
        </div>

        <!-- FDH Log -->
        <div class="tab-pane fade" id="pills-fdh" role="tabpanel" aria-labelledby="pills-fdh-tab" tabindex="0">
            <div class="card dash-card border-0 shadow-sm">
                <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                    <h6 class="mb-0 fw-bold text-info">
                        <i class="bi bi-terminal-fill me-2"></i> FDH Claim Status Scheduler Log
                    </h6>
                </div>
                <div class="card-body p-0">
                    <pre class="log-console" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 0 0 16px 16px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; margin-bottom: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $fdhLog }}</pre>
                </div>
            </div>
        </div>

        <!-- AOPOD Log -->
        @if(($hospcode ?? '') === '00025' || ($hospital_code ?? '') === '00025')
            <div class="tab-pane fade" id="pills-aopod" role="tabpanel" aria-labelledby="pills-aopod-tab" tabindex="0">
                <div class="card dash-card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold text-success">
                            <i class="bi bi-terminal-fill me-2"></i> AOPOD Send Scheduler Log
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <pre class="log-console" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 0 0 16px 16px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', Courier, monospace; font-size: 0.9rem; margin-bottom: 0; white-space: pre-wrap; word-wrap: break-word;">{{ $aopodLog }}</pre>
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
