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
                <i class="bi bi-hospital text-primary me-2"></i> Lookup Hospcode
            </h4>
            <p class="text-muted small mb-0">จัดการรหัสสถานพยาบาลและสิทธิการรักษาหลัก</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Add Hospcode
            </button>
        </div>
    </div>

    <!-- Hospcode Content -->
    <div class="dash-card border-0 shadow-sm overflow-visible">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-hospcode">
                    <thead class="bg-light text-primary border-bottom">
                        <tr>
                            <th class="ps-4">Hospcode</th>
                            <th>ชื่อสถานพยาบาล</th>
                            <th class="text-center">Hmain UCS</th>
                            <th class="text-center">Hmain SSS</th>
                            <th class="text-center">ในจังหวัด</th>
                            <th class="text-center pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $item->hospcode }}</td>
                                <td>{{ $item->hospcode_name }}</td>
                                <td class="text-center">
                                    @if($item->hmain_ucs === 'Y') 
                                        <span class="badge rounded-pill bg-primary px-3">UCS</span> 
                                    @else 
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->hmain_sss === 'Y') 
                                        <span class="badge rounded-pill bg-success px-3">SSS</span> 
                                    @else 
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->in_province === 'Y') 
                                        <span class="badge rounded-pill bg-info px-3">ในจังหวัด</span> 
                                    @else 
                                        <span class="text-muted small">นอกจังหวัด</span>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                            data-hospcode="{{ $item->hospcode }}"    
                                            data-hospcode_name="{{ $item->hospcode_name }}"
                                            data-hmain_ucs="{{ $item->hmain_ucs }}"
                                            data-hmain_sss="{{ $item->hmain_sss }}"
                                            data-in_province="{{ $item->in_province }}"                          
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            title="แก้ไข">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_hospcode.destroy', $item) }}">
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

    <!-- Modal Create -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.lookup_hospcode.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle-fill me-2"></i> Add Lookup Hospcode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกสถานพยาบาล (จาก HOSxP)</label>
                        <select class="form-select" id="searchHospcode" name="hospcode" required></select>
                        <div class="form-text small text-muted">พิมพ์เพื่อค้นหารหัสหรือชื่อสถานพยาบาลจาก HOSxP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อสถานพยาบาล (Auto-fill)</label>
                        <input class="form-control bg-secondary-subtle" id="createHospcodeName" name="hospcode_name" type="text" placeholder="จะแสดงอัตโนมัติเมื่อเลือก Hospcode" readonly required>
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tags-fill me-1"></i> เชื่อมโยงระบบ (Flags)</label>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="hmain_ucs" value="Y" id="hmain_ucs_c">
                                <label class="form-check-label" for="hmain_ucs_c">UCS</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="hmain_sss" value="Y" id="hmain_sss_c">
                                <label class="form-check-label" for="hmain_sss_c">SSS</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="in_province" value="Y" id="in_province_c">
                                <label class="form-check-label" for="in_province_c">ในจังหวัด</label>
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
                        <i class="bi bi-pencil-square me-2"></i> Edit Lookup Hospcode
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hospcode (Locked)</label>
                        <input class="form-control bg-secondary-subtle" id="edithospcode" name="hospcode" type="text" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อสถานพยาบาล</label>
                        <input class="form-control bg-light" id="edithospcode_name" name="hospcode_name" type="text" required>
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tags-fill me-1"></i> ปรับปรุงสิทธิการรักษา (Flags)</label>
                    <div class="row g-3">
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="hmain_ucs" id="edithmain_ucs" value="Y">
                                <label class="form-check-label" for="edithmain_ucs">UCS</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="hmain_sss" id="edithmain_sss" value="Y">
                                <label class="form-check-label" for="edithmain_sss">SSS</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="in_province" id="editin_province" value="Y">
                                <label class="form-check-label" for="editin_province">ในจังหวัด</label>
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
        // Initialize DataTable
        $('#table-hospcode').DataTable({
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
            order: [[0, 'asc']],
            dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // Initialize Select2 with AJAX for Hospcode Search
        $('#searchHospcode').select2({
            theme: 'bootstrap-5',
            placeholder: 'พิมพ์รหัสหรือชื่อสถานพยาบาล...',
            minimumInputLength: 2,
            dropdownParent: $('#createModal'),
            ajax: {
                url: "{{ route('admin.lookup_hospcode.search_hospcodes') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.hospcode,
                                text: item.hospcode + ' | ' + item.hospcode_name,
                                item: item
                            }
                        })
                    };
                },
                cache: true
            }
        });

        // Handle Select event
        $('#searchHospcode').on('select2:select', function (e) {
            const item = e.params.data.item;
            $('#createHospcodeName').val(item.hospcode_name);
        });

        // Set ข้อมูลใน Edit Modal
        $('.btn-edit').on('click', function () {
            const data = $(this).data();
            $('#edithospcode').val(data.hospcode);
            $('#edithospcode_name').val(data.hospcode_name);
            $('#edithmain_ucs').prop('checked', data.hmain_ucs === 'Y');
            $('#edithmain_sss').prop('checked', data.hmain_sss === 'Y');
            $('#editin_province').prop('checked', data.in_province === 'Y');
            $('#editForm').attr('action', "{{ url('admin/lookup_hospcode') }}/" + data.hospcode);
        });

        // SweetAlert ยืนยันลบ
        $('.btn-delete').on('click', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบ Hospcode?',
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
