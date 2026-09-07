<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Ai\AiService;
use App\Services\Ai\Context\HosxpContextService;
use App\Services\Ai\RagSearchService;

class MrecHosxpMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Check if user has permission to access Medical Records Master Data
     */
    protected function checkPermission()
    {
        if (!auth()->check()) {
            abort(403, 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }
        $user = auth()->user();
        if ($user->status !== 'admin' && ($user->allow_emr ?? 'N') !== 'Y') {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงระบบข้อมูลพื้นฐาน HOSxP (งานเวชระเบียน)');
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
            return view('mrec.hosxp_master.index', [
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
        $docTotal = $hosxp->table('doctor')->count();
        $docActive = $hosxp->table('doctor')->where('active', 'Y')->count();
        $docMissingCouncil = $hosxp->table('doctor')->where('active', 'Y')
            ->where(function ($q) {
                $q->whereNull('council_code')->orWhere('council_code', '');
            })->count();
        $docMissingLic = $hosxp->table('doctor')->where('active', 'Y')
            ->where(function ($q) {
                $q->whereNull('licenseno')->orWhere('licenseno', '')->orWhere('licenseno', '-');
            })->count();

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

        $stats = [
            'doctor' => [
                'total' => $docTotal,
                'active' => $docActive,
                'inactive' => $docTotal - $docActive,
                'missing_council' => $docMissingCouncil,
                'missing_license' => $docMissingLic,
            ],
            'nondrugitems' => [
                'total' => $itemTotal,
                'active' => $itemActive,
                'inactive' => $itemTotal - $itemActive,
                'missing_adp' => $itemMissingAdp,
                'mapped_adp' => $itemActive - $itemMissingAdp,
            ],
            'pttype' => [
                'total' => $ptTotal,
                'active' => $ptActive,
                'inactive' => $ptTotal - $ptActive,
                'missing_std' => $ptMissingStd,
                'missing_hip' => $ptMissingHip,
                'mapped_std' => $ptActive - $ptMissingStd,
            ]
        ];

        // ==========================================
        // 2. Fetch Tab Specific Records
        // ==========================================
        $records = null;

        if ($activeTab === 'doctor') {
            $query = $hosxp->table('doctor');

            if ($filter === 'active') {
                $query->where('active', 'Y');
            } elseif ($filter === 'inactive') {
                $query->where(function ($q) {
                    $q->whereNull('active')->orWhere('active', '!=', 'Y');
                });
            } elseif ($filter === 'missing_council') {
                $query->where('active', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('council_code')->orWhere('council_code', '');
                    });
            } elseif ($filter === 'missing_license') {
                $query->where('active', 'Y')
                    ->where(function ($q) {
                        $q->whereNull('licenseno')->orWhere('licenseno', '')->orWhere('licenseno', '-');
                    });
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('licenseno', 'like', "%{$search}%")
                      ->orWhere('cid', 'like', "%{$search}%")
                      ->orWhere('department', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('active', 'desc')
                ->orderBy('code', 'asc')
                ->paginate(50)
                ->withQueryString();

        } elseif ($activeTab === 'nondrugitems') {
            $query = $hosxp->table('nondrugitems as n')
                ->leftJoin('income as i', 'i.income', '=', 'n.income')
                ->leftJoin('nhso_adp_type as t', 't.nhso_adp_type_id', '=', 'n.nhso_adp_type_id')
                ->select([
                    'n.icode', 'n.name', 'n.income', 'i.name as income_name',
                    'n.price', 'n.ipd_price', 'n.unitcost',
                    'n.nhso_adp_code', 'n.nhso_adp_type_id', 't.nhso_adp_type_name',
                    'n.billcode', 'n.billnumber', 'n.istatus', 'n.sks_coverage_price'
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
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('n.name', 'like', "%{$search}%")
                      ->orWhere('n.icode', 'like', "%{$search}%")
                      ->orWhere('n.nhso_adp_code', 'like', "%{$search}%")
                      ->orWhere('i.name', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('n.istatus', 'desc')
                ->orderBy('n.icode', 'asc')
                ->paginate(50)
                ->withQueryString();

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
                ->paginate(50)
                ->withQueryString();
        }

        // Get AI model info configured for HOSxP
        $aiModelInfo = [
            'provider' => AiService::getProvider('hosxp'),
            'model' => AiService::getHosxpModelName(),
        ];

        return view('mrec.hosxp_master.index', compact(
            'hosxpAlive',
            'activeTab',
            'stats',
            'records',
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
            // 1. Fetch relevant context from HOSxP Context Service
            $contextService = app(HosxpContextService::class);
            $hosxpContext = $contextService->getContext($query);

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
คุณมีหน้าที่ช่วยตรวจสอบความถูกต้อง แนะนำการแมปรหัสมาตรฐาน (เช่น NHSO ADP, pttype_standard, licenseno) และให้คำแนะนำที่ถูกต้องตามมาตรฐาน สปสช., กรมบัญชีกลาง และกระทรวงสาธารณสุข
กติกาการตอบ:
1. ใช้ภาษาไทยที่สุภาพ เป็นมืออาชีพ กระชับ และเข้าใจง่าย
2. หากมีการแนะนำให้แก้ไขฐานข้อมูล ให้เขียนคำสั่ง SQL (UPDATE / SELECT) ใน fenced code block ```sql ชัดเจน เพื่อให้เจ้าหน้าที่นำไปตรวจสอบก่อนใช้งาน
3. อ้างอิงข้อมูลจริงจากบริบทของโรงพยาบาลที่แนบให้เสมอ";

            $userPrompt = "คำถามจากเจ้าหน้าที่เวชระเบียน (หมวด {$tab}):\n\"{$query}\"\n\n";

            if ($hosxpContext && !empty($hosxpContext['text'])) {
                $userPrompt .= "--- ข้อมูลบริบทจริงจากฐานข้อมูล HOSxP ---\n" . $hosxpContext['text'] . "\n\n";
            }

            if ($ragContext) {
                $userPrompt .= "--- ข้อกำหนดและแนวทางมาตรฐานจากคลังความรู้ RAG ---\n" . $ragContext . "\n\n";
            }

            $userPrompt .= "กรุณาตอบคำถาม วิเคราะห์จุดผิดปกติหรือความเสี่ยง และให้แนวทางแก้ไขหรือคำสั่ง SQL ที่ปลอดภัยและถูกต้องครับ";

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
            Log::error("Mrec Copilot Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผล AI: ' . $e->getMessage()
            ], 500);
        }
    }
}
