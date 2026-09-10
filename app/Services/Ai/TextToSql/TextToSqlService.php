<?php

namespace App\Services\Ai\TextToSql;

use App\Services\Ai\AiService;
use App\Services\Ai\RagSearchService;
use App\Services\Ai\Security\SqlSecurityGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TextToSqlService
{
    protected AiService $aiService;
    protected SchemaCatalogService $schemaCatalog;
    protected SqlSecurityGuard $securityGuard;
    protected RagSearchService $ragSearchService;

    public function __construct(
        AiService $aiService,
        SchemaCatalogService $schemaCatalog,
        SqlSecurityGuard $securityGuard,
        RagSearchService $ragSearchService
    ) {
        $this->aiService = $aiService;
        $this->schemaCatalog = $schemaCatalog;
        $this->securityGuard = $securityGuard;
        $this->ragSearchService = $ragSearchService;
    }

    /**
     * Generate SQL from natural language question, validate security, execute, and compare with RAG knowledge
     *
     * @param string $question Natural language question
     * @param string $dbTarget 'auto', 'hrims', or 'hosxp'
     * @param array $history Previous conversation history
     * @return array
     */
    public function generateAndExecute(string $question, string $dbTarget = 'auto', array $history = []): array
    {
        $cleanQuestion = trim($question);
        if (empty($cleanQuestion)) {
            return [
                'success' => false,
                'message' => 'กรุณาระบุคำถามที่ต้องการค้นหา'
            ];
        }

        // 1. Resolve target database (auto-detect if 'auto')
        $target = $this->resolveTargetDatabase($cleanQuestion, $dbTarget);
        $contextScope = ($target === 'hosxp') ? 'hosxp' : 'hosfin';

        // 2. Retrieve relevant Schema
        // (HRiMS strictly hosfin_*, HOSxP strictly nondrugitems, pttype, doctor)
        $schema = ($target === 'hosxp')
            ? $this->schemaCatalog->getHosxpSchema($cleanQuestion)
            : $this->schemaCatalog->getHrimsSchema($cleanQuestion);

        // 3. Search relevant RAG knowledge from uploaded documents / manuals
        $ragContextText = '';
        $ragSources = [];
        try {
            $ragChunks = $this->ragSearchService->searchSimilarChunks($cleanQuestion, 4);
            if (!empty($ragChunks)) {
                $ragContextLines = [];
                foreach ($ragChunks as $item) {
                    $chunk = $item['chunk'] ?? null;
                    if (!$chunk) continue;
                    $doc = $chunk->document;
                    $docTitle = $doc ? $doc->title : 'คู่มือมาตรฐาน';
                    $ragContextLines[] = "[คู่มือ/ระเบียบ: {$docTitle} (หน้า {$chunk->page_number})]: " . $chunk->content;
                    $ragSources[] = [
                        'title' => $docTitle,
                        'filename' => $doc ? $doc->filename : '',
                        'page' => $chunk->page_number,
                        'snippet' => mb_substr($chunk->content, 0, 150) . '...'
                    ];
                }
                if (!empty($ragContextLines)) {
                    $ragContextText = "\n\n=== เอกสารคู่มือและระเบียบมาตรฐานที่เกี่ยวข้องจากคลังความรู้ RAG ===\n" . implode("\n\n", $ragContextLines);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("TextToSql RAG search error: " . $e->getMessage());
        }

        // 4. Build Prompt for LLM to generate SQL
        $systemPrompt = $this->buildSystemPrompt($target, $schema, $ragContextText);
        $userPrompt = $this->buildUserPrompt($cleanQuestion, $history);

        // 5. Call LLM to generate SQL
        $rawResponse = '';
        try {
            $rawResponse = $this->aiService->generateChat($userPrompt, $systemPrompt, $contextScope);
        } catch (\Throwable $e) {
            Log::error("TextToSql LLM Generation Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับโมเดล AI: ' . $e->getMessage(),
                'db_target' => $target
            ];
        }

        // 6. Parse SQL from LLM response
        $parsed = $this->parseLlmResponse($rawResponse);
        $generatedSql = $parsed['sql'];
        $explanation = $parsed['explanation'];

        if (empty($generatedSql)) {
            // If LLM returned a helpful explanation/guide without SQL, treat as successful conversational response
            if (!empty($explanation) && !preg_match('/(ไม่สามารถแปลง|error|syntax)/iu', $explanation)) {
                return [
                    'success' => true,
                    'summary' => $explanation,
                    'sql' => null,
                    'rows' => [],
                    'columns' => [],
                    'total_rows' => 0,
                    'db_target' => $target,
                    'execution_time_ms' => 0,
                    'suggestions' => [],
                    'sources' => $ragSources
                ];
            }

            return [
                'success' => false,
                'message' => $explanation ?: 'AI ไม่สามารถแปลงคำถามเป็นคำสั่ง SQL ที่ถูกต้องได้ กรุณาลองปรับคำถามให้เฉพาะเจาะจงขึ้น',
                'raw_response' => $rawResponse,
                'db_target' => $target
            ];
        }

        // 7. Validate with SqlSecurityGuard (Strict SELECT Only, No Multi-statement, Force LIMIT, Allowed Tables Only)
        $securityResult = $this->securityGuard->validateAndSanitize($generatedSql, $target);
        if (!$securityResult['is_valid']) {
            return [
                'success' => false,
                'message' => $securityResult['error'],
                'sql' => $generatedSql,
                'db_target' => $target
            ];
        }

        $sanitizedSql = $securityResult['sanitized_sql'];

        // 8. Execute SQL on target connection
        $connectionName = ($target === 'hosxp') ? 'hosxp' : 'mysql';
        $startTime = microtime(true);
        $rows = [];

        try {
            // Check connection first
            DB::connection($connectionName)->getPdo();
            $rows = DB::connection($connectionName)->select($sanitizedSql);
        } catch (\Throwable $e) {
            $err = $e->getMessage();
            Log::warning("TextToSql Execution Error on [{$connectionName}]: {$err} | SQL: {$sanitizedSql}");

            if (str_contains(strtolower($err), 'access denied') || str_contains(strtolower($err), 'connection refused') || str_contains(strtolower($err), 'unknown host')) {
                return [
                    'success' => false,
                    'message' => "ไม่สามารถเชื่อมต่อฐานข้อมูล [{$target}] ได้ (กรุณาตรวจสอบการตั้งค่า Host/Credentials หรือเครือข่าย รพ.)",
                    'sql' => $sanitizedSql,
                    'db_target' => $target
                ];
            }

            return [
                'success' => false,
                'message' => 'คำสั่ง SQL ขัดข้อง: ' . $err,
                'sql' => $sanitizedSql,
                'db_target' => $target
            ];
        }

        $execTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        // 9. Mask PDPA sensitive data (e.g. 13-digit CID)
        $isAdmin = auth()->check() && auth()->user()->status === 'admin';
        $maskedRows = $this->securityGuard->maskSensitiveData($rows, $isAdmin);

        // Convert rows to array representation and extract column headers
        $arrayRows = array_map(fn($r) => (array)$r, $maskedRows);
        $columns = !empty($arrayRows) ? array_keys($arrayRows[0]) : [];
        $columnLabels = $this->getColumnLabels($columns);

        // 10. Generate Executive Summary & Comparative Analysis with RAG
        $summary = $this->generateSummary(
            $cleanQuestion,
            $target,
            $arrayRows,
            $explanation,
            $ragContextText,
            $contextScope
        );
        $suggestions = $this->generateSuggestions($cleanQuestion, $target, $columns);

        return [
            'success' => true,
            'db_target' => $target,
            'sql' => $sanitizedSql,
            'explanation' => $explanation,
            'columns' => $columns,
            'column_labels' => $columnLabels,
            'rows' => $arrayRows,
            'total_rows' => count($arrayRows),
            'summary' => $summary,
            'suggestions' => $suggestions,
            'sources' => $ragSources,
            'execution_time_ms' => $execTimeMs,
        ];
    }

    /**
     * Resolve target database based on question intent
     */
    public function resolveTargetDatabase(string $question, string $preference = 'auto'): string
    {
        if (in_array($preference, ['hrims', 'hosxp'], true)) {
            return $preference;
        }

        $q = mb_strtolower($question, 'UTF-8');

        // Strong HOSxP indicators (Master configs: nondrug, pttype, doctor, adp, etc.)
        $isHosxp = (bool) preg_match('/(nondrug|ค่าบริการ|หัตถการ|adp|pttype|สิทธิการรักษา|16\s*แฟ้ม|hipdata|doctor|แพทย์|หมอ|licenseno|ใบประกอบ|สภาวิชาชีพ)/iu', $q);

        // Strong HRiMS indicators (HosFin: financial, fiscal, ap, ar, tb, journal, account, costs)
        $isHrims = (bool) preg_match('/(hosfin|การเงิน|การคลัง|ผังบัญชี|งบ|งบทดลอง|สมุดรายวัน|เจ้าหนี้|ลูกหนี้|บิล|ค้างจ่าย|ค้างชำระ|บริษัท|vendor|ap\b|ar\b|voucher|journal|กระแสเงินสด|เงินสด|ต้นทุน|สถิติ|หนี้สิน)/iu', $q);

        if ($isHosxp && !$isHrims) {
            return 'hosxp';
        }

        if ($isHrims && !$isHosxp) {
            return 'hrims';
        }

        return $isHosxp ? 'hosxp' : 'hrims';
    }

    /**
     * Build System Prompt for Text-to-SQL
     */
    protected function buildSystemPrompt(string $targetDb, string $schema, string $ragContext = ''): string
    {
        $dbTitle = ($targetDb === 'hosxp')
            ? 'HOSxP Master Data (ตรวจสอบการตั้งค่า: nondrugitems, pttype, doctor)'
            : 'HRiMS HosFin (ระบบการเงินการคลัง HosFin: สืบค้นและวิเคราะห์ข้อมูลจากตาราง hosfin_* ทั้งหมด 12 ตาราง)';

        return <<<EOT
คุณคือผู้เชี่ยวชาญด้านการเงินการคลังโรงพยาบาลและการวิเคราะห์ฐานข้อมูล (Hospital CFO & Senior Financial Database Analyst) สำหรับ {$dbTitle}
หน้าที่ของคุณ: แปลงคำถามภาษาไทยของผู้ใช้ ให้เป็นคำสั่ง SQL สำหรับ MariaDB/MySQL เพื่อดึงข้อมูลจริงทุกคอลัมน์และทุกแถวที่เกี่ยวข้อง นำมาตอบคำถามและวิเคราะห์ข้อมูลด้านการเงินการคลังได้อย่างถูกต้อง ปลอดภัย และแม่นยำสูงสุด

=== โครงสร้างตารางและคอลัมน์ที่อนุญาตให้ใช้งาน (Schema Definition) ===
{$schema}
{$ragContext}

=== กฎเหล็กด้านความปลอดภัยและความถูกต้อง (STRICT RULES) ===
1. ต้องสร้างเฉพาะคำสั่ง **SELECT** เท่านั้น ห้ามสร้าง INSERT, UPDATE, DELETE, DROP, ALTER, CREATE หรือคำสั่งแก้ไขข้อมูลใดๆ ทั้งสิ้น เด็ดขาด
2. ต้องเป็นคำสั่งเดี่ยว (Single Statement) ห้ามมีเครื่องหมายเซมิโคลอน (;) คั่นหลายคำสั่ง
3. สำหรับ HosFin: อ้างอิงเฉพาะตารางที่ขึ้นต้นด้วย `hosfin_` เท่านั้น (มีทั้งหมด 12 ตาราง: ap_bills, ar_debtors, trial_balance, daily_summaries, cost_summaries, monthly_balances, journals, journal_items, accounts, subledgers, dtl_mappings, sync_logs)
4. สำหรับ HOSxP: อ้างอิงเฉพาะตาราง `nondrugitems`, `pttype`, `doctor` เท่านั้นเพื่อตรวจสอบการตั้งค่าข้อมูลพื้นฐาน
5. การค้นหาข้อความภาษาไทย ให้ใช้ `LIKE '%...%'`
6. สำหรับคำถามภาพรวม ภาพรวมระบบ หรือถามว่าดูอะไรได้บ้าง (เช่น "ดูอะไรได้บ้าง", "มีข้อมูลอะไรบ้าง", "สรุปข้อมูลการเงิน", "ภาพรวมการเงิน"):
   - ห้ามค้นหา information_schema หรือ SHOW TABLES เด็ดขาด!
   - ให้สร้างคำสั่ง SELECT สรุปสถิติสำคัญจริงจากตาราง `hosfin_*` เช่น:
     SELECT 
       (SELECT COUNT(*) FROM hosfin_gl_ap_bills) AS total_ap_bills,
       (SELECT COALESCE(SUM(remaining_debt), 0) FROM hosfin_gl_ap_bills WHERE is_paid = 0) AS total_ap_unpaid,
       (SELECT COUNT(*) FROM hosfin_gl_ar_debtors) AS total_ar_debtors,
       (SELECT COALESCE(SUM(outstanding_balance), 0) FROM hosfin_gl_ar_debtors) AS total_ar_outstanding,
       (SELECT COUNT(*) FROM hosfin_trial_balance) AS total_tb_rows,
       (SELECT MAX(acc_year) FROM hosfin_trial_balance) AS latest_tb_year,
       (SELECT cash_balance FROM hosfin_gl_daily_summaries ORDER BY summary_date DESC LIMIT 1) AS latest_cash_balance,
       (SELECT COUNT(*) FROM hosfin_gl_journals) AS total_vouchers
7. ผลลัพธ์ต้องตอบกลับในรูปแบบ JSON แท้ 100% (ไม่มี Markdown นอก JSON) ตามโครงสร้างนี้:
{
  "sql": "SELECT ...",
  "explanation": "คำอธิบายภาษาไทยสั้นๆ ชัดเจน ว่าคำสั่งนี้ค้นหาอะไรจากตารางไหน เพื่อตอบคำถามเรื่องใด"
}
EOT;
    }

    /**
     * Build User Prompt including previous context
     */
    protected function buildUserPrompt(string $question, array $history = []): string
    {
        $context = '';
        if (!empty($history)) {
            $lastFew = array_slice($history, -4);
            $lines = [];
            foreach ($lastFew as $msg) {
                $role = ($msg['role'] ?? '') === 'user' ? 'ผู้ใช้' : 'AI';
                $content = $msg['content'] ?? '';
                if ($content) {
                    $lines[] = "{$role}: " . mb_substr($content, 0, 150);
                }
            }
            if (!empty($lines)) {
                $context = "บริบทการสนทนาก่อนหน้านี้:\n" . implode("\n", $lines) . "\n\n";
            }
        }

        return "{$context}คำถามของผู้ใช้: {$question}\n\nกรุณาตอบเป็น JSON เท่านั้นตามรูปแบบที่กำหนด:";
    }

    /**
     * Parse SQL and explanation from LLM JSON response
     */
    protected function parseLlmResponse(string $raw): array
    {
        $clean = trim($raw);

        // Strip ```json ... ``` code fence
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        // Try direct JSON decode
        $data = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['sql'])) {
            return [
                'sql' => trim($data['sql']),
                'explanation' => $data['explanation'] ?? ''
            ];
        }

        // Fallback regex if LLM responded with embedded JSON or raw SQL
        if (preg_match('/"sql"\s*:\s*"([^"]+)"/s', $clean, $m)) {
            return [
                'sql' => stripslashes($m[1]),
                'explanation' => preg_match('/"explanation"\s*:\s*"([^"]+)"/s', $clean, $ex) ? stripslashes($ex[1]) : ''
            ];
        }

        if (preg_match('/(SELECT\s+[\s\S]+?)(?:;|$)/i', $clean, $m)) {
            return [
                'sql' => trim($m[1]),
                'explanation' => 'ค้นหาข้อมูลตามเงื่อนไขที่ระบุ'
            ];
        }

        return [
            'sql' => '',
            'explanation' => $clean
        ];
    }

    /**
     * Generate summary and comparative financial analysis
     */
    protected function generateSummary(
        string $question,
        string $dbTarget,
        array $rows,
        string $explanation,
        string $ragContext = '',
        string $contextScope = 'hosfin'
    ): string {
        $count = count($rows);
        $dbName = ($dbTarget === 'hosxp') ? 'HOSxP (การตั้งค่าข้อมูลพื้นฐาน Master Data)' : 'HRiMS (ระบบการเงินการคลัง HosFin)';

        if ($count === 0) {
            return "ผลลัพธ์จากฐานข้อมูล {$dbName}: ไม่พบข้อมูลที่ตรงกับเงื่อนไข \"{$question}\"";
        }

        // Generate AI-powered Financial & Fiscal Analysis
        try {
            $sampleData = array_slice($rows, 0, 15);
            $sampleJson = json_encode($sampleData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            if ($dbTarget === 'hosxp') {
                $analysisPrompt = <<<EOT
คำถามของผู้ใช้: "{$question}"
ฐานข้อมูล: {$dbName}
คำอธิบายการประมวลผล: {$explanation}
ข้อมูลจริงที่ได้จากการสืบค้น (พบ {$count} รายการ, ตัวอย่างข้อมูล):
{$sampleJson}
{$ragContext}

หน้าที่ของคุณ: ตรวจสอบความถูกต้องสมบูรณ์ของการตั้งค่า Master Data ของโรงพยาบาล
1. **สรุปผลการตรวจสอบข้อมูล**:
   - สรุปจำนวนรายการที่พบ และลักษณะของข้อมูล
2. **วิเคราะห์ความถูกต้องเทียบกับมาตรฐาน**:
   - ตรวจสอบว่ามีการตั้งค่าครบถ้วนหรือไม่ เช่น การผูกรหัส ADP สปสช. (nhso_adp_code), รหัส 16 แฟ้ม (hipdata_code), หรือเลขที่ใบอนุญาตประกอบวิชาชีพแพทย์ (licenseno)
   - หากมีคู่มือจากคลังความรู้ RAG ให้เทียบเคียงข้อกำหนดมาตรฐาน
3. **ข้อเสนอแนะเพื่อแก้ไขปรับปรุง**:
   - ระบุสิ่งที่ต้องแก้ไขในระบบ HOSxP เพื่อป้องกัน Error ในการส่งเบิก e-Claim หรือการรายงานข้อมูล

ตอบเป็นภาษาไทย รูปแบบสวยงาม มีหัวข้อและ Bullet points ชัดเจน
EOT;
            } else {
                $analysisPrompt = <<<EOT
คำถามของผู้ใช้: "{$question}"
ฐานข้อมูล: {$dbName}
คำอธิบายการประมวลผล: {$explanation}
ข้อมูลจริงที่ได้จากการสืบค้นจากตาราง hosfin_* (พบ {$count} รายการ, ตัวอย่างข้อมูล):
{$sampleJson}
{$ragContext}

หน้าที่ของคุณ: ทำหน้าที่เป็นผู้เชี่ยวชาญด้านการเงินการคลังโรงพยาบาล (Hospital CFO & Senior Financial Analyst)
ให้วิเคราะห์ข้อมูลจริงที่ได้รับเพื่อตอบคำถามและให้มุมมองเชิงบริหารการเงินการคลัง:
1. **สรุปตัวเลขและประเด็นสำคัญ (Key Financial Figures)**:
   - นำตัวเลขจริงจากผลลัพธ์มาสรุปอย่างชัดเจน จัดรูปแบบตัวเลขให้อ่านง่าย เช่น มีจุลภาคคั่นหลักพัน (เช่น 47,200,451.67 บาท)
   - ระบุจำนวนรายการ ยอดรวม ยอดคงเหลือ หรือสัดส่วนสำคัญ
2. **วิเคราะห์สถานะการเงินการคลัง (Financial & Fiscal Analysis)**:
   - วิเคราะห์นัยยะทางการเงิน เช่น สภาพคล่องทางการเงิน (Liquidity), ภาระหนี้สินเจ้าหนี้การค้า (AP), การติดตามหนี้ลูกหนี้ค่ารักษาพยาบาล (AR), ความสมดุลของงบทดลอง หรือโครงสร้างต้นทุน
   - หากมีข้อมูลจากคลังความรู้ RAG (ระเบียบเงินบำรุง/ระเบียบการคลัง) ให้วิเคราะห์เทียบเคียงกับระเบียบด้วย
3. **ข้อเสนอแนะเชิงบริหารและการปฏิบัติการ (Actionable Recommendations)**:
   - คำแนะนำเพื่อการตัดสินใจ การวางแผนจ่ายหนี้ การเร่งรัดเรียกเก็บหนี้ หรือการปรับปรุงระบบบัญชีการเงิน

ตอบเป็นภาษาไทย รูปแบบสวยงาม มีหัวข้อและ Bullet points ชัดเจน
EOT;
            }

            $aiAnalysis = $this->aiService->generateChat($analysisPrompt, null, $contextScope);
            if (!empty($aiAnalysis) && mb_strlen($aiAnalysis, 'UTF-8') > 30) {
                return $aiAnalysis;
            }
        } catch (\Throwable $e) {
            Log::warning("TextToSql AI financial analysis failed: " . $e->getMessage());
        }

        // Standard fallback summary if AI call fails
        $firstRow = $rows[0];
        $numericSums = [];

        foreach ($firstRow as $col => $val) {
            if (is_numeric($val) && preg_match('/(total|count|sum|amount|net|remain|debt|income|expense|balance)/i', $col)) {
                $totalVal = array_sum(array_column($rows, $col));
                $numericSums[$col] = number_format($totalVal, 2);
            }
        }

        $summaryText = "ผลลัพธ์จากฐานข้อมูล {$dbName} พบทั้งหมด {$count} รายการ";
        if (!empty($numericSums)) {
            $parts = [];
            foreach ($numericSums as $col => $sum) {
                $parts[] = "{$col}: {$sum} บาท";
            }
            $summaryText .= " (" . implode(', ', $parts) . ")";
        }

        if (!empty($explanation)) {
            $summaryText .= "\n\n• **การประมวลผล:** " . $explanation;
        }

        return $summaryText;
    }

    /**
     * Generate dynamic follow-up suggestions (friendly business questions, no backend table names)
     */
    protected function generateSuggestions(string $question, string $dbTarget, array $columns): string
    {
        if ($dbTarget === 'hosxp') {
            return json_encode([
                "ตรวจรายการค่าบริการและหัตถการที่ยังไม่ผูกรหัส ADP สปสช.",
                "ตรวจสอบการตั้งค่าสิทธิการรักษาที่ยังไม่ผูกรหัสส่งออก 16 แฟ้ม",
                "ตรวจรายชื่อแพทย์และผู้ตรวจรักษาที่ไม่มีเลขที่ใบประกอบวิชาชีพ"
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            "จัดอันดับเจ้าหนี้การค้าที่มียอดหนี้ค้างชำระสูงสุด 10 อันดับ",
            "วิเคราะห์ลูกหนี้ค่ารักษาพยาบาลค้างชำระแยกตามสิทธิและกองทุน",
            "สรุปกระแสเงินสดและเงินสดคงเหลือสะสมรายวัน",
            "ขอยอดสรุปงบทดลอง (Trial Balance) ล่าสุด",
            "ดูโครงสร้างต้นทุนโรงพยาบาล (ค่าแรง, ค่าวัสดุ, ค่าลงทุน) รายเดือน"
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Map database column names to user-friendly Thai business labels
     */
    public function getColumnLabels(array $columns): array
    {
        $map = [
            // Aliases from overview & summary queries
            'total_ap_bills' => 'จำนวนบิลเจ้าหนี้',
            'total_ap_unpaid' => 'ยอดหนี้ค้างจ่าย (บาท)',
            'total_ar_debtors' => 'จำนวนลูกหนี้',
            'total_ar_outstanding' => 'ยอดหนี้คงค้าง (บาท)',
            'total_tb_rows' => 'จำนวนรายการงบทดลอง',
            'latest_tb_year' => 'ปีงบประมาณล่าสุด',
            'latest_cash_balance' => 'เงินสดคงเหลือสะสม (บาท)',
            'total_vouchers' => 'จำนวนใบสำคัญบันทึกบัญชี',
            'total_voucher' => 'จำนวนใบสำคัญบันทึกบัญชี',
            'collection_rate_percent' => 'อัตราการได้รับชดเชย (%)',
            'total_remaining_debt' => 'ยอดหนี้คงเหลือรวม (บาท)',
            'total_outstanding' => 'ยอดหนี้คงค้างรวม (บาท)',
            'debtor_count' => 'จำนวนลูกหนี้/หน่วยงาน',
            'bill_count' => 'จำนวนบิล',
            'total_bills' => 'จำนวนบิลทั้งหมด',

            // AP Bills (hosfin_gl_ap_bills)
            'id' => 'ลำดับ',
            'vendor_name' => 'ชื่อบริษัท/เจ้าหนี้',
            'category' => 'หมวดหมู่',
            'bill_no' => 'เลขที่บิล/ใบแจ้งหนี้',
            'bill_date' => 'วันที่ในบิล',
            'account_code' => 'รหัสบัญชี',
            'account_name' => 'ชื่อบัญชี',
            'total_credit' => 'ยอดหนี้รวม (บาท)',
            'total_debit' => 'ยอดชำระแล้ว (บาท)',
            'remaining_debt' => 'ยอดหนี้คงเหลือ (บาท)',
            'fiscal_year' => 'ปีงบประมาณ',
            'is_paid' => 'สถานะการชำระ',

            // AR Debtors (hosfin_gl_ar_debtors)
            'debtor_type' => 'ประเภทลูกหนี้/สิทธิ',
            'total_billed' => 'ยอดเรียกเก็บทั้งหมด (บาท)',
            'total_collected' => 'ยอดที่ชดเชยแล้ว (บาท)',
            'outstanding_balance' => 'ยอดหนี้คงค้าง (บาท)',
            'fiscal_month' => 'เดือนงบประมาณ',

            // Trial Balance (hosfin_trial_balance)
            'acc_year' => 'ปีบัญชี',
            'acc_month' => 'เดือนบัญชี',
            'acc_period' => 'งวดบัญชี',
            'main_account_code' => 'รหัสบัญชีหลัก',
            'debit_bf' => 'เดบิตยกมา (บาท)',
            'credit_bf' => 'เครดิตยกมา (บาท)',
            'debit_month' => 'เดบิตงวดนี้ (บาท)',
            'credit_month' => 'เครดิตงวดนี้ (บาท)',
            'debit_net' => 'เดบิตสุทธิยกไป (บาท)',
            'credit_net' => 'เครดิตสุทธิยกไป (บาท)',
            'import_filename' => 'ชื่อไฟล์นำเข้า',

            // Daily Summaries (hosfin_gl_daily_summaries)
            'summary_date' => 'วันที่สรุปข้อมูล',
            'total_income' => 'รายรับรวม (บาท)',
            'total_expense' => 'รายจ่ายรวม (บาท)',
            'net_cash_flow' => 'กระแสเงินสดสุทธิ (บาท)',
            'cash_balance' => 'เงินสดคงเหลือสะสม (บาท)',
            'voucher_count' => 'จำนวนใบสำคัญ',

            // Cost Summaries (hosfin_gl_cost_summaries)
            'lc_amount' => 'ค่าแรงบุคลากร LC (บาท)',
            'mc_amount' => 'ค่าวัสดุและยา MC (บาท)',
            'cc_amount' => 'ค่าลงทุน/เสื่อม CC (บาท)',
            'other_cost' => 'ต้นทุนอื่นๆ (บาท)',
            'total_cost' => 'ต้นทุนรวมทั้งหมด (บาท)',

            // Monthly Balances (hosfin_gl_monthly_balances)
            'beginning_debit' => 'เดบิตยกมาต้นงวด (บาท)',
            'beginning_credit' => 'เครดิตยกมาต้นงวด (บาท)',
            'period_debit' => 'เดบิตประจำงวด (บาท)',
            'period_credit' => 'เครดิตประจำงวด (บาท)',
            'ending_debit' => 'เดบิตสุทธิปลายงวด (บาท)',
            'ending_credit' => 'เครดิตสุทธิปลายงวด (บาท)',

            // Journals & Items (hosfin_gl_journals, hosfin_gl_journal_items)
            'voucher_no' => 'เลขที่ใบสำคัญ',
            'voucher_date' => 'วันที่ลงบัญชี',
            'journal_type' => 'ประเภทสมุดรายวัน',
            'description' => 'คำอธิบายรายการ',
            'posted_status' => 'สถานะผ่านรายการ',
            'apar' => 'ข้อมูลเจ้าหนี้/ลูกหนี้',
            'item_no' => 'ลำดับรายการ',
            'debit' => 'เดบิต (บาท)',
            'credit' => 'เครดิต (บาท)',
            'department' => 'แผนก/ศูนย์ต้นทุน',

            // Accounts (hosfin_gl_accounts)
            'account_type' => 'หมวดบัญชี',
            'account_category' => 'หมวดหมู่บัญชีย่อย',
            'normal_balance' => 'ด้านปกติ',
            'is_active' => 'สถานะเปิดใช้งาน',
            'cost_type' => 'ประเภทต้นทุน',
            'service_type' => 'ประเภทบริการ',

            // Subledgers (hosfin_gl_subledgers)
            'subledger_code' => 'รหัสบัญชีย่อย',
            'raw_note' => 'หมายเหตุ',

            // DTL & Sync Logs
            'group_code' => 'รหัสกลุ่ม',
            'group_name' => 'ชื่อกลุ่ม',
            'sync_type' => 'ประเภทการซิงค์',
            'records_count' => 'จำนวนรายการที่ประมวลผล',
            'status' => 'สถานะ',
            'message' => 'ข้อความผลการทำงาน',
            'duration_seconds' => 'เวลาที่ใช้ (วินาที)',

            // HOSxP Master
            'icode' => 'รหัสค่าบริการ',
            'name' => 'ชื่อรายการ',
            'price' => 'ราคาปกติ (บาท)',
            'price2' => 'ราคา 2 (บาท)',
            'price3' => 'ราคา 3 (บาท)',
            'income' => 'หมวดค่ารักษา',
            'nhso_adp_code' => 'รหัสมาตรฐาน ADP สปสช.',
            'billcode' => 'รหัสเบิกกรมบัญชีกลาง',
            'unit' => 'หน่วยนับ',
            'istat' => 'สถานะการใช้งาน',
            'istatus' => 'สถานะการใช้งาน',
            'pttype' => 'รหัสสิทธิการรักษา',
            'pcode' => 'กลุ่มสิทธิมาตรฐาน',
            'hipdata_code' => 'รหัสส่งออก 16 แฟ้ม',
            'max_debt_money' => 'เพดานหนี้สูงสุด (บาท)',
            'paidst' => 'สถานะการชำระเงิน',
            'isuse' => 'สถานะเปิดใช้งาน',
            'code' => 'รหัสแพทย์',
            'licenseno' => 'เลขที่ใบประกอบวิชาชีพ',
            'council_code' => 'รหัสสภาวิชาชีพ',
            'position_id' => 'รหัสตำแหน่ง',
            'spclty' => 'รหัสสาขาความเชี่ยวชาญ',
            'clinic' => 'รหัสคลินิก',
            'cid' => 'เลขประจำตัวประชาชน',
            'active' => 'สถานะปฏิบัติงาน',

            // HOSxP Pricing by Rights (pttype_items_price) & Opitemrece
            'pttype_items_price_id' => 'รหัสราคาตามสิทธิ',
            'items_table_name' => 'ประเภทตารางรายการ',
            'items_table_code' => 'รหัสรายการ (icode)',
            'pttype_price_group_id' => 'รหัสกลุ่มราคาตามสิทธิ',
            'discount_percent' => 'ส่วนลดตามสิทธิ (%)',
            'unitprice' => 'ราคาต่อหน่วย (บาท)',
            'sum_price' => 'ยอดเงินรวม (บาท)',
            'qty' => 'จำนวน',
            'vn' => 'เลขที่รับบริการ (VN)',
            'an' => 'เลขที่ผู้ป่วยใน (AN)',
            'hn' => 'เลขประจำตัวผู้ป่วย (HN)',

            // General aggregations
            'count' => 'จำนวนรายการ',
            'cnt' => 'จำนวนรายการ',
            'total' => 'ยอดรวม',
            'sum' => 'ยอดรวม (บาท)',
            'sum_amount' => 'ยอดเงินรวม (บาท)',
            'total_amount' => 'ยอดเงินรวม (บาท)',
            'avg_amount' => 'ยอดเงินเฉลี่ย (บาท)',
        ];

        $labels = [];
        foreach ($columns as $col) {
            $colStr = (string)$col;
            if (isset($map[$colStr])) {
                $labels[$colStr] = $map[$colStr];
            } else {
                // Fallback translation
                $label = str_replace('_', ' ', $colStr);
                $label = preg_replace('/^total\s+/i', 'ยอดรวม ', $label);
                $label = preg_replace('/^sum\s+/i', 'ยอดรวม ', $label);
                $label = preg_replace('/^count\s+/i', 'จำนวน ', $label);
                $label = preg_replace('/^latest\s+/i', 'ล่าสุด ', $label);
                $labels[$colStr] = trim($label);
            }
        }

        return $labels;
    }
}
