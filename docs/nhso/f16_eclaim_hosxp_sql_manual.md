# คู่มือคำสั่ง Raw SQL Query สำหรับดึงข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim สปสช.) จากฐานข้อมูล HOSxP

เอกสารนี้รวบรวมคำสั่ง SQL (Raw Query) และ Business Logic สำหรับดึงข้อมูลจากฐานข้อมูล **HOSxP (MySQL/MariaDB)** เพื่อประกอบเป็นชุดข้อมูลมาตรฐาน **16 แฟ้ม (e-Claim)** สำหรับส่งเบิกกองทุน สปสช. (UCS), กรมบัญชีกลาง (OFC), ประกันสังคม (SSS), และองค์กรปกครองส่วนท้องถิ่น (LGO)

> 💡 **หมายเหตุ:** หากต้องการดูโครงสร้าง **16/17 แฟ้ม FDH (Financial Data Hub)** สามารถดูได้ที่ [docs/fdh/f16_fdh_hosxp_sql_manual.md](file:///d:/Project%20Laravel/h-rims/docs/fdh/f16_fdh_hosxp_sql_manual.md)

---

## 📌 สารบัญแฟ้มข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)

| ลำดับ | ชื่อแฟ้ม | จำนวนฟิลด์ | คำอธิบายแฟ้ม | ตารางหลักใน HOSxP ที่ใช้งาน |
| :---: | :--- | :---: | :--- | :--- |
| 1 | **INS.txt** | 17 | ข้อมูลสิทธิการรักษาพยาบาล | `ovst`, `ipt`, `visit_pttype`, `pttype`, `ovst_seq` |
| 2 | **PAT.txt** | 15 | ข้อมูลประวัติผู้ป่วย | `patient`, `ovst`, `ipt` |
| 3 | **OPD.txt** | 8 | ข้อมูลการรับบริการผู้ป่วยนอก | `ovst`, `vn_stat` |
| 4 | **IPD.txt** | 14 | ข้อมูลการรับบริการผู้ป่วยใน | `ipt`, `ovst`, `an_stat` |
| 5 | **ODX.txt** | 6 | ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก | `ovstdiag`, `doctor` |
| 6 | **OOP.txt** | 7 | ข้อมูลหัตถการผู้ป่วยนอก | `ovst_operation`, `doctor` |
| 7 | **IDX.txt** | 7 | ข้อมูลการวินิจฉัยโรคผู้ป่วยใน | `iptdiag`, `doctor` |
| 8 | **IOP.txt** | 7 | ข้อมูลหัตถการผ่าตัดผู้ป่วยใน | `ipt_operation`, `doctor` |
| 9 | **ORF.txt** | 7 | ข้อมูลการส่งต่อผู้ป่วยนอก (Refer Out) | `referout`, `ovst` |
| 10 | **IRF.txt** | 5 | ข้อมูลการส่งต่อผู้ป่วยใน (Refer Out IPD) | `referout`, `ipt` |
| 11 | **LVD.txt** | 6 | ข้อมูลการลากลับบ้านของผู้ป่วยใน | `ipt_leave` |
| 12 | **DRU.txt** | 16 | ข้อมูลรายการสั่งใช้ยา | `opitemrece`, `drugitems`, `drugusage`, `doctor` |
| 13 | **CHA.txt** | 6 | ข้อมูลสรุปค่าบริการ 16 หมวด สปสช. | `opitemrece`, `income` |
| 14 | **CHT.txt** | 8 | ข้อมูลสรุปยอดรวมค่าใช้จ่ายและใบเสร็จ | `ovst`, `vn_stat`, `an_stat`, `rcpt_print` |
| 15 | **AER.txt** | 18 | ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ | `er_regist`, `referout`, `ovst`, `ipt_accident` |
| 16 | **ADP.txt** | 27 | ข้อมูลบริการเสริม/อุปกรณ์/PPFS/แลปพิเศษ | `opitemrece`, `nondrugitems`, `doctor` |

---

## ⚖️ ตารางเปรียบเทียบความแตกต่างระหว่าง e-Claim (สปสช.) กับ FDH (กระทรวงสาธารณสุข)

| แฟ้ม | e-Claim (สปสช.) | FDH (Financial Data Hub) |
| :--- | :--- | :--- |
| **OPD** | 8 คอลัมน์ (HN, CLINIC, DATEOPD, TIMEOPD, SEQ, UUC, DETAIL, BTIMEDSC) | **15 คอลัมน์** (เพิ่ม BTEMP, SBP, DBP, PR, RR, OPTYPE, TYPEIN, TYPEOUT) |
| **OOP** | 7 คอลัมน์ | **8 คอลัมน์** (เพิ่ม `SERVPRICE`) |
| **CHT** | 8 คอลัมน์ | **10 คอลัมน์** (เพิ่ม `INVOICE_NO`, `INVOICE_LT`) |
| **DRU** | 16 คอลัมน์มาตรฐาน | **มีฟิลด์ `DRUGPRICE`, `SP_ITEM`** |
| **LVD** | 6 คอลัมน์ | **7 คอลัมน์** (เพิ่ม `QTYDAY`) |
| **LAB** | ไม่มีใน 16 แฟ้ม e-Claim | **มีแฟ้ม LAB.txt เป็นแฟ้มที่ 17** |
| **PERMITNO** | ดึง Claim Code / EDC / Authen | สิทธิ UCS เน้น **Authen Code / Claim Code** |
| **AER.UCAE** | ส่งค่าตาม Refer/ER | OP ดึงจาก `er_pt_type.ucae` / IP ดึงจาก `ipt_accident` (ไม่นำ Refer I/O มาปน) |
| **ADP TYPE** | ทั่วไป 1-14 | รองรับ **`TYPE = 5`** สำหรับโครงการบริการเฉพาะ เช่น `WALKIN` (30 บาทรักษาทุกที่) |

---

## 🛠️ กฎการจัดรูปแบบข้อมูลสากล (Global Data Formatting)
1. **ตัวคั่นข้อมูล (Delimiter):** ใช้เครื่องหมาย Pipe (`|`) คั่นระหว่างฟิลด์
2. **รูปแบบวันที่ (Date):** รูปแบบ ค.ศ. 8 หลัก `YYYYMMDD` เช่น `20260829`
3. **รูปแบบเวลา (Time):** รูปแบบ 4 หลัก `HHMM` (ไม่มีเครื่องหมาย `:`) เช่น `0830`
4. **ขึ้นบรรทัดใหม่ (Line Ending):** ใช้ `CRLF` (`\r\n`)
5. **การเข้ารหัสตัวอักษร:** ANSI (TIS-620 / Windows-874)

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
WHERE o.vn IN (:vns)
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
  `HN|CLINIC|DATEOPD|TIMEOPD|SEQ|UUC|DETAIL|BTMDSC`

```sql
SELECT 
    o.hn AS HN,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    REPLACE(LEFT(o.vsttime, 5), ':', '') AS TIMEOPD,
    o.vn AS SEQ,
    '1' AS UUC,
    REPLACE(REPLACE(COALESCE(o.main_dep_name, 'ตรวจรักษาทั่วไป'), '|', ' '), '\r\n', ' ') AS DETAIL,
    '' AS BTMDSC
FROM ovst o
WHERE o.vn IN (:vns)
ORDER BY o.vstdate, o.vsttime;
```

---

## 4. แฟ้ม IPD.txt (ข้อมูลการรับบริการผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
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

## 5. แฟ้ม ODX.txt (ข้อมูลการวินิจฉัยโรคผู้ป่วยนอก)
* **โครงสร้างฟิลด์:**
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

## 6. แฟ้ม OOP.txt (ข้อมูลหัตถการผู้ป่วยนอก)
* **โครงสร้างฟิลด์:**
  `HN|DATEOPD|CLINIC|OPER|DROPID|PERSON_ID|SEQ`

```sql
SELECT 
    o.hn AS HN,
    DATE_FORMAT(o.vstdate, '%Y%m%d') AS DATEOPD,
    LPAD(TRIM(COALESCE(o.cur_dep, '00100')), 5, '0') AS CLINIC,
    UPPER(REPLACE(TRIM(op.icd9), '.', '')) AS OPER,
    COALESCE(doc.licenseno, '') AS DROPID,
    TRIM(pt.cid) AS PERSON_ID,
    o.vn AS SEQ
FROM ovst_operation op
INNER JOIN ovst o ON o.vn = op.vn
LEFT JOIN patient pt ON pt.hn = o.hn
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
ORDER BY op.vn;
```

---

## 7. แฟ้ม IDX.txt (ข้อมูลการวินิจฉัยโรคผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `AN|DIAG|DXTYPE|DRDX`

```sql
SELECT 
    id.an AS AN,
    UPPER(REPLACE(TRIM(id.icd10), '.', '')) AS DIAG,
    COALESCE(id.diagtype, '1') AS DXTYPE,
    COALESCE(doc.licenseno, '') AS DRDX
FROM iptdiag id
LEFT JOIN doctor doc ON doc.code = id.doctor
WHERE id.an IN (:ans)
ORDER BY id.an, id.diagtype;
```

---

## 8. แฟ้ม IOP.txt (ข้อมูลหัตถการผ่าตัดผู้ป่วยใน)
* **โครงสร้างฟิลด์:**
  `AN|OPER|OPTYPE|DROPID|DATEIN|TIMEIN|DATEOUT|TIMEOUT`

```sql
SELECT 
    iop.an AS AN,
    UPPER(REPLACE(TRIM(iop.icd9), '.', '')) AS OPER,
    '1' AS OPTYPE,
    COALESCE(doc.licenseno, '') AS DROPID,
    DATE_FORMAT(iop.opdate, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(iop.optime, 5), ':', '') AS TIMEIN,
    DATE_FORMAT(COALESCE(iop.enddate, iop.opdate), '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(COALESCE(iop.endtime, iop.optime), 5), ':', '') AS TIMEOUT
FROM ipt_operation iop
LEFT JOIN doctor doc ON doc.code = iop.doctor
WHERE iop.an IN (:ans)
ORDER BY iop.an;
```

---

## 9. แฟ้ม ORF.txt & IRF.txt (ข้อมูลการส่งต่อ)

### แฟ้ม ORF.txt:
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

### แฟ้ม IRF.txt:
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

## 10. แฟ้ม LVD.txt (ข้อมูลการลากลับบ้าน IPD)
* **โครงสร้างฟิลด์:**
  `SEQLVD|AN|DATEOUT|TIMEOUT|DATEIN|TIMEIN`

```sql
SELECT 
    ROW_NUMBER() OVER(PARTITION BY l.an ORDER BY l.leave_date) AS SEQLVD,
    l.an AS AN,
    DATE_FORMAT(l.leave_date, '%Y%m%d') AS DATEOUT,
    REPLACE(LEFT(l.leave_time, 5), ':', '') AS TIMEOUT,
    DATE_FORMAT(l.back_date, '%Y%m%d') AS DATEIN,
    REPLACE(LEFT(l.back_time, 5), ':', '') AS TIMEIN
FROM ipt_leave l
WHERE l.an IN (:ans)
ORDER BY l.an, l.leave_date;
```

---

## 11. แฟ้ม DRU.txt (ข้อมูลรายการสั่งใช้ยา)
* **โครงสร้างฟิลด์:**
  `HCODE|HN|AN|CLINIC|PERSON_ID|DATE_SERV|DID|DIDNAME|AMOUNT|DRUGPRC|DRUGCOST|DIDSTD|UNIT|UNIT_PACK|SEQ|DRUGREMARK|PA_NO|TOTCOPAY|USE_STATUS|TOTAL|SIGCODE|SIGTEXT|PROVIDER`

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
    FORMAT(COALESCE(op.unitprice, 0), 2) AS DRUGPRC,
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
    COALESCE(doc.licenseno, '') AS PROVIDER
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

## 12. แฟ้ม CHA.txt (สรุปค่าบริการ 16 หมวด สปสช.)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATE|CHRGITEM|AMOUNT|PERSON_ID|SEQ`

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

## 13. แฟ้ม CHT.txt (สรุปยอดรวมค่าใช้จ่ายและใบเสร็จ)
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

## 14. แฟ้ม AER.txt (ข้อมูลอุบัติเหตุ ฉุกเฉิน และส่งต่อ)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEOPD|AUTHAE|AEDATE|AETIME|AETYPE|REFER_NO|REFMAINI|IREFTYPE|REFMAINO|OREFTYPE|UCAE|EMTYPE|SEQ|AESTATUS|DALERT|TALERT`

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

---

## 15. แฟ้ม ADP.txt (บริการเสริม/อุปกรณ์/PPFS/แลปพิเศษ)
* **โครงสร้างฟิลด์:**
  `HN|AN|DATEOPD|TYPE|CODE|QTY|RATE|SEQ|CAGCODE|DOSE|CA_TYPE|SERIALNO|TOTCOPAY|USE_STATUS|TOTAL|QTYDAY|TMLTCODE|STATUS1|BI|CLINIC|ITEMSRC|PROVIDER|GRAVIDA|GA_WEEK|DCIP|LMP|SP_ITEM`

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
LEFT JOIN ovst o ON o.vn = op.vn
LEFT JOIN nondrugitems n ON n.icode = op.icode
LEFT JOIN doctor doc ON doc.code = op.doctor
WHERE op.vn IN (:vns)
  AND op.icode NOT LIKE '1%'
ORDER BY op.vn, op.item_no;
```

---

## 🚀 การประยุกต์ใช้งานใน PHP / Laravel

ในระบบ H-RIMS ข้อมูล e-Claim สปสช. ถูกประมวลผลผ่าน `App\Services\F16EclaimExportService`:

```php
use App\Services\F16EclaimExportService;

$vns = ['690701130818', '690701140920'];
$result = F16EclaimExportService::generate16Files($vns);

// $result['files']['INS'] -> เนื้อหาไฟล์ INS.txt
// $result['counts']       -> สรุปจำนวนแถวของแต่ละแฟ้ม
```
