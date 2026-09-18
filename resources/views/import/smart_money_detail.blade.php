@extends('layouts.app')

@section('content')
<div class="container-fluid py-3 px-4">
    {{-- Header & Back Button --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('import.smart_money', ['budget_year' => $batch->budget_year]) }}"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> กลับหน้ารวม Smart Money
            </a>
            <div>
                <h4 class="fw-bold mb-0 text-dark">
                    รายงานรายละเอียดการเบิกจ่ายเงินรายบุคคล (Batch No. <span class="text-primary font-monospace">{{ $batch->batch_no }}</span>)
                </h4>
                <div class="text-muted small">
                    <span>งวด: <strong>{{ $batch->round_no }}</strong> | ผังบัญชี: <strong>{{ $batch->account_code }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-info btn-sm rounded-pill px-3 shadow-sm fw-semibold text-white" onclick="syncDetailFromSmt('{{ $batch->batch_no }}')">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> ดึงรายคนจาก SMT
            </button>
            <a href="{{ route('import.smart_money.detail', [$batch->batch_no, 'export' => 'excel']) }}"
                class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold text-white">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
            </a>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#importDetailModal">
                <i class="bi bi-upload me-1"></i> นำเข้าไฟล์ Excel รายตัว
            </button>
        </div>
    </div>

    {{-- Batch Info Card --}}
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <span class="text-muted small">วันที่โอนเงิน:</span>
                <div class="fw-bold text-dark">{{ !empty($batch->transfer_date) ? DateThai($batch->transfer_date) : '-' }}</div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small">กองทุน / กองทุนย่อย:</span>
                <div class="fw-bold text-dark text-truncate" title="{{ $batch->fund_main }} - {{ $batch->fund_sub }}">{{ $batch->fund_main }} - {{ $batch->fund_sub }}</div>
            </div>
            <div class="col-md-2">
                <span class="text-muted small">ยอดเงินโอนเข้าบัญชี:</span>
                <div class="fw-bold text-success fs-5">{{ number_format($batch->net_amount, 2) }} <span class="fs-6 text-muted">บาท</span></div>
            </div>
            <div class="col-md-2">
                <span class="text-muted small">เลขที่ใบเสร็จ:</span>
                <div>
                    @if(!empty($batch->receive_no))
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> {{ $batch->receive_no }}
                        </span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle fw-medium px-2 py-1">
                            <i class="bi bi-clock-history me-1"></i> ยังไม่ออก
                        </span>
                    @endif
                </div>
            </div>
            <div class="col-md-3 text-end">
                <span class="text-muted small">จำนวนรายคนไข้ในระบบ:</span>
                <div class="fw-bold text-primary fs-5">{{ number_format($total_details_count) }} <span class="fs-6 text-muted">ราย</span></div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-person-lines-fill text-primary me-2"></i> รายชื่อผู้ป่วยขอเบิกชดเชย
            </h6>
            <form method="GET" action="{{ route('import.smart_money.detail', $batch->batch_no) }}" class="d-flex gap-2" style="max-width: 320px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm rounded-pill shadow-none" placeholder="ค้นหา HN, AN, เลขบัตร, ชื่อ...">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="width: 100%;">
                    <thead class="table-light">
                        <tr class="text-nowrap small text-muted">
                            <th class="text-center" width="4%">ลำดับ</th>
                            <th class="text-center" width="7%">HN</th>
                            <th class="text-center" width="7%">AN</th>
                            <th class="text-center" width="6%">ประเภท</th>
                            <th class="text-center" width="11%">เลขบัตรประชาชน</th>
                            <th class="text-start" width="16%">ชื่อ - สกุล</th>
                            <th class="text-center" width="8%">วันที่รับบริการ</th>
                            <th class="text-end" width="9%">ชดเชยสุทธิ</th>
                            <th class="text-center" width="8%">REP_NO</th>
                            <th class="text-center" width="6%">กองทุนหลัก</th>
                            <th class="text-center" width="7%">กองทุนย่อย</th>
                            <th class="text-start" width="11%">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $index => $row)
                        <tr>
                            <td class="text-center small text-muted">{{ $details->firstItem() + $index }}</td>
                            <td class="text-center fw-bold text-primary font-monospace small">{{ $row->hn }}</td>
                            <td class="text-center small text-dark font-monospace">{{ $row->an ?: '-' }}</td>
                            <td class="text-center small">
                                <span class="badge {{ $row->pt_type === 'ผู้ป่วยใน' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-primary' }} rounded-pill px-2">
                                    {{ $row->pt_type }}
                                </span>
                            </td>
                            <td class="text-center small font-monospace text-muted">{{ $row->cid }}</td>
                            <td class="text-start small fw-semibold text-dark">{{ $row->pt_name }}</td>
                            <td class="text-center small">{{ !empty($row->vstdate) ? DateThai($row->vstdate) : '-' }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($row->receive_total, 2) }}</td>
                            <td class="text-center small text-muted font-monospace">{{ $row->repno }}</td>
                            <td class="text-center small"><span class="badge bg-light text-dark border">{{ $row->main_fund }}</span></td>
                            <td class="text-center small"><span class="badge bg-light text-primary border">{{ $row->sub_fund }}</span></td>
                            <td class="text-start small text-muted text-truncate" style="max-width: 150px;">{{ $row->sub_fund_desc }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                ยังไม่มีข้อมูลรายบุคคลสำหรับ Batch นี้
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#importDetailModal">
                                        <i class="bi bi-upload me-1"></i> นำเข้าไฟล์ Excel รายบุคคล
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($details->hasPages())
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                แสดง {{ $details->firstItem() }} ถึง {{ $details->lastItem() }} จาก {{ number_format($details->total()) }} รายการ
            </div>
            <div>
                {{ $details->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal: นำเข้าไฟล์ Excel รายบุคคล --}}
<div class="modal fade" id="importDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header text-white p-3 px-4" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-2" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.2); border-radius: 10px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">นำเข้าไฟล์รายบุคคล Smart Money</h5>
                        <div class="text-white-50 small">Batch No: {{ $batch->batch_no }} (งวด {{ $batch->round_no }})</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="formImportDetail" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="batch_no" value="{{ $batch->batch_no }}">
                    <label for="detail_excel" class="card border-2 border-dashed rounded-4 p-4 text-center bg-white mb-3 d-block" style="cursor: pointer;">
                        <i class="bi bi-file-earmark-person text-primary fs-1 mb-2"></i>
                        <h6 class="fw-bold text-dark mb-1">คลิกเลือกไฟล์ Excel รายบุคคล</h6>
                        <div class="text-muted small">ไฟล์ที่ดาวน์โหลดจากปุ่ม Export Excel ในหน้ารายละเอียดของ สปสช.</div>
                        <input type="file" class="d-none" id="detail_excel" name="detail_excel" accept=".xlsx, .xls, .csv" onchange="previewDetailFile(this)">
                        <div id="detail_file_name" class="mt-2 text-primary fw-bold small d-none"></div>
                    </label>
                </form>

                <div id="detailImportLoading" class="p-3 bg-white rounded-4 border border-light-subtle shadow-sm mb-3 d-none">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small text-muted fw-bold" id="detailProgressStatusText">กำลังนำเข้ารายชื่อคนไข้...</span>
                        <span class="badge bg-primary fw-bold" id="detailProgressPercent" style="border-radius: 8px;">0%</span>
                    </div>
                    <div class="progress mb-2" style="height: 14px; border-radius: 7px; background-color: #e9ecef; overflow: hidden;">
                        <div id="detailProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                    <div class="text-muted small text-start" style="font-size: 11px;">
                        <i class="bi bi-info-circle me-1"></i> ระบบกำลังอ่านข้อมูลจากชีต Excel และเชื่อมโยงกับ Batch หลัก
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-3 bg-white">
                <button type="button" class="btn btn-light px-3 rounded-pill fw-semibold" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary px-4 rounded-pill fw-semibold shadow-sm" id="btnSubmitDetailImport">
                    <i class="bi bi-upload me-1"></i> เริ่มนำเข้า
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function previewDetailFile(input) {
        if (input.files && input.files[0]) {
            $('#detail_file_name').removeClass('d-none').html('<i class="bi bi-file-earmark-check me-1"></i> ' + input.files[0].name);
        }
    }

    $('#btnSubmitDetailImport').on('click', function() {
        var fileInput = document.getElementById('detail_excel');
        if(!fileInput.files || fileInput.files.length === 0) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกไฟล์ Excel รายบุคคลก่อน', 'warning');
            return;
        }

        var formData = new FormData($('#formImportDetail')[0]);
        $('#detailImportLoading').removeClass('d-none');
        $('#detailProgressBar').css('width', '15%');
        $('#detailProgressPercent').text('15%');
        $('#detailProgressStatusText').text('กำลังอัปโหลดไฟล์...');
        $(this).prop('disabled', true);

        var progressInterval = setInterval(function() {
            var currentWidth = parseInt($('#detailProgressBar')[0]?.style.width) || 15;
            if (currentWidth < 85) {
                var nextWidth = currentWidth + Math.floor(Math.random() * 12) + 5;
                if (nextWidth > 85) nextWidth = 85;
                $('#detailProgressBar').css('width', nextWidth + '%');
                $('#detailProgressPercent').text(nextWidth + '%');
                if (nextWidth > 50) {
                    $('#detailProgressStatusText').text('กำลังอ่านข้อมูลรายชื่อและเชื่อมโยง...');
                }
            }
        }, 350);

        fetch("{{ route('import.smart_money.import_detail') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content'),
                "Accept": "application/json"
            },
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            clearInterval(progressInterval);
            $('#detailProgressBar').css('width', '100%');
            $('#detailProgressPercent').text('100%');
            $('#detailProgressStatusText').text('ประมวลผลเสร็จสิ้น 100%');

            setTimeout(function() {
                $('#detailImportLoading').addClass('d-none');
                $('#btnSubmitDetailImport').prop('disabled', false);

                if(res.status === 'success') {
                    $('#importDetailModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ 100%',
                        text: res.message,
                        confirmButtonText: 'ตกลง',
                        customClass: { popup: 'rounded-4' }
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('ผิดพลาด', res.message || 'ไม่สามารถนำเข้าข้อมูลได้', 'error');
                }
            }, 400);
        })
        .catch(err => {
            clearInterval(progressInterval);
            $('#detailImportLoading').addClass('d-none');
            $('#btnSubmitDetailImport').prop('disabled', false);
            Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการส่งไฟล์', 'error');
        });
    });

    window.syncDetailFromSmt = function(batchNo) {
        if (!batchNo) return;
        Swal.fire({
            title: 'กำลังดึงข้อมูลรายคน...',
            html: `
                <div class="text-center p-3">
                    <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <div class="fw-bold text-dark mb-1">กำลังเชื่อมต่อและดึงข้อมูลรายบุคคล...</div>
                    <div class="small text-muted">ระบบจะค้นหาจากฐานข้อมูลและดาวน์โหลดรายงานจาก SMT อัตโนมัติ</div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: { popup: 'rounded-4' }
        });

        $.ajax({
            url: "{{ url('import/smart-money/sync-detail') }}/" + encodeURIComponent(batchNo),
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(res) {
                if (res && res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ดึงข้อมูลสำเร็จ!',
                        html: `
                            <div class="text-start p-3 bg-light rounded-4 small">
                                <div class="text-success fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i> ${res.message}</div>
                                <div class="text-muted">จำนวนผู้ป่วย: <strong>${(res.count || 0).toLocaleString()}</strong> รายการ</div>
                                <div class="text-muted">ยอดเงินรวม: <strong>${res.total_amount_formatted || '0.00'}</strong> บาท</div>
                            </div>
                        `,
                        customClass: { popup: 'rounded-4' }
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ไม่สามารถดึงข้อมูลได้',
                        text: res.message || 'ไม่พบรายงานรายบุคคลในระบบ',
                        customClass: { popup: 'rounded-4' }
                    });
                }
            },
            error: function(err) {
                var msg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: msg,
                    customClass: { popup: 'rounded-4' }
                });
            }
        });
    };
</script>
@endpush
