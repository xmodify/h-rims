<?php

namespace App\Services\Ai\TextToSql;

class SchemaCatalogService
{
    /**
     * Cache for extracted_schemas.json
     */
    protected static ?array $extractedSchemas = null;

    /**
     * Get Schema string for RiMS Database (Strictly all 12 hosfin_* tables)
     */
    public function getHrimsSchema(string $userQuery): string
    {
        $tables = $this->getCuratedHosfinTables();

        $expertService = app(\App\Services\Ai\Knowledge\HospitalFinancialKnowledgeService::class);
        $yearsInfo = $expertService->getBudgetYearsInfo();
        $hospName = $yearsInfo['hospital_name'];
        $availableYearsStr = implode(', ', $yearsInfo['available_years']);
        $baseYear = $yearsInfo['baseline_year'];
        $targetYear = $yearsInfo['target_planning_year'];
        $shortTarget = $yearsInfo['short_target_year'];
        $shortBase = $yearsInfo['short_baseline_year'];
        $maxYear = $yearsInfo['max_recorded_year'];

        $out = "=== ฐานข้อมูล RiMS (ระบบการเงิน HosFin - ตาราง hosfin_* ทั้งหมด 15 ตาราง) หน่วยบริการ: {$hospName} ===\n";
        $out .= "ชนิดฐานข้อมูล: MySQL / MariaDB (Connection: mysql)\n";
        $out .= "กฎเหล็ก: อนุญาตให้เขียนคำสั่ง SELECT เฉพาะตารางที่ขึ้นต้นด้วย 'hosfin_' เท่านั้น ห้ามใช้ตารางอื่นนอกเหนือจาก hosfin_*\n";
        $out .= "ข้อแนะนำสำคัญในการเขียน SQL ด้านการเงินการคลัง (ใช้ได้กับทุก รพ. ทุกปีงบประมาณ และในแต่ละเดือนครบทุกมิติ):\n";
        $out .= "- ปีงบประมาณ (fiscal_year / acc_year / budget_year) จัดเก็บเป็นปี พ.ศ. (ในระบบมีข้อมูลปี: {$availableYearsStr})\n";
        $out .= "- เจ้าหนี้ค้างจ่าย (AP): `hosfin_gl_ap_bills` ดูยอดหนี้คงเหลือที่ `remaining_debt > 0` หรือ `is_paid = 0` (0=ค้างจ่าย, 1=จ่ายแล้ว)\n";
        $out .= "- ลูกหนี้ค่ารักษาค้างชำระ (AR): `hosfin_gl_ar_debtors` ดูยอดหนี้คงค้างที่ `outstanding_balance > 0` แยกตามประเภท `debtor_type` หรือสิทธิ\n";
        $out .= "- งบทดลองและการติดตามรายเดือน: `hosfin_trial_balance` มี `acc_period` (เช่น '{$baseYear}-07' หรือ '{$baseYear}-08'), `acc_year`, `acc_month`:\n";
        $out .= "  * ยอดเคลื่อนไหวเฉพาะงวดเดือนนั้น (Single Month Movement): ใช้ `debit_month` และ `credit_month`\n";
        $out .= "  * ยอดสะสมยกไปตั้งแต่ต้นปีถึงงวดนั้น (Cumulative Balance): ใช้ `debit_net` และ `credit_net`\n";
        $out .= "  * ยอดยกมาต้นงวด: ใช้ `debit_bf` และ `credit_bf`\n";
        $out .= "- กระแสเงินสดและสรุปการเงินรายวัน: `hosfin_gl_daily_summaries` จัดเก็บข้อมูลเป็นรายวัน (คอลัมน์คือ `summary_date` เป็น DATE, ไม่มีคอลัมน์ fiscal_month) มีรายรับ (`total_income`), รายจ่าย (`total_expense`), สุทธิ (`net_cash_flow`), เงินสดคงเหลือสะสม (`cash_balance`)\n";
        $out .= "- การติดตามและเปรียบเทียบในแต่ละเดือน/รายเดือนทุกมิติ: ให้ดึงจาก `hosfin_trial_balance` โดยใช้ `acc_period` (เช่น '{$baseYear}-07', '{$baseYear}-08', '{$baseYear}-10'), `acc_month`, `debit_month`, `credit_month`, หรือดึงโครงสร้างต้นทุนรายเดือนจาก `hosfin_gl_cost_summaries` (มี `fiscal_year`, `fiscal_month` 1-12) หรือเป้าหมายจาก `hosfin_planfin_targets`\n";
        $out .= "- ต้นทุนโรงพยาบาลรายเดือน: `hosfin_gl_cost_summaries` มีค่าแรง LC (`lc_amount`), ค่าของ MC (`mc_amount`), ค่าลงทุน CC (`cc_amount`), ต้นทุนรวม (`total_cost`), `fiscal_year`, `fiscal_month` (1=ต.ค. ถึง 12=ก.ย.)\n";
        $out .= "- สมุดรายวันและรายการบัญชี: `hosfin_gl_journals` เชื่อมกับ `hosfin_gl_journal_items` ด้วย `voucher_no`\n";
        $out .= "- แผนเงินบำรุงโรงพยาบาล (PlanFin) & การจัดทำแผนทุกปีงบประมาณ:\n";
        $out .= "  * ปีงบประมาณในฐานข้อมูล: ปัจจุบันมีเป้าหมายแผนเงินบำรุงของปี {$availableYearsStr} (ปีฐานล่าสุดคือ {$baseYear})\n";
        $out .= "  * การจัดทำแผนสำหรับปีอนาคตที่ยังไม่มีข้อมูลในตาราง (เช่น ปี {$targetYear} หรือปีใดๆ ที่ > {$maxYear}): หากผู้ใช้ถามเรื่อง 'การทำแผนปี {$shortTarget}', 'แผนปี {$targetYear}', 'ทำแผนวันที่ 15', 'จำลองแผน {$shortTarget}', 'เตรียมตัวทำแผน' >> **ห้ามใส่ `WHERE budget_year = {$targetYear}` หรือ `WHERE budget_year = {$shortTarget}` เด็ดขาด** เพราะจะไม่ได้ข้อมูล (0 แถว)! ให้ดึงหมวดแผนและเป้าหมายปี {$baseYear} เพื่อนำตัวเลขจริงมาเป็นฐาน (Baseline) ประกอบการวิเคราะห์และแนะนำการตั้งเป้าหมายปี {$targetYear} เช่น: `SELECT c.sort_order, c.category_type, c.plan_code, c.plan_name, t.budget_year, t.round_no, t.target_amount FROM hosfin_planfin_categories c LEFT JOIN hosfin_planfin_targets t ON c.plan_code = t.plan_code AND t.budget_year = {$baseYear} ORDER BY c.sort_order ASC`\n";
        $out .= "  * คำว่า 'planfin' หรือ 'หมายถึง planfin': **ห้ามใส่ `WHERE plan_name LIKE '%planfin%'` เด็ดขาด** เพราะในคอลัมน์ไม่มีคำภาษาอังกฤษนี้ (เก็บเป็นชื่อไทย เช่น P04 รายได้ UC, P13S รวมรายได้, P14 ยา, P26S รวมค่าใช้จ่าย, P27S รายได้สุทธิ, P29 EBITDA) ให้เขียนคำสั่ง SELECT ดึงหมวดแผนและเป้าหมายปี {$baseYear} ออกมาทั้งหมด\n";
        $out .= "  * เป้าหมายแผนเงินบำรุง: `hosfin_planfin_targets` มี `budget_year`, `round_no`, `plan_code`, `target_amount` (ยอดเป้าหมายทั้งปี)\n";
        $out .= "  * หมวดแผนเงินบำรุง: `hosfin_planfin_categories` มี `plan_code`, `plan_name`, `category_type` (revenue, expense, summary, kpi), `sort_order`\n";
        $out .= "    - รายได้: P04 (UC), P05 (EMS), P06 (เบิกต้นสังกัด), P61 (อปท.), P07 (ตรงกรมบัญชีกลาง), P08 (ประกันสังคม), P09 (ต่างด้าว), P10 (บริการอื่น), P11 (งบส่วนบุคลากร), P12 (รายได้อื่น), P13 (งบลงทุน)\n";
        $out .= "    - สรุปรายได้: `P13S` (รวมรายได้)\n";
        $out .= "    - ค่าใช้จ่าย: P14 (ยา), P15 (เวชภัณฑ์/วัสดุการแพทย์), P151 (ทันตกรรม), P16 (วิทย์การแพทย์), P17 (เงินเดือน/จ้างประจำ), P18 (จ้างชั่วคราว/พกส.), P19 (ค่าตอบแทน), P20 (บุคลากรอื่น), P21 (ค่าใช้สอย), P22 (สาธารณูปโภค), P23 (วัสดุใช้ไป), P24 (ค่าเสื่อมราคา), P241 (หนี้สูญ), P25 (ค่าใช้จ่ายอื่น)\n";
        $out .= "    - สรุปค่าใช้จ่าย: `P26S` (รวมค่าใช้จ่าย)\n";
        $out .= "    - รายได้สุทธิ: `P27S` (รายได้สูง/ต่ำกว่าค่าใช้จ่ายสุทธิ Net Income = P13S - P26S)\n";
        $out .= "    - EBITDA: `P29` (EBITDA รวมรายได้หักงบลงทุน - รวมค่าใช้จ่ายหักค่าเสื่อส่วน CC)\n";
        $out .= "    - วงเงินลงทุนด้วยเงินบำรุงได้ตามเกณฑ์กระทรวงฯ: 20% ของ EBITDA (`P29 * 0.20`)\n";
        $out .= "  * จับคู่ผังบัญชี PlanFin: `hosfin_planfin_mappings` จับคู่ `account_code` กับ `plan_code`\n";
        $out .= "  * ตัวอย่างคำสั่ง SELECT แผนเงินบำรุง:\n";
        $out .= "    - ดึงเป้าหมายแผนเงินบำรุงปีปัจจุบัน/ฐาน Baseline ปี {$baseYear}: `SELECT c.sort_order, c.category_type, c.plan_code, c.plan_name, t.budget_year, t.round_no, t.target_amount FROM hosfin_planfin_categories c LEFT JOIN hosfin_planfin_targets t ON c.plan_code = t.plan_code AND t.budget_year = {$baseYear} ORDER BY c.sort_order ASC`\n";
        $out .= "    - ดึงเป้าหมายสรุป (รายได้, ค่าใช้จ่าย, กำไรสุทธิ, EBITDA): `SELECT t.plan_code, c.plan_name, t.target_amount FROM hosfin_planfin_targets t JOIN hosfin_planfin_categories c ON c.plan_code = t.plan_code WHERE t.budget_year = {$baseYear} AND t.plan_code IN ('P13S', 'P26S', 'P27S', 'P29')`\n\n";

        foreach ($tables as $table => $info) {
            $out .= "TABLE: `{$table}` -- {$info['description']}\nCOLUMNS:\n";
            foreach ($info['columns'] as $col => $desc) {
                $out .= "  - `{$col}`: {$desc}\n";
            }
            $out .= "\n";
        }

        return $out;
    }

    /**
     * Get Schema string for HOSxP ข้อมูลพื้นฐาน (Strictly: nondrugitems, pttype, doctor)
     */
    public function getHosxpSchema(string $userQuery): string
    {
        $allTables = $this->getCuratedHosxpTables();
        $tables = $this->selectRelevantHosxpTables($userQuery, $allTables);

        $out = "=== ฐานข้อมูล HOSxP (ตรวจสอบการตั้งค่าข้อมูลพื้นฐาน) ===\n";
        $out .= "ชนิดฐานข้อมูล: MySQL / MariaDB (Connection: hosxp)\n\n";
        $out .= "=== โครงสร้างหลักและตารางตั้งต้น (Core Anchor Architecture 5 เสาหลัก) ===\n";
        $out .= "ระบบข้อมูลพื้นฐานในหน้านี้จะตั้งต้นด้วย 5 กลุ่มตารางหลัก แล้วเชื่อมโยงไปยังตาราง lookup และแคตตาล็อก:\n\n";
        $out .= "1. [บุคลากรทางการแพทย์]: ตั้งต้นด้วยตาราง `doctor` เสมอ (เช่น `FROM doctor d`)\n";
        $out .= "   - เชื่อมตำแหน่ง: `LEFT JOIN doctor_position dp ON dp.id = d.position_id`\n";
        $out .= "   - เชื่อมสาขาความเชี่ยวชาญ: `LEFT JOIN spclty s ON s.spclty = d.spclty`\n";
        $out .= "   - เชื่อมคลินิก: `LEFT JOIN clinic c ON c.clinic = d.clinic`\n";
        $out .= "   - ฟิลด์สำคัญ: `code` (รหัสแพทย์), `name` (ชื่อแพทย์), `licenseno` (เลขใบประกอบฯ), `cid` (เลขบัตรประชาชน 13 หลัก), `council_code` (สภาวิชาชีพ), `active` (สถานะปฏิบัติงาน 'Y'/'N')\n";
        $out .= "   - เกณฑ์ตรวจสอบ: แฟ้ม PROVIDER (16 แฟ้ม) และส่งเคลม AIPN/FDH แพทย์ต้องมีเลข ว. (ขึ้นต้นด้วย ว/ท/ภ/พ ตามด้วยตัวเลข), มี CID ครบ 13 หลัก และระบุรหัสสภาวิชาชีพ\n\n";
        $out .= "2. [ค่ารักษาพยาบาลและหัตถการ]: ตั้งต้นด้วยตาราง `nondrugitems` เสมอ (เช่น `FROM nondrugitems n`)\n";
        $out .= "   - เชื่อมหมวดค่ารักษา: `LEFT JOIN income i ON i.income = n.income`\n";
        $out .= "   - เชื่อมสถานะการชำระ: `LEFT JOIN paidst p ON p.paidst = n.paidst`\n";
        $out .= "   - เชื่อมราคาตามสิทธิ (HOSxP v4): `LEFT JOIN pttype_items_price pip ON pip.items_table_code = n.icode` (เชื่อมผ่าน items_table_code = icode โดยตรง ห้ามกรอง WHERE items_table_name)\n";
        $out .= "   - ฟิลด์สำคัญ: `icode` (รหัสค่าบริการ), `name` (ชื่อรายการ), `price` (ราคามาตรฐาน 1), `price2`, `price3`, `nhso_adp_code` (รหัส ADP สปสช.), `nhso_adp_type_id` (หมวด ADP), `billcode` (รหัสเบิกกรมบัญชีกลาง), `sks_tmlt_code` (รหัสตรวจแล็บ TMLT), `istatus` (สถานะ 'Y'/'N')\n";
        $out .= "   - เกณฑ์ตรวจสอบ: แฟ้ม ADP (16 แฟ้ม/FDH) ต้องผูก nhso_adp_code และ nhso_adp_type_id, สิทธิข้าราชการต้องมี billcode กรมบัญชีกลาง\n\n";
        $out .= "3. [สิทธิการรักษา]: ตั้งต้นด้วยตาราง `pttype` เสมอ (เช่น `FROM pttype p`)\n";
        $out .= "   - เชื่อมสถานะชำระเงิน: `LEFT JOIN paidst p1 ON p1.paidst = p.paidst`\n";
        $out .= "   - เชื่อมกลุ่มสิทธิมาตรฐานประเทศ: `LEFT JOIN pcode pc ON pc.code = p.pcode` (A1=จ่ายเอง, UC=บัตรทอง, OF=ข้าราชการ, SS=ประกันสังคม)\n";
        $out .= "   - เชื่อมสิทธิมาตรฐาน PROVIS/สปสช.: `LEFT JOIN provis_instype pi ON pi.code = p.nhso_code`\n";
        $out .= "   - เชื่อมกลุ่มราคาตามสิทธิ: `LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id = p.pttype_price_group_id`\n";
        $out .= "   - เชื่อมสิทธิย่อย สปสช.: `LEFT JOIN pttype_nhso_subinscl inscl ON inscl.pttype = p.pttype`\n";
        $out .= "   - ฟิลด์สำคัญ: `pttype` (รหัสสิทธิ), `name` (ชื่อสิทธิ), `hipdata_code` (รหัสส่งออก 16 แฟ้ม เช่น UCS, OFC, SSS, LGO), `pttype_std_code` (รหัสส่งออก HOSxP), `isuse` (สถานะ 'Y'/'N')\n";
        $out .= "   - เกณฑ์ตรวจสอบ: ต้องมี hipdata_code ถูกต้องตรงตามกลุ่มสิทธิ pcode และสอดคล้องกับ provis_instype\n\n";
        $out .= "4. [ยาและเวชภัณฑ์]: ตั้งต้นด้วยตาราง `drugitems` (เช่น `FROM drugitems d`)\n";
        $out .= "   - เชื่อมหมวดรายได้: `LEFT JOIN income i ON i.income = d.income` (ปกติ income = '03')\n";
        $out .= "   - เชื่อมรหัสมาตรฐานอ้างอิง: `LEFT JOIN drugitems_ref_code r1 ON r1.icode = d.icode AND r1.drugitems_ref_code_type_id = 1` (24 หลัก), `LEFT JOIN drugitems_ref_code r3 ON r3.icode = d.icode AND r3.drugitems_ref_code_type_id = 3` (TMT)\n";
        $out .= "   - ฟิลด์สำคัญ: `icode` (รหัสยา), `name` (ชื่อยา), `strength`, `units`, `dosageform`, `drugaccount` (บัญชียา: ก, ข, ค, ง, จ = ยาในบัญชี ED | '-', 'NED', หรือว่าง = ยานอกบัญชี NED), `did` (รหัส 24 หลัก), `tmt_tp_code` (รหัส TMT), `unitprice` (ราคา OPD 1), `price2`, `price3`, `ipd_price`, `unitcost`, `stdprice`, `sks_price` (ราคาเบิกกรมบัญชีกลาง), `istatus` (สถานะ 'Y'/'N')\n";
        $out .= "   - เกณฑ์ตรวจสอบ: สำหรับแฟ้ม DRU (16 แฟ้ม/FDH/AIPN/SSOP/CSOP) รายการยา Active ต้องมีรหัส 24 หลักครบถ้วน (ความยาว 24 ตัวอักษร), มีรหัส TMT, ระบุบัญชียา ED/NED ชัดเจน และมีราคาขาย unitprice\n\n";
        $out .= "5. [ตรวจชันสูตร/แล็บ]: ตาราง `lab_items` (ตรวจเดี่ยว) และ `lab_items_sub_group` (ชุดตรวจ/โปรไฟล์)\n";
        $out .= "   - กฎเหล็ก: ตรวจเดี่ยว `lab_items.icode` ต้องผูกเข้ากับ `nondrugitems.icode`, และชุดตรวจ `lab_items_sub_group.group_icode` ต้องผูกเข้ากับ `nondrugitems.icode` เสมอ มิฉะนั้นจะไม่สามารถคิดเงินและส่งเบิกเคลมได้\n";
        $out .= "   - ฟิลด์สำคัญ: `lab_items_code`, `lab_items_name`, `icode`, `tmlt_code` (รหัส TMLT), `loinc_code`, `active_status`\n";
        $out .= "   - เกณฑ์ตรวจสอบ: ตรวจสอบรายการแล็บที่ยังไม่ผูก icode คิดเงิน หรือขาดรหัสมาตรฐาน TMLT\n\n";
        $out .= "6. [รหัสโรค ICD-10 และรหัสหัตถการ ICD-9]:\n";
        $out .= "   - รหัสโรค HOSxP: ตั้งต้นด้วยตาราง `icd101` (เช่น `FROM icd101 i`)\n";
        $out .= "     - ฟิลด์สำคัญ: `code` (รหัสโรค เช่น A00, I10, E119), `name` (ชื่อภาษาอังกฤษ), `tname` (ชื่อภาษาไทย), `active_status` ('Y'=เปิดใช้งาน | 'N'=ปิดใช้งาน), `ipd_valid` (ใช้กับ IPD ได้หรือไม่ 'Y'/'N'), `agemin`, `agemax`, `sex`\n";
        $out .= "     - เทียบเกณฑ์ สกส. (กรมบัญชีกลาง/ข้าราชการ): `LEFT JOIN lookup_icd10_chi chi ON chi.code = i.code`\n";
        $out .= "       - กฎสำคัญ: `chi.accpdx` ('Y'=สกส. ยอมรับเป็นโรคหลัก PDX ได้ | 'N'=สกส. ไม่รับเป็นโรคหลัก PDX เด็ดขาด หากใช้เป็น PDX จะถูกปฏิเสธเคลมหรือติด C-Code)\n";
        $out .= "     - แยกประเภทบริการ OP / PP (สปสช.): `LEFT JOIN lookup_icd10 nhso ON nhso.icd10 = i.code`\n";
        $out .= "       - หมายเหตุ: ตาราง lookup_icd10 เอาไว้แค่แยกประเภทบริการ เช่น `nhso.pp = 'Y'` คือสร้างเสริมสุขภาพป้องกันโรค (PP เช่น รหัสกลุ่ม Z) ส่วนรหัสอื่นคือรักษาพยาบาลทั่วไป (OP) ไม่ใช่เงื่อนไขข้อผิดพลาดการเคลม\n";
        $out .= "   - รหัสหัตถการ HOSxP: ตั้งต้นด้วยตาราง `icd9cm1` (เช่น `FROM icd9cm1 c`)\n";
        $out .= "     - ฟิลด์สำคัญ: `code` (รหัสหัตถการ เช่น 8907, 9904), `name` (ชื่อหัตถการ), `active_status` ('Y'=เปิดใช้งาน | 'N'=ปิดใช้งาน), `export_proced` (ส่งออกหัตถการ)\n";
        $out .= "     - เทียบเกณฑ์มาตรฐาน สกส./ประกันสังคม: `LEFT JOIN lookup_icd9_chi chi9 ON chi9.code = c.code`\n\n";
        $out .= "=== มาตรฐานการตรวจสอบก่อนส่งออกแยกตามกองทุน (Fund Audit Rules) ===\n";
        $out .= "- 16 แฟ้ม / FDH: DRU (24 หลัก, TMT, ED/NED), ADP (nhso_adp_code, adp_type), INS (hipdata_code), PROVIDER (licenseno ว., cid 13 หลัก), DIAG (รหัสโรคที่เปิดใช้งาน active_status = 'Y')\n";
        $out .= "- AIPN (ผู้ป่วยใน ประกันสังคม IPD): อุปกรณ์/อวัยวะเทียมเทียบกับ lookup_sss_equipdev_aipn, ยา 24 หลัก, แพทย์มีเลข ว., หัตถการ ICD-9 ใน lookup_icd9_chi\n";
        $out .= "- SSOP (ผู้ป่วยนอก ประกันสังคม OPD): ค่าบริการ OPD, รหัส ADP, รหัสยา 24 หลัก, สิทธิประกันสังคม (pcode = 'SS')\n";
        $out .= "- CSOP / CIPN (ข้าราชการ กรมบัญชีกลาง OPD/IPD): ยาเทียบกับ drugcat_chi (ราคากลาง, 24 หลัก, ยา จ(2)), nondrugitems.billcode, รหัสโรคหลักต้องผ่านเกณฑ์ lookup_icd10_chi.accpdx != 'N'\n\n";
        $out .= "*** ตัวอย่างคำสั่ง SELECT ที่ถูกต้องและปลอดภัย ***:\n";
        $out .= "- แพทย์ไม่มีเลขใบประกอบฯ: `SELECT d.code, d.name, d.licenseno, d.council_code, d.cid, dp.name AS position_name, s.name AS spclty_name, c.name AS clinic_name, d.active FROM doctor d LEFT JOIN doctor_position dp ON dp.id = d.position_id LEFT JOIN spclty s ON s.spclty = d.spclty LEFT JOIN clinic c ON c.clinic = d.clinic WHERE (d.licenseno IS NULL OR d.licenseno = '' OR d.licenseno LIKE '-%') AND d.active = 'Y'`\n";
        $out .= "- ค่าบริการที่ยังไม่ผูกรหัส ADP: `SELECT n.icode, n.name, n.price, i.name AS income_name, n.nhso_adp_code FROM nondrugitems n LEFT JOIN income i ON i.income = n.income WHERE (n.nhso_adp_code IS NULL OR n.nhso_adp_code = '') AND n.istatus = 'Y' AND n.price > 0`\n";
        $out .= "- ยา Active ขาดรหัส 24 หลัก: `SELECT d.icode, d.name, d.strength, d.drugaccount, d.unitprice, d.did FROM drugitems d WHERE d.istatus = 'Y' AND (d.did IS NULL OR LENGTH(TRIM(d.did)) < 24)`\n";
        $out .= "- ตรวจสอบยา ED หรือ NED: `SELECT d.icode, d.name, d.strength, d.dosageform, d.drugaccount, CASE WHEN d.drugaccount IN ('ก','ข','ค','ง','จ') THEN 'ยาในบัญชียาหลัก (ED)' ELSE 'ยานอกบัญชียาหลัก (NED)' END AS ed_status, d.did, d.tmt_tp_code, d.unitprice, d.sks_price FROM drugitems d WHERE d.icode = '1000001' OR d.did LIKE '%...%'`\n";
        $out .= "- รหัสโรค ICD-10 ที่ปิดใช้งาน: `SELECT code, name, tname, active_status FROM icd101 WHERE active_status = 'N' OR active_status IS NULL`\n";
        $out .= "- รหัสโรคที่ สกส. ไม่รับเป็นโรคหลัก: `SELECT i.code, i.name, i.tname, chi.accpdx, chi.desc FROM icd101 i INNER JOIN lookup_icd10_chi chi ON chi.code = i.code WHERE chi.accpdx = 'N'`\n";
        $out .= "- ตรวจสอบรหัสโรคเจาะจง (สถานะเปิด/ปิด และเกณฑ์ สกส./สปสช.): `SELECT i.code, i.name, i.tname, i.active_status, chi.accpdx AS chi_accpdx, nhso.pp AS nhso_pp FROM icd101 i LEFT JOIN lookup_icd10_chi chi ON chi.code = i.code LEFT JOIN lookup_icd10 nhso ON nhso.icd10 = i.code WHERE i.code = 'A00'`\n";
        $out .= "- รหัสหัตถการ ICD-9 ที่ปิดใช้งาน: `SELECT code, name, active_status FROM icd9cm1 WHERE active_status = 'N'`\n";
        $out .= "- แล็บที่ยังไม่ผูก icode: `SELECT l.lab_items_code, l.lab_items_name, l.icode, l.tmlt_code FROM lab_items l WHERE l.active_status = 'Y' AND (l.icode IS NULL OR l.icode = '')`\n";
        $out .= "- สิทธิที่รหัสส่งออกไม่ตรงกับ PROVIS: `SELECT p.pttype, p.name, p.hipdata_code, p.pttype_std_code, pi.code AS provis_code, pi.name AS provis_name, pi.pttype_std_code AS provis_std_code FROM pttype p LEFT JOIN provis_instype pi ON pi.code = p.nhso_code WHERE p.isuse = 'Y' AND (p.pttype_std_code != pi.pttype_std_code OR p.pttype_std_code IS NULL)`\n\n";
        $out .= "*** กฎเหล็กและขอบเขตข้อมูล ***:\n";
        $out .= "- สามารถสืบค้นตรวจสอบข้อมูลพื้นฐานทั้ง 6 เสาหลัก (doctor, nondrugitems, pttype, drugitems, lab_items, icd101/icd9cm1) และตารางเชื่อมโยง/แคตตาล็อกที่เกี่ยวข้องได้ครบถ้วน\n";
        $out .= "- คำสั่ง SELECT ทุกคำสั่งต้องปลอดภัย (Read-Only) และห้าม INSERT/UPDATE/DELETE เด็ดขาด\n\n";

        foreach ($tables as $table => $info) {
            $out .= "TABLE: `{$table}` -- {$info['description']}\nCOLUMNS:\n";
            foreach ($info['columns'] as $col => $desc) {
                $out .= "  - `{$col}`: {$desc}\n";
            }
            $out .= "\n";
        }

        return $out;
    }

    /**
     * Curated dictionary of all 12 HosFin tables and accurate columns
     */
    public function getCuratedHosfinTables(): array
    {
        return [
            'hosfin_gl_ap_bills' => [
                'description' => 'ตารางบิลเจ้าหนี้การค้า (Accounts Payable Bills): รายการหนี้ ค่าวัสดุ/ยา/บริการ ที่โรงพยาบาลต้องจ่ายชำระให้เจ้าหนี้/ผู้ขาย',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'vendor_name' => 'varchar(255) ชื่อบริษัทเจ้าหนี้/ผู้ขาย/คู่ค้า',
                    'category' => 'varchar(100) หมวดหมู่รายการ (เช่น ยา, เวชภัณฑ์, ค่าจ้าง, ค่าบริการ, ทั่วไป)',
                    'bill_no' => 'varchar(100) เลขที่บิล/ใบแจ้งหนี้/ใบรับวางบิล',
                    'bill_date' => 'date วันที่ลงในบิล/ใบแจ้งหนี้',
                    'account_code' => 'varchar(50) รหัสผังบัญชีเจ้าหนี้',
                    'account_name' => 'varchar(255) ชื่อผังบัญชีเจ้าหนี้',
                    'total_credit' => 'decimal(15,2) ยอดหนี้รวมตามบิล (ยอดตั้งหนี้)',
                    'total_debit' => 'decimal(15,2) ยอดเงินที่จ่ายชำระไปแล้ว',
                    'remaining_debt' => 'decimal(15,2) ยอดหนี้คงเหลือที่ยังค้างจ่าย (ถ้า > 0 คือยังค้างชำระ)',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ. เช่น 2568, 2569)',
                    'is_paid' => 'tinyint(4) สถานะการชำระ (0=ค้างชำระ, 1=ชำระครบแล้ว)',
                ]
            ],
            'hosfin_gl_ar_debtors' => [
                'description' => 'ตารางลูกหนี้ค่ารักษาพยาบาล (Accounts Receivable Debtors): ยอดตั้งหนี้ เรียกเก็บ และหนี้ค้างชำระแยกตามกองทุน/สิทธิการรักษา',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'account_code' => 'varchar(50) รหัสผังบัญชีลูกหนี้',
                    'account_name' => 'varchar(255) ชื่อบัญชีลูกหนี้ (เช่น ลูกหนี้ค่ารักษาพยาบาล-สิทธิบัตรทอง, ประกันสังคม, ข้าราชการ)',
                    'debtor_type' => 'varchar(100) ประเภทลูกหนี้/กองทุนสุขภาพ',
                    'total_billed' => 'decimal(15,2) ยอดตั้งลูกหนี้/ยอดเรียกเก็บทั้งหมด',
                    'total_collected' => 'decimal(15,2) ยอดเงินชดเชยที่ได้รับชำระจริงแล้ว',
                    'outstanding_balance' => 'decimal(15,2) ยอดลูกหนี้คงค้างรอเรียกเก็บ/รอชดเชย (หนี้ค้างชำระ)',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ. เช่น 2568, 2569)',
                    'fiscal_month' => 'int(11) เดือนงบประมาณ (1-12)',
                ]
            ],
            'hosfin_trial_balance' => [
                'description' => 'ตารางงบทดลอง (Trial Balance): ยอดยกมา ยอดประจำงวด และยอดคงเหลือสุทธิยกไป ทุกหมวดบัญชี',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'acc_year' => 'int(11) ปีบัญชี/ปีงบประมาณ (พ.ศ. เช่น 2568, 2569)',
                    'acc_month' => 'tinyint(4) เดือนบัญชี (1-12)',
                    'acc_period' => 'varchar(10) งวดบัญชี (เช่น 2569/01)',
                    'main_account_code' => 'varchar(30) รหัสบัญชีหลัก',
                    'account_code' => 'varchar(30) รหัสผังบัญชี',
                    'account_name' => 'varchar(255) ชื่อผังบัญชี',
                    'debit_bf' => 'decimal(15,2) ยอดเดบิตยกมาต้นงวด',
                    'credit_bf' => 'decimal(15,2) ยอดเครดิตยกมาต้นงวด',
                    'debit_month' => 'decimal(15,2) ยอดเดบิตเคลื่อนไหวประจำงวดเดือนนี้',
                    'credit_month' => 'decimal(15,2) ยอดเครดิตเคลื่อนไหวประจำงวดเดือนนี้',
                    'debit_net' => 'decimal(15,2) ยอดเดบิตสุทธิยกไปปลายงวด',
                    'credit_net' => 'decimal(15,2) ยอดเครดิตสุทธิยกไปปลายงวด',
                ]
            ],
            'hosfin_gl_daily_summaries' => [
                'description' => 'ตารางสรุปการเงินและกระแสเงินสดรายวัน (Daily Financial Summaries & Cash Flow): รายรับ รายจ่าย กระแสเงินสดสุทธิ และเงินสดคงเหลือสะสม',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'summary_date' => 'date วันที่สรุปข้อมูล',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ.)',
                    'total_income' => 'decimal(15,2) รายรับรวมประจำวัน',
                    'total_expense' => 'decimal(15,2) รายจ่ายรวมประจำวัน',
                    'net_cash_flow' => 'decimal(15,2) กระแสเงินสดสุทธิประจำวัน (รายรับ - รายจ่าย)',
                    'cash_balance' => 'decimal(15,2) ยอดเงินสดและเงินฝากธนาคารคงเหลือสะสม',
                    'voucher_count' => 'int(11) จำนวนใบสำคัญบันทึกบัญชีในวันนั้น',
                ]
            ],
            'hosfin_gl_cost_summaries' => [
                'description' => 'ตารางสรุปต้นทุนโรงพยาบาลรายเดือน (Hospital Cost Summaries): โครงสร้างต้นทุน LC (ค่าแรง), MC (ค่าวัสดุ), CC (ค่าลงทุน/เสื่อมราคา)',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ. เช่น 2569)',
                    'fiscal_month' => 'int(11) เดือนงบประมาณ (1-12)',
                    'lc_amount' => 'decimal(15,2) ต้นทุนค่าแรงบุคลากร (Labor Cost - LC)',
                    'mc_amount' => 'decimal(15,2) ต้นทุนค่าวัสดุ ยา เวชภัณฑ์ (Material Cost - MC)',
                    'cc_amount' => 'decimal(15,2) ต้นทุนค่าลงทุน ค่าเสื่อมราคาและสิ่งก่อสร้าง (Capital Cost - CC)',
                    'other_cost' => 'decimal(15,2) ต้นทุนค่าใช้จ่ายดำเนินงานอื่นๆ',
                    'total_cost' => 'decimal(15,2) ต้นทุนรวมทั้งหมดประจำเดือน',
                ]
            ],
            'hosfin_gl_monthly_balances' => [
                'description' => 'ตารางยอดยกมารายเดือนตามผังบัญชี (Monthly Account Balances): ยอดยกมา ยอดประจำงวด และยอดยกไปรายเดือน',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ.)',
                    'fiscal_month' => 'int(11) เดือนงบประมาณ (1-12)',
                    'acc_period' => 'varchar(10) งวดบัญชี',
                    'account_code' => 'varchar(50) รหัสบัญชี',
                    'account_name' => 'varchar(255) ชื่อบัญชี',
                    'account_type' => 'varchar(50) หมวดบัญชี (สินทรัพย์, หนี้สิน, ทุน, รายได้, ค่าใช้จ่าย)',
                    'beginning_debit' => 'decimal(15,2) เดบิตยอดยกมาต้นงวด',
                    'beginning_credit' => 'decimal(15,2) เครดิตยอดยกมาต้นงวด',
                    'period_debit' => 'decimal(15,2) เดบิตประจำงวด',
                    'period_credit' => 'decimal(15,2) เครดิตประจำงวด',
                    'ending_debit' => 'decimal(15,2) เดบิตสุทธิยกไปปลายงวด',
                    'ending_credit' => 'decimal(15,2) เครดิตสุทธิยกไปปลายงวด',
                ]
            ],
            'hosfin_gl_journals' => [
                'description' => 'ตารางใบสำคัญสมุดรายวันทั่วไป (Journal Vouchers Header): รายการบันทึกบัญชีรายวันและเลขที่ใบสำคัญ',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'voucher_no' => 'varchar(50) เลขที่ใบสำคัญสมุดรายวัน (ใช้ JOIN กับ hosfin_gl_journal_items.voucher_no)',
                    'voucher_date' => 'date วันที่ลงบัญชี/วันที่ใบสำคัญ',
                    'journal_type' => 'varchar(20) ประเภทสมุดรายวัน (เช่น JV=ทั่วไป, AP=ซื้อ/เจ้าหนี้, AR=รับชำระ/ลูกหนี้, CP=จ่ายเงิน, CR=รับเงิน)',
                    'description' => 'text คำอธิบายรายการลงบัญชี',
                    'total_debit' => 'decimal(15,2) ยอดรวมเดบิตของใบสำคัญ',
                    'total_credit' => 'decimal(15,2) ยอดรวมเครดิตของใบสำคัญ',
                    'posted_status' => 'varchar(20) สถานะการผ่านรายการ (POSTED, UNPOSTED)',
                    'fiscal_year' => 'int(11) ปีงบประมาณ (พ.ศ.)',
                    'fiscal_month' => 'int(11) เดือนงบประมาณ (1-12)',
                    'apar' => 'varchar(100) ข้อมูลเจ้าหนี้/ลูกหนี้ที่เกี่ยวข้อง',
                ]
            ],
            'hosfin_gl_journal_items' => [
                'description' => 'ตารางรายการเดบิต/เครดิต สมุดรายวัน (Journal Voucher Lines): รายการลงบัญชีรายบรรทัด รหัสบัญชี ยอดเดบิต เครดิต และแผนก',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'journal_id' => 'bigint(20) FK รหัสใบสำคัญ (อ้างอิง hosfin_gl_journals.id)',
                    'voucher_no' => 'varchar(50) เลขที่ใบสำคัญ (อ้างอิง hosfin_gl_journals.voucher_no)',
                    'item_no' => 'int(11) ลำดับรายการในใบสำคัญ',
                    'account_code' => 'varchar(50) รหัสผังบัญชี',
                    'account_name' => 'varchar(255) ชื่อผังบัญชี',
                    'description' => 'varchar(255) คำอธิบายรายการรายบรรทัด',
                    'debit' => 'decimal(15,2) ยอดเงินด้านเดบิต',
                    'credit' => 'decimal(15,2) ยอดเงินด้านเครดิต',
                    'department' => 'varchar(100) แผนก/ศูนย์ต้นทุนที่บันทึก',
                ]
            ],
            'hosfin_gl_accounts' => [
                'description' => 'ตารางผังบัญชี GL (Chart of Accounts Master): รายชื่อและรหัสผังบัญชีทั้งหมดของโรงพยาบาล',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'account_code' => 'varchar(50) รหัสผังบัญชี',
                    'account_name' => 'varchar(255) ชื่อผังบัญชี',
                    'account_type' => 'varchar(50) หมวดบัญชี (1=สินทรัพย์, 2=หนี้สิน, 3=ทุน, 4=รายได้, 5=ค่าใช้จ่าย)',
                    'account_category' => 'varchar(100) หมวดหมู่บัญชีย่อย',
                    'normal_balance' => 'varchar(10) ด้านปกติของบัญชี (DEBIT หรือ CREDIT)',
                    'is_active' => 'tinyint(4) สถานะเปิดใช้งาน (1=เปิดใช้งาน, 0=ปิด)',
                    'cost_type' => 'varchar(20) ประเภทต้นทุน (LC, MC, CC)',
                    'service_type' => 'varchar(20) ประเภทบริการ',
                ]
            ],
            'hosfin_gl_subledgers' => [
                'description' => 'ตารางทะเบียนคุมบัญชีย่อยและผู้ขาย (Subledgers & Vendors Master): รหัสบัญชีย่อยและรายชื่อบริษัทคู่ค้า',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'subledger_code' => 'varchar(100) รหัสบัญชีย่อย',
                    'vendor_name' => 'varchar(255) ชื่อผู้ขาย/บริษัท/หน่วยงาน',
                    'category' => 'varchar(100) หมวดหมู่คู่ค้า',
                    'raw_note' => 'varchar(255) หมายเหตุเพิ่มเติม',
                ]
            ],
            'hosfin_dtl_mappings' => [
                'description' => 'ตารางผังบัญชี DTL Mappings: การจับคู่รหัสกลุ่มบัญชี DTL และรหัสบัญชีของโรงพยาบาล',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'group_code' => 'varchar(30) รหัสกลุ่มบัญชี DTL',
                    'group_name' => 'varchar(255) ชื่อกลุ่มบัญชี DTL',
                    'account_code' => 'varchar(30) รหัสบัญชี',
                    'account_name' => 'varchar(255) ชื่อบัญชี',
                ]
            ],
            'hosfin_gl_sync_logs' => [
                'description' => 'ตารางประวัติการซิงค์ข้อมูล (GL Sync Logs): บันทึกประวัติการนำเข้าและซิงค์ข้อมูลบัญชีการเงิน',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'sync_type' => 'varchar(50) ประเภทข้อมูลที่ซิงค์',
                    'records_count' => 'int(11) จำนวนรายการที่ประมวลผล',
                    'status' => 'varchar(20) สถานะการซิงค์ (SUCCESS, FAILED)',
                    'message' => 'text ข้อความผลการซิงค์',
                    'agent_ip' => 'varchar(50) ที่อยู่ IP เครื่องที่ดำเนินการ',
                    'duration_seconds' => 'decimal(8,2) ระยะเวลาที่ใช้ในการซิงค์ (วินาที)',
                ]
            ],
            'hosfin_planfin_categories' => [
                'description' => 'ตารางหมวดแผนเงินบำรุงโรงพยาบาล (PlanFin Categories Master): หมวดรายได้ (P04-P13), หมวดค่าใช้จ่าย (P14-P251), สรุปรายได้ (P13S), สรุปค่าใช้จ่าย (P26S), รายได้สุทธิ (P27S), และ EBITDA (P29)',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'plan_code' => 'varchar(20) รหัสหมวดแผนเงินบำรุง (เช่น P04, P13S, P14, P26S, P27S, P29)',
                    'plan_name' => 'varchar(255) ชื่อหมวดแผนเงินบำรุง (เช่น รายได้ UC, รวมรายได้, ต้นทุนยา, EBITDA)',
                    'category_type' => 'varchar(20) ประเภทหมวด (revenue, expense, summary, kpi)',
                    'sort_order' => 'int(11) ลำดับการจัดเรียงหมวดแผน',
                ]
            ],
            'hosfin_planfin_targets' => [
                'description' => 'ตารางเป้าหมายแผนเงินบำรุงโรงพยาบาล (PlanFin Targets & Estimates): เป้าหมายรายได้ ค่าใช้จ่าย กำไรสุทธิ และ EBITDA ตามปีงบประมาณและรอบแผน',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'budget_year' => 'int(11) ปีงบประมาณ (พ.ศ. เช่น 2569, 2570)',
                    'round_no' => 'varchar(20) รอบการจัดทำแผน (เช่น 256901, 256902, 1st, 2nd)',
                    'plan_code' => 'varchar(20) รหัสหมวดแผน (Foreign Key -> hosfin_planfin_categories.plan_code)',
                    'baseline_amount' => 'decimal(15,2) ยอดฐานผลการดำเนินงานอ้างอิง',
                    'growth_rate' => 'decimal(8,2) อัตราการเติบโตเป้าหมาย (%)',
                    'target_amount' => 'decimal(15,2) ยอดเป้าหมายแผนเงินบำรุงทั้งปี (บาท)',
                    'notes' => 'text บันทึกคำอธิบายเป้าหมาย',
                    'created_by' => 'bigint(20) รหัสผู้บันทึกแผน',
                ]
            ],
            'hosfin_planfin_mappings' => [
                'description' => 'ตารางจับคู่ผังบัญชีกับหมวดแผนเงินบำรุง (PlanFin Account Mappings): ผูกรหัสผังบัญชี (GL Account) เข้ากับหมวดแผนเงินบำรุง PlanFin',
                'columns' => [
                    'id' => 'bigint(20) รหัสรายการ (Primary Key)',
                    'account_code' => 'varchar(50) รหัสผังบัญชีโรงพยาบาล',
                    'account_name' => 'varchar(255) ชื่อผังบัญชี',
                    'plan_code' => 'varchar(20) รหัสหมวดแผนเงินบำรุงที่ผูก (Foreign Key -> hosfin_planfin_categories.plan_code)',
                    'plan_name' => 'varchar(255) ชื่อหมวดแผนเงินบำรุง',
                ]
            ]
        ];
    }

    /**
     * Curated dictionary of HOSxP ข้อมูลพื้นฐาน & Lookup tables
     */
    public function getCuratedHosxpTables(): array
    {
        return [
            'nondrugitems' => [
                'description' => 'ตารางตั้งค่ารายการค่าบริการและหัตถการที่ไม่ใช่ยา (ข้อมูลพื้นฐานค่าบริการ)',
                'columns' => [
                    'icode' => 'varchar(7) รหัสรายการค่ารักษาพยาบาล (Primary Key ขึ้นต้นด้วย 3)',
                    'name' => 'varchar(200) ชื่อรายการค่าบริการ/หัตถการ',
                    'price' => 'double ราคาค่าบริการปกติ (ราคา 1)',
                    'price2' => 'double ราคาค่าบริการราคา 2',
                    'price3' => 'double ราคาค่าบริการราคา 3',
                    'income' => 'char(2) รหัสหมวดค่ารักษาพยาบาล (Foreign Key -> income.income)',
                    'nhso_adp_type_id' => 'int ประเภทค่าบริการ ADP สปสช. (Foreign Key -> nhso_adp_type.nhso_adp_type_id)',
                    'nhso_adp_code' => 'varchar(15) รหัสมาตรฐานค่าบริการ ADP สปสช. สำหรับส่งเบิก e-Claim',
                    'billcode' => 'varchar(10) รหัสเบิกจ่ายตามระเบียบกรมบัญชีกลาง (CSMBS)',
                    'unit' => 'varchar(100) หน่วยนับค่าบริการ',
                    'istatus' => 'char(1) สถานะการใช้งาน (Y=ใช้งานปกติ, N=ยกเลิก)',
                    'paidst' => 'char(2) สถานะการชำระเงินเริ่มต้น (Foreign Key -> paidst.paidst)',
                    'sks_claim_category_type_id' => 'int หมวดการเคลม e-Claim สกส.',
                    'sks_tmlt_code' => 'varchar(15) รหัสตรวจแล็บมาตรฐาน TMLT',
                    'moph_price_item_code' => 'varchar(10) รหัสค่าบริการสาธารณสุข MOPH',
                ]
            ],
            'pttype' => [
                'description' => 'ตารางตั้งค่าสิทธิการรักษาพยาบาล (Health Insurance Rights Master)',
                'columns' => [
                    'pttype' => 'char(2) รหัสสิทธิการรักษาพยาบาล (Primary Key เช่น 10, 20, 30, UCS, OFC, SSS, O1, O3)',
                    'name' => 'varchar(250) ชื่อสิทธิการรักษาพยาบาล',
                    'pcode' => 'char(2) กลุ่มสิทธิมาตรฐานระดับประเทศ (Foreign Key -> pcode.code เช่น A1=จ่ายเอง, UC=บัตรทอง, OF=ข้าราชการ, SS=ประกันสังคม)',
                    'hipdata_code' => 'varchar(6) รหัสส่งออก 16 แฟ้ม (มาตรฐาน e-Claim / 16 แฟ้ม)',
                    'paidst' => 'char(2) สถานะการชำระเงินเริ่มต้น (Foreign Key -> paidst.paidst เช่น 00=ค้างชำระ, 01=ชำระเองเบิกได้, 02=ลูกหนี้สิทธิ)',
                    'price_type' => 'int ลำดับราคาที่เลือกใช้จาก Master (1=price, 2=price2, 3=price3)',
                    'pttype_price_group_id' => 'int รหัสกลุ่มราคาตามสิทธิ (เชื่อมกับ pttype_items_price)',
                    'max_debt_money' => 'double เพดานหนี้สูงสุดที่อนุญาต',
                    'isuse' => 'char(1) สถานะเปิดใช้งาน (Y=ใช้งาน, N=ไม่ใช้งาน)',
                ]
            ],
            'doctor' => [
                'description' => 'ตารางตั้งค่ารายชื่อแพทย์และบุคลากรทางการแพทย์ (Doctor & Staff Master)',
                'columns' => [
                    'code' => 'varchar(15) รหัสแพทย์ในระบบ HOSxP (Primary Key)',
                    'name' => 'varchar(150) ชื่อ-นามสกุลแพทย์หรือผู้ตรวจรักษา',
                    'licenseno' => 'varchar(50) เลขที่ใบอนุญาตประกอบวิชาชีพเวชกรรม (เลข ว., ท., พ. ฯลฯ)',
                    'council_code' => 'varchar(2) รหัสสภาวิชาชีพ (01=แพทยสภา, 02=ทันตแพทยสภา, 03=สภาการพยาบาล, 04=สภาเภสัชกรรม)',
                    'position_id' => 'int รหัสตำแหน่งสายงาน (Foreign Key -> doctor_position.id)',
                    'spclty' => 'char(2) รหัสสาขาความเชี่ยวชาญทางการแพทย์ (Foreign Key -> spclty.spclty)',
                    'clinic' => 'char(3) รหัสคลินิกประจำ (Foreign Key -> clinic.clinic)',
                    'cid' => 'varchar(17) เลขประจำตัวประชาชน 13 หลัก',
                    'active' => 'char(1) สถานะการปฏิบัติงาน (Y=ปฏิบัติงานอยู่, N=ลาออก/ย้าย)',
                ]
            ],
            'pttype_items_price' => [
                'description' => 'ตารางกำหนดราคาแยกตามสิทธิการรักษาพยาบาล (Price by Insurance Right Master): HOSxP v4 แยกเก็บราคาตามสิทธิต่างๆ ไว้ที่นี่ โดยราคาหลักจะอยู่ที่ nondrugitems.price ส่วนราคาแยกสิทธิต่างๆ อยู่ที่ pttype_items_price.price เชื่อมด้วย items_table_code = nondrugitems.icode (ห้ามกรอง WHERE items_table_name)',
                'columns' => [
                    'pttype_items_price_id' => 'int รหัสรายการราคาตามสิทธิ (Primary Key)',
                    'items_table_name' => 'varchar(50) ชื่อตารางรายการ (ใน HOSxP v4 เก็บเป็น drugitems ทั้งหมด ห้ามกรองฟิลด์นี้)',
                    'items_table_code' => 'varchar(100) รหัสรายการ (เก็บรหัส icode เชื่อมกับ nondrugitems.icode ห้ามใช้ชื่อคอลัมน์ icode ในตารางนี้)',
                    'pttype' => 'char(2) รหัสสิทธิการรักษา (เชื่อมกับ pttype.pttype)',
                    'pttype_price_group_id' => 'int รหัสกลุ่มราคาตามสิทธิ',
                    'price' => 'double ราคาที่กำหนดสำหรับสิทธินี้ (เปรียบเทียบกับราคาหลักใน nondrugitems.price)',
                    'discount_percent' => 'double เปอร์เซ็นต์ส่วนลดตามสิทธิ (%)',
                    'paidst' => 'char(2) สถานะการชำระเงินสำหรับสิทธินี้',
                ]
            ],
            'income' => [
                'description' => 'ตารางหมวดค่ารักษาพยาบาล 16 หมวดมาตรฐาน (Income Master: ค่าห้อง, ค่ายา, ค่าแล็บ, ค่าผ่าตัด ฯลฯ)',
                'columns' => [
                    'income' => 'char(2) รหัสหมวดค่ารักษา (Primary Key เช่น 01=ค่าห้อง/อาหาร, 02=ค่าอวัยวะเทียม, 03=ค่ายา, 07=ค่าตรวจวินิจฉัย/แล็บ, 11=ค่าผ่าตัด/หัตถการ)',
                    'name' => 'varchar(200) ชื่อหมวดค่ารักษาพยาบาล',
                    'income_group' => 'char(2) กลุ่มหมวดค่ารักษา',
                    'drg_group' => 'char(2) หมวดตามเกณฑ์ DRGs',
                    'std_group' => 'char(2) หมวดมาตรฐาน สปสช.',
                ]
            ],
            'paidst' => [
                'description' => 'ตารางสถานะการชำระเงินค่ารักษาพยาบาล (Payment Status Master)',
                'columns' => [
                    'paidst' => 'char(2) รหัสสถานะ (Primary Key เช่น 00=ค้างชำระ, 01=ชำระเองเบิกได้, 02=ลูกหนี้สิทธิ)',
                    'name' => 'varchar(100) ชื่อสถานะการชำระเงิน',
                ]
            ],
            'pcode' => [
                'description' => 'ตารางกลุ่มสิทธิมาตรฐานระดับประเทศ (National Rights Group Master)',
                'columns' => [
                    'code' => 'char(2) รหัสกลุ่มสิทธิ (Primary Key เช่น A1=จ่ายเงินเอง, A2=เบิกต้นสังกัด, UC=บัตรทอง, OF=ข้าราชการ, SS=ประกันสังคม)',
                    'name' => 'varchar(150) ชื่อกลุ่มสิทธิมาตรฐาน',
                ]
            ],
            'spclty' => [
                'description' => 'ตารางสาขาความเชี่ยวชาญทางการแพทย์ (Medical Specialties Master)',
                'columns' => [
                    'spclty' => 'char(2) รหัสสาขา (Primary Key เช่น 01=อายุรกรรม, 02=ศัลยกรรม, 03=สูติกรรม, 04=กุมารเวชกรรม)',
                    'name' => 'varchar(100) ชื่อสาขาความเชี่ยวชาญ',
                ]
            ],
            'doctor_position' => [
                'description' => 'ตารางตำแหน่งวิชาชีพทางการแพทย์ (Doctor Positions Master)',
                'columns' => [
                    'id' => 'int รหัสตำแหน่ง (Primary Key เช่น 1=แพทย์, 2=ทันตแพทย์, 3=เภสัชกร, 4=พยาบาล)',
                    'name' => 'varchar(100) ชื่อตำแหน่งวิชาชีพ',
                ]
            ],
            'clinic' => [
                'description' => 'ตารางคลินิกบริการในโรงพยาบาล (Hospital Clinic Master)',
                'columns' => [
                    'clinic' => 'char(3) รหัสคลินิก (Primary Key เช่น 001=อายุรกรรม, 002=ศัลยกรรม)',
                    'name' => 'varchar(150) ชื่อคลินิก',
                    'depcode' => 'varchar(3) รหัสแผนก',
                ]
            ],
            'provis_instype' => [
                'description' => 'ตารางมาตรฐานสิทธิการรักษาพยาบาล PROVIS / สนย. (PROVIS Insurance Types Master)',
                'columns' => [
                    'code' => 'char(2) รหัสสิทธิมาตรฐาน PROVIS (Primary Key เชื่อมกับ pttype.nhso_code)',
                    'name' => 'varchar(150) ชื่อสิทธิมาตรฐาน PROVIS / สปสช.',
                    'pttype_std_code' => 'char(5) รหัสส่งออกตามมาตรฐาน PROVIS (เปรียบเทียบกับ pttype.pttype_std_code)',
                ]
            ],
            'pttype_price_group' => [
                'description' => 'ตารางกลุ่มราคาตามสิทธิการรักษาพยาบาล (Price Groups Master)',
                'columns' => [
                    'pttype_price_group_id' => 'int รหัสกลุ่มราคาตามสิทธิ (Primary Key เชื่อมกับ pttype.pttype_price_group_id)',
                    'pttype_price_group_name' => 'varchar(200) ชื่อกลุ่มราคาตามสิทธิ',
                ]
            ],
            'pttype_nhso_subinscl' => [
                'description' => 'ตารางจับคู่สิทธิการรักษากับรหัสสิทธิย่อย สปสช. (NHSO Sub-Insurance Classification Mapping)',
                'columns' => [
                    'pttype' => 'char(2) รหัสสิทธิการรักษา (เชื่อมกับ pttype.pttype)',
                    'nhso_subinscl' => 'varchar(3) รหัสสิทธิย่อย สปสช. (เช่น 01, 02, 03)',
                ]
            ],
            'drugitems' => [
                'description' => 'ตารางข้อมูลยาและเวชภัณฑ์ (Drug Items Master): รายการยา, รหัสมาตรฐาน 24 หลัก, TMT, ราคาแยกเก็บ และบัญชียา ED/NED',
                'columns' => [
                    'icode' => 'varchar(7) รหัสรายการยา (Primary Key ขึ้นต้นด้วย 1 หรือ 2)',
                    'name' => 'varchar(150) ชื่อทางการค้า/ชื่อยา',
                    'strength' => 'varchar(100) ความแรงของยา',
                    'units' => 'varchar(50) หน่วยนับ',
                    'dosageform' => 'varchar(50) รูปแบบยา (เม็ด, แคปซูล, ฉีด ฯลฯ)',
                    'drugaccount' => 'varchar(10) บัญชียาหลักแห่งชาติ: ก, ข, ค, ง, จ = ยาในบัญชี (ED) | ว่าง, \'-\', หรือ \'NED\' = ยานอกบัญชี (NED)',
                    'did' => 'varchar(30) รหัสยามาตรฐาน 24 หลัก (NDC24)',
                    'tmt_tp_code' => 'varchar(20) รหัสมาตรฐานยาไทย TMT (TP/GPU)',
                    'unitprice' => 'double ราคาจำหน่าย OPD ปกติ (ราคา 1)',
                    'price2' => 'double ราคาจำหน่าย OPD ราคา 2',
                    'price3' => 'double ราคาจำหน่าย OPD ราคา 3',
                    'ipd_price' => 'double ราคาจำหน่าย IPD',
                    'unitcost' => 'double ราคาทุนต่อหน่วย',
                    'stdprice' => 'double ราคากลางมาตรฐาน',
                    'sks_price' => 'double ราคาเบิกจ่ายตรงกรมบัญชีกลาง (CSMBS/CSOP/CIPN)',
                    'sks_reimb_price' => 'double ราคาชดเชยกรมบัญชีกลาง',
                    'income' => 'char(2) หมวดรายได้ (Foreign Key -> income.income ปกติคือ 03 ค่ายา)',
                    'istatus' => 'char(1) สถานะการใช้งาน (Y=เปิดใช้งาน, N=ยกเลิก)',
                ]
            ],
            'drugitems_ref_code' => [
                'description' => 'ตารางเก็บประวัติรหัสมาตรฐานอ้างอิงของยา (Drug Reference Codes): type 1 = รหัส 24 หลัก, type 2 = Barcode, type 3 = รหัส TMT, type 4 = รหัส TTMT (ยาแผนไทย)',
                'columns' => [
                    'drugitems_ref_code_id' => 'int รหัสรายการ (Primary Key)',
                    'icode' => 'varchar(7) รหัสยา (เชื่อมกับ drugitems.icode)',
                    'drugitems_ref_code_type_id' => 'int ประเภท (1=24หลัก, 2=Barcode, 3=TMT, 4=TTMT)',
                    'ref_code' => 'varchar(100) ค่ารหัสมาตรฐานอ้างอิง',
                ]
            ],
            'drugusage' => [
                'description' => 'ตารางวิธีใช้ยา (Drug Usage Master): วิธีการรับประทานยาและข้อบ่งใช้สำหรับพิมพ์สติ๊กเกอร์ยาและส่งออกแฟ้ม DRU',
                'columns' => [
                    'drugusage' => 'varchar(10) รหัสวิธีใช้ยา (Primary Key)',
                    'name1' => 'varchar(100) ข้อความวิธีใช้แถวที่ 1',
                    'name2' => 'varchar(100) ข้อความวิธีใช้แถวที่ 2',
                    'name3' => 'varchar(100) ข้อความวิธีใช้แถวที่ 3',
                    'shortlist' => 'varchar(50) ชื่อย่อคำสั่งวิธีใช้',
                    'status' => 'char(1) สถานะการใช้งาน (Y=ใช้งาน, N=ไม่ใช้งาน)',
                ]
            ],
            'lab_items' => [
                'description' => 'ตารางรายการตรวจชันสูตรทางห้องปฏิบัติการเดี่ยว (Lab Single Items Master): ต้องผูก icode เข้ากับ nondrugitems จึงจะคิดเงินและส่งเคลมได้',
                'columns' => [
                    'lab_items_code' => 'int รหัสรายการตรวจแล็บ (Primary Key)',
                    'lab_items_name' => 'varchar(100) ชื่อการตรวจแล็บ',
                    'icode' => 'varchar(7) รหัสค่าบริการใน nondrugitems ที่ต้องผูก (หากเว้นว่างจะคิดเงินและส่งเคลมไม่ได้)',
                    'service_price' => 'double ราคาค่าตรวจ OPD 1',
                    'service_price2' => 'double ราคาค่าตรวจ OPD 2',
                    'service_price3' => 'double ราคาค่าตรวจ OPD 3',
                    'service_price_ipd' => 'double ราคาค่าตรวจ IPD',
                    'service_cost' => 'double ราคาทุนค่าตรวจ',
                    'tmlt_code' => 'varchar(20) รหัสตรวจแล็บมาตรฐาน TMLT (Thai Medical Laboratory Terminology)',
                    'loinc_code' => 'varchar(20) รหัสสากล LOINC',
                    'lab_items_sub_group_code' => 'int รหัสชุดตรวจโปรไฟล์ (Foreign Key -> lab_items_sub_group.lab_items_sub_group_code)',
                    'lab_items_group' => 'int แผนกกลุ่มงานแล็บ (Foreign Key -> lab_items_group.lab_items_group_code)',
                    'active_status' => 'char(1) สถานะการใช้งาน (Y=เปิดใช้งาน, N=ปิดใช้งาน)',
                ]
            ],
            'lab_items_sub_group' => [
                'description' => 'ตารางชุดตรวจ/โปรไฟล์ทางห้องปฏิบัติการ (Lab Profile / Sub Group Master เช่น CBC, Lipid, Electrolyte): ต้องผูก group_icode เข้ากับ nondrugitems',
                'columns' => [
                    'lab_items_sub_group_code' => 'int รหัสชุดตรวจโปรไฟล์ (Primary Key)',
                    'lab_items_sub_group_name' => 'varchar(100) ชื่อชุดตรวจ/โปรไฟล์ (เช่น CBC, Lipid Profile, LFT)',
                    'group_icode' => 'varchar(7) รหัสค่าบริการชุดตรวจใน nondrugitems (หากเว้นว่างจะคิดเงินชุดตรวจไม่ได้)',
                    'group_price' => 'double ราคาชุดตรวจ OPD 1',
                    'group_price_ipd' => 'double ราคาชุดตรวจ IPD',
                    'tmlt_code' => 'varchar(20) รหัสตรวจแล็บมาตรฐาน TMLT ของชุดตรวจ',
                    'loinc_code' => 'varchar(20) รหัสสากล LOINC',
                    'active_status' => 'char(1) สถานะการใช้งาน (Y=เปิดใช้งาน, N=ปิดใช้งาน)',
                ]
            ],
            'drugcat_nhso' => [
                'description' => 'ตารางแคตตาล็อกยา สปสช. (NHSO Drug Catalog Master ใน RiMS): ฐานข้อมูลรายการยามาตรฐาน สปสช. สำหรับตรวจสอบความถูกต้องของรหัส 24 หลัก, TMT, ราคาเบิกจ่าย และสถานะ ED/NED',
                'columns' => [
                    'hospdrugcode' => 'varchar(255) รหัสยาของโรงพยาบาล (เชื่อมกับ drugitems.icode)',
                    'genericname' => 'varchar(255) ชื่อสามัญทางยา',
                    'tradename' => 'varchar(255) ชื่อทางการค้า',
                    'dosageform' => 'varchar(255) รูปแบบยา',
                    'strength' => 'varchar(255) ความแรงยา',
                    'unitprice' => 'double ราคาเบิกชดเชย สปสช.',
                    'ised' => 'varchar(255) สถานะบัญชียา: E = ในบัญชียาหลัก (ED) | N = นอกบัญชียาหลัก (NED)',
                    'ndc24' => 'varchar(255) รหัสยา 24 หลักมาตรฐาน สปสช.',
                    'tmtid' => 'varchar(255) รหัส TMT มาตรฐาน',
                    'dateeffective' => 'date วันที่มีผลบังคับใช้',
                    'ised_approved' => 'varchar(255) สถานะการอนุมัติบัญชียา',
                ]
            ],
            'drugcat_chi' => [
                'description' => 'ตารางแคตตาล็อกยา กรมบัญชีกลาง CSMBS (CHI Drug Catalog Master ใน RiMS): รายการยาและเพดานราคาเบิกจ่ายตรงสิทธิข้าราชการ (CSOP/CIPN)',
                'columns' => [
                    'hospdrugcode' => 'varchar(255) รหัสยาของโรงพยาบาล (เชื่อมกับ drugitems.icode)',
                    'tmtid' => 'varchar(255) รหัส TMT มาตรฐาน',
                    'unitprice' => 'double ราคาเพดานเบิกจ่ายตรงสิทธิข้าราชการ',
                ]
            ],
            'lookup_sss_equipdev_aipn' => [
                'description' => 'ตารางรหัสมาตรฐานอุปกรณ์และอวัยวะเทียม ประกันสังคม กองทุน AIPN (SSS AIPN Equipment & Devices Master ใน RiMS)',
                'columns' => [
                    'code' => 'varchar(50) รหัสอุปกรณ์/อวัยวะเทียมมาตรฐาน ประกันสังคม',
                    'name' => 'varchar(255) ชื่อรายการอุปกรณ์/อวัยวะเทียม',
                    'price' => 'double ราคาเพดานเบิกชดเชย AIPN',
                ]
            ],
            'icd101' => [
                'description' => 'ตารางรหัสโรคมาตรฐานสากล ICD-10 ใน HOSxP (ICD-10 Diagnosis Master): รหัสโรค, ชื่อภาษาอังกฤษ, ชื่อภาษาไทย, สถานะเปิด/ปิดใช้งาน (active_status)',
                'columns' => [
                    'code' => 'varchar(7) รหัสโรค ICD-10 (Primary Key เช่น A00, I10, E119, K297, Z000)',
                    'name' => 'varchar(200) ชื่อโรคภาษาอังกฤษ',
                    'tname' => 'varchar(150) ชื่อโรคภาษาไทย',
                    'active_status' => 'char(1) สถานะการใช้งาน: Y = เปิดใช้งานปกติ | N หรือว่าง = ปิดการใช้งาน (ห้ามแพทย์สั่งใช้หรือส่งออก)',
                    'ipd_valid' => 'char(1) อนุญาตให้ใช้เป็นรหัสวินิจฉัยผู้ป่วยใน IPD หรือไม่ (Y/N)',
                    'icd10compat' => 'char(1) ความสอดคล้องกับมาตรฐาน ICD-10 สากล',
                    'agemin' => 'varchar(3) เกณฑ์อายุต่ำสุดของผู้ป่วยที่ใช้วินิจฉัยนี้ได้',
                    'agemax' => 'varchar(3) เกณฑ์อายุสูงสุดของผู้ป่วยที่ใช้วินิจฉัยนี้ได้',
                    'sex' => 'int(11) เพศที่อนุญาตให้ใช้วินิจฉัยนี้ (1=ชาย, 2=หญิง, 0=ไม่จำกัด)',
                ]
            ],
            'icd9cm1' => [
                'description' => 'ตารางรหัสหัตถการมาตรฐาน ICD-9-CM ใน HOSxP (ICD-9 Procedure Master): รหัสหัตถการ, ชื่อหัตถการ, สถานะเปิด/ปิดใช้งาน',
                'columns' => [
                    'code' => 'varchar(9) รหัสหัตถการ ICD-9-CM (Primary Key เช่น 8907, 9904, 4523, 3893)',
                    'name' => 'varchar(200) ชื่อหัตถการทางการแพทย์',
                    'active_status' => 'varchar(1) สถานะการใช้งาน: Y = เปิดใช้งานปกติ | N หรือว่าง = ปิดการใช้งาน',
                    'export_proced' => 'char(1) สถานะการส่งออกหัตถการ (Y/N)',
                ]
            ],
            'lookup_icd10_chi' => [
                'description' => 'ตารางตรวจสอบรหัสโรคสิทธิสวัสดิการข้าราชการ/กรมบัญชีกลาง สกส. (CSMBS ICD-10 Rules ใน RiMS): กำหนดเกณฑ์การยอมรับเป็นโรคหลัก (accpdx)',
                'columns' => [
                    'code' => 'varchar(10) รหัสโรค ICD-10 (Primary Key เชื่อมกับ icd101.code)',
                    'accpdx' => 'varchar(5) สกส. รับเป็นโรคหลัก (PDX) หรือไม่: Y = ยอมรับเป็นโรคหลักได้ | N = สกส. ไม่รับเป็นโรคหลักเด็ดขาด (ห้ามลงเป็นโรคหลัก ไม่งั้นจะถูกปฏิเสธเบิกเคลม/ติด C-Code)',
                    'code_cat' => 'varchar(5) หมวดรหัสโรค',
                    'desc' => 'varchar(255) คำอธิบายภาษาอังกฤษของ สกส.',
                ]
            ],
            'lookup_icd10' => [
                'description' => 'ตารางตรวจสอบรหัสโรคสิทธิบัตรทอง สปสช. (NHSO ICD-10 Rules ใน RiMS): รหัสบริการส่งเสริมป้องกันโรค (PP), รหัส ODS, โรคไต, HIV, TB',
                'columns' => [
                    'icd10' => 'varchar(100) รหัสโรค ICD-10 (Primary Key เชื่อมกับ icd101.code)',
                    'pp' => 'varchar(1) รหัสบริการสร้างเสริมสุขภาพและป้องกันโรค (PP): Y = บริการ PP สปสช.',
                    'ods' => 'varchar(1) รหัสการผ่าตัดวันเดียว One Day Surgery (ODS): Y = เข้าเกณฑ์ ODS สปสช.',
                    'kidney' => 'varchar(1) รหัสกลุ่มโรคไต (Y/N)',
                    'hiv' => 'varchar(1) รหัสกลุ่มโรค HIV (Y/N)',
                    'tb' => 'varchar(1) รหัสกลุ่มวัณโรค TB (Y/N)',
                ]
            ],
            'lookup_icd9_chi' => [
                'description' => 'ตารางรหัสหัตถการมาตรฐาน สกส./ประกันสังคม (CHI ICD-9 Rules ใน RiMS)',
                'columns' => [
                    'code' => 'varchar(255) รหัสหัตถการ ICD-9 มาตรฐาน สกส. (Primary Key เชื่อมกับ icd9cm1.code)',
                    'desc' => 'varchar(255) คำอธิบายหัตถการ',
                ]
            ]
        ];
    }

    /**
     * Select relevant HOSxP tables among master and lookup tables
     */
    protected function selectRelevantHosxpTables(string $query, array $tables): array
    {
        $q = mb_strtolower($query, 'UTF-8');
        $selected = [];

        $isNondrug = preg_match('/(ค่าบริการ|หัตถการ|nondrug|adp|หมวด|income|ราคา|icode|billcode|ค่ารักษา)/iu', $q);
        $isPttype = preg_match('/(สิทธิ|pttype|บัตรทอง|ประกันสังคม|ข้าราชการ|hipdata|16\s*แฟ้ม|เบิกได้|จ่ายเอง|pcode|สิทธิการรักษา|subinscl|provis)/iu', $q);
        $isDoctor = preg_match('/(หมอ|แพทย์|doctor|ผู้ตรวจ|licenseno|ใบประกอบ|สภาวิชาชีพ|council|ตำแหน่ง|เชี่ยวชาญ|spclty|คลินิก|clinic)/iu', $q);
        $isPriceByRight = preg_match('/(pttype_items_price|ราคาแยกตามสิทธิ|ราคาตามสิทธิ|แยกสิทธ|แยกสิทธิ์|หลายสิทธิ|หลายสิทธิ์|หลายราคา|ราคาต่างกัน|ส่วนลด|ราคาพิเศษ|กลุ่มราคา)/iu', $q);
        $isDrug = preg_match('/(ยา|drug|did|tmt|icode|24\s*หลัก|ed\b|ned\b|drugcat|ค่ายา|drugusage|วิธีใช้|9418424|\b\d{6,7}\b)/iu', $q);
        $isLab = preg_match('/(lab|แลป|แล็บ|tmlt|loinc|ชุดตรวจ|โปรไฟล์|profile|item|สิ่งส่งตรวจ|specimen|labcat)/iu', $q);
        $isIcd = preg_match('/(icd|icd10|icd-10|icd9|icd-9|โรค|หัตถการ|วินิจฉัย|diag|pdx|sdx|โรคหลัก|โรคแทรก|accpdx|ปิดรหัส|ปิดการใช้งาน|\b[a-z]\d{2,3}\b)/iu', $q);
        $isFundAudit = preg_match('/(16\s*แฟ้ม|fdh|aipn|ssop|csop|cipn|กองทุน|ส่งออก|เคลม|claim|audit|ตรวจสอบ)/iu', $q);

        if ($isDoctor) {
            $selected['doctor'] = $tables['doctor'];
            $selected['doctor_position'] = $tables['doctor_position'];
            $selected['spclty'] = $tables['spclty'];
            if (isset($tables['clinic'])) $selected['clinic'] = $tables['clinic'];
        }

        if ($isNondrug) {
            $selected['nondrugitems'] = $tables['nondrugitems'];
            $selected['income'] = $tables['income'];
            $selected['paidst'] = $tables['paidst'];
        }

        if ($isPttype) {
            $selected['pttype'] = $tables['pttype'];
            $selected['pcode'] = $tables['pcode'];
            $selected['paidst'] = $tables['paidst'];
            if (isset($tables['provis_instype'])) $selected['provis_instype'] = $tables['provis_instype'];
            if (isset($tables['pttype_price_group'])) $selected['pttype_price_group'] = $tables['pttype_price_group'];
            if (isset($tables['pttype_nhso_subinscl'])) $selected['pttype_nhso_subinscl'] = $tables['pttype_nhso_subinscl'];
        }

        if ($isPriceByRight) {
            $selected['pttype_items_price'] = $tables['pttype_items_price'];
            $selected['pttype'] = $tables['pttype'];
            $selected['nondrugitems'] = $tables['nondrugitems'];
        }

        if ($isDrug) {
            if (isset($tables['drugitems'])) $selected['drugitems'] = $tables['drugitems'];
            if (isset($tables['drugitems_ref_code'])) $selected['drugitems_ref_code'] = $tables['drugitems_ref_code'];
            if (isset($tables['drugusage'])) $selected['drugusage'] = $tables['drugusage'];
            if (isset($tables['income'])) $selected['income'] = $tables['income'];
            if (isset($tables['drugcat_nhso'])) $selected['drugcat_nhso'] = $tables['drugcat_nhso'];
            if (isset($tables['drugcat_chi'])) $selected['drugcat_chi'] = $tables['drugcat_chi'];
        }

        if ($isLab) {
            if (isset($tables['lab_items'])) $selected['lab_items'] = $tables['lab_items'];
            if (isset($tables['lab_items_sub_group'])) $selected['lab_items_sub_group'] = $tables['lab_items_sub_group'];
            if (isset($tables['nondrugitems'])) $selected['nondrugitems'] = $tables['nondrugitems'];
        }

        if ($isIcd) {
            if (isset($tables['icd101'])) $selected['icd101'] = $tables['icd101'];
            if (isset($tables['icd9cm1'])) $selected['icd9cm1'] = $tables['icd9cm1'];
            if (isset($tables['lookup_icd10_chi'])) $selected['lookup_icd10_chi'] = $tables['lookup_icd10_chi'];
            if (isset($tables['lookup_icd10'])) $selected['lookup_icd10'] = $tables['lookup_icd10'];
            if (isset($tables['lookup_icd9_chi'])) $selected['lookup_icd9_chi'] = $tables['lookup_icd9_chi'];
        }

        if ($isFundAudit) {
            if (isset($tables['drugitems'])) $selected['drugitems'] = $tables['drugitems'];
            if (isset($tables['nondrugitems'])) $selected['nondrugitems'] = $tables['nondrugitems'];
            if (isset($tables['pttype'])) $selected['pttype'] = $tables['pttype'];
            if (isset($tables['doctor'])) $selected['doctor'] = $tables['doctor'];
            if (isset($tables['lookup_sss_equipdev_aipn'])) $selected['lookup_sss_equipdev_aipn'] = $tables['lookup_sss_equipdev_aipn'];
            if (isset($tables['drugcat_chi'])) $selected['drugcat_chi'] = $tables['drugcat_chi'];
            if (isset($tables['drugcat_nhso'])) $selected['drugcat_nhso'] = $tables['drugcat_nhso'];
            if (isset($tables['icd101'])) $selected['icd101'] = $tables['icd101'];
            if (isset($tables['lookup_icd10_chi'])) $selected['lookup_icd10_chi'] = $tables['lookup_icd10_chi'];
        }

        // If general or no specific match, include the core masters + lookups
        if (empty($selected)) {
            return $tables;
        }

        return $selected;
    }
}
