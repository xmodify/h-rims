            text: 'กรุณารอสักครู่ขณะระบบดึงและตรวจสอบข้อมูลพรีวิว',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Destroy existing DataTables if initialized to prevent error
        if ($.fn.DataTable.isDataTable('#table-prev-billtran')) { $('#table-prev-billtran').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-billdisp')) { $('#table-prev-billdisp').DataTable().destroy(); }
        if ($.fn.DataTable.isDataTable('#table-prev-opservices')) { $('#table-prev-opservices').DataTable().destroy(); }

        $.ajax({
            url: "{{ url('claim_op/sss_export_preview') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                vns: selectedVns,
                session_id: sessionId,
                station_id: stationId
            },
            success: function(response) {
                console.log("AJAX Success response:", response);
                Swal.close();
                if (response.success) {
                    // 1. Populate BILLTRAN Table & Raw XML
                    $('#preview-billtran-raw').val(response.billtran_raw);
                    var html1 = '';
                    response.billtran_table.forEach(function(fields) {
                        if (fields.length < 10) return;
                        var vn = fields[19]; // Appended VN
                        var val = response.validation[vn] || { billtran_ok: true, billtran_err: '' };
                        var statusBadge = val.billtran_ok 
                            ? '<span class="badge bg-success" style="font-size:0.75rem;"><i class="bi bi-check-circle-fill"></i> ผ่าน</span>'
                            : `<span class="badge bg-danger" style="font-size:0.75rem; cursor:pointer;" title="${val.billtran_err}"><i class="bi bi-exclamation-triangle-fill"></i> ไม่ผ่าน</span>`;
                        
                        html1 += `<tr>
                            <td>${statusBadge}</td>
                            <td>${fields[0] || ''}</td>
                            <td>${fields[1] || ''}</td>
                            <td>${fields[2] || ''}</td>
                            <td>${fields[3] || ''}</td>
                            <td>${fields[4] || ''}</td>
                            <td>${fields[5] || ''}</td>
                            <td>${fields[6] || ''}</td>
                            <td>${fields[7] || ''}</td>
                            <td class="text-end fw-bold">${fields[8] || '0.00'}</td>
                            <td class="text-end text-muted">${fields[9] || '0.00'}</td>
                            <td>${fields[10] || ''}</td>
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