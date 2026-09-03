<?php

namespace App\Services\Ai\Context;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HosfinContextService
{
    /**
     * Get HosFin periods info with lightweight caching
     */
    public function getPeriodsInfo(): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_trial_balance')) {
                return null;
            }

            static $cachedPeriods = null;
            if ($cachedPeriods !== null) {
                return $cachedPeriods ?: null;
            }

            $allPeriods = DB::table('hosfin_trial_balance')
                ->distinct()
                ->orderBy('acc_period', 'asc')
                ->pluck('acc_period')
                ->toArray();

            if (empty($allPeriods)) {
                $cachedPeriods = false;
                return null;
            }

            $minPeriod = reset($allPeriods);
            $maxPeriod = end($allPeriods);
            $cachedPeriods = [
                'all' => $allPeriods,
                'min' => $minPeriod,
                'max' => $maxPeriod,
                'count' => count($allPeriods),
                'listStr' => implode(', ', $allPeriods),
            ];

            return $cachedPeriods;
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService Periods Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get live summary of HosFin metrics (13 Financial Distress Ratios)
     */
    public function getHosFinSummary(): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_trial_balance')) {
                return null;
            }
            $latestPeriod = DB::table('hosfin_trial_balance')->orderBy('acc_period', 'desc')->value('acc_period');
            if (!$latestPeriod) {
                return null;
            }

            $controller = app(\App\Http\Controllers\HosFinController::class);
            $view = $controller->index();
            $data = $view->getData();
            if (empty($data['hasData'])) {
                return null;
            }

            $m = $data['latestMetrics'];
            $period = $data['latestPeriodLabel'] ?? '';
            $netCash = $m['105']['val'] ?? 0;
            $riskScore = $data['riskScore'] ?? 0;
            $riskLabel = $data['riskScoreLevelLabel'] ?? '';
            $cr = $m['100']['val'] ?? 0;
            $qr = $m['101']['val'] ?? 0;
            $cash = $m['102']['val'] ?? 0;
            $nwc = $m['104']['val'] ?? 0;
            $payDrugs = $m['260']['val'] ?? 0;
            $collectUc = $m['261']['val'] ?? 0;
            $collectOfc = $m['262']['val'] ?? 0;
            $inventory = $m['264']['val'] ?? 0;
            $netMargin = $m['307']['val'] ?? 0;
            $netIncome = $m['NI']['val'] ?? 0;

            $text = "ข้อมูลดัชนีชี้วัดสถานะการเงิน (HosFin Financial Distress Ratios) ของโรงพยาบาล ณ งวดบัญชีล่าสุด {$period} พร้อมสูตรคำนวณและกลุ่มผังบัญชีจากตาราง hosfin_dtl_mappings:\n";
            $text .= "1. [105] เงินบำรุงคงเหลือสุทธิ: " . number_format($netCash, 2) . " บาท " . ($netCash < 0 ? "(วิกฤต ติดลบสูง)" : "(ปกติ)") . "\n"
                  . "   • สูตร: กลุ่ม 1005X (เงินบำรุงคงเหลือ) หักลบ กลุ่ม 1005Y (ภาระหนี้สิน)\n"
                  . "   • ตัวตั้ง 1005X = " . number_format($m['105']['num'] ?? 0, 2) . " บาท, ตัวหัก 1005Y = " . number_format($m['105']['den'] ?? 0, 2) . " บาท\n";
            $text .= "2. [100] สภาพคล่องหมุนเวียน Current Ratio: {$cr} เท่า (เกณฑ์ปกติ >= 1.5, ปัจจุบันต่ำกว่าเกณฑ์มาก)\n"
                  . "   • สูตร: กลุ่ม 1001X (สินทรัพย์หมุนเวียน: " . number_format($m['100']['num'] ?? 0, 2) . ") / กลุ่ม 1001Y (หนี้สินหมุนเวียน: " . number_format($m['100']['den'] ?? 0, 2) . ")\n";
            $text .= "3. [101] สภาพคล่องหมุนเวียนเร็ว Quick Ratio: {$qr} เท่า (เกณฑ์ปกติ >= 1.0)\n"
                  . "   • สูตร: กลุ่ม 1002X (เงินสดและลูกหนี้: " . number_format($m['101']['num'] ?? 0, 2) . ") / กลุ่ม 1001Y (" . number_format($m['101']['den'] ?? 0, 2) . ")\n";
            $text .= "4. [102] สภาพคล่องเงินสด Cash Ratio: {$cash} เท่า (เกณฑ์ปกติ >= 0.8, เงินสดเหลือน้อยมาก)\n"
                  . "   • สูตร: กลุ่ม 1003X (เงินสดและเทียบเท่า: " . number_format($m['102']['num'] ?? 0, 2) . ") / กลุ่ม 1001Y (" . number_format($m['102']['den'] ?? 0, 2) . ")\n";
            $text .= "5. [104] ทุนหมุนเวียนสุทธิ Net Working Capital: " . number_format($nwc, 2) . " บาท " . ($nwc < 0 ? "(ติดลบ วิกฤต)" : "") . "\n"
                  . "   • สูตร: กลุ่ม 1001X (สินทรัพย์หมุนเวียน) - กลุ่ม 1001Y (หนี้สินหมุนเวียน)\n";
            $text .= "6. [260] ระยะเวลาชำระเจ้าหนี้การค้ายาและเวชภัณฑ์: {$payDrugs} วัน (เกณฑ์ปกติ <= 60 วัน, ปัจจุบันค้างจ่ายนานผิดปกติกว่า 8 เดือน)\n"
                  . "   • สูตร: (กลุ่ม 2600X เจ้าหนี้การค้ายาเฉลี่ย: " . number_format($m['260']['num'] ?? 0, 2) . " / กลุ่ม 2600Y ซื้อยาใช้ไปรวม: " . number_format($m['260']['den'] ?? 0, 2) . ") * 300 วัน\n";
            $text .= "7. [261] ระยะเวลาถัวเฉลี่ยเก็บหนี้สิทธิ์ UC: {$collectUc} วัน (เกณฑ์ปกติ <= 30-45 วัน)\n"
                  . "   • สูตร: (กลุ่ม 2610X ลูกหนี้ UC เฉลี่ย: " . number_format($m['261']['num'] ?? 0, 2) . " / กลุ่ม 2610Y รายได้สิทธิ์ UC สุทธิ: " . number_format($m['261']['den'] ?? 0, 2) . ") * 300 วัน\n";
            $text .= "8. [262] ระยะเวลาถัวเฉลี่ยเก็บหนี้สิทธิ์ข้าราชการ (CSMBS): {$collectOfc} วัน (เกณฑ์ปกติ <= 30-45 วัน, ลูกหนี้ค้างท่อสูงมาก)\n"
                  . "   • สูตร: (กลุ่ม 2620X ลูกหนี้ข้าราชการเฉลี่ย: " . number_format($m['262']['num'] ?? 0, 2) . " / กลุ่ม 2620Y รายได้ข้าราชการสุทธิ: " . number_format($m['262']['den'] ?? 0, 2) . ") * 300 วัน\n";
            $text .= "9. [264] การบริหารสินค้าคงคลัง ยา: {$inventory} วัน (เกณฑ์ปกติ <= 60 วัน)\n"
                  . "   • สูตร: (กลุ่ม 2640X วัสดุคงคลังเฉลี่ย / กลุ่ม 2640Y วัสดุใช้ไป) * 300 วัน\n";
            $text .= "10. [320] EBITDA / Operating Margin: " . ($m['320']['val'] ?? 0) . " % (สูตร: กลุ่ม 3200X EBITDA / กลุ่ม 3002Y รายได้บริการ * 100)\n";
            $text .= "11. [321] Return on Asset (ROA): " . ($m['321']['val'] ?? 0) . " % (สูตร: กลุ่ม 3007X กำไรสุทธิ / กลุ่ม 3014Y สินทรัพย์รวม * 100)\n";
            $text .= "12. [307] Net Margin: {$netMargin} % (กำไรสุทธิ: " . number_format($netIncome, 2) . " บาท จากกลุ่ม 3007X / กลุ่ม 3006Y รายได้รวม)\n";
            $text .= "13. [RISK SCORE] คะแนนความเสี่ยงวิกฤต: {$riskScore} / 7 ({$riskLabel}) จากเกณฑ์ 5 ด้านหลัก (CR<1.5, QR<1.0, Cash<0.8, NWC<0, กำไร<0)\n";

            // Append Live GL Intelligence (AP, AR, Cost LC/MC/CC, Cash)
            if (Schema::hasTable('hosfin_gl_ap_bills')) {
                $totalUnpaidAp = (float)DB::table('hosfin_gl_ap_bills')->where('is_paid', 0)->sum('remaining_debt');
                $totalUnpaidApCount = (int)DB::table('hosfin_gl_ap_bills')->where('is_paid', 0)->count();
                $topCreditors = DB::table('hosfin_gl_ap_bills')
                    ->select('vendor_name', DB::raw('SUM(remaining_debt) as remaining_debt'), DB::raw('COUNT(*) as total_bills'))
                    ->where('is_paid', 0)
                    ->groupBy('vendor_name')
                    ->orderBy('remaining_debt', 'desc')
                    ->limit(5)
                    ->get();

                $text .= "\nข้อมูลเจ้าหนี้การค้าจริงจากระบบ GL (AP Bills):\n"
                      . "- หนี้ค้างชำระรวม: " . number_format($totalUnpaidAp, 2) . " บาท จากทั้งหมด " . number_format($totalUnpaidApCount) . " บิล\n";
                if ($topCreditors->isNotEmpty()) {
                    $text .= "- เจ้าหนี้ค้างจ่ายสูงสุด 5 อันดับแรก: " . $topCreditors->map(fn($v) => "{$v->vendor_name} (" . number_format($v->remaining_debt, 2) . " บ.)")->implode(', ') . "\n";
                }
            }

            if (Schema::hasTable('hosfin_gl_ar_debtors')) {
                $totalArOutstanding = (float)DB::table('hosfin_gl_ar_debtors')->sum('outstanding_balance');
                $arTypeSummaries = DB::table('hosfin_gl_ar_debtors')
                    ->select('debtor_type', DB::raw('SUM(outstanding_balance) as outstanding'))
                    ->groupBy('debtor_type')
                    ->orderBy('outstanding', 'desc')
                    ->get();

                $text .= "\nข้อมูลลูกหนี้ค่ารักษาพยาบาลจริงจากระบบ GL (AR Debtors):\n"
                      . "- ลูกหนี้คงค้างรอชดเชยรวม: " . number_format($totalArOutstanding, 2) . " บาท\n"
                      . "- ยอดค้างแยกตามสิทธิ: " . $arTypeSummaries->map(fn($s) => ($s->debtor_type ?: 'ทั่วไป') . " (" . number_format($s->outstanding, 2) . " บ.)")->implode(', ') . "\n";
            }

            if (Schema::hasTable('hosfin_gl_cost_summaries')) {
                $totalCost = (float)DB::table('hosfin_gl_cost_summaries')->sum('total_cost');
                $totalLc = (float)DB::table('hosfin_gl_cost_summaries')->sum('lc_amount');
                $totalMc = (float)DB::table('hosfin_gl_cost_summaries')->sum('mc_amount');
                $totalCc = (float)DB::table('hosfin_gl_cost_summaries')->sum('cc_amount');

                $lcPct = $totalCost > 0 ? round(($totalLc / $totalCost) * 100, 1) : 0;
                $mcPct = $totalCost > 0 ? round(($totalMc / $totalCost) * 100, 1) : 0;
                $ccPct = $totalCost > 0 ? round(($totalCc / $totalCost) * 100, 1) : 0;

                $text .= "\nโครงสร้างต้นทุนบริการจริงจากระบบ GL (Cost LC / MC / CC):\n"
                      . "- ต้นทุนรวมทั้งสิ้น: " . number_format($totalCost, 2) . " บาท\n"
                      . "- MC (ค่าวัสดุ ยา และเวชภัณฑ์): " . number_format($totalMc, 2) . " บาท ({$mcPct}%)\n"
                      . "- LC (ค่าแรงและบุคลากร): " . number_format($totalLc, 2) . " บาท ({$lcPct}%)\n"
                      . "- CC (ค่าลงทุนและเสื่อมราคา): " . number_format($totalCc, 2) . " บาท ({$ccPct}%)\n";
            }

            if (Schema::hasTable('hosfin_gl_accounts') && Schema::hasTable('hosfin_gl_journal_items')) {
                $cashBank = DB::table('hosfin_gl_accounts as a')
                    ->leftJoin('hosfin_gl_journal_items as i', 'a.account_code', '=', 'i.account_code')
                    ->where('a.account_code', 'like', '1101%')
                    ->select('a.account_code', 'a.account_name', DB::raw('SUM(COALESCE(i.debit, 0) - COALESCE(i.credit, 0)) as balance'))
                    ->groupBy('a.account_code', 'a.account_name')
                    ->having('balance', '<>', 0)
                    ->get();
                $totalCash = (float)$cashBank->sum('balance');

                $text .= "\nข้อมูลเงินสดและเงินฝากธนาคารจริงจากระบบ GL:\n"
                      . "- ยอดเงินสดและเงินฝากธนาคารคงเหลือรวม: " . number_format($totalCash, 2) . " บาท (จาก " . $cashBank->count() . " บัญชี)\n";
            }

            return [
                'text' => $text,
                'period' => $period,
                'netCash' => $netCash,
                'riskScore' => $riskScore,
                'riskLabel' => $riskLabel
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService Summary Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Look up specific account codes or account names in hosfin_trial_balance
     */
    public function getTrialBalanceAccountContext(string $query): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_trial_balance')) {
                return null;
            }
            $allPeriods = DB::table('hosfin_trial_balance')->distinct()->orderBy('acc_period', 'desc')->limit(3)->pluck('acc_period')->toArray();
            $latestPeriod = $allPeriods[0] ?? null;
            $prevPeriod = $allPeriods[1] ?? null;
            if (!$latestPeriod) {
                return null;
            }

            $isAskingPreviousMonth = (bool) preg_match('/(เดือนที่แล้ว|เดือนก่อน|งวดก่อน|ย้อนหลัง)/iu', $query);
            $targetPeriod = ($isAskingPreviousMonth && $prevPeriod) ? $prevPeriod : $latestPeriod;
            $periodDesc = ($targetPeriod === $prevPeriod) ? "{$targetPeriod} (เดือนที่แล้ว / เดือนก่อนหน้า)" : "{$targetPeriod} (งวดล่าสุด)";

            // 1. Check if query contains an account code pattern e.g. 1102050101 or 1102
            preg_match('/(\d{4,10}(?:\.\d{1,4})?)/', $query, $codeMatch);
            $queryCode = $codeMatch[1] ?? null;

            $items = collect();

            if ($queryCode) {
                $items = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $targetPeriod)
                    ->where('account_code', 'like', $queryCode . '%')
                    ->select('account_code', 'account_name', 'debit_bf', 'credit_bf', 'debit_month', 'credit_month', 'debit_net', 'credit_net')
                    ->limit(15)
                    ->get();
            }

            // 1.5 Check if query is asking for ratio formula breakdown
            $ratioGroupMap = [
                '105' => ['name' => 'เงินบำรุงคงเหลือสุทธิ (105)', 'num' => '1005X', 'den' => '1005Y', 'type' => 'subtract'],
                '104' => ['name' => 'ทุนหมุนเวียนสุทธิ NWC (104)', 'num' => '1001X', 'den' => '1001Y', 'type' => 'subtract'],
                '100' => ['name' => 'สภาพคล่องหมุนเวียน Current Ratio (100)', 'num' => '1001X', 'den' => '1001Y', 'type' => 'divide'],
                '102' => ['name' => 'สภาพคล่องเงินสด Cash Ratio (102)', 'num' => '1003X', 'den' => '1001Y', 'type' => 'divide'],
                '260' => ['name' => 'ระยะเวลาชำระเจ้าหนี้การค้ายา (260)', 'num' => '2600X', 'den' => '2600Y', 'type' => 'days'],
                '261' => ['name' => 'ระยะเวลาเรียกเก็บหนี้สิทธิ UC (261)', 'num' => '2610X', 'den' => '2610Y', 'type' => 'days'],
                '262' => ['name' => 'ระยะเวลาเรียกเก็บหนี้สิทธิข้าราชการ (262)', 'num' => '2620X', 'den' => '2620Y', 'type' => 'days'],
                '264' => ['name' => 'การบริหารสินค้าคงคลัง ยา (264)', 'num' => '2640X', 'den' => '2640Y', 'type' => 'days'],
            ];

            $matchedRatioKey = null;
            if (preg_match('/(105|เงินบำรุง|ภาระหนี้สิน|ทำไม.*ติดลบ)/iu', $query)) $matchedRatioKey = '105';
            elseif (preg_match('/(260|ค่ายา|เจ้าหนี้.*ยา)/iu', $query)) $matchedRatioKey = '260';
            elseif (preg_match('/(100|current\s*ratio|สภาพคล่องหมุนเวียน)/iu', $query)) $matchedRatioKey = '100';
            elseif (preg_match('/(102|cash\s*ratio|สภาพคล่องเงินสด)/iu', $query)) $matchedRatioKey = '102';
            elseif (preg_match('/(104|nwc|ทุนหมุนเวียน)/iu', $query)) $matchedRatioKey = '104';
            elseif (preg_match('/(261|ลูกหนี้\s*uc)/iu', $query)) $matchedRatioKey = '261';
            elseif (preg_match('/(262|ลูกหนี้.*ข้าราชการ|csmbs)/iu', $query)) $matchedRatioKey = '262';
            elseif (preg_match('/(264|คงคลัง.*ยา|วัสดุคงคลัง)/iu', $query)) $matchedRatioKey = '264';

            $isFormulaOrWhyQuery = (bool) preg_match('/(สูตร|คำนวณ|ทำไม|ที่มา|วิเคราะห์|ratio|อัตราส่วน|เกิดจาก|ผังไหน|กลุ่ม)/iu', $query);

            if ($matchedRatioKey && $isFormulaOrWhyQuery && Schema::hasTable('hosfin_dtl_mappings')) {
                $rDef = $ratioGroupMap[$matchedRatioKey];
                $numGroup = $rDef['num'];
                $denGroup = $rDef['den'];

                $numAccs = DB::table('hosfin_dtl_mappings as m')
                    ->join('hosfin_trial_balance as tb', function($j) use ($targetPeriod) {
                        $j->on('m.account_code', '=', 'tb.account_code')->where('tb.acc_period', $targetPeriod);
                    })
                    ->where('m.group_code', $numGroup)
                    ->select('m.account_code', 'm.account_name', 'tb.debit_net', 'tb.credit_net')
                    ->orderByDesc(DB::raw('GREATEST(tb.debit_net, tb.credit_net)'))
                    ->limit(5)
                    ->get();

                $denAccs = DB::table('hosfin_dtl_mappings as m')
                    ->join('hosfin_trial_balance as tb', function($j) use ($targetPeriod) {
                        $j->on('m.account_code', '=', 'tb.account_code')->where('tb.acc_period', $targetPeriod);
                    })
                    ->where('m.group_code', $denGroup)
                    ->select('m.account_code', 'm.account_name', 'tb.debit_net', 'tb.credit_net')
                    ->orderByDesc(DB::raw('GREATEST(tb.debit_net, tb.credit_net)'))
                    ->limit(5)
                    ->get();

                $rLines = [];
                $rLines[] = "วิเคราะห์เจาะลึกที่มาของดัชนี {$rDef['name']} จากตาราง hosfin_dtl_mappings ร่วมกับงบทดลองจริง งวด {$periodDesc}:";
                $rLines[] = "\n1. ฝั่งตัวตั้ง [กลุ่ม {$numGroup}]:";
                foreach ($numAccs as $a) {
                    $val = $a->debit_net > 0 ? ("เดบิต: " . number_format($a->debit_net, 2)) : ("เครดิต: " . number_format($a->credit_net, 2));
                    $rLines[] = "   - [{$a->account_code}] {$a->account_name} => {$val} บาท";
                }
                $rLines[] = "\n2. ฝั่งตัวหาร/ตัวหัก [กลุ่ม {$denGroup}]:";
                foreach ($denAccs as $b) {
                    $val = $b->credit_net > 0 ? ("เครดิต: " . number_format($b->credit_net, 2)) : ("เดบิต: " . number_format($b->debit_net, 2));
                    $rLines[] = "   - [{$b->account_code}] {$b->account_name} => {$val} บาท";
                }

                return [
                    'text' => implode("\n", $rLines),
                    'period' => $targetPeriod,
                    'count' => count($numAccs) + count($denAccs),
                    'preview' => "เจาะลึกผังบัญชีกลุ่ม {$numGroup} และ {$denGroup} สำหรับคำนวณ {$rDef['name']}"
                ];
            }

            // 2. Search by keywords
            if ($items->isEmpty()) {
                $cleanSearch = preg_replace('/(เท่าไหร่|จ่ายไป|จ่าย|เดือนที่แล้ว|เดือนก่อน|เดือนนี้|งวดนี้|งวดก่อน|ขอดู|หน่อย|เป็นอย่างไร|กี่บาท|มีอะไรบ้าง|ดู|ยอด|ช่วย|หา|รายงาน|ข้อมูล|ของ|ใน|ได้ไหม|คืออะไร)/iu', ' ', $query);
                $rawTokens = array_values(array_filter(array_map('trim', explode(' ', $cleanSearch)), fn($w) => mb_strlen($w) >= 2));

                $commonTerms = ['เงินเดือน', 'ค่าจ้าง', 'ค่าตอบแทน', 'ลูกหนี้', 'เจ้าหนี้', 'ค่ายา', 'ค่ารักษา', 'uc', 'สิทธิ', 'อปท', 'ประกันสังคม', 'ข้าราชการ', 'เงินยืม', 'วัสดุ', 'ค่าเสื่อม', 'รายได้', 'ค่าใช้จ่าย', 'ล่วงเวลา', 'ot', 'พกส', 'ค่าไฟ', 'ค่าน้ำ'];
                $matchedKw = null;
                foreach ($commonTerms as $term) {
                    if (mb_stripos($query, $term) !== false) {
                        $matchedKw = $term;
                        break;
                    }
                }

                $searchTerm = $matchedKw ?: ($rawTokens[0] ?? null);

                if ($searchTerm && !in_array($searchTerm, ['งบ', 'งบทดลอง'], true)) {
                    $items = DB::table('hosfin_trial_balance')
                        ->where('acc_period', $targetPeriod)
                        ->where('account_name', 'like', '%' . $searchTerm . '%')
                        ->select('account_code', 'account_name', 'debit_bf', 'credit_bf', 'debit_month', 'credit_month', 'debit_net', 'credit_net')
                        ->orderByDesc(DB::raw('GREATEST(debit_month, debit_net, credit_month, credit_net)'))
                        ->limit(12)
                        ->get();
                }
            }

            // 3. Trial Balance Overview
            if ($items->isEmpty() && preg_match('/(งบทดลอง|งบการเงิน|ดูงบ|ภาพรวมงบ|หมวดบัญชี|ผังบัญชีทั้งหมด)/iu', $query)) {
                $cats = [
                    '1' => 'หมวด 1: สินทรัพย์ (Assets)',
                    '2' => 'หมวด 2: หนี้สิน (Liabilities)',
                    '3' => 'หมวด 3: ส่วนทุน/เงินบำรุง (Equity)',
                    '4' => 'หมวด 4: รายได้ (Revenues)',
                    '5' => 'หมวด 5: ค่าใช้จ่าย (Expenses)',
                ];
                $catLines = [];
                $catLines[] = "สรุปภาพรวม 5 หมวดบัญชีในงบทดลอง (Trial Balance) งวดล่าสุด {$latestPeriod} มีดังนี้:";
                foreach ($cats as $digit => $title) {
                    $sumDebit = DB::table('hosfin_trial_balance')
                        ->where('acc_period', $latestPeriod)
                        ->where('account_code', 'like', $digit . '%')
                        ->sum('debit_net');
                    $sumCredit = DB::table('hosfin_trial_balance')
                        ->where('acc_period', $latestPeriod)
                        ->where('account_code', 'like', $digit . '%')
                        ->sum('credit_net');
                    $count = DB::table('hosfin_trial_balance')
                        ->where('acc_period', $latestPeriod)
                        ->where('account_code', 'like', $digit . '%')
                        ->count();
                    $netText = $sumDebit > $sumCredit 
                        ? ("เดบิตคงเหลือ: " . number_format($sumDebit - $sumCredit, 2) . " บาท")
                        : ("เครดิตคงเหลือ: " . number_format($sumCredit - $sumDebit, 2) . " บาท");
                    $catLines[] = "• **{$title}** (จำนวน {$count} ผังบัญชี) => {$netText}";
                }

                $topAccounts = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $latestPeriod)
                    ->orderByDesc(DB::raw('GREATEST(debit_net, credit_net)'))
                    ->limit(5)
                    ->get();
                $catLines[] = "\nรายการผังบัญชีที่มียอดคงเหลือสูงสุด 5 รายการแรกในงวดนี้:";
                foreach ($topAccounts as $t) {
                    $b = $t->debit_net > 0 ? ("เดบิต: " . number_format($t->debit_net, 2)) : ("เครดิต: " . number_format($t->credit_net, 2));
                    $catLines[] = "- [{$t->account_code}] {$t->account_name} => {$b}";
                }
                $catLines[] = "\n(ผู้ใช้สามารถสั่งดูผังบัญชีที่สนใจ เช่น 'ขอดูผังลูกหนี้ UC', 'ผังค่ายา', หรือระบุรหัสบัญชีเพื่อเจาะลึกได้)";

                return [
                    'text' => implode("\n", $catLines),
                    'period' => $latestPeriod,
                    'count' => 5,
                    'preview' => 'ภาพรวม 5 หมวดบัญชี และ 5 ผังยอดสูงสุด'
                ];
            }

            // 4. Top accounts
            if ($items->isEmpty() && preg_match('/(สูงสุด|มากที่สุด|top|5 อันดับ|ยอดเยอะ)/iu', $query)) {
                $isAsset = preg_match('/(ลูกหนี้|สินทรัพย์)/iu', $query);
                $codePrefix = $isAsset ? '1102%' : '5%';
                $items = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $latestPeriod)
                    ->where('account_code', 'like', $codePrefix)
                    ->select('account_code', 'account_name', 'debit_bf', 'credit_bf', 'debit_month', 'credit_month', 'debit_net', 'credit_net')
                    ->orderByDesc('debit_net')
                    ->limit(7)
                    ->get();
            }

            if ($items->isEmpty()) {
                return null;
            }

            $lines = [];
            $preview = '';
            foreach ($items as $idx => $row) {
                $net = $row->debit_net > 0 ? ("เดบิตคงเหลือสุทธิ: " . number_format($row->debit_net, 2) . " บาท") : ("เครดิตคงเหลือสุทธิ: " . number_format($row->credit_net, 2) . " บาท");
                $month = ($row->debit_month > 0 || $row->credit_month > 0) ? (" (ประจำเดือนนี้ เดบิต: " . number_format($row->debit_month, 2) . ", เครดิต: " . number_format($row->credit_month, 2) . ")") : "";
                $lines[] = "- ผังบัญชี [{$row->account_code}] {$row->account_name} => {$net}{$month}";
                if ($idx < 2) {
                    $preview .= ($preview ? ', ' : '') . "[{$row->account_code}] " . mb_substr($row->account_name, 0, 30);
                }
            }

            $text = "ข้อมูลงบทดลองรายผังบัญชีจริง (Trial Balance by Account Code) งวด {$periodDesc}:\n" . implode("\n", $lines);

            return [
                'text' => $text,
                'period' => $targetPeriod,
                'count' => count($items),
                'preview' => $preview
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService Trial Balance Warning: " . $e->getMessage());
            return null;
        }
    }
}
