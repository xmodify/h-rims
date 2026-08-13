@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 bg-white shadow-sm" style="border-radius: 14px;">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>
                รายละเอียดการตรวจสอบเบื้องต้น (REP) สิทธิ์ข้าราชการ OFC [OPD]
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายและปฏิเสธการจ่าย (Deny) สำหรับผู้ป่วยนอก</div>
            <div class="mt-2">
                <a href="{{ url('import/rep_ofc') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-nowrap">วันที่:</span>
                <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                <input type="text" id="start_date_picker" class="form-control form-control-sm datepicker_th" style="width: 120px; border-radius: 8px;" value="{{ $start_date }}" readonly>
                <span class="text-muted small text-nowrap">ถึง:</span>
                <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                <input type="text" id="end_date_picker" class="form-control form-control-sm datepicker_th" style="width: 120px; border-radius: 8px;" value="{{ $end_date }}" readonly>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- OPD Data Table Card -->
    <div class="card dash-card border-0 shadow-sm mb-4" style="border-radius: 14px; border-top: 4px solid #0d6efd !important;">
        <div class="card-header bg-transparent border-0 pb-0 pt-4 px-4">
            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-badge me-2 text-primary"></i> ผู้ป่วยนอก OP</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="rep_ofc_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">ประเภท</th>
                            <th class="text-center">เลขที่ REP</th> 
                            <th class="text-center">HN</th>
                            <th class="text-center">AN</th>
                            <th class="text-center">ชื่อ-สกุล</th>
                            <th class="text-center">วันเข้ารักษา</th>
                            <th class="text-center">วันจำหน่าย</th>
                            <th class="text-center">โครงการ</th>  
                            <th class="text-center">เรียกเก็บ</th>                                         
                            <th class="text-center">ชดเชย สปสช.</th> 
                            <th class="text-center">ชดเชย ต้นสังกัด</th> 
                            <th class="text-center">Error Code</th>
                            <th class="text-center">Deny HC</th> 
                            <th class="text-center">Deny AE</th>
                            <th class="text-center">Deny INST</th>
                            <th class="text-center">Deny IP</th>
                            <th class="text-center">Deny DMIS</th>
                            <th class="text-center">Remark</th>
                            <th class="text-center">Audit Results</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables --}}
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
      $('.datepicker_th').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1050
      });

      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
      }

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

      // OPD DataTable
      $('#rep_ofc_list').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('import/rep_ofc_detail') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.type = 'opd';
            }
        },
        columns: [
            { data: 'dep', name: 'rep_type', className: 'text-center' },
            { data: 'repno', name: 'repno', className: 'text-center' },
            { data: 'hn', name: 'hn', className: 'text-center fw-bold' },
            { data: 'an', name: 'an', className: 'text-center' },
            { data: 'pt_name', name: 'pt_name' },
            { data: 'datetimeadm', name: 'datetimeadm', className: 'text-center small' },
            { data: 'datetimedch', name: 'datetimedch', className: 'text-center small text-muted' },
            { data: 'proj', name: 'proj', className: 'text-center small' },
            { data: 'charge_total', name: 'charge_total', className: 'text-end text-muted' },
            { data: 'net_compensate_nhso', name: 'net_compensate_nhso', className: 'text-end text-primary fw-bold' },
            { data: 'net_compensate_employer', name: 'net_compensate_employer', className: 'text-end' },
            { data: 'error_code', name: 'error_code', className: 'text-center' },
            { data: 'deny_hc', name: 'deny_hc', className: 'text-center' },
            { data: 'deny_ae', name: 'deny_ae', className: 'text-center' },
            { data: 'deny_inst', name: 'deny_inst', className: 'text-center' },
            { data: 'deny_ip', name: 'deny_ip', className: 'text-center' },
            { data: 'deny_dmis', name: 'deny_dmis', className: 'text-center' },
            { data: 'remark', name: 'remark', className: 'small' },
            { data: 'audit_results', name: 'audit_results', className: 'small' }
        ],
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
                text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                action: function ( e, dt, node, config ) {
                    var start = $('#start_date').val();
                    var end = $('#end_date').val();
                    window.location.href = "{{ url('import/rep_ofc_detail') }}?export=excel&type=opd&start_date=" + start + "&end_date=" + end;
                }
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: { previous: "ก่อนหน้า", next: "ถัดไป" },
            processing: "กำลังโหลดข้อมูล..."
        }
      });
    });
</script>  
@endpush
