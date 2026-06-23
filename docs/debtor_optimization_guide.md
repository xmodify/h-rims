# คู่มือการปรับปรุงประสิทธิภาพระบบลูกหนี้ (Debtor Optimization Guide)

คู่มือนี้สรุปแนวทางการแก้ปัญหาระบบทำงานช้า (Query Slowness) และระบบล่มหน้าเว็บค้าง (HTTP 500 Execution Timeout) ในระบบประมวลผลลูกหนี้เมื่อเลือกรายการจำนวนมาก โดยใช้กรณีศึกษาจากผัง **1102050102_106** เพื่อเป็นต้นแบบในการปรับปรุงผังอื่น ๆ ต่อไป

---

## 1. การปรับปรุง Query ในหน้าแสดงผลหลัก (Speedup 1,500x)

### ปัญหาเดิม
คำสั่งดึงข้อมูลคนไข้มีส่วนที่เชื่อม (`LEFT JOIN`) กับข้อมูลใบเสร็จจากตาราง `rcpt_print` (ซึ่งมีข้อมูลขนาดใหญ่หลายแสนแถว) โดยใช้ Subquery ที่ไม่มีการกรองข้อมูลใด ๆ:
```sql
LEFT JOIN (
    SELECT r.vn, SUM(r.total_amount) AS total_amount, GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
    FROM rcpt_print r
    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
    WHERE a.rcpno IS NULL   
    GROUP BY r.vn
) r ON r.vn = d.vn
```
*ผลเสีย:* MySQL ต้องดึงและจัดกลุ่ม (`GROUP BY`) ข้อมูลใบเสร็จทั้งหมดในระบบโรงพยาบาลขึ้นมาก่อนทุกครั้ง ส่งผลให้คิวรี่ทำงานช้ามาก (ประมาณ 1.6 วินาทีต่อการดึงข้อมูล 70 รายการ)

### แนวทางแก้ไข
ปรับปรุงโดยระบุเจาะจงให้ตาราง `rcpt_print` กรองเฉพาะ `vn` ของคนไข้ที่อยู่ในช่วงเวลาที่เราค้นหาเท่านั้น ผ่านการทำ `INNER JOIN` กับตารางลูกหนี้ตัวหลัก (`d2`) ภายใน Subquery:
```sql
LEFT JOIN (
    SELECT d2.vn, SUM(r.total_amount) AS total_amount, GROUP_CONCAT(r.rcpno ORDER BY r.rcpno) AS rcpno
    FROM hrims.debtor_1102050102_106 d2
    INNER JOIN rcpt_print r ON r.vn = d2.vn
    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
    WHERE a.rcpno IS NULL   
      AND d2.vstdate BETWEEN ? AND ? -- หรือเงื่อนไขที่กรองตามคนไข้ที่ค้นหา
    GROUP BY d2.vn
) r ON r.vn = d.vn
```
*ผลลัพธ์:* ฐานข้อมูลจะทำงานเร็วขึ้นอย่างมหาศาล (ลดเวลาลงเหลือเพียง **0.001 วินาที**)

---

## 2. การปรับปรุงฟังก์ชันปรับปรุงยอด (Bulk Adjustment) เพื่อแก้ Error 500

### ปัญหาเดิม (N+1 Queries)
ฟังก์ชัน `bulk_adj` เดิมจะรับ ID ทั้งหมดที่ติ๊กเลือกแล้วทำการรันคำสั่ง SQL ดึงข้อมูลใบเสร็จคนไข้ทีละรายการในลูป `foreach`:
```php
foreach ($ids as $id) {
    $row = DB::connection('hosxp')->selectOne("SELECT ... WHERE d.vn = ?", [$id]);
    // ทำการประมวลผลและอัปเดตฐานข้อมูลทีละแถว...
}
```
*ผลเสีย:* หากผู้ใช้เลือก 100 รายการ จะรันคำสั่งหนัก ๆ นี้ถึง 100 ครั้ง ส่งผลให้ PHP Timeout หน้าเว็บค้าง และเกิด Error 500

### แนวทางแก้ไขที่ฝั่ง Backend (Controller)
1. เปลี่ยนจากการวนลูป Query ทีละคน มาเป็น**การ Query ทั้งหมดในรอบเดียว** โดยส่งตัวแปรเป็นแบบอาร์เรย์ร่วมกับคำสั่ง `IN`
2. รองรับการทำงานแบบ AJAX โดยส่งค่ากลับไปเป็น JSON แทนการ Redirect:

```php
public function _1102050102_106_bulk_adj(Request $request)
{
    $ids = $request->checkbox_d ?: [];
    if (empty($ids)) {
        return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
    }
    
    $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
    $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
    $adjusted_count = 0;

    // สร้าง placeholders และ parameters สำหรับ Query ชุดเดียว
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, $ids);

    $rows = DB::connection('hosxp')->select("
        SELECT d.vn, d.debtor, d.receive, d.rcpt_money, d.debtor_lock,
               IFNULL(r.total_amount,0) - IFNULL(d.rcpt_money,0) AS total_bill
        FROM hrims.debtor_1102050102_106 d
        LEFT JOIN (
            SELECT r.vn, SUM(r.total_amount) AS total_amount
            FROM rcpt_print r
            LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
            WHERE a.rcpno IS NULL
              AND r.vn IN ($placeholders) -- กรองเฉพาะใบเสร็จคนไข้ที่เลือกปรับปรุงยอด
            GROUP BY r.vn
        ) r ON r.vn = d.vn
        WHERE d.vn IN ($placeholders) -- ดึงข้อมูลเฉพาะคนไข้ที่เลือกทั้งหมดในคำสั่งเดียว
    ", $params);

    foreach ($rows as $row) {
        if ($row && $row->debtor_lock == 'Y') {
            $receive = (float)$row->receive + (float)$row->total_bill;
            $diff = (float)$row->debtor - (float)$receive;
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
                'adj_inc' => $diff > 0 ? $diff : 0,
                'adj_dec' => $diff < 0 ? abs($diff) : 0
            ];

            \App\Models\Debtor_1102050102_106::where('vn', $row->vn)->update($update_data);
            $adjusted_count++;
        }
    }

    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
            'adjusted_count' => $adjusted_count
        ]);
    }
    return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว');
}
```

---

## 3. การทำ Progress Bar (แสดง %) ที่ฝั่ง Frontend (Blade View)

ที่ฝั่งหน้ากาก Javascript แทนที่จะทำการ Submit Form ปกติซึ่งทำให้หน้าเว็บโหลดหมุนติ้วและค้าง ให้เปลี่ยนไปส่งแบบ **AJAX Chunking** เพื่อแบ่งข้อมูลส่งประมวลผลเป็นรอบ ๆ (เช่น รอบละ 100 รายการ) เพื่อแสดงแถบเปอร์เซ็นต์โหลด:

```javascript
function bulkAdjust() {
    const sel = [...document.querySelectorAll('input[name="checkbox_d[]"]:checked')].map(e=>e.value);
    if(!sel.length) { Swal.fire('แจ้งเตือน','กรุณาเลือกรายการ','warning'); return; }
    
    Swal.fire({
        title: 'ปรับปรุงยอดเป็น 0',
        html: `
            <div class="text-center mb-3" style="font-size: 16px; color: #6c757d;">จำนวน ${sel.length} รายการ</div>
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label small fw-bold">หมายเหตุการปรับปรุง</label>
                    <input type="text" id="blk_note" class="form-control rounded-pill" value="ปรับปรุงยอดเป็น 0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">วันที่ปรับปรุง</label>
                    <input type="text" id="blk_date_th" class="form-control rounded-pill datepicker_th" value="${thaiToday()}" readonly>
                    <input type="hidden" id="blk_date" value="${today()}">
                </div>
            </div>
        `,
        icon: 'info', showCancelButton: true, confirmButtonColor: '#ffc107', confirmButtonText: 'ยืนยัน',
        preConfirm: () => { return { note: $('#blk_note').val(), date: $('#blk_date').val() } }
    }).then((r) => {
        if (r.isConfirmed) {
            // แบ่งส่งข้อมูลครั้งละ 100 รายการ
            const chunkSize = 100;
            const chunks = [];
            for (let i = 0; i < sel.length; i += chunkSize) {
                chunks.push(sel.slice(i, i + chunkSize));
            }

            let currentChunkIndex = 0;
            const total = sel.length;
            let totalAdjusted = 0;

            // แสดงหน้ากาก Progress Bar
            Swal.fire({
                title: 'กำลังปรับปรุงยอดเป็น 0...',
                html: `
                    <div class="progress mb-2" style="height: 25px;">
                        <div id="adj-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning text-dark fw-bold" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                    <div id="adj-progress-text" class="text-muted small">กำลังดำเนินการ 0 จากทั้งหมด ${total} รายการ</div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { sendNextAdjChunk(); }
            });

            function sendNextAdjChunk() {
                if (currentChunkIndex >= chunks.length) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: `ปรับปรุงยอดจำนวน ${totalAdjusted} รายการเรียบร้อยแล้ว`,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => { location.reload(); });
                    return;
                }

                const chunk = chunks[currentChunkIndex];

                $.ajax({
                    url: "/debtor/1102050102_106_bulk_adj", // ปรับตาม URL ของแต่ละผัง
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        checkbox_d: chunk,
                        bulk_adj_note: r.value.note,
                        bulk_adj_date: r.value.date
                    },
                    success: function(res) {
                        currentChunkIndex++;
                        totalAdjusted += (res.adjusted_count || 0);

                        const processedCount = Math.min(currentChunkIndex * chunkSize, total);
                        const percent = Math.round((processedCount / total) * 100);

                        // อัปเดตแถบ Progress Bar %
                        const progressBar = document.getElementById('adj-progress-bar');
                        const progressText = document.getElementById('adj-progress-text');
                        if (progressBar) {
                            progressBar.style.width = percent + '%';
                            progressBar.innerText = percent + '%';
                        }
                        if (progressText) {
                            progressText.innerText = `กำลังดำเนินการ ${processedCount} จากทั้งหมด ${total} รายการ`;
                        }

                        sendNextAdjChunk();
                    },
                    error: function() {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปรับปรุงยอดได้สำเร็จ', 'error');
                    }
                });
            }
        }
    });
}
```
