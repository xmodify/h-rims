@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-success me-2"></i>
                รายละเอียด Statement เบิกจ่ายตรง อปท.LGO [ฟอกไต HD]
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามสถานะ</div>
            <div class="mt-2">
                <a href="{{ url('import/stm_lgo_kidney') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
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
                <table id="stm_lgo_kidney_list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">Dep</th>
                            <th class="text-center">Filename</th> 
                            <th class="text-center">REP</th> 
                            <th class="text-center">HN</th>
                            <th class="text-center">CID</th>
                            <th class="text-center">ชื่อ-สกุล</th>
                            <th class="text-center">วันเข้ารักษา</th>   
                            <th class="text-center">ชดเชยค่ารักษา</th>                                                           
                            <th class="text-center">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stm_lgo_kidney_list as $row)
                        <tr>
                            <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->dep }}</span></td>
                            <td class="small fw-bold text-dark">{{ $row->stm_filename }}</td>
                            <td class="text-centersmall">REP: {{ $row->repno }}</td>
                            <td class="text-center fw-bold">{{ $row->hn }}</td>
                            <td class="text-center small text-muted">{{ $row->cid }}</td>
                            <td>{{ $row->pt_name }}</td>
                            <td class="text-center">{{ DateThai($row->datetimeadm) }}</td>   
                            <td class="text-end text-success fw-bold">{{ number_format($row->compensate_kidney,2) }}</td>
                            <td>{{ $row->note }}</td> 
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

      $('#stm_lgo_kidney_list').DataTable({
        dom: '<"row mb-3"' +
                '<"col-md-6"l>' + // Show รายการ
                '<"col-md-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
              '>' +
              'rt' +
              '<"row mt-3"' +
                '<"col-md-6"i>' + // Info
                '<"col-md-6"p>' + // Pagination
              '>',
        buttons: [
            {
              extend: 'excelHtml5',
              text: 'Excel',
              className: 'btn btn-success',
              title: 'Statement เบิกจ่ายตรง อปท.LGO [ฟอกไต HD] รายละเอียด'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: "ก่อนหน้า",
              next: "ถัดไป"
            }
        }
      });
    });
  </script> 
@endpush

