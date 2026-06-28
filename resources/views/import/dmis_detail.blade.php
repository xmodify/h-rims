@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>
                รายละเอียดธุรกรรมรายบุคคล Seamless For DMIS
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามโครงการและประเภทบริการ</div>
            <div class="mt-2">
                <a href="{{ route('import.dmis') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        
        <form method="POST" action="{{ route('import.dmis.detail') }}" class="m-0" id="filterForm">
            @csrf
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small">โครงการ:</span>
                <select class="form-select form-select-sm" name="claim_type" id="claim_type" style="width: 250px; border-radius: 8px;">
                    <option value="">-- ทั้งหมด --</option>
                    @foreach($claim_types as $type)
                        <option value="{{ $type }}" {{ $claim_type == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>

                <span class="text-muted small">วันที่:</span>
                <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                <input type="text" id="start_date_picker" class="form-control form-control-sm datepicker_th text-center" style="width: 120px; border-radius: 8px; cursor: pointer;" readonly>
                
                <span class="text-muted small">ถึง:</span>
                <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                <input type="text" id="end_date_picker" class="form-control form-control-sm datepicker_th text-center" style="width: 120px; border-radius: 8px; cursor: pointer;" readonly>
                
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- DMIS Data Table Card -->
    <div class="card dash-card accent-9 mb-4">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge me-2 text-primary"></i> รายการธุรกรรมรายบุคคล</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="t_dmis_detail" class="table table-modern w-100" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th class="text-center">วันที่รับบริการ</th>
                            <th class="text-center">ประเภทกิจกรรม</th>
                            <th class="text-center">เลขธุรกรรม (Trans ID)</th>
                            <th class="text-center">HN</th>
                            <th class="text-center">เลขบัตรประชาชน</th>
                            <th class="text-center">ชื่อ-สกุลผู้ป่วย</th>
                            <th class="text-center">ยอดขอเบิก</th>
                            <th class="text-center">ร้อยละจ่าย</th>
                            <th class="text-center">ชดเชยจริง</th>
                            <th class="text-center">Deny Code</th>
                            <th class="text-center">คำอธิบายปฏิเสธ</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables will populate this --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Initialize Thai Datepicker
        $('.datepicker_th').datepicker({
            format: 'd M yyyy',
            todayBtn: "linked",
            todayHighlight: true,
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            zIndexOffset: 1050
        });

        // Set Initial Date Values
        var start_date_val = "{{ $start_date }}";
        var end_date_val = "{{ $end_date }}";
        if (start_date_val) {
            $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
        }
        if (end_date_val) {
            $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
        }

        // Sync Datepicker to Hidden Inputs
        $('.datepicker_th').on('changeDate', function (e) {
            var date = e.date;
            var targetId = $(this).attr('id').replace('_picker', '');
            var hiddenInput = $('#' + targetId);
            if (date) {
                var day = ("0" + date.getDate()).slice(-2);
                var month = ("0" + (date.getMonth() + 1)).slice(-2);
                var year = date.getFullYear();
                hiddenInput.val(year + "-" + month + "-" + day);
            } else {
                hiddenInput.val('');
            }
        });

        // Initialize DataTable
        $('#t_dmis_detail').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('import.dmis.detail') }}",
                data: function (d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.claim_type = $('#claim_type').val();
                }
            },
            columns: [
                { data: 'vstdate', name: 'vstdate', className: 'text-center' },
                { data: 'claim_type_name', name: 'claim_type_name' },
                { data: 'trans_id', name: 'trans_id', className: 'text-center' },
                { data: 'hn', name: 'hn', className: 'text-center fw-bold' },
                { data: 'cid', name: 'cid', className: 'text-center' },
                { data: 'ptname', name: 'ptname' },
                { data: 'claim_price', name: 'claim_price', className: 'text-end text-muted' },
                { data: 'pay_percent', name: 'pay_percent', className: 'text-center' },
                { data: 'receive_total', name: 'receive_total', className: 'text-end text-success fw-bold' },
                { data: 'deny_code', name: 'deny_code', className: 'text-center' },
                { data: 'deny_warning', name: 'deny_warning' }
            ],
            dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>>t<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            buttons: [
                {
                    text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                    className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm',
                    action: function (e, dt, node, config) {
                        var start = $('#start_date').val();
                        var end = $('#end_date').val();
                        var claimType = $('#claim_type').val();
                        window.location.href = "{{ route('import.dmis.detail') }}?export=excel&start_date=" + start + "&end_date=" + end + "&claim_type=" + encodeURIComponent(claimType);
                    }
                }
            ],
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                paginate: {
                    previous: "ก่อนหน้า",
                    next: "ถัดไป"
                },
                processing: '<div class="text-primary mt-2"><span class="spinner-border spinner-border-sm" role="status"></span> กำลังดึงข้อมูล...</div>'
            }
        });
    });
</script>
@endpush
