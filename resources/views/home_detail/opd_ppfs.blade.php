@extends('layouts.app')

@section('content')

<div class="container-fluid py-4"> 
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white pt-4 pb-0 border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title text-primary mb-0">
          <i class="bi bi-shield-check mr-2"></i> 
          รายงานผู้มารับบริการ UC-OP (PPFS)
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
                <button type="submit" onclick="showLoading()" class="btn btn-primary px-3 shadow-sm">{{ __('ค้นหา') }}</button>
            </div>
        </form>
      </div>
    </div>

    <div class="card-body pt-0">
      <div class="table-responsive">            
        <table id="t_search" class="table table-hover table-bordered align-middle" width="100%">
          <thead class="bg-light">
            <tr>
                <th class="text-center">ลำดับ</th>
                <th class="text-center" width="5%">ปิดสิทธิ</th>  
                <th class="text-center">Authen</th>

                <th class="text-center">วันที่รับบริการ/เวลา</th>
                <th class="text-center">Queue</th>     
                <th class="text-center">ชื่อ-สกุล | CID | HN</th>    
                <th class="text-center">การติดต่อ</th>
                <th class="text-center">สิทธิ | Hmain</th>
                <th class="text-center">PDX</th>
                <th class="text-center">ค่ารักษาทั้งหมด</th>
                <th class="text-center">ชำระเอง</th>
                <th class="text-center">ที่เบิกได้</th>
                <th class="text-center">รายการเรียกเก็บ</th>
            </tr>
          </thead> 
          <tbody> 
            <?php 
              $sum_income = 0; 
              $sum_rcpt_money = 0;
              $sum_debtor = 0;
              $sum_claim_price = 0;
            ?>
            @foreach($search as $index => $row) 
            <tr>
              <td align="center" class="text-muted">{{ $index + 1 }}</td>
              <td align="center">                  
                @php
                    $status = $row->claim_status;
                @endphp

                @if($status === 'success')
                    <button onclick="alertAlreadyClosed('สปสช.')" class="btn btn-outline-success btn-sm rounded-circle" title="ปิดสิทธิเรียบร้อยแล้ว">
                        <i class="bi bi-check-circle-fill"></i>
                    </button>
                @elseif(in_array($status, ['pulled', 'failed']))

                    <button onclick="pushNhsoData('{{ $row->cid }}', '{{ $row->vstdate }}')" class="btn btn-outline-warning btn-sm rounded-circle" title="รอยืนยันปิดสิทธิ (Push)">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                    </button>
                @else
                    <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm rounded-circle" title="ดึงข้อมูลจาก สปสช. (Pull)">
                        <i class="bi bi-cloud-download-fill"></i>
                    </button>
                @endif
              </td>  
              <td align="center">
                @if($row->auth_code == 'Y')
                  <span class="badge bg-success shadow-sm">Y</span>
                @else
                  <span class="badge bg-danger shadow-sm">N</span>
                @endif
              </td>

              <td align="left">
                <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                <small class="text-muted">{{$row->vsttime}}</small>
              </td>
              <td align="center"><span class="badge bg-secondary shadow-sm">{{ $row->oqueue }}</span></td>   
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
              <td align="center"><span class="badge bg-light text-dark border shadow-sm">{{ $row->pdx ?: '-' }}</span></td>
              <td align="right" class="fw-bold">{{ number_format($row->income, 2) }}</td>
              <td align="right" class="text-danger">{{ number_format($row->rcpt_money, 2) }}</td>
              <td align="right" class="text-primary fw-bold">{{ number_format($row->debtor, 2) }}</td>
              <td align="left">
                <div style="font-size: 0.8rem; line-height: 1.2;">{{$row->claim_list}}</div>
              </td>
            </tr>
            <?php 
              $sum_income += $row->income;
              $sum_rcpt_money += $row->rcpt_money;
              $sum_debtor += $row->debtor;
              $sum_claim_price += $row->claim_price;
            ?>
            @endforeach                 
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer bg-light border-0 py-3">
        <div class="row text-center g-3">
          <div class="col-md-4 border-end">
            <small class="text-muted d-block">ค่ารักษาจริง</small>
            <span class="h6 mb-0">{{ number_format($sum_income,2)}}</span>
          </div>
          <div class="col-md-4 border-end">
            <small class="text-muted d-block">ชำระเอง</small>
            <span class="h6 mb-0 text-danger">{{ number_format($sum_rcpt_money,2)}}</span>
          </div>
          <div class="col-md-4">
            <small class="text-muted d-block">ที่เบิกได้</small>
            <span class="h6 mb-0 text-primary">{{ number_format($sum_debtor,2)}}</span>
          </div>
          </div>
        </div>
    </div>
  </div> 
</div>      

<script>
function alertAlreadyClosed(source) {
    Swal.fire({
        icon: 'info',
        title: 'ปิดสิทธิเรียบร้อยแล้ว',
        text: 'รายการนี้ปิดสิทธิโดย ' + source + ' เรียบร้อยแล้ว ไม่จำเป็นต้องส่งซ้ำอีกครั้ง',
        confirmButtonText: 'รับทราบ',
        confirmButtonColor: '#0d6efd'
    });
}

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
            if (data.found) {
                Swal.fire({
                    icon: 'success',
                    title: 'พบข้อมูลปิดสิทธิ',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่พบการปิดสิทธิจากระบบอื่น',
                    text: 'ยังไม่มีการปิดสิทธิสำหรับรายการนี้ใน สปสช. ต้องการปิดสิทธิด้วยระบบ RiMS หรือไม่?',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'ปิดสิทธิเลย',
                    cancelButtonText: 'ยกเลิก'
                }).then(result => {
                    if (result.isConfirmed) {
                        pushNhsoData(cid, vstdate);
                    }
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message || 'ไม่สามารถเชื่อมต่อกับระบบได้',
            });
        });
}

function pushNhsoData(cid, vstdate) {
    Swal.fire({
        title: 'ยืนยันการส่งข้อมูล?',
        text: "ระบบจะดึงข้อมูลจาก HOSxP และส่งไปปิดสิทธิที่ สปสช.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ตกลง, ส่งข้อมูล!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังดำเนินการ...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            $.ajax({
                url: "{{ route('api.nhso.push_indiv') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    cid: cid,
                    vstdate: vstdate
                },
                success: function(response) {
                    if (response.status == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: 'ปิดสิทธิเรียบร้อยแล้ว',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สำเร็จ',
                            text: response.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล'
                        });
                    }
                },
                error: function(xhr) {
                    let msg = 'ไม่สามารถเชื่อมต่อกับระบบได้';
                    if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: msg
                    });
                }
            });
        }
    });
}


function showLoading() {
    Swal.fire({
        title: 'กำลังค้นหา...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
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

      $('#t_search').DataTable({
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
              title: 'UC_PPFS_Reports_{{ $start_date }}_{{ $end_date }}'
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

