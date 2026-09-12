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

        $out = "=== ฐานข้อมูล RiMS (ระบบการเงิน HosFin - ตาราง hosfin_* ทั้งหมด 12 ตาราง) ===\n";
        $out .= "ชนิดฐานข้อมูล: MySQL / MariaDB (Connection: mysql)\n";
        $out .= "กฎเหล็ก: อนุญาตให้เขียนคำสั่ง SELECT เฉพาะตารางที่ขึ้นต้นด้วย 'hosfin_' เท่านั้น ห้ามใช้ตารางอื่นนอกเหนือจาก hosfin_*\n";
        $out .= "ข้อแนะนำสำคัญในการเขียน SQL ด้านการเงินการคลัง:\n";
        $out .= "- ปีงบประมาณ (fiscal_year / acc_year) จัดเก็บเป็นปี พ.ศ. เช่น 2568, 2569\n";
        $out .= "- เจ้าหนี้ค้างจ่าย (AP): `hosfin_gl_ap_bills` ดูยอดหนี้คงเหลือที่ `remaining_debt > 0` หรือ `is_paid = 0` (0=ค้างจ่าย, 1=จ่ายแล้ว)\n";
        $out .= "- ลูกหนี้ค่ารักษาค้างชำระ (AR): `hosfin_gl_ar_debtors` ดูยอดหนี้คงค้างที่ `outstanding_balance > 0` แยกตามประเภท `debtor_type` หรือสิทธิ\n";
        $out .= "- งบทดลอง: `hosfin_trial_balance` ยอดเดบิต/เครดิตยกมา (`debit_bf`, `credit_bf`), ประจำงวด (`debit_month`, `credit_month`), สุทธิยกไป (`debit_net`, `credit_net`)\n";
        $out .= "- กระแสเงินสดและสรุปการเงินรายวัน: `hosfin_gl_daily_summaries` มีรายรับ (`total_income`), รายจ่าย (`total_expense`), สุทธิ (`net_cash_flow`), เงินสดคงเหลือสะสม (`cash_balance`)\n";
        $out .= "- ต้นทุนโรงพยาบาล: `hosfin_gl_cost_summaries` มีค่าแรง LC (`lc_amount`), ค่าของ MC (`mc_amount`), ค่าลงทุน CC (`cc_amount`), ต้นทุนรวม (`total_cost`)\n";
        $out .= "- สมุดรายวันและรายการบัญชี: `hosfin_gl_journals` เชื่อมกับ `hosfin_gl_journal_items` ด้วย `voucher_no`\n\n";

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
        $out .= "=== โครงสร้างหลักและตารางตั้งต้น (Core Anchor Architecture) ===\n";
        $out .= "ระบบข้อมูลพื้นฐานในหน้านี้จะตั้งต้นด้วย 3 ตารางหลักเสมอ แล้วค่อยเชื่อมไปยังตาราง lookup อื่นตามคอลัมน์:\n\n";
        $out .= "1. [บุคลากรทางการแพทย์]: ตั้งต้นด้วยตาราง `doctor` เสมอ (เช่น `FROM doctor d`)\n";
        $out .= "   - เชื่อมตำแหน่ง: `LEFT JOIN doctor_position dp ON dp.id = d.position_id`\n";
        $out .= "   - เชื่อมสาขาความเชี่ยวชาญ: `LEFT JOIN spclty s ON s.spclty = d.spclty`\n";
        $out .= "   - เชื่อมคลินิก: `LEFT JOIN clinic c ON c.clinic = d.clinic`\n";
        $out .= "   - ฟิลด์สำคัญ: `code` (รหัสแพทย์), `name` (ชื่อแพทย์), `licenseno` (เลขใบประกอบฯ), `cid` (เลขบัตรประชาชน 13 หลัก), `council_code` (สภาวิชาชีพ), `active` (สถานะปฏิบัติงาน 'Y'/'N')\n\n";
        $out .= "2. [ค่ารักษาพยาบาล]: ตั้งต้นด้วยตาราง `nondrugitems` เสมอ (เช่น `FROM nondrugitems n`)\n";
        $out .= "   - เชื่อมหมวดค่ารักษา: `LEFT JOIN income i ON i.income = n.income`\n";
        $out .= "   - เชื่อมสถานะการชำระ: `LEFT JOIN paidst p ON p.paidst = n.paidst`\n";
        $out .= "   - เชื่อมราคาตามสิทธิ (HOSxP v4): `LEFT JOIN pttype_items_price pip ON pip.items_table_code = n.icode` (เชื่อมผ่าน items_table_code = icode โดยตรง ห้ามกรอง WHERE items_table_name)\n";
        $out .= "   - ฟิลด์สำคัญ: `icode` (รหัสค่าบริการ), `name` (ชื่อรายการ), `price` (ราคามาตรฐาน 1), `price2`, `price3`, `nhso_adp_code` (รหัส ADP สปสช.), `billcode` (รหัสเบิกกรมบัญชีกลาง), `istatus` (สถานะ 'Y'/'N')\n\n";
        $out .= "3. [สิทธิการรักษา]: ตั้งต้นด้วยตาราง `pttype` เสมอ (เช่น `FROM pttype p`)\n";
        $out .= "   - เชื่อมสถานะชำระเงิน: `LEFT JOIN paidst p1 ON p1.paidst = p.paidst`\n";
        $out .= "   - เชื่อมกลุ่มสิทธิมาตรฐานประเทศ: `LEFT JOIN pcode pc ON pc.code = p.pcode`\n";
        $out .= "   - เชื่อมสิทธิมาตรฐาน PROVIS/สปสช.: `LEFT JOIN provis_instype pi ON pi.code = p.nhso_code`\n";
        $out .= "   - เชื่อมกลุ่มราคาตามสิทธิ: `LEFT JOIN pttype_price_group pg ON pg.pttype_price_group_id = p.pttype_price_group_id`\n";
        $out .= "   - เชื่อมสิทธิย่อย สปสช.: `LEFT JOIN pttype_nhso_subinscl inscl ON inscl.pttype = p.pttype`\n";
        $out .= "   - เชื่อมรายการราคาตามสิทธินี้: `LEFT JOIN pttype_items_price pip ON pip.pttype = p.pttype`\n";
        $out .= "   - ฟิลด์สำคัญ: `pttype` (รหัสสิทธิ), `name` (ชื่อสิทธิ), `hipdata_code` (รหัสส่งออก 16 แฟ้ม), `pttype_std_code` (รหัสส่งออก HOSxP), `isuse` (สถานะ 'Y'/'N')\n\n";
        $out .= "=== ความสำคัญของการดูตารางที่เชื่อมโยง (Lookup Tables) ===\n";
        $out .= "- การตั้งค่าจะถูกต้องได้ จำเป็นต้องดูตารางที่เชื่อมโยงควบคู่กันเสมอ เช่น:\n";
        $out .= "  * ดู `doctor_position`: เพื่อแยกว่าบุคลากรเป็นแพทย์จริง หรือเป็นเจ้าหน้าที่สายสนับสนุน (เช่น พนักงานบริการ, เวชสถิติ) ซึ่งถ้าเป็นสายสนับสนุนเลขใบประกอบฯ มักขึ้นต้นด้วยขีด '-' ตามด้วยเลขบัตร\n";
        $out .= "  * ดู `spclty` และ `clinic`: เพื่อดูว่าสังกัดคลินิกและสาขาความเชี่ยวชาญใด เพื่อให้ส่งออกแฟ้ม PROVIDER (43 แฟ้ม) ได้สมบูรณ์\n";
        $out .= "  * ดู `income`: เพื่อตรวจว่าค่าบริการ nondrugitems ผูกหมวดรายได้ 16 หมวดตรงตามประเภทหรือไม่\n";
        $out .= "  * ดู `provis_instype`: เพื่อตรวจว่าสิทธิ pttype ผูกรหัสมาตรฐาน PROVIS และรหัสส่งออก 16 แฟ้ม (UCS=0100) ตรงกันหรือไม่\n\n";
        $out .= "*** ตัวอย่างคำสั่ง SELECT ที่ถูกต้องและปลอดภัย ***:\n";
        $out .= "- แพทย์ไม่มีเลขใบประกอบฯ: `SELECT d.code, d.name, d.licenseno, d.council_code, d.cid, dp.name AS position_name, s.name AS spclty_name, c.name AS clinic_name, d.active FROM doctor d LEFT JOIN doctor_position dp ON dp.id = d.position_id LEFT JOIN spclty s ON s.spclty = d.spclty LEFT JOIN clinic c ON c.clinic = d.clinic WHERE (d.licenseno IS NULL OR d.licenseno = '' OR d.licenseno LIKE '-%') AND d.active = 'Y'`\n";
        $out .= "- ค่าบริการที่ยังไม่ผูกรหัส ADP: `SELECT n.icode, n.name, n.price, i.name AS income_name, n.nhso_adp_code FROM nondrugitems n LEFT JOIN income i ON i.income = n.income WHERE (n.nhso_adp_code IS NULL OR n.nhso_adp_code = '') AND n.istatus = 'Y' AND n.price > 0`\n";
        $out .= "- สิทธิที่รหัสส่งออกไม่ตรงกับ PROVIS: `SELECT p.pttype, p.name, p.hipdata_code, p.pttype_std_code, pi.code AS provis_code, pi.name AS provis_name, pi.pttype_std_code AS provis_std_code FROM pttype p LEFT JOIN provis_instype pi ON pi.code = p.nhso_code WHERE p.isuse = 'Y' AND (p.pttype_std_code != pi.pttype_std_code OR p.pttype_std_code IS NULL)`\n\n";
        $out .= "*** กฎเหล็กและขอบเขตข้อมูล ***:\n";
        $out .= "- ให้มุ่งเน้นดึงข้อมูลของ 3 กลุ่มตารางตั้งต้นนี้ให้ถูกต้องตรงเป๊ะก่อน (doctor, nondrugitems, pttype) ร่วมกับตาราง lookup ข้างต้น\n";
        $out .= "- ในหน้านี้ยังไม่ต้องเชื่อมหรืออ้างอิงตารางยา (drugitems) หรือแล็บ (lab_items) จนกว่าจะมีการเพิ่มข้อมูลพื้นฐานดังกล่าวในระบบต่อไป\n";
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

        // If general or no specific match, include the 3 core masters + pttype_items_price + lookups
        if (empty($selected)) {
            return $tables;
        }

        return $selected;
    }
}
