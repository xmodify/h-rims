@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header -->
    <div class="page-header-box mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="bi bi-calendar-event text-primary me-2"></i> ปีงบประมาณ
            </h4>
            <p class="text-muted small mb-0">จัดการช่วงเวลาปฏิบัติงานและรายงานแยกตามปีพุทธศักราช</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success px-4 shadow-sm hover-scale rounded-pill" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="bi bi-plus-circle-fill me-1"></i> เพิ่มปีงบประมาณ
            </button>
        </div>
    </div>

    <!-- Budget Year Content -->
    <div class="dash-card border-0 shadow-sm overflow-visible">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="table-budget">
                    <thead class="bg-light text-primary border-bottom">
                        <tr>
                            <th class="ps-4">ปี</th>
                            <th>ชื่อปีงบประมาณ</th>
                            <th class="text-center">วันที่เริ่ม</th>
                            <th class="text-center">วันที่สิ้นสุด</th> 
                            <th class="text-center pe-4">จัดการ</th>                
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budget_year as $item)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $item->LEAVE_YEAR_ID }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 rounded-pill">
                                        {{ $item->LEAVE_YEAR_NAME }}
                                    </span>
                                </td>
                                <td class="text-center text-muted">
                                    <i class="bi bi-calendar2-check-fill text-success me-1 small"></i>
                                    {{ \Carbon\Carbon::parse($item->DATE_BEGIN)->format('d/m/Y') }}
                                </td>
                                <td class="text-center text-muted">
                                    <i class="bi bi-calendar2-x-fill text-danger me-1 small"></i>
                                    {{ \Carbon\Carbon::parse($item->DATE_END)->format('d/m/Y') }}
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button type="button" class="btn btn-white btn-sm px-3 btn-edit border-end"
                                            data-leave-year-id="{{ $item->LEAVE_YEAR_ID }}"
                                            data-leave-year-name="{{ $item->LEAVE_YEAR_NAME }}"
                                            data-date-begin="{{ $item->DATE_BEGIN }}"
                                            data-date-end="{{ $item->DATE_END }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            title="แก้ไข">
                                            <i class="bi bi-pencil-square text-warning"></i>
                                        </button>
                                        <form class="d-inline delete-form" method="POST" action="{{ route('admin.budget_year.destroy', $item) }}">
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
            <form method="POST" action="{{ route('admin.budget_year.store') }}" class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle-fill me-2"></i> เพิ่มปีงบประมาณ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold font-sm">ปีงบประมาณ (พ.ศ.)</label>
                        <input class="form-control bg-light" name="LEAVE_YEAR_ID" type="number" placeholder="เช่น 2567" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold font-sm">ชื่อปีงบประมาณ</label>
                        <input class="form-control bg-light" name="LEAVE_YEAR_NAME" type="text" placeholder="เช่น ปีงบประมาณ 2567" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold font-sm">วันที่เริ่ม</label>
                            <input class="form-control bg-light" name="DATE_BEGIN" type="date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold font-sm">วันที่สิ้นสุด</label>
                            <input class="form-control bg-light" name="DATE_END" type="date" required>
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
                        <i class="bi bi-pencil-square me-2"></i> แก้ไขปีงบประมาณ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold font-sm">ปีงบประมาณ (Locked)</label>
                        <input class="form-control bg-secondary-subtle" id="eLEAVE_YEAR_ID" name="LEAVE_YEAR_ID" type="text" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold font-sm">ชื่อปีงบประมาณ</label>
                        <input class="form-control bg-light" id="eLEAVE_YEAR_NAME" name="LEAVE_YEAR_NAME" type="text" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold font-sm">วันที่เริ่ม</label>
                            <input class="form-control bg-light" id="eDATE_BEGIN" name="DATE_BEGIN" type="date" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold font-sm">วันที่สิ้นสุด</label>
                            <input class="form-control bg-light" id="eDATE_END" name="DATE_END" type="date" required>
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
    .font-sm { font-size: 0.85rem; }
    .bg-primary-subtle { background-color: #eef2ff; }
</style>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Initialize DataTable
        $('#table-budget').DataTable({
            pageLength: 25,
            language: { url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json' },
            order: [[0, 'desc']],
            dom: "<'row px-4 pt-4'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row px-4 pb-4'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });

        // Set ข้อมูลใน Edit Modal
        $('.btn-edit').on('click', function () {
            const data = $(this).data();
            $('#eLEAVE_YEAR_ID').val(data.leaveYearId);
            $('#eLEAVE_YEAR_NAME').val(data.leaveYearName);
            $('#eDATE_BEGIN').val(data.dateBegin);
            $('#eDATE_END').val(data.dateEnd);
            $('#editForm').attr('action', "{{ url('admin/budget_year') }}/" + data.leaveYearId);
        });

        // SweetAlert ยืนยันลบ
        $('.btn-delete').on('click', function () {
            const form = $(this).closest('form');
            Swal.fire({
                title: 'ยืนยันการลบปีงบประมาณ?',
                text: "การลบอาจส่งผลต่อการเรียกดูรายงานย้อนหลังในบางส่วน!",
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
