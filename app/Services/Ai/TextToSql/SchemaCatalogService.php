<?php

namespace App\Services\Ai\TextToSql;

class SchemaCatalogService
{
    /**
     * Cache for extracted_schemas.json
     */
    protected static ?array $extractedSchemas = null;

    /**
     * Get Schema string for HRiMS Database (Strictly all 12 hosfin_* tables)
     */
    public function getHrimsSchema(string $userQuery): string
    {
        $tables = $this->getCuratedHosfinTables();

        $out = "=== ฐานข้อมูล HRiMS (ระบบการเงิน HosFin - ตาราง hosfin_* ทั้งหมด 12 ตาราง) ===\n";
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
     * Curated dictionary of HOSxP Master Data tables (Strictly: nondrugitems, pttype, doctor)
     */
    protected function getCuratedHosxpTables(): array
    {
        return [
            'nondrugitems' => [
                'description' => 'ตารางตั้งค่ารายการค่าบริการและหัตถการที่ไม่ใช่ยา (Services & Non-drug Master)',
                'columns' => [
                    'icode' => 'varchar(7) รหัสรายการค่ารักษาพยาบาล (Primary Key)',
                    'name' => 'varchar(100) ชื่อรายการค่าบริการ/หัตถการ',
                    'price' => 'double ราคาค่าบริการปกติ',
                    'price2' => 'double ราคาค่าบริการราคา 2',
                    'price3' => 'double ราคาค่าบริการราคา 3',
                    'income' => 'varchar(2) หมวดค่ารักษาพยาบาล (เชื่อมตาราง income)',
                    'nhso_adp_code' => 'varchar(20) รหัสมาตรฐานค่าบริการ ADP สปสช. (ใช้ตรวจสอบว่าผูกรหัสถูกต้องหรือไม่)',
                    'billcode' => 'varchar(20) รหัสเบิกจ่ายตามระเบียบกรมบัญชีกลาง',
                    'unit' => 'varchar(20) หน่วยนับค่าบริการ',
                    'istat' => 'varchar(1) สถานะการใช้งาน (Y=ใช้งานปกติ, N=ยกเลิก)',
                ]
            ],
            'pttype' => [
                'description' => 'ตารางตั้งค่าสิทธิการรักษาพยาบาล (Health Insurance Rights Master)',
                'columns' => [
                    'pttype' => 'varchar(2) รหัสสิทธิการรักษาพยาบาล (Primary Key เช่น 10, 20, 30, UCS, OFC, SSS)',
                    'name' => 'varchar(100) ชื่อสิทธิการรักษาพยาบาล',
                    'pcode' => 'varchar(2) กลุ่มสิทธิมาตรฐานตามโครงสร้าง สปสช./กระทรวง',
                    'hipdata_code' => 'varchar(20) รหัสส่งออก 16 แฟ้ม (ใช้ตรวจสอบว่าผูกรหัสส่งออกถูกต้องตรงตามมาตรฐานหรือไม่)',
                    'max_debt_money' => 'double เพดานหนี้สูงสุดที่อนุญาต',
                    'paidst' => 'varchar(2) สถานะการชำระเงิน (01=ไม่ต้องจ่าย/สิทธิยกเว้น, 02=ชำระเงินสด, 03=ลูกหนี้เบิกได้)',
                    'isuse' => 'varchar(1) สถานะเปิดใช้งาน (Y/N)',
                ]
            ],
            'doctor' => [
                'description' => 'ตารางตั้งค่ารายชื่อแพทย์และบุคลากรทางการแพทย์ (Doctor & Staff Master)',
                'columns' => [
                    'code' => 'varchar(6) รหัสแพทย์ในระบบ HOSxP (Primary Key)',
                    'name' => 'varchar(100) ชื่อ-นามสกุลแพทย์หรือผู้ตรวจรักษา',
                    'licenseno' => 'varchar(20) เลขที่ใบอนุญาตประกอบวิชาชีพเวชกรรม (ว. หรือ ท. หรือ พ.)',
                    'council_code' => 'varchar(20) รหัสสภาวิชาชีพ (เช่น 01 แพทยสภา, 02 ทันตแพทยสภา, 03 สภาการพยาบาล)',
                    'position_id' => 'int รหัสตำแหน่งสายงาน',
                    'cid' => 'varchar(13) เลขประจำตัวประชาชน 13 หลัก',
                    'active' => 'varchar(1) สถานะการปฏิบัติงาน (Y=ปฏิบัติงานอยู่, N=ลาออก/ย้าย)',
                ]
            ]
        ];
    }

    /**
     * Select relevant HOSxP tables among the 3 master tables
     */
    protected function selectRelevantHosxpTables(string $query, array $tables): array
    {
        $q = mb_strtolower($query, 'UTF-8');
        $selected = [];

        $isNondrug = preg_match('/(ค่าบริการ|หัตถการ|nondrug|adp|หมวด|income|ราคา|icode|billcode|ค่ารักษา)/iu', $q);
        $isPttype = preg_match('/(สิทธิ|pttype|บัตรทอง|ประกันสังคม|ข้าราชการ|hipdata|16\s*แฟ้ม|เบิกได้|จ่ายเอง|pcode)/iu', $q);
        $isDoctor = preg_match('/(หมอ|แพทย์|doctor|ผู้ตรวจ|licenseno|ใบประกอบ|สภาวิชาชีพ|council)/iu', $q);

        if ($isNondrug) {
            $selected['nondrugitems'] = $tables['nondrugitems'];
        }

        if ($isPttype) {
            $selected['pttype'] = $tables['pttype'];
        }

        if ($isDoctor) {
            $selected['doctor'] = $tables['doctor'];
        }

        // If no specific keyword, provide all 3 for general config checks
        if (empty($selected)) {
            return $tables;
        }

        return $selected;
    }
}
