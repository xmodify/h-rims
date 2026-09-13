<?php

namespace App\Services\Ai\Knowledge;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\HosFinController;

/**
 * Hospital Financial & Fiscal Management Knowledge Engine (MOPH Standards)
 * คลังองค์ความรู้ผู้เชี่ยวชาญการบริหารการเงินการคลังโรงพยาบาล สังกัดกระทรวงสาธารณสุข
 * ออกแบบให้ใช้งานได้กับทุกโรงพยาบาล ทุกปีงบประมาณ และครอบคลุมการติดตามในแต่ละเดือนครบทุกมิติ
 */
class HospitalFinancialKnowledgeService
{
    /**
     * Get hospital name dynamically from database settings (ใช้ได้กับทุก รพ.)
     */
    public function getHospitalName(): string
    {
        try {
            $name = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
            if (!empty($name)) {
                return trim($name);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
                $opdName = DB::table('opdconfig')->value('hospitalname');
                if (!empty($opdName)) {
                    return trim($opdName);
                }
            }
            return 'โรงพยาบาล';
        } catch (\Throwable $e) {
            return 'โรงพยาบาล';
        }
    }

    /**
     * Get hospital code dynamically from database settings (ใช้ได้กับทุก รพ.)
     */
    public function getHospitalCode(): string
    {
        try {
            $code = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
            if (!empty($code)) {
                return trim($code);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
                $opdCode = DB::table('opdconfig')->value('hospitalcode');
                if (!empty($opdCode)) {
                    return trim($opdCode);
                }
            }
            return '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get dynamic budget years info (ใช้ได้กับทุกปีงบประมาณ ทั้งอดีต ปัจจุบัน และอนาคต)
     */
    public function getBudgetYearsInfo(?int $requestedYear = null): array
    {
        try {
            $planYears = DB::table('hosfin_planfin_targets')->distinct()->pluck('budget_year')->filter()->map(fn($y) => intval($y))->toArray();
            $tbYears = DB::table('hosfin_trial_balance')->distinct()->pluck('acc_year')->filter()->map(fn($y) => intval($y))->toArray();
            $glApYears = \Illuminate\Support\Facades\Schema::hasTable('hosfin_gl_ap_bills') ? DB::table('hosfin_gl_ap_bills')->distinct()->pluck('fiscal_year')->filter()->map(fn($y) => intval($y))->toArray() : [];
            $glCostYears = \Illuminate\Support\Facades\Schema::hasTable('hosfin_gl_cost_summaries') ? DB::table('hosfin_gl_cost_summaries')->distinct()->pluck('fiscal_year')->filter()->map(fn($y) => intval($y))->toArray() : [];

            $allRecordedYears = array_values(array_unique(array_merge($planYears, $tbYears, $glApYears, $glCostYears)));
            rsort($allRecordedYears);

            $maxRecordedYear = !empty($allRecordedYears) ? reset($allRecordedYears) : (intval(date('Y')) + 543);
            $currentBudgetYear = HosFinController::getCurrentBudgetYear() ?: $maxRecordedYear;

            // Target planning year resolution
            $targetYear = $requestedYear ?: ($currentBudgetYear >= $maxRecordedYear ? $currentBudgetYear + 1 : $currentBudgetYear);
            $isFutureYear = ($targetYear > $maxRecordedYear);
            $hasSavedPlan = in_array($targetYear, $planYears);
            $baselineYear = $isFutureYear ? $maxRecordedYear : (($targetYear <= min($allRecordedYears ?: [2569])) ? $targetYear : $targetYear - 1);

            return [
                'hospital_name' => $this->getHospitalName(),
                'hospital_code' => $this->getHospitalCode(),
                'available_years' => $allRecordedYears,
                'plan_years' => $planYears,
                'max_recorded_year' => $maxRecordedYear,
                'current_budget_year' => $currentBudgetYear,
                'target_planning_year' => $targetYear,
                'baseline_year' => $baselineYear,
                'short_target_year' => substr((string)$targetYear, -2),
                'short_baseline_year' => substr((string)$baselineYear, -2),
                'is_future_year' => $isFutureYear,
                'has_saved_plan' => $hasSavedPlan,
            ];
        } catch (\Throwable $e) {
            return [
                'hospital_name' => 'โรงพยาบาล',
                'hospital_code' => '',
                'available_years' => [2569],
                'plan_years' => [2569],
                'max_recorded_year' => 2569,
                'current_budget_year' => 2569,
                'target_planning_year' => 2570,
                'baseline_year' => 2569,
                'short_target_year' => '70',
                'short_baseline_year' => '69',
                'is_future_year' => true,
                'has_saved_plan' => false,
            ];
        }
    }

    /**
     * Parse Thai month and fiscal period from user query (ในแต่ละเดือน)
     */
    public function parseMonthAndPeriod(string $query, ?int $budgetYear = null): ?array
    {
        $q = mb_strtolower($query, 'UTF-8');
        $yInfo = $this->getBudgetYearsInfo($budgetYear);
        $by = $budgetYear ?: $yInfo['max_recorded_year'];

        // Thai Month mapping to calendar month (1..12)
        $monthMap = [
            'ตุลาคม' => 10, 'ต.ค.' => 10, 'october' => 10, 'oct' => 10,
            'พฤศจิกายน' => 11, 'พ.ย.' => 11, 'november' => 11, 'nov' => 11,
            'ธันวาคม' => 12, 'ธ.ค.' => 12, 'december' => 12, 'dec' => 12,
            'มกราคม' => 1, 'ม.ค.' => 1, 'january' => 1, 'jan' => 1,
            'กุมภาพันธ์' => 2, 'ก.พ.' => 2, 'february' => 2, 'feb' => 2,
            'มีนาคม' => 3, 'มี.ค.' => 3, 'march' => 3, 'mar' => 3,
            'เมษายน' => 4, 'เม.ย.' => 4, 'april' => 4, 'apr' => 4,
            'พฤษภาคม' => 5, 'พ.ค.' => 5, 'may' => 5,
            'มิถุนายน' => 6, 'มิ.ย.' => 6, 'june' => 6, 'jun' => 6,
            'กรกฎาคม' => 7, 'ก.ค.' => 7, 'july' => 7, 'jul' => 7,
            'สิงหาคม' => 8, 'ส.ค.' => 8, 'august' => 8, 'aug' => 8,
            'กันยายน' => 9, 'ก.ย.' => 9, 'september' => 9, 'sep' => 9,
        ];

        $calMonth = null;
        $monthNameTh = null;

        // Check text month names
        foreach ($monthMap as $name => $m) {
            if (mb_strpos($q, $name) !== false) {
                $calMonth = $m;
                $monthNameTh = mb_substr($name, 0, 1) === 'ต' || mb_substr($name, 0, 1) === 'พ' || mb_substr($name, 0, 1) === 'ธ' || mb_substr($name, 0, 1) === 'ม' || mb_substr($name, 0, 1) === 'ก' || mb_substr($name, 0, 1) === 'เ' || mb_substr($name, 0, 1) === 'ส' ? $name : null;
                break;
            }
        }

        // Check explicit period pattern like 2569-07 or 2569/07 or งวด 7
        if (!$calMonth) {
            if (preg_match('/(?:25\d{2})[-_\/](\d{1,2})/', $q, $pm)) {
                $calMonth = intval($pm[1]);
            } elseif (preg_match('/(?:งวด|เดือนที่)\s*(\d{1,2})/u', $q, $pm)) {
                $rawNo = intval($pm[1]);
                if ($rawNo >= 1 && $rawNo <= 12) {
                    // If between 1-12, could be fiscal month or calendar month. In Thai accounting, งวด 1 = ต.ค. (10)
                    $fiscalMonthToCal = [1 => 10, 2 => 11, 3 => 12, 4 => 1, 5 => 2, 6 => 3, 7 => 4, 8 => 5, 9 => 6, 10 => 7, 11 => 8, 12 => 9];
                    $calMonth = $fiscalMonthToCal[$rawNo] ?? $rawNo;
                }
            }
        }

        if (!$calMonth || $calMonth < 1 || $calMonth > 12) {
            return null;
        }

        // Fiscal month index (1=Oct, 12=Sep)
        $fiscalMonth = ($calMonth >= 10) ? ($calMonth - 9) : ($calMonth + 3);
        $periodYear = ($calMonth >= 10) ? ($by - 1) : $by;
        $periodStr = sprintf('%d-%02d', $periodYear, $calMonth);

        $thMonthsFull = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];

        return [
            'budget_year' => $by,
            'period' => $periodStr,
            'period_year' => $periodYear,
            'calendar_month' => $calMonth,
            'fiscal_month' => $fiscalMonth,
            'month_name_th' => $thMonthsFull[$calMonth] ?? "เดือนที่ {$calMonth}",
            'cumulative_months' => $fiscalMonth,
        ];
    }

    /**
     * Get relevant expert knowledge based on user query keywords
     */
    public function getRelevantKnowledge(string $query): string
    {
        $q = mb_strtolower($query, 'UTF-8');
        $sections = [];

        // Resolve requested year if mentioned
        $requestedYear = null;
        if (preg_match('/\b(25\d{2})\b/', $q, $ym)) {
            $requestedYear = intval($ym[1]);
        } elseif (preg_match('/(?:ปี|งบ)\s*(\d{2})\b/u', $q, $ym)) {
            $requestedYear = intval('25' . $ym[1]);
        }

        $yearsInfo = $this->getBudgetYearsInfo($requestedYear);
        $monthInfo = $this->parseMonthAndPeriod($query, $requestedYear);

        // 1. PlanFin & Future Planning (แผนเงินบำรุง, แผนปี, EBITDA, วงเงินลงทุน 20%)
        if (preg_match('/(planfin|แผนเงินบำรุง|ทำแผน|แผนปี|แผนงบ|งบประมาณ|ebitda|วงเงินลงทุน|20%|หมวดแผน|simulator|เป้าหมายแผน|งบลงทุน)/iu', $q)) {
            $sections[] = $this->getPlanfinKnowledge($yearsInfo);
            if (preg_match('/(ทำแผน|เตรียม|ประชุม|simulator|จำลอง|15|ปีถัดไป)/iu', $q) || ($requestedYear && $requestedYear >= $yearsInfo['max_recorded_year'])) {
                $sections[] = $this->getBudgetPlanningRoadmap($yearsInfo);
            }
        }

        // 2. Month-by-Month Dimension Tracking (รายเดือน, แต่ละเดือน, งวด, เทียบแผนรายเดือน, matrix 12 เดือน)
        if (preg_match('/(เดือน|รายเดือน|แต่ละเดือน|งวด|matrix|สะสม|มกราคม|กุมภาพันธ์|มีนาคม|เมษายน|พฤษภาคม|มิถุนายน|กรกฎาคม|สิงหาคม|กันยายน|ตุลาคม|พฤศจิกายน|ธันวาคม)/iu', $q)) {
            $sections[] = $this->getMonthlyTrackingKnowledge($yearsInfo, $monthInfo);
        }

        // 3. Financial Distress Ratios & Risk Score (13 ดัชนีวิกฤต, สภาพคล่อง, Risk Score)
        if (preg_match('/(วิกฤต|สภาพคล่อง|risk|คะแนนความเสี่ยง|cr\b|qr\b|nwc|105|เงินบำรุงสุทธิ|ระยะเวลา|เจ้าหนี้ยาสูงสุด|เก็บหนี้|หมุนเวียน|ดัชนี|ratio)/iu', $q)) {
            $sections[] = $this->getFinancialDistressKnowledge($yearsInfo);
        }

        // 4. FM Costing & Unit Cost & TPS Score (ต้นทุน, LC, MC, CC, Unit Cost, TPS)
        if (preg_match('/(ต้นทุน|cost|lc\b|mc\b|cc\b|unit\s*cost|tps|ค่าแรง|ค่ายา|ค่าของ|เสื่อมราคา|ประสิทธิภาพ)/iu', $q)) {
            $sections[] = $this->getCostingAndTpsKnowledge($yearsInfo);
        }

        // 5. Working Capital, Regulations, Cash Flow, AR/AP Management (ระเบียบเงินบำรุง, ลูกหนี้, เจ้าหนี้, สำรอง 3 เดือน)
        if (preg_match('/(ระเบียบ|เงินบำรุง|สำรอง|กระแสเงินสด|cash\s*flow|ลูกหนี้|ar\b|เจ้าหนี้|ap\b|หนี้ค้าง|ตัดหนี้สูญ|priorit|1101)/iu', $q)) {
            $sections[] = $this->getWorkingCapitalKnowledge($yearsInfo);
        }

        // 6. Chart of Accounts 5 Categories, Account Drill-Down, Forecasting & Strategic Recommendations (หมวด 1-5, เจาะรายตัว, คาดการ, แนะนำ)
        if (preg_match('/(หมวด|ผังบัญชี|งบทดลอง|สินทรัพย์|หนี้สิน|ทุน|ส่วนของเจ้าของ|รายได้|ค่าใช้จ่าย|เจาะ|รายตัว|คาดการ|พยากรณ์|แนะนำ|รายหมวด|\d{4,10}\.\d+)/iu', $q)) {
            $sections[] = $this->getChartOfAccountsAndDrillDownKnowledge($yearsInfo);
        }

        // If generic financial question, return broad summary
        if (empty($sections)) {
            $sections[] = $this->getOverviewFramework($yearsInfo);
            $sections[] = $this->getMonthlyTrackingKnowledge($yearsInfo);
            $sections[] = $this->getChartOfAccountsAndDrillDownKnowledge($yearsInfo);
        }

        return implode("\n\n" . str_repeat('=', 60) . "\n\n", $sections);
    }

    /**
     * Overview Framework of Hospital Financial Management (สป.สธ.)
     */
    public function getOverviewFramework(array $yInfo): string
    {
        $hosp = $yInfo['hospital_name'];
        return <<<EOT
=== กรอบมาตรฐานการบริหารการเงินการคลังโรงพยาบาล สังกัดกระทรวงสาธารณสุข ({$hosp}) ===
1. วัตถุประสงค์หลัก: รักษาเสถียรภาพทางการเงิน ความมั่นคงของเงินบำรุง และควบคุมความเสี่ยงต่อภาวะวิกฤตทางการเงิน (Financial Distress) เพื่อให้ {$hosp} สามารถจัดบริการสาธารณสุขได้อย่างมีคุณภาพ ต่อเนื่อง และยั่งยืน
2. เสาหลักการเงินการคลัง 4 มิติ ครอบคลุมทุกปีงบประมาณและทุกงวดเดือน:
   - มิติที่ 1: การวางแผนยุทธศาสตร์งบประมาณ (PlanFin): ควบคุมรายได้-ค่าใช้จ่าย และกำกับวงเงินลงทุน (Capex $\le$ 20% EBITDA)
   - มิติที่ 2: การเฝ้าระวังและเตือนภัยวิกฤต (13 Financial Distress Ratios & 7 Risk Scores): วัดสภาพคล่อง สัดส่วนหนี้สิน และระยะเวลาหมุนเวียน
   - มิติที่ 3: การบริหารต้นทุนและความคุ้มค่า (FM Costing & TPS Score 15 คะแนน): บริหารสัดส่วน LC/MC/CC และคำนวณ Unit Cost ต่อบริการ
   - มิติที่ 4: การบริหารเงินทุนหมุนเวียน (Working Capital Management): เร่งรัดลูกหนี้สิทธิ์ (AR Tracking), บริหารเครดิตเจ้าหนี้การค้า (AP Aging), และสำรองสภาพคล่อง 3 เดือน
EOT;
    }

    /**
     * Complete PlanFin Knowledge Base (Universal across any budget year)
     */
    public function getPlanfinKnowledge(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        $baseY = $yInfo['baseline_year'];
        $targetY = $yInfo['target_planning_year'];

        return <<<EOT
=== องค์ความรู้มาตรฐาน: การจัดทำและบริหารแผนเงินบำรุงโรงพยาบาล (MOPH PlanFin Master Guide) ===
หน่วยบริการ: {$hosp} | ปีงบประมาณฐาน: {$baseY} | ปีงบประมาณเป้าหมาย: {$targetY}

1. ความหมายและวัตถุประสงค์ของ PlanFin:
   - PlanFin (Financial Plan) คือ "แผนเงินบำรุงโรงพยาบาล" เป็นเครื่องมือทางการเงินภาคบังคับตามระเบียบกระทรวงสาธารณสุข สำหรับควบคุมกรอบรายรับ-รายจ่าย วางแผนกระแสเงินสด และป้องกันภาวะเงินบำรุงติดลบ
   - สามารถใช้งานได้กับทุกโรงพยาบาล และทุกปีงบประมาณ
   - รอบการจัดทำแผน:
     * รอบที่ 1 (แผนต้นปี): จัดทำล่วงหน้าก่อนเริ่มปีงบประมาณใหม่ (ช่วงสิงหาคม - กันยายน)
     * รอบที่ 2 (ปรับปรุงแผนกลางปี): ทบทวนเป้าหมายตามผลการดำเนินงานจริงรอบ 6 เดือน (ช่วงมีนาคม - เมษายน)

2. โครงสร้างระบบแผนเงินบำรุง (7 แผนย่อยบูรณาการ):
   • แผนที่ 1: แผนประมาณการรายได้และค่าใช้จ่ายรวม (Plan 1 - Comprehensive Revenue & Expense Plan)
     - รายได้ (Revenue): P04 (UC), P05 (EMS), P06 (เบิกต้นสังกัด), P61 (อปท.), P07 (ตรงกรมบัญชีกลาง), P08 (ประกันสังคม), P09 (ต่างด้าว), P10 (บริการอื่น), P11 (งบส่วนบุคลากร), P12 (รายได้อื่น), P13 (งบลงทุน)
     - สรุปรายได้รวม: `P13S` (Total Revenue = ผลรวม P04 ถึง P13)
     - ค่าใช้จ่าย (Expense): P14 (ยา), P15 (เวชภัณฑ์มิใช่ยา/วัสดุการแพทย์), P151 (ทันตกรรม), P16 (วิทย์การแพทย์), P17 (เงินเดือน/ค่าจ้างประจำ), P18 (จ้างชั่วคราว/พกส./จ้างเหมา), P19 (ค่าตอบแทน), P20 (สิทธิประโยชน์บุคลากรอื่น), P21 (ค่าใช้สอย), P22 (สาธารณูปโภค), P23 (วัสดุใช้ไป), P24 (ค่าเสื่อมราคา), P241 (หนี้สูญ/สงสัยจะสูญ), P25 (ค่าใช้จ่ายอื่น)
     - สรุปค่าใช้จ่ายรวม: `P26S` (Total Expense = ผลรวม P14 ถึง P251)
     - ผลการดำเนินงานสุทธิ (Net Income): `P27S` = P13S - P26S (ต้องตั้งเป้าหมายไม่ให้ติดลบ)
   • แผนที่ 2: แผนจัดซื้อยา (Drug Purchasing Plan) -> ควบคุมต้นทุนยา P14 ให้สัมพันธ์กับคลังยาและอัตราใช้จริง
   • แผนที่ 3: แผนจัดซื้อเวชภัณฑ์มิใช่ยาและวัสดุการแพทย์ (Medical Supplies Plan) -> ควบคุม P15, P151, P16
   • แผนที่ 4: แผนชำระหนี้เจ้าหนี้การค้า (Accounts Payable Repayment Plan) -> วางแผนจ่ายหนี้ AP ให้สอดคล้องกับกระแสเงินสดรับ
   • แผนที่ 5: แผนติดตามและเร่งรัดเรียกเก็บลูกหนี้ค่ารักษาพยาบาล (AR Collection Plan) -> เร่งปิดสิทธิและส่งเคลมกองทุน
   • แผนที่ 6: แผนจัดซื้อครุภัณฑ์ ที่ดิน และสิ่งก่อสร้างด้วยเงินบำรุง (Capital Expenditure Plan - งบลงทุน) -> ผูกกับกรอบ 20% EBITDA
   • แผนที่ 7: แผนสนับสนุนเครือข่ายบริการปฐมภูมิ/รพ.สต. (Primary Care Network Support Plan)

3. สูตรการคำนวณสำคัญตามเกณฑ์กระทรวงสาธารณสุข:
   • EBITDA (P29): กำไรก่อนหักดอกเบี้ย ภาษี และค่าเสื่อมราคา (สะท้อนเงินสดจากการดำเนินงานจริง)
     สูตร: EBITDA = (รวมรายได้ P13S - งบลงทุน P13) - (รวมค่าใช้จ่าย P26S - ค่าเสื่อมราคา P24)
   • กฎเหล็กวงเงินลงทุนด้วยเงินบำรุง (Capital Expenditure Limit):
     สูตร: วงเงินลงทุนครุภัณฑ์สิ่งก่อสร้าง = ไม่เกิน 20% ของ EBITDA (P29 * 0.20)
     ข้อบังคับ: หาก EBITDA ติดลบ หรือเงินบำรุงสุทธิ (ดัชนี 105) ติดลบ ห้ามโรงพยาบาลตั้งงบลงทุนเพิ่มด้วยเงินบำรุง เว้นแต่ได้รับอนุมัติกรณีจำเป็นเร่งด่วนจากนายแพทย์สาธารณสุขจังหวัด (นพ.สสจ.) หรือผู้ว่าราชการจังหวัด
   • อัตรากำไรจากการดำเนินงาน (Operating Margin):
     สูตร: (EBITDA / รายได้จากการดำเนินงาน) * 100 -> เกณฑ์มาตรฐานควรเป็นบวก (> 0%) และเกรดดีมากควร > 7.29%
EOT;
    }

    /**
     * Month-by-Month Multi-Dimensional Tracking Knowledge (ครอบคลุมในแต่ละเดือนครบทุกมิติ)
     */
    public function getMonthlyTrackingKnowledge(array $yInfo, ?array $monthInfo = null): string
    {
        $hosp = $yInfo['hospital_name'];
        $baseY = $yInfo['baseline_year'];
        $targetY = $yInfo['target_planning_year'];

        $specificMonthSection = "";
        if (!empty($monthInfo)) {
            $mName = $monthInfo['month_name_th'];
            $pPeriod = $monthInfo['period'];
            $fMonth = $monthInfo['fiscal_month'];
            $bYear = $monthInfo['budget_year'];
            $cumM = $monthInfo['cumulative_months'];

            $specificMonthSection = <<<EOT

★ การวิเคราะห์เจาะจงสำหรับ: {$mName} (งวดบัญชี {$pPeriod} | เดือนที่ {$fMonth} ของปีงบประมาณ {$bYear})
- มิติที่ 1: ผลงานประจำเดือนเดี่ยว: เป้าหมาย = (เป้าหมายทั้งปี / 12) เทียบกับความเคลื่อนไหวจริงในงวด ({$pPeriod})
- มิติที่ 2: ผลงานสะสมถึงเดือนนี้: เป้าหมายสะสม {$cumM} เดือน = (เป้าหมายทั้งปี / 12) * {$cumM} เทียบกับยอดยกไปสุทธิ
- มิติที่ 3: สภาพคล่องสิ้นเดือน: เงินสดและเงินฝากธนาคารคงเหลือจริงจากผังบัญชี 1101% ณ สิ้นงวด {$pPeriod}
- มิติที่ 4: สัดส่วนต้นทุนเดือนนี้: ตรวจสอบ LC บุคลากร (< 50-55%) และ MC ยา/เวชภัณฑ์ (< 20-25%)
- มิติที่ 5: ภาระหนี้สิน: ยอดเจ้าหนี้ AP ค้างจ่าย และลูกหนี้ AR รอชดเชย ณ สิ้นงวด {$pPeriod}
- มิติที่ 6: ตัวชี้วัดวิกฤต: สภาพคล่อง CR/QR, NWC และเงินบำรุงสุทธิ (105) หลังปิดงวดประจำเดือน {$mName}

EOT;
        }

        return <<<EOT
=== องค์ความรู้มาตรฐาน: การติดตามและกำกับแผนเงินบำรุงรายเดือน ครบทุกมิติ (Monthly Financial Tracking) ===
หน่วยบริการ: {$hosp} | รอบปีงบประมาณ: 12 เดือน (ตุลาคม ถึง กันยายน)
{$specificMonthSection}
1. มิติการติดตามเปรียบเทียบในแต่ละเดือน (Monthly Monitoring Dimensions):
   ในระบบ RiMS HosFin มีกลไกติดตามผลการดำเนินงานเปรียบเทียบกับแผนในแต่ละเดือน 2 ระดับ:
   • **ระดับที่ 1: ผลงานประจำเดือนเดี่ยว (Single Month Plan vs Actual)**:
     - เป้าหมายประจำเดือน = `เป้าหมายทั้งปี / 12`
     - ผลงานจริงประจำเดือน = ตัวเลขความเคลื่อนไหวในงบทดลองงวดเดือนนั้น (`debit_month - credit_month`)
     - ผลต่าง (Variance) = `ผลงานจริงประจำเดือน - เป้าหมายประจำเดือน`
     - ร้อยละความสำเร็จ (% Achievement) = `(ผลงานจริง / เป้าหมายประจำเดือน) * 100`
   • **ระดับที่ 2: ผลงานสะสมตั้งแต่ต้นปีงบประมาณถึงเดือนนั้น (Cumulative Plan vs Actual)**:
     - เมื่อติดตามถึงเดือนที่ $N$ (เช่น ต.ค.=เดือน 1, มี.ค.=เดือน 6, ก.ค.=เดือน 10, ก.ย.=เดือน 12):
     - เป้าหมายสะสม $N$ เดือน = `(เป้าหมายทั้งปี / 12) * N`
     - ผลงานจริงสะสม = ตัวเลขสุทธิยกไปในงบทดลองงวดเดือนนั้น (`debit_net - credit_net`)
     - อัตราการเบิกจ่าย/จัดเก็บสะสมต้องสัมพันธ์กับเวลาที่ผ่านไป (Time Elapsed: N/12)

2. มิติความครอบคลุมทางการเงิน 6 ด้านในแต่ละเดือน (6 Financial Dimensions):
   • **มิติที่ 1: รายได้รายเดือนแยกตามกองทุน (Revenue Streams)**:
     - ตรวจสอบอัตราจัดเก็บรายได้สิทธิ UC (P04), สิทธิข้าราชการ (P07), ประกันสังคม (P08) และ อปท. (P61) ทุกสิ้นเดือน
     - ตรวจหาสาเหตุหากรายได้เดือนใดต่ำกว่าเกณฑ์ เช่น เกิดจาก Coding Error, ติด Deny หรือยอดชดเชย IPD ลดลง
   • **มิติที่ 2: ค่าใช้จ่ายและต้นทุนรายเดือน (Expenditure & Cost Control)**:
     - ค่าซื้อยาใช้ไป (P14) และเวชภัณฑ์มิใช่ยา (P15) ประจำเดือน: ต้องคุมไม่ให้เกินกรอบ 10-12% และ 8-10% ของรายได้เดือนนั้น
     - ค่าตอบแทนและจ้างเหมา (P17-P19): ควบคุมไม่ให้เกิน 50-55% ของรายได้เดือนนั้น
   • **มิติที่ 3: กำไรสุทธิและ EBITDA รายเดือน (Monthly Operating Results)**:
     - รายได้สุทธิประจำเดือน (`P27S = P13S - P26S`): ต้องเฝ้าระวังไม่ให้เกิดภาวะขาดทุนต่อเนื่องเกิน 2 เดือน
     - EBITDA ประจำเดือน (`P29`): ประเมินกระแสเงินสดจากการดำเนินงานแท้จริง
   • **มิติที่ 4: สภาพคล่องและเงินสดคงเหลือรายเดือน (Cash & Liquidity)**:
     - ติดตามยอดเงินสดและเงินฝากธนาคารคงเหลือจริงทุกสิ้นเดือนจากผังบัญชีกลุ่ม `1101%`
     - ติดตามกระแสเงินสดรับ-จ่าย และเงินสดคงเหลือสะสมจาก `hosfin_gl_daily_summaries`
   • **มิติที่ 5: ภาระหนี้สินหมุนเวียนรายเดือน (Working Capital & AP/AR)**:
     - ยอดเจ้าหนี้การค้าค้างจ่าย (AP Bills) สิ้นเดือน: ติดตามยอดค้างจ่าย และรอบอายุหนี้
     - ยอดลูกหนี้ค่ารักษาพยาบาลคงค้าง (AR Debtors) สิ้นเดือน: ติดตามหนี้ค้างเกิน 90 วัน
   • **มิติที่ 6: ดัชนีเตือนภัยทางการเงินรายเดือน (Monthly Distress Ratios)**:
     - คำนวณ CR, QR, Cash Ratio, NWC และดัชนี 105 (เงินบำรุงสุทธิหลังหักหนี้) ทุกสิ้นเดือน เพื่อจับสัญญาณวิกฤตล่วงหน้า

3. เครื่องมือสนับสนุนในระบบ RiMS HosFin:
   - เมนู **`hosfin/planfin`** มีรายงานรองรับทั้ง 3 มุมมอง:
     * แท็บ 1.1: แผนสะสมรายปี (Cumulative Plan vs Actual)
     * แท็บ 1.2: แผนรายเดือนเฉพาะงวด (Monthly Plan vs Actual) พร้อมตัวชี้วัด KPI ประจำเดือน
     * แท็บ 1.3: เมทริกซ์ 12 เดือน (12-Month Matrix Trends) แสดงแนวโน้มรายรับ-รายจ่าย รายเดือนครบทั้ง 12 เดือนของปีงบประมาณ
EOT;
    }

    /**
     * Roadmap for Budget Planning Meeting (Universal for any target planning year)
     */
    public function getBudgetPlanningRoadmap(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        $baseY = $yInfo['baseline_year'];
        $targetY = $yInfo['target_planning_year'];
        $shortTarget = $yInfo['short_target_year'];
        $shortBase = $yInfo['short_baseline_year'];

        return <<<EOT
=== คู่มือและแนวทางการเตรียมตัวจัดทำแผนเงินบำรุงปี {$targetY} (FY{$shortTarget} Budget Planning Roadmap) ===
หน่วยบริการ: {$hosp} | ฐานข้อมูลตั้งต้น: ปีงบประมาณ {$baseY} | ปีที่จัดทำแผน: ปีงบประมาณ {$targetY}

ขั้นตอนการเตรียมข้อมูลและสมมติฐานสำคัญ 5 ขั้นตอน (5-Step Strategy):

1. **สรุปผลการดำเนินงานจริงปี {$baseY} มาเป็นฐานข้อมูลตั้งต้น (Baseline Calculation)**:
   - นำตัวเลขผลการดำเนินงานจริงจากงบทดลอง (Trial Balance) ล่าสุดของปี {$baseY} (สะสม $N$ เดือน) มาปรับเป็นฐานทั้งปี (Annualized Run-rate):
     `ประมาณการฐานทั้งปี (Full-Year Baseline) = (ยอดสะสมจริง / จำนวนเดือนสะสม) * 12`
   - ในระบบ RiMS HosFin มีข้อมูลเป้าหมายปี {$baseY} และงบทดลองจริงพร้อมดึงมาเปรียบเทียบอัตโนมัติ

2. **การตั้งสมมติฐานรายได้ปี {$targetY} แยกตามกองทุน (Revenue Growth Assumptions)**:
   - กองทุน UC (P04 - สัดส่วนหลัก ~45-50%): ประเมินจากแนวโน้มผู้รับบริการ, CMI, Adj.RW และอัตราจ่ายชดเชย IPD ของ สปสช. (แนะนำตั้งเป้า % Growth อย่างระมัดระวัง 2-4% เพื่อป้องกัน Overestimate)
   - สิทธิเบิกจ่ายตรงข้าราชการ (P07): ตรวจสอบแนวโน้มยาใน/นอกบัญชี และค่าบริการตามประกาศกรมบัญชีกลาง
   - สิทธิประกันสังคม (P08): ตรวจสอบจำนวนผู้ประกันตนที่เลือก {$hosp} และอัตราเหมาจ่ายรายหัว
   - งบลงทุน (P13) และรายได้อื่น (P12): ตั้งเป้าตามยอดจัดสรรและสถิติจริง

3. **การกำหนดกรอบควบคุมค่าใช้จ่ายปี {$targetY} (Cost Ceiling Constraints)**:
   - ต้นทุนยา (P14): ต้องคุมสัดส่วนไม่ให้เกิน 10-12% ของเป้าหมายรายได้รวม
   - ต้นทุนเวชภัณฑ์มิใช่ยา/วัสดุการแพทย์ (P15): ต้องคุมไม่เกิน 8-10% ของเป้าหมายรายได้รวม
   - ค่าตอบแทน เงินเดือน และจ้างเหมา (P17, P18, P19): รวมกันไม่ควรเกิน 50-55% ของรายได้รวม เพื่อรักษาสภาพคล่อง
   - สาธารณูปโภคและค่าใช้สอย (P21, P22): ควบคุมตามมาตรการประหยัดพลังงานและการใช้ทรัพยากร

4. **คำนวณกรอบวงเงินงบลงทุนด้วยเงินบำรุงปี {$targetY} (CapEx 20% Rule)**:
   - คำนวณ EBITDA ประมาณการของปี {$targetY}
   - นำ `EBITDA ประมาณการ * 20%` = เป็น "เพดานงบประมาณจัดซื้อครุภัณฑ์/สิ่งก่อสร้างปี {$targetY}" ที่แต่ละฝ่ายเสนอขอจัดซื้อได้ ห้ามเสนอเกินเพดานนี้เด็ดขาด

5. **เครื่องมือสนับสนุนในระบบ RiMS HosFin ที่ต้องเปิดใช้ในวันประชุม**:
   - เข้าเมนู **`hosfin/planfin`** และคลิกแท็บ **"แบบจำลองจัดทำแผนเงินบำรุงปี {$targetY} (FY{$shortTarget} Budget Simulator)"**
   - หน้าจอนี้รองรับ:
     * ดึงตัวเลข Baseline ปี {$baseY} อัตโนมัติ
     * กรอกปรับ % Growth รายได้และค่าใช้จ่ายแต่ละหมวดได้แบบ Real-time
     * คำนวณกำไรสุทธิ (P27S), EBITDA (P29) และกรอบวงเงินลงทุน 20% ทันทีที่เปลี่ยนตัวเลข
     * มีปุ่ม **"ส่งออก Excel แผนปี {$targetY}"** เพื่อนำเข้าเอกสารการประชุม
     * มีปุ่ม **"บันทึกเป้าหมายปี {$targetY}" (Save Targets)** เมื่อที่ประชุมมีมติเห็นชอบ
EOT;
    }

    /**
     * 13 Financial Distress Ratios & 7 Risk Scores Knowledge
     */
    public function getFinancialDistressKnowledge(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        return <<<EOT
=== องค์ความรู้มาตรฐาน: 13 ดัชนีชี้วัดสถานะทางการเงินและเกณฑ์วิกฤต 7 ระดับ (สป.สธ.) ===
หน่วยบริการ: {$hosp}

1. กลุ่มดัชนีวัดสภาพคล่องทางการเงิน (Liquidity Ratios - 5 มิติ):
   • ดัชนี [100] อัตราส่วนสภาพคล่องหมุนเวียน (Current Ratio - CR):
     - สูตร: สินทรัพย์หมุนเวียน (1001X) / หนี้สินหมุนเวียน (1001Y)
     - เกณฑ์มาตรฐาน: $\ge 1.5$ เท่า (หากต่ำกว่า 1.0 แสดงว่ามีความเสี่ยงที่จะชำระหนี้ระยะสั้นไม่ได้)
   • ดัชนี [101] อัตราส่วนสภาพคล่องหมุนเวียนเร็ว (Quick Ratio - QR):
     - สูตร: (เงินสดและเทียบเท่า + ลูกหนี้ค่ารักษา) (1002X) / หนี้สินหมุนเวียน (1001Y)
     - เกณฑ์มาตรฐาน: $\ge 1.0$ เท่า (วัดสภาพคล่องโดยตัดสินค้าคงคลังยาออก)
   • ดัชนี [102] อัตราส่วนสภาพคล่องเงินสด (Cash Ratio):
     - สูตร: เงินสดและเงินฝากธนาคาร (1003X) / หนี้สินหมุนเวียน (1001Y)
     - เกณฑ์มาตรฐาน: $\ge 0.8$ เท่า (สะท้อนเงินสดพร้อมจ่ายทันที)
   • ดัชนี [104] ทุนหมุนเวียนสุทธิ (Net Working Capital - NWC):
     - สูตร: สินทรัพย์หมุนเวียน - หนี้สินหมุนเวียน
     - เกณฑ์มาตรฐาน: ต้องมากกว่า 0 บาท (หากติดลบ แสดงว่าหนี้สินหมุนเวียนมากกว่าสินทรัพย์หมุนเวียน เข้าข่ายวิกฤต)
   • ดัชนี [105] เงินบำรุงคงเหลือสุทธิ (Net Cash):
     - สูตร: เงินบำรุงคงเหลือ (1005X) - ภาระหนี้สินผูกพัน (1005Y)
     - เกณฑ์มาตรฐาน: ต้องมากกว่า 0 บาท (หากติดลบ แสดงว่าเงินสดจริงไม่พอจ่ายหนี้ที่มีอยู่)

2. กลุ่มดัชนีวัดประสิทธิภาพการบริหารหนี้และคลัง (Turnover Ratios):
   • ดัชนี [260] ระยะเวลาชำระหนี้เจ้าหนี้การค้ายาและเวชภัณฑ์ (Days Payables Outstanding):
     - สูตร: (เจ้าหนี้การค้ายาเฉลี่ย / ค่าซื้อยาใช้ไปรวม) * 300 วัน
     - เกณฑ์มาตรฐาน: $\le 60$ วัน (หากเกิน 180 วัน ถือว่าผิดนัดชำระและกระทบเครดิตโรงพยาบาล)
   • ดัชนี [261] ระยะเวลาถัวเฉลี่ยเรียกเก็บหนี้สิทธิ์ UC (Days Sales Outstanding - UC):
     - สูตร: (ลูกหนี้ UC เฉลี่ย / รายได้สิทธิ์ UC สุทธิ) * 300 วัน
     - เกณฑ์มาตรฐาน: $\le 30 - 45$ วัน (หากเกิน 90 วัน แสดงว่ามีลูกหนี้ติด C/Deny หรือส่งเบิกช้า)
   • ดัชนี [262] ระยะเวลาถัวเฉลี่ยเรียกเก็บหนี้สิทธิ์ข้าราชการ CSMBS:
     - เกณฑ์มาตรฐาน: $\le 30 - 45$ วัน
   • ดัชนี [264] อัตราหมุนเวียนสินค้าคงคลังยา (Days Sales of Inventory):
     - สูตร: (ยาคงคลังเฉลี่ย / ยาใช้ไปรวม) * 300 วัน
     - เกณฑ์มาตรฐาน: $\le 60$ วัน (หากสูงเกินไปทำให้เงินจมและเสี่ยงยาหมดอายุ)

3. กลุ่มดัชนีวัดความสามารถในการทำกำไรและผลตอบแทน (Profitability Ratios):
   • ดัชนี [320] EBITDA / Operating Margin: เกณฑ์ $\ge 0\%$, เกรดดี $> 7.29\%$
   • ดัชนี [321] อัตราผลตอบแทนจากสินทรัพย์รวม (Return on Assets - ROA): เกณฑ์ $\ge 0\%$
   • ดัชนี [307] อัตรากำไรสุทธิ (Net Margin): กำไรสุทธิ (P27S) / รายได้รวม (P13S) เกณฑ์ $\ge 0\%$

4. การจัดระดับความเสี่ยงทางการเงิน 7 ระดับ (MOPH Financial Risk Score 0 - 7):
   ประเมินจากเกณฑ์ 5 ด้าน (CR < 1.5, QR < 1.0, Cash Ratio < 0.8, NWC < 0, ผลการดำเนินงานขาดทุน):
   - ระดับ 0: ปกติ ไม่มีสัญญาณวิกฤต (สภาพคล่องสมบูรณ์)
   - ระดับ 1 - 2: เฝ้าระวังต่ำ (เริ่มมีดัชนีบางตัวต่ำกว่าเกณฑ์)
   - ระดับ 3 - 4: เฝ้าระวังปานกลาง (สภาพคล่องเริ่มตึงตัว ต้องคุมค่าใช้จ่าย)
   - ระดับ 5 - 6: วิกฤตค่อนข้างรุนแรง (NWC ติดลบ หรือเงินบำรุงสุทธิติดลบ)
   - ระดับ 7: วิกฤตรุนแรงสูงสุด (เงินสดหมด ไม่สามารถชำระหนี้ค่ายาและค่าจ้าง ต้องจัดทำแผนฟื้นฟูทางการเงินส่ง สสจ./เขต)
EOT;
    }

    /**
     * FM Costing, Unit Cost, and TPS Score (15 points) Knowledge
     */
    public function getCostingAndTpsKnowledge(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        return <<<EOT
=== องค์ความรู้มาตรฐาน: การวิเคราะห์ต้นทุนโรงพยาบาล (FM Costing) และเกณฑ์ TPS Score ===
หน่วยบริการ: {$hosp}

1. โครงสร้างต้นทุนบริการโรงพยาบาล (Cost Component Structure: LC / MC / CC):
   • LC (Labor Cost - ค่าแรงและบุคลากร):
     - ได้แก่ เงินเดือน ค่าจ้างประจำ ค่าจ้างชั่วคราว พกส. ค่าตอบแทนเบี้ยเลี้ยงเหมาจ่าย (ฉ.11/ฉ.12) และค่าล่วงเวลา (OT)
     - สัดส่วนที่เหมาะสม: ประมาณ 50% - 55% ของรายได้รวม (ไม่ควรเกิน 60%)
   • MC (Material Cost - ค่าวัสดุ ยา และเวชภัณฑ์):
     - ได้แก่ ค่ายา (MC-Drug), ค่าเวชภัณฑ์มิใช่ยา/วัสดุการแพทย์ (MC-NonDrug), ค่าแล็บ/ชันสูตร และวัสดุบริโภค
     - สัดส่วนที่เหมาะสม: ประมาณ 20% - 25% ของรายได้รวม (ไม่ควรเกิน 30%)
   • CC (Capital Cost - ค่าลงทุนและค่าเสื่อมราคา):
     - ได้แก่ ค่าเสื่อมราคาอาคารและครุภัณฑ์ทางการแพทย์ (P24)
     - สัดส่วนที่เหมาะสม: ประมาณ 10% - 15% ของรายได้รวม

2. การวิเคราะห์ต้นทุนต่อหน่วยบริการ (Unit Cost Analysis):
   • Unit Cost ผู้ป่วยนอก (OPD): ต้นทุนเฉลี่ยต่อการรับบริการ 1 ครั้ง (Cost per Visit)
     - นำไปเปรียบเทียบกับอัตราเหมาจ่ายรายหัว (Capitation) สิทธิ UC และค่าบริการเหมาจ่าย
   • Unit Cost ผู้ป่วยใน (IPD): ต้นทุนเฉลี่ยต่อวันนอน (Cost per Day) และต้นทุนต่อน้ำหนักสัมพัทธ์ (Cost per Adj.RW)
     - นำไปเปรียบเทียบกับอัตราจ่ายชดเชยต่อ Adj.RW ของ สปสช. (เช่น เกณฑ์ 8,350 บาทต่อ Adj.RW)
     - หาก Unit Cost ต่อ Adj.RW สูงกว่าอัตราจ่ายชดเชย แสดงว่าโรงพยาบาลกำลังขาดทุนจากการรักษาผู้ป่วยใน ต้องทบทวนการใช้ยาและวันนอนเฉลี่ย (ALOS)

3. เกณฑ์การประเมินประสิทธิภาพ Total Performance Score (TPS) ด้านการเงิน 15 คะแนน:
   - เป็นดัชนีชี้วัดรวมที่กระทรวงสาธารณสุขใช้ประเมินประสิทธิภาพผู้บริหารและโรงพยาบาลประจำปี
   - ตัวชี้วัดสำคัญที่ให้คะแนน:
     1. ผลการดำเนินงานสุทธิ (Operating Margin / Net Income) เป็นบวก
     2. ระดับคะแนนความเสี่ยงวิกฤต (Risk Score) ต้องอยู่ในระดับ 0-2
     3. ประสิทธิภาพการควบคุมต้นทุนต่อ Adj.RW (Unit Cost Efficiency)
     4. การบรรลุเป้าหมายตามแผนเงินบำรุง (Plan vs Actual)
     5. การควบคุมสัดส่วนหนี้สินและระยะเวลาชำระหนี้ยาสูงสุดไม่เกิน 60 วัน
EOT;
    }

    /**
     * Working Capital, MOPH Regulations, Cash Flow, AR/AP Strategy
     */
    public function getWorkingCapitalKnowledge(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        return <<<EOT
=== องค์ความรู้มาตรฐาน: ระเบียบเงินบำรุง สธ. 2562 และยุทธศาสตร์การบริหารกระแสเงินสด (Working Capital) ===
หน่วยบริการ: {$hosp}

1. ระเบียบกระทรวงสาธารณสุขว่าด้วยเงินบำรุงของหน่วยบริการ พ.ศ. 2562:
   • หลักการเงินบำรุง: เป็นเงินรายได้ที่หน่วยบริการได้รับจากการให้บริการสาธารณสุข การจำหน่ายยาและเวชภัณฑ์ และเงินอุดหนุน
   • การกันเงินสำรอง (Cash Reserve):
     - {$hosp} ควรกำหนดวงเงินสำรองเพื่อเป็นสภาพคล่องสำหรับค่าใช้จ่ายจำเป็นประจำไม่น้อยกว่า 3 เดือน (เช่น ค่าจ้างบุคลากร, ค่ายาจำเป็น, สาธารณูปโภค)
   • อำนาจการอนุมัติการใช้จ่ายเงินบำรุง:
     - การจัดซื้อจัดจ้างให้เป็นไปตามระเบียบกระทรวงการคลังว่าด้วยการจัดซื้อจัดจ้างฯ พ.ศ. 2560 และกฎหมายที่เกี่ยวข้อง
     - การจัดซื้อครุภัณฑ์/สิ่งก่อสร้างด้วยเงินบำรุง ต้องผ่านความเห็นชอบจากคณะกรรมการบริหาร รพ. และอยู่ในกรอบแผนเงินบำรุงที่ได้รับอนุมัติ

2. ยุทธศาสตร์การบริหารลูกหนี้ค่ารักษาพยาบาล (AR Management & Cash Conversion Cycle):
   • ปัญหาลูกหนี้ค้างท่อ (Aging AR): ลูกหนี้สิทธิ UC, ข้าราชการ, อปท. ที่ค้างนานเกิน 90 วัน มักเกิดจาก:
     - บันทึกรหัสโรค/หัตถการไม่ครบถ้วน (Coding Error)
     - ข้อมูลติดเงื่อนไข C (Check), V (Validation) หรือ Deny จาก สปสช./กรมบัญชีกลาง
   • มาตรการเร่งรัดเรียกเก็บหนี้:
     - ตั้งทีมตรวจสอบข้อผิดพลาด (Re-process Claim) ภายใน 15-30 วันหลังถูก Deny
     - การตัดหนี้สูญ (Bad Debt Write-off - P241): ต้องปฏิบัติตามหลักเกณฑ์ สธ. อย่างเคร่งครัด เช่น ลูกหนี้ส่งใบเตือนเกิน 3 ครั้งและติดตามเกิน 1-2 ปี

3. กลยุทธ์การจัดลำดับการชำระหนี้เจ้าหนี้การค้า (Debt Prioritization Framework - AP Management):
   • เมื่อสภาพคล่องตึงตัว ควรกำหนดลำดับความสำคัญในการจ่ายหนี้ดังนี้:
     - ลำดับ 1: ค่าจ้าง เงินเดือน และค่าตอบแทนบุคลากรทางการแพทย์ (เพื่อความต่อเนื่องของบริการ)
     - ลำดับ 2: เจ้าหนี้ยากลุ่มช่วยชีวิต (Life-saving Drugs) และยาหลักที่มีรอบการส่งมอบต่อเนื่อง
     - ลำดับ 3: ค่าสาธารณูปโภคและระบบสนับสนุนจำเป็น (ไฟฟ้า, ประปา, ออกซิเจน)
     - ลำดับ 4: เจ้าหนี้การค้ายาและเวชภัณฑ์ทั่วไปตามอายุหนี้ (FIFO - First In, First Out)
     - ลำดับ 5: ค่าจ้างเหมาบริการและครุภัณฑ์ลงทุนใหม่
EOT;
    }

    /**
     * Chart of Accounts (5 Categories), Item-level Drill-down, Forecasting & Prescriptive Strategic Guidance
     */
    public function getChartOfAccountsAndDrillDownKnowledge(?array $yInfo = null): string
    {
        $yInfo = $yInfo ?: $this->getBudgetYearsInfo();
        $hosp = $yInfo['hospital_name'];
        $baseY = $yInfo['baseline_year'];

        return <<<EOT
=== องค์ความรู้มาตรฐาน: การบริหารผังบัญชี 5 หมวด การเจาะลึกรายตัว การคาดการณ์ และข้อเสนอแนะเชิงยุทธศาสตร์ ===
หน่วยบริการ: {$hosp} | ฐานปีงบประมาณ: {$baseY}

1. โครงสร้าง 5 หมวดบัญชีมาตรฐานโรงพยาบาล สังกัดกระทรวงสาธารณสุข (Chart of Accounts Master Structure):
   • **หมวด 1: สินทรัพย์ (Assets) — รหัสขึ้นต้นด้วย 1**:
     - 1101 เงินสดและเงินฝากธนาคาร: ประกอบด้วย เงินสดในมือ (1101010101.101), เงินฝากคลัง (1101020501.103), เงินฝากธนาคารในงบประมาณ (1101020603.101), เงินฝากธนาคารนอกงบประมาณ ออมทรัพย์ UC ธกส. (1101030102.10102), เงินฝากสถานะสิทธิ์ กรุงไทย (1101030102.10101)
     - 1102 ลูกหนี้ค่ารักษาพยาบาล: ลูกหนี้สิทธิ UC (1102050101.101), ลูกหนี้ข้าราชการ กรมบัญชีกลาง (1102050102), ลูกหนี้ประกันสังคม (1102050103), ลูกหนี้ อปท. (1102050107)
     - 1104 สินค้าคงคลัง: ยาคงคลัง (1104010101), เวชภัณฑ์มิใช่ยา/วัสดุการแพทย์คงคลัง (1104010102)
     - 1201 ที่ดิน อาคาร และอุปกรณ์/สิ่งก่อสร้าง (หักค่าเสื่อมราคาสะสม)
     - ธรรมชาติของบัญชี: เดบิต (เพิ่มฝั่งเดบิต ลดฝั่งเครดิต) ยอดคงเหลือปกติ = เดบิตสุทธิ
   • **หมวด 2: หนี้สิน (Liabilities) — รหัสขึ้นต้นด้วย 2**:
     - 2101 เจ้าหนี้การค้า: เจ้าหนี้การค้ายา (2101010101.101), เจ้าหนี้เวชภัณฑ์มิใช่ยา (2101010102), เจ้าหนี้วัสดุการแพทย์ (2101010103)
     - 2102 เจ้าหนี้อื่น และค่าใช้จ่ายค้างจ่าย (ค่าจ้างค้างจ่าย, สาธารณูปโภคค้างจ่าย)
     - 2108 หนี้สินหมุนเวียนอื่น (เงินรับฝาก, ภาษีหัก ณ ที่จ่ายรอส่ง)
     - ธรรมชาติของบัญชี: เครดิต (เพิ่มฝั่งเครดิต ลดฝั่งเดบิต) ยอดคงเหลือปกติ = เครดิตสุทธิ
   • **หมวด 3: ส่วนของเจ้าของ / ทุน (Equity) — รหัสขึ้นต้นด้วย 3**:
     - 3101 เงินบำรุงสะสม (Accumulated Operating Reserve): สะท้อนความมั่งคั่งและเงินกองทุนสะสมของโรงพยาบาลตั้งแต่อดีต
     - 3201 รายได้สูง/(ต่ำ)กว่าค่าใช้จ่ายสะสมประจำปี
     - ธรรมชาติของบัญชี: เครดิต (หากกำไรยอดเครดิตจะเพิ่ม หากขาดทุนสะสมจะถูกเดบิตลดทอน)
   • **หมวด 4: รายได้ (Revenues) — รหัสขึ้นต้นด้วย 4**:
     - 4101 รายได้ค่ารักษาพยาบาลสิทธิ UC (OPD รายหัว, IPD Global Budget, PP บริการสร้างเสริมฯ, Fee Schedule)
     - 4102 สิทธิข้าราชการ กรมบัญชีกลาง (CSMBS)
     - 4103 สิทธิประกันสังคม (SSS)
     - 4104 สิทธิองค์กรปกครองส่วนท้องถิ่น (อปท. LGO)
     - 4301 รายได้เงินอุดหนุนจากรัฐบาลและงบส่วนบุคลากร
     - ธรรมชาติของบัญชี: เครดิต (บันทึกรายได้ฝั่งเครดิต หักลด/ปรับปรุงฝั่งเดบิต)
   • **หมวด 5: ค่าใช้จ่าย (Expenses) — รหัสขึ้นต้นด้วย 5**:
     - 5101 ค่าใช้จ่ายบุคลากร (LC): เงินเดือนข้าราชการ (5101010101.101), ค่าจ้าง พกส./ลูกจ้างชั่วคราว, ค่าตอบแทน ฉ.11/ฉ.12, ค่าเบี้ยเลี้ยงและล่วงเวลา (OT)
     - 5102 ค่าใช้จ่ายยาและเวชภัณฑ์ (MC): ค่ายา (5102010101.101), ค่าเวชภัณฑ์มิใช่ยา (5102010102), ค่าชันสูตร
     - 5103 ค่าวัสดุใช้ไป และ 5104 ค่าสาธารณูปโภค (ค่าไฟ, ค่าน้ำ, ค่าไปรษณีย์โทรคมนาคม)
     - 5201 ค่าเสื่อมราคาและค่าตัดจำหน่าย (CC)
     - ธรรมชาติของบัญชี: เดบิต (บันทึกค่าใช้จ่ายฝั่งเดบิต ยอดคงเหลือปกติ = เดบิตสุทธิ)

2. ระเบียบวิธีเจาะลึกรายตัว (Account & Item-Level Diagnostic Drill-Down):
   - **การตรวจสอบ 3 มิติต่อ 1 รายการผังบัญชี**:
     * มิติ 1 (ยอดยกมาต้นงวด): ยอดยกมาจากปีก่อนหน้า (`debit_bf - credit_bf`)
     * มิติ 2 (การรับ-จ่ายเคลื่อนไหวเดือนนี้): ความเคลื่อนไหวที่เกิดขึ้นเฉพาะในรอบงวดเดือนปัจจุบัน (`debit_month`, `credit_month`)
     * มิติ 3 (ยอดคงเหลือสะสมยกไปสุทธิ): ผลสะสมตั้งแต่ต้นปีงบประมาณจนถึงปัจจุบัน (`debit_net - credit_net`)
   - **การเจาะลึกบัญชีเงินสดและเงินฝากธนาคาร (1101%)**: ตรวจสอบการกระทบยอดกับ Statement จริง หากยอดคงเหลือในบัญชี UC ธกส. หรือกรุงไทยลดต่ำลง ให้ตรวจสอบว่าเกิดจากรายรับชดเชยที่เข้ามาล่าช้า หรือมีการจ่ายเช็ค/โอนเงินชำระเจ้าหนี้การค้าก้อนใหญ่
   - **การเจาะลึกบัญชีลูกหนี้รายสิทธิ (1102%)**: ตรวจสอบอัตราการเรียกเก็บและหนี้ค้างท่อ หากยอดลูกหนี้สิทธิใดสะสมสูงขึ้นผิดปกติ แสดงว่ามีเคสตกค้างที่ยังไม่ส่งเคลม หรือติดเงื่อนไข Deny / C-code
   - **การเจาะลึกบัญชีเจ้าหนี้การค้า (2101%)**: ตรวจสอบยอดหนี้คงค้างรายบริษัท เพื่อไม่ให้ระยะเวลาชำระหนี้ยาสูงสุดเกินเกณฑ์ 60 วัน

3. การคาดการณ์แนวโน้มล่วงหน้า (Predictive Forecasting):
   - **การประมาณการสิ้นปีงบประมาณทั้งปี (Annualized Run-rate)**:
     `ยอดประมาณการสิ้นปีทั้งปี = (ยอดสะสมจริง ณ เดือนที่ N / N) * 12`
     * นำยอดประมาณการสิ้นปีนี้ ไปเปรียบเทียบกับเป้าหมายแผนเงินบำรุงประจำปี (PlanFin Targets) เพื่อดูว่า "มีแนวโน้มจะบรรลุเป้าหมายหรือไม่"
   - **การพยากรณ์กระแสเงินสดและรันเวย์สภาพคล่อง (Cash Runway Forecast)**:
     * คำนวณอัตราการเผาเงินสดสุทธิเฉลี่ยต่อเดือน (Net Cash Burn Rate) = `รายจ่ายเงินสดเฉลี่ย - รายรับเงินสดเฉลี่ย`
     * หาก Burn Rate ติดลบ ให้คำนวณจำนวนเดือนที่เงินสดคงเหลือจะรองรับได้ (Cash Runway = เงินสดคงเหลือ / Burn Rate ต่อเดือน) เพื่อแจ้งเตือนผู้บริหารล่วงหน้าอย่างน้อย 3 เดือน

4. ข้อเสนอแนะเชิงกลยุทธ์และการปฏิบัติจริง (Actionable & Prescriptive Recommendations):
   - **กรณีเงินสด/สภาพคล่องตึงตัว**: ดำเนินการตาม Debt Prioritization Framework จ่ายบุคลากรและยากลุ่มช่วยชีวิตก่อน เจรจายืดหนี้เจ้าหนี้การค้าทั่วไป พร้อมเร่งส่ง Re-process ลูกหนี้สิทธิที่ติด Deny ภายใน 15 วัน
   - **กรณีค่าใช้จ่ายหมวด 5 สูงกว่าเป้าหมาย**: ใช้มาตรการ Cost Ceiling Freeze ควบคุมค่าจ้างเหมาและล่วงเวลา (OT) ให้ไม่เกิน 50-55% ของรายได้ และจัดทำ Formulary ควบคุมการใช้ยานอกบัญชี (NED)
   - **กรณีรายได้หมวด 4 ต่ำกว่าเป้าหมาย**: ตรวจสอบอัตรา CMI, Adj.RW และทบทวนกระบวนการ Audit เวชระเบียน เพื่อป้องกันการตกหล่นของรหัสโรคและรหัสหัตถการสำคัญ
EOT;
    }
}
