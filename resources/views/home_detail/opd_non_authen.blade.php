@extends('layouts.app')

@section('content')

<div class="container-fluid py-4">  
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-person-x-fill mr-2"></i> 
          รายชื่อผู้มารับบริการ (ไม่ขอ AuthenCode)
          <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
            วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}
          </small>
        </h5>
        
        <form method="POST" class="d-flex gap-2 align-items-center mb-0">
            @csrf            
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-event"></i></span>
                <input type="hidden" id="start_date" name="start_date" value="{{ $start_date }}">
                <input type="text" id="start_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">
                
                <span class="input-group-text bg-white">ถึง</span>
                
                <input type="hidden" id="end_date" name="end_date" value="{{ $end_date }}">
                <input type="text" id="end_date_picker" class="form-control datepicker_th text-center" readonly style="width: 120px; cursor: pointer;">
                <button type="submit" class="btn btn-primary px-3 shadow-sm">{{ __('ค้นหา') }}</button>
            </div>
        </form>
      </div>
    </div>

    <div class="card-body">
      <div class="table-responsive">            
        <table id="list" class="table table-hover table-bordered align-middle" width="100%">
          <thead class="bg-light">
            <tr>
              <th class="text-center">ลำดับ</th>
              <th class="text-center">รับบริการ</th>
              <th class="text-center">Queue</th>
              <th class="text-center">ชื่อ-สกุล | CID | HN</th>
              <th class="text-center">การติดต่อ</th>
              <th class="text-center">สิทธิ | Hmain</th>
              <th class="text-center">ค่ารักษาทั้งหมด</th>
              <th class="text-center">ชำระเอง</th>
              <th class="text-center">ที่เบิกได้</th>
              <th class="text-center">จุดบริการ</th>            
            </tr>     
          </thead> 
          <tbody> 
            @foreach($sql as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td> 
              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{ $row->vsttime }}</small>
              </td>
              <td align="center">
                <span class="badge bg-secondary shadow-sm">{{ $row->oqueue }}</span>
              </td>
              <td align="left">
                <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                <small class="text-muted">CID: {{ $row->cid }} | HN: {{ $row->hn }}</small>
              </td>
              <td align="left">
                <small class="d-block"><i class="bi bi-phone mr-1"></i>{{ $row->mobile_phone_number ?: '-' }}</small>
                <small class="text-muted"><i class="bi bi-telephone mr-1"></i>{{ $row->hometel ?: '-' }}</small>
              </td>
              <td align="left">
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</small>
                <span class="badge bg-secondary shadow-sm">H: {{ $row->hospmain ?: 'N/A' }}</span>
              </td> 
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
              <td align="right">
                <span class="badge outline-primary text-primary shadow-sm" style="border: 1px solid #0d6efd;">{{ $row->department }}</span>
              </td>
 
            </tr>
            @endforeach                 
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
      // Initialize Datepicker Thai
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

      $('#list').DataTable({
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
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel mr-1"></i> Excel',
              className: 'btn btn-success btn-sm',
              title: 'Non_Authen_Reports_{{ $start_date }}_{{ $end_date }}'
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหา...",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: '<i class="bi bi-chevron-left"></i>',
              next: '<i class="bi bi-chevron-right"></i>'
            }
        }
      });
    });
  </script>
@endpush