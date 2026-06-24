@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4 px-lg-5">


    <!-- Header with Title & Nav Tabs -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h4 class="mb-0 text-success fw-bold d-flex align-items-center">
            <i class="bi bi-send-fill me-2 fs-3"></i>
            <span>ข้อมูล AOPOD</span>
        </h4>
        
        <ul class="nav nav-pills p-1 bg-light rounded-4 shadow-sm mb-0" id="aopodTabs" role="tablist" style="max-width: 600px;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold aopod-tab-success" id="sync-tab" data-bs-toggle="tab" data-bs-target="#sync-pane" type="button" role="tab" aria-controls="sync-pane" aria-selected="true" style="padding: 10px 20px;">
                    <i class="bi bi-send-fill me-2"></i>การตั้งค่าและส่งข้อมูล
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold aopod-tab-danger" id="death-tab" data-bs-toggle="tab" data-bs-target="#death-pane" type="button" role="tab" aria-controls="death-pane" aria-selected="false" style="padding: 10px 20px;">
                    <i class="bi bi-heart-pulse-fill me-2"></i>ตรวจสอบข้อมูลการตาย
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="aopodTabsContent">
        <!-- Tab 1: Sync & Logs (Existing Layout) -->
        <div class="tab-pane fade show active" id="sync-pane" role="tabpanel" aria-labelledby="sync-tab" tabindex="0">
            <div class="row">
                <!-- Control Card -->
                <div class="col-xl-4 col-lg-12 mb-4">
                    <!-- Manual Send Card -->
                    <div class="card dash-card accent-10 border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-success">
                                <i class="bi bi-sliders me-2"></i> จัดการส่งข้อมูล (Manual Send)
                            </h6>
                        </div>
                        <div class="card-body p-4 pt-0">
                            <p class="text-muted small mb-4">เลือกช่วงเวลาที่ต้องการประมวลผลและส่งข้อมูลบริการ (OPD/IPD) ไปยังระบบกลาง AOPOD โดยระบบจะแบ่งข้อมูลเป็นช่วงละ 10 วันโดยอัตโนมัติเพื่อลดภาระงานของเซิร์ฟเวอร์</p>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">วันที่เริ่มต้น</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-success-subtle text-success"><i class="bi bi-calendar-event"></i></span>
                                        <input type="hidden" id="start_date" name="start_date">
                                        <input type="text" class="form-control border-success-subtle shadow-sm datepicker_th" id="start_date_display" readonly required placeholder="เลือกวันที่">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">วันที่สิ้นสุด</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-success-subtle text-success"><i class="bi bi-calendar-event"></i></span>
                                        <input type="hidden" id="end_date" name="end_date">
                                        <input type="text" class="form-control border-success-subtle shadow-sm datepicker_th" id="end_date_display" readonly required placeholder="เลือกวันที่">
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-success w-100 py-2.5 rounded-pill shadow hover-scale fw-bold" id="sendAOPODBtn">
                                <i class="bi bi-send-check-fill me-2"></i> ส่งข้อมูลระบุช่วงวันที่
                            </button>
                            <button type="button" class="btn btn-outline-success w-100 py-2.5 rounded-pill shadow-sm hover-scale fw-bold mt-2" onclick="startAopodManualSend()">
                                <i class="bi bi-play-circle-fill me-2"></i> ส่งข้อมูลตามตารางเวลาทันที
                            </button>

                            <hr class="my-4 opacity-10">

                            <!-- Windows Task Scheduler Guide -->
                            <div class="bg-light p-3 rounded-4 border mb-4">
                                <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-clock-history me-1 text-success"></i> Windows Task Scheduler (AOPOD Send)</h6>
                                <p class="text-muted" style="font-size: 11px;">ส่งอัตโนมัติทุกชั่วโมง (นาทีที่ 15) | Program: <code>powershell.exe</code> | Arguments:</p>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control border-secondary bg-white text-muted" style="font-size: 11px;" value="-WindowStyle Hidden -Command &quot;Invoke-RestMethod -Uri '{{ $amnosend }}' -Method Post&quot;" readonly id="aopod_cmd">
                                    <button class="btn btn-success text-white" type="button" onclick="copyToClipboard('aopod_cmd')">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-4 opacity-10">

                            <!-- API Connection Settings -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="bi bi-gear-fill me-1"></i> ตั้งค่าเชื่อมต่อ API (Provincial Settings)
                                </h6>
                                <button type="button" class="btn btn-outline-success btn-sm px-2.5 rounded-pill shadow-sm hover-scale" onclick="testAopodConnection()" style="font-size: 11px;">
                                    <i class="bi bi-patch-check-fill me-1"></i> ทดสอบเชื่อมต่อ API
                                </button>
                            </div>
                            <p class="text-muted small mb-3">กำหนด Token และ API สำหรับเชื่อมต่อระบบกลางของจังหวัด</p>
                            <form id="saveTokenForm" onsubmit="saveAopodToken(event)">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">AOPOD API Token</label>
                                    <div class="input-group input-group-sm shadow-sm">
                                        <span class="input-group-text bg-light text-primary"><i class="bi bi-shield-lock"></i></span>
                                        <input type="password" class="form-control" id="aopod_token_input" name="aopod_token" value="{{ $aopod_token }}" placeholder="ระบุ Token ที่นี่">
                                        <button class="btn btn-outline-secondary text-secondary bg-light" type="button" onclick="toggleTokenVisibility()"><i class="bi bi-eye" id="toggleTokenEye"></i></button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">AOPOD Death API URL</label>
                                    <div class="input-group input-group-sm shadow-sm">
                                        <span class="input-group-text bg-light text-primary"><i class="bi bi-link-45deg"></i></span>
                                        <input type="text" class="form-control" id="aopod_url_api_death_input" name="aopod_url_api_death" value="{{ $aopod_url_api_death }}" placeholder="https://...">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold hover-scale text-white py-2 shadow-sm">
                                    <i class="bi bi-save2-fill me-1"></i> บันทึกการตั้งค่า
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Logs Table Card -->
                <div class="col-xl-8 col-lg-12 mb-4">
                    <div class="card dash-card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-dark text-white border-0 py-3 rounded-top-4 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-success">
                                <i class="bi bi-clock-history me-2"></i> ประวัติการทำงาน (AOPOD Logs)
                            </h6>
                            <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill small">ทั้งหมด {{ count($aopodLogs) }} รายการล่าสุด</span>
                        </div>
                        <div class="card-body p-3">
                            @if(count($aopodLogs) > 0)
                                <div class="table-responsive">
                                    <table id="aopodLogsTable" class="table table-hover align-middle mb-0 border-0 w-100">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 180px;" class="border-0">เวลาบันทึก</th>
                                                <th style="width: 120px;" class="border-0">สถานะ</th>
                                                <th class="border-0 text-start">รายละเอียดผลลัพธ์</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($aopodLogs as $log)
                                                <tr>
                                                    <td class="fw-bold text-secondary text-nowrap ps-3">{{ $log['timestamp'] ? DatetimeThai($log['timestamp']) : 'N/A' }}</td>
                                                    <td>
                                                        @if(isset($log['data']['ok']) && $log['data']['ok'] === true)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">สำเร็จ</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill">ล้มเหลว</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-start pe-3">
                                                        @if($log['data'])
                                                            <div class="d-flex flex-column">
                                                                <span class="fw-semibold text-dark">
                                                                    ส่งข้อมูลวันที่ {{ isset($log['data']['start_date']) ? DateThai($log['data']['start_date']) : '-' }} ถึง {{ isset($log['data']['end_date']) ? DateThai($log['data']['end_date']) : '-' }} (รหัสรพ. {{ $log['data']['hospcode'] ?? '-' }})
                                                                </span>
                                                                <small class="text-muted mt-1">
                                                                    จำนวนแถวข้อมูล: 
                                                                    OPD <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['opd'] ?? 0 }}</span> | 
                                                                    IPD <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['ipd'] ?? 0 }}</span> | 
                                                                    IPD Bed <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['ipd_bed'] ?? 0 }}</span> | 
                                                                    Hospital <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-0.5 rounded-pill">{{ $log['data']['received']['hospital'] ?? 0 }}</span>
                                                                </small>
                                                            </div>
                                                        @else
                                                            <span class="text-muted small">{{ $log['raw'] }}</span>
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
                                    ยังไม่มีประวัติการส่งข้อมูล AOPOD ในระบบ (No logs found)
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Death Audit (New Feature) -->
        <div class="tab-pane fade" id="death-pane" role="tabpanel" aria-labelledby="death-tab" tabindex="0">
            <!-- Control bar for Death Sync -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="mb-1 text-dark fw-bold">
                            <i class="bi bi-heart-pulse-fill text-danger me-2"></i> ตรวจสอบความสอดคล้องข้อมูลการตาย (Death Completeness Audit)
                        </h5>
                        <p class="text-muted small mb-0">ดึงข้อมูลประวัติการตายอ้างอิงจากระบบ AOPOD และวิเคราะห์ความครบถ้วนของการบันทึกข้อมูลในฐาน HOSxP ทั้งหมด 4 ตารางหลัก</p>
                    </div>
                </div>
            </div>
 
            <!-- Sync Progress Bar Container -->
            <div id="syncProgressContainer" style="display: none;" class="mb-4 p-4 bg-light rounded-4 border shadow-sm">
                <div class="d-flex justify-content-between mb-2">
                    <span id="syncStepText" class="fw-bold text-success"><i class="bi bi-gear-fill spin me-1"></i> กำลังเตรียมประมวลผล...</span>
                    <span id="syncProgressPct" class="fw-bold text-success">0%</span>
                </div>
                <div class="progress" style="height: 16px; border-radius: 8px;">
                    <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
 
            <!-- Completeness stats cards -->
            <div class="row mb-4" id="statsCardsContainer">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white" style="border-left: 5px solid #0d6efd !important;">
                        <small class="text-muted fw-bold text-uppercase">ตาราง PATIENT</small>
                        <h2 class="my-2 fw-bold text-primary" id="patientPctText">{{ $settings['aopod_death_pct_patient'] ?? '0' }}%</h2>
                        <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar bg-primary" id="patientProgress" role="progressbar" style="width: {{ $settings['aopod_death_pct_patient'] ?? '0' }}%"></div>
                        </div>
                        <span class="small text-muted" id="patientDetailsText">{{ $settings['aopod_death_details_patient'] ?? '0 / 0 ราย' }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white" style="border-left: 5px solid #198754 !important;">
                        <small class="text-muted fw-bold text-uppercase">ตาราง PERSON (บัญชี 1)</small>
                        <h2 class="my-2 fw-bold text-success" id="personPctText">{{ $settings['aopod_death_pct_person'] ?? '0' }}%</h2>
                        <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar bg-success" id="personProgress" role="progressbar" style="width: {{ $settings['aopod_death_pct_person'] ?? '0' }}%"></div>
                        </div>
                        <span class="small text-muted" id="personDetailsText">{{ $settings['aopod_death_details_person'] ?? '0 / 0 ราย' }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white" style="border-left: 5px solid #ffc107 !important;">
                        <small class="text-muted fw-bold text-uppercase">ตาราง CLINICMEMBER</small>
                        <h2 class="my-2 fw-bold text-warning" id="clinicPctText">{{ $settings['aopod_death_pct_clinicmember'] ?? '0' }}%</h2>
                        <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar bg-warning" id="clinicProgress" role="progressbar" style="width: {{ $settings['aopod_death_pct_clinicmember'] ?? '0' }}%"></div>
                        </div>
                        <span class="small text-muted" id="clinicDetailsText">{{ $settings['aopod_death_details_clinicmember'] ?? '0 / 0 ราย' }}</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-3 h-100 bg-white" style="border-left: 5px solid #dc3545 !important;">
                        <small class="text-muted fw-bold text-uppercase">ตาราง DEATH (สาเหตุตาย)</small>
                        <h2 class="my-2 fw-bold text-danger" id="deathPctText">{{ $settings['aopod_death_pct_death'] ?? '0' }}%</h2>
                        <div class="progress mb-2" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar bg-danger" id="deathProgress" role="progressbar" style="width: {{ $settings['aopod_death_pct_death'] ?? '0' }}%"></div>
                        </div>
                        <span class="small text-muted" id="deathDetailsText">{{ $settings['aopod_death_details_death'] ?? '0 / 0 ราย' }}</span>
                    </div>
                </div>
            </div>
 
            <!-- Audit details table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-stars text-danger me-1"></i> รายการคนไข้ที่มีข้อมูลไม่สอดคล้อง (ขัดแย้ง)</h6>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-1.5 rounded-pill small" id="apiStatusBadge">ยังไม่ได้ตรวจสอบ</span>
                        <button class="btn text-white px-4 py-2 rounded-pill shadow hover-scale fw-bold" id="syncDeathBtn" onclick="syncDeathData()" style="background: linear-gradient(to right, #e33b35, #ffb000); border: none;">
                            <i class="bi-arrow-repeat me-1"></i> Sync Death (ตรวจสอบข้อมูลล่าสุด)
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="deathAuditTable" class="table table-hover align-middle mb-0 border-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">HN / CID</th>
                                <th class="border-0">ชื่อ-นามสกุล</th>
                                <th class="border-0 text-center">Patient <span class="badge bg-danger-subtle text-danger rounded-pill ms-1" id="badgePatientCount" style="font-size: 10px; display: none;">ยังไม่ตาย 0</span></th>
                                <th class="border-0 text-center">Person <span class="badge bg-danger-subtle text-danger rounded-pill ms-1" id="badgePersonCount" style="font-size: 10px; display: none;">ยังไม่ตาย 0</span></th>
                                <th class="border-0 text-center">Clinic Member <span class="badge bg-danger-subtle text-danger rounded-pill ms-1" id="badgeClinicCount" style="font-size: 10px; display: none;">ยังไม่ตาย 0</span></th>
                                <th class="border-0 text-center">Death <span class="badge bg-danger-subtle text-danger rounded-pill ms-1" id="badgeDeathCount" style="font-size: 10px; display: none;">ยังไม่ลง 0</span></th>
                                <th class="border-0 text-center">ผลการตรวจสอบ</th>
                                <th class="border-0 text-center">ข้อมูล AOPOD</th>
                            </tr>
                        </thead>
                        <tbody id="deathAuditTableBody" data-loaded="{{ count($deathAuditList) > 0 ? 'true' : 'false' }}">
                            @if(count($deathAuditList) > 0)
                                @foreach($deathAuditList as $item)
                                    @php
                                        $itemArr = [
                                            'hn' => $item->hn,
                                            'cid' => $item->cid,
                                            'fullname' => $item->fullname,
                                            'patient_death' => $item->patient_death,
                                            'patient_deathday' => $item->patient_deathday,
                                            'person_death' => $item->person_death,
                                            'person_deathday' => $item->person_deathday,
                                            'active_clinics' => $item->active_clinics,
                                            'active_clinics_list' => $item->active_clinics_list,
                                            'has_clinics' => $item->has_clinics ? true : false,
                                            'death_table_date' => $item->death_table_date,
                                            'death_table_diag' => $item->death_table_diag,
                                            'death_table_cause' => $item->death_table_cause,
                                            'aopod_death_date' => $item->aopod_death_date,
                                            'aopod_death_diag' => $item->aopod_death_diag,
                                            'aopod_death_cause' => $item->aopod_death_cause,
                                            'aopod_death_place' => $item->aopod_death_place,
                                            'is_complete' => $item->is_complete ? true : false
                                        ];
                                        $itemJson = rawurlencode(json_encode($itemArr));
                                    @endphp
                                    <tr class="{{ $item->is_complete ? 'opacity-75 bg-light' : '' }}">
                                        <td>
                                            <span class="fw-bold text-dark d-block">{{ $item->cid }}</span>
                                            <small class="text-muted text-uppercase">HN: {{ $item->hn }}</small>
                                        </td>
                                        <td class="fw-semibold text-dark">{{ $item->fullname }}</td>
                                        <td class="text-center" data-order="{{ $item->patient_death === 'Y' ? '1' : '2' }}">
                                            @if($item->patient_death === 'Y')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย</span>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ $item->person_death === 'Y' ? '1' : (($item->person_death === 'N/A' || is_null($item->person_death)) ? '2' : '3') }}">
                                            @if($item->person_death === 'N/A' || is_null($item->person_death))
                                                <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-dash-circle me-1"></i> นอกเขต</span>
                                            @elseif($item->person_death === 'Y')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย</span>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ !$item->has_clinics ? '2' : ($item->active_clinics === 0 ? '1' : '3') }}">
                                            @if(!$item->has_clinics)
                                                <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-dash-circle me-1"></i> ไม่มีคลินิก</span>
                                            @elseif($item->active_clinics === 0)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center hover-scale" style="cursor: pointer;" onclick="showActiveClinics('{{ $itemJson }}')" title="คลิกเพื่อดูรายละเอียดคลินิก"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย (ค้าง {{ $item->active_clinics }} คลินิก)</span>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ $item->death_table_date ? '1' : '2' }}">
                                            @if($item->death_table_date)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ลงทะเบียนแล้ว</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ลง</span>
                                            @endif
                                        </td>
                                        <td class="text-center" data-order="{{ $item->is_complete ? '1' : '2' }}">
                                            @if($item->is_complete)
                                                <i class="bi bi-check-circle-fill text-success fs-4" title="ข้อมูลตรงกัน"></i>
                                            @else
                                                <i class="bi bi-x-circle-fill text-danger fs-4" title="ยังไม่ครบ"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-bold hover-scale" onclick="showDeathDetail('{{ $itemJson }}')"><i class="bi bi-clipboard2-pulse"></i> ดูข้อมูล</button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-cloud-arrow-down fs-1 d-block mb-2 text-secondary"></i>
                                        กรุณากดปุ่ม Sync Death เพื่อสแกนและประมวลผลข้อมูลการตายล่าสุด
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        @php
                            $totalCount = count($deathAuditList);
                            
                            $patientDead = $deathAuditList->where('patient_death', 'Y')->count();
                            $patientAlive = $totalCount - $patientDead;
                            
                            $personDead = $deathAuditList->where('person_death', 'Y')->count();
                            $personNA = $deathAuditList->filter(function($x) {
                                return $x->person_death === 'N/A' || is_null($x->person_death);
                            })->count();
                            $personAlive = $totalCount - $personDead - $personNA;
                            
                            $clinicNone = $deathAuditList->where('has_clinics', 0)->count();
                            $clinicDischarged = $deathAuditList->filter(function($x) {
                                return $x->has_clinics == 1 && $x->active_clinics == 0;
                            })->count();
                            $clinicPending = $totalCount - $clinicNone - $clinicDischarged;
                            
                            $deathReg = $deathAuditList->filter(function($x) {
                                return !is_null($x->death_table_date);
                            })->count();
                            $deathUnreg = $totalCount - $deathReg;

                            $completeCount = $deathAuditList->where('is_complete', 1)->count();
                            $incompleteCount = $totalCount - $completeCount;
                        @endphp
                        <tfoot class="fw-bold text-center text-dark" id="deathAuditTableFoot" style="{{ $totalCount > 0 ? '' : 'display: none;' }}">
                            <tr>
                                <th colspan="2" class="text-start ps-3">รวมทั้งหมด <span id="footTotalCount">{{ $totalCount }}</span> ราย</th>
                                <th>
                                    <span class="text-success">ตาย <span id="footPatientDead">{{ $patientDead }}</span></span><br>
                                    <span class="text-danger">ยังไม่ตาย <span id="footPatientAlive">{{ $patientAlive }}</span></span>
                                </th>
                                <th>
                                    <span class="text-success">ตาย <span id="footPersonDead">{{ $personDead }}</span></span><br>
                                    <span class="text-danger">ยังไม่ตาย <span id="footPersonAlive">{{ $personAlive }}</span></span><br>
                                    <span class="text-muted">นอกเขต <span id="footPersonNA">{{ $personNA }}</span></span>
                                </th>
                                <th>
                                    <span class="text-success">ตาย <span id="footClinicDischarged">{{ $clinicDischarged }}</span></span><br>
                                    <span class="text-danger">ยังไม่ตาย <span id="footClinicPending">{{ $clinicPending }}</span></span><br>
                                    <span class="text-muted">ไม่มี <span id="footClinicNone">{{ $clinicNone }}</span></span>
                                </th>
                                <th>
                                    <span class="text-success">ลงแล้ว <span id="footDeathReg">{{ $deathReg }}</span></span><br>
                                    <span class="text-danger">ยังไม่ลง <span id="footDeathUnreg">{{ $deathUnreg }}</span></span>
                                </th>
                                <th>
                                    <span class="text-success">ตรงกัน <span id="footCompleteCount">{{ $completeCount }}</span></span><br>
                                    <span class="text-danger">ไม่ตรง <span id="footIncompleteCount">{{ $incompleteCount }}</span></span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Death Detail Modal -->
    <div class="modal fade" id="deathDetailModal" tabindex="-1" aria-labelledby="deathDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white border-0 py-3 rounded-top-4">
                    <h6 class="modal-title fw-bold" id="deathDetailModalLabel">
                        <i class="bi bi-heart-pulse-fill me-2"></i> ข้อมูลการตายอ้างอิงจาก AOPOD
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">ชื่อ-นามสกุล</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white fw-semibold" id="modalFullname" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalFullname')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">เลขบัตรประชาชน (CID)</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white fw-semibold" id="modalCid" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalCid')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">HN ใน HOSxP</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white fw-semibold" id="modalHn" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalHn')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">วันที่ตาย</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white fw-semibold" id="modalDeathDate" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalDeathDate')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">รหัสสาเหตุ (ICD-10)</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white fw-semibold text-danger" id="modalDeathDiag" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalDeathDiag')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">รายละเอียดสาเหตุการตาย</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white" id="modalDeathCause" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalDeathCause')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">สถานที่ตาย</label>
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" class="form-control bg-white" id="modalDeathPlace" readonly>
                                <button class="btn btn-secondary text-white" type="button" onclick="copyModalField('modalDeathPlace')"><i class="bi bi-copy"></i> คัดลอก</button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-4 mb-0 border-0 rounded-4 d-flex align-items-center shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-3 fs-4 text-warning"></i>
                        <div>
                           <small class="d-block fw-bold text-dark">คำแนะนำสำหรับเจ้าหน้าที่เวชระเบียน</small>
                           <small class="text-muted d-block mt-0.5">กรุณานำข้อมูลข้างต้นไปเปิดบันทึกประวัติผู้ตายในระบบ HOSxP ของท่าน โดยอัปเดตสถานะการตายในทะเบียนทั่วไป (Patient), ทะเบียนประชากรเขตรับผิดชอบ (Person), ทำการจำหน่ายทะเบียนสมาชิกโรคเรื้อรัง (Clinicmember) และกรอกข้อมูลรายงานการตาย (Death) ด้วยสาเหตุและรหัส ICD-10 ดังกล่าว</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-scale {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-scale:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
    }
    .nav-pills .nav-link.aopod-tab-success {
        color: #198754;
    }
    .nav-pills .nav-link.aopod-tab-success.active {
        background-color: #198754 !important;
        color: #fff !important;
    }
    .nav-pills .nav-link.aopod-tab-danger {
        color: #dc3545;
    }
    .nav-pills .nav-link.aopod-tab-danger.active {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    #deathAuditTable tfoot th {
        background-color: #e8f0fe !important;
        color: #1e293b !important;
    }
</style>
@endsection

@push('scripts')
<script>
    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "status-audit-asc": function ( a, b ) {
            let map = { '1': 1, '3': 2, '2': 3 };
            let valA = map[a] || 99;
            let valB = map[b] || 99;
            return valA - valB;
        },
        "status-audit-desc": function ( a, b ) {
            let map = { '3': 1, '1': 2, '2': 3 };
            let valA = map[a] || 99;
            let valB = map[b] || 99;
            return valA - valB;
        }
    } );

    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกสำเร็จ',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }

    $(document).ready(function () {
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1050
        });

        $('.datepicker_th').on('changeDate', function(e) {
            var date = e.date;
            var targetId = $(this).attr('id').replace('_display', '');
            var hiddenInput = $('#' + targetId);
            
            if(date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear(); // Gregorian
                hiddenInput.val(year + "-" + month + "-" + day);
            } else {
                hiddenInput.val('');
            }
        });

        $('#aopodLogsTable').DataTable({
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                infoEmpty: "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
                infoFiltered: "(กรองข้อมูลจากทั้งหมด _MAX_ รายการ)",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                paginate: {
                    first: "หน้าแรก",
                    last: "หน้าสุดท้าย",
                    next: "ถัดไป",
                    previous: "ก่อนหน้า"
                }
            }
        });

        if ($('#deathAuditTableBody').data('loaded') === true) {
            $('#deathAuditTable').DataTable({
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json'
                },
                columnDefs: [
                    { type: 'status-audit', targets: [2, 3, 4] }
                ],
                initComplete: function(settings, json) {
                    if (!$('#exportExcelBtn').length) {
                        $('#deathAuditTable_filter').append('<button type="button" id="exportExcelBtn" onclick="exportTableToExcel()" class="btn btn-success btn-sm ms-2 py-1.5 px-3 rounded-pill fw-bold hover-scale shadow-sm"><i class="bi bi-file-earmark-excel-fill me-1"></i> Excel</button>');
                    }
                }
            });
        }
    });

    function testAopodConnection() {
        Swal.fire({
            title: 'กำลังทดสอบการเชื่อมต่อ AOPOD...',
            html: 'กรุณารอสักครู่ ระบบกำลังทดสอบการเชื่อมต่อกับเซิร์ฟเวอร์ AOPOD',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("admin.logs.schedule.aopod.test") }}')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'การทดสอบสำเร็จ',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#198754'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'การทดสอบล้มเหลว',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการร้องขอ',
                    text: err.message,
                    confirmButtonText: 'ตกลง'
                });
            });
    }

    function formatThaiDate(dateStr) {
        if (!dateStr) return '';
        const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const year = parseInt(parts[0], 10) + 543;
        const month = months[parseInt(parts[1], 10) - 1];
        const day = parseInt(parts[2], 10);
        return `${day} ${month} ${year}`;
    }

    document.getElementById('sendAOPODBtn').addEventListener('click', function() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        const startDisplay = document.getElementById('start_date_display').value;
        const endDisplay = document.getElementById('end_date_display').value;

        if (!start || !end) {
            Swal.fire({ icon: 'warning', title: 'กรุณาเลือกวันที่ให้ครบ', confirmButtonText: 'ตกลง', confirmButtonColor: '#28a745' });
            return;
        }

        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล AOPOD?',
            text: `ช่วงวันที่ ${startDisplay} ถึง ${endDisplay}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ส่งข้อมูล',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d'
        }).then(async (result) => {
            if (result.isConfirmed) {
                // Helper to split date range into chunks of 10 days
                function getPeriods(startDateStr, endDateStr, daysPerPeriod = 10) {
                    let start = new Date(startDateStr);
                    let end = new Date(endDateStr);
                    let periods = [];
                    
                    let currentStart = new Date(start);
                    while (currentStart <= end) {
                        let currentEnd = new Date(currentStart);
                        currentEnd.setDate(currentEnd.getDate() + daysPerPeriod - 1);
                        if (currentEnd > end) {
                            currentEnd = new Date(end);
                        }
                        
                        periods.push({
                            start: currentStart.toISOString().split('T')[0],
                            end: currentEnd.toISOString().split('T')[0]
                        });
                        
                        currentStart = new Date(currentEnd);
                        currentStart.setDate(currentStart.getDate() + 1);
                    }
                    return periods;
                }

                const periods = getPeriods(start, end, 10);
                const totalPeriods = periods.length;

                Swal.fire({
                    title: 'กำลังส่งข้อมูล AOPOD...',
                    html: `
                        <div id="aopods-progress-text" class="mb-2">กำลังเตรียมข้อมูลและแบ่งช่วงเวลา...</div>
                        <div class="progress" style="height: 20px; border-radius: 10px;">
                            <div id="aopods-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                let opdTotal = 0;
                let ipdTotal = 0;
                let ipdBedTotal = 0;
                let hospitalTotal = 0;
                let failedPeriods = [];
                let overallSuccess = true;

                for (let i = 0; i < totalPeriods; i++) {
                    const period = periods[i];
                    const percent = Math.round((i / totalPeriods) * 100);

                    // Update UI
                    const progressText = document.getElementById('aopods-progress-text');
                    const progressBar = document.getElementById('aopods-progress-bar');
                    if (progressText) {
                        progressText.innerHTML = `กำลังส่งข้อมูลช่วงที่ ${i + 1}/${totalPeriods}<br>(${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)})`;
                    }
                    if (progressBar) {
                        progressBar.style.width = `${percent}%`;
                        progressBar.innerHTML = `${percent}%`;
                        progressBar.setAttribute('aria-valuenow', percent);
                    }

                    try {
                        let response = await fetch(`{{ url('api/amnosend') }}?start_date=${period.start}&end_date=${period.end}&log=false`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });

                        const text = await response.text();
                        try {
                            const data = JSON.parse(text);
                            if (data.ok) {
                                opdTotal += data.received.opd || 0;
                                ipdTotal += data.received.ipd || 0;
                                ipdBedTotal += data.received.ipd_bed || 0;
                                hospitalTotal += data.received.hospital || 0;
                            } else {
                                overallSuccess = false;
                                failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (มีช่วงที่ล้มเลว)`);
                            }
                        } catch (e) {
                            overallSuccess = false;
                            failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (ข้อมูลตอบกลับไม่ถูกต้อง)`);
                        }
                    } catch (error) {
                        overallSuccess = false;
                        failedPeriods.push(`${formatThaiDate(period.start)} ถึง ${formatThaiDate(period.end)} (${error.message || error})`);
                    }
                }

                // Final update to progress bar
                const progressBar = document.getElementById('aopods-progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                    progressBar.innerHTML = '100%';
                    progressBar.setAttribute('aria-valuenow', 100);
                }

                // Save log summary to backend
                try {
                    await fetch('{{ route("admin.aopod.log-summary") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            start_date: start,
                            end_date: end,
                            ok: overallSuccess,
                            opd: opdTotal,
                            ipd: ipdTotal,
                            ipd_bed: ipdBedTotal,
                            hospital: hospitalTotal
                        })
                    });
                } catch (e) {
                    console.error('Failed to log summary:', e);
                }

                // Show final result
                const summaryText = `
                    <div class="text-start p-2">
                        <b>สถานะ:</b> ${overallSuccess ? '<span class="text-success fw-bold">✅ สำเร็จทั้งหมด</span>' : '<span class="text-warning fw-bold">⚠️ เสร็จสิ้นแต่มีข้อผิดพลาดบางส่วน</span>'}<br>
                        <b>ช่วงวันที่ส่งจริง:</b> ${startDisplay} ถึง ${endDisplay}<br>
                        ${failedPeriods.length > 0 ? `<b class="text-danger">ช่วงข้อมูลที่ล้มเหลว:</b><br><ul class="text-danger">${failedPeriods.map(p => `<li>${p}</li>`).join('')}</ul>` : ''}
                        <hr class="my-2">
                        <b>สรุปจำนวนข้อมูลที่ถูกส่งสำเร็จ:</b><br>
                        <ul class="mb-0">
                            <li>OPD: <span class="badge bg-primary">${opdTotal}</span></li>
                            <li>IPD: <span class="badge bg-primary">${ipdTotal}</span></li>
                            <li>IPD Bed: <span class="badge bg-primary">${ipdBedTotal}</span></li>
                            <li>Hospital: <span class="badge bg-primary">${hospitalTotal}</span></li>
                        </ul>
                    </div>
                `;

                Swal.fire({
                    icon: overallSuccess ? 'success' : 'warning',
                    title: 'การส่งข้อมูล AOPOD เสร็จสิ้น',
                    html: summaryText,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    location.reload();
                });
            }
        });
    });

    function startAopodManualSend() {
        Swal.fire({
            title: 'ยืนยันการส่งข้อมูล AOPOD?',
            text: 'ระบบจะเริ่มประมวลผลข้อมูลบริการและส่งข้อมูลย้อนหลัง 10 วัน (นับจากวันนี้) ไปยังเซิร์ฟเวอร์ AOPOD ตามที่ตาราง Schedule กำหนดไว้',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'เริ่มส่งข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'กำลังส่งข้อมูล AOPOD...',
                    html: 'กรุณารอสักครู่ ระบบกำลังประมวลผลและอัปโหลดข้อมูล',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('{{ route("admin.logs.schedule.aopod.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ส่งข้อมูลสำเร็จ',
                            text: data.message,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ส่งข้อมูลล้มเหลว',
                            text: data.message,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาดในการร้องขอ',
                        text: err.message,
                        confirmButtonText: 'ตกลง'
                    });
                });
            }
        });
    }

    function syncDeathData(forceRefresh = true) {
        $('#syncDeathBtn').prop('disabled', true);
        $('#syncProgressContainer').slideDown();
        $('#statsCardsContainer').hide();
        
        let progress = 0;
        let progressInterval = setInterval(() => {
            if (progress < 30) {
                progress += 3;
                $('#syncStepText').html('<i class="bi bi-cloud-arrow-down-fill text-primary"></i> Step 1/3: กำลังเรียกดึงรายชื่อผู้ตายจาก AOPOD API...');
            } else if (progress < 70) {
                progress += 2;
                $('#syncStepText').html('<i class="bi bi-database-fill-gear text-warning"></i> Step 2/3: กำลังสแกนความสอดคล้องของตาราง Patient, Person, Clinic, Death ใน HOSxP...');
            } else if (progress < 90) {
                progress += 1;
                $('#syncStepText').html('<i class="bi bi-bar-chart-line-fill text-success"></i> Step 3/3: กำลังประมวลผลคำนวณอัตราความสมบูรณ์ร้อยละภาพรวม...');
            }
            $('#syncProgressBar').css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');
            $('#syncProgressPct').text(progress + '%');
        }, 80);

        fetch('{{ route("admin.aopod.death-check") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ force_refresh: forceRefresh })
        })
        .then(res => res.json())
        .then(res => {
            clearInterval(progressInterval);
            
            $('#syncProgressBar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
            $('#syncProgressPct').text('100%');
            $('#syncStepText').html('<i class="bi bi-check-circle-fill text-success"></i> ตรวจสอบข้อมูลเสร็จสิ้นเรียบร้อยแล้ว!');

            setTimeout(() => {
                $('#syncProgressContainer').slideUp();
                $('#syncDeathBtn').prop('disabled', false);
                
                if (res.status === 'success') {
                    $('#patientPctText').text(res.stats.patient_pct + '%');
                    $('#patientProgress').css('width', res.stats.patient_pct + '%');
                    $('#patientDetailsText').text(res.stats.patient_details);

                    $('#personPctText').text(res.stats.person_pct + '%');
                    $('#personProgress').css('width', res.stats.person_pct + '%');
                    $('#personDetailsText').text(res.stats.person_details);

                    $('#clinicPctText').text(res.stats.clinic_pct + '%');
                    $('#clinicProgress').css('width', res.stats.clinic_pct + '%');
                    $('#clinicDetailsText').text(res.stats.clinic_details);

                    $('#deathPctText').text(res.stats.death_pct + '%');
                    $('#deathProgress').css('width', res.stats.death_pct + '%');
                    $('#deathDetailsText').text(res.stats.death_details);

                    // อัปเดตตัวเลขจำนวนที่ค้างในหัวตาราง
                    if (res.stats.patient_alive_count > 0) {
                        $('#badgePatientCount').text(`ยังไม่ตาย ${res.stats.patient_alive_count}`).show();
                    } else {
                        $('#badgePatientCount').hide();
                    }
                    if (res.stats.person_alive_count > 0) {
                        $('#badgePersonCount').text(`ยังไม่ตาย ${res.stats.person_alive_count}`).show();
                    } else {
                        $('#badgePersonCount').hide();
                    }
                    if (res.stats.clinic_pending_count > 0) {
                        $('#badgeClinicCount').text(`ยังไม่ตาย ${res.stats.clinic_pending_count}`).show();
                    } else {
                        $('#badgeClinicCount').hide();
                    }
                    if (res.stats.death_unregistered_count > 0) {
                        $('#badgeDeathCount').text(`ยังไม่ลง ${res.stats.death_unregistered_count}`).show();
                    } else {
                        $('#badgeDeathCount').hide();
                    }

                    $('#statsCardsContainer').fadeIn();

                    let badgeClass = 'bg-success-subtle text-success';
                    let statusLabel = 'ดึงข้อมูลแคช';
                    if (res.api_status === 'success') {
                        statusLabel = 'API ออนไลน์';
                    } else if (res.api_status === 'fallback_mock') {
                        statusLabel = 'ใช้ข้อมูลจำลองสำหรับพัฒนา';
                        badgeClass = 'bg-warning-subtle text-warning';
                    } else if (res.api_status === 'failed') {
                        statusLabel = 'API ผิดพลาด: ' + res.api_error;
                        badgeClass = 'bg-danger-subtle text-danger';
                    }
                    $('#apiStatusBadge').attr('class', 'badge px-3 py-1.5 rounded-pill small ' + badgeClass).text(statusLabel);

                    let rowsHtml = '';
                    if (res.details.length === 0) {
                        rowsHtml = `
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                                    ไม่พบรายการข้อมูลขัดแย้ง ประวัติการตายได้รับการบันทึกครบถ้วนสมบูรณ์แล้วใน HOSxP!
                                </td>
                            </tr>
                        `;
                    } else {
                        res.details.forEach(item => {

                            let patientCell = '';
                            let patientOrder = '';
                            if (item.patient_death === 'Y') {
                                patientCell = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>`;
                                patientOrder = '1';
                            } else {
                                patientCell = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย</span>`;
                                patientOrder = '2';
                            }

                            let personCell = '';
                            let personOrder = '';
                            if (item.person_death === 'N/A') {
                                personCell = `<span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-dash-circle me-1"></i> นอกเขต</span>`;
                                personOrder = '2';
                            } else if (item.person_death === 'Y') {
                                personCell = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>`;
                                personOrder = '1';
                            } else {
                                personCell = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย</span>`;
                                personOrder = '3';
                            }

                            let clinicCell = '';
                            let clinicOrder = '';
                            if (!item.has_clinics) {
                                clinicCell = `<span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-dash-circle me-1"></i> ไม่มีคลินิก</span>`;
                                clinicOrder = '2';
                            } else if (item.active_clinics === 0) {
                                clinicCell = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ตาย</span>`;
                                clinicOrder = '1';
                            } else {
                                let itemJson = encodeURIComponent(JSON.stringify(item));
                                clinicCell = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center hover-scale" style="cursor: pointer;" onclick="showActiveClinics('${itemJson}')" title="คลิกเพื่อดูรายละเอียดคลินิก"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ตาย (ค้าง ${item.active_clinics} คลินิก)</span>`;
                                clinicOrder = '3';
                            }

                            let deathCell = '';
                            let deathOrder = '';
                            if (item.death_table_date) {
                                deathCell = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-check-circle-fill me-1"></i> ลงทะเบียนแล้ว</span>`;
                                deathOrder = '1';
                            } else {
                                deathCell = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill small d-inline-flex align-items-center"><i class="bi bi-x-circle-fill me-1"></i> ยังไม่ลง</span>`;
                                deathOrder = '2';
                            }

                            let statusCell = item.is_complete 
                                ? '<i class="bi bi-check-circle-fill text-success fs-4" title="ข้อมูลตรงกัน"></i>' 
                                : '<i class="bi bi-x-circle-fill text-danger fs-4" title="ยังไม่ครบ"></i>';

                            let itemJson = encodeURIComponent(JSON.stringify(item));
                            let actionButton = `<button class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-bold hover-scale" onclick="showDeathDetail('${itemJson}')"><i class="bi bi-clipboard2-pulse"></i> ดูข้อมูล</button>`;

                            rowsHtml += `
                                <tr ${item.is_complete ? 'class="opacity-75 bg-light"' : ''}>
                                    <td>
                                        <span class="fw-bold text-dark d-block">${item.cid}</span>
                                        <small class="text-muted text-uppercase">HN: ${item.hn}</small>
                                    </td>
                                    <td class="fw-semibold text-dark">${item.fullname}</td>
                                    <td class="text-center" data-order="${patientOrder}">${patientCell}</td>
                                    <td class="text-center" data-order="${personOrder}">${personCell}</td>
                                    <td class="text-center" data-order="${clinicOrder}">${clinicCell}</td>
                                    <td class="text-center" data-order="${deathOrder}">${deathCell}</td>
                                    <td class="text-center" data-order="${item.is_complete ? '1' : '2'}">${statusCell}</td>
                                    <td class="text-center">${actionButton}</td>
                                </tr>
                            `;
                        });
                    }

                    // คำนวณค่าผลรวมเพื่ออัปเดตลงใน Footer
                    let totalCount = res.details.length;
                    let patientDead = 0;
                    let personDead = 0;
                    let personNA = 0;
                    let clinicNone = 0;
                    let clinicDischarged = 0;
                    let deathReg = 0;
                    let completeCount = 0;

                    res.details.forEach(item => {
                        if (item.patient_death === 'Y') patientDead++;
                        if (item.person_death === 'Y') personDead++;
                        if (item.person_death === 'N/A' || !item.person_death) personNA++;
                        if (!item.has_clinics) clinicNone++;
                        else if (item.active_clinics === 0) clinicDischarged++;
                        if (item.death_table_date) deathReg++;
                        if (item.is_complete) completeCount++;
                    });

                    let patientAlive = totalCount - patientDead;
                    let personAlive = totalCount - personDead - personNA;
                    let clinicPending = totalCount - clinicNone - clinicDischarged;
                    let deathUnreg = totalCount - deathReg;
                    let incompleteCount = totalCount - completeCount;

                    $('#footTotalCount').text(totalCount);
                    $('#footPatientDead').text(patientDead);
                    $('#footPatientAlive').text(patientAlive);
                    $('#footPersonDead').text(personDead);
                    $('#footPersonAlive').text(personAlive);
                    $('#footPersonNA').text(personNA);
                    $('#footClinicDischarged').text(clinicDischarged);
                    $('#footClinicPending').text(clinicPending);
                    $('#footClinicNone').text(clinicNone);
                    $('#footDeathReg').text(deathReg);
                    $('#footDeathUnreg').text(deathUnreg);
                    $('#footCompleteCount').text(completeCount);
                    $('#footIncompleteCount').text(incompleteCount);
                    if (totalCount > 0) {
                        $('#deathAuditTableFoot').show();
                    } else {
                        $('#deathAuditTableFoot').hide();
                    }

                    if ($.fn.DataTable.isDataTable('#deathAuditTable')) {
                        $('#deathAuditTable').DataTable().destroy();
                    }
                    $('#deathAuditTableBody').html(rowsHtml);
                    $('#deathAuditTable').DataTable({
                        pageLength: 10,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.10.24/i18n/Thai.json'
                        },
                        columnDefs: [
                            { type: 'status-audit', targets: [2, 3, 4] }
                        ],
                        initComplete: function(settings, json) {
                            if (!$('#exportExcelBtn').length) {
                                $('#deathAuditTable_filter').append('<button type="button" id="exportExcelBtn" onclick="exportTableToExcel()" class="btn btn-success btn-sm ms-2 py-1.5 px-3 rounded-pill fw-bold hover-scale shadow-sm"><i class="bi bi-file-earmark-excel-fill me-1"></i> Excel</button>');
                            }
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'ซิงค์ข้อมูลสำเร็จ',
                        text: `วิเคราะห์ประวัติคนไข้ที่ตายจำนวน ${res.details.length} รายการ`,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#198754'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ซิงค์ข้อมูลล้มเหลว',
                        text: res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก API',
                        confirmButtonText: 'ตกลง'
                    });
                }
            }, 500);
        })
        .catch(err => {
            clearInterval(progressInterval);
            $('#syncProgressContainer').slideUp();
            $('#syncDeathBtn').prop('disabled', false);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดการเชื่อมต่อ',
                text: err.message,
                confirmButtonText: 'ตกลง'
            });
        });
    }

    function showDeathDetail(itemJson) {
        let item = JSON.parse(decodeURIComponent(itemJson));
        $('#modalFullname').val(item.fullname);
        $('#modalCid').val(item.cid);
        $('#modalHn').val(item.hn);
        $('#modalDeathDate').val(formatThaiDate(item.aopod_death_date));
        $('#modalDeathDiag').val(item.aopod_death_diag);
        $('#modalDeathCause').val(item.aopod_death_cause);
        $('#modalDeathPlace').val(item.aopod_death_place || 'โรงพยาบาลทั่วไป');
        
        $('#deathDetailModal').modal('show');
    }

    function showActiveClinics(itemJson) {
        let item = JSON.parse(decodeURIComponent(itemJson));
        let clinics = item.active_clinics_list || [];
        
        let listHtml = '<ul class="list-group text-start shadow-sm rounded-4 border-0">';
        clinics.forEach(c => {
            listHtml += `
                <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
                    <div>
                        <span class="badge bg-primary-subtle text-primary me-2 px-2.5 py-1.5 rounded-pill">${c.code}</span>
                        <strong class="text-dark">${c.name}</strong>
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill small">ยังไม่ตาย</span>
                </li>
            `;
        });
        listHtml += '</ul>';

        Swal.fire({
            title: '<span class="fw-bold text-dark fs-5"><i class="bi bi-hospital text-warning me-2"></i>รายชื่อคลินิกโรคเรื้อรังที่ยังไม่ได้จำหน่าย</span>',
            html: `<p class="text-muted small text-start mb-3">คนไข้: <strong>${item.fullname}</strong> (HN: ${item.hn})</p>${listHtml}`,
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#ffc107',
            customClass: {
                popup: 'rounded-4'
            }
        });
    }

    function copyModalField(fieldId) {
        var copyText = document.getElementById(fieldId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกสำเร็จ',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }

    function toggleTokenVisibility() {
        const input = document.getElementById('aopod_token_input');
        const eyeIcon = document.getElementById('toggleTokenEye');
        if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    }

    function saveAopodToken(e) {
        e.preventDefault();
        const token = document.getElementById('aopod_token_input').value;
        const deathUrl = document.getElementById('aopod_url_api_death_input').value;

        Swal.fire({
            title: 'กำลังบันทึกการตั้งค่า...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('{{ route("admin.aopod.save-token") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ 
                aopod_token: token,
                aopod_url_api_death: deathUrl
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: data.message,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#198754'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'บันทึกล้มเหลว',
                    text: data.message || 'เกิดข้อผิดพลาด',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
                text: err.message,
                confirmButtonText: 'ตกลง'
            });
        });
    }

    function exportTableToExcel() {
        if (typeof XLSX === 'undefined') {
            let script = document.createElement('script');
            script.src = "{{ asset('assets/vendor/xlsx.full.min.js') }}";
            script.onload = function() {
                doExportXLSX();
            };
            document.head.appendChild(script);
        } else {
            doExportXLSX();
        }
    }

    function doExportXLSX() {
        let table = $('#deathAuditTable').DataTable();
        let data = table.rows({ search: 'applied' }).data();
        let wsData = [
            ["HN", "CID", "ชื่อ-นามสกุล", "Patient", "Person", "Clinic Member", "Death", "ผลการตรวจสอบ"]
        ];
        
        data.each(function (value, index) {
            let rowNode = table.row(index).node();
            let cols = $(rowNode).find('td');
            
            let cid = $(cols[0]).find('.fw-bold').text().trim();
            let hn = $(cols[0]).find('small').text().replace('HN:', '').trim();
            let fullname = $(cols[1]).text().trim();
            let patient = $(cols[2]).text().trim();
            let person = $(cols[3]).text().trim();
            let clinic = $(cols[4]).text().trim();
            let death = $(cols[5]).text().trim();
            let auditResult = $(cols[6]).attr('data-order') === '1' ? 'ตรงกัน' : 'ยังไม่ครบ';
            
            wsData.push([hn, cid, fullname, patient, person, clinic, death, auditResult]);
        });
        
        let wb = XLSX.utils.book_new();
        let ws = XLSX.utils.aoa_to_sheet(wsData);
        
        // Force HN (Col 0) and CID (Col 1) to String format
        let range = XLSX.utils.decode_range(ws['!ref']);
        for (let R = range.s.r + 1; R <= range.e.r; ++R) {
            let cellA = ws[XLSX.utils.encode_cell({r: R, c: 0})];
            if (cellA) {
                cellA.t = 's';
                cellA.z = '@';
            }
            let cellB = ws[XLSX.utils.encode_cell({r: R, c: 1})];
            if (cellB) {
                cellB.t = 's';
                cellB.z = '@';
            }
        }
        
        XLSX.utils.book_append_sheet(wb, ws, "Audit Result");
        XLSX.writeFile(wb, "AOPOD_Death_Audit_" + new Date().toISOString().slice(0,10) + ".xlsx");
    }
</script>
@endpush
