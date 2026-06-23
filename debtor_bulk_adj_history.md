# คู่มือการติดตั้งระบบประวัติการปรับปรุงยอดและใบแนบ PDF (Debtor History Log & PDF Guide)

คู่มือนี้จัดทำขึ้นเพื่อใช้เป็นแม่แบบในการย้ายและสร้างระบบ **"ประวัติปรับปรุงยอดลูกหนี้"** พร้อมระบบ **"พิมพ์ใบแนบ PDF แนวตั้ง"** ให้กับผังลูกหนี้อื่น ๆ ทีละผัง โดยแยกส่วนการประมวลผล (SRP) ออกมาจาก Controller หลักมาไว้ที่ `DebtorAdjController` 

---

## สถาปัตยกรรมของระบบ (Architecture)
1. **Controller (`app/Http/Controllers/DebtorAdjController.php`)**: รับผิดชอบการดึงประวัติการปรับปรุงยอด (ฟิลด์ `adj_inc > 0` หรือ `adj_dec > 0`) โดยรอบรับการตอบกลับเป็น **JSON** สำหรับแสดงผลแบบแบ่งหน้าด้วย DataTable และ **PDF Stream** ในรูปแบบแนวตั้ง (Portrait)
2. **View Blade (`resources/views/debtor/1102050101_XXX.blade.php`)**: แสดงปุ่ม "ประวัติปรับปรุง", หน้ากาก Modal แสดงผลข้อมูลประวัติพร้อมระบบค้นหาตามช่วงวันที่แบบ Datepicker และตารางเป็น DataTable แสดงผลทีละ 10 รายการ
3. **PDF Template (`resources/views/debtor_adj/1102050101_XXX_pdf.blade.php`)**: แม่แบบการพิมพ์ใบแนบปรับปรุงยอด ขนาด A4 แนวตั้ง กำหนดคอลัมน์และขนาดฟอนต์ให้พอดี 1 แถวต่อ 1 รายการ พร้อมแถบเซ็นชื่อ

---

## ขั้นตอนการติดตั้งสปินเนอร์และตารางทีละผัง

### ขั้นตอนที่ 1: เพิ่มเมธอดใน `DebtorAdjController.php`

ให้สร้างเมธอดของผังนั้น ๆ โดยเลียนแบบจากโค้ดด้านล่างนี้ (เปลี่ยนชื่อเทเบิล `debtor_1102050101_103` และชื่อ PDF เป็นผังที่ต้องการ เช่น `_1102050102_804`):

```php
    public function _1102050101_XXX(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query ข้อมูลที่มีการปรับปรุงยอด (adj_inc > 0 หรือ adj_dec > 0)
        // สำหรับบางผังที่เก็บด้วยคีย์ an ให้เปลี่ยน vn เป็น an
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_XXX
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_XXX_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_XXX.pdf');
        }

        return abort(404);
    }
```

และหากต้องการย้ายฟังก์ชัน `bulk_adj` ของผังนั้นมารวมที่นี่ ให้ก๊อปปี้ฟังก์ชันจาก `DebtorController` มาไว้ที่นี่ด้วย เช่น:

```php
    public function _1102050101_XXX_bulk_adj(Request $request)
    {
        // โค้ดสำหรับทำ Bulk Adjust แบบรอบเดียว (Batch) จากคู่มือ Debtor Optimization Guide
    }
```

---

### ขั้นตอนที่ 2: ลงทะเบียน Routes ใน `routes/web.php`

ลงทะเบียน Route ใหม่สำหรับประวัติปรับยอด ชี้ไปที่ `DebtorAdjController` และหากมีฟังก์ชัน bulk_adj ที่ย้ายมา ให้เปลี่ยน Class ปลายทางใน Route เก่าด้วย:

```php
// Route ค้นหาประวัติ / พิมพ์ PDF ประวัติการปรับปรุงยอด
Route::get('debtor/adjust_log/1102050101_XXX', [\App\Http\Controllers\DebtorAdjController::class, '_1102050101_XXX']);

// ปรับปรุง Route Bulk Adjust เดิมชี้มาที่ DebtorAdjController (ถ้ามีการย้าย)
Route::post('debtor/1102050101_XXX_bulk_adj', [\App\Http\Controllers\DebtorAdjController::class, '_1102050101_XXX_bulk_adj']);
```

---

### ขั้นตอนที่ 3: ออกแบบหน้ากากฝั่ง Blade View (`resources/views/debtor/1102050101_XXX.blade.php`)

#### A. เพิ่มปุ่มในกลุ่มเครื่องมือควบคุมตาราง
วางปุ่ม **"ประวัติปรับปรุง"** ไว้ข้างๆ ปุ่มส่งออกอื่นๆ:

```html
<button type="button" class="btn btn-outline-info btn-sm" onclick="openAdjModal()">
     <i class="bi bi-journal-text me-1"></i> ประวัติปรับปรุง
</button>
```

#### B. เพิ่ม HTML โครงสร้างของ Modal ไว้ที่ตอนท้ายของไฟล์ (นอกฟอร์มหลัก)
```html
<!-- History Adjustment Modal -->
<div id="adjLogModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-dark border-0 py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-journal-text me-2"></i> ประวัติการปรับปรุงยอดลูกหนี้ 1102050101.XXX-[ชื่อผังบัญชี]
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- ส่วนตัวกรองช่วงวันที่ค้นหาประวัติ -->
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-5 d-flex align-items-center">
                        <span class="input-group-text bg-white text-muted border-end-0 rounded-start">วันที่ปรับยอด</span>
                        <input type="text" id="adj_start_date_picker" class="form-control border-start-0 rounded-0 datepicker_th" value="{{DateThai(date('Y-m-01'))}}" readonly>
                        <input type="hidden" id="adj_start_date" value="{{date('Y-m-01')}}">
                        <span class="input-group-text bg-white border-start-0 border-end-0 rounded-0">ถึง</span>
                        <input type="text" id="adj_end_date_picker" class="form-control border-start-0 rounded-end datepicker_th" value="{{DateThai(date('Y-m-t'))}}" readonly>
                        <input type="hidden" id="adj_end_date" value="{{date('Y-m-t')}}">
                    </div>
                    <div class="col-md-7 d-flex gap-2">
                        <button type="button" class="btn btn-info text-dark fw-bold px-3 shadow-sm" onclick="loadAdjLogs()">
                            <i class="bi bi-search me-1"></i> ค้นหา
                        </button>
                        <a id="btn-adj-print-pdf" class="btn btn-danger fw-bold px-3 shadow-sm" href="#" target="_blank">
                            <i class="bi bi-file-pdf me-1"></i> พิมพ์ใบแนบปรับปรุง (PDF)
                        </a>
                    </div>
                </div>

                <!-- ตารางประวัติปรับยอด -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="adj_logs_table" width="100%">
                        <thead>
                            <tr class="table-info align-middle text-center" style="font-size: 13px;">
                                <th>ลำดับ</th>
                                <th>วันที่ปรับปรุง</th>
                                <th>วันที่บริการ</th>
                                <th>HN</th>
                                <th>ชื่อ-สกุล</th>
                                <th>ยอดลูกหนี้</th>
                                <th>ยอดชดเชย</th>
                                <th>ปรับเพิ่ม</th>
                                <th>ปรับลด</th>
                                <th>เหตุผลการปรับปรุง</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 13px;">
                            <!-- ข้อมูลจะถูกดึงเข้าแบบ AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
```

#### C. เขียน Javascript ไว้ด้านในของบล็อก `@push('scripts')`
*ข้อสังเกต: ต้องอยู่ภายใน `@push('scripts')` เพื่อให้สามารถเรียกใช้ jQuery (`$`) ได้หลังจากเลย์เอาต์หลักโหลดเรียบร้อย*

```javascript
    function openAdjModal() {
        $('#adjLogModal').modal('show');
        updateAdjPdfUrl();
        loadAdjLogs();
    }

    function updateAdjPdfUrl() {
        const start = $('#adj_start_date').val();
        const end = $('#adj_end_date').val();
        const url = `{{ url('debtor/adjust_log/1102050101_XXX') }}?start_date=${start}&end_date=${end}&export_type=pdf`;
        $('#btn-adj-print-pdf').attr('href', url);
    }

    function loadAdjLogs() {
        const start = $('#adj_start_date').val();
        const end = $('#adj_end_date').val();
        
        // ล้าง/ทำลายอินสแตนซ์ DataTable เดิมออกก่อนเพื่อโหลดใหม่
        if ($.fn.DataTable.isDataTable('#adj_logs_table')) {
            $('#adj_logs_table').DataTable().destroy();
        }
        
        $('#adj_logs_table tbody').html(`
            <tr>
                <td colspan="10" class="text-center p-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="text-muted small mt-2">กำลังดึงข้อมูลประวัติการปรับปรุงยอด...</div>
                </td>
            </tr>
        `);

        $.ajax({
            url: `{{ url('debtor/adjust_log/1102050101_XXX') }}`,
            type: 'GET',
            data: {
                start_date: start,
                end_date: end,
                export_type: 'json'
            },
            success: function(res) {
                if (res.success && res.data.length > 0) {
                    let html = '';
                    res.data.forEach((row, index) => {
                        html += `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td class="text-center" style="white-space: nowrap;">${formatThaiDate(row.adj_date)}</td>
                                <td class="text-center" style="white-space: nowrap;">${formatThaiDate(row.vstdate)}</td>
                                <td class="text-center">${row.hn}</td>
                                <td class="text-start">${row.ptname}</td>
                                <td class="text-end">${parseFloat(row.debtor || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end">${parseFloat(row.receive || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end text-purple fw-bold" style="color: purple;">${parseFloat(row.adj_inc || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-end text-primary fw-bold" style="color: blue;">${parseFloat(row.adj_dec || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-start">${row.adj_note || ''}</td>
                            </tr>
                        `;
                    });
                    $('#adj_logs_table tbody').html(html);
                    
                    // เริ่มต้นทำงานของ DataTable 10 แถว
                    $('#adj_logs_table').DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
                        language: {
                            search: "ค้นหา:",
                            lengthMenu: "แสดง _MENU_ รายการ",
                            info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                            infoEmpty: "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
                            zeroRecords: "ไม่พบข้อมูลที่ตรงกัน",
                            paginate: {
                                first: "หน้าแรก",
                                last: "หน้าสุดท้าย",
                                next: "ถัดไป",
                                previous: "ก่อนหน้า"
                            }
                        },
                        columnDefs: [
                            { orderable: false, targets: 0 }
                        ]
                    });
                } else {
                    $('#adj_logs_table tbody').html(`
                        <tr>
                            <td colspan="10" class="text-center p-4 text-muted">ไม่พบข้อมูลการปรับปรุงยอดในช่วงวันที่ระบุ</td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#adj_logs_table tbody').html(`
                    <tr>
                        <td colspan="10" class="text-center p-4 text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td>
                    </tr>
                `);
            }
        });
    }

    function formatThaiDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        const months = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear() + 543}`;
    }

    $(document).ready(function() {
        $('#adj_start_date_picker').datepicker({
            format: 'd M yyyy',
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            todayBtn: 'linked',
            todayHighlight: true
        }).on('changeDate', function(e) {
            if (e.date) {
                const y = e.date.getFullYear(), m = ('0' + (e.date.getMonth() + 1)).slice(-2), d = ('0' + e.date.getDate()).slice(-2);
                $('#adj_start_date').val(y + '-' + m + '-' + d);
                updateAdjPdfUrl();
            }
        });

        $('#adj_end_date_picker').datepicker({
            format: 'd M yyyy',
            autoclose: true,
            language: 'th-th',
            thaiyear: true,
            todayBtn: 'linked',
            todayHighlight: true
        }).on('changeDate', function(e) {
            if (e.date) {
                const y = e.date.getFullYear(), m = ('0' + (e.date.getMonth() + 1)).slice(-2), d = ('0' + e.date.getDate()).slice(-2);
                $('#adj_end_date').val(y + '-' + m + '-' + d);
                updateAdjPdfUrl();
            }
        });
    });
```

---

### ขั้นตอนที่ 4: สร้างไฟล์ PDF View Template

สร้างไฟล์แม่แบบสำหรับการเรนเดอร์ PDF แนวตั้ง A4 ที่ `resources/views/debtor_adj/1102050101_XXX_pdf.blade.php`:

```html
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @font-face {
                font-family: 'THSarabunNew';
                src: url('fonts/thsarabunnew-webfont.eot');
                src: url('fonts/thsarabunnew-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew-webfont.ttf') format('truetype');
                font-weight: normal;
                font-style: normal;
            }        
            @font-face {
                font-family: 'THSarabunNew';
                src: url('fonts/thsarabunnew_bold-webfont.eot');
                src: url('fonts/thsarabunnew_bold-webfont.eot?#iefix') format('embedded-opentype'),
                    url('fonts/thsarabunnew_bold-webfont.woff') format('woff'),
                    url('fonts/thsarabunnew_bold-webfont.ttf') format('truetype');
                font-weight: bold;
                font-style: normal;
            } 
            @page {
                margin: 0cm 0cm;
            }
            header {
                position: fixed;
                font-family: "THSarabunNew";
                top: 0.5cm;
                left: 1.2cm;
                right: 1.2cm;          
                font-size: 11px;
                line-height: 1.0;  
                text-align: center; 
            }
            footer {
                position: fixed;
                font-family: "THSarabunNew";
                bottom: 0.5cm;
                left: 1.2cm;
                right: 1.2cm;          
                font-size: 12px;
                line-height: 0.95;               
            }
            body {
                font-family: "THSarabunNew";
                font-size: 11px;
                line-height: 1.0;  
                margin-top: 3.8cm;
                margin-bottom: 2.5cm;
                margin-left: 1.2cm;
                margin-right: 1.2cm;                     
            }
            table {
                border-collapse: collapse;
                width: 100%;
                font-size: 10.5px;
            }
            table, th, td {
                border: 1px solid black; 
            }   
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center;
                padding: 3px;
            }
            td {
                padding: 3px;
            }
        </style> 
    </head>
    <body>
        <header>
            <div>
                <strong>
                    <span style="font-size: 14px;">ใบแนบรายละเอียดการปรับปรุงยอดลูกหนี้แยกรายตัว</span><br>
                    หน่วยบริการ: {{$hospital_name}} ({{$hospital_code}}) <br>    
                    รหัสผังบัญชี 1102050101.XXX-[ชื่อผังบัญชี] <br>
                    วันที่ปรับยอด {{DateThai($start_date)}} ถึง {{DateThai($end_date)}} <br>
                </strong>
            </div>
        </header>

        <footer> 
            <table width="100%" style="border: none;">
                <tr style="border: none;">
                    <td width="33%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้จัดทำรายงาน<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                    <td width="33%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้ตรวจสอบ<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                    <td width="34%" align="center" style="border: none;">
                        ลงชื่อ.......................................................ผู้อนุมัติ<br>
                        (...................................................)<br>
                        ตำแหน่ง.......................................................<br>
                        วันที่........../........../..........
                    </td>
                </tr>
            </table>
        </footer>

        <main>
            <table>
                <thead>
                      <tr>
                        <th width="4%">ลำดับ</th>
                        <th width="11%">วันที่ปรับปรุง</th>
                        <th width="11%">วันที่บริการ</th>
                        <th width="8%">HN</th>
                        <th width="25%">ชื่อ-สกุล</th>
                        <th width="8%">ยอดลูกหนี้</th>
                        <th width="8%">ยอดชดเชย</th>
                        <th width="8%">ปรับเพิ่ม</th>
                        <th width="8%">ปรับลด</th>
                        <th width="9%">เหตุผลการปรับปรุง</th>
                    </tr>     
                </thead> 
                <tbody>
                    <?php $count = 1; ?>
                    <?php $sum_debtor = 0; $sum_receive = 0; $sum_adj_inc = 0; $sum_adj_dec = 0; ?>
                    @foreach($data as $row)                              
                    <tr>
                        <td align="center">{{$count}}</td> 
                        <td align="center" style="white-space: nowrap;">{{DateThai($row->adj_date)}}</td>
                        <td align="center" style="white-space: nowrap;">{{DateThai($row->vstdate)}}</td>
                        <td align="center">{{$row->hn}}</td>
                        <td align="left" style="white-space: nowrap;">{{$row->ptname}}</td>
                        <td align="right">{{number_format($row->debtor,2)}}</td>
                        <td align="right">{{number_format($row->receive,2)}}</td> 
                        <td align="right" style="color: purple;">{{number_format($row->adj_inc,2)}}</td>
                        <td align="right" style="color: blue;">{{number_format($row->adj_dec,2)}}</td>
                        <td align="left">{{$row->adj_note}}</td>
                    </tr>                
                    <?php 
                        $count++; 
                        $sum_debtor += $row->debtor; 
                        $sum_receive += $row->receive; 
                        $sum_adj_inc += $row->adj_inc;
                        $sum_adj_dec += $row->adj_dec;
                    ?>
                    @endforeach
                     <tr style="font-weight: bold; background-color: #f9f9f9;">
                        <td align="right" colspan="5">รวมทั้งสิ้น &nbsp;</td>   
                        <td align="right">{{number_format($sum_debtor,2)}}</td>
                        <td align="right">{{number_format($sum_receive,2)}}</td> 
                        <td align="right" style="color: purple;">{{number_format($sum_adj_inc,2)}}</td>
                        <td align="right" style="color: blue;">{{number_format($sum_adj_dec,2)}}</td>
                        <td></td>
                    </tr>          
                </tbody>
            </table> 
        </main>           
    </body>
</html>
```
