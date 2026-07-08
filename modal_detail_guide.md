# คู่มือการสร้าง Modal รายละเอียดรับบริการ และระบบตรวจสอบสิทธิ์ (*_detail)

เอกสารนี้ระบุมาตรฐานการออกแบบและขั้นตอนสำหรับการสร้างหน้าจอ Modal รายละเอียดการรับบริการแบบ 3 คอลัมน์ แนวนอน (Landscape) และการเชื่อมต่อระบบข้อมูลหลังบ้าน (*_detail) ในระบบ RiMS

---

## 1. การออกแบบ Modal ฝั่ง Client (HTML & JavaScript)

### HTML Markup (รายละเอียดของ Modal)
ใช้ตัวเลือกขนาดหน้าต่าง `modal-xl` เพื่อแสดงผลแนวตั้ง/แนวนอนแบบ 3 คอลัมน์ได้ครบถ้วนโดยไม่อึดอัด

```html
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-info-circle-fill me-2"></i>รายละเอียดการรับบริการและตรวจสอบสิทธิ์
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- ข้อมูลจะถูกเขียนลงที่นี่ด้วย JavaScript -->
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>
</div>
```

### JavaScript Implementation (`window.showDetails`)
จัดการรับข้อมูลจากฝั่ง Backend ผ่าน AJAX จากนั้นคำนวณสถานะความพร้อม (แดง, เหลือง, เขียว) และวาดคอลัมน์การแสดงผล:

```javascript
window.showDetails = function(vn) {
    const body = document.getElementById('detailsModalBody');
    if (!body) return;
    body.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-arrow-repeat spin me-2"></i>กำลังโหลด...</div>';
    $('#detailsModal').modal('show');

    $.get("{{ url('claim_op/sss_detail') }}", { vn: vn })
        .done(function(data) {
            const visit = data.visit;
            const diagnoses = data.diagnoses;
            const drugs = data.drugs;

            let pdx = visit.pdx || '-';
            let sec_diags = [];
            let procedures = [];
            let has_pdx = false;

            diagnoses.forEach(function(d) {
                if (d.diagtype == '2') {
                    procedures.push(d.icd10);
                } else if (d.diagtype != '1') {
                    sec_diags.push(d.icd10);
                }
                if (d.diagtype == '1') {
                    has_pdx = true;
                }
            });

            // ปุ่มหรือสัญลักษณ์สถานะปิดสิทธิ (สปสช.)
            let endpointBtn = '';
            if (visit.endpoint === 'Y') {
                endpointBtn = `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>ปิดสิทธิแล้ว (สปสช.)</span>`;
            } else {
                endpointBtn = `<button onclick="pullNhsoData('${visit.vstdate}', '${visit.cid}', '${vn}')" class="btn btn-warning btn-sm py-1 px-2 fw-bold" style="font-size:0.75rem;"><i class="bi bi-cloud-download-fill me-1"></i>ดึงข้อมูล (Pull)</button>`;
            }

            let receiptText = parseFloat(visit.rcpt_money) > 0 && visit.rcpno_list ? ` (${visit.rcpno_list})` : '';
            let invoice_no = visit.sss_invno && visit.sss_invno !== '0' ? visit.sss_invno : (visit.debt_id_list && visit.debt_id_list !== '0' ? visit.debt_id_list : '');

            // 1. ตรวจสอบเงื่อนไขข้อผิดพลาด (Validation)
            const errors = [];
            if (!invoice_no || invoice_no === '0') {
                errors.push("ไม่พบเลขใบแจ้งหนี้ (InvoiceNo) กรุณากดออกใบแจ้งหนี้ใน HOSxP");
            }
            if (!visit.cid || visit.cid.length !== 13) {
                errors.push("เลขบัตรประชาชน (CID) ว่างหรือความยาวไม่ครบ 13 หลัก");
            }
            if (!visit.hn) {
                errors.push("ไม่พบ HN");
            }
            if (!has_pdx) {
                errors.push("ไม่พบรหัสวินิจฉัยโรคหลัก (PDX) กรุณาบันทึกแพทย์ผู้ตรวจโรค");
            }
            if (parseFloat(visit.uc_money || 0) <= 0) {
                errors.push("ยอดเงินเรียกเก็บ (uc_money) น้อยกว่าหรือเท่ากับ 0 บาท");
            }

            // 2. คำนวณแบนเนอร์แสดงสถานะความพร้อมด้านบนสุด
            let statusHtml = '';
            if (errors.length > 0) {
                statusHtml = `
                <div class="col-12 mb-2">
                  <div class="alert alert-danger py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fef2f2; color: #991b1b; border-left: 5px solid #dc2626 !important;">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1" style="font-size: 1.1rem; color: #dc2626;"></i>
                    <div>
                      <div class="fw-bold mb-1 text-dark">สถานะ: ไม่ผ่านเกณฑ์ส่งออก (มีข้อผิดพลาดที่ต้องแก้ไข)</div>
                      <ul class="mb-0 ps-3 text-danger">${errors.map(err => `<li>${err}</li>`).join('')}</ul>
                    </div>
                  </div>
                </div>`;
            } else if (visit.endpoint !== 'Y') {
                statusHtml = `
                <div class="col-12 mb-2">
                  <div class="alert alert-warning py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #fffbeb; color: #92400e; border-left: 5px solid #d97706 !important;">
                    <i class="bi bi-exclamation-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #d97706;"></i>
                    <div>
                      <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลผ่านเกณฑ์ แต่ยังไม่ปิดสิทธิ (สปสช.)</div>
                      <div class="text-muted">ข้อมูลผ่านเกณฑ์การตรวจสอบแล้ว แต่กรุณากดดึงข้อมูลหรือปิดสิทธิ สปสช. ให้เรียบร้อยเพื่อส่งออก</div>
                    </div>
                  </div>
                </div>`;
            } else {
                statusHtml = `
                <div class="col-12 mb-2">
                  <div class="alert alert-success py-2 px-3 border-0 shadow-sm d-flex align-items-start small" style="background-color: #f0fdf4; color: #166534; border-left: 5px solid #16a34a !important;">
                    <i class="bi bi-check-circle-fill me-2 mt-1" style="font-size: 1.1rem; color: #16a34a;"></i>
                    <div>
                      <div class="fw-bold mb-1 text-dark">สถานะ: ข้อมูลพร้อมส่งออก (ผ่านเกณฑ์และปิดสิทธิเรียบร้อย)</div>
                      <div class="text-muted">ข้อมูลถูกต้องครบถ้วนและทำการปิดสิทธิเรียบร้อยแล้ว</div>
                    </div>
                  </div>
                </div>`;
            }

            // 3. แสดงคอลัมน์การจัดกลุ่มข้อมูล 3 คอลัมน์ (ข้อมูลผู้ป่วย / ทางคลินิก / การเงิน)
            let html = `
            <style>
              .compact-info-table th, .compact-info-table td {
                  font-size: 12px !important;
                  padding: 6px 12px !important;
                  border-bottom: 1px solid #dee2e6 !important;
              }
              #modal-drugs-table th, #modal-drugs-table td,
              #modal-services-table th, #modal-services-table td {
                  font-size: 12px !important;
                  padding: 6px 8px !important;
              }
              .dataTables_wrapper, .dataTables_info, .dataTables_paginate, .dataTables_length, .dataTables_filter {
                  font-size: 12px !important;
              }
            </style>
            <div class="row g-3">
              ${statusHtml}

              <!-- คอลัมน์ที่ 1: ข้อมูลผู้ป่วย -->
              <div class="col-md-4">
                <div class="card border-0 bg-light h-100">
                  <div class="card-body py-2 px-3" style="font-size: 11px;">
                    <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-person-fill me-1"></i>ข้อมูลผู้ป่วย</div>
                    <table class="table table-sm table-borderless mb-0 w-100 compact-info-table">
                      <tr><th class="text-muted" style="width:43%;">HN</th><td class="fw-bold text-dark" >${visit.hn}</td></tr>
                      <tr><th class="text-muted" >CID</th><td class="text-dark" >${visit.cid ?? '-'}</td></tr>
                      <tr><th class="text-muted" >ชื่อ-สกุล</th><td class="text-dark" >${visit.ptname}</td></tr>
                      <tr><th class="text-muted" >เพศ/อายุ</th><td class="text-dark" >${visit.sex == '1' ? 'ชาย' : (visit.sex == '2' ? 'หญิง' : (visit.sex ?? '-'))} / ${visit.age_y ?? '-'} ปี</td></tr>
                      <tr><th class="text-muted" >สิทธิ์การรักษา</th><td class="text-dark" >${visit.pttype_name ?? '-'}</td></tr>
                      <tr><th class="text-muted" >รพ.หลัก (HMAIN)</th><td class="text-dark fw-bold text-danger" >${visit.hospmain ?? '-'}</td></tr>
                      <tr><th class="text-muted" >Hipdata Code</th><td class="text-dark" >${visit.hipdata_code ?? '-'}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- คอลัมน์ที่ 2: ข้อมูลทางคลินิก -->
              <div class="col-md-4">
                <div class="card border-0 bg-light h-100">
                  <div class="card-body py-2 px-3" style="font-size: 11px;">
                    <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-clipboard2-pulse me-1"></i>ข้อมูลทางคลินิก</div>
                    <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                      <tr><th class="text-muted" style="width:40%;">วันที่รับบริการ</th><td class="text-dark" style="word-break: break-all;">${visit.vstdate} ${visit.vsttime}</td></tr>
                      <tr><th class="text-muted" >CC</th><td class="text-dark" style="word-break: break-all;">${visit.cc ?? '-'}</td></tr>
                      <tr><th class="text-muted" >PDX</th><td class="fw-bold text-danger" style="word-break: break-all;">${pdx}</td></tr>
                      <tr><th class="text-muted" >SDX</th><td class="text-dark" style="word-break: break-all;">${sec_diags.join(', ') || '-'}</td></tr>
                      <tr><th class="text-muted" >ICD-9</th><td class="text-dark" style="word-break: break-all;">${procedures.join(', ') || '-'}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- คอลัมน์ที่ 3: ข้อมูลการเงิน -->
              <div class="col-md-4">
                <div class="card border-0 bg-light h-100">
                  <div class="card-body py-2 px-3" style="font-size: 11px;">
                    <div class="fw-bold text-primary mb-2 small" style="font-size: 12px;"><i class="bi bi-currency-dollar me-1"></i>ข้อมูลการเงิน</div>
                    <table class="table table-sm table-borderless mb-0 w-100 compact-info-table" style="table-layout: fixed;">
                      <tr><th class="text-muted" style="width:40%;">เลขใบแจ้งหนี้</th><td class="fw-bold ${invoice_no ? 'text-success' : 'text-danger'}" style="word-break: break-all;">${invoice_no}</td></tr>
                      <tr><th class="text-muted" >รวมค่ารักษา</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.income).toFixed(2)} บาท</td></tr>
                      <tr><th class="text-muted" >ชำระเงินสด</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.rcpt_money).toFixed(2)} บาท${receiptText}</td></tr>
                      <tr><th class="text-muted" >ยอดเรียกเก็บ</th><td class="text-dark" style="word-break: break-all;">${parseFloat(visit.uc_money || 0).toFixed(2)} บาท</td></tr>
                      <tr><th class="text-muted" >สถานะปิดสิทธิ</th><td >${endpointBtn}</td></tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- ส่วนแท็บรายละเอียดรายการยา และ ค่าบริการรักษาพยาบาล -->
              <div class="col-12 mt-3">
                <ul class="nav nav-tabs nav-tabs-custom mb-2" id="modalDetailTabs" role="tablist" style="font-size: 0.85rem;">
                  <li class="nav-item">
                    <button class="nav-link active fw-bold text-primary" id="modal-drugs-tab" data-bs-toggle="tab" data-bs-target="#modal-drugs-panel" type="button" role="tab"><i class="bi bi-capsule me-1"></i>รายการยา</button>
                  </li>
                  <li class="nav-item">
                    <button class="nav-link fw-bold text-success" id="modal-services-tab" data-bs-toggle="tab" data-bs-target="#modal-services-panel" type="button" role="tab"><i class="bi bi-list-check me-1"></i>ค่ารักษาพยาบาล</button>
                  </li>
                </ul>
                <div class="tab-content" id="modalDetailTabsContent">
                  <!-- แท็บรายการยา -->
                  <div class="tab-pane fade show active" id="modal-drugs-panel" role="tabpanel" style="font-size: 12px;">
                    <table id="modal-drugs-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                      <thead>
                        <tr><th>ชื่อยา/เวชภัณฑ์</th><th class="text-center" width="10%">จำนวน</th><th class="text-end" width="12%">ราคารวม (บาท)</th><th>ประเภทการชำระ</th><th>สิทธิการรักษา</th><th>รหัสมาตรฐาน TMT</th></tr>
                      </thead>
                      <tbody>
                        <!-- รายการยาลูปผ่าน JavaScript -->
                      </tbody>
                    </table>
                  </div>
                  <!-- แท็บค่าบริการรักษาพยาบาล -->
                  <div class="tab-pane fade" id="modal-services-panel" role="tabpanel" style="font-size: 12px;">
                    <table id="modal-services-table" class="table table-sm table-hover align-middle mb-0 small border w-100">
                      <thead>
                        <tr><th>ชื่อบริการ/ค่ารักษาพยาบาล</th><th class="text-center" width="10%">จำนวน</th><th class="text-end" width="12%">ราคารวม (บาท)</th><th>ประเภทการชำระ</th><th>สิทธิการรักษา</th><th>ADP</th></tr>
                      </thead>
                      <tbody>
                        <!-- รายการค่าบริการลูปผ่าน JavaScript -->
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>`;

            body.innerHTML = html;

            // ทำลาย DataTable เก่าถ้ามีการประกาศไว้ก่อนหน้า
            if ($.fn.DataTable.isDataTable('#modal-drugs-table')) { $('#modal-drugs-table').DataTable().destroy(); }
            if ($.fn.DataTable.isDataTable('#modal-services-table')) { $('#modal-services-table').DataTable().destroy(); }

            // ประกาศเปิดใช้ DataTable สำหรับตารางยา
            if (drugs.filter(d => d.icode.startsWith('1')).length > 0) {
                $('#modal-drugs-table').DataTable({
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                    language: { search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } }
                });
            }

            // ประกาศเปิดใช้ DataTable สำหรับตารางค่ารักษา
            if (drugs.filter(d => !d.icode.startsWith('1')).length > 0) {
                $('#modal-services-table').DataTable({
                    pageLength: 5,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "ทั้งหมด"]],
                    language: { search: "ค้นหา:", lengthMenu: "แสดง _MENU_ รายการ", info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ", paginate: { previous: "ก่อนหน้า", next: "ถัดไป" } }
                });
            }

            // ปรับขนาดหน้าจอหัวตารางอัตโนมัติเมื่อกดสลับแท็บ
            $('#modalDetailTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });
        })
        .fail(function(xhr) {
            body.innerHTML = `<div class="alert alert-danger">เกิดข้อผิดพลาด: ${xhr.statusText}</div>`;
        });
};
```

---

## 2. โครงสร้างฝั่ง Server (`*_detail`)

ในการสร้าง endpoint สำหรับส่งข้อมูลแสดงผลรายละเอียด จะต้องเขียนคิวรีดึงข้อมูลประเภทการชำระ (`paidst`) และสิทธิ์การรักษา (`pttype`) สำหรับแต่ละรายการยา/ค่าบริการด้วยเพื่อแปลงรหัสให้เป็นชื่อแสดงผล:

```php
public function sss_detail(Request $request)
{
    $vn = $request->vn;
    if (empty($vn)) {
        return response()->json(['error' => 'Invalid VN'], 400);
    }

    // 1. ดึงข้อมูลประวัติการรับบริการทั่วไปและสิทธิ์การเงินของผู้ป่วย
    $visit = DB::connection('hosxp')->selectOne('
        SELECT o.vstdate, o.vsttime, pt.hn, pt.sex, v.age_y, CONCAT(pt.pname, pt.fname, SPACE(1), pt.lname) AS ptname, pt.cid,
               p.name AS pttype_name, p.hipdata_code, os.cc, v.pdx, v.income, v.uc_money, IFNULL(rc.rcpt_money, 0) AS rcpt_money,
               rc.rcpno_list, v.debt_id_list, osb.invno AS sss_invno, osb.billno AS sss_billno,
               IF((ep.claimCode LIKE "EP%" OR ep.claim_status IN ("success")),"Y",NULL) AS endpoint
        FROM ovst o
        LEFT JOIN patient pt ON pt.hn = o.hn
        LEFT JOIN visit_pttype vp ON vp.vn = o.vn
        LEFT JOIN pttype p ON p.pttype = vp.pttype
        LEFT JOIN opdscreen os ON os.vn = o.vn
        LEFT JOIN vn_stat v ON v.vn = o.vn
        LEFT JOIN ovst_sss_billtran osb ON osb.vn = o.vn
        LEFT JOIN (
            SELECT r.vn, SUM(r.total_amount) AS rcpt_money, GROUP_CONCAT(r.rcpno) AS rcpno_list
            FROM rcpt_print r
            LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
            WHERE a.rcpno IS NULL
            GROUP BY r.vn
        ) rc ON rc.vn = o.vn
        LEFT JOIN hrims.nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
        WHERE o.vn = ?
    ', [$vn]);

    // 2. ดึงรหัสโรค (Diagnoses) ทั้งหมด
    $diagnoses = DB::connection('hosxp')->select('
        SELECT icd10, diagtype 
        FROM ovstdiag 
        WHERE vn = ?
    ', [$vn]);

    // 3. ดึงรายการยาและค่าบริการ (เชื่อมโยง paidst และ pttype เพื่อเอาชื่อเต็ม)
    $drugs = DB::connection('hosxp')->select("
        SELECT op.icode, sd.name, op.qty, op.sum_price, COALESCE(nd.tmtid, sd.sks_drug_code) AS tmtid,
               op.drugusage,
               CONCAT(IFNULL(du.name1,''), ' ', IFNULL(du.name2,''), ' ', IFNULL(du.name3,'')) AS drugusage_text,
               sd.sks_product_category_id,
               op.paidst AS paids, pst.name AS paids_name,
               op.pttype, ptt.name AS pttype_name, ni.nhso_adp_code
        FROM opitemrece op
        INNER JOIN s_drugitems sd ON sd.icode = op.icode
        LEFT JOIN drugusage du ON du.drugusage = op.drugusage
        LEFT JOIN nondrugitems ni ON ni.icode = op.icode
        LEFT JOIN paidst pst ON pst.paidst = op.paidst
        LEFT JOIN pttype ptt ON ptt.pttype = op.pttype
        WHERE op.vn = ?
    ", [$vn]);

    return response()->json([
        'visit' => $visit,
        'diagnoses' => $diagnoses,
        'drugs' => $drugs
    ]);
}
```
