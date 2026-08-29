# คู่มือคำสั่ง Raw SQL Query และโครงสร้างมาตรฐาน 17 แฟ้ม e-Claim (สปสช. พ.ศ. ๒๕๖๔) จากฐานข้อมูล HOSxP

เอกสารนี้รวบรวมคำสั่ง SQL (Raw Query), Business Logic และโครงสร้างข้อมูลมาตรฐาน **17 แฟ้ม e-Claim** ตามประกาศสำนักงานหลักประกันสุขภาพแห่งชาติ (สปสช.) เรื่องโครงสร้างชุดข้อมูลเพื่อการเรียกเก็บค่าใช้จ่ายเพื่อบริการสาธารณสุข พ.ศ. ๒๕๖๔ สำหรับส่งเบิกกองทุน สปสช. (UCS), กรมบัญชีกลาง (OFC), ประกันสังคม (SSS), รถไฟ (SRT), กทม. (BKK) และองค์กรปกครองส่วนท้องถิ่น (LGO)

> 💡 **หมายเหตุ:** หากต้องการดูโครงสร้าง **17 แฟ้ม FDH (Financial Data Hub)** สามารถดูได้ที่ [docs/fdh/f16_fdh_hosxp_sql_manual.md](file:///d:/Project%20Laravel/h-rims/docs/fdh/f16_fdh_hosxp_sql_manual.md)

---

## 📌 สารบัญแฟ้มข้อมูลมาตรฐาน 17 แฟ้ม (e-Claim ประกาศ สปสช. ๒๕๖๔)

| ลำดับ | ชื่อแฟ้ม | จำนวนฟิลด์ | คำอธิบายแฟ้ม | ตารางหลักใน HOSxP ที่ใช้งาน |
| :---: | :--- | :---: | :--- | :--- |
| 1 | **INS.txt** | 19 | ข้อมูลสิทธิการรักษาพยาบาล (มี HCODE, OWNNAME) | `ovst`, `ipt`, `visit_pttype`, `pttype`, `ovst_seq`, `opdscreen` |
| 2 | **PAT.txt** | 15 | ข้อมูลประวัติผู้ป่วย (ชื่อ-นามสกุล, CID, ที่อยู่) | `patient`, `ovst`, `ipt` |
| 3 | **OPD.txt** | 15 | ข้อมูลการตรวจรักษา OPD (รวม Vital Signs และ CC) | `ovst`, `vn_stat`, `opdscreen` |
| 4 | **ORF.txt** | 7 | ข้อมูลการส่งต่อผู้ป่วยนอก (Refer Out OPD) | `referout`, `ovst` |
| 5 | **ODX.txt** | 8 | ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก (มี PERSON_ID, SEQ) | `ovstdiag`, `doctor`, `patient` |
| 6 | **OOP.txt** | 8 | ข้อมูลหัตถการผู้ป่วยนอก (มี SERVPRICE) | `ovst_operation`, `doctor`, `patient` |
| 7 | **IPD.txt** | 13 | ข้อมูลการรับบริการผู้ป่วยใน | `ipt`, `an_stat` |
| 8 | **IRF.txt** | 3 | ข้อมูลการส่งต่อผู้ป่วยใน (Refer Out IPD) | `referout`, `ipt` |
| 9 | **IDX.txt** | 4 | ข้อมูลการวินิจฉัยโรคผู้ป่วยใน | `iptdiag`, `doctor` |
| 10 | **IOP.txt** | 8 | ข้อมูลหัตถการผ่าตัดผู้ป่วยใน | `ipt_operation`, `doctor` |
| 11 | **CHT.txt** | 11 | ข้อมูลสรุปยอดรวมค่าใช้จ่ายและใบเสร็จ | `vn_stat`, `an_stat`, `rcpt_print`, `pttype` |
| 12 | **CHA.txt** | 7 | ข้อมูลสรุปค่าบริการ 16 หมวด สปสช. (มี PERSON_ID, SEQ) | `opitemrece`, `income`, `patient` |
| 13 | **AER.txt** | 18 | ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ | `er_regist`, `referout`, `ovst`, `ipt_accident` |
| 14 | **ADP.txt** | 27 | ข้อมูลบริการเสริม/อุปกรณ์/หัตถการ/PPFS | `opitemrece`, `nondrugitems`, `doctor` |
| 15 | **LVD.txt** | 7 | ข้อมูลการลากลับบ้านของผู้ป่วยใน (มี QTYDAY) | `ipt_leave` |
| 16 | **DRU.txt** | 24 | ข้อมูลรายการสั่งใช้ยา (มี DRUGPRICE, DRUGCOST, TOTCOPAY, TOTAL) | `opitemrece`, `drugitems`, `drugusage`, `doctor` |
| 17 | **LABFU.txt** | 7 | ข้อมูลผลตรวจทางห้องปฏิบัติการติดตามการรักษา | `lab_head`, `lab_order`, `lab_items` |

---

## 📁 มาตรฐานการตั้งชื่อโฟลเดอร์ส่งออก (Export Subfolder Convention)

ระบบ H-RIMS กำหนดโครงสร้างชื่อโฟลเดอร์สำหรับนำเข้าโปรแกรม e-Claim Client ดังนี้:

- **ผู้ป่วยนอก (OPD):** `F16_ECLAIM_OP_{สิทธิ}_{ปีพ.ศ.เดือนวัน_เวลา}`
  - ตัวอย่าง: `F16_ECLAIM_OP_OFC_25690829_1346`
  - ตัวอย่าง: `F16_ECLAIM_OP_UCS_25690829_1346`
  - ตัวอย่าง: `F16_ECLAIM_OP_LGO_25690829_1346`
- **ผู้ป่วยใน (IPD):** `F16_ECLAIM_IP_{สิทธิ}_{ปีพ.ศ.เดือนวัน_เวลา}`
  - ตัวอย่าง: `F16_ECLAIM_IP_OFC_25690829_1346`
  - ตัวอย่าง: `F16_ECLAIM_IP_UCS_25690829_1346`

---

## 💰 กฎดุลการเงินและการแยกยอดเบิกได้/เบิกไม่ได้ (Financial & paidst Rules)

### 1. การแยกยอดในแฟ้ม `DRU` และ `ADP` รายบรรทัด:
อ้างอิงจากตาราง `paidst` ของ HOSxP:
- **`paidst = '02'` (ลูกหนี้สิทธิ):** คือยอดที่โรงพยาบาลตั้งเบิกจากกองทุน
  - `TOTAL` (ยอดขอเบิก) = `sum_price`
  - `TOTCOPAY` (เบิกไม่ได้/จ่ายเอง) = `0`
- **`paidst <> '02'` (เช่น `01` ชำระเองเบิกได้, `03` ชำระเองเบิกไม่ได้, `04` ส่วนลด):**
  - `TOTAL` (ยอดขอเบิก) = `0.00`
  - `TOTCOPAY` (เบิกไม่ได้/จ่ายเอง) = `sum_price`

### 2. กฎการตรวจสอบดุลการเงินข้ามแฟ้ม (Audit Rules):
1. **$\sum \text{CHA.AMOUNT} = \text{CHT.TOTAL}$** (ยอดรวมทุกหมวดใน CHA ต้องเท่ากับยอดรวมค่ารักษาทั้งหมดใน CHT พอดี 100%)
2. **`CHT.TOTAL` = `CHT.PAID` (จ่ายเอง/เบิกไม่ได้) + `ยอดลูกหนี้สิทธิ` (ยอดขอเบิก)**
3. **`CHT.PAID` $\approx \sum \text{DRU.TOTCOPAY} + \sum \text{ADP.TOTCOPAY}$**

---

## 🛠️ กฎการจัดรูปแบบข้อมูลสากล (Global Data Formatting)
1. **ตัวคั่นข้อมูล (Delimiter):** ใช้เครื่องหมาย Pipe (`|`) คั่นระหว่างฟิลด์
2. **รูปแบบวันที่ (Date):** รูปแบบ ค.ศ. 8 หลัก `YYYYMMDD` เช่น `20260829`
3. **รูปแบบเวลา (Time):** รูปแบบ 4 หลัก `HHMM` (ไม่มีเครื่องหมาย `:`) เช่น `0830`
4. **ขึ้นบรรทัดใหม่ (Line Ending):** ใช้ `CRLF` (`\r\n`)
5. **การเข้ารหัสตัวอักษร:** `TIS-620 / Windows-874 (ANSI)` หรือ `UTF-8`

---

## 1. แฟ้ม INS.txt (ข้อมูลสิทธิการรักษาพยาบาล - 19 คอลัมน์)
* **Header:**
  `HN|INSCL|SUBTYPE|CID|HCODE|DATEEXP|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE`

```sql
SELECT 
    o.hn AS HN,
    COALESCE(p.hipdata_code, o.pttype, 'UCS') AS INSCL,
    COALESCE(o.pt_subtype, '10') AS SUBTYPE,
    TRIM(pt.cid) AS CID,
    :hcode AS HCODE,
    DATE_FORMAT(vp.expire_date, '%Y%m%d') AS DATEEXP,
    COALESCE(vp.hospmain, '') AS HOSPMAIN,
    COALESCE(vp.hospsub, '') AS HOSPSUB,
    COALESCE(vp.nhso_govcode, '') AS GOVCODE,
    COALESCE(vp.nhso_govname, '') AS GOVNAME,
    COALESCE(oq.edc_approve_list_text, vp.claim_code, vp.auth_code, '') AS PERMITNO,
    COALESCE(vp.nhso_docno, '') AS DOCNO,
    COALESCE(vp.nhso_ownright_pid, '') AS OWNRPID,
    COALESCE(vp.nhso_ownright_name, '') AS OWNNAME,
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
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

---

## 2. แฟ้ม PAT.txt (ข้อมูลประวัติผู้ป่วย - 15 คอลัมน์)
* **Header:**
  `HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE`
* **สูตร NAMEPAT:** `ชื่อ<วรรค 2 เคาะ>นามสกุล<วรรค 1 เคาะ>,<วรรค 1 เคาะ>คำนำหน้า`

```sql
SELECT DISTINCT
    :hcode AS HCODE,
    pt.hn AS HN,
    LPAD(TRIM(COALESCE(pt.chwpart, '00')), 2, '0') AS CHANGWAT,
    LPAD(TRIM(COALESCE(pt.amppart, '00')), 2, '0') AS AMPHUR,
    DATE_FORMAT(pt.birthday, '%Y%m%d') AS DOB,
    pt.sex AS SEX,
    COALESCE(pt.marrystatus, '1') AS MARRIAGE,
    COALESCE(pt.occupation, '000') AS OCCUPA,
    COALESCE(pt.citizenship, '099') AS NATION,
    TRIM(pt.cid) AS PERSON_ID,
    CONCAT(TRIM(pt.fname), '  ', TRIM(pt.lname), ' , ', TRIM(pt.pname)) AS NAMEPAT,
    TRIM(pt.pname) AS TITLE,
    TRIM(pt.fname) AS FNAME,
    TRIM(pt.lname) AS LNAME,
    '1' AS IDTYPE
FROM patient pt
WHERE pt.hn IN (:hns);
```

---

## 3. แฟ้ม OPD.txt (ข้อมูลการรับบริการผู้ป่วยนอก - 15 คอลัมน์)
* **Header:**
  `HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTEMP|SBP|DBP|PR|RR|OPTYPE|TYPEIN|TYPEOUT`

```sql
SELECT 
    o.hn AS HN,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    DATE_FORMAT(o.vsttime, '%H%i') AS TIMEOPD,
    o.vn AS SEQ,
    '1' AS UUC,
    REPLACE(COALESCE(os.cc, 'ตรวจรักษาทั่วไป'), '|', ' ') AS DETAIL,
    COALESCE(os.temperature, '') AS BTEMP,
    COALESCE(os.bps, '') AS SBP,
    COALESCE(os.bpd, '') AS DBP,
    COALESCE(os.pulse, '') AS PR,
    COALESCE(os.rr, '') AS RR,
    '1' AS OPTYPE,
    '1' AS TYPEIN,
    '1' AS TYPEOUT
FROM ovst o
LEFT JOIN opdscreen os ON os.vn = o.vn
WHERE o.vn IN (:vns);
```

---

## 4. แฟ้ม ORF.txt (ข้อมูลการส่งต่อผู้ป่วยนอก - 7 คอลัมน์)
* **Header:**
  `HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE`

```sql
SELECT 
    r.hn AS HN,
    DATE_FORMAT(r.refer_date, '%Y%m%d') AS DATEOPD,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    r.refer_hospcode AS REFER,
    '1' AS REFERTYPE,
    r.vn AS SEQ,
    DATE_FORMAT(r.refer_date, '%Y%m%d') AS REFERDATE
FROM referout r
JOIN ovst o ON o.vn = r.vn
WHERE r.vn IN (:vns);
```

---

## 5. แฟ้ม ODX.txt (ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก - 8 คอลัมน์)
* **Header:**
  `HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX|PERSON_ID|SEQ`

```sql
SELECT 
    od.hn AS HN,
    DATE_FORMAT(od.vstdate, '%Y%m%d') AS DATEDX,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    REPLACE(TRIM(od.icd10), '.', '') AS DIAG,
    od.diagtype AS DXTYPE,
    COALESCE(doc.licenseno, 'ว00000') AS DRDX,
    TRIM(pt.cid) AS PERSON_ID,
    od.vn AS SEQ
FROM ovstdiag od
JOIN ovst o ON o.vn = od.vn
LEFT JOIN patient pt ON pt.hn = od.hn
LEFT JOIN doctor doc ON doc.code = od.doctor
WHERE od.vn IN (:vns)
ORDER BY od.vn, od.diagtype;
```

---

## 6. แฟ้ม OOP.txt (ข้อมูลหัตถการผู้ป่วยนอก - 8 คอลัมน์)
* **Header:**
  `HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE`

```sql
SELECT 
    op.hn AS HN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATEOPD,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    REPLACE(TRIM(op.icd9), '.', '') AS OPER,
    COALESCE(doc.licenseno, 'ว00000') AS DROPID,
    TRIM(pt.cid) AS PERSON_ID,
    op.vn AS SEQ,
    '0.00' AS SERVPRICE
FROM ovst_operation op
JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = op.hn
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns);
```

---

## 7. แฟ้ม IPD.txt (ข้อมูลการรับบริการผู้ป่วยใน - 13 คอลัมน์)
* **Header:**
  `HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE`

```sql
SELECT 
    i.hn AS HN,
    i.an AS AN,
    DATE_FORMAT(i.regdate, '%Y%m%d') AS DATEADM,
    DATE_FORMAT(i.regtime, '%H%i') AS TIMEADM,
    DATE_FORMAT(i.dchdate, '%Y%m%d') AS DATEDSC,
    DATE_FORMAT(i.dchtime, '%H%i') AS TIMEDSC,
    COALESCE(i.dchstts, '01') AS DISCHS,
    COALESCE(i.dchtype, '1') AS DISCHT,
    LPAD(COALESCE(i.ward, '01'), 2, '0') AS WARDDSC,
    LPAD(COALESCE(i.dept, '01'), 2, '0') AS DEPT,
    COALESCE(i.adm_weight, 0) AS ADM_W,
    '1' AS UUC,
    '1' AS SVCTYPE
FROM ipt i
WHERE i.an IN (:ans);
```

---

## 8. แฟ้ม IRF.txt (ข้อมูลการส่งต่อผู้ป่วยใน - 3 คอลัมน์)
* **Header:**
  `AN|REFER|REFERTYPE`

```sql
SELECT 
    r.vn AS AN,
    r.refer_hospcode AS REFER,
    '1' AS REFERTYPE
FROM referout r
WHERE r.vn IN (:ans);
```

---

## 9. แฟ้ม IDX.txt (ข้อมูลการวินิจฉัยโรคผู้ป่วยใน - 4 คอลัมน์)
* **Header:**
  `AN|DIAG|DXTYPE|DRDX`

```sql
SELECT 
    id.an AS AN,
    REPLACE(TRIM(id.icd10), '.', '') AS DIAG,
    id.diagtype AS DXTYPE,
    COALESCE(doc.licenseno, 'ว00000') AS DRDX
FROM iptdiag id
LEFT JOIN doctor doc ON doc.code = id.doctor
WHERE id.an IN (:ans)
ORDER BY id.an, id.diagtype;
```

---

## 10. แฟ้ม IOP.txt (ข้อมูลหัตถการผ่าตัดผู้ป่วยใน - 8 คอลัมน์)
* **Header:**
  `AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT`

```sql
SELECT 
    io.an AS AN,
    REPLACE(TRIM(io.icd9), '.', '') AS OPER,
    COALESCE(io.optype, '1') AS OPTYPE,
    COALESCE(doc.licenseno, 'ว00000') AS DROPID,
    DATE_FORMAT(io.opdate, '%Y%m%d') AS DATEIN,
    DATE_FORMAT(io.optime, '%H%i') AS TIMEIN,
    DATE_FORMAT(io.enddate, '%Y%m%d') AS DATEOUT,
    DATE_FORMAT(io.endtime, '%H%i') AS TIMEOUT
FROM ipt_operation io
LEFT JOIN doctor doc ON doc.code = io.doctor
WHERE io.an IN (:ans);
```

---

## 11. แฟ้ม CHT.txt (ข้อมูลสรุปยอดรวมค่าใช้จ่ายและใบเสร็จ - 11 คอลัมน์)
* **Header:**
  `HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT`

```sql
SELECT 
    v.hn AS HN,
    COALESCE(v.an, '') AS AN,
    DATE_FORMAT(v.vstdate, '%Y%m%d') AS DATE,
    ROUND(v.income, 2) AS TOTAL,
    ROUND(COALESCE(v.paid_money, v.rcpt_money, 0), 2) AS PAID,
    p.hipdata_code AS PTTYPE,
    TRIM(pt.cid) AS PERSON_ID,
    v.vn AS SEQ,
    '' AS OPD_MEMO,
    '' AS INVOICE_NO,
    '' AS INVOICE_LT
FROM vn_stat v
JOIN patient pt ON pt.hn = v.hn
LEFT JOIN pttype p ON p.pttype = v.pttype
WHERE v.vn IN (:vns);
```

---

## 12. แฟ้ม CHA.txt (ข้อมูลสรุปค่าบริการ 16 หมวด สปสช. - 7 คอลัมน์)
* **Header:**
  `HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ`

```sql
SELECT 
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATE,
    COALESCE(inc.drg_chrgitem_id, op.income, '16') AS CHRGITEM,
    ROUND(SUM(op.sum_price), 2) AS AMOUNT,
    TRIM(pt.cid) AS PERSON_ID,
    op.vn AS SEQ
FROM opitemrece op
JOIN patient pt ON pt.hn = op.hn
LEFT JOIN income inc ON inc.income = op.income
WHERE op.vn IN (:vns)
GROUP BY op.vn, op.hn, op.an, op.vstdate, pt.cid, COALESCE(inc.drg_chrgitem_id, op.income, '16')
ORDER BY op.vn, CHRGITEM;
```

---

## 13. แฟ้ม AER.txt (ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ - 18 คอลัมน์)
* **Header:**
  `HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT`

```sql
SELECT 
    er.hn AS HN,
    COALESCE(er.an, '') AS AN,
    DATE_FORMAT(er.vstdate, '%Y%m%d') AS DATEOPD,
    '' AS AUTHAE,
    DATE_FORMAT(er.vstdate, '%Y%m%d') AS AEDATE,
    DATE_FORMAT(er.vsttime, '%H%i') AS AETIME,
    '' AS AETYPE,
    COALESCE(ro.refer_number, '') AS REFER_NO,
    COALESCE(ro.refer_hospcode, '') AS REFMAINI,
    CASE WHEN ro.refer_hospcode IS NOT NULL THEN '1' ELSE '' END AS IREFTYPE,
    '' AS REFMAINO,
    '' AS OREFTYPE,
    COALESCE(er.ucae, '') AS UCAE,
    '3' AS EMTYPE,
    er.vn AS SEQ,
    '' AS AESTATUS,
    '' AS DALERT,
    '' AS TALERT
FROM er_regist er
LEFT JOIN referout ro ON ro.vn = er.vn
WHERE er.vn IN (:vns);
```

---

## 14. แฟ้ม ADP.txt (ข้อมูลบริการเสริม/อุปกรณ์/หัตถการ - 27 คอลัมน์)
* **Header:**
  `HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP/E_SCREEN|LMP|SP_ITEM`

```sql
SELECT 
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATEOPD,
    COALESCE(nd.nhso_adp_type, '17') AS TYPE,
    COALESCE(nd.nhso_adp_code, op.icode) AS CODE,
    op.qty AS QTY,
    ROUND(op.unitprice, 2) AS RATE,
    op.vn AS SEQ,
    '' AS CAGCODE,
    '' AS DOSE,
    '' AS CA_TYPE,
    '' AS SERIALNO,
    -- หาก paidst <> '02' (เช่น 01, 03, 04) เข้า TOTCOPAY
    CASE WHEN op.paidst IS NOT NULL AND op.paidst <> '02' THEN ROUND(op.sum_price, 2) ELSE 0 END AS TOTCOPAY,
    '' AS USE_STATUS,
    -- หาก paidst = '02' (ลูกหนี้สิทธิ) เข้า TOTAL
    CASE WHEN op.paidst IS NULL OR op.paidst = '02' THEN ROUND(op.sum_price, 2) ELSE 0.00 END AS TOTAL,
    '' AS QTYDAY,
    '' AS TMLTCODE,
    '' AS STATUS1,
    '' AS BI,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    '1' AS ITEMSRC,
    COALESCE(doc.licenseno, 'ว00000') AS PROVIDER,
    '' AS GRAVIDA,
    '' AS GA_WEEK,
    '' AS DCIP,
    '' AS LMP,
    '' AS SP_ITEM
FROM opitemrece op
LEFT JOIN nondrugitems nd ON nd.icode = op.icode
LEFT JOIN ovst o ON o.vn = op.vn
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND (op.icode NOT LIKE '1%' OR nd.nhso_adp_code IS NOT NULL);
```

---

## 15. แฟ้ม LVD.txt (ข้อมูลการลากลับบ้านของผู้ป่วยใน - 7 คอลัมน์)
* **Header:**
  `SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY`

```sql
SELECT 
    lv.ipt_leave_id AS SEQLVD,
    lv.an AS AN,
    DATE_FORMAT(lv.leave_date, '%Y%m%d') AS DATEOUT,
    DATE_FORMAT(lv.leave_time, '%H%i') AS TIMEOUT,
    DATE_FORMAT(lv.back_date, '%Y%m%d') AS DATEIN,
    DATE_FORMAT(lv.back_time, '%H%i') AS TIMEIN,
    DATEDIFF(lv.back_date, lv.leave_date) AS QTYDAY
FROM ipt_leave lv
WHERE lv.an IN (:ans);
```

---

## 16. แฟ้ม DRU.txt (ข้อมูลรายการสั่งใช้ยา - 24 คอลัมน์)
* **Header:**
  `HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM`

```sql
SELECT 
    :hcode AS HCODE,
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    LPAD(COALESCE(o.spclty, '01'), 2, '0') AS CLINIC,
    TRIM(pt.cid) AS PERSON_ID,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATE_SERV,
    op.icode AS DID,
    CONCAT(TRIM(d.name), ' ', TRIM(COALESCE(d.strength, '')), ' ', TRIM(COALESCE(d.units, ''))) AS DIDNAME,
    op.qty AS AMOUNT,
    ROUND(op.unitprice, 2) AS DRUGPRICE,
    ROUND(COALESCE(d.cost, 0), 2) AS DRUGCOST,
    COALESCE(d.sks_drug_code, d.tmt_tp_code, d.tmt_gp_code, d.did, op.icode) AS DIDSTD,
    COALESCE(d.units, 'เม็ด') AS UNIT,
    '1x1' AS UNIT_PACK,
    op.vn AS SEQ,
    '' AS DRUGREMARK,
    '' AS PA_NO,
    -- หาก paidst <> '02' เข้า TOTCOPAY
    CASE WHEN op.paidst IS NOT NULL AND op.paidst <> '02' THEN ROUND(op.sum_price, 2) ELSE 0 END AS TOTCOPAY,
    CASE WHEN op.an IS NOT NULL AND op.an <> '' THEN '1' ELSE '2' END AS USE_STATUS,
    -- หาก paidst = '02' เข้า TOTAL
    CASE WHEN op.paidst IS NULL OR op.paidst = '02' THEN ROUND(op.sum_price, 2) ELSE 0.00 END AS TOTAL,
    COALESCE(op.drugusage, '') AS SIGCODE,
    CONCAT(COALESCE(du.name1, ''), ' ', COALESCE(du.name2, ''), ' ', COALESCE(du.name3, '')) AS SIGTEXT,
    COALESCE(doc.licenseno, 'ว00000') AS PROVIDER,
    '' AS SP_ITEM
FROM opitemrece op
JOIN drugitems d ON d.icode = op.icode
JOIN patient pt ON pt.hn = op.hn
LEFT JOIN ovst o ON o.vn = op.vn
LEFT JOIN drugusage du ON du.drugusage = op.drugusage
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icode LIKE '1%';
```

---

## 17. แฟ้ม LABFU.txt (ข้อมูลผลตรวจทางห้องปฏิบัติการติดตามการรักษา - 7 คอลัมน์)
* **Header:**
  `HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT`

```sql
SELECT 
    :hcode AS HCODE,
    lh.hn AS HN,
    TRIM(pt.cid) AS PERSON_ID,
    DATE_FORMAT(lh.order_date, '%Y%m%d') AS DATESERV,
    lh.vn AS SEQ,
    COALESCE(li.tmlt_code, li.provis_labcode, li.lab_items_code) AS LABTEST,
    REPLACE(TRIM(lo.lab_order_result), '|', '') AS LABRESULT
FROM lab_head lh
JOIN lab_order lo ON lo.lab_order_number = lh.lab_order_number
JOIN lab_items li ON li.lab_items_code = lo.lab_items_code
JOIN patient pt ON pt.hn = lh.hn
WHERE lh.vn IN (:vns)
  AND lo.lab_order_result IS NOT NULL 
  AND lo.lab_order_result <> '';
```
