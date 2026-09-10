<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Services\Ai\RagSearchService;
use App\Services\Ai\TextToSql\TextToSqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopilotController extends Controller
{
    protected TextToSqlService $textToSqlService;
    protected RagSearchService $ragSearchService;

    public function __construct(
        TextToSqlService $textToSqlService,
        RagSearchService $ragSearchService
    ) {
        $this->textToSqlService = $textToSqlService;
        $this->ragSearchService = $ragSearchService;
    }

    /**
     * Check if the authenticated user has permission to use RiMS Copilot
     */
    protected function checkCopilotAccess()
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        if ($user->status !== 'admin' && ($user->allow_ai_copilot ?? 'N') !== 'Y') {
            abort(403, 'คุณไม่ได้รับสิทธิ์ใช้งาน RiMS Copilot กรุณาติดต่อผู้ดูแลระบบ');
        }

        return $user;
    }

    /**
     * Full-screen RiMS Copilot Workspace View
     */
    public function index(Request $request)
    {
        $user = $this->checkCopilotAccess();

        // Initial context passed via query string: 'hosfin', 'hosxp', 'rag', or 'auto'
        $initialContext = $request->query('context', 'auto');
        if (!in_array($initialContext, ['auto', 'hosfin', 'hosxp', 'rag'], true)) {
            $initialContext = 'auto';
        }

        // Check module permissions
        $canAccessHosfin = true;
        $canAccessHosxp = true;
        $canAccessRag = true;

        return view('copilot.index', compact('user', 'initialContext', 'canAccessHosfin', 'canAccessHosxp', 'canAccessRag'));
    }

    /**
     * List all chat sessions belonging to the current user (optionally filtered by scope)
     */
    public function getSessions(Request $request)
    {
        $user = $this->checkCopilotAccess();

        $query = AiChatSession::where('user_id', $user->id)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc');

        $scope = $request->query('scope');
        if ($scope && in_array($scope, ['hosfin', 'hosxp', 'rag'], true)) {
            $query->where('context_scope', $scope);
        }

        $sessions = $query->get();

        return response()->json([
            'success' => true,
            'sessions' => $sessions
        ]);
    }

    /**
     * Create a new chat session
     */
    public function createSession(Request $request)
    {
        $user = $this->checkCopilotAccess();

        $scope = $request->input('context_scope', 'auto');
        if (!in_array($scope, ['auto', 'hosfin', 'hosxp', 'rag'], true)) {
            $scope = 'auto';
        }

        $session = AiChatSession::create([
            'user_id' => $user->id,
            'title' => 'การสนทนาใหม่',
            'context_scope' => $scope,
        ]);

        return response()->json([
            'success' => true,
            'session' => $session
        ]);
    }

    /**
     * Get messages of a specific session
     */
    public function getSessionMessages($id)
    {
        $user = $this->checkCopilotAccess();
        $isAdmin = ($user->status ?? null) === 'admin';

        $session = AiChatSession::where('user_id', $user->id)->findOrFail($id);
        $messages = $session->messages()->get();

        // Format message payloads
        $formatted = $messages->map(function ($msg) use ($isAdmin) {
            $data = [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'sql_query' => $isAdmin ? $msg->sql_query : null,
                'db_target' => $msg->db_target,
                'execution_time_ms' => $msg->execution_time_ms,
                'created_at' => $msg->created_at ? $msg->created_at->format('H:i น.') : '',
            ];

            if (!empty($msg->query_result_json)) {
                $rows = json_decode($msg->query_result_json, true) ?: [];
                $columns = !empty($rows) ? array_keys($rows[0]) : [];
                $data['rows'] = $rows;
                $data['columns'] = $columns;
                $data['column_labels'] = $this->textToSqlService->getColumnLabels($columns);
                $data['total_rows'] = count($rows);
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'session' => $session,
            'messages' => $formatted
        ]);
    }

    /**
     * Delete a chat session
     */
    public function deleteSession($id)
    {
        $user = $this->checkCopilotAccess();

        $session = AiChatSession::where('user_id', $user->id)->findOrFail($id);
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบการสนทนาเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Clear all chat sessions of current user
     */
    public function clearAllSessions()
    {
        $user = $this->checkCopilotAccess();

        AiChatSession::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'ล้างประวัติการสนทนาทั้งหมดเรียบร้อยแล้ว'
        ]);
    }

    /**
     * Send a question to RiMS Copilot (Text-to-SQL or RAG)
     */
    public function ask(Request $request)
    {
        $user = $this->checkCopilotAccess();
        $isAdmin = ($user->status ?? null) === 'admin';

        $request->validate([
            'question' => 'required|string|min:1',
            'session_id' => 'nullable|integer',
            'scope' => 'nullable|string',
        ]);

        $question = trim($request->input('question'));
        $sessionId = $request->input('session_id');
        $scope = $request->input('scope', 'auto');

        // Retrieve or create Session
        $session = null;
        if ($sessionId) {
            $session = AiChatSession::where('user_id', $user->id)->find($sessionId);
        }

        if (!$session) {
            $session = AiChatSession::create([
                'user_id' => $user->id,
                'title' => mb_substr($question, 0, 45, 'UTF-8'),
                'context_scope' => $scope,
            ]);
        } elseif ($session->title === 'การสนทนาใหม่') {
            $session->update([
                'title' => mb_substr($question, 0, 45, 'UTF-8')
            ]);
        }

        // Save User Message
        AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $question,
        ]);

        // Get past history for context
        $pastMessages = $session->messages()
            ->latest('id')
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // -------------------------------------------------------------
        // Branch 0: Pure Greeting Questions (Only for auto/rag scopes, HosFin always queries live tables)
        // -------------------------------------------------------------
        if ($this->isCapabilityQuestion($question, $scope)) {
            $cap = $this->getCapabilityResponse($scope);
            $botMsg = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $cap['summary'],
                'db_target' => $scope,
            ]);
            $session->touch();

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'session_title' => $session->title,
                'message_id' => $botMsg->id,
                'mode' => 'text_to_sql',
                'summary' => $cap['summary'],
                'sql' => null,
                'rows' => [],
                'columns' => [],
                'total_rows' => 0,
                'db_target' => $scope,
                'execution_time_ms' => 0,
                'suggestions' => $cap['suggestions'],
                'sources' => []
            ]);
        }

        // -------------------------------------------------------------
        // Branch A: RAG Mode (Document Q&A)
        // -------------------------------------------------------------
        if ($scope === 'rag') {
            $ragResult = $this->ragSearchService->ask($question, 5, $pastMessages, 'rag');
            $answer = $ragResult['answer'];
            $sources = $ragResult['sources'] ?? [];

            $botMsg = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $answer,
                'db_target' => 'none',
            ]);

            $session->touch();

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'session_title' => $session->title,
                'message_id' => $botMsg->id,
                'mode' => 'rag',
                'answer' => $answer,
                'sources' => $sources,
            ]);
        }

        // -------------------------------------------------------------
        // Branch B: Text-to-SQL Mode (HRiMS, HOSxP, or Auto-detect)
        // -------------------------------------------------------------
        if ($scope === 'auto') {
            $isDocQuestion = (bool) preg_match('/(คู่มือ|ระเบียบ|ข้อบังคับ|ประกาศ|cpg|แนวทาง|มาตรฐาน|หนังสือสั่งการ|วิธีใช้|วิธีปฏิบัติ|เอกสาร)/iu', $question);
            if ($isDocQuestion) {
                try {
                    $ragResult = $this->ragSearchService->ask($question, 5, $pastMessages, 'auto');
                    if (!empty($ragResult['answer']) && !empty($ragResult['sources'])) {
                        $botMsg = AiChatMessage::create([
                            'session_id' => $session->id,
                            'role' => 'assistant',
                            'content' => $ragResult['answer'],
                            'db_target' => 'none',
                        ]);
                        $session->touch();
                        return response()->json([
                            'success' => true,
                            'session_id' => $session->id,
                            'session_title' => $session->title,
                            'message_id' => $botMsg->id,
                            'mode' => 'rag',
                            'answer' => $ragResult['answer'],
                            'sources' => $ragResult['sources'] ?? [],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("Auto mode RAG search error: " . $e->getMessage());
                }
            }
        }

        $dbTarget = ($scope === 'hosfin') ? 'hrims' : (($scope === 'hosxp') ? 'hosxp' : 'auto');
        $sqlResult = $this->textToSqlService->generateAndExecute($question, $dbTarget, $pastMessages);

        if (!$sqlResult['success']) {
            // In auto mode, try RAG fallback if Text-to-SQL could not process
            if ($scope === 'auto') {
                try {
                    $ragResult = $this->ragSearchService->ask($question, 5, $pastMessages, 'auto');
                    if (!empty($ragResult['answer']) && !empty($ragResult['sources'])) {
                        $botMsg = AiChatMessage::create([
                            'session_id' => $session->id,
                            'role' => 'assistant',
                            'content' => $ragResult['answer'],
                            'db_target' => 'none',
                        ]);
                        $session->touch();
                        return response()->json([
                            'success' => true,
                            'session_id' => $session->id,
                            'session_title' => $session->title,
                            'message_id' => $botMsg->id,
                            'mode' => 'rag',
                            'answer' => $ragResult['answer'],
                            'sources' => $ragResult['sources'] ?? [],
                        ]);
                    }
                } catch (\Throwable $e) {
                    // Ignore fallback error
                }
            }

            $errorMessage = $sqlResult['message'] ?? 'ไม่สามารถประมวลผลคำสั่งได้';

            $botMsg = AiChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $errorMessage,
                'sql_query' => $sqlResult['sql'] ?? null,
                'db_target' => $sqlResult['db_target'] ?? 'hrims',
            ]);

            $session->touch();

            return response()->json([
                'success' => false,
                'session_id' => $session->id,
                'session_title' => $session->title,
                'message_id' => $botMsg->id,
                'mode' => 'text_to_sql',
                'message' => $errorMessage,
                'sql' => $isAdmin ? ($sqlResult['sql'] ?? null) : null,
                'db_target' => $sqlResult['db_target'] ?? 'hrims',
            ]);
        }

        // Successful Text-to-SQL Execution
        $summary = $sqlResult['summary'];
        $rows = $sqlResult['rows'];
        $columns = $sqlResult['columns'];
        $sql = $sqlResult['sql'];
        $dbTargetResolved = $sqlResult['db_target'];
        $execTimeMs = $sqlResult['execution_time_ms'];
        $suggestions = json_decode($sqlResult['suggestions'], true) ?: [];

        $botMsg = AiChatMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $summary,
            'sql_query' => $sql,
            'query_result_json' => json_encode($rows, JSON_UNESCAPED_UNICODE),
            'db_target' => $dbTargetResolved,
            'execution_time_ms' => $execTimeMs,
        ]);

        $session->touch();

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'session_title' => $session->title,
            'message_id' => $botMsg->id,
            'mode' => 'text_to_sql',
            'summary' => $summary,
            'sql' => $isAdmin ? $sql : null,
            'explanation' => $sqlResult['explanation'],
            'columns' => $columns,
            'column_labels' => $sqlResult['column_labels'] ?? [],
            'rows' => $rows,
            'total_rows' => count($rows),
            'db_target' => $dbTargetResolved,
            'execution_time_ms' => $execTimeMs,
            'suggestions' => $suggestions,
            'sources' => $sqlResult['sources'] ?? [],
        ]);
    }

    /**
     * Check if a question is asking for a pure greeting (never intercept HosFin data queries)
     */
    protected function isCapabilityQuestion(string $question, string $scope = 'auto'): bool
    {
        // Never intercept HosFin questions: user expects live querying across hosfin_* tables
        if ($scope === 'hosfin') {
            return false;
        }

        $q = mb_strtolower(trim($question), 'UTF-8');
        $q = preg_replace('/[?!.,\s]+$/u', '', $q);

        // Greetings and capability inquiry phrases
        $capabilityPhrases = [
            'สวัสดี', 'สวัสดีครับ', 'สวัสดีค่ะ', 'หวัดดี', 'hello', 'hi', 'start', 'เริ่มต้น',
            'ทำอะไรได้บ้าง', 'ช่วยอะไรได้บ้าง', 'ตรวจสอบอะไรได้บ้าง', 'ตอนนี้ตรวจสอบอะไรได้บ้าง',
            'สืบค้นอะไรได้บ้าง', 'ค้นหาอะไรได้บ้าง', 'มีข้อมูลอะไรบ้าง', 'มีอะไรบ้าง', 'ความสามารถ'
        ];

        return in_array($q, $capabilityPhrases, true);
    }

    /**
     * Build capability response based on current scope
     */
    protected function getCapabilityResponse(string $scope): array
    {
        if ($scope === 'hosfin') {
            $summary = "ในระบบ **HosFin** ผมสามารถช่วยท่านสืบค้นและวิเคราะห์ข้อมูลด้านการเงินการคลังโรงพยาบาลได้หลากหลายรายการครับ เช่น:\n\n"
                . "1. **เจ้าหนี้การค้า (AP)**: สรุปยอดหนี้แยกตามบริษัท/ผู้ขาย, รายการบิลค้างจ่าย, ใบรับวางบิล และประวัติการจ่ายเงิน\n"
                . "2. **ลูกหนี้ค่ารักษาพยาบาล (AR)**: ตรวจสอบลูกหนี้ค้างชำระแยกตามสิทธิ (UC, ประกันสังคม, ข้าราชการ) และลูกหนี้ตามผังบัญชี\n"
                . "3. **ผังบัญชีและงบทดลอง (Trial Balance)**: ขอยอดสรุปงบทดลองรายเดือน และยอดเดบิต/เครดิตแต่ละหมวด\n"
                . "4. **สมุดรายวันทั่วไป (Journal Voucher)**: ตรวจสอบการลงบัญชี GL รายวันและรายละเอียดเอกสารประกอบ\n"
                . "5. **เทียบเคียงระเบียบการเงิน (RAG)**: ระเบียบเงินบำรุงและแนวทางปฏิบัติการเงินการคลัง";

            $suggestions = [
                'ขอยอดสรุปเจ้าหนี้การค้าแยกตามบริษัท',
                'ยอดหนี้ค้างจ่ายองค์การเภสัชกรรม GPO',
                'ลูกหนี้ค่ารักษาพยาบาลค้างชำระตามผังบัญชี',
                'ขอยอดงบทดลอง (Trial Balance) เดือนล่าสุด',
                'สรุปการลงสมุดรายวันทั่วไป (Journal Voucher)'
            ];
        } elseif ($scope === 'hosxp') {
            $summary = "ในระบบ **HOSxP Setting** ผมสามารถช่วยท่านตรวจสอบความถูกต้องของข้อมูลพื้นฐาน Master Data ของโรงพยาบาลได้ครับ เช่น:\n\n"
                . "1. **รายการค่ารักษาพยาบาลและหัตถการ (nondrugitems)**: ตรวจสอบรายการที่ยังไม่ได้ผูกรหัสมาตรฐาน ADP หรือรหัส 16 แฟ้ม\n"
                . "2. **สิทธิการรักษาพยาบาล (pttype)**: ตรวจสอบการตั้งค่าสิทธิการรักษาที่เปิดใช้งาน และรหัสมาตรฐาน pttype\n"
                . "3. **ข้อมูลแพทย์และผู้ประกอบวิชาชีพ (doctor)**: รายชื่อแพทย์ที่ยังไม่มีเลขที่ใบประกอบวิชาชีพ (เลข ว.) หรือตำแหน่ง\n"
                . "4. **เทียบเคียงมาตรฐานข้อมูล (RAG)**: คู่มือมาตรฐานข้อมูล สปสช., กรมบัญชีกลาง และโครงสร้าง 16 แฟ้ม";

            $suggestions = [
                'ตรวจสอบรายการค่าบริการที่ยังไม่ผูกรหัส ADP',
                'ตรวจสอบการตั้งค่าสิทธิการรักษา pttype',
                'รายชื่อแพทย์ที่ไม่มีเลขที่ใบประกอบวิชาชีพ',
                'รายการหัตถการและค่าบริการทางการแพทย์',
                'ตรวจสอบรหัสมาตรฐาน 16 แฟ้มในรายการค่าบริการ'
            ];
        } elseif ($scope === 'rag') {
            $summary = "ในระบบ **Knowledge Base** ผมสามารถช่วยท่านค้นหาข้อมูล กฎระเบียบ คู่มือปฏิบัติงาน และแนวทาง CPG จากเอกสารได้ครับ เช่น:\n\n"
                . "1. **ระเบียบเงินบำรุงโรงพยาบาล**: ระเบียบเงินบำรุงฉบับล่าสุด และหลักเกณฑ์การใช้จ่าย\n"
                . "2. **แนวทางเวชปฏิบัติ (CPG)**: แนวทางการวินิจฉัยและบันทึกรหัสโรค เช่น Sepsis, Stroke, STEMI\n"
                . "3. **หลักเกณฑ์การเบิกจ่ายและชดเชย**: เกณฑ์ฟอกไต, รหัส C-Code, แนวทาง สปสช.\n"
                . "4. **มาตรฐานข้อมูล 16 แฟ้ม**: โครงสร้างฟิลด์และการแก้ไข Error ใน e-Claim";

            $suggestions = [
                'ระเบียบเงินบำรุงโรงพยาบาลฉบับล่าสุด',
                'แนวทางการวินิจฉัยและบันทึกรหัสโรค Sepsis CPG',
                'แนวทางการบันทึกและตรวจสอบรหัส C-Code',
                'แนวทางการเบิกจ่ายกรณีฟอกไต (Hemodialysis)',
                'โครงสร้างข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)'
            ];
        } else {
            $summary = "ผมคือ **RiMS Copilot** ผู้ช่วย AI อัจฉริยะ ท่านสามารถถามข้อมูลได้ทั้ง 3 ระบบหลักครับ:\n\n"
                . "1. **🏢 HosFin (ระบบการเงิน)**: เจ้าหนี้การค้า, ลูกหนี้ค่ารักษา, ผังบัญชี, งบทดลอง\n"
                . "2. **🏥 HOSxP Setting (ระบบตั้งค่า)**: ตรวจสอบรายการค่าบริการผูก ADP, การตั้งค่าสิทธิ pttype, เลข ว. แพทย์\n"
                . "3. **📚 Knowledge Base (คลังความรู้)**: ค้นหาระเบียบเงินบำรุง, แนวทาง CPG, หลักเกณฑ์เบิกจ่าย สปสช.";

            $suggestions = [
                'ขอยอดสรุปเจ้าหนี้การค้าแยกตามบริษัท',
                'ตรวจสอบรายการค่าบริการที่ยังไม่ผูกรหัส ADP',
                'ระเบียบเงินบำรุงโรงพยาบาลฉบับล่าสุด',
                'ลูกหนี้ค่ารักษาพยาบาลค้างชำระตามสิทธิ',
                'รายชื่อแพทย์และเลขที่ใบประกอบวิชาชีพ'
            ];
        }

        return [
            'summary' => $summary,
            'suggestions' => $suggestions
        ];
    }
}
