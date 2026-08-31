@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-robot text-success me-2"></i>
                ตรวจสอบสถานะการเคลม E-Claim สปสช.
            </h5>
            <div class="text-muted small mt-1">วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</div>
        </div>
        
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <form method="POST" action="{{ url('check/eclaim_status') }}" class="d-flex align-items-center gap-2 m-0">
                @csrf
                <div class="input-group input-group-sm" style="width: auto;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                    <input type="hidden" id="patient_type" name="patient_type" value="{{ $patient_type }}">
                    <input type="text" id="start_date_picker" class="form-control datepicker_th border-start-0 text-center" readonly style="width: 130px; cursor: pointer;">
                    
                    <span class="input-group-text bg-white">ถึง</span>
                    
                    <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                    <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 130px; cursor: pointer;">
                </div>

                @php
                    $hipdata_names = [
                        'UCS' => 'UCS สิทธิ UC',
                        'OFC' => 'OFC ข้าราชการ',
                        'SSS' => 'SSS ประกันสังคม',
                        'LGO' => 'LGO อปท',
                        'NHS' => 'NHS สิทธิ สปสช.',
                        'STP' => 'STP บุคคลผู้มีปัญหาสถานะและสิทธิ',
                        'BKK' => 'BKK ข้าราชการ กรุงเทพมหานคร',
                        'BMT' => 'BMT สิทธิองค์การขนส่งมวลชนกรุงเทพ',
                        'SRT' => 'SRT สิทธิการรถไฟแห่งประเทศไทย',
                        'KKT' => 'KKT สิทธิการเคหะแห่งชาติ',
                        'PTY' => 'PTY สิทธิเมืองพัทยา',
                        'WEL' => 'WEL สิทธิบัตรสวัสดิการ',
                        'PVT' => 'PVT สิทธิครูเอกชน',
                    ];

                    $sortOrder = ['UCS', 'OFC', 'SSS', 'LGO', 'NHS', 'STP', 'BKK', 'BMT', 'SRT', 'PVT'];
                    $sorted_hipdata_list = collect($hipdata_list)->sortBy(function($item) use ($sortOrder) {
                        $item_upper = strtoupper(trim($item));
                        $index = array_search($item_upper, $sortOrder);
                        return $index === false ? 999 : $index;
                    })->values()->all();
                @endphp

                <select name="hipdata" class="form-select form-select-sm text-start" style="width: 280px; cursor: pointer;">
                    <option value="">-- ทุกกลุ่มสิทธิประโยชน์ --</option>
                    @foreach($sorted_hipdata_list as $hd)
                        @php
                            $hd_upper = strtoupper(trim($hd));
                        @endphp
                        <option value="{{ $hd }}" {{ $hipdata == $hd ? 'selected' : '' }}>
                            {{ $hipdata_names[$hd_upper] ?? $hd }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm hover-scale">
                    <i class="bi bi-search me-1"></i> ค้นหา
                </button>
            </form>
            
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3.5 shadow-sm hover-scale d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#EclaimAutoPullModal" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <i class="bi bi-arrow-repeat fs-6 me-2"></i> <span>Sync e-Claim Client</span>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#ExtensionInfoModal">
                <i class="bi bi-puzzle-fill me-1.5"></i> Extension
            </button>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#EclaimExcelModal">
                <i class="bi bi-file-earmark-excel-fill me-1.5"></i> นำเข้า Excel
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        @php
            $status_list = [
                '0' => ['name' => 'ผ่านการตรวจสอบขั้นต้น รอส่ง', 'color' => '#6c757d', 'bg' => '#ffffff'],
                '1' => ['name' => 'ส่งไปยังสปสช.', 'color' => '#ffc107', 'bg' => '#ffff99'],
                '2' => ['name' => 'ไม่ผ่านการตรวจสอบขั้นต้น', 'color' => '#dc3545', 'bg' => '#ffcccc'],
                '3' => ['name' => 'ไม่ผ่านการตรวจสอบจากสปสช.(C)', 'color' => '#fd7e14', 'bg' => '#ffd8b1'],
                '4' => ['name' => 'ผ่านการตรวจสอบจากสปสช.(A)', 'color' => '#0dcaf0', 'bg' => '#ccffff'],
            ];
        @endphp

        @foreach($status_list as $code => $info)
            @php
                $item = $summary->get($code);
                $count = $item ? $item->count : 0;
                $sum = $item ? $item->sum_amount : 0;
            @endphp
            <div class="col-md-2-4 col-sm-6">
                <div class="card h-100 border-0 shadow-sm status-card" 
                     data-status="{{ $code }}"
                     style="border-left: 5px solid {{ $info['color'] }} !important; background-color: {{ $info['bg'] }} !important; cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-muted text-uppercase">{{ $info['name'] }}</span>
                            <span class="badge rounded-pill" style="background-color: {{ $info['color'] }}">{{ $code }}</span>
                        </div>
                        <h4 class="mb-1 fw-bold">{{ number_format($count) }} <small class="fs-6 fw-normal text-muted">ราย</small></h4>
                        <div class="text-primary fw-bold">{{ number_format($sum, 2) }} <small class="fw-normal text-muted">บาท</small></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0 mb-4">
        <div class="card-header bg-white border-bottom-0 pb-0 pt-3">
            <ul class="nav nav-tabs card-header-tabs" id="patientTypeTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $patient_type == 'OP' ? 'active' : '' }} fw-bold" id="opd-tab" data-bs-toggle="tab" data-patient-type="OP" type="button" role="tab"><i class="bi bi-person me-1"></i> ผู้ป่วยนอก (OPD)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $patient_type == 'IP' ? 'active' : '' }} fw-bold" id="ipd-tab" data-bs-toggle="tab" data-patient-type="IP" type="button" role="tab"><i class="bi bi-door-open me-1"></i> ผู้ป่วยใน (IPD)</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">E-Claim No.</th>
                            <th class="text-center">HIPDATA</th>
                            <th class="text-center">CID</th>
                            <th class="text-center">HN</th>
                            <th class="text-center">AN</th>
                            <th class="text-start">ชื่อ-สกุล</th>
                            <th class="text-center">วันที่เข้ารับบริการ</th> 
                            <th class="text-center">เวลารับบริการ</th>
                            <th class="text-center">วันที่จำหน่าย</th>
                            <th class="text-center">เวลาจำหน่าย</th>
                            <th class="text-start">สถานะข้อมูล</th>
                            <th class="text-start">ชื่อผู้บันทึกเบิกชดเชย</th>
                            <th class="text-end">ยอดเรียกเก็บ</th>
                            <th class="text-center">REP</th>
                            <th class="text-center">STM</th>
                            <th class="text-center">SEQ</th>
                            <th class="text-start">รายละเอียดการตรวจสอบ</th>
                            <th class="text-start">Deny/Warning</th>
                            <th class="text-center">Channel</th>   
                        </tr>     
                    </thead> 
                    <tbody> 
                    </tbody>
                </table> 
            </div>          
        </div> 
    </div>  
</div>     

<!-- Modal E-Claim Auto Pull (ThaiD Session Powered) -->
<div class="modal fade" id="EclaimAutoPullModal" tabindex="-1" aria-labelledby="EclaimAutoPullModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
      <!-- Modal Header -->
      <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="bg-white bg-opacity-25 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-robot fs-5"></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold mb-0" id="EclaimAutoPullModalLabel">Sync e-Claim Client (ระบบใหม่)</h6>
            <span class="small text-white-50" style="font-size: 0.75rem;">ซิงก์ตรงจาก e-Claim สปสช. (Client/home) ด้วย ThaiD Session</span>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="btnAutoPullCloseX"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-light">
        
        <!-- ThaiD Session Status Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: #ffffff;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2.5">
                <div id="pullSessionIcon" class="rounded-circle p-2 d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 36px; height: 36px;">
                  <i class="bi bi-shield-check fs-5"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark small" id="pullSessionTitle">สถานะการเชื่อมต่อ e-Claim:</span>
                    <span id="pullSessionBadge" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.75rem;">
                      <i class="bi bi-check-circle-fill me-1"></i> เชื่อมต่อแล้ว
                    </span>
                  </div>
                  <div class="text-muted" style="font-size: 0.75rem;" id="pullSessionDetail">
                    ผู้ใช้งาน: <span class="fw-semibold text-dark" id="pullSessionUser">-</span>
                  </div>
                </div>
              </div>
              <div id="pullSessionAction">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 small" onclick="openEclaimThaidQrModal(refreshPullModalBotStatus)" style="font-size: 0.78rem;">
                  <i class="bi bi-qr-code-scan me-1"></i> สแกน ThaiD QR
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 1. FORM SETUP STATE (Before pull) -->
        <div id="pullFormState">
          <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
            <div class="row g-3">
              <!-- Date Range -->
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-calendar-event me-1 text-primary"></i> วันที่เริ่มต้น:
                </label>
                <div class="input-group input-group-sm">
                  <input type="text" id="pull_start_date_picker" class="form-control rounded-3" readonly style="background-color: #fff; cursor: pointer;">
                  <input type="hidden" id="pull_start_date" value="{{ $start_date }}">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-calendar-check me-1 text-primary"></i> ถึงวันที่:
                </label>
                <div class="input-group input-group-sm">
                  <input type="text" id="pull_end_date_picker" class="form-control rounded-3" readonly style="background-color: #fff; cursor: pointer;">
                  <input type="hidden" id="pull_end_date" value="{{ $end_date }}">
                </div>
              </div>

              <!-- Benefit Scheme (สิทธิประโยชน์) -->
              <div class="col-md-12">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-card-checklist me-1 text-primary"></i> สิทธิประโยชน์ (Benefit Scheme):
                </label>
                <select id="pull_hipdata" class="form-select form-select-sm rounded-3">
                  <option value="">-- ทุกสิทธิประโยชน์ (ทั้งหมด) --</option>
                  <option value="UCS">UCS สิทธิ UC (บัตรทอง / ประกันสุขภาพถ้วนหน้า)</option>
                  <option value="OFC">OFC ข้าราชการ / กรมบัญชีกลาง</option>
                  <option value="SSS">SSS ประกันสังคม</option>
                  <option value="LGO">LGO สิทธิ อปท. (องค์กรปกครองส่วนท้องถิ่น)</option>
                  <option value="NHS">NHS สิทธิ สปสช.</option>
                  <option value="STP">STP บุคคลผู้มีปัญหาสถานะและสิทธิ</option>
                  <option value="BKK">BKK ข้าราชการ กรุงเทพมหานคร</option>
                  <option value="BMT">BMT สิทธิองค์การขนส่งมวลชนกรุงเทพ</option>
                  <option value="SRT">SRT สิทธิการรถไฟแห่งประเทศไทย</option>
                  <option value="PVT">PVT สิทธิครูเอกชน</option>
                </select>
              </div>

              <!-- Patient Type (ประเภทผู้ป่วย) -->
              <div class="col-md-12">
                <label class="form-label fw-bold small mb-1 text-secondary">
                  <i class="bi bi-person-badge me-1 text-primary"></i> ประเภทผู้ป่วย:
                </label>
                <div class="d-flex gap-3">
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_all" value="ALL" checked>
                    <label class="form-check-label small" for="pull_pt_all">ทุกประเภท (OPD + IPD)</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_opd" value="OPD">
                    <label class="form-check-label small" for="pull_pt_opd">ผู้ป่วยนอก (OPD)</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="pull_patient_type" id="pull_pt_ipd" value="IPD">
                    <label class="form-check-label small" for="pull_pt_ipd">ผู้ป่วยใน (IPD)</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info border-0 rounded-4 p-3 small mb-0 d-flex gap-2">
            <i class="bi bi-info-circle-fill text-primary fs-5 flex-shrink-0"></i>
            <div>
              <b>ระบบจะวนดึงข้อมูลครบทุกหน้าอัตโนมัติ:</b> และทำการอัปเดตลงตาราง <code>eclaim_status</code> โดยจับคู่ด้วย <b>AN (ผู้ป่วยใน)</b> หรือ <b>SEQ/VN (ผู้ป่วยนอก)</b> ป้องกันข้อมูลซ้ำซ้อน 100%
            </div>
          </div>
        </div>

        <!-- 2. PROGRESS STATE (During pull) -->
        <div id="pullProgressState" class="py-4 text-center" style="display: none;">
          <div class="spinner-border text-primary mb-3" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <h6 class="fw-bold text-dark mb-1" id="pullProgressTitle">กำลังเชื่อมต่อและดึงข้อมูลจาก E-Claim สปสช...</h6>
          <p class="text-muted small mb-3" id="pullProgressDesc">ระบบกำลังกวาดรายการข้อมูลและตรวจสอบสถานะการเคลม</p>
          
          <div class="progress rounded-pill mb-2 shadow-sm" style="height: 12px;">
            <div id="pullProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%;"></div>
          </div>
          <span class="small text-secondary font-monospace" id="pullProgressPercent">กำลังประมวลผลข้อมูล...</span>
        </div>

        <!-- 3. RESULT STATE (After pull completes) -->
        <div id="pullResultState" style="display: none;">
          <div class="alert alert-success border-0 shadow-sm rounded-4 p-3 mb-3 d-flex align-items-center gap-2.5">
            <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
              <i class="bi bi-check-lg fs-5"></i>
            </div>
            <div>
              <h6 class="fw-bold text-success mb-0" id="pullResultHeading">ดึงข้อมูลสำเร็จ!</h6>
              <div class="small text-dark" id="pullResultMessage">-</div>
            </div>
          </div>

          <!-- Summary Stats 4 Cards -->
          <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-primary-subtle text-primary h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">พบทั้งหมด</span>
                <h4 class="fw-bold mb-0 my-1" id="statTotalFound">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการ</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-info-subtle text-info h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">ประเภท</span>
                <div class="fw-bold small my-1" id="statPtType">OPD: 0 | IPD: 0</div>
                <span class="small" style="font-size: 0.7rem;">ราย</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-success-subtle text-success h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">เพิ่มใหม่</span>
                <h4 class="fw-bold mb-0 my-1" id="statInserted">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการ</span>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="card border-0 shadow-sm rounded-4 text-center p-2 bg-warning-subtle text-warning-emphasis h-100">
                <span class="small text-muted" style="font-size: 0.72rem;">อัปเดตสถานะ</span>
                <h4 class="fw-bold mb-0 my-1" id="statUpdated">0</h4>
                <span class="small" style="font-size: 0.7rem;">รายการเดิม</span>
              </div>
            </div>
          </div>

          <!-- Breakdown Details -->
          <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-2">
            <h6 class="fw-bold small mb-2 text-dark border-bottom pb-1.5"><i class="bi bi-pie-chart-fill me-1 text-primary"></i> รายละเอียดตามกลุ่มสิทธิ & สถานะ:</h6>
            <div class="d-flex flex-wrap gap-1.5 mb-2" id="statSchemeBadges"></div>
            <div class="d-flex flex-wrap gap-1.5" id="statStatusBadges"></div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-light border-0 py-2.5 px-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3.5" data-bs-dismiss="modal" id="btnPullCancel">
          <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
        </button>

        <div id="pullFooterActions">
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm d-inline-flex align-items-center" id="btnStartAutoPull" onclick="submitAutoPull()">
            <i class="bi bi-arrow-repeat me-2"></i> <span>เริ่ม Sync e-Claim Client</span>
          </button>
        </div>

        <div id="pullCompletedFooterActions" style="display: none;">
          <button type="button" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" data-bs-dismiss="modal" onclick="reloadEclaimTable()">
            <i class="bi bi-table me-1"></i> ดูข้อมูลในตารางทันที
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Import Excel -->
<div class="modal fade" id="EclaimExcelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel-fill me-2"></i> นำเข้าข้อมูล E-Claim จาก Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ url('check/eclaim_status/import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                <i class="bi bi-info-circle-fill me-2"></i> 
                <strong>คำแนะนำ:</strong> ให้ Export ไฟล์รายงานออกจากหน้าเว็บ E-Claim (.xlsx หรือ .csv) แล้วนำไฟล์นั้นมาอัปโหลดที่นี่
            </div>
            <div class="mb-3">
                <label for="excelFile" class="form-label fw-bold">เลือกไฟล์นามสกุล .xls, .xlsx หรือ .csv</label>
                <input class="form-control" type="file" id="excelFile" name="file[]" multiple required>
            </div>
        </div>
        <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-success rounded-pill px-4" onclick="showLoading()">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> อัปโหลดและประมวลผล
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Extension Info -->
<div class="modal fade" id="ExtensionInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-puzzle-fill text-warning me-2"></i> วิธีติดตั้งและใช้งาน Chrome Extension</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-start">
          <h6 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-1-circle"></i> ขั้นตอนที่ 1 : ติดตั้งส่วนเสริม (ทำครั้งเดียว)</h6>
          <ol class="mb-4 text-muted small lh-lg">
              <li>ดาวน์โหลดไฟล์ส่วนเสริมลงในเครื่องคอมพิวเตอร์ของคุณ <br/><a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-sm btn-outline-primary mt-1 mb-2"><i class="bi bi-download"></i> ดาวน์โหลด eclaim_sync.zip (เวอร์ชั่นล่าสุด)</a><br/> จากนั้น<b>แตกไฟล์ (Extract / Unzip)</b> ลงในโฟลเดอร์ให้เรียบร้อย (เช่น สร้างโฟลเดอร์ชื่อ <code>eclaim_sync</code> บน Desktop)</li>
              <li>เปิด Google Chrome และพิมพ์ที่ช่อง URL ด้านบน: <code class="bg-light p-1 text-primary">chrome://extensions/</code> แล้วกด Enter</li>
              <li>ที่มุมขวาบนของหน้าจอ ให้คลิกเปิดสวิตช์ <b>โหมดนักพัฒนาซอฟต์แวร์ (Developer mode)</b></li>
              <li>คลิกปุ่ม <b>โหลดส่วนขยายที่ยังไม่ได้แพ็ก (Load unpacked)</b> (มุมซ้ายบน) แล้วคลิกเลือกโฟลเดอร์ <code>eclaim_sync</code> ที่แตกไฟล์ไว้</li>
          </ol>

          <h6 class="fw-bold mb-3 text-warning border-bottom pb-2"><i class="bi bi-gear-fill me-1"></i> ขั้นตอนที่ 2 : ตั้งค่าการส่งข้อมูล (ทำครั้งเดียว)</h6>
          <div class="mb-4 text-muted small">
              <p class="mb-1">เมื่อติดตั้งแล้ว ให้ตั้งค่าที่อยู่ในการส่งข้อมูล (API URL) ดังนี้:</p>
              <div class="bg-light p-3 rounded-3 border">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                       <span class="fw-bold text-dark">URL ที่ต้องคัดลอก:</span>
                       <button class="btn btn-xs btn-primary py-0" onclick="copyToClipboard('{{ url('api') }}')">คัดลอก</button>
                  </div>
                  <code id="apiUrlPath" class="text-break text-danger fw-bold">{{ url('api') }}</code>
              </div>
              <ol class="mt-2 lh-lg">
                  <li>คลิกที่ไอคอน Extension <b>"RiMS E-Claim Sync"</b></li>
                  <li>คลิกที่ไอคอน <b>⚙️ (ฟันเฟือง)</b> มุมขวาบนของหน้าต่างป๊อปอัป</li>
                  <li><b>คัดลอก URL ด้านบนไปวาง</b> ในช่อง RiMS API URL จากนั้นกด <b>บันทึกการตั้งค่า</b></li>
              </ol>
          </div>
          
          <h6 class="fw-bold mb-3 text-success border-bottom pb-2"><i class="bi bi-2-circle"></i> ขั้นตอนที่ 3 : วิธีการดึงข้อมูล (ทำรายวัน)</h6>
          <ol class="mb-4 text-muted small lh-lg">
              <li>ให้ใช้ Google Chrome เปิดหน้าเว็บ <a href="https://eclaim.nhso.go.th/Client" target="_blank" class="text-decoration-underline fw-bold">E-Claim สปสช.</a> และ Login เข้าสู่ระบบ</li>
              <li>เปิดเข้าสู่หน้าระบบและค้นหาช่วงวันที่ต้องการ เมื่อมีข้อมูลรายชื่อผู้ป่วยแสดงในตารางเรียบร้อยแล้ว</li>
              <li>คลิกที่ <b>ไอคอนส่วนขยาย "RiMS E-Claim Sync"</b> แล้วกดปุ่ม <b>"ดึงข้อมูลเข้าสู่ RiMS"</b> </li>
              <li>รอให้ระบบทำการกวาดตารางและส่งข้อมูลเข้าฐานข้อมูลของโรงพยาบาลจนขึ้นข้อความสำเร็จ</li>
          </ol>

          <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem">
             <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <b>หมายเหตุ:</b> หากข้อมูลมีหลายหน้า ต้องคลิกเปลี่ยนหน้า และกดปุ่มซิงค์ทีละหน้า
          </div>
      </div>
      <div class="modal-footer border-0 bg-light">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>
</div>

<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: translateY(-2px); }
    .bg-success-soft { background-color: #d1fae5; }
    
    /* Summary Cards */
    .status-card { transition: all 0.3s ease; border-radius: 12px; }
    .status-card:hover { transform: translateY(-5px); shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    .col-md-2-4 { width: 20%; }
    @media (max-width: 992px) { .col-md-2-4 { width: 33.33%; } }
    @media (max-width: 768px) { .col-md-2-4 { width: 50%; } }
    @media (max-width: 576px) { .col-md-2-4 { width: 100%; } }

    /* Row status colors with !important and very high specificity (#list ID) to override DataTable/Bootstrap defaults on td */
    #list.table tbody tr.row-status-1 td { background-color: #ffff99 !important; } /* เหลืองตอง */
    #list.table tbody tr.row-status-2 td { background-color: #ffcccc !important; } /* แดง/ชมพูอ่อน */
    #list.table tbody tr.row-status-3 td { background-color: #ffd8b1 !important; } /* ส้มอ่อน */
    #list.table tbody tr.row-status-4 td { background-color: #ccffff !important; } /* ฟ้าอ่อน/เขียวมินต์ */
    #list.table tbody tr.row-status-0 td { background-color: #ffffff !important; } /* ขาว */
</style>

@endsection

@push('scripts')
<script>
    function showLoading() {
        Swal.fire({
            title: 'กำลังอัปโหลดและตีความไฟล์...',
            html: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showSuccessAlert();
            }).catch(err => {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccessAlert();
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'คัดลอกไม่สำเร็จ',
                text: 'กรุณาคัดลอกด้วยตนเอง: ' + text
            });
        }
        document.body.removeChild(textArea);
    }

    function showSuccessAlert() {
        Swal.fire({
            icon: 'success',
            title: 'คัดลอกแล้ว!',
            text: 'นำไปวางในช่อง RiMS API URL ในหน้าตั้งค่าของ Extension ได้เลย',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // --- E-Claim Auto Pull Functions ---
    function refreshPullModalBotStatus() {
        fetch("{{ route('check.eclaim_status.bot_status') }}")
            .then(res => res.json())
            .then(data => {
                const icon = document.getElementById('pullSessionIcon');
                const badge = document.getElementById('pullSessionBadge');
                const userEl = document.getElementById('pullSessionUser');
                const btnStart = document.getElementById('btnStartAutoPull');

                if (data.connected) {
                    icon.className = "rounded-circle p-2 d-flex align-items-center justify-content-center bg-success-subtle text-success";
                    icon.innerHTML = '<i class="bi bi-shield-check fs-5"></i>';
                    badge.className = "badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5";
                    badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> เชื่อมต่อแล้ว';
                    userEl.innerText = data.user || 'เจ้าหน้าที่ e-Claim';
                    if (btnStart) btnStart.disabled = false;
                } else {
                    icon.className = "rounded-circle p-2 d-flex align-items-center justify-content-center bg-warning-subtle text-warning-emphasis";
                    icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill fs-5"></i>';
                    badge.className = "badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5";
                    badge.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> ยังไม่ได้เชื่อมต่อ';
                    userEl.innerText = data.message || 'กรุณาสแกน ThaiD QR ก่อน';
                }
            })
            .catch(() => {});
    }

    function submitAutoPull() {
        const startDate = document.getElementById('pull_start_date').value;
        const endDate = document.getElementById('pull_end_date').value;
        const hipdata = document.getElementById('pull_hipdata').value;
        const ptRadios = document.getElementsByName('pull_patient_type');
        let patientType = 'ALL';
        for (let r of ptRadios) {
            if (r.checked) { patientType = r.value; break; }
        }

        // Show Progress State
        document.getElementById('pullFormState').style.display = 'none';
        document.getElementById('pullResultState').style.display = 'none';
        document.getElementById('pullProgressState').style.display = 'block';
        document.getElementById('pullFooterActions').style.display = 'none';
        document.getElementById('pullCompletedFooterActions').style.display = 'none';
        document.getElementById('btnPullCancel').disabled = true;
        document.getElementById('btnAutoPullCloseX').disabled = true;

        fetch("{{ route('check.eclaim_status.auto_pull') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                hipdata: hipdata,
                patient_type: patientType
            })
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('pullProgressState').style.display = 'none';
            document.getElementById('btnPullCancel').disabled = false;
            document.getElementById('btnAutoPullCloseX').disabled = false;

            if (data.status === 'success' || data.status === 'info') {
                document.getElementById('pullResultState').style.display = 'block';
                document.getElementById('pullCompletedFooterActions').style.display = 'block';

                document.getElementById('pullResultHeading').innerText = data.status === 'success' ? 'ดึงข้อมูลสำเร็จ!' : 'ผลการตรวจสอบ';
                document.getElementById('pullResultMessage').innerText = data.message;

                const stats = data.stats || {};
                document.getElementById('statTotalFound').innerText = (stats.total || 0).toLocaleString();
                document.getElementById('statPtType').innerText = `OPD: ${stats.opd || 0} | IPD: ${stats.ipd || 0}`;
                document.getElementById('statInserted').innerText = (stats.inserted || 0).toLocaleString();
                document.getElementById('statUpdated').innerText = (stats.updated || 0).toLocaleString();

                // Scheme badges
                const schemeBox = document.getElementById('statSchemeBadges');
                schemeBox.innerHTML = '';
                if (stats.by_scheme && Object.keys(stats.by_scheme).length > 0) {
                    for (let [scheme, count] of Object.entries(stats.by_scheme)) {
                        schemeBox.innerHTML += `<span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small me-1 mb-1">${scheme}: ${count} ราย</span>`;
                    }
                } else {
                    schemeBox.innerHTML = '<span class="text-muted small">ไม่มีข้อมูลสิทธิ</span>';
                }

                // Status badges
                const statusBox = document.getElementById('statStatusBadges');
                statusBox.innerHTML = '';
                if (stats.by_status && Object.keys(stats.by_status).length > 0) {
                    for (let [st, count] of Object.entries(stats.by_status)) {
                        statusBox.innerHTML += `<span class="badge bg-secondary-subtle text-dark border rounded-pill px-2.5 py-1 small me-1 mb-1">${st}: ${count} ราย</span>`;
                    }
                }
            } else if (data.status === 'need_login') {
                // Session expired -> Prompt ThaiD QR
                document.getElementById('pullFormState').style.display = 'block';
                document.getElementById('pullFooterActions').style.display = 'block';
                
                var autoPullModalEl = document.getElementById('EclaimAutoPullModal');
                var autoPullModal = bootstrap.Modal.getInstance(autoPullModalEl);
                if (autoPullModal) {
                    autoPullModal.hide();
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Session e-Claim หมดอายุ',
                    text: 'กรุณาสแกน ThaiD QR Code เพื่อเข้าสู่ระบบ e-Claim ก่อนเริ่มดึงข้อมูลครับ',
                    confirmButtonText: 'สแกน ThaiD ทันที',
                    showCancelButton: true,
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {
                    if (result.isConfirmed) {
                        openEclaimThaidQrModal(function() {
                            // After ThaiD login success, re-open pull modal and auto-start
                            var m = new bootstrap.Modal(document.getElementById('EclaimAutoPullModal'));
                            m.show();
                            submitAutoPull();
                        });
                    }
                });
            } else {
                // Show Error
                document.getElementById('pullFormState').style.display = 'block';
                document.getElementById('pullFooterActions').style.display = 'block';
                Swal.fire({
                    icon: 'error',
                    title: 'ดึงข้อมูลไม่สำเร็จ',
                    text: data.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ e-Claim สปสช.'
                });
            }
        })
        .catch(err => {
            document.getElementById('pullProgressState').style.display = 'none';
            document.getElementById('pullFormState').style.display = 'block';
            document.getElementById('pullFooterActions').style.display = 'block';
            document.getElementById('btnPullCancel').disabled = false;
            document.getElementById('btnAutoPullCloseX').disabled = false;

            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: ' + err.message
            });
        });
    }

    function reloadEclaimTable() {
        // Sync dates from modal to main inputs if user changed them
        const pullStart = document.getElementById('pull_start_date').value;
        const pullEnd = document.getElementById('pull_end_date').value;
        const pullHip = document.getElementById('pull_hipdata').value;

        if (pullStart) {
            $('#start_date').val(pullStart);
            $('#start_date_picker').datepicker('setDate', new Date(pullStart));
        }
        if (pullEnd) {
            $('#end_date').val(pullEnd);
            $('#end_date_picker').datepicker('setDate', new Date(pullEnd));
        }
        if (pullHip) {
            $('select[name="hipdata"]').val(pullHip);
        }

        $('#list').DataTable().ajax.reload(null, false);
    }

    $(document).ready(function () {
      // Initialize Datepicker Thai
      $('.datepicker_th').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1050
      });

      // Modal Datepickers
      $('#pull_start_date_picker').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1100
      });

      $('#pull_end_date_picker').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1100
      });

      // Set initial values
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
          $('#pull_start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
          $('#pull_end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

      // Sync Changes to Hidden Inputs
      $('.datepicker_th').on('changeDate', function(e) {
          var date = e.date;
          var targetId = $(this).attr('id').replace('_picker', '');
          var hiddenInput = $('#' + targetId);
          if(date) {
              var day = ("0" + date.getDate()).slice(-2);
              var month = ("0" + (date.getMonth() + 1)).slice(-2);
              var year = date.getFullYear();
              hiddenInput.val(year + "-" + month + "-" + day);
          } else {
              hiddenInput.val('');
          }
      });

      $('#pull_start_date_picker, #pull_end_date_picker').on('changeDate', function(e) {
          var date = e.date;
          var targetId = $(this).attr('id').replace('_picker', '');
          var hiddenInput = $('#' + targetId);
          if(date) {
              var day = ("0" + date.getDate()).slice(-2);
              var month = ("0" + (date.getMonth() + 1)).slice(-2);
              var year = date.getFullYear();
              hiddenInput.val(year + "-" + month + "-" + day);
          } else {
              hiddenInput.val('');
          }
      });

      // Reset modal state on open
      $('#EclaimAutoPullModal').on('show.bs.modal', function () {
          document.getElementById('pullFormState').style.display = 'block';
          document.getElementById('pullProgressState').style.display = 'none';
          document.getElementById('pullResultState').style.display = 'none';
          document.getElementById('pullFooterActions').style.display = 'block';
          document.getElementById('pullCompletedFooterActions').style.display = 'none';
          document.getElementById('btnPullCancel').disabled = false;
          document.getElementById('btnAutoPullCloseX').disabled = false;

          // Sync current search inputs to modal
          var curStart = $('#start_date').val();
          var curEnd = $('#end_date').val();
          var curHip = $('select[name="hipdata"]').val();
          if (curStart) {
              $('#pull_start_date').val(curStart);
              $('#pull_start_date_picker').datepicker('setDate', new Date(curStart));
          }
          if (curEnd) {
              $('#pull_end_date').val(curEnd);
              $('#pull_end_date_picker').datepicker('setDate', new Date(curEnd));
          }
          if (curHip) {
              $('#pull_hipdata').val(curHip);
          }

          refreshPullModalBotStatus();
      });

      window.currentStatusFilter = '';
      window.currentPatientType = "{{ $patient_type }}";

      $('#list').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('check/eclaim_status') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.hipdata = $('select[name="hipdata"]').val();
                d.status_filter = window.currentStatusFilter || '';
                d.patient_type = window.currentPatientType || 'OP';
            }
        },
        columns: [
            { data: 'eclaim_no', name: 'eclaim_no', className: 'text-center fw-bold' },
            { data: 'hipdata', name: 'hipdata', className: 'text-center small' },
            { data: 'cid', name: 'cid', className: 'text-center' },
            { data: 'hn', name: 'hn', className: 'text-center' },
            { data: 'an', name: 'an', className: 'text-center', render: function(data) { return data || '-'; } },
            { data: 'ptname', name: 'ptname', className: 'text-start' },
            { 
                data: 'vstdate', 
                name: 'vstdate', 
                className: 'text-center small',
                render: function(data) {
                    if (!data) return '-';
                    var date = new Date(data);
                    var day = date.getDate();
                    var month = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."][date.getMonth()];
                    var year = date.getFullYear() + 543;
                    return day + ' ' + month + ' ' + year;
                }
            },
            { data: 'vsttime', name: 'vsttime', className: 'text-center small', render: function(data) { return data || '-'; } },
            { 
                data: 'dchdate', 
                name: 'dchdate', 
                className: 'text-center small',
                render: function(data) {
                    if (!data) return '-';
                    var date = new Date(data);
                    var day = date.getDate();
                    var month = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."][date.getMonth()];
                    var year = date.getFullYear() + 543;
                    return day + ' ' + month + ' ' + year;
                }
            },
            { data: 'dchtime', name: 'dchtime', className: 'text-center small', render: function(data) { return data || '-'; } },
            { data: 'status', name: 'status', className: 'text-start small' },
            { data: 'recorder', name: 'recorder', className: 'text-start small', render: function(data) { return data || '-'; } },
            { 
                data: 'claim_amount', 
                name: 'claim_amount', 
                className: 'text-end fw-bold text-primary',
                render: function(data) {
                    return parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            },
            { data: 'rep', name: 'rep', className: 'text-center small', render: function(data) { return data || '-'; } },
            { data: 'stm', name: 'stm', className: 'text-center small', render: function(data) { return data || '-'; } },
            { data: 'seq', name: 'seq', className: 'text-center small', render: function(data) { return data || '-'; } },
            { data: 'check_detail', name: 'check_detail', className: 'text-start small', render: function(data) { return data || '-'; } },
            { data: 'deny_warning', name: 'deny_warning', className: 'text-start small text-danger', render: function(data) { return data || '-'; } },
            { 
                data: 'channel', 
                name: 'channel', 
                className: 'text-center',
                render: function(data) {
                    if(data === 'Excel') {
                        return '<span class="badge bg-success-soft text-success"><i class="bi bi-file-earmark-excel"></i> Excel</span>';
                    } else if(data === 'Extension') {
                        return '<span class="badge bg-info-soft text-info"><i class="bi bi-browser-chrome"></i> Extension</span>';
                    } else if(data === 'ThaiD Auto' || data === 'Bot') {
                        return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-robot"></i> ThaiD</span>';
                    } else {
                        return '<span class="badge bg-light text-dark">' + (data || '-') + '</span>';
                    }
                }
            }
        ],
        createdRow: function(row, data, dataIndex) {
            var st_code = (data.status || '').substring(0, 1);
            var row_class = 'row-status-0';
            if(st_code === '1') row_class = 'row-status-1';
            else if(st_code === '2') row_class = 'row-status-2';
            else if(st_code === '3') row_class = 'row-status-3';
            else if(st_code === '4') row_class = 'row-status-4';
            $(row).addClass(row_class);
        },
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
              text: 'Export CSV',
              className: 'btn btn-primary btn-sm rounded-pill shadow-sm',
              title: 'รายงานสถานะ E-Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
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
        order: [[6, 'desc']]
      });

      // Dynamic summary card updater when AJAX receives updated stats
      $('#list').on('xhr.dt', function (e, settings, json, xhr) {
          if (json && json.summary) {
              for (var i = 0; i <= 4; i++) {
                  var code = i.toString();
                  var item = json.summary[code];
                  var count = item ? parseInt(item.count) : 0;
                  var sum = item ? parseFloat(item.sum_amount || 0) : 0;
                  
                  var card = $('.status-card[data-status="' + code + '"]');
                  card.find('h4').html(count.toLocaleString() + ' <small class="fs-6 fw-normal text-muted">ราย</small>');
                  card.find('.text-primary').html(sum.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <small class="fw-normal text-muted">บาท</small>');
              }
          }
      });

      // Search Form Submit (AJAX reload without full page flash)
      $('form').on('submit', function(e) {
          e.preventDefault();
          window.currentStatusFilter = '';
          $('.status-card').css('opacity', '1').removeClass('shadow border border-2 border-dark');
          $('#list').DataTable().ajax.reload();
      });

      // Filter table when clicking on status cards (supports toggle on/off)
      $('.status-card').on('click', function() {
          const status = $(this).data('status').toString();
          
          if (window.currentStatusFilter === status) {
              // Unselect / show all
              window.currentStatusFilter = '';
              $('.status-card').css('opacity', '1').removeClass('shadow border border-2 border-dark');
          } else {
              window.currentStatusFilter = status;
              $('.status-card').css('opacity', '0.4').removeClass('shadow border border-2 border-dark');
              $(this).css('opacity', '1').addClass('shadow border border-2 border-dark');
          }
          
          $('#list').DataTable().ajax.reload();
      });

      // Tab switcher event handler
      $('#patientTypeTab button').on('click', function (e) {
          e.preventDefault();
          window.currentPatientType = $(this).data('patient-type');
          $('#patient_type').val(window.currentPatientType);
          
          $('#patientTypeTab button').removeClass('active');
          $(this).addClass('active');

          $('#list').DataTable().ajax.reload();
      });
    });
</script>
@endpush
