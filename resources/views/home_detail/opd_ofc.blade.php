@extends('layouts.app')

@section('content')

<div class="container-fluid py-4"> 
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-file-earmark-person mr-2"></i> 
          รายงานสิทธิข้าราชการ (OFC)
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
                <th class="text-center" width="6%">ดึงปิดสิทธิ</th>
                <th class="text-center text-nowrap">Authen</th>  
                <th class="text-center text-nowrap">ปิดสิทธิ</th>
                <th class="text-center">PPFS</th>
                <th class="text-center">EDC</th>
                <th class="text-center">วันที่รับบริการ/เวลา</th> 
                <th class="text-center text-nowrap">ชื่อ-สกุล | CID | HN</th>    
                <th class="text-center">การติดต่อ</th>
                <th class="text-center">สิทธิ | Hmain</th>
                <th class="text-center">PDX</th>
                <th class="text-center text-nowrap">ค่ารักษาทั้งหมด</th>
                <th class="text-center text-nowrap">ชำระเอง</th>
                <th class="text-center text-nowrap">ที่เบิกได้</th>
            </tr>
          </thead> 
          <tbody> 
            @foreach($sql as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td>
              <td align="center">                                 
                  <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm">
                      <i class="bi bi-cloud-download"></i>
                  </button>              
              </td> 
              <td align="center">
                @if($row->auth_code == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td>               
              <td align="center">
                @if($row->endpoint == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td> 
              <td align="center">
                @if($row->ppfs == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td> 
              <td align="center">
                @if($row->edc)
                  <span class="badge bg-info text-dark shadow-sm">{{$row->edc}}</span>
                @endif
              </td>                
              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{ $row->vsttime }}</small>
              </td>                
              <td align="left">
                <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                <small class="text-muted">CID: {{ $row->cid }} | HN: {{ $row->hn }}</small>
              </td> 
              <td align="left">
                <div class="mt-1"><small class="text-muted"><i class="bi bi-phone"></i> {{ $row->mobile_phone_number ?: '-' }}</small></div>
              </td>
              <td align="left">
                <small class="d-block text-truncate" style="max-width: 150px;" title="{{ $row->pttype }}">{{ $row->pttype }}</small>
                <span class="badge bg-secondary shadow-sm">H: {{ $row->hospmain ?: 'N/A' }}</span>
              </td>
              <td align="center"><span class="badge bg-secondary shadow-sm">{{$row->pdx}}</span></td>
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
            
            </tr>
            @endforeach                 
          </tbody>
        </table>     
      </div>          
    </div> 
  </div>    
</div>      

<script>
  function pullNhsoData(vstdate, cid) {
      Swal.fire({
          title: 'กำลังดึงข้อมูล...',
          text: 'กรุณารอสักครู่',
          allowOutsideClick: false,
          didOpen: () => {
              Swal.showLoading()
          }
      });

      fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": "{{ csrf_token() }}",
              "Accept": "application/json"
          },
          body: JSON.stringify({
              vstdate: vstdate,
              cid: cid
          })
      })
          .then(async response => {
              const data = await response.json();
              if (!response.ok) {
                  throw new Error(data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
              }
              return data;
          })
          .then(data => {
              Swal.fire({
                  icon: 'success',
                  title: 'ดึงข้อมูลสำเร็จ',
                  text: data.message || 'ข้อมูลถูกบันทึกเรียบร้อยแล้ว',
                  timer: 2000,
                  showConfirmButton: false
              }).then(() => {
                  location.reload();
              });
          })
          .catch(error => {
              Swal.fire({
                  icon: 'error',
                  title: 'เกิดข้อผิดพลาด',
                  text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
              });
          });
  }
</script>

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
              title: 'OFC_Reports_{{ $start_date }}_{{ $end_date }}'
            }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "ค้นหาข้อมูล...",
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