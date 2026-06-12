@extends('layouts.app')

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-search text-primary me-2"></i> Lookup iCode
            </h4>
            <p class="text-muted small mb-0">จัดการรหัสรายการบริการแยกตามประเภทสิทธิและการรักษา</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group shadow-sm">
                <form method="POST" action="{{ route('admin.insert_lookup_uc_cr') }}" class="d-inline import-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 rounded-start-pill border-end-0">
                        <i class="bi bi-cloud-download me-1"></i> นำเข้า UC_CR
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.insert_lookup_ppfs') }}" class="d-inline import-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 border-start-0 border-end-0">
                         นำเข้า PPFS
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.insert_lookup_herb32') }}" class="d-inline import-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 border-start-0 border-end-0">
                         นำเข้า Herb32
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.insert_lookup_sss_hc') }}" class="d-inline import-form">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 rounded-end-pill border-start-0">
                        <i class="bi bi-cloud-download me-1"></i> นำเข้า SSS-HC
                    </button>
                </form>
            </div>
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Add iCode
            </button>
        </div>
    </div>



    <div class="dash-card border-0 shadow-sm overflow-visible">
        <div class="card-header bg-white border-bottom-0 pt-3 px-4">
            <ul class="nav nav-pills nav-fill gap-2 modern-tabs" id="icodeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-bold text-dark" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-pane" type="button" role="tab">
                        <i class="bi bi-grid-fill me-1"></i> ทั้งหมด ({{ number_format($all->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-primary" id="uc-tab" data-bs-toggle="tab" data-bs-target="#uc-pane" type="button" role="tab">
                         UC-CR ({{ number_format($uc_cr->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-success" id="ppfs-tab" data-bs-toggle="tab" data-bs-target="#ppfs-pane" type="button" role="tab">
                         PPFS ({{ number_format($ppfs->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-warning" id="herb-tab" data-bs-toggle="tab" data-bs-target="#herb-pane" type="button" role="tab">
                         สมุนไพร ({{ number_format($herb32->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-info" id="kidney-tab" data-bs-toggle="tab" data-bs-target="#kidney-pane" type="button" role="tab">
                         ฟอกไต HD ({{ number_format($kidney->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-danger" id="ems-tab" data-bs-toggle="tab" data-bs-target="#ems-pane" type="button" role="tab">
                         EMS ({{ number_format($ems->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold" id="ssshc-tab" data-bs-toggle="tab" data-bs-target="#ssshc-pane" type="button" role="tab" style="color: #6366f1;">
                         SSS-HC ({{ number_format($sss_hc->count()) }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold text-danger" id="missing-tab" data-bs-toggle="tab" data-bs-target="#missing-pane" type="button" role="tab">
                         📋 ตรวจสอบรหัสคู่มือ ({{ number_format($total_rules_count) }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0 pt-2">
            <div class="tab-content" id="icodeTabContent">
                @php
                    $panes = [
                        ['id' => 'all', 'data' => $all],
                        ['id' => 'uc', 'data' => $uc_cr],
                        ['id' => 'ppfs', 'data' => $ppfs],
                        ['id' => 'herb', 'data' => $herb32],
                        ['id' => 'kidney', 'data' => $kidney],
                        ['id' => 'ems', 'data' => $ems],
                        ['id' => 'ssshc', 'data' => $sss_hc],
                    ];
                @endphp

                @foreach($panes as $index => $pane)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $pane['id'] }}-pane" role="tabpanel" tabindex="0">
                        @if($pane['id'] === 'uc')
                            <div class="p-3">
                                <!-- Sub Tabs for UC-CR -->
                                <ul class="nav nav-tabs mb-3 border-bottom" id="ucSubTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active fw-bold px-4 py-2 border-0 border-bottom border-3 text-primary" id="uc-ins-subtab" data-bs-toggle="tab" data-bs-target="#uc-ins-subpane" type="button" role="tab" style="border-bottom-color: #3b82f6 !important; border-radius: 0;">
                                             Instrument ({{ number_format($uc_cr_instrument->count()) }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link fw-bold px-4 py-2 border-0 border-bottom border-3 text-secondary" id="uc-other-subtab" data-bs-toggle="tab" data-bs-target="#uc-other-subpane" type="button" role="tab" style="border-bottom-color: #64748b !important; border-radius: 0;">
                                             Other ({{ number_format($uc_cr_other->count()) }})
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="ucSubTabContent">
                                    <!-- Instrument Sub-pane -->
                                    <div class="tab-pane fade show active" id="uc-ins-subpane" role="tabpanel" tabindex="0">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0 datatable-icode" id="table-uc-ins" style="width: 100%;">
                                                <thead class="bg-light text-primary border-bottom">
                                                    <tr>
                                                        <th class="ps-4">iCode</th>
                                                        <th>ชื่อรายการ</th>
                                                        <th>หมวดหมู่</th>
                                                        <th class="text-center">ADP Code</th>
                                                        <th class="text-end">ราคา HOSxP</th>
                                                        <th class="text-end">ราคา UCS (ประกาศ)</th>
                                                        <th class="text-center">ตรงประกาศ</th>
                                                        <th class="text-center">Flags</th>
                                                        <th class="text-center pe-4">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($uc_cr_instrument as $item)
                                                        @php
                                                            $insRule = $ins_rules[$item->nhso_adp_code] ?? null;
                                                            $ucsPrice = $insRule['prices']['UCS'] ?? 0;
                                                            $hosxpPrice = $hosxp_prices[$item->icode] ?? 0;
                                                            $isMatch = (abs($ucsPrice - $hosxpPrice) < 0.1);
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-4 fw-bold text-dark">{{ $item->icode }}</td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 300px;" title="{{ $item->name }}">
                                                                    {{ $item->name }}
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted small">
                                                                    {{ $insRule['category'] ?? '-' }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border">{{ $item->nhso_adp_code ?? '-' }}</span>
                                                            </td>
                                                            <td class="text-end">
                                                                @if($hosxpPrice > 0)
                                                                    <span class="fw-bold text-primary">{{ number_format($hosxpPrice, 2) }}</span>
                                                                    <span class="text-muted small"> บาท</span>
                                                                @else
                                                                    <span class="text-muted small">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end">
                                                                @if($ucsPrice > 0)
                                                                    <span class="fw-bold text-success">{{ number_format($ucsPrice, 2) }}</span>
                                                                    <span class="text-muted small"> บาท</span>
                                                                @else
                                                                    <span class="text-muted small">-</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if(($insRule['ins_ucs'] ?? '') === 'Y')
                                                                    @if($isMatch)
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                            <i class="bi bi-check-circle-fill me-1"></i>ตรง
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                                            <i class="bi bi-x-circle-fill me-1"></i>ไม่ตรง
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                                                        ไม่อยู่ในประกาศ
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    @if($item->uc_cr === 'Y') <span class="badge rounded-pill bg-primary" title="UC_CR">UC-CR</span> @endif
                                                                    @if($item->ppfs === 'Y') 
                                                                        @if(in_array($item->nhso_adp_code, $valid_ppfs_adps))
                                                                            <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                                        @else
                                                                            <span class="d-inline-flex align-items-center">
                                                                                <i class="bi bi-x-circle-fill text-danger me-1" title="ไม่มีรหัส ADP นี้ใน claims/ppfs_rules.php"></i>
                                                                                <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                                            </span>
                                                                        @endif
                                                                    @endif
                                                                    @if($item->herb32 === 'Y') <span class="badge rounded-pill bg-warning text-dark" title="Herb32">สมุนไพร</span> @endif
                                                                    @if($item->kidney === 'Y') <span class="badge rounded-pill bg-info text-white" title="Kidney">ฟอกไต HD</span> @endif
                                                                    @if($item->ems === 'Y') <span class="badge rounded-pill bg-danger" title="EMS">EMS</span> @endif
                                                                    @if($item->sss_hc === 'Y') <span class="badge rounded-pill text-white" style="background-color: #6366f1;" title="SSS_HC">SSS-HC</span> @endif
                                                                </div>
                                                            </td>
                                                            <td class="text-center pe-4">
                                                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                                    <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                                                        data-icode="{{ $item->icode }}"    
                                                                        data-name="{{ $item->name }}"
                                                                        data-nhso_adp_code="{{ $item->nhso_adp_code }}"
                                                                        data-uc_cr="{{ $item->uc_cr }}"
                                                                        data-ppfs="{{ $item->ppfs }}"
                                                                        data-herb32="{{ $item->herb32 }}" 
                                                                        data-kidney="{{ $item->kidney }}"
                                                                        data-ems="{{ $item->ems }}"                              
                                                                        data-sss_hc="{{ $item->sss_hc }}"                              
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editModal"
                                                                        title="แก้ไข">
                                                                        <i class="bi bi-pencil-square text-warning"></i>
                                                                    </button>
                                                                    <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_icode.destroy', $item) }}">
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

                                    <!-- Other Sub-pane -->
                                    <div class="tab-pane fade" id="uc-other-subpane" role="tabpanel" tabindex="0">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0 datatable-icode" id="table-uc-other" style="width: 100%;">
                                                <thead class="bg-light text-primary border-bottom">
                                                    <tr>
                                                        <th class="ps-4">iCode</th>
                                                        <th>ชื่อรายการ</th>
                                                        <th>หมวดหมู่</th>
                                                        <th class="text-center">ADP Code</th>
                                                        <th class="text-center">Flags</th>
                                                        <th class="text-center pe-4">จัดการ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($uc_cr_other as $item)
                                                        @php
                                                            $insRuleOther = $ins_rules[$item->nhso_adp_code] ?? null;
                                                            $ucsPriceOther = $insRuleOther['prices']['UCS'] ?? 0;
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-4 fw-bold text-dark">{{ $item->icode }}</td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 280px;" title="{{ $item->name }}">
                                                                    {{ $item->name }}
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted small">
                                                                    {{ $insRuleOther['category'] ?? '-' }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border">{{ $item->nhso_adp_code ?? '-' }}</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    @if($item->uc_cr === 'Y') <span class="badge rounded-pill bg-primary" title="UC_CR">UC-CR</span> @endif
                                                                    @if($item->ppfs === 'Y') 
                                                                        @if(in_array($item->nhso_adp_code, $valid_ppfs_adps))
                                                                            <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                                        @else
                                                                            <span class="d-inline-flex align-items-center">
                                                                                <i class="bi bi-x-circle-fill text-danger me-1" title="ไม่มีรหัส ADP นี้ใน claims/ppfs_rules.php"></i>
                                                                                <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                                            </span>
                                                                        @endif
                                                                    @endif
                                                                    @if($item->herb32 === 'Y') <span class="badge rounded-pill bg-warning text-dark" title="Herb32">สมุนไพร</span> @endif
                                                                    @if($item->kidney === 'Y') <span class="badge rounded-pill bg-info text-white" title="Kidney">ฟอกไต HD</span> @endif
                                                                    @if($item->ems === 'Y') <span class="badge rounded-pill bg-danger" title="EMS">EMS</span> @endif
                                                                    @if($item->sss_hc === 'Y') <span class="badge rounded-pill text-white" style="background-color: #6366f1;" title="SSS_HC">SSS-HC</span> @endif
                                                                </div>
                                                            </td>
                                                            <td class="text-center pe-4">
                                                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                                    <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                                                        data-icode="{{ $item->icode }}"    
                                                                        data-name="{{ $item->name }}"
                                                                        data-nhso_adp_code="{{ $item->nhso_adp_code }}"
                                                                        data-uc_cr="{{ $item->uc_cr }}"
                                                                        data-ppfs="{{ $item->ppfs }}"
                                                                        data-herb32="{{ $item->herb32 }}" 
                                                                        data-kidney="{{ $item->kidney }}"
                                                                        data-ems="{{ $item->ems }}"                              
                                                                        data-sss_hc="{{ $item->sss_hc }}"                              
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editModal"
                                                                        title="แก้ไข">
                                                                        <i class="bi bi-pencil-square text-warning"></i>
                                                                    </button>
                                                                    <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_icode.destroy', $item) }}">
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
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 datatable-icode" id="table-{{ $pane['id'] }}">
                                    <thead class="bg-light text-primary border-bottom">
                                        <tr>
                                            <th class="ps-4">iCode</th>
                                            <th>ชื่อรายการ</th>
                                            <th class="text-center">ADP Code</th>
                                            <th class="text-center">Flags</th>
                                            <th class="text-center pe-4">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pane['data'] as $item)
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark">{{ $item->icode }}</td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;" title="{{ $item->name }}">
                                                        {{ $item->name }}
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border">{{ $item->nhso_adp_code ?? '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        @if($item->uc_cr === 'Y') <span class="badge rounded-pill bg-primary" title="UC_CR">UC-CR</span> @endif
                                                        @if($item->ppfs === 'Y') 
                                                            @if(in_array($item->nhso_adp_code, $valid_ppfs_adps))
                                                                <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                            @else
                                                                <span class="d-inline-flex align-items-center">
                                                                    <i class="bi bi-x-circle-fill text-danger me-1" title="ไม่มีรหัส ADP นี้ใน claims/ppfs_rules.php"></i>
                                                                    <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span>
                                                                </span>
                                                            @endif
                                                        @endif
                                                        @if($item->herb32 === 'Y') <span class="badge rounded-pill bg-warning text-dark" title="Herb32">สมุนไพร</span> @endif
                                                        @if($item->kidney === 'Y') <span class="badge rounded-pill bg-info text-white" title="Kidney">ฟอกไต HD</span> @endif
                                                        @if($item->ems === 'Y') <span class="badge rounded-pill bg-danger" title="EMS">EMS</span> @endif
                                                        @if($item->sss_hc === 'Y') <span class="badge rounded-pill text-white" style="background-color: #6366f1;" title="SSS_HC">SSS-HC</span> @endif
                                                    </div>
                                                </td>
                                                <td class="text-center pe-4">
                                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                        <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                                            data-icode="{{ $item->icode }}"    
                                                            data-name="{{ $item->name }}"
                                                            data-nhso_adp_code="{{ $item->nhso_adp_code }}"
                                                            data-uc_cr="{{ $item->uc_cr }}"
                                                            data-ppfs="{{ $item->ppfs }}"
                                                            data-herb32="{{ $item->herb32 }}" 
                                                            data-kidney="{{ $item->kidney }}"
                                                            data-ems="{{ $item->ems }}"                              
                                                            data-sss_hc="{{ $item->sss_hc }}"                              
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editModal"
                                                            title="แก้ไข">
                                                            <i class="bi bi-pencil-square text-warning"></i>
                                                        </button>
                                                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_icode.destroy', $item) }}">
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
                        @endif
                    </div>
                @endforeach

                <div class="tab-pane fade" id="missing-pane" role="tabpanel" tabindex="0">
                    <div class="p-4">
                        <div class="alert alert-info border-0 rounded-3 mb-4 small p-3">
                            <i class="bi bi-info-circle-fill me-2"></i> <strong>คำแนะนำ:</strong> ตารางด้านล่างนี้แสดงรายการทั้งหมดที่มีอยู่ในคู่มือการเบิกจ่าย (PPFS หรือ อุปกรณ์เทียม Instrument) เปรียบเทียบกับรายการใน HOSxP ว่ามีรหัสเบิกจ่าย (ADP Code) จับคู่อยู่หรือไม่ และมีราคาตั้งใน HOSxP เท่าใด
                        </div>

                        <!-- Sub Tabs for Missing items -->
                        <ul class="nav nav-tabs mb-3 border-bottom" id="missingSubTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-bold px-4 py-2 border-0 border-bottom border-3 text-success" id="missing-ppfs-subtab" data-bs-toggle="tab" data-bs-target="#missing-ppfs-subpane" type="button" role="tab" style="border-bottom-color: #10b981 !important; border-radius: 0;">
                                     PPFS ({{ count($ppfs_details) }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-bold px-4 py-2 border-0 border-bottom border-3 text-primary" id="missing-ins-subtab" data-bs-toggle="tab" data-bs-target="#missing-ins-subpane" type="button" role="tab" style="border-bottom-color: #3b82f6 !important; border-radius: 0;">
                                     Instrument ({{ count($ins_details) }})
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-2" id="missingSubTabContent">
                            <!-- PPFS Sub-pane -->
                            <div class="tab-pane fade show active" id="missing-ppfs-subpane" role="tabpanel" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 datatable-icode" id="table-missing-ppfs" style="width: 100%;">
                                        <thead class="bg-light text-success border-bottom">
                                            <tr>
                                                <th class="ps-4">ADP Code</th>
                                                <th>ชื่อกิจกรรมบริการ (ตามคู่มือ)</th>
                                                <th class="text-center">อัตราจ่ายชดเชย</th>
                                                <th class="text-center">พบใน HOSxP (icode)</th>
                                                <th class="text-end pe-4">ราคาใน HOSxP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($ppfs_details as $code => $detail)
                                                <tr>
                                                    <td class="ps-4 fw-bold text-success">{{ $code }}</td>
                                                    <td>
                                                        <div class="fw-bold text-dark mb-1">{{ $detail['name'] }}</div>
                                                        @if(isset($detail['sex']) || isset($detail['age']))
                                                            <div class="small text-muted">เพศ: {{ ($detail['sex'] ?? null) === 'F' ? 'หญิง' : (($detail['sex'] ?? null) === 'M' ? 'ชาย' : 'ไม่จำกัด') }} | อายุ: {{ $detail['age']['min'] ?? '0' }} - {{ $detail['age']['max'] ?? 'ไม่จำกัด' }} ปี</div>
                                                        @else
                                                            <div class="small text-muted">หมวดหมู่: {{ $detail['category'] ?? 'บริการสร้างเสริมสุขภาพและป้องกันโรค' }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center fw-bold text-success">
                                                        {{ isset($detail['amount']) ? number_format($detail['amount'], 2) . ' บาท' : 'เหมาจ่าย' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if($detail['hosxp_icode'] !== 'ไม่พบ')
                                                            <span class="badge bg-success-subtle text-success border border-success">{{ $detail['hosxp_icode'] }}</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger border border-danger">ไม่พบ</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-4 fw-bold text-primary">
                                                        {{ $detail['hosxp_price'] }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Instrument Sub-pane -->
                            <div class="tab-pane fade" id="missing-ins-subpane" role="tabpanel" tabindex="0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 datatable-icode" id="table-missing-ins" style="width: 100%;">
                                        <thead class="bg-light text-primary border-bottom">
                                            <tr>
                                                <th class="ps-4">ADP Code</th>
                                                <th>ชื่อกิจกรรมบริการ (ตามคู่มือ)</th>
                                                <th class="text-end">UCS</th>
                                                <th class="text-end">OFC</th>
                                                <th class="text-end">SSS</th>
                                                <th class="text-end">LGO</th>
                                                <th class="text-end">FS</th>
                                                <th class="text-end">UCEP</th>
                                                <th class="text-center">พบใน HOSxP (icode)</th>
                                                <th class="text-end pe-4">ราคาใน HOSxP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($ins_details as $code => $detail)
                                                @php
                                                    $ucsP = $detail['prices']['UCS'] ?? 0;
                                                    $ofcP = $detail['prices']['OFC'] ?? 0;
                                                    $sssP = $detail['prices']['SSS'] ?? 0;
                                                    $lgoP = $detail['prices']['LGO'] ?? 0;
                                                    $fsP = $detail['prices']['FS'] ?? 0;
                                                    $ucepP = $detail['prices']['UCEP'] ?? 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 fw-bold text-primary">{{ $code }}</td>
                                                    <td>
                                                        <div class="fw-bold text-dark mb-1">{{ $detail['name'] }}</div>
                                                        <div class="small text-muted">หมวดหมู่: {{ $detail['category'] ?? 'อุปกรณ์และอวัยวะเทียม' }}</div>
                                                    </td>
                                                    <td class="text-end fw-bold text-success">{{ $ucsP > 0 ? number_format($ucsP, 2) : '-' }}</td>
                                                    <td class="text-end text-dark">{{ $ofcP > 0 ? number_format($ofcP, 2) : '-' }}</td>
                                                    <td class="text-end text-dark">{{ $sssP > 0 ? number_format($sssP, 2) : '-' }}</td>
                                                    <td class="text-end text-dark">{{ $lgoP > 0 ? number_format($lgoP, 2) : '-' }}</td>
                                                    <td class="text-end text-dark">{{ $fsP > 0 ? number_format($fsP, 2) : '-' }}</td>
                                                    <td class="text-end text-dark">{{ $ucepP > 0 ? number_format($ucepP, 2) : '-' }}</td>
                                                    <td class="text-center">
                                                        @if($detail['hosxp_icode'] !== 'ไม่พบ')
                                                            <span class="badge bg-success-subtle text-success border border-success">{{ $detail['hosxp_icode'] }}</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger border border-danger">ไม่พบ</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-4 fw-bold text-primary">
                                                        {{ $detail['hosxp_price'] }}
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
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.lookup_icode.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle-fill me-2"></i> Add Lookup iCode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ค้นหารหัส/ชื่อรายการ/ADP (จาก HOSxP)</label>
                        <select class="form-select" id="searchIcode" name="icode" required></select>
                        <div class="form-text small text-muted">พิมพ์เพื่อค้นหาไอโค้ดหรือชื่อรายการจากฐานข้อมูล HOSxP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รายการบริการ (Auto-fill)</label>
                        <input class="form-control bg-secondary-subtle" id="createName" name="name" type="text" placeholder="จะแสดงอัตโนมัติเมื่อเลือก iCode" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">NHSO ADP Code (Auto-fill)</label>
                        <input class="form-control bg-secondary-subtle" id="createAdp" name="nhso_adp_code" type="text" placeholder="จะแสดงอัตโนมัติเมื่อเลือก iCode" readonly>
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tags-fill me-1"></i> เชื่อมโยงระบบ (Flags)</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="uc_cr" value="Y" id="cr_cr_c">
                                <label class="form-check-label" for="cr_cr_c">UC-CR</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ppfs" value="Y" id="ppfs_c">
                                <label class="form-check-label" for="ppfs_c">PPFS</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="herb32" value="Y" id="herb32_c">
                                <label class="form-check-label" for="herb32_c">สมุนไพร</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="kidney" value="Y" id="kidney_c">
                                <label class="form-check-label" for="kidney_c">ฟอกไต HD</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ems" value="Y" id="ems_c">
                                <label class="form-check-label" for="ems_c">EMS</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="sss_hc" value="Y" id="sss_hc_c">
                                <label class="form-check-label" for="sss_hc_c">SSS-HC</label>
                            </div>
                        </div>
                    </div>
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
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="editForm" class="modal-content border-0 shadow-lg">
                @csrf @method('PUT')
                <div class="modal-header bg-primary text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit Lookup iCode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">iCode (Locked)</label>
                        <input class="form-control bg-secondary-subtle" id="icode" name="icode" type="text" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">รายการบริการ</label>
                        <input class="form-control bg-light" id="editName" name="name" type="text" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">NHSO ADP Code</label>
                        <input class="form-control bg-light" id="editAdp" name="nhso_adp_code" type="text">
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tags-fill me-1"></i> ปรับปรุงสถานะ (Flags)</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="uc_cr" id="edituc_cr" value="Y">
                                <label class="form-check-label" for="edituc_cr">UC-CR</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ppfs" id="editppfs" value="Y">
                                <label class="form-check-label" for="editppfs">PPFS</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="herb32" id="editherb32" value="Y">
                                <label class="form-check-label" for="editherb32">สมุนไพร</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="kidney" id="editkidney" value="Y">
                                <label class="form-check-label" for="editkidney">ฟอกไต HD</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ems" id="editems" value="Y">
                                <label class="form-check-label" for="editems">EMS</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="sss_hc" id="editsss_hc" value="Y">
                                <label class="form-check-label" for="editsss_hc">SSS-HC</label>
                            </div>
                        </div>
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
    .bg-light-subtle { background-color: #f8fafc; transition: background 0.2s; }
    .bg-light-subtle:hover { background-color: #f1f5f9; }
    
    .modern-tabs .nav-link {
        color: #64748b;
        background: #f1f5f9;
        border: none;
        padding: 10px 20px;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    .modern-tabs .nav-link:hover {
        background: #e2e8f0;
    }
    .modern-tabs .nav-link.active {
        background: #fff !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: #3b82f6;
    }
    /* Tab Specific Active Colors */
    .modern-tabs #all-tab.active { border-color: #334155; color: #1e293b !important; }
    .modern-tabs #uc-tab.active { border-color: #3b82f6; color: #3b82f6 !important; }
    .modern-tabs #ppfs-tab.active { border-color: #10b981; color: #10b981 !important; }
    .modern-tabs #herb-tab.active { border-color: #f59e0b; color: #f59e0b !important; }
    .modern-tabs #kidney-tab.active { border-color: #06b6d4; color: #06b6d4 !important; }
    .modern-tabs #ems-tab.active { border-color: #ef4444; color: #ef4444 !important; }
    .modern-tabs #ssshc-tab.active { border-color: #6366f1; color: #6366f1 !important; }
    .modern-tabs #missing-tab.active { border-color: #ef4444; color: #ef4444 !important; }

    /* Select2 Bootstrap 5 Fixes */
    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0.5rem;
        padding: 0.375rem 0.75rem;
        height: calc(3.5rem + 2px);
        background-color: #f8fafc;
    }
</style>

@endsection

@push('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize all DataTables
        $('.datatable-icode').each(function() {
            $(this).DataTable({
                pageLength: 25,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
                order: [[0, 'asc']],
                dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });
        });

        // Initialize Select2 with AJAX for Icode Search
        $('#searchIcode').select2({
            theme: 'bootstrap-5',
            placeholder: 'พิมพ์ icode หรือชื่อรายการเพื่อค้นหา...',
            minimumInputLength: 2,
            dropdownParent: $('#createModal'),
            ajax: {
                url: "{{ route('admin.lookup_icode.search_items') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            let displayText = item.icode + ' | ' + item.name;
                            if (item.nhso_adp_code) {
                                displayText += ' | [ADP: ' + item.nhso_adp_code + ']';
                            }
                            return {
                                id: item.icode,
                                text: displayText,
                                item: item
                            }
                        })
                    };
                },
                cache: true
            }
        });

        // Handle Select event
        $('#searchIcode').on('select2:select', function (e) {
            const item = e.params.data.item;
            $('#createName').val(item.name);
            $('#createAdp').val(item.nhso_adp_code);
        });

        // Handle tab switching for DataTable responsiveness
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        // Set ข้อมูลใน Edit Modal
        $('.btn-edit').on('click', function () {
            const data = $(this).data();
            $('#icode').val(data.icode);
            $('#editName').val(data.name);
            $('#editAdp').val(data.nhso_adp_code);
            $('#edituc_cr').prop('checked', data.uc_cr === 'Y');
            $('#editppfs').prop('checked', data.ppfs === 'Y');
            $('#editherb32').prop('checked', data.herb32 === 'Y');
            $('#editkidney').prop('checked', data.kidney === 'Y');
            $('#editems').prop('checked', data.ems === 'Y');
            $('#editsss_hc').prop('checked', data.sss_hc === 'Y');
            $('#editForm').attr('action', "{{ url('admin/lookup_icode') }}/" + data.icode);
        });

        // SweetAlert ยืนยันนำเข้าข้อมูลพร้อมแสดง Progress Bar %
        $('.import-form').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            const actionUrl = $(form).attr('action');
            const token = $(form).find('input[name="_token"]').val();
            const importName = $(form).find('button[type="submit"]').text().trim();

            Swal.fire({
                title: 'ยืนยันการนำเข้าข้อมูล?',
                text: `คุณต้องการนำเข้าข้อมูล ${importName} ใช่หรือไม่?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                borderRadius: '15px'
            }).then((result) => {
                if (result.isConfirmed) {
                    let timerInterval;
                    Swal.fire({
                        title: `กำลังนำเข้าข้อมูล ${importName}...`,
                        html: `
                            <div class="progress" style="height: 25px;">
                                <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        borderRadius: '15px',
                        didOpen: () => {
                            Swal.showLoading();
                            let width = 0;
                            const progressBar = $('#import-progress-bar');
                            timerInterval = setInterval(() => {
                                if (width < 95) {
                                    width += Math.floor(Math.random() * 10) + 1;
                                    if (width > 95) width = 95;
                                    progressBar.css('width', width + '%');
                                    progressBar.attr('aria-valuenow', width);
                                    progressBar.text(width + '%');
                                }
                            }, 150);
                        }
                    });

                    // AJAX Request
                    $.ajax({
                        url: actionUrl,
                        type: 'POST',
                        data: {
                            _token: token
                        },
                        success: function (response) {
                            clearInterval(timerInterval);
                            const progressBar = $('#import-progress-bar');
                            progressBar.css('width', '100%');
                            progressBar.attr('aria-valuenow', 100);
                            progressBar.text('100%');

                            setTimeout(() => {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: response.message || 'นำเข้าข้อมูลเรียบร้อยแล้ว',
                                    borderRadius: '15px',
                                    confirmButtonColor: '#0a4d2c'
                                }).then(() => {
                                    window.location.reload();
                                });
                            }, 500);
                        },
                        error: function (xhr) {
                            clearInterval(timerInterval);
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด!',
                                text: xhr.responseJSON?.message || 'ไม่สามารถนำเข้าข้อมูลได้',
                                borderRadius: '15px'
                            });
                        }
                    });
                }
            });
        });

        // SweetAlert ยืนยันลบ
        $('.btn-delete').on('click', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบ iCode?',
                text: "การลบอาจส่งผลต่อการเชื่อมโยงข้อมูลในระบบอื่น!",
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
</script>
@endpush