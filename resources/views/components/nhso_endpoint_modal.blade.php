<!-- Modal ดึงข้อมูลปิดสิทธิจาก สปสช. (NHSO Endpoint Pull Modal) -->
<div class="modal fade" id="nhsoEndpointModal" tabindex="-1" aria-labelledby="nhsoEndpointModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 95vw; width: 1440px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      
      <!-- Modal Header: Title on Left, Date & Pull Button on Right -->
      <div class="modal-header bg-white border-bottom py-2.5 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2.5">
          <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="bi bi-hospital-fill fs-5"></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold mb-0 text-dark" id="nhsoEndpointModalLabel">
              ดึงข้อมูลปิดสิทธิจาก สปสช. (NHSO Endpoint Pull)
            </h6>
            <div class="text-muted small mt-0.5" style="font-size: 0.78rem;">
              วันที่ <span id="nhsoModalSubDateStart" class="fw-semibold text-dark">-</span> ถึง <span id="nhsoModalSubDateEnd" class="fw-semibold text-dark">-</span>
            </div>
          </div>
        </div>

        <!-- Controls: Date Range & Action Buttons grouped together on the RIGHT -->
        <div class="d-flex align-items-center gap-2 ms-auto flex-nowrap modal-header-controls">
          <div class="input-group input-group-sm shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <span class="input-group-text bg-white border-end-0 text-muted" id="nhso_modal_cal_icon" style="cursor: pointer;"><i class="bi bi-calendar-event"></i></span>
            <input type="hidden" id="nhso_modal_start_date" value="{{ date('Y-m-d') }}">
            <input type="text" id="nhso_modal_start_date_picker" class="form-control datepicker_th border-start-0 text-center fw-semibold" readonly tabindex="-1" style="width: 115px; cursor: pointer; font-size: 0.8rem;">
            
            <span class="input-group-text bg-white text-muted">ถึง</span>
            
            <input type="hidden" id="nhso_modal_end_date" value="{{ date('Y-m-d') }}">
            <input type="text" id="nhso_modal_end_date_picker" class="form-control datepicker_th text-center fw-semibold" readonly tabindex="-1" style="width: 115px; cursor: pointer; font-size: 0.8rem;">
            
            <button type="button" class="btn btn-primary px-3 fw-bold" id="btn_modal_search">
              <i class="bi bi-search me-1"></i> ค้นหา
            </button>
          </div>

          <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm fw-bold text-nowrap" id="btn_modal_trigger_pull">
            <i class="bi bi-download me-1"></i> ดึงปิดสิทธิ สปสช.
          </button>

          <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close" id="nhsoModalTopCloseBtn"></button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-3 p-lg-4 bg-light text-start" style="max-height: 80vh;">
        
        <!-- Batch Pull Progress Card (Shown during pull) -->
        <div id="nhsoModalPullProgressCard" class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3 d-none">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-bold text-dark" id="nhsoModalProgressTitle">กำลังประมวลผลดึงปิดสิทธิ...</span>
            <span class="badge bg-danger rounded-pill px-2.5" id="nhsoModalPercentBadge">0%</span>
          </div>
          <div class="progress mb-2" style="height: 14px; border-radius: 8px; background-color: #fee2e2;">
            <div id="nhsoModalProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%; border-radius: 8px;"></div>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <small id="nhsoModalProgressText" class="text-muted" style="font-size: 0.78rem;">กำลังเตรียมข้อมูล...</small>
            <div id="nhsoModalPullSummary" class="small fw-bold text-success d-none"></div>
          </div>
        </div>

        <style>
          .custom-pills .nav-link {
              background: #fff;
              color: #64748b;
              border: 1px solid #e2e8f0;
              transition: all 0.2s ease;
              font-size: 0.82rem;
              font-weight: 600;
              padding: 6px 18px;
          }
          .custom-pills .nav-link.active {
              background: #3b82f6 !important;
              color: #fff !important;
              border-color: #3b82f6 !important;
          }
          .custom-pills .nav-link:hover:not(.active) {
              background: #f8fafc;
              border-color: #cbd5e1;
          }
          .bg-success-soft { background-color: #dcfce7 !important; color: #15803d !important; }
          .bg-info-soft { background-color: #e0f2fe !important; color: #0369a1 !important; }
          .bg-secondary-soft { background-color: #f1f5f9 !important; color: #475569 !important; }
          .table-modern thead th {
              background-color: #f8fafc;
              font-size: 0.78rem;
              font-weight: 700;
              color: #475569;
              white-space: nowrap;
              vertical-align: middle;
              border-bottom: 1px solid #e2e8f0;
              position: sticky;
              top: 0;
              z-index: 5;
              background-color: #f8fafc !important;
              box-shadow: 0 1px 2px rgba(0,0,0,0.06);
          }
          .table-modern tbody td {
              vertical-align: middle;
              font-size: 0.8rem;
          }
          #nhsoEndpointModal .dataTables_wrapper {
              font-size: 0.82rem;
          }
          #nhsoEndpointModal .table-responsive {
              max-height: 58vh;
              overflow: auto;
              border: 1px solid #e2e8f0;
              border-radius: 8px;
          }
          @media (max-width: 991.98px) {
            #nhsoEndpointModal .modal-header {
              flex-direction: column;
              align-items: flex-start !important;
            }
            #nhsoEndpointModal .modal-header-controls {
              width: 100%;
              justify-content: space-between;
              flex-wrap: wrap;
            }
          }
          /* Ensure SweetAlert dialog is always on top of modal and datepicker */
          div.swal2-container {
              z-index: 99999 !important;
          }
          /* Completely hide datepicker popup when SweetAlert is active */
          body.swal2-shown .datepicker,
          .swal2-shown .datepicker {
              display: none !important;
          }
        </style>

        <!-- Tabs Navigation -->
        <ul class="nav nav-pills custom-pills mb-3 gap-2" id="endpointModalTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill shadow-sm" id="modal-closed-tab" data-bs-toggle="tab" data-bs-target="#modal-closed" type="button" role="tab" aria-controls="modal-closed" aria-selected="true">
              <i class="bi bi-check-circle-fill me-1.5"></i> ปิดสิทธิ สปสช. แล้ว 
              <span class="badge bg-white text-primary ms-1" id="modal_closed_count">0</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill shadow-sm" id="modal-pending-tab" data-bs-toggle="tab" data-bs-target="#modal-pending" type="button" role="tab" aria-controls="modal-pending" aria-selected="false">
              <i class="bi bi-clock-history me-1.5"></i> รอปิดสิทธิ สปสช.
              <span class="badge bg-white text-danger ms-1" id="modal_pending_count">0</span>
            </button>
          </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content" id="endpointModalTabContent">
          
          <!-- Tab 1: Closed Records -->
          <div class="tab-pane fade show active" id="modal-closed" role="tabpanel" aria-labelledby="modal-closed-tab">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
              <div class="card-body p-3">
                <div class="table-responsive">
                  <table id="list_closed_modal" class="table table-modern table-hover w-100 align-middle mb-0">
                    <thead>
                      <tr>
                        <th class="text-center" style="width: 50px;">ลำดับ</th>               
                        <th class="text-start">ชื่อ-นามสกุล</th>
                        <th class="text-center" style="width: 140px;">CID</th>
                        <th class="text-start">สิทธิ สปสช.</th> 
                        <th class="text-center" style="width: 170px;">วัน-เวลาที่รับบริการ</th>
                        <th class="text-center" style="width: 130px;">CLAIM TYPE</th>
                        <th class="text-center" style="width: 130px;">CLAIM CODE</th>          
                      </tr>     
                    </thead> 
                    <tbody>
                      <!-- Loaded via DataTables AJAX -->
                    </tbody>
                  </table> 
                </div>          
              </div> 
            </div>
          </div>

          <!-- Tab 2: Pending Records -->
          <div class="tab-pane fade" id="modal-pending" role="tabpanel" aria-labelledby="modal-pending-tab">
            <div class="card border-0 shadow-sm rounded-3 bg-white">
              <div class="card-body p-3">
                
                <!-- Action Bar for Pending -->
                <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                  <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-bold" id="btn_modal_bulk_push" disabled>
                    <i class="bi bi-send-check-fill me-1"></i> ส่งปิดสิทธิรายการที่เลือก (<span id="modal_selected_count">0</span>)
                  </button>
                  <div class="text-muted small" style="font-size: 0.78rem;">
                    <i class="bi bi-info-circle me-1 text-primary"></i> เลือกรายการที่ต้องการแล้วกดปุ่มเพื่อส่งปิดสิทธิทีละรายการ
                  </div>
                </div>

                <div class="table-responsive">
                  <table id="list_pending_modal" class="table table-modern table-hover w-100 align-middle mb-0">
                    <thead>
                      <tr>
                        <th class="text-center" style="width: 30px;">
                          <input type="checkbox" class="form-check-input" id="modal_check_all_pending">
                        </th>
                        <th class="text-center" style="width: 40px;">ลำดับ</th>
                        <th class="text-center" style="width: 90px;">AUTHEN</th>
                        <th class="text-center" style="width: 125px;">วันที่รับบริการ/เวลา</th>
                        <th class="text-center" style="width: 55px;">QUEUE</th>
                        <th class="text-start" style="min-width: 170px;">ชื่อ-สกุล | CID | HN</th>
                        <th class="text-center" style="width: 100px;">การติดต่อ</th>
                        <th class="text-start" style="min-width: 140px;">สิทธิ | HMAIN</th>
                        <th class="text-center" style="width: 65px;">PDX</th>
                        <th class="text-end" style="width: 90px;">ค่ารักษาทั้งหมด</th>
                        <th class="text-end" style="width: 80px;">ต้องชำระ</th>
                        <th class="text-end" style="width: 80px;">ชำระเอง</th>
                        <th class="text-end" style="width: 85px;">ที่เบิกได้</th>
                      </tr>     
                    </thead> 
                    <tbody>
                      <!-- Loaded via DataTables AJAX -->
                    </tbody>
                  </table> 
                </div>          
              </div> 
            </div>
          </div>

        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer border-top bg-white py-2 px-4 d-flex justify-content-between align-items-center">
        <div class="text-muted small" style="font-size: 0.75rem;">
          <i class="bi bi-shield-check text-success me-1"></i> ระบบตรวจสอบและปิดสิทธิ สปสช. (API Endpoint)
        </div>
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>

<script>
  (function () {
    let modalEl = null;
    let dtClosed = null;
    let dtPending = null;
    let isModalPulling = false;
    let hasLoadedEndpointData = false;

    // Common DataTables layout configuration
    const modalCommonConfig = {
      dom: '<"row mb-3 align-items-center"' +
              '<"col-sm-6"l>' + // Show entries
              '<"col-sm-6 d-flex justify-content-end align-items-center gap-2"fB>' + // Search + Export
            '>' +
            'rt' +
            '<"row mt-3 align-items-center"' +
              '<"col-sm-6 text-muted small"i>' + // Info
              '<"col-sm-6 d-flex justify-content-end"p>' + // Pagination
            '>',
      lengthMenu: [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"] ],
      pageLength: 10,
      stateSave: false,
      language: {
        search: "ค้นหา:",
        lengthMenu: "แสดง _MENU_ รายการ",
        info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
        infoEmpty: "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
        zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
        emptyTable: "No data available in table",
        paginate: {
          previous: "ก่อนหน้า",
          next: "ถัดไป"
        }
      }
    };

    function initModalDataTables() {
      if (!$.fn.DataTable) return;

      if (!$.fn.DataTable.isDataTable('#list_closed_modal')) {
        dtClosed = $('#list_closed_modal').DataTable({
          ...modalCommonConfig,
          columns: [
            { data: 'index', className: 'text-center text-muted small' },
            { 
              data: 'name', 
              className: 'text-start fw-bold text-dark small' 
            },
            { data: 'cid', className: 'text-center small text-muted font-monospace' },
            { 
              data: 'subInsclName', 
              className: 'text-start',
              render: function(data) {
                return `<div class="small text-muted lh-1" style="font-size: 0.78rem;">${data || '-'}</div>`;
              }
            },
            { data: 'serviceDateTime', className: 'text-center small' },
            { 
              data: 'claimType', 
              className: 'text-center',
              render: function(data) {
                return `<div class="small text-primary fw-bold">${data || '-'}</div>`;
              }
            },
            { 
              data: 'claimCode', 
              className: 'text-center',
              render: function(data) {
                return data ? `<span class="badge bg-success-soft text-success px-2 py-1 font-monospace fw-bold">${data}</span>` : '<span class="text-muted small">-</span>';
              }
            }
          ],
          buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel (ปิดสิทธิแล้ว)',
              className: 'btn btn-success btn-sm rounded-pill px-3 shadow-sm',
              title: function() {
                const s = $('#nhsoModalSubDateStart').text();
                const e = $('#nhsoModalSubDateEnd').text();
                return `รายชื่อผู้มารับบริการ ปิดสิทธิ สปสช. แล้ว วันที่ ${s} ถึง ${e}`;
              }
            }
          ]
        });
      }

      if (!$.fn.DataTable.isDataTable('#list_pending_modal')) {
        dtPending = $('#list_pending_modal').DataTable({
          ...modalCommonConfig,
          columnDefs: [
            { orderable: false, targets: 0 }
          ],
          order: [[1, 'asc']],
          columns: [
            {
              data: null,
              className: 'text-center',
              render: function(data, type, row) {
                return `<input type="checkbox" class="form-check-input modal-pending-checkbox" 
                               data-cid="${row.cid}" 
                               data-vstdate="${row.vstdate}"
                               data-name="${row.ptname}">`;
              }
            },
            { data: 'index', className: 'text-center text-muted small' },
            { 
              data: 'claimCode', 
              className: 'text-center',
              render: function(data) {
                return data ? `<span class="badge bg-info-soft text-info px-2 py-0.5 font-monospace">${data}</span>` : '<span class="text-muted small">-</span>';
              }
            },
            { 
              data: null, 
              className: 'text-center small',
              render: function(data, type, row) {
                return `<div class="fw-bold">${row.vstdate_thai || row.vstdate}</div><div class="text-muted" style="font-size: 0.7rem;">${row.vsttime || ''}</div>`;
              }
            },
            { 
              data: 'oqueue', 
              className: 'text-center',
              render: function(data) {
                return `<span class="badge bg-light text-dark border px-2">${data || '-'}</span>`;
              }
            },
            { 
              data: null, 
              className: 'text-start',
              render: function(data, type, row) {
                return `<div class="fw-bold text-dark small">${row.ptname || '-'}</div><div class="text-muted small font-monospace" style="font-size: 0.7rem;">CID: ${row.cid || '-'} | HN: ${row.hn || '-'}</div>`;
              }
            },
            { data: 'mobile_phone_number', className: 'text-center small text-muted' },
            { 
              data: null, 
              className: 'text-start',
              render: function(data, type, row) {
                return `<small class="text-truncate d-block" style="max-width: 150px;">${row.subInsclName || '-'}</small><span class="badge bg-secondary-soft text-secondary" style="font-size: 0.65rem;">H: ${row.hospmain || '-'}</span>`;
              }
            },
            { 
              data: 'pdx', 
              className: 'text-center',
              render: function(data) {
                return `<span class="badge bg-light text-dark border">${data || '-'}</span>`;
              }
            },
            { data: 'income', className: 'text-end fw-bold small' },
            { data: 'paid_money', className: 'text-end text-dark small' },
            { data: 'rcpt_money', className: 'text-end text-danger small' },
            { data: 'debtor', className: 'text-end text-primary fw-bold small' }
          ],
          buttons: [
            {
              extend: 'excelHtml5',
              text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel (รอปิดสิทธิ)',
              className: 'btn btn-danger btn-sm rounded-pill px-3 shadow-sm',
              title: function() {
                const s = $('#nhsoModalSubDateStart').text();
                const e = $('#nhsoModalSubDateEnd').text();
                return `รายชื่อผู้มารับบริการ รอปิดสิทธิ สปสช. วันที่ ${s} ถึง ${e}`;
              }
            }
          ]
        });
      }
    }

    // Fetch data via API and populate DataTables
    async function loadEndpointModalData(startDate, endDate) {
      $('#nhsoEndpointModal .datepicker_th').datepicker('hide');
      $('.datepicker').hide();
      if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
      }

      let start = startDate || $('#nhso_modal_start_date').val() || '{{ date("Y-m-d") }}';
      let end = endDate || $('#nhso_modal_end_date').val() || '{{ date("Y-m-d") }}';

      const sParts = String(start).split('-');
      if (sParts.length === 3 && parseInt(sParts[0], 10) > 2400) {
        start = `${parseInt(sParts[0], 10) - 543}-${sParts[1]}-${sParts[2]}`;
      }
      const eParts = String(end).split('-');
      if (eParts.length === 3 && parseInt(eParts[0], 10) > 2400) {
        end = `${parseInt(eParts[0], 10) - 543}-${eParts[1]}-${eParts[2]}`;
      }

      const btnSearch = $('#btn_modal_search');
      btnSearch.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>ค้นหา...');

      try {
        const url = `{{ url('api/nhso_endpoint_data') }}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`;
        const res = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const json = await res.json();
        if (json.status === 'success') {
          // Update subtitles and badges
          $('#nhsoModalSubDateStart').text(json.start_date_thai || json.start_date);
          $('#nhsoModalSubDateEnd').text(json.end_date_thai || json.end_date);
          $('#modal_closed_count').text(json.closed_count || 0);
          $('#modal_pending_count').text(json.pending_count || 0);

          // Populate Closed Table
          if (dtClosed) {
            dtClosed.clear().rows.add(json.closed || []).draw();
          }

          // Populate Pending Table
          if (dtPending) {
            dtPending.clear().rows.add(json.pending || []).draw();
          }

          // Reset checkboxes
          $('#modal_check_all_pending').prop('checked', false);
          updatePendingSelection();
          hasLoadedEndpointData = true;
          return json;
        }
        return null;
      } catch (err) {
        console.error("Fetch NHSO Endpoint Data Error:", err);
        Swal.fire({
          icon: 'error',
          title: 'เกิดข้อผิดพลาด',
          text: 'ไม่สามารถดึงข้อมูลรายการปิดสิทธิได้ กรุณาลองใหม่อีกครั้ง',
          confirmButtonColor: '#3b82f6'
        });
        return null;
      } finally {
        btnSearch.prop('disabled', false).html('<i class="bi bi-search me-1"></i> ค้นหา');
      }
    }

    // Update Bulk Push selection count
    function updatePendingSelection() {
      const selected = $('.modal-pending-checkbox:checked');
      const count = selected.length;
      $('#modal_selected_count').text(count);
      $('#btn_modal_bulk_push').prop('disabled', count === 0);
    }

    // Generate dates in range helper
    function getDatesInRange(startDateStr, endDateStr) {
      const dates = [];
      const sParts = startDateStr.split('-');
      const eParts = endDateStr.split('-');
      let sYear = parseInt(sParts[0], 10);
      let eYear = parseInt(eParts[0], 10);
      if (sYear > 2400) sYear -= 543;
      if (eYear > 2400) eYear -= 543;

      let currentDate = new Date(sYear, parseInt(sParts[1], 10) - 1, parseInt(sParts[2], 10));
      const endDate = new Date(eYear, parseInt(eParts[1], 10) - 1, parseInt(eParts[2], 10));
      currentDate.setHours(0,0,0,0);
      endDate.setHours(0,0,0,0);
      
      while (currentDate <= endDate) {
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const day = String(currentDate.getDate()).padStart(2, '0');
        dates.push(`${year}-${month}-${day}`);
        currentDate.setDate(currentDate.getDate() + 1);
      }
      return dates;
    }

    // Document Ready Setup
    $(document).ready(function () {
      modalEl = document.getElementById('nhsoEndpointModal');
      if (!modalEl) return;

      // Initialize datepickers inside modal
      if ($.fn.datepicker) {
        $('.datepicker_th', modalEl).datepicker({
          format: 'd M yyyy',
          todayBtn: "linked",
          todayHighlight: true,
          autoclose: true,
          language: 'th-th',
          thaiyear: true,
          zIndexOffset: 1060,
          container: '#nhsoEndpointModal',
          showOnFocus: false
        });

        // Show picker explicitly only on user click/touch
        $('.datepicker_th', modalEl).on('click', function(e) {
          e.stopPropagation();
          $(this).datepicker('show');
        });

        $('#nhso_modal_cal_icon').on('click', function(e) {
          e.stopPropagation();
          $('#nhso_modal_start_date_picker').datepicker('show');
        });

        const initialStart = $('#nhso_modal_start_date').val() || '{{ date("Y-m-d") }}';
        const initialEnd = $('#nhso_modal_end_date').val() || '{{ date("Y-m-d") }}';

        $('#nhso_modal_start_date_picker').datepicker('setDate', new Date(initialStart));
        $('#nhso_modal_end_date_picker').datepicker('setDate', new Date(initialEnd));

        // Immediately update subtitle text
        if ($('#nhso_modal_start_date_picker').val()) {
          $('#nhsoModalSubDateStart').text($('#nhso_modal_start_date_picker').val());
        }
        if ($('#nhso_modal_end_date_picker').val()) {
          $('#nhsoModalSubDateEnd').text($('#nhso_modal_end_date_picker').val());
        }

        $('.datepicker_th', modalEl).on('changeDate', function(e) {
          const date = e.date;
          const targetId = $(this).attr('id').replace('_picker', '');
          if (date) {
            const day = ("0" + date.getDate()).slice(-2);
            const month = ("0" + (date.getMonth() + 1)).slice(-2);
            let year = date.getFullYear();
            if (year > 2400) year -= 543;
            $('#' + targetId).val(year + "-" + month + "-" + day);
          }
          // Sync subtitle dates immediately
          $('#nhsoModalSubDateStart').text($('#nhso_modal_start_date_picker').val());
          $('#nhsoModalSubDateEnd').text($('#nhso_modal_end_date_picker').val());

          // Hide datepicker immediately upon date selection & blur to prevent refocus popups
          $(this).datepicker('hide');
          if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
          }
        });

        // Hide datepicker if user clicks "วันนี้" button
        $(modalEl).on('click', '.datepicker .today', function() {
          setTimeout(function() {
            $('.datepicker_th', modalEl).datepicker('hide');
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
              document.activeElement.blur();
            }
          }, 50);
        });
      }

      // Initialize DataTables
      initModalDataTables();

      // Adjust column sizes when tab switched
      $('button[data-bs-toggle="tab"]', modalEl).on('shown.bs.tab', function () {
        if (dtClosed) dtClosed.columns.adjust().draw();
        if (dtPending) dtPending.columns.adjust().draw();
      });

      // When modal is shown
      modalEl.addEventListener('shown.bs.modal', function () {
        initModalDataTables();
        if (dtClosed) dtClosed.columns.adjust().draw();
        if (dtPending) dtPending.columns.adjust().draw();

        if (!hasLoadedEndpointData) {
          loadEndpointModalData();
        }
      });

      // Clean up datepickers when modal is hidden
      modalEl.addEventListener('hide.bs.modal', function () {
        $('.datepicker_th', modalEl).datepicker('hide');
        $('.datepicker').hide();
      });

      // Search button click
      $('#btn_modal_search').on('click', function (e) {
        e.preventDefault();
        $('.datepicker_th', modalEl).datepicker('hide');
        $('.datepicker').hide();
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
          document.activeElement.blur();
        }
        loadEndpointModalData();
      });

      // Pending Checkbox All
      $('#modal_check_all_pending').on('change', function () {
        $('.modal-pending-checkbox').prop('checked', this.checked);
        updatePendingSelection();
      });

      // Individual Checkbox change
      $(document).on('change', '.modal-pending-checkbox', function () {
        updatePendingSelection();
        const allCount = $('.modal-pending-checkbox').length;
        const checkedCount = $('.modal-pending-checkbox:checked').length;
        $('#modal_check_all_pending').prop('checked', allCount > 0 && allCount === checkedCount);
      });

      // Bulk Push Action in Tab 2
      $('#btn_modal_bulk_push').on('click', async function () {
        const selected = $('.modal-pending-checkbox:checked');
        if (selected.length === 0) return;

        const result = await Swal.fire({
          title: 'ยืนยันการส่งปิดสิทธิ?',
          text: `ระบบจะดำเนินการส่งข้อมูลปิดสิทธิทีละรายการ จำนวน ${selected.length} รายการ`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'ยืนยัน',
          cancelButtonText: 'ยกเลิก',
          confirmButtonColor: '#3b82f6',
          borderRadius: '16px',
          returnFocus: false,
          didClose: () => {
            $('.datepicker_th', modalEl).datepicker('hide');
            $('.datepicker').hide();
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
              document.activeElement.blur();
            }
          }
        });

        if (!result.isConfirmed) {
          $('.datepicker_th', modalEl).datepicker('hide');
          $('.datepicker').hide();
          if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
          }
          return;
        }

        Swal.fire({
          title: 'กำลังดำเนินการส่งปิดสิทธิ...',
          html: 'รายการที่ <b id="current_bulk_idx">1</b> จาก <b>' + selected.length + '</b><br><small id="current_bulk_name" class="text-muted"></small>',
          allowOutsideClick: false,
          didOpen: () => { Swal.showLoading(); }
        });

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < selected.length; i++) {
          const item = $(selected[i]);
          const cid = item.data('cid');
          const vstdate = item.data('vstdate');
          const name = item.data('name');

          $('#current_bulk_idx').text(i + 1);
          $('#current_bulk_name').text(name || cid);

          try {
            const res = await fetch("{{ url('api/nhso_endpoint_push_indiv') }}", {
              method: "POST",
              headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json",
                "Accept": "application/json"
              },
              body: JSON.stringify({ cid, vstdate })
            });
            const data = await res.json();
            if (data.status === 'success') successCount++; else failCount++;
          } catch (e) {
            failCount++;
          }
          await new Promise(r => setTimeout(r, 150));
        }

        await Swal.fire({
          title: 'ดำเนินการเสร็จสิ้น',
          text: `สำเร็จ ${successCount} รายการ, ล้มเหลว ${failCount} รายการ`,
          icon: successCount > 0 ? 'success' : 'error',
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#3b82f6',
          borderRadius: '16px'
        });

        // Refresh tables
        loadEndpointModalData();
      });

      // Trigger Batch Pull button
      $('#btn_modal_trigger_pull').on('click', async function (e) {
        e.preventDefault();
        e.stopPropagation();

        // Immediately close any open datepickers and blur active input to prevent calendar bounce
        $('.datepicker_th', modalEl).datepicker('hide');
        $('.datepicker').hide();
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
          document.activeElement.blur();
        }

        if (isModalPulling) return;

        let startVal = $('#nhso_modal_start_date').val();
        let endVal = $('#nhso_modal_end_date').val();

        if (!startVal || !endVal) {
          Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน',
            text: 'กรุณาระบุช่วงวันที่ต้องการดึงปิดสิทธิ',
            returnFocus: false
          });
          return;
        }

        const sParts = startVal.split('-');
        if (sParts.length === 3 && parseInt(sParts[0], 10) > 2400) {
          startVal = `${parseInt(sParts[0], 10) - 543}-${sParts[1]}-${sParts[2]}`;
        }
        const eParts = endVal.split('-');
        if (eParts.length === 3 && parseInt(eParts[0], 10) > 2400) {
          endVal = `${parseInt(eParts[0], 10) - 543}-${eParts[1]}-${eParts[2]}`;
        }

        const dates = getDatesInRange(startVal, endVal);
        if (dates.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'แจ้งเตือน',
            text: 'ช่วงวันที่ไม่ถูกต้อง',
            returnFocus: false
          });
          return;
        }

        // Get formatted display dates directly from datepicker inputs
        const displayStart = $('#nhso_modal_start_date_picker').val() || startVal;
        const displayEnd = $('#nhso_modal_end_date_picker').val() || endVal;
        const btnPull = $('#btn_modal_trigger_pull');

        // 1. Fetch case list to check first
        btnPull.prop('disabled', true);
        const originalBtnHtml = btnPull.html();
        btnPull.html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังตรวจนับเคส...');

        let listData = null;
        try {
          const listRes = await fetch(`{{ url('api/nhso_get_pull_list') }}?start_date=${encodeURIComponent(startVal)}&end_date=${encodeURIComponent(endVal)}`, {
            headers: { 'Accept': 'application/json' }
          });
          if (listRes.ok) {
            listData = await listRes.json();
          }
        } catch (e) {
          console.error("Fetch pull list error:", e);
        } finally {
          btnPull.prop('disabled', false).html(originalBtnHtml);
        }

        if (!listData || listData.status !== 'success') {
          Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: listData && listData.message ? listData.message : 'ไม่สามารถเชื่อมต่อเพื่อตรวจสอบรายการเคสได้',
            returnFocus: false
          });
          return;
        }

        const items = listData.items || [];
        const totalCases = items.length; // จำนวนคน (Unique CIDs)
        const totalVns = listData.total_vns || totalCases; // จำนวนครั้งรับบริการ (VNs)

        if (totalCases === 0) {
          await Swal.fire({
            icon: 'info',
            title: 'ไม่พบเคสค้างตรวจสอบ',
            text: `ข้อมูลสำหรับช่วงวันที่ ${displayStart} ถึง ${displayEnd} ได้รับการดึงปิดสิทธิครบถ้วนแล้ว (ไม่มีรายการค้างตรวจ)`,
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#3b82f6',
            borderRadius: '16px',
            returnFocus: false,
            didClose: () => {
              $('.datepicker_th', modalEl).datepicker('hide');
              $('.datepicker').hide();
              if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
              }
            }
          });
          return;
        }

        const confirmHtml = (totalVns > totalCases)
          ? `ระบบพบรายการรอปิดสิทธิ <b>${totalVns.toLocaleString()}</b> รายการ<br><small class="text-muted">(ผู้ป่วย <b>${totalCases.toLocaleString()}</b> คน ที่ต้องส่งตรวจ สปสช.)</small><br><span class="small text-secondary mt-1 d-inline-block">สำหรับช่วงวันที่ ${displayStart} ถึง ${displayEnd}</span>`
          : `ระบบพบเคสที่ต้องตรวจสอบจำนวน <b>${totalCases.toLocaleString()}</b> เคส<br><span class="small text-secondary mt-1 d-inline-block">สำหรับช่วงวันที่ ${displayStart} ถึง ${displayEnd}</span>`;

        const confirmBtnText = (totalVns > totalCases)
          ? `เริ่มดึงข้อมูล (${totalCases.toLocaleString()} คน / ${totalVns.toLocaleString()} รายการ)`
          : `เริ่มดึงข้อมูล (${totalCases.toLocaleString()} เคส)`;

        const confirm = await Swal.fire({
          title: 'ยืนยันการดึงปิดสิทธิ สปสช.?',
          html: confirmHtml,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: confirmBtnText,
          cancelButtonText: 'ยกเลิก',
          confirmButtonColor: '#ef4444',
          borderRadius: '16px',
          returnFocus: false,
          willOpen: () => {
            $('.datepicker_th', modalEl).datepicker('hide');
            $('.datepicker').hide();
          },
          didClose: () => {
            $('.datepicker_th', modalEl).datepicker('hide');
            $('.datepicker').hide();
            if (document.activeElement && typeof document.activeElement.blur === 'function') {
              document.activeElement.blur();
            }
          }
        });

        if (!confirm.isConfirmed) {
          $('.datepicker_th', modalEl).datepicker('hide');
          $('.datepicker').hide();
          if (document.activeElement && typeof document.activeElement.blur === 'function') {
            document.activeElement.blur();
          }
          return;
        }

        // Show inline progress card
        isModalPulling = true;
        const progressCard = $('#nhsoModalPullProgressCard');
        const progressBar = $('#nhsoModalProgressBar');
        const percentBadge = $('#nhsoModalPercentBadge');
        const progressText = $('#nhsoModalProgressText');
        const progressTitle = $('#nhsoModalProgressTitle');
        const pullSummary = $('#nhsoModalPullSummary');

        btnPull.prop('disabled', true);
        progressCard.removeClass('d-none');
        pullSummary.addClass('d-none').text('');
        progressTitle.text('กำลังดึงข้อมูลปิดสิทธิจาก สปสช...');
        progressBar.css('width', '0%');
        percentBadge.text('0%');
        progressText.html((totalVns > totalCases)
          ? `เริ่มต้นตรวจสอบผู้ป่วย <b>${totalCases.toLocaleString()}</b> คน (ครอบคลุม <b>${totalVns.toLocaleString()}</b> รายการ)...`
          : `เริ่มต้นตรวจสอบเคสทั้งหมด <b>${totalCases.toLocaleString()}</b> รายการ...`
        );

        let processedCases = 0;
        let totalPulled = 0;
        let totalInserted = 0;
        let totalUpdated = 0;
        let failCount = 0;
        const chunkSize = 15; // 15 cases per batch for optimal pacing and avoiding NHSO rate-limits

        for (let i = 0; i < totalCases; i += chunkSize) {
          const chunk = items.slice(i, i + chunkSize);
          const currentEnd = Math.min(i + chunkSize, totalCases);

          progressText.html((totalVns > totalCases)
            ? `กำลังส่งตรวจสอบผู้ป่วยคนที่ <b>${i + 1} - ${currentEnd}</b> จาก <b>${totalCases.toLocaleString()}</b> คน (ครอบคลุม <b>${totalVns.toLocaleString()}</b> รายการ)...`
            : `กำลังส่งตรวจสอบเคสที่ <b>${i + 1} - ${currentEnd}</b> จากทั้งหมด <b>${totalCases.toLocaleString()}</b> เคส...`
          );

          try {
            const res = await fetch("{{ url('api/nhso_pull_chunk') }}", {
              method: "POST",
              headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Content-Type": "application/json",
                "Accept": "application/json"
              },
              body: JSON.stringify({ items: chunk })
            });

            if (res.ok) {
              const data = await res.json();
              if (data.success) {
                totalPulled += (data.pulled || 0);
                totalInserted += (data.inserted || 0);
                totalUpdated += (data.updated || 0);
                failCount += (data.errors || 0);
              } else {
                failCount += chunk.length;
              }
            } else {
              failCount += chunk.length;
            }
          } catch (e) {
            console.error("Pull chunk error:", e);
            failCount += chunk.length;
          }

          processedCases += chunk.length;
          const percent = Math.min(100, Math.round((processedCases / totalCases) * 100));
          progressBar.css('width', percent + '%');
          percentBadge.text(percent + '%');

          // Polite pause between chunks to keep connection healthy
          await new Promise(r => setTimeout(r, 200));
        }

        // Finished Batch Pull
        isModalPulling = false;
        btnPull.prop('disabled', false);
        progressBar.css('width', '100%');
        percentBadge.text('100%');
        progressTitle.text('ดึงข้อมูลปิดสิทธิเสร็จสิ้น');
        progressText.html((totalVns > totalCases)
          ? `<i class="bi bi-check-circle-fill text-success me-1"></i> ตรวจสอบครบ <b>${totalCases.toLocaleString()}</b> คน ครอบคลุม <b>${totalVns.toLocaleString()}</b> รายการ (บันทึกใหม่ <b>${totalInserted.toLocaleString()}</b> รายการ, อัปเดต <b>${totalUpdated.toLocaleString()}</b> รายการ)`
          : `<i class="bi bi-check-circle-fill text-success me-1"></i> ตรวจสอบครบ <b>${totalCases.toLocaleString()}</b> เคส (บันทึกใหม่ <b>${totalInserted.toLocaleString()}</b> รายการ, อัปเดต <b>${totalUpdated.toLocaleString()}</b> รายการ)`
        );

        $('.datepicker_th', modalEl).datepicker('hide');
        $('.datepicker').hide();
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
          document.activeElement.blur();
        }

        // Auto refresh table data and get latest database counts
        const latestStats = await loadEndpointModalData();
        const finalClosed = latestStats ? (latestStats.closed_count || 0) : parseInt($('#modal_closed_count').text() || '0', 10);
        const finalPending = latestStats ? (latestStats.pending_count || 0) : parseInt($('#modal_pending_count').text() || '0', 10);

        // Auto hide inline progress card after 3 seconds
        setTimeout(() => {
          progressCard.fadeOut(400, function() {
            $(this).addClass('d-none').show();
          });
        }, 3000);

        // Persistent summary alert matching standard FDH layout (green, yellow, red badges)
        const overallSuccess = (failCount === 0);
        const summaryHtml = `
            <div class="text-start p-2 fs-6">
                <b>สถานะ:</b> ${overallSuccess ? '✅ สำเร็จทั้งหมด' : '⚠️ เสร็จสิ้น แต่มีข้อผิดพลาดบางส่วน'}<br>
                <b>รายการทั้งหมด:</b> ${totalCases.toLocaleString()} คน (${totalVns.toLocaleString()} รายการ)<br>
                <hr class="my-2">
                <b>ดึงข้อมูลสำเร็จ:</b> <span class="badge bg-success text-white">${totalInserted.toLocaleString()}</span> รายการ<br>
                <b>ไม่พบข้อมูล:</b> <span class="badge bg-warning text-dark">${Number(finalPending).toLocaleString()}</span> รายการ<br>
                <b>เกิดข้อผิดพลาด:</b> <span class="badge bg-danger text-white">${failCount.toLocaleString()}</span> รายการ
            </div>
        `;

        await Swal.fire({
            icon: overallSuccess ? 'success' : 'warning',
            title: 'ดึงปิดสิทธิ สปสช. เสร็จสิ้น',
            html: summaryHtml,
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#0dcaf0',
            allowOutsideClick: false,
            returnFocus: false,
            didClose: () => {
              $('.datepicker_th', modalEl).datepicker('hide');
              $('.datepicker').hide();
              if (document.activeElement && typeof document.activeElement.blur === 'function') {
                document.activeElement.blur();
              }
            }
        });
      });

      // Auto open modal if redirected from legacy route (?open_nhso_modal=1)
      if (window.location.search.indexOf('open_nhso_modal=1') !== -1) {
        setTimeout(() => {
          if (modalEl.length && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl[0]);
            modalInst.show();
          }
        }, 300);
      }

    });
  })();
</script>
