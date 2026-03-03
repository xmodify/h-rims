@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-4">
    <!-- Page Header & Search -->
    <div class="page-header-box mt-3 mb-4">
        <div>
            <h5 class="text-dark mb-0 fw-bold">
                <i class="bi bi-robot text-success me-2"></i>
                ตรวจสอบสถานะการเคลม E-Claim สปสช.
            </h5>
            <div class="text-muted small mt-1">วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}</div>
        </div>
        
        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
            <form method="POST" action="{{ url('check/eclaim_status') }}" class="d-flex align-items-center gap-2 m-0">
                @csrf
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3"></i></span>
                    <input type="date" name="start_date" class="form-control border-start-0" value="{{ $start_date }}" style="width: 140px;">
                    <span class="input-group-text bg-white">ถึง</span>
                    <input type="date" name="end_date" class="form-control" value="{{ $end_date }}" style="width: 140px;">
                    <button type="submit" class="btn btn-primary px-3 shadow-sm hover-scale">
                        <i class="bi bi-search me-1"></i> ค้นหา
                    </button>
                </div>
            </form>
            
            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#ExtensionInfoModal">
                <i class="bi bi-puzzle-fill me-1"></i> วิธีดึงข้อมูลด้วย Extension
            </button>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm hover-scale" data-bs-toggle="modal" data-bs-target="#EclaimExcelModal">
                <i class="bi bi-file-earmark-excel-fill me-1"></i> นำเข้าไฟล์ Excel
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="card dash-card border-top-0 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">            
                <table id="list" class="table table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center">E-Claim No.</th>
                            <th class="text-center">HIPDATA</th>
                            <th class="text-center">CID</th>
                            <th class="text-center">HN</th>
                            <th class="text-center">AN</th>
                            <th class="text-start">ชื่อ-สกุล</th>
                            <th class="text-center">วันที่เข้ารับบริการ</th> 
                            <th class="text-center">เวลารับบริการ</th>
                            <th class="text-center">วันที่จำหน่าย</th>
                            <th class="text-center">เวลาจำหน่าย</th>
                            <th class="text-start">สถานะข้อมูล</th>
                            <th class="text-start">ชื่อผู้บันทึกเบิกชดเชย</th>
                            <th class="text-end">ยอดเรียกเก็บ</th>
                            <th class="text-center">REP</th>
                            <th class="text-center">STM</th>
                            <th class="text-center">SEQ</th>
                            <th class="text-start">รายละเอียดการตรวจสอบ</th>
                            <th class="text-start">Deny/Warning</th>
                            <th class="text-center">Channel</th>   
                        </tr>     
                    </thead> 
                    <tbody> 
                        @foreach($sql as $row) 
                        @php
                            $st_code = substr($row->status, 0, 1);
                            $row_class = 'row-status-0'; // Default to white
                            if($st_code == '1') $row_class = 'row-status-1';
                            elseif($st_code == '2') $row_class = 'row-status-2';
                            elseif($st_code == '3') $row_class = 'row-status-3';
                            elseif($st_code == '4') $row_class = 'row-status-4';
                        @endphp
                        <tr class="{{ $row_class }}">
                            <td class="text-center fw-bold">{{ $row->eclaim_no }}</td>
                            <td class="text-center small">{{ $row->hipdata }}</td>
                            <td class="text-center">{{ $row->cid }}</td>
                            <td class="text-center">{{ $row->hn }}</td>
                            <td class="text-center">{{ $row->an ?: '-' }}</td>
                            <td class="text-start">{{ $row->ptname }}</td>
                            <td class="text-center small">{{ $row->vstdate ? DateThai($row->vstdate) : '-' }}</td>
                            <td class="text-center small">{{ $row->vsttime ?: '-' }}</td>
                            <td class="text-center small">{{ $row->dchdate ? DateThai($row->dchdate) : '-' }}</td>
                            <td class="text-center small">{{ $row->dchtime ?: '-' }}</td>
                            <td class="text-start small">
                                {{ $row->status }}
                            </td>
                            <td class="text-start small">{{ $row->recorder ?: '-' }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($row->claim_amount, 2) }}</td>
                            <td class="text-center small">{{ $row->rep ?: '-' }}</td>
                            <td class="text-center small">{{ $row->stm ?: '-' }}</td>
                            <td class="text-center small">{{ $row->seq ?: '-' }}</td>
                            <td class="text-start small">{{ $row->check_detail ?: '-' }}</td>
                            <td class="text-start small text-danger">{{ $row->deny_warning ?: '-' }}</td>
                            <td class="text-center">
                                @if($row->channel == 'Excel')
                                    <span class="badge bg-success-soft text-success"><i class="bi bi-file-earmark-excel"></i> Excel</span>
                                @elseif($row->channel == 'Extension')
                                    <span class="badge bg-info-soft text-info"><i class="bi bi-browser-chrome"></i> Extension</span>
                                @else
                                    <span class="badge bg-light text-dark">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach                 
                    </tbody>
                </table> 
            </div>          
        </div> 
    </div>  
</div>     

<!-- Modal Import Excel -->
<div class="modal fade" id="EclaimExcelModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel-fill me-2"></i> นำเข้าข้อมูล E-Claim จาก Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="{{ url('check/eclaim_status/import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
                <i class="bi bi-info-circle-fill me-2"></i> 
                <strong>คำแนะนำ:</strong> ให้ Export ไฟล์รายงานออกจากหน้าเว็บ E-Claim (.xlsx หรือ .csv) แล้วนำไฟล์นั้นมาอัปโหลดที่นี่
            </div>
            <div class="mb-3">
                <label for="excelFile" class="form-label fw-bold">เลือกไฟล์นามสกุล .xls, .xlsx หรือ .csv</label>
                <input class="form-control" type="file" id="excelFile" name="file[]" multiple required>
            </div>
        </div>
        <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" class="btn btn-success rounded-pill px-4" onclick="showLoading()">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> อัปโหลดและประมวลผล
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Extension Info -->
<div class="modal fade" id="ExtensionInfoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold"><i class="bi bi-puzzle-fill text-warning me-2"></i> วิธีติดตั้งและใช้งาน Chrome Extension</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-start">
          <h6 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-1-circle"></i> ขั้นตอนที่ 1 : ติดตั้งส่วนเสริม (ทำครั้งเดียว)</h6>
          <ol class="mb-4 text-muted small lh-lg">
              <li>ดาวน์โหลดไฟล์ส่วนเสริมลงในเครื่องคอมพิวเตอร์ของคุณ <br/><a href="{{ url('downloads/eclaim_sync.zip') }}" class="btn btn-sm btn-outline-primary mt-1 mb-2"><i class="bi bi-download"></i> ดาวน์โหลด eclaim_sync.zip (เวอร์ชั่นล่าสุด)</a><br/> จากนั้น<b>แตกไฟล์ (Extract / Unzip)</b> ลงในโฟลเดอร์ให้เรียบร้อย (เช่น สร้างโฟลเดอร์ชื่อ <code>eclaim_sync</code> บน Desktop)</li>
              <li>เปิด Google Chrome และพิมพ์ที่ช่อง URL ด้านบน: <code class="bg-light p-1 text-primary">chrome://extensions/</code> แล้วกด Enter</li>
              <li>ที่มุมขวาบนของหน้าจอ ให้คลิกเปิดสวิตช์ <b>โหมดนักพัฒนาซอฟต์แวร์ (Developer mode)</b></li>
              <li>คลิกปุ่ม <b>โหลดส่วนขยายที่ยังไม่ได้แพ็ก (Load unpacked)</b> (มุมซ้ายบน) แล้วคลิกเลือกโฟลเดอร์ <code>eclaim_sync</code> ที่แตกไฟล์ไว้</li>
          </ol>

          <h6 class="fw-bold mb-3 text-warning border-bottom pb-2"><i class="bi bi-gear-fill me-1"></i> ขั้นตอนที่ 2 : ตั้งค่าการส่งข้อมูล (ทำครั้งเดียว)</h6>
          <div class="mb-4 text-muted small">
              <p class="mb-1">เมื่อติดตั้งแล้ว ให้ตั้งค่าที่อยู่ในการส่งข้อมูล (API URL) ดังนี้:</p>
              <div class="bg-light p-3 rounded-3 border">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                       <span class="fw-bold text-dark">URL ที่ต้องคัดลอก:</span>
                       <button class="btn btn-xs btn-primary py-0" onclick="copyToClipboard('{{ url('api/eclaim/sync') }}')">คัดลอก</button>
                  </div>
                  <code id="apiUrlPath" class="text-break text-danger fw-bold">{{ url('api/eclaim/sync') }}</code>
              </div>
              <ol class="mt-2 lh-lg">
                  <li>คลิกที่ไอคอน Extension <b>"RiMS E-Claim Sync"</b></li>
                  <li>คลิกที่ไอคอน <b>⚙️ (ฟันเฟือง)</b> มุมขวาบนของหน้าต่างป๊อปอัป</li>
                  <li><b>คัดลอก URL ด้านบนไปวาง</b> ในช่อง RiMS API URL จากนั้นกด <b>บันทึกการตั้งค่า</b></li>
              </ol>
          </div>
          
          <h6 class="fw-bold mb-3 text-success border-bottom pb-2"><i class="bi bi-2-circle"></i> ขั้นตอนที่ 3 : วิธีการดึงข้อมูล (ทำรายวัน)</h6>
          <ol class="mb-4 text-muted small lh-lg">
              <li>ให้ใช้ Google Chrome เปิดหน้าเว็บ <a href="https://eclaim.nhso.go.th/Client" target="_blank" class="text-decoration-underline fw-bold">E-Claim สปสช.</a> และ Login เข้าสู่ระบบ</li>
              <li>เปิดเข้าสู่หน้าระบบและค้นหาช่วงวันที่ต้องการ เมื่อมีข้อมูลรายชื่อผู้ป่วยแสดงในตารางเรียบร้อยแล้ว</li>
              <li>คลิกที่ <b>ไอคอนส่วนขยาย "RiMS E-Claim Sync"</b> แล้วกดปุ่ม <b>"ดึงข้อมูลเข้าสู่ RiMS"</b> </li>
              <li>รอให้ระบบทำการกวาดตารางและส่งข้อมูลเข้าฐานข้อมูลของโรงพยาบาลจนขึ้นข้อความสำเร็จ</li>
          </ol>

          <div class="alert alert-warning py-2 mb-0" style="font-size: 0.85rem">
             <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <b>หมายเหตุ:</b> หากข้อมูลมีหลายหน้า ต้องคลิกเปลี่ยนหน้า และกดปุ่มซิงค์ทีละหน้า
          </div>
      </div>
      <div class="modal-footer border-0 bg-light">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>
</div>

<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: translateY(-2px); }
    .bg-success-soft { background-color: #d1fae5; }
    /* Row status colors with !important and very high specificity (#list ID) to override DataTable/Bootstrap defaults on td */
    #list.table tbody tr.row-status-1 td { background-color: #ffff99 !important; } /* เหลืองตอง */
    #list.table tbody tr.row-status-2 td { background-color: #ffcccc !important; } /* แดง/ชมพูอ่อน */
    #list.table tbody tr.row-status-3 td { background-color: #ffd8b1 !important; } /* ส้มอ่อน */
    #list.table tbody tr.row-status-4 td { background-color: #ccffff !important; } /* ฟ้าอ่อน/เขียวมินต์ */
    #list.table tbody tr.row-status-0 td { background-color: #ffffff !important; } /* ขาว */
</style>

@endsection

@push('scripts')
<script>
    function showLoading() {
        Swal.fire({
            title: 'กำลังอัปโหลดและตีความไฟล์...',
            html: 'กรุณารอสักครู่ ห้ามปิดหน้าต่างนี้',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'คัดลอกแล้ว!',
                text: 'นำไปวางในช่อง RiMS API URL ในหน้าตั้งค่าของ Extension ได้เลย',
                timer: 2000,
                showConfirmButton: false
            });
        });
    }

    $(document).ready(function () {
      $('#list').DataTable({
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
              extend: 'excelHtml5',
              text: 'Export CSV',
              className: 'btn btn-primary btn-sm rounded-pill shadow-sm',
              title: 'รายงานสถานะ E-Claim วันที่ {{ DateThai($start_date) }} ถึง {{ DateThai($end_date) }}'
            }
        ],
        language: {
            search: "ค้นหา:",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            paginate: {
              previous: "ก่อนหน้า",
              next: "ถัดไป"
            }
        },
        order: [[6, 'desc']] // เรียงวันที่เข้ารับบริการล่าสุดขึ้นก่อน (index 6 คือวันที่รับบริการ)
      });
    });
</script>
@endpush
