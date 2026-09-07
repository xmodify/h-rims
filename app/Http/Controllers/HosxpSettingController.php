<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Ai\AiService;
use App\Services\Ai\Context\HosxpContextService;
use App\Services\Ai\RagSearchService;

class HosxpSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to access HOSxP Setting / Master Data
     */
    protected function checkPermission()
    {
        if (!auth()->check()) {
            abort(403, 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }
        $user = auth()->user();
        if ($user->status !== 'admin' && ($user->allow_emr ?? 'N') !== 'Y') {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงระบบข้อมูลพื้นฐาน HOSxP');
        }
    }

    /**
     * Main Hub View
     */
    public function index(Request $request)
    {
        $this->checkPermission();

        $activeTab = $request->input('tab', 'doctor');
        $search = trim($request->input('search', ''));
        $filter = $request->input('filter', 'all');

        $hosxpAlive = false;
        try {
            $test = DB::connection('hosxp')->select('SELECT 1 as alive');
            $hosxpAlive = !empty($test);
        } catch (\Throwable $e) {
            $hosxpAlive = false;
        }

        if (!$hosxpAlive) {
            return view('emr.hosxp_setting.index', [
                'hosxpAlive' => false,
                'activeTab' => $activeTab,
                'stats' => [],
                'records' => collect(),
                'search' => $search,
                'filter' => $filter,
                'errorMessage' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ได้ กรุณาตรวจสอบการตั้งค่า DB_HOST_HOSXP ใน .env'
            ]);
        }

        $hosxp = DB::connection('hosxp');

        // ==========================================
        // 1. Calculate Summary Stats for KPI Cards
        // ==========================================
        $activeDocs = [];
        $inactiveDocs = [];
        $invalidLicenseDocs = [];
        $invalidCidDocs = [];
        $doctorTabConfigs = [];

        if ($activeTab === 'doctor') {
            $doctors = $hosxp->select('
                SELECT d.code, d.name, d.licenseno, d.cid, d.active, d.council_code, d.provider_type_code, d.sex, d.birth_date,
                       dp.name AS position_name
                FROM doctor d
                LEFT JOIN doctor_position dp ON dp.id = d.position_id
                ORDER BY d.active DESC, d.name ASC
            ');

            foreach ($doctors as $doc) {
                $lic = trim($doc->licenseno ?? '');
                $isLicValid = (bool) preg_match('/^(?:-|[วทภพ\-]\d+)$/u', $lic);

                $cid = trim($doc->cid ?? '');
                $isCidValid = (!empty($cid) && strlen($cid) === 13);

                $doc_errors = [];
                if (empty($lic)) {
                    $doc_errors[] = 'ไม่มีเลขใบประกอบวิชาชีพ';
                } elseif (!$isLicValid) {
                    $doc_errors[] = "เลขใบประกอบฯ '{$lic}' รูปแบบไม่ถูกต้อง (ต้องขึ้นต้นด้วย ว, ท, ภ, พ หรือ - และตามด้วยตัวเลข)";
                }

                if (empty($cid)) {
                    $doc_errors[] = 'ไม่มีเลขบัตรประชาชน';
                } elseif (!$isCidValid) {
                    $doc_errors[] = 'เลขบัตรประชาชนต้องยาว 13 หลัก';
                }

                $doc->doc_errors = $doc_errors;
                $doc->is_lic_valid = $isLicValid;
                $doc->is_cid_valid = $isCidValid;

                if ($doc->active === 'Y') {
                    $activeDocs[] = $doc;
                } else {
                    $inactiveDocs[] = $doc;
                }

                if (empty($lic) || !$isLicValid) {
                    $invalidLicenseDocs[] = $doc;
                }

                if (empty($cid) || !$isCidValid) {
                    $invalidCidDocs[] = $doc;
                }
            }

            $stats['doctor'] = [
                'total' => count($doctors),
                'active' => count($activeDocs),
                'inactive' => count($inactiveDocs),
                'invalid_license' => count($invalidLicenseDocs),
                'invalid_cid' => count($invalidCidDocs),
            ];

            $doctorTabConfigs = [
                [
                    'id' => 'active-tab-pane',
                    'active' => true,
                    'title' => 'รายชื่อแพทย์ที่เปิดใช้งาน',
                    'data' => $activeDocs,
                    'table_id' => 'table-active'
                ],
                [
                    'id' => 'inactive-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่ปิดใช้งาน',
                    'data' => $inactiveDocs,
                    'table_id' => 'table-inactive'
                ],
                [
                    'id' => 'invalid-license-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขใบประกอบวิชาชีพไม่ถูกต้อง',
                    'data' => $invalidLicenseDocs,
                    'table_id' => 'table-invalid-license'
                ],
                [
                    'id' => 'invalid-cid-tab-pane',
                    'active' => false,
                    'title' => 'รายชื่อแพทย์ที่เลขบัตรประชาชนไม่ถูกต้อง',
                    'data' => $invalidCidDocs,
                    'table_id' => 'table-invalid-cid'
                ]
            ];
        } else {
            $docTotal = $hosxp->table('doctor')->count();
            $docActive = $hosxp->table('doctor')->where('active', 'Y')->count();
            $stats['doctor'] = [
                'total' => $docTotal,
                'active' => $docActive,
                'inactive' => $docTotal - $docActive,
                'invalid_license' => 0,
                'invalid_cid' => 0,
            ];
        }

        $itemTotal = $hosxp->table('nondrugitems')->count();
        $itemActive = $hosxp->table('nondrugitems')->where('istatus', 'Y')->count();
        $itemMissingAdp = $hosxp->table('nondrugitems')->where('istatus', 'Y')
            ->where(function ($q) {
                $q->whereNull('nhso_adp_code')->orWhere('nhso_adp_code', '');
            })->count();

        $ptTotal = $hosxp->table('pttype')->count();
        $ptActive = $hosxp->table('pttype')->where('isuse', 'Y')->count();
        $ptMissingStd = $hosxp->table('pttype')->where('isuse', 'Y')
            ->where(function ($q) {
                $q->whereNull('pttype_std_code')->orWhere('pttype_std_code', '');
            })->count();
        $ptMissingHip = $hosxp->table('pttype')->where('isuse', 'Y')
            ->where(function ($q) {
                $q->whereNull('hipdata_code')->orWhere('hipdata_code', '');
            })->count();

        $stats['nondrugitems'] = [
            'total' => $itemTotal,
            'active' => $itemActive,
            'missing_adp' => $itemMissingAdp,
            'mapped_adp' => $itemActive - $itemMissingAdp,
            'health_rate' => $itemActive > 0 ? round((($itemActive - $itemMissingAdp) / $itemActive) * 100, 1) : 100,
        ];
        $stats['pttype'] = [
            'total' => $ptTotal,
            'active' => $ptActive,
            'missing_std' => $ptMissingStd,
            'missing_hip' => $ptMissingHip,
            'mapped_std' => $ptActive - $ptMissingStd,
            'health_rate' => $ptActive > 0 ? round((($ptActive - $ptMissingStd) / $ptActive) * 100, 1) : 100,
        ];

        // ==========================================
        // 2. Query Tab Records with Pagination & Search
        // ==========================================
        $records = collect();

        if ($activeTab === 'doctor') {
            // Handled via $doctorTabConfigs and client-side DataTables matching check/doctor
        } elseif ($activeTab === 'nondrugitems') {
            $query = $hosxp->table('nondrugitems as n')
                ->leftJoin('income as i', 'n.income', '=', 'i.income')
                ->select([
                    'n.icode', 'n.name', 'n.price', 'n.nhso_adp_code',
                    'n.nhso_adp_type_id', 'n.istatus', 'n.unit',
                    'n.income', 'n.billcode',
                    'i.name as income_name'
                ]);

            if ($filter === 'active') {
                $query->where('n.istatus', 'Y');
            } elseif ($filter === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('n.istatus')->orWhere('n.istatus', '!=', 'Y');
                });
            } elseif ($filter === 'missing_adp') {
                $query->where('n.istatus', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('n.nhso_adp_code')->orWhere('n.nhso_adp_code', '');
                    });
            } elseif ($filter === 'mapped_adp') {
                $query->where('n.istatus', 'Y')
                    ->whereNotNull('n.nhso_adp_code')
                    ->where('n.nhso_adp_code', '!=', '');
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('n.name', 'like', "%{$search}%")
                      ->orWhere('n.icode', 'like', "%{$search}%")
                      ->orWhere('n.nhso_adp_code', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('n.istatus', 'desc')
                ->orderBy('n.icode', 'asc')
                ->get();

            // Validate each nondrugitem record
            foreach ($records as $item) {
                $itemErrors = [];
                if (empty(trim($item->nhso_adp_code ?? ''))) {
                    $itemErrors[] = 'ยังไม่ผูกรหัส ADP';
                }
                if (empty(trim($item->income ?? ''))) {
                    $itemErrors[] = 'ยังไม่ระบุหมวดรายได้ (income)';
                }
                if (($item->price ?? 0) <= 0) {
                    $itemErrors[] = 'ราคา OPD เป็น 0 หรือไม่ได้ระบุ';
                }
                $item->item_errors = $itemErrors;
                $item->is_valid = empty($itemErrors);
            }

        } elseif ($activeTab === 'pttype') {
            $query = $hosxp->table('pttype as p')
                ->select([
                    'p.pttype', 'p.name', 'p.pcode', 'p.paidst',
                    'p.hipdata_code', 'p.nhso_code', 'p.pttype_std_code',
                    'p.export_eclaim', 'p.isuse', 'p.nhso_subinscl'
                ]);

            if ($filter === 'active') {
                $query->where('p.isuse', 'Y');
            } elseif ($filter === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('p.isuse')->orWhere('p.isuse', '!=', 'Y');
                });
            } elseif ($filter === 'missing_std') {
                $query->where('p.isuse', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('p.pttype_std_code')->orWhere('p.pttype_std_code', '');
                    });
            } elseif ($filter === 'missing_hip') {
                $query->where('p.isuse', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('p.hipdata_code')->orWhere('p.hipdata_code', '');
                    });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('p.name', 'like', "%{$search}%")
                      ->orWhere('p.pttype', 'like', "%{$search}%")
                      ->orWhere('p.pttype_std_code', 'like', "%{$search}%")
                      ->orWhere('p.pcode', 'like', "%{$search}%")
                      ->orWhere('p.hipdata_code', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('p.isuse', 'desc')
                ->orderBy('p.pttype', 'asc')
                ->get();

            // Validate each pttype record
            foreach ($records as $pt) {
                $ptErrors = [];
                if (empty(trim($pt->pttype_std_code ?? ''))) {
                    $ptErrors[] = 'ขาดรหัสมาตรฐาน 4 หลัก (std_code)';
                }
                if (empty(trim($pt->hipdata_code ?? ''))) {
                    $ptErrors[] = 'ขาดรหัส HIPDATA';
                }
                if (empty(trim($pt->pcode ?? ''))) {
                    $ptErrors[] = 'ขาดกลุ่มสิทธิ (pcode)';
                }
                $pt->item_errors = $ptErrors;
                $pt->is_valid = empty($ptErrors);
            }
        }

        // Get AI model info configured for HOSxP
        $aiModelInfo = [
            'provider' => AiService::getProvider('hosxp'),
            'model' => AiService::getHosxpModelName(),
        ];

        return view('emr.hosxp_setting.index', compact(
            'hosxpAlive',
            'activeTab',
            'stats',
            'records',
            'activeDocs',
            'inactiveDocs',
            'invalidLicenseDocs',
            'invalidCidDocs',
            'doctorTabConfigs',
            'search',
            'filter',
            'aiModelInfo'
        ));
    }

    /**
     * Dedicated AI Copilot Query for HOSxP Master Data
     */
    public function copilotAsk(Request $request)
    {
        $this->checkPermission();

        $query = trim($request->input('query', ''));
        $tab = $request->input('tab', 'doctor');

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุคำถามหรือข้อสงสัยครับ'
            ], 422);
        }

        try {
            // 1. Fetch relevant context from HOSxP Context Service (passing $tab for direct table routing)
            $contextService = app(HosxpContextService::class);
            $hosxpContext = $contextService->getContext($query, $tab);

            // 2. Fetch relevant standard guidelines from RAG if available
            $ragContext = null;
            try {
                if (class_exists(RagSearchService::class)) {
                    $ragService = app(RagSearchService::class);
                    $ragResult = $ragService->search($query, 3);
                    if (!empty($ragResult['results'])) {
                        $chunks = [];
                        foreach ($ragResult['results'] as $r) {
                            $chunks[] = "• [จากคู่มือ {$r['filename']}]: {$r['content']}";
                        }
                        $ragContext = implode("\n", $chunks);
                    }
                }
            } catch (\Throwable $re) {
                Log::info("RAG search optional skip: " . $re->getMessage());
            }

            // 3. Build Prompt with Domain Instructions
            $systemPrompt = "คุณคือ 'RiMS Copilot' ผู้ช่วย AI อัจฉริยะด้านข้อมูลระบบ HOSxP และงานเวชระเบียนของโรงพยาบาล
คุณมีหน้าที่ช่วยตรวจสอบความถูกต้อง แนะนำการตรวจสอบข้อมูล และให้คำแนะนำที่ถูกต้องตามมาตรฐาน สปสช., กรมบัญชีกลาง และกระทรวงสาธารณสุข

*** ข้อกำหนดและกฎสำคัญเด็ดขาด (Strict Rules) ***:
1. ห้ามเขียนคำสั่ง SQL ทุกชนิด (เช่น SELECT, UPDATE, INSERT, DELETE) เด็ดขาด ไม่ต้องแสดงบล็อกคำสั่ง SQL ใดๆ ให้ผู้ใช้ แม้ผู้ใช้จะขอโดยตรง ให้แจ้งอย่างสุภาพว่าระบบสงวนสิทธิ์ไม่แสดงคำสั่งแก้ฐานข้อมูลโดยตรงเพื่อความปลอดภัยของข้อมูลโรงพยาบาล
2. ห้ามเอ่ยชื่อตารางฐานข้อมูลภายในโดยตรง (เช่น doctor, nondrugitems, pttype, spclty, income ฯลฯ) ให้ใช้ภาษาทางการแพทย์หรือชื่อหมวดข้อมูลเชิงธุรกิจแทน เช่น 'ข้อมูลแพทย์และบุคลากร', 'รายการค่าบริการและค่ารักษาพยาบาล', 'สิทธิการรักษาพยาบาล'
3. ให้คำแนะนำผู้ใช้ในการตรวจสอบและแก้ไขข้อมูลผ่าน 'หน้าจอเมนูของโปรแกรม HOSxP' เป็นหลัก เช่น:
   - ข้อมูลแพทย์/บุคลากร: แนะนำให้เข้าเมนู 'เครื่องมือ (Tools) > ตั้งค่าระบบ (System Setting) > กำหนดข้อมูลแพทย์/ผู้ให้บริการ' เพื่อแก้ไขหรือบันทึกเลขที่ใบประกอบวิชาชีพ, เลขประจำตัวประชาชน 13 หลัก, และรหัสสภาวิชาชีพ
   - ข้อมูลค่ารักษาพยาบาล: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดรายการค่ารักษาพยาบาล (Non-Drug Items)' เพื่อตรวจสอบการผูกรหัส ADP และหมวดรายได้
   - ข้อมูลสิทธิการรักษา: แนะนำให้เข้าเมนู 'เครื่องมือ > ตั้งค่าระบบ > กำหนดสิทธิการรักษา (Pttype)' เพื่อตรวจสอบรหัสสิทธิมาตรฐานและรหัสส่งออกเคลม
4. อธิบายอย่างเป็นมืออาชีพ สุภาพ ชัดเจน เข้าใจง่าย ชี้ให้เห็นว่าข้อมูลขาดอะไรและจะส่งผลกระทบต่อการส่งออก 43 แฟ้ม หรือการส่งเบิกเคลมอย่างไร พร้อมบอกวิธีบันทึกแก้ไขใน HOSxP
5. จัดรูปแบบด้วย Markdown ใช้หัวข้อ, bullet points, และตัวหนาให้อ่านง่าย สบายตา";

            $userPrompt = "คำถามจากเจ้าหน้าที่ (หมวด {$tab}):\n\"{$query}\"\n\n";

            if ($hosxpContext && !empty($hosxpContext['text'])) {
                $userPrompt .= "--- ข้อมูลบริบทจริงจากระบบ HOSxP ---\n" . $hosxpContext['text'] . "\n\n";
            }

            if ($ragContext) {
                $userPrompt .= "--- ข้อกำหนดและแนวทางมาตรฐานจากคลังความรู้ RAG ---\n" . $ragContext . "\n\n";
            }

            $userPrompt .= "กรุณาตอบคำถาม วิเคราะห์จุดที่ข้อมูลไม่สมบูรณ์หรือความเสี่ยงต่อการส่งเคลม และให้คำแนะนำการตรวจสอบ/บันทึกแก้ไขข้อมูลผ่านหน้าจอโปรแกรม HOSxP โดยห้ามระบุคำสั่ง SQL หรือชื่อตารางฐานข้อมูลครับ";

            $aiService = app(AiService::class);
            $answer = $aiService->generateChat($userPrompt, $systemPrompt, 'hosxp');

            return response()->json([
                'success' => true,
                'answer' => $answer,
                'provider' => AiService::getProvider('hosxp'),
                'model' => AiService::getHosxpModelName(),
                'sources' => $hosxpContext['sources'] ?? []
            ]);

        } catch (\Throwable $e) {
            Log::error("HOSxP Setting Copilot Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผล AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
