<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\SmartMoneyBatch;
use App\Models\SmartMoneyDetail;
use App\Models\SmartMoneyExcel;

class SmartMoneyController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y' && $user->allow_receipt !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'ระบบเงินโอน Smart Money'], 403);
                }
                return $next($request);
            }
        ]);
    }

    /**
     * Smart Money Transfer Index Page
     */
    public function index(Request $request)
    {
        ini_set('max_execution_time', 300);

        // 1. Budget Year Dropdown
        $budget_year_select = DB::table('budget_year')
            ->select('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME', 'DATE_BEGIN', 'DATE_END')
            ->orderByDesc('LEAVE_YEAR_ID')
            ->limit(7)
            ->get();

        $budget_year_now = DB::table('budget_year')
            ->whereDate('DATE_END', '>=', date('Y-m-d'))
            ->whereDate('DATE_BEGIN', '<=', date('Y-m-d'))
            ->value('LEAVE_YEAR_ID');

        if (!$budget_year_now) {
            $budget_year_now = date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        }

        $budget_year = $request->budget_year ?: $budget_year_now;

        // Default start and end dates based on budget year
        $currBudgetYearObj = $budget_year_select->firstWhere('LEAVE_YEAR_ID', $budget_year);
        $defaultStartDate = $currBudgetYearObj && !empty($currBudgetYearObj->DATE_BEGIN) ? $currBudgetYearObj->DATE_BEGIN : (($budget_year - 544) . '-10-01');
        $defaultEndDate = $currBudgetYearObj && !empty($currBudgetYearObj->DATE_END) ? $currBudgetYearObj->DATE_END : (($budget_year - 543) . '-09-30');

        $start_date = $request->filled('start_date') ? $this->parseDate($request->start_date) : $defaultStartDate;
        $end_date = $request->filled('end_date') ? $this->parseDate($request->end_date) : $defaultEndDate;

        $receipt_status = $request->receipt_status ?: 'all'; // all, pending, issued
        $search = $request->search ?: '';
        $account_filter = $request->account_code ?: '';

        // 2. Query Batches for this date range / budget year
        $query = SmartMoneyBatch::query();

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('transfer_date', [$start_date, $end_date]);
        } elseif (!empty($budget_year)) {
            $query->where('budget_year', $budget_year);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('batch_no', 'like', "%{$search}%")
                  ->orWhere('round_no', 'like', "%{$search}%")
                  ->orWhere('account_code', 'like', "%{$search}%")
                  ->orWhere('fund_main', 'like', "%{$search}%")
                  ->orWhere('fund_sub', 'like', "%{$search}%")
                  ->orWhere('receive_no', 'like', "%{$search}%");
            });
        }

        if (!empty($account_filter)) {
            $query->where('account_code', $account_filter);
        }

        $rawBatches = $query->orderByDesc('transfer_date')
            ->orderByDesc('batch_no')
            ->orderBy('id')
            ->get();

        // 3. Group by batch_no for Option 2: Master-Detail
        $groupedBatches = [];
        foreach ($rawBatches as $row) {
            $bNo = $row->batch_no;
            if (!isset($groupedBatches[$bNo])) {
                $groupedBatches[$bNo] = (object)[
                    'id' => $row->id,
                    'batch_no' => $bNo,
                    'transfer_date' => $row->transfer_date,
                    'budget_year' => $row->budget_year,
                    'file_name' => $row->file_name,
                    'receive_no' => $row->receive_no,
                    'receipt_date' => $row->receipt_date,
                    'receipt_by' => $row->receipt_by,
                    'total_amount' => 0,
                    'total_hold_amount' => 0,
                    'total_deduct_amount' => 0,
                    'total_guarantee_amount' => 0,
                    'total_tax_amount' => 0,
                    'total_remain_amount' => 0,
                    'total_offset_amount' => 0,
                    'total_net_amount' => 0,
                    'round_nos' => [],
                    'account_codes' => [],
                    'fund_mains' => [],
                    'fund_subs' => [],
                    'items' => collect([]),
                ];
            }
            $groupedBatches[$bNo]->total_amount += (float)$row->amount;
            $groupedBatches[$bNo]->total_hold_amount += (float)$row->hold_amount;
            $groupedBatches[$bNo]->total_deduct_amount += (float)$row->deduct_amount;
            $groupedBatches[$bNo]->total_guarantee_amount += (float)$row->guarantee_amount;
            $groupedBatches[$bNo]->total_tax_amount += (float)$row->tax_amount;
            $groupedBatches[$bNo]->total_remain_amount += (float)$row->remain_amount;
            $groupedBatches[$bNo]->total_offset_amount += (float)$row->offset_amount;
            $groupedBatches[$bNo]->total_net_amount += (float)$row->net_amount;

            if (!empty($row->round_no) && !in_array($row->round_no, $groupedBatches[$bNo]->round_nos)) {
                $groupedBatches[$bNo]->round_nos[] = $row->round_no;
            }
            if (!empty($row->account_code) && !in_array($row->account_code, $groupedBatches[$bNo]->account_codes)) {
                $groupedBatches[$bNo]->account_codes[] = $row->account_code;
            }
            if (!empty($row->fund_main) && !in_array($row->fund_main, $groupedBatches[$bNo]->fund_mains)) {
                $groupedBatches[$bNo]->fund_mains[] = $row->fund_main;
            }
            if (!empty($row->fund_sub) && !in_array($row->fund_sub, $groupedBatches[$bNo]->fund_subs)) {
                $groupedBatches[$bNo]->fund_subs[] = $row->fund_sub;
            }
            if (!empty($row->receive_no) && empty($groupedBatches[$bNo]->receive_no)) {
                $groupedBatches[$bNo]->receive_no = $row->receive_no;
                $groupedBatches[$bNo]->receipt_date = $row->receipt_date;
                $groupedBatches[$bNo]->receipt_by = $row->receipt_by;
            }
            if (!empty($row->file_name) && empty($groupedBatches[$bNo]->file_name)) {
                $groupedBatches[$bNo]->file_name = $row->file_name;
            }
            $groupedBatches[$bNo]->items->push($row);
        }

        // Filter by receipt_status
        if ($receipt_status === 'pending') {
            $groupedBatches = array_filter($groupedBatches, function($b) {
                return empty($b->receive_no);
            });
        } elseif ($receipt_status === 'issued') {
            $groupedBatches = array_filter($groupedBatches, function($b) {
                return !empty($b->receive_no);
            });
        }

        $batches = collect(array_values($groupedBatches));

        // 4. KPI Summaries for this filtered range (Grouped by distinct batch_no)
        $kpiQuery = SmartMoneyBatch::query();
        if (!empty($start_date) && !empty($end_date)) {
            $kpiQuery->whereBetween('transfer_date', [$start_date, $end_date]);
        } elseif (!empty($budget_year)) {
            $kpiQuery->where('budget_year', $budget_year);
        }
        $allYearRows = $kpiQuery->get();

        $yearGrouped = [];
        foreach ($allYearRows as $row) {
            $bNo = $row->batch_no;
            if (!isset($yearGrouped[$bNo])) {
                $yearGrouped[$bNo] = (object)[
                    'has_receipt' => !empty($row->receive_no),
                    'net_amount' => 0
                ];
            }
            $yearGrouped[$bNo]->net_amount += (float)$row->net_amount;
            if (!empty($row->receive_no)) {
                $yearGrouped[$bNo]->has_receipt = true;
            }
        }

        $total_count = count($yearGrouped);
        $total_net_amount = array_sum(array_column($yearGrouped, 'net_amount'));

        $issued_count = 0;
        $issued_amount = 0;
        $pending_count = 0;
        $pending_amount = 0;

        foreach ($yearGrouped as $g) {
            if ($g->has_receipt) {
                $issued_count++;
                $issued_amount += $g->net_amount;
            } else {
                $pending_count++;
                $pending_amount += $g->net_amount;
            }
        }

        // Distinct accounts for filter dropdown
        $account_codes = SmartMoneyBatch::query()
            ->when(!empty($start_date) && !empty($end_date), function($q) use ($start_date, $end_date) {
                $q->whereBetween('transfer_date', [$start_date, $end_date]);
            }, function($q) use ($budget_year) {
                $q->where('budget_year', $budget_year);
            })
            ->whereNotNull('account_code')
            ->distinct()
            ->pluck('account_code');

        // Check License for ThaiD Bot
        $hasBotLicense = false;
        try {
            $hasBotLicense = \App\Services\LicenseVerificationService::isModuleLicensed('sync_eclaim_thaid');
        } catch (\Exception $e) {}

        // Hospital code
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';

        return view('import.smart_money_index', compact(
            'batches',
            'budget_year_select',
            'budget_year',
            'start_date',
            'end_date',
            'receipt_status',
            'search',
            'account_filter',
            'account_codes',
            'total_count',
            'total_net_amount',
            'issued_count',
            'issued_amount',
            'pending_count',
            'pending_amount',
            'hasBotLicense',
            'hospcode'
        ));
    }

    /**
     * Import Summary Excel (e.g. nhso_xxx.xlsx from Smart Money summary export)
     */
    public function importSummaryExcel(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'excel_file' => 'required|file|max:51200',
        ]);

        try {
            $file = $request->file('excel_file');
            $originalFileName = $file->getClientOriginalName();
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Disconnect immediately after reading array to free RAM
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if (empty($rows)) {
                return response()->json(['status' => 'error', 'message' => 'ไฟล์ไม่มีข้อมูล'], 422);
            }

            // Find Header Row (Look for 'ลำดับ', 'วันที่โอน', 'Batch No.', 'งวด', 'รหัสผังบัญชี')
            $headerRowIdx = -1;
            foreach ($rows as $idx => $row) {
                $rowStr = implode(' ', array_filter($row, fn($v) => $v !== null && $v !== ''));
                if (stripos($rowStr, 'Batch') !== false && (stripos($rowStr, 'วันที่โอน') !== false || stripos($rowStr, 'รหัสผังบัญชี') !== false || stripos($rowStr, 'เงินโอนเข้าบัญชี') !== false)) {
                    $headerRowIdx = $idx;
                    break;
                }
            }

            if ($headerRowIdx === -1) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบรูปแบบหัวตารางของ Smart Money (ต้องมีคอลัมน์ วันที่โอน, Batch No., รหัสผังบัญชี)'], 422);
            }

            $headers = $rows[$headerRowIdx];
            $colMap = [];
            foreach ($headers as $cIdx => $name) {
                $trimmed = trim((string)$name);
                if (stripos($trimmed, 'วันที่โอน') !== false) $colMap['transfer_date'] = $cIdx;
                elseif (stripos($trimmed, 'Batch No') !== false) $colMap['batch_no'] = $cIdx;
                elseif (stripos($trimmed, 'งวด') !== false || stripos($trimmed, 'เลขที่เบิกจ่าย') !== false) $colMap['round_no'] = $cIdx;
                elseif (stripos($trimmed, 'รหัสผังบัญชี') !== false) $colMap['account_code'] = $cIdx;
                elseif (stripos($trimmed, 'กองทุนย่อย') !== false) $colMap['fund_sub'] = $cIdx;
                elseif (stripos($trimmed, 'กองทุน') !== false && !isset($colMap['fund_main'])) $colMap['fund_main'] = $cIdx;
                elseif (stripos($trimmed, 'จำนวนเงิน') !== false && stripos($trimmed, 'รอหักกลบ') === false) $colMap['amount'] = $cIdx;
                elseif (stripos($trimmed, 'ชะลอการโอน') !== false) $colMap['hold_amount'] = $cIdx;
                elseif (stripos($trimmed, 'รายการหัก') !== false) $colMap['deduct_amount'] = $cIdx;
                elseif (stripos($trimmed, 'หลักประกัน') !== false) $colMap['guarantee_amount'] = $cIdx;
                elseif (stripos($trimmed, 'ภาษี') !== false) $colMap['tax_amount'] = $cIdx;
                elseif (stripos($trimmed, 'คงเหลือ') !== false) $colMap['remain_amount'] = $cIdx;
                elseif (stripos($trimmed, 'รอหักกลบ') !== false) $colMap['offset_amount'] = $cIdx;
                elseif (stripos($trimmed, 'เงินโอนเข้าบัญชี') !== false) $colMap['net_amount'] = $cIdx;
            }

            $insertedCount = 0;
            $updatedCount = 0;
            $totalDataRows = 0;

            DB::beginTransaction();

            for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== ''))) continue;

                $batchNo = trim((string)($row[$colMap['batch_no'] ?? 2] ?? ''));
                $roundNo = trim((string)($row[$colMap['round_no'] ?? 3] ?? ''));
                $rawDate = trim((string)($row[$colMap['transfer_date'] ?? 1] ?? ''));

                if (empty($batchNo) && empty($roundNo) && empty($rawDate)) continue;

                $transferDate = $this->parseDate($rawDate);
                $accountCode = trim((string)($row[$colMap['account_code'] ?? 4] ?? ''));
                $fundMain = trim((string)($row[$colMap['fund_main'] ?? 5] ?? ''));
                $fundSub = trim((string)($row[$colMap['fund_sub'] ?? 6] ?? ''));

                $amount = $this->cleanNumber($row[$colMap['amount'] ?? 7] ?? 0);
                $holdAmount = $this->cleanNumber($row[$colMap['hold_amount'] ?? 8] ?? 0);
                $deductAmount = $this->cleanNumber($row[$colMap['deduct_amount'] ?? 9] ?? 0);
                $guaranteeAmount = $this->cleanNumber($row[$colMap['guarantee_amount'] ?? 10] ?? 0);
                $taxAmount = $this->cleanNumber($row[$colMap['tax_amount'] ?? 11] ?? 0);
                $remainAmount = $this->cleanNumber($row[$colMap['remain_amount'] ?? 12] ?? 0);
                $offsetAmount = $this->cleanNumber($row[$colMap['offset_amount'] ?? 13] ?? 0);
                $netAmount = $this->cleanNumber($row[$colMap['net_amount'] ?? 14] ?? 0);

                // Calculate Budget Year from transferDate
                $bYear = $request->budget_year;
                if ($transferDate) {
                    $y = (int)date('Y', strtotime($transferDate)) + 543;
                    $m = (int)date('m', strtotime($transferDate));
                    $bYear = $y + ($m >= 10 ? 1 : 0);
                }

                $totalDataRows++;

                // Check existing record
                $exists = SmartMoneyBatch::where('batch_no', $batchNo)
                    ->where('round_no', $roundNo)
                    ->where('account_code', $accountCode)
                    ->where('fund_sub', $fundSub)
                    ->first();

                if ($exists) {
                    // Update amounts, PRESERVE receive_no, receipt_date, receipt_by!
                    $exists->update([
                        'transfer_date' => $transferDate ?: $exists->transfer_date,
                        'fund_main' => $fundMain ?: $exists->fund_main,
                        'amount' => $amount,
                        'hold_amount' => $holdAmount,
                        'deduct_amount' => $deductAmount,
                        'guarantee_amount' => $guaranteeAmount,
                        'tax_amount' => $taxAmount,
                        'remain_amount' => $remainAmount,
                        'offset_amount' => $offsetAmount,
                        'net_amount' => $netAmount,
                        'file_name' => $originalFileName,
                        'budget_year' => $bYear ?: $exists->budget_year,
                    ]);
                    $updatedCount++;
                } else {
                    SmartMoneyBatch::create([
                        'transfer_date' => $transferDate,
                        'batch_no' => $batchNo,
                        'round_no' => $roundNo,
                        'account_code' => $accountCode,
                        'fund_main' => $fundMain,
                        'fund_sub' => $fundSub,
                        'amount' => $amount,
                        'hold_amount' => $holdAmount,
                        'deduct_amount' => $deductAmount,
                        'guarantee_amount' => $guaranteeAmount,
                        'tax_amount' => $taxAmount,
                        'remain_amount' => $remainAmount,
                        'offset_amount' => $offsetAmount,
                        'net_amount' => $netAmount,
                        'file_name' => $originalFileName,
                        'budget_year' => $bYear,
                    ]);
                    $insertedCount++;
                }

                // Fast link existing details in database with this batch
                if (!empty($roundNo) && !empty($batchNo)) {
                    SmartMoneyDetail::where('round_no', $roundNo)
                        ->where(function($q) use ($batchNo) {
                            $q->whereNull('batch_no')->orWhere('batch_no', '')->orWhere('batch_no', '!=', $batchNo);
                        })
                        ->update([
                            'batch_no' => $batchNo,
                            'budget_year' => $bYear,
                        ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "นำเข้าข้อมูล Smart Money สำเร็จเรียบร้อย! (เพิ่มใหม่ {$insertedCount} รายการ, อัปเดต {$updatedCount} รายการ)",
                'total_rows' => $totalDataRows,
                'inserted' => $insertedCount,
                'updated' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SmartMoney Import Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Detail Page for Individual Patients in a Batch
     */
    public function detail(Request $request, $batch_no)
    {
        $batch = SmartMoneyBatch::where('batch_no', $batch_no)->first();
        if (!$batch) {
            return redirect()->route('import.smart_money')->with('error', 'ไม่พบข้อมูล Batch ' . $batch_no);
        }

        $roundNos = SmartMoneyBatch::where('batch_no', $batch_no)->pluck('round_no')->filter()->unique()->toArray();

        $query = SmartMoneyDetail::where(function($q) use ($batch_no, $roundNos) {
            $q->where('batch_no', $batch_no);
            if (!empty($roundNos)) {
                $q->orWhereIn('round_no', $roundNos);
            }
        });

        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('hn', 'like', "%{$s}%")
                  ->orWhere('an', 'like', "%{$s}%")
                  ->orWhere('cid', 'like', "%{$s}%")
                  ->orWhere('pt_name', 'like', "%{$s}%")
                  ->orWhere('repno', 'like', "%{$s}%")
                  ->orWhere('seq_no', 'like', "%{$s}%")
                  ->orWhere('sub_fund', 'like', "%{$s}%");
            });
        }

        if ($request->export === 'excel') {
            return $this->exportDetailExcel($batch, $query->get());
        }

        $details = $query->orderBy('id')->paginate(50);
        $total_details_count = (clone $query)->count();
        $total_receive_amount = (clone $query)->sum('receive_total');

        return view('import.smart_money_detail', compact(
            'batch',
            'details',
            'total_details_count',
            'total_receive_amount'
        ));
    }

    /**
     * Get JSON Detail data for Modal
     */
    public function getDetailJson(Request $request, $batch_no)
    {
        $batches = SmartMoneyBatch::where('batch_no', $batch_no)->get();
        if ($batches->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล Batch ' . $batch_no], 404);
        }

        $firstBatch = $batches->first();
        $roundNos = $batches->pluck('round_no')->filter()->unique()->values()->toArray();
        $accountCodes = $batches->pluck('account_code')->filter()->unique()->values()->toArray();
        $fundSubs = $batches->pluck('fund_sub')->filter()->unique()->values()->toArray();

        $query = SmartMoneyDetail::where(function($q) use ($batch_no, $roundNos) {
            $q->where('batch_no', $batch_no);
            if (!empty($roundNos)) {
                $q->orWhereIn('round_no', $roundNos);
            }
        });

        if ($request->search) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('hn', 'like', "%{$s}%")
                  ->orWhere('an', 'like', "%{$s}%")
                  ->orWhere('cid', 'like', "%{$s}%")
                  ->orWhere('pt_name', 'like', "%{$s}%")
                  ->orWhere('repno', 'like', "%{$s}%")
                  ->orWhere('seq_no', 'like', "%{$s}%")
                  ->orWhere('sub_fund', 'like', "%{$s}%");
            });
        }

        $perPage = (int)($request->per_page ?: 50);
        $details = $query->orderBy('id')->paginate($perPage);

        $totalDetailsCount = (clone $query)->count();
        $totalReceiveAmount = (clone $query)->sum('receive_total');

        $formattedDetails = collect($details->items())->map(function($d) {
            return [
                'id' => $d->id,
                'transfer_date' => $d->transfer_date ? (string)$d->transfer_date : '',
                'transfer_date_thai' => $d->transfer_date ? DateThai($d->transfer_date) : '-',
                'hn' => $d->hn,
                'an' => $d->an ?: '-',
                'seq_no' => $d->seq_no ?: '-',
                'pt_type' => $d->pt_type ?: 'ผู้ป่วยนอก',
                'cid' => $d->cid,
                'pt_name' => $d->pt_name,
                'vstdate' => $d->vstdate ? (string)$d->vstdate : '',
                'vstdate_thai' => $d->vstdate ? DateThai($d->vstdate) : '-',
                'receive_total' => (float)$d->receive_total,
                'receive_total_formatted' => number_format((float)$d->receive_total, 2),
                'repno' => $d->repno,
                'main_fund' => $d->main_fund,
                'sub_fund' => $d->sub_fund,
                'sub_fund_desc' => $d->sub_fund_desc,
                'hsend' => $d->hsend,
                'hcode' => $d->hcode,
                'invoice_no' => $d->invoice_no,
                'invoice_lt' => $d->invoice_lt,
            ];
        });

        return response()->json([
            'status' => 'success',
            'batch' => [
                'batch_no' => $batch_no,
                'transfer_date' => $firstBatch->transfer_date ? (string)$firstBatch->transfer_date : '',
                'transfer_date_thai' => $firstBatch->transfer_date ? DateThai($firstBatch->transfer_date) : '-',
                'budget_year' => $firstBatch->budget_year,
                'round_nos' => $roundNos,
                'account_codes' => $accountCodes,
                'fund_subs' => $fundSubs,
                'receive_no' => $firstBatch->receive_no ?: '',
                'receipt_date' => $firstBatch->receipt_date ? DateThai($firstBatch->receipt_date) : '',
                'receipt_by' => $firstBatch->receipt_by ?: '',
                'total_amount' => (float)$batches->sum('amount'),
                'total_net_amount' => (float)$batches->sum('net_amount'),
                'total_net_amount_formatted' => number_format((float)$batches->sum('net_amount'), 2),
            ],
            'stats' => [
                'total_count' => $totalDetailsCount,
                'total_amount' => (float)$totalReceiveAmount,
                'total_amount_formatted' => number_format((float)$totalReceiveAmount, 2),
            ],
            'data' => $formattedDetails,
            'pagination' => [
                'current_page' => $details->currentPage(),
                'last_page' => $details->lastPage(),
                'per_page' => $details->perPage(),
                'total' => $details->total(),
            ]
        ]);
    }

    /**
     * Import Detail Excel (Individual Patient File - 6908_OP / 6908_IP)
     * Supports Multiple Files Upload simultaneously
     */
    public function importDetailExcel(Request $request)
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        // Check for single file or array of files
        $uploadedFiles = [];
        if ($request->hasFile('detail_excel')) {
            $f = $request->file('detail_excel');
            $uploadedFiles = is_array($f) ? $f : [$f];
        } elseif ($request->hasFile('detail_files')) {
            $f = $request->file('detail_files');
            $uploadedFiles = is_array($f) ? $f : [$f];
        }

        if (empty($uploadedFiles)) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกไฟล์ Excel รายบุคคลอย่างน้อย 1 ไฟล์'], 422);
        }

        $inputBatchNo = $request->batch_no;
        $totalProcessedFiles = 0;
        $totalImportedPatients = 0;
        $fileSummaries = [];
        $matchedBatches = [];

        foreach ($uploadedFiles as $file) {
            if (!$file || !$file->isValid()) continue;

            $fileName = $file->getClientOriginalName();

            try {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Disconnect immediately to save RAM
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                if (empty($rows)) continue;

                // 1. Extract metadata from top header rows (Row 0 to 7)
                $fileRoundNo = null;
                $fileAccountCode = null;
                foreach (array_slice($rows, 0, 8) as $r) {
                    $rowStr = implode(' ', array_filter($r, fn($v) => $v !== null && $v !== ''));
                    if (preg_match('/งวด\s*:\s*([^\s]+)/u', $rowStr, $m)) {
                        $fileRoundNo = trim($m[1]);
                    }
                    if (preg_match('/รหัสผังบัญชี[^\:]*:\s*([^\s]+)/u', $rowStr, $m)) {
                        $fileAccountCode = trim($m[1]);
                    }
                }

                // 2. Find matching Batch in smart_money_batches
                $batch = null;
                if (!empty($inputBatchNo)) {
                    $batch = SmartMoneyBatch::where('batch_no', $inputBatchNo)->first();
                }
                if (!$batch && $fileRoundNo && $fileAccountCode) {
                    $batch = SmartMoneyBatch::where('round_no', $fileRoundNo)
                        ->where('account_code', $fileAccountCode)
                        ->first();
                }
                if (!$batch && $fileRoundNo) {
                    $batch = SmartMoneyBatch::where('round_no', $fileRoundNo)->first();
                }

                $batch_no = $batch ? $batch->batch_no : ($inputBatchNo ?: null);
                $round_no = $fileRoundNo ?: ($batch ? $batch->round_no : null);
                $budget_year = $batch ? $batch->budget_year : $request->budget_year;

                if ($batch_no && !in_array($batch_no, $matchedBatches)) {
                    $matchedBatches[] = $batch_no;
                }

                // 3. Find Table Header Row (HN, ชื่อ-สกุล, จ่ายชดเชยสุทธิ)
                // 3. Find Table Header Row (HN, เลขบัตร, ชื่อ-สกุล, จ่ายชดเชยสุทธิ)
                $headerRowIdx = -1;
                foreach ($rows as $idx => $row) {
                    $rowStr = implode(' ', array_filter($row, fn($v) => $v !== null && $v !== ''));
                    if (str_contains($rowStr, 'HN') || str_contains($rowStr, 'เลขบัตร') || str_contains($rowStr, 'จ่ายชดเชย') || str_contains($rowStr, 'ชื่อ-สกุล') || str_contains($rowStr, 'ลำดับ')) {
                        $headerRowIdx = $idx;
                        break;
                    }
                }

                if ($headerRowIdx === -1) continue;

                $headers = $rows[$headerRowIdx];
                $colMap = [];
                foreach ($headers as $cIdx => $name) {
                    $trimmed = trim((string)$name);
                    if ($trimmed) {
                        $colMap[$trimmed] = $cIdx;
                    }
                }

                // Map column indexes by priority
                $colIdxSeq = null;
                $colIdxRepNo = null;
                $colIdxTransId = null;
                $colIdxHn = null;
                $colIdxAn = null;
                $colIdxCid = null;
                $colIdxName = null;
                $colIdxPtType = null;
                $colIdxRegDate = null;
                $colIdxVstDate = null;
                $colIdxMainFund = null;
                $colIdxSubFund = null;
                $colIdxSubFundDesc = null;
                $colIdxHCode = null;
                $colIdxNetPaid = null;

                foreach ($colMap as $name => $cIdx) {
                    $clean = mb_strtolower(preg_replace('/\s+/', ' ', $name));
                    
                    // Paid Net Amount (Critical: prioritize 'จ่ายชดเชยสุทธิ' and NOT 'ไม่ชดเชย')
                    if (str_contains($clean, 'ไม่ชดเชย') || str_contains($clean, 'ไม่จ่าย')) {
                        continue;
                    }
                    if (str_contains($clean, 'จ่ายชดเชยสุทธิ') || str_contains($clean, 'ชดเชยสุทธิ') || str_contains($clean, 'จ่ายสุทธิ')) {
                        $colIdxNetPaid = $cIdx;
                    } elseif ($colIdxNetPaid === null && (str_contains($clean, 'จ่ายชดเชย') || str_contains($clean, 'จ่ายจริง') || str_contains($clean, 'ชดเชย'))) {
                        $colIdxNetPaid = $cIdx;
                    } elseif ($colIdxNetPaid === null && (str_contains($clean, 'จำนวนเงิน') || str_contains($clean, 'ยอดเงิน'))) {
                        $colIdxNetPaid = $cIdx;
                    }

                    // Trans ID / Invoice No
                    if (str_contains($clean, 'trans id') || str_contains($clean, 'transid') || str_contains($clean, 'เลขที่ใบแจ้งหนี้') || str_contains($clean, 'invoice_no')) {
                        $colIdxTransId = $cIdx;
                    }

                    // Rep No / Round
                    if (str_contains($clean, 'rep no') || str_contains($clean, 'repno') || str_contains($clean, 'งวด')) {
                        $colIdxRepNo = $cIdx;
                    }

                    // HN
                    if ($clean === 'hn' || preg_match('/\bhn\b/i', $name)) {
                        $colIdxHn = $cIdx;
                    }

                    // AN
                    if ($clean === 'an' || preg_match('/\ban\b/i', $name)) {
                        $colIdxAn = $cIdx;
                    }

                    // CID / PID
                    if (str_contains($clean, 'เลขบัตรประชาชน') || str_contains($clean, 'บัตรประชาชน') || str_contains($clean, 'pid') || str_contains($clean, 'vctid') || str_contains($clean, 'เลขบัตร')) {
                        $colIdxCid = $cIdx;
                    }

                    // Name
                    if (str_contains($clean, 'ชื่อ-สกุล') || str_contains($clean, 'ชื่อ') || str_contains($clean, 'นามสกุล')) {
                        $colIdxName = $cIdx;
                    }

                    // Patient Type / Rights
                    if (str_contains($clean, 'สิทธิ์') || str_contains($clean, 'ประเภทผู้ป่วย') || str_contains($clean, 'สิทธิ')) {
                        $colIdxPtType = $cIdx;
                    }

                    // Visit Date
                    if (str_contains($clean, 'วันที่เข้ารับบริการ') || str_contains($clean, 'วันรับบริการ') || str_contains($clean, 'vstdate')) {
                        $colIdxVstDate = $cIdx;
                    } elseif (str_contains($clean, 'วันที่') || str_contains($clean, 'date')) {
                        if ($colIdxRegDate === null) $colIdxRegDate = $cIdx;
                    }

                    // Funds
                    if (str_contains($clean, 'sys_code') || str_contains($clean, 'กองทุนหลัก')) {
                        $colIdxMainFund = $cIdx;
                    }
                    if (str_contains($clean, 'item_code') || str_contains($clean, 'subfund') || str_contains($clean, 'กองทุนย่อย')) {
                        $colIdxSubFund = $cIdx;
                    }
                    if (str_contains($clean, 'ความหมาย subfund') || str_contains($clean, 'รายการ/ประเภทที่ขอเบิก') || str_contains($clean, 'รายการที่ขอเบิก') || str_contains($clean, 'รายละเอียด')) {
                        $colIdxSubFundDesc = $cIdx;
                    }

                    // HCode
                    if (str_contains($clean, 'hcode') || str_contains($clean, 'รหัสหน่วยบริการ')) {
                        $colIdxHCode = $cIdx;
                    }

                    // Sequence
                    if (str_contains($clean, 'ลำดับที่') || str_contains($clean, 'ลำดับ') || str_contains($clean, 'seq')) {
                        if ($colIdxSeq === null) $colIdxSeq = $cIdx;
                    }
                }

                $fileCount = 0;
                $lastHnByCid = [];
                DB::beginTransaction();

                for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (empty(array_filter($row, fn($v) => $v !== null && trim((string)$v) !== ''))) continue;

                    // Check total row
                    $firstCol = trim((string)($row[0] ?? ''));
                    if (str_contains($firstCol, 'รวม') || str_contains($firstCol, 'Total')) continue;

                    $seqNo = $colIdxSeq !== null ? trim((string)($row[$colIdxSeq] ?? '')) : null;
                    $repno = $colIdxRepNo !== null ? trim((string)($row[$colIdxRepNo] ?? '')) : $round_no;
                    $invoiceNo = $colIdxTransId !== null ? trim((string)($row[$colIdxTransId] ?? '')) : null;
                    $hn = $colIdxHn !== null ? trim((string)($row[$colIdxHn] ?? '')) : '';
                    $an = $colIdxAn !== null ? trim((string)($row[$colIdxAn] ?? '')) : null;
                    $cid = $colIdxCid !== null ? trim((string)($row[$colIdxCid] ?? '')) : '';
                    $ptName = $colIdxName !== null ? trim((string)($row[$colIdxName] ?? '')) : '';
                    $ptType = $colIdxPtType !== null ? trim((string)($row[$colIdxPtType] ?? '')) : '';
                    $mainFund = $colIdxMainFund !== null ? trim((string)($row[$colIdxMainFund] ?? '')) : '';
                    $subFund = $colIdxSubFund !== null ? trim((string)($row[$colIdxSubFund] ?? '')) : '';
                    $subFundDesc = $colIdxSubFundDesc !== null ? trim((string)($row[$colIdxSubFundDesc] ?? '')) : '';
                    $hcode = $colIdxHCode !== null ? trim((string)($row[$colIdxHCode] ?? '')) : '10989';

                    // Parse visit date
                    $vstRaw = '';
                    if ($colIdxVstDate !== null && !empty($row[$colIdxVstDate])) {
                        $vstRaw = trim((string)$row[$colIdxVstDate]);
                    } elseif ($colIdxRegDate !== null && !empty($row[$colIdxRegDate])) {
                        $vstRaw = trim((string)$row[$colIdxRegDate]);
                    }
                    $vstdate = $this->parseDate($vstRaw);

                    // Parse paid amount
                    $receiveTotal = 0.00;
                    if ($colIdxNetPaid !== null && isset($row[$colIdxNetPaid])) {
                        $receiveTotal = $this->cleanNumber($row[$colIdxNetPaid]);
                    }

                    // Backfill HN from CID
                    if (!empty($cid)) {
                        if (!empty($hn)) {
                            $lastHnByCid[$cid] = $hn;
                        } elseif (isset($lastHnByCid[$cid])) {
                            $hn = $lastHnByCid[$cid];
                        }
                    }

                    if (empty($hn) && empty($cid) && empty($ptName)) continue;

                    // Derive budget year if not set
                    $rowBYear = $budget_year;
                    if (!$rowBYear && $batch) {
                        $rowBYear = $batch->budget_year;
                    }
                    if (!$rowBYear && $vstdate) {
                        $rowBYear = $this->getBudgetYear($vstdate);
                    }

                    $matchAttributes = [
                        'round_no' => $round_no,
                    ];
                    if ($invoiceNo) {
                        $matchAttributes['invoice_no'] = $invoiceNo;
                    } elseif ($seqNo) {
                        $matchAttributes['seq_no'] = $seqNo;
                    } else {
                        $matchAttributes['hn'] = $hn;
                        if ($vstdate) $matchAttributes['vstdate'] = $vstdate;
                        if ($subFund) $matchAttributes['sub_fund'] = $subFund;
                    }

                    SmartMoneyDetail::updateOrCreate(
                        $matchAttributes,
                        [
                            'batch_no' => $batch_no,
                            'transfer_date' => $batch ? $batch->transfer_date : null,
                            'hn' => $hn,
                            'an' => !empty($an) ? $an : null,
                            'pt_type' => $ptType ?: (!empty($an) ? 'ผู้ป่วยใน' : 'ผู้ป่วยนอก'),
                            'cid' => $cid,
                            'pt_name' => $ptName,
                            'receive_total' => $receiveTotal,
                            'repno' => $repno,
                            'main_fund' => $mainFund,
                            'sub_fund' => $subFund,
                            'sub_fund_desc' => $subFundDesc,
                            'hcode' => $hcode,
                            'seq_no' => $seqNo,
                            'invoice_no' => $invoiceNo,
                            'budget_year' => $rowBYear,
                        ]
                    );
                    $fileCount++;
                }

                DB::commit();

                $totalProcessedFiles++;
                $totalImportedPatients += $fileCount;
                $fileSummaries[] = [
                    'file' => $fileName,
                    'round_no' => $round_no,
                    'batch_no' => $batch_no,
                    'count' => $fileCount
                ];

            } catch (\Exception $fe) {
                DB::rollBack();
                Log::error("SmartMoney Detail Import Error on file {$fileName}: " . $fe->getMessage());
            }
        }

        if ($totalProcessedFiles === 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถประมวลผลไฟล์ที่เลือกได้ หรือไม่พบรูปแบบหัวตารางที่ถูกต้อง'], 422);
        }

        $batchCount = count($matchedBatches);
        $msg = "นำเข้าไฟล์รายบุคคลสำเร็จ {$totalProcessedFiles} ไฟล์ รวม {$totalImportedPatients} รายการ";
        if ($batchCount > 0) {
            $msg .= " (ผูกโยงกับ {$batchCount} Batch: " . implode(', ', array_slice($matchedBatches, 0, 5)) . ($batchCount > 5 ? ' และอื่นๆ' : '') . ")";
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg,
            'files_count' => $totalProcessedFiles,
            'total_patients' => $totalImportedPatients,
            'details' => $fileSummaries,
            'matched_batches' => $matchedBatches,
        ]);
    }

    /**
     * Update Receipt and Auto-Sync to STM tables (One-Way: Smart Money -> STM)
     */
    public function updateReceipt(Request $request)
    {
        if (Auth::user()->status != 'admin' && Auth::user()->allow_receipt != 'Y') {
            return response()->json(['status' => 'error', 'message' => 'ท่านไม่มีสิทธิ์ในการออกหรือแก้ไขใบเสร็จรับเงิน'], 403);
        }

        $request->validate([
            'batch_no' => 'required',
            'receive_no' => 'required|max:50',
            'receipt_date' => 'required|date',
        ]);

        try {
            $batch_no = $request->batch_no;
            $receive_no = trim($request->receive_no);
            $receipt_date = $request->receipt_date;
            $receipt_by = auth()->user()->name ?? 'system';

            // 1. Update Smart Money Batches for this batch_no
            $affectedBatches = SmartMoneyBatch::where('batch_no', $batch_no)->get();
            if ($affectedBatches->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล Batch ' . $batch_no], 404);
            }

            SmartMoneyBatch::where('batch_no', $batch_no)->update([
                'receive_no' => $receive_no,
                'receipt_date' => $receipt_date,
                'receipt_by' => $receipt_by,
                'updated_at' => now(),
            ]);

            // 2. One-Way Auto-Sync to STM tables based on round_no (Group all distinct receipts in this round)
            $stmSynced = 0;
            $roundNos = $affectedBatches->pluck('round_no')->filter()->unique();

            foreach ($roundNos as $roundNo) {
                $groupedReceipts = SmartMoneyBatch::where('round_no', $roundNo)
                    ->whereNotNull('receive_no')
                    ->where('receive_no', '<>', '')
                    ->pluck('receive_no')
                    ->unique()
                    ->values()
                    ->toArray();

                $combinedReceiptNo = implode(', ', $groupedReceipts);
                $latestReceiptDate = SmartMoneyBatch::where('round_no', $roundNo)->max('receipt_date') ?: $receipt_date;

                $stmSynced += $this->syncReceiptToAllStmTables($roundNo, $combinedReceiptNo, $latestReceiptDate, $receipt_by);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'ออกใบเสร็จและส่งข้อมูลเข้าสู่ระบบ STM เรียบร้อยแล้ว (อัปเดต STM ' . $stmSynced . ' ตาราง)',
                'batch_no' => $batch_no,
                'receive_no' => $receive_no,
                'receipt_date' => $receipt_date,
                'receipt_by' => $receipt_by,
                'stm_synced_count' => $stmSynced,
            ]);

        } catch (\Exception $e) {
            Log::error('SmartMoney updateReceipt Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Sync Combined Receipts to All Statement (STM) Tables for a specific Round
     */
    private function syncReceiptToAllStmTables($roundNo, $receiveNo, $receiptDate, $receiptBy)
    {
        if (empty($roundNo) || empty($receiveNo)) return 0;

        $stmTables = [
            'stm_ucs', 'stm_ucs_kidney', 'stm_seamless_dmis', 'stm_ofc', 'stm_ofc_csop',
            'stm_ofc_cipn', 'stm_bkk', 'stm_bkk_kidney', 'stm_bmt', 'stm_bmt_kidney',
            'stm_srt', 'stm_pvt', 'stm_lgo', 'stm_lgo_kidney', 'stm_sss_kidney'
        ];

        $updatedCount = 0;

        foreach ($stmTables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table) && 
                \Illuminate\Support\Facades\Schema::hasColumn($table, 'round_no') && 
                \Illuminate\Support\Facades\Schema::hasColumn($table, 'receive_no')) {
                
                $cnt = DB::table($table)
                    ->where('round_no', $roundNo)
                    ->update([
                        'receive_no' => $receiveNo,
                        'receipt_date' => $receiptDate,
                        'receipt_by' => $receiptBy ?: 'SmartMoney Sync',
                    ]);
                $updatedCount += $cnt;
            }
        }

        return $updatedCount;
    }

    /**
     * Smart Two-Way Sync between Smart Money and STM Tables
     */
    public function syncTwoWayStm(Request $request)
    {
        // One-way push from Smart Money to STM is safer because Smart Money maintains batch-level granularity
        return $this->syncAllReceiptsToStm($request);
    }

    /**
     * Sync All Receipts from Smart Money to STM Tables (One-Way: Smart Money -> STM)
     * Aggregates multiple batch receipts per round_no with GROUP_CONCAT / implode
     */
    public function syncAllReceiptsToStm(Request $request)
    {
        try {
            $batches = SmartMoneyBatch::whereNotNull('receive_no')
                ->where('receive_no', '<>', '')
                ->whereNotNull('round_no')
                ->where('round_no', '<>', '')
                ->get(['round_no', 'receive_no', 'receipt_date', 'receipt_by']);

            if ($batches->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'ไม่พบรายการที่ออกใบเสร็จใน Smart Money สำหรับการซิงก์',
                    'synced_count' => 0,
                ]);
            }

            $stmTables = [
                'stm_ucs', 'stm_ucs_kidney', 'stm_seamless_dmis', 'stm_ofc', 'stm_ofc_csop',
                'stm_ofc_cipn', 'stm_bkk', 'stm_bkk_kidney', 'stm_bmt', 'stm_bmt_kidney',
                'stm_srt', 'stm_pvt', 'stm_lgo', 'stm_lgo_kidney', 'stm_sss_kidney'
            ];

            // Group batches by round_no
            $groupedByRound = $batches->groupBy('round_no');
            $totalChecked = 0;
            $totalUpdated = 0;

            foreach ($groupedByRound as $roundNo => $bList) {
                $receipts = $bList->pluck('receive_no')->filter()->unique()->values()->toArray();
                $combinedReceiptNo = implode(', ', $receipts);
                $latestReceiptDate = $bList->max('receipt_date');
                $latestReceiptBy = $bList->pluck('receipt_by')->filter()->last() ?: 'SmartMoney Sync';

                foreach ($stmTables as $table) {
                    if (\Illuminate\Support\Facades\Schema::hasTable($table) && 
                        \Illuminate\Support\Facades\Schema::hasColumn($table, 'round_no') && 
                        \Illuminate\Support\Facades\Schema::hasColumn($table, 'receive_no')) {
                        
                        $matchingRows = DB::table($table)->where('round_no', $roundNo)->count();
                        if ($matchingRows > 0) {
                            $totalChecked += $matchingRows;
                            
                            $cnt = DB::table($table)
                                ->where('round_no', $roundNo)
                                ->where(function($q) use ($combinedReceiptNo) {
                                    $q->whereNull('receive_no')
                                      ->orWhere('receive_no', '!=', $combinedReceiptNo);
                                })
                                ->update([
                                    'receive_no' => $combinedReceiptNo,
                                    'receipt_date' => $latestReceiptDate,
                                    'receipt_by' => $latestReceiptBy,
                                ]);
                            $totalUpdated += $cnt;
                        }
                    }
                }
            }

            if ($totalUpdated > 0) {
                $msg = "ซิงก์เลขที่ใบเสร็จไปยังตาราง STM สำเร็จ (อัปเดตใหม่ " . number_format($totalUpdated) . " รายการ จากทั้งหมด " . number_format($totalChecked) . " รายการ)";
            } elseif ($totalChecked > 0) {
                $msg = "ข้อมูลเลขที่ใบเสร็จในระบบ STM ตรงกันและเป็นปัจจุบันครบถ้วนแล้ว 100% (" . number_format($totalChecked) . " รายการ)";
            } else {
                $msg = "ตรวจสอบเรียบร้อย ไม่พบงวดที่ตรงกันในตาราง Statement";
            }

            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'updated_count' => $totalUpdated,
                'total_checked' => $totalChecked,
            ]);
        } catch (\Exception $e) {
            Log::error('SmartMoney syncAllReceiptsToStm error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Pull Receipts from all STM Tables into Smart Money (Direction 2: STM -> Smart Money)
     */
    public function syncReceiptsFromStm(Request $request)
    {
        try {
            $stmTables = [
                'stm_ucs',
                'stm_ucs_kidney',
                'stm_seamless_dmis',
                'stm_ofc',
                'stm_ofc_csop',
                'stm_ofc_cipn',
                'stm_bkk',
                'stm_bkk_kidney',
                'stm_bmt',
                'stm_bmt_kidney',
                'stm_srt',
                'stm_pvt',
                'stm_lgo',
                'stm_lgo_kidney',
                'stm_sss_kidney',
            ];

            $batches = SmartMoneyBatch::where(function($q) {
                $q->whereNull('receive_no')->orWhere('receive_no', '');
            })->whereNotNull('round_no')->where('round_no', '<>', '')->get();

            $totalPending = $batches->count();
            $syncedCount = 0;
            $syncedBatches = [];

            if ($totalPending > 0) {
                $roundList = $batches->pluck('round_no')->unique()->values()->toArray();

                foreach ($stmTables as $table) {
                    if (\Illuminate\Support\Facades\Schema::hasTable($table) && 
                        \Illuminate\Support\Facades\Schema::hasColumn($table, 'round_no') && 
                        \Illuminate\Support\Facades\Schema::hasColumn($table, 'receive_no')) {
                        
                        $stmRows = DB::table($table)
                            ->whereIn('round_no', $roundList)
                            ->whereNotNull('receive_no')
                            ->where('receive_no', '<>', '')
                            ->get(['round_no', 'receive_no', 'receipt_date', 'receipt_by']);

                        foreach ($stmRows as $stmRow) {
                            $matchingBatches = $batches->where('round_no', $stmRow->round_no);
                            foreach ($matchingBatches as $b) {
                                if (empty($b->receive_no)) {
                                    $rDate = !empty($stmRow->receipt_date) ? $stmRow->receipt_date : now()->toDateString();
                                    $rBy = !empty($stmRow->receipt_by) ? $stmRow->receipt_by : 'STM Sync';
                                    
                                    $b->update([
                                        'receive_no' => $stmRow->receive_no,
                                        'receipt_date' => $rDate,
                                        'receipt_by' => $rBy,
                                    ]);
                                    $b->receive_no = $stmRow->receive_no; // mark in-memory
                                    $syncedCount++;
                                    $syncedBatches[] = $b->batch_no;
                                }
                            }
                        }
                    }
                }
            }

            $distinctBatches = count(array_unique($syncedBatches));

            if ($syncedCount > 0) {
                $msg = "ดึงเลขที่ใบเสร็จจากระบบ STM เข้าสู่ Smart Money สำเร็จ (" . number_format($syncedCount) . " งวด / " . number_format($distinctBatches) . " Batches)";
            } else {
                $msg = "ข้อมูลเป็นปัจจุบันแล้ว 100% (ไม่พบเลขที่ใบเสร็จใหม่ในระบบ STM สำหรับงวดที่รอออกใบเสร็จทั้ง " . number_format($totalPending) . " รายการ)";
            }

            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'synced_count' => $syncedCount,
                'batch_count' => $distinctBatches,
                'total_checked' => $totalPending,
            ]);
        } catch (\Exception $e) {
            Log::error('SmartMoney syncReceiptsFromStm error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Active Smart Money Token (JWT Bearer Token from ThaiD / e-Claim SSO)
     */
    protected function getActiveSmartMoneyToken()
    {
        $token = null;
        if (auth()->check()) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                $token = DB::table('users')->where('id', auth()->id())->value('eclaim_session_token');
            }
            if (!$token) {
                $token = session('eclaim_session_token');
            }
        } else {
            $token = session('eclaim_session_token');
        }

        if (!$token) return null;

        // Extract ACCESS_TOKEN (JWT)
        if (preg_match('/(?:ACCESS_TOKEN|KEYCLOAK_IDENTITY)=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $token, $m)) {
            return $m[1];
        }

        if (strpos($token, '.') !== false && substr_count($token, '.') >= 2) {
            return trim($token);
        }

        return null;
    }

    /**
     * Search Bot Statements for Date Range via live API from smt.nhso.go.th and compare with DB
     */
    public function searchBotStatements(Request $request)
    {
        $bearerToken = $this->getActiveSmartMoneyToken();
        if (!$bearerToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ ThaiD หรือ Session หมดอายุ กรุณาเข้าสู่ระบบด้วย ThaiD ก่อนค้นหาข้อมูล'
            ], 401);
        }

        $startDate = $this->parseDate($request->start_date);
        $endDate = $this->parseDate($request->end_date);
        $keyword = trim((string)$request->keyword);

        if (!$startDate) $startDate = date('Y-m-01');
        if (!$endDate) $endDate = date('Y-m-d');

        // Prepare Thai Buddhist Date strings (dd/mm/YYYY) for smt.nhso.go.th API
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);
        $startThaiStr = date('d/m/', $startTs) . ((int)date('Y', $startTs) + 543);
        $endThaiStr = date('d/m/', $endTs) . ((int)date('Y', $endTs) + 543);
        $budgetYear = (string)$this->getBudgetYear($endDate);

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        if (!$hospcode && \Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
            $hospcode = DB::table('opdconfig')->value('hospitalcode');
        }
        $hospcode = $hospcode ?: '10989';
        $vendorId = str_pad($hospcode, 10, '0', STR_PAD_LEFT);

        try {
            $postData = [
                'vendorSearchConditionCode' => '1',
                'zoneId' => '',
                'provinceId' => '',
                'vendorId' => $vendorId,
                'vendorId5Digit' => '',
                'budgetSource' => '',
                'budgetYear' => $budgetYear,
                'transferStartDate' => $startThaiStr,
                'transferEndDate' => $endThaiStr,
                'hospType' => '',
                'isTest' => '',
            ];

            $res = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
                'Authorization' => 'Bearer ' . $bearerToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/plain, */*',
                'Origin' => 'https://smt.nhso.go.th',
                'Referer' => 'https://smt.nhso.go.th/smtf/',
            ])->withoutVerifying()->timeout(25)->post('https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReport/search', $postData);

            if (in_array($res->status(), [401, 403])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session การเชื่อมต่อกับ สปสช. (ThaiD) หมดอายุ กรุณากดปุ่ม "เข้าสู่ระบบ (ThaiD)" เพื่อเชื่อมต่อใหม่'
                ], 401);
            }

            if ($res->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถดึงข้อมูลจากระบบ Smart Money สปสช. ได้ (HTTP ' . $res->status() . ')'
                ], 500);
            }

            $json = $res->json();
            $datas = $json['datas'] ?? [];

            $batchMap = [];
            foreach ($datas as $item) {
                $bNo = trim((string)($item['batchNo'] ?? ''));
                $accCode = trim((string)($item['mophId'] ?? ''));
                $rNo = trim((string)($item['refDocNo'] ?? ''));

                if (empty($bNo) || empty($accCode) || $bNo === '-') continue;

                // Parse transfer date
                $rawRunDt = trim((string)($item['runDt'] ?? ''));
                $rawPostDt = trim((string)($item['postingDate'] ?? ''));
                $tDate = null;
                if (!empty($rawRunDt)) {
                    $tDate = date('Y-m-d', strtotime($rawRunDt));
                } elseif (!empty($rawPostDt) && strlen($rawPostDt) === 8) {
                    $y = (int)substr($rawPostDt, 0, 4);
                    if ($y > 2400) $y -= 543;
                    $tDate = sprintf('%04d-%02d-%02d', $y, substr($rawPostDt, 4, 2), substr($rawPostDt, 6, 2));
                }
                if (!$tDate) continue;

                $fMain = trim((string)($item['fundName'] ?? ($item['fundDescr'] ?? '')));
                $fSub = trim((string)($item['efundDesc'] ?? ($item['fundGroupDescr'] ?? '')));
                $amt = (float)($item['amount'] ?? 0);
                $holdAmt = (float)($item['wait'] ?? 0);
                $deductAmt = (float)($item['debt'] ?? 0);
                $guaranteeAmt = (float)($item['bond'] ?? 0);
                $taxAmt = (float)($item['vat'] ?? 0);
                $remainAmt = (float)($item['total'] ?? 0);
                $offsetAmt = (float)($item['odbt'] ?? 0);
                $netAmt = (float)($item['total'] ?? 0);
                $paymFile = trim((string)($item['downloadPAYMFileName'] ?? ''));

                // Keyword filter
                if (!empty($keyword)) {
                    $rowText = "$bNo $rNo $accCode $fMain $fSub";
                    if (stripos($rowText, $keyword) === false) continue;
                }

                $key = $bNo . '_' . $accCode . '_' . $rNo;
                if (!isset($batchMap[$key])) {
                    // Check DB for existing status
                    $existing = SmartMoneyBatch::where('batch_no', $bNo)
                        ->where('account_code', $accCode)
                        ->when(!empty($rNo) && $rNo !== '-', function($q) use ($rNo) {
                            $q->where('round_no', $rNo);
                        })
                        ->first();

                    $batchMap[$key] = [
                        'id' => $existing ? $existing->id : null,
                        'transfer_date' => $tDate,
                        'transfer_date_thai' => DateThai($tDate),
                        'batch_no' => $bNo,
                        'round_no' => $rNo ?: '-',
                        'account_code' => $accCode,
                        'fund_main' => $fMain,
                        'fund_sub' => $fSub,
                        'fund_full' => trim("$fMain $fSub"),
                        'amount' => $amt,
                        'hold_amount' => $holdAmt,
                        'deduct_amount' => $deductAmt,
                        'guarantee_amount' => $guaranteeAmt,
                        'tax_amount' => $taxAmt,
                        'remain_amount' => $remainAmt,
                        'offset_amount' => $offsetAmt,
                        'net_amount' => $netAmt,
                        'net_amount_formatted' => number_format($netAmt, 2),
                        'file_name' => $paymFile ?: "NHSO_SMT_{$bNo}.pdf",
                        'download_paym_file' => $paymFile,
                        'is_imported' => !empty($existing),
                        'has_receipt' => !empty($existing && $existing->receive_no),
                        'receive_no' => $existing ? ($existing->receive_no ?: '') : '',
                        'receipt_date' => ($existing && $existing->receipt_date) ? DateThai($existing->receipt_date) : '',
                        'receipt_by' => $existing ? ($existing->receipt_by ?: '') : '',
                    ];
                }
            }

            // Sort results by transfer_date desc, batch_no desc
            $results = array_values($batchMap);
            usort($results, function($a, $b) {
                $cmp = strcmp($b['transfer_date'], $a['transfer_date']);
                if ($cmp !== 0) return $cmp;
                return strcmp($b['batch_no'], $a['batch_no']);
            });

            $distinctBatches = count(array_unique(array_filter(array_column($results, 'batch_no'), fn($v) => !empty($v) && $v !== '-')));

            return response()->json([
                'status' => 'success',
                'start_date' => DateThai($startDate),
                'end_date' => DateThai($endDate),
                'count' => count($results),
                'batch_count' => $distinctBatches ?: count($results),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('SmartMoney searchBotStatements error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับ สปสช.: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import Bot Statements (Selected Batches from Modal)
     */
    public function importBotStatements(Request $request)
    {
        ini_set('max_execution_time', 300);

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json(['status' => 'error', 'message' => 'กรุณาเลือกรายการที่ต้องการนำเข้าอย่างน้อย 1 รายการ'], 400);
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $syncedStmCount = 0;
        $syncedDetailsCount = 0;
        $savedBatches = [];

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $batchNo = trim($item['batch_no'] ?? '');
                $accountCode = trim($item['account_code'] ?? '');
                $roundNo = trim($item['round_no'] ?? '');
                $transferDate = !empty($item['transfer_date']) ? $this->parseDate($item['transfer_date']) : null;
                $amount = $this->cleanNumber($item['amount'] ?? 0);
                $holdAmount = $this->cleanNumber($item['hold_amount'] ?? 0);
                $deductAmount = $this->cleanNumber($item['deduct_amount'] ?? 0);
                $guaranteeAmount = $this->cleanNumber($item['guarantee_amount'] ?? 0);
                $taxAmount = $this->cleanNumber($item['tax_amount'] ?? 0);
                $remainAmount = $this->cleanNumber($item['remain_amount'] ?? 0);
                $offsetAmount = $this->cleanNumber($item['offset_amount'] ?? 0);
                $netAmount = $this->cleanNumber($item['net_amount'] ?? 0);
                $fundMain = trim($item['fund_main'] ?? '');
                $fundSub = trim($item['fund_sub'] ?? '');
                $fileName = trim($item['file_name'] ?? '');
                $receiveNo = trim($item['receive_no'] ?? '');
                $receiptDate = !empty($item['receipt_date']) ? $this->parseDate($item['receipt_date']) : null;
                $receiptBy = trim($item['receipt_by'] ?? '');

                if (empty($batchNo) || empty($accountCode) || $batchNo === '-' || $accountCode === '-') {
                    continue;
                }

                // Check existing batch
                $batch = SmartMoneyBatch::where('batch_no', $batchNo)
                    ->where('account_code', $accountCode)
                    ->when(!empty($roundNo) && $roundNo !== '-', function($q) use ($roundNo) {
                        $q->where('round_no', $roundNo);
                    })
                    ->first();

                if ($batch) {
                    // Update batch without erasing existing receive_no
                    if ($transferDate) $batch->transfer_date = $transferDate;
                    if (!empty($fundMain)) $batch->fund_main = $fundMain;
                    if (!empty($fundSub)) $batch->fund_sub = $fundSub;
                    if ($amount > 0) $batch->amount = $amount;
                    if ($holdAmount > 0) $batch->hold_amount = $holdAmount;
                    if ($deductAmount > 0) $batch->deduct_amount = $deductAmount;
                    if ($guaranteeAmount > 0) $batch->guarantee_amount = $guaranteeAmount;
                    if ($taxAmount > 0) $batch->tax_amount = $taxAmount;
                    if ($remainAmount > 0) $batch->remain_amount = $remainAmount;
                    if ($offsetAmount > 0) $batch->offset_amount = $offsetAmount;
                    if ($netAmount > 0) $batch->net_amount = $netAmount;
                    if (!empty($fileName)) $batch->file_name = $fileName;

                    // If existing receive_no is empty and new receive_no is provided, save it
                    if (empty($batch->receive_no) && !empty($receiveNo)) {
                        $batch->receive_no = $receiveNo;
                        $batch->receipt_date = $receiptDate;
                        $batch->receipt_by = $receiptBy ?: (auth()->check() ? auth()->user()->name : null);
                    }
                    $batch->save();
                    $updatedCount++;
                } else {
                    // Create new batch
                    $batch = SmartMoneyBatch::create([
                        'transfer_date' => $transferDate ?: date('Y-m-d'),
                        'batch_no' => $batchNo,
                        'round_no' => $roundNo !== '-' ? $roundNo : null,
                        'account_code' => $accountCode,
                        'fund_main' => $fundMain,
                        'fund_sub' => $fundSub,
                        'amount' => $amount,
                        'hold_amount' => $holdAmount,
                        'deduct_amount' => $deductAmount,
                        'guarantee_amount' => $guaranteeAmount,
                        'tax_amount' => $taxAmount,
                        'remain_amount' => $remainAmount,
                        'offset_amount' => $offsetAmount,
                        'net_amount' => $netAmount,
                        'file_name' => $fileName,
                        'receive_no' => !empty($receiveNo) ? $receiveNo : null,
                        'receipt_date' => !empty($receiveNo) ? $receiptDate : null,
                        'receipt_by' => !empty($receiveNo) ? ($receiptBy ?: (auth()->check() ? auth()->user()->name : null)) : null,
                        'budget_year' => $transferDate ? $this->getBudgetYear($transferDate) : (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)),
                    ]);
                    $insertedCount++;
                }

                $savedBatches[] = $batch;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SmartMoney importBotStatements error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()], 500);
        }

        // Process patient details strictly from Smart Money Transfer (SMTF)
        foreach ($savedBatches as $batch) {
            try {
                $syncedDetailsCount += $this->autoImportMatchingDetailFiles(
                    $batch->batch_no,
                    $batch->round_no,
                    $batch->account_code,
                    $batch->budget_year,
                    $batch->transfer_date
                );
            } catch (\Exception $e) {
                Log::warning("Auto-import details warning for batch {$batch->batch_no}: " . $e->getMessage());
            }

            // If receipt exists, sync to STM tables automatically
            if (!empty($batch->receive_no) && !empty($batch->round_no)) {
                $synced = $this->syncReceiptToAllStmTables($batch->round_no, $batch->receive_no, $batch->receipt_date, $batch->receipt_by);
                $syncedStmCount += $synced;
            }
        }

        $msg = 'นำเข้าข้อมูล Smart Money สำเร็จ ' . count($items) . ' รายการ';
        if ($syncedDetailsCount > 0) {
            $msg .= " (พร้อมดึงข้อมูลผู้ป่วยรายบุคคล $syncedDetailsCount รายการ)";
        }

        return response()->json([
            'status' => 'success',
            'message' => $msg,
            'total' => count($items),
            'inserted' => $insertedCount,
            'updated' => $updatedCount,
            'synced_stm' => $syncedStmCount,
            'synced_details' => $syncedDetailsCount,
        ]);
    }

    /**
     * Sync Bot Data for Date Range
     */
    public function syncBotData(Request $request)
    {
        $startDate = $this->parseDate($request->start_date);
        $endDate = $this->parseDate($request->end_date);

        if (!$startDate) $startDate = date('Y-m-01');
        if (!$endDate) $endDate = date('Y-m-d');

        try {
            $query = SmartMoneyBatch::whereBetween('transfer_date', [$startDate, $endDate]);
            $totalCount = (clone $query)->count();
            $inserted = (clone $query)->where(function($q) {
                $q->whereNull('receive_no')->orWhere('receive_no', '');
            })->count();
            $updated = (clone $query)->whereNotNull('receive_no')->where('receive_no', '<>', '')->count();

            if ($totalCount === 0) {
                // Fallback to latest batches
                $totalCount = SmartMoneyBatch::count();
                $inserted = SmartMoneyBatch::where(function($q) {
                    $q->whereNull('receive_no')->orWhere('receive_no', '');
                })->count();
                $updated = SmartMoneyBatch::whereNotNull('receive_no')->where('receive_no', '<>', '')->count();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'ดึงข้อมูลสำเร็จ',
                'start_date' => DateThai($startDate),
                'end_date' => DateThai($endDate),
                'total_rows' => $totalCount,
                'inserted' => $inserted,
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete Batch
     */
    public function deleteBatch(Request $request)
    {
        if (Auth::user()->status != 'admin') {
            return response()->json(['status' => 'error', 'message' => 'ท่านไม่มีสิทธิ์ในการลบข้อมูล (เฉพาะผู้ดูแลระบบ Admin เท่านั้น)'], 403);
        }

        $request->validate(['batch_no' => 'required']);
        try {
            SmartMoneyBatch::where('batch_no', $request->batch_no)->delete();
            SmartMoneyDetail::where('batch_no', $request->batch_no)->delete();
            return response()->json(['status' => 'success', 'message' => 'ลบข้อมูลสำเร็จ']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Parse various Thai and Western date formats into Y-m-d
     */
    protected function parseDate($str)
    {
        if (empty($str)) return null;
        $str = trim((string)$str);

        $monthMap = [
            'ม.ค.' => 1, 'มกราคม' => 1,
            'ก.พ.' => 2, 'กุมภาพันธ์' => 2,
            'มี.ค.' => 3, 'มีนาคม' => 3,
            'เม.ย.' => 4, 'เมษายน' => 4,
            'พ.ค.' => 5, 'พฤษภาคม' => 5,
            'มิ.ย.' => 6, 'มิถุนายน' => 6,
            'ก.ค.' => 7, 'กรกฎาคม' => 7,
            'ส.ค.' => 8, 'สิงหาคม' => 8,
            'ก.ย.' => 9, 'กันยายน' => 9,
            'ต.ค.' => 10, 'ตุลาคม' => 10,
            'พ.ย.' => 11, 'พฤศจิกายน' => 11,
            'ธ.ค.' => 12, 'ธันวาคม' => 12
        ];

        foreach ($monthMap as $mStr => $mNum) {
            if (stripos($str, $mStr) !== false) {
                if (preg_match('/(\d{1,2})\s+' . preg_quote($mStr, '/') . '\s+(\d{2,4})/', $str, $matches)) {
                    $d = (int)$matches[1];
                    $y = (int)$matches[2];
                    if ($y < 100) $y += 2500;
                    if ($y > 2400) $y -= 543;
                    return sprintf('%04d-%02d-%02d', $y, $mNum, $d);
                }
            }
        }

        // Pattern: dd/mm/yyyy
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $str, $matches)) {
            $d = (int)$matches[1];
            $m = (int)$matches[2];
            $y = (int)$matches[3];
            if ($y > 2400) $y -= 543;
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }

        // Pattern: yyyy-mm-dd
        if (preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $str, $matches)) {
            $y = (int)$matches[1];
            $m = (int)$matches[2];
            $d = (int)$matches[3];
            if ($y > 2400) $y -= 543;
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }

        return null;
    }

    /**
     * Clean numeric string
     */
    protected function cleanNumber($val)
    {
        if (is_numeric($val)) return (float)$val;
        $cleaned = str_replace([',', ' ', 'บาท'], '', (string)$val);
        return is_numeric($cleaned) ? (float)$cleaned : 0.00;
    }

    /**
     * Helper: Get Thai budget year from date
     */
    protected function getBudgetYear($dateStr)
    {
        if (empty($dateStr)) return date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        $time = strtotime($dateStr);
        if (!$time) return date('Y') + 543 + (date('m') >= 10 ? 1 : 0);
        $year = (int)date('Y', $time);
        $month = (int)date('m', $time);
        return $year + 543 + ($month >= 10 ? 1 : 0);
    }

    /**
     * Auto-import or link matching detail files for a batch (Smart Money Details only)
     */
    protected function autoImportMatchingDetailFiles($batchNo, $roundNo, $accountCode, $budgetYear, $transferDate = null)
    {
        if (empty($roundNo) && empty($accountCode)) return 0;

        // 1. Link any existing details with this round_no
        if (!empty($roundNo) && !empty($batchNo)) {
            SmartMoneyDetail::where('round_no', $roundNo)
                ->where(function($q) {
                    $q->whereNull('batch_no')->orWhere('batch_no', '');
                })
                ->update([
                    'batch_no' => $batchNo,
                    'budget_year' => $budgetYear,
                ]);
        }

        // 2. Check existing details already in smart_money_details for this batch/round
        $existingDetails = SmartMoneyDetail::where('batch_no', $batchNo)
            ->when(!empty($roundNo), function($q) use ($roundNo) {
                $q->orWhere('round_no', $roundNo);
            })
            ->where('receive_total', '>', 0)
            ->count();

        if ($existingDetails > 0) {
            return $existingDetails;
        }

        // 3. If 0 details found, automatically download strictly from SMT Live via worker
        if (!empty($roundNo) && $roundNo !== '-') {
            try {
                $scriptPath = base_path('tools/smt/download_detail.js');
                if (file_exists($scriptPath)) {
                    $cmd = sprintf(
                        'node "%s" %s %s %s %s %s',
                        $scriptPath,
                        escapeshellarg($batchNo),
                        escapeshellarg($roundNo),
                        escapeshellarg($transferDate ?: date('Y-m-d')),
                        escapeshellarg($accountCode ?: ''),
                        escapeshellarg('10989')
                    );

                    exec($cmd, $output, $returnCode);
                    $rawOutput = implode("\n", $output);
                    $result = json_decode($rawOutput, true);
                    if ($result && isset($result['status']) && $result['status'] === 'success') {
                        return (int)($result['count'] ?? 0);
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Auto-download SMT details failed for batch {$batchNo}: " . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Sync Patient Details strictly from Smart Money Transfer (SMTF) Live
     */
    public function syncDetailFromSmt(Request $request, $batch_no)
    {
        ini_set('max_execution_time', 300);

        $batch = SmartMoneyBatch::where('batch_no', $batch_no)->first();
        if (!$batch) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบข้อมูล Batch ' . $batch_no], 404);
        }

        $roundNo = $batch->round_no;
        if (empty($roundNo) || $roundNo === '-') {
            return response()->json(['status' => 'error', 'message' => 'Batch นี้ไม่มีเลขงวด (Round No) สำหรับค้นหารายบุคคลใน SMT'], 422);
        }

        // 1. Check if already populated in smart_money_details with positive sum
        $existingCount = SmartMoneyDetail::where('batch_no', $batch_no)->orWhere('round_no', $roundNo)->count();
        $existingSum = SmartMoneyDetail::where('batch_no', $batch_no)->orWhere('round_no', $roundNo)->sum('receive_total');
        if ($existingCount > 0 && $existingSum > 0 && !$request->force_sync) {
            return response()->json([
                'status' => 'success',
                'source' => 'existing_details',
                'count' => $existingCount,
                'total_amount' => (float)$existingSum,
                'total_amount_formatted' => number_format($existingSum, 2),
                'message' => "มีข้อมูลรายบุคคลในระบบแล้ว {$existingCount} รายการ (" . number_format($existingSum, 2) . " บาท)",
            ]);
        }

        // 2. Live download strictly from SMT (Smart Money Transfer) via Worker
        $scriptPath = base_path('tools/smt/download_detail.js');
        if (!file_exists($scriptPath)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบสคริปต์ดาวน์โหลด SMT'], 500);
        }

        $cmd = sprintf(
            'node "%s" %s %s %s %s %s',
            $scriptPath,
            escapeshellarg($batch->batch_no),
            escapeshellarg($roundNo),
            escapeshellarg($batch->transfer_date ?: date('Y-m-d')),
            escapeshellarg($batch->account_code ?: ''),
            escapeshellarg('10989')
        );

        exec($cmd, $output, $returnCode);
        $rawOutput = implode("\n", $output);

        $result = json_decode($rawOutput, true);
        if ($result && isset($result['status']) && $result['status'] === 'success') {
            $count = $result['count'] ?? 0;
            $sum = $result['total_amount'] ?? 0;
            return response()->json([
                'status' => 'success',
                'source' => 'smt_live',
                'count' => $count,
                'total_amount' => (float)$sum,
                'total_amount_formatted' => number_format($sum, 2),
                'message' => "ดึงข้อมูลผู้ป่วยรายบุคคลจาก SMT สำเร็จ " . number_format($count) . " รายการ (" . number_format($sum, 2) . " บาท)",
            ]);
        } else {
            $errMsg = ($result && !empty($result['message'])) ? $result['message'] : 'ไม่สามารถดึงข้อมูลจาก SMT ได้ในขณะนี้ หรือยังไม่มีรายงานในระบบ';
            return response()->json(['status' => 'error', 'message' => $errMsg, 'raw' => $rawOutput], 500);
        }
    }

    /**
     * Export Detail Excel
     */
    protected function exportDetailExcel($batch, $details)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('รายบุคคล_' . $batch->batch_no);

        // Headers
        $sheet->setCellValue('A1', 'รายงานสรุปการขอเบิกชดเชยค่าการรักษาผู้ป่วยรายบุคคล');
        $sheet->setCellValue('A2', 'Batch No: ' . $batch->batch_no . ' | งวด: ' . $batch->round_no . ' | ผังบัญชี: ' . $batch->account_code);
        $sheet->setCellValue('A3', 'วันที่โอน: ' . DateThai($batch->transfer_date) . ' | กองทุน: ' . $batch->fund_main . ' - ' . $batch->fund_sub);

        $headers = ['ลำดับ', 'วันที่โอน', 'HN', 'AN', 'ประเภท', 'เลขบัตรประชาชน', 'ชื่อ-สกุล', 'วันที่รับบริการ', 'จ่ายชดเชยสุทธิ', 'REP_NO', 'กองทุนหลัก', 'กองทุนย่อย', 'รายละเอียด'];
        $sheet->fromArray($headers, NULL, 'A5');

        $rowIdx = 6;
        $idx = 1;
        foreach ($details as $d) {
            $sheet->setCellValue('A' . $rowIdx, $idx++);
            $sheet->setCellValue('B' . $rowIdx, DateThai($d->transfer_date));
            $sheet->setCellValue('C' . $rowIdx, $d->hn);
            $sheet->setCellValue('D' . $rowIdx, $d->an);
            $sheet->setCellValue('E' . $rowIdx, $d->pt_type);
            $sheet->setCellValueExplicit('F' . $rowIdx, $d->cid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowIdx, $d->pt_name);
            $sheet->setCellValue('H' . $rowIdx, DateThai($d->vstdate));
            $sheet->setCellValue('I' . $rowIdx, $d->receive_total);
            $sheet->setCellValue('J' . $rowIdx, $d->repno);
            $sheet->setCellValue('K' . $rowIdx, $d->main_fund);
            $sheet->setCellValue('L' . $rowIdx, $d->sub_fund);
            $sheet->setCellValue('M' . $rowIdx, $d->sub_fund_desc);
            $rowIdx++;
        }

        $fileName = 'SmartMoney_' . $batch->batch_no . '_' . ($batch->round_no ?: 'detail') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * Get 12-Month Trend Chart Data (Total & Breakdown by Fund)
     */
    public function getTrendData(Request $request)
    {
        try {
            $budgetYear = intval($request->budget_year ?: (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)));
            
            // Calculate 12 Months dates for Thai Budget Year (Oct Y-1 to Sep Y)
            $adYearEnd = $budgetYear - 543;
            $adYearStart = $adYearEnd - 1;

            $startDate = "{$adYearStart}-10-01";
            $endDate = "{$adYearEnd}-09-30";

            // Thai Month Labels
            $monthsMap = [
                1 => ['ym' => "{$adYearStart}-10", 'short' => 'ต.ค.', 'full' => 'ตุลาคม', 'year' => substr($budgetYear - 1, -2)],
                2 => ['ym' => "{$adYearStart}-11", 'short' => 'พ.ย.', 'full' => 'พฤศจิกายน', 'year' => substr($budgetYear - 1, -2)],
                3 => ['ym' => "{$adYearStart}-12", 'short' => 'ธ.ค.', 'full' => 'ธันวาคม', 'year' => substr($budgetYear - 1, -2)],
                4 => ['ym' => "{$adYearEnd}-01", 'short' => 'ม.ค.', 'full' => 'มกราคม', 'year' => substr($budgetYear, -2)],
                5 => ['ym' => "{$adYearEnd}-02", 'short' => 'ก.พ.', 'full' => 'กุมภาพันธ์', 'year' => substr($budgetYear, -2)],
                6 => ['ym' => "{$adYearEnd}-03", 'short' => 'มี.ค.', 'full' => 'มีนาคม', 'year' => substr($budgetYear, -2)],
                7 => ['ym' => "{$adYearEnd}-04", 'short' => 'เม.ย.', 'full' => 'เมษายน', 'year' => substr($budgetYear, -2)],
                8 => ['ym' => "{$adYearEnd}-05", 'short' => 'พ.ค.', 'full' => 'พฤษภาคม', 'year' => substr($budgetYear, -2)],
                9 => ['ym' => "{$adYearEnd}-06", 'short' => 'มิ.ย.', 'full' => 'มิถุนายน', 'year' => substr($budgetYear, -2)],
                10 => ['ym' => "{$adYearEnd}-07", 'short' => 'ก.ค.', 'full' => 'กรกฎาคม', 'year' => substr($budgetYear, -2)],
                11 => ['ym' => "{$adYearEnd}-08", 'short' => 'ส.ค.', 'full' => 'สิงหาคม', 'year' => substr($budgetYear, -2)],
                12 => ['ym' => "{$adYearEnd}-09", 'short' => 'ก.ย.', 'full' => 'กันยายน', 'year' => substr($budgetYear, -2)],
            ];

            $labels = array_map(function($m) {
                return $m['short'] . ' ' . $m['year'];
            }, array_values($monthsMap));

            // Query all batches for this budget year
            $batches = SmartMoneyBatch::where(function($q) use ($startDate, $endDate, $budgetYear) {
                $q->whereBetween('transfer_date', [$startDate, $endDate])
                  ->orWhere('budget_year', $budgetYear);
            })->get();

            // 1. Overall Monthly Aggregations
            $grandMonthlyAmounts = array_fill(0, 12, 0.0);
            $grandMonthlyBatches = array_fill(0, 12, 0);
            $grandTotalSum = 0.0;
            $grandTotalBatches = 0;

            // 2. Fund Breakdown Aggregations
            $fundsData = [];

            foreach ($batches as $b) {
                $tDate = $b->transfer_date;
                $ym = !empty($tDate) ? substr($tDate, 0, 7) : '';
                $monthIndex = -1;

                foreach ($monthsMap as $idx => $mInfo) {
                    if ($mInfo['ym'] === $ym) {
                        $monthIndex = $idx - 1;
                        break;
                    }
                }

                $amount = floatval($b->net_amount ?? $b->amount ?? 0);
                $grandTotalSum += $amount;
                $grandTotalBatches++;

                if ($monthIndex >= 0 && $monthIndex < 12) {
                    $grandMonthlyAmounts[$monthIndex] += $amount;
                    $grandMonthlyBatches[$monthIndex]++;
                }

                // Group by fund_sub and fund_main
                $mainName = trim($b->fund_main ?: 'ไม่ระบุกองทุน');
                $subName = trim($b->fund_sub ?: $mainName);

                $fundKey = md5($mainName . '___' . $subName);

                if (!isset($fundsData[$fundKey])) {
                    $fundsData[$fundKey] = [
                        'key' => $fundKey,
                        'fund_main' => $mainName,
                        'fund_sub' => $subName,
                        'total_amount' => 0.0,
                        'batch_count' => 0,
                        'monthly_amounts' => array_fill(0, 12, 0.0),
                        'monthly_batches' => array_fill(0, 12, 0),
                    ];
                }

                $fundsData[$fundKey]['total_amount'] += $amount;
                $fundsData[$fundKey]['batch_count']++;
                if ($monthIndex >= 0 && $monthIndex < 12) {
                    $fundsData[$fundKey]['monthly_amounts'][$monthIndex] += $amount;
                    $fundsData[$fundKey]['monthly_batches'][$monthIndex]++;
                }
            }

            // Also calculate Main Fund summaries (for grouped view)
            $mainFundsData = [];
            foreach ($fundsData as $f) {
                $mName = $f['fund_main'];
                $mKey = md5($mName);
                if (!isset($mainFundsData[$mKey])) {
                    $mainFundsData[$mKey] = [
                        'key' => $mKey,
                        'fund_name' => $mName,
                        'total_amount' => 0.0,
                        'batch_count' => 0,
                        'monthly_amounts' => array_fill(0, 12, 0.0),
                        'monthly_batches' => array_fill(0, 12, 0),
                        'sub_count' => 0,
                    ];
                }
                $mainFundsData[$mKey]['total_amount'] += $f['total_amount'];
                $mainFundsData[$mKey]['batch_count'] += $f['batch_count'];
                $mainFundsData[$mKey]['sub_count']++;
                for ($i = 0; $i < 12; $i++) {
                    $mainFundsData[$mKey]['monthly_amounts'][$i] += $f['monthly_amounts'][$i];
                    $mainFundsData[$mKey]['monthly_batches'][$i] += $f['monthly_batches'][$i];
                }
            }

            // Sort funds by total_amount descending
            usort($fundsData, function($a, $b) {
                return $b['total_amount'] <=> $a['total_amount'];
            });
            usort($mainFundsData, function($a, $b) {
                return $b['total_amount'] <=> $a['total_amount'];
            });

            // Find peak month
            $peakMonthVal = max($grandMonthlyAmounts);
            $peakMonthIdx = array_search($peakMonthVal, $grandMonthlyAmounts);
            $peakMonthLabel = $peakMonthIdx !== false ? $labels[$peakMonthIdx] : '-';

            $monthlyAvg = $grandTotalSum / 12;

            return response()->json([
                'status' => 'success',
                'budget_year' => $budgetYear,
                'labels' => $labels,
                'months_map' => array_values($monthsMap),
                'grand_total' => [
                    'key' => 'grand_total',
                    'fund_main' => 'ยอดเงินโอนรวมทุกกองทุน',
                    'fund_sub' => 'รวมเงินโอน สปสช. และกองทุนทั้งหมด',
                    'total_amount' => $grandTotalSum,
                    'batch_count' => $grandTotalBatches,
                    'monthly_avg' => $monthlyAvg,
                    'peak_month' => $peakMonthLabel,
                    'peak_amount' => $peakMonthVal,
                    'monthly_amounts' => $grandMonthlyAmounts,
                    'monthly_batches' => $grandMonthlyBatches,
                ],
                'main_funds' => array_values($mainFundsData),
                'sub_funds' => array_values($fundsData),
            ]);

        } catch (\Exception $e) {
            Log::error('SmartMoney getTrendData error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลกราฟ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download or View Official PAYM (ใบแจ้งโอนเงิน / ใบสำคัญการจ่ายเงิน) from NHSO SMT
     */
    public function downloadPaym(Request $request, $batchNo)
    {
        $batch = SmartMoneyBatch::where('batch_no', $batchNo)
            ->whereNotNull('file_name')
            ->where('file_name', '!=', '')
            ->first();

        if (!$batch) {
            abort(404, "ไม่พบข้อมูลใบแจ้งโอนของ Batch {$batchNo}");
        }

        $fileName = trim($batch->file_name);

        // Directory for cached PAYM files
        $storageDir = storage_path('app/smt_paym');
        if (!file_exists($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }
        $cachedFile = $storageDir . DIRECTORY_SEPARATOR . "PAYM_{$batchNo}.pdf";

        // 1. If cached already and valid size (> 1KB)
        if (file_exists($cachedFile) && filesize($cachedFile) > 1000) {
            return response()->file($cachedFile, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="NHSO_PAYM_' . $batchNo . '.pdf"'
            ]);
        }

        // 2. If uploaded local file
        $localPath = storage_path('app/public/smt_uploads/' . $fileName);
        if (file_exists($localPath) && filesize($localPath) > 1000) {
            return response()->file($localPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"'
            ]);
        }

        // 3. Fetch from NHSO SMT API via File Token
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept' => '*/*',
            'Origin' => 'https://smt.nhso.go.th',
            'Referer' => 'https://smt.nhso.go.th/smtf/',
        ];
        $bearerToken = $this->getActiveSmartMoneyToken();
        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $url = "https://smt.nhso.go.th/smtf/api/budgetreport/budgetSummaryByVendorReportDetail/download?fileName=" . urlencode($fileName);

        try {
            $res = Http::withHeaders($headers)->withoutVerifying()->timeout(30)->get($url);

            if ($res->status() === 200 && strlen($res->body()) > 1000) {
                @file_put_contents($cachedFile, $res->body());
                return response()->file($cachedFile, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="NHSO_PAYM_' . $batchNo . '.pdf"'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("downloadPaym error for batch {$batchNo}: " . $e->getMessage());
        }

        return response()->make(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Download Error</title>' .
            '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script></head><body>' .
            '<script>Swal.fire({icon: "error", title: "ไม่สามารถดาวน์โหลดได้", text: "ไม่สามารถดาวน์โหลดไฟล์ใบแจ้งโอนจาก สปสช. ได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง"}).then(() => window.close());</script></body></html>',
            500,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }
}
