<!-- Modal: F16 KTB Health Platform Export Center -->
<style>
    .f16-ktb-sortable-th {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
        white-space: nowrap;
        position: relative;
    }
    .f16-ktb-sortable-th:hover {
        background-color: #e2e8f0 !important;
    }
    .f16-ktb-table-container {
        max-height: 320px;
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #fff;
    }
    .f16-ktb-table-container thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8fafc !important;
        color: #0f172a !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        border-bottom: 2px solid #94a3b8 !important;
        padding: 7px 10px !important;
    }
    .f16-ktb-table-container td {
        padding: 6px 10px !important;
        font-size: 0.8rem !important;
        white-space: nowrap;
    }
    .nav-pills-ktb .nav-link {
        font-size: 0.74rem;
        padding: 4px 4px;
        border-radius: 6px;
        color: #334155;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
        min-width: 52px;
        text-align: center;
    }
    .nav-pills-ktb .nav-link:hover {
        background-color: #f1f5f9;
    }
    .nav-pills-ktb .nav-link.active {
        background-color: #0284c7 !important;
        color: #fff !important;
        font-weight: 700;
        border-color: #0284c7 !important;
        box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);
    }
    .f16-badge-count {
        font-size: 0.65rem !important;
        padding: 2px 6px !important;
        font-weight: 700;
        display: inline-block;
    }
    .badge-count-success {
        background-color: #10b981 !important;
        color: #ffffff !important;
    }
    .badge-count-zero {
        background-color: #f8fafc !important;
        color: #64748b !important;
        border: 1px solid #cbd5e1 !important;
    }
</style>

<div class="modal fade" id="f16KtbExportModal" tabindex="-1" aria-labelledby="f16KtbExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header with KTB Gradient Theme -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #034488 0%, #0077b6 50%, #00a2e8 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="bi bi-cloud-arrow-up-fill fs-4" style="color: #0077b6;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="f16KtbExportModalLabel">
                            ส่งออกข้อมูลมาตรฐาน 16 แฟ้ม (Krungthai Digital Health Platform)
                        </h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-primary fw-bold" id="f16KtbModalActivityTitle">[S01] คัดกรองสุขภาพกาย/จิต (SCR)</span>
                            <span class="badge text-white fw-bold" style="background-color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);" id="f16KtbModalSelectedBadge">0 รายการที่เลือก</span>
                            <span class="text-white-50 small">HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Loading State -->
                <div id="f16KtbLoadingOverlay" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3.2rem; height: 3.2rem;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลและเตรียมข้อมูล 16 แฟ้มจาก HOSxP ตามสเปก KTB...</h6>
                    <p class="text-muted small">ระบบกำลังจัดเตรียมโครงสร้างแฟ้ม INS, PAT, OPD, ODX, OOP, CHT, CHA, ADP, DRU, LABFU กรุณารอสักครู่</p>
                </div>

                <!-- Main Content Area -->
                <div id="f16KtbMainContent" style="display: none;">
                    <!-- 17 Tabs Bar -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 bg-white rounded">
                            <ul class="nav nav-pills nav-pills-ktb nav-fill flex-wrap gap-1" id="f16KtbTabs" role="tablist">
                                @php
                                    $fileTabs = [
                                        ['key' => 'INS', 'name' => 'INS', 'desc' => 'สิทธิการรักษา'],
                                        ['key' => 'PAT', 'name' => 'PAT', 'desc' => 'ประวัติผู้ป่วย'],
                                        ['key' => 'OPD', 'name' => 'OPD', 'desc' => 'การรับบริการ OPD'],
                                        ['key' => 'ORF', 'name' => 'ORF', 'desc' => 'ส่งต่อผู้ป่วยนอก'],
                                        ['key' => 'ODX', 'name' => 'ODX', 'desc' => 'การวินิจฉัยโรค OPD'],
                                        ['key' => 'OOP', 'name' => 'OOP', 'desc' => 'หัตถการ OPD'],
                                        ['key' => 'IPD', 'name' => 'IPD', 'desc' => 'ผู้ป่วยใน'],
                                        ['key' => 'IRF', 'name' => 'IRF', 'desc' => 'ส่งต่อผู้ป่วยใน'],
                                        ['key' => 'IDX', 'name' => 'IDX', 'desc' => 'การวินิจฉัย IPD'],
                                        ['key' => 'IOP', 'name' => 'IOP', 'desc' => 'หัตถการ IPD'],
                                        ['key' => 'CHT', 'name' => 'CHT', 'desc' => 'สรุปค่าใช้จ่าย'],
                                        ['key' => 'CHA', 'name' => 'CHA', 'desc' => 'ค่ารักษา 20 หมวด'],
                                        ['key' => 'AER', 'name' => 'AER', 'desc' => 'อุบัติเหตุฉุกเฉิน'],
                                        ['key' => 'ADP', 'name' => 'ADP', 'desc' => 'บริการส่งเสริม/PPFS'],
                                        ['key' => 'LVD', 'name' => 'LVD', 'desc' => 'วันลากลับบ้าน'],
                                        ['key' => 'DRU', 'name' => 'DRU', 'desc' => 'การสั่งใช้ยา'],
                                        ['key' => 'LABFU', 'name' => 'LABFU', 'desc' => 'ผลตรวจทางห้องปฏิบัติการ']
                                    ];
                                @endphp
                                @foreach($fileTabs as $tab)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link text-center {{ $tab['key'] === 'ADP' ? 'active' : '' }}" 
                                                id="f16-ktb-tab-{{ $tab['key'] }}" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#f16-ktb-pane-{{ $tab['key'] }}" 
                                                type="button" 
                                                role="tab"
                                                onclick="switchKtbFileTab('{{ $tab['key'] }}')">
                                            <div class="fw-bold">{{ $tab['name'] }}</div>
                                            <span class="badge rounded-pill f16-badge-count badge-count-zero mt-1" id="f16-ktb-badge-{{ $tab['key'] }}">0</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Contents Container -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary fs-6 fw-bold" id="f16KtbCurrentFileTitle">ADP.txt</span>
                                <span class="text-muted small" id="f16KtbCurrentFileDesc">ข้อมูลรายการบริการส่งเสริมป้องกันโรค (PPFS)</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group input-group-sm" style="width: 220px;">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" id="f16KtbSearchInput" placeholder="ค้นหาในตาราง..." onkeyup="filterKtbCurrentTable()">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="tab-content" id="f16KtbTabContent">
                                @foreach($fileTabs as $tab)
                                    <div class="tab-pane fade {{ $tab['key'] === 'ADP' ? 'show active' : '' }}" 
                                         id="f16-ktb-pane-{{ $tab['key'] }}" 
                                         role="tabpanel">
                                        <div class="f16-ktb-table-container">
                                            <table class="table table-sm table-hover table-striped mb-0 w-100" id="f16-ktb-table-{{ $tab['key'] }}">
                                                <thead>
                                                    <tr id="f16-ktb-thead-{{ $tab['key'] }}">
                                                        <th>กำลังโหลด...</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="f16-ktb-tbody-{{ $tab['key'] }}">
                                                    <tr>
                                                        <td class="text-center text-muted py-4">ไม่มีข้อมูล</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Action Result / Progress Section -->
                    <div class="mt-3 p-3 bg-white rounded border d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-dark"><i class="bi bi-info-circle text-primary me-1"></i> โครงสร้างไฟล์:</span>
                            <span class="text-muted small ms-1">รูปแบบ 16 แฟ้ม e-Claim สปสช. พ.ศ. ๒๕๖๔ ตามคู่มือ KTB Health Platform 1 OCT 2025</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-1"></i> ปิด
                            </button>
                            <button type="button" class="btn btn-primary px-4 shadow" id="btnKtbDownloadZip" onclick="executeKtbZipExport()">
                                <i class="bi bi-file-earmark-zip-fill me-1"></i> ดาวน์โหลดไฟล์ 16 แฟ้ม (KTB .ZIP)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var globalKtbF16Data = {};
    var currentKtbTab = 'ADP';
    var currentKtbKeys = [];
    var currentKtbActivity = 'S01';
    var currentKtbActivityTitle = 'ชุดบริการตรวจประเมินสุขภาพกาย/จิต (SCR)';

    /**
     * ฟังก์ชันเปิด Modal ส่งออก 16 แฟ้ม KTB
     */
    window.openF16KtbModal = function(keys, activityCode, activityTitle) {
        activityCode = activityCode || 'S01';
        activityTitle = activityTitle || '';

        if (!keys || keys.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกรายการ',
                    text: 'กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม KTB',
                    confirmButtonText: 'ตกลง'
                });
            } else {
                alert('กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม KTB');
            }
            return;
        }

        currentKtbKeys = keys;
        currentKtbActivity = activityCode;
        currentKtbActivityTitle = activityTitle || ('[' + activityCode + ']');

        $('#f16KtbModalActivityTitle').text(currentKtbActivityTitle);
        $('#f16KtbModalSelectedBadge').text(keys.length + ' รายการที่เลือก');

        // Reset state
        $('#f16KtbLoadingOverlay').show();
        $('#f16KtbMainContent').hide();
        $('#btnKtbDownloadZip').prop('disabled', false).html('<i class="bi bi-file-earmark-zip-fill me-1"></i> ดาวน์โหลดไฟล์ 16 แฟ้ม (KTB .ZIP)');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('f16KtbExportModal'));
            modal.show();
        } else {
            $('#f16KtbExportModal').modal('show');
        }

        // Fetch preview data
        $.ajax({
            url: "{{ url('ktb/f16_preview') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keys: currentKtbKeys,
                activity_code: currentKtbActivity
            },
            success: function (res) {
                if (res.success) {
                    globalKtbF16Data = res.files || {};
                    renderAllKtbTables(globalKtbF16Data);
                    $('#f16KtbLoadingOverlay').hide();
                    $('#f16KtbMainContent').fadeIn(200);
                } else {
                    showKtbError(res.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            },
            error: function (xhr) {
                showKtbError(xhr.responseJSON?.message || 'ไม่สามารถเชื่อมต่อ Server ได้');
            }
        });
    };

    window.showKtbError = function(msg) {
        $('#f16KtbLoadingOverlay').html(`
            <div class="text-danger py-4">
                <i class="bi bi-exclamation-triangle fs-1 mb-2"></i>
                <h6 class="fw-bold">เกิดข้อผิดพลาด</h6>
                <p class="small text-muted mb-3">${msg}</p>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        `);
    };

    window.renderAllKtbTables = function(data) {
        for (var fileKey in data) {
            if (!data.hasOwnProperty(fileKey)) continue;
            var rows = data[fileKey];
            var count = rows.length;
            $('#f16-ktb-badge-' + fileKey).text(count);

            if (count > 0) {
                $('#f16-ktb-badge-' + fileKey).removeClass('badge-count-zero bg-secondary').addClass('badge-count-success');
                var headers = Object.keys(rows[0]);
                
                // Render thead
                var thHtml = '';
                for (var i = 0; i < headers.length; i++) {
                    thHtml += '<th class="f16-ktb-sortable-th">' + headers[i] + '</th>';
                }
                $('#f16-ktb-thead-' + fileKey).html(thHtml);

                // Render tbody
                var trHtml = '';
                var maxRows = Math.min(rows.length, 200);
                for (var j = 0; j < maxRows; j++) {
                    var r = rows[j];
                    trHtml += '<tr>';
                    for (var k = 0; k < headers.length; k++) {
                        var h = headers[k];
                        var val = r[h] !== null && r[h] !== undefined ? r[h] : '';
                        trHtml += '<td>' + escapeKtbHtml(val) + '</td>';
                    }
                    trHtml += '</tr>';
                }
                $('#f16-ktb-tbody-' + fileKey).html(trHtml);
            } else {
                $('#f16-ktb-badge-' + fileKey).removeClass('badge-count-success bg-success').addClass('badge-count-zero');
                $('#f16-ktb-thead-' + fileKey).html('<th>ไม่มีข้อมูล</th>');
                $('#f16-ktb-tbody-' + fileKey).html('<tr><td class="text-center text-muted py-3">ไม่มีรายการในแฟ้มนี้</td></tr>');
            }
        }
        switchKtbFileTab(currentKtbTab);
    };

    window.switchKtbFileTab = function(key) {
        currentKtbTab = key;
        $('#f16KtbCurrentFileTitle').text(key + '.txt');
        var tabDescMap = {
            'INS': 'ข้อมูลสิทธิการรักษาพยาบาล',
            'PAT': 'ข้อมูลประวัติผู้ป่วยและที่อยู่',
            'OPD': 'ข้อมูลการตรวจรักษา OPD และสัญญาณชีพ',
            'ORF': 'ข้อมูลการส่งต่อผู้ป่วยนอก (Refer Out)',
            'ODX': 'ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก (ICD-10)',
            'OOP': 'ข้อมูลหัตถการผู้ป่วยนอก (ICD-9)',
            'IPD': 'ข้อมูลการรับบริการผู้ป่วยใน',
            'IRF': 'ข้อมูลการส่งต่อผู้ป่วยใน',
            'IDX': 'ข้อมูลการวินิจฉัยโรคผู้ป่วยใน',
            'IOP': 'ข้อมูลหัตถการผ่าตัดผู้ป่วยใน',
            'CHT': 'ข้อมูลสรุปยอดค่ารักษาพยาบาล',
            'CHA': 'ข้อมูลสรุปค่าบริการแจกแจงตามหมวด 20 หมวด',
            'AER': 'ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ',
            'ADP': 'ข้อมูลบริการเสริม/อุปกรณ์/ส่งเสริมป้องกันโรค (PPFS KTB)',
            'LVD': 'ข้อมูลการลากลับบ้าน',
            'DRU': 'ข้อมูลรายการสั่งใช้ยาและเวชภัณฑ์',
            'LABFU': 'ข้อมูลผลตรวจทางห้องปฏิบัติการติดตามการรักษา'
        };
        $('#f16KtbCurrentFileDesc').text(tabDescMap[key] || '');
        filterKtbCurrentTable();
    };

    window.filterKtbCurrentTable = function() {
        var query = ($('#f16KtbSearchInput').val() || '').toLowerCase();
        var rows = $('#f16-ktb-tbody-' + currentKtbTab + ' tr');
        rows.each(function () {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(query) > -1);
        });
    };

    function escapeKtbHtml(str) {
        if (typeof str !== 'string') return str;
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * สั่งสร้างและดาวน์โหลดไฟล์ Zip
     */
    window.executeKtbZipExport = function() {
        if (!currentKtbKeys || currentKtbKeys.length === 0) return;

        var btn = $('#btnKtbDownloadZip');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังบีบอัดไฟล์ Zip...');

        $.ajax({
            url: "{{ url('ktb/f16_export') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keys: currentKtbKeys,
                activity_code: currentKtbActivity
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="bi bi-file-earmark-zip-fill me-1"></i> ดาวน์โหลดไฟล์ 16 แฟ้ม (KTB .ZIP)');
                if (res.success && res.download_url) {
                    window.location.href = res.download_url;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'ส่งออกไฟล์ 16 แฟ้ม สำเร็จ!',
                            html: 'ดาวน์โหลดไฟล์ <strong>' + res.zip_file_name + '</strong> เรียบร้อยแล้ว<br><small class="text-muted">สามารถนำไฟล์ Zip นี้ไปอัปโหลดขึ้นระบบ Krungthai Digital Health Platform ได้ทันที</small>',
                            confirmButtonText: 'ตกลง'
                        });
                    }
                } else {
                    alert(res.message || 'ไม่สามารถส่งออกไฟล์ได้');
                }
            },
            error: function (xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-file-earmark-zip-fill me-1"></i> ดาวน์โหลดไฟล์ 16 แฟ้ม (KTB .ZIP)');
                alert(xhr.responseJSON?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ Server');
            }
        });
    };
</script>
