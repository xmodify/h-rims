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

                    // BILLDISP Tab population
                    let hDisp = '';
                    (res.billdisp || []).forEach(row => {
                        hDisp += `<tr>
                            <td>${row.DispID || ''}</td><td>${row.PrescID || ''}</td><td>${row.InvNo || ''}</td><td>${row.DispDate || ''}</td>
                            <td>${row.HN || ''}</td><td>${row.Name || ''}</td><td>${row.Amount || ''}</td><td>${row.Reimb || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-billdisp-body').innerHTML = hDisp;

                    // DispensedItems Tab population
                    let hDispItems = '';
                    (res.dispenseditems || []).forEach(row => {
                        hDispItems += `<tr>
                            <td>${row.DispID || ''}</td><td>${row.PrescID || ''}</td><td>${row.ItemSeq || ''}</td><td>${row.LocalCd || ''}</td>
                            <td>${row.StdCd || ''}</td><td>${row.Qty || ''}</td><td>${row.PrdCat || ''}</td><td>${row.Reimb || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-dispenseditems-body').innerHTML = hDispItems;

                    // OPServices Tab population
                    let hOps = '';
                    (res.opservices || []).forEach(row => {
                        hOps += `<tr>
                            <td>${row.HN || ''}</td><td>${row.SvDate || ''}</td><td>${row.Class || ''}</td><td>${row.CareType || ''}</td>
                            <td>${row.InvNo || ''}</td><td>${row.PrePay || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-opservices-body').innerHTML = hOps;

                    // OPDiagnoses Tab population
                    let hDiag = '';
                    (res.opdiagnoses || []).forEach(row => {
                        hDiag += `<tr>
                            <td>${row.HN || ''}</td><td>${row.SvDate || ''}</td><td>${row.DiagType || ''}</td><td>${row.DiagCode || ''}</td>
                        </tr>`;
                    });
                    document.getElementById('prev-opdiagnoses-body').innerHTML = hDiag;

                    // If errors exist, disable download button
                    const errorCount = auditIssues.filter(i => i.severity === 'error').length;
                    const btnDownload = document.getElementById('btnDownloadSSOP');
                    if (errorCount > 0) {
                        btnDownload.disabled = true;
                        btnDownload.innerHTML = `<i class="bi bi-x-circle me-1"></i> กรุณาแก้ไขข้อผิดพลาด (${errorCount} รายการ)`;
                        btnDownload.className = 'btn btn-danger px-4';
                    } else {
                        btnDownload.disabled = false;
                        btnDownload.innerHTML = `<i class="bi bi-download me-1"></i> ยืนยันการดาวน์โหลด SSOP (.zip)`;
                        btnDownload.className = 'btn btn-success px-4';
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: res.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลพรีวิว'
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'ผิดพลาด',
                    text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'ไม่สามารถประมวลผลคำขอได้'
                });
            }
        });
    };

    window.downloadSSOPExportZip = function() {
        const sessionId = document.getElementById('export_session_id').value;
        const stationId = document.getElementById('export_station_id').value;
        const tflag = document.getElementById('export_tflag').value;

        // Redirect/Download trigger
        const queryParams = $.param({
            vns: selectedVnsForExport,
            session_id: sessionId,
            station_id: stationId,
            tflag: tflag
        });
        window.location.href = "{{ url('api/ssop_export_download') }}?" + queryParams;

        $('#ssopPreviewModal').modal('hide');
        Swal.fire({
            icon: 'success',
            title: 'สร้างไฟล์นำส่งเรียบร้อยแล้ว!',
            text: 'ดาวน์โหลดไฟล์นำส่ง SSOP สำเร็จแล้ว',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            // Refresh tables
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                skip_chart: 1
            });
        });
    };

    $(document).ready(function () {
        loadDashboard({
            budget_year: "{{ $budget_year }}",
            start_date: "{{ $start_date }}",
            end_date: "{{ $end_date }}"
        });

        $(document).on('submit', '#form_budget_year', function(e) {
            e.preventDefault();
            loadDashboard({
                budget_year: $(this).find('select[name="budget_year"]').val()
            });
        });

        $(document).on('submit', '#form_indiv', function(e) {
            e.preventDefault();
            loadDashboard({
                budget_year: $('#form_budget_year select[name="budget_year"]').val() || "{{ $budget_year }}",
                start_date: $(this).find('#start_date').val(),
                end_date: $(this).find('#end_date').val(),
                skip_chart: 1
            });
        });
    });
  </script>
@endpush
