<?php

namespace App\Services\Ai\Context;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class HosxpContextService
{
    /**
     * Get HOSxP ข้อมูลพื้นฐาน & 16-Files Lookup Context based on user query
     *
     * @param string $query User's question
     * @return array|null ['text' => string, 'sources' => array]
     */
    public function getContext(string $query, ?string $category = null): ?array
    {
        try {
            // Check if HOSxP connection is accessible
            if (!$this->isHosxpConnected()) {
                return null;
            }

            $contextBlocks = [];
            $sources = [];

            // 1. Direct routing if category/tab is explicitly specified
            if ($category === 'doctor') {
                $doctorData = $this->getDoctorContext($query, true);
                if ($doctorData) {
                    $contextBlocks[] = $doctorData['text'];
                    $sources[] = $doctorData['source'];
                }
            } elseif ($category === 'nondrugitems') {
                $nondrugData = $this->getNondrugItemContext($query, true);
                if ($nondrugData) {
                    $contextBlocks[] = $nondrugData['text'];
                    $sources[] = $nondrugData['source'];
                }
                $adpData = $this->getAdpLookupContext($query);
                if ($adpData) {
                    $contextBlocks[] = $adpData['text'];
                    $sources[] = $adpData['source'];
                }
            } elseif ($category === 'pttype') {
                $pttypeData = $this->getPttypeContext($query, true);
                if ($pttypeData) {
                    $contextBlocks[] = $pttypeData['text'];
                    $sources[] = $pttypeData['source'];
                }
            } elseif ($category === 'drug' || $category === 'drugitems') {
                $drugData = $this->getDrugItemContext($query, true);
                if ($drugData) {
                    $contextBlocks[] = $drugData['text'];
                    $sources[] = $drugData['source'];
                }
            } elseif ($category === 'lab' || $category === 'lab_items') {
                $labData = $this->getLabItemContext($query, true);
                if ($labData) {
                    $contextBlocks[] = $labData['text'];
                    $sources[] = $labData['source'];
                }
            } elseif ($category === 'icd' || $category === 'icd10' || $category === 'icd9') {
                $icdData = $this->getIcdContext($query, true);
                if ($icdData) {
                    $contextBlocks[] = $icdData['text'];
                    $sources[] = $icdData['source'];
                }
            } elseif ($category === 'fund_audit' || $category === 'audit' || $category === 'fund') {
                $fundData = $this->getFundAuditContext($query, true);
                if ($fundData) {
                    $contextBlocks[] = $fundData['text'];
                    $sources[] = $fundData['source'];
                }
            } else {
                // Fallback: Smart auto-detection based on user question keywords
                // 0. Check for Fund Audit / 16 แฟ้ม / AIPN / SSOP / CSOP
                $fundData = $this->getFundAuditContext($query);
                if ($fundData) {
                    $contextBlocks[] = $fundData['text'];
                    $sources[] = $fundData['source'];
                }

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

                // 4. Check for Doctor / Medical Staff
                $doctorData = $this->getDoctorContext($query);
                if ($doctorData) {
                    $contextBlocks[] = $doctorData['text'];
                    $sources[] = $doctorData['source'];
                }

                // 5. Check for Pttype / Standard Right mappings
                $pttypeData = $this->getPttypeContext($query);
                if ($pttypeData) {
                    $contextBlocks[] = $pttypeData['text'];
                    $sources[] = $pttypeData['source'];
                }

                // 6. Check for Drug items / Drug catalog / hrims.drugcat_*
                $drugData = $this->getDrugItemContext($query);
                if ($drugData) {
                    $contextBlocks[] = $drugData['text'];
                    $sources[] = $drugData['source'];
                }

                // 7. Check for Lab items / Lab profile / hrims.labcat_*
                $labData = $this->getLabItemContext($query);
                if ($labData) {
                    $contextBlocks[] = $labData['text'];
                    $sources[] = $labData['source'];
                }

                // 8. Check for ICD-10 & ICD-9 / chi accpdx / nhso pp
                $icdData = $this->getIcdContext($query);
                if ($icdData) {
                    $contextBlocks[] = $icdData['text'];
                    $sources[] = $icdData['source'];
                }
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
    public function getNondrugItemContext(string $query, bool $force = false): ?array
    {
        try {
            // Guard: If not forced, avoid false positives from doctor/staff queries
            if (!$force) {
                $isExplicitDoctorQuery = (bool) preg_match('/(แพทย์|หมอ|licenseno|ว\.|ท\.|สภาวิชาชีพ)/iu', $query)
                    && !(bool) preg_match('/(ค่ารักษา|ค่าบริการ|nondrug|เวชภัณฑ์|adp|icode|หัตถการ)/iu', $query);
                if ($isExplicitDoctorQuery) {
                    return null;
                }
            }

            // Match icode pattern (e.g. 3003941 or numbers 5-7 digits)
            preg_match('/(?:icode\s*[:=]?\s*|\b)([3-9]\d{5,6})\b/i', $query, $codeMatch);
            $targetIcode = $codeMatch[1] ?? null;

            $items = collect();
            $searchTermUsed = '';

            // Priority 1: Exact icode drill-down
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
            }

            // Priority 2: General query for items missing ADP code (only if no specific icode found)
            if ($items->isEmpty()) {
                $isMissingAdpQuery = (bool) preg_match('/(ไม่มี\s*adp|ขาด\s*adp|ไม่(ได้)?ผูก(\s*adp)?|ไม่มีรหัส\s*adp|adp.*(ว่าง|หาย|ขาด)|(ยังไม่|ไม่ได้)\s*ผูก)/iu', $query);
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
            }

            if (!$targetIcode) {
                // If not forced and no target icode, only search if query has relevant nondrug keywords
                if (!$force) {
                    $hasNondrugKeyword = (bool) preg_match('/(ค่ารักษา|ค่าบริการ|nondrug|เวชภัณฑ์|adp|icode|หัตถการ|แลป|lab|x-ray|เอกซเรย์|ห้อง|เตียง|ยา)/iu', $query);
                    if (!$hasNondrugKeyword) {
                        return null;
                    }
                }

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
                if ($force || (bool) preg_match('/(สรุป|สถิติ|ตรวจ|ขาด|ผิด|ไม่มี|ว่าง|สมบูรณ์|ไม่สมบูรณ์|ภาพรวม)/iu', $query)) {
                    $totalActive = DB::connection('hosxp')->table('nondrugitems')->where('istatus', 'Y')->count();
                    $missingCount = DB::connection('hosxp')->table('nondrugitems')
                        ->where('istatus', 'Y')
                        ->where(function ($q) {
                            $q->whereNull('nhso_adp_code')->orWhere('nhso_adp_code', '');
                        })
                        ->count();

                    $missingItems = DB::connection('hosxp')->table('nondrugitems as n')
                        ->leftJoin('income as i', 'i.income', '=', 'n.income')
                        ->where('n.istatus', 'Y')
                        ->where(function ($q) {
                            $q->whereNull('n.nhso_adp_code')->orWhere('n.nhso_adp_code', '');
                        })
                        ->select('n.icode', 'n.name', 'n.price', 'n.income', 'i.name as income_name')
                        ->limit(5)
                        ->get();

                    $lines = [
                        "สรุปสถานะการตั้งค่าค่ารักษาพยาบาล (nondrugitems) ใน HOSxP:",
                        "• รายการที่เปิดใช้งานทั้งหมด: {$totalActive} รายการ",
                        "• รายการที่ยังไม่ได้ผูกรหัส ADP สปสช.: {$missingCount} รายการ",
                        "• ตัวอย่างรายการที่ยังไม่ได้ผูกรหัส ADP:"
                    ];
                    foreach ($missingItems as $idx => $m) {
                        $lines[] = "  " . ($idx + 1) . ". [{$m->icode}] {$m->name} (หมวด income: [{$m->income}] {$m->income_name})";
                    }

                    return [
                        'text' => "[สรุปข้อมูลค่ารักษาพยาบาล HOSxP]:\n" . implode("\n", $lines),
                        'source' => [
                            'title' => "สรุปค่ารักษาพยาบาล HOSxP",
                            'filename' => 'hosxp_nondrugitems_summary',
                            'page' => 1,
                            'score' => 97.0,
                            'snippet' => "พบ {$missingCount} รายการจากทั้งหมด {$totalActive} รายการที่ยังไม่มี nhso_adp_code"
                        ]
                    ];
                }

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
                'text' => "[ข้อมูลค่าบริการจริงจากระบบ HOSxP]:\n" . implode("\n", $lines),
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

    /**
     * Search Doctor & Staff context
     */
    public function getDoctorContext(string $query, bool $force = false): ?array
    {
        try {
            if (!$force) {
                $isDoctorQuery = (bool) preg_match('/(แพทย์|หมอ|บุคลากร|เจ้าหน้าที่|licenseno|ว\.|ท\.|ใบประกอบ|รหัสแพทย์|doctor|cid|สภาวิชาชีพ)/iu', $query);
                if (!$isDoctorQuery) {
                    return null;
                }
            }

            // Extract doctor code (e.g. 0123 or numbers 3-5 digits)
            preg_match('/(?:รหัส(?:\s*แพทย์)?\s*[:=]?\s*|\b)([0-9]{3,5})\b/iu', $query, $codeMatch);
            $targetCode = $codeMatch[1] ?? null;

            $items = collect();
            $label = '';

            if ($targetCode) {
                $label = "รหัสแพทย์: {$targetCode}";
                $items = DB::connection('hosxp')->table('doctor')
                    ->where('code', $targetCode)
                    ->select(['code', 'name', 'licenseno', 'cid', 'department', 'jobposition', 'active', 'council_code', 'provider_type_code'])
                    ->limit(3)
                    ->get();
            }

            if ($items->isEmpty()) {
                // Keyword search in doctor name
                $cleanSearch = preg_replace('/(แพทย์|หมอ|บุคลากร|เจ้าหน้าที่|ค้นหา|ดู|ขอดู|ข้อมูล|hosxp|doctor|รหัส|มี|สภาวิชาชีพ|ใบประกอบ|ครบไหม|ไหม|ถูกไหม)/iu', ' ', $query);
                $tokens = array_values(array_filter(array_map('trim', explode(' ', $cleanSearch)), fn($t) => mb_strlen($t) >= 2));
                if (!empty($tokens)) {
                    $label = "ค้นหาชื่อ: {$tokens[0]}";
                    $items = DB::connection('hosxp')->table('doctor')
                        ->where('name', 'like', '%' . $tokens[0] . '%')
                        ->select(['code', 'name', 'licenseno', 'cid', 'department', 'jobposition', 'active', 'council_code'])
                        ->limit(5)
                        ->get();
                }

                if ($items->isEmpty()) {
                    // Check if asking for summary / audit of doctors or if force is active
                    $isAudit = $force || (bool) preg_match('/(สรุป|สถิติ|ตรวจ|ขาด|ผิด|ไม่มี|ว่าง|สมบูรณ์|ไม่สมบูรณ์)/iu', $query);
                    if ($isAudit) {
                        $total = DB::connection('hosxp')->table('doctor')->count();
                        $active = DB::connection('hosxp')->table('doctor')->where('active', 'Y')->count();
                        $missingCouncil = DB::connection('hosxp')->table('doctor')->where('active', 'Y')
                            ->where(function($q) { $q->whereNull('council_code')->orWhere('council_code', ''); })->count();
                        $missingLic = DB::connection('hosxp')->table('doctor')->where('active', 'Y')
                            ->where(function($q) { $q->whereNull('licenseno')->orWhere('licenseno', '')->orWhere('licenseno', '-'); })->count();
                        $missingCid = DB::connection('hosxp')->table('doctor')->where('active', 'Y')
                            ->where(function($q) { $q->whereNull('cid')->orWhere('cid', '')->orWhereRaw('LENGTH(cid) != 13'); })->count();

                        $lines = [
                            "สรุปข้อมูลแพทย์และบุคลากรในระบบ HOSxP:",
                            "• บุคลากรทั้งหมดในระบบ: {$total} คน (Active: {$active} คน)",
                            "• บุคลากร Active ที่ยังไม่ได้ระบุสภาวิชาชีพ: {$missingCouncil} คน",
                            "• บุคลากร Active ที่ยังไม่มีเลขที่ใบประกอบวิชาชีพ: {$missingLic} คน",
                            "• บุคลากร Active ที่เลขบัตรประชาชนไม่ครบ 13 หลัก: {$missingCid} คน",
                            "(หมายเหตุ: บุคลากรที่ไม่ใช่แพทย์ มักบันทึกเลขใบประกอบเป็น -เลขบัตรประชาชนตามมาตรฐาน HOSxP)"
                        ];

                        return [
                            'text' => "[สรุปข้อมูลแพทย์และบุคลากร HOSxP]:\n" . implode("\n", $lines),
                            'source' => [
                                'title' => "สรุปสถานะแพทย์/บุคลากร HOSxP",
                                'filename' => 'hosxp_doctor_summary',
                                'page' => 1,
                                'score' => 97.0,
                                'snippet' => "ข้อมูลความสมบูรณ์ของบุคลากรในระบบ HOSxP"
                            ]
                        ];
                    }

                    return null;
                }
            }

            if ($items->isEmpty()) {
                return null;
            }

            $lines = ["ข้อมูลแพทย์/บุคลากรจาก HOSxP ({$label}):"];
            foreach ($items as $doc) {
                $status = ($doc->active === 'Y') ? '🟢 Active' : '⚪ Inactive';
                $council = !empty($doc->council_code) ? "สภาวิชาชีพ: {$doc->council_code}" : "ยังไม่ระบุสภา";
                $lines[] = "- [{$doc->code}] {$doc->name} | ใบอนุญาต: " . ($doc->licenseno ?: 'ไม่มี') . " | {$council} | แผนก: " . ($doc->department ?: 'ไม่ระบุ') . " | {$status}";
            }

            return [
                'text' => "[ข้อมูลแพทย์/บุคลากรจาก HOSxP]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => "ข้อมูลแพทย์/บุคลากร HOSxP",
                    'filename' => 'hosxp_doctor',
                    'page' => 1,
                    'score' => 95.0,
                    'snippet' => implode(", ", array_map(fn($d) => "[{$d->code}] {$d->name}", $items->all()))
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("Doctor Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search Pttype context
     */
    public function getPttypeContext(string $query, bool $force = false): ?array
    {
        try {
            if (!$force) {
                $isPttypeQuery = (bool) preg_match('/(สิทธิ|สิทธิการรักษา|pttype|เบิกได้|จ่ายเงิน|บัตรทอง|ปกส|ประกันสังคม|กสท|จ่ายตรง|paidst|pcode)/iu', $query);
                if (!$isPttypeQuery) {
                    return null;
                }
            }

            // Extract pttype code (e.g. 01, 10, A1)
            preg_match('/(?:(?:สิทธิ|รหัส)(?:\s*การรักษา)?\s*[:=]?\s*)([A-Za-z0-9]{2,3})\b/iu', $query, $codeMatch);
            $targetCode = $codeMatch[1] ?? null;

            $items = collect();
            $label = '';

            if ($targetCode) {
                $label = "รหัสสิทธิ: {$targetCode}";
                $items = DB::connection('hosxp')->table('pttype')
                    ->where('pttype', $targetCode)
                    ->select(['pttype', 'name', 'pcode', 'paidst', 'hipdata_code', 'nhso_code', 'pttype_std_code', 'isuse', 'export_eclaim'])
                    ->limit(2)
                    ->get();
            }

            if ($items->isEmpty()) {
                $cleanSearch = preg_replace('/(สิทธิการรักษา|สิทธิ|ค้นหา|ดู|ขอดู|ข้อมูล|hosxp|pttype|รหัส)/iu', ' ', $query);
                $tokens = array_values(array_filter(array_map('trim', explode(' ', $cleanSearch)), fn($t) => mb_strlen($t) >= 2));
                if (!empty($tokens)) {
                    $label = "ค้นหาชื่อสิทธิ: {$tokens[0]}";
                    $items = DB::connection('hosxp')->table('pttype')
                        ->where('name', 'like', '%' . $tokens[0] . '%')
                        ->where('isuse', 'Y')
                        ->select(['pttype', 'name', 'pcode', 'paidst', 'hipdata_code', 'nhso_code', 'pttype_std_code', 'isuse', 'export_eclaim'])
                        ->limit(5)
                        ->get();
                }

                if ($items->isEmpty() && ($force || preg_match('/(สรุป|สถิติ|ตรวจ|ขาด|ผิด|ไม่มี|ว่าง|สมบูรณ์|ไม่สมบูรณ์)/iu', $query))) {
                    // Summary
                    $total = DB::connection('hosxp')->table('pttype')->count();
                    $active = DB::connection('hosxp')->table('pttype')->where('isuse', 'Y')->count();
                    $missingStd = DB::connection('hosxp')->table('pttype')->where('isuse', 'Y')
                        ->where(function($q) { $q->whereNull('pttype_std_code')->orWhere('pttype_std_code', ''); })->count();

                    $lines = [
                        "สรุปข้อมูลสิทธิการรักษาในระบบ HOSxP:",
                        "• สิทธิการรักษาทั้งหมด: {$total} สิทธิ (เปิดใช้งาน: {$active} สิทธิ)",
                        "• สิทธิ Active ที่ยังไม่ได้ระบุรหัสมาตรฐาน 4 หลัก: {$missingStd} สิทธิ",
                        "(แนะนำ: ควรกำหนดรหัสสิทธิมาตรฐานให้ตรงกับมาตรฐานของ สปสช./กรมบัญชีกลาง เพื่อให้ส่งออกเคลม FDH ถูกต้อง)"
                    ];

                    return [
                        'text' => "[สรุปข้อมูลสิทธิการรักษา HOSxP]:\n" . implode("\n", $lines),
                        'source' => [
                            'title' => "สรุปสิทธิการรักษา HOSxP",
                            'filename' => 'hosxp_pttype_summary',
                            'page' => 1,
                            'score' => 97.0,
                            'snippet' => "ข้อมูลความสมบูรณ์ของสิทธิการรักษาในระบบ HOSxP"
                        ]
                    ];
                }
            }

            if ($items->isEmpty()) {
                return null;
            }

            $lines = ["ข้อมูลสิทธิการรักษาจาก HOSxP ({$label}):"];
            foreach ($items as $pt) {
                $status = ($pt->isuse === 'Y') ? '🟢 ใช้งาน' : '⚪ ปิดใช้งาน';
                $stdCode = !empty($pt->pttype_std_code) ? "รหัสมาตรฐาน: {$pt->pttype_std_code}" : "⚠️ ขาดรหัสมาตรฐาน";
                $hip = !empty($pt->hipdata_code) ? "HIPDATA: {$pt->hipdata_code}" : "-";
                $paidst = !empty($pt->paidst) ? "paidst: {$pt->paidst}" : "-";
                $lines[] = "- [{$pt->pttype}] {$pt->name} | {$stdCode} | {$hip} | {$paidst} | {$status}";
            }

            return [
                'text' => "[ข้อมูลสิทธิการรักษาจาก HOSxP]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => "ข้อมูลสิทธิการรักษา pttype HOSxP",
                    'filename' => 'hosxp_pttype',
                    'page' => 1,
                    'score' => 95.0,
                    'snippet' => implode(", ", array_map(fn($p) => "[{$p->pttype}] {$p->name}", $items->all()))
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("Pttype Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search drugitems (ข้อมูลยา, ราคาแยกเก็บ, ตารางเชื่อมโยง, และแคตตาล็อกยา hrims.drugcat_*)
     */
    public function getDrugItemContext(string $query, bool $force = false): ?array
    {
        try {
            if (!$force) {
                $hasDrugKeyword = (bool) preg_match('/(ยา|drug|drugitems|icode|did|24หลัก|tmt|แคตตาล็อกยา|drugcat|ned|ed|ค่ายา|ราคาแยกเก็บ|unitprice|sks_price|unitcost|\b[0-9]{6,7}\b|\b[0-9]{24}\b)/iu', $query);
                if (!$hasDrugKeyword) {
                    return null;
                }
            }

            $localDb = config('database.connections.mysql.database');
            $hosxp = DB::connection('hosxp');

            // 1. Check if user query mentions specific drug code (6-7 digit code, 24-digit code, or icode)
            preg_match('/(?:icode\s*[:=]?\s*|\b)([0-9]{6,7}|[0-9]{24})\b/i', $query, $codeMatch);
            $targetIcode = $codeMatch[1] ?? null;

            // Priority 1: Exact drug code search (icode, 24-digit, TMT, or ref_code)
            if ($targetIcode) {
                $drug = $hosxp->select("
                    SELECT d.icode, d.name, d.strength, d.units, d.dosageform, d.drugaccount,
                           d.unitprice, d.price2, d.price3, d.ipd_price, d.unitcost, d.stdprice, d.sks_price, d.sks_reimb_price,
                           d.did, d2.ref_code AS code_24, d.tmt_tp_code, d3.ref_code AS code_tmt,
                           i.name AS income_name, d.income, d.istatus,
                           nd.unitprice AS price_nhso, nd.ised AS ised_nhso, nd.ndc24 AS code_24_nhso, nd.tmtid AS code_tmt_nhso,
                           chi.unitprice AS price_chi
                    FROM drugitems d
                    LEFT JOIN income i ON i.income = d.income
                    LEFT JOIN drugitems_ref_code d2 ON d2.icode = d.icode AND d2.drugitems_ref_code_type_id = 1
                    LEFT JOIN drugitems_ref_code d3 ON d3.icode = d.icode AND d3.drugitems_ref_code_type_id = 3
                    LEFT JOIN (
                        SELECT hospdrugcode, unitprice, ised, ndc24, tmtid
                        FROM {$localDb}.drugcat_nhso
                        GROUP BY hospdrugcode
                    ) nd ON nd.hospdrugcode = d.icode
                    LEFT JOIN (
                        SELECT hospdrugcode, unitprice
                        FROM {$localDb}.drugcat_chi
                        GROUP BY hospdrugcode
                    ) chi ON chi.hospdrugcode = d.icode
                    WHERE d.icode = ? OR d.did LIKE ? OR d.tmt_tp_code = ? OR d2.ref_code LIKE ? OR d3.ref_code = ?
                    LIMIT 1
                ", [$targetIcode, "%{$targetIcode}%", $targetIcode, "%{$targetIcode}%", $targetIcode]);

                if (!empty($drug)) {
                    $d = $drug[0];
                    $code24 = !empty($d->code_24) ? $d->code_24 : ($d->did ?: '⚠️ ไม่มีรหัส 24 หลัก');
                    $codeTmt = !empty($d->code_tmt) ? $d->code_tmt : ($d->tmt_tp_code ?: '⚠️ ไม่มีรหัส TMT');
                    $status = ($d->istatus === 'Y') ? 'Active (เปิดใช้งาน)' : 'Inactive (ปิดใช้งาน)';
                    $isEd = in_array(trim($d->drugaccount ?? ''), ['ก', 'ข', 'ค', 'ง', 'จ'], true);
                    $edLabel = $isEd ? "ยาในบัญชียาหลักแห่งชาติ (ED บัญชี '{$d->drugaccount}')" : "ยานอกบัญชียาหลักแห่งชาติ (NED / บัญชี '" . ($d->drugaccount ?: '-') . "')";
                    $nhsoEd = ($d->ised_nhso === 'E') ? 'ยาในบัญชี (ED)' : (($d->ised_nhso === 'N') ? 'ยานอกบัญชี (NED)' : 'ไม่ระบุ');
                    $nhsoCatalog = !empty($d->price_nhso) ? "พบในแคตตาล็อก สปสช. (ราคาเบิก: {$d->price_nhso} บ., สถานะบัญชี: {$nhsoEd})" : "⚠️ ไม่พบในแคตตาล็อก สปสช. (drugcat_nhso)";
                    $chiCatalog = !empty($d->price_chi) ? "พบในแคตตาล็อก กรมบัญชีกลาง (ราคาเบิก: {$d->price_chi} บ.)" : "ไม่พบในแคตตาล็อก กรมบัญชีกลาง (drugcat_chi)";

                    $lines = [
                        "รายละเอียดข้อมูลยาจากระบบ HOSxP และแคตตาล็อกยา RiMS (icode: {$d->icode}):",
                        "• ชื่อยา: {$d->name} {$d->strength} (รูปแบบ: {$d->dosageform}, หน่วย: {$d->units})",
                        "• สถานะบัญชียา: **{$edLabel}** | หมวดรายได้: [{$d->income}] {$d->income_name}",
                        "• รหัสมาตรฐาน: 24 หลัก: {$code24} | TMT: {$codeTmt}",
                        "• ราคาแยกเก็บ (เหมือนค่ารักษาพยาบาล):",
                        "  - ราคาจำหน่าย OPD 1: " . number_format($d->unitprice, 2) . " บ. | OPD 2: " . number_format($d->price2, 2) . " บ. | OPD 3: " . number_format($d->price3, 2) . " บ.",
                        "  - ราคาจำหน่าย IPD 1: " . number_format($d->ipd_price, 2) . " บ.",
                        "  - ราคาทุน (unitcost): " . number_format($d->unitcost, 2) . " บ. | ราคากลาง (stdprice): " . number_format($d->stdprice ?? 0, 2) . " บ.",
                        "  - ราคาเบิกจ่ายกรมบัญชีกลาง (sks_price): " . number_format($d->sks_price ?? 0, 2) . " บ. (ชดเชย: " . number_format($d->sks_reimb_price ?? 0, 2) . " บ.)",
                        "• สถานะในแคตตาล็อกของ RiMS:",
                        "  - สปสช. (hrims.drugcat_nhso): {$nhsoCatalog}",
                        "  - กรมบัญชีกลาง (hrims.drugcat_chi): {$chiCatalog}",
                        "• สถานะการใช้งาน: {$status}"
                    ];

                    return [
                        'text' => "[ข้อมูลยา {$d->name} ({$d->icode})]:\n" . implode("\n", $lines),
                        'source' => [
                            'title' => "ข้อมูลยา {$d->name} ({$d->icode})",
                            'filename' => 'hosxp_drugitems',
                            'page' => 1,
                            'score' => 99.0,
                            'snippet' => "{$d->name} ({$edLabel}) ราคา OPD: {$d->unitprice} บ., รหัส 24 หลัก: {$code24}"
                        ]
                    ];
                }

                // If not found in HOSxP drugitems, search in RiMS drug catalogs
                $catNhso = DB::select("
                    SELECT hospdrugcode, genericname, tradename, dosageform, strength, unitprice, ised, ndc24, tmtid
                    FROM {$localDb}.drugcat_nhso
                    WHERE hospdrugcode = ? OR ndc24 LIKE ? OR tmtid = ?
                    LIMIT 1
                ", [$targetIcode, "%{$targetIcode}%", $targetIcode]);

                if (!empty($catNhso)) {
                    $c = $catNhso[0];
                    $cEd = ($c->ised === 'E') ? 'ยาในบัญชียาหลักแห่งชาติ (ED)' : 'ยานอกบัญชียาหลักแห่งชาติ (NED)';
                    $lines = [
                        "ผลการค้นหารหัส '{$targetIcode}' ในแคตตาล็อกยา สปสช. (drugcat_nhso) และระบบ HOSxP:",
                        "• สถานะใน HOSxP: ⚠️ ยังไม่พบรายการยานี้ในฐานข้อมูล drugitems ของโรงพยาบาล",
                        "• ข้อมูลจากแคตตาล็อกมาตรฐาน สปสช. (drugcat_nhso):",
                        "  - ชื่อสามัญ: {$c->genericname} | ชื่อการค้า: {$c->tradename}",
                        "  - ความแรง: {$c->strength} | รูปแบบ: {$c->dosageform}",
                        "  - สถานะบัญชียา: **{$cEd}** (ised = '{$c->ised}')",
                        "  - รหัสมาตรฐาน: 24 หลัก: " . ($c->ndc24 ?: '-') . " | TMT: " . ($c->tmtid ?: '-'),
                        "  - ราคาชดเชย สปสช.: " . number_format($c->unitprice, 2) . " บาท",
                        "• คำแนะนำ: หากโรงพยาบาลมีการใช้ยานี้ สามารถนำรหัส 24 หลักและ TMT ข้างต้นไปเพิ่มรายการยาใน HOSxP (เมนู Tools > System Setting > กำหนดรายการยา) พร้อมกำหนดบัญชียาเป็น {$cEd}"
                    ];

                    return [
                        'text' => "[ข้อมูลยาจากแคตตาล็อก สปสช. รหัส {$targetIcode}]:\n" . implode("\n", $lines),
                        'source' => [
                            'title' => "แคตตาล็อกยา สปสช. ({$targetIcode})",
                            'filename' => 'drugcat_nhso',
                            'page' => 1,
                            'score' => 95.0,
                            'snippet' => "พบในแคตตาล็อก สปสช. {$c->genericname} ({$cEd})"
                        ]
                    ];
                }

                // If code not found in both HOSxP and Catalogs, but user asks whether it is ED or NED
                $isEdNedQuery = (bool) preg_match('/(นอกบัญชี|ในบัญชี|ed\b|ned\b|บัญชีหลัก)/iu', $query);
                if ($isEdNedQuery || $force) {
                    $lines = [
                        "ผลการตรวจสอบรหัส '{$targetIcode}' ในฐานข้อมูล HOSxP และแคตตาล็อกยา RiMS:",
                        "• ผลการค้นหา: ไม่พบรหัส '{$targetIcode}' ในตาราง drugitems ของ HOSxP และแคตตาล็อกยามาตรฐาน (drugcat_nhso / drugcat_chi)",
                        "",
                        "• หลักเกณฑ์การแยกรหัสยาในบัญชี (ED) และนอกบัญชี (NED) ตามมาตรฐานกระทรวงสาธารณสุขและ สปสช.:",
                        "  1. ยาในบัญชียาหลักแห่งชาติ (ED - Essential Drugs):",
                        "     - ในระบบ HOSxP: ดูจากฟิลด์ `drugitems.drugaccount` จะกำหนดเป็นตัวอักษร **'ก', 'ข', 'ค', 'ง', หรือ 'จ'**",
                        "     - ในแคตตาล็อก สปสช. (drugcat_nhso): ฟิลด์ `ised` จะระบุเป็น **'E'**",
                        "     - หมวดรายได้ (income): กำหนดเป็นหมวด '03' (ค่ายาในบัญชียาหลักแห่งชาติ)",
                        "  2. ยานอกบัญชียาหลักแห่งชาติ (NED - Non-Essential Drugs):",
                        "     - ในระบบ HOSxP: ฟิลด์ `drugitems.drugaccount` จะเว้นว่าง, เป็นเครื่องหมาย **'-'**, หรือระบุว่า **'NED'**",
                        "     - ในแคตตาล็อก สปสช. (drugcat_nhso): ฟิลด์ `ised` จะระบุเป็น **'N'**",
                        "     - หมวดรายได้ (income): กำหนดเป็นหมวด '17' (ค่ายานอกบัญชียาหลักแห่งชาติ)",
                        "",
                        "• วิธีตรวจสอบและตั้งค่าในโปรแกรม HOSxP:",
                        "  - เข้าเมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดรายการยา (Drug Items)'",
                        "  - ค้นหารายการยาที่ต้องการ แล้วตรวจสอบที่ช่อง 'บัญชียา' เพื่อเลือกกำหนดเป็น ก-จ หรือระบุเป็นยานอกบัญชี พร้อมตรวจสอบรหัส 24 หลักและ TMT ให้ครบถ้วน"
                    ];

                    return [
                        'text' => "[ผลการค้นหารหัส {$targetIcode} และหลักเกณฑ์ ED/NED]:\n" . implode("\n", $lines),
                        'source' => [
                            'title' => "หลักเกณฑ์ยาในบัญชีและนอกบัญชี (ED/NED)",
                            'filename' => 'hosxp_ed_ned_criteria',
                            'page' => 1,
                            'score' => 90.0,
                            'snippet' => "เกณฑ์การตรวจสอบ drugaccount ใน HOSxP และ ised ใน drugcat_nhso"
                        ]
                    ];
                }
            }

            // Priority 2: General questions about drug catalog, pricing structure, or connected tables
            $isRelationQuery = (bool) preg_match('/(เชื่อม(โยง)?|ตารางไหน|ตารางอะไร|โครงสร้าง|ราคาแยกเก็บ|drugcat|แคตตาล็อกยา|สัมพันธ์|เก็บยังไง)/iu', $query);
            $isSummaryQuery = (bool) preg_match('/(สรุป|สถิติ|มีกี่|ทั้งหมด|ขาด\s*24|ขาด\s*tmt|ไม่มีรหัส)/iu', $query);

            if ($isRelationQuery || $isSummaryQuery || $force) {
                $totalDrugs = $hosxp->table('drugitems')->count();
                $activeDrugs = $hosxp->table('drugitems')->where('istatus', 'Y')->count();
                $missing24 = $hosxp->select("
                    SELECT COUNT(*) as c FROM drugitems d
                    LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 1
                    WHERE d.istatus = 'Y' AND (d.did IS NULL OR LENGTH(TRIM(d.did)) < 24) AND (r.ref_code IS NULL OR LENGTH(TRIM(r.ref_code)) < 24)
                ")[0]->c ?? 0;
                $missingTmt = $hosxp->select("
                    SELECT COUNT(*) as c FROM drugitems d
                    LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 3
                    WHERE d.istatus = 'Y' AND (d.tmt_tp_code IS NULL OR d.tmt_tp_code = '') AND (r.ref_code IS NULL OR r.ref_code = '')
                ")[0]->c ?? 0;

                $nhsoCatCount = DB::table('drugcat_nhso')->count();
                $chiCatCount = DB::table('drugcat_chi')->count();
                $fdhCatCount = DB::table('drugcat_fdh')->count();

                $lines = [
                    "ภาพรวมข้อมูลยา (drugitems) ในระบบ HOSxP และการเชื่อมโยงแคตตาล็อกยา RiMS:",
                    "• สถิติรายการยา HOSxP: ทั้งหมด {$totalDrugs} รายการ (เปิดใช้งาน {$activeDrugs} รายการ, ปิดใช้งาน " . ($totalDrugs - $activeDrugs) . " รายการ)",
                    "• ความสมบูรณ์ของรหัสยา Active: ขาดรหัส 24 หลัก {$missing24} รายการ, ขาดรหัส TMT {$missingTmt} รายการ",
                    "",
                    "• โครงสร้างราคาแยกเก็บของยาใน HOSxP (เหมือนค่ารักษาพยาบาล):",
                    "  - ราคาจำหน่าย OPD: unitprice (ราคาปกติ 1), price2 (ราคา 2), price3 (ราคา 3)",
                    "  - ราคาจำหน่าย IPD: ipd_price (ราคา IPD 1), ipd_price2, ipd_price3",
                    "  - ราคาทุนและราคากลาง: unitcost (ต้นทุนต่อหน่วย), stdprice (ราคากลางมาตรฐาน)",
                    "  - ราคาเบิกจ่ายตามสิทธิ: sks_price (ราคาจ่ายตรงกรมบัญชีกลาง), sks_reimb_price (ราคาชดเชย)",
                    "",
                    "• ตารางที่ drugitems เชื่อมโยงในระบบ HOSxP:",
                    "  1. income: หมวดรายได้ค่าบริการทางการแพทย์และค่ายา (เชื่อมด้วย drugitems.income = income.income)",
                    "  2. drugitems_ref_code: เก็บประวัติรหัสมาตรฐานอ้างอิง (type 1 = รหัส 24 หลัก, type 2 = Barcode, type 3 = รหัส TMT, type 4 = รหัสยาแผนไทย)",
                    "  3. ttmt_code: รหัสมาตรฐานสมุนไพรและยาแผนไทย",
                    "  4. drugusage และ sp_use: วิธีใช้ยาปกติ และวิธีใช้ยาพิเศษสำหรับพิมพ์สติ๊กเกอร์ยา",
                    "  5. dphardep และ stock_item_drugitems: การจัดสรรคลังยาและการตัดยอดสต๊อกยา",
                    "  6. drugitems_price_plan: แผนการปรับราคาตามช่วงเวลาที่มีผลบังคับใช้",
                    "  7. drugitems_interaction และ drugitems_icd10: ระบบแจ้งเตือนการแพ้ยาและข้อห้ามใช้ตามโรค",
                    "",
                    "• ตารางแคตตาล็อกยาในฐานข้อมูล RiMS (hrims.drugcat_*):",
                    "  1. hrims.drugcat_nhso: แคตตาล็อกยา สปสช. (มี {$nhsoCatCount} รายการ) เชื่อมด้วย hospdrugcode = drugitems.icode เพื่อตรวจสอบการอนุมัติ, รหัส 24 หลัก (ndc24), TMT, ราคาเบิก สปสช. และหมวด ised (E=ในบัญชี, N=นอกบัญชี)",
                    "  2. hrims.drugcat_chi: แคตตาล็อกยา กรมบัญชีกลาง CSMBS (มี {$chiCatCount} รายการ) ใช้เปรียบเทียบราคาสิทธิข้าราชการและการเบิกจ่ายตรง",
                    "  3. hrims.drugcat_fdh: แคตตาล็อกยา Financial Data Hub (มี {$fdhCatCount} รายการ) ใช้ตรวจสอบความพร้อมในการส่งออกข้อมูลเคลม FDH MOPH",
                    "",
                    "(แนะนำการตั้งค่าใน HOSxP: เข้าเมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดรายการยา (Drug Items)' เพื่อบันทึกรหัส 24 หลัก, TMT และปรับโครงสร้างราคาแยกเก็บให้ครบถ้วน)"
                ];

                return [
                    'text' => "[โครงสร้างและความสัมพันธ์ของข้อมูลยาและแคตตาล็อก]:\n" . implode("\n", $lines),
                    'source' => [
                        'title' => "โครงสร้างข้อมูลยาและแคตตาล็อก HOSxP & RiMS",
                        'filename' => 'hosxp_drug_relations',
                        'page' => 1,
                        'score' => 98.0,
                        'snippet' => "สรุปความสัมพันธ์ของ drugitems, ราคาแยกเก็บ, และแคตตาล็อก hrims.drugcat_*"
                    ]
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Drug Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search lab_items and lab_items_sub_group (ข้อมูล Lab, Item vs Profile, การผูก icode และแคตตาล็อก hrims.labcat_*)
     */
    public function getLabItemContext(string $query, bool $force = false): ?array
    {
        try {
            if (!$force) {
                $hasLabKeyword = (bool) preg_match('/(lab|แลป|แล็บ|lab_items|lab_items_sub_group|profile|ชุดตรวจ|item|ตรวจเดี่ยว|tmlt|loinc|labcat|สิ่งส่งตรวจ|specimen|cbc|fbs|lipid|electrolyte|creatinine|urinalysis)/iu', $query);
                if (!$hasLabKeyword) {
                    return null;
                }
            }

            $localDb = config('database.connections.mysql.database');
            $hosxp = DB::connection('hosxp');

            // 1. General questions about Lab structure, Item vs Profile, or connected tables
            $isRelationQuery = (bool) preg_match('/(เชื่อม(โยง)?|ตารางไหน|ตารางอะไร|โครงสร้าง|item|profile|ชุดตรวจ|ตรวจเดี่ยว|labcat|ผูก\s*icode|nondrugitems)/iu', $query);
            $isSummaryQuery = (bool) preg_match('/(สรุป|สถิติ|มีกี่|ทั้งหมด|ยังไม่ผูก|ขาด\s*tmlt|ไม่(ได้)?ผูก)/iu', $query);

            if ($isRelationQuery || $isSummaryQuery || $force) {
                $totalLabItems = $hosxp->table('lab_items')->count();
                $activeLabItems = $hosxp->table('lab_items')->where('active_status', 'Y')->count();
                $unmappedItems = $hosxp->select("
                    SELECT COUNT(*) as c FROM lab_items l
                    LEFT JOIN nondrugitems n ON n.icode = l.icode
                    WHERE l.active_status = 'Y' AND (l.icode IS NULL OR l.icode = '' OR n.icode IS NULL)
                ")[0]->c ?? 0;
                $missingTmlt = $hosxp->table('lab_items')->where('active_status', 'Y')->where(function($q) {
                    $q->whereNull('tmlt_code')->orWhere('tmlt_code', '');
                })->count();

                $totalProfiles = $hosxp->table('lab_items_sub_group')->count();
                $unmappedProfiles = $hosxp->select("
                    SELECT COUNT(*) as c FROM lab_items_sub_group sg
                    LEFT JOIN nondrugitems n ON n.icode = sg.group_icode
                    WHERE (sg.active_status <> 'N' OR sg.active_status IS NULL) AND (sg.group_icode IS NULL OR sg.group_icode = '' OR n.icode IS NULL)
                ")[0]->c ?? 0;

                $labcatChiCount = DB::table('labcat_chi')->count();
                $labcatTmtCount = DB::table('labcat_tmt')->count();
                $labcatSsCount = DB::table('labcat_ss')->count();

                $lines = [
                    "ภาพรวมข้อมูล Lab ในระบบ HOSxP และการเชื่อมโยงแคตตาล็อก Lab RiMS:",
                    "• สถิติ Lab ในระบบ HOSxP:",
                    "  - รายการตรวจเดี่ยว (lab_items): ทั้งหมด {$totalLabItems} รายการ (เปิดใช้งาน {$activeLabItems} รายการ, ยังไม่ผูก icode คิดเงิน {$unmappedItems} รายการ, ขาดรหัส TMLT {$missingTmlt} รายการ)",
                    "  - ชุดตรวจ/โปรไฟล์ (lab_items_sub_group): ทั้งหมด {$totalProfiles} ชุดตรวจ (ยังไม่ผูก group_icode คิดเงิน {$unmappedProfiles} ชุด)",
                    "",
                    "• การจำแนกประเภท Item และ Profile (ตามแนวทาง Lab Catalog):",
                    "  1. Item (รายการตรวจเดี่ยว): อยู่ในตาราง lab_items เช่น FBS, Creatinine, BUN, SGOT, SGPT, Uric Acid",
                    "  2. Profile (ชุดตรวจ/โปรไฟล์): อยู่ในตาราง lab_items_sub_group เช่น CBC, Lipid Profile, Electrolyte, Liver Function Test (LFT), Urine Analysis (U/A)",
                    "",
                    "• กฎสำคัญเด็ดขาดเรื่องการผูกรหัสค่ารักษาพยาบาล (icode ใน nondrugitems):",
                    "  - รายการตรวจเดี่ยว lab_items ต้องผูกรหัส 'icode' เข้ากับตาราง 'nondrugitems'",
                    "  - ชุดตรวจโปรไฟล์ lab_items_sub_group ต้องผูกรหัส 'group_icode' เข้ากับตาราง 'nondrugitems'",
                    "  - ความสำคัญ: หากไม่มีการผูก icode เข้ากับ nondrugitems ระบบ HOSxP จะไม่สามารถคิดค่าบริการเข้าใบเสร็จ และไม่สามารถส่งออกเคลม e-Claim/FDH 16 แฟ้มได้เลย",
                    "",
                    "• โครงสร้างราคาค่าตรวจทางห้องปฏิบัติการ:",
                    "  - รายการเดี่ยว: service_price (ราคา OPD 1), service_price2, service_price3, service_price_ipd, service_cost (ต้นทุน)",
                    "  - ชุดตรวจโปรไฟล์: group_price (ราคาชุดตรวจ OPD 1), group_price2, group_price3, group_price_ipd",
                    "",
                    "• ตารางที่เชื่อมโยงกับ Lab ในระบบ HOSxP:",
                    "  1. nondrugitems: ตารางค่าบริการและค่ารักษาพยาบาล (ผูกผ่าน lab_items.icode หรือ lab_items_sub_group.group_icode)",
                    "  2. lab_items_group: หมวดกลุ่มงาน/แผนกทางห้องปฏิบัติการ (เช่น CLINICAL CHEMISTRY, HEMATOLOGY, CLINICAL MICROSCOPY)",
                    "  3. lab_specimen_items: ชนิดสิ่งส่งตรวจ (ผูกผ่าน specimen_code เช่น เลือด, ปัสสาวะ, อุจจาระ)",
                    "  4. lab_order, lab_order_service, lab_head: ตารางบันทึกการสั่งตรวจและการรายงานผลการตรวจของผู้ป่วย",
                    "",
                    "• ตารางแคตตาล็อก Lab ในฐานข้อมูล RiMS (hrims.labcat_*):",
                    "  1. hrims.labcat_chi: แคตตาล็อกแล็ป กรมบัญชีกลาง CSMBS (มี {$labcatChiCount} รายการ) เชื่อมด้วย lccode = nondrugitems.icode เพื่อตรวจสอบรหัส TMLT, LOINC, และราคาชดเชย",
                    "  2. hrims.labcat_tmt: แคตตาล็อกแล็ป TMT มาตรฐาน (มี {$labcatTmtCount} รายการ) สำหรับแมปรหัสการตรวจให้ได้มาตรฐานกระทรวงฯ",
                    "  3. hrims.labcat_ss: แคตตาล็อกแล็ป ประกันสังคม (มี {$labcatSsCount} รายการ) สำหรับตรวจสอบสิทธิประกันสังคม",
                    "  (ในแคตตาล็อกมีฟิลด์ panel ระบุว่า 'Y' คือชุดตรวจ หรือ 'N' คือรายการเดี่ยว)",
                    "",
                    "(แนะนำการตั้งค่าใน HOSxP: เข้าเมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดรายการตรวจทางห้องปฏิบัติการ (Lab Items)' และ 'กำหนดชุดตรวจ (Lab Sub Group)' เพื่อผูก icode และบันทึกรหัสมาตรฐาน TMLT/LOINC ให้ครบถ้วน)"
                ];

                return [
                    'text' => "[โครงสร้างและความสัมพันธ์ของข้อมูล Lab และแคตตาล็อก]:\n" . implode("\n", $lines),
                    'source' => [
                        'title' => "โครงสร้างข้อมูล Lab และแคตตาล็อก HOSxP & RiMS",
                        'filename' => 'hosxp_lab_relations',
                        'page' => 1,
                        'score' => 98.0,
                        'snippet' => "สรุปความสัมพันธ์ของ lab_items, lab_items_sub_group, nondrugitems และแคตตาล็อก hrims.labcat_*"
                    ]
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Lab Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Audit HOSxP Setting Readiness across major funds: 16 แฟ้ม/FDH, AIPN, SSOP, CSOP, CIPN
     */
    public function getFundAuditContext(string $query, bool $force = false): ?array
    {
        try {
            $isFundQuery = (bool) preg_match('/(16\s*แฟ้ม|fdh|aipn|ssop|csop|cipn|กองทุน|ส่งออก|เคลม|claim|audit|ตรวจสอบ(การตั้งค่า|ความพร้อม)?)/iu', $query);
            if (!$isFundQuery && !$force) {
                return null;
            }

            $hosxp = DB::connection('hosxp');

            // 1. DRU Audit
            $totalActiveDrugs = $hosxp->table('drugitems')->where('istatus', 'Y')->count();
            $drugsMissing24 = $hosxp->select("
                SELECT COUNT(*) as c FROM drugitems d
                LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 1
                WHERE d.istatus = 'Y' AND (d.did IS NULL OR LENGTH(TRIM(d.did)) < 24) AND (r.ref_code IS NULL OR LENGTH(TRIM(r.ref_code)) < 24)
            ")[0]->c ?? 0;
            $drugsMissingTmt = $hosxp->select("
                SELECT COUNT(*) as c FROM drugitems d
                LEFT JOIN drugitems_ref_code r ON r.icode = d.icode AND r.drugitems_ref_code_type_id = 3
                WHERE d.istatus = 'Y' AND (d.tmt_tp_code IS NULL OR d.tmt_tp_code = '') AND (r.ref_code IS NULL OR r.ref_code = '')
            ")[0]->c ?? 0;

            // 2. ADP Audit
            $totalActiveNondrug = $hosxp->table('nondrugitems')->where('istatus', 'Y')->count();
            $nondrugMissingAdp = $hosxp->table('nondrugitems')->where('istatus', 'Y')->where(function($q) {
                $q->whereNull('nhso_adp_code')->orWhere('nhso_adp_code', '');
            })->count();

            // 3. Doctor Audit
            $totalActiveDocs = $hosxp->table('doctor')->where('active', 'Y')->count();
            $docsMissingLic = $hosxp->table('doctor')->where('active', 'Y')->where(function($q) {
                $q->whereNull('licenseno')->orWhere('licenseno', '')->orWhere('licenseno', 'LIKE', '-%');
            })->count();
            $docsMissingCid = $hosxp->table('doctor')->where('active', 'Y')->where(function($q) {
                $q->whereNull('cid')->orWhereRaw('LENGTH(TRIM(cid)) != 13');
            })->count();

            // 4. Pttype Audit
            $totalActivePttype = $hosxp->table('pttype')->where('isuse', 'Y')->count();
            $pttypeMissingHipdata = $hosxp->table('pttype')->where('isuse', 'Y')->where(function($q) {
                $q->whereNull('hipdata_code')->orWhere('hipdata_code', '');
            })->count();

            // 5. Lab Audit
            $totalActiveLab = $hosxp->table('lab_items')->where('active_status', 'Y')->count();
            $labUnmappedIcode = $hosxp->select("
                SELECT COUNT(*) as c FROM lab_items l
                LEFT JOIN nondrugitems n ON n.icode = l.icode
                WHERE l.active_status = 'Y' AND (l.icode IS NULL OR l.icode = '' OR n.icode IS NULL)
            ")[0]->c ?? 0;
            $labMissingTmlt = $hosxp->table('lab_items')->where('active_status', 'Y')->where(function($q) {
                $q->whereNull('tmlt_code')->orWhere('tmlt_code', '');
            })->count();

            // 6. ICD-10 & ICD-9 Audit
            $inactiveIcd10 = $hosxp->table('icd101')->where('active_status', 'N')->count();
            $chiNotPdxCount = DB::table('lookup_icd10_chi')->where('accpdx', 'N')->count();
            $totalActiveIcd9 = $hosxp->table('icd9cm1')->where('active_status', 'Y')->count();

            // Specific fund checks
            $equipDevCount = DB::table('lookup_sss_equipdev_aipn')->count();
            $drugcatChiCount = DB::table('drugcat_chi')->count();
            $drugcatNhsoCount = DB::table('drugcat_nhso')->count();

            $lines = [
                "ผลการตรวจสอบความพร้อมของการตั้งค่าระบบ HOSxP ก่อนส่งออกแต่ละกองทุน (Fund Audit Summary):",
                "",
                "1. กองทุน 16 แฟ้ม / FDH (Financial Data Hub / e-Claim สปสช.):",
                "   • แฟ้ม DRU (ยา): ยาเปิดใช้งาน {$totalActiveDrugs} รายการ -> ขาดรหัส 24 หลัก {$drugsMissing24} รายการ, ขาดรหัส TMT {$drugsMissingTmt} รายการ",
                "   • แฟ้ม ADP (ค่าบริการ/หัตถการ): ค่าบริการเปิดใช้งาน {$totalActiveNondrug} รายการ -> ยังไม่ผูกรหัส ADP สปสช. {$nondrugMissingAdp} รายการ",
                "   • แฟ้ม PROVIDER (ผู้ให้บริการ): แพทย์ปฏิบัติงาน {$totalActiveDocs} ราย -> ไม่มี/เลขใบประกอบฯ ไม่ถูกต้อง {$docsMissingLic} ราย, เลขบัตร ปชช. ไม่ครบ 13 หลัก {$docsMissingCid} ราย",
                "   • แฟ้ม INS (สิทธิการรักษา): สิทธิเปิดใช้งาน {$totalActivePttype} สิทธิ -> ขาดรหัสส่งออก 16 แฟ้ม (hipdata_code) {$pttypeMissingHipdata} สิทธิ",
                "   • แฟ้ม DIAG (รหัสโรค): รหัสโรคที่ปิดใช้งานใน HOSxP {$inactiveIcd10} รายการ (active_status = 'N')",
                "",
                "2. กองทุน AIPN (ผู้ป่วยใน ประกันสังคม IPD):",
                "   • อุปกรณ์/อวัยวะเทียม: ใน RiMS มีแคตตาล็อกอุปกรณ์เทียมประกันสังคม {$equipDevCount} รายการ (lookup_sss_equipdev_aipn) ต้องผูกรหัสค่าบริการให้ตรงเพื่อป้องกันติด C",
                "   • รหัสยา: ตรวจสอบความสมบูรณ์ของรหัส 24 หลัก (ขาด {$drugsMissing24} รายการ)",
                "   • แพทย์ผู้รักษา: ตรวจสอบเลข ว. แพทย์ (ขาด {$docsMissingLic} ราย) เพื่อส่งออกใน XML AIPN",
                "   • รหัสหัตถการ ICD-9: เปิดใช้งาน {$totalActiveIcd9} รายการ",
                "",
                "3. กองทุน SSOP (ผู้ป่วยนอก ประกันสังคม OPD):",
                "   • ตรวจสอบรายการค่าบริการต้องมีรหัส ADP สปสช. (ขาด {$nondrugMissingAdp} รายการ)",
                "   • รายการยาต้องมีรหัส 24 หลักและ TMT ครบถ้วน",
                "   • สิทธิการรักษาประกันสังคมต้องกำหนด pcode = 'SS'",
                "",
                "4. กองทุน CSOP / CIPN (ข้าราชการ กรมบัญชีกลาง OPD/IPD):",
                "   • ยา: เทียบกับแคตตาล็อกยา กรมบัญชีกลาง CSMBS ({$drugcatChiCount} รายการ) ตรวจสอบราคาสิทธิข้าราชการ (sks_price) และยา จ(2)",
                "   • ค่าบริการ: ตรวจสอบการระบุรหัส billcode กรมบัญชีกลางใน nondrugitems",
                "   • รหัสโรคหลัก (PDX): รหัสที่ สกส. 'ไม่รับเป็นโรคหลัก' มี {$chiNotPdxCount} รายการ (accpdx = 'N' หากบันทึกเป็นโรคหลักจะถูก Reject หรือติด C-Code)",
                "   • สิทธิการรักษาข้าราชการต้องกำหนด pcode = 'OF'",
                "",
                "5. การตรวจทางห้องปฏิบัติการ (Lab Items & Profiles):",
                "   • รายการตรวจเดี่ยว lab_items ที่ยังไม่ผูก icode คิดเงิน: {$labUnmappedIcode} รายการ (คิดเงินและส่งเคลมไม่ได้)",
                "   • รายการแล็บที่ยังไม่ระบุรหัสมาตรฐาน TMLT: {$labMissingTmlt} รายการ",
                "",
                "6. รหัสโรค ICD-10 และรหัสหัตถการ ICD-9:",
                "   • รหัสโรคที่ปิดใช้งานใน HOSxP (active_status = 'N'): {$inactiveIcd10} รายการ",
                "   • รหัสโรคที่ สกส. ไม่รับเป็นโรคหลัก (accpdx = 'N'): {$chiNotPdxCount} รายการ",
                "   • รหัสหัตถการ ICD-9 ที่เปิดใช้งาน: {$totalActiveIcd9} รายการ"
            ];

            return [
                'text' => "[ผลการตรวจสอบการตั้งค่า HOSxP รายกองทุน]:\n" . implode("\n", $lines),
                'source' => [
                    'title' => "สรุปผลการตรวจสอบการตั้งค่าระบบ HOSxP รายกองทุน",
                    'filename' => 'hosxp_fund_audit',
                    'page' => 1,
                    'score' => 99.0,
                    'snippet' => "สรุปความพร้อมการตั้งค่า HOSxP ก่อนส่งออก 16 แฟ้ม, AIPN, SSOP, CSOP"
                ]
            ];
        } catch (\Throwable $e) {
            Log::warning("Fund Audit Context Warning: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search ICD-10 (icd101) and ICD-9 (icd9cm1), cross-check with lookup_icd10_chi (accpdx) and lookup_icd10 (pp/ods)
     */
    public function getIcdContext(string $query, bool $force = false): ?array
    {
        try {
            if (!$force) {
                $hasIcdKeyword = (bool) preg_match('/(icd|icd10|icd-10|icd9|icd-9|รหัสโรค|รหัสหัตถการ|การวินิจฉัย|diag|pdx|sdx|โรคหลัก|โรคแทรก|accpdx|ปิดรหัส|ปิดการใช้งาน|\b[a-zA-Z]\d{2,3}(?:\.\d{1,2})?\b|\b\d{2,4}(?:\.\d{1,2})?\b)/iu', $query);
                if (!$hasIcdKeyword) {
                    return null;
                }
            }

            $hosxp = DB::connection('hosxp');

            // Priority 1: Extract specific ICD-10 code (e.g. A00, I10, E11.9, K29.7, Z00.0, U50)
            $extractedCodes = [];
            if (preg_match_all('/\b([A-Za-z]\d{2,3}(?:\.\d{1,2})?)\b/', $query, $matches)) {
                $extractedCodes = array_map('strtoupper', $matches[1]);
            }

            // Also check for specific ICD-9 code (e.g. 89.07, 9904, 45.23)
            $extractedIcd9 = [];
            if (preg_match_all('/\b(\d{2}\.\d{1,2}|\b\d{4}\b)\b/', $query, $m9)) {
                $extractedIcd9 = $m9[1];
            }

            if (!empty($extractedCodes)) {
                $details = [];
                foreach (array_unique($extractedCodes) as $rawCode) {
                    $codeNoDot = str_replace('.', '', $rawCode);
                    $codeWithDot = (strlen($codeNoDot) > 3) ? substr($codeNoDot, 0, 3) . '.' . substr($codeNoDot, 3) : $codeNoDot;

                    // Lookup in HOSxP icd101
                    $row = $hosxp->table('icd101')
                        ->where('code', $rawCode)
                        ->orWhere('code', $codeNoDot)
                        ->orWhere('code', $codeWithDot)
                        ->first();

                    // Lookup in RiMS lookup_icd10_chi (สกส. กรมบัญชีกลาง)
                    $chi = DB::table('lookup_icd10_chi')
                        ->where('code', $rawCode)
                        ->orWhere('code', $codeNoDot)
                        ->orWhere('code', $codeWithDot)
                        ->first();

                    // Lookup in RiMS lookup_icd10 (สปสช.)
                    $nhso = DB::table('lookup_icd10')
                        ->where('icd10', $rawCode)
                        ->orWhere('icd10', $codeNoDot)
                        ->orWhere('icd10', $codeWithDot)
                        ->first();

                    if ($row || $chi || $nhso) {
                        $codeDisplay = $row->code ?? $rawCode;
                        $nameEng = $row->name ?? ($chi->desc ?? 'ไม่พบชื่อภาษาอังกฤษ');
                        $nameThai = $row->tname ?? 'ไม่ได้ระบุชื่อภาษาไทย';
                        $status = ($row->active_status ?? '') === 'Y' 
                            ? '✅ เปิดใช้งานปกติใน HOSxP (active_status = Y)' 
                            : '❌ ปิดการใช้งานใน HOSxP (active_status = N หรือว่าง: แพทย์ไม่สามารถสั่งใช้และห้ามส่งออก)';

                        // สกส. Rule (accpdx)
                        $chiStatus = '❓ ไม่พบในตารางตรวจสอบ สกส.';
                        if ($chi) {
                            if (($chi->accpdx ?? '') === 'Y') {
                                $chiStatus = '✅ สกส. (กรมบัญชีกลาง/ข้าราชการ) รับเป็นโรคหลัก (PDX) ได้ตามปกติ (accpdx = Y)';
                            } elseif (($chi->accpdx ?? '') === 'N') {
                                $chiStatus = '⚠️ สกส. (กรมบัญชีกลาง/ข้าราชการ) ไม่รับเป็นโรคหลักเด็ดขาด (accpdx = N: ห้ามใช้เป็น PDX ไม่งั้นจะถูกปฏิเสธเบิกเคลม CSOP/CIPN หรือติด C-Code ต้องใช้เป็น SDX เท่านั้น)';
                            } else {
                                $chiStatus = 'ℹ️ บันทึกใน สกส. (accpdx = ' . ($chi->accpdx ?: '-') . ')';
                            }
                        }

                        // สปสช. Rule (pp/ods)
                        $nhsoNotes = [];
                        if ($nhso) {
                            if (($nhso->pp ?? '') === 'Y') $nhsoNotes[] = 'บริการสร้างเสริมสุขภาพและป้องกันโรค (PP)';
                            if (($nhso->ods ?? '') === 'Y' || ($nhso->ods_p ?? '') === 'Y') $nhsoNotes[] = 'หัตถการวันเดียว One Day Surgery (ODS)';
                            if (($nhso->kidney ?? '') === 'Y') $nhsoNotes[] = 'กลุ่มโรคไต';
                            if (($nhso->hiv ?? '') === 'Y') $nhsoNotes[] = 'กลุ่มโรค HIV';
                            if (($nhso->tb ?? '') === 'Y') $nhsoNotes[] = 'กลุ่มวัณโรค TB';
                        }
                        $nhsoStatus = !empty($nhsoNotes) 
                            ? '⭐ สปสช. จัดกลุ่ม: ' . implode(', ', $nhsoNotes) 
                            : 'สปสช.: เป็นรหัสการรักษาพยาบาลทั่วไป';

                        $details[] = implode("\n", [
                            "• รหัสโรค: {$codeDisplay} ({$nameEng})",
                            "  - ชื่อภาษาไทย: {$nameThai}",
                            "  - สถานะใน HOSxP: {$status}",
                            "  - เกณฑ์กองทุนข้าราชการ (สกส.): {$chiStatus}",
                            "  - เกณฑ์กองทุนบัตรทอง (สปสช.): {$nhsoStatus}",
                            "  - ผู้ป่วยใน IPD: " . (($row->ipd_valid ?? 'Y') === 'Y' ? 'ใช้เป็นรหัสผู้ป่วยในได้' : 'ไม่อนุญาตให้ใช้กับ IPD'),
                            "  - การตั้งค่าใน HOSxP: เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรหัสโรค (ICD-10)' ค้นหารหัส '{$codeDisplay}' เพื่อแก้ไขสถานะเปิด/ปิด"
                        ]);
                    }
                }

                if (!empty($details)) {
                    return [
                        'text' => "[ผลการตรวจสอบรหัสโรค ICD-10 จากฐานข้อมูล HOSxP และแคตตาล็อกกองทุน]:\n" . implode("\n\n", $details),
                        'source' => [
                            'title' => "ผลการตรวจสอบรหัสโรค ICD-10 ใน HOSxP & กองทุน",
                            'filename' => 'hosxp_icd10_check',
                            'page' => 1,
                            'score' => 99.0,
                            'snippet' => "ตรวจสอบรหัสโรค สถานะเปิด/ปิด และเกณฑ์การรับเป็นโรคหลัก สกส./สปสช."
                        ]
                    ];
                }
            }

            // Priority 2: Check for ICD-9 procedures
            if (!empty($extractedIcd9)) {
                $procDetails = [];
                foreach (array_unique($extractedIcd9) as $rawProc) {
                    $procClean = str_replace('.', '', $rawProc);
                    $row9 = $hosxp->table('icd9cm1')
                        ->where('code', $rawProc)
                        ->orWhere('code', $procClean)
                        ->first();

                    $sss9 = DB::table('lookup_icd9_sss')
                        ->where('code', $rawProc)
                        ->orWhere('code', $procClean)
                        ->first();

                    if ($row9 || $sss9) {
                        $code9Display = $row9->code ?? $rawProc;
                        $nameProc = $row9->name ?? ($sss9->desc ?? 'ไม่ระบุชื่อหัตถการ');
                        $status9 = ($row9->active_status ?? '') === 'Y'
                            ? '✅ เปิดใช้งานปกติใน HOSxP (active_status = Y)'
                            : '❌ ปิดการใช้งานใน HOSxP (active_status = N หรือว่าง)';
                        $sssStatus = $sss9 ? '✅ มีในแคตตาล็อกหัตถการมาตรฐานประกันสังคม' : 'ℹ️ ไม่พบในรายการมาตรฐานประกันสังคม';

                        $procDetails[] = implode("\n", [
                            "• รหัสหัตถการ ICD-9-CM: {$code9Display} - {$nameProc}",
                            "  - สถานะใน HOSxP: {$status9}",
                            "  - ส่งออกหัตถการ: " . (($row9->export_proced ?? 'Y') === 'Y' ? 'ส่งออกปกติ (export_proced = Y)' : 'ไม่ส่งออก'),
                            "  - เกณฑ์ประกันสังคม: {$sssStatus}",
                            "  - การตั้งค่าใน HOSxP: เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรหัสหัตถการ (ICD-9-CM)'"
                        ]);
                    }
                }

                if (!empty($procDetails)) {
                    return [
                        'text' => "[ผลการตรวจสอบรหัสหัตถการ ICD-9 จากฐานข้อมูล HOSxP]:\n" . implode("\n\n", $procDetails),
                        'source' => [
                            'title' => "ผลการตรวจสอบรหัสหัตถการ ICD-9 ใน HOSxP",
                            'filename' => 'hosxp_icd9_check',
                            'page' => 1,
                            'score' => 98.0,
                            'snippet' => "ตรวจสอบรหัสหัตถการ ICD-9 สถานะเปิด/ปิด และเกณฑ์ประกันสังคม"
                        ]
                    ];
                }
            }

            // Priority 3: General summary & Audit of ICD-10 and ICD-9
            $isSummaryQuery = (bool) preg_match('/(สรุป|สถิติ|มีกี่|ทั้งหมด|ปิดรหัส|ปิดใช้งาน|สกส\s*ไม่รับ|accpdx|หัตถการ|icd10|icd9)/iu', $query);
            if ($isSummaryQuery || $force) {
                $totalIcd10 = $hosxp->table('icd101')->count();
                $inactiveIcd10 = $hosxp->table('icd101')->where('active_status', 'N')->count();
                $activeIcd10 = $totalIcd10 - $inactiveIcd10;

                $totalChi = DB::table('lookup_icd10_chi')->count();
                $chiNotPdx = DB::table('lookup_icd10_chi')->where('accpdx', 'N')->count();

                $totalNhsoPp = DB::table('lookup_icd10')->where('pp', 'Y')->count();
                $totalNhsoOds = DB::table('lookup_icd10')->where('ods', 'Y')->count();

                $totalIcd9 = $hosxp->table('icd9cm1')->count();
                $activeIcd9 = $hosxp->table('icd9cm1')->where('active_status', 'Y')->count();

                $lines = [
                    "ภาพรวมข้อมูลรหัสโรค (ICD-10) และรหัสหัตถการ (ICD-9) ในระบบ HOSxP และเกณฑ์กองทุน:",
                    "• รหัสโรค ICD-10 ใน HOSxP (icd101): ทั้งหมด " . number_format($totalIcd10) . " รายการ",
                    "  - เปิดใช้งาน (Active): " . number_format($activeIcd10) . " รายการ",
                    "  - ❌ ปิดการใช้งาน (Inactive: active_status = 'N'): " . number_format($inactiveIcd10) . " รายการ (ป้องกันแพทย์บันทึกรหัสที่ยกเลิก)",
                    "",
                    "• เกณฑ์กองทุนสวัสดิการข้าราชการ/กรมบัญชีกลาง (สกส. ใน RiMS: lookup_icd10_chi):",
                    "  - รหัสโรคในแคตตาล็อก สกส.: " . number_format($totalChi) . " รายการ",
                    "  - ⚠️ รหัสที่ สกส. 'ไม่รับเป็นโรคหลัก' (accpdx = 'N'): " . number_format($chiNotPdx) . " รายการ",
                    "    (ข้อควรระวัง: ห้ามให้แพทย์ลงรหัสกลุ่มนี้เป็น Principal Diagnosis / PDX โดยเด็ดขาด มิฉะนั้นการส่งเคลม CSOP/CIPN จะติด Error C-Code หรือถูกปฏิเสธชดเชยค่ารักษา)",
                    "",
                    "• เกณฑ์กองทุนหลักประกันสุขภาพแห่งชาติ (สปสช. ใน RiMS: lookup_icd10):",
                    "  - รหัสบริการส่งเสริมสุขภาพและป้องกันโรค (PP): " . number_format($totalNhsoPp) . " รายการ",
                    "  - รหัสหัตถการวันเดียว (ODS): " . number_format($totalNhsoOds) . " รายการ",
                    "",
                    "• รหัสหัตถการ ICD-9-CM ใน HOSxP (icd9cm1):",
                    "  - ทั้งหมด: " . number_format($totalIcd9) . " รายการ (เปิดใช้งาน " . number_format($activeIcd9) . " รายการ)",
                    "",
                    "คำแนะนำการตั้งค่าในโปรแกรม HOSxP:",
                    "1. เมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดรหัสโรค (ICD-10)': ใช้สำหรับตรวจสอบและเปิด/ปิดสถานะ active_status ของรหัสโรค",
                    "2. เมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดรหัสหัตถการ (ICD-9-CM)': ใช้สำหรับกำหนดสถานะ active_status และการส่งออก export_proced"
                ];

                return [
                    'text' => "[สรุปภาพรวมรหัสโรค ICD-10 และรหัสหัตถการ ICD-9]:\n" . implode("\n", $lines),
                    'source' => [
                        'title' => "สรุปข้อมูลรหัสโรค ICD-10 และหัตถการ ICD-9 ใน HOSxP",
                        'filename' => 'hosxp_icd_summary',
                        'page' => 1,
                        'score' => 99.0,
                        'snippet' => "สรุปรหัสโรคที่ปิดใช้งาน, เกณฑ์ สกส. accpdx=N และเกณฑ์ สปสช."
                    ]
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("ICD Context Warning: " . $e->getMessage());
            return null;
        }
    }
}
