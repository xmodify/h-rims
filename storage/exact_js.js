                            <td>${row.vstdate}</td>
                            <td class="fw-bold text-primary">${row.hn}</td>
                            <td>${row.ptname}</td>
                            <td>${row.cid}</td>
                            <td class="${row.diag_mismatch ? 'text-danger fw-bold' : ''}">${row.diag_code || '-'}</td>
                            <td class="${row.drug_mismatch ? 'text-danger fw-bold' : ''}">${row.drug_code || '-'}</td>
                            <td class="small text-muted">${row.source_filename}</td>
                        </tr>`;
                    });
                }
                body21.innerHTML = h21;

                // Populate Tab 2.2
                let h22 = '';
                const items22 = data.feedback_22 || [];
                if (items22.length === 0) {
                    h22 = '<tr><td colspan="7" class="text-center text-muted py-3">ไม่พบรายการผลตอบกลับ ตอนที่ 2.2</td></tr>';
                } else {
                    items22.forEach(row => {
                        h22 += `<tr>
                            <td>${row.vstdate}</td>
                            <td class="fw-bold text-primary">${row.hn}</td>
                            <td>${row.ptname}</td>
                            <td>${row.cid}</td>
                            <td class="text-danger fw-bold">${row.diag_code || '-'}</td>
                            <td class="text-danger fw-bold">${row.drug_code || '-'}</td>
                            <td class="small text-muted">${row.source_filename}</td>
                        </tr>`;
                    });
                }
                body22.innerHTML = h22;
            })
            .fail(function() {
                body21.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">ผิดพลาดในการเชื่อมต่อข้อมูล</td></tr>';
                body22.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">ผิดพลาดในการเชื่อมต่อข้อมูล</td></tr>';
            });
    };

    window.uploadSssZip = function(type) {
        const inputId = type === 'rep' ? 'zip_file_rep' : (type === 'stm' ? 'zip_file_stm' : (type === 'chronic' ? 'zip_file_chronic' : 'zip_file_chronic_reg'));
        const input = document.getElementById(inputId);
        if (!input || input.files.length === 0) return;

        const formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('type', type);
        for(let i=0; i<input.files.length; i++) {
            formData.append('zip_files[]', input.files[i]);
        }

        Swal.fire({
            title: 'กำลังอัปโหลดและประมวลผลไฟล์...',
            text: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "{{ url('api/import_sss_zip') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'นำเข้าสำเร็จ!',
                        html: `นำเข้าข้อมูลเรียบร้อยแล้ว<br>จำนวนข้อมูล: ${res.inserted_count} แถว<br>${res.message || ''}`,
                    }).then(() => {
                        input.value = '';
                        loadFeedbackList();
                        // Reload main dashboard tables to see latest feedback/stm status
                        loadDashboard({
                            budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            skip_chart: 1
                        });
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ไม่สำเร็จ',
                        text: res.message || 'เกิดข้อผิดพลาดในการประมวลผลไฟล์ ZIP'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาดในการนำเข้า',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถสื่อสารกับเซิร์ฟเวอร์ได้'
                });
            }
        });
    };

    // export SSOP functions
    let selectedVnsForExport = [];
    window.exportSelectedSSOP = function() {
        selectedVnsForExport = [];
        $('.claim-select-check:checked').each(function() {
            selectedVnsForExport.push(this.value);
        });

        if (selectedVnsForExport.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกรายการ',
                text: 'กรุณาติ๊กเลือกผู้ป่วยอย่างน้อย 1 รายการก่อนทำการส่งออก SSOP'
            });
            return;
        }

        // Random Session ID setup
        const minVal = 1000;
        const maxVal = 9999;
        const randomSession = Math.floor(Math.random() * (maxVal - minVal + 1)) + minVal;
        document.getElementById('export_session_id').value = randomSession;
        document.getElementById('export_station_id').value = '01';
        document.getElementById('export_tflag').value = 'A';

        $('#ssopExportModal').modal('show');
    };

    window.previewSSOPExport = function() {
        const sessionId = document.getElementById('export_session_id').value;
        const stationId = document.getElementById('export_station_id').value;
        const tflag = document.getElementById('export_tflag').value;

        if (!sessionId) {
            Swal.fire({ icon: 'warning', title: 'กรุณากรอก Session ID' });
            return;
        }

        Swal.fire({
            title: 'กำลังเตรียมและประมวลผลข้อมูลส่งออก...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: "{{ url('api/ssop_export_preview') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                vns: selectedVnsForExport,
                session_id: sessionId,
                station_id: stationId,
                tflag: tflag
            },
            success: function(res) {
                Swal.close();
                if (res.success) {
                    $('#ssopExportModal').modal('hide');
                    $('#ssopPreviewModal').modal('show');

                    // Pre-Audit Tab population
                    let hAudit = '';
                    const auditIssues = res.audit_issues || [];
                    if (auditIssues.length === 0) {
                        hAudit = '<tr><td colspan="5" class="text-center text-success fw-bold py-3"><i class="bi bi-patch-check-fill me-1"></i> ผ่านการตรวจสอบ ไม่พบข้อผิดพลาด Pre-Audit (พร้อมนำส่ง 100%)</td></tr>';
                    } else {
                        auditIssues.forEach((issue, idx) => {
                            const badge = issue.severity === 'error' ? 'bg-danger' : 'bg-warning text-dark';
                            const rowClass = issue.severity === 'error' ? 'table-danger-light' : 'table-warning-light';
                            hAudit += `<tr class="${rowClass}">
                                <td class="text-center">${idx + 1}</td>
                                <td><div class="fw-bold">${issue.hn}</div><div>${issue.ptname}</div></td>
                                <td>${issue.vstdate}</td>
                                <td class="fw-bold text-dark">${issue.message}</td>
                                <td class="text-center"><span class="badge ${badge}">${issue.severity.toUpperCase()}</span></td>
                            </tr>`;
                        });
                    }
                    document.getElementById('prev-audit-body').innerHTML = hAudit;

                    // BILLTRAN Tab population
                    let hBill = '';
                    (res.billtran || []).forEach(row => {
                        hBill += `<tr>
                            <td>${row.Station || ''}</td><td>${row.InvNo || ''}</td><td>${row.HN || ''}</td><td>${row.MemberNo || ''}</td>
                            <td>${row.Amount || ''}</td><td>${row.Paid || ''}</td><td>${row.Claim || ''}</td><td>${row.Name || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billtran-body').innerHTML = hBill;

                    // BillItems Tab population
                    let hItems = '';
                    (res.billitems || []).forEach(row => {
                        hItems += `<tr>
                            <td>${row.InvNo || ''}</td><td>${row.ItemSeq || ''}</td><td>${row.BillGr || ''}</td><td>${row.LCode || ''}</td>
                            <td>${row.Qty || ''}</td><td>${row.Charge || ''}</td><td>${row.Claim || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billitems-body').innerHTML = hItems;
