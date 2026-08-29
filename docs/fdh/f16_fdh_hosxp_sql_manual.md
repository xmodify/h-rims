# คู่มือคำสั่ง Raw SQL Query สำหรับดึงข้อมูลมาตรฐาน 16/17 แฟ้ม FDH (Financial Data Hub) จากฐานข้อมูล HOSxP

เอกสารนี้รวบรวมคำสั่ง SQL (Raw Query) และ Business Logic สำหรับดึงข้อมูลจากฐานข้อมูล **HOSxP (MySQL/MariaDB)** เพื่อประกอบเป็นชุดข้อมูลมาตรฐาน **16/17 แฟ้ม FDH (Financial Data Hub)** สำหรับส่งข้อมูลบริการสุขภาพและการเรียกเก็บชดเชยของกระทรวงสาธารณสุขและ สปสช. (FDH Data Set Version ล่าสุด)

> 💡 **หมายเหตุ:** หากต้องการดูโครงสร้าง **16 แฟ้ม e-Claim สปสช. (ดั้งเดิม)** สามารถดูได้ที่ [docs/nhso/f16_eclaim_hosxp_sql_manual.md](file:///d:/Project%20Laravel/h-rims/docs/nhso/f16_eclaim_hosxp_sql_manual.md)

---

## 📌 สารบัญแฟ้มข้อมูลมาตรฐาน 17 แฟ้ม FDH

| ลำดับ | ชื่อแฟ้ม | จำนวนฟิลด์ | คำอธิบายแฟ้ม | ตารางหลักใน HOSxP |
| :---: | :--- | :---: | :--- | :--- |
| 1 | **INS.txt** | 17 | ข้อมูลสิทธิการรักษาพยาบาล (PERMITNO สิทธิ UCS จาก Authen/Claim code) | `ovst`, `ipt`, `visit_pttype`, `pttype`, `nhso_endpoint` |
| 2 | **PAT.txt** | 15 | ข้อมูลประวัติผู้ป่วย (ชื่อ-นามสกุล, CID, ที่อยู่) | `patient`, `ovst`, `ipt` |
| 3 | **OPD.txt** | 15 | ข้อมูลการรับบริการผู้ป่วยนอก (รวมสัญญาณชีพ Vital Signs และ CC) | `ovst`, `vn_stat`, `opdscreen` |
| 4 | **ORF.txt** | 7 | ข้อมูลการส่งต่อผู้ป่วยนอก (Refer Out OPD) | `referout`, `ovst` |
| 5 | **ODX.txt** | 6 | ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก | `ovstdiag`, `doctor` |
| 6 | **OOP.txt** | 8 | ข้อมูลหัตถการผู้ป่วยนอก (มี SERVPRICE) | `ovst_operation`, `doctor`, `opitemrece` |
| 7 | **IPD.txt** | 14 | ข้อมูลการรับบริการผู้ป่วยใน | `ipt`, `an_stat` |
| 8 | **IRF.txt** | 5 | ข้อมูลการส่งต่อผู้ป่วยใน (Refer Out IPD) | `referout`, `ipt` |
| 9 | **IDX.txt** | 7 | ข้อมูลการวินิจฉัยโรคผู้ป่วยใน | `iptdiag`, `doctor` |
| 10 | **IOP.txt** | 7 | ข้อมูลหัตถการผ่าตัดผู้ป่วยใน | `ipt_operation`, `doctor` |
| 11 | **CHT.txt** | 10 | ข้อมูลสรุปยอดรวมค่าใช้จ่ายและใบเสร็จ (มี INVOICE_NO, INVOICE_LT) | `vn_stat`, `an_stat`, `rcpt_print`, `rcpt_debt` |
| 12 | **CHA.txt** | 6 | ข้อมูลสรุปค่าบริการ 16 หมวด สปสช./กรมบัญชีกลาง | `opitemrece`, `income` |
| 13 | **AER.txt** | 18 | ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ (UCAE / AE Type ทั้ง OP และ IP) | `er_regist`, `er_pt_type`, `ipt_accident`, `referout` |
| 14 | **ADP.txt** | 27 | ข้อมูลบริการเสริม/อุปกรณ์/PPFS/โครงการพิเศษ (TYPE 5 - WALKIN) | `opitemrece`, `nondrugitems`, `doctor` |
| 15 | **LVD.txt** | 7 | ข้อมูลการลากลับบ้านของผู้ป่วยใน (มี QTYDAY) | `ipt_leave` |
| 16 | **DRU.txt** | 16 | ข้อมูลรายการสั่งใช้ยา (มี DRUGPRICE, SP_ITEM) | `opitemrece`, `drugitems`, `drugusage`, `doctor` |
| 17 | **LAB.txt** | 11 | ข้อมูลผลตรวจทางห้องปฏิบัติการ (Lab Tests/Results) | `lab_head`, `lab_order` |

---

## 🛠️ กฎการจัดรูปแบบข้อมูลสากล (Global Data Formatting)
1. **ตัวคั่นข้อมูล (Delimiter):** ใช้เครื่องหมาย Pipe (`|`) คั่นระหว่างฟิลด์
2. **รูปแบบวันที่ (Date):** รูปแบบ ค.ศ. 8 หลัก `YYYYMMDD` เช่น `20260829`
3. **รูปแบบเวลา (Time):** รูปแบบ 4 หลัก `HHMM` (ไม่มีเครื่องหมาย `:`) เช่น `0830`
4. **ขึ้นบรรทัดใหม่ (Line Ending):** ใช้ `CRLF` (`\r\n`)
5. **การเข้ารหัสตัวอักษร (Encoding):** รองรับทั้ง `TIS-620 / Windows-874 (ANSI)` และ `UTF-8`

---

## 1. แฟ้ม INS.txt (ข้อมูลสิทธิการรักษาพยาบาล)
* **โครงสร้าง 17 ฟิลด์:**
  `HN|INSCL|SUBTYPE|CID|HOSPMAIN|HOSPSUB|GOVCODE|GOVNAME|PERMITNO|DOCNO|OWNRPID|OWNRNAME|AN|SEQ|SUBINSCL|RELINSCL|HTYPE`
* **เงื่อนไขสำคัญสำหรับ PERMITNO (สิทธิ UCS):**
  ดึงจาก `visit_pttype.auth_code` $\rightarrow$ `nhso_endpoint` (claimCode/authenCode) $\rightarrow$ `visit_pttype.claim_code` (**ห้ามนำเลข EDC ข้าราชการมาปน**)

### ฝั่ง OPD:
```sql
SELECT 
    o.hn AS HN,
    COALESCE(p.hipdata_code, o.pttype, 'UCS') AS INSCL,
    COALESCE(o.pt_subtype, 'O4') AS SUBTYPE,
    TRIM(pt.cid) AS CID,
    COALESCE(vp.hospmain, '') AS HOSPMAIN,
    COALESCE(vp.hospsub, '') AS HOSPSUB,
    '' AS GOVCODE,
    '' AS GOVNAME,
    COALESCE(
        NULLIF(TRIM(vp.auth_code), ''),
        NULLIF(TRIM(ep.claimCode), ''),
        NULLIF(TRIM(ep.authenCode), ''),
        NULLIF(TRIM(vp.claim_code), ''),
        ''
    ) AS PERMITNO,
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
LEFT JOIN nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = o.vstdate
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

### ฝั่ง IPD:
```sql
SELECT 
    i.hn AS HN,
    COALESCE(p.hipdata_code, i.pttype, 'UCS') AS INSCL,
    COALESCE(i.pt_subtype, 'O4') AS SUBTYPE,
    TRIM(pt.cid) AS CID,
    COALESCE(vp.hospmain, i.hospmain, '') AS HOSPMAIN,
    COALESCE(vp.hospsub, '') AS HOSPSUB,
    '' AS GOVCODE,
    '' AS GOVNAME,
    COALESCE(
        NULLIF(TRIM(ia.claim_code), ''),
        NULLIF(TRIM(vp.auth_code), ''),
        NULLIF(TRIM(ep.claimCode), ''),
        NULLIF(TRIM(ep.authenCode), ''),
        NULLIF(TRIM(vp.claim_code), ''),
        ''
    ) AS PERMITNO,
    '' AS DOCNO,
    '' AS OWNRPID,
    '' AS OWNRNAME,
    i.an AS AN,
    COALESCE(i.vn, '') AS SEQ,
    '' AS SUBINSCL,
    '' AS RELINSCL,
    '' AS HTYPE
FROM ipt i
LEFT JOIN patient pt ON pt.hn = i.hn
LEFT JOIN visit_pttype vp ON vp.vn = i.vn
LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, i.pttype)
LEFT JOIN ipt_accident ia ON ia.an = i.an
LEFT JOIN nhso_endpoint ep ON ep.cid = pt.cid AND ep.vstdate = i.regdate
WHERE i.an IN (:ans)
ORDER BY i.regdate, i.regtime;
```

---

## 2. แฟ้ม PAT.txt (ข้อมูลประวัติผู้ป่วย)
* **โครงสร้าง 15 ฟิลด์:**
  `HCODE|HN|CHANGWAT|AMPHUR|DOB|SEX|MARRIAGE|OCCUPA|NATION|PERSON_ID|NAMEPAT|TITLE|FNAME|LNAME|IDTYPE`
* **สูตร NAMEPAT:** `ชื่อ<เคาะ 2 ครั้ง>นามสกุล<เคาะ 1 ครั้ง>,<เคาะ 1 ครั้ง>คำนำหน้า`

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
FROM patient pt
WHERE pt.hn IN (:hns);
```

---

## 3. แฟ้ม OPD.txt (ข้อมูลการรับบริการผู้ป่วยนอก - 15 คอลัมน์)
* **โครงสร้าง 15 ฟิลด์เฉพาะ FDH:**
  `HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTEMP|SBP|DBP|PR|RR|OPTYPE|TYPEIN|TYPEOUT`
* **ดึงสัญญาณชีพ (Vital Signs) และอาการสำคัญ (CC) จาก `opdscreen`:**

```sql
SELECT 
    o.hn AS HN,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    REPLACE(LEFT(o.vsttime, 5), ':', '') AS TIMEOPD,
    o.vn AS SEQ,
    '1' AS UUC,
    REPLACE(REPLACE(COALESCE(sc.cc, o.main_dep_name, 'ตรวจรักษาทั่วไป'), '|', ' '), '\r\n', ' ') AS DETAIL,
    COALESCE(sc.temperature, '') AS BTEMP,
    COALESCE(CAST(sc.bps AS CHAR), '') AS SBP,
    COALESCE(CAST(sc.bpd AS CHAR), '') AS DBP,
    COALESCE(CAST(sc.pulse AS CHAR), '') AS PR,
    COALESCE(CAST(sc.rr AS CHAR), '') AS RR,
    '' AS OPTYPE,
    '1' AS TYPEIN,
    '1' AS TYPEOUT
FROM ovst o
LEFT JOIN opdscreen sc ON sc.vn = o.vn
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

---

## 4. แฟ้ม IPD.txt (ข้อมูลการรับบริการผู้ป่วยใน)
* **โครงสร้าง 14 ฟิลด์:**
  `HN|AN|DATEADM|TIMEADM|DATEDSC|TIMEDSC|DISCHS|DISCHT|WARDDSC|DEPT|ADM_W|UUC|SVCTYPE|SEQ`

```sql
SELECT 
    i.hn AS HN,
    i.an AS AN,
    DATE_FORMAT(i.regdate, '%Y%m%d') AS DATEADM,
    REPLACE(LEFT(i.regtime, 5), ':', '') AS TIMEADM,
    DATE_FORMAT(i.dchdate, '%Y%m%d') AS DATEDSC,
    REPLACE(LEFT(i.dchtime, 5), ':', '') AS TIMEDSC,
    COALESCE(i.dch_status, '1') AS DISCHS,
    COALESCE(i.dch_type, '1') AS DISCHT,
    LPAD(TRIM(COALESCE(i.ward, '01')), 4, '0') AS WARDDSC,
    LPAD(TRIM(COALESCE(i.dept, '01')), 2, '0') AS DEPT,
    FORMAT(COALESCE(ans.adm_weight, 0), 2) AS ADM_W,
    '1' AS UUC,
    '' AS SVCTYPE,
    COALESCE(i.vn, '') AS SEQ
FROM ipt i
LEFT JOIN an_stat ans ON ans.an = i.an
WHERE i.an IN (:ans)
ORDER BY i.regdate, i.regtime;
```

---

## 5. แฟ้ม ODX.txt (การวินิจฉัยโรค OPD)
* **โครงสร้าง 6 ฟิลด์:**
  `HN|DATEDX|CLINIC|DIAG|DXTYPE|DRDX`

```sql
SELECT 
    o.hn AS HN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEDX,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    UPPER(REPLACE(TRIM(od.icd10), '.', '')) AS DIAG,
    COALESCE(od.diagtype, '1') AS DXTYPE,
    COALESCE(doc.licenseno, '') AS DRDX
FROM ovstdiag od
INNER JOIN ovst o ON o.vn = od.vn
LEFT JOIN doctor doc ON doc.code = od.doctor
WHERE od.vn IN (:vns)
ORDER BY od.vn, od.diagtype, od.ovst_diag_id;
```

---

## 6. แฟ้ม IDX.txt (การวินิจฉัยโรค IPD)
* **โครงสร้าง 7 ฟิลด์:**
  `AN|DIAG|DXTYPE|DRDX|DRDX_NAME|DATE_IN|DATE_OUT`

```sql
SELECT 
    i.an AS AN,
    UPPER(REPLACE(TRIM(id.icd10), '.', '')) AS DIAG,
    COALESCE(id.diagtype, '1') AS DXTYPE,
    COALESCE(doc.licenseno, '') AS DRDX,
    COALESCE(doc.name, '') AS DRDX_NAME,
    DATE_FORMAT(i.regdate, '%Y%m%d') AS DATE_IN,
    DATE_FORMAT(i.dchdate, '%Y%m%d') AS DATE_OUT
FROM iptdiag id
INNER JOIN ipt i ON i.an = id.an
LEFT JOIN doctor doc ON doc.code = id.doctor
WHERE id.an IN (:ans)
ORDER BY id.an, id.diagtype;
```

---

## 7. แฟ้ม OOP.txt (หัตถการ OPD - มี SERVPRICE)
* **โครงสร้าง 8 ฟิลด์:**
  `HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ|SERVPRICE`

```sql
SELECT 
    o.hn AS HN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    UPPER(REPLACE(TRIM(op.icd9), '.', '')) AS OPER,
    COALESCE(doc.licenseno, '') AS DROPID,
    TRIM(pt.cid) AS PERSON_ID,
    o.vn AS SEQ,
    FORMAT(COALESCE(op.sum_price, 0), 2) AS SERVPRICE
FROM ovst_operation op
INNER JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = o.hn
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
ORDER BY op.vn;
```

---

## 8. แฟ้ม IOP.txt (หัตถการผ่าตัด IPD)
* **โครงสร้าง 7 ฟิลด์:**
  `AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT`

```sql
SELECT 
    i.an AS AN,
    UPPER(REPLACE(TRIM(iop.icd9), '.', '')) AS OPER,
    '1' AS OPTYPE,
    COALESCE(doc.licenseno, '') AS DROPID,
    DATE_FORMAT(iop.opdate, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(iop.optime, 5), ':', '') AS TIMEIN,
    DATE_FORMAT(COALESCE(iop.enddate, iop.opdate), '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(COALESCE(iop.endtime, iop.optime), 5), ':', '') AS TIMEOUT
FROM ipt_operation iop
INNER JOIN ipt i ON i.an = iop.an
LEFT JOIN doctor doc ON doc.code = iop.doctor
WHERE iop.an IN (:ans)
ORDER BY iop.an;
```

---

## 9. แฟ้ม ORF.txt & IRF.txt (ข้อมูลการส่งต่อ Refer Out)

### แฟ้ม ORF.txt (OPD Refer - 7 ฟิลด์):
`HN|DATEOPD|CLINIC|REFER|REFERTYPE|SEQ|REFERDATE`
```sql
SELECT 
    o.hn AS HN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    COALESCE(ro.refer_hospcode, '') AS REFER,
    COALESCE(ro.refer_type, '1') AS REFERTYPE,
    o.vn AS SEQ,
    DATE_FORMAT(ro.refer_date, '%Y%m%d') AS REFERDATE
FROM referout ro
INNER JOIN ovst o ON o.vn = ro.vn
WHERE ro.vn IN (:vns);
```

### แฟ้ม IRF.txt (IPD Refer - 5 ฟิลด์):
`AN|REFER|REFERTYPE|DISCHS|REFERDATE`
```sql
SELECT 
    i.an AS AN,
    COALESCE(ro.refer_hospcode, '') AS REFER,
    COALESCE(ro.refer_type, '1') AS REFERTYPE,
    COALESCE(i.dch_status, '1') AS DISCHS,
    DATE_FORMAT(ro.refer_date, '%Y%m%d') AS REFERDATE
FROM referout ro
INNER JOIN ipt i ON i.an = ro.vn OR i.vn = ro.vn
WHERE i.an IN (:ans);
```

---

## 10. แฟ้ม CHT.txt (สรุปยอดรวมค่าใช้จ่าย - มี INVOICE_NO/LT)
* **โครงสร้าง 10 ฟิลด์เฉพาะ FDH:**
  `HN|AN|DATE|TOTAL|PAID|PTTYPE|PERSON_ID|SEQ|OPD_MEMO|INVOICE_NO|INVOICE_LT`

```sql
SELECT 
    o.hn AS HN,
    COALESCE(o.an, '') AS AN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATE,
    FORMAT(COALESCE(v.income, 0), 2) AS TOTAL,
    FORMAT(COALESCE(v.rcpt_money, 0), 2) AS PAID,
    COALESCE(p.hipdata_code, o.pttype, 'UCS') AS PTTYPE,
    TRIM(pt.cid) AS PERSON_ID,
    o.vn AS SEQ,
    '' AS OPD_MEMO,
    COALESCE(rc.finance_number, rc.receipt_number, '') AS INVOICE_NO,
    COALESCE(rc.book_number, '') AS INVOICE_LT
FROM ovst o
LEFT JOIN vn_stat v ON v.vn = o.vn
LEFT JOIN patient pt ON pt.hn = o.hn
LEFT JOIN visit_pttype vp ON vp.vn = o.vn
LEFT JOIN pttype p ON p.pttype = COALESCE(vp.pttype, o.pttype)
LEFT JOIN rcpt_print rc ON rc.vn = o.vn
WHERE o.vn IN (:vns)
ORDER BY o.vstdate;
```

---

## 11. แฟ้ม CHA.txt (สรุปค่าบริการ 16 หมวด สปสช.)
* **โครงสร้าง 6 ฟิลด์:**
  `HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ`
* **การ Map หมวดรายได้ HOSxP (`income`) สู่ 16 หมวด FDH:**
  - `01` $\rightarrow$ `11`, `02` $\rightarrow$ `21`, `03`/`04`/`17` $\rightarrow$ `41`/`42`, `05` $\rightarrow$ `51`, `06` $\rightarrow$ `61`, `07` $\rightarrow$ `71`, `08` $\rightarrow$ `81`, `09` $\rightarrow$ `91`, `10` $\rightarrow$ `A1`, `11` $\rightarrow$ `B1`, `12`/`18` $\rightarrow$ `C1`, `13` $\rightarrow$ `D1`, `14` $\rightarrow$ `E1`, `15` $\rightarrow$ `F1`, `16` $\rightarrow$ `G1`, อื่นๆ $\rightarrow$ `H1`

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
    COALESCE(op.vn, '') AS SEQ
FROM opitemrece op
LEFT JOIN patient pt ON pt.hn = op.hn
WHERE op.vn IN (:vns)
GROUP BY op.vn, op.hn, op.an, op.vstdate, pt.cid, CHRGITEM
ORDER BY op.vn, CHRGITEM;
```

---

## 12. แฟ้ม AER.txt (อุบัติเหตุ ฉุกเฉิน และส่งต่อ - UCAE ทั้ง OP และ IP)
* **โครงสร้าง 18 ฟิลด์:**
  `HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT`
* **Business Logic การกำหนด UCAE / AE Type:**
  - **OPD:** ดึงจาก `visit_pttype.nhso_ucae_type_code` หรือ `er_pt_type.ucae` (เชื่อม `er_regist`) หากไม่มีให้เป็น `'N'`
  - **IPD:** ดึงจาก `ipt_accident.ipt_accident_ae_type_id` (`A`/`E`/`N`) หากไม่มีให้เป็น `'N'`
  - **ไม่นำ I/O จากตาราง Refer มาแปลงใส่ UCAE**

### ฝั่ง OPD:
```sql
SELECT 
    o.hn AS HN,
    COALESCE(o.an, '') AS AN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    '' AS AUTHAE,
    DATE_FORMAT(COALESCE(er.enter_date, o.vstdate), '%Y%m%d') AS AEDATE,
    REPLACE(LEFT(COALESCE(er.enter_time, o.vsttime), 5), ':', '') AS AETIME,
    COALESCE(et.ucae, 'N') AS AETYPE,
    COALESCE(ro.refer_number, '') AS REFER_NO,
    '' AS REFMAINI,
    '' AS IREFTYPE,
    COALESCE(ro.refer_hospcode, '') AS REFMAINO,
    IF(ro.referout_id IS NOT NULL, '1100', '') AS OREFTYPE,
    COALESCE(NULLIF(TRIM(vp.nhso_ucae_type_code), ''), NULLIF(TRIM(et.ucae), ''), 'N') AS UCAE,
    COALESCE(et.nhso_emtype_id, '3') AS EMTYPE,
    o.vn AS SEQ,
    '' AS AESTATUS,
    '' AS DALERT,
    '' AS TALERT
FROM ovst o
LEFT JOIN er_regist er ON er.vn = o.vn
LEFT JOIN er_pt_type et ON et.er_pt_type = er.er_pt_type
LEFT JOIN visit_pttype vp ON vp.vn = o.vn
LEFT JOIN referout ro ON ro.vn = o.vn
WHERE o.vn IN (:vns)
ORDER BY o.vstdate;
```

### ฝั่ง IPD:
```sql
SELECT 
    i.hn AS HN,
    i.an AS AN,
    DATE_FORMAT(i.regdate, '%Y%m%d') AS DATEOPD,
    '' AS AUTHAE,
    DATE_FORMAT(COALESCE(ia.accident_date, i.regdate), '%Y%m%d') AS AEDATE,
    REPLACE(LEFT(COALESCE(ia.accident_time, i.regtime), 5), ':', '') AS AETIME,
    COALESCE(ia.ipt_accident_ae_type_id, 'N') AS AETYPE,
    COALESCE(ro.refer_number, '') AS REFER_NO,
    '' AS REFMAINI,
    '' AS IREFTYPE,
    COALESCE(ro.refer_hospcode, '') AS REFMAINO,
    IF(ro.referout_id IS NOT NULL, '1100', '') AS OREFTYPE,
    COALESCE(ia.ipt_accident_ae_type_id, 'N') AS UCAE,
    COALESCE(ia.ipt_accident_emtype_code, '3') AS EMTYPE,
    COALESCE(i.vn, '') AS SEQ,
    '' AS AESTATUS,
    '' AS DALERT,
    '' AS TALERT
FROM ipt i
LEFT JOIN ipt_accident ia ON ia.an = i.an
LEFT JOIN referout ro ON ro.vn = i.vn OR ro.vn = i.an
WHERE i.an IN (:ans)
ORDER BY i.regdate;
```

---

## 13. แฟ้ม ADP.txt (บริการเสริม/PPFS/โครงการเฉพาะ - TYPE 5 WALKIN)
* **โครงสร้าง 27 ฟิลด์:**
  `HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP|LMP|SP_ITEM`
* **โครงการเฉพาะ (Project):** เช่น 30 บาทรักษาทุกที่ ให้ส่ง `TYPE = '5'` และ `CODE = 'WALKIN'`

```sql
SELECT 
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATEOPD,
    COALESCE(op.nhso_adp_type, n.nhso_adp_type, '14') AS TYPE,
    COALESCE(op.nhso_adp_code, n.nhso_adp_code, op.icode) AS CODE,
    IF(op.qty = FLOOR(op.qty), CAST(op.qty AS SIGNED), 1) AS QTY,
    FORMAT(COALESCE(op.unitprice, 0), 2) AS RATE,
    COALESCE(op.vn, '') AS SEQ,
    CONCAT(COALESCE(op.vn, op.an), ':', COALESCE(op.nhso_adp_type, n.nhso_adp_type, '14'), ':', COALESCE(op.nhso_adp_code, n.nhso_adp_code, op.icode), ':', FORMAT(COALESCE(op.unitprice, 0), 2), ':False') AS CAGCODE,
    COALESCE(op.hos_guid, CONCAT('{', UPPER(MD5(CONCAT(COALESCE(op.vn, op.an), op.icode))), '}')) AS DOSE,
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
LEFT JOIN ovst o ON o.vn = op.vn
LEFT JOIN nondrugitems n ON n.icode = op.icode
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icode NOT LIKE '1%'
ORDER BY op.vn, op.item_no;
```

---

## 14. แฟ้ม DRU.txt (รายการสั่งใช้ยา - มี DRUGPRICE, SP_ITEM)
* **โครงสร้าง 16 ฟิลด์:**
  `HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRICE|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER|SP_ITEM`

```sql
SELECT 
    :hcode AS HCODE,
    op.hn AS HN,
    COALESCE(op.an, '') AS AN,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    TRIM(pt.cid) AS PERSON_ID,
    DATE_FORMAT(op.vstdate, '%Y%m%d') AS DATE_SERV,
    COALESCE(d.did, op.icode) AS DID,
    TRIM(REPLACE(COALESCE(d.name, op.icode), '|', ' ')) AS DIDNAME,
    CAST(op.qty AS SIGNED) AS AMOUNT,
    FORMAT(COALESCE(op.unitprice, 0), 2) AS DRUGPRICE,
    FORMAT(COALESCE(d.unitcost, op.unitprice, 0), 2) AS DRUGCOST,
    COALESCE(d.nhso_adp_code, d.did, '') AS DIDSTD,
    COALESCE(d.units, 'TAB') AS UNIT,
    COALESCE(d.packqty, '1') AS UNIT_PACK,
    COALESCE(op.vn, '') AS SEQ,
    '' AS DRUGREMARK,
    '' AS PA_NO,
    '0.00' AS TOTCOPAY,
    '' AS USE_STATUS,
    FORMAT(COALESCE(op.sum_price, 0), 2) AS TOTAL,
    COALESCE(du.drugusage, '') AS SIGCODE,
    REPLACE(TRIM(CONCAT(COALESCE(du.name1, ''), ' ', COALESCE(du.name2, ''), ' ', COALESCE(du.name3, ''))), '|', ' ') AS SIGTEXT,
    COALESCE(doc.licenseno, '') AS PROVIDER,
    '' AS SP_ITEM
FROM opitemrece op
LEFT JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = op.hn
LEFT JOIN drugitems d ON d.icode = op.icode
LEFT JOIN drugusage du ON du.drugusage = op.drugusage
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icode LIKE '1%'
ORDER BY op.vn, op.item_no;
```

---

## 15. แฟ้ม LVD.txt (การลากลับบ้าน IPD - มี QTYDAY)
* **โครงสร้าง 7 ฟิลด์:**
  `SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN|QTYDAY`

```sql
SELECT 
    ROW_NUMBER() OVER(PARTITION BY l.an ORDER BY l.leave_date) AS SEQLVD,
    l.an AS AN,
    DATE_FORMAT(l.leave_date, '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(l.leave_time, 5), ':', '') AS TIMEOUT,
    DATE_FORMAT(l.back_date, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(l.back_time, 5), ':', '') AS TIMEIN,
    DATEDIFF(l.back_date, l.leave_date) AS QTYDAY
FROM ipt_leave l
WHERE l.an IN (:ans)
ORDER BY l.an, l.leave_date;
```

---

## 16. แฟ้ม LAB.txt (ผลตรวจทางห้องปฏิบัติการ - แฟ้มที่ 17 FDH)
* **โครงสร้าง 11 ฟิลด์:**
  `HCODE|HN|PERSON_ID|DATESERV|SEQ|LABTEST|LABRESULT|UNIT|STANDARDRANGE|LABNAME|REMARK`

```sql
SELECT 
    :hcode AS HCODE,
    lh.hn AS HN,
    TRIM(pt.cid) AS PERSON_ID,
    DATE_FORMAT(lh.order_date, '%Y%m%d') AS DATESERV,
    COALESCE(lh.vn, '') AS SEQ,
    COALESCE(lo.lab_items_code, lo.lab_items_name_ref) AS LABTEST,
    REPLACE(TRIM(lo.lab_order_result), '|', ' ') AS LABRESULT,
    COALESCE(li.unit, '') AS UNIT,
    COALESCE(li.range_check, '') AS STANDARDRANGE,
    REPLACE(TRIM(li.lab_items_name), '|', ' ') AS LABNAME,
    '' AS REMARK
FROM lab_head lh
INNER JOIN lab_order lo ON lo.lab_order_number = lh.lab_order_number
INNER JOIN patient pt ON pt.hn = lh.hn
LEFT JOIN lab_items li ON li.lab_items_code = lo.lab_items_code
WHERE lh.vn IN (:vns)
  AND lo.lab_order_result IS NOT NULL 
  AND TRIM(lo.lab_order_result) <> ''
ORDER BY lh.vn, lo.lab_order_number;
```

---

## 🚀 สรุปการประยุกต์ใช้งานในระบบ H-RIMS

ในระบบ H-RIMS ข้อมูล 17 แฟ้ม FDH ถูกประมวลผลผ่าน `App\Services\F16FdhExportService` ซึ่งมีฟังก์ชันหลัก 2 ส่วน:
1. `F16FdhExportService::generate16Files($vns)` $\rightarrow$ สำหรับผู้ป่วยนอก (OPD)
2. `F16FdhExportService::generate16FilesIp($ans)` $\rightarrow$ สำหรับผู้ป่วยใน (IPD)
