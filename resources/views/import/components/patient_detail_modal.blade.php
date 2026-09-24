{{-- Universal Modal: รายละเอียดผู้ป่วยรายคน --}}
<div class="modal fade" id="patientDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable" style="max-width: 95vw; width: 1360px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            {{-- Modal Header --}}
            <div class="modal-header text-white p-3 px-4" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 12px;">
                        <i class="bi bi-person-lines-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold mb-0 text-white" id="pmodal_title">รายละเอียดผู้ป่วยรายคน</h5>
                            <span class="badge bg-white text-dark font-monospace px-2.5 py-1" id="pmodal_round_badge" style="font-size: 13px;">งวด -</span>
                        </div>
                        <div class="text-white-50 small mt-0.5" id="pmodal_subtitle">Statement รายละเอียดงวดรับเงิน</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body p-3 p-md-4 bg-light">
                {{-- Round Summary Info Card --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3">
                                <span class="text-muted small">เลขงวด:</span>
                                <div class="fw-bold text-primary font-monospace fs-6" id="pmodal_round_no">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted small">ชื่อไฟล์ Statement:</span>
                                <div class="fw-semibold text-dark text-truncate small" id="pmodal_filename" title="-">-</div>
                            </div>
                            <div class="col-md-2">
                                <span class="text-muted small">เลขที่ใบเสร็จ:</span>
                                <div id="pmodal_receipt_display">-</div>
                            </div>
                            <div class="col-md-3 text-md-end">
                                <span class="text-muted small">ยอดชดเชยค่ารักษารวม:</span>
                                <div class="fw-bold text-success fs-5" id="pmodal_total_amount">0.00 บาท</div>
                                <div class="text-muted small" id="pmodal_total_count_hint">(0 รายการ)</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Toolbar: Search, Stats & Actions --}}
                <div class="card border-0 shadow-sm rounded-4 mb-3 bg-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 500px;">
                                <div class="input-group input-group-sm flex-grow-1">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-start-0 shadow-none" id="pmodal_search_input" placeholder="ค้นหา HN, CID, AN, ชื่อ-สกุล, REP...">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 text-nowrap" id="pmodal_btn_search">ค้นหา</button>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border px-2.5 py-1.5" id="pmodal_stats_badge">
                                    <i class="bi bi-people me-1 text-primary"></i> <strong>0</strong> รายการ (0.00 บาท)
                                </span>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-semibold" id="pmodal_btn_export">
                                    <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Patient Table Card --}}
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                        <table class="table table-hover table-bordered align-middle mb-0 small" id="pmodal_table" style="width: 100%;">
                            <thead class="table-light sticky-top" style="z-index: 5;">
                                <tr>
                                    <th class="text-center" width="4%">#</th>
                                    <th class="text-center" width="7%">แผนก</th>
                                    <th class="text-center" width="8%">REP</th>
                                    <th class="text-center" width="9%">HN</th>
                                    <th class="text-center" width="13%">CID / AN</th>
                                    <th class="text-start" width="18%">ชื่อ - สกุล</th>
                                    <th class="text-center" width="11%">วันรับบริการ</th>
                                    <th class="text-end" width="11%">ยอดชดเชย (บาท)</th>
                                    <th class="text-start" width="11%">หมายเหตุ</th>
                                    <th class="text-center" width="8%">ใบเสร็จ</th>
                                </tr>
                            </thead>
                            <tbody id="pmodal_table_body">
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <div class="mt-2 fw-semibold">กำลังโหลดข้อมูลผู้ป่วย...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    {{-- Pagination Footer --}}
                    <div class="card-footer bg-white border-top d-flex flex-wrap align-items-center justify-content-between p-3 gap-2">
                        <div class="text-muted small" id="pmodal_page_info">แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ</div>
                        <div class="d-flex align-items-center gap-1" id="pmodal_pagination_controls"></div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer bg-white border-top d-flex justify-content-between p-3">
                <a href="javascript:void(0);" id="pmodal_fullpage_link" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> เปิดหน้ารายละเอียดแบบเต็มจอ (New Tab)
                </a>
                <button type="button" class="btn btn-sm btn-light px-4 rounded-pill fw-bold border" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        var currentModalType = '';
        var currentModalDep = '';
        var currentModalRoundNo = '';
        var currentModalFilename = '';

        $(document).on('click', '.btn-view-patient-detail', function() {
            var type = $(this).data('type') || '';
            var dep = $(this).data('dep') || '';
            var roundNo = $(this).data('round') || '';
            var filename = $(this).data('filename') || '';
            var count = $(this).data('count');
            var amount = $(this).data('amount');
            var receive = $(this).data('receive');
            var date = $(this).data('date');

            currentModalType = type;
            currentModalDep = dep;
            currentModalRoundNo = roundNo;
            currentModalFilename = filename;

            $('#pmodal_search_input').val('');
            $('#pmodal_round_badge').text(roundNo ? 'งวด ' + roundNo : filename);
            $('#pmodal_round_no').text(roundNo || '-');
            $('#pmodal_filename').text(filename || '-').attr('title', filename || '-');

            if (amount) {
                $('#pmodal_total_amount').text(amount + ' บาท');
            } else {
                $('#pmodal_total_amount').text('0.00 บาท');
            }
            if (count) {
                $('#pmodal_total_count_hint').text('(' + Number(count).toLocaleString() + ' รายการ)');
            } else {
                $('#pmodal_total_count_hint').text('(0 รายการ)');
            }
            if (receive) {
                $('#pmodal_receipt_display').html('<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> ' + receive + '</span>');
            } else {
                $('#pmodal_receipt_display').html('<span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1"><i class="bi bi-clock-history me-1"></i> ยังไม่ออก</span>');
            }

            var modalEl = document.getElementById('patientDetailModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else if (window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else if (typeof $ !== 'undefined' && typeof $(modalEl).modal === 'function') {
                    $(modalEl).modal('show');
                }
            }

            loadUniversalPatientModalData(type, dep, roundNo, filename, 1, '');
        });

        window.loadUniversalPatientModalData = function(type, dep, roundNo, filename, page, search) {
            page = page || 1;
            search = search !== undefined ? search : ($('#pmodal_search_input').val() || '');

            $('#pmodal_table_body').html(`
                <tr>
                    <td colspan="10" class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 fw-semibold">กำลังโหลดข้อมูลผู้ป่วย...</div>
                    </td>
                </tr>
            `);

            var url = "{{ route('import.stm.patient_detail') }}?type=" + encodeURIComponent(type) +
                      "&dep=" + encodeURIComponent(dep) +
                      "&round_no=" + encodeURIComponent(roundNo) +
                      "&stm_filename=" + encodeURIComponent(filename) +
                      "&page=" + page;
            if (search) {
                url += "&search=" + encodeURIComponent(search);
            }

            fetch(url, {
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                    "Accept": "application/json"
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    var info = res.round_info;
                    var st = res.stats;

                    if (res.title) {
                        $('#pmodal_subtitle').text(res.title);
                    }
                    if (res.fullpage_url) {
                        $('#pmodal_fullpage_link').attr('href', res.fullpage_url).removeClass('d-none');
                    } else {
                        $('#pmodal_fullpage_link').addClass('d-none');
                    }

                    $('#pmodal_round_badge').text('งวด ' + (info.round_no || roundNo));
                    $('#pmodal_round_no').text(info.round_no || roundNo || '-');
                    $('#pmodal_filename').text(info.stm_filename || filename || '-').attr('title', info.stm_filename || filename || '-');
                    $('#pmodal_total_amount').text(info.total_amount_formatted + ' บาท');
                    $('#pmodal_total_count_hint').text('(' + Number(info.total_count).toLocaleString() + ' รายการ)');

                    if (info.receive_no) {
                        var recHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> ' + info.receive_no;
                        if (info.receipt_date_thai && info.receipt_date_thai !== '-') {
                            recHtml += ' (' + info.receipt_date_thai + ')';
                        }
                        recHtml += '</span>';
                        $('#pmodal_receipt_display').html(recHtml);
                    } else {
                        $('#pmodal_receipt_display').html('<span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1"><i class="bi bi-clock-history me-1"></i> ยังไม่ออก</span>');
                    }

                    $('#pmodal_stats_badge').html('<i class="bi bi-people me-1 text-primary"></i> <strong>' + Number(st.total_count).toLocaleString() + '</strong> รายการ (' + st.total_amount_formatted + ' บาท)');

                    // Rows
                    if (!res.data || res.data.length === 0) {
                        $('#pmodal_table_body').html(`
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 mb-1 d-block opacity-40"></i>
                                    <div class="fw-semibold text-dark mt-2">ไม่พบรายการผู้ป่วยในเงื่อนไขที่เลือก</div>
                                </td>
                            </tr>
                        `);
                    } else {
                        var html = '';
                        var startIdx = ((res.pagination.current_page - 1) * res.pagination.per_page) + 1;
                        res.data.forEach(function(d, idx) {
                            var depBadge = '<span class="badge bg-light text-dark border">' + (d.dep || 'OPD') + '</span>';
                            var cidDisplay = (d.cid_or_an && d.cid_or_an !== '-') ? '<span class="font-monospace text-dark">' + d.cid_or_an + '</span>' : '<span class="text-muted opacity-50">-</span>';
                            var hnDisplay = (d.hn && d.hn !== '-') ? '<span class="fw-bold font-monospace text-primary">' + d.hn + '</span>' : '<span class="text-muted opacity-50">-</span>';
                            var ptNameDisplay = (d.pt_name && d.pt_name !== '-') ? '<span class="fw-semibold text-dark">' + d.pt_name + '</span>' : '<span class="text-muted opacity-50">-</span>';
                            var recDisplay = d.receive_no ? '<span class="badge bg-success-subtle text-success border border-success-subtle">' + d.receive_no + '</span>' : '<span class="text-muted small">-</span>';

                            html += `
                                <tr>
                                    <td class="text-center text-muted small">${startIdx + idx}</td>
                                    <td class="text-center">${depBadge}</td>
                                    <td class="text-center font-monospace small">${d.repno || '-'}</td>
                                    <td class="text-center">${hnDisplay}</td>
                                    <td class="text-center font-monospace">${cidDisplay}</td>
                                    <td class="text-start">${ptNameDisplay}</td>
                                    <td class="text-center small">${d.datetimeadm_thai}</td>
                                    <td class="text-end fw-bold text-success font-monospace">${d.amount_formatted}</td>
                                    <td class="text-start small text-muted">${d.note || '-'}</td>
                                    <td class="text-center">${recDisplay}</td>
                                </tr>
                            `;
                        });
                        $('#pmodal_table_body').html(html);
                    }

                    // Pagination
                    var pg = res.pagination;
                    var from = ((pg.current_page - 1) * pg.per_page) + 1;
                    var to = Math.min(pg.current_page * pg.per_page, pg.total);
                    $('#pmodal_page_info').text(`แสดง ${pg.total > 0 ? from : 0} ถึง ${to} จากทั้งหมด ${pg.total} รายการ`);

                    var pagHtml = '';
                    if (pg.last_page > 1) {
                        pagHtml += `<button class="btn btn-xs btn-outline-secondary ${pg.current_page === 1 ? 'disabled' : ''}" onclick="changeUniversalPatientModalPage(${pg.current_page - 1})"><i class="bi bi-chevron-left"></i></button>`;
                        for (var i = Math.max(1, pg.current_page - 2); i <= Math.min(pg.last_page, pg.current_page + 2); i++) {
                            pagHtml += `<button class="btn btn-xs ${i === pg.current_page ? 'btn-primary' : 'btn-outline-secondary'}" onclick="changeUniversalPatientModalPage(${i})">${i}</button>`;
                        }
                        pagHtml += `<button class="btn btn-xs btn-outline-secondary ${pg.current_page === pg.last_page ? 'disabled' : ''}" onclick="changeUniversalPatientModalPage(${pg.current_page + 1})"><i class="bi bi-chevron-right"></i></button>`;
                    }
                    $('#pmodal_pagination_controls').html(pagHtml);
                } else {
                    $('#pmodal_table_body').html(`
                        <tr>
                            <td colspan="10" class="text-center py-5 text-danger">
                                <i class="bi bi-exclamation-triangle-fill fs-3 mb-2 d-block"></i>
                                ${res.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'}
                            </td>
                        </tr>
                    `);
                }
            })
            .catch(err => {
                $('#pmodal_table_body').html(`
                    <tr>
                        <td colspan="10" class="text-center py-5 text-danger">
                            <i class="bi bi-x-circle-fill fs-3 mb-2 d-block"></i>
                            เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์
                        </td>
                    </tr>
                `);
            });
        };

        window.changeUniversalPatientModalPage = function(page) {
            var search = $('#pmodal_search_input').val();
            loadUniversalPatientModalData(currentModalType, currentModalDep, currentModalRoundNo, currentModalFilename, page, search);
        };

        $(document).on('click', '#pmodal_btn_search', function() {
            loadUniversalPatientModalData(currentModalType, currentModalDep, currentModalRoundNo, currentModalFilename, 1, $('#pmodal_search_input').val());
        });

        $(document).on('keypress', '#pmodal_search_input', function(e) {
            if (e.which === 13) {
                loadUniversalPatientModalData(currentModalType, currentModalDep, currentModalRoundNo, currentModalFilename, 1, $(this).val());
            }
        });

        $(document).on('click', '#pmodal_btn_export', function() {
            if (!currentModalRoundNo && !currentModalFilename) return;
            var search = $('#pmodal_search_input').val();
            var exportUrl = "{{ route('import.stm.patient_export') }}?type=" + encodeURIComponent(currentModalType) +
                            "&dep=" + encodeURIComponent(currentModalDep) +
                            "&round_no=" + encodeURIComponent(currentModalRoundNo) +
                            "&stm_filename=" + encodeURIComponent(currentModalFilename);
            if (search) {
                exportUrl += "&search=" + encodeURIComponent(search);
            }
            window.location.href = exportUrl;
        });
    })();
</script>
@endpush
