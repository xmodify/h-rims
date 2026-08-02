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
