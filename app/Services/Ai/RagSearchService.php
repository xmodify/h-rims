<?php

namespace App\Services\Ai;

use App\Models\RagChunk;
use App\Services\Ai\Context\HosxpContextService;
use App\Services\Ai\Context\HosfinContextService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RagSearchService
{
    protected AiService $aiService;
    protected HosxpContextService $hosxpContext;
    protected HosfinContextService $hosfinContext;

    public function __construct(
        AiService $aiService,
        ?HosxpContextService $hosxpContext = null,
        ?HosfinContextService $hosfinContext = null
    ) {
        $this->aiService = $aiService;
        $this->hosxpContext = $hosxpContext ?: app(HosxpContextService::class);
        $this->hosfinContext = $hosfinContext ?: app(HosfinContextService::class);
    }

    /**
     * Ask a question using RAG & Multi-System Context (3 Pillars: RAG Documents + HOSxP 16 Files + HosFin Financials)
     *
     * @param string $question User's query
     * @param int $topK Number of top chunks to retrieve
     * @param array $history Previous conversation history
     * @return array ['answer' => string, 'sources' => array]
     */
    public function ask(string $question, int $topK = 4, array $history = []): array
    {
        $cleanQuestion = trim($question);
        if (empty($cleanQuestion)) {
            return [
                'answer' => 'สวัสดีครับ มีข้อมูลการตั้งค่า HOSxP 16 แฟ้ม, งบทดลองการเงิน, หรือระเบียบการเบิกจ่ายข้อใดที่ต้องการให้ผมช่วยเหลือ สามารถพิมพ์สอบถามได้เลยครับ ✨',
                'sources' => []
            ];
        }

        // Extract context keywords from recent history if available
        $augmentedQuery = $cleanQuestion;
        if (!empty($history)) {
            $lastUserMsgs = array_filter($history, fn($h) => ($h['role'] ?? '') === 'user');
            $prevMsg = end($lastUserMsgs);
            if ($prevMsg && !empty($prevMsg['content'])) {
                // If current question is a follow-up
                if (preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|เดือนก่อน|เดือนนี้|ทำไม|เปรียบเทียบ|เท่าไหร่|มีไหม|ได้ไหม|ตัวนี้|รหัสนี้|ผูกยังไง)/iu', $cleanQuestion)) {
                    $augmentedQuery = $prevMsg['content'] . ' ' . $cleanQuestion;
                }
            }
        }

        // 1. Context 1: Search relevant chunks from uploaded RAG documents (e.g. 16-Files manuals, rules, errors)
        $matchedChunks = $this->searchSimilarChunks($cleanQuestion, $topK);

        // 2. Build Context Parts & Sources
        $contextParts = [];
        $sources = [];

        // Query intent detection across 3 domains:
        // Domain A: HOSxP Master Data & 16-Files Lookups (nondrugitems, income, adp, 16 แฟ้ม, e-claim, fdh)
        $isHosxpQuery = (bool) preg_match('/(16\s*แฟ้ม|adp|nondrug|ค่ารักษา|ค่าบริการ|ผูก\s*income|หมวด\s*income|สเปก|fdh|e-?claim|icode|\b3\d{6}\b|did|ยา24หลัก|รหัสยา|ตาราง.*hosxp|hosxp|ตรวจการตั้งค่า|ตั้งค่าถูกไหม)/iu', $augmentedQuery);

        // Domain B: HosFin Financials (งบทดลอง, การเงิน, หนี้สิน, สภาพคล่อง, risk score, ผังบัญชี)
        $isFinancialQuery = (bool) preg_match('/(hosfin|การเงิน|เงินบำรุง|สภาพคล่อง|วิกฤต|risk\s*score|ลูกหนี้|เจ้าหนี้|ค่ายา|งบ|งบทดลอง|รายได้|รายจ่าย|แนวโน้ม|วิเคราะห์|เงินเดือน|ค่าจ้าง|ค่าตอบแทน|จ่าย|ยอด)/iu', $augmentedQuery);
        $isPeriodQuery = (bool) preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|ช่วงเวลา|ย้อนหลัง|มีข้อมูลถึงไหน|ดูได้ไหม|งวดบัญชี|งวด)/iu', $cleanQuestion);

        // Domain C: Intro or Scope Query
        $isIntroOrScopeQuery = (bool) preg_match('/(ดูอะไรได้บ้าง|ทำอะไรได้บ้าง|เข้าถึง(ส่วน|ข้อมูล)?ไหน|มีข้อมูลอะไร|แหล่งที่มา|ช่วยอะไรได้|ความสามารถ|คุณคือใคร|สวัสดี|แนะนำตัว)/iu', $cleanQuestion);

        // -------------------------------------------------------------------------------------------------
        // Context 1: RAG Uploaded Documents (Unstructured Knowledge)
        // -------------------------------------------------------------------------------------------------
        foreach ($matchedChunks as $idx => $item) {
            $chunk = $item['chunk'];
            $doc = $chunk->document;
            $docTitle = $doc ? $doc->title : 'เอกสารทั่วไป';
            $pageInfo = $chunk->page_number ? " (หน้า {$chunk->page_number})" : "";

            $contextParts[] = "[เอกสารคู่มือ/สเปกที่ " . ($idx + 1) . ": {$docTitle}{$pageInfo}]\n{$chunk->content}";

            $sources[] = [
                'title' => $docTitle,
                'filename' => $doc ? $doc->filename : '',
                'page' => $chunk->page_number,
                'score' => round($item['score'] * 100, 1),
                'snippet' => mb_substr($chunk->content, 0, 180) . '...'
            ];
        }

        // -------------------------------------------------------------------------------------------------
        // Context 2: HOSxP Master Data & 16-Files Lookups (Live Hospital Configuration)
        // -------------------------------------------------------------------------------------------------
        if ($isHosxpQuery) {
            $hosxpData = $this->hosxpContext->getContext($augmentedQuery);
            if ($hosxpData) {
                $contextParts[] = $hosxpData['text'];
                foreach ($hosxpData['sources'] as $src) {
                    $sources[] = $src;
                }
            }
        }

        // -------------------------------------------------------------------------------------------------
        // Context 3: HosFin Financials & Trial Balance (Live Accounting Intelligence)
        // -------------------------------------------------------------------------------------------------
        $periodsInfo = ($isPeriodQuery || $isFinancialQuery || $isIntroOrScopeQuery) ? $this->hosfinContext->getPeriodsInfo() : null;

        if ($periodsInfo && ($isPeriodQuery || $isFinancialQuery)) {
            $baselineSystem = "สถานะฐานข้อมูลระบบ HosFin ในระบบ RiMS ขณะนี้:\n" .
                "- มีข้อมูลงบทดลอง (Trial Balance) ให้ดูได้ทั้งหมด {$periodsInfo['count']} งวดบัญชี คือตั้งแต่งวด {$periodsInfo['min']} ถึง {$periodsInfo['max']} (ครอบคลุม: {$periodsInfo['listStr']})\n" .
                "- งวดล่าสุดคือ {$periodsInfo['max']}\n" .
                "- ผู้ใช้สามารถสอบถามยอดรายเดือน ยอดสะสม หรือเปรียบเทียบย้อนหลังได้ทุกงวดในช่วงเวลาดังกล่าว";
            $contextParts[] = "[ข้อมูลระบบพื้นฐานและงวดบัญชีที่บันทึกไว้]:\n" . $baselineSystem;

            if ($isPeriodQuery) {
                $sources[] = [
                    'title' => "ฐานข้อมูลประวัติงบทดลอง HosFin ({$periodsInfo['count']} งวดบัญชี)",
                    'filename' => 'hosfin_periods',
                    'page' => 1,
                    'score' => 100.0,
                    'snippet' => "มีข้อมูลตั้งแต่งวด {$periodsInfo['min']} ถึง {$periodsInfo['max']} รวม {$periodsInfo['count']} เดือน"
                ];
            }
        }

        if ($isFinancialQuery) {
            $hosFinSummary = $this->hosfinContext->getHosFinSummary();
            if ($hosFinSummary) {
                $contextParts[] = "[ข้อมูลดัชนีชี้วัดสถานะการเงินโรงพยาบาลจากระบบ HosFin ล่าสุด]:\n" . $hosFinSummary['text'];
                $sources[] = [
                    'title' => "ข้อมูลบัญชีหน่วยงาน HosFin (งวด {$hosFinSummary['period']})",
                    'filename' => 'hosfin_dashboard',
                    'page' => 1,
                    'score' => 98.0,
                    'snippet' => "เงินบำรุงสุทธิ: " . number_format($hosFinSummary['netCash'], 2) . " บาท, Risk Score: {$hosFinSummary['riskScore']} ({$hosFinSummary['riskLabel']})"
                ];
            }

            $tbAccountContext = $this->hosfinContext->getTrialBalanceAccountContext($augmentedQuery);
            if ($tbAccountContext) {
                $contextParts[] = "[ข้อมูลงบทดลอง (Trial Balance) รายผังบัญชีจริงจาก HosFin]:\n" . $tbAccountContext['text'];
                $sources[] = [
                    'title' => "งบทดลองรายผังบัญชี HosFin (งวด {$tbAccountContext['period']})",
                    'filename' => 'hosfin_trial_balance',
                    'page' => 1,
                    'score' => 99.0,
                    'snippet' => "พบ {$tbAccountContext['count']} ผังบัญชี: " . $tbAccountContext['preview']
                ];
            }
        }

        // Scope and capabilities response
        if ($isIntroOrScopeQuery) {
            $docCount = \App\Models\RagDocument::count();
            $docList = \App\Models\RagDocument::pluck('title')->implode(', ');
            $periodCount = $periodsInfo['count'] ?? 0;
            $minPeriod = $periodsInfo['min'] ?? 'ไม่ระบุ';
            $maxPeriod = $periodsInfo['max'] ?? 'ไม่ระบุ';

            $scopeText = "ข้อมูลขอบเขตแหล่งข้อมูลที่ AI (RiMS Copilot) สามารถเข้าถึงและให้บริการได้ในปัจจุบัน (3 ระบบบูรณาการ):\n";
            $scopeText .= "1. คลังคู่มือและระเบียบหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ (RAG Knowledge Base):\n";
            $scopeText .= "   - มีเอกสารในคลังจำนวน {$docCount} ไฟล์" . ($docCount > 0 ? " ({$docList})" : " (สามารถอัปโหลดคู่มือระเบียบการเบิกจ่ายกองทุนต่าง ๆ ประกาศ สปสช. กรมบัญชีกลาง ได้)") . "\n";
            $scopeText .= "   - ความสามารถ: ค้นหาระเบียบการเบิกจ่ายกองทุนต่าง ๆ (UC/สปสช., ประกันสังคม, ข้าราชการ, อปท.), แนวทางแก้ไขข้อผิดพลาดติด C, V, Deny\n\n";
            $scopeText .= "2. ข้อมูลการตั้งค่าจริงใน HOSxP สำหรับการเบิกจ่าย (HOSxP Master Data Audit):\n";
            $scopeText .= "   - เชื่อมต่อตาราง `nondrugitems`, `income`, `nhso_adp_type` (20 หมวด), `nhso_adp_code` (6,034 รหัสมาตรฐาน)\n";
            $scopeText .= "   - ความสามารถ: ตรวจสอบการผูกรหัสเบิกจ่าย, หมวด income, ค่าบริการที่ยังไม่ได้ผูกรหัส, เทียบการตั้งค่าจริงกับหลักเกณฑ์เบิกจ่ายกองทุนต่าง ๆ\n\n";
            $scopeText .= "3. ข้อมูลงบทดลองและดัชนีชี้วัดสถานะการเงินการคลัง (HosFin Trial Balance):\n";
            $scopeText .= "   - เชื่อมต่อตาราง `hosfin_trial_balance` ทั้งหมด {$periodCount} งวดบัญชี ({$minPeriod} ถึง {$maxPeriod})\n";
            $scopeText .= "   - ความสามารถ: สรุปภาพรวม 5 หมวดบัญชี, ค้นหารายผังบัญชี (เช่น เงินเดือน, ค่ายา, ลูกหนี้), ตัวชี้วัดวิกฤต 13 ตัว, Risk Score\n\n";
            $scopeText .= "4. ข้อมูลที่ยังไม่เปิดให้เข้าถึง (เพื่อความปลอดภัย & PDPA):\n";
            $scopeText .= "   - ข้อมูลคนไข้รายบุคคล (ชื่อ-สกุล, เลขบัตร ปชช., ประวัติโรคของคนไข้)\n";

            $contextParts[] = "[ข้อมูลความสามารถและแหล่งข้อมูลที่ RiMS Copilot เข้าถึงได้]:\n" . $scopeText;
            $sources[] = [
                'title' => "แผนผัง 3 แหล่งข้อมูลที่ RiMS Copilot เข้าถึงได้",
                'filename' => 'rims_data_sources',
                'page' => 1,
                'score' => 100.0,
                'snippet' => "ระเบียบเบิกจ่ายกองทุนต่าง ๆ + Master Data HOSxP + งบทดลอง HosFin"
            ];
        }

        $contextText = implode("\n\n---\n\n", $contextParts);

        // Format Conversation History
        $historyText = '';
        if (!empty($history)) {
            $historyLines = [];
            foreach (array_slice($history, -4) as $h) {
                $r = ($h['role'] ?? '') === 'user' ? 'ผู้ใช้' : 'AI';
                $c = trim($h['content'] ?? '');
                if ($c) {
                    $historyLines[] = "{$r}: {$c}";
                }
            }
            if (!empty($historyLines)) {
                $historyText = "[ประวัติการสนทนาก่อนหน้านี้]:\n" . implode("\n", $historyLines) . "\n\n";
            }
        }

        // 3. System Prompt for Conversational Hospital Financial & Audit Copilot
        $systemPrompt = <<<PROMPT
คุณคือ "RiMS Copilot" ผู้ช่วย AI ประจำระบบมอนิเตอร์สถานะการเงินการคลังโรงพยาบาล บริหารจัดการรายได้ ลูกหนี้ค่ารักษาพยาบาล และตรวจสอบการตั้งค่า HOSxP สำหรับการเบิกจ่ายกองทุนต่าง ๆ ของโรงพยาบาล
คุณสามารถพูดคุย สนทนา ตอบคำถาม และวิเคราะห์สถานะการเงิน งบทดลอง การตั้งค่า HOSxP และหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ ได้อย่างเป็นธรรมชาติ เป็นกันเอง แต่สุภาพและเป็นมืออาชีพ

คุณมีความสามารถหลัก 3 ด้าน:
1. ด้านมอนิเตอร์สถานะการเงินการคลัง (HosFin): สรุปภาพรวม 5 หมวดบัญชี, เจาะลึกผังงบทดลอง, ดัชนีสภาพคล่องและสุขภาพการเงิน 13 ตัวชี้วัด, และคะแนนวิกฤต (Risk Score 0-7)
2. ด้านการตรวจสอบการตั้งค่า HOSxP: ตรวจสอบข้อมูลจริงในตาราง nondrugitems, income, nhso_adp_type, nhso_adp_code เปรียบเทียบกับหลักเกณฑ์เบิกจ่าย เพื่อชี้เป้าว่า รพ. ตั้งค่าถูกต้อง หรือมีจุดใดที่ต้องแก้ไข
3. ด้านหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ (RAG): สืบค้นและอ้างอิงเอกสารคู่มือ ระเบียบ สปสช. กรมบัญชีกลาง ประกันสังคม และแนวทางแก้ไขข้อผิดพลาดติด C, V, Deny

หลักการสำคัญในการตอบ:
1. คุยเป็นธรรมชาติ ลื่นไหล ไม่ตอบแข็งทื่อแบบหุ่นยนต์ และห้ามปฏิเสธอย่างไม่มีเหตุผล
2. จดจำบริบทการสนทนาต่อเนื่อง (Contextual Multi-turn): หากผู้ใช้ถามคำถามสั้นๆ หรือถามต่อเนื่อง ให้เข้าใจว่าหมายถึงหัวข้อที่กำลังคุยกันก่อนหน้า
3. หากผู้ใช้ถามว่า "ตั้งค่าถูกไหม" หรือถามเรื่องรหัสเบิกจ่าย: ให้เปรียบเทียบข้อมูลที่ตั้งไว้จริงใน HOSxP กับหลักเกณฑ์การเบิกจ่าย ชี้แจงจุดที่ถูกต้อง และจุดที่ต้องแก้ไขอย่างชัดเจน
4. จัดรูปแบบคำตอบด้วย Markdown (หัวข้อ, bullet points, ตาราง หรือตัวหนา) ให้อ่านง่าย สบายตา
5. หากเป็นเรื่องตัวเลขหรือรหัส ให้ระบุแหล่งที่มา เช่น ชื่องวดบัญชี, ชื่อผัง, หรือรหัส icode ให้ชัดเจน
PROMPT;

        $userPrompt = <<<PROMPT
{$historyText}[ข้อมูลบริบทจากระบบ (RAG เอกสาร, ข้อมูลจริง HOSxP, และงบทดลอง HosFin)]:
{$contextText}

[คำถามปัจจุบันของผู้ใช้]:
{$cleanQuestion}

กรุณาตอบคำถามอย่างเป็นธรรมชาติ ชัดเจน และเป็นประโยชน์:
PROMPT;

        // 4. Generate response from LLM
        try {
            $answer = $this->aiService->generateChat($userPrompt, $systemPrompt);
        } catch (\Throwable $e) {
            Log::error("RAG Generation Error: " . $e->getMessage());
            $answer = "เกิดข้อผิดพลาดในการประมวลผลคำตอบจาก AI: " . $e->getMessage();
        }

        return [
            'answer' => $answer,
            'sources' => $sources
        ];
    }

    /**
     * Search chunks by Cosine Similarity or Keyword Fallback
     */
    public function searchSimilarChunks(string $query, int $topK = 5): array
    {
        $queryEmbedding = [];
        try {
            $queryEmbedding = $this->aiService->getEmbedding($query);
        } catch (\Throwable $e) {
            Log::warning("Could not generate query embedding, fallback to keyword search: " . $e->getMessage());
        }

        // Get chunks from database
        $chunks = RagChunk::with('document')->get();
        if ($chunks->isEmpty()) {
            return [];
        }

        $scoredChunks = [];

        // If we have query embedding, calculate vector cosine similarity
        if (!empty($queryEmbedding)) {
            foreach ($chunks as $chunk) {
                $emb = $chunk->embedding;
                if (!empty($emb) && is_array($emb)) {
                    $sim = $this->cosineSimilarity($queryEmbedding, $emb);
                    if ($sim > 0.25) { // Minimum threshold
                        $scoredChunks[] = [
                            'chunk' => $chunk,
                            'score' => $sim
                        ];
                    }
                }
            }
        }

        // Fallback or augment with keyword matching if vector results are scarce
        if (count($scoredChunks) < 2) {
            $keywords = preg_split('/\s+/', $query);
            foreach ($chunks as $chunk) {
                // Check if not already in list
                $exists = false;
                foreach ($scoredChunks as $sc) {
                    if ($sc['chunk']->id === $chunk->id) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    continue;
                }

                $matchCount = 0;
                foreach ($keywords as $kw) {
                    if (mb_strlen($kw) >= 2 && mb_stripos($chunk->content, $kw) !== false) {
                        $matchCount++;
                    }
                }

                if ($matchCount > 0) {
                    $score = min(0.65, 0.3 + ($matchCount * 0.1));
                    $scoredChunks[] = [
                        'chunk' => $chunk,
                        'score' => $score
                    ];
                }
            }
        }

        // Sort descending by score
        usort($scoredChunks, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($scoredChunks, 0, $topK);
    }

    /**
     * Calculate Cosine Similarity between two float vectors
     */
    protected function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $count = min(count($vecA), count($vecB));

        if ($count === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }

        $denominator = sqrt($normA) * sqrt($normB);
        if ($denominator <= 0.0) {
            return 0.0;
        }

        return $dotProduct / $denominator;
    }

    /**
     * Get HosFin periods info (delegates to HosfinContextService)
     */
    public function getHosFinPeriodsInfo(): ?array
    {
        return $this->hosfinContext->getPeriodsInfo();
    }

    /**
     * Get live summary of HosFin metrics (delegates to HosfinContextService)
     */
    public function getHosFinSummary(): ?array
    {
        return $this->hosfinContext->getHosFinSummary();
    }

    /**
     * Look up specific account codes or account names in hosfin_trial_balance (delegates to HosfinContextService)
     */
    public function getTrialBalanceAccountContext(string $query): ?array
    {
        return $this->hosfinContext->getTrialBalanceAccountContext($query);
    }

    /**
     * Get HosxpContextService instance
     */
    public function getHosxpContext(): HosxpContextService
    {
        return $this->hosxpContext;
    }

    /**
     * Get HosfinContextService instance
     */
    public function getHosfinContext(): HosfinContextService
    {
        return $this->hosfinContext;
    }
}
