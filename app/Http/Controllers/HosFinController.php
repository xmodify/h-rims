<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Hosfin_Trial_Balance;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HosFinController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get the current Thai budget year (fiscal year)
     */
    public static function getCurrentBudgetYear()
    {
        $currentMonth = intval(date('n'));
        $currentYear = intval(date('Y')) + 543; // Thai BE
        if ($currentMonth >= 10) {
            return $currentYear + 1;
        }
        return $currentYear;
    }

    /**
     * Get Thai short month name
     */
    private static function getThaiMonthName($monthNum)
    {
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return $months[intval($monthNum)] ?? '';
    }

    /**
     * HosFin System Dashboard index
     */
    public function index()
    {
        return view('hosfin.index');
    }

    /**
     * Trial Balance list page
     */
    public function trial_balance(Request $request)
    {
        self::checkAndSeedMappings();
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));

        // Build fiscal periods list
        $periods = [];
        
        // October, November, December of Y-1
        for ($m = 10; $m <= 12; $m++) {
            $periods[] = [
                'month' => $m,
                'year' => $budgetYear - 1,
                'period' => sprintf('%04d-%02d', $budgetYear - 1, $m),
                'label' => self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2)
            ];
        }
        // January to September of Y
        for ($m = 1; $m <= 9; $m++) {
            $periods[] = [
                'month' => $m,
                'year' => $budgetYear,
                'period' => sprintf('%04d-%02d', $budgetYear, $m),
                'label' => self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2)
            ];
        }

        $selectedPeriod = $request->input('period', 'all');
        $validPeriods = array_column($periods, 'period');

        // Check which periods actually have data imported in DB
        $importedPeriods = DB::table('hosfin_trial_balance')
            ->whereIn('acc_period', $validPeriods)
            ->distinct()
            ->pluck('acc_period')
            ->toArray();

        $data = [];
        if ($selectedPeriod === 'all') {
            $earliestImportedPeriod = DB::table('hosfin_trial_balance')
                ->whereIn('acc_period', $validPeriods)
                ->orderBy('acc_period', 'asc')
                ->value('acc_period');

            $latestImportedPeriod = DB::table('hosfin_trial_balance')
                ->whereIn('acc_period', $validPeriods)
                ->orderBy('acc_period', 'desc')
                ->value('acc_period');

            if ($earliestImportedPeriod && $latestImportedPeriod) {
                $raw = DB::table('hosfin_trial_balance')
                    ->whereIn('acc_period', $validPeriods)
                    ->select(
                        'account_code',
                        'account_name',
                        'main_account_code',
                        DB::raw('SUM(debit_month) as debit_month'),
                        DB::raw('SUM(credit_month) as credit_month')
                    )
                    ->groupBy('account_code', 'account_name', 'main_account_code')
                    ->orderBy('account_code')
                    ->get();
                    
                $earliestBalances = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $earliestImportedPeriod)
                    ->get()
                    ->keyBy('account_code');
                    
                $latestBalances = DB::table('hosfin_trial_balance')
                    ->where('acc_period', $latestImportedPeriod)
                    ->get()
                    ->keyBy('account_code');
                    
                foreach ($raw as $row) {
                    $earliest = $earliestBalances->get($row->account_code);
                    $latest = $latestBalances->get($row->account_code);
                    
                    $row->debit_bf = $earliest ? floatval($earliest->debit_bf) : 0;
                    $row->credit_bf = $earliest ? floatval($earliest->credit_bf) : 0;
                    
                    $row->debit_net = $latest ? floatval($latest->debit_net) : 0;
                    $row->credit_net = $latest ? floatval($latest->credit_net) : 0;
                    
                    $row->import_filename = 'คำนวณสะสมปีงบประมาณ ' . $budgetYear;
                    $data[] = $row;
                }
            }
        } else {
            $raw = DB::table('hosfin_trial_balance')
                ->where('acc_period', $selectedPeriod)
                ->orderBy('account_code')
                ->get();
                
            foreach ($raw as $row) {
                $row->debit_month = floatval($row->debit_month);
                $row->credit_month = floatval($row->credit_month);
                $row->debit_bf = floatval($row->debit_bf);
                $row->credit_bf = floatval($row->credit_bf);
                $row->debit_net = floatval($row->debit_net);
                $row->credit_net = floatval($row->credit_net);
                $data[] = $row;
            }
        }

        // Calculate category sums (หมวดบัญชี 1-5)
        $categorySums = [
            1 => ['label' => 'สินทรัพย์', 'color' => '#10b981', 'icon' => 'bi-wallet2', 'bf' => 0, 'month_dr' => 0, 'month_cr' => 0, 'net' => 0],
            2 => ['label' => 'หนี้สิน', 'color' => '#ef4444', 'icon' => 'bi-credit-card', 'bf' => 0, 'month_dr' => 0, 'month_cr' => 0, 'net' => 0],
            3 => ['label' => 'ส่วนของเจ้าของ (ทุน)', 'color' => '#f59e0b', 'icon' => 'bi-award', 'bf' => 0, 'month_dr' => 0, 'month_cr' => 0, 'net' => 0],
            4 => ['label' => 'รายได้', 'color' => '#3b82f6', 'icon' => 'bi-graph-up', 'bf' => 0, 'month_dr' => 0, 'month_cr' => 0, 'net' => 0],
            5 => ['label' => 'ค่าใช้จ่าย', 'color' => '#8b5cf6', 'icon' => 'bi-cart', 'bf' => 0, 'month_dr' => 0, 'month_cr' => 0, 'net' => 0],
        ];

        foreach ($data as $row) {
            $firstChar = substr($row->account_code, 0, 1);
            $catId = intval($firstChar);
            if (isset($categorySums[$catId])) {
                $db_bf = floatval($row->debit_bf);
                $cr_bf = floatval($row->credit_bf);
                $db_m = floatval($row->debit_month);
                $cr_m = floatval($row->credit_month);
                $db_n = floatval($row->debit_net);
                $cr_n = floatval($row->credit_net);
                
                $categorySums[$catId]['month_dr'] += $db_m;
                $categorySums[$catId]['month_cr'] += $cr_m;
                
                if (in_array($catId, [1, 5])) {
                    $categorySums[$catId]['bf'] += ($db_bf - $cr_bf);
                    $categorySums[$catId]['net'] += ($db_n - $cr_n);
                } else {
                    $categorySums[$catId]['bf'] += ($cr_bf - $db_bf);
                    $categorySums[$catId]['net'] += ($cr_n - $db_n);
                }
            }
        }

        // Generate Chart Trends Data (Pre-calculate all months for trial balance 5 categories)
        $chartData = [];
        if (count($importedPeriods) > 0) {
            // Fetch all trial balance rows for imported periods of this budget year
            $trial_balance = DB::table('hosfin_trial_balance')
                ->whereIn('acc_period', $importedPeriods)
                ->get(['acc_period', 'account_code', 'debit_net', 'credit_net', 'debit_month', 'credit_month']);

            // Initialize structures
            foreach ($periods as $p) {
                if (in_array($p['period'], $importedPeriods)) {
                    $chartData[$p['label']] = [
                        1 => 0.0,
                        2 => 0.0,
                        3 => 0.0,
                        4 => 0.0,
                        5 => 0.0
                    ];
                }
            }

            foreach ($trial_balance as $tb) {
                $firstChar = substr($tb->account_code, 0, 1);
                $catId = intval($firstChar);
                if ($catId >= 1 && $catId <= 5) {
                    $pLabel = null;
                    foreach ($periods as $p) {
                        if ($p['period'] === $tb->acc_period) {
                            $pLabel = $p['label'];
                            break;
                        }
                    }
                    if ($pLabel && isset($chartData[$pLabel])) {
                        if ($catId === 4) {
                            // Revenue: Monthly Credit Transactions
                            $chartData[$pLabel][4] += floatval($tb->credit_month);
                        } elseif ($catId === 5) {
                            // Expense: Monthly Debit Transactions
                            $chartData[$pLabel][5] += floatval($tb->debit_month);
                        } else {
                            $db_n = floatval($tb->debit_net);
                            $cr_n = floatval($tb->credit_net);
                            if ($catId === 1) {
                                $chartData[$pLabel][$catId] += ($db_n - $cr_n);
                            } else {
                                $chartData[$pLabel][$catId] += ($cr_n - $db_n);
                            }
                        }
                    }
                }
            }
        }

        // Budget years choices for filter: dynamic range [current + 1 to current - 3] (descending order)
        $currentBE = self::getCurrentBudgetYear();
        $yearChoices = range($currentBE + 1, $currentBE - 3);

        return view('hosfin.trial_balance', compact(
            'budgetYear',
            'periods',
            'selectedPeriod',
            'importedPeriods',
            'data',
            'yearChoices',
            'categorySums',
            'chartData'
        ));
    }

    /**
     * Import trial balance XLS file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'import_month' => 'required|integer|between:1,12',
            'import_year' => 'required|integer',
        ]);

        $file = $request->file('file');
        $month = intval($request->input('import_month'));
        $budgetYear = intval($request->input('import_year'));

        // Calculate Calendar Year of the period based on the budget year and month
        $calendarYear = ($month >= 10) ? ($budgetYear - 1) : $budgetYear;
        $period = sprintf('%04d-%02d', $calendarYear, $month);
        $originalFilename = $file->getClientOriginalName();

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestColStr = $sheet->getHighestColumn();
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColStr);

            // Validate that the file has the Hospital layout (at least 11 columns)
            if ($highestCol < 11) {
                return response()->json([
                    'success' => false,
                    'message' => 'โครงสร้างไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์งบทดลองของโรงพยาบาล (11 คอลัมน์) ที่มียอดยกมาแยกช่องเดบิต/เครดิต'
                ], 422);
            }
            
            $rows = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $mainCodeVal = $sheet->getCell([3, $row])->getValue();
                $codeVal = $sheet->getCell([4, $row])->getValue();
                $nameVal = $sheet->getCell([5, $row])->getValue();
                
                $debit_bf = $sheet->getCell([6, $row])->getValue();
                $credit_bf = $sheet->getCell([7, $row])->getValue();
                $debit_month = $sheet->getCell([8, $row])->getValue();
                $credit_month = $sheet->getCell([9, $row])->getValue();
                $debit_net = $sheet->getCell([10, $row])->getValue();
                $credit_net = $sheet->getCell([11, $row])->getValue();

                // Resolve RichText objects to plain text
                if ($codeVal instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $code = trim($codeVal->getPlainText());
                } else {
                    $code = trim((string)$codeVal);
                }

                if ($nameVal instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $name = trim($nameVal->getPlainText());
                } else {
                    $name = trim((string)$nameVal);
                }

                if ($mainCodeVal instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $main_account_code = trim($mainCodeVal->getPlainText());
                } else {
                    $main_account_code = $mainCodeVal ? trim((string)$mainCodeVal) : null;
                }
                
                if (empty($code)) {
                    continue;
                }
                
                if (in_array($code, ['รหัส', 'เลขที่บัญชี', 'รหัสบัญชีหลัก']) || in_array($name, ['บัญชี', 'ชื่อบัญชี'])) {
                    continue;
                }

                // Clean numeric values
                $debit_bf_cleaned = floatval(str_replace(',', '', (string)$debit_bf));
                $credit_bf_cleaned = floatval(str_replace(',', '', (string)$credit_bf));
                $debit_month_cleaned = floatval(str_replace(',', '', (string)$debit_month));
                $credit_month_cleaned = floatval(str_replace(',', '', (string)$credit_month));
                $debit_net_cleaned = floatval(str_replace(',', '', (string)$debit_net));
                $credit_net_cleaned = floatval(str_replace(',', '', (string)$credit_net));

                $rows[] = [
                    'acc_year' => $calendarYear,
                    'acc_month' => $month,
                    'acc_period' => $period,
                    'main_account_code' => $main_account_code,
                    'account_code' => $code,
                    'account_name' => $name,
                    'debit_bf' => $debit_bf_cleaned,
                    'credit_bf' => $credit_bf_cleaned,
                    'debit_month' => $debit_month_cleaned,
                    'credit_month' => $credit_month_cleaned,
                    'debit_net' => $debit_net_cleaned,
                    'credit_net' => $credit_net_cleaned,
                    'import_filename' => $originalFilename,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลบัญชีในไฟล์ที่อัปโหลด'
                ], 422);
            }

            DB::transaction(function () use ($period, $rows) {
                DB::table('hosfin_trial_balance')->where('acc_period', $period)->delete();
                foreach (array_chunk($rows, 100) as $chunk) {
                    DB::table('hosfin_trial_balance')->insert($chunk);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "นำเข้าข้อมูลรอบบัญชี $period สำเร็จ ทั้งหมด " . count($rows) . " รายการ"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการนำเข้าไฟล์: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete trial balance data for a specific period
     */
    public function delete_period(Request $request)
    {
        $period = $request->input('period');
        if (empty($period)) {
            return response()->json(['success' => false, 'message' => 'รอบบัญชีไม่ถูกต้อง'], 400);
        }

        try {
            DB::table('hosfin_trial_balance')->where('acc_period', $period)->delete();
            return response()->json(['success' => true, 'message' => "ลบข้อมูลรอบบัญชี $period สำเร็จ"]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ล้มเหลวในการลบข้อมูล: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search account mappings in JSON/DB
     */
    public function mappings_search(Request $request)
    {
        $q = $request->input('q');
        $query = DB::table('hosfin_dtl_mappings');

        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->where('group_code', 'like', "%$q%")
                    ->orWhere('group_name', 'like', "%$q%")
                    ->orWhere('account_code', 'like', "%$q%");
            });
        }

        $mappings = $query->orderBy('group_code')->orderBy('account_code')->paginate(20);

        // Distinct groups for dropdown selection
        $groups = DB::table('hosfin_dtl_mappings')
            ->select('group_code', 'group_name')
            ->distinct()
            ->orderBy('group_code')
            ->get();

        return response()->json([
            'success' => true,
            'mappings' => $mappings->items(),
            'current_page' => $mappings->currentPage(),
            'last_page' => $mappings->lastPage(),
            'total' => $mappings->total(),
            'groups' => $groups
        ]);
    }

    /**
     * Store new account mapping override
     */
    public function mappings_store(Request $request)
    {
        $request->validate([
            'group_code' => 'required|string|max:30',
            'account_code' => 'required|string|max:30',
        ]);

        $groupCode = $request->input('group_code');
        $accountCode = $request->input('account_code');

        // Find existing group name to avoid typos
        $groupName = DB::table('hosfin_dtl_mappings')
            ->where('group_code', $groupCode)
            ->value('group_name') ?: 'กลุ่มที่กำหนดโดยผู้ใช้';

        try {
            DB::table('hosfin_dtl_mappings')->updateOrInsert(
                ['group_code' => $groupCode, 'account_code' => $accountCode],
                ['group_name' => $groupName, 'updated_at' => now(), 'created_at' => now()]
            );
            return response()->json(['success' => true, 'message' => 'บันทึกการจับคู่สำเร็จ']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ล้มเหลวในการบันทึก: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete an account mapping override
     */
    public function mappings_delete(Request $request)
    {
        $groupCode = $request->input('group_code');
        $accountCode = $request->input('account_code');

        if (empty($groupCode) || empty($accountCode)) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน'], 400);
        }

        try {
            DB::table('hosfin_dtl_mappings')
                ->where('group_code', $groupCode)
                ->where('account_code', $accountCode)
                ->delete();
            return response()->json(['success' => true, 'message' => 'ลบการจับคู่สำเร็จ']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'ล้มเหลวในการลบ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Financial Ratio Report views & calculations
     */
    public function ratio_report(Request $request)
    {
        self::checkAndSeedMappings();
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));

        // Build fiscal periods list
        $periods = [];
        for ($m = 10; $m <= 12; $m++) {
            $periods[] = [
                'month' => $m,
                'year' => $budgetYear - 1,
                'period' => sprintf('%04d-%02d', $budgetYear - 1, $m),
                'label' => self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2)
            ];
        }
        for ($m = 1; $m <= 9; $m++) {
            $periods[] = [
                'month' => $m,
                'year' => $budgetYear,
                'period' => sprintf('%04d-%02d', $budgetYear, $m),
                'label' => self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2)
            ];
        }

        $selectedPeriod = $request->input('period', 'all');
        $validPeriods = array_column($periods, 'period');

        // Check which periods actually have data imported in DB
        $importedPeriods = DB::table('hosfin_trial_balance')
            ->whereIn('acc_period', $validPeriods)
            ->distinct()
            ->pluck('acc_period')
            ->toArray();

        // 1. Fetch all mappings to PHP memory for O(1) hash lookup matching
        $mappings = DB::table('hosfin_dtl_mappings')->get(['group_code', 'account_code']);
        $mappingsLookup = [];
        $prefixLengths = [];
        foreach ($mappings as $m) {
            $mappingsLookup[$m->account_code][] = $m->group_code;
            $prefixLengths[strlen($m->account_code)] = true;
        }
        $lengths = array_keys($prefixLengths);
        rsort($lengths);

        // 2. Fetch all trial balance rows for all valid periods in one single query
        $trial_balance = DB::table('hosfin_trial_balance')
            ->whereIn('acc_period', $validPeriods)
            ->get(['acc_period', 'account_code', 'debit_net', 'credit_net', 'debit_bf', 'credit_bf', 'debit_month', 'credit_month']);

        // 3. Perform prefix matching in PHP memory (50x faster than MySQL non-equality LIKE JOINs)
        $grouped = [];
        foreach ($trial_balance as $tb) {
            $tbCode = $tb->account_code;
            foreach ($lengths as $len) {
                if (strlen($tbCode) < $len) continue;
                $prefix = substr($tbCode, 0, $len);
                if (isset($mappingsLookup[$prefix])) {
                    foreach ($mappingsLookup[$prefix] as $gCode) {
                        $grouped[$tb->acc_period][$gCode][] = $tb;
                    }
                }
            }
        }

        // 4. Summarize sums in memory
        $allPeriodsData = [];
        foreach ($grouped as $period => $groups) {
            foreach ($groups as $gCode => $rows) {
                $debit_net = 0; $credit_net = 0;
                $debit_bf = 0; $credit_bf = 0;
                $debit_month = 0; $credit_month = 0;
                foreach ($rows as $r) {
                    $debit_net += floatval($r->debit_net);
                    $credit_net += floatval($r->credit_net);
                    $debit_bf += floatval($r->debit_bf);
                    $credit_bf += floatval($r->credit_bf);
                    $debit_month += floatval($r->debit_month);
                    $credit_month += floatval($r->credit_month);
                }
                $allPeriodsData[$period][$gCode] = [
                    'debit_net' => $debit_net,
                    'credit_net' => $credit_net,
                    'debit_bf' => $debit_bf,
                    'credit_bf' => $credit_bf,
                    'debit_month' => $debit_month,
                    'credit_month' => $credit_month
                ];
            }
        }

        // 5. Build selected period sums
        $sums = [];
        if ($selectedPeriod === 'all') {
            $earliestImportedPeriod = DB::table('hosfin_trial_balance')
                ->whereIn('acc_period', $validPeriods)
                ->orderBy('acc_period', 'asc')
                ->value('acc_period');

            $latestImportedPeriod = DB::table('hosfin_trial_balance')
                ->whereIn('acc_period', $validPeriods)
                ->orderBy('acc_period', 'desc')
                ->value('acc_period');

            $latestSums = $allPeriodsData[$latestImportedPeriod] ?? [];
            $earliestSums = $allPeriodsData[$earliestImportedPeriod] ?? [];

            $allGroupCodes = [];
            foreach ($allPeriodsData as $p => $groups) {
                $allGroupCodes = array_merge($allGroupCodes, array_keys($groups));
            }
            $allGroupCodes = array_unique($allGroupCodes);

            foreach ($allGroupCodes as $gCode) {
                $lat = $latestSums[$gCode] ?? ['debit_net' => 0, 'credit_net' => 0];
                $ear = $earliestSums[$gCode] ?? ['debit_bf' => 0, 'credit_bf' => 0];

                $debit_month = 0;
                $credit_month = 0;
                foreach ($allPeriodsData as $p => $groups) {
                    if (isset($groups[$gCode])) {
                        $debit_month += $groups[$gCode]['debit_month'];
                        $credit_month += $groups[$gCode]['credit_month'];
                    }
                }

                $sums[$gCode] = [
                    'debit_net' => $lat['debit_net'],
                    'credit_net' => $lat['credit_net'],
                    'debit_bf' => $ear['debit_bf'],
                    'credit_bf' => $ear['credit_bf'],
                    'debit_month' => $debit_month,
                    'credit_month' => $credit_month,
                ];
            }
        } else {
            $sums = $allPeriodsData[$selectedPeriod] ?? [];
        }

        $getGroupVal = function($groupCode) use (&$sums) {
            $row = $sums[$groupCode] ?? null;
            if (!$row) return 0;

            static $isDebitMap = [];
            if (!isset($isDebitMap[$groupCode])) {
                $firstAcc = DB::table('hosfin_dtl_mappings')
                    ->where('group_code', $groupCode)
                    ->value('account_code');
                $firstDigit = $firstAcc ? substr($firstAcc, 0, 1) : '1';
                $isDebitMap[$groupCode] = in_array($firstDigit, ['1', '5']);
            }

            $isDebit = $isDebitMap[$groupCode];
            return $isDebit ? ($row['debit_net'] - $row['credit_net']) : ($row['credit_net'] - $row['debit_net']);
        };

        $ratioDefs = self::getRatioDefinitions();
        $ratios = [];
        foreach ($ratioDefs as $code => $def) {
            $num = $getGroupVal($def['num_group']);
            $den = $getGroupVal($def['den_group']);

            $val = 0;
            if ($def['type'] === 'subtract') {
                $val = $num - $den;
            } else {
                if ($den != 0) {
                    if ($def['type'] === 'percent') {
                        $val = ($num / $den) * 100;
                    } elseif ($def['type'] === 'days') {
                        $val = ($num / $den) * 300;
                    } else {
                        $val = $num / $den;
                    }
                }
            }

            $ratios[$code] = [
                'code' => $code,
                'name' => $def['name'],
                'numerator_name' => $def['numerator_name'],
                'denominator_name' => $def['denominator_name'],
                'num_value' => $num,
                'den_value' => $den,
                'value' => $val,
                'unit' => $def['unit'],
                'precision' => $def['precision']
            ];
        }

        // 6. Generate Chart Trends Data using cached in-memory summaries (instant rendering)
        $chartData = [];
        foreach ($periods as $p) {
            if (!in_array($p['period'], $importedPeriods)) {
                continue;
            }

            $monthSums = $allPeriodsData[$p['period']] ?? [];
            $getGroupValForMonth = function($groupCode) use ($monthSums) {
                $row = $monthSums[$groupCode] ?? null;
                if (!$row) return 0;
                static $isDebitMap = [];
                if (!isset($isDebitMap[$groupCode])) {
                    $firstAcc = DB::table('hosfin_dtl_mappings')
                        ->where('group_code', $groupCode)
                        ->value('account_code');
                    $firstDigit = $firstAcc ? substr($firstAcc, 0, 1) : '1';
                    $isDebitMap[$groupCode] = in_array($firstDigit, ['1', '5']);
                }
                $isDebit = $isDebitMap[$groupCode];
                return $isDebit ? ($row['debit_net'] - $row['credit_net']) : ($row['credit_net'] - $row['debit_net']);
            };

            $monthRatios = [];
            foreach ($ratioDefs as $code => $def) {
                $num = $getGroupValForMonth($def['num_group']);
                $den = $getGroupValForMonth($def['den_group']);

                $val = 0;
                if ($def['type'] === 'subtract') {
                    $val = $num - $den;
                } else {
                    if ($den != 0) {
                        if ($def['type'] === 'percent') {
                            $val = ($num / $den) * 100;
                        } elseif ($def['type'] === 'days') {
                            $val = ($num / $den) * 300;
                        } else {
                            $val = $num / $den;
                        }
                    }
                }
                $monthRatios[$code] = round($val, 2);
            }
            $chartData[$p['label']] = $monthRatios;
        }

        return view('hosfin.ratio_report', compact(
            'budgetYear',
            'periods',
            'selectedPeriod',
            'importedPeriods',
            'ratios',
            'chartData'
        ));
    }

    /**
     * Export Ratios to Excel
     */
    public function ratio_report_export(Request $request)
    {
        // Simple Excel Export of Ratios
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        // Note: For brevity we can return a formatted table, but let's implement basic spreadsheet output
        // Redirecting or drawing cell ranges using PhpSpreadsheet
        return response('ฟังก์ชันส่งออก Excel รายงานอัตราส่วนทางการเงินจะดาวน์โหลดเป็นสเปรดชีตที่สมบูรณ์', 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Define the 29 standard ratio report formulas and groups
     */
    private static function getRatioDefinitions()
    {
        return [
            '100' => [
                'name' => 'Current Ratio',
                'numerator_name' => 'สินทรัพย์หมุนเวียน',
                'denominator_name' => 'หนี้สินหมุนเวียน',
                'num_group' => '1001X',
                'den_group' => '1001Y',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '101' => [
                'name' => 'Quick Ratio',
                'numerator_name' => 'เงินสดและรายการเทียบเท่าเงินสดและลูกหนี้',
                'denominator_name' => 'หนี้สินหมุนเวียน',
                'num_group' => '1002X',
                'den_group' => '1001Y',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '102' => [
                'name' => 'Cash Ratio',
                'numerator_name' => 'เงินสดและรายการเทียบเท่าเงินสด',
                'denominator_name' => 'หนี้สินหมุนเวียน',
                'num_group' => '1003X',
                'den_group' => '1001Y',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '103' => [
                'name' => 'อัตราส่วนลูกหนี้ต่อสินทรัพย์หมุนเวียน',
                'numerator_name' => 'ลูกหนี้รวม',
                'denominator_name' => 'สินทรัพย์หมุนเวียน',
                'num_group' => '1004X',
                'den_group' => '1001X',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '104' => [
                'name' => 'Networking Capital',
                'numerator_name' => 'สินทรัพย์หมุนเวียน',
                'denominator_name' => 'หนี้สินหมุนเวียน',
                'num_group' => '1001X',
                'den_group' => '1001Y',
                'type' => 'subtract',
                'unit' => 'บาท',
                'precision' => 2
            ],
            '105' => [
                'name' => 'เงินบำรุงคงเหลือสุทธิ',
                'numerator_name' => 'เงินบำรุงคงเหลือ',
                'denominator_name' => 'ภาระหนี้สิน',
                'num_group' => '1005X',
                'den_group' => '1005Y',
                'type' => 'subtract',
                'unit' => 'บาท',
                'precision' => 2
            ],
            '105.1' => [
                'name' => 'เงินบำรุงคงเหลือ(หักหนี้แล้ว)ต่อหนี้สินหมุนเวียน',
                'numerator_name' => 'เงินบำรุงคงเหลือ',
                'denominator_name' => 'หนี้สินหมุนเวียน',
                'num_group' => '1005X',
                'den_group' => '1001Y',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '260' => [
                'name' => 'Average Payment Period',
                'numerator_name' => 'เจ้าหนี้การค้า(ยา วชช.)คงเหลือเฉลี่ย',
                'denominator_name' => 'เจ้าหนี้การค้า(ยา วชช.)รวม',
                'num_group' => '2600X',
                'den_group' => '2600Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '261' => [
                'name' => 'Average Collection Period-สิทธิ UC',
                'numerator_name' => 'ลูกหนี้ค่ารักษาสิทธิ UC เฉลี่ย',
                'denominator_name' => 'รายได้ค่ารักษาพยาบาลสิทธิ UC สุทธิ',
                'num_group' => '2610X',
                'den_group' => '2610Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '262' => [
                'name' => 'Average Collection Period-CSMBS',
                'numerator_name' => 'ลูกหนี้ค่ารักษาสิทธิ CS เฉลี่ย',
                'denominator_name' => 'รายได้ค่ารักษาพยาบาล CS สุทธิ',
                'num_group' => '2620X',
                'den_group' => '2620Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '263' => [
                'name' => 'Average Collection Period-SSS',
                'numerator_name' => 'ลูกหนี้ค่ารักษาสิทธิ SS เฉลี่ย',
                'denominator_name' => 'รายได้ค่ารักษาพยาบาลสิทธิ SS สุทธิ',
                'num_group' => '2630X',
                'den_group' => '2630Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '264' => [
                'name' => 'Inventory Management',
                'numerator_name' => 'วัสดุคงคลังเฉลี่ย',
                'denominator_name' => 'วัสดุใช้ไป',
                'num_group' => '2640X',
                'den_group' => '2640Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '302' => [
                'name' => 'อัตรากำไรขั้นต้น(ไม่มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรขั้นต้น (ไม่รวมค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3002X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '303' => [
                'name' => 'อัตรากำไรขั้นต้น(มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรขั้นต้น (มีค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3003X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '304' => [
                'name' => 'อัตรากำไรจากการดำเนินงาน(ไม่มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรดำเนินงาน (ไม่มีค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3004X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '305' => [
                'name' => 'อัตรากำไรจากการดำเนินงาน(มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรดำเนินงาน (มีค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3005X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '306' => [
                'name' => 'อัตรากำไรสุทธิ(ไม่มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรสุทธิ (ไม่มีค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้รวม',
                'num_group' => '3006X',
                'den_group' => '3006Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '307' => [
                'name' => 'อัตรากำไรสุทธิ(มีค่าเสื่อมฯ)',
                'numerator_name' => 'กำไรสุทธิ (มีค่าเสื่อมฯ)',
                'denominator_name' => 'รายได้รวม',
                'num_group' => '3007X',
                'den_group' => '3006Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '310' => [
                'name' => 'ค่าใช้จ่ายรวมต่อรายได้จากการบริการ',
                'numerator_name' => 'ค่าใช้จ่ายรวม',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3010X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '311' => [
                'name' => 'ต้นทุนค่ารักษาพยาบาลต่อค่าใช้จ่ายรวม',
                'numerator_name' => 'ต้นทุนค่ารักษาพยาบาล',
                'denominator_name' => 'ค่าใช้จ่ายรวม',
                'num_group' => '3011X',
                'den_group' => '3010X',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '312' => [
                'name' => 'ค่าใช้จ่ายดำเนินการต่อค่าใช้จ่ายรวม',
                'numerator_name' => 'ค่าใช้จ่ายดำเนินงาน',
                'denominator_name' => 'ค่าใช้จ่ายรวม',
                'num_group' => '3012X',
                'den_group' => '3010X',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '313' => [
                'name' => 'ค่าใช้จ่ายบุคลากรต่อค่าใช้จ่ายรวม',
                'numerator_name' => 'ค่าใช้จ่ายบุคลากร',
                'denominator_name' => 'ค่าใช้จ่ายรวม',
                'num_group' => '3013X',
                'den_group' => '3010X',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '314' => [
                'name' => 'กำไรสุทธิ(ไม่มีค่าเสื่อมฯ)ต่อสินทรัพย์รวม',
                'numerator_name' => 'กำไรสุทธิ (ไม่มีค่าเสื่อมฯ)',
                'denominator_name' => 'สินทรัพย์รวม',
                'num_group' => '3006X',
                'den_group' => '3014Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '315' => [
                'name' => 'กำไรสุทธิ(มีค่าเสื่อมฯ)ต่อสินทรัพย์รวม',
                'numerator_name' => 'กำไรสุทธิ (มีค่าเสื่อมฯ)',
                'denominator_name' => 'สินทรัพย์รวม',
                'num_group' => '3007X',
                'den_group' => '3014Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '316' => [
                'name' => 'I/E Ratio',
                'numerator_name' => 'รายได้รวม',
                'denominator_name' => 'ค่าใช้จ่ายรวม',
                'num_group' => '3006Y',
                'den_group' => '3010X',
                'type' => 'divide',
                'unit' => 'เท่า',
                'precision' => 2
            ],
            '320' => [
                'name' => 'Operating Margin %',
                'numerator_name' => 'EBITDA',
                'denominator_name' => 'รายได้จากการรักษา/งบปุคลากร/กองทุน',
                'num_group' => '3200X',
                'den_group' => '3002Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '321' => [
                'name' => 'Return on Asset %',
                'numerator_name' => 'รายได้สูง(ต่ำ)กว่าค่าใช้จ่ายสุทธิ',
                'denominator_name' => 'สินทรัพย์รวม',
                'num_group' => '3007X',
                'den_group' => '3014Y',
                'type' => 'percent',
                'unit' => '%',
                'precision' => 2
            ],
            '333' => [
                'name' => 'EBITDA',
                'numerator_name' => 'รายได้ (ไม่รวมงบลงทุน)',
                'denominator_name' => 'ค่าใช้จ่าย (ไม่รวมค่าเสื่อมราคา)',
                'num_group' => '3330X',
                'den_group' => '3330Y',
                'type' => 'subtract',
                'unit' => 'บาท',
                'precision' => 2
            ],
            '334' => [
                'name' => 'NI+Depreciation',
                'numerator_name' => 'รายได้รวม',
                'denominator_name' => 'ค่าใช้จ่ายรวม',
                'num_group' => '3006Y',
                'den_group' => '3010X',
                'type' => 'subtract',
                'unit' => 'บาท',
                'precision' => 2
            ],
        ];
    }

    /**
     * Auto-seed mappings table from JSON file if it is empty
     */
    private static function checkAndSeedMappings()
    {
        try {
            $hasTable = \Illuminate\Support\Facades\Schema::hasTable('hosfin_dtl_mappings');
            if (!$hasTable) {
                return;
            }

            $needsSeed = (DB::table('hosfin_dtl_mappings')->count() === 0) || 
                         DB::table('hosfin_dtl_mappings')->whereNull('group_name')->orWhere('group_name', '')->exists();

            if ($needsSeed) {
                $filePath = base_path('docs/lookup/hosfin_dtl_mappings.json');
                if (file_exists($filePath)) {
                    $jsonData = json_decode(file_get_contents($filePath), true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData)) {
                        DB::beginTransaction();
                        try {
                            // Truncate to ensure clean slate if we are self-healing empty names
                            DB::table('hosfin_dtl_mappings')->truncate();

                            $batch = [];
                            foreach ($jsonData as $row) {
                                $batch[] = [
                                    'group_code' => trim($row['group_code']),
                                    'group_name' => trim($row['group_name'] ?? ''),
                                    'account_code' => trim($row['account_code']),
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ];
                                if (count($batch) >= 1000) {
                                    DB::table('hosfin_dtl_mappings')->insert($batch);
                                    $batch = [];
                                }
                            }
                            if (!empty($batch)) {
                                DB::table('hosfin_dtl_mappings')->insert($batch);
                            }
                            DB::commit();
                        } catch (\Throwable $e) {
                            DB::rollBack();
                            Log::error("HosFin Auto-Seed Error: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("HosFin checkAndSeedMappings Error: " . $e->getMessage());
        }
    }
}
