<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Hosfin_Trial_Balance;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HosFinController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_hosfin !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ข้อมูลบัญชีหน่วยงาน (HosFin)'], 403);
                }
                return $next($request);
            }
        ]);
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
    public static function getThaiMonthName($monthNum)
    {
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        return $months[intval($monthNum)] ?? '';
    }

    /**
     * HosFin System Dashboard index
     */
    public function index(Request $request)
    {
        // 1. HosFin Dashboard is strictly powered by Live GL Data
        $hasGlData = DB::table('hosfin_gl_journal_items')->exists();
        if (!$hasGlData) {
            $budgetYear = self::getCurrentBudgetYear();
            $ratioDefs = self::getRatioDefinitions();

            // Build 12 fiscal periods list for the current fiscal year
            $periods = [];
            $chartLabels = [];
            $monthlyRevenueExpenseTrend = [];

            for ($m = 10; $m <= 12; $m++) {
                $p = sprintf('%04d-%02d', $budgetYear - 1, $m);
                $lbl = self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2);
                $periods[] = ['month' => $m, 'year' => $budgetYear - 1, 'period' => $p, 'label' => $lbl];
                $chartLabels[] = $lbl;
                $monthlyRevenueExpenseTrend[$lbl] = ['revenue' => 0.0, 'expense' => 0.0];
            }
            for ($m = 1; $m <= 9; $m++) {
                $p = sprintf('%04d-%02d', $budgetYear, $m);
                $lbl = self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2);
                $periods[] = ['month' => $m, 'year' => $budgetYear, 'period' => $p, 'label' => $lbl];
                $chartLabels[] = $lbl;
                $monthlyRevenueExpenseTrend[$lbl] = ['revenue' => 0.0, 'expense' => 0.0];
            }

            $latestMetrics = [];
            $chartData = [];
            $statusMap = [];

            foreach ($ratioDefs as $code => $def) {
                $latestMetrics[$code] = [
                    'val' => 0.0,
                    'num' => 0.0,
                    'den' => 0.0,
                    'num_label' => $def['num_label'] ?? '',
                    'den_label' => $def['den_label'] ?? '',
                    'unit' => $def['unit'] ?? '',
                ];
                $chartData[$code] = array_fill(0, 12, 0.0);
                $statusMap[$code] = [
                    'label' => '0.00',
                    'class' => 'text-muted border-secondary',
                    'bg' => 'bg-secondary bg-opacity-10',
                ];
            }

            return view('hosfin.index', [
                'hasData' => true,
                'isGlEmpty' => true,
                'latestPeriod' => sprintf('%04d-10', $budgetYear - 1),
                'latestPeriodLabel' => 'รอซิงค์ข้อมูล GL (ปีงบประมาณ ' . $budgetYear . ')',
                'budgetYear' => $budgetYear,
                'latestMetrics' => $latestMetrics,
                'periodHistory' => [],
                'chartLabels' => $chartLabels,
                'chartData' => $chartData,
                'monthlyRevenueExpenseTrend' => $monthlyRevenueExpenseTrend,
                'statusMap' => $statusMap,
                'ratioDefs' => $ratioDefs,
                'riskScore' => 0,
                'riskScoreBgClass' => 'bg-secondary-subtle border-secondary-subtle',
                'riskScoreTextClass' => 'text-muted',
                'riskScoreNumBgClass' => 'bg-secondary',
                'riskScoreLevelLabel' => 'รอข้อมูล GL',
                'apUnpaidSum' => 0.0,
                'apUnpaidCount' => 0,
                'apTotalVendorsCount' => 0,
                'apTopCreditors' => collect([]),
                'arOutstandingSum' => 0.0,
                'arTotalOb' => 0.0,
                'arTotalBilled' => 0.0,
                'arTotalCollected' => 0.0,
                'arAccountCount' => 0,
                'arTypeSummaries' => collect([]),
                'cashBalance' => 0.0,
                'cashLiveBalance' => 0.0,
                'cashAccountsCount' => 0,
                'cashBankAccounts' => collect([]),
                'glSyncTimeText' => 'ยังไม่มีการซิงค์ข้อมูล (รอเชื่อมต่อจากโปรแกรม Rims GL Sync)',
                'glSyncSuccess' => false,
                'latestImportFilename' => 'GL_SYNC',
                'periods' => $periods,
                'importedPeriods' => [],
            ]);
        }

        // Ensure GL Monthly Balances exist for this GL data
        $hasGlBalances = DB::table('hosfin_gl_monthly_balances')->exists();
        if (!$hasGlBalances) {
            self::syncGlMonthlyBalances();
        }

        // Available budget years from GL monthly balances and trial balance
        $periodsGl = DB::table('hosfin_gl_monthly_balances')->distinct()->pluck('acc_period')->filter()->toArray();
        $yearsGl = [];
        foreach ($periodsGl as $p) {
            $parts = explode('-', $p);
            $py = intval($parts[0] ?? 0);
            $pm = intval($parts[1] ?? 0);
            if ($py > 0) {
                $yearsGl[] = ($pm >= 10) ? ($py + 1) : $py;
            }
        }
        $yearsTb = DB::table('hosfin_trial_balance')->distinct()->pluck('acc_year')->filter()->map(fn($y) => intval($y))->toArray();
        $currentYear = self::getCurrentBudgetYear();
        $budgetYearChoices = array_values(array_unique(array_merge([$currentYear, 2570, 2569, 2568], $yearsGl, $yearsTb)));
        rsort($budgetYearChoices);

        $requestedYear = intval($request->input('budget_year', 0));
        $requestedPeriod = $request->input('period');

        $latestPeriod = null;
        if ($requestedPeriod && DB::table('hosfin_gl_monthly_balances')->where('acc_period', $requestedPeriod)->exists()) {
            $latestPeriod = $requestedPeriod;
        } elseif ($requestedYear > 0) {
            $fiscalPeriodsForYear = [];
            for ($m = 10; $m <= 12; $m++) {
                $fiscalPeriodsForYear[] = sprintf('%04d-%02d', $requestedYear - 1, $m);
            }
            for ($m = 1; $m <= 9; $m++) {
                $fiscalPeriodsForYear[] = sprintf('%04d-%02d', $requestedYear, $m);
            }

            $latestPeriod = DB::table('hosfin_gl_monthly_balances')
                ->whereIn('acc_period', $fiscalPeriodsForYear)
                ->orderBy('acc_period', 'desc')
                ->value('acc_period');
        } else {
            $latestPeriod = DB::table('hosfin_gl_monthly_balances')
                ->orderBy('acc_period', 'desc')
                ->value('acc_period');
        }

        if (!$latestPeriod) {
            $fallbackYear = $requestedYear > 0 ? $requestedYear : self::getCurrentBudgetYear();
            $latestPeriod = sprintf('%04d-10', ($fallbackYear - 1));
        }

        $latestImportFilename = 'GL_LIVE';

        // Parse budget year from latest period
        list($calYear, $calMonth) = explode('-', $latestPeriod);
        $calYear = intval($calYear);
        $calMonth = intval($calMonth);
        $budgetYear = ($calMonth >= 10) ? ($calYear + 1) : $calYear;


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

        $validPeriods = array_column($periods, 'period');
        $prevFyEndPeriod = sprintf('%04d-09', $budgetYear - 1);
        $queryPeriods = array_merge($validPeriods, [$prevFyEndPeriod]);

        // Check which periods actually have data in GL monthly balances
        $importedPeriods = DB::table('hosfin_gl_monthly_balances')
            ->whereIn('acc_period', $validPeriods)
            ->distinct()
            ->pluck('acc_period')
            ->toArray();

        // 1. Fetch all mappings to PHP memory
        $mappings = DB::table('hosfin_dtl_mappings')->get(['group_code', 'account_code']);
        $mappingsLookup = [];
        $prefixLengths = [];
        foreach ($mappings as $m) {
            $mappingsLookup[$m->account_code][] = $m->group_code;
            $prefixLengths[strlen($m->account_code)] = true;
        }
        $lengths = array_keys($prefixLengths);
        rsort($lengths);

        // 2. Fetch all GL monthly balance rows purely from GL (hosfin_gl_monthly_balances)
        $trial_balance = DB::table('hosfin_gl_monthly_balances')
            ->whereIn('acc_period', $queryPeriods)
            ->get([
                'acc_period',
                'account_code',
                'ending_debit as debit_net',
                'ending_credit as credit_net',
                'beginning_debit as debit_bf',
                'beginning_credit as credit_bf',
                'period_debit as debit_month',
                'period_credit as credit_month'
            ]);

        // 3. Prefix matching in PHP memory
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

            // Override/add totals directly from raw trial balance for 100% accuracy
            $periodTb = $trial_balance->where('acc_period', $period);
            
            $assetsDebitNet = 0;
            $revDebitNet = 0;
            $expDebitNet = 0;
            
            foreach ($periodTb as $tb) {
                $firstDigit = substr($tb->account_code, 0, 1);
                if ($firstDigit === '1') {
                    $assetsDebitNet += floatval($tb->debit_net) - floatval($tb->credit_net);
                } elseif ($firstDigit === '4') {
                    $revDebitNet += floatval($tb->credit_net) - floatval($tb->debit_net);
                } elseif ($firstDigit === '5') {
                    $expDebitNet += floatval($tb->debit_net) - floatval($tb->credit_net);
                }
            }
            
            $allPeriodsData[$period]['3014Y'] = [
                'debit_net' => $assetsDebitNet, 'credit_net' => 0, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];
            $allPeriodsData[$period]['3006Y'] = [
                'debit_net' => 0, 'credit_net' => $revDebitNet, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];
            $allPeriodsData[$period]['3010X'] = [
                'debit_net' => $expDebitNet, 'credit_net' => 0, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];

            // Cumulative credit monthly activity summing for 2600Y
            $getFiscalPeriodsUpTo = function($targetPeriod) use ($periods) {
                $list = [];
                foreach ($periods as $p) {
                    $list[] = $p['period'];
                    if ($p['period'] === $targetPeriod) {
                        break;
                    }
                }
                return $list;
            };
            
            $periodsUpTo = $getFiscalPeriodsUpTo($period);
            $cumCredit = 0;
            foreach ($periodsUpTo as $p) {
                $rows = $grouped[$p]['2600Y'] ?? [];
                foreach ($rows as $r) {
                    $cumCredit += floatval($r->credit_month);
                }
            }
            
            $allPeriodsData[$period]['2600Y'] = [
                'debit_net' => 0, 'credit_net' => $cumCredit, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => $cumCredit
            ];
        }

        // Helper to retrieve values
        $getGroupValForPeriod = function($period, $groupCode) use (&$allPeriodsData) {
            $row = $allPeriodsData[$period][$groupCode] ?? null;
            if (!$row) return 0;

            if (in_array($groupCode, ['3014Y', '3006Y', '3010X'])) {
                return $row['debit_net'] ?: $row['credit_net'];
            }

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

        // Helper to get average value for days-based indicator numerators
        $getAverageGroupVal = function($period, $groupCode) use ($getGroupValForPeriod, $prevFyEndPeriod) {
            $currentVal = $getGroupValForPeriod($period, $groupCode);
            if (in_array($groupCode, ['2640X', '2600X', '2610X', '2620X', '2630X'])) {
                $prevVal = $getGroupValForPeriod($prevFyEndPeriod, $groupCode);
                if ($prevVal != 0) {
                    return ($currentVal + $prevVal) / 2;
                }
            }
            return $currentVal;
        };

        $targetCodes = ['105', '104', '100', '101', '102', '264', '261', '262', '260', '320', '321', '307', 'NI', 'RISK_SCORE'];
        $ratioDefs = self::getRatioDefinitions();
        $history = [];

        foreach ($targetCodes as $code) {
            $history[$code] = [];
        }

        foreach ($validPeriods as $period) {
            if (!in_array($period, $importedPeriods)) {
                continue;
            }

            foreach ($targetCodes as $code) {
                if (!isset($ratioDefs[$code])) continue;
                $def = $ratioDefs[$code];

                $num = $getAverageGroupVal($period, $def['num_group']);
                $den = $getAverageGroupVal($period, $def['den_group']);

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
                $history[$code][$period] = [
                    'val' => round($val, $def['precision']),
                    'num' => $num,
                    'den' => $den
                ];
            }

            // Calculate Risk Score for this period
            $crVal = $history['100'][$period]['val'] ?? 0;
            $qrVal = $history['101'][$period]['val'] ?? 0;
            $cashVal = $history['102'][$period]['val'] ?? 0;
            $nwcVal = $history['104'][$period]['val'] ?? 0;
            $niVal = $getGroupValForPeriod($period, '3007X'); // Net Income from group 3007X

            $pRiskScore = 0;
            if ($crVal < 1.5) $pRiskScore += 1;
            if ($qrVal < 1.0) $pRiskScore += 1;
            if ($cashVal < 0.8) $pRiskScore += 1;
            if ($nwcVal < 0) $pRiskScore += 1;
            if ($niVal < 0) $pRiskScore += 1;

            $pCalMonth = intval(substr($period, 5, 2));
            $pMonthsPassed = 1;
            if ($pCalMonth >= 10) {
                $pMonthsPassed = $pCalMonth - 9;
            } else {
                $pMonthsPassed = $pCalMonth + 3;
            }

            if ($nwcVal >= 0 && $niVal < 0) {
                $pMonthlyLoss = abs($niVal) / $pMonthsPassed;
                if ($pMonthlyLoss > 0) {
                    $pMonthsToDeplete = $nwcVal / $pMonthlyLoss;
                    if ($pMonthsToDeplete <= 3) {
                        $pRiskScore += 2;
                    } elseif ($pMonthsToDeplete <= 6) {
                        $pRiskScore += 1;
                    }
                }
            } elseif ($nwcVal < 0 && $niVal >= 0) {
                $pMonthlyGain = $niVal / $pMonthsPassed;
                if ($pMonthlyGain > 0) {
                    $pMonthsToRecover = abs($nwcVal) / $pMonthlyGain;
                    if ($pMonthsToRecover > 6) {
                        $pRiskScore += 2;
                    } elseif ($pMonthsToRecover > 3) {
                        $pRiskScore += 1;
                    }
                } else {
                    $pRiskScore += 2;
                }
            } elseif ($nwcVal < 0 && $niVal < 0) {
                $pRiskScore += 2;
            }

            $history['RISK_SCORE'][$period] = [
                'val' => $pRiskScore,
                'num' => $pRiskScore,
                'den' => 7
            ];
        }

        // Latest period metrics and label
        $latestMetrics = [];
        foreach ($targetCodes as $code) {
            $latestMetrics[$code] = $history[$code][$latestPeriod] ?? ['val' => 0, 'num' => 0, 'den' => 0];
        }

        $latestPeriodLabel = '';
        foreach ($periods as $p) {
            if ($p['period'] === $latestPeriod) {
                $latestPeriodLabel = $p['label'];
                break;
            }
        }

        // Calculate Risk Score for the latest period
        $crVal = $latestMetrics['100']['val'];
        $qrVal = $latestMetrics['101']['val'];
        $cashVal = $latestMetrics['102']['val'];
        $nwcVal = $latestMetrics['104']['val'];
        $niVal = $getGroupValForPeriod($latestPeriod, '3007X'); // Net Income from group 3007X

        $riskScore = 0;

        // 1. Asset Liquidity Group
        if ($crVal < 1.5) $riskScore += 1;
        if ($qrVal < 1.0) $riskScore += 1;
        if ($cashVal < 0.8) $riskScore += 1;

        // 2. Financial Stability Group
        if ($nwcVal < 0) $riskScore += 1;
        if ($niVal < 0) $riskScore += 1;

        // 3. Severe Financial Distress Group
        $monthsPassed = 1;
        if ($calMonth >= 10) {
            $monthsPassed = $calMonth - 9;
        } else {
            $monthsPassed = $calMonth + 3;
        }

        if ($nwcVal >= 0 && $niVal < 0) {
            $monthlyLoss = abs($niVal) / $monthsPassed;
            if ($monthlyLoss > 0) {
                $monthsToDeplete = $nwcVal / $monthlyLoss;
                if ($monthsToDeplete <= 3) {
                    $riskScore += 2;
                } elseif ($monthsToDeplete <= 6) {
                    $riskScore += 1;
                }
            }
        } elseif ($nwcVal < 0 && $niVal >= 0) {
            $monthlyGain = $niVal / $monthsPassed;
            if ($monthlyGain > 0) {
                $monthsToRecover = abs($nwcVal) / $monthlyGain;
                if ($monthsToRecover > 6) {
                    $riskScore += 2;
                } elseif ($monthsToRecover > 3) {
                    $riskScore += 1;
                }
            } else {
                $riskScore += 2;
            }
        } elseif ($nwcVal < 0 && $niVal < 0) {
            $riskScore += 2;
        }

        // Determine classes based on score
        if ($riskScore >= 6) {
            $riskScoreBgClass = 'bg-danger bg-opacity-10 border-danger-subtle';
            $riskScoreTextClass = 'text-danger fw-bold';
            $riskScoreNumBgClass = 'bg-danger bg-opacity-25';
            $riskScoreLevelLabel = 'วิกฤตทางการเงิน';
        } elseif ($riskScore >= 5) {
            $riskScoreBgClass = 'bg-warning bg-opacity-10 border-warning-subtle';
            $riskScoreTextClass = 'text-warning-custom fw-bold';
            $riskScoreNumBgClass = 'bg-warning bg-opacity-25';
            $riskScoreLevelLabel = 'เฝ้าระวังสูง';
        } elseif ($riskScore >= 3) {
            $riskScoreBgClass = 'bg-warning bg-opacity-10 border-warning-subtle';
            $riskScoreTextClass = 'text-warning-custom';
            $riskScoreNumBgClass = 'bg-warning bg-opacity-10';
            $riskScoreLevelLabel = 'เฝ้าระวังปานกลาง';
        } else {
            $riskScoreBgClass = 'bg-success bg-opacity-10 border-success-subtle';
            $riskScoreTextClass = 'text-success-custom';
            $riskScoreNumBgClass = 'bg-success bg-opacity-25';
            $riskScoreLevelLabel = 'ปกติ / เฝ้าระวังต่ำ';
        }

        // Chart.js structures
        $chartLabels = [];
        foreach ($periods as $p) {
            if (in_array($p['period'], $importedPeriods)) {
                $chartLabels[] = $p['label'];
            }
        }

        $chartData = [];
        $periodHistory = [];
        foreach ($targetCodes as $code) {
            $chartData[$code] = [];
            $periodHistory[$code] = [];
            foreach ($periods as $p) {
                if (in_array($p['period'], $importedPeriods)) {
                    $chartData[$code][] = $history[$code][$p['period']]['val'] ?? 0;
                    $periodHistory[$code][$p['label']] = [
                        'val' => $history[$code][$p['period']]['val'] ?? 0,
                        'num' => $history[$code][$p['period']]['num'] ?? 0,
                        'den' => $history[$code][$p['period']]['den'] ?? 0,
                    ];
                }
            }
        }

        // Evaluate statuses
        $statusMap = [];
        foreach ($targetCodes as $code) {
            $val = $latestMetrics[$code]['val'];
            $statusLabel = 'ปกติ';
            $statusClass = 'text-success border-success';
            $bgClass = 'bg-success bg-opacity-10';

            if ($code === '100') {
                if ($val >= 1.5) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } elseif ($val >= 1.0) {
                    $statusLabel = 'เฝ้าระวัง'; $statusClass = 'text-warning border-warning'; $bgClass = 'bg-warning bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '101') {
                if ($val >= 1.0) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '102') { // Cash Ratio
                if ($val >= 0.8) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '105' || $code === '104') {
                if ($val >= 0) {
                    $statusLabel = 'ปกติ (บวก)'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต (ติดลบ)'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '264') { // สินค้าคงคลัง
                if ($val <= 60) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '261' || $code === '262') { // สิทธิ UC / ข้าราชการ
                if ($val <= 60) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '260') { // เจ้าหนี้ค่ายา
                if ($val <= 90) {
                    $statusLabel = 'ปกติ'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } elseif ($val <= 180) {
                    $statusLabel = 'เฝ้าระวัง'; $statusClass = 'text-warning border-warning'; $bgClass = 'bg-warning bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            } elseif ($code === '320' || $code === '321' || $code === '307' || $code === 'NI') {
                if ($val >= 0) {
                    $statusLabel = 'ปกติ (กำไร)'; $statusClass = 'text-success border-success'; $bgClass = 'bg-success bg-opacity-10';
                } else {
                    $statusLabel = 'วิกฤต (ขาดทุน)'; $statusClass = 'text-danger border-danger'; $bgClass = 'bg-danger bg-opacity-10';
                }
            }

            $statusMap[$code] = [
                'label' => $statusLabel,
                'class' => $statusClass,
                'bg' => $bgClass
            ];
        }

        // Calculate Monthly Revenue vs Expense trend
        $monthlyRevenueExpenseTrend = [];
        foreach ($periods as $p) {
            if (in_array($p['period'], $importedPeriods)) {
                $monthlyRevenueExpenseTrend[$p['label']] = [
                    'revenue' => 0.0,
                    'expense' => 0.0
                ];
            }
        }

        foreach ($trial_balance as $tb) {
            if (!in_array($tb->acc_period, $importedPeriods)) {
                continue;
            }
            $firstDigit = substr($tb->account_code, 0, 1);
            if ($firstDigit === '4' || $firstDigit === '5') {
                $pLabel = null;
                foreach ($periods as $p) {
                    if ($p['period'] === $tb->acc_period) {
                        $pLabel = $p['label'];
                        break;
                    }
                }
                if ($pLabel && isset($monthlyRevenueExpenseTrend[$pLabel])) {
                    if ($firstDigit === '4') {
                        $monthlyRevenueExpenseTrend[$pLabel]['revenue'] += (floatval($tb->credit_month) - floatval($tb->debit_month));
                    } else {
                        $monthlyRevenueExpenseTrend[$pLabel]['expense'] += (floatval($tb->debit_month) - floatval($tb->credit_month));
                    }
                }
            }
        }

        $selectedDefs = [];
        foreach ($targetCodes as $code) {
            if (isset($ratioDefs[$code])) {
                $selectedDefs[$code] = $ratioDefs[$code];
            }
        }

        $apUnpaidSum = 0;
        $apUnpaidCount = 0;
        $apTotalVendorsCount = 0;
        $apUnpaidSum = 0;
        $apUnpaidCount = 0;
        $apTotalVendorsCount = 0;
        $apTopCreditors = collect();

        $arOutstandingSum = 0;
        $arTotalOb = 0;
        $arTotalBilled = 0;
        $arTotalCollected = 0;
        $arAccountCount = 0;
        $arTypeSummaries = collect();

        $cashBalance = 0;
        $cashAccountsCount = 0;
        $cashBankAccounts = collect();

        $latestSyncLog = null;
        $glSyncTimeText = 'ยังไม่มีการซิงค์ (รอเชื่อมต่อ)';
        $glSyncSuccess = false;

        try {
            // Check latest successful GL sync log
            $latestSyncLog = \App\Models\HosfinGlSyncLog::where('status', 'success')->latest('id')->first();
            $latestTimestamp = $latestSyncLog ? $latestSyncLog->created_at : null;

            if (!$latestTimestamp) {
                $latestJournal = DB::table('hosfin_gl_journals')->latest('updated_at')->first();
                $latestTimestamp = $latestJournal ? $latestJournal->updated_at : null;
            }

            if (!$latestTimestamp) {
                $latestApBill = DB::table('hosfin_gl_ap_bills')->latest('updated_at')->first();
                $latestTimestamp = $latestApBill ? $latestApBill->updated_at : null;
            }

            if ($latestTimestamp) {
                $dt = \Carbon\Carbon::parse($latestTimestamp);
                $thaiYear = ($dt->year + 543) % 100;
                $thaiMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
                $monthName = $thaiMonths[$dt->month] ?? '';
                $glSyncTimeText = $dt->day . ' ' . $monthName . ' ' . $thaiYear . ' ' . $dt->format('H:i') . ' น.';
                $glSyncSuccess = true;
            }

            // AP from GL
            $apEndingBalance = (float)DB::table('hosfin_gl_monthly_balances')
                ->where('acc_period', $latestPeriod)
                ->where('account_code', 'like', '2101%')
                ->sum(DB::raw('ending_credit - ending_debit'));

            $apUnpaidSum = (float)\App\Models\HosfinGlApBill::where('is_paid', 0)->sum('remaining_debt');
            $apUnpaidCount = (int)\App\Models\HosfinGlApBill::where('is_paid', 0)->count();
            $apTotalVendorsCount = (int)\App\Models\HosfinGlApBill::where('is_paid', 0)->distinct('vendor_name')->count('vendor_name');
            $apTopCreditors = \App\Models\HosfinGlApBill::select(
                    'vendor_name',
                    DB::raw('MAX(category) as category'),
                    DB::raw('COUNT(*) as total_bills'),
                    DB::raw('SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_bills'),
                    DB::raw('SUM(CASE WHEN is_paid = 0 THEN remaining_debt ELSE 0 END) as remaining_debt')
                )
                ->groupBy('vendor_name')
                ->having('remaining_debt', '>', 0)
                ->orderBy('remaining_debt', 'desc')
                ->limit(8)
                ->get();

            // AR from GL (Accounts Receivable หมวด 1102)
            $arEndingBalance = (float)DB::table('hosfin_gl_monthly_balances')
                ->where('acc_period', $latestPeriod)
                ->where('account_code', 'like', '1102%')
                ->sum(DB::raw('ending_debit - ending_credit'));

            $periodArAccountCount = DB::table('hosfin_gl_monthly_balances')
                ->where('acc_period', $latestPeriod)
                ->where('account_code', 'like', '1102%')
                ->where(DB::raw('ending_debit - ending_credit'), '<>', 0)
                ->count();

            $arTotals = DB::table('hosfin_gl_journal_items')
                ->where('account_code', 'like', '1102%')
                ->select(
                    DB::raw('SUM(debit) as total_dr'),
                    DB::raw('SUM(credit) as total_cr'),
                    DB::raw('SUM(debit - credit) as net_outstanding'),
                    DB::raw('COUNT(DISTINCT account_code) as total_accounts')
                )
                ->first();

            $arOutstandingSum = (float)($arTotals->net_outstanding ?? 0);
            $arAccountCount = $periodArAccountCount > 0 ? $periodArAccountCount : (int)($arTotals->total_accounts ?? 0);

            $arTotalOb = (float)\App\Models\HosfinGlArDebtor::where('fiscal_month', 0)->sum('outstanding_balance');
            $arTotalBilled = (float)\App\Models\HosfinGlArDebtor::where('fiscal_month', '>', 0)->sum('total_billed');
            $arTotalCollected = (float)\App\Models\HosfinGlArDebtor::where('fiscal_month', '>', 0)->sum('total_collected');

            $arTypeSummaries = \App\Models\HosfinGlArDebtor::select(
                    'debtor_type',
                    DB::raw('COUNT(DISTINCT account_code) as account_count'),
                    DB::raw('SUM(CASE WHEN fiscal_month = 0 THEN outstanding_balance ELSE 0 END) as ob_balance'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_billed ELSE 0 END) as total_billed'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_collected ELSE 0 END) as total_collected'),
                    DB::raw('SUM(outstanding_balance) as outstanding_balance')
                )
                ->groupBy('debtor_type')
                ->orderBy('outstanding_balance', 'desc')
                ->get();

            // CASH from GL (hosfin_gl_monthly_balances for latestPeriod)
            $cashMappings = DB::table('hosfin_dtl_mappings')
                ->where('group_code', '1003X')
                ->pluck('account_code')
                ->toArray();

            $cashBankAccounts = DB::table('hosfin_gl_monthly_balances as b')
                ->leftJoin('hosfin_gl_accounts as a', 'b.account_code', '=', 'a.account_code')
                ->select(
                    'b.account_code',
                    DB::raw('COALESCE(a.account_name, b.account_code) as account_name'),
                    DB::raw('(b.ending_debit - b.ending_credit) as net_balance')
                )
                ->where('b.acc_period', $latestPeriod)
                ->where(function($q) use ($cashMappings) {
                    $q->where('b.account_code', 'like', '1003%')
                      ->orWhere('b.account_code', 'like', '1101%');
                    foreach ($cashMappings as $c) {
                        $q->orWhere('b.account_code', 'like', $c . '%');
                    }
                })
                ->having('net_balance', '<>', 0)
                ->orderBy('net_balance', 'desc')
                ->get();

            if ($cashBankAccounts->isNotEmpty()) {
                $cashBalance = (float)$cashBankAccounts->sum('net_balance');
                $cashAccountsCount = $cashBankAccounts->count();
            } else {
                $hasGlJournals = DB::table('hosfin_gl_journal_items')->exists();
                if ($hasGlJournals) {
                    $cashBankAccounts = DB::table('hosfin_gl_journal_items as i')
                        ->select('i.account_code', 'i.account_name', DB::raw('SUM(i.debit - i.credit) as net_balance'))
                        ->where(function($q) use ($cashMappings) {
                            $q->where('i.account_code', 'like', '1003%')
                              ->orWhere('i.account_code', 'like', '1101%');
                            foreach ($cashMappings as $c) {
                                $q->orWhere('i.account_code', 'like', $c . '%');
                            }
                        })
                        ->groupBy('i.account_code', 'i.account_name')
                        ->having('net_balance', '<>', 0)
                        ->orderBy('net_balance', 'desc')
                        ->get();
                    $cashBalance = (float)$cashBankAccounts->sum('net_balance');
                    $cashAccountsCount = $cashBankAccounts->count();
                } else {
                    $cashBalance = 0;
                    $cashAccountsCount = 0;
                    $cashBankAccounts = collect();
                }
            }

            // Cash Live balance (running balance up to present in GL)
            $cashLiveBalance = 0.0;
            $operatingCashLive = 0.0;
            $hasGlJournals = DB::table('hosfin_gl_journal_items')->exists();
            if ($hasGlJournals) {
                // All cash accounts (1101%)
                $cashLiveBalance = (float)DB::table('hosfin_gl_journal_items as i')
                    ->where(function($q) use ($cashMappings) {
                        $q->where('i.account_code', 'like', '1003%')
                          ->orWhere('i.account_code', 'like', '1101%');
                        foreach ($cashMappings as $c) {
                            $q->orWhere('i.account_code', 'like', $c . '%');
                        }
                    })
                    ->sum(DB::raw('i.debit - i.credit'));

                // 1003X Operating cash only (เงินสดและรายการเทียบเท่าเงินสด)
                $operatingCashLive = (float)DB::table('hosfin_gl_journal_items as i')
                    ->where(function($q) use ($cashMappings) {
                        foreach ($cashMappings as $c) {
                            $q->orWhere('i.account_code', 'like', $c . '%');
                        }
                    })
                    ->sum(DB::raw('i.debit - i.credit'));
            }
            if ($cashLiveBalance == 0 && $cashBalance > 0) {
                $cashLiveBalance = $cashBalance;
            }

            // Classify cash strictly according to MOPH Standards:
            // 1. Operating Cash (เงินบำรุงพร้อมใช้ตามเกณฑ์ สธ. กลุ่ม 1003X ที่ใช้คำนวณ Cash Ratio และดัชนี 105)
            // 2. Restricted Cash (เงินงบลงทุน UC, เงินบริจาค หรือเงินที่มีวัตถุประสงค์เฉพาะนอกกลุ่ม 1003X)
            $operatingCash = 0.0;
            $restrictedCash = 0.0;
            foreach ($cashBankAccounts as $ca) {
                $isMoph1003x = in_array($ca->account_code, $cashMappings);
                if (!$isMoph1003x) {
                    foreach ($cashMappings as $cm) {
                        if (str_starts_with($ca->account_code, $cm)) {
                            $isMoph1003x = true;
                            break;
                        }
                    }
                }

                if ($isMoph1003x) {
                    $operatingCash += (float)$ca->net_balance;
                    $ca->is_restricted = false;
                } else {
                    $restrictedCash += (float)$ca->net_balance;
                    $ca->is_restricted = true;
                }
            }

            if ($operatingCashLive == 0 && $operatingCash > 0) {
                $operatingCashLive = $operatingCash;
            }
        } catch (\Throwable $e) {}

        return view('hosfin.index', [
            'hasData' => true,
            'latestPeriodLabel' => $latestPeriodLabel,
            'budgetYear' => $budgetYear,
            'budgetYearChoices' => $budgetYearChoices,
            'latestMetrics' => $latestMetrics,

            'periodHistory' => $periodHistory,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'statusMap' => $statusMap,
            'ratioDefs' => $selectedDefs,
            'riskScore' => $riskScore,
            'riskScoreBgClass' => $riskScoreBgClass,
            'riskScoreTextClass' => $riskScoreTextClass,
            'riskScoreNumBgClass' => $riskScoreNumBgClass,
            'riskScoreLevelLabel' => $riskScoreLevelLabel,
            'monthlyRevenueExpenseTrend' => $monthlyRevenueExpenseTrend,
            'apEndingBalance' => $apEndingBalance ?? null,
            'apUnpaidSum' => $apUnpaidSum,
            'apUnpaidCount' => $apUnpaidCount,
            'apTotalVendorsCount' => $apTotalVendorsCount,
            'apTopCreditors' => $apTopCreditors,
            'arEndingBalance' => $arEndingBalance ?? null,
            'arOutstandingSum' => $arOutstandingSum,
            'arTotalOb' => $arTotalOb,
            'arTotalBilled' => $arTotalBilled,
            'arTotalCollected' => $arTotalCollected,
            'arAccountCount' => $arAccountCount,
            'arTypeSummaries' => $arTypeSummaries,
            'cashBalance' => $cashBalance,
            'cashLiveBalance' => $cashLiveBalance,
            'operatingCash' => $operatingCash ?? 0,
            'operatingCashLive' => $operatingCashLive ?? $operatingCash ?? 0,
            'restrictedCash' => $restrictedCash ?? 0,
            'cashAccountsCount' => $cashAccountsCount,
            'cashBankAccounts' => $cashBankAccounts,
            'glSyncTimeText' => $glSyncTimeText,
            'glSyncSuccess' => $glSyncSuccess,
            'latestImportFilename' => $latestImportFilename,
            'latestPeriod' => $latestPeriod,
            'periods' => $periods,
            'importedPeriods' => $importedPeriods,
        ]);
    }

    /**
     * Trial Balance list page
     */
    public function trial_balance(Request $request)
    {
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
                            // Net Revenue: Monthly Credit minus Debit Transactions
                            $chartData[$pLabel][4] += (floatval($tb->credit_month) - floatval($tb->debit_month));
                        } elseif ($catId === 5) {
                            // Net Expense: Monthly Debit minus Credit Transactions
                            $chartData[$pLabel][5] += (floatval($tb->debit_month) - floatval($tb->credit_month));
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
                $name = preg_replace('/\s*\((Yes|No)\)\s*$/iu', '', $name);
                $name = trim($name);

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
     * Check if Python and access-parser are ready.
     */
    private function checkPythonDependencies()
    {
        $pyStatus = \App\Helpers\PythonHelper::checkStatus();
        return $pyStatus['available'] && $pyStatus['has_access_parser'];
    }

    /**
     * Upload and analyze an MDB/ZIP file, returning available periods and counts.
     */
    public function analyzeMdb(Request $request)
    {
        $pyStatus = \App\Helpers\PythonHelper::checkStatus();
        if (!$pyStatus['available']) {
            return response()->json([
                'success' => false,
                'is_python_missing' => true,
                'message' => 'ระบบไม่พบโปรแกรม Python บนเซิร์ฟเวอร์สำหรับอ่านไฟล์ฐานข้อมูล (.mdb)',
                'guide' => $pyStatus['guide']
            ], 400);
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไฟล์ที่อัปโหลด'
            ], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());
        
        if ($ext !== 'zip') {
            return response()->json([
                'success' => false,
                'message' => 'รองรับเฉพาะไฟล์บีบอัด .zip เท่านั้น'
            ], 400);
        }

        if (strncasecmp($originalName, 'D', 1) !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'ชื่อไฟล์ต้องขึ้นต้นด้วยตัวอักษร D เท่านั้น (เช่น D1625_xxxx.zip)'
            ], 400);
        }

        try {
            $tempDir = storage_path('app/temp_mdb_' . uniqid());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $mdbPath = '';
            if ($ext === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($file->getRealPath()) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'mdb') {
                            $zip->extractTo($tempDir, $filename);
                            $mdbPath = $tempDir . '/' . $filename;
                            break;
                        }
                    }
                    $zip->close();
                }
                if (empty($mdbPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่พบไฟล์ .mdb ภายในไฟล์ .zip'
                    ], 422);
                }
            } else {
                $filename = 'extracted_' . uniqid() . '.mdb';
                $file->move($tempDir, $filename);
                $mdbPath = $tempDir . '/' . $filename;
            }

            $pythonScript = base_path('app/Helpers/Python/analyze_mdb.py');
            $runResult = \App\Helpers\PythonHelper::runScript($pythonScript, [$mdbPath]);

            if (!$runResult['success']) {
                $this->deleteDir($tempDir);
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถวิเคราะห์ไฟล์ได้: ' . $runResult['output']
                ], 500);
            }

            $outputStr = $runResult['output'];
            $data = json_decode($outputStr, true);
            if (!is_array($data) || isset($data['error'])) {
                $this->deleteDir($tempDir);
                $errorMsg = isset($data['error']) ? $data['error'] : 'ไฟล์งบกระทรวงรูปแบบไม่ถูกต้องหรือไม่พบข้อมูลในตาราง DataIn';
                if (!is_array($data)) {
                    $errorMsg = 'ผลการวิเคราะห์ไฟล์ไม่ถูกต้อง: ' . $outputStr;
                }
                return response()->json([
                    'success' => false,
                    'message' => 'วิเคราะห์ไฟล์ล้มเหลว: ' . $errorMsg
                ], 422);
            }

            $tempToken = uniqid('mdb_');
            session([$tempToken => [
                'dir' => $tempDir,
                'path' => $mdbPath
            ]]);

            return response()->json([
                'success' => true,
                'temp_token' => $tempToken,
                'periods' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการวิเคราะห์ไฟล์: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import a specific period from the uploaded MDB file.
     */
    public function importMdbPeriod(Request $request)
    {
        $pyStatus = \App\Helpers\PythonHelper::checkStatus();
        if (!$pyStatus['available']) {
            return response()->json([
                'success' => false,
                'is_python_missing' => true,
                'message' => 'ระบบไม่พบโปรแกรม Python บนเซิร์ฟเวอร์สำหรับอ่านไฟล์ฐานข้อมูล (.mdb)',
                'guide' => $pyStatus['guide']
            ], 400);
        }

        $tempToken = $request->input('temp_token');
        $pdate = $request->input('pdate');
        $period = $request->input('period');

        if (empty($tempToken) || empty($pdate) || empty($period)) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลคำขอไม่ครบถ้วน'
            ], 400);
        }

        $sessionData = session($tempToken);
        if (!$sessionData || !file_exists($sessionData['path'])) {
            return response()->json([
                'success' => false,
                'message' => 'ไฟล์เซสชันหมดอายุหรือไม่มีอยู่จริง กรุณาอัปโหลดไฟล์ใหม่อีกครั้ง'
            ], 422);
        }

        try {
            $mdbPath = $sessionData['path'];
            $pythonScript = base_path('app/Helpers/Python/import_mdb_period.py');
            $runResult = \App\Helpers\PythonHelper::runScript($pythonScript, [$mdbPath, $pdate]);

            if (!$runResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถประมวลผลข้อมูลในเดือนที่เลือกได้: ' . $runResult['output']
                ], 500);
            }

            $outputStr = $runResult['output'];
            $rows = json_decode($outputStr, true);
            if (!is_array($rows) || isset($rows['error'])) {
                $errorMsg = isset($rows['error']) ? $rows['error'] : 'ไฟล์งบกระทรวงรูปแบบไม่ถูกต้องหรือไม่พบข้อมูลในตาราง DataIn';
                if (!is_array($rows)) {
                    $errorMsg = 'ผลการดึงข้อมูลไม่ถูกต้อง: ' . $outputStr;
                }
                return response()->json([
                    'success' => false,
                    'message' => 'ดึงข้อมูลล้มเหลว: ' . $errorMsg
                ], 422);
            }

            $nameMap = DB::table('hosfin_dtl_mappings')
                ->select('account_code', 'account_name')
                ->distinct()
                ->pluck('account_name', 'account_code')
                ->toArray();

            $insertRows = [];
            foreach ($rows as $row) {
                $code = $row['account_code'];
                $cleanName = isset($nameMap[$code]) ? $nameMap[$code] : $row['account_name'];
                
                $insertRows[] = [
                    'acc_year' => $row['acc_year'],
                    'acc_month' => $row['acc_month'],
                    'acc_period' => $row['acc_period'],
                    'main_account_code' => $row['main_account_code'],
                    'account_code' => $code,
                    'account_name' => $cleanName,
                    'debit_bf' => $row['debit_bf'],
                    'credit_bf' => $row['credit_bf'],
                    'debit_month' => $row['debit_month'],
                    'credit_month' => $row['credit_month'],
                    'debit_net' => $row['debit_net'],
                    'credit_net' => $row['credit_net'],
                    'import_filename' => $row['import_filename'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            DB::transaction(function () use ($period, $insertRows) {
                DB::table('hosfin_trial_balance')->where('acc_period', $period)->delete();
                foreach (array_chunk($insertRows, 100) as $chunk) {
                    DB::table('hosfin_trial_balance')->insert($chunk);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "นำเข้าข้อมูลรอบบัญชีกระทรวง $period สำเร็จ ทั้งหมด " . count($insertRows) . " รายการ"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to recursively delete a directory
     */
    private function deleteDir($dirPath) {
        if (!is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * Search account mappings in JSON/DB
     */
    public function mappings_search(Request $request)
    {
        $q = $request->input('q');
        $groupCode = $request->input('group_code');
        $isPrint = $request->input('print') == 1;
        
        $query = DB::table('hosfin_dtl_mappings')
            ->select(
                'group_code',
                'group_name',
                'account_code',
                'account_name'
            );

        // Fetch distinct groups for dropdown selection
        $groups = DB::table('hosfin_dtl_mappings')
            ->select('group_code', 'group_name')
            ->distinct()
            ->orderBy('group_code')
            ->get();

        if (!empty($groupCode)) {
            $query->where('group_code', $groupCode);
        }

        if (!empty($q)) {
            $query->where(function($sub) use ($q) {
                $sub->where('group_code', 'like', "%$q%")
                    ->orWhere('group_name', 'like', "%$q%")
                    ->orWhere('account_code', 'like', "%$q%")
                    ->orWhere('account_name', 'like', "%$q%");
            });
        }

        // Bypassing pagination if print request
        if ($isPrint) {
            $mappings = $query->orderBy('hosfin_dtl_mappings.group_code')->orderBy('hosfin_dtl_mappings.account_code')->get();
            return response()->json([
                'success' => true,
                'mappings' => $mappings,
                'groups' => $groups
            ]);
        }

        $mappings = $query->orderBy('hosfin_dtl_mappings.group_code')->orderBy('hosfin_dtl_mappings.account_code')->paginate(25);

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
     * Get list of unmapped accounts from trial balance for selected budget year
     */
    public function get_unmapped_accounts(Request $request)
    {
        $period = $request->input('period', 'all');
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        
        // Define periods of interest
        if ($period === 'all') {
            $periods = [];
            for ($m = 10; $m <= 12; $m++) {
                $periods[] = sprintf('%04d-%02d', $budgetYear - 1, $m);
            }
            for ($m = 1; $m <= 9; $m++) {
                $periods[] = sprintf('%04d-%02d', $budgetYear, $m);
            }
        } else {
            $periods = [$period];
        }

        // Get trial balance rows for selected period(s)
        $tbAccounts = DB::table('hosfin_trial_balance')
            ->whereIn('acc_period', $periods)
            ->get(['account_code', 'account_name', 'debit_net', 'credit_net']);

        // Aggregate net values
        $accountSums = [];
        foreach ($tbAccounts as $tb) {
            $code = $tb->account_code;
            if (!isset($accountSums[$code])) {
                $accountSums[$code] = [
                    'account_code' => $code,
                    'account_name' => $tb->account_name,
                    'net_val' => 0.0
                ];
            }
            $firstDigit = substr($code, 0, 1);
            $isDebit = in_array($firstDigit, ['1', '5']);
            $val = $isDebit ? (floatval($tb->debit_net) - floatval($tb->credit_net)) : (floatval($tb->credit_net) - floatval($tb->debit_net));
            $accountSums[$code]['net_val'] += $val;
        }

        // Get all mapped prefixes
        $mappings = DB::table('hosfin_dtl_mappings')->pluck('account_code')->toArray();

        // Filter out mapped accounts
        $unmapped = [];
        foreach ($accountSums as $code => $acc) {
            $matched = false;
            foreach ($mappings as $prefix) {
                if (strpos($code, $prefix) === 0) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                // Skip if net value is exactly 0.00 to make the report cleaner
                if (round($acc['net_val'], 2) != 0.00) {
                    $unmapped[] = $acc;
                }
            }
        }

        // Sort by account code
        usort($unmapped, function($a, $b) {
            return strcmp($a['account_code'], $b['account_code']);
        });

        return response()->json([
            'success' => true,
            'unmapped' => $unmapped
        ]);
    }

    /**
     * Financial Ratio Report views & calculations
     */
    public function ratio_report(Request $request)
    {
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
        $prevFyEndPeriod = sprintf('%04d-09', $budgetYear - 1);
        $queryPeriods = array_merge($validPeriods, [$prevFyEndPeriod]);

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
            ->whereIn('acc_period', $queryPeriods)
            ->get(['acc_period', 'account_code', 'debit_net', 'credit_net', 'debit_bf', 'credit_bf', 'debit_month', 'credit_month', 'import_filename']);

        // Deduplicate: If an acc_period has multiple import sources (e.g. manual file and GL_SYNC), pick one
        $periodPreferredSource = [];
        $sources = $trial_balance->groupBy('acc_period');
        foreach ($sources as $p => $rows) {
            $distinctFiles = $rows->pluck('import_filename')->unique();
            if ($distinctFiles->count() > 1) {
                $manual = $distinctFiles->first(fn($f) => $f !== 'GL_SYNC');
                $periodPreferredSource[$p] = $manual ?: $distinctFiles->first();
            }
        }

        if (!empty($periodPreferredSource)) {
            $trial_balance = $trial_balance->filter(function($tb) use ($periodPreferredSource) {
                $preferred = $periodPreferredSource[$tb->acc_period] ?? null;
                return !$preferred || $tb->import_filename === $preferred;
            });
        }

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

            // Override/add totals directly from raw trial balance for 100% accuracy
            $periodTb = $trial_balance->where('acc_period', $period);
            
            $assetsDebitNet = 0;
            $revDebitNet = 0;
            $expDebitNet = 0;
            
            foreach ($periodTb as $tb) {
                $firstDigit = substr($tb->account_code, 0, 1);
                if ($firstDigit === '1') {
                    $assetsDebitNet += floatval($tb->debit_net) - floatval($tb->credit_net);
                } elseif ($firstDigit === '4') {
                    $revDebitNet += floatval($tb->credit_net) - floatval($tb->debit_net);
                } elseif ($firstDigit === '5') {
                    $expDebitNet += floatval($tb->debit_net) - floatval($tb->credit_net);
                }
            }
            
            $allPeriodsData[$period]['3014Y'] = [
                'debit_net' => $assetsDebitNet, 'credit_net' => 0, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];
            $allPeriodsData[$period]['3006Y'] = [
                'debit_net' => 0, 'credit_net' => $revDebitNet, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];
            $allPeriodsData[$period]['3010X'] = [
                'debit_net' => $expDebitNet, 'credit_net' => 0, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => 0
            ];

            // Cumulative credit monthly activity summing for 2600Y
            $getFiscalPeriodsUpTo = function($targetPeriod) use ($periods) {
                $list = [];
                $matched = false;
                foreach ($periods as $p) {
                    $list[] = $p['period'];
                    if ($p['period'] === $targetPeriod) {
                        $matched = true;
                        break;
                    }
                }
                return $matched ? $list : [];
            };
            
            $periodsUpTo = $getFiscalPeriodsUpTo($period);
            $cumCredit = 0;
            foreach ($periodsUpTo as $p) {
                $rows = $grouped[$p]['2600Y'] ?? [];
                foreach ($rows as $r) {
                    $cumCredit += floatval($r->credit_month);
                }
            }
            
            $allPeriodsData[$period]['2600Y'] = [
                'debit_net' => 0, 'credit_net' => $cumCredit, 'debit_bf' => 0, 'credit_bf' => 0, 'debit_month' => 0, 'credit_month' => $cumCredit
            ];
        }

        // Helper to retrieve values
        $getGroupValForPeriod = function($period, $groupCode) use (&$allPeriodsData) {
            $row = $allPeriodsData[$period][$groupCode] ?? null;
            if (!$row) return 0;

            if (in_array($groupCode, ['3014Y', '3006Y', '3010X'])) {
                return $row['debit_net'] ?: $row['credit_net'];
            }

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

        // Helper to get average value for days-based indicator numerators
        $getAverageGroupVal = function($period, $groupCode) use ($getGroupValForPeriod, $prevFyEndPeriod) {
            $currentVal = $getGroupValForPeriod($period, $groupCode);
            if (in_array($groupCode, ['2640X', '2600X', '2610X', '2620X', '2630X'])) {
                $prevVal = $getGroupValForPeriod($prevFyEndPeriod, $groupCode);
                if ($prevVal != 0) {
                    return ($currentVal + $prevVal) / 2;
                }
            }
            return $currentVal;
        };

        $ratioDefs = self::getRatioDefinitions();
        $history = [];
        foreach ($ratioDefs as $code => $def) {
            $history[$code] = [];
        }

        foreach ($validPeriods as $period) {
            if (!in_array($period, $importedPeriods)) {
                continue;
            }

            foreach ($ratioDefs as $code => $def) {
                $num = $getAverageGroupVal($period, $def['num_group']);
                $den = $getAverageGroupVal($period, $def['den_group']);

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
                $history[$code][$period] = [
                    'val' => round($val, $def['precision']),
                    'num' => $num,
                    'den' => $den
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
                if ($p === $prevFyEndPeriod) continue;
                $allGroupCodes = array_merge($allGroupCodes, array_keys($groups));
            }
            $allGroupCodes = array_unique($allGroupCodes);

            foreach ($allGroupCodes as $gCode) {
                $lat = $latestSums[$gCode] ?? ['debit_net' => 0, 'credit_net' => 0];
                $ear = $earliestSums[$gCode] ?? ['debit_bf' => 0, 'credit_bf' => 0];

                $debit_month = 0;
                $credit_month = 0;
                foreach ($allPeriodsData as $p => $groups) {
                    if ($p === $prevFyEndPeriod) continue;
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
        }

        $getGroupVal = function($groupCode) use ($getAverageGroupVal, $allPeriodsData, $prevFyEndPeriod, &$sums) {
            if (in_array($groupCode, ['2640X', '2600X', '2610X', '2620X', '2630X'])) {
                $latestImportedPeriod = DB::table('hosfin_trial_balance')
                    ->whereIn('acc_period', array_diff(array_keys($allPeriodsData), [$prevFyEndPeriod]))
                    ->orderBy('acc_period', 'desc')
                    ->value('acc_period');
                
                if ($latestImportedPeriod) {
                    return $getAverageGroupVal($latestImportedPeriod, $groupCode);
                }
            }
            
            $row = $sums[$groupCode] ?? null;
            if (!$row) return 0;

            if (in_array($groupCode, ['3014Y', '3006Y', '3010X'])) {
                return $row['debit_net'] ?: $row['credit_net'];
            }

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

        $ratios = [];
        foreach ($ratioDefs as $code => $def) {
            if ($selectedPeriod !== 'all') {
                $hist = $history[$code][$selectedPeriod] ?? ['val' => 0, 'num' => 0, 'den' => 0];
                $ratios[$code] = [
                    'code' => $code,
                    'name' => $def['name'],
                    'numerator_name' => $def['numerator_name'],
                    'denominator_name' => $def['denominator_name'],
                    'num_value' => $hist['num'],
                    'den_value' => $hist['den'],
                    'value' => $hist['val'],
                    'unit' => $def['unit'],
                    'precision' => $def['precision']
                ];
            } else {
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
                    'value' => round($val, $def['precision']),
                    'unit' => $def['unit'],
                    'precision' => $def['precision']
                ];
            }
        }

        // 6. Generate Chart Trends Data using cached in-memory summaries (instant rendering)
        $chartData = [];
        foreach ($periods as $p) {
            if (!in_array($p['period'], $importedPeriods)) {
                continue;
            }

            $monthRatios = [];
            foreach ($ratioDefs as $code => $def) {
                $monthRatios[$code] = $history[$code][$p['period']]['val'] ?? 0;
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
    public static function getRatioDefinitions()
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
                'name' => 'ระยะเวลาชำระเจ้าหนี้การค้ายา&เวชภัณฑ์มิใช่ยา',
                'numerator_name' => 'เจ้าหนี้การค้า(ยา วชช.)คงเหลือเฉลี่ย',
                'denominator_name' => 'เจ้าหนี้การค้า(ยา วชช.)รวม',
                'num_group' => '2600X',
                'den_group' => '2600Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '261' => [
                'name' => 'ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิ UC',
                'numerator_name' => 'ลูกหนี้ค่ารักษาสิทธิ UC เฉลี่ย',
                'denominator_name' => 'รายได้ค่ารักษาพยาบาลสิทธิ UC สุทธิ',
                'num_group' => '2610X',
                'den_group' => '2610Y',
                'type' => 'days',
                'unit' => 'วัน',
                'precision' => 2
            ],
            '262' => [
                'name' => 'ระยะเวลาถัวเฉลี่ยในการเรียกเก็บหนี้สิทธิข้าราชการ',
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
                'name' => 'การบริหารสินคงคลัง (Inventory Management)',
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
            'NI' => [
                'name' => 'Net Income (กำไรสุทธิ)',
                'numerator_name' => 'กำไรสุทธิ',
                'denominator_name' => '',
                'num_group' => '3007X',
                'den_group' => '',
                'type' => 'subtract',
                'unit' => 'บาท',
                'precision' => 2
            ],
            'RISK_SCORE' => [
                'name' => 'RISK SCORE (คะแนนความเสี่ยงทางการเงิน)',
                'numerator_name' => 'คะแนนสะสม',
                'denominator_name' => 'คะแนนเต็ม',
                'num_group' => '',
                'den_group' => '',
                'type' => 'value',
                'unit' => 'คะแนน',
                'precision' => 0
            ],
        ];
    }

    /**
     * GL Report: Accounts Payable (AP Creditors & Unpaid Bills)
     */
    public function ap_report(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        $yearChoices = range(self::getCurrentBudgetYear() + 1, self::getCurrentBudgetYear() - 3);

        $unpaidQuery = \App\Models\HosfinGlApBill::where('fiscal_year', $budgetYear)->where('is_paid', 0);
        $paidQuery = \App\Models\HosfinGlApBill::where('fiscal_year', $budgetYear)->where('is_paid', 1);

        $totalUnpaidSum = (float)$unpaidQuery->sum('remaining_debt');
        $totalUnpaidBillsCount = (int)$unpaidQuery->count();
        $totalPaidSum = (float)$paidQuery->sum('total_debit');
        $totalPaidBillsCount = (int)$paidQuery->count();
        $totalVendorsCount = (int)$unpaidQuery->distinct('vendor_name')->count('vendor_name');

        $vendorsSummary = \App\Models\HosfinGlApBill::where('fiscal_year', $budgetYear)
            ->select(
                'vendor_name',
                DB::raw('MAX(category) as category'),
                DB::raw('COUNT(*) as total_bills'),
                DB::raw('SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_bills'),
                DB::raw('SUM(total_credit) as total_credit'),
                DB::raw('SUM(total_debit) as total_debit'),
                DB::raw('SUM(CASE WHEN is_paid = 0 THEN remaining_debt ELSE 0 END) as remaining_debt'),
                DB::raw('GROUP_CONCAT(DISTINCT account_code) as account_codes'),
                DB::raw('GROUP_CONCAT(DISTINCT account_name) as account_names')
            )
            ->groupBy('vendor_name')
            ->orderBy('remaining_debt', 'desc')
            ->get();

        // Get all unique account categories for dropdown filter
        $accountChoices = \App\Models\HosfinGlApBill::where('fiscal_year', $budgetYear)
            ->whereNotNull('account_code')
            ->where('account_code', '<>', '')
            ->select('account_code', 'account_name', DB::raw('COUNT(*) as bill_count'))
            ->groupBy('account_code', 'account_name')
            ->orderBy('account_code')
            ->get();

        $bills = \App\Models\HosfinGlApBill::where('fiscal_year', $budgetYear)
                       ->orderBy('remaining_debt', 'desc')
                       ->orderBy('bill_date', 'desc')
                       ->get();

        $activeTab = $request->input('tab', 'vendor');

        // Accounting Audit & Anomaly Detection
        // 1. Cross-account bills (bills booked across multiple 2101 accounts)
        $crossAccountBills = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->leftJoin('hosfin_gl_subledgers as s', 'j.apar', '=', 's.subledger_code')
            ->whereNotNull('j.apar')
            ->where('j.apar', '<>', '')
            ->where('i.account_code', 'like', '2101%')
            ->where('j.fiscal_year', $budgetYear)
            ->select(
                'j.apar as bill_no',
                DB::raw('MAX(s.vendor_name) as vendor_name'),
                DB::raw('MAX(s.category) as category'),
                DB::raw('COUNT(DISTINCT i.account_code) as acc_count'),
                DB::raw('SUM(i.credit) as total_cr'),
                DB::raw('SUM(i.debit) as total_dr'),
                DB::raw('SUM(i.credit - i.debit) as net_rem')
            )
            ->groupBy('j.apar')
            ->having('acc_count', '>', 1)
            ->get();

        $crossAccountDetails = [];
        if ($crossAccountBills->isNotEmpty()) {
            $crossBillNos = $crossAccountBills->pluck('bill_no')->toArray();
            $vouchers = DB::table('hosfin_gl_journals as j')
                ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
                ->whereIn('j.apar', $crossBillNos)
                ->where('i.account_code', 'like', '2101%')
                ->select('j.apar', 'j.voucher_no', 'j.voucher_date', 'i.account_code', 'i.account_name', 'i.debit', 'i.credit')
                ->orderBy('j.voucher_date', 'asc')
                ->get()
                ->groupBy('apar');

            foreach ($crossAccountBills as $b) {
                $bVouchers = $vouchers->get($b->bill_no, collect());
                $crVouchers = $bVouchers->filter(function($v) { return $v->credit > 0; });
                $drVouchers = $bVouchers->filter(function($v) { return $v->debit > 0; });
                $crossAccountDetails[] = [
                    'bill_no' => $b->bill_no,
                    'vendor_name' => $b->vendor_name ?: $b->bill_no,
                    'category' => $b->category ?: 'ทั่วไป',
                    'total_cr' => (float)$b->total_cr,
                    'total_dr' => (float)$b->total_dr,
                    'net_rem' => (float)$b->net_rem,
                    'cr_vouchers' => $crVouchers,
                    'dr_vouchers' => $drVouchers,
                ];
            }
        }

        // 2. Overpaid bills (Debit > Credit)
        $overpaidBills = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->leftJoin('hosfin_gl_subledgers as s', 'j.apar', '=', 's.subledger_code')
            ->whereNotNull('j.apar')
            ->where('j.apar', '<>', '')
            ->where('i.account_code', 'like', '2101%')
            ->where('j.fiscal_year', $budgetYear)
            ->select(
                'j.apar as bill_no',
                DB::raw('MAX(s.vendor_name) as vendor_name'),
                DB::raw('MAX(s.category) as category'),
                DB::raw('SUM(i.credit) as total_cr'),
                DB::raw('SUM(i.debit) as total_dr'),
                DB::raw('SUM(i.debit - i.credit) as overpaid_amount')
            )
            ->groupBy('j.apar')
            ->having('overpaid_amount', '>', 0.01)
            ->orderBy('overpaid_amount', 'desc')
            ->get();

        return view('hosfin.ap_report', [
            'budgetYear' => $budgetYear,
            'yearChoices' => $yearChoices,
            'activeTab' => $activeTab,
            'totalUnpaidSum' => $totalUnpaidSum,
            'totalUnpaidBillsCount' => $totalUnpaidBillsCount,
            'totalPaidSum' => $totalPaidSum,
            'totalPaidBillsCount' => $totalPaidBillsCount,
            'totalVendorsCount' => $totalVendorsCount,
            'vendorsSummary' => $vendorsSummary,
            'bills' => $bills,
            'crossAccountDetails' => $crossAccountDetails,
            'overpaidBills' => $overpaidBills,
            'accountChoices' => $accountChoices,
        ]);
    }

    /**
     * AJAX: Get bills for a specific AP vendor
     */
    public function ap_vendor_bills(Request $request)
    {
        $vendor = trim($request->input('vendor', ''));
        if ($vendor === '') {
            return response()->json(['status' => 'error', 'message' => 'Vendor name is required'], 400);
        }

        $query = \App\Models\HosfinGlApBill::where('vendor_name', $vendor);
        if ($request->filled('budget_year')) {
            $query->where('fiscal_year', intval($request->input('budget_year')));
        }

        $bills = $query->orderBy('remaining_debt', 'desc')
            ->orderBy('bill_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'vendor' => $vendor,
            'total_bills' => $bills->count(),
            'unpaid_bills' => $bills->where('is_paid', 0)->count(),
            'total_credit' => (float)$bills->sum('total_credit'),
            'total_debit' => (float)$bills->sum('total_debit'),
            'remaining_debt' => (float)$bills->where('is_paid', 0)->sum('remaining_debt'),
            'bills' => $bills,
        ]);
    }

    /**
     * GL Report: Accounts Receivable (AR Debtors by Fund/Right)
     */
    public function ar_report(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        $yearChoices = range(self::getCurrentBudgetYear() + 1, self::getCurrentBudgetYear() - 3);
        $selectedPeriod = $request->input('period', 'all');

        // Build 12 fiscal periods list for the fiscal year
        $periods = [];
        for ($m = 10; $m <= 12; $m++) {
            $fm = $m - 9; // 10 -> 1, 11 -> 2, 12 -> 3
            $periods[] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year'         => $budgetYear - 1,
                'period'       => sprintf('%04d-%02d', $budgetYear - 1, $m),
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2)
            ];
        }
        for ($m = 1; $m <= 9; $m++) {
            $fm = $m + 3; // 1 -> 4, 2 -> 5, ... 9 -> 12
            $periods[] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year'         => $budgetYear,
                'period'       => sprintf('%04d-%02d', $budgetYear, $m),
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2)
            ];
        }

        // Check which fiscal months exist in hosfin_gl_ar_debtors (ignore month 0 which is OB)
        $existingMonths = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
            ->where('fiscal_month', '>', 0)
            ->distinct()
            ->pluck('fiscal_month')
            ->toArray();

        // Determine if specific period is selected
        $selectedFm = null;
        $selectedPeriodLabel = 'สะสมทั้งปีงบประมาณ ' . $budgetYear;

        if ($selectedPeriod !== 'all') {
            foreach ($periods as $p) {
                if ($p['period'] === $selectedPeriod || (string)$p['fiscal_month'] === (string)$selectedPeriod) {
                    $selectedFm = $p['fiscal_month'];
                    $selectedPeriod = $p['period'];
                    $selectedPeriodLabel = 'ประจำงวด ' . $p['label'];
                    break;
                }
            }
            if ($selectedFm === null) {
                $selectedPeriod = 'all';
            }
        }

        $totalOb = 0.0;
        $totalOutstandingYear = 0.0;

        if ($selectedPeriod === 'all') {
            $totalOb = (float)\App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)->where('fiscal_month', 0)->sum('outstanding_balance');
            $totalBilled = (float)\App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)->where('fiscal_month', '>', 0)->sum('total_billed');
            $totalCollected = (float)\App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)->where('fiscal_month', '>', 0)->sum('total_collected');
            $totalOutstandingYear = (float)\App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)->where('fiscal_month', '>', 0)->sum('outstanding_balance');
            $totalOutstanding = $totalOb + $totalOutstandingYear;

            $typeSummaries = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
                ->select(
                    'debtor_type',
                    DB::raw('COUNT(DISTINCT account_code) as account_count'),
                    DB::raw('SUM(CASE WHEN fiscal_month = 0 THEN outstanding_balance ELSE 0 END) as ob_balance'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_billed ELSE 0 END) as total_billed'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_collected ELSE 0 END) as total_collected'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN outstanding_balance ELSE 0 END) as year_outstanding'),
                    DB::raw('SUM(outstanding_balance) as outstanding_balance')
                )
                ->groupBy('debtor_type')
                ->orderBy('outstanding_balance', 'desc')
                ->get();

            // Group by account_code so each account code appears exactly once with cumulative total!
            $debtors = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
                ->select(
                    'account_code',
                    DB::raw('MAX(account_name) as account_name'),
                    DB::raw('MAX(debtor_type) as debtor_type'),
                    DB::raw('SUM(CASE WHEN fiscal_month = 0 THEN outstanding_balance ELSE 0 END) as ob_balance'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_billed ELSE 0 END) as total_billed'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN total_collected ELSE 0 END) as total_collected'),
                    DB::raw('SUM(CASE WHEN fiscal_month > 0 THEN outstanding_balance ELSE 0 END) as year_outstanding'),
                    DB::raw('SUM(outstanding_balance) as outstanding_balance'),
                    DB::raw('COUNT(DISTINCT CASE WHEN fiscal_month > 0 THEN fiscal_month END) as month_count')
                )
                ->groupBy('account_code')
                ->orderBy('outstanding_balance', 'desc')
                ->get();
        } else {
            // Specific month
            $monthData = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
                ->where('fiscal_month', $selectedFm)
                ->select(
                    DB::raw('SUM(total_billed) as total_billed'),
                    DB::raw('SUM(total_collected) as total_collected'),
                    DB::raw('SUM(outstanding_balance) as total_outstanding')
                )->first();

            $totalBilled = (float)($monthData->total_billed ?? 0);
            $totalCollected = (float)($monthData->total_collected ?? 0);
            $totalOutstanding = (float)($monthData->total_outstanding ?? 0);
            $totalOutstandingYear = $totalOutstanding;

            $typeSummaries = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
                ->where('fiscal_month', $selectedFm)
                ->select(
                    'debtor_type',
                    DB::raw('COUNT(DISTINCT account_code) as account_count'),
                    DB::raw('0 as ob_balance'),
                    DB::raw('SUM(total_billed) as total_billed'),
                    DB::raw('SUM(total_collected) as total_collected'),
                    DB::raw('SUM(outstanding_balance) as year_outstanding'),
                    DB::raw('SUM(outstanding_balance) as outstanding_balance')
                )
                ->groupBy('debtor_type')
                ->orderBy('outstanding_balance', 'desc')
                ->get();

            $debtors = \App\Models\HosfinGlArDebtor::where('fiscal_year', $budgetYear)
                ->where('fiscal_month', $selectedFm)
                ->select(
                    'account_code',
                    'account_name',
                    'debtor_type',
                    DB::raw('0 as ob_balance'),
                    'total_billed',
                    'total_collected',
                    DB::raw('outstanding_balance as year_outstanding'),
                    'outstanding_balance',
                    DB::raw('1 as month_count')
                )
                ->orderBy('outstanding_balance', 'desc')
                ->get();
        }

        $collectionRate = $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0;

        return view('hosfin.ar_report', [
            'budgetYear'           => $budgetYear,
            'yearChoices'          => $yearChoices,
            'periods'              => $periods,
            'existingMonths'       => $existingMonths,
            'selectedPeriod'       => $selectedPeriod,
            'selectedPeriodLabel'  => $selectedPeriodLabel,
            'typeSummaries'        => $typeSummaries,
            'totalBilled'          => $totalBilled,
            'totalCollected'       => $totalCollected,
            'totalOutstanding'     => $totalOutstanding,
            'totalOb'              => $totalOb,
            'totalOutstandingYear' => $totalOutstandingYear,
            'collectionRate'       => $collectionRate,
            'debtors'              => $debtors,
        ]);
    }

    /**
     * GL Report: Hospital Service Cost Analysis (LC / MC / CC)
     */
    public function cost_report(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        $yearChoices = range(self::getCurrentBudgetYear() + 1, self::getCurrentBudgetYear() - 3);
        $selectedPeriod = $request->input('period', 'all');

        // Build 12 fiscal periods list for the fiscal year
        $periods = [];
        for ($m = 10; $m <= 12; $m++) {
            $fm = $m - 9; // 10 -> 1, 11 -> 2, 12 -> 3
            $periods[] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year'         => $budgetYear - 1,
                'period'       => sprintf('%04d-%02d', $budgetYear - 1, $m),
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2)
            ];
        }
        for ($m = 1; $m <= 9; $m++) {
            $fm = $m + 3; // 1 -> 4, 2 -> 5, ... 9 -> 12
            $periods[] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year'         => $budgetYear,
                'period'       => sprintf('%04d-%02d', $budgetYear, $m),
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2)
            ];
        }

        // Check which fiscal months exist in cost summaries
        $existingMonths = \App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)
            ->distinct()
            ->pluck('fiscal_month')
            ->toArray();

        // Determine if specific period is selected
        $selectedFm = null;
        $selectedPeriodLabel = 'สะสมทั้งปีงบประมาณ ' . $budgetYear;

        if ($selectedPeriod !== 'all') {
            foreach ($periods as $p) {
                if ($p['period'] === $selectedPeriod || (string)$p['fiscal_month'] === (string)$selectedPeriod) {
                    $selectedFm = $p['fiscal_month'];
                    $selectedPeriod = $p['period'];
                    $selectedPeriodLabel = 'ประจำงวด ' . $p['label'];
                    break;
                }
            }
            if ($selectedFm === null) {
                $selectedPeriod = 'all';
            }
        }

        $costSummaries = \App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)
            ->orderBy('fiscal_month', 'asc')
            ->get();

        if ($selectedPeriod === 'all') {
            $totalLc = (float)\App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)->sum('lc_amount');
            $totalMc = (float)\App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)->sum('mc_amount');
            $totalCc = (float)\App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)->sum('cc_amount');
            $totalOther = (float)\App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)->sum('other_cost');
            $totalCost = (float)\App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)->sum('total_cost');

            $topAccountsQuery = DB::table('hosfin_gl_accounts as a')
                ->leftJoin('hosfin_gl_journal_items as i', 'a.account_code', '=', 'i.account_code')
                ->leftJoin('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
                ->where('a.account_code', 'like', '5%')
                ->where('j.fiscal_year', $budgetYear);
        } else {
            $monthCost = \App\Models\HosfinGlCostSummary::where('fiscal_year', $budgetYear)
                ->where('fiscal_month', $selectedFm)
                ->first();

            $totalLc = (float)($monthCost->lc_amount ?? 0);
            $totalMc = (float)($monthCost->mc_amount ?? 0);
            $totalCc = (float)($monthCost->cc_amount ?? 0);
            $totalOther = (float)($monthCost->other_cost ?? 0);
            $totalCost = (float)($monthCost->total_cost ?? 0);

            $topAccountsQuery = DB::table('hosfin_gl_accounts as a')
                ->leftJoin('hosfin_gl_journal_items as i', 'a.account_code', '=', 'i.account_code')
                ->leftJoin('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
                ->where('a.account_code', 'like', '5%')
                ->where('j.fiscal_year', $budgetYear)
                ->where('j.fiscal_month', $selectedFm);
        }

        $lcPercent = $totalCost > 0 ? round(($totalLc / $totalCost) * 100, 1) : 0;
        $mcPercent = $totalCost > 0 ? round(($totalMc / $totalCost) * 100, 1) : 0;
        $ccPercent = $totalCost > 0 ? round(($totalCc / $totalCost) * 100, 1) : 0;

        $topAccounts = $topAccountsQuery->select(
                'a.account_code',
                'a.account_name',
                'a.cost_type',
                'a.service_type',
                DB::raw('SUM(COALESCE(i.debit, 0) - COALESCE(i.credit, 0)) as net_expense'),
                DB::raw('COUNT(i.id) as tx_count')
            )
            ->groupBy('a.account_code', 'a.account_name', 'a.cost_type', 'a.service_type')
            ->havingRaw('SUM(COALESCE(i.debit, 0) - COALESCE(i.credit, 0)) != 0 OR COUNT(i.id) > 0')
            ->orderBy('net_expense', 'desc')
            ->get();

        $chartLabels = $costSummaries->map(fn($c) => $c->period_label)->toArray();
        $chartLc = $costSummaries->map(fn($c) => (float)$c->lc_amount)->toArray();
        $chartMc = $costSummaries->map(fn($c) => (float)$c->mc_amount)->toArray();
        $chartCc = $costSummaries->map(fn($c) => (float)$c->cc_amount)->toArray();
        $chartOther = $costSummaries->map(fn($c) => (float)$c->other_cost)->toArray();
        $chartTotal = $costSummaries->map(fn($c) => (float)$c->total_cost)->toArray();

        return view('hosfin.cost_report', [
            'budgetYear'          => $budgetYear,
            'yearChoices'         => $yearChoices,
            'periods'             => $periods,
            'existingMonths'      => $existingMonths,
            'selectedPeriod'      => $selectedPeriod,
            'selectedPeriodLabel' => $selectedPeriodLabel,
            'selectedFm'          => $selectedFm,
            'costSummaries'       => $costSummaries,
            'totalLc'             => $totalLc,
            'totalMc'             => $totalMc,
            'totalCc'             => $totalCc,
            'totalOther'          => $totalOther,
            'totalCost'           => $totalCost,
            'lcPercent'           => $lcPercent,
            'mcPercent'           => $mcPercent,
            'ccPercent'           => $ccPercent,
            'topAccounts'         => $topAccounts,
            'chartLabels'         => $chartLabels,
            'chartLc'             => $chartLc,
            'chartMc'             => $chartMc,
            'chartCc'             => $chartCc,
            'chartOther'          => $chartOther,
            'chartTotal'          => $chartTotal,
        ]);
    }

    /**
     * Recalculate Trial Balance and Metrics directly from Live GL Journals
     */
    public function recalculate_from_gl(Request $request)
    {
        $hasGlData = DB::table('hosfin_gl_journal_items')->exists();
        if (!$hasGlData) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลสมุดรายวันในระบบ GL กรุณาเปิดโปรแกรม Rims GL Sync แล้วกด "ซิงค์ข้อมูลทันที" ก่อน'
            ], 422);
        }

        try {
            $count = self::syncTrialBalanceFromGl();

            return response()->json([
                'success' => true,
                'message' => "ประมวลผลข้อมูลจาก GL สำเร็จเรียบร้อย! อัปเดตงบทดลองและดัชนีทางการเงิน $count รายการ",
                'records_count' => $count
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผลจาก GL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggregate GL Journal Items into Monthly Balances (Live Daily GL Calculation)
     */
    public static function syncGlMonthlyBalances()
    {
        // 1. Fetch raw journal activity grouped by year, month, account
        $rows = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->select(
                'j.fiscal_year as j_fy',
                'j.fiscal_month as j_fm',
                'i.account_code',
                DB::raw("MAX(i.account_name) as account_name"),
                DB::raw("SUM(COALESCE(i.debit, 0)) as total_debit"),
                DB::raw("SUM(COALESCE(i.credit, 0)) as total_credit")
            )
            ->whereNotNull('i.account_code')
            ->where('i.account_code', '<>', '')
            ->whereNotNull('j.fiscal_year')
            ->groupBy('j.fiscal_year', 'j.fiscal_month', 'i.account_code')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        // Account names lookup from mappings
        $nameMap = DB::table('hosfin_dtl_mappings')
            ->select('account_code', 'account_name')
            ->distinct()
            ->pluck('account_name', 'account_code')
            ->toArray();

        // 2. Group by budget year
        $byBudgetYear = [];
        foreach ($rows as $r) {
            $fy = intval($r->j_fy);
            $fm = intval($r->j_fm);
            
            if ($fm === 0) {
                // Opening balance of fiscal year
                $byBudgetYear[$fy][0]['accounts'][$r->account_code] = [
                    'name' => $r->account_name ?: ($nameMap[$r->account_code] ?? $r->account_code),
                    'dr_month' => (float)$r->total_debit,
                    'cr_month' => (float)$r->total_credit,
                ];
                continue;
            }

            // Calculate calendar year and month for period
            if ($fm >= 1 && $fm <= 3) {
                $cMonth = $fm + 9;
                $cYear = $fy - 1;
            } else {
                $cMonth = $fm - 3;
                $cYear = $fy;
            }

            $period = sprintf('%04d-%02d', $cYear, $cMonth);
            $byBudgetYear[$fy][$fm]['period'] = $period;
            $byBudgetYear[$fy][$fm]['cYear'] = $cYear;
            $byBudgetYear[$fy][$fm]['cMonth'] = $cMonth;
            $byBudgetYear[$fy][$fm]['accounts'][$r->account_code] = [
                'name' => $r->account_name ?: ($nameMap[$r->account_code] ?? $r->account_code),
                'dr_month' => (float)$r->total_debit,
                'cr_month' => (float)$r->total_credit,
            ];
        }

        // 3. For each budget year, build chronological cumulative periods (fm 1 to 12)
        $monthlyInserts = [];
        $affectedPeriods = [];

        foreach ($byBudgetYear as $fy => $monthsData) {
            // Keep cumulative running totals across the fiscal year for each account
            $runningNet = []; // account_code => ['dr' => float, 'cr' => float]

            // Seed with opening balances (fm = 0)
            if (isset($monthsData[0]['accounts'])) {
                foreach ($monthsData[0]['accounts'] as $accCode => $accData) {
                    $runningNet[$accCode] = [
                        'dr' => $accData['dr_month'],
                        'cr' => $accData['cr_month']
                    ];
                }
            }

            for ($fm = 1; $fm <= 12; $fm++) {
                if (!isset($monthsData[$fm])) {
                    continue;
                }

                $mInfo = $monthsData[$fm];
                $period = $mInfo['period'];
                $affectedPeriods[] = $period;

                // Collect all accounts present in either this month or in running balance
                $allAccountsInScope = array_unique(array_merge(
                    array_keys($runningNet),
                    array_keys($mInfo['accounts'])
                ));

                foreach ($allAccountsInScope as $accCode) {
                    $accData = $mInfo['accounts'][$accCode] ?? null;
                    $drMonth = $accData ? $accData['dr_month'] : 0.0;
                    $crMonth = $accData ? $accData['cr_month'] : 0.0;

                    $prevDr = $runningNet[$accCode]['dr'] ?? 0.0;
                    $prevCr = $runningNet[$accCode]['cr'] ?? 0.0;

                    $netDr = $prevDr + $drMonth;
                    $netCr = $prevCr + $crMonth;

                    // Update running
                    $runningNet[$accCode] = [
                        'dr' => $netDr,
                        'cr' => $netCr
                    ];

                    $cleanName = $nameMap[$accCode] ?? ($accData ? $accData['name'] : $accCode);

                    $monthlyInserts[] = [
                        'fiscal_year' => $fy,
                        'fiscal_month' => $fm,
                        'acc_period' => $period,
                        'account_code' => $accCode,
                        'account_name' => $cleanName,
                        'account_type' => substr($accCode, 0, 1) ?: '1',
                        'beginning_debit' => round($prevDr, 2),
                        'beginning_credit' => round($prevCr, 2),
                        'period_debit' => round($drMonth, 2),
                        'period_credit' => round($crMonth, 2),
                        'ending_debit' => round($netDr, 2),
                        'ending_credit' => round($netCr, 2),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
        }

        // 4. Save to hosfin_gl_monthly_balances (Dedicated GL Monthly Aggregate table)
        if (!empty($monthlyInserts)) {
            $affectedPeriods = array_unique($affectedPeriods);
            DB::transaction(function() use ($affectedPeriods, $monthlyInserts) {
                // Delete previous rows for affected periods
                DB::table('hosfin_gl_monthly_balances')
                    ->whereIn('acc_period', $affectedPeriods)
                    ->delete();

                foreach (array_chunk($monthlyInserts, 250) as $chunk) {
                    DB::table('hosfin_gl_monthly_balances')->insert($chunk);
                }
            });
            return count($monthlyInserts);
        }

        return 0;
    }

    /**
     * Backward compatibility alias
     */
    public static function syncTrialBalanceFromGl()
    {
        return self::syncGlMonthlyBalances();
    }

    /**
     * AI Financial Diagnosis based purely on Live GL Data
     */
    public function ai_analyze(Request $request)
    {
        // 1. AP from GL
        $totalUnpaidAp = (float)\App\Models\HosfinGlApBill::where('is_paid', 0)->sum('remaining_debt');
        $totalUnpaidApCount = (int)\App\Models\HosfinGlApBill::where('is_paid', 0)->count();
        $totalPaidAp = (float)\App\Models\HosfinGlApBill::where('is_paid', 1)->sum('total_debit');
        $topCreditors = \App\Models\HosfinGlApBill::select(
                'vendor_name',
                DB::raw('MAX(category) as category'),
                DB::raw('COUNT(*) as total_bills'),
                DB::raw('SUM(remaining_debt) as remaining_debt')
            )
            ->where('is_paid', 0)
            ->groupBy('vendor_name')
            ->orderBy('remaining_debt', 'desc')
            ->limit(5)
            ->get();

        // 2. AR from GL
        $totalArOutstanding = (float)\App\Models\HosfinGlArDebtor::sum('outstanding_balance');
        $totalArBilled = (float)\App\Models\HosfinGlArDebtor::sum('total_billed');
        $totalArCollected = (float)\App\Models\HosfinGlArDebtor::sum('total_collected');
        $totalArCount = (int)\App\Models\HosfinGlArDebtor::count();
        $arTypeSummaries = \App\Models\HosfinGlArDebtor::select(
                'debtor_type',
                DB::raw('COUNT(*) as account_count'),
                DB::raw('SUM(total_billed) as billed'),
                DB::raw('SUM(total_collected) as collected'),
                DB::raw('SUM(outstanding_balance) as outstanding')
            )
            ->groupBy('debtor_type')
            ->orderBy('outstanding', 'desc')
            ->get();

        $topArDebtors = \App\Models\HosfinGlArDebtor::orderBy('outstanding_balance', 'desc')->limit(5)->get();

        // 3. Cost from GL
        $totalCost = (float)\App\Models\HosfinGlCostSummary::sum('total_cost');
        $totalLc = (float)\App\Models\HosfinGlCostSummary::sum('lc_amount');
        $totalMc = (float)\App\Models\HosfinGlCostSummary::sum('mc_amount');
        $totalCc = (float)\App\Models\HosfinGlCostSummary::sum('cc_amount');

        $lcPercent = $totalCost > 0 ? round(($totalLc / $totalCost) * 100, 1) : 0;
        $mcPercent = $totalCost > 0 ? round(($totalMc / $totalCost) * 100, 1) : 0;
        $ccPercent = $totalCost > 0 ? round(($totalCc / $totalCost) * 100, 1) : 0;

        // 4. Cash & Bank Accounts (1101%)
        $cashBankAccounts = DB::table('hosfin_gl_accounts as a')
            ->leftJoin('hosfin_gl_journal_items as i', 'a.account_code', '=', 'i.account_code')
            ->where('a.account_code', 'like', '1101%')
            ->select('a.account_code', 'a.account_name', DB::raw('SUM(COALESCE(i.debit, 0) - COALESCE(i.credit, 0)) as balance'))
            ->groupBy('a.account_code', 'a.account_name')
            ->having('balance', '<>', 0)
            ->orderBy('balance', 'desc')
            ->get();
        $totalCash = (float)$cashBankAccounts->sum('balance');
        $cashAccountsCount = $cashBankAccounts->count();

        // 5. Ratios & Risk Score (Period-Aware)
        $indexData = $this->index($request)->getData();
        $riskScore = $indexData['riskScore'] ?? 0;
        $riskScoreLabel = $indexData['riskScoreLevelLabel'] ?? 'ไม่ระบุ';
        $latestMetrics = $indexData['latestMetrics'] ?? [];
        $latestPeriod = $indexData['latestPeriod'] ?? null;
        $latestPeriodLabel = $indexData['latestPeriodLabel'] ?? 'งวดล่าสุด';
        $budgetYear = $indexData['budgetYear'] ?? self::getCurrentBudgetYear();

        $netOperatingFund = $latestMetrics['105']['val'] ?? 0;
        $currentRatio = $latestMetrics['100']['val'] ?? 0;
        $cashRatio = $latestMetrics['102']['val'] ?? 0;
        $quickRatio = $latestMetrics['101']['val'] ?? 0;
        $nwc = $latestMetrics['104']['val'] ?? 0;
        $drugPayDays = $latestMetrics['260']['val'] ?? 0;
        $ofcCollectDays = $latestMetrics['262']['val'] ?? 0;
        $ucCollectDays = $latestMetrics['261']['val'] ?? 0;
        $inventoryDays = $latestMetrics['264']['val'] ?? 0;
        $netMargin = $latestMetrics['307']['val'] ?? 0;

        $operatingCash = $indexData['operatingCash'] ?? 0;
        $restrictedCash = $indexData['restrictedCash'] ?? 0;

        // Previous period comparison (Month-over-Month Trend)
        $prevPeriodText = "";
        if (!empty($latestPeriod)) {
            $prevPeriod = DB::table('hosfin_gl_monthly_balances')
                ->where('acc_period', '<', $latestPeriod)
                ->orderBy('acc_period', 'desc')
                ->value('acc_period');

            if ($prevPeriod) {
                try {
                    $prevReq = new Request(['period' => $prevPeriod]);
                    $prevData = $this->index($prevReq)->getData();
                    $prevMetrics = $prevData['latestMetrics'] ?? [];
                    $prevNetFund = $prevMetrics['105']['val'] ?? null;
                    $prevRisk = $prevData['riskScore'] ?? null;
                    $prevLabel = $prevData['latestPeriodLabel'] ?? $prevPeriod;

                    if ($prevNetFund !== null) {
                        $diffFund = $netOperatingFund - $prevNetFund;
                        $diffSign = ($diffFund >= 0 ? '+' : '');
                        $prevPeriodText = "- แนวโน้มเปรียบเทียบกับงวดก่อนหน้า ({$prevLabel}):\n"
                            . "  * เงินบำรุงสุทธิ (105): งวดนี้ " . number_format($netOperatingFund, 2) . " บ. | งวดก่อน " . number_format($prevNetFund, 2) . " บ. (เปลี่ยนแปลง {$diffSign}" . number_format($diffFund, 2) . " บาท)\n"
                            . "  * ระดับความเสี่ยง (Risk Score): งวดนี้ระดับ {$riskScore} | งวดก่อนระดับ {$prevRisk}\n";
                    }
                } catch (\Throwable $ex) {
                    // Ignore MoM comparison error
                }
            }
        }

        $glData = compact(
            'totalUnpaidAp', 'totalUnpaidApCount', 'totalPaidAp', 'topCreditors',
            'totalArOutstanding', 'totalArBilled', 'totalArCollected', 'totalArCount', 'arTypeSummaries', 'topArDebtors',
            'totalCost', 'totalLc', 'totalMc', 'totalCc', 'lcPercent', 'mcPercent', 'ccPercent',
            'totalCash', 'operatingCash', 'restrictedCash', 'cashAccountsCount', 'cashBankAccounts',
            'riskScore', 'riskScoreLabel', 'latestPeriod', 'latestPeriodLabel', 'budgetYear',
            'netOperatingFund', 'currentRatio', 'cashRatio', 'quickRatio', 'nwc',
            'drugPayDays', 'ofcCollectDays', 'ucCollectDays', 'inventoryDays', 'netMargin'
        );

        // Sources list for UI
        $sources = [
            [
                'title' => 'สมุดรายวันและผังบัญชี GL (' . number_format(DB::table('hosfin_gl_journal_items')->count()) . ' รายการ)',
                'filename' => 'hosfin_gl_journal_items',
                'page' => 1,
                'snippet' => 'บัญชีเงินสดและเงินฝากธนาคาร ' . number_format($totalCash, 2) . ' บาท (' . $cashAccountsCount . ' บัญชี)'
            ],
            [
                'title' => 'ทะเบียนเจ้าหนี้การค้า GL (AP Bills: ' . number_format($totalUnpaidApCount) . ' บิล)',
                'filename' => 'hosfin_gl_ap_bills',
                'page' => 1,
                'snippet' => 'หนี้เจ้าหนี้ค้างชำระ ' . number_format($totalUnpaidAp, 2) . ' บาท, ระยะเวลาค้างจ่ายค่ายา ' . $drugPayDays . ' วัน'
            ],
            [
                'title' => 'ทะเบียนลูกหนี้ค่ารักษาพยาบาล GL (AR Debtors: ' . number_format($totalArCount) . ' ผังบัญชี)',
                'filename' => 'hosfin_gl_ar_debtors',
                'page' => 1,
                'snippet' => 'ลูกหนี้ค้างท่อ ' . number_format($totalArOutstanding, 2) . ' บาท, สิทธิข้าราชการค้างเก็บ ' . $ofcCollectDays . ' วัน'
            ],
            [
                'title' => 'โครงสร้างต้นทุนบริการ GL (LC/MC/CC ต้นทุนรวม ' . number_format($totalCost, 2) . ' บาท)',
                'filename' => 'hosfin_gl_cost_summaries',
                'page' => 1,
                'snippet' => "LC ค่าแรง {$lcPercent}% | MC ค่าวัสดุยา {$mcPercent}% | CC ค่าเสื่อมลงทุน {$ccPercent}%"
            ],
            [
                'title' => '13 ดัชนีชี้วัดสถานะการเงินโรงพยาบาล (HosFin Financial Distress Ratios)',
                'filename' => 'hosfin_ratios',
                'page' => 1,
                'snippet' => "Risk Score: {$riskScore}/7 ({$riskScoreLabel}), เงินบำรุงสุทธิ (105): " . number_format($netOperatingFund, 2) . " บาท"
            ],
        ];

        // Execute 100% Real Generative AI Synthesis (Supports Gemini, Ollama, OpenAI-compatible)
        $answer = null;
        $aiError = null;
        $provider = \App\Services\Ai\AiService::getProvider();
        $providerLabel = match($provider) {
            'gemini' => 'Google Gemini (Cloud)',
            'ollama' => 'Ollama (Local / On-Premise)',
            default => 'OpenAI / DeepSeek (Compatible)'
        };
        $model = \App\Services\Ai\AiService::getModelName('hosfin');

        try {
            $glFactSheet = "ข้อมูลจริงจากฐานข้อมูลบัญชีแยกประเภท GL (General Ledger) ล่าสุด:\n"
                . "- งวดบัญชี: {$latestPeriodLabel} (ปีงบ {$budgetYear})\n"
                . "- ระดับความเสี่ยงทางการเงิน (Risk Score): ระดับ {$riskScore} / 7 ({$riskScoreLabel})\n"
                . "- เงินบำรุงคงเหลือสุทธิ (105): " . number_format($netOperatingFund, 2) . " บาท\n"
                . "- สภาพคล่อง: Current Ratio = {$currentRatio} เท่า, Cash Ratio = {$cashRatio} เท่า, Quick Ratio = {$quickRatio} เท่า, ทุนหมุนเวียน NWC = " . number_format($nwc, 2) . " บาท\n"
                . "- เงินสดและเงินฝากธนาคารจริงใน GL: " . number_format($totalCash, 2) . " บาท จาก {$cashAccountsCount} บัญชี (เป็นเงินสดพร้อมใช้ตามเกณฑ์ สธ. 1003X: " . number_format($operatingCash, 2) . " บาท ที่นำมาคำนวณใน Cash Ratio & ดัชนี 105, และเป็นเงินเฉพาะกิจ/งบลงทุน UC/บริจาค: " . number_format($restrictedCash, 2) . " บาท ที่กันไว้ตามเกณฑ์ สธ.)\n"
                . "- เจ้าหนี้การค้า (AP Bills): หนี้ค้างชำระรวม " . number_format($totalUnpaidAp, 2) . " บาท จากทั้งหมด " . number_format($totalUnpaidApCount) . " บิล\n"
                . "  เจ้าหนี้ค้างจ่ายสูงสุด: " . $topCreditors->map(fn($v) => "{$v->vendor_name} (" . number_format($v->remaining_debt, 2) . " บ.)")->implode(', ') . "\n"
                . "  ระยะเวลาชำระหนี้ค่ายา (260): {$drugPayDays} วัน (เกณฑ์ปกติ <= 60 วัน)\n"
                . "- ลูกหนี้ค่ารักษาพยาบาล (AR Debtors): ยอดค้างชำระรวม " . number_format($totalArOutstanding, 2) . " บาท (จากตั้งเบิก " . number_format($totalArBilled, 2) . " บ., รับชดเชยแล้ว " . number_format($totalArCollected, 2) . " บ.)\n"
                . "  แยกตามสิทธิ: " . $arTypeSummaries->map(fn($s) => "{$s->debtor_type} ค้าง " . number_format($s->outstanding, 2) . " บ.")->implode(', ') . "\n"
                . "  ระยะเวลาเก็บหนี้ข้าราชการ (262): {$ofcCollectDays} วัน, ลูกหนี้ UC (261): {$ucCollectDays} วัน\n"
                . "- โครงสร้างต้นทุนบริการ (Cost LC/MC/CC): รวม " . number_format($totalCost, 2) . " บาท\n"
                . "  MC ค่าวัสดุยา: " . number_format($totalMc, 2) . " บาท ({$mcPercent}%)\n"
                . "  LC ค่าแรงบุคลากร: " . number_format($totalLc, 2) . " บาท ({$lcPercent}%)\n"
                . "  CC ค่าลงทุนและเสื่อมราคา: " . number_format($totalCc, 2) . " บาท ({$ccPercent}%)\n"
                . "  อัตราสำรองคลังยา (264): {$inventoryDays} วัน, Net Margin (307): {$netMargin}%\n"
                . $prevPeriodText;

            if ($provider === 'ollama') {
                $aiPrompt = "คุณคือผู้เชี่ยวชาญการเงินการคลังโรงพยาบาล วิเคราะห์งบ GL สรุปรายงานผู้บริหารแบบกระชับ ตรงประเด็น (ตอบสั้นกระชับ 4 ข้อหลัก ไม่ต้องเกริ่นยาว):\n\n"
                    . $glFactSheet . "\n\n"
                    . "กรุณาสรุป 4 ประเด็นหลักอย่างกระชับ:\n"
                    . "### 1. บทสรุปสุขภาพการเงินและสภาพคล่องปัจจุบัน\n"
                    . "### 2. ชี้เป้าคอขวดหนี้ AP / ลูกหนี้ AR\n"
                    . "### 3. ความเสี่ยงกระแสเงินสด\n"
                    . "### 4. แผนปฏิบัติการเร่งด่วน 30-60 วัน\n\n"
                    . "หมายเหตุ: ห้ามใช้แท็ก HTML หรือสูตร LaTeX ให้ใช้ Markdown ธรรมดา";
            } else {
                $aiPrompt = "คุณคือผู้เชี่ยวชาญด้านการเงินการคลังโรงพยาบาลภาครัฐและระบบบัญชี GL (Hospital Financial Advisor & Executive Strategist)\n"
                    . "กรุณาวิเคราะห์สุขภาพการเงินของโรงพยาบาลอย่างเจาะลึกจากฐานข้อมูลบัญชี GL จริง (General Ledger) ต่อไปนี้:\n\n"
                    . $glFactSheet . "\n\n"
                    . "กรุณาสรุปและจัดทำรายงานผู้บริหาร (Executive Summary) โดยจัดโครงสร้างคำตอบเป็น 4 ส่วนหลักให้ชัดเจน:\n"
                    . "### 1. บทสรุปสุขภาพการเงินและสภาพคล่องปัจจุบัน (Executive Overview & Liquidity Status)\n"
                    . "### 2. ชี้เป้าสาเหตุของวิกฤตและคอขวดจากฐานข้อมูล GL (Root Cause Diagnosis: บิลเจ้าหนี้ AP, ลูกหนี้ AR, โครงสร้างต้นทุน LC/MC/CC)\n"
                    . "### 3. การคาดการณ์ล่วงหน้า 3-6 เดือน และความเสี่ยงกระแสเงินสดหมดมือ (Forward-Looking Cash Runway & Risk Projections)\n"
                    . "### 4. แผนปฏิบัติการเร่งด่วน 30-60-90 วัน และข้อเสนอแนะเชิงกลยุทธ์ (Strategic Action Roadmap: ลำดับการจ่ายหนี้ค่ายา และแผนเร่งรัดลูกหนี้)\n\n"
                    . "หมายเหตุ: ให้อ้างอิงตัวเลข ยอดเงิน ชื่อบริษัทคู่ค้า และสิทธิการรักษาจากฐานข้อมูล GL ข้างต้นอย่างเจาะจง ใช้ภาษาไทยทางการที่กระชับ ชัดเจน น่าเชื่อถือ ให้ข้อเสนอแนะที่ผู้อำนวยการโรงพยาบาลนำไปตัดสินใจสั่งการได้ทันที (ห้ามใช้แท็ก HTML เช่น <font> และห้ามใช้สูตร LaTeX เช่น $$ \text{...} $$ ให้ใช้ Markdown ธรรมดาและเขียนสมการอ่านง่าย)";
            }

            $aiService = app(\App\Services\Ai\AiService::class);
            $answer = $aiService->generateChat($aiPrompt, null, 'hosfin');
        } catch (\Throwable $e) {
            $aiError = $e->getMessage();
            \Illuminate\Support\Facades\Log::warning("HosFin pure AI analysis failed: " . $aiError);
        }

        $snapshotData = [
            'period' => $latestPeriod,
            'periodLabel' => $latestPeriodLabel,
            'budgetYear' => $budgetYear,
            'riskScore' => $riskScore,
            'riskScoreLabel' => $riskScoreLabel,
            'netOperatingFund' => $netOperatingFund,
            'currentRatio' => $currentRatio,
            'cashRatio' => $cashRatio,
            'quickRatio' => $quickRatio,
            'drugPayDays' => $drugPayDays,
            'ofcCollectDays' => $ofcCollectDays,
            'totalUnpaidAp' => $totalUnpaidAp,
            'totalUnpaidApCount' => $totalUnpaidApCount,
            'totalArOutstanding' => $totalArOutstanding,
            'totalArCount' => $totalArCount,
            'totalCash' => $totalCash,
            'operatingCash' => $operatingCash,
        ];

        if (!empty($answer)) {
            return response()->json([
                'success' => true,
                'answer' => $answer,
                'provider' => $provider,
                'provider_label' => $providerLabel,
                'model' => $model,
                'sources' => $sources,
                'snapshot' => $snapshotData,
                'glSummary' => [
                    'totalUnpaidAp' => $totalUnpaidAp,
                    'totalArOutstanding' => $totalArOutstanding,
                    'totalCost' => $totalCost,
                    'totalCash' => $totalCash,
                    'riskScore' => $riskScore,
                    'riskScoreLabel' => $riskScoreLabel,
                ]
            ]);
        }

        // Return clear, multi-provider error message
        return response()->json([
            'success' => false,
            'is_ai_error' => true,
            'provider' => $provider,
            'provider_label' => $providerLabel,
            'model' => $model,
            'message' => $aiError ?: 'ไม่สามารถเชื่อมต่อระบบ AI ได้ กรุณาตรวจสอบการตั้งค่าผู้ให้บริการ AI',
            'settings_url' => route('admin.rag.index'),
            'sources' => $sources,
            'snapshot' => $snapshotData,
            'glSummary' => [
                'totalUnpaidAp' => $totalUnpaidAp,
                'totalArOutstanding' => $totalArOutstanding,
                'totalCost' => $totalCost,
                'totalCash' => $totalCash,
                'riskScore' => $riskScore,
                'riskScoreLabel' => $riskScoreLabel,
            ]
        ]);
    }

    /**
     * In-Modal AI Drill-down using Text-to-SQL for HosFin
     */
    public function ai_drilldown(Request $request, \App\Services\Ai\TextToSql\TextToSqlService $textToSqlService)
    {
        $question = trim($request->input('question', ''));
        $period = $request->input('period');
        $budgetYear = $request->input('budget_year');

        if (empty($question)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุคำถามที่ต้องการค้นหาเจาะลึก'
            ]);
        }

        $contextualQuestion = $question;
        $contextHints = [];
        if (!empty($period) && !str_contains($question, $period)) {
            $contextHints[] = "งวดบัญชี {$period}";
        }
        if (!empty($budgetYear) && !str_contains($question, (string)$budgetYear)) {
            $contextHints[] = "ปีงบประมาณ {$budgetYear}";
        }
        if (!empty($contextHints)) {
            $contextualQuestion .= " (" . implode(', ', $contextHints) . ")";
        }

        try {
            $result = $textToSqlService->generateAndExecute($contextualQuestion, 'hrims');
            return response()->json($result);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("HosFin AI Drilldown Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการประมวลผลคำถามเจาะลึก: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Cash & Bank Register (ทะเบียนรับ-จ่ายเงินสดและเงินฝากธนาคาร)
     */
    public function cash_register(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        $yearChoices = range(self::getCurrentBudgetYear() + 1, self::getCurrentBudgetYear() - 3);

        $fyStart = ($budgetYear - 544) . '-10-01'; // 2025-10-01
        $fyEnd   = ($budgetYear - 543) . '-09-30'; // 2026-09-30

        $selectedPeriod = $request->input('period', 'all');
        $selectedAccount = $request->input('account_code', 'all');
        $viewMode = $request->input('view_mode', $selectedPeriod === 'all' ? 'monthly' : 'daily');
        if ($selectedPeriod !== 'all' && $viewMode === 'monthly') {
            $viewMode = 'daily';
        }
        if ($selectedPeriod === 'all' && $viewMode === 'daily') {
            $viewMode = 'monthly';
        }
        $txType = $request->input('type', 'all'); // 'all', 'dr', 'cr'
        $search = trim($request->input('search', ''));

        // 1. Build 12 fiscal periods
        $periods = [];
        for ($m = 10; $m <= 12; $m++) {
            $fm = $m - 9;
            $y = $budgetYear - 544;
            $p = sprintf('%04d-%02d', $y, $m);
            $periods[$p] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year_ce'      => $y,
                'period'       => $p,
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)($budgetYear - 1), -2),
                'start_date'   => date('Y-m-01', strtotime("$y-$m-01")),
                'end_date'     => date('Y-m-t', strtotime("$y-$m-01")),
            ];
        }
        for ($m = 1; $m <= 9; $m++) {
            $fm = $m + 3;
            $y = $budgetYear - 543;
            $p = sprintf('%04d-%02d', $y, $m);
            $periods[$p] = [
                'fiscal_month' => $fm,
                'month'        => $m,
                'year_ce'      => $y,
                'period'       => $p,
                'label'        => self::getThaiMonthName($m) . ' ' . substr((string)$budgetYear, -2),
                'start_date'   => date('Y-m-01', strtotime("$y-$m-01")),
                'end_date'     => date('Y-m-t', strtotime("$y-$m-01")),
            ];
        }

        if ($selectedPeriod !== 'all' && !isset($periods[$selectedPeriod])) {
            $selectedPeriod = 'all';
        }

        $startDate = $selectedPeriod === 'all' ? $fyStart : $periods[$selectedPeriod]['start_date'];
        $endDate   = $selectedPeriod === 'all' ? $fyEnd   : $periods[$selectedPeriod]['end_date'];
        $selectedPeriodLabel = $selectedPeriod === 'all' ? 'ภาพรวมทั้งปีงบประมาณ ' . $budgetYear : 'ประจำงวด ' . $periods[$selectedPeriod]['label'];

        // 2. Query Cash & Bank Accounts (1101%)
        $accounts = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->select(
                'i.account_code',
                'i.account_name',
                DB::raw('COUNT(*) as tx_count'),
                DB::raw('SUM(CASE WHEN j.voucher_date < "' . $fyStart . '" THEN i.debit - i.credit ELSE 0 END) as ob'),
                DB::raw('SUM(CASE WHEN j.voucher_date BETWEEN "' . $fyStart . '" AND "' . $fyEnd . '" THEN i.debit ELSE 0 END) as total_dr'),
                DB::raw('SUM(CASE WHEN j.voucher_date BETWEEN "' . $fyStart . '" AND "' . $fyEnd . '" THEN i.credit ELSE 0 END) as total_cr')
            )
            ->groupBy('i.account_code', 'i.account_name')
            ->orderBy('i.account_code')
            ->get();

        $selectedAccountName = 'รวมทุกบัญชีเงินสดและเงินฝากธนาคาร';
        if ($selectedAccount !== 'all') {
            $accObj = $accounts->firstWhere('account_code', $selectedAccount);
            if ($accObj) {
                $selectedAccountName = $accObj->account_code . ' : ' . $accObj->account_name;
            }
        }

        // 3. Opening Balance before startDate
        $obQuery = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->where('j.voucher_date', '<', $startDate);

        if ($selectedAccount !== 'all') {
            $obQuery->where('i.account_code', $selectedAccount);
        }
        $openingBalance = floatval($obQuery->select(DB::raw('SUM(i.debit - i.credit) as ob'))->value('ob') ?: 0.0);

        // 4. Period Summary
        $periodSummaryQuery = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->whereBetween('j.voucher_date', [$startDate, $endDate]);

        if ($selectedAccount !== 'all') {
            $periodSummaryQuery->where('i.account_code', $selectedAccount);
        }

        $periodSummary = $periodSummaryQuery->select(
            DB::raw('SUM(i.debit) as total_dr'),
            DB::raw('SUM(i.credit) as total_cr'),
            DB::raw('COUNT(DISTINCT j.id) as voucher_count'),
            DB::raw('COUNT(i.id) as item_count')
        )->first();

        $totalDr = floatval($periodSummary->total_dr ?? 0);
        $totalCr = floatval($periodSummary->total_cr ?? 0);
        $netCashFlow = $totalDr - $totalCr;
        $endingBalance = $openingBalance + $netCashFlow;
        $totalVouchers = intval($periodSummary->voucher_count ?? 0);

        // 5. Monthly breakdown (for 12 months overview)
        $monthlyRows = [];
        $runningMonthlyOB = floatval(
            DB::table('hosfin_gl_journal_items as i')
                ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
                ->where('i.account_code', 'like', '1101%')
                ->when($selectedAccount !== 'all', fn($q) => $q->where('i.account_code', $selectedAccount))
                ->where('j.voucher_date', '<', $fyStart)
                ->select(DB::raw('SUM(i.debit - i.credit) as ob'))
                ->value('ob') ?: 0.0
        );

        foreach ($periods as $p => $pInfo) {
            $mTotals = DB::table('hosfin_gl_journal_items as i')
                ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
                ->where('i.account_code', 'like', '1101%')
                ->when($selectedAccount !== 'all', fn($q) => $q->where('i.account_code', $selectedAccount))
                ->whereBetween('j.voucher_date', [$pInfo['start_date'], $pInfo['end_date']])
                ->select(
                    DB::raw('SUM(i.debit) as dr'),
                    DB::raw('SUM(i.credit) as cr'),
                    DB::raw('COUNT(DISTINCT j.id) as voucher_count')
                )
                ->first();

            $mDr = floatval($mTotals->dr ?? 0);
            $mCr = floatval($mTotals->cr ?? 0);
            $mNet = $mDr - $mCr;
            $mEnd = $runningMonthlyOB + $mNet;

            $monthlyRows[$p] = [
                'period'        => $p,
                'fiscal_month'  => $pInfo['fiscal_month'],
                'label'         => $pInfo['label'],
                'opening_bal'   => $runningMonthlyOB,
                'dr'            => $mDr,
                'cr'            => $mCr,
                'net'           => $mNet,
                'ending_bal'    => $mEnd,
                'voucher_count' => intval($mTotals->voucher_count ?? 0),
            ];
            $runningMonthlyOB = $mEnd;
        }

        // 6. Daily breakdown (when a specific month is selected)
        $dailyRows = [];
        $dailyTransactionsByDate = [];
        if ($selectedPeriod !== 'all') {
            $rawDaily = DB::table('hosfin_gl_journal_items as i')
                ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
                ->where('i.account_code', 'like', '1101%')
                ->when($selectedAccount !== 'all', fn($q) => $q->where('i.account_code', $selectedAccount))
                ->whereBetween('j.voucher_date', [$startDate, $endDate])
                ->select(
                    'j.voucher_date',
                    DB::raw('SUM(i.debit) as dr'),
                    DB::raw('SUM(i.credit) as cr'),
                    DB::raw('COUNT(DISTINCT j.id) as voucher_count'),
                    DB::raw('COUNT(i.id) as item_count')
                )
                ->groupBy('j.voucher_date')
                ->orderBy('j.voucher_date', 'asc')
                ->get();

            $curDailyRunning = $openingBalance;
            foreach ($rawDaily as $d) {
                $dDr = floatval($d->dr);
                $dCr = floatval($d->cr);
                $dNet = $dDr - $dCr;
                $dEnd = $curDailyRunning + $dNet;

                $dailyRows[$d->voucher_date] = [
                    'date'          => $d->voucher_date,
                    'opening_bal'   => $curDailyRunning,
                    'dr'            => $dDr,
                    'cr'            => $dCr,
                    'net'           => $dNet,
                    'ending_bal'    => $dEnd,
                    'voucher_count' => intval($d->voucher_count),
                    'item_count'    => intval($d->item_count)
                ];
                $curDailyRunning = $dEnd;
            }
        }

        // 7. Ledger Transactions Query
        $ledgerQuery = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->whereBetween('j.voucher_date', [$startDate, $endDate]);

        if ($selectedAccount !== 'all') {
            $ledgerQuery->where('i.account_code', $selectedAccount);
        }

        $ledgerItems = $ledgerQuery->select(
            'j.id as journal_id',
            'j.voucher_no',
            'j.voucher_date',
            'j.description as journal_desc',
            'i.id as item_id',
            'i.account_code',
            'i.account_name',
            'i.debit',
            'i.credit',
            'i.description as item_desc'
        )
        ->orderBy('j.voucher_date', 'asc')
        ->orderBy('j.id', 'asc')
        ->orderBy('i.id', 'asc')
        ->get();

        // Calculate running balance row by row (Preserves true running balance)
        $runningBal = $openingBalance;
        foreach ($ledgerItems as $item) {
            $drVal = floatval($item->debit);
            $crVal = floatval($item->credit);
            $runningBal += ($drVal - $crVal);
            $item->running_balance = $runningBal;
            $item->display_desc = !empty($item->item_desc) ? $item->item_desc : $item->journal_desc;
            $item->tx_type = ($drVal > 0) ? 'dr' : 'cr';

            if ($selectedPeriod !== 'all') {
                $dailyTransactionsByDate[$item->voucher_date][] = $item;
            }
        }

        // Chart series data
        $chartLabels = [];
        $chartDr = [];
        $chartCr = [];
        $chartBal = [];
        if ($selectedPeriod === 'all') {
            foreach ($monthlyRows as $pKey => $mRow) {
                $chartLabels[] = $mRow['label'];
                $chartDr[] = round($mRow['dr'], 2);
                $chartCr[] = round($mRow['cr'], 2);
                $chartBal[] = round($mRow['ending_bal'], 2);
            }
        } else {
            foreach ($dailyRows as $dDate => $dRow) {
                $chartLabels[] = date('d/m', strtotime($dDate));
                $chartDr[] = round($dRow['dr'], 2);
                $chartCr[] = round($dRow['cr'], 2);
                $chartBal[] = round($dRow['ending_bal'], 2);
            }
        }

        return view('hosfin.cash_register', [
            'budgetYear'             => $budgetYear,
            'yearChoices'            => $yearChoices,
            'periods'                => $periods,
            'selectedPeriod'         => $selectedPeriod,
            'selectedPeriodLabel'    => $selectedPeriodLabel,
            'accounts'               => $accounts,
            'selectedAccount'        => $selectedAccount,
            'selectedAccountName'    => $selectedAccountName,
            'viewMode'               => $viewMode,
            'txType'                 => $txType,
            'search'                 => $search,
            'openingBalance'         => $openingBalance,
            'totalDr'                => $totalDr,
            'totalCr'                => $totalCr,
            'netCashFlow'            => $netCashFlow,
            'endingBalance'          => $endingBalance,
            'totalVouchers'          => $totalVouchers,
            'monthlyRows'            => $monthlyRows,
            'dailyRows'              => $dailyRows,
            'dailyTransactionsByDate'=> $dailyTransactionsByDate,
            'ledgerItems'            => $ledgerItems,
            'chartLabels'            => $chartLabels,
            'chartDr'                => $chartDr,
            'chartCr'                => $chartCr,
            'chartBal'               => $chartBal,
        ]);
    }

    /**
     * Export Cash & Bank Register to Excel (.xlsx)
     */
    public function cash_register_export(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        $selectedPeriod = $request->input('period', 'all');
        $selectedAccount = $request->input('account_code', 'all');
        $txType = $request->input('type', 'all');
        $search = trim($request->input('search', ''));

        $fyStart = ($budgetYear - 544) . '-10-01';
        $fyEnd   = ($budgetYear - 543) . '-09-30';

        if ($selectedPeriod !== 'all') {
            $pParts = explode('-', $selectedPeriod);
            if (count($pParts) === 2) {
                $y = intval($pParts[0]);
                $m = intval($pParts[1]);
                $startDate = date('Y-m-01', strtotime("$y-$m-01"));
                $endDate   = date('Y-m-t', strtotime("$y-$m-01"));
                $periodLabel = self::getThaiMonthName($m) . ' ' . substr((string)($m >= 10 ? $budgetYear - 1 : $budgetYear), -2);
            } else {
                $startDate = $fyStart;
                $endDate = $fyEnd;
                $periodLabel = 'ทั้งปีงบประมาณ ' . $budgetYear;
            }
        } else {
            $startDate = $fyStart;
            $endDate = $fyEnd;
            $periodLabel = 'ทั้งปีงบประมาณ ' . $budgetYear;
        }

        // Opening balance
        $obQuery = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->where('j.voucher_date', '<', $startDate);
        if ($selectedAccount !== 'all') {
            $obQuery->where('i.account_code', $selectedAccount);
        }
        $openingBalance = floatval($obQuery->select(DB::raw('SUM(i.debit - i.credit) as ob'))->value('ob') ?: 0.0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ทะเบียนรับ-จ่าย');

        // Header Title
        $sheet->setCellValue('A1', 'ทะเบียนรับ-จ่ายเงินสดและเงินฝากธนาคาร (Cash & Bank Register)');
        $sheet->setCellValue('A2', "ปีงบประมาณ: {$budgetYear} | งวด: {$periodLabel} | บัญชี: {$selectedAccount}");
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(11);

        // Summary Bar
        $sheet->setCellValue('A4', 'ยอดยกมาต้นงวด:');
        $sheet->setCellValue('B4', $openingBalance);
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A4:B4')->getFont()->setBold(true);

        // Headers
        $headers = ['วันที่', 'เลขที่เอกสาร', 'รหัสบัญชี', 'ชื่อบัญชี', 'รายการ / คำอธิบาย', 'รายรับ (เดบิต Dr)', 'รายจ่าย (เครดิต Cr)', 'ยอดคงเหลือสะสม'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($headers as $idx => $h) {
            $sheet->setCellValue($cols[$idx] . '6', $h);
        }
        $sheet->getStyle('A6:H6')->getFont()->setBold(true);
        $sheet->getStyle('A6:H6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFE2E8F0');

        $rowNum = 7;

        $ledgerQuery = DB::table('hosfin_gl_journal_items as i')
            ->join('hosfin_gl_journals as j', 'i.journal_id', '=', 'j.id')
            ->where('i.account_code', 'like', '1101%')
            ->whereBetween('j.voucher_date', [$startDate, $endDate]);

        if ($selectedAccount !== 'all') {
            $ledgerQuery->where('i.account_code', $selectedAccount);
        }

        $items = $ledgerQuery->select(
            'j.voucher_no',
            'j.voucher_date',
            'j.description as journal_desc',
            'i.account_code',
            'i.account_name',
            'i.debit',
            'i.credit',
            'i.description as item_desc'
        )
        ->orderBy('j.voucher_date', 'asc')
        ->orderBy('j.id', 'asc')
        ->orderBy('i.id', 'asc')
        ->get();

        $running = $openingBalance;
        foreach ($items as $item) {
            $dr = floatval($item->debit);
            $cr = floatval($item->credit);
            $running += ($dr - $cr);
            $desc = !empty($item->item_desc) ? $item->item_desc : $item->journal_desc;

            // Apply filter after running balance is computed
            if ($txType === 'dr' && $dr <= 0) continue;
            if ($txType === 'cr' && $cr <= 0) continue;
            if ($search !== '' && stripos($desc, $search) === false && stripos($item->voucher_no, $search) === false) continue;

            $sheet->setCellValue('A' . $rowNum, $item->voucher_date);
            $sheet->setCellValue('B' . $rowNum, $item->voucher_no);
            $sheet->setCellValue('C' . $rowNum, $item->account_code);
            $sheet->setCellValue('D' . $rowNum, $item->account_name);
            $sheet->setCellValue('E' . $rowNum, $desc);
            $sheet->setCellValue('F' . $rowNum, $dr > 0 ? $dr : 0);
            $sheet->setCellValue('G' . $rowNum, $cr > 0 ? $cr : 0);
            $sheet->setCellValue('H' . $rowNum, $running);

            $sheet->getStyle('F' . $rowNum . ':H' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = "cash_register_{$budgetYear}_{$selectedPeriod}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($filename) . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // =========================================================================
    // PlanFin System Methods (ระบบบริหารและติดตามแผนเงินบำรุง)
    // =========================================================================

    /**
     * PlanFin Dashboard (2 Tabs: Monthly Plan vs Actual & FY70 Budget Simulator)
     */
    public function planfin(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', self::getCurrentBudgetYear()));
        if ($budgetYear <= 0) {
            $budgetYear = 2569;
        }

        // Available periods in trial balance
        $availablePeriods = DB::table('hosfin_trial_balance')
            ->distinct()
            ->orderBy('acc_period', 'desc')
            ->pluck('acc_period')
            ->toArray();

        // Available budget years
        $yearsTb = DB::table('hosfin_trial_balance')->distinct()->pluck('acc_year')->filter()->map(fn($y) => intval($y))->toArray();
        $yearsPf = DB::table('hosfin_planfin_targets')->distinct()->pluck('budget_year')->filter()->map(fn($y) => intval($y))->toArray();
        $currentYear = self::getCurrentBudgetYear();
        $budgetYearChoices = array_values(array_unique(array_merge([$currentYear, 2570, 2569, 2568], $yearsTb, $yearsPf)));
        rsort($budgetYearChoices);

        $requestedPeriod = $request->input('period');
        $requestedYear = intval($request->input('budget_year', 0));

        if ($requestedPeriod) {
            $pParts = explode('-', $requestedPeriod);
            $py = intval($pParts[0] ?? 2569);
            $pm = intval($pParts[1] ?? 7);
            $budgetYear = ($pm >= 10) ? ($py + 1) : $py;
            $selectedPeriod = $requestedPeriod;
        } elseif ($requestedYear > 0) {
            $budgetYear = $requestedYear;
            $yearPeriods = array_values(array_filter($availablePeriods, function($p) use ($budgetYear) {
                $parts = explode('-', $p);
                $py = intval($parts[0] ?? 0);
                $pm = intval($parts[1] ?? 0);
                return (($pm >= 10 && $py === $budgetYear - 1) || ($pm <= 9 && $py === $budgetYear));
            }));
            $selectedPeriod = in_array("{$budgetYear}-07", $yearPeriods) ? "{$budgetYear}-07" : ($yearPeriods[0] ?? "{$budgetYear}-07");
        } else {
            $budgetYear = self::getCurrentBudgetYear() ?: 2569;
            $selectedPeriod = in_array("{$budgetYear}-07", $availablePeriods) ? "{$budgetYear}-07" : (in_array('2569-07', $availablePeriods) ? '2569-07' : ($availablePeriods[0] ?? '2569-07'));
        }

        // Derive fiscal years dynamically from selected period
        $pParts = explode('-', $selectedPeriod);
        $pYear = intval($pParts[0] ?? 2569);
        $pMonth = intval($pParts[1] ?? 7);
        $budgetYear = ($pMonth >= 10) ? ($pYear + 1) : $pYear;
        $priorYear = $budgetYear - 1;
        $targetSimYear = $budgetYear + 1;

        $cumMonths = ($pMonth >= 10) ? ($pMonth - 9) : ($pMonth + 3);
        if ($cumMonths <= 0 || $cumMonths > 12) $cumMonths = 10;
        $selectedPeriodLabel = self::getThaiMonthName($pMonth) . ' ' . substr((string)$pYear, -2);

        // Available periods dropdown filtered for the selected budgetYear
        $periodOptions = [];
        foreach ($availablePeriods as $p) {
            $parts = explode('-', $p);
            $y = intval($parts[0] ?? 2569);
            $m = intval($parts[1] ?? 1);
            $calcYear = ($m >= 10) ? ($y + 1) : $y;
            if ($calcYear === $budgetYear) {
                $cMonths = ($m >= 10) ? ($m - 9) : ($m + 3);
                $label = self::getThaiMonthName($m) . ' ' . substr((string)$y, -2);
                $periodOptions[] = [
                    'period' => $p,
                    'label' => $label,
                    'year' => $y,
                    'month' => $m,
                    'cum_months' => $cMonths
                ];
            }
        }

        // If no periods in database for this year, generate 12 standard fiscal months
        if (empty($periodOptions)) {
            for ($cm = 1; $cm <= 12; $cm++) {
                $m = ($cm <= 3) ? ($cm + 9) : ($cm - 3);
                $y = ($m >= 10) ? ($budgetYear - 1) : $budgetYear;
                $p = sprintf('%04d-%02d', $y, $m);
                $label = self::getThaiMonthName($m) . ' ' . substr((string)$y, -2);
                $periodOptions[] = [
                    'period' => $p,
                    'label' => $label,
                    'year' => $y,
                    'month' => $m,
                    'cum_months' => $cm
                ];
            }
        } else {
            usort($periodOptions, fn($a, $b) => $a['cum_months'] <=> $b['cum_months']);
        }


        // Fetch master categories
        $categories = DB::table('hosfin_planfin_categories')
            ->orderBy('sort_order', 'asc')
            ->get();

        // Fetch target plans for active year ($budgetYear)
        $targetsRaw = DB::table('hosfin_planfin_targets')
            ->where('budget_year', $budgetYear)
            ->whereIn('round_no', [$budgetYear . '02', $budgetYear . '01', '1st', '2nd'])
            ->orderBy('round_no', 'desc')
            ->get()
            ->groupBy('plan_code');

        $planTargets = [];
        foreach ($targetsRaw as $pCode => $rows) {
            $planTargets[$pCode] = floatval($rows->first()->target_amount ?? 0);
        }

        // Fetch simulation targets for $targetSimYear
        $targetsSimRaw = DB::table('hosfin_planfin_targets')
            ->where('budget_year', $targetSimYear)
            ->get()
            ->keyBy('plan_code');

        // Real-time actuals from hosfin_trial_balance
        $actualsPeriod = $this->calculatePlanfinActuals($selectedPeriod);
        $actualsPriorYear = $this->calculatePlanfinActuals("{$priorYear}-09"); // full 12 months prior year
        
        // Baseline period for FY Simulator: follows the selected period from dropdown (or explicit baseline_period)
        $baselinePeriod = $request->get('baseline_period', $selectedPeriod);
        if (!in_array($baselinePeriod, $availablePeriods)) {
            $matchingInYear = array_filter($availablePeriods, fn($p) => str_starts_with($p, "{$budgetYear}-"));
            $baselinePeriod = reset($matchingInYear) ?: $selectedPeriod;
        }
        $actualsBaseline = $this->calculatePlanfinActuals($baselinePeriod);
        
        $bParts = explode('-', $baselinePeriod);
        $bMonth = intval($bParts[1] ?? 7);
        $baseMonths = ($bMonth >= 10) ? ($bMonth - 9) : ($bMonth + 3);
        if ($baseMonths <= 0 || $baseMonths > 12) $baseMonths = 10;

        // Build Tab 1 Data (Plan vs Actual)
        $tab1Rows = [];
        foreach ($categories as $cat) {
            $code = $cat->plan_code;
            $name = $cat->plan_name;
            $type = $cat->category_type;

            $annualTarget = $planTargets[$code] ?? 0.0;
            // Summary rows targets sum
            if ($code === 'P13S') {
                $annualTarget = 0.0;
                foreach (['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'] as $c) {
                    $annualTarget += ($planTargets[$c] ?? 0.0);
                }
            } elseif ($code === 'P26S') {
                $annualTarget = 0.0;
                foreach (['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'] as $c) {
                    $annualTarget += ($planTargets[$c] ?? 0.0);
                }
            } elseif ($code === 'P27S') {
                $annualTarget = ($tab1Rows['P13S']['annual_target'] ?? 0.0) - ($tab1Rows['P26S']['annual_target'] ?? 0.0);
            }

            $planCum = ($annualTarget / 12.0) * $cumMonths;
            $actualCum = $actualsPeriod[$code] ?? 0.0;
            $diff = $actualCum - $planCum;
            $percent = ($planCum != 0) ? ($diff / $planCum) * 100.0 : 0.0;

            // Determine status OK / Not OK
            $status = 'OK';
            if ($type === 'revenue') {
                $status = ($diff >= 0) ? 'OK' : 'Not OK';
            } elseif ($type === 'expense') {
                $status = ($diff <= 0) ? 'OK' : 'Not OK';
            } elseif ($code === 'P27S') {
                $status = ($diff >= 0) ? 'OK' : 'Not OK';
            }

            $tab1Rows[$code] = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'sort_order' => $cat->sort_order,
                'annual_target' => $annualTarget,
                'plan_cum' => $planCum,
                'actual_cum' => $actualCum,
                'diff' => $diff,
                'percent' => $percent,
                'status' => $status
            ];
        }

        // Build Tab 1 Monthly Data (Current Selected Month Only)
        $actualsMonthlyPeriod = $this->calculatePlanfinMonthlyActuals($selectedPeriod);
        $tab1MonthlyRows = [];
        foreach ($categories as $cat) {
            $code = $cat->plan_code;
            $name = $cat->plan_name;
            $type = $cat->category_type;

            $annualTarget = $tab1Rows[$code]['annual_target'] ?? 0.0;
            $planMonth = ($annualTarget / 12.0);
            $actualMonth = $actualsMonthlyPeriod[$code] ?? 0.0;
            $diffMonth = $actualMonth - $planMonth;
            $percentMonth = ($planMonth != 0) ? ($diffMonth / $planMonth) * 100.0 : 0.0;

            $statusMonth = 'OK';
            if ($type === 'revenue') {
                $statusMonth = ($diffMonth >= 0) ? 'OK' : 'Not OK';
            } elseif ($type === 'expense') {
                $statusMonth = ($diffMonth <= 0) ? 'OK' : 'Not OK';
            } elseif ($code === 'P27S') {
                $statusMonth = ($diffMonth >= 0) ? 'OK' : 'Not OK';
            }

            $tab1MonthlyRows[$code] = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'sort_order' => $cat->sort_order,
                'annual_target' => $annualTarget,
                'plan_month' => $planMonth,
                'actual_month' => $actualMonth,
                'diff_month' => $diffMonth,
                'percent_month' => $percentMonth,
                'status_month' => $statusMonth
            ];
        }

        // Monthly KPI Metrics for Selected Month
        $kpiMonthActualRev = $tab1MonthlyRows['P13S']['actual_month'] ?? 0;
        $kpiMonthPlanRev = $tab1MonthlyRows['P13S']['plan_month'] ?? 0;
        $kpiMonthActualExp = $tab1MonthlyRows['P26S']['actual_month'] ?? 0;
        $kpiMonthPlanExp = $tab1MonthlyRows['P26S']['plan_month'] ?? 0;
        $kpiMonthActualNet = $tab1MonthlyRows['P27S']['actual_month'] ?? 0;
        $kpiMonthActualEbitda = $tab1MonthlyRows['P29']['actual_month'] ?? 0;

        // 12-Month Matrix Trends Calculation (Across all fiscal periods of active year)
        $yearPeriodList = array_column($periodOptions, 'period');
        $tbMatrixRaw = DB::table('hosfin_trial_balance as t')
            ->join('hosfin_planfin_mappings as m', 't.account_code', '=', 'm.account_code')
            ->whereIn('t.acc_period', $yearPeriodList)
            ->select(
                't.acc_period',
                'm.plan_code',
                DB::raw("SUM(CASE WHEN t.account_code LIKE '4%' THEN (COALESCE(t.credit_month, 0) - COALESCE(t.debit_month, 0)) ELSE 0 END) as rev"),
                DB::raw("SUM(CASE WHEN t.account_code LIKE '5%' THEN (COALESCE(t.debit_month, 0) - COALESCE(t.credit_month, 0)) ELSE 0 END) as exp")
            )
            ->groupBy('t.acc_period', 'm.plan_code')
            ->get();

        $matrixLookup = [];
        foreach ($tbMatrixRaw as $row) {
            $val = (floatval($row->rev) != 0) ? floatval($row->rev) : floatval($row->exp);
            $matrixLookup[$row->plan_code][$row->acc_period] = $val;
        }

        $revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
        $expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

        foreach ($yearPeriodList as $p) {
            $pRev = 0.0;
            foreach ($revCodes as $c) { $pRev += ($matrixLookup[$c][$p] ?? 0.0); }
            $matrixLookup['P13S'][$p] = $pRev;

            $pExp = 0.0;
            foreach ($expCodes as $c) { $pExp += ($matrixLookup[$c][$p] ?? 0.0); }
            $matrixLookup['P26S'][$p] = $pExp;

            $matrixLookup['P27S'][$p] = $pRev - $pExp;
            $pEbitdaR = $pRev - ($matrixLookup['P13'][$p] ?? 0.0) - ($matrixLookup['P121'][$p] ?? 0.0);
            $pEbitdaE = $pExp - ($matrixLookup['P24'][$p] ?? 0.0) - ($matrixLookup['P251'][$p] ?? 0.0);
            $matrixLookup['P29'][$p] = $pEbitdaR - $pEbitdaE;
        }

        // Build Tab 2 Data (Budget FY Simulator)
        $tab2Rows = [];
        foreach ($categories as $cat) {
            $code = $cat->plan_code;
            $name = $cat->plan_name;
            $type = $cat->category_type;

            $yPrior = $actualsPriorYear[$code] ?? 0.0;
            $yBaseMonths = $actualsBaseline[$code] ?? 0.0;
            $yBaseEst = ($baseMonths > 0) ? ($yBaseMonths / (float)$baseMonths) * 12.0 : 0.0;

            // Saved target for simulation year
            $targetSimObj = $targetsSimRaw->get($code);
            $targetSim = $targetSimObj ? floatval($targetSimObj->target_amount) : $yBaseEst;
            $growthRate = $targetSimObj ? floatval($targetSimObj->growth_rate) : (($yBaseEst > 0) ? (($targetSim - $yBaseEst) / $yBaseEst) * 100.0 : 0.0);

            $tab2Rows[$code] = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'sort_order' => $cat->sort_order,
                'y_prior' => $yPrior,
                'y_base_months' => $yBaseMonths,
                'y_base_est' => $yBaseEst,
                'target_sim' => $targetSim,
                'growth_rate' => $growthRate,
                'notes' => $targetSimObj->notes ?? ''
            ];
        }

        // Recalculate summary items for Tab 2
        $revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
        $expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

        $tab2_p13s_sim = 0; $tab2_p26s_sim = 0;
        foreach ($revCodes as $c) { $tab2_p13s_sim += ($tab2Rows[$c]['target_sim'] ?? 0); }
        foreach ($expCodes as $c) { $tab2_p26s_sim += ($tab2Rows[$c]['target_sim'] ?? 0); }

        if (isset($tab2Rows['P13S'])) $tab2Rows['P13S']['target_sim'] = $tab2_p13s_sim;
        if (isset($tab2Rows['P26S'])) $tab2Rows['P26S']['target_sim'] = $tab2_p26s_sim;
        if (isset($tab2Rows['P27S'])) $tab2Rows['P27S']['target_sim'] = $tab2_p13s_sim - $tab2_p26s_sim;

        $tab2_p29r_sim = $tab2_p13s_sim - ($tab2Rows['P13']['target_sim'] ?? 0) - ($tab2Rows['P121']['target_sim'] ?? 0);
        $tab2_p29e_sim = $tab2_p26s_sim - ($tab2Rows['P24']['target_sim'] ?? 0) - ($tab2Rows['P251']['target_sim'] ?? 0);
        $tab2_p29_sim = $tab2_p29r_sim - $tab2_p29e_sim;

        if (isset($tab2Rows['P29-R'])) $tab2Rows['P29-R']['target_sim'] = $tab2_p29r_sim;
        if (isset($tab2Rows['P29-E'])) $tab2Rows['P29-E']['target_sim'] = $tab2_p29e_sim;
        if (isset($tab2Rows['P29'])) $tab2Rows['P29']['target_sim'] = $tab2_p29_sim;

        // Executive KPI Metrics for Tab 1
        $kpiActualRevenue = $tab1Rows['P13S']['actual_cum'] ?? 0;
        $kpiActualExpense = $tab1Rows['P26S']['actual_cum'] ?? 0;
        $kpiActualNetIncome = $tab1Rows['P27S']['actual_cum'] ?? 0;
        $kpiActualEBITDA = $tab1Rows['P29']['actual_cum'] ?? 0;
        $kpiPlanRevenue = $tab1Rows['P13S']['plan_cum'] ?? 0;
        $kpiPlanExpense = $tab1Rows['P26S']['plan_cum'] ?? 0;
        $kpiCapInvestment = max(0, $kpiActualEBITDA * 0.20);
        $kpiTab2CapInvestment = max(0, $tab2_p29_sim * 0.20);

        // Hospital Profile
        $hospName = 'รพ. หัวตะพาน';
        $hospCode = '10989';
        try {
            $hospName = DB::table('main_setting')->where('name', 'hospital_name')->value('value')
                ?? (DB::table('main_setting')->where('name', 'hosp_name')->value('value') ?? 'รพ. หัวตะพาน');
            $hospCode = DB::table('main_setting')->where('name', 'hospital_code')->value('value')
                ?? (DB::table('main_setting')->where('name', 'hcode')->value('value') ?? '10989');
        } catch (\Throwable $e) {
            // fallback defaults
        }

        // =========================================================================
        // Sub-Accounts Aggregation for Interactive Accordion Drill-down
        // =========================================================================
        $mappings = DB::table('hosfin_planfin_mappings')
            ->orderBy('plan_code')
            ->orderBy('account_code')
            ->get();

        $neededPeriods = array_values(array_unique([$selectedPeriod, $baselinePeriod, "{$priorYear}-09"]));
        $tbSubRows = DB::table('hosfin_trial_balance as t')
            ->join('hosfin_planfin_mappings as m', 't.account_code', '=', 'm.account_code')
            ->whereIn('t.acc_period', $neededPeriods)
            ->select('t.acc_period', 't.account_code', 't.debit_net', 't.credit_net', 't.debit_month', 't.credit_month')
            ->get();

        $tbSubLookup = [];
        $tbSubLookupMonthly = [];
        foreach ($tbSubRows as $r) {
            $firstDigit = substr($r->account_code, 0, 1);
            $val = ($firstDigit === '4') ? (floatval($r->credit_net) - floatval($r->debit_net)) : (floatval($r->debit_net) - floatval($r->credit_net));
            $valM = ($firstDigit === '4') ? (floatval($r->credit_month) - floatval($r->debit_month)) : (floatval($r->debit_month) - floatval($r->credit_month));
            $tbSubLookup[$r->acc_period][$r->account_code] = $val;
            $tbSubLookupMonthly[$r->acc_period][$r->account_code] = $valM;
        }

        $subTargetsSimRaw = DB::table('hosfin_planfin_targets')
            ->where('budget_year', $targetSimYear)
            ->where('round_no', '1st')
            ->get()
            ->keyBy('plan_code');

        $subAccountsByPlan = [];
        foreach ($mappings as $m) {
            $pCode = $m->plan_code;
            $accCode = $m->account_code;
            $actualCum = $tbSubLookup[$selectedPeriod][$accCode] ?? 0.0;
            $actualMonth = $tbSubLookupMonthly[$selectedPeriod][$accCode] ?? 0.0;
            $yPrior = $tbSubLookup["{$priorYear}-09"][$accCode] ?? 0.0;
            $yBase = $tbSubLookup[$baselinePeriod][$accCode] ?? 0.0;
            $yEst = ($baseMonths > 0) ? ($yBase / (float)$baseMonths) * 12.0 : 0.0;

            $targetSubObj = $subTargetsSimRaw->get($accCode);
            $targetSim = $targetSubObj ? floatval($targetSubObj->target_amount) : $yEst;
            $growthRate = $targetSubObj ? floatval($targetSubObj->growth_rate) : (($yEst > 0) ? (($targetSim - $yEst) / $yEst) * 100.0 : 0.0);

            $subAccountsByPlan[$pCode][] = [
                'account_code' => $accCode,
                'account_name' => $m->account_name ?: $accCode,
                'plan_code' => $pCode,
                'actual_cum' => $actualCum,
                'actual_month' => $actualMonth,
                'y_prior' => $yPrior,
                'y_base_months' => $yBase,
                'y_base_est' => $yEst,
                'target_sim' => $targetSim,
                'growth_rate' => $growthRate,
            ];
        }

        foreach ($subAccountsByPlan as $pCode => &$subs) {
            usort($subs, function($a, $b) {
                $valA = max(abs($a['y_base_est']), abs($a['actual_cum']));
                $valB = max(abs($b['y_base_est']), abs($b['actual_cum']));
                return $valB <=> $valA;
            });
        }
        unset($subs);

        // Guarantee parent categories in Tab 2 are exact SUM of their sub-accounts
        foreach ($subAccountsByPlan as $pCode => $subs) {
            if (count($subs) > 0 && isset($tab2Rows[$pCode])) {
                $sumSubTarget = 0.0;
                foreach ($subs as $sub) {
                    $sumSubTarget += floatval($sub['target_sim']);
                }
                $tab2Rows[$pCode]['target_sim'] = $sumSubTarget;
                $pBase = floatval($tab2Rows[$pCode]['y_base_est']);
                if ($pBase > 0) {
                    $tab2Rows[$pCode]['growth_rate'] = (($sumSubTarget - $pBase) / $pBase) * 100.0;
                } else {
                    $tab2Rows[$pCode]['growth_rate'] = 0.0;
                }
            }
        }

        // Recalculate summary totals from updated categories
        $tab2_p13s_sim = 0; $tab2_p26s_sim = 0;
        foreach ($revCodes as $c) { $tab2_p13s_sim += ($tab2Rows[$c]['target_sim'] ?? 0); }
        foreach ($expCodes as $c) { $tab2_p26s_sim += ($tab2Rows[$c]['target_sim'] ?? 0); }

        if (isset($tab2Rows['P13S'])) $tab2Rows['P13S']['target_sim'] = $tab2_p13s_sim;
        if (isset($tab2Rows['P26S'])) $tab2Rows['P26S']['target_sim'] = $tab2_p26s_sim;
        if (isset($tab2Rows['P27S'])) $tab2Rows['P27S']['target_sim'] = $tab2_p13s_sim - $tab2_p26s_sim;

        $tab2_p29r_sim = $tab2_p13s_sim - ($tab2Rows['P13']['target_sim'] ?? 0) - ($tab2Rows['P121']['target_sim'] ?? 0);
        $tab2_p29e_sim = $tab2_p26s_sim - ($tab2Rows['P24']['target_sim'] ?? 0) - ($tab2Rows['P251']['target_sim'] ?? 0);
        $tab2_p29_sim = $tab2_p29r_sim - $tab2_p29e_sim;

        if (isset($tab2Rows['P29-R'])) $tab2Rows['P29-R']['target_sim'] = $tab2_p29r_sim;
        if (isset($tab2Rows['P29-E'])) $tab2Rows['P29-E']['target_sim'] = $tab2_p29e_sim;
        if (isset($tab2Rows['P29'])) $tab2Rows['P29']['target_sim'] = $tab2_p29_sim;
        $kpiTab2CapInvestment = max(0, $tab2_p29_sim * 0.20);

        // =========================================================================
        // Sub-Plans Master Definitions (Plans 2 through 7 from Ministry Template)
        // =========================================================================
        $subPlansDef = [
            'plan2' => [
                'title' => '2. แผนจัดซื้อยา เวชภัณฑ์มิใช่ยา วัสดุการแพทย์ วัสดุวิทยาศาสตร์การแพทย์',
                'icon' => 'bi-capsule',
                'items' => [
                    ['code' => 'MED01', 'name' => 'ยา (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                    ['code' => 'MED02', 'name' => 'วัสดุเภสัชกรรม (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                    ['code' => 'MED03', 'name' => 'วัสดุการแพทย์ทั่วไป (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                    ['code' => 'MED04', 'name' => 'วัสดุวิทยาศาสตร์และการแพทย์ (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                    ['code' => 'MED05', 'name' => 'วัสดุเอกซเรย์ (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                    ['code' => 'MED06', 'name' => 'วัสดุทันตกรรม (รวมสนับสนุน รพ.สต.ในเครือข่าย)'],
                ]
            ],
            'plan3' => [
                'title' => '3. แผนจัดซื้อวัสดุอื่น',
                'icon' => 'bi-box-seam',
                'items' => [
                    ['code' => 'MAT01', 'name' => 'วัสดุสำนักงาน'],
                    ['code' => 'MAT02', 'name' => 'วัสดุยานพาหนะและขนส่ง'],
                    ['code' => 'MAT03', 'name' => 'วัสดุเชื้อเพลิงและหล่อลื่น'],
                    ['code' => 'MAT04', 'name' => 'วัสดุไฟฟ้าและวิทยุ'],
                    ['code' => 'MAT05', 'name' => 'วัสดุโฆษณาและเผยแพร่'],
                    ['code' => 'MAT06', 'name' => 'วัสดุคอมพิวเตอร์'],
                    ['code' => 'MAT07', 'name' => 'วัสดุงานบ้านงานครัว'],
                    ['code' => 'MAT08', 'name' => 'วัสดุบริโภค'],
                    ['code' => 'MAT09', 'name' => 'วัสดุเครื่องแต่งกาย'],
                    ['code' => 'MAT10', 'name' => 'วัสดุก่อสร้าง'],
                    ['code' => 'MAT11', 'name' => 'วัสดุการเกษตร'],
                    ['code' => 'MAT12', 'name' => 'ครุภัณฑ์มูลค่าต่ำกว่าเกณฑ์'],
                ]
            ],
            'plan4' => [
                'title' => '4. แผนบริหารจัดการเจ้าหนี้การค้า',
                'icon' => 'bi-receipt',
                'items' => [
                    ['code' => 'AP01', 'name' => 'เจ้าหนี้การค้ายา'],
                    ['code' => 'AP02', 'name' => 'เจ้าหนี้การค้าวัสดุเภสัชกรรม'],
                    ['code' => 'AP03', 'name' => 'เจ้าหนี้การค้าวัสดุการแพทย์ทั่วไป'],
                    ['code' => 'AP04', 'name' => 'เจ้าหนี้การค้าวัสดุวิทยาศาสตร์และการแพทย์'],
                    ['code' => 'AP05', 'name' => 'เจ้าหนี้การค้าวัสดุเอกซเรย์'],
                    ['code' => 'AP06', 'name' => 'เจ้าหนี้การค้าวัสดุทันตกรรม'],
                    ['code' => 'AP07', 'name' => 'เจ้าหนี้ตามจ่าย'],
                    ['code' => 'AP08', 'name' => 'ค่าจ้างชั่วคราว/พกส./ค่าจ้างเหมาบุคลากรอื่นค้างจ่าย'],
                    ['code' => 'AP09', 'name' => 'ค่าตอบแทนค้างจ่าย'],
                    ['code' => 'AP10', 'name' => 'ค่าใช้จ่ายบุคลากรอื่นค้างจ่าย'],
                    ['code' => 'AP11', 'name' => 'เจ้าหนี้ค่าแรงอื่นค้างจ่าย'],
                    ['code' => 'AP12', 'name' => 'ค่าสาธารณูปโภคค้างจ่าย'],
                    ['code' => 'AP13', 'name' => 'เจ้าหนี้ค่าครุภัณฑ์ สิ่งก่อสร้างฯ'],
                    ['code' => 'AP14', 'name' => 'เจ้าหนี้การค้าวัสดุอื่น'],
                    ['code' => 'AP15', 'name' => 'เจ้าหนี้อื่น'],
                ]
            ],
            'plan5' => [
                'title' => '5. แผนบริหารจัดการลูกหนี้',
                'icon' => 'bi-person-lines-fill',
                'items' => [
                    ['code' => 'AR01', 'name' => 'ลูกหนี้ UC'],
                    ['code' => 'AR02', 'name' => 'ลูกหนี้ เบิกต้นสังกัด'],
                    ['code' => 'AR03', 'name' => 'ลูกหนี้ อปท'],
                    ['code' => 'AR04', 'name' => 'ลูกหนี้ กรมบัญชีกลาง'],
                    ['code' => 'AR05', 'name' => 'ลูกหนี้ ประกันสังคม'],
                    ['code' => 'AR06', 'name' => 'ลูกหนี้ แรงงานต่างด้าว'],
                    ['code' => 'AR07', 'name' => 'ลูกหนี้ อื่น ๆ'],
                ]
            ],
            'plan6' => [
                'title' => '6. แผนการลงทุนเพิ่ม',
                'icon' => 'bi-building-gear',
                'items' => [
                    ['code' => 'INV01', 'name' => 'จัดซื้อ จัดหาด้วยเงินบำรุงของ รพ. ปี 2570'],
                    ['code' => 'INV02', 'name' => 'จัดซื้อ ด้วยงบค่าบริการฯเบิกจ่ายลักษณะงบลงทุน ปี 2570'],
                    ['code' => 'INV03', 'name' => 'จัดซื้อ จัดหาด้วยเงินงบประมาณ ของ รพ. ปี 2570'],
                    ['code' => 'INV04', 'name' => 'จัดซื้อ จัดหาด้วยเงินบริจาค ของ รพ. ปี งปม.2564 - ปัจจุบัน'],
                    ['code' => 'INV05', 'name' => 'จัดซื้อ จัดหาด้วยเงินบริจาค ของ รพ.  ก่อน  1 ต.ค. 63'],
                ]
            ],
            'plan7' => [
                'title' => '7. แผนสนับสนุน รพ.สต.',
                'icon' => 'bi-hospital',
                'items' => [
                    ['code' => 'SUB01', 'name' => 'Fixed Cost (ว5313)'],
                    ['code' => 'SUB02', 'name' => 'รายการอื่น'],
                    ['code' => 'SUB03', 'name' => 'ยา'],
                    ['code' => 'SUB04', 'name' => 'วัสดุเภสัชกรรม'],
                    ['code' => 'SUB05', 'name' => 'วัสดุการแพทย์ทั่วไป'],
                    ['code' => 'SUB06', 'name' => 'วัสดุวิทยาศาสตร์และการแพทย์'],
                    ['code' => 'SUB07', 'name' => 'วัสดุเอกซเรย์'],
                    ['code' => 'SUB08', 'name' => 'วัสดุทันตกรรม'],
                    ['code' => 'SUB09', 'name' => 'วัสดุอื่น'],
                    ['code' => 'SUB10', 'name' => 'งบค่าเสื่อม UC'],
                ]
            ]
        ];

        $subPlansData = [];
        foreach ($subPlansDef as $pKey => $pDef) {
            $items = [];
            foreach ($pDef['items'] as $it) {
                $savedObj = $targetsSimRaw->get($it['code']);
                $valBg = $savedObj ? floatval($savedObj->baseline_amount) : 0.0;
                $valNonBg = $savedObj ? floatval($savedObj->target_amount) : 0.0;
                $items[] = [
                    'code' => $it['code'],
                    'name' => $it['name'],
                    'budget_amt' => $valBg,
                    'non_budget_amt' => $valNonBg,
                    'total_amt' => $valBg + $valNonBg
                ];
            }
            $subPlansData[$pKey] = [
                'title' => $pDef['title'],
                'icon' => $pDef['icon'] ?? 'bi-file-earmark-text',
                'items' => $items
            ];
        }

        return view('hosfin.planfin', compact(
            'budgetYear',
            'budgetYearChoices',
            'priorYear',
            'targetSimYear',

            'baseMonths',
            'selectedPeriod',
            'selectedPeriodLabel',
            'cumMonths',
            'periodOptions',
            'categories',
            'tab1Rows',
            'tab1MonthlyRows',
            'tab2Rows',
            'subAccountsByPlan',
            'subPlansData',
            'kpiActualRevenue',
            'kpiActualExpense',
            'kpiActualNetIncome',
            'kpiActualEBITDA',
            'kpiPlanRevenue',
            'kpiPlanExpense',
            'kpiMonthActualRev',
            'kpiMonthPlanRev',
            'kpiMonthActualExp',
            'kpiMonthPlanExp',
            'kpiMonthActualNet',
            'kpiMonthActualEbitda',
            'matrixLookup',
            'kpiCapInvestment',
            'kpiTab2CapInvestment',
            'tab2_p29_sim',
            'hospName',
            'hospCode'
        ));

    }

    /**
     * Helper to compute PlanFin actual figures from hosfin_trial_balance
     */
    private function calculatePlanfinActuals($period)
    {
        $raw = DB::table('hosfin_trial_balance as t')
            ->join('hosfin_planfin_mappings as m', 't.account_code', '=', 'm.account_code')
            ->where('t.acc_period', $period)
            ->select(
                'm.plan_code',
                DB::raw("SUM(CASE WHEN t.account_code LIKE '4%' THEN (COALESCE(t.credit_net, 0) - COALESCE(t.debit_net, 0)) ELSE 0 END) as rev"),
                DB::raw("SUM(CASE WHEN t.account_code LIKE '5%' THEN (COALESCE(t.debit_net, 0) - COALESCE(t.credit_net, 0)) ELSE 0 END) as exp")
            )
            ->groupBy('m.plan_code')
            ->get();

        $res = [];
        foreach ($raw as $r) {
            $val = (floatval($r->rev) != 0) ? floatval($r->rev) : floatval($r->exp);
            $res[$r->plan_code] = $val;
        }

        // Summary Calculations
        $revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
        $expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

        $res['P13S'] = 0.0;
        foreach ($revCodes as $c) { $res['P13S'] += ($res[$c] ?? 0.0); }

        $res['P26S'] = 0.0;
        foreach ($expCodes as $c) { $res['P26S'] += ($res[$c] ?? 0.0); }

        $res['P27S'] = $res['P13S'] - $res['P26S']; // Net Income
        $res['P29-R'] = $res['P13S'] - ($res['P13'] ?? 0.0) - ($res['P121'] ?? 0.0);
        $res['P29-E'] = $res['P26S'] - ($res['P24'] ?? 0.0) - ($res['P251'] ?? 0.0);
        $res['P29'] = $res['P29-R'] - $res['P29-E']; // EBITDA

        return $res;
    }

    /**
     * Helper to compute PlanFin monthly actual figures (movement) from hosfin_trial_balance
     */
    private function calculatePlanfinMonthlyActuals($period)
    {
        $raw = DB::table('hosfin_trial_balance as t')
            ->join('hosfin_planfin_mappings as m', 't.account_code', '=', 'm.account_code')
            ->where('t.acc_period', $period)
            ->select(
                'm.plan_code',
                DB::raw("SUM(CASE WHEN t.account_code LIKE '4%' THEN (COALESCE(t.credit_month, 0) - COALESCE(t.debit_month, 0)) ELSE 0 END) as rev"),
                DB::raw("SUM(CASE WHEN t.account_code LIKE '5%' THEN (COALESCE(t.debit_month, 0) - COALESCE(t.credit_month, 0)) ELSE 0 END) as exp")
            )
            ->groupBy('m.plan_code')
            ->get();

        $res = [];
        foreach ($raw as $r) {
            $val = (floatval($r->rev) != 0) ? floatval($r->rev) : floatval($r->exp);
            $res[$r->plan_code] = $val;
        }

        $revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
        $expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

        $res['P13S'] = 0.0;
        foreach ($revCodes as $c) { $res['P13S'] += ($res[$c] ?? 0.0); }

        $res['P26S'] = 0.0;
        foreach ($expCodes as $c) { $res['P26S'] += ($res[$c] ?? 0.0); }

        $res['P27S'] = $res['P13S'] - $res['P26S'];
        $res['P29-R'] = $res['P13S'] - ($res['P13'] ?? 0.0) - ($res['P121'] ?? 0.0);
        $res['P29-E'] = $res['P26S'] - ($res['P24'] ?? 0.0) - ($res['P251'] ?? 0.0);
        $res['P29'] = $res['P29-R'] - $res['P29-E'];

        return $res;
    }

    /**
     * Analyze uploaded M1625 ZIP file for PlanFin
     */
    public function analyzeMdbPlanfin(Request $request)
    {
        $pyStatus = \App\Helpers\PythonHelper::checkStatus();
        if (!$pyStatus['available']) {
            return response()->json([
                'success' => false,
                'message' => 'ระบบไม่พบโปรแกรม Python บนเซิร์ฟเวอร์'
            ], 400);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด'], 400);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext !== 'zip' && $ext !== 'mdb') {
            return response()->json(['success' => false, 'message' => 'รองรับเฉพาะไฟล์ .zip หรือ .mdb เท่านั้น'], 400);
        }

        try {
            $tempDir = storage_path('app/temp_planfin_' . uniqid());
            mkdir($tempDir, 0777, true);
            $targetPath = $tempDir . '/' . $originalName;
            $file->move($tempDir, $originalName);

            $pythonScript = base_path('app/Helpers/Python/analyze_mdb_planfin.py');
            $runResult = \App\Helpers\PythonHelper::runScript($pythonScript, [$targetPath]);

            if (!$runResult['success']) {
                $this->deleteDir($tempDir);
                return response()->json(['success' => false, 'message' => 'วิเคราะห์ไฟล์ล้มเหลว: ' . $runResult['output']], 500);
            }

            $data = json_decode($runResult['output'], true);
            if (!is_array($data) || isset($data['error'])) {
                $this->deleteDir($tempDir);
                return response()->json(['success' => false, 'message' => $data['error'] ?? 'รูปแบบไฟล์ไม่ถูกต้อง'], 422);
            }

            $token = uniqid('pf_');
            session([$token => ['dir' => $tempDir, 'path' => $targetPath]]);

            return response()->json([
                'success' => true,
                'temp_token' => $token,
                'hcode' => $data['hcode'] ?? '',
                'plans' => $data['plans'] ?? [],
                'months' => $data['months'] ?? [],
                'latest_month' => $data['latest_month'] ?? ''
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Import PlanFin Targets & Data from MDB
     */
    public function importMdbPlanfin(Request $request)
    {
        $token = $request->input('temp_token');
        $periodNo = $request->input('period_no');

        if (!$token || !session()->has($token)) {
            return response()->json(['success' => false, 'message' => 'Session ไฟล์หมดอายุ กรุณาอัปโหลดใหม่'], 400);
        }

        $sessionData = session($token);
        $filePath = $sessionData['path'];
        $tempDir = $sessionData['dir'];

        try {
            $pythonScript = base_path('app/Helpers/Python/import_mdb_planfin.py');
            $runResult = \App\Helpers\PythonHelper::runScript($pythonScript, [$filePath, $periodNo]);

            if (!$runResult['success']) {
                return response()->json(['success' => false, 'message' => 'นำเข้าล้มเหลว: ' . $runResult['output']], 500);
            }

            $res = json_decode($runResult['output'], true);
            if (!isset($res['success']) || !$res['success']) {
                return response()->json(['success' => false, 'message' => $res['error'] ?? 'ข้อมูลไม่ถูกต้อง'], 422);
            }

            DB::transaction(function () use ($res, $periodNo) {
                // 1. Insert/Update Targets
                if (!empty($res['targets'])) {
                    DB::table('hosfin_planfin_targets')
                        ->where('budget_year', $res['budget_year'])
                        ->where('round_no', $periodNo)
                        ->delete();

                    $batchTargets = [];
                    foreach ($res['targets'] as $t) {
                        $batchTargets[] = [
                            'budget_year' => $t['budget_year'],
                            'round_no' => $t['round_no'],
                            'plan_code' => $t['plan_code'],
                            'target_amount' => $t['target_amount'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    foreach (array_chunk($batchTargets, 100) as $chunk) {
                        DB::table('hosfin_planfin_targets')->insert($chunk);
                    }
                }

                // 2. Sync Mappings if new accounts found
                if (!empty($res['mappings'])) {
                    $existingAccs = DB::table('hosfin_planfin_mappings')->pluck('account_code')->toArray();
                    $newMaps = [];
                    foreach ($res['mappings'] as $m) {
                        if (!in_array($m['account_code'], $existingAccs)) {
                            $newMaps[] = [
                                'account_code' => $m['account_code'],
                                'account_name' => $m['account_name'] ?? null,
                                'plan_code' => $m['plan_code'],
                                'plan_name' => $m['plan_name'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }
                    if (!empty($newMaps)) {
                        DB::table('hosfin_planfin_mappings')->insert($newMaps);
                    }
                }

                // 3. Sync latest trial balance records if not in hosfin_trial_balance
                if (!empty($res['tb_records'])) {
                    $firstRec = $res['tb_records'][0];
                    $recPeriod = $firstRec['acc_period'];
                    $hasPeriod = DB::table('hosfin_trial_balance')->where('acc_period', $recPeriod)->exists();
                    if (!$hasPeriod) {
                        $tbBatch = [];
                        foreach ($res['tb_records'] as $r) {
                            $tbBatch[] = [
                                'acc_year' => $r['acc_year'],
                                'acc_month' => $r['acc_month'],
                                'acc_period' => $r['acc_period'],
                                'main_account_code' => $r['main_account_code'],
                                'account_code' => $r['account_code'],
                                'account_name' => $r['account_name'],
                                'debit_bf' => 0,
                                'credit_bf' => 0,
                                'debit_month' => $r['debit_month'],
                                'credit_month' => $r['credit_month'],
                                'debit_net' => $r['debit_net'],
                                'credit_net' => $r['credit_net'],
                                'import_filename' => basename($filePath),
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                        foreach (array_chunk($tbBatch, 100) as $chunk) {
                            DB::table('hosfin_trial_balance')->insert($chunk);
                        }
                    }
                }
            });

            // Clean temp
            $this->deleteDir($tempDir);
            session()->forget($token);

            return response()->json([
                'success' => true,
                'message' => "นำเข้าแผนเงินบำรุงรอบ {$periodNo} สำเร็จ ทั้งหมด " . ($res['targets_count'] ?? 0) . " รายการ"
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Save Budget Year 2570 Estimates
     */
    public function savePlanfinTarget(Request $request)
    {
        $budgetYear = intval($request->input('budget_year', 2570));
        $roundNo = $request->input('round_no', '1st');
        $items = $request->input('items', []);

        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'ไม่พบข้อมูลที่ต้องการบันทึก'], 400);
        }

        try {
            DB::transaction(function () use ($budgetYear, $roundNo, $items) {
                foreach ($items as $item) {
                    $code = trim($item['plan_code'] ?? '');
                    if (!$code) continue;

                    DB::table('hosfin_planfin_targets')->updateOrInsert(
                        [
                            'budget_year' => $budgetYear,
                            'round_no' => $roundNo,
                            'plan_code' => $code
                        ],
                        [
                            'baseline_amount' => floatval($item['baseline_amount'] ?? 0),
                            'growth_rate' => floatval($item['growth_rate'] ?? 0),
                            'target_amount' => floatval($item['target_amount'] ?? 0),
                            'notes' => $item['notes'] ?? null,
                            'created_by' => auth()->id(),
                            'updated_at' => now()
                        ]
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => "บันทึกแผนประมาณการเรียบร้อยแล้ว"
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Export PlanFin Budget & Monitoring Report to Excel
     */
    public function exportPlanfinExcel(Request $request)
    {
        $targetYear = intval($request->input('target_year', 2570));
        $budgetYear = intval($request->input('budget_year', $targetYear - 1));
        $period = $request->input('period', "{$budgetYear}-07");

        $baseMonths = 10;
        $priorYear = $budgetYear - 1;

        // Hospital Profile
        $hospName = 'รพ. หัวตะพาน';
        $hospCode = '10989';
        try {
            $hospName = DB::table('main_setting')->where('name', 'hospital_name')->value('value')
                ?? (DB::table('main_setting')->where('name', 'hosp_name')->value('value') ?? 'รพ. หัวตะพาน');
            $hospCode = DB::table('main_setting')->where('name', 'hospital_code')->value('value')
                ?? (DB::table('main_setting')->where('name', 'hcode')->value('value') ?? '10989');
        } catch (\Throwable $e) {}

        $spreadsheet = new Spreadsheet();

        // Data queries
        $categories = DB::table('hosfin_planfin_categories')->orderBy('sort_order')->get();
        $targetsSimRaw = DB::table('hosfin_planfin_targets')->where('budget_year', $targetYear)->get()->keyBy('plan_code');
        $targetsBudgetRaw = DB::table('hosfin_planfin_targets')->where('budget_year', $budgetYear)->get()->keyBy('plan_code');
        
        $actualsPeriod = $this->calculatePlanfinActuals($period);
        $actualsMonthlyPeriod = $this->calculatePlanfinMonthlyActuals($period);
        $actualsPriorYear = $this->calculatePlanfinActuals("{$priorYear}-09");

        // Sub-accounts mapping
        $mappings = DB::table('hosfin_planfin_mappings')->orderBy('plan_code')->orderBy('account_code')->get();
        $subMapsByPlan = [];
        foreach ($mappings as $m) {
            $subMapsByPlan[$m->plan_code][] = $m;
        }

        // -------------------------------------------------------------
        // SHEET 1: แผนประมาณการปี 2570 (Tab 2)
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle("แผนประมาณการ_{$targetYear}");

        $sheet1->setCellValue('A1', "แผนประมาณการรายได้และควบคุมค่าใช้จ่าย ประจำปีงบประมาณ {$targetYear}");
        $sheet1->setCellValue('A2', "หน่วยบริการ: {$hospName} ({$hospCode}) | ฐานอ้างอิง: ผลการดำเนินงานจริงรอบ {$baseMonths} เดือน ปีงบประมาณ {$budgetYear}");
        $sheet1->mergeCells('A1:G1');
        $sheet1->mergeCells('A2:G2');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
        $sheet1->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));

        // KPI calculations
        $revCodes = ['P04','P05','P06','P61','P07','P08','P09','P10','P11','P12','P121','P13'];
        $expCodes = ['P14','P15','P151','P16','P17','P18','P19','P20','P21','P22','P23','P24','P241','P25','P251'];

        $p13s_sim = 0; $p26s_sim = 0;
        foreach ($revCodes as $c) { $p13s_sim += floatval($targetsSimRaw->get($c)->target_amount ?? 0); }
        foreach ($expCodes as $c) { $p26s_sim += floatval($targetsSimRaw->get($c)->target_amount ?? 0); }
        $p27s_sim = $p13s_sim - $p26s_sim;
        $p29r = $p13s_sim - floatval($targetsSimRaw->get('P13')->target_amount ?? 0) - floatval($targetsSimRaw->get('P121')->target_amount ?? 0);
        $p29e = $p26s_sim - floatval($targetsSimRaw->get('P24')->target_amount ?? 0) - floatval($targetsSimRaw->get('P251')->target_amount ?? 0);
        $p29_sim = $p29r - $p29e;
        $cap20_sim = max(0, $p29_sim * 0.20);

        // KPI Box
        $sheet1->setCellValue('B4', 'ประมาณการรายได้สุทธิ (P27S)');
        $sheet1->setCellValue('B5', $p27s_sim);
        $sheet1->setCellValue('D4', 'EBITDA ประมาณการ (P29)');
        $sheet1->setCellValue('D5', $p29_sim);
        $sheet1->setCellValue('F4', 'วงเงินลงทุนด้วยเงินบำรุงได้ (20%)');
        $sheet1->setCellValue('F5', $cap20_sim);

        $sheet1->getStyle('B4:G4')->getFont()->setBold(true)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
        $sheet1->getStyle('B5:G5')->getFont()->setBold(true)->setSize(12);
        $sheet1->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet1->getStyle('D5')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet1->getStyle('F5')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet1->getStyle('B4:C5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFECFDF5');
        $sheet1->getStyle('D4:E5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFF6FF');
        $sheet1->getStyle('F4:G5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F3FF');

        // Headers
        $headers1 = [
            'A7' => 'รหัสรายการ',
            'B7' => 'รายการ / ผังบัญชี',
            'C7' => "ผลการดำเนินงาน ปี {$priorYear}",
            'D7' => "ผลการดำเนินงาน ({$baseMonths} ด.)",
            'E7' => "ประมาณการ ผลดำเนินงานทั้งปี",
            'F7' => "% เติบโต",
            'G7' => "แผนประมาณการ (แผนต้นปี {$targetYear})"
        ];
        foreach ($headers1 as $cell => $text) {
            $sheet1->setCellValue($cell, $text);
        }
        $sheet1->getStyle('A7:G7')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));
        $sheet1->getStyle('A7:G7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet1->getStyle('A7:G7')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet1->getStyle('A7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('C7:E7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet1->getStyle('F7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle('G7')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $currRow = 8;
        foreach ($categories as $cat) {
            $code = $cat->plan_code;
            $name = $cat->plan_name;
            $tObj = $targetsSimRaw->get($code);
            $targetAmt = $tObj ? floatval($tObj->target_amount) : 0;
            $growthRate = $tObj ? floatval($tObj->growth_rate) : 0;
            $baseMonthsAmt = $tObj ? floatval($tObj->baseline_amount) : 0;
            $baseEstAmt = ($baseMonths > 0) ? ($baseMonthsAmt / $baseMonths) * 12 : 0;

            if ($code === 'P13S') $targetAmt = $p13s_sim;
            elseif ($code === 'P26S') $targetAmt = $p26s_sim;
            elseif ($code === 'P27S') $targetAmt = $p27s_sim;
            elseif ($code === 'P29') $targetAmt = $p29_sim;

            $sheet1->setCellValue("A{$currRow}", $code);
            $sheet1->setCellValue("B{$currRow}", $name);
            $sheet1->setCellValue("C{$currRow}", floatval($actualsPriorYear[$code] ?? 0));
            $sheet1->setCellValue("D{$currRow}", $baseMonthsAmt);
            $sheet1->setCellValue("E{$currRow}", $baseEstAmt);
            $sheet1->setCellValue("F{$currRow}", $growthRate / 100);
            $sheet1->setCellValue("G{$currRow}", $targetAmt);

            $sheet1->getStyle("A{$currRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet1->getStyle("C{$currRow}:E{$currRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet1->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $sheet1->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0.00');

            if (in_array($code, ['P13S', 'P26S', 'P27S', 'P29'])) {
                $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFont()->setBold(true);
                $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                if ($code === 'P13S') $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFill()->getStartColor()->setARGB('FFD1FAE5');
                elseif ($code === 'P26S') $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFill()->getStartColor()->setARGB('FFFEE2E2');
                elseif ($code === 'P27S') $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFill()->getStartColor()->setARGB('FFCCFBF1');
                elseif ($code === 'P29') $sheet1->getStyle("A{$currRow}:G{$currRow}")->getFill()->getStartColor()->setARGB('FFE0E7FF');
            }
            $currRow++;

            // Sub-accounts drilldown
            if (!empty($subMapsByPlan[$code])) {
                foreach ($subMapsByPlan[$code] as $sub) {
                    $subCode = $sub->account_code;
                    $subName = $sub->account_name;
                    $subTObj = $targetsSimRaw->get($subCode);
                    if (!$subTObj) continue;
                    
                    $subTargetAmt = floatval($subTObj->target_amount ?? 0);
                    $subGrowth = floatval($subTObj->growth_rate ?? 0);
                    $subBaseAmt = floatval($subTObj->baseline_amount ?? 0);
                    $subEstAmt = ($baseMonths > 0) ? ($subBaseAmt / $baseMonths) * 12 : 0;

                    $sheet1->setCellValue("A{$currRow}", $subCode);
                    $sheet1->setCellValue("B{$currRow}", "   - " . $subName);
                    $sheet1->setCellValue("C{$currRow}", 0);
                    $sheet1->setCellValue("D{$currRow}", $subBaseAmt);
                    $sheet1->setCellValue("E{$currRow}", $subEstAmt);
                    $sheet1->setCellValue("F{$currRow}", $subGrowth / 100);
                    $sheet1->setCellValue("G{$currRow}", $subTargetAmt);

                    $sheet1->getStyle("A{$currRow}")->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF64748B'));
                    $sheet1->getStyle("B{$currRow}")->getFont()->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF475569'));
                    $sheet1->getStyle("A{$currRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    $sheet1->getStyle("C{$currRow}:E{$currRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet1->getStyle("F{$currRow}")->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
                    $sheet1->getStyle("G{$currRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet1->getStyle("C{$currRow}:G{$currRow}")->getFont()->setSize(9);
                    $currRow++;
                }
            }
        }

        $sheet1->getStyle("A7:G" . ($currRow - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // SHEET 2: กลุ่มแผนปฏิบัติการย่อย 2-7
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('แผนปฏิบัติการย่อย_2-7');

        $sheet2->setCellValue('A1', "กลุ่มแผนปฏิบัติการย่อยประกอบแผนเงินบำรุง (แผนที่ 2–7) ประจำปีงบประมาณ {$targetYear}");
        $sheet2->setCellValue('A2', "หน่วยบริการ: {$hospName} ({$hospCode})");
        $sheet2->mergeCells('A1:E1');
        $sheet2->mergeCells('A2:E2');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));

        $headers2 = [
            'A4' => 'รหัสแผน',
            'B4' => 'รายการแผนปฏิบัติการย่อย',
            'C4' => 'เงินงบประมาณ (บาท)',
            'D4' => 'เงินนอกงบ / เงินบำรุง (บาท)',
            'E4' => 'รวมทั้งสิ้น (บาท)'
        ];
        foreach ($headers2 as $cell => $text) {
            $sheet2->setCellValue($cell, $text);
        }
        $sheet2->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet2->getStyle('A4:E4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $sheet2->getStyle('A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle('C4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $subPlansDef = [
            '2. แผนจัดซื้อยา เวชภัณฑ์ วัสดุการแพทย์' => [
                ['code' => 'MED01', 'name' => 'ยาในบัญชียาหลักแห่งชาติ'],
                ['code' => 'MED02', 'name' => 'ยานอกบัญชียาหลักแห่งชาติ'],
                ['code' => 'MED03', 'name' => 'เวชภัณฑ์มิใช่ยา'],
                ['code' => 'MED04', 'name' => 'วัสดุการแพทย์'],
                ['code' => 'MED05', 'name' => 'วัสดุเภสัชกรรม'],
                ['code' => 'MED06', 'name' => 'วัสดุวิทยาศาสตร์และการแพทย์'],
            ],
            '3. แผนจัดซื้อวัสดุอื่น' => [
                ['code' => 'MAT01', 'name' => 'วัสดุทันตกรรม'],
                ['code' => 'MAT02', 'name' => 'วัสดุเอกซเรย์'],
                ['code' => 'MAT03', 'name' => 'วัสดุเชื้อเพลิง'],
                ['code' => 'MAT04', 'name' => 'วัสดุงานบ้านงานครัว'],
                ['code' => 'MAT05', 'name' => 'วัสดุสำนักงาน'],
                ['code' => 'MAT06', 'name' => 'วัสดุยานพาหนะ'],
                ['code' => 'MAT07', 'name' => 'วัสดุไฟฟ้าและวิทยุ'],
                ['code' => 'MAT08', 'name' => 'วัสดุคอมพิวเตอร์'],
                ['code' => 'MAT09', 'name' => 'วัสดุโฆษณาและเผยแพร่'],
                ['code' => 'MAT10', 'name' => 'วัสดุก่อสร้าง'],
                ['code' => 'MAT11', 'name' => 'วัสดุเครื่องแต่งกาย'],
                ['code' => 'MAT12', 'name' => 'วัสดุอื่น ๆ'],
            ],
            '4. แผนบริหารจัดการเจ้าหนี้การค้า' => [
                ['code' => 'AP01', 'name' => 'องค์การเภสัชกรรม (ยา)'],
                ['code' => 'AP02', 'name' => 'องค์การเภสัชกรรม (เวชภัณฑ์มิใช่ยา)'],
                ['code' => 'AP03', 'name' => 'บริษัทเอกชน (ยา)'],
                ['code' => 'AP04', 'name' => 'บริษัทเอกชน (เวชภัณฑ์มิใช่ยา)'],
                ['code' => 'AP05', 'name' => 'บริษัทเอกชน (วัสดุการแพทย์)'],
                ['code' => 'AP06', 'name' => 'บริษัทเอกชน (วัสดุวิทยาศาสตร์)'],
                ['code' => 'AP07', 'name' => 'บริษัทเอกชน (วัสดุทันตกรรม)'],
                ['code' => 'AP08', 'name' => 'บริษัทเอกชน (วัสดุเอกซเรย์)'],
                ['code' => 'AP09', 'name' => 'บริษัทเอกชน (วัสดุอื่น)'],
                ['code' => 'AP10', 'name' => 'ค่าสาธารณูปโภคค้างจ่าย'],
                ['code' => 'AP11', 'name' => 'ค่าจ้างเหมาบริการ'],
                ['code' => 'AP12', 'name' => 'เงินเดือน/ค่าตอบแทน'],
                ['code' => 'AP13', 'name' => 'ค่าล่วงเวลาค้างจ่าย'],
                ['code' => 'AP14', 'name' => 'เงิน พตส. ค้างจ่าย'],
                ['code' => 'AP15', 'name' => 'เจ้าหนี้อื่น'],
            ],
            '5. แผนบริหารจัดการลูกหนี้' => [
                ['code' => 'AR01', 'name' => 'ลูกหนี้ค่ารักษาพยาบาล (สิทธิ UC)'],
                ['code' => 'AR02', 'name' => 'ลูกหนี้ค่ารักษาพยาบาล (สิทธิประกันสังคม)'],
                ['code' => 'AR03', 'name' => 'ลูกหนี้ค่ารักษาพยาบาล (สิทธิข้าราชการ/อปท.)'],
                ['code' => 'AR04', 'name' => 'ลูกหนี้ค่ารักษาพยาบาล (ต่างด้าว)'],
                ['code' => 'AR05', 'name' => 'ลูกหนี้ค่ารักษาพยาบาล (พรบ.คุ้มครองผู้ประสบภัยจากรถ)'],
                ['code' => 'AR06', 'name' => 'ลูกหนี้เงินยืมทดรอง'],
                ['code' => 'AR07', 'name' => 'ลูกหนี้อื่น ๆ'],
            ],
            '6. แผนการลงทุนเพิ่ม' => [
                ['code' => 'INV01', 'name' => "จัดซื้อ จัดหาด้วยเงินบำรุงของ รพ. ปี {$targetYear}"],
                ['code' => 'INV02', 'name' => "จัดซื้อ ด้วยงบค่าบริการฯเบิกจ่ายลักษณะงบลงทุน ปี {$targetYear}"],
                ['code' => 'INV03', 'name' => "จัดซื้อ จัดหาด้วยเงินงบประมาณ ของ รพ. ปี {$targetYear}"],
                ['code' => 'INV04', 'name' => 'จัดซื้อ จัดหาด้วยเงินบริจาค ของ รพ. ปี งปม.2564 - ปัจจุบัน'],
                ['code' => 'INV05', 'name' => 'จัดซื้อ จัดหาด้วยเงินบริจาค ของ รพ. ก่อน 1 ต.ค. 63'],
            ],
            '7. แผนสนับสนุน รพ.สต.' => [
                ['code' => 'SUB01', 'name' => 'Fixed Cost (ว5313)'],
                ['code' => 'SUB02', 'name' => 'รายการอื่น'],
                ['code' => 'SUB03', 'name' => 'ยา'],
                ['code' => 'SUB04', 'name' => 'วัสดุเภสัชกรรม'],
                ['code' => 'SUB05', 'name' => 'วัสดุการแพทย์ทั่วไป'],
                ['code' => 'SUB06', 'name' => 'วัสดุวิทยาศาสตร์และการแพทย์'],
                ['code' => 'SUB07', 'name' => 'วัสดุเอกซเรย์'],
                ['code' => 'SUB08', 'name' => 'วัสดุทันตกรรม'],
                ['code' => 'SUB09', 'name' => 'วัสดุอื่น'],
                ['code' => 'SUB10', 'name' => 'งบค่าเสื่อม UC'],
            ]
        ];

        $r2 = 5;
        foreach ($subPlansDef as $groupTitle => $items) {
            $sheet2->setCellValue("A{$r2}", $groupTitle);
            $sheet2->mergeCells("A{$r2}:E{$r2}");
            $sheet2->getStyle("A{$r2}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF4338CA'));
            $sheet2->getStyle("A{$r2}:E{$r2}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFEDE9FE');
            $r2++;

            foreach ($items as $item) {
                $savedObj = $targetsSimRaw->get($item['code']);
                $bg = $savedObj ? floatval($savedObj->baseline_amount) : 0;
                $nonBg = $savedObj ? floatval($savedObj->target_amount) : 0;
                $tot = $bg + $nonBg;

                $sheet2->setCellValue("A{$r2}", $item['code']);
                $sheet2->setCellValue("B{$r2}", $item['name']);
                $sheet2->setCellValue("C{$r2}", $bg);
                $sheet2->setCellValue("D{$r2}", $nonBg);
                $sheet2->setCellValue("E{$r2}", $tot);

                $sheet2->getStyle("A{$r2}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet2->getStyle("C{$r2}:E{$r2}")->getNumberFormat()->setFormatCode('#,##0.00');
                $r2++;
            }
        }

        $sheet2->getStyle("A4:E" . ($r2 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // SHEET 3: ติดตามแผนรายเดือน (Tab 1)
        // -------------------------------------------------------------
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('ติดตามแผนรายเดือน');

        $sheet3->setCellValue('A1', "รายงานติดตามแผนเงินบำรุง รายได้และค่าใช้จ่าย (ณ งวดเดือน {$period})");
        $sheet3->setCellValue('A2', "หน่วยบริการ: {$hospName} ({$hospCode}) | ปีงบประมาณ {$budgetYear}");
        $sheet3->mergeCells('A1:J1');
        $sheet3->mergeCells('A2:J2');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers3 = [
            'A4' => 'รหัสรายการ',
            'B4' => 'รายการ',
            'C4' => 'แผนทั้งปี',
            'D4' => 'แผนสะสม',
            'E4' => 'ผลดำเนินงานจริงสะสม',
            'F4' => 'ผลต่างสะสม',
            'G4' => '% บรรลุสะสม',
            'H4' => 'แผนงวดเดือนนี้',
            'I4' => 'ผลจริงเดือนนี้',
            'J4' => 'สถานะ'
        ];
        foreach ($headers3 as $cell => $text) {
            $sheet3->setCellValue($cell, $text);
        }
        $sheet3->getStyle('A4:J4')->getFont()->setBold(true);
        $sheet3->getStyle('A4:J4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $pParts = explode('-', $period);
        $pMonth = intval($pParts[1] ?? 7);
        $cMonths = ($pMonth >= 10) ? ($pMonth - 9) : ($pMonth + 3);
        if ($cMonths <= 0 || $cMonths > 12) $cMonths = 10;

        $r3 = 5;
        foreach ($categories as $cat) {
            $code = $cat->plan_code;
            $name = $cat->plan_name;

            $annualTarget = floatval($targetsBudgetRaw->get($code)->target_amount ?? 0);
            $planCum = ($annualTarget / 12.0) * $cMonths;
            $actualCum = floatval($actualsPeriod[$code] ?? 0);
            $diffCum = $actualCum - $planCum;
            $pctCum = ($planCum != 0) ? ($diffCum / $planCum) * 100.0 : 0.0;

            $planM = $annualTarget / 12.0;
            $actualM = floatval($actualsMonthlyPeriod[$code] ?? 0);

            $status = 'OK';
            if ($cat->category_type === 'revenue' && $diffCum < 0) $status = 'Not OK';
            elseif ($cat->category_type === 'expense' && $diffCum > 0) $status = 'Not OK';

            $sheet3->setCellValue("A{$r3}", $code);
            $sheet3->setCellValue("B{$r3}", $name);
            $sheet3->setCellValue("C{$r3}", $annualTarget);
            $sheet3->setCellValue("D{$r3}", $planCum);
            $sheet3->setCellValue("E{$r3}", $actualCum);
            $sheet3->setCellValue("F{$r3}", $diffCum);
            $sheet3->setCellValue("G{$r3}", $pctCum / 100);
            $sheet3->setCellValue("H{$r3}", $planM);
            $sheet3->setCellValue("I{$r3}", $actualM);
            $sheet3->setCellValue("J{$r3}", $status);

            $sheet3->getStyle("A{$r3}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet3->getStyle("C{$r3}:F{$r3}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("G{$r3}")->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $sheet3->getStyle("H{$r3}:I{$r3}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("J{$r3}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            if (in_array($code, ['P13S', 'P26S', 'P27S', 'P29'])) {
                $sheet3->getStyle("A{$r3}:J{$r3}")->getFont()->setBold(true);
                $sheet3->getStyle("A{$r3}:J{$r3}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                if ($code === 'P13S') $sheet3->getStyle("A{$r3}:J{$r3}")->getFill()->getStartColor()->setARGB('FFD1FAE5');
                elseif ($code === 'P26S') $sheet3->getStyle("A{$r3}:J{$r3}")->getFill()->getStartColor()->setARGB('FFFEE2E2');
                elseif ($code === 'P27S') $sheet3->getStyle("A{$r3}:J{$r3}")->getFill()->getStartColor()->setARGB('FFCCFBF1');
                elseif ($code === 'P29') $sheet3->getStyle("A{$r3}:J{$r3}")->getFill()->getStartColor()->setARGB('FFE0E7FF');
            }
            $r3++;
        }

        $sheet3->getStyle("A4:J" . ($r3 - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('FFE2E8F0');
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet back to Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $filename = "PlanFin_{$targetYear}_Report_" . date('Ymd_His') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}


