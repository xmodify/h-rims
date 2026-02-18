@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-success me-2"></i>
                ข้อมูล Statement สวัสดิการข้าราชการ CIPN
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามสถานะ</div>
            <div class="mt-2">
                <a href="{{ url('import/stm_ofc_cipn') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
                </a>
            </div>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="m-0">
            @csrf
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">วันที่:</span>
                <input type="hidden" name="start_date" id="start_date" value="{{ $start_date }}">
                <input type="text" id="start_date_picker" class="form-control form-control-sm datepicker_th" style="width: 120px; border-radius: 8px;" value="{{ $start_date }}" readonly>
                <span class="text-muted small">ถึง:</span>
                <input type="hidden" name="end_date" id="end_date" value="{{ $end_date }}">
                <input type="text" id="end_date_picker" class="form-control form-control-sm datepicker_th" style="width: 120px; border-radius: 8px;" value="{{ $end_date }}" readonly>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">ค้นหา</button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card accent-9 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="stm_ofc_cipn_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th>AN</th>
                            <th>ชื่อ - สกุล</th>
                            <th>จำหน่าย</th>
                            <th>PT / DRG</th>
                            <th>AdjRW</th>
                            <th>ค่ารักษา¹</th>
                            <th>ค่าห้อง</th>
                            <th>ค่ารักษา²</th>
                            <th>พึงรับ</th>
                            <th>RepNo.</th>
                            <th>เลขที่ใบเสร็จ</th>
                            <th>วันที่ออกใบเสร็จ</th>
                            <th>ผู้ออกใบเสร็จ</th>
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
      $('.datepicker_th').datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1050
      });

      // Set initial values
      var start_date_val = "{{ $start_date }}";
      var end_date_val = "{{ $end_date }}";
      if(start_date_val) {
          $('#start_date_picker').datepicker('setDate', new Date(start_date_val));
      }
      if(end_date_val) {
          $('#end_date_picker').datepicker('setDate', new Date(end_date_val));
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

      $('#stm_ofc_cipn_list').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('stm_ofc_cipndetail') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        columns: [
            { data: 'an', name: 'an', className: 'text-center' },
            { data: 'namepat', name: 'namepat' },
            { 
                data: 'datedsc', 
                name: 'datedsc', 
                className: 'text-center',
                render: function (data, type, row) {
                    if (!data) return '';
                    var date = new Date(data);
                    return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'numeric', year: '2-digit' });
                }
            },
            { 
                data: null, 
                name: 'ptype', 
                render: function (data, type, row) {
                    return '<div class="small">' + (row.ptype ? row.ptype : '') + '</div>' + 
                           '<div class="small text-muted">' + (row.drg ? row.drg : '') + '</div>';
                }
            },
            { data: 'adjrw', name: 'adjrw', className: 'text-end small' },
            { data: 'amreimb', name: 'amreimb', className: 'text-end text-muted' },
            { data: 'amlim', name: 'amlim', className: 'text-end text-muted' },
            { data: 'pamreim', name: 'pamreim', className: 'text-end text-muted' },
            { data: 'gtotal', name: 'gtotal', className: 'text-end fw-bold text-success' },
            { 
                data: 'rid', 
                name: 'rid', 
                className: 'text-center small',
                render: function(data) { return 'REP: ' + (data ? data : ''); }
            },
            { data: 'receive_no', name: 'receive_no', className: 'text-center text-primary fw-bold small' },
            { data: 'receipt_date', name: 'receipt_date', className: 'text-center small' },
            { data: 'receipt_by', name: 'receipt_by', className: 'text-center small text-muted' }
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
                    window.location.href = "{{ route('stm_ofc_cipndetail') }}?export=excel&start_date=" + start + "&end_date=" + end;
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
