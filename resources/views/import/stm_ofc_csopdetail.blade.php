@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-file-earmark-text-fill text-success me-2"></i>
                ข้อมูล Statement สวัสดิการข้าราชการ CSOP-ฟอกไต OFC
            </h5>
            <div class="text-muted small mt-1">รายละเอียดข้อมูลการเบิกจ่ายแยกตามสถานะ</div>
            <div class="mt-2">
                <a href="{{ url('import/stm_ofc_csop') }}" class="btn btn-secondary btn-sm rounded-pill px-3">
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
                <button type="button" onclick="triggerResync()" class="btn btn-warning btn-sm rounded-pill px-3 ms-2 shadow-sm" id="btn_resync" style="display:none;" title="รีซิงก์รหัส HN เก่าในประวัติชดเชยตามคู่มือจับคู่ล่าสุด">
                    <i class="bi bi-arrow-clockwise me-1"></i> Resync update HN
                </button>
            </div>
        </form>
    </div>

    <!-- Tab/Filter Navigation -->
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-pills gap-2 bg-white p-2 rounded shadow-sm d-inline-flex" id="sysTypeTab" role="tablist" style="border: 1px solid rgba(225, 230, 235, 0.75);">
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn btn-sm rounded-pill px-3 active" id="all-tab" data-sys-type="all" type="button" role="tab">
                        <i class="bi bi-grid-fill me-1"></i> ทั้งหมด
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn btn-sm rounded-pill px-3" id="csop-tab" data-sys-type="csop" type="button" role="tab">
                        <i class="bi bi-person-badge-fill me-1"></i> ผู้ป่วยนอกทั่วไป (CSOP)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn btn-sm rounded-pill px-3" id="kidney-tab" data-sys-type="kidney" type="button" role="tab">
                        <i class="bi bi-droplet-fill me-1 text-danger"></i> ผู้ป่วยฟอกไต (HD)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link btn btn-sm rounded-pill px-3" id="unmapped-tab" data-sys-type="unmapped" type="button" role="tab">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> HN ที่ไม่พบใน HOSxP
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card dash-card accent-9 mb-4">
        <div class="card-body p-4">
            
            <!-- Normal Table Container -->
            <div id="normal_table_container">
                <div class="table-responsive">
                    <table id="stm_ofc_csop_list" class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th>Station</th>
                                <th>Sys</th> 
                                <th>HN</th>
                                <th>Hreg</th>
                                <th>ชื่อ-สกุล</th>
                                <th>InvNo</th>
                                <th>vstdate</th>                    
                                <th>ค่ารักษาที่เบิก</th> 
                                <th>RepNo</th>
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

            <!-- Unmapped Table Container -->
            <div id="unmapped_table_container" style="display: none;">
                <div class="table-responsive">
                    <table id="stm_ofc_csop_unmapped_list" class="table table-modern w-100">
                        <thead>
                            <tr>
                                <th class="text-center">HN (จากไฟล์ STM)</th>
                                <th>ชื่อ-สกุล (จากไฟล์ STM)</th>
                                <th class="text-center">จำนวนวิสิท (Visits)</th>
                                <th class="text-end">ชดเชยรวม (บาท)</th>
                                <th class="text-center" width="15%">จับคู่ข้อมูล HN</th>
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
</div>

<!-- Modal for Mapping HN -->
<div class="modal fade" id="mappingModal" tabindex="-1" aria-labelledby="mappingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 16px;">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="mappingModalLabel">
                    <i class="bi bi-link-45deg text-primary me-1"></i> จับคู่ HN HOSxP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i> 
                    กรุณาค้นหาคนไข้ใน HOSxP เพื่อจับคู่กับ HN จากสเตทเมนต์นี้
                </div>
                
                <form id="mappingForm">
                    <div class="mb-3 bg-light p-3 rounded-3">
                        <div class="row">
                            <div class="col-6">
                                <label class="text-muted small d-block">HN จากไฟล์ STM</label>
                                <span id="lbl_incorrect_hn" class="fw-bold text-danger fs-6">-</span>
                                <input type="hidden" name="incorrect_hn" id="txt_incorrect_hn">
                            </div>
                            <div class="col-6">
                                <label class="text-muted small d-block">ชื่อจากไฟล์ STM</label>
                                <span id="lbl_pt_name" class="fw-bold text-dark fs-6">-</span>
                                <input type="hidden" name="pt_name" id="txt_pt_name">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">ค้นหาคนไข้ HOSxP (ระบุ HN หรือ ชื่อ-นามสกุล)</label>
                        <div class="input-group">
                            <input type="text" id="search_patient_query" class="form-control" placeholder="ค้นหาด้วย HN หรือชื่อคนไข้..." autocomplete="off">
                            <button type="button" id="btn_search_patient" class="btn btn-primary">
                                <i class="bi bi-search"></i> ค้นหา
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">ผลการค้นหา (กรุณาเลือกคนไข้ที่ถูกต้อง)</label>
                        <div id="search_results_container" class="border rounded p-2" style="max-height: 200px; overflow-y: auto; background-color: #fcfcfc;">
                            <div class="text-center text-muted py-3 small">ระบุคำค้นหาแล้วกดปุ่มค้นหา</div>
                        </div>
                        <input type="hidden" name="correct_hn" id="txt_correct_hn">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-3 btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" onclick="saveMapping()" id="btn_save_mapping" class="btn btn-success rounded-pill px-4 btn-sm" disabled>
                    <i class="bi bi-check-circle me-1"></i> บันทึกการจับคู่
                </button>
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

      var currentSysType = 'all';

      var table = $('#stm_ofc_csop_list').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('import.stm_ofc_csopdetail') }}",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.sys_type = currentSysType;
            }
        },
        columns: [
            { data: 'station', name: 'station' },
            { data: 'sys', name: 'sys' },
            { data: 'hn', name: 'hn', className: 'text-center fw-bold' },
            { data: 'hreg', name: 'hreg', className: 'text-center small text-muted' },
            { data: 'pt_name', name: 'pt_name' },
            { data: 'invno', name: 'invno', className: 'text-center small' },
            { data: 'vstdate', name: 'vstdate', className: 'text-center small text-muted' },
            { data: 'amount', name: 'amount', className: 'text-end fw-bold text-success' },
            { data: 'rid', name: 'rid', className: 'text-center small' }
            , { data: 'receive_no', name: 'receive_no', className: 'text-center text-primary fw-bold small' }
            , { data: 'receipt_date', name: 'receipt_date', className: 'text-center small' }
            , { data: 'receipt_by', name: 'receipt_by', className: 'text-center small text-muted' }
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
                    window.location.href = "{{ route('import.stm_ofc_csopdetail') }}?export=excel&start_date=" + start + "&end_date=" + end + "&sys_type=" + currentSysType;
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
            processing: "กำลังโหลดข้อมูล..."
        }
      });

      var unmappedTable = null;

      function initUnmappedTable() {
          if (unmappedTable) {
              unmappedTable.ajax.reload();
              return;
          }
          unmappedTable = $('#stm_ofc_csop_unmapped_list').DataTable({
              processing: true,
              serverSide: true,
              ajax: {
                  url: "{{ route('import.stm_ofc_csopdetail') }}",
                  data: function (d) {
                      d.start_date = $('#start_date').val();
                      d.end_date = $('#end_date').val();
                      d.sys_type = currentSysType;
                  }
              },
              columns: [
                  { data: 'hn', name: 'hn', className: 'text-center fw-bold text-danger' },
                  { data: 'pt_name', name: 'pt_name' },
                  { data: 'count_no', name: 'count_no', className: 'text-center fw-bold' },
                  { data: 'amount', name: 'amount', className: 'text-end fw-bold text-dark' },
                  { 
                      data: null, 
                      orderable: false, 
                      searchable: false,
                      className: 'text-center',
                      render: function(data, type, row) {
                          return '<button type="button" class="btn btn-sm btn-primary rounded-pill px-3 btn-map-hn" data-hn="' + row.hn + '" data-name="' + row.pt_name + '">' +
                                 '<i class="bi bi-link-45deg me-1"></i> จับคู่ HN</button>';
                      }
                  }
              ],
              dom: '<"row mb-3"<"col-md-6"l><"col-md-6 d-flex justify-content-end align-items-center gap-2"f>><rt><"row mt-3"<"col-md-6"i><"col-md-6"p>>',
              language: {
                  search: "ค้นหา:",
                  lengthMenu: "แสดง _MENU_ รายการ",
                  info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                  paginate: {
                    previous: "ก่อนหน้า",
                    next: "ถัดไป"
                  },
                  processing: "กำลังโหลดข้อมูล..."
              }
          });
      }

      // Handle Tab Click to Reload Table
      $('#sysTypeTab button').on('click', function (e) {
          e.preventDefault();
          $('#sysTypeTab button').removeClass('active');
          $(this).addClass('active');
          currentSysType = $(this).data('sys-type');

          if (currentSysType === 'unmapped') {
              $('#normal_table_container').hide();
              $('#unmapped_table_container').show();
              $('#btn_resync').show();
              initUnmappedTable();
          } else {
              $('#unmapped_table_container').hide();
              $('#normal_table_container').show();
              $('#btn_resync').hide();
              table.ajax.reload();
          }
      });

      // Mapping Modal Trigger
      $(document).on('click', '.btn-map-hn', function() {
          var hn = $(this).data('hn');
          var name = $(this).data('name');

          $('#txt_incorrect_hn').val(hn);
          $('#txt_pt_name').val(name);
          $('#lbl_incorrect_hn').text(hn);
          $('#lbl_pt_name').text(name);

          // Clear search results
          $('#search_patient_query').val('');
          $('#search_results_container').html('<div class="text-center text-muted py-3 small">ระบุคำค้นหาแล้วกดปุ่มค้นหา</div>');
          $('#txt_correct_hn').val('');
          $('#btn_save_mapping').prop('disabled', true);

          $('#mappingModal').modal('show');
      });

      // Search Patient in Modal
      $('#btn_search_patient').on('click', function() {
          performPatientSearch();
      });

      $('#search_patient_query').on('keypress', function(e) {
          if (e.which === 13) {
              e.preventDefault();
              performPatientSearch();
          }
      });

      function performPatientSearch() {
          var query = $('#search_patient_query').val().trim();
          if (query.length < 2) {
              Swal.fire({ icon: 'warning', title: 'คำค้นหาสั้นเกินไป', text: 'กรุณาระบุตัวอักษรอย่างน้อย 2 ตัวขึ้นไป' });
              return;
          }

          $('#search_results_container').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2 small">กำลังสืบค้น...</span></div>');
          $('#btn_save_mapping').prop('disabled', true);

          $.ajax({
              url: "{{ url('import/stm_ofc_csop/search_patient') }}",
              type: "POST",
              data: {
                  _token: "{{ csrf_token() }}",
                  q: query
              },
              success: function(res) {
                  if (res.length === 0) {
                      $('#search_results_container').html('<div class="text-center text-danger py-3 small"><i class="bi bi-x-circle me-1"></i> ไม่พบข้อมูลคนไข้ใน HOSxP</div>');
                      return;
                  }

                  var html = '<div class="list-group list-group-flush">';
                  res.forEach(function(p) {
                      var fullName = p.pname + p.fname + ' ' + p.lname;
                      html += '<a href="javascript:void(0)" class="list-group-item list-group-item-action py-2 select-patient-row" data-hn="' + p.hn + '" data-name="' + fullName + '">' +
                              '<div class="d-flex w-100 justify-content-between align-items-center">' +
                                  '<div>' +
                                      '<span class="fw-bold text-primary me-2">' + p.hn + '</span>' +
                                      '<span class="fw-bold text-dark">' + fullName + '</span>' +
                                  '</div>' +
                                  '<span class="badge bg-secondary rounded-pill small">' + (p.cid || '') + '</span>' +
                              '</div>' +
                              '</a>';
                  });
                  html += '</div>';
                  $('#search_results_container').html(html);
              },
              error: function() {
                  $('#search_results_container').html('<div class="text-center text-danger py-3 small"><i class="bi bi-exclamation-triangle-fill me-1"></i> ผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์</div>');
              }
          });
      }

      // Select Patient Row
      $(document).on('click', '.select-patient-row', function() {
          $('.select-patient-row').removeClass('active bg-light-soft text-primary');
          $(this).addClass('active bg-light-soft text-primary');
          
          var hn = $(this).data('hn');
          $('#txt_correct_hn').val(hn);
          $('#btn_save_mapping').prop('disabled', false);
      });

      // Save Mapping function
      window.saveMapping = function() {
          var formData = {
              _token: "{{ csrf_token() }}",
              incorrect_hn: $('#txt_incorrect_hn').val(),
              correct_hn: $('#txt_correct_hn').val(),
              pt_name: $('#txt_pt_name').val()
          };

          Swal.fire({
              title: 'ยืนยันการจับคู่ HN?',
              text: "จับคู่ HN " + formData.incorrect_hn + " กับ HN HOSxP " + formData.correct_hn,
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'ตกลง',
              cancelButtonText: 'ยกเลิก'
          }).then((result) => {
              if (result.isConfirmed) {
                  Swal.fire({
                      title: 'กำลังบันทึก...',
                      allowOutsideClick: false,
                      didOpen: () => { Swal.showLoading(); }
                  });

                  $.ajax({
                      url: "{{ url('import/stm_ofc_csop/save_mapping') }}",
                      type: "POST",
                      data: formData,
                      success: function(res) {
                          if (res.success) {
                              Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message });
                              $('#mappingModal').modal('hide');
                              unmappedTable.ajax.reload();
                          } else {
                              Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
                          }
                      },
                      error: function(xhr) {
                          var msg = xhr.responseJSON ? xhr.responseJSON.message : 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ';
                          Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
                      }
                  });
              }
          });
      };

      // Resync mapping function
      window.triggerResync = function() {
          Swal.fire({
              title: 'ยืนยันการทำ Resync?',
              text: "ระบบจะทำการอัปเดตข้อมูล HN ย้อนหลังของเคสฟอกไตทั้งหมดอ้างอิงตามคู่มือจับคู่ล่าสุด",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#ffc107',
              cancelButtonColor: '#d33',
              confirmButtonText: 'เริ่ม Resync',
              cancelButtonText: 'ยกเลิก'
          }).then((result) => {
              if (result.isConfirmed) {
                  Swal.fire({
                      title: 'กำลังรีซิงก์ข้อมูล...',
                      text: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
                      allowOutsideClick: false,
                      didOpen: () => { Swal.showLoading(); }
                  });

                  $.ajax({
                      url: "{{ url('import/stm_ofc_csop/resync') }}",
                      type: "POST",
                      data: { _token: "{{ csrf_token() }}" },
                      success: function(res) {
                          if (res.success) {
                              Swal.fire({ icon: 'success', title: 'สำเร็จ', text: res.message });
                              if (unmappedTable) {
                                  unmappedTable.ajax.reload();
                              }
                          } else {
                              Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: res.message });
                          }
                      },
                      error: function(xhr) {
                          var msg = xhr.responseJSON ? xhr.responseJSON.message : 'เกิดข้อผิดพลาด';
                          Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: msg });
                      }
                  });
              }
          });
      };
    });
</script> 
@endpush
