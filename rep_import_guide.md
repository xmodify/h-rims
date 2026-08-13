# คู่มือระบบนำเข้าไฟล์ผลตรวจสอบเบื้องต้น (REP) และการขยายผลสำหรับสิทธิการรักษาอื่น ๆ

คู่มือนี้จัดทำขึ้นเพื่ออธิบายสถาปัตยกรรมของระบบนำเข้าไฟล์ REP (การตรวจสอบเบื้องต้นจาก สปสช. หรือกองทุนต่าง ๆ) และขั้นตอนการขยายผลเพื่อสร้างโมดูลนำเข้าข้อมูลสิทธิการรักษาอื่น ๆ (เช่น สิทธิประกันสังคม SSS, สิทธิข้าราชการ OFC, หรือองค์กรปกครองส่วนท้องถิ่น LGO)

---

## 1. โครงสร้างฐานข้อมูล (Database Schema)

ระบบของ H-RIMS ไม่ได้ใช้ Laravel Migrations แต่ควบคุมโครงสร้างและตรวจสอบผ่านไฟล์ **`docs/lookup/extracted_schemas.json`** 

เมื่อต้องการสร้างสิทธิใหม่ เช่น SSS (ประกันสังคม) ให้เพิ่มการนิยามตารางในไฟล์ JSON ดังกล่าว:

1. **ตารางหลัก (เช่น `rep_sss`)**: เก็บข้อมูลจริงที่รวมรวมไว้และลบข้อมูลซ้ำออกตอนนำเข้าไฟล์ใหม่
2. **ตารางพัก (Staging Table - เช่น `rep_sssexcel`)**: ใช้สำหรับบันทึกข้อมูล Excel แบบ Chunks (ทีละ 250 แถว) เพื่อป้องกัน Memory Limit จากนั้นจึงทำการ Merge เข้าตารางหลัก

### ตัวอย่างการระบุฟิลด์ขั้นต่ำใน `extracted_schemas.json`
* โครงสร้างฟิลด์ควรลอกเลียนมาจาก `rep_ucs` (ดูโครงสร้างคอลัมน์ทั้งหมดได้ในคู่มือ [rep_ucs_columns_guide.md](file:///d:/Project%20Laravel/h-rims/rep_ucs_columns_guide.md))
* ที่จำเป็นต้องมีเพิ่มเป็นพิเศษคือ:
  - `rep_filename` (string): เก็บชื่อไฟล์ดั้งเดิม
  - `rep_type` (string: `'OP'` หรือ `'IP'`): แยกผู้ป่วยนอกและผู้ป่วยใน
  - `is_appeal` (boolean/tinyint): บอกว่าเป็นไฟล์อุทธรณ์ (`_APPEAL_`) หรือไม่

---

## 2. ขั้นตอนการตั้งค่าในแอปพลิเคชัน (Step-by-Step implementation)

สมมติว่าต้องการทำระบบนำเข้าสิทธิประกันสังคม (**REP SSS**) ให้ปฏิบัติตาม 5 ขั้นตอนหลักดังนี้:

### ขั้นตอนที่ 1: สร้าง Eloquent Models
สร้างโมเดลสำหรับตารางหลักและตารางพัก เช่น:
- `App\Models\Rep_sss`
- `App\Models\Rep_sssexcel`

ระบุคุณสมบัติ `$fillable` ให้ครบถ้วนตามรายชื่อคอลัมน์ของตาราง:
```php
protected $fillable = [
    'rep_filename', 'rep_type', 'is_appeal', 'repno', 'no', 'tran_id', 'hn', 'an', 'cid', 
    'pt_name', 'pt_type', 'datetimeadm', 'vstdate', 'vsttime', 'datetimedch', 'dchdate', 
    'dchtime', 'error_code', 'charge_total', 'net_compensate_nhso', ...
];
```

### ขั้นตอนที่ 2: ผูก Collation เพื่อประสิทธิภาพ (Collation Alignment)
เพื่อป้องกันปัญหา Query ช้าจากการ Join ข้ามตารางกับ HOSxP (เช่น `patient` หรือ `ovst`) ให้ไปเพิ่มชื่อตารางใหม่ในเมธอด `alignColumnCollations()` ในไฟล์ [MainSettingController.php](file:///d:/Project%20Laravel/h-rims/app/Http/Controllers/Admin/MainSettingController.php):
```php
'rep_sss' => ['hn', 'an'],
'rep_sssexcel' => ['hn', 'an'],
```
*ระบบจะแปลง Collation ของฟิลด์ `hn` และ `an` ของตารางเหล่านี้ให้ตรงกับ HOSxP อัตโนมัติเวลาทำการติดตั้งหรือซ่อมแซมระบบ*

### ขั้นตอนที่ 3: ลงทะเบียนระบบนำเข้าใน ImportRepController
สร้างฟังก์ชันการบันทึกใหม่ใน [ImportRepController.php](file:///d:/Project%20Laravel/h-rims/app/Http/Controllers/ImportRepController.php) (ตัวอย่างเช่นฟังก์ชัน `rep_sss_save(Request $request)`):

1. **ระบุดัชนีคอลัมน์ (Column Mapping)**: สร้างอาร์เรย์จับคู่ index ของคอลัมน์ Excel ให้ตรงกับฟิลด์ฐานข้อมูล (ดูดัชนีคอลัมน์จริงจากคู่มือ `rep_ucs_columns_guide.md`)
2. **คัดแยกประเภทข้อมูลและอุทธรณ์จากชื่อไฟล์**:
   ```php
   $rep_type = (stripos($file_name, '_IP_') !== false) ? 'IP' : 'OP';
   $is_appeal = (stripos($file_name, '_APPEAL_') !== false) ? 1 : 0;
   ```
3. **ประมวลผลวันที่**: ตรวจสอบฟอร์แมตวันที่ (`d/m/Y H:i:s`) จาก Excel และใช้ Carbon แปลงเป็น `Y-m-d H:i:s` เพื่อบันทึกลง MySQL
4. **บันทึกลงตารางพัก (Staging Buffer)**: เขียนข้อมูลลง `rep_sssexcel` เป็น Chunk (ครั้งละ 250 - 500 รายการ) เพื่อลดภาระ Memory
5. **ล้างข้อมูลซ้ำและย้ายข้อมูลจากตารางพักไปตารางหลัก (Merge Process)**:
   - ตรวจสอบว่าในตารางหลักมีชื่อไฟล์เดิมอยู่หรือไม่ หากมีให้สั่งลบออกก่อน เพื่อรองรับการนำเข้าไฟล์เดิมซ้ำ (Overwrite)
   - สั่งรันคำสั่ง `INSERT INTO rep_sss SELECT ... FROM rep_sssexcel`
   - เคลียร์ตารางพัก `TRUNCATE TABLE rep_sssexcel`

### ขั้นตอนที่ 4: สร้างระบบจัดการและหน้าต่างแสดงผล (Views & View Routing)
1. **เพิ่มแถบแท็บ (Tab/Route/Link)** ในระบบเมนูหลัก [app.blade.php](file:///d:/Project%20Laravel/h-rims/resources/views/layouts/app.blade.php) หรือหน้าดัชนีนำเข้า [rep_index.blade.php](file:///d:/Project%20Laravel/h-rims/resources/views/import/rep_index.blade.php) เพื่อให้กดเลือกประเภทการอัปโหลดไฟล์ของสิทธิใหม่
2. **สร้าง View สรุปผล**: คัดลอกรูปแบบจาก `resources/views/import/rep_ucs.blade.php` มาเป็น `rep_sss.blade.php` 
   - แสดงตัวเลขสรุปสะสมรายปีงบประมาณ
   - แสดงรายการไฟล์ที่นำเข้า พร้อมปุ่มลบไฟล์
   - แสดงลิงก์ Modal แสดงรายละเอียดคนไข้ที่ส่งไม่ผ่าน (คัดเลือกเฉพาะคนไข้ที่มี `error_code` ไม่เป็นค่าว่าง)
   - สร้างสคริปต์วาดกราฟสะสมรหัสติด C (C-Code Error Distribution Chart)
3. **อัปเดต Routes** ใน `routes/web.php` สำหรับรองรับการบันทึกข้อมูล ดูรายชื่อคนไข้ไม่ผ่าน และลบไฟล์

### ขั้นตอนที่ 5: เชื่อมโยงประวัติและผลลัพธ์ในหน้าจอตรวจสอบ (Claims & Audits Integration)
1. **ส่วนหน้ารายชื่อเคลม**: ในหน้าจอตรวจสอบสิทธินั้น ๆ (เช่น `claim_op/sss_incup` หรือ `claim_ip/sss`) ปรับคิวรี SQL สำหรับแท็บการส่งเคลม/เรียกเก็บเงิน
2. **เชื่อม Join เข้าตาราง REP**:
   ```sql
   LEFT JOIN (
       SELECT hn, vstdate, GROUP_CONCAT(DISTINCT error_code) AS error_code, GROUP_CONCAT(DISTINCT repno) AS repno
       FROM hrims.rep_sss
       WHERE rep_type = "OP" AND vstdate BETWEEN ? AND ?
       GROUP BY hn, vstdate
   ) rep ON rep.hn = o.hn AND rep.vstdate = o.vstdate
   ```
   *หมายเหตุ: สำหรับ IPD ให้ Join บน `an` หรือ `hn` และ `dchdate` (วันที่จำหน่าย) หรือ `datetimeadm`*
3. **ปรับแต่งหน้าตารางของสิทธิใหม่**: 
   - แสดงป้ายแจ้งเตือน **`C: [รหัส]`** ในคอลัมน์ข้อผิดพลาด (Error column)
   - แสดงเลขที่ REP ที่ดึงได้จากตาราง `rep_sss` เข้าไปในคอลัมน์ REP No. เพื่อทำหน้าที่แสดงผลล่วงหน้าก่อนที่ Statement จะส่งมา

---

## 3. ข้อสังเกตและแนวทางปฏิบัติที่สำคัญ (Best Practices)

- **การใช้ Index ในซับคิวรี (Subquery Indexing)**:
  เมื่อเขียน Join ตาราง REP เข้ากับตารางเคลมหลัก ให้ใส่เงื่อนไขฟิลเตอร์วันที่ (`vstdate BETWEEN ? AND ?` หรือ `dchdate BETWEEN ? AND ?`) เข้าไปในวงเล็บของ Subquery ด้วยเสมอ เพื่อให้ฐานข้อมูลใช้ Index ในการดึงเฉพาะเคสของหน้าจอนั้น ๆ มาคำนวณ แทนที่จะรัน Full Scan ข้อมูลทั้งหมดในตาราง REP
- **การระบุชื่อประเภทไฟล์อุทธรณ์**:
  โค้ดนำเข้าของตารางพักและตารางหลักใช้การตรวจจับคีย์เวิร์ด `_APPEAL_` เป็นหลัก ดังนั้นเมื่อสิทธิ์อื่นต้องการดึงไฟล์อุทธรณ์เข้ามา ควรใช้โครงสร้างแบบเดียวกันเพื่อเปิดใช้งาน Badges อุทธรณ์อัตโนมัติ
