@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i> User Management
            </h4>
            <p class="text-muted small mb-0">จัดการข้อมูลผู้ใช้งานระบบและกำหนดสิทธิการเข้าถึง</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add New User
            </button>
        </div>
    </div>

    <!-- User Tabs -->
    <ul class="nav nav-pills mb-3" id="userTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-4 py-2 rounded-pill me-2 d-flex align-items-center gap-2 fw-bold nav-link-active" 
                    id="active-tab" data-bs-toggle="pill" data-bs-target="#active-pane" type="button" role="tab" aria-controls="active-pane" aria-selected="true">
                <i class="bi bi-check-circle-fill"></i>
                เปิดการใช้งาน
                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle ms-1 px-2 py-1">{{ $users->where('active', 'Y')->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-4 py-2 rounded-pill d-flex align-items-center gap-2 fw-bold nav-link-inactive" 
                    id="inactive-tab" data-bs-toggle="pill" data-bs-target="#inactive-pane" type="button" role="tab" aria-controls="inactive-pane" aria-selected="false">
                <i class="bi bi-x-circle-fill"></i>
                ปิดการใช้งาน
                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle ms-1 px-2 py-1">{{ $users->where('active', '!=', 'Y')->count() }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="userTabContent">
        <!-- Active Tab Pane -->
        <div class="tab-pane fade show active" id="active-pane" role="tabpanel" aria-labelledby="active-tab" tabindex="0">
            <div class="dash-card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="activeUserTable" style="width:100%">
                            <thead class="bg-light text-primary border-bottom">
                                <tr>
                                    <th class="ps-4">ชื่อ - นามสกุล</th>
                                    <th>อีเมล (Email)</th>
                                    <th class="text-center">เลขบัตรประชาชน (CID)</th>
                                    <th class="text-center">สถานะใช้งาน</th>
                                    <th class="text-center">ประเภทผู้ใช้</th>
                                    <th class="text-center">สิทธิ์การเข้าถึง</th>
                                    <th class="text-center pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users->where('active', 'Y') as $user)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-box icon-bg-2 me-3 mb-0" style="width: 35px; height: 35px; border-radius: 50%;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <span class="fw-bold">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td class="text-center">
                                            @if(!empty($user->cid))
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-shield-check-fill me-1"></i> {{ $user->cid }}
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> ไม่มี CID
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-success-subtle text-success px-3">
                                                <i class="bi bi-check-circle-fill me-1"></i> Active
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill {{ $user->status === 'admin' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} px-3">
                                                {{ strtoupper($user->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($user->status === 'admin')
                                                <span class="badge rounded-pill bg-dark text-white px-2 shadow-sm" style="font-size: 0.7rem;">ADMIN (ALL)</span>
                                            @else
                                                <div class="d-flex flex-wrap justify-content-center gap-1" style="max-width: 250px; margin: 0 auto;">
                                                    @if($user->allow_home === 'Y') <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">Home Detail</span> @endif
                                                    @if($user->allow_import === 'Y') <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">นำเข้าข้อมูล</span> @endif
                                                    @if($user->allow_check === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ตรวจสอบข้อมูล</span> @endif
                                                     @if($user->allow_check_right === 'Y') <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">ตรวจสอบสิทธิการรักษา (สปสช.)</span> @endif
                                                    @if($user->allow_emr === 'Y') <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.65rem;">งานเวชระเบียน</span> @endif
                                                    @if($user->allow_claim_op === 'Y') <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">เรียกเก็บ OP</span> @endif
                                                    @if($user->allow_claim_ip === 'Y') <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">เรียกเก็บ IP</span> @endif
                                                    @if($user->allow_mishos === 'Y') <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 0.65rem;">MIS Hospital</span> @endif
                                                    @if($user->allow_debtor === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ลูกหนี้ค่ารักษา</span> @endif
                                                    @if($user->allow_debtor_lock === 'Y') <span class="badge bg-danger text-white" style="font-size: 0.65rem;">Lock ลูกหนี้</span> @endif
                                                    @if($user->allow_debtor_acc === 'Y') <span class="badge bg-info text-dark" style="font-size: 0.65rem;">ทะเบียนคุมลูกหนี้</span> @endif
                                                    @if($user->allow_receipt === 'Y') <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.65rem;">ออกใบเสร็จ</span> @endif
                                                    @if($user->allow_nhso_endpoint === 'Y') <span class="badge bg-primary text-white" style="font-size: 0.65rem;">ปิดสิทธิ สปสช. (API)</span> @endif
                                                    @if($user->allow_aopod_death === 'Y') <span class="badge bg-success text-white" style="font-size: 0.65rem;">AOPOD ข้อมูลการตาย</span> @endif
                                                    @if($user->allow_hosfin === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">HosFin</span> @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->name }}"
                                                    data-email="{{ $user->email }}"
                                                    data-active="{{ $user->active }}"
                                                    data-status="{{ $user->status }}"
                                                    data-allow_home="{{ $user->allow_home }}"
                                                    data-allow_import="{{ $user->allow_import }}"
                                                    data-allow_check="{{ $user->allow_check }}"
                                                     data-allow_check_right="{{ $user->allow_check_right }}"
                                                    data-allow_emr="{{ $user->allow_emr }}"
                                                    data-allow_claim_op="{{ $user->allow_claim_op }}"
                                                    data-allow_claim_ip="{{ $user->allow_claim_ip }}"
                                                    data-allow_mishos="{{ $user->allow_mishos }}"
                                                    data-allow_debtor="{{ $user->allow_debtor }}"
                                                    data-allow_debtor_lock="{{ $user->allow_debtor_lock }}"
                                                    data-allow_debtor_acc="{{ $user->allow_debtor_acc }}"
                                                    data-allow_receipt="{{ $user->allow_receipt }}"
                                                    data-cid="{{ $user->cid }}"
                                                    data-fdh_user="{{ $user->fdh_user }}"
                                                    data-fdh_pass="{{ $user->fdh_pass }}"
                                                    data-fdh_secret_key="{{ $user->fdh_secretKey }}"
                                                    data-allow_nhso_endpoint="{{ $user->allow_nhso_endpoint }}"
                                                    data-allow_aopod_death="{{ $user->allow_aopod_death }}"
                                                    data-allow_hosfin="{{ $user->allow_hosfin }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal"
                                                    title="แก้ไข">
                                                    <i class="bi bi-pencil-square text-warning"></i>
                                                </button>
                                                <form class="d-inline reset-password-form" method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                                    @csrf
                                                    <button type="button" class="btn btn-white btn-sm px-3 btn-reset-password border-end" data-name="{{ $user->name }}" title="รีเซ็ตรหัสผ่าน">
                                                        <i class="bi bi-key text-info"></i>
                                                    </button>
                                                </form>
                                                <form class="d-inline delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-white btn-sm px-3 btn-delete" title="ลบ">
                                                        <i class="bi bi-trash3 text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inactive Tab Pane -->
        <div class="tab-pane fade" id="inactive-pane" role="tabpanel" aria-labelledby="inactive-tab" tabindex="0">
            <div class="dash-card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="inactiveUserTable" style="width:100%">
                            <thead class="bg-light text-primary border-bottom">
                                <tr>
                                    <th class="ps-4">ชื่อ - นามสกุล</th>
                                    <th>อีเมล (Email)</th>
                                    <th class="text-center">เลขบัตรประชาชน (CID)</th>
                                    <th class="text-center">สถานะใช้งาน</th>
                                    <th class="text-center">ประเภทผู้ใช้</th>
                                    <th class="text-center">สิทธิ์การเข้าถึง</th>
                                    <th class="text-center pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users->where('active', '!=', 'Y') as $user)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-box icon-bg-2 me-3 mb-0" style="width: 35px; height: 35px; border-radius: 50%;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <span class="fw-bold">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td class="text-center">
                                            @if(!empty($user->cid))
                                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-shield-check-fill me-1"></i> {{ $user->cid }}
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> ไม่มี CID
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-danger-subtle text-danger px-3">
                                                <i class="bi bi-x-circle-fill me-1"></i> Inactive
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill {{ $user->status === 'admin' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} px-3">
                                                {{ strtoupper($user->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($user->status === 'admin')
                                                <span class="badge rounded-pill bg-dark text-white px-2 shadow-sm" style="font-size: 0.7rem;">ADMIN (ALL)</span>
                                            @else
                                                <div class="d-flex flex-wrap justify-content-center gap-1" style="max-width: 250px; margin: 0 auto;">
                                                    @if($user->allow_home === 'Y') <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">Home Detail</span> @endif
                                                    @if($user->allow_import === 'Y') <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">นำเข้าข้อมูล</span> @endif
                                                    @if($user->allow_check === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ตรวจสอบข้อมูล</span> @endif
                                                     @if($user->allow_check_right === 'Y') <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">ตรวจสอบสิทธิการรักษา (สปสช.)</span> @endif
                                                    @if($user->allow_emr === 'Y') <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.65rem;">งานเวชระเบียน</span> @endif
                                                    @if($user->allow_claim_op === 'Y') <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">เรียกเก็บ OP</span> @endif
                                                    @if($user->allow_claim_ip === 'Y') <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">เรียกเก็บ IP</span> @endif
                                                    @if($user->allow_mishos === 'Y') <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size: 0.65rem;">MIS Hospital</span> @endif
                                                    @if($user->allow_debtor === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ลูกหนี้ค่ารักษา</span> @endif
                                                    @if($user->allow_debtor_lock === 'Y') <span class="badge bg-danger text-white" style="font-size: 0.65rem;">Lock ลูกหนี้</span> @endif
                                                    @if($user->allow_debtor_acc === 'Y') <span class="badge bg-info text-dark" style="font-size: 0.65rem;">ทะเบียนคุมลูกหนี้</span> @endif
                                                    @if($user->allow_receipt === 'Y') <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.65rem;">ออกใบเสร็จ</span> @endif
                                                    @if($user->allow_nhso_endpoint === 'Y') <span class="badge bg-primary text-white" style="font-size: 0.65rem;">ปิดสิทธิ สปสช. (API)</span> @endif
                                                    @if($user->allow_aopod_death === 'Y') <span class="badge bg-success text-white" style="font-size: 0.65rem;">AOPOD ข้อมูลการตาย</span> @endif
                                                    @if($user->allow_hosfin === 'Y') <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">HosFin</span> @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ $user->name }}"
                                                    data-email="{{ $user->email }}"
                                                    data-active="{{ $user->active }}"
                                                    data-status="{{ $user->status }}"
                                                    data-allow_home="{{ $user->allow_home }}"
                                                    data-allow_import="{{ $user->allow_import }}"
                                                    data-allow_check="{{ $user->allow_check }}"
                                                     data-allow_check_right="{{ $user->allow_check_right }}"
                                                    data-allow_emr="{{ $user->allow_emr }}"
                                                    data-allow_claim_op="{{ $user->allow_claim_op }}"
                                                    data-allow_claim_ip="{{ $user->allow_claim_ip }}"
                                                    data-allow_mishos="{{ $user->allow_mishos }}"
                                                    data-allow_debtor="{{ $user->allow_debtor }}"
                                                    data-allow_debtor_lock="{{ $user->allow_debtor_lock }}"
                                                    data-allow_debtor_acc="{{ $user->allow_debtor_acc }}"
                                                    data-allow_receipt="{{ $user->allow_receipt }}"
                                                    data-cid="{{ $user->cid }}"
                                                    data-fdh_user="{{ $user->fdh_user }}"
                                                    data-fdh_pass="{{ $user->fdh_pass }}"
                                                    data-fdh_secret_key="{{ $user->fdh_secretKey }}"
                                                    data-allow_nhso_endpoint="{{ $user->allow_nhso_endpoint }}"
                                                    data-allow_aopod_death="{{ $user->allow_aopod_death }}"
                                                    data-allow_hosfin="{{ $user->allow_hosfin }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal"
                                                    title="แก้ไข">
                                                    <i class="bi bi-pencil-square text-warning"></i>
                                                </button>
                                                <form class="d-inline reset-password-form" method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                                    @csrf
                                                    <button type="button" class="btn btn-white btn-sm px-3 btn-reset-password border-end" data-name="{{ $user->name }}" title="รีเซ็ตรหัสผ่าน">
                                                        <i class="bi bi-key text-info"></i>
                                                    </button>
                                                </form>
                                                <form class="d-inline delete-form" method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn btn-white btn-sm px-3 btn-delete" title="ลบ">
                                                        <i class="bi bi-trash3 text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i> Create New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ชื่อ-สกุล</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                <input name="name" type="text" class="form-control bg-light border-start-0 ps-0" placeholder="กรอกชื่อ-นามสกุล" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input name="email" type="email" class="form-control bg-light border-start-0 ps-0" placeholder="example@mail.com" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Initial Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key"></i></span>
                                <input name="password" type="password" class="form-control bg-light border-start-0 ps-0" placeholder="ไม่ต่ำกว่า 6 ตัวอักษร" required minlength="6">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">เลขบัตรประชาชน CID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-heading"></i></span>
                                <input name="cid" type="text" class="form-control bg-light border-start-0 ps-0" placeholder="เลขบัตรประชาชน 13 หลัก">
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">FDH User</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="addFdhUser" name="fdh_user" type="text" placeholder="user.hcode">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">FDH Pass</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="addFdhPass" name="fdh_pass" type="password" placeholder="FDH Password">
                                <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" onclick="togglePassVisibility('addFdhPass', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">FDH Secret Key</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="addFdhSecretKey" name="fdh_secretKey" type="password" placeholder="Secret Key">
                                <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" onclick="togglePassVisibility('addFdhSecretKey', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-success w-100 py-2 rounded-3 btn-test-fdh-token" data-user-input="#addFdhUser" data-pass-input="#addFdhPass" data-key-input="#addFdhSecretKey">
                                <i class="bi bi-shield-check me-1"></i> ทดสอบ Token
                            </button>
                        </div>
                    </div>
                    <hr class="my-4 opacity-10">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Permissions (สิทธิ์การเข้าถึง)</h6>
                    <div class="row row-cols-md-4 row-cols-1 g-3">
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_home" id="add_allow_home" value="Y">
                                <label class="form-check-label small" for="add_allow_home">Home Detail</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_import" id="add_allow_import" value="Y">
                                <label class="form-check-label small" for="add_allow_import">นำเข้าข้อมูล</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_check" id="add_allow_check" value="Y">
                                <label class="form-check-label small" for="add_allow_check">ตรวจสอบข้อมูล</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_check_right" id="add_allow_check_right" value="Y">
                                <label class="form-check-label small text-info fw-bold" for="add_allow_check_right">ตรวจสอบสิทธิการรักษา (สปสช.)</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_emr" id="add_allow_emr" value="Y">
                                <label class="form-check-label small" for="add_allow_emr">งานเวชระเบียน</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_claim_op" id="add_allow_claim_op" value="Y">
                                <label class="form-check-label small" for="add_allow_claim_op">เรียกเก็บ OP</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_claim_ip" id="add_allow_claim_ip" value="Y">
                                <label class="form-check-label small" for="add_allow_claim_ip">เรียกเก็บ IP</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_mishos" id="add_allow_mishos" value="Y">
                                <label class="form-check-label small" for="add_allow_mishos">MIS Hospital</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_debtor" id="add_allow_debtor" value="Y">
                                <label class="form-check-label small" for="add_allow_debtor">ลูกหนี้ค่ารักษา</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_debtor_lock" id="add_allow_debtor_lock" value="Y">
                                <label class="form-check-label small text-danger fw-bold" for="add_allow_debtor_lock">Lock ลูกหนี้</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_debtor_acc" id="add_allow_debtor_acc" value="Y">
                                <label class="form-check-label small text-info fw-bold" for="add_allow_debtor_acc">ทะเบียนคุมลูกหนี้</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_receipt" id="add_allow_receipt" value="Y">
                                <label class="form-check-label small text-warning fw-bold" for="add_allow_receipt">สิทธิ์การออกใบเสร็จ</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_nhso_endpoint" id="add_allow_nhso_endpoint" value="Y">
                                <label class="form-check-label small text-primary fw-bold" for="add_allow_nhso_endpoint">ปิดสิทธิ สปสช. (API)</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="allow_hosfin" id="add_allow_hosfin" value="Y">
                                <label class="form-check-label small text-success fw-bold" for="add_allow_hosfin">HosFin</label>
                            </div>
                        </div>
                        @if(\Illuminate\Support\Facades\Schema::hasTable('lookup_hospcode') && \Illuminate\Support\Facades\DB::table('lookup_hospcode')->where('hospcode', '00025')->exists())
                            <div class="col">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="allow_aopod_death" id="add_allow_aopod_death" value="Y">
                                    <label class="form-check-label small text-success fw-bold" for="add_allow_aopod_death">AOPOD ข้อมูลการตาย</label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <input type="hidden" name="active" value="Y">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4 rounded-pill">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <form method="POST" id="editForm" class="modal-content border-0 shadow-lg">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit User Information
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">ชื่อ-สกุล</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editName" name="name" type="text" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editEmail" name="email" type="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">เลขบัตรประชาชน CID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-heading"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editCid" name="cid" type="text" placeholder="เลขบัตรประชาชน 13 หลัก">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label fw-bold">User Status</label>
                                    <select class="form-select bg-light" id="editStatus" name="status">
                                        <option value="user">User</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold d-block">Account Active</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" name="active" id="editActive" value="Y">
                                        <label class="form-check-label ms-2" for="editActive" id="activeLabel">เปิดใช้งาน</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-end mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">FDH User</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editFdhUser" name="fdh_user" type="text" placeholder="user.hcode">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">FDH Pass</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-key"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editFdhPass" name="fdh_pass" type="password" placeholder="FDH Password">
                                <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" onclick="togglePassVisibility('editFdhPass', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">FDH Secret Key</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock"></i></span>
                                <input class="form-control bg-light border-start-0 ps-0" id="editFdhSecretKey" name="fdh_secretKey" type="password" placeholder="Secret Key">
                                <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" onclick="togglePassVisibility('editFdhSecretKey', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-success w-100 py-2 rounded-3 btn-test-fdh-token" data-user-input="#editFdhUser" data-pass-input="#editFdhPass" data-key-input="#editFdhSecretKey">
                                <i class="bi bi-shield-check me-1"></i> ทดสอบ Token
                            </button>
                        </div>
                    </div>
                    <hr class="my-4 opacity-10">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-shield-check me-2"></i>Permissions (สิทธิ์การเข้าถึง)</h6>
                    <div class="row row-cols-md-4 row-cols-1 g-3">
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_home" id="edit_allow_home" value="Y">
                                <label class="form-check-label small" for="edit_allow_home">Home Detail</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_import" id="edit_allow_import" value="Y">
                                <label class="form-check-label small" for="edit_allow_import">นำเข้าข้อมูล</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_check" id="edit_allow_check" value="Y">
                                <label class="form-check-label small" for="edit_allow_check">ตรวจสอบข้อมูล</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_check_right" id="edit_allow_check_right" value="Y">
                                <label class="form-check-label small text-info fw-bold" for="edit_allow_check_right">ตรวจสอบสิทธิการรักษา (สปสช.)</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_emr" id="edit_allow_emr" value="Y">
                                <label class="form-check-label small" for="edit_allow_emr">งานเวชระเบียน</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_claim_op" id="edit_allow_claim_op" value="Y">
                                <label class="form-check-label small" for="edit_allow_claim_op">เรียกเก็บ OP</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_claim_ip" id="edit_allow_claim_ip" value="Y">
                                <label class="form-check-label small" for="edit_allow_claim_ip">เรียกเก็บ IP</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_mishos" id="edit_allow_mishos" value="Y">
                                <label class="form-check-label small" for="edit_allow_mishos">MIS Hospital</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_debtor" id="edit_allow_debtor" value="Y">
                                <label class="form-check-label small" for="edit_allow_debtor">ลูกหนี้ค่ารักษา</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_debtor_lock" id="edit_allow_debtor_lock" value="Y">
                                <label class="form-check-label small text-danger fw-bold" for="edit_allow_debtor_lock">Lock ลูกหนี้</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_debtor_acc" id="edit_allow_debtor_acc" value="Y">
                                <label class="form-check-label small text-info fw-bold" for="edit_allow_debtor_acc">ทะเบียนคุมลูกหนี้</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_receipt" id="edit_allow_receipt" value="Y">
                                <label class="form-check-label small text-warning fw-bold" for="edit_allow_receipt">สิทธิ์การออกใบเสร็จ</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_nhso_endpoint" id="edit_allow_nhso_endpoint" value="Y">
                                <label class="form-check-label small text-primary fw-bold" for="edit_allow_nhso_endpoint">ปิดสิทธิ สปสช. (API)</label>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input p_switch" type="checkbox" name="allow_hosfin" id="edit_allow_hosfin" value="Y">
                                <label class="form-check-label small text-success fw-bold" for="edit_allow_hosfin">HosFin</label>
                            </div>
                        </div>
                        @if(\Illuminate\Support\Facades\Schema::hasTable('lookup_hospcode') && \Illuminate\Support\Facades\DB::table('lookup_hospcode')->where('hospcode', '00025')->exists())
                            <div class="col">
                                <div class="form-check form-switch">
                                    <input class="form-check-input p_switch" type="checkbox" name="allow_aopod_death" id="edit_allow_aopod_death" value="Y">
                                    <label class="form-check-label small text-success fw-bold" for="edit_allow_aopod_death">AOPOD ข้อมูลการตาย</label>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">อัปเดตข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: scale(1.02); }
    .btn-white { background: #fff; border: 1px solid #edf2f7; }
    .btn-white:hover { background: #f8fafc; }
    .bg-success-subtle { background-color: #d1fae5; }
    .bg-danger-subtle { background-color: #fee2e2; }
    .bg-primary-subtle { background-color: #dbeafe; }
    .bg-secondary-subtle { background-color: #f1f5f9; }
    .bg-info-subtle { background-color: #e0f2fe; }
    .bg-warning-subtle { background-color: #fef3c7; }

    .nav-pills .nav-link-active {
        color: #15803d !important;
        background-color: #f0fdf4 !important;
        border: 1px solid #bbf7d0 !important;
        transition: all 0.2s ease-in-out;
    }
    .nav-pills .nav-link-active.active {
        color: #ffffff !important;
        background-color: #16a34a !important;
        border-color: #16a34a !important;
        box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2);
    }
    .nav-pills .nav-link-inactive {
        color: #b91c1c !important;
        background-color: #fef2f2 !important;
        border: 1px solid #fecaca !important;
        transition: all 0.2s ease-in-out;
    }
    .nav-pills .nav-link-inactive.active {
        color: #ffffff !important;
        background-color: #dc2626 !important;
        border-color: #dc2626 !important;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
    }
</style>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Initialize DataTable on both tables
        $('#activeUserTable, #inactiveUserTable').DataTable({
            pageLength: 10,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
            order: [[0, 'asc']],
            dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // Adjust column sizes of hidden tables when tab switching occurs to prevent layout bugs
        $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // Set ข้อมูลใน Edit Modal (using event delegation for DataTables compatibility)
        $(document).on('click', '.btn-edit', function () {
            const data = $(this).data();
            $('#editName').val(data.name);
            $('#editEmail').val(data.email);
            $('#editStatus').val(data.status);
            $('#editActive').prop('checked', data.active === 'Y');
            
            // Set Permissions
            $('#edit_allow_home').prop('checked', data.allow_home === 'Y');
            $('#edit_allow_import').prop('checked', data.allow_import === 'Y');
            $('#edit_allow_check').prop('checked', data.allow_check === 'Y');
            $('#edit_allow_check_right').prop('checked', data.allow_check_right === 'Y');
            $('#edit_allow_emr').prop('checked', data.allow_emr === 'Y');
            $('#edit_allow_claim_op').prop('checked', data.allow_claim_op === 'Y');
            $('#edit_allow_claim_ip').prop('checked', data.allow_claim_ip === 'Y');
            $('#edit_allow_mishos').prop('checked', data.allow_mishos === 'Y');
            $('#edit_allow_debtor').prop('checked', data.allow_debtor === 'Y');
            $('#edit_allow_debtor_lock').prop('checked', data.allow_debtor_lock === 'Y');
            $('#edit_allow_debtor_acc').prop('checked', data.allow_debtor_acc === 'Y');
            $('#edit_allow_receipt').prop('checked', data.allow_receipt === 'Y');
            $('#edit_allow_nhso_endpoint').prop('checked', data.allow_nhso_endpoint === 'Y');
            $('#edit_allow_aopod_death').prop('checked', data.allow_aopod_death === 'Y');
            $('#edit_allow_hosfin').prop('checked', data.allow_hosfin === 'Y');
            $('#editCid').val(data.cid);

            // Set FDH credentials
            $('#editFdhUser').val(data.fdh_user || '');
            $('#editFdhPass').val(data.fdh_pass || '');
            $('#editFdhSecretKey').val(data.fdh_secret_key || '');

            updateActiveLabel(data.active === 'Y');
            
            // Disable permissions if admin
            togglePermissionInputs(data.status === 'admin');
            
            $('#editForm').attr('action', "{{ url('admin/users') }}/" + data.id);
        });

        $('#editStatus').on('change', function() {
            togglePermissionInputs($(this).val() === 'admin');
        });

        function togglePermissionInputs(isAdmin) {
            if (isAdmin) {
                $('.p_switch').prop('checked', true).prop('disabled', true);
            } else {
                $('.p_switch').prop('disabled', false);
            }
        }

        $('#editActive').on('change', function() {
            updateActiveLabel($(this).is(':checked'));
        });

        function updateActiveLabel(isActive) {
            $('#activeLabel').text(isActive ? 'เปิดใช้งาน' : 'ระงับการใช้งาน').toggleClass('text-success', isActive).toggleClass('text-danger', !isActive);
        }

        // Test FDH Token
        $(document).on('click', '.btn-test-fdh-token', function () {
            const userInput = $(this).data('user-input');
            const passInput = $(this).data('pass-input');
            const keyInput = $(this).data('key-input');

            const fdhUser = $(userInput).val() ? $(userInput).val().trim() : '';
            const fdhPass = $(passInput).val() ? $(passInput).val().trim() : '';
            const fdhSecretKey = $(keyInput).val() ? $(keyInput).val().trim() : '';

            if (!fdhUser || !fdhPass || !fdhSecretKey) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณากรอก FDH User, FDH Pass และ FDH Secret Key ให้ครบถ้วนก่อนทดสอบ Token',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0ea5e9',
                    borderRadius: '15px'
                });
                return;
            }

            Swal.fire({
                title: 'กำลังทดสอบการเชื่อมต่อ...',
                text: 'กรุณารอสักครู่ ระบบกำลังขอ Token จาก FDH',
                allowOutsideClick: false,
                borderRadius: '15px',
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('{{ route("profile.test-fdh-token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    fdh_user: fdhUser,
                    fdh_pass: fdhPass,
                    fdh_secretKey: fdhSecretKey
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.token) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เชื่อมต่อสำเร็จ',
                        html: `
                            <div class="text-start p-2">
                                <p class="mb-2 text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> ดึง Access Token สำเร็จ</p>
                                <div class="bg-light p-3 small border rounded-3" style="word-break: break-all; font-family: monospace; max-height: 150px; overflow-y: auto;">
                                    ${data.token}
                                </div>
                            </div>
                        `,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#16a34a',
                        borderRadius: '15px'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เชื่อมต่อล้มเหลว',
                        text: data.message || 'ไม่สามารถขอ Access Token จาก FDH ได้ กรุณาตรวจสอบความถูกต้องของ FDH User และ Password',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#ef4444',
                        borderRadius: '15px'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: error.message || error,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#ef4444',
                    borderRadius: '15px'
                });
            });
        });

        // SweetAlert ยืนยัน Reset Password
        $(document).on('click', '.btn-reset-password', function () {
            const form = $(this).closest('form');
            const name = $(this).data('name');
            Swal.fire({
                title: 'ยืนยันรีเซ็ตรหัสผ่าน?',
                text: `คุณต้องการรีเซ็ตรหัสผ่านของ "${name}" เป็น "12345678" ใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, รีเซ็ตทันที!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                borderRadius: '15px'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // SweetAlert ยืนยันลบ (using event delegation for DataTables compatibility)
        $(document).on('click', '.btn-delete', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบผู้ใช้?',
                text: "ข้อมูลนี้จะหายไปอย่างถาวรและไม่สามารถเรียกคืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบทันที!',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                borderRadius: '15px'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false,
                borderRadius: '15px'
            });
        @endif
    });

    function togglePassVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endpush