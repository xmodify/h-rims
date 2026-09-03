<?php

namespace App\Services\Ai\Context;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HosxpContextService
{
    /**
     * Get HOSxP Master Data & 16-Files Lookup Context based on user query
     *
     * @param string $query User's question
     * @return array|null ['text' => string, 'sources' => array]
     */
    public function getContext(string $query): ?array
    {
        try {
            // Check if HOSxP connection is accessible
            if (!$this->isHosxpConnected()) {
                return null;
            }

            $contextBlocks = [];
            $sources = [];

            // 1. Check for specific medical items / services (nondrugitems)
            $nondrugData = $this->getNondrugItemContext($query);
            if ($nondrugData) {
                $contextBlocks[] = $nondrugData['text'];
                $sources[] = $nondrugData['source'];
            }

            // 2. Check for ADP Lookup / Types / Missing ADP items
            $adpData = $this->getAdpLookupContext($query);
            if ($adpData) {
                $contextBlocks[] = $adpData['text'];
                $sources[] = $adpData['source'];
            }

            // 3. Check for Income category mappings
            $incomeData = $this->getIncomeCategoryContext($query);
            if ($incomeData) {
                $contextBlocks[] = $incomeData['text'];
                $sources[] = $incomeData['source'];
            }

            if (empty($contextBlocks)) {
                return null;
            }

            return [
                'text' => implode("\n\n", $contextBlocks),
                'sources' => $sources
            ];

        } catch (\Throwable $e) {
            Log::warning("HosxpContextService Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if HOSxP database connection is alive
     */
    public function isHosxpConnected(): bool
    {
        try {
            static $isConnected = null;
            if ($isConnected !== null) {
                return $isConnected;
            }

            $test = DB::connection('hosxp')->select("SELECT 1 as alive");
            $isConnected = !empty($test);
            return $isConnected;
        } catch (\Throwable $e) {
            Log::warning("HOSxP Database Connection Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search nondrugitems (ค่ารักษาพยาบาล/ค่าบริการ/เวชภัณฑ์มิใช่ยา)
     */
    public function getNondrugItemContext(string $query): ?array
    {
        try {
            // Match icode pattern (e.g. 3003941 or numbers 5-7 digits)
            preg_match('/(?:icode\s*[:=]?\s*|\b)([3-9]\d{5,6})\b/i', $query, $codeMatch);
            $targetIcode = $codeMatch[1] ?? null;

            // Check if asking for items missing ADP code (เช่น "ยังไม่ได้ผูก adp", "ไม่มี adp", "ขาด adp")
            $isMissingAdpQuery = (bool) preg_match('/(ไม่มี\s*adp|ขาด\s*adp|ไม่(ได้)?ผูก\s*adp|ไม่มีรหัส\s*adp|adp.*(ว่าง|หาย|ขาด))/iu', $query);
            if ($isMissingAdpQuery) {
                $totalActive = DB::connection('hosxp')->table('nondrugitems')->where('istatus', 'Y')->count();
                $missingItems = DB::connection('hosxp')->table('nondrugitems as n')
                    ->leftJoin('income as i', 'i.income', '=', 'n.income')
                    ->where('n.istatus', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('n.nhso_adp_code')->orWhere('n.nhso_adp_code', '');
                    })
                    ->select('n.icode', 'n.name', 'n.price', 'n.income', 'i.name as income_name')
                    ->limit(10)
                    ->get();

                $missingCount = DB::connection('hosxp')->table('nondrugitems')
                    ->where('istatus', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('nhso_adp_code')->orWhere('nhso_adp_code', '');
                    })
                    ->count();

                $lines = [];
                $lines[] = "สถิติรายการค่ารักษาพยาบาล (nondrugitems) ใน HOSxP ที่ยังไม่ได้ผูกรหัส NHSO ADP Code:";
                $lines[] = "• รายการที่เปิดใช้งานทั้งหมด: {$totalActive} รายการ";
                $lines[] = "• รายการที่ยังไม่มีรหัส ADP: {$missingCount} รายการ (" . round(($missingCount / max(1, $totalActive)) * 100, 1) . "%)";
                $lines[] = "• ตัวอย่างรายการที่ยังไม่ได้ใส่รหัส ADP (10 รายการแรก):";
                foreach ($missingItems as $idx => $m) {
                    $lines[] = "  " . ($idx + 1) . ". [{$m->icode}] {$m->name} (หมวด income: [{$m->income}] {$m->income_name}, ราคา OPD: {$m->price} บาท)";
                }
                $lines[] = "(แนะนำ: ให้ผู้ดูแลระบบเข้าไปที่ HOSxP เมนูตั้งค่าค่ารักษาพยาบาล เพื่อใส่รหัส nhso_adp_code และ nhso_adp_type_id ให้ครบถ้วน)";

                return [
                    'text' => "[ข้อมูลรายการที่ยังไม่ได้ผูกรหัส ADP ใน HOSxP]:\n" . implode("\n", $lines),
                    'source' => [
                        'title' => "รายการ HOSxP ที่ยังไม่ผูกรหัส ADP ({$missingCount} รายการ)",
                        'filename' => 'hosxp_nondrugitems_missing_adp',
                        'page' => 1,
                        'score' => 99.0,
                        'snippet' => "พบ {$missingCount} รายการจากทั้งหมด {$totalActive} รายการที่ยังไม่มี nhso_adp_code"
                    ]
                ];
            }

            // Search by specific icode or keywords in item name
            $items = collect();
            $searchTermUsed = '';

            if ($targetIcode) {
                $searchTermUsed = "รหัส icode: {$targetIcode}";
                $items = DB::connection('hosxp')->table('nondrugitems as n')
                    ->leftJoin('income as i', 'i.income', '=', 'n.income')
                    ->leftJoin('nhso_adp_type as t', 't.nhso_adp_type_id', '=', 'n.nhso_adp_type_id')
                    ->where('n.icode', $targetIcode)
                    ->select([
                        'n.icode', 'n.name', 'n.price', 'n.ipd_price', 'n.unitcost',
                        'n.income', 'i.name as income_name',
                        'n.nhso_adp_code', 'n.nhso_adp_type_id', 't.nhso_adp_type_name',
                        'n.billcode', 'n.billnumber', 'n.ucef_code', 'n.csmbs_claim_cat',
                        'n.sks_coverage_price', 'n.enable_sks_opd', 'n.enable_sks_ipd', 'n.istatus'
                    ])
                    ->get();
            } else {
                // Extract keywords (e.g. สายยาง, น้ำตาล, DTX, ทำแผล, กายภาพ, เอกซเรย์, อัลตราซาวด์)
                $cleanSearch = preg_replace('/(ขอดู|ขอ|ช่วย|อยากรู้|สอบถาม|ข้อมูล|การตั้งค่า|การผูก|ตรวจ|เช็ค|ดู|มีไหม|ใน|hosxp|nondrugitems|nondrugitem|nondrug|ค่ารักษาพยาบาล|ค่ารักษา|ค่าบริการ|ตั้งค่า|ผูก|รหัส|อะไร|บ้าง|ให้หน่อย|ถูกไหม|เท่าไหร่|ตาราง|ฟิลด์|ตัวไหน|ยังไง|adp)/iu', ' ', $query);
                $tokens = array_values(array_filter(array_map('trim', explode(' ', $cleanSearch)), fn($t) => mb_strlen($t) >= 2));

                // Prefer longer token or the first meaningful token
                $selectedToken = null;
                foreach ($tokens as $t) {
                    if (mb_strlen($t) >= 3) {
                        $selectedToken = $t;
                        break;
                    }
                }
                if (!$selectedToken && !empty($tokens)) {
                    $selectedToken = $tokens[0];
                }

                if ($selectedToken) {
                    $searchTermUsed = "ชื่อรายการ: {$selectedToken}";
                    $items = DB::connection('hosxp')->table('nondrugitems as n')
                        ->leftJoin('income as i', 'i.income', '=', 'n.income')
                        ->leftJoin('nhso_adp_type as t', 't.nhso_adp_type_id', '=', 'n.nhso_adp_type_id')
                        ->where('n.istatus', 'Y')
                        ->where('n.name', 'like', '%' . $selectedToken . '%')
                        ->select([
                            'n.icode', 'n.name', 'n.price', 'n.ipd_price', 'n.unitcost',
                            'n.income', 'i.name as income_name',
                            'n.nhso_adp_code', 'n.nhso_adp_type_id', 't.nhso_adp_type_name',
                            'n.billcode', 'n.billnumber', 'n.ucef_code', 'n.csmbs_claim_cat',
                            'n.sks_coverage_price', 'n.enable_sks_opd', 'n.enable_sks_ipd', 'n.istatus'
                        ])
                        ->limit(5)
                        ->get();
                }
            }

            if ($items->isEmpty()) {
                return null;
            }

            $lines = [];
            $lines[] = "ข้อมูลการตั้งค่าค่ารักษาพยาบาล (nondrugitems) จากฐานข้อมูล HOSxP จริง ({$searchTermUsed}):";
            foreach ($items as $idx => $item) {
                $adpStatus = !empty($item->nhso_adp_code) ? "{$item->nhso_adp_code} (Type: {$item->nhso_adp_type_id} - " . ($item->nhso_adp_type_name ?: 'ไม่ระบุ') . ")" : "⚠️ ยังไม่ได้ใส่รหัส ADP";
                $billcodeStatus = !empty($item->billcode) ? $item->billcode : 'ไม่ระบุ';
                $sksCoverage = !empty($item->sks_coverage_price) ? number_format($item->sks_coverage_price, 2) . " บาท" : "ไม่ระบุ";

                $lines[] = "--------------------------------------------------";
                $lines[] = "รายการที่ " . ($idx + 1) . ": [{$item->icode}] {$item->name}";
                $lines[] = "• หมวดรายได้ (income): [{$item->income}] " . ($item->income_name ?: 'ไม่ระบุชื่อหมวด');
                $lines[] = "• รหัส ADP สปสช. (nhso_adp_code): {$adpStatus}";
                $lines[] = "• รหัสกรมบัญชีกลาง (billcode): {$billcodeStatus}";
                $lines[] = "• ราคา OPD: " . number_format($item->price, 2) . " บาท | ราคา IPD: " . number_format($item->ipd_price ?? 0, 2) . " บาท";
                $lines[] = "• เพดานราคาชดเชย (sks_coverage_price): {$sksCoverage}";
                $lines[] = "• สิทธิ์ส่งเบิก: OPD=" . ($item->enable_sks_opd ?: 'N') . ", IPD=" . ($item->enable_sks_ipd ?: 'N');
            }

            $previewTitle = count($items) === 1 ? "รายการ [{$items[0]->icode}] {$items[0]->name}" : "ผลค้นหาค่ารักษาพยาบาล HOSxP ({$searchTermUsed})";

            return [
                'text' => "[ข้อมูลค่าบริการจริงจากตาราง nondrugitems ใน HOSxP]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => $previewTitle,
                    'filename' => 'hosxp_nondrugitems',
                    'page' => 1,
                    'score' => 98.0,
                    'snippet' => "ดึงข้อมูลการตั้งค่าจริงจาก HOSxP: " . mb_substr($items[0]->name, 0, 50) . " (icode: {$items[0]->icode})"
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("nondrugitems Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get ADP Lookup types or verify ADP codes against nhso_adp_type & nhso_adp_code
     */
    public function getAdpLookupContext(string $query): ?array
    {
        try {
            $isAdpQuery = (bool) preg_match('/(หมวด\s*adp|ประเภท\s*adp|adp\s*type|รหัส\s*adp|16\s*แฟ้ม\s*adp|adp\.txt)/iu', $query);
            if (!$isAdpQuery) {
                return null;
            }

            // Check if user is asking about the 20 standard ADP types
            $types = DB::connection('hosxp')->table('nhso_adp_type')
                ->orderBy('nhso_adp_type_id', 'asc')
                ->get();

            if ($types->isEmpty()) {
                return null;
            }

            $lines = [];
            $lines[] = "ตารางมาตรฐานประเภทบริการ ADP (nhso_adp_type 20 หมวด) สำหรับการเบิกจ่ายกองทุนใน HOSxP:";
            foreach ($types as $t) {
                $lines[] = "- Type [{$t->nhso_adp_type_id}]: {$t->nhso_adp_type_name}";
            }

            // Check if query has a specific 6-digit ADP code (เช่น 020700, 5501, ฯลฯ)
            preg_match('/\b(\d{4,6})\b/', $query, $adpMatch);
            if (!empty($adpMatch[1])) {
                $searchCode = $adpMatch[1];
                $matchedAdpCode = DB::connection('hosxp')->table('nhso_adp_code as c')
                    ->leftJoin('nhso_adp_type as t', 't.nhso_adp_type_id', '=', 'c.nhso_adp_type_id')
                    ->where('c.nhso_adp_code', $searchCode)
                    ->select('c.nhso_adp_code', 'c.nhso_adp_code_name', 'c.nhso_adp_type_id', 't.nhso_adp_type_name')
                    ->first();

                if ($matchedAdpCode) {
                    $lines[] = "\nผลตรวจสอบรหัสมาตรฐาน สปสช. [{$searchCode}] ในตาราง nhso_adp_code:";
                    $lines[] = "• รหัส: {$matchedAdpCode->nhso_adp_code}";
                    $lines[] = "• ชื่อตามประกาศ: {$matchedAdpCode->nhso_adp_code_name}";
                    $lines[] = "• อยู่ในหมวด: Type {$matchedAdpCode->nhso_adp_type_id} ({$matchedAdpCode->nhso_adp_type_name})";
                }
            }

            return [
                'text' => "[ข้อมูลตารางมาตรฐานหมวด ADP สำหรับเบิกจ่ายกองทุนจาก HOSxP]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => "ตารางมาตรฐานประเภทบริการ ADP กองทุน สปสช. (nhso_adp_type)",
                    'filename' => 'hosxp_nhso_adp_type',
                    'page' => 1,
                    'score' => 97.0,
                    'snippet' => "รายการประเภทบริการ ADP ทั้งหมด 20 หมวดมาตรฐาน"
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("ADP Lookup Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Income category mapping context (19 categories)
     */
    public function getIncomeCategoryContext(string $query): ?array
    {
        try {
            $isIncomeQuery = (bool) preg_match('/(หมวด\s*income|หมวดรายได้|ผูก\s*income|16\s*หมวด|income\s*hosxp)/iu', $query);
            if (!$isIncomeQuery) {
                return null;
            }

            $incomes = DB::connection('hosxp')->table('income')
                ->orderBy('income', 'asc')
                ->get(['income', 'name', 'income_csmbs_code', 'income_sss_group_code']);

            if ($incomes->isEmpty()) {
                return null;
            }

            $lines = [];
            $lines[] = "ตารางมาตรฐานหมวดรายได้ของโรงพยาบาล (income) ใน HOSxP (ใช้แปลงเป็นแฟ้ม CHA / CHT):";
            foreach ($incomes as $inc) {
                $lines[] = "- หมวด [{$inc->income}]: {$inc->name} (รหัส CSMBS: " . ($inc->income_csmbs_code ?: '-') . ")";
            }

            return [
                'text' => "[ข้อมูลหมวดรายได้ income จาก HOSxP]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => "หมวดรายได้ HOSxP (income 19 หมวด)",
                    'filename' => 'hosxp_income',
                    'page' => 1,
                    'score' => 96.0,
                    'snippet' => "โครงสร้างหมวดรายได้ของโรงพยาบาลที่ใช้แมปกับ 16 แฟ้ม"
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("Income Context Warning: " . $e->getMessage());
            return null;
        }
    }
}
