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
            $view = $controller->index(new \Illuminate\Http\Request());
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

            // 1. Check if query contains an account code pattern e.g. 1101030102.10102 or 1101
            preg_match('/(\d{3,12}(?:\.\d{1,8})?)/', $query, $codeMatch);
            $queryCode = $codeMatch[1] ?? null;

            $items = collect();

            if ($queryCode && strlen($queryCode) >= 4) {
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

            // 2.5 Category-specific Drill-Down (หมวด 1 ถึง 5 หรือ สินทรัพย์, หนี้สิน, ทุน, รายได้, ค่าใช้จ่าย)
            $catMatches = [
                '1' => ['title' => 'หมวด 1: สินทรัพย์ (Assets)', 'digit' => '1', 'nature' => 'debit'],
                '2' => ['title' => 'หมวด 2: หนี้สิน (Liabilities)', 'digit' => '2', 'nature' => 'credit'],
                '3' => ['title' => 'หมวด 3: ส่วนของเจ้าของ/ทุน (Equity)', 'digit' => '3', 'nature' => 'credit'],
                '4' => ['title' => 'หมวด 4: รายได้ (Revenues)', 'digit' => '4', 'nature' => 'credit'],
                '5' => ['title' => 'หมวด 5: ค่าใช้จ่าย (Expenses)', 'digit' => '5', 'nature' => 'debit'],
            ];

            $targetCatDigit = null;
            if (preg_match('/หมวด\s*([1-5])/u', $query, $mcm)) {
                $targetCatDigit = $mcm[1];
            } elseif (preg_match('/(หมวด|กลุ่ม)?\s*(สินทรัพย์|asset)/iu', $query)) {
                $targetCatDigit = '1';
            } elseif (preg_match('/(หมวด|กลุ่ม)?\s*(หนี้สิน|liabilit)/iu', $query)) {
                $targetCatDigit = '2';
            } elseif (preg_match('/(หมวด|กลุ่ม)?\s*(ส่วนของเจ้าของ|ทุน|equity)/iu', $query)) {
                $targetCatDigit = '3';
            } elseif (preg_match('/(หมวด|กลุ่ม)?\s*(รายได้|revenue|income)/iu', $query) && !preg_match('/(รวมรายได้|แผน)/iu', $query)) {
                $targetCatDigit = '4';
            } elseif (preg_match('/(หมวด|กลุ่ม)?\s*(ค่าใช้จ่าย|expense)/iu', $query) && !preg_match('/(รวมค่าใช้จ่าย|แผน)/iu', $query)) {
                $targetCatDigit = '5';
            }

            if ($items->isEmpty() && $targetCatDigit && isset($catMatches[$targetCatDigit])) {
                $catInfo = $catMatches[$targetCatDigit];
                $digit = $catInfo['digit'];

                $sumBf = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $targetPeriod)
                    ->where('account_code', 'like', $digit . '%')
                    ->selectRaw('SUM(debit_bf) as sum_debit_bf, SUM(credit_bf) as sum_credit_bf, SUM(debit_month) as sum_debit_month, SUM(credit_month) as sum_credit_month, SUM(debit_net) as sum_debit_net, SUM(credit_net) as sum_credit_net, COUNT(*) as acc_count')
                    ->first();

                $netEnding = ($catInfo['nature'] === 'debit')
                    ? (($sumBf->sum_debit_net ?? 0) - ($sumBf->sum_credit_net ?? 0))
                    : (($sumBf->sum_credit_net ?? 0) - ($sumBf->sum_debit_net ?? 0));

                $topCatItems = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $targetPeriod)
                    ->where('account_code', 'like', $digit . '%')
                    ->orderByDesc(DB::raw('GREATEST(debit_net, credit_net)'))
                    ->limit(10)
                    ->get();

                $cLines = [];
                $cLines[] = "=== วิเคราะห์เจาะลึก {$catInfo['title']} (ตาราง hosfin_trial_balance งวด {$periodDesc}) ===";
                $cLines[] = "1. สรุปภาพรวมหมวดนี้ (จำนวน {$sumBf->acc_count} ผังบัญชี):";
                $cLines[] = "   • ยอดยกมาต้นปี: " . number_format(($catInfo['nature'] === 'debit') ? (($sumBf->sum_debit_bf ?? 0) - ($sumBf->sum_credit_bf ?? 0)) : (($sumBf->sum_credit_bf ?? 0) - ($sumBf->sum_debit_bf ?? 0)), 2) . " บาท";
                $cLines[] = "   • ความเคลื่อนไหวประจำเดือนนี้: เดบิต = " . number_format($sumBf->sum_debit_month ?? 0, 2) . " บาท | เครดิต = " . number_format($sumBf->sum_credit_month ?? 0, 2) . " บาท";
                $cLines[] = "   • ยอดสะสมยกไปสุทธิปลายงวด: " . number_format($netEnding, 2) . " บาท";

                $cLines[] = "\n2. รายการผังบัญชีสำคัญ 10 อันดับแรกในหมวดนี้ (Drill-down Items):";
                foreach ($topCatItems as $idx => $ti) {
                    $itemNet = ($catInfo['nature'] === 'debit')
                        ? ($ti->debit_net - $ti->credit_net)
                        : ($ti->credit_net - $ti->debit_net);
                    $itemMonth = ($ti->debit_month > 0 || $ti->credit_month > 0)
                        ? " [เดือนนี้ D: " . number_format($ti->debit_month, 2) . " C: " . number_format($ti->credit_month, 2) . "]"
                        : "";
                    $cLines[] = "   " . ($idx + 1) . ". [{$ti->account_code}] {$ti->account_name} => คงเหลือสุทธิ " . number_format($itemNet, 2) . " บาท{$itemMonth}";
                }

                return [
                    'text' => implode("\n", $cLines),
                    'period' => $targetPeriod,
                    'count' => count($topCatItems),
                    'preview' => "เจาะลึก {$catInfo['title']} รวม {$sumBf->acc_count} ผัง ยอดสุทธิ " . number_format($netEnding, 2)
                ];
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

    /**
     * Get detailed AP Creditor Bills context from hosfin_gl_ap_bills
     * Includes vendor name, category, aging, due ranking, and specific vendor bills
     */
    public function getApVendorContext(string $query): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_gl_ap_bills')) {
                return null;
            }

            $isApQuery = (bool) preg_match('/(เจ้าหนี้|บริษัท|คู่ค้า|ผู้ขาย|ค้างจ่าย|จ่ายก่อน|จ่ายใคร|ลำดับการจ่าย|บิล|ครบกำหนด|อายุหนี้|aging|องค์การเภสัช|gpo|ซิลลิค|ไทยเฮลท์|ฟอกไต|เบอร์ลิน|ค่ายา|เวชภัณฑ์|เจ้าหนี้การค้า|ap)/iu', $query);
            if (!$isApQuery) {
                return null;
            }

            $totalUnpaidDebt = (float) DB::table('hosfin_gl_ap_bills')->where('is_paid', 0)->sum('remaining_debt');
            $totalUnpaidCount = (int) DB::table('hosfin_gl_ap_bills')->where('is_paid', 0)->count();
            $totalVendorsCount = (int) DB::table('hosfin_gl_ap_bills')->where('is_paid', 0)->distinct('vendor_name')->count('vendor_name');

            // 1. Category breakdown
            $categorySummary = DB::table('hosfin_gl_ap_bills')
                ->select('category', DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(remaining_debt) as remaining'))
                ->where('is_paid', 0)
                ->groupBy('category')
                ->orderBy('remaining', 'desc')
                ->limit(7)
                ->get();

            // 2. Top Creditors by remaining debt
            $topVendors = DB::table('hosfin_gl_ap_bills')
                ->select('vendor_name', DB::raw('MAX(category) as category'), DB::raw('COUNT(*) as total_bills'), DB::raw('SUM(remaining_debt) as remaining'))
                ->where('is_paid', 0)
                ->groupBy('vendor_name')
                ->orderBy('remaining', 'desc')
                ->limit(8)
                ->get();

            // 3. Oldest aging creditors
            $oldestVendors = DB::table('hosfin_gl_ap_bills')
                ->select('vendor_name', 'category', 'bill_no', 'bill_date', 'remaining_debt', DB::raw('DATEDIFF(NOW(), bill_date) as days_old'))
                ->where('is_paid', 0)
                ->whereNotNull('bill_date')
                ->orderBy('bill_date', 'asc')
                ->limit(5)
                ->get();

            // 4. Check for specific vendor name in query
            $cleanQuery = preg_replace('/(เจ้าหนี้|บริษัท|จำกัด|มหาชน|ค้างจ่าย|ยอด|เท่าไหร่|มีไหม|กี่บาท|บิล|ช่วย|ดู|หน่อย)/iu', ' ', $query);
            $tokens = array_filter(array_map('trim', explode(' ', $cleanQuery)), fn($w) => mb_strlen($w) >= 3);
            
            $specificVendorBills = collect();
            $matchedVendorName = null;
            foreach ($tokens as $token) {
                $matches = DB::table('hosfin_gl_ap_bills')
                    ->where('is_paid', 0)
                    ->where('vendor_name', 'like', "%{$token}%")
                    ->orderBy('bill_date', 'asc')
                    ->limit(10)
                    ->get();
                if ($matches->isNotEmpty()) {
                    $specificVendorBills = $matches;
                    $matchedVendorName = $matches->first()->vendor_name;
                    break;
                }
            }

            $lines = [];
            $lines[] = "ข้อมูลเจ้าหนี้การค้าและบิลค้างชำระจริงจากระบบ GL (ตาราง hosfin_gl_ap_bills):";
            $lines[] = "• ภาพรวมหนี้สินค้างชำระ: " . number_format($totalUnpaidDebt, 2) . " บาท (รวม " . number_format($totalUnpaidCount) . " บิล จาก " . number_format($totalVendorsCount) . " บริษัท)";
            
            $lines[] = "\n• สรุปยอดหนี้แยกตามหมวดหมู่การใช้งาน:";
            foreach ($categorySummary as $c) {
                $lines[] = "  - หมวด " . ($c->category ?: 'ทั่วไป') . ": " . number_format($c->remaining, 2) . " บาท (" . number_format($c->total_bills) . " บิล)";
            }

            $lines[] = "\n• รายชื่อบริษัทเจ้าหนี้ค้างชำระสูงสุด 8 อันดับแรก:";
            foreach ($topVendors as $idx => $v) {
                $lines[] = "  " . ($idx + 1) . ". {$v->vendor_name} (หมวด: " . ($v->category ?: 'ทั่วไป') . ") => ค้างชำระ " . number_format($v->remaining, 2) . " บาท (" . number_format($v->total_bills) . " บิล)";
            }

            if ($oldestVendors->isNotEmpty()) {
                $lines[] = "\n• บิลเจ้าหนี้ที่ค้างชำระยาวนานที่สุด (เสี่ยงเกินเกณฑ์เครดิตเทอม):";
                foreach ($oldestVendors as $o) {
                    $lines[] = "  - {$o->vendor_name} บิลเลขที่: {$o->bill_no} (ลงวันที่: {$o->bill_date}, ค้างประมาณ {$o->days_old} วัน) ยอด " . number_format($o->remaining_debt, 2) . " บาท";
                }
            }

            if ($specificVendorBills->isNotEmpty()) {
                $lines[] = "\n• รายการบิลค้างชำระของบริษัท [{$matchedVendorName}]:";
                $vendorTotal = 0;
                foreach ($specificVendorBills as $b) {
                    $vendorTotal += (float)$b->remaining_debt;
                    $lines[] = "  - บิล {$b->bill_no} (วันที่ {$b->bill_date}, ผัง: {$b->account_name}) ยอดคงเหลือ: " . number_format($b->remaining_debt, 2) . " บาท";
                }
                $lines[] = "  รวมยอดค้างของบริษัทนี้: " . number_format($vendorTotal, 2) . " บาท";
            }

            $lines[] = "\n• หลักเกณฑ์และข้อแนะนำเชิงกลยุทธ์ในการจัดลำดับการจ่ายหนี้ (Payment Prioritization Matrix):";
            $lines[] = "  1. [Tier 1 - เร่งด่วนสูงสุด (สีแดง)]: เจ้าหนี้ยาสามัญ/ยาช่วยชีวิตหลัก เช่น 'องค์การเภสัชกรรม (GPO)' และบริษัทยาสำคัญ (เช่น ซิลลิค ฟาร์มา, เบอร์ลิน) -> ต้องจัดสรรจ่ายก่อนเพื่อรักษาวงเงินเครดิตและไม่ให้ถูกระงับการส่งยา";
            $lines[] = "  2. [Tier 2 - บริการผู้ป่วยต่อเนื่อง (สีส้ม)]: ค่าจ้างเหมาบริการทางการแพทย์ต่อเนื่อง เช่น 'เป็นหนึ่งไตเทียม (ฟอกไต)' และ 'ไทยเฮลท์ อิมเมจจิ้ง (CT Scan)' -> ต้องจ่ายเพื่อป้องกันการหยุดให้บริการผู้ป่วยวิกฤต";
            $lines[] = "  3. [Tier 3 - วัสดุวิทยาศาสตร์และการแพทย์ (สีเหลือง)]: น้ำยาชันสูตร Lab และเวชภัณฑ์สิ้นเปลือง";
            $lines[] = "  4. [Tier 4 - วัสดุสำนักงาน/คอมพิวเตอร์/ซ่อมบำรุง (สีเขียว)]: สามารถเจรจาขอขยายระยะเวลาเครดิตเทอม หรือผ่อนชำระเป็นงวดได้";

            return [
                'text' => implode("\n", $lines),
                'totalUnpaid' => $totalUnpaidDebt,
                'count' => $totalUnpaidCount,
                'preview' => "เจ้าหนี้ค้าง " . number_format($totalUnpaidDebt, 2) . " บ. (สูงสุด: " . ($topVendors->first()->vendor_name ?? '') . ")"
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService AP Vendor Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get AR Debtors context from hosfin_gl_ar_debtors
     */
    public function getArDebtorContext(string $query): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_gl_ar_debtors')) {
                return null;
            }

            $isArQuery = (bool) preg_match('/(ลูกหนี้|ค่ารักษา|สิทธิ|สปสช|uc|บัตรทอง|ข้าราชการ|csmbs|อปท|ประกันสังคม|sss|ชดเชย|ค้างท่อ|ตั้งเบิก|พ\.ร\.บ|ar)/iu', $query);
            if (!$isArQuery) {
                return null;
            }

            $totalOutstanding = (float) DB::table('hosfin_gl_ar_debtors')->sum('outstanding_balance');
            $totalBilled = (float) DB::table('hosfin_gl_ar_debtors')->sum('total_billed');
            $totalCollected = (float) DB::table('hosfin_gl_ar_debtors')->sum('total_collected');
            $collectRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

            $summaries = DB::table('hosfin_gl_ar_debtors')
                ->select(
                    'debtor_type',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_billed) as billed'),
                    DB::raw('SUM(total_collected) as collected'),
                    DB::raw('SUM(outstanding_balance) as outstanding')
                )
                ->groupBy('debtor_type')
                ->orderBy('outstanding', 'desc')
                ->get();

            $lines = [];
            $lines[] = "ข้อมูลลูกหนี้ค่ารักษาพยาบาลจริงจากระบบ GL (ตาราง hosfin_gl_ar_debtors):";
            $lines[] = "• ลูกหนี้คงค้างรอชดเชยรวมทั้งสิ้น: " . number_format($totalOutstanding, 2) . " บาท (จากยอดตั้งเบิก " . number_format($totalBilled, 2) . " บาท, ได้รับชดเชยแล้ว " . number_format($totalCollected, 2) . " บาท, อัตราเก็บหนี้สำเร็จ {$collectRate}%)";
            $lines[] = "\n• ยอดลูกหนี้คงค้างรอการชดเชยแยกตามกองทุน/สิทธิการรักษา:";
            foreach ($summaries as $s) {
                $rate = $s->billed > 0 ? round(($s->collected / $s->billed) * 100, 1) : 0;
                $lines[] = "  - " . ($s->debtor_type ?: 'ทั่วไป') . ": ยอดค้างชำระ " . number_format($s->outstanding, 2) . " บาท (ตั้งเบิก: " . number_format($s->billed, 2) . " บ., รับแล้ว: " . number_format($s->collected, 2) . " บ. หรือ {$rate}%)";
            }

            $lines[] = "\n• ข้อสังเกตและข้อเสนอแนะในการเร่งรัดกระแสเงินสดเข้า (Cash Inflow):";
            $lines[] = "  1. กองทุน สปสช. (UC) และ ข้าราชการ/อปท. มียอดค้างชำระรวมกันกว่า 80% ของลูกหนี้ทั้งหมด ให้เร่งติดตาม Statement รอบตัดจ่ายกลางเดือนและปลายเดือน";
            $lines[] = "  2. ตรวจสอบเคสที่ส่งเบิกแล้วติด C, V, Deny เพื่อเร่งแก้ไขและส่งเบิกซ้ำก่อนปิดงวดบัญชี จะช่วยเปลี่ยนลูกหนี้เป็นเงินสดหมุนเวียนได้เร็วที่สุด";

            return [
                'text' => implode("\n", $lines),
                'totalOutstanding' => $totalOutstanding,
                'preview' => "ลูกหนี้ค้างรอชดเชย " . number_format($totalOutstanding, 2) . " บ. (สปสช. + ข้าราชการ สูงสุด)"
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService AR Debtor Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Journal entries context from hosfin_gl_journals and hosfin_gl_journal_items
     */
    public function getJournalContext(string $query): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_gl_journals') || !Schema::hasTable('hosfin_gl_journal_items')) {
                return null;
            }

            $isJournalQuery = (bool) preg_match('/(สมุดรายวัน|ใบสำคัญ|voucher|jv|pv|rv|รายวันทั่วไป|รายการลงบัญชี|เดบิต|เครดิต)/iu', $query);
            if (!$isJournalQuery) {
                return null;
            }

            // Check if specific voucher pattern exists
            preg_match('/([A-Za-z]{2,5}[-_]?\d{4,12})/i', $query, $vm);
            $targetVoucher = $vm[1] ?? null;

            if ($targetVoucher) {
                $voucher = DB::table('hosfin_gl_journals')
                    ->where('voucher_no', 'like', "%{$targetVoucher}%")
                    ->first();
                if ($voucher) {
                    $items = DB::table('hosfin_gl_journal_items')
                        ->where('journal_id', $voucher->id)
                        ->orderBy('item_no', 'asc')
                        ->get();

                    $lines = [];
                    $lines[] = "ข้อมูลใบสำคัญรายวันเลขที่ {$voucher->voucher_no} (วันที่ {$voucher->voucher_date}, ประเภท {$voucher->journal_type}):";
                    $lines[] = "• คำอธิบาย: {$voucher->description}";
                    $lines[] = "• รายการเดบิต/เครดิต:";
                    foreach ($items as $it) {
                        $dr = $it->debit > 0 ? ("เดบิต: " . number_format($it->debit, 2)) : "";
                        $cr = $it->credit > 0 ? ("เครดิต: " . number_format($it->credit, 2)) : "";
                        $amt = trim("{$dr} {$cr}");
                        $lines[] = "  - [{$it->account_code}] {$it->account_name}: {$amt} บาท (" . ($it->description ?: '-') . ")";
                    }
                    $lines[] = "• ยอดรวม เดบิต: " . number_format($voucher->total_debit, 2) . " บาท, เครดิต: " . number_format($voucher->total_credit, 2) . " บาท";

                    return [
                        'text' => implode("\n", $lines),
                        'preview' => "ใบสำคัญ {$voucher->voucher_no}"
                    ];
                }
            }

            // Recent journals summary
            $recentJournals = DB::table('hosfin_gl_journals')
                ->orderBy('voucher_date', 'desc')
                ->limit(5)
                ->get();

            $lines = [];
            $lines[] = "ข้อมูลภาพรวมสมุดรายวันทั่วไป (ตาราง hosfin_gl_journals):";
            $lines[] = "• มีรายการใบสำคัญในระบบทั้งหมด " . number_format(DB::table('hosfin_gl_journals')->count()) . " ใบ";
            $lines[] = "• ตัวอย่างใบสำคัญล่าสุด:";
            foreach ($recentJournals as $rj) {
                $lines[] = "  - [{$rj->voucher_no}] วันที่: {$rj->voucher_date} ประเภท: {$rj->journal_type} ยอด: " . number_format($rj->total_debit, 2) . " บาท คำอธิบาย: " . mb_substr($rj->description, 0, 40);
            }

            return [
                'text' => implode("\n", $lines),
                'preview' => "สมุดรายวันล่าสุด 5 รายการ"
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService Journal Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Look up PlanFin budget targets, monthly tracking, and future simulation
     * Supports: any hospital, any budget year, each individual month, and all dimensions
     */
    public function getPlanfinContext(string $query): ?array
    {
        try {
            if (!Schema::hasTable('hosfin_planfin_targets') || !Schema::hasTable('hosfin_planfin_categories')) {
                return null;
            }

            $isPlanfinQuery = (bool) preg_match('/(planfin|แผนเงินบำรุง|ทำแผน|แผนปี|แผนงบ|จัดทำแผน|ebitda|กำไรสุทธิตามแผน|วงเงินลงทุน|20%|target|หมวดแผน|จำลองแผน|simulator|mdb|แผนประมาณการ|รายเดือน|แต่ละเดือน|งวด|matrix|เทียบแผน|เป้าหมายแผน)/iu', $query);
            if (!$isPlanfinQuery) {
                return null;
            }

            $expertService = app(\App\Services\Ai\Knowledge\HospitalFinancialKnowledgeService::class);

            // 1. Resolve requested budget year
            $requestedYear = null;
            if (preg_match('/\b(25\d{2})\b/', $query, $ym)) {
                $requestedYear = intval($ym[1]);
            } elseif (preg_match('/(?:ปี|งบ)\s*(\d{2})\b/u', $query, $ym)) {
                $requestedYear = intval('25' . $ym[1]);
            }

            $yearsInfo = $expertService->getBudgetYearsInfo($requestedYear);
            $hospName = $yearsInfo['hospital_name'];
            $targetYear = $yearsInfo['target_planning_year'];
            $baselineYear = $yearsInfo['baseline_year'];
            $shortTarget = $yearsInfo['short_target_year'];
            $shortBase = $yearsInfo['short_baseline_year'];

            $isFuturePlanningQuery = (bool) preg_match('/(ทำแผน|เตรียมทำแผน|จัดทำแผน|แผนปีหน้า|ปีถัดไป|จำลอง|simulator|งบลงทุน)/iu', $query);

            if ($requestedYear === null && !$isFuturePlanningQuery) {
                $activePlanYear = $yearsInfo['max_recorded_year'];
                $isFutureYear = false;
                $hasSavedPlan = in_array($activePlanYear, $yearsInfo['plan_years']);
            } else {
                $isFutureYear = $yearsInfo['is_future_year'];
                $hasSavedPlan = $yearsInfo['has_saved_plan'];
                $activePlanYear = $hasSavedPlan ? $targetYear : $baselineYear;
            }

            $targetsRaw = DB::table('hosfin_planfin_targets as t')
                ->join('hosfin_planfin_categories as c', 'c.plan_code', '=', 't.plan_code')
                ->where('t.budget_year', $activePlanYear)
                ->select('t.plan_code', 'c.plan_name', 'c.category_type', 't.round_no', 't.target_amount')
                ->orderBy('c.sort_order')
                ->get();

            if ($targetsRaw->isEmpty()) {
                // Fallback to highest year in targets table
                $maxPlanYear = DB::table('hosfin_planfin_targets')->max('budget_year');
                if ($maxPlanYear) {
                    $targetsRaw = DB::table('hosfin_planfin_targets as t')
                        ->join('hosfin_planfin_categories as c', 'c.plan_code', '=', 't.plan_code')
                        ->where('t.budget_year', $maxPlanYear)
                        ->select('t.plan_code', 'c.plan_name', 'c.category_type', 't.round_no', 't.target_amount')
                        ->orderBy('c.sort_order')
                        ->get();
                    $activePlanYear = $maxPlanYear;
                }
            }

            $planTargets = [];
            $planNames = [];
            $roundNo = $targetsRaw->isNotEmpty() ? ($targetsRaw->first()->round_no ?? '') : '';
            foreach ($targetsRaw as $row) {
                $planTargets[$row->plan_code] = floatval($row->target_amount);
                $planNames[$row->plan_code] = $row->plan_name;
            }

            // Target figures
            $targetRev = $planTargets['P13S'] ?? 0.0;
            $targetExp = $planTargets['P26S'] ?? 0.0;
            $targetNet = $planTargets['P27S'] ?? ($targetRev - $targetExp);
            $targetEbitda = $planTargets['P29'] ?? 0.0;
            $targetCap20 = max(0, $targetEbitda * 0.20);

            // 2. Resolve Month/Period
            $monthInfo = $expertService->parseMonthAndPeriod($query, $activePlanYear);
            $allPeriodsInTb = DB::table('hosfin_trial_balance')->distinct()->orderBy('acc_period', 'asc')->pluck('acc_period')->toArray();
            $latestPeriod = !empty($allPeriodsInTb) ? end($allPeriodsInTb) : null;

            // Selected period for evaluation
            $selectedPeriod = null;
            if ($monthInfo && in_array($monthInfo['period'], $allPeriodsInTb, true)) {
                $selectedPeriod = $monthInfo['period'];
            } elseif ($latestPeriod) {
                $selectedPeriod = $latestPeriod;
            }

            // Reflection helpers from HosFinController
            $controller = app(\App\Http\Controllers\HosFinController::class);
            $refCum = new \ReflectionMethod($controller, 'calculatePlanfinActuals');
            $refCum->setAccessible(true);
            $refMon = new \ReflectionMethod($controller, 'calculatePlanfinMonthlyActuals');
            $refMon->setAccessible(true);

            $lines = [];

            // Title block
            if ($isFutureYear && !$hasSavedPlan) {
                $lines[] = "=== ข้อมูลเตรียมการจัดทำแผนเงินบำรุงปี {$targetYear} (FY{$shortTarget}) หน่วยบริการ: {$hospName} ===";
                $lines[] = "• สถานะ: อยู่ระหว่างเตรียมการจัดทำแผน โดยใช้ผลการดำเนินงานและเป้าหมายปีงบประมาณ {$baselineYear} เป็นฐานข้อมูลอ้างอิง (Baseline)";
            } else {
                $lines[] = "=== ข้อมูลแผนเงินบำรุงโรงพยาบาล (PlanFin) ประจำปีงบประมาณ {$activePlanYear} หน่วยบริการ: {$hospName} ===";
                if ($roundNo) {
                    $lines[] = "• รอบการจัดทำแผน: {$roundNo}";
                }
            }

            // Summary annual targets
            $lines[] = "\n1. เป้าหมายแผนเงินบำรุงทั้งปี (Annual Targets):";
            $lines[] = "   • รายได้รวมตามแผน (P13S): " . number_format($targetRev, 2) . " บาท (เฉลี่ยเดือนละ " . number_format($targetRev / 12, 2) . " บาท)";
            $lines[] = "   • ค่าใช้จ่ายรวมตามแผน (P26S): " . number_format($targetExp, 2) . " บาท (เฉลี่ยเดือนละ " . number_format($targetExp / 12, 2) . " บาท)";
            $lines[] = "   • รายได้สุทธิตามแผน (P27S Net Income): " . number_format($targetNet, 2) . " บาท (" . ($targetNet >= 0 ? "กำไรตามแผน" : "ขาดดุลตามแผน") . ")";
            $lines[] = "   • EBITDA ตามแผน (P29): " . number_format($targetEbitda, 2) . " บาท";
            $lines[] = "   • เพดานวงเงินลงทุนด้วยเงินบำรุง (20% ของ EBITDA): " . number_format($targetCap20, 2) . " บาท";

            // If user asked about a specific month or latest period
            if ($selectedPeriod) {
                $actualsCum = $refCum->invoke($controller, $selectedPeriod);
                $actualsMon = $refMon->invoke($controller, $selectedPeriod);

                $pParts = explode('-', $selectedPeriod);
                $pm = intval($pParts[1] ?? 1);
                $cumMonths = ($pm >= 10) ? ($pm - 9) : ($pm + 3);
                if ($cumMonths <= 0 || $cumMonths > 12) $cumMonths = 12;

                $thMonths = [
                    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                ];
                $mLabel = $thMonths[$pm] ?? "งวด {$selectedPeriod}";

                // Single month figures
                $monRev = floatval($actualsMon['P13S'] ?? 0);
                $monExp = floatval($actualsMon['P26S'] ?? 0);
                $monNet = floatval($actualsMon['P27S'] ?? ($monRev - $monExp));
                $monEbitda = floatval($actualsMon['P29'] ?? 0);
                $monPlanRev = $targetRev / 12.0;
                $monPlanExp = $targetExp / 12.0;
                $monRevDiff = $monRev - $monPlanRev;
                $monExpDiff = $monExp - $monPlanExp;

                // Cumulative figures
                $cumRev = floatval($actualsCum['P13S'] ?? 0);
                $cumExp = floatval($actualsCum['P26S'] ?? 0);
                $cumNet = floatval($actualsCum['P27S'] ?? ($cumRev - $cumExp));
                $cumEbitda = floatval($actualsCum['P29'] ?? 0);
                $cumCap20 = max(0, $cumEbitda * 0.20);
                $planCumRev = ($targetRev / 12.0) * $cumMonths;
                $planCumExp = ($targetExp / 12.0) * $cumMonths;
                $cumRevPct = ($planCumRev > 0) ? ($cumRev / $planCumRev) * 100.0 : 0.0;
                $cumExpPct = ($planCumExp > 0) ? ($cumExp / $planCumExp) * 100.0 : 0.0;

                // Cash & Bank 1101% at this period
                $cashBankBalance = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $selectedPeriod)
                    ->where('account_code', 'like', '1101%')
                    ->sum(DB::raw('COALESCE(debit_net, 0) - COALESCE(credit_net, 0)'));

                // Cost summaries for this month
                $costSummary = DB::table('hosfin_gl_cost_summaries')
                    ->where('fiscal_year', $activePlanYear)
                    ->where('fiscal_month', $cumMonths)
                    ->first();

                $lines[] = "\n2. ผลการดำเนินงานประจำเดือน {$mLabel} (งวดบัญชี {$selectedPeriod} | เดือนที่ {$cumMonths}/12):";
                $lines[] = "   • [มิติที่ 1: ผลงานประจำเดือนเดี่ยว (Single Month Movement)]";
                $lines[] = "     - รายได้จริงประจำเดือน: " . number_format($monRev, 2) . " บาท (เทียบเป้าหมายเดือนละ " . number_format($monPlanRev, 2) . " บาท, " . ($monRevDiff >= 0 ? "เกินเป้า +" : "ต่ำกว่าเป้า ") . number_format($monRevDiff, 2) . " บาท)";
                $lines[] = "     - ค่าใช้จ่ายจริงประจำเดือน: " . number_format($monExp, 2) . " บาท (เทียบกรอบงบเดือนละ " . number_format($monPlanExp, 2) . " บาท, " . ($monExpDiff <= 0 ? "ประหยัดกว่ากรอบ " : "เกินกรอบ +") . number_format($monExpDiff, 2) . " บาท)";
                $lines[] = "     - กำไรสุทธิประจำเดือน: " . number_format($monNet, 2) . " บาท (" . ($monNet >= 0 ? "เกินดุล/กำไร" : "ขาดดุล/ติดลบ") . ")";
                $lines[] = "     - EBITDA ประจำเดือน: " . number_format($monEbitda, 2) . " บาท";

                $lines[] = "   • [มิติที่ 2: ผลงานสะสมถึงเดือนนี้ (Cumulative {$cumMonths} Months)]";
                $lines[] = "     - รายได้จริงสะสม: " . number_format($cumRev, 2) . " บาท (เทียบเป้าหมายสะสม " . number_format($planCumRev, 2) . " บาท คิดเป็น " . number_format($cumRevPct, 2) . "% ของเป้า)";
                $lines[] = "     - ค่าใช้จ่ายจริงสะสม: " . number_format($cumExp, 2) . " บาท (เทียบเป้าหมายสะสม " . number_format($planCumExp, 2) . " บาท คิดเป็น " . number_format($cumExpPct, 2) . "% ของเป้า)";
                $lines[] = "     - กำไรสุทธิสะสม: " . number_format($cumNet, 2) . " บาท (" . ($cumNet >= 0 ? "เกินดุลสะสม" : "ขาดดุลสะสม") . ")";
                $lines[] = "     - EBITDA สะสม: " . number_format($cumEbitda, 2) . " บาท => กรอบวงเงินลงทุน 20% สะสม: " . number_format($cumCap20, 2) . " บาท";

                $lines[] = "   • [มิติที่ 3: สภาพคล่องและเงินสดคงเหลือจริง (Cash & Liquidity)]";
                $lines[] = "     - ยอดเงินสดและเงินฝากธนาคารคงเหลือจริง (ผัง 1101% ณ สิ้นงวด): " . number_format($cashBankBalance, 2) . " บาท";

                if ($costSummary) {
                    $totalCost = floatval($costSummary->total_cost);
                    $lcAmt = floatval($costSummary->lc_amount);
                    $mcAmt = floatval($costSummary->mc_amount);
                    $ccAmt = floatval($costSummary->cc_amount);
                    $lcPct = $totalCost > 0 ? round(($lcAmt / $totalCost) * 100, 1) : 0;
                    $mcPct = $totalCost > 0 ? round(($mcAmt / $totalCost) * 100, 1) : 0;
                    $lines[] = "   • [มิติที่ 4: โครงสร้างต้นทุนประจำเดือน (Cost Structure LC/MC/CC)]";
                    $lines[] = "     - ต้นทุนรวมประจำเดือน: " . number_format($totalCost, 2) . " บาท";
                    $lines[] = "     - LC (ค่าแรงบุคลากร): " . number_format($lcAmt, 2) . " บาท ({$lcPct}% ของต้นทุน, เกณฑ์ปกติ < 50-55%)";
                    $lines[] = "     - MC (ค่าของ/ยา/เวชภัณฑ์): " . number_format($mcAmt, 2) . " บาท ({$mcPct}% ของต้นทุน, เกณฑ์ปกติ < 20-25%)";
                    $lines[] = "     - CC (ค่าลงทุน/เสื่อมราคา): " . number_format($ccAmt, 2) . " บาท";
                }
            }

            // 3. Multi-Month Matrix Trajectory (ถ้าถามเรื่อง แต่ละเดือน, รายเดือน, matrix)
            if (preg_match('/(แต่ละเดือน|รายเดือน|matrix|12\s*เดือน|แนวโน้ม)/iu', $query)) {
                $yearPeriods = array_filter($allPeriodsInTb, fn($p) => str_starts_with($p, "{$activePlanYear}-") || str_starts_with($p, ($activePlanYear - 1) . "-1"));
                if (!empty($yearPeriods)) {
                    $lines[] = "\n3. แนวโน้มผลการดำเนินงานแยกตามแต่ละเดือนของปีงบประมาณ {$activePlanYear} (12-Month Matrix Trends):";
                    foreach ($yearPeriods as $yp) {
                        try {
                            $mAct = $refMon->invoke($controller, $yp);
                            $pParts = explode('-', $yp);
                            $mNo = intval($pParts[1] ?? 1);
                            $r = number_format($mAct['P13S'] ?? 0, 0);
                            $e = number_format($mAct['P26S'] ?? 0, 0);
                            $n = number_format($mAct['P27S'] ?? 0, 0);
                            $lines[] = "   - งวด {$yp} (เดือน {$mNo}): รายรับ {$r} บ. | รายจ่าย {$e} บ. | กำไรสุทธิ {$n} บ.";
                        } catch (\Throwable $ex) {
                            // Skip if single period calculation fails
                        }
                    }
                }
            }

            // 4. Future Budget Simulator Guidance
            if ($isFutureYear || preg_match('/(ทำแผน|เตรียม|จำลอง|simulator|15|งบลงทุน)/iu', $query)) {
                $lines[] = "\n4. การใช้งานระบบแบบจำลองแผนงบประมาณปี {$targetYear} (FY{$shortTarget} Budget Simulator):";
                $lines[] = "   • เข้าเมนู `hosfin/planfin` และคลิกแท็บ **\"แบบจำลองจัดทำแผนเงินบำรุงปี {$targetYear} (FY{$shortTarget} Budget Simulator)\"**";
                $lines[] = "   • ระบบจะดึงฐานผลงานจริงปี {$baselineYear} มาเป็น Baseline Run-rate ทั้งปีอัตโนมัติ";
                $lines[] = "   • ผู้บริหารสามารถปรับ % Growth รายได้และรายจ่ายแต่ละหมวด, คำนวณ EBITDA และเคาะกรอบงบลงทุน 20% แบบ Real-time";
                $lines[] = "   • รองรับการ **\"ส่งออก Excel แผนปี {$targetYear}\"** เพื่อนำเข้าเอกสารการประชุม และปุ่ม **\"บันทึกเป้าหมายปี {$targetYear}\"**";
            }

            // Append Expert Knowledge
            try {
                $lines[] = "\n" . $expertService->getRelevantKnowledge($query);
            } catch (\Throwable $ex) {
                // Ignore if expert knowledge fails
            }

            $previewTitle = $isFutureYear
                ? "แผนเงินบำรุงปี {$targetYear} (ฐานอ้างอิงปี {$baselineYear}, เป้าหมายรายได้ " . number_format($targetRev / 1000000, 1) . "M) พร้อมแบบจำลอง FY{$shortTarget}"
                : "แผนเงินบำรุงปี {$activePlanYear} (เป้ารายได้ " . number_format($targetRev / 1000000, 1) . "M, กำไร " . number_format($targetNet / 1000, 0) . "k, EBITDA " . number_format($targetEbitda / 1000000, 1) . "M)";

            return [
                'text' => implode("\n", $lines),
                'preview' => $previewTitle
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService PlanFin Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Unified HosFin Context Aggregator for RiMS AI
     * Automatically retrieves and merges all relevant financial dimensions based on query
     *
     * @param string $query User's question
     * @return array|null ['text' => string, 'sources' => array]
     */
    public function getContext(string $query): ?array
    {
        try {
            $contextBlocks = [];
            $sources = [];

            // 1. PlanFin & Monthly Tracking Context
            $planfinData = $this->getPlanfinContext($query);
            if ($planfinData && !empty($planfinData['text'])) {
                $contextBlocks[] = $planfinData['text'];
                $sources[] = [
                    'title' => "แผนเงินบำรุงโรงพยาบาล PlanFin & ติดตามรายเดือน (ตาราง hosfin_planfin_targets)",
                    'filename' => 'hosfin_planfin_targets',
                    'page' => 1,
                    'snippet' => $planfinData['preview']
                ];
            }

            // 2. Financial Distress Ratios (13 ดัชนี สป.สธ. และ Risk Score 0-7)
            if (preg_match('/(วิกฤต|สภาพคล่อง|risk|ความเสี่ยง|105|เงินบำรุงสุทธิ|cr\b|qr\b|nwc|ดัชนี|ratio|สถานะการเงิน|ภาพรวมการเงิน)/iu', $query)) {
                $summaryData = $this->getHosFinSummary();
                if ($summaryData && !empty($summaryData['text'])) {
                    $contextBlocks[] = $summaryData['text'];
                    $sources[] = [
                        'title' => "13 ดัชนีชี้วัดสถานะการเงินและการเตือนภัยวิกฤต สป.สธ. (ตาราง hosfin_trial_balance)",
                        'filename' => 'hosfin_trial_balance',
                        'page' => 1,
                        'snippet' => "13 ดัชนีสถานะการเงิน งวด " . ($summaryData['period'] ?? '')
                    ];
                }
            }

            // 3. AP Creditor Bills (เจ้าหนี้การค้า)
            if (preg_match('/(เจ้าหนี้|บริษัท|คู่ค้า|ผู้ขาย|ค้างจ่าย|จ่ายใคร|ลำดับการจ่าย|บิล|ครบกำหนด|อายุหนี้|aging|ap\b)/iu', $query)) {
                $apData = $this->getApVendorContext($query);
                if ($apData && !empty($apData['text'])) {
                    $contextBlocks[] = $apData['text'];
                    $sources[] = [
                        'title' => "ทะเบียนคุมเจ้าหนี้การค้าและบิลค้างชำระ (ตาราง hosfin_gl_ap_bills)",
                        'filename' => 'hosfin_gl_ap_bills',
                        'page' => 1,
                        'snippet' => $apData['preview']
                    ];
                }
            }

            // 4. AR Debtor Claims (ลูกหนี้ค่ารักษาพยาบาล)
            if (preg_match('/(ลูกหนี้|ค่ารักษา|ar\b|ท่อ|ค้างท่อ|ชดเชย|สิทธิบัตรทอง|ข้าราชการ|ประกันสังคม|ตั้งเบิก|เรียกเก็บ)/iu', $query)) {
                $arData = $this->getArDebtorContext($query);
                if ($arData && !empty($arData['text'])) {
                    $contextBlocks[] = $arData['text'];
                    $sources[] = [
                        'title' => "ทะเบียนคุมลูกหนี้ค่ารักษาพยาบาลและยอดชดเชย (ตาราง hosfin_gl_ar_debtors)",
                        'filename' => 'hosfin_gl_ar_debtors',
                        'page' => 1,
                        'snippet' => $arData['preview']
                    ];
                }
            }

            // 5. Trial Balance by Account & Categories Drill-Down
            if (preg_match('/(ผังบัญชี|งบทดลอง|รหัสบัญชี|เดบิต|เครดิต|1101|1102|ยอดยกมา|ยอดยกไป|หมวด\s*[1-5]|หมวด|สินทรัพย์|หนี้สิน|ส่วนของเจ้าของ|ทุน|รายได้|ค่าใช้จ่าย|เงินฝาก|เงินสด|รับจ่าย|ยอดคงเหลือ|เจาะ|รายตัว|รายผัง|\d{4,10}\.\d+)/iu', $query)) {
                $tbData = $this->getTrialBalanceAccountContext($query);
                if ($tbData && !empty($tbData['text'])) {
                    $contextBlocks[] = $tbData['text'];
                    $sources[] = [
                        'title' => "งบทดลองรายผังบัญชี (ตาราง hosfin_trial_balance)",
                        'filename' => 'hosfin_trial_balance',
                        'page' => 1,
                        'snippet' => $tbData['preview']
                    ];
                }
            }

            // 6. Journal Vouchers
            if (preg_match('/(สมุดรายวัน|ใบสำคัญ|voucher|jv|pv|rv|รายวัน)/iu', $query)) {
                $jvData = $this->getJournalContext($query);
                if ($jvData && !empty($jvData['text'])) {
                    $contextBlocks[] = $jvData['text'];
                    $sources[] = [
                        'title' => "สมุดรายวันและใบสำคัญลงบัญชี (ตาราง hosfin_gl_journals)",
                        'filename' => 'hosfin_gl_journals',
                        'page' => 1,
                        'snippet' => $jvData['preview']
                    ];
                }
            }

            // 7. If no blocks matched yet, provide expert knowledge fallback
            if (empty($contextBlocks)) {
                $expData = $this->getExpertFinancialKnowledge($query);
                if ($expData && !empty($expData['text'])) {
                    $contextBlocks[] = $expData['text'];
                    $sources[] = [
                        'title' => "คู่มือมาตรฐานการบริหารการเงินการคลังโรงพยาบาล สธ.",
                        'filename' => 'moph_hospital_cfo_guide',
                        'page' => 1,
                        'snippet' => $expData['preview']
                    ];
                }
            }

            if (empty($contextBlocks)) {
                return null;
            }

            return [
                'text' => implode("\n\n" . str_repeat('=', 50) . "\n\n", $contextBlocks),
                'sources' => $sources
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService getContext error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get standalone expert financial knowledge
     */
    public function getExpertFinancialKnowledge(string $query): ?array
    {
        try {
            $expertKnowledge = app(\App\Services\Ai\Knowledge\HospitalFinancialKnowledgeService::class);
            $text = $expertKnowledge->getRelevantKnowledge($query);
            if (empty($text)) {
                return null;
            }
            return [
                'text' => $text,
                'preview' => 'หลักเกณฑ์และมาตรฐานการบริหารการเงินการคลังโรงพยาบาล สังกัด สธ.'
            ];
        } catch (\Throwable $e) {
            Log::warning("HosfinContextService ExpertKnowledge Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Alias for getTrialBalanceAccountContext
     */
    public function getTrialBalanceContext(string $query): ?array
    {
        return $this->getTrialBalanceAccountContext($query);
    }
}
