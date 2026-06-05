@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Import Form Card -->
    <div class="row justify-content-center mt-3 mb-4">
        <div class="col-md-8">
            <div class="card dash-card accent-13">
                <div class="card-body">
                    <form id="importForm" onsubmit="showLoadingAlert()" action="{{ route('import.sss_equipdev_aipn_save') }}" method="POST" enctype="multipart/form-data" class="m-0">
                        @csrf
                        <div class="text-center mb-3">
                            <h6 class="fw-bold text-dark">
                                <i class="bi bi-file-earmark-excel-fill me-2 text-success"></i> นำเข้าไฟล์ Excel SSS Equipdev AIPN (.xls, .xlsx)
                            </h6>
                            <p class="text-muted small">เลือกไฟล์ Excel สำหรับนำเข้าตาราง sss_equipdev_aipn (ระบบจะลบข้อมูลเก่าและแทนที่ด้วยข้อมูลใหม่ทั้งหมด)</p>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input class="form-control" id="formFile" type="file" name="file" accept=".xls,.xlsx" required style="border-radius: 10px 0 0 10px;">
                            <button class="btn btn-teal text-white px-4" type="submit" style="border-radius: 0 10px 10px 0; background-color: #0d9488;">
                                <i class="bi bi-cloud-upload me-2"></i> นำเข้าข้อมูล
                            </button>
                        </div>

                        @if ($message = Session::get('success'))
                            <div class="alert alert-success border-0 shadow-sm py-2 mb-0">
                                <i class="bi bi-check-circle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                        @if ($message = Session::get('error'))
                            <div class="alert alert-danger border-0 shadow-sm py-2 mb-0">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>{{ $message }}</strong>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="page-header-box mb-3">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-database-fill-gear text-teal me-2" style="color: #0d9488;"></i>
                ตารางอ้างอิงอุปกรณ์/อวัยวะเทียม SSS Equipdev AIPN
            </h5>
            <div class="text-muted small mt-1">จำนวนข้อมูลทั้งหมดในระบบ: <strong class="text-teal" style="color: #0d9488;">{{ number_format($total_records) }}</strong> รายการ</div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-0" id="equipdevTabs" role="tablist" style="border-bottom: 2px solid #0d9488;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="active-tab" data-bs-toggle="tab" data-bs-target="#tab-active" type="button" role="tab">
                <i class="bi bi-calendar-check me-1 text-success"></i>
                Active
                <span class="badge ms-1 text-white" style="background-color: #0d9488;" id="badge-active">{{ number_format($active_records) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="all-tab" data-bs-toggle="tab" data-bs-target="#tab-all" type="button" role="tab">
                <i class="bi bi-calendar-x me-1 text-danger"></i>
                Expire
                <span class="badge ms-1 bg-danger" id="badge-all">{{ number_format($expired_records) }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Tab: Active (dateexp > today) -->
        <div class="tab-pane fade show active" id="tab-active" role="tabpanel">
            <div class="card dash-card border-top-0" style="border-radius: 0 0 10px 10px;">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="equipdevTableActive" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">Bill Group</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-center">Rate 2</th>
                                    <th class="text-center">Description</th>
                                    <th class="text-center">Date Rev</th>
                                    <th class="text-center">Date Eff</th>
                                    <th class="text-center">Date Exp</th>
                                    <th class="text-center">Last Upd</th>
                                    <th class="text-center">Dt Cond</th>
                                    <th class="text-center">Note</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: All -->
        <div class="tab-pane fade" id="tab-all" role="tabpanel">
            <div class="card dash-card border-top-0" style="border-radius: 0 0 10px 10px;">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="equipdevTableAll" class="table table-modern w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">Bill Group</th>
                                    <th class="text-center">Code</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-center">Rate 2</th>
                                    <th class="text-center">Description</th>
                                    <th class="text-center">Date Rev</th>
                                    <th class="text-center">Date Eff</th>
                                    <th class="text-center">Date Exp</th>
                                    <th class="text-center">Last Upd</th>
                                    <th class="text-center">Dt Cond</th>
                                    <th class="text-center">Note</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert: Success -->
@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'นำเข้าสำเร็จ',
                text: "{!! session('success') !!}",
                confirmButtonText: 'ปิด',
                confirmButtonColor: '#0d9488',
                customClass: {
                    confirmButton: 'btn btn-primary btn-sm px-4'
                },
                allowOutsideClick: false
            });
        });
    </script>
@endif
<!-- SweetAlert: Error -->
@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: @json(session('error')),
                confirmButtonText: 'ปิด',
                confirmButtonColor: '#0d9488',
                customClass: {
                    confirmButton: 'btn btn-primary btn-sm px-4'
                }
            });
        });
    </script>
@endif

@endsection

@push('scripts')
    <script>
        function showLoadingAlert() {
            const fileInput = document.getElementById('formFile');
            if (fileInput.files.length > 0) {
                Swal.fire({
                    title: 'กำลังนำเข้าข้อมูล...',
                    text: 'กรุณารอสักครู่ ระบบกำลังประมวลผลไฟล์ Excel',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
            }
        }

        function getColumns() {
            return [
                { data: 'billgroup', name: 'billgroup', className: 'text-center' },
                { data: 'code', name: 'code', className: 'fw-bold text-dark text-center' },
                { data: 'unit', name: 'unit', className: 'text-center' },
                { 
                    data: 'rate', name: 'rate', className: 'text-end',
                    render: function(data) {
                        return data !== null ? parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-';
                    }
                },
                { 
                    data: 'rate2', name: 'rate2', className: 'text-end',
                    render: function(data) {
                        return data !== null ? parseFloat(data).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-';
                    }
                },
                { data: 'desc', name: 'desc' },
                { data: 'daterev', name: 'daterev', className: 'text-center' },
                { data: 'dateeff', name: 'dateeff', className: 'text-center' },
                { 
                    data: 'dateexp', name: 'dateexp', className: 'text-center',
                    render: function(data) {
                        if (!data) return '-';
                        const today = new Date().toISOString().slice(0, 10);
                        const isExpired = data < today;
                        return isExpired
                            ? `<span class="badge bg-danger">${data}</span>`
                            : `<span class="badge text-white" style="background-color:#0d9488;">${data}</span>`;
                    }
                },
                { data: 'lastupd', name: 'lastupd', className: 'text-center' },
                { data: 'dtcond', name: 'dtcond', className: 'text-center' },
                { data: 'note', name: 'note' }
            ];
        }

        function getDtLanguage() {
            return {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
            };
        }

        $(document).ready(function () {
            var baseUrl = "{{ route('import.sss_equipdev_aipn') }}";

            // Table: Active (dateexp > today)
            var tableActive = $('#equipdevTableActive').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: baseUrl,
                    type: "GET",
                    data: function(d) { d.tab = 'active'; }
                },
                columns: getColumns(),
                pageLength: 10,
                language: getDtLanguage()
            });

            // Table: All
            var tableAll = null;

            // Lazy-init "All" tab table on first click
            $('#all-tab').on('shown.bs.tab', function () {
                if (!tableAll) {
                    tableAll = $('#equipdevTableAll').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: baseUrl,
                            type: "GET",
                            data: function(d) { d.tab = 'all'; }
                        },
                        columns: getColumns(),
                        pageLength: 25,
                        language: getDtLanguage()
                    });
                } else {
                    tableAll.draw();
                }
            });
        });
    </script>
@endpush

