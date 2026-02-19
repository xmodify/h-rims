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
                <i class="bi bi-door-open text-primary me-2"></i> Lookup Ward
            </h4>
            <p class="text-muted small mb-0">จัดการข้อมูลหอผู้ป่วยและประเภทการรับบริการ</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.insert_lookup_ward') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary px-4 shadow-sm hover-scale rounded-pill">
                    <i class="bi bi-cloud-download me-1"></i> นำเข้า Ward
                </button>
            </form>
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-1"></i> Add Ward
            </button>
        </div>
    </div>

    <!-- Ward Content -->
    <div class="dash-card border-0 shadow-sm overflow-visible">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-ward">
                    <thead class="bg-light text-primary border-bottom">
                        <tr>
                            <th class="ps-4">Ward</th>
                            <th>หอผู้ป่วย</th>
                            <th class="text-center">จำนวนเตียง</th>
                            <th class="text-center">ประเภทหอผู้ป่วย</th>
                            <th class="text-center pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $item->ward }}</td>
                                <td>{{ $item->ward_name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 rounded-pill">{{ $item->bed_qty }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        @if($item->ward_normal === 'Y') <span class="badge rounded-pill bg-primary">ทั่วไป</span> @endif
                                        @if($item->ward_m === 'Y') <span class="badge rounded-pill bg-info">ชาย</span> @endif
                                        @if($item->ward_f === 'Y') <span class="badge rounded-pill bg-rose">หญิง</span> @endif
                                        @if($item->ward_vip === 'Y') <span class="badge rounded-pill bg-warning text-dark">VIP</span> @endif
                                        @if($item->ward_lr === 'Y') <span class="badge rounded-pill bg-danger">ห้องคลอด</span> @endif
                                        @if($item->ward_homeward === 'Y') <span class="badge rounded-pill bg-success">Homeward</span> @endif
                                    </div>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-white btn-sm px-3 btn-edit border-end" 
                                            data-ward="{{ $item->ward }}"    
                                            data-ward_name="{{ $item->ward_name }}"
                                            data-bed_qty="{{ $item->bed_qty }}"
                                            data-ward_normal="{{ $item->ward_normal }}"
                                            data-ward_m="{{ $item->ward_m }}"
                                            data-ward_f="{{ $item->ward_f }}"
                                            data-ward_vip="{{ $item->ward_vip }}"
                                            data-ward_lr="{{ $item->ward_lr }}"   
                                            data-ward_homeward="{{ $item->ward_homeward }}"                            
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            title="แก้ไข">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.lookup_ward.destroy', $item) }}">
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
            <form method="POST" action="{{ route('admin.lookup_ward.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle-fill me-2"></i> Add Lookup Ward
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกหอผู้ป่วย (จาก HOSxP)</label>
                        <select class="form-select" id="searchWard" name="ward" required></select>
                        <div class="form-text small text-muted">พิมพ์เพื่อค้นหารหัสหรือชื่อหอผู้ป่วยจาก HOSxP</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อหอผู้ป่วย (Auto-fill)</label>
                        <input class="form-control bg-secondary-subtle" id="createWardName" name="ward_name" type="text" placeholder="จะแสดงอัตโนมัติเมื่อเลือก Ward" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวนเตียงจริง</label>
                        <input class="form-control bg-light" name="bed_qty" type="number" placeholder="0" required>
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tag-fill me-1"></i> ประเภทหอผู้ป่วย (Flags)</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_normal" value="Y" id="ward_normal_c">
                                <label class="form-check-label" for="ward_normal_c">ทั่วไป</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_m" value="Y" id="ward_m_c">
                                <label class="form-check-label" for="ward_m_c">ชาย</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_f" value="Y" id="ward_f_c">
                                <label class="form-check-label" for="ward_f_c">หญิง</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_vip" value="Y" id="ward_vip_c">
                                <label class="form-check-label" for="ward_vip_c">VIP</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_lr" value="Y" id="ward_lr_c">
                                <label class="form-check-label" for="ward_lr_c">ห้องคลอด</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_homeward" value="Y" id="ward_homeward_c">
                                <label class="form-check-label" for="ward_homeward_c">Homeward</label>
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
                        <i class="bi bi-pencil-square me-2"></i> Edit Lookup Ward
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ward (Locked)</label>
                        <input class="form-control bg-secondary-subtle" id="editward" name="ward" type="text" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อหอผู้ป่วย</label>
                        <input class="form-control bg-light" id="editward_name" name="ward_name" type="text" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">จำนวนเตียงจริง</label>
                        <input class="form-control bg-light" id="editbed_qty" name="bed_qty" type="number" required>
                    </div>
                    <hr class="my-4 opacity-10">
                    <label class="form-label fw-bold mb-3 d-block text-primary"><i class="bi bi-tag-fill me-1"></i> ปรับปรุงประเภท (Flags)</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_normal" id="editward_normal" value="Y">
                                <label class="form-check-label" for="editward_normal">ทั่วไป</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_m" id="editward_m" value="Y">
                                <label class="form-check-label" for="editward_m">ชาย</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_f" id="editward_f" value="Y">
                                <label class="form-check-label" for="editward_f">หญิง</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_vip" id="editward_vip" value="Y">
                                <label class="form-check-label" for="editward_vip">VIP</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_lr" id="editward_lr" value="Y">
                                <label class="form-check-label" for="editward_lr">ห้องคลอด</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch p-2 border rounded bg-light-subtle">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="ward_homeward" id="editward_homeward" value="Y">
                                <label class="form-check-label" for="editward_homeward">Homeward</label>
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
    .bg-rose { background-color: #f472b6; color: white; }
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
        $('#table-ward').DataTable({
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
            order: [[0, 'asc']],
            dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

         // Initialize Select2 with AJAX for Ward Search
         $('#searchWard').select2({
            theme: 'bootstrap-5',
            placeholder: 'พิมพ์รหัสหรือชื่อหอผู้ป่วย...',
            minimumInputLength: 1,
            dropdownParent: $('#createModal'),
            ajax: {
                url: "{{ route('admin.lookup_ward.search_wards') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.ward,
                                text: item.ward + ' | ' + item.ward_name,
                                item: item
                            }
                        })
                    };
                },
                cache: true
            }
        });

        // Handle Select event
        $('#searchWard').on('select2:select', function (e) {
            const item = e.params.data.item;
            $('#createWardName').val(item.ward_name);
        });

        // Set ข้อมูลใน Edit Modal
        $('.btn-edit').on('click', function () {
            const data = $(this).data();
            $('#editward').val(data.ward);
            $('#editward_name').val(data.ward_name);
            $('#editbed_qty').val(data.bed_qty);
            $('#editward_normal').prop('checked', data.ward_normal === 'Y');
            $('#editward_m').prop('checked', data.ward_m === 'Y');
            $('#editward_f').prop('checked', data.ward_f === 'Y');
            $('#editward_vip').prop('checked', data.ward_vip === 'Y');
            $('#editward_lr').prop('checked', data.ward_lr === 'Y');
            $('#editward_homeward').prop('checked', data.ward_homeward === 'Y');
            $('#editForm').attr('action', "{{ url('admin/lookup_ward') }}/" + data.ward);
        });

        // SweetAlert ยืนยันลบ
        $('.btn-delete').on('click', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบ Ward?',
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
