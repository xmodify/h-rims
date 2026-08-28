# คู่มือคำสั่ง Raw SQL Query สำหรับดึงข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim) จากฐานข้อมูล HOSxP

เอกสารนี้รวบรวมคำสั่ง SQL (Raw Query) และ Business Logic สำหรับดึงข้อมูลจากฐานข้อมูล **HOSxP (MySQL/MariaDB)** เพื่อประกอบเป็นชุดข้อมูลมาตรฐาน **16 แฟ้ม (e-Claim)** สำหรับส่งเบิกกองทุน สปสช. (UCS), กรมบัญชีกลาง (OFC), ประกันสังคม (SSS), และองค์กรปกครองส่วนท้องถิ่น (LGO)

---

## 📌 สารบัญแฟ้มข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)

| ลำดับ | ชื่อแฟ้ม | คำอธิบายแฟ้ม | ตารางหลักใน HOSxP ที่ใช้งาน |
| :---: | :--- | :--- | :--- |
| 1 | **INS.txt** | ข้อมูลสิทธิการรักษาพยาบาล | `ovst`, `visit_pttype`, `pttype`, `ovst_seq` |
| 2 | **PAT.txt** | ข้อมูลประวัติผู้ป่วย | `patient`, `ovst` |
| 3 | **OPD.txt** | ข้อมูลการรับบริการผู้ป่วยนอก | `ovst`, `vn_stat` |
| 4 | **IPD.txt** | ข้อมูลการรับบริการผู้ป่วยใน | `ipt`, `ovst` |
| 5 | **ODX.txt** | ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก | `ovstdiag`, `doctor` |
| 6 | **OOP.txt** | ข้อมูลหัตถการผู้ป่วยนอก | `ovst_operation`, `doctor` |
| 7 | **IDX.txt** | ข้อมูลการวินิจฉัยโรคผู้ป่วยใน | `iptdiag`, `doctor` |
| 8 | **IOP.txt** | ข้อมูลหัตถการผ่าตัดผู้ป่วยใน | `ipt_operation`, `doctor` |
| 9 | **ORF.txt** | ข้อมูลการส่งต่อผู้ป่วยนอก (Refer Out) | `referout`, `ovst` |
| 10 | **IRF.txt** | ข้อมูลการส่งต่อผู้ป่วยใน (Refer Out IPD) | `referout`, `ipt` |
| 11 | **LVD.txt** | ข้อมูลการลากลับบ้านของผู้ป่วยใน | `ipt_leave` |
| 12 | **DRU.txt** | ข้อมูลรายการสั่งใช้ยา | `opitemrece`, `drugitems`, `drugusage`, `doctor` |
| 13 | **CHA.txt** | ข้อมูลสรุปค่าบริการ 16 หมวด สปสช. | `opitemrece`, `income` |
| 14 | **CHT.txt** | ข้อมูลสรุปยอดรวมค่าใช้จ่ายและใบเสร็จ | `ovst`, `vn_stat`, `rcpt_print` |
| 15 | **AER.txt** | ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ | `er_regist`, `referout`, `ovst` |
| 16 | **ADP.txt** | ข้อมูลบริการเสริม/อุปกรณ์/PPFS/แลปพิเศษ | `opitemrece`, `nondrugitems`, `doctor` |

---

## 🛠️ กฎการจัดรูปแบบข้อมูลสากล (Global Data Formatting)
1. **ตัวคั่นข้อมูล (Delimiter):** ใช้เครื่องหมาย Pipe (`|`) คั่นระหว่างฟิลด์
2. **รูปแบบวันที่ (Date):** รูปแบบ ค.ศ. 8 หลัก `YYYYMMDD` เช่น `20260827`
3. **รูปแบบเวลา (Time):** รูปแบบ 4 หลัก `HHMM` (ไม่มีเครื่องหมาย `:`) เช่น `0830`
4. **ขึ้นบรรทัดใหม่ (Line Ending):** ใช้ `CRLF` (`\r\n`)

---

## 1. แฟ้ม INS.txt (ข้อมูลสิทธิการรักษาพยาบาล)
* **โครงสร้างฟิลด์:**
  `HN|INSCL|SUBTYPE|CID|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNRNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE`

```sql
SELECT 
    o.hn AS HN,
    COALESCE(p.hipdata_code, o.pttype, 'A2') AS INSCL,
    COALESCE(o.pt_subtype, 'O4') AS SUBTYPE,
    TRIM(pt.cid) AS CID,
    COALESCE(vp.hospmain, '') AS HOSPMAIN,
    COALESCE(vp.hospsub, '') AS HOSPSUB,
    '' AS GOVCODE,
    '' AS GOVNAME,
    COALESCE(vp.claim_code, oq.edc_approve_list_text, vp.auth_code, '') AS PERMITNO,
    '' AS DOCNO,
    '' AS OWNRPID,
    '' AS OWNRNAME,
    COALESCE(o.an, '') AS AN,
    COALESCE(o.vn, '') AS SEQ,
    '' AS SUBINSCL,
    '' AS RELINSCL,
    '' AS HTYPE
FROM ovst o
LEFT JOIN patient pt ON pt.hn = o.hn
LEFT JOIN visit_pttype vp ON vp.vn = o.vn
LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
LEFT JOIN ovst_seq oq ON oq.vn = o.vn
WHERE o.vn IN (:vns) -- หรือระบุ o.vstdate BETWEEN 'YYYY-MM-DD' AND 'YYYY-MM-DD'
ORDER BY o.vstdate, o.vsttime;
```

---

## 2. แฟ้ม PAT.txt (ข้อมูลประวัติผู้ป่วย)
* **โครงสร้างฟิลด์:**
  `HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE`
* **สูตร NAMEPAT:** `ชื่อ<วรรค 2 เคาะ>นามสกุล<วรรค 1 เคาะ>,<วรรค 1 เคาะ>คำนำหน้า`

```sql
SELECT DISTINCT
    :hcode AS HCODE,
    pt.hn AS HN,
    LPAD(TRIM(COALESCE(pt.chwpart, '00')), 2, '0') AS CHANGWAT,
    LPAD(TRIM(COALESCE(pt.amppart, '00')), 2, '0') AS AMPHUR,
    DATE_FORMAT(pt.birthday, '%Y%m%d') AS DOB,
    IF(pt.sex = '2', '2', '1') AS SEX,
    COALESCE(pt.marrystatus, '1') AS MARRIAGE,
    LPAD(TRIM(COALESCE(pt.occupation, '000')), 3, '0') AS OCCUPA,
    COALESCE(pt.nationality, '099') AS NATION,
    TRIM(pt.cid) AS PERSON_ID,
    CONCAT(TRIM(pt.fname), '  ', TRIM(pt.lname), ' , ', TRIM(pt.pname)) AS NAMEPAT,
    TRIM(pt.pname) AS TITLE,
    TRIM(pt.fname) AS FNAME,
    TRIM(pt.lname) AS LNAME,
    '1' AS IDTYPE
FROM ovst o
INNER JOIN patient pt ON pt.hn = o.hn
WHERE o.vn IN (:vns);
```

---

## 3. แฟ้ม OPD.txt (ข้อมูลการรับบริการผู้ป่วยนอก)
* **โครงสร้างฟิลด์:**
  `HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC`

```sql
SELECT 
    o.hn AS HN,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    REPLACE(LEFT(o.vsttime, 5), ':', '') AS TIMEOPD,
    o.vn AS SEQ,
    '1' AS UUC
FROM ovst o
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

---

## 4. แฟ้ม IPD.txt (ข้อมูลการรับบริการผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE`

```sql
SELECT 
    ipt.hn AS HN,
    ipt.an AS AN,
    DATE_FORMAT(ipt.regdate, '%Y%m%d') AS DATEADM,
    REPLACE(LEFT(ipt.regtime, 5), ':', '') AS TIMEADM,
    DATE_FORMAT(ipt.dchdate, '%Y%m%d') AS DATEDSC,
    REPLACE(LEFT(ipt.dchtime, 5), ':', '') AS TIMEDSC,
    COALESCE(ipt.dchstts, '1') AS DISCHS,
    COALESCE(ipt.dchtype, '1') AS DISCHT,
    LPAD(TRIM(COALESCE(ipt.ward, '01')), 2, '0') AS WARDDSC,
    LPAD(TRIM(COALESCE(ipt.spclty, '01')), 2, '0') AS DEPT,
    FORMAT(COALESCE(ipt.bw, 50.000), 3) AS ADM_W,
    '1' AS UUC,
    COALESCE(ipt.svctype, '') AS SVCTYPE
FROM ipt
WHERE ipt.an IN (:ans)
ORDER BY ipt.regdate, ipt.regtime;
```

---

## 5. แฟ้ม ODX.txt (การวินิจฉัยโรคผู้ป่วยนอก)
* **โครงสร้างฟิลด์:**
  `HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ`

```sql
SELECT 
    od.hn AS HN,
    DATE_FORMAT(od.vstdate, '%Y%m%d') AS DATEDX,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    UPPER(REPLACE(TRIM(od.icd10), '.', '')) AS DIAG,
    COALESCE(od.diagtype, '1') AS DXTYPE,
    COALESCE(doc.licenseno, od.doctor, 'ว00000') AS DRDX,
    TRIM(pt.cid) AS PERSON_ID,
    od.vn AS SEQ
FROM ovstdiag od
INNER JOIN ovst o ON o.vn = od.vn
LEFT JOIN patient pt ON pt.hn = od.hn
LEFT JOIN doctor doc ON doc.code = od.doctor
WHERE od.vn IN (:vns)
  AND od.icd10 IS NOT NULL AND od.icd10 <> ''
ORDER BY od.vn, od.diagtype;
```

---

## 6. แฟ้ม OOP.txt (หัตถการผู้ป่วยนอก)
* **โครงสร้างฟิลด์:**
  `HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE`

```sql
SELECT 
    op.hn AS HN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    LPAD(TRIM(COALESCE(o.cur_dep, '01200')), 5, '0') AS CLINIC,
    REPLACE(TRIM(op.icd9), '.', '') AS OPER,
    COALESCE(doc.licenseno, op.doctor, 'พ00000') AS DROPID,
    TRIM(pt.cid) AS PERSON_ID,
    op.vn AS SEQ,
    IF(op.price > 0, FORMAT(op.price, 2), '') AS SERVPRICE
FROM ovst_operation op
INNER JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = op.hn
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icd9 IS NOT NULL AND op.icd9 <> ''
ORDER BY op.vn;
```

---

## 7. แฟ้ม IDX.txt (การวินิจฉัยโรคผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `AN|DIAG|DXTYPE|DRDX`

```sql
SELECT 
    id.an AS AN,
    UPPER(REPLACE(TRIM(id.icd10), '.', '')) AS DIAG,
    COALESCE(id.diagtype, '1') AS DXTYPE,
    COALESCE(doc.licenseno, id.doctor, 'ว00000') AS DRDX
FROM iptdiag id
LEFT JOIN doctor doc ON doc.code = id.doctor
WHERE id.an IN (:ans)
  AND id.icd10 IS NOT NULL AND id.icd10 <> ''
ORDER BY id.an, id.diagtype;
```

---

## 8. แฟ้ม IOP.txt (หัตถการผ่าตัดผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT`

```sql
SELECT 
    io.an AS AN,
    REPLACE(TRIM(io.icd9), '.', '') AS OPER,
    COALESCE(io.opertype, '1') AS OPTYPE,
    COALESCE(doc.licenseno, io.doctor, 'ว00000') AS DROPID,
    DATE_FORMAT(io.opdate, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(io.optime, 5), ':', '') AS TIMEIN,
    DATE_FORMAT(io.enddate, '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(io.endtime, 5), ':', '') AS TIMEOUT
FROM ipt_operation io
LEFT JOIN doctor doc ON doc.code = io.doctor
WHERE io.an IN (:ans)
  AND io.icd9 IS NOT NULL AND io.icd9 <> ''
ORDER BY io.an;
```

---

## 9. แฟ้ม ORF.txt (ส่งต่อผู้ป่วยนอก - Refer Out)
* **โครงสร้างฟิลด์:**
  `HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE`

```sql
SELECT 
    ro.hn AS HN,
    DATE_FORMAT(ro.refer_date, '%Y%m%d') AS DATEOPD,
    LPAD(TRIM(COALESCE(o.cur_dep, '01200')), 5, '0') AS CLINIC,
    TRIM(ro.refer_hospcode) AS REFER,
    COALESCE(ro.refer_type, '2') AS REFERTYPE,
    ro.vn AS SEQ,
    DATE_FORMAT(ro.refer_date, '%Y%m%d') AS REFERDATE
FROM referout ro
INNER JOIN ovst o ON o.vn = ro.vn
WHERE ro.vn IN (:vns)
ORDER BY ro.refer_date;
```

---

## 10. แฟ้ม IRF.txt (ส่งต่อผู้ป่วยใน - Refer Out IPD)
* **โครงสร้างฟิลด์:**
  `AN|REFER|REFERTYPE`

```sql
SELECT 
    o.an AS AN,
    TRIM(ro.refer_hospcode) AS REFER,
    COALESCE(ro.refer_type, '2') AS REFERTYPE
FROM referout ro
INNER JOIN ovst o ON o.vn = ro.vn
WHERE ro.vn IN (:vns)
  AND o.an IS NOT NULL AND o.an <> '';
```

---

## 11. แฟ้ม LVD.txt (การลากลับบ้านของผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY`

```sql
SELECT 
    ipt.hn AS HN,
    il.an AS AN,
    DATE_FORMAT(il.leave_date, '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(il.leave_time, 5), ':', '') AS TIMEOUT,
    DATE_FORMAT(il.return_date, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(il.return_time, 5), ':', '') AS TIMEIN,
    DATEDIFF(il.return_date, il.leave_date) AS QTYDAY
FROM ipt_leave il
INNER JOIN ipt ON ipt.an = il.an
WHERE il.an IN (:ans);
```

---

## 12. แฟ้ม DRU.txt (รายการสั่งใช้ยา)
* **โครงสร้างฟิลด์:**
  `HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRIC|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER`
* **เงื่อนไข:** รายการยาที่มี `icode` ขึ้นต้นด้วย `1`

```sql
SELECT 
    :hcode AS HCODE,
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    TRIM(pt.cid) AS PERSON_ID,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATE_SERV,
    op.icode AS DID,
    REPLACE(TRIM(d.name), '|', ' ') AS DIDNAME,
    IF(op.qty = FLOOR(op.qty), CAST(op.qty AS SIGNED), FORMAT(op.qty, 2)) AS AMOUNT,
    FORMAT(COALESCE(op.unitprice, 0), 2) AS DRUGPRIC,
    FORMAT(COALESCE(op.cost, 0), 2) AS DRUGCOST,
    COALESCE(d.nhso_tmt_id, d.tmt_tp_code, d.tmt_gp_code, d.did, '') AS DIDSTD,
    COALESCE(d.units, 'เม็ด') AS UNIT,
    COALESCE(d.packing, CONCAT('1x', COALESCE(d.units, 'เม็ด'))) AS UNIT_PACK,
    op.vn AS SEQ,
    '' AS DRUGREMARK,
    '' AS PA_NO,
    '0.00' AS TOTCOPAY,
    '' AS USE_STATUS,
    FORMAT(COALESCE(op.sum_price, 0), 2) AS TOTAL,
    COALESCE(du.code, '') AS SIGCODE,
    TRIM(CONCAT(COALESCE(du.name1, ''), ' ', COALESCE(du.name2, ''))) AS SIGTEXT,
    COALESCE(doc.licenseno, '') AS PROVIDER
FROM opitemrece op
INNER JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = op.hn
LEFT JOIN drugitems d ON d.icode = op.icode
LEFT JOIN doctor doc ON doc.code = op.doctor
LEFT JOIN drugusage du ON du.drugusage = op.drugusage
WHERE op.vn IN (:vns)
  AND op.icode LIKE '1%'
ORDER BY op.vn, op.item_no;
```

---

## 13. แฟ้ม CHA.txt (ค่าบริการ 16 หมวด สปสช.)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ`
* **ตารางจับคู่หมวดรายได้ (income mapping):**
  * `01` -> `11` (ค่าห้อง/ค่าอาหาร)
  * `02` -> `21` (ค่าอาหาร)
  * `03`, `17` -> `41` (ค่ายาในบัญชี)
  * `04` -> `42` (ค่ายานอกบัญชี)
  * `05` -> `51` (ค่าเวชภัณฑ์มิใช่ยา)
  * `06` -> `61` (ค่าบริการโลหิต)
  * `07` -> `71` (ค่าตรวจวินิจฉัยทางเทคนิคการแพทย์)
  * `08` -> `81` (ค่าตรวจวินิจฉัยทางรังสี)
  * `09` -> `91` (ตรวจวิธีพิเศษอื่นๆ)
  * `10` -> `A1` (อุปกรณ์/เครื่องมือแพทย์)
  * `11` -> `B1` (หัตถการและวิสัญญี)
  * `12`, `18` -> `C1` (การพยาบาล)
  * `13` -> `D1` (ทันตกรรม)
  * `14` -> `E1` (กายภาพบำบัด)
  * `15` -> `F1` (แพทย์แผนไทย)
  * `16` -> `G1` (บริการอื่นๆ)
  * อื่นๆ -> `H1` (เบ็ดเตล็ด)

```sql
SELECT 
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATE,
    CASE LPAD(TRIM(op.income), 2, '0')
        WHEN '01' THEN '11'
        WHEN '02' THEN '21'
        WHEN '03' THEN '41'
        WHEN '04' THEN '42'
        WHEN '17' THEN '41'
        WHEN '05' THEN '51'
        WHEN '06' THEN '61'
        WHEN '07' THEN '71'
        WHEN '08' THEN '81'
        WHEN '09' THEN '91'
        WHEN '10' THEN 'A1'
        WHEN '11' THEN 'B1'
        WHEN '12' THEN 'C1'
        WHEN '18' THEN 'C1'
        WHEN '13' THEN 'D1'
        WHEN '14' THEN 'E1'
        WHEN '15' THEN 'F1'
        WHEN '16' THEN 'G1'
        ELSE 'H1'
    END AS CHRGITEM,
    FORMAT(SUM(op.sum_price), 2) AS AMOUNT,
    TRIM(pt.cid) AS PERSON_ID,
    op.vn AS SEQ
FROM opitemrece op
LEFT JOIN patient pt ON pt.hn = op.hn
WHERE op.vn IN (:vns)
GROUP BY op.vn, op.hn, op.an, op.vstdate, pt.cid, CHRGITEM
ORDER BY op.vn, CHRGITEM;
```

---

## 14. แฟ้ม CHT.txt (สรุปยอดรวมค่าใช้จ่ายและใบเสร็จ)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ`

```sql
SELECT 
    o.hn AS HN,
    COALESCE(o.an, '') AS AN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATE,
    FORMAT(COALESCE(v.income, 0), 2) AS TOTAL,
    FORMAT(COALESCE(v.rcpt_money, 0), 2) AS PAID,
    COALESCE(p.hipdata_code, o.pttype, 'A2') AS PTTYPE,
    TRIM(pt.cid) AS PERSON_ID,
    o.vn AS SEQ
FROM ovst o
LEFT JOIN vn_stat v ON v.vn = o.vn
LEFT JOIN patient pt ON pt.hn = o.hn
LEFT JOIN visit_pttype vp ON vp.vn = o.vn
LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

---

## 15. แฟ้ม AER.txt (ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT`

```sql
SELECT 
    o.hn AS HN,
    COALESCE(o.an, '') AS AN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    '' AS AUTHAE,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS AEDATE,
    REPLACE(LEFT(er.enter_time, 5), ':', '') AS AETIME,
    '' AS AETYPE,
    COALESCE(ro.refer_number, '') AS REFER_NO,
    '' AS REFMAINI,
    '' AS IREFTYPE,
    COALESCE(ro.refer_hospcode, '') AS REFMAINO,
    '1100' AS OREFTYPE,
    '' AS UCAE,
    '3' AS EMTYPE,
    o.vn AS SEQ,
    '' AS AESTATUS,
    '' AS DALERT,
    '' AS TALERT
FROM er_regist er
INNER JOIN ovst o ON o.vn = er.vn
LEFT JOIN referout ro ON ro.vn = er.vn
WHERE er.vn IN (:vns)
ORDER BY o.vstdate;
```

---

## 16. แฟ้ม ADP.txt (บริการเสริม/อุปกรณ์/PPFS/แลปพิเศษ)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP|LMP|SP_ITEM`
* **เงื่อนไข:** รายการเวชภัณฑ์มิใช่ยา/ค่าบริการ/แลป/หัตถการ (`icode` ที่ไม่ได้ขึ้นต้นด้วย `1`)
* **สูตร CAGCODE:** `{SEQ}:{TYPE}:{CODE}:{RATE}:False`
* **สูตร DOSE:** `{HOS_GUID}` หรือ `{MD5_HASH}`

```sql
SELECT 
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATEOPD,
    COALESCE(op.nhso_adp_type, n.nhso_adp_type, '14') AS TYPE,
    COALESCE(op.nhso_adp_code, n.nhso_adp_code, op.icode) AS CODE,
    IF(op.qty = FLOOR(op.qty), CAST(op.qty AS SIGNED), 1) AS QTY,
    FORMAT(COALESCE(op.unitprice, 0), 2) AS RATE,
    op.vn AS SEQ,
    CONCAT(op.vn, ':', COALESCE(op.nhso_adp_type, n.nhso_adp_type, '14'), ':', COALESCE(op.nhso_adp_code, n.nhso_adp_code, op.icode), ':', FORMAT(COALESCE(op.unitprice, 0), 2), ':False') AS CAGCODE,
    COALESCE(op.hos_guid, CONCAT('{', UPPER(MD5(CONCAT(op.vn, op.icode))), '}')) AS DOSE,
    '' AS CA_TYPE,
    '' AS SERIALNO,
    '0.00' AS TOTCOPAY,
    '' AS USE_STATUS,
    FORMAT(COALESCE(op.sum_price, 0), 2) AS TOTAL,
    '' AS QTYDAY,
    '' AS TMLTCODE,
    '' AS STATUS1,
    '' AS BI,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    '' AS ITEMSRC,
    COALESCE(doc.licenseno, '') AS PROVIDER,
    '' AS GRAVIDA,
    '' AS GA_WEEK,
    '' AS DCIP,
    '' AS LMP,
    '' AS SP_ITEM
FROM opitemrece op
INNER JOIN ovst o ON o.vn = op.vn
LEFT JOIN nondrugitems n ON n.icode = op.icode
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icode NOT LIKE '1%'
ORDER BY op.vn, op.item_no;
```

---

## 🚀 สรุปขั้นตอนและตัวอย่างการประยุกต์ใช้งานใน PHP / Laravel

ในระบบ RiMS ข้อมูลทั้งหมดถูกนำมาประมวลผลผ่าน `App\Services\F16EclaimExportService` โดยทำการ Query ข้อมูลก้อนใหญ่ 1 ครั้งผ่าน connection `hosxp` เพื่อประสิทธิภาพสูงสุดในการประมวลผลหลายหมื่นเรคคอร์ดในเสี้ยววินาที:

```php
use App\Services\F16EclaimExportService;

$vns = ['690701130818', '690701140920'];
$result = F16EclaimExportService::generate16Files($vns);

// $result['files']['INS'] -> เนื้อหาไฟล์ INS.txt
// $result['files']['PAT'] -> เนื้อหาไฟล์ PAT.txt
// $result['counts']       -> สรุปจำนวนแถวของแต่ละแฟ้ม
```
