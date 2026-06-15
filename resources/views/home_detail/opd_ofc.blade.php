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
                    <button type="button" onclick="pullAllNhsoData()" class="btn btn-outline-primary px-3 shadow-sm">
                        <i class="bi bi-cloud-download-fill me-1"></i> ดึงปิดสิทธิทั้งหมด
                    </button>
                    <button type="button" class="btn btn-outline-success px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importEdcModal">
                        <i class="bi bi-file-earmark-arrow-up-fill me-1"></i> นำเข้าเลข EDC
                    </button>
            </div>
        </form>
      </div>
    </div>

    <div class="card-body">
      @php
          $sql_all = $sql;
          $sql_match = [];
          $sql_no_hosxp = [];
          $sql_no_ktb = [];
          $sql_mismatch = [];
          $sql_no_info = [];

          foreach ($sql as $row) {
              $edc_hosxp_list = array_filter(array_map('trim', explode(',', $row->edc)));
              $edc_ktb_list = array_filter(array_map('trim', explode(',', $row->edc_ktb)));

              $has_hosxp = !empty($edc_hosxp_list);
              $has_ktb = !empty($edc_ktb_list);
              $is_match = $has_hosxp && $has_ktb && count(array_intersect($edc_hosxp_list, $edc_ktb_list)) > 0;

              if ($is_match) {
                  $sql_match[] = $row;
              } elseif ($has_ktb && !$has_hosxp) {
                  $sql_no_hosxp[] = $row;
              } elseif ($has_hosxp && !$has_ktb) {
                  $sql_no_ktb[] = $row;
              } elseif ($has_hosxp && $has_ktb && !$is_match) {
                  $sql_mismatch[] = $row;
              } else {
                  $sql_no_info[] = $row;
              }
          }

          $tabs = [
              ['id' => 'all', 'title' => 'ทั้งหมด', 'badge_class' => 'bg-secondary', 'data' => $sql_all, 'active' => true],
              ['id' => 'match', 'title' => 'จับคู่ถูกต้อง (Match)', 'badge_class' => 'bg-success', 'data' => $sql_match, 'active' => false],
              ['id' => 'no-hosxp', 'title' => 'ไม่พบคีย์ใน HOSxP', 'badge_class' => 'bg-danger', 'data' => $sql_no_hosxp, 'active' => false],
              ['id' => 'no-ktb', 'title' => 'ไม่พบข้อมูลใน KTB', 'badge_class' => 'bg-warning text-dark', 'data' => $sql_no_ktb, 'active' => false],
              ['id' => 'mismatch', 'title' => 'เลข EDC ไม่ตรงกัน', 'badge_class' => 'bg-primary', 'data' => $sql_mismatch, 'active' => false],
              ['id' => 'no-info', 'title' => 'ไม่มีข้อมูล EDC ทั้งคู่', 'badge_class' => 'bg-light text-dark', 'data' => $sql_no_info, 'active' => false],
          ];
      @endphp

      <ul class="nav nav-tabs mb-3" id="edcTab" role="tablist">
          @foreach($tabs as $tab)
          <li class="nav-item" role="presentation">
              <button class="nav-link {{ $tab['active'] ? 'active' : '' }} {{ $tab['id'] !== 'all' && $tab['id'] !== 'no-info' ? 'fw-bold' : '' }}" 
                      id="{{ $tab['id'] }}-tab" 
                      data-bs-toggle="tab" 
                      data-bs-target="#{{ $tab['id'] }}-pane" 
                      type="button" 
                      role="tab" 
                      aria-controls="{{ $tab['id'] }}-pane" 
                      aria-selected="{{ $tab['active'] ? 'true' : 'false' }}">
                  {{ $tab['title'] }} <span class="badge {{ $tab['badge_class'] }}">{{ count($tab['data']) }}</span>
              </button>
          </li>
          @endforeach
      </ul>

      <div class="tab-content" id="edcTabContent">
          @foreach($tabs as $tab)
          <div class="tab-pane fade {{ $tab['active'] ? 'show active' : '' }}" id="{{ $tab['id'] }}-pane" role="tabpanel" aria-labelledby="{{ $tab['id'] }}-tab">
            <div class="table-responsive mt-3">            
              <table class="table table-hover table-bordered align-middle datatable-list" width="100%">
                 <thead class="bg-light">
                  <tr>
                      <th class="text-center">ลำดับ</th>
                      <th class="text-center" width="6%">ปิดสิทธิ</th>
                      <th class="text-center text-nowrap">Authen</th>
                      <th class="text-center">PPFS</th>
                      <th class="text-center">EDC</th>
                      <th class="text-center">EDC (KTB)</th>
                      <th class="text-center">วันที่รับบริการ/เวลา</th> 
                      <th class="text-center text-nowrap">ชื่อ-สกุล | CID | HN</th>    
                      <th class="text-center">การติดต่อ</th>
                      <th class="text-center">สิทธิ | Hmain</th>
                      <th class="text-center">PDX</th>
                      <th class="text-center text-nowrap">ค่ารักษาทั้งหมด</th>
                      <th class="text-center text-nowrap">ชำระเอง</th>
                      <th class="text-center text-nowrap">ที่เบิกได้</th>
                      <th class="text-center text-nowrap">ห้องตรวจ</th>
                  </tr>
                </thead> 
                <tbody> 
                  @foreach($tab['data'] as $index => $row) 
                  <tr>
                    <td align="center" class="text-muted">{{ $index + 1 }}</td>
                    <td align="center" data-order="{{ $row->endpoint === 'Y' ? 3 : (in_array($row->claim_status, ['pulled', 'failed']) ? 2 : 1) }}">                  
                      @php
                          $status = $row->claim_status;
                      @endphp

                      @if($row->endpoint === 'Y')
                          <button onclick="alertAlreadyClosed('สปสช.')" class="btn btn-outline-success btn-sm rounded-circle" title="ปิดสิทธิเรียบร้อยแล้ว">
                              <i class="bi bi-check-circle-fill"></i>
                          </button>
                      @elseif(in_array($status, ['pulled', 'failed']))

                          <button onclick="pushNhsoData('{{ $row->cid }}', '{{ $row->vstdate }}')" class="btn btn-outline-warning btn-sm rounded-circle" title="รอยืนยันปิดสิทธิ (Push)">
                              <i class="bi bi-arrow-up-circle-fill"></i>
                          </button>
                      @else
                          <button onclick="pullNhsoData('{{ $row->vstdate }}', '{{ $row->cid }}')" class="btn btn-outline-info btn-sm rounded-circle pull-nhso-btn" data-vstdate="{{ $row->vstdate }}" data-cid="{{ $row->cid }}" title="ดึงข้อมูลจาก สปสช. (Pull)">
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
                    <td align="center">
                      @if($row->ppfs == 'Y')
                        <span class="badge bg-success shadow-sm">Y</span>
                      @else
                        <span class="badge bg-danger shadow-sm">N</span>
                      @endif
                    </td> 
                    @php
                        $edc_hosxp_list = array_filter(array_map('trim', explode(',', $row->edc)));
                        $edc_ktb_list = array_filter(array_map('trim', explode(',', $row->edc_ktb)));
                        
                        $is_match = !empty($edc_hosxp_list) && !empty($edc_ktb_list) && count(array_intersect($edc_hosxp_list, $edc_ktb_list)) > 0;
                        $text_class = $is_match ? 'text-success fw-bold' : 'text-danger fw-bold';
                        $order_val = $is_match ? 1 : 0;
                    @endphp
                    <td align="center">
                      @if($row->edc)
                        <span class="{{ $text_class }}">{{$row->edc}}</span>
                      @endif
                    </td>
                    <td align="center" data-order="{{ $order_val }}">
                      @if($row->edc_ktb_with_time)
                        <span>{{$row->edc_ktb_with_time}}</span>
                      @else
                        -
                      @endif
                    </td>                
                    <td align="left">
                      <small class="d-block fw-bold text-dark">{{ DateThai($row->vstdate) }}</small>
                      <small class="text-muted">{{ $row->vsttime }}</small>
                      @if(!empty($row->oqueue))
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1" style="font-size: 0.7rem;">Q-{{ $row->oqueue }}</span>
                      @endif
                    </td>                
                    <td align="left">
                      <div class="fw-bold text-dark">{{ $row->ptname }}</div>
                      <small class="text-muted">CID: <span>{{ $row->cid }}</span> | HN: <span>{{ $row->hn }}</span></small>
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
                    <td align="center"><span class="badge bg-secondary shadow-sm">{{$row->department}}</span></td>
                  </tr>
                  @endforeach                 
                </tbody>
              </table>     
            </div>          
          </div>
          @endforeach
      </div>
    </div> 
  </div>    
</div>

    <!-- Modal Import EDC ZIP -->
    <div class="modal fade" id="importEdcModal" tabindex="-1" aria-labelledby="importEdcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="importEdcModalLabel">
                        <i class="bi bi-file-earmark-zip-fill me-2"></i> นำเข้าไฟล์เลขอนุมัติ EDC (ZIP)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="edcZipForm" enctype="multipart/form-data">
                        <div class="mb-4 text-center">
                            <i class="bi bi-cloud-arrow-up-fill text-success" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 small">เลือกไฟล์ ZIP ที่ประกอบไปด้วยไฟล์รายงานเลข EDC ด้านใน</p>
                        </div>
                        <div class="mb-3">
                            <label for="zip_file" class="form-label small fw-bold text-muted">เลือกไฟล์ ZIP</label>
                            <input class="form-control" type="file" id="zip_file" name="zip_file" accept=".zip" required>
                        </div>
                    </form>

                    <!-- Progress Bar Area -->
                    <div id="edc-import-progress-area" style="display: none;">
                        <hr>
                        <div id="edc-progress-text" class="mb-2 text-start small text-muted">กำลังเตรียมนำเข้า...</div>
                        <div class="progress mb-2" style="height: 20px;">
                            <div id="edc-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <div id="edc-details-log" class="text-start small bg-light p-2 border rounded-3" style="max-height: 120px; overflow-y: auto; font-family: monospace; font-size: 11px; line-height: 1.4;"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal" id="cancelImportBtn">ยกเลิก</button>
                    <button type="button" class="btn btn-success px-4 rounded-pill shadow" id="submitZipBtn">นำเข้าข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

<script>
function alertAlreadyClosed(source) {
    Swal.fire({
        icon: 'info',
        title: 'ปิดสิทธิเรียบร้อยแล้ว',
        text: 'รายการนี้ปิดสิทธิโดย' + source + 'เรียบร้อยแล้ว ไม่จำเป็นต้องส่งซ้ำอีกครั้ง',
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
                // ✅ พบข้อมูล → บันทึกแล้ว รีโหลดหน้า
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
                // ❌ ไม่พบข้อมูล → ถามก่อนว่าต้องการปิดเองไหม
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
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: xhr.responseJSON ? xhr.responseJSON.message : 'ไม่สามารถติดต่อ Server ได้'
                    });
                }
            });
        }
    })
}

async function pullAllNhsoData() {
    const buttons = document.querySelectorAll('.pull-nhso-btn');
    if (buttons.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'ไม่มีรายการที่ต้องดึง',
            text: 'ทุกรายการได้รับการดึงข้อมูลหรือปิดสิทธิเรียบร้อยแล้ว'
        });
        return;
    }

    const result = await Swal.fire({
        title: 'ยืนยันดึงข้อมูลทั้งหมด?',
        text: `ระบบจะดึงข้อมูลจาก สปสช. สำหรับผู้ป่วยทั้งหมดที่ยังไม่ได้ดึงจำนวน ${buttons.length} รายการ`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ตกลง, ดึงข้อมูล',
        cancelButtonText: 'ยกเลิก'
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'กำลังดึงข้อมูล...',
        html: `กำลังดำเนินการรายการที่ <b id="current-pull-index">1</b> จากทั้งหมด <b>${buttons.length}</b> รายการ<br><br>` +
              `<div class="progress" style="height: 20px;"><div id="pull-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%">0%</div></div>`,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let successCount = 0;
    for (let i = 0; i < buttons.length; i++) {
        const btn = buttons[i];
        const vstdate = btn.getAttribute('data-vstdate');
        const cid = btn.getAttribute('data-cid');
        
        // update progress
        document.getElementById('current-pull-index').innerText = i + 1;
        const pct = Math.round(((i) / buttons.length) * 100);
        document.getElementById('pull-progress-bar').style.width = pct + '%';
        document.getElementById('pull-progress-bar').innerText = pct + '%';

        try {
            const res = await fetch("{{ url('api/nhso_endpoint_pull_indiv') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ vstdate, cid })
            });
            if (res.ok) {
                successCount++;
            }
        } catch (err) {
            console.error('Failed to pull for ' + cid, err);
        }
    }

    Swal.fire({
        icon: 'success',
        title: 'ดึงข้อมูลสำเร็จ',
        text: `ดึงข้อมูลเสร็จสิ้นทั้งหมด ${successCount} จาก ${buttons.length} รายการ`,
        confirmButtonText: 'ตกลง'
    }).then(() => {
        location.reload();
    });
}

function showLoading() {
    Swal.fire({
        title: 'กำลังดำเนินการ...',
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

      $('.datatable-list').DataTable({
        order: [[1, 'asc']],
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

      // EDC ZIP Import Handler
      document.getElementById('submitZipBtn').addEventListener('click', async function () {
          const fileInput = document.getElementById('zip_file');
          if (fileInput.files.length === 0) {
              Swal.fire({ icon: 'warning', title: 'กรุณาเลือกไฟล์ ZIP', confirmButtonText: 'ตกลง' });
              return;
          }

          const formData = new FormData();
          formData.append('zip_file', fileInput.files[0]);
          formData.append('_token', "{{ csrf_token() }}");

          // Show progress area
          document.getElementById('edc-import-progress-area').style.display = 'block';
          const logDiv = document.getElementById('edc-details-log');
          const progressText = document.getElementById('edc-progress-text');
          const progressBar = document.getElementById('edc-progress-bar');
          const submitBtn = document.getElementById('submitZipBtn');
          const cancelBtn = document.getElementById('cancelImportBtn');

          logDiv.innerHTML = '';
          progressText.innerText = 'กำลังอัปโหลดและแตกไฟล์ ZIP...';
          progressBar.style.width = '0%';
          progressBar.innerText = '0%';
          progressBar.setAttribute('aria-valuenow', 0);
          submitBtn.disabled = true;
          cancelBtn.disabled = true;

          try {
              logDiv.innerHTML += `<div>📤 กำลังอัปโหลดและแตกไฟล์ ZIP...</div>`;
              const uploadRes = await fetch("{{ route('api.import_edc_zip') }}", {
                  method: 'POST',
                  body: formData,
                  headers: {
                      'X-CSRF-TOKEN': "{{ csrf_token() }}",
                      'Accept': 'application/json'
                  }
              });

              const uploadData = await uploadRes.json();
              if (!uploadRes.ok || !uploadData.success) {
                  throw new Error(uploadData.message || 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ ZIP');
              }

              const uniqueId = uploadData.unique_id;
              const files = uploadData.files;
              const totalFiles = files.length;

              logDiv.innerHTML += `<div class="text-success">✔ อัปโหลดสำเร็จ พบไฟล์รายงานด้านในทั้งหมด ${totalFiles} ไฟล์</div>`;
              logDiv.scrollTop = logDiv.scrollHeight;

              let processedCount = 0;
              for (let i = 0; i < totalFiles; i++) {
                  const fileObj = files[i];
                  progressText.innerText = `กำลังประมวลผลไฟล์ที่ ${i + 1}/${totalFiles}: ${fileObj.name}`;
                  logDiv.innerHTML += `<div>🚀 เริ่มนำเข้าไฟล์ ${fileObj.name}...</div>`;
                  logDiv.scrollTop = logDiv.scrollHeight;

                  const fileFormData = new FormData();
                  fileFormData.append('unique_id', uniqueId);
                  fileFormData.append('file_name', fileObj.name);

                  const fileRes = await fetch("{{ route('api.import_edc_file') }}", {
                      method: 'POST',
                      body: fileFormData,
                      headers: {
                          'X-CSRF-TOKEN': "{{ csrf_token() }}",
                          'Accept': 'application/json'
                      }
                  });

                  const fileData = await fileRes.json();
                  if (fileRes.ok && fileData.success) {
                      processedCount++;
                      logDiv.innerHTML += `<div class="text-success" style="margin-left: 10px;">✔ ${fileData.message}</div>`;
                  } else {
                      logDiv.innerHTML += `<div class="text-danger" style="margin-left: 10px;">❌ ล้มเหลว: ${fileData.message || 'เกิดข้อผิดพลาด'}</div>`;
                  }

                  const percent = Math.round((processedCount / totalFiles) * 100);
                  progressBar.style.width = `${percent}%`;
                  progressBar.innerText = `${percent}%`;
                  progressBar.setAttribute('aria-valuenow', percent);
                  logDiv.scrollTop = logDiv.scrollHeight;
              }

              progressText.innerText = 'นำเข้าข้อมูลเสร็จสิ้น!';
              progressBar.classList.replace('bg-success', 'bg-primary');

              Swal.fire({
                  icon: 'success',
                  title: 'นำเข้าเลข EDC สำเร็จ!',
                  text: `ประมวลผลเสร็จสิ้นทั้งหมด ${processedCount} จาก ${totalFiles} ไฟล์`,
                  confirmButtonText: 'ตกลง',
                  confirmButtonColor: '#198754'
              }).then(() => {
                  location.reload();
              });

          } catch (error) {
              logDiv.innerHTML += `<div class="text-danger">❌ เกิดข้อผิดพลาดร้ายแรง: ${error.message}</div>`;
              progressText.innerText = 'การนำเข้าล้มเหลว';
              Swal.fire({
                  icon: 'error',
                  title: 'การนำเข้าล้มเหลว',
                  text: error.message
              });
          } finally {
              submitBtn.disabled = false;
              cancelBtn.disabled = false;
          }
      });
    });
  </script>
@endpush