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
                <form method="POST" action="{{ route('admin.insert_lookup_uc_cr') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 rounded-start-pill border-end-0">
                        <i class="bi bi-cloud-download me-1"></i> นำเข้า UC_CR
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.insert_lookup_ppfs') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 border-start-0 border-end-0">
                         นำเข้า PPFS
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.insert_lookup_herb32') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary px-3 rounded-end-pill border-start-0">
                         นำเข้า Herb32
                    </button>
                </form>
            </div>
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Add iCode
            </button>
        </div>
    </div>

    <!-- Category Tabs -->
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
                    ];
                @endphp

                @foreach($panes as $index => $pane)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $pane['id'] }}-pane" role="tabpanel" tabindex="0">
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
                                                    @if($item->ppfs === 'Y') <span class="badge rounded-pill bg-success" title="PPFS">PPFS</span> @endif
                                                    @if($item->herb32 === 'Y') <span class="badge rounded-pill bg-warning text-dark" title="Herb32">สมุนไพร</span> @endif
                                                    @if($item->kidney === 'Y') <span class="badge rounded-pill bg-info text-white" title="Kidney">ฟอกไต HD</span> @endif
                                                    @if($item->ems === 'Y') <span class="badge rounded-pill bg-danger" title="EMS">EMS</span> @endif
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
                @endforeach
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
                        <label class="form-label fw-bold">ค้นหารหัส/ชื่อรายการ (จาก HOSxP)</label>
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
                            return {
                                id: item.icode,
                                text: item.icode + ' | ' + item.name,
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
            $('#editForm').attr('action', "{{ url('admin/lookup_icode') }}/" + data.icode);
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