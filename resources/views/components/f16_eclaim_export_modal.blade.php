<!-- Modal: F16 e-Claim Export Center (Reusable 16 Files Component) -->
<div class="modal fade" id="f16EclaimExportModal" tabindex="-1" aria-labelledby="f16EclaimExportModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header with e-Claim Teal Theme -->
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0b7379 0%, #0e939a 50%, #17b7be 100%);">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-up-right fs-4 text-warning"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="f16EclaimExportModalLabel">
                            ส่งออกข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)
                        </h5>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-white text-dark fw-bold" id="f16ModalClaimTitle">OFC (กรมบัญชีกลาง)</span>
                            <span class="badge text-white fw-bold" style="background-color: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4);" id="f16ModalSelectedBadge">0 รายการที่เลือก</span>
                            <span class="text-white-50 small" id="f16ModalHcodeText">HCODE: {{ \App\Services\LicenseVerificationService::getHcode() }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- Loading State -->
                <div id="f16LoadingOverlay" class="text-center py-5">
                    <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem; color: #0e939a !important;" role="status"></div>
                    <h6 class="fw-bold text-dark mb-1">กำลังประมวลผลและดึงข้อมูล 16 แฟ้มจาก HOSxP...</h6>
                    <p class="text-muted small">ระบบกำลังเตรียมไฟล์ INS, PAT, OPD, DRU, CHA, CHT, ADP ฯลฯ กรุณารอสักครู่</p>
                </div>

                <!-- Main Content Area (Hidden while loading) -->
                <div id="f16MainContent" style="display: none;">
                    <!-- 11 OP Tabs Bar -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-2 bg-white rounded">
                            <ul class="nav nav-pills nav-fill flex-wrap gap-1" id="f16Tabs" role="tablist">
                                @php
                                    $fileTabs = [
                                        ['key' => 'INS', 'name' => 'INS', 'desc' => 'สิทธิ'],
                                        ['key' => 'PAT', 'name' => 'PAT', 'desc' => 'ผู้ป่วย'],
                                        ['key' => 'OPD', 'name' => 'OPD', 'desc' => 'ผู้ป่วยนอก'],
                                        ['key' => 'ORF', 'name' => 'ORF', 'desc' => 'ส่งต่อ'],
                                        ['key' => 'ODX', 'name' => 'ODX', 'desc' => 'วินิจฉัย'],
                                        ['key' => 'OOP', 'name' => 'OOP', 'desc' => 'หัตถการ'],
                                        ['key' => 'DRU', 'name' => 'DRU', 'desc' => 'ยา'],
                                        ['key' => 'CHA', 'name' => 'CHA', 'desc' => '16 หมวด'],
                                        ['key' => 'CHT', 'name' => 'CHT', 'desc' => 'การเงิน'],
                                        ['key' => 'AER', 'name' => 'AER', 'desc' => 'อุบัติเหตุ'],
                                        ['key' => 'ADP', 'name' => 'ADP', 'desc' => 'บริการเสริม'],
                                    ];
                                @endphp

                                @foreach($fileTabs as $index => $tab)
                                <li class="nav-item f16-tab-item" role="presentation">
                                    <button class="nav-link text-center px-1 py-1 {{ $index === 0 ? 'active' : '' }}" 
                                            id="f16-tab-{{ $tab['key'] }}" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#f16-pane-{{ $tab['key'] }}" 
                                            type="button" 
                                            role="tab"
                                            style="font-size: 0.78rem; min-width: 65px;">
                                        <div class="fw-bold">{{ $tab['name'] }}</div>
                                        <span class="badge rounded-pill bg-secondary text-white f16-badge-count mt-1" id="badge-count-{{ $tab['key'] }}" style="font-size: 0.68rem;">0</span>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <!-- Tab Contents / Text Previews -->
                    <div class="tab-content" id="f16TabPanes">
                        @foreach($fileTabs as $index => $tab)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="f16-pane-{{ $tab['key'] }}" role="tabpanel">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-dark text-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-file-earmark-text text-warning"></i>
                                        <span class="fw-bold">{{ $tab['name'] }}.txt</span>
                                        <span class="text-white-50 small">({{ $tab['desc'] }})</span>
                                    </div>
                                    <span class="badge bg-secondary" id="pane-count-{{ $tab['key'] }}">0 แถว</span>
                                </div>
                                <div class="card-body p-0">
                                    <pre class="m-0 p-3 bg-dark text-info" id="preview-content-{{ $tab['key'] }}" style="max-height: 280px; min-height: 180px; overflow-y: auto; font-family: 'Consolas', 'Courier New', monospace; font-size: 0.8rem; line-height: 1.4; white-space: pre; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">(ไม่มีข้อมูล)</pre>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Export Folder Options -->
                    <div class="card border-0 shadow-sm mt-3 bg-white">
                        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="f16CreateSubfolderSwitch" checked>
                                <label class="form-check-label fw-bold text-dark small cursor-pointer" for="f16CreateSubfolderSwitch">
                                    <i class="bi bi-folder-plus text-primary me-1"></i>สร้างโฟลเดอร์ย่อยตามสิทธิและวันเวลาอัตโนมัติ 
                                    <span class="text-muted fw-normal" id="f16SubfolderPreviewText">(เช่น F16_OFC_25690825_1800)</span>
                                </label>
                            </div>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>เขียนไฟล์ .txt ทั้ง 11 แฟ้มลงโฟลเดอร์โดยตรง
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <span id="f16ExportProgressText" class="fw-bold text-primary small"></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> ปิดหน้าต่าง
                    </button>
                    <button type="button" class="btn text-white px-4 fw-bold shadow-sm" id="btnExecuteF16Export" onclick="executeF16DirectoryExport()" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;">
                        <i class="bi bi-folder-check me-1"></i> <span id="btnExecuteF16ExportText">เลือกโฟลเดอร์และส่งออก (11 แฟ้ม OP .txt)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global State for F16 e-Claim Export
    window._f16ExportState = {
        vns: [],
        claimCode: 'OFC',
        claimTitle: 'OP-OFC (ข้าราชการ)',
        fullFiles: {},
        counts: {},
        subfolderName: ''
    };

    /**
     * ฟังก์ชันเปิด Modal ส่งออก 16 แฟ้ม (e-Claim)
     * @param {Object} config { vns: ['690701130818', ...], claimCode: 'OFC', claimTitle: 'OP-OFC (ข้าราชการ)' }
     */
    window.openF16EclaimExportModal = function(config) {
        config = config || {};
        const vns = config.vns || [];
        const claimCode = config.claimCode || 'OFC';
        const claimTitle = config.claimTitle || 'OP-OFC (ข้าราชการ)';

        if (!vns || vns.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'ยังไม่ได้เลือกรายการ',
                    text: 'กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม',
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#0e939a'
                });
            } else {
                alert('กรุณาติ๊กเลือกรายการในตารางอย่างน้อย 1 รายการก่อนส่งออก 16 แฟ้ม');
            }
            return;
        }

        // Save State
        window._f16ExportState.vns = vns;
        window._f16ExportState.claimCode = claimCode;
        window._f16ExportState.claimTitle = claimTitle;
        window._f16ExportState.fullFiles = {};
        window._f16ExportState.counts = {};

        // Update Header
        $('#f16ModalClaimTitle').text(claimTitle);
        $('#f16ModalSelectedBadge').text(vns.length + ' รายการที่เลือก');
        $('#f16ExportProgressText').text('');

        // Reset UI to Loading State
        $('#f16LoadingOverlay').show();
        $('#f16MainContent').hide();
        $('#btnExecuteF16Export').prop('disabled', true);

        // Show Modal (Compatible with both Bootstrap 4 and Bootstrap 5)
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('f16EclaimExportModal')).show();
            } catch (e) {
                $('#f16EclaimExportModal').modal('show');
            }
        } else {
            $('#f16EclaimExportModal').modal('show');
        }

        // AJAX Request for Preview & Generation
        $.ajax({
            url: '{{ route("f16_eclaim_export.preview") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vns: vns,
                claim_code: claimCode
            },
            success: function(res) {
                $('#f16LoadingOverlay').hide();
                $('#f16MainContent').show();
                $('#btnExecuteF16Export').prop('disabled', false);

                if (res.status === 'success') {
                    const counts = res.counts || {};
                    const snippets = res.snippets || {};

                    // Update Tab Badges and Snippets (11 OP Files)
                    const keys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'DRU', 'CHA', 'CHT', 'AER', 'ADP'];
                    keys.forEach(function(k) {
                        const count = counts[k] || 0;
                        const badgeEl = $('#badge-count-' + k);
                        const paneCountEl = $('#pane-count-' + k);
                        const previewEl = $('#preview-content-' + k);

                        badgeEl.text(count);
                        paneCountEl.text(count + ' แถว');

                        if (count > 0) {
                            badgeEl.removeClass('bg-secondary').addClass('text-white').css('background-color', '#0e939a');
                            previewEl.text(snippets[k] || '(ไม่มีข้อมูล)');
                        } else {
                            badgeEl.removeClass('text-white').addClass('bg-secondary').css('background-color', '');
                            previewEl.text('(ไม่มีข้อมูลสำหรับแฟ้มนี้)');
                        }
                    });

                    // Activate First Tab
                    $('#f16-tab-INS').trigger('click');
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('เกิดข้อผิดพลาด', res.message || 'ไม่สามารถประมวลผล 16 แฟ้มได้', 'error');
                    } else {
                        alert(res.message || 'ไม่สามารถประมวลผล 16 แฟ้มได้');
                    }
                }
            },
            error: function(xhr) {
                $('#f16LoadingOverlay').hide();
                let errMsg = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('ข้อผิดพลาด', errMsg, 'error');
                } else {
                    alert(errMsg);
                }
            }
        });
    };

    /**
     * บันทึกไฟล์ .txt ทั้ง 11 แฟ้ม OP ลงโฟลเดอร์โดยตรงผ่าน File System Access API
     */
    window.executeF16DirectoryExport = async function() {
        const state = window._f16ExportState;
        if (!state.vns || state.vns.length === 0) {
            alert('ไม่พบรายการที่เลือก');
            return;
        }

        // Check Browser Support for Directory Picker
        if (!('showDirectoryPicker' in window)) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'เบราว์เซอร์ไม่รองรับ Directory Picker',
                    html: 'ฟังก์ชันบันทึกลงโฟลเดอร์โดยตรงรองรับบน <b>Google Chrome</b> หรือ <b>Microsoft Edge</b> กรุณาเปิดใช้งานผ่าน Chrome / Edge เพื่อความสะดวกในการใช้งานครับ',
                    confirmButtonText: 'เข้าใจแล้ว'
                });
            } else {
                alert('ฟังก์ชันบันทึกลงโฟลเดอร์โดยตรงรองรับบน Google Chrome หรือ Microsoft Edge');
            }
            return;
        }

        let dirHandle;
        try {
            // 1. Open Native Folder Selection Dialog
            dirHandle = await window.showDirectoryPicker({
                mode: 'readwrite',
                startIn: 'downloads'
            });
        } catch (err) {
            // User cancelled folder selection
            if (err.name !== 'AbortError') {
                console.error('Directory Picker Error:', err);
            }
            return;
        }

        // Show Exporting Indicator
        const btn = $('#btnExecuteF16Export');
        const originalBtnHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>กำลังบันทึกไฟล์...');
        $('#f16ExportProgressText').text('⏳ กำลังดึงข้อมูลเนื้อหาเต็มทั้ง 11 แฟ้ม...');

        // 2. Fetch Full File Content from Server
        $.ajax({
            url: '{{ route("f16_eclaim_export.export_data") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vns: state.vns,
                claim_code: state.claimCode
            },
            success: async function(res) {
                if (res.status === 'success') {
                    const files = res.files || {};
                    const counts = res.counts || {};
                    const subfolderName = res.subfolder_name || ('F16_' + state.claimCode + '_' + Date.now());
                    const createSubfolder = $('#f16CreateSubfolderSwitch').is(':checked');

                    try {
                        // 3. Determine Target Directory
                        let targetDir = dirHandle;
                        if (createSubfolder) {
                            targetDir = await dirHandle.getDirectoryHandle(subfolderName, { create: true });
                        }

                        $('#f16ExportProgressText').text('⏳ กำลังเขียนไฟล์ .txt ทั้ง 11 แฟ้มลงโฟลเดอร์...');

                        // 4. Write each of the 11 OP .txt files
                        const fileKeys = ['INS', 'PAT', 'OPD', 'ORF', 'ODX', 'OOP', 'DRU', 'CHA', 'CHT', 'AER', 'ADP'];
                        let writtenFiles = 0;

                        for (const k of fileKeys) {
                            const fileName = k + '.txt';
                            const fileContent = files[k] || '';
                            
                            // Create or overwrite file
                            const fileHandle = await targetDir.getFileHandle(fileName, { create: true });
                            const writable = await fileHandle.createWritable();
                            
                            // Write UTF-8 string
                            await writable.write(fileContent);
                            await writable.close();
                            writtenFiles++;
                        }

                        btn.prop('disabled', false).html(originalBtnHtml);
                        $('#f16ExportProgressText').html('<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>ส่งออกสำเร็จครบ 11 แฟ้ม</span>');

                        // 5. Show Success Notification
                        const folderDisplay = createSubfolder ? subfolderName : dirHandle.name;
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'ส่งออก 16 แฟ้ม (e-Claim) สำเร็จเรียบร้อย!',
                                html: `
                                    <div class="text-start p-3 bg-light rounded small mt-2">
                                        <div class="mb-1"><b>📁 โฟลเดอร์:</b> <code class="text-primary fs-6">${folderDisplay}</code></div>
                                        <div class="mb-1"><b>📄 จำนวนไฟล์:</b> ครบ 11 แฟ้มผู้ป่วยนอก (.txt)</div>
                                        <div class="mb-0"><b>👥 ผู้รับบริการ:</b> ${state.vns.length} รายการ</div>
                                    </div>
                                    <div class="mt-3 text-muted small">
                                        เปิดหน้า <b>e-Claim (import16)</b> แล้วกดปุ่ม <b>[ แนบไฟล์ ]</b> เพื่อเลือกไฟล์ทั้งหมดไปนำเข้าได้ทันที
                                    </div>
                                `,
                                confirmButtonText: 'รับทราบ',
                                confirmButtonColor: '#0e939a'
                            });
                        } else {
                            alert('ส่งออกสำเร็จเรียบร้อยที่โฟลเดอร์: ' + folderDisplay);
                        }

                    } catch (writeErr) {
                        console.error('File write error:', writeErr);
                        btn.prop('disabled', false).html(originalBtnHtml);
                        $('#f16ExportProgressText').text('');
                        alert('เกิดข้อผิดพลาดในการเขียนไฟล์: ' + writeErr.message);
                    }
                } else {
                    btn.prop('disabled', false).html(originalBtnHtml);
                    $('#f16ExportProgressText').text('');
                    alert(res.message || 'ไม่สามารถส่งออกข้อมูลได้');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalBtnHtml);
                $('#f16ExportProgressText').text('');
                let errMsg = 'เกิดข้อผิดพลาดในการส่งออกข้อมูล';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert(errMsg);
            }
        });
    };
</script>
