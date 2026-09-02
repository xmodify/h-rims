<?php

namespace App\Services\Ai;

use App\Models\RagChunk;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RagSearchService
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Ask a question using RAG
     *
     * @param string $question User's query
     * @param int $topK Number of top chunks to retrieve
     * @return array ['answer' => string, 'sources' => array]
     */
    public function ask(string $question, int $topK = 4, array $history = []): array
    {
        $cleanQuestion = trim($question);
        if (empty($cleanQuestion)) {
            return [
                'answer' => 'สวัสดีครับ มีข้อมูลการเงิน งบทดลอง หรือระเบียบการเบิกจ่ายข้อใดที่ต้องการให้ผมช่วยเหลือ สามารถพิมพ์สอบถามได้เลยครับ ✨',
                'sources' => []
            ];
        }

        // Extract context keywords from recent history if available
        $augmentedQuery = $cleanQuestion;
        if (!empty($history)) {
            $lastUserMsgs = array_filter($history, fn($h) => ($h['role'] ?? '') === 'user');
            $prevMsg = end($lastUserMsgs);
            if ($prevMsg && !empty($prevMsg['content'])) {
                // If current question is a follow-up (e.g. "ดูได้ทุกเดือนไหม", "แล้วถ้าเป็นเดือนก่อนล่ะ")
                if (preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|เดือนก่อน|เดือนนี้|ทำไม|เปรียบเทียบ|เท่าไหร่|มีไหม|ได้ไหม)/iu', $cleanQuestion)) {
                    $augmentedQuery = $prevMsg['content'] . ' ' . $cleanQuestion;
                }
            }
        }

        // 1. Search relevant chunks from uploaded documents
        $matchedChunks = $this->searchSimilarChunks($cleanQuestion, $topK);

        // 2. Build Context
        $contextParts = [];
        $sources = [];

        // Baseline system awareness: All available periods in HosFin
        $allPeriods = DB::table('hosfin_trial_balance')->distinct()->orderBy('acc_period', 'asc')->pluck('acc_period')->toArray();
        $minPeriod = reset($allPeriods) ?: '2568-09';
        $maxPeriod = end($allPeriods) ?: '2569-07';
        $periodCount = count($allPeriods);
        $periodListStr = implode(', ', $allPeriods);

        $baselineSystem = "สถานะฐานข้อมูลระบบ HosFin ในระบบ RiMS ขณะนี้:\n" .
            "- มีข้อมูลงบทดลอง (Trial Balance) ให้ดูได้ทั้งหมด {$periodCount} งวดบัญชี คือตั้งแต่งวด {$minPeriod} ถึง {$maxPeriod} (ครอบคลุม: {$periodListStr})\n" .
            "- งวดล่าสุดคือ {$maxPeriod}\n" .
            "- ผู้ใช้สามารถสอบถามยอดรายเดือน ยอดสะสม หรือเปรียบเทียบย้อนหลังได้ทุกงวดในช่วงเวลาดังกล่าว";
        $contextParts[] = "[ข้อมูลระบบพื้นฐานและงวดบัญชีที่บันทึกไว้]:\n" . $baselineSystem;

        // Check if user is asking about time range / all months (เช่น "ดูได้ทุกเดือนไหม", "มีเดือนไหนบ้าง")
        if (preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|ช่วงเวลา|ย้อนหลัง|มีข้อมูลถึงไหน|ดูได้ไหม)/iu', $cleanQuestion)) {
            $sources[] = [
                'title' => "ฐานข้อมูลประวัติงบทดลอง HosFin ({$periodCount} งวดบัญชี)",
                'filename' => 'hosfin_periods',
                'page' => 1,
                'score' => 100.0,
                'snippet' => "มีข้อมูลตั้งแต่งวด {$minPeriod} ถึง {$maxPeriod} รวม {$periodCount} เดือน"
            ];
        }

        // Check if query is asking about capabilities, data sources, or scope (เช่น "ดูอะไรได้บ้าง", "เข้าถึงส่วนไหนได้บ้าง")
        $isIntroOrScopeQuery = (bool) preg_match('/(ดูอะไรได้บ้าง|ทำอะไรได้บ้าง|เข้าถึง(ส่วน|ข้อมูล)?ไหน|มีข้อมูลอะไร|แหล่งที่มา|ช่วยอะไรได้|ความสามารถ|คุณคือใคร|สวัสดี|แนะนำตัว)/iu', $cleanQuestion);
        if ($isIntroOrScopeQuery) {
            $docCount = \App\Models\RagDocument::count();
            $docList = \App\Models\RagDocument::pluck('title')->implode(', ');

            $scopeText = "ข้อมูลขอบเขตแหล่งข้อมูลที่ AI (RiMS Copilot) สามารถเข้าถึงและให้บริการได้ในปัจจุบัน:\n";
            $scopeText .= "1. คลังเอกสารระเบียบราชการ (RAG Knowledge Base):\n";
            $scopeText .= "   - มีเอกสารในคลังจำนวน {$docCount} ไฟล์" . ($docCount > 0 ? " ({$docList})" : " (ยังไม่มีเอกสารอัปโหลด สามารถอัปโหลดไฟล์ PDF/DOCX ได้ที่หน้าคลังความรู้ AI)") . "\n";
            $scopeText .= "   - ความสามารถ: ค้นหาระเบียบการเบิกจ่าย, ผังบัญชีกระทรวง, แนวทางแก้ไขข้อผิดพลาดติด C, V, Deny\n\n";
            $scopeText .= "2. ข้อมูลงบทดลอง (HosFin Trial Balance):\n";
            $scopeText .= "   - เชื่อมต่อฐานข้อมูลตาราง `hosfin_trial_balance` ทั้งหมด {$periodCount} งวดบัญชี ({$minPeriod} ถึง {$maxPeriod})\n";
            $scopeText .= "   - ความสามารถ: สรุปภาพรวม 5 หมวด (สินทรัพย์, หนี้สิน, ทุน, รายได้, ค่าใช้จ่าย), ค้นหารายผังบัญชี (เช่น เงินเดือน, ค่ายา, ลูกหนี้), ตรวจสอบยอดเดบิต-เครดิต\n\n";
            $scopeText .= "3. ดัชนีชี้วัดสถานะการเงินโรงพยาบาล (HosFin Financial Distress Ratios):\n";
            $scopeText .= "   - ดึงตัวชี้วัด 13 ตัวแบบ Real-time: เงินบำรุงคงเหลือสุทธิ, Risk Score, สภาพคล่อง Current/Cash Ratio, หนี้ค่ายาค้างจ่าย, ลูกหนี้ข้าราชการ/UC ค้างท่อ\n\n";
            $scopeText .= "4. ข้อมูลที่ยังไม่เปิดให้เข้าถึง (เพื่อความปลอดภัย & PDPA):\n";
            $scopeText .= "   - ข้อมูลคนไข้รายบุคคล (ชื่อ-สกุล, เลขบัตร ปชช., ประวัติโรคของคนไข้)\n";

            $contextParts[] = "[ข้อมูลความสามารถและแหล่งข้อมูลที่ RiMS Copilot เข้าถึงได้]:\n" . $scopeText;
            $sources[] = [
                'title' => "แผนผังแหล่งข้อมูลที่ RiMS Copilot เข้าถึงได้",
                'filename' => 'rims_data_sources',
                'page' => 1,
                'score' => 100.0,
                'snippet' => "คลังเอกสาร ({$docCount} ไฟล์) + งบทดลอง HosFin ({$periodCount} งวด) + ดัชนีสุขภาพการเงิน"
            ];
        }

        // Check if query is about hospital financial status, HosFin, salaries, or accounts
        $isFinancialQuery = (bool) preg_match('/(hosfin|การเงิน|เงินบำรุง|สภาพคล่อง|วิกฤต|risk\s*score|ลูกหนี้|เจ้าหนี้|ค่ายา|งบ|รายได้|รายจ่าย|แนวโน้ม|วิเคราะห์|เงินเดือน|ค่าจ้าง|ค่าตอบแทน|ค่ารักษา|จ่าย|ยอด)/iu', $augmentedQuery);
        $hosFinSummary = $this->getHosFinSummary();

        if ($isFinancialQuery && $hosFinSummary) {
            $contextParts[] = "[ข้อมูลดัชนีชี้วัดสถานะการเงินโรงพยาบาลจากระบบ HosFin ล่าสุด]:\n" . $hosFinSummary['text'];
            $sources[] = [
                'title' => "ข้อมูลบัญชีหน่วยงาน HosFin (งวด {$hosFinSummary['period']})",
                'filename' => 'hosfin_dashboard',
                'page' => 1,
                'score' => 98.0,
                'snippet' => "เงินบำรุงสุทธิ: " . number_format($hosFinSummary['netCash'], 2) . " บาท, Risk Score: {$hosFinSummary['riskScore']} ({$hosFinSummary['riskLabel']})"
            ];
        }

        // Check if query is asking about specific trial balance accounts / codes
        $tbAccountContext = $this->getTrialBalanceAccountContext($augmentedQuery);
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

        foreach ($matchedChunks as $idx => $item) {
            $chunk = $item['chunk'];
            $doc = $chunk->document;
            $docTitle = $doc ? $doc->title : 'เอกสารทั่วไป';
            $pageInfo = $chunk->page_number ? " (หน้า {$chunk->page_number})" : "";

            $contextParts[] = "[เอกสารที่ " . ($idx + 1) . ": {$docTitle}{$pageInfo}]\n{$chunk->content}";

            $sources[] = [
                'title' => $docTitle,
                'filename' => $doc ? $doc->filename : '',
                'page' => $chunk->page_number,
                'score' => round($item['score'] * 100, 1),
                'snippet' => mb_substr($chunk->content, 0, 180) . '...'
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
คุณคือ "RiMS Copilot" ผู้ช่วย AI อัจฉริยะประจำระบบบริหารจัดการรายได้และลูกหนี้ค่ารักษาพยาบาลของโรงพยาบาล
คุณสามารถพูดคุย สนทนา ตอบคำถาม และวิเคราะห์สถานะการเงิน งบทดลอง และระเบียบราชการได้อย่างเป็นธรรมชาติ เป็นกันเอง แต่สุภาพและเป็นมืออาชีพ

หลักการสำคัญในการตอบ:
1. คุยเป็นธรรมชาติ ลื่นไหล ไม่ตอบแข็งทื่อแบบหุ่นยนต์ และห้ามปฏิเสธอย่างไม่มีเหตุผล
2. จดจำบริบทการสนทนาต่อเนื่อง (Contextual Multi-turn): หากผู้ใช้ถามคำถามสั้นๆ หรือคำถามต่อเนื่อง (เช่น "ดูได้ทุกเดือนไหม", "แล้วเดือนก่อนล่ะ", "ทำไม") ให้เข้าใจว่าหมายถึงหัวข้อที่กำลังคุยกันก่อนหน้า เช่น เรื่องเงินเดือน หรืองบทดลอง
3. หากผู้ใช้ถามว่า "ดูได้ทุกเดือนไหม" หรือถามเรื่องช่วงเวลางวดบัญชี ให้ตอบยืนยันอย่างกระตือรือร้นว่า "ดูได้ทุกเดือนเลยครับ!" พร้อมระบุว่าระบบมีข้อมูลงบทดลองตั้งแต่เดือนใดถึงเดือนใด (เช่น ก.ย. 68 ถึง ก.ค. 69 รวม 11 เดือน) และยกตัวอย่างคำสั่งที่ผู้ใช้สามารถสอบถามต่อได้
4. จัดรูปแบบคำตอบด้วย Markdown (หัวข้อ, bullet points, ตัวหนา) ให้อ่านง่าย สบายตา
5. หากเป็นเรื่องข้อมูลตัวเลข ให้ระบุแหล่งที่มา เช่น งวดบัญชี หรือชื่อผังบัญชีให้ชัดเจน
PROMPT;

        $userPrompt = <<<PROMPT
{$historyText}[ข้อมูลบริบทจากระบบ HosFin และคลังเอกสารโรงพยาบาล]:
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
     * Get live summary of HosFin metrics
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

            return [
                'text' => $text,
                'period' => $period,
                'netCash' => $netCash,
                'riskScore' => $riskScore,
                'riskLabel' => $riskLabel
            ];
        } catch (\Throwable $e) {
            Log::warning("HosFin Summary Extraction Warning: " . $e->getMessage());
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

            // 1.5 Check if query is asking for ratio formula breakdown or why a ratio is at that level
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

            // 2. If no code match, search by dynamic terms extracted from query
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

            // 3. If asking for Trial Balance Overview (เช่น "ดูงบทดลองได้ไหม", "งบทดลอง", "งบการเงิน")
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
                    $b = $t->debit_net > 0 ? ("เดบิต: " . number_format($t->debit_net, 2) . " บาท") : ("เครดิต: " . number_format($t->credit_net, 2) . " บาท");
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

            // 4. If asking for top accounts (e.g. สูงสุด, มากที่สุด)
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
            Log::warning("Trial Balance Account Extraction Warning: " . $e->getMessage());
            return null;
        }
    }
}
