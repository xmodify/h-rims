                            <td><span class="badge bg-success">${fields[11] || ''}</span></td>
                            <td>${fields[12] || ''}</td>
                            <td class="fw-bold">${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td>${fields[15] || ''}</td>
                            <td class="text-end text-primary fw-bold">${fields[16] || '0.00'}</td>
                            <td>${fields[17] || ''}</td>
                            <td>${fields[18] || '0.00'}</td>
                        </tr>`;
                    });
                    $('#preview-billtran-tbody').html(html1);

                    // 2. Populate BILLDISP Table & Raw XML
                    $('#preview-billdisp-raw').val(response.billdisp_raw);
                    var html2 = '';
                    response.billdisp_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        var vn = fields[16]; // VisitNo
                        var val = response.validation[vn] || { billdisp_ok: true, billdisp_err: '' };
                        var statusBadge = val.billdisp_ok 
                            ? '<span class="badge bg-success" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> ผ่าน</span>'
                            : `<span class="badge bg-danger" style="font-size:0.75rem; cursor:pointer;" title="${val.billdisp_err}"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน</span>`;

                        html2 += `<tr>
                            <td>${statusBadge}</td>
                            <td>${fields[0] || ''}</td>
                            <td class="fw-bold text-primary">${fields[1] || ''}</td>
                            <td>${fields[2] || ''}</td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td><span class="badge bg-secondary">${fields[8] || ''}</span></td>
                            <td class="text-end">${fields[9] || '0.00'}</td>
                            <td class="text-end fw-bold">${fields[10] || '0.00'}</td>
                            <td>${fields[11] || '0.00'}</td>
                            <td>${fields[12] || '0.00'}</td>
                            <td>${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td class="text-center fw-bold">${fields[15] || '0'}</td>
                            <td>${fields[16] || ''}</td>
                        </tr>`;
                    });
                    
                    $('#preview-billdisp-tbody').html(html2);

                    // 3. Populate OPServices Table & Raw XML
                    $('#preview-opservices-raw').val(response.opservices_raw);
                    var html3 = '';
                    response.opservices_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        var vn = fields[1]; // VisitNo
                        var val = response.validation[vn] || { opservices_ok: true, opservices_err: '' };
                        var statusBadge = val.opservices_ok 
                            ? '<span class="badge bg-success" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> ผ่าน</span>'
                            : `<span class="badge bg-danger" style="font-size:0.75rem; cursor:pointer;" title="${val.opservices_err}"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน</span>`;

                        html3 += `<tr>
                            <td>${statusBadge}</td>
                            <td>${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td><span class="badge bg-info">${fields[2] || ''}</span></td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td>${fields[8] || ''}</td>
                            <td>${fields[9] || ''}</td>
                            <td>${fields[10] || ''}</td>
                            <td>${fields[11] || ''}</td>
                            <td>${fields[12] || ''}</td>
                            <td>${fields[13] || ''}</td>
                            <td>${fields[14] || ''}</td>
                            <td>${fields[15] || ''}</td>
                            <td>${fields[16] || ''}</td>
                            <td>${fields[17] || ''}</td>
                            <td>${fields[18] || '0.00'}</td>
                            <td><span class="badge bg-success">${fields[19] || ''}</span></td>
                            <td>${fields[20] || ''}</td>
                            <td>${fields[21] || ''}</td>
                        </tr>`;
                    });
                    $('#preview-opservices-tbody').html(html3);

                    // Initialize DataTables for Preview Tables
                    const prevDtConfig = {
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            paginate: {
                                previous: "ก่อนหน้า",
                                next: "ถัดไป"
                            }
                        },
                        scrollY: "300px",
                        scrollCollapse: true,
                        scrollX: true,
                        autoWidth: false
                    };
                    $('#table-prev-billtran').DataTable(prevDtConfig);
                    $('#table-prev-billdisp').DataTable(prevDtConfig);
                    $('#table-prev-opservices').DataTable(prevDtConfig);

                    // Open Preview Modal
                    $('#ssopPreviewModal').modal('show');
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: response.error || 'ไม่สามารถประมวลผลข้อมูลพรีวิวได้' });
                }
            },
            error: function(xhr) {
                console.error("AJAX Error details:", xhr.status, xhr.statusText, xhr.responseText);
                Swal.close();
                var msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : ('เกิดข้อผิดพลาดในการเชื่อมต่อ (สถานะ: ' + xhr.status + ' ' + xhr.statusText + ')');
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: msg });
            }
        });
    }

    function triggerActualDownload() {
        var selectedVns = [];
        $('.claim-select-check:checked').each(function() {
            selectedVns.push($(this).val());
        });

        var sessionId = $('#export_session_id').val().trim();
        var stationId = $('#export_station_id').val().trim();

        $('#ssopPreviewModal').modal('hide');

        Swal.fire({
            title: 'กำลังสร้างไฟล์...',
            text: 'กรุณารอสักครู่ขณะสร้างไฟล์ Zip เพื่อดาวน์โหลด',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Standard POST form submit to trigger file download
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ url('claim_op/sss_export_ssop') }}";
        
        // CSRF Token
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = "{{ csrf_token() }}";
        form.appendChild(csrfInput);

        // Session ID & Station ID
        var sessInput = document.createElement('input');
        sessInput.type = 'hidden';
        sessInput.name = 'session_id';
        sessInput.value = sessionId;
        form.appendChild(sessInput);

        var statInput = document.createElement('input');
        statInput.type = 'hidden';
        statInput.name = 'station_id';
        statInput.value = stationId;
        form.appendChild(statInput);

        // Selected VNs
        selectedVns.forEach(function(vn) {
            var vnInput = document.createElement('input');
            vnInput.type = 'hidden';
            vnInput.name = 'vns[]';
            vnInput.value = vn;
            form.appendChild(vnInput);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);