# คู่มือการปรับปรุงประสิทธิภาพ Query (Database Optimization Guide)

เอกสารนี้รวบรวมแนวทางการปรับปรุงความเร็วและลดภาระการทำงานของฐานข้อมูล HOSxP (MySQL) สำหรับตารางที่มีข้อมูลขนาดใหญ่ เช่น `rcpt_print` และ `opitemrece`

---

## 1. ปัญหา: การทำ Subquery JOIN ด้านล่าง (ช้าและเป็นคอขวด)

### รูปแบบที่เป็นปัญหา (Before)
```sql
SELECT o.vn, rc.rcpt_money
FROM ovst o
LEFT JOIN (
    SELECT r.vn, SUM(r.total_amount) AS rcpt_money
    FROM rcpt_print r
    LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
    WHERE a.rcpno IS NULL
    GROUP BY r.vn
) rc ON rc.vn = o.vn
WHERE o.vstdate BETWEEN '2026-08-01' AND '2026-08-31'
```

### สาเหตุที่ทำงานช้า:
* **Derived Table Materialization**: MySQL จะต้องทำการสแกนตาราง `rcpt_print` ทั้งหมดในระบบ (ประวัติหลายสิบปี มีหลายล้านแถว) นำมาหาผลรวมและจัดกลุ่ม (`GROUP BY`) ก่อน เพื่อสร้างตารางสมมุติขึ้นมา จากนั้นค่อยนำมาจับคู่ (`Join`) กับคนไข้เฉพาะช่วงวันที่เราต้องการ
* ยิ่งตารางมีขนาดใหญ่ขึ้น ระบบจะยิ่งหมุนค้างและช้าลงเรื่อยๆ จนส่งผลกระทบต่อเบราว์เซอร์และ CPU ของเซิร์ฟเวอร์

---

## 2. วิธีแก้ไข: การใช้ Correlated Subquery บนหัว (ใน SELECT)

### รูปแบบที่แนะนำ (After)
```sql
SELECT o.vn,
  (SELECT SUM(r.total_amount) 
   FROM rcpt_print r 
   LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno 
   WHERE r.vn = o.vn AND a.rcpno IS NULL
  ) AS rcpt_money
FROM ovst o
WHERE o.vstdate BETWEEN '2026-08-01' AND '2026-08-31'
```

### ทำไมถึงเร็วกว่า:
* **Index Lookup**: ระบบจะทำการกรองคนไข้เฉพาะช่วงวันที่กำหนดก่อน (เช่น เหลือ 50 เคส) จากนั้นจึงวิ่งไปดึงข้อมูลใน `rcpt_print` เฉพาะ `vn` ของคนไข้ 50 เคสนั้นผ่าน Index ทันที โดยไม่ต้องนำข้อมูลประวัติการรักษาทั้งหมดในอดีตมาประมวลผลก่อน
* **สิทธิ์ตรงตามความเป็นจริง (สำหรับสิทธิเฉพาะ เช่น ประกันสังคม SSS)**: สามารถระบุการเช็คสิทธิย่อยเพิ่มในซับคิวรีได้โดยตรง: `WHERE r.vn = o.vn AND r.pttype = vp.pttype`

---

## 3. การลดคำสั่งซ้ำซ้อนและย้ายการคำนวณไป PHP

ในกรณีที่ต้องมีการใช้ยอดเงินดังกล่าวมาคำนวณต่อ เช่น ยอดเรียกเก็บ (`claim_price = income - rcpt_money`)

### รูปแบบที่ควรหลีกเลี่ยง (คิวรีซ้ำซ้อน)
```sql
SELECT 
  (SELECT SUM(...) FROM opitemrece WHERE vn = o.vn) AS income,
  (SELECT SUM(...) FROM rcpt_print WHERE vn = o.vn) AS rcpt_money,
  -- ต้องเขียน Subquery ซ้ำอีกรอบเพื่อลบกัน ทำให้ DB ทำงานเพิ่มขึ้นเท่าตัว
  (SELECT SUM(...) FROM opitemrece WHERE vn = o.vn) - (SELECT SUM(...) FROM rcpt_print WHERE vn = o.vn) AS claim_price
```

### รูปแบบที่แนะนำ (คำนวณฝั่ง PHP)
1. **ใน SQL SELECT**: ดึงมาเฉพาะยอดที่ต้องการเพียงรอบเดียว
   ```sql
   SELECT 
     (SELECT SUM(...) FROM opitemrece WHERE vn = o.vn) AS income,
     (SELECT SUM(...) FROM rcpt_print WHERE vn = o.vn) AS rcpt_money
   ```
2. **ในฝั่ง PHP Controller**: วนลูปและคำนวณผลต่างก่อนส่งให้หน้า View
   ```php
   foreach ($claim as $row) {
       $row->claim_price = floatval($row->income) - floatval($row->rcpt_money);
   }
   ```
   *วิธีนี้จะลดจำนวนครั้งที่ฐานข้อมูลต้องค้นข้อมูลซ้ำลงถึง **50%***

---

## 4. การปรับปรุงประสิทธิภาพการโหลดกราฟสถิติรายปี (Yearly Chart Optimization)

การคำนวณสถิติ 12 เดือนของปีงบประมาณมักเป็นจุดที่ทำให้หน้าเว็บหมุนค้างและโหลดช้า (3-15 วินาที) เนื่องจากต้องสแกนประวัติการรักษาย้อนหลังทั้งปี

### 4.1 กลยุทธ์การแคชระดับคอนโทรลเลอร์ (Controller Caching Strategy)
ใช้ `Cache::remember` แคชผลลัพธ์ข้อมูลกราฟ 12 เดือนไว้เป็นเวลา **5 นาที (300 วินาที)**:
```php
if (!$request->input('skip_chart')) {
    $chartCacheKey = 'chart_' . $claimCode . '_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
    $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
        // ประมวลผลสถิติ 12 เดือนที่นี่
        return [
            'month' => $month,
            'claim_price' => $claim_price,
            'claim_sent_price' => $claim_sent_price,
            'receive_total' => $receive_total,
        ];
    });

    $month = $chartData['month'] ?? [];
    $claim_price = $chartData['claim_price'] ?? [];
    $claim_sent_price = $chartData['claim_sent_price'] ?? [];
    $receive_total = $chartData['receive_total'] ?? [];
}
```
*ผลลัพธ์: การเปิดหน้าเดิมซ้ำ หรือผู้ใช้งานท่านอื่นเปิดดู จะได้ผลลัพธ์กราฟทันทีใน **0.05 วินาที***

---

### 4.2 การใช้ In-Memory Hash Map และ `$months_map` (สำหรับข้อมูลปริมาณมาก เช่น ผู้ป่วยนอก OP)
ในโมดูลผู้ป่วยนอก (OP) ที่มีข้อมูลหลายหมื่นถึงหลายแสนแถวต่อปี การทำ `JOIN` หลายตารางข้ามฐานข้อมูลใน SQL จะทำให้ Server ค้าง ให้ใช้เทคนิค **In-Memory Aggregation** ใน PHP:

1. **ดึงข้อมูลหลักจาก HOSxP แบบเบา (Raw Data):**
   ```sql
   SELECT o.vn, o.hn, pt.cid, o.vstdate, LEFT(o.vsttime, 5) AS vsttime5,
          YEAR(o.vstdate) AS yr, MONTH(o.vstdate) AS mo, SUM(op.sum_price) AS total_price
   FROM ovst o ...
   WHERE o.vstdate BETWEEN ? AND ?
   GROUP BY o.vn, o.hn, pt.cid, o.vstdate, o.vsttime
   ```

2. **ดึงสถานะส่งและชดเชยมาเป็น Key Map (O(1) Hash Map):**
   ```php
   $fdh_vns = DB::table('fdh_claim_status')->pluck('seq')->filter()->flip()->toArray();
   $eclaim_keys = DB::table('eclaim_status')->whereBetween('vstdate', [$start_date_b, $end_date_b])
       ->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
   $stm_rows = DB::table('stm_ucs')->whereBetween('vstdate', [$start_date_b, $end_date_b])
       ->selectRaw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5)) AS k, SUM(receive_total) AS rec_total")
       ->groupBy(DB::raw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5))"))
       ->pluck('rec_total', 'k')->toArray();
   ```

3. **แมปเดือนปีงบประมาณไทยด้วย `$months_map` ใน PHP:**
   ```php
   $months_map = [
       10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
       1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.',
       4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
       7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.'
   ];

   $monthly_agg = [];
   foreach ($vns_data as $row) {
       $m = (int)$row->mo;
       $y = (int)$row->yr;
       $k_month = sprintf('%04d-%02d', $y, $m);

       $hn_key = $row->hn . '_' . $row->vstdate . '_' . $row->vsttime5;
       $cid_key = $row->cid . '_' . $row->vstdate . '_' . $row->vsttime5;

       $is_sent = isset($fdh_vns[$row->vn]) || isset($eclaim_keys[$hn_key]) || isset($stm_rows[$cid_key]);
       $rec = $stm_rows[$cid_key] ?? 0;

       if (!isset($monthly_agg[$k_month])) {
           $short_year = substr((string)($y + 543), -2);
           $month_name = ($months_map[$m] ?? $m) . ' ' . $short_year;
           $monthly_agg[$k_month] = [
               'month' => $month_name,
               'claim_price' => 0,
               'claim_sent_price' => 0,
               'receive_total' => 0
           ];
       }

       $monthly_agg[$k_month]['claim_price'] += (float)$row->total_price;
       if ($is_sent) {
           $monthly_agg[$k_month]['claim_sent_price'] += (float)$row->total_price;
       }
       $monthly_agg[$k_month]['receive_total'] += (float)$rec;
   }
   ksort($monthly_agg);
   ```

---

### 4.3 การแยกการโหลดตารางและกราฟด้วย `skip_chart` (Frontend-Backend Co-op)
* **เปิดหน้าแรก / เปลี่ยนปีงบประมาณ:** เรียกแบบปกติ ไม่ส่ง `skip_chart` $\rightarrow$ ระบบจะคำนวณ/ดึงแคชกราฟ + ส่งข้อมูลตาราง
* **ค้นหาวันที่ / ตรวจสอบรายคน / รีเฟรชตาราง:** ส่ง `skip_chart: 1` $\rightarrow$ Controller จะข้ามบล็อกการรันกราฟทันที ดึงเฉพาะตารางรายวัน ทำให้ตารางแสดงผลใน **0.1 วินาที**

