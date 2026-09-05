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
     * @param string|null $pageContext Current page route / context (e.g. '/hosfin')
     * @return array ['answer' => string, 'sources' => array]
     */
    public function ask(string $question, int $topK = 4, array $history = [], ?string $pageContext = null): array
    {
        $cleanQuestion = trim($question);
        if (empty($cleanQuestion)) {
            return [
                'answer' => 'สวัสดีครับ มีข้อมูลสถานะการเงินการคลัง, บิลเจ้าหนี้ AP, ลูกหนี้ค่ารักษา, หรือระเบียบข้อใดที่ต้องการให้ผมช่วยเหลือ สามารถพิมพ์สอบถามได้เลยครับ ✨',
                'sources' => []
            ];
        }

        $isHosfinPage = $pageContext && (str_contains($pageContext, 'hosfin') || str_contains($pageContext, 'financial'));
        $isRagPage = $pageContext && (str_contains($pageContext, 'rag-knowledge') || str_contains($pageContext, 'rag'));

        // On RAG page, retrieve more chunks (8) for thorough multi-document coverage
        if ($isRagPage && $topK < 8) {
            $topK = 8;
        }

        // Extract context keywords from recent history if available
        $augmentedQuery = $cleanQuestion;
        if (!empty($history)) {
            $lastUserMsgs = array_filter($history, fn($h) => ($h['role'] ?? '') === 'user');
            $prevMsg = end($lastUserMsgs);
            if ($prevMsg && !empty($prevMsg['content'])) {
                // If current question is a follow-up
                if (preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|เดือนก่อน|เดือนนี้|ทำไม|เปรียบเทียบ|เท่าไหร่|มีไหม|ได้ไหม|ตัวนี้|รหัสนี้|ผูกยังไง|บริษัทไหน|จ่ายใคร|จ่ายก่อน)/iu', $cleanQuestion)) {
                    $augmentedQuery = $prevMsg['content'] . ' ' . $cleanQuestion;
                }
            }
        }

        // 1. Context 1: Search relevant chunks from uploaded RAG documents with True Hybrid Search
        $matchedChunks = $this->searchSimilarChunks($cleanQuestion, $topK);

        // 2. Build Context Parts & Sources
        $contextParts = [];
        $sources = [];

        // Global Knowledge Base Document Inventory: Always inform AI what documents exist in the system
        $allDocs = \App\Models\RagDocument::where('status', 'completed')->get(['id', 'title', 'filename', 'chunk_count']);
        if ($allDocs->isNotEmpty()) {
            $docLines = [];
            foreach ($allDocs as $d) {
                $docLines[] = "- [เอกสาร #{$d->id}] \"{$d->title}\" (ไฟล์ {$d->filename}, บันทึกไว้ {$d->chunk_count} ย่อหน้า)";
            }
            $inventoryNotice = "[สารบัญเอกสารทั้งหมดในคลังความรู้ RAG ที่พร้อมใช้งานขณะนี้ (รวม " . $allDocs->count() . " เล่ม)]:\n"
                . implode("\n", $docLines) . "\n"
                . "*ข้อกำหนดเด็ดขาดสำหรับ AI: ระบบมีเอกสารทั้งหมดข้างต้นอยู่ในระบบแล้ว ห้ามตอบว่าระบบไม่มีเอกสารเหล่านี้ หรือบอกให้ผู้ใช้อัปโหลดเอกสารที่มีอยู่แล้วซ้ำอีก หากผู้ใช้ถามถึงเล่มใด ให้อ้างอิงจากเนื้อหาและสารบัญเอกสารด้านล่างนี้ได้ทันที*";
            $contextParts[] = $inventoryNotice;
        }

        // Query intent detection across 3 domains:
        // Domain A: HOSxP Master Data & 16-Files Lookups (nondrugitems, income, adp, 16 แฟ้ม, e-claim, fdh)
        // If on RAG page, only lookup HOSxP if user explicitly mentions HOSxP configuration check
        $isHosxpQuery = $isRagPage
            ? (bool) preg_match('/(ตรวจการตั้งค่า|ตั้งค่าถูกไหม|ใน\s*hosxp|เทียบกับ\s*hosxp|ตาราง.*hosxp)/iu', $augmentedQuery)
            : (bool) preg_match('/(16\s*แฟ้ม|adp|nondrug|ค่ารักษา|ค่าบริการ|ผูก\s*income|หมวด\s*income|สเปก|fdh|e-?claim|icode|\b3\d{6}\b|did|ยา24หลัก|รหัสยา|ตาราง.*hosxp|hosxp|ตรวจการตั้งค่า|ตั้งค่าถูกไหม)/iu', $augmentedQuery);

        // Domain B: HosFin Financials (งบทดลอง, การเงิน, หนี้สิน, สภาพคล่อง, risk score, ผังบัญชี, AP, AR, GL)
        // If on RAG page, strictly DISABLE HosFin financial data injection (focus 100% on RAG documents)
        $isFinancialQuery = !$isRagPage && ($isHosfinPage || (bool) preg_match('/(hosfin|การเงิน|เงินบำรุง|สภาพคล่อง|วิกฤต|risk\s*score|ลูกหนี้|เจ้าหนี้|ค่ายา|งบ|งบทดลอง|รายได้|รายจ่าย|แนวโน้ม|วิเคราะห์|เงินเดือน|ค่าจ้าง|ค่าตอบแทน|จ่าย|ยอด|บริษัท|บิล|ค้างชำระ|aging|สมุดรายวัน|ใบสำคัญ|voucher)/iu', $augmentedQuery));
        $isPeriodQuery = !$isRagPage && (bool) preg_match('/(ทุกเดือน|เดือนไหน|กี่เดือน|ช่วงเวลา|ย้อนหลัง|มีข้อมูลถึงไหน|ดูได้ไหม|งวดบัญชี|งวด)/iu', $cleanQuestion);

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

            $contextParts[] = "[เอกสารคู่มือ/ระเบียบมาตรฐานที่ " . ($idx + 1) . ": {$docTitle}{$pageInfo}]\n{$chunk->content}";

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
                "- มีข้อมูลงบทดลอง (Trial Balance) และบัญชี GL ให้ดูได้ทั้งหมด {$periodsInfo['count']} งวดบัญชี คือตั้งแต่งวด {$periodsInfo['min']} ถึง {$periodsInfo['max']} (ครอบคลุม: {$periodsInfo['listStr']})\n" .
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
            // A. Live GL Executive Summary
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

            // B. AP Creditors & Bills Lookup (from hosfin_gl_ap_bills)
            $apVendorContext = $this->hosfinContext->getApVendorContext($augmentedQuery);
            if ($apVendorContext) {
                $contextParts[] = "[ข้อมูลบิลเจ้าหนี้การค้ารายบริษัทจากระบบ GL (AP Bills)]:\n" . $apVendorContext['text'];
                $sources[] = [
                    'title' => "บิลเจ้าหนี้การค้ารายบริษัท GL (hosfin_gl_ap_bills)",
                    'filename' => 'hosfin_gl_ap_bills',
                    'page' => 1,
                    'score' => 99.0,
                    'snippet' => $apVendorContext['preview']
                ];
            }

            // C. AR Debtors Lookup (from hosfin_gl_ar_debtors)
            $arDebtorContext = $this->hosfinContext->getArDebtorContext($augmentedQuery);
            if ($arDebtorContext) {
                $contextParts[] = "[ข้อมูลลูกหนี้ค่ารักษาพยาบาลแยกตามสิทธิจากระบบ GL (AR Debtors)]:\n" . $arDebtorContext['text'];
                $sources[] = [
                    'title' => "ลูกหนี้ค่ารักษาพยาบาลแยกตามสิทธิ GL (hosfin_gl_ar_debtors)",
                    'filename' => 'hosfin_gl_ar_debtors',
                    'page' => 1,
                    'score' => 99.0,
                    'snippet' => $arDebtorContext['preview']
                ];
            }

            // D. Journal Lookup (from hosfin_gl_journals & journal_items)
            $journalContext = $this->hosfinContext->getJournalContext($augmentedQuery);
            if ($journalContext) {
                $contextParts[] = "[ข้อมูลสมุดรายวันทั่วไปและใบสำคัญ GL (Journals)]:\n" . $journalContext['text'];
                $sources[] = [
                    'title' => "สมุดรายวันทั่วไป GL (hosfin_gl_journals)",
                    'filename' => 'hosfin_gl_journals',
                    'page' => 1,
                    'score' => 95.0,
                    'snippet' => $journalContext['preview']
                ];
            }

            // E. Trial Balance Account Lookup (from hosfin_trial_balance)
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

            if ($isRagPage) {
                $scopeText = "ข้อมูลคลังเอกสารและระเบียบการเบิกจ่าย (RAG Knowledge Base) ในระบบ RiMS ขณะนี้:\n";
                $scopeText .= "- มีเอกสารคู่มือ/ระเบียบในคลังทั้งหมด {$docCount} ฉบับ" . ($docCount > 0 ? " ({$docList})" : " (สามารถอัปโหลดไฟล์คู่มือ ระเบียบ สปสช. หรือประกาศทางการแพทย์ได้ในหน้านี้)") . "\n";
                $scopeText .= "- ขอบเขตการตอบคำถามในหน้านี้: ค้นหาและตอบคำถามจาก 'คลังเอกสารและระเบียบคู่มือ' โดยเฉพาะ เช่น ระเบียบการเบิกจ่ายกองทุนต่าง ๆ (UC/สปสช., ข้าราชการ, ประกันสังคม, อปท.), แนวทางแก้ไขข้อผิดพลาดติด C, V, Deny และขั้นตอนตามคู่มือ 16 แฟ้มมาตรฐาน\n";
                $scopeText .= "- ในหน้านี้จะไม่มีการดึงตัวเลขบัญชี GL หนี้สิน หรือข้อมูลการเงิน เพื่อให้การตอบคำถามตรงตามเอกสารคู่มืออย่างแม่นยำที่สุดครับ";

                $contextParts[] = "[ข้อมูลความสามารถและแหล่งเอกสาร RAG]:\n" . $scopeText;
                $sources[] = [
                    'title' => "คลังเอกสารคู่มือและระเบียบ RAG ({$docCount} ฉบับ)",
                    'filename' => 'rag_documents_index',
                    'page' => 1,
                    'score' => 100.0,
                    'snippet' => "เอกสาร {$docCount} ฉบับ: " . mb_substr($docList, 0, 100) . '...'
                ];
            } else {
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

        // 3. System Prompt tailored by page context
        if ($isRagPage) {
            $systemPrompt = <<<PROMPT
คุณคือ "RiMS Copilot (RAG Knowledge Specialist)" ผู้ช่วย AI อัจฉริยะประจำคลังความรู้ ระเบียบ และคู่มือการเบิกจ่ายกองทุนสุขภาพ (สปสช., ประกันสังคม, ข้าราชการ, อปท.) และแนวทางแก้ไขข้อผิดพลาด (เช่น ติด C, V, Deny) ประจำระบบ RiMS

บทบาทและหน้าที่ของคุณในหน้านี้ (คลังเอกสารและระเบียบคู่มือ RAG):
1. ค้นหาและตอบคำถามโดยอ้างอิงจาก "เอกสารคู่มือ/ระเบียบมาตรฐานที่ได้รับ" ในระบบเป็นหลัก
2. ระบุชื่อเอกสาร และเลขหน้า (ถ้ามี) ที่ใช้อ้างอิงในคำตอบเสมอ เพื่อให้ผู้ใช้สามารถตรวจสอบกลับไปยังเอกสารต้นฉบับได้
3. หากในเอกสารที่ค้นพบมีข้อมูลตรงกับคำถาม ให้อธิบายเป็นขั้นตอน ชัดเจน ตรงประเด็น และสรุปประเด็นสำคัญให้อ่านเข้าใจง่าย
4. หากในคลังเอกสารยังไม่มีข้อมูลเกี่ยวกับเรื่องที่ถาม ให้แจ้งผู้ใช้อย่างสุภาพตามตรงว่า "จากการสืบค้นในคลังเอกสารคู่มือปัจจุบัน ยังไม่พบข้อมูลเรื่องดังกล่าว ผู้ดูแลระบบสามารถอัปโหลดไฟล์คู่มือหรือประกาศเพิ่มเติมได้ในหน้านี้ครับ" โดยห้ามแต่งเติมข้อมูลที่ไม่มีในเอกสาร
5. จัดรูปแบบคำตอบด้วย Markdown อย่างสวยงาม ให้อ่านง่าย สบายตา ใช้หัวข้อ, bullet points, และตัวหนา
6. กฎสำคัญเรื่องการจัดรูปแบบ:
   - ห้ามใช้แท็ก HTML เช่น <font color=...> หรือ <span> (หากต้องการเน้น ให้ใช้ตัวหนา **ข้อความ** หรือใส่วงเล็บ [ข้อควรระวัง] แทน)
   - ห้ามใช้ไวยากรณ์สมการคณิตศาสตร์แบบ LaTeX เช่น $$ \text{...} $$ ให้เขียนเป็นข้อความธรรมดา
PROMPT;
        } elseif ($isHosfinPage || $isFinancialQuery) {
            $systemPrompt = <<<PROMPT
คุณคือ "RiMS Copilot" ผู้ช่วย AI อัจฉริยะประจำระบบมอนิเตอร์สถานะการเงินการคลังโรงพยาบาล (Hospital Financial Advisor & CFO Copilot) ประจำระบบ RiMS
คุณมีความเชี่ยวชาญสูงสุดในการวิเคราะห์งบทดลอง, บัญชีแยกประเภท GL (General Ledger), บิลเจ้าหนี้การค้า (AP Bills), ลูกหนี้ค่ารักษาพยาบาล (AR Debtors), โครงสร้างต้นทุน (LC/MC/CC), และดัชนีชี้วัดสถานะการเงิน 13 ตัวชี้วัดตามเกณฑ์กระทรวงสาธารณสุข

บทบาทและหลักการสำคัญในการตอบคำถามในหน้านี้ (วิเคราะห์ HosFin ร่วมกับคู่มือ RAG):
1. ตอบด้วยตัวเลขจริงและข้อมูลบริษัทจริงจากฐานข้อมูล GL:
   - ตาราง hosfin_gl_ap_bills: มียอดหนี้ค้างชำระจริง 1,417 บิล รวมกว่า 41.67 ล้านบาท แยกรายบริษัท (เช่น องค์การเภสัชกรรม, ไทยเฮลท์ อิมเมจจิ้ง, ซิลลิค ฟาร์มา, เป็นหนึ่งไตเทียม, เบอร์ลินฟาร์มาซูติคอล ฯลฯ)
   - ตาราง hosfin_gl_ar_debtors: ลูกหนี้ค่ารักษาพยาบาลรอชดเชยแยกตามสิทธิ (สปสช. UC, ข้าราชการ/อปท., ประกันสังคม)
   - ตาราง hosfin_gl_journals & hosfin_gl_journal_items: รายการสมุดรายวันทั่วไปและใบสำคัญ
   - ตาราง hosfin_trial_balance & hosfin_dtl_mappings: ผังบัญชีงบทดลองและสูตรอัตราส่วนทางการเงิน
2. เชื่อมโยงและเปรียบเทียบกับคู่มือและเกณฑ์กระทรวงสาธารณสุข (Cross-reference กับ RAG):
   - หากมีเอกสารคู่มือหรือระเบียบใน RAG ที่เกี่ยวข้อง (เช่น เกณฑ์ระยะเวลาชำระค่ายา สธ. ไม่เกิน 60 วัน, เกณฑ์ความเสี่ยงทางการเงิน Risk Score 0-7, เกณฑ์เงินบำรุงสุทธิสูตร 105, หรือเกณฑ์การตั้งสำรองหนี้สูญ) ให้อ้างอิงประกอบการวิเคราะห์ตัวเลขจริงเสมอ
3. เมื่อผู้ใช้ถามถึงเจ้าหนี้ / บริษัทไหนต้องจ่ายก่อน / จัดลำดับการจ่ายหนี้:
   - ให้อ้างอิงชื่อบริษัท ยอดหนี้ค้าง วันที่บิล และอายุหนี้ (aging) เสมอ
   - จัดลำดับความสำคัญตาม "หลักเกณฑ์การบริหารสภาพคล่องทางการแพทย์ (Medical Debt Prioritization Framework)":
     * [Tier 1 - เร่งด่วนสูงสุด (สีแดง)]: ยาสามัญและยาช่วยชีวิตหลัก (องค์การเภสัชกรรม GPO, บริษัทยารายใหญ่) -> ต้องจ่ายก่อนเพื่อไม่ให้ถูกตัดวงเงินและป้องกันยาขาดคลัง
     * [Tier 2 - บริการผู้ป่วยต่อเนื่อง (สีส้ม)]: บริการทางการแพทย์ต่อเนื่องที่กระทบคนไข้ทันที (เช่น เป็นหนึ่งไตเทียม/ฟอกไต, ไทยเฮลท์/CT Scan) -> ต้องจ่ายเพื่อไม่ให้หยุดการรักษา
     * [Tier 3 - วัสดุวิทยาศาสตร์และการแพทย์]: น้ำยาชันสูตร Lab และเวชภัณฑ์สิ้นเปลือง
     * [Tier 4 - วัสดุสำนักงาน/ทั่วไป]: สามารถเจรจาขอขยายระยะเวลาเครดิตเทอม หรือผ่อนชำระได้
4. เสนอแนะการวางแผนล่วงหน้า (Forward-Looking & Cash Inflow Strategy):
   - ชี้เป้าการเร่งรัดลูกหนี้ สปสช. และข้าราชการ เพื่อดึงกระแสเงินสดเข้ามาหมุนเวียนจ่ายเจ้าหนี้ค่ายา
5. จัดรูปแบบคำตอบด้วย Markdown อย่างสวยงาม ชัดเจน ใช้หัวข้อ, bullet points, ตัวหนา, และตารางเมื่อมีตัวเลขหลายรายการ
6. กฎสำคัญเรื่องการจัดรูปแบบ:
   - ห้ามใช้แท็ก HTML เช่น <font color=...> หรือ <span> (หากต้องการเน้น ให้ใช้ตัวหนา **ข้อความ** หรือใส่วงเล็บ [วิกฤต/สีแดง] แทน)
   - ห้ามใช้ไวยากรณ์สมการคณิตศาสตร์แบบ LaTeX เช่น $$ \text{...} $$ (ให้เขียนเป็นสมการข้อความธรรมดา เช่น 'เงินบำรุงคงเหลือสุทธิ (105) = เงินบำรุงคงเหลือ (1005X) - ภาระหนี้สินผูกพัน (1005Y)')
PROMPT;
        } else {
            $systemPrompt = <<<PROMPT
คุณคือ "RiMS Copilot" ผู้ช่วย AI ประจำระบบมอนิเตอร์สถานะการเงินการคลังโรงพยาบาล บริหารจัดการรายได้ ลูกหนี้ค่ารักษาพยาบาล และตรวจสอบการตั้งค่า HOSxP สำหรับการเบิกจ่ายกองทุนต่าง ๆ ของโรงพยาบาล
คุณสามารถพูดคุย สนทนา ตอบคำถาม และวิเคราะห์สถานะการเงิน งบทดลอง การตั้งค่า HOSxP และหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ ได้อย่างเป็นธรรมชาติ เป็นกันเอง แต่สุภาพและเป็นมืออาชีพ

คุณมีความสามารถหลัก 3 ด้าน:
1. ด้านมอนิเตอร์สถานะการเงินการคลัง (HosFin): สรุปภาพรวม 5 หมวดบัญชี, เจาะลึกผังงบทดลอง, บิลเจ้าหนี้ AP, ลูกหนี้ AR, ดัชนีสภาพคล่องและสุขภาพการเงิน 13 ตัวชี้วัด, และคะแนนวิกฤต (Risk Score 0-7)
2. ด้านการตรวจสอบการตั้งค่า HOSxP: ตรวจสอบข้อมูลจริงในตาราง nondrugitems, income, nhso_adp_type, nhso_adp_code เปรียบเทียบกับหลักเกณฑ์เบิกจ่าย เพื่อชี้เป้าว่า รพ. ตั้งค่าถูกต้อง หรือมีจุดใดที่ต้องแก้ไข
3. ด้านหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ (RAG): สืบค้นและอ้างอิงเอกสารคู่มือ ระเบียบ สปสช. กรมบัญชีกลาง ประกันสังคม และแนวทางแก้ไขข้อผิดพลาดติด C, V, Deny

หลักการสำคัญในการตอบ:
1. คุยเป็นธรรมชาติ ลื่นไหล ไม่ตอบแข็งทื่อแบบหุ่นยนต์ และห้ามปฏิเสธอย่างไม่มีเหตุผล
2. จดจำบริบทการสนทนาต่อเนื่อง (Contextual Multi-turn): หากผู้ใช้ถามคำถามสั้นๆ หรือถามต่อเนื่อง ให้เข้าใจว่าหมายถึงหัวข้อที่กำลังคุยกันก่อนหน้า
3. หากผู้ใช้ถามว่า "ตั้งค่าถูกไหม" หรือถามเรื่องรหัสเบิกจ่าย: ให้เปรียบเทียบข้อมูลที่ตั้งไว้จริงใน HOSxP กับหลักเกณฑ์การเบิกจ่าย ชี้แจงจุดที่ถูกต้อง และจุดที่ต้องแก้ไขอย่างชัดเจน
4. จัดรูปแบบคำตอบด้วย Markdown (หัวข้อ, bullet points, ตาราง หรือตัวหนา) ให้อ่านง่าย สบายตา
5. ห้ามใช้แท็ก HTML เช่น <font color=...> และห้ามใช้สูตร LaTeX เช่น $$ \text{...} $$ ให้เขียนเป็นสมการข้อความธรรมดา
6. หากเป็นเรื่องตัวเลขหรือรหัส ให้ระบุแหล่งที่มา เช่น ชื่องวดบัญชี, ชื่อผัง, หรือรหัส icode ให้ชัดเจน
PROMPT;
        }

        if ($isRagPage) {
            $userPrompt = <<<PROMPT
{$historyText}[ข้อมูลที่สืบค้นได้จากคลังเอกสารคู่มือและระเบียบ RAG]:
{$contextText}

[คำถามของผู้ใช้]:
{$cleanQuestion}

กรุณาตอบคำถามโดยอ้างอิงจากเนื้อหาในเอกสารที่ได้รับข้างต้น ระบุชื่อเอกสารและหน้าอ้างอิง (ถ้ามี) อย่างชัดเจนและเป็นประโยชน์:
PROMPT;
        } elseif ($isHosfinPage || $isFinancialQuery) {
            $userPrompt = <<<PROMPT
{$historyText}[ข้อมูลบริบทจากระบบ (งบทดลอง HosFin, บัญชี GL, และเอกสารคู่มือระเบียบ สธ./RAG)]:
{$contextText}

[คำถามปัจจุบันของผู้ใช้]:
{$cleanQuestion}

กรุณาวิเคราะห์และตอบคำถามด้วยตัวเลขจริง เปรียบเทียบกับหลักเกณฑ์/คู่มือที่เกี่ยวข้องอย่างชัดเจนและเป็นประโยชน์:
PROMPT;
        } else {
            $userPrompt = <<<PROMPT
{$historyText}[ข้อมูลบริบทจากระบบ (RAG เอกสาร, ข้อมูลจริง HOSxP, และงบทดลอง HosFin)]:
{$contextText}

[คำถามปัจจุบันของผู้ใช้]:
{$cleanQuestion}

กรุณาตอบคำถามอย่างเป็นธรรมชาติ ชัดเจน และเป็นประโยชน์:
PROMPT;
        }

        // 4. Generate response from LLM
        try {
            $answer = $this->aiService->generateChat($userPrompt, $systemPrompt, $pageContext);
        } catch (\Throwable $e) {
            Log::error("RAG Generation Error: " . $e->getMessage());
            $provider = \App\Services\Ai\AiService::getProvider();
            $providerLabel = match($provider) {
                'gemini' => 'Google Gemini (Cloud)',
                'ollama' => 'Ollama (On-Premise / Local)',
                default => 'OpenAI / DeepSeek (Compatible)'
            };
            $answer = "⚠️ **ระบบ AI ยังไม่พร้อมใช้งาน หรือยังไม่ได้ตั้งค่าการเชื่อมต่อ**\n\n"
                    . "• **ผู้ให้บริการที่เลือกไว้:** `{$providerLabel}`\n"
                    . "• **สาเหตุ:** " . $e->getMessage() . "\n\n"
                    . "💡 **คำแนะนำ:** ระบบ RiMS Copilot รองรับการเชื่อมต่อ AI หลากหลายค่าย:\n"
                    . "1. **Google Gemini:** ใช้งานผ่าน Cloud API ได้รวดเร็ว\n"
                    . "2. **Ollama:** สำหรับรัน Local LLM ภายในโรงพยาบาล (เช่น Typhoon, Llama 3) ไม่ต้องต่อเน็ต\n"
                    . "3. **OpenAI-Compatible:** สำหรับผู้ให้บริการโมเดลภายนอกอื่นๆ\n\n"
                    . "กรุณาเปิดหน้า **ตั้งค่า AI & LLM Connection** เพื่อเลือกผู้ให้บริการ บันทึกค่า หรือทดสอบการเชื่อมต่อครับ";
        }

        return [
            'answer' => $answer,
            'sources' => $sources
        ];
    }

    /**
     * Search chunks by True Hybrid Search (Vector Cosine + Document Title Boost + Keyword / BM25 Matching + Document Diversity)
     */
    public function searchSimilarChunks(string $query, int $topK = 8): array
    {
        $cleanQuery = trim($query);
        $queryEmbedding = [];
        try {
            $queryEmbedding = $this->aiService->getEmbedding($cleanQuery);
        } catch (\Throwable $e) {
            Log::warning("Could not generate query embedding, fallback to keyword search: " . $e->getMessage());
        }

        // Get chunks from database with parent document
        $chunks = RagChunk::with('document')->get();
        if ($chunks->isEmpty()) {
            return [];
        }

        // Extract meaningful tokens
        $rawTokens = preg_split('/[\s,\.\-\_\/]+/u', $cleanQuery);
        $tokens = [];
        foreach ($rawTokens as $t) {
            $t = trim($t);
            if (mb_strlen($t) >= 2 && !in_array($t, ['ใน', 'ที่', 'จะ', 'ขอ', 'ดู', 'มี', 'และ', 'หรือ', 'คือ', 'ว่า', 'ไหม', 'บ้าง', 'ครับ', 'ค่ะ', 'อะไร', 'ช่วย', 'หน่อย', 'เรื่อง'])) {
                $tokens[] = $t;
            }
        }

        $hasQueryEmbedding = !empty($queryEmbedding);
        $scoredChunks = [];

        foreach ($chunks as $chunk) {
            $doc = $chunk->document;
            $docTitle = $doc ? $doc->title : '';
            $docFilename = $doc ? $doc->filename : '';

            // 1. Vector Cosine Similarity
            $vectorScore = 0.0;
            $emb = $chunk->embedding;
            if ($hasQueryEmbedding && !empty($emb) && is_array($emb)) {
                $sim = $this->cosineSimilarity($queryEmbedding, $emb);
                if ($sim > 0.15) {
                    $vectorScore = $sim;
                }
            }

            // 2. Document Title Match Bonus (Massive relevance boost when question directly mentions document name/topic)
            $titleBonus = 0.0;
            if (!empty($docTitle)) {
                // Exact phrase or sub-string match (e.g. 'FM Costing', '7 efficiency', 'คู่มือบัญชี')
                if (mb_stripos($docTitle, $cleanQuery) !== false || mb_stripos($cleanQuery, $docTitle) !== false) {
                    $titleBonus += 0.50;
                } else {
                    foreach ($tokens as $token) {
                        if (mb_stripos($docTitle, $token) !== false || mb_stripos($docFilename, $token) !== false) {
                            $titleBonus += 0.25;
                        }
                    }
                }
                $titleBonus = min(0.60, $titleBonus);
            }

            // 3. Content Keyword Matching
            $contentMatchCount = 0;
            foreach ($tokens as $token) {
                if (mb_stripos($chunk->content, $token) !== false) {
                    $contentMatchCount++;
                }
            }
            $contentScore = $contentMatchCount > 0 ? min(0.60, 0.25 + ($contentMatchCount * 0.08)) : 0.0;

            // 4. Hybrid Combined Score
            if ($vectorScore > 0) {
                $finalScore = ($vectorScore * 0.60) + ($contentScore * 0.20) + $titleBonus;
            } else {
                $finalScore = $contentScore + $titleBonus;
            }

            if ($finalScore >= 0.20) {
                $scoredChunks[] = [
                    'chunk' => $chunk,
                    'score' => $finalScore,
                    'doc_id' => $chunk->document_id
                ];
            }
        }

        if (empty($scoredChunks)) {
            return [];
        }

        // Sort descending by combined score
        usort($scoredChunks, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 5. Diversity Enforcement:
        // Do not allow a single document to monopolize all topK slots if other relevant documents exist
        $result = [];
        $docCounts = [];
        $maxPerDoc = max(3, (int) ceil($topK / 2));

        foreach ($scoredChunks as $item) {
            $docId = $item['doc_id'];
            $currentCount = $docCounts[$docId] ?? 0;

            if ($currentCount < $maxPerDoc) {
                $result[] = $item;
                $docCounts[$docId] = $currentCount + 1;
                if (count($result) >= $topK) {
                    break;
                }
            }
        }

        // Fill up remaining slots from scored chunks if any remain
        if (count($result) < $topK) {
            foreach ($scoredChunks as $item) {
                if (!in_array($item, $result, true)) {
                    $result[] = $item;
                    if (count($result) >= $topK) {
                        break;
                    }
                }
            }
        }

        return $result;
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
