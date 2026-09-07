<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\HosfinGlAccount;
use App\Models\HosfinGlJournal;
use App\Models\HosfinGlJournalItem;
use App\Models\HosfinGlSubledger;
use App\Models\HosfinGlApBill;
use App\Models\HosfinGlArDebtor;
use App\Models\HosfinGlCostSummary;
use App\Models\HosfinGlDailySummary;
use App\Models\HosfinGlSyncLog;
use App\Models\MainSetting;

class HosfinGlSyncController extends Controller
{
    /**
     * Get Current Hospital Code
     */
    protected function getCurrentHospcode()
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        if (!$hospcode) {
            $hospcode = DB::table('lookup_hospcode')->value('hospcode');
        }
        return $hospcode ? trim($hospcode) : '';
    }

    /**
     * Expected GL Sync Tokens for this Hospital
     */
    protected function getExpectedTokens()
    {
        $tokens = [];
        $hospcode = $this->getCurrentHospcode();
        if ($hospcode) {
            $tokens[] = 'rims-gl-' . $hospcode . '-token-2569';
        }

        // Custom token from env / config if set explicitly
        $customToken = config('services.gl_sync.token', env('GL_SYNC_TOKEN'));
        if ($customToken && !in_array($customToken, ['rims-gl-token-2569-secret', ''])) {
            $tokens[] = $customToken;
        }

        return $tokens;
    }

    /**
     * Validate GL Sync Token with Hospital Isolation
     */
    protected function validateRequestToken(Request $request)
    {
        $token = $request->bearerToken() ?: $request->input('token') ?: $request->header('X-GL-SYNC-TOKEN');
        if (!$token) {
            return [
                'valid' => false,
                'status' => 401,
                'message' => 'Unauthorized: ไม่พบ API Token สำหรับซิงค์ข้อมูล (Missing GL Sync Token)'
            ];
        }

        $currentHospcode = $this->getCurrentHospcode();

        // 1. Detect if token belongs to another hospital (rims-gl-{hospcode}-token-2569)
        if (preg_match('/rims-gl-(\d{5})-token-2569/', $token, $matches)) {
            $tokenHospcode = $matches[1];
            if ($currentHospcode && $tokenHospcode !== $currentHospcode) {
                Log::warning("HosfinGlSync: Cross-hospital sync attempt blocked! Token Hospcode: {$tokenHospcode}, Server Hospcode: {$currentHospcode}, IP: " . $request->ip());
                return [
                    'valid' => false,
                    'status' => 403,
                    'message' => "Cross-Hospital Sync Blocked: ตรวจพบการส่งข้อมูลข้าม รพ.! (Token นี้เป็นของ รพ. รหัส {$tokenHospcode} แต่ Server นี้คือ รพ. รหัส {$currentHospcode})"
                ];
            }
        }

        // 2. Detect if payload contains a different hospital code
        $payloadHospcode = $request->input('hospcode');
        if ($payloadHospcode && $currentHospcode && $payloadHospcode !== $currentHospcode) {
            Log::warning("HosfinGlSync: Cross-hospital sync payload blocked! Payload Hospcode: {$payloadHospcode}, Server Hospcode: {$currentHospcode}, IP: " . $request->ip());
            return [
                'valid' => false,
                'status' => 403,
                'message' => "Cross-Hospital Sync Blocked: ข้อมูลบัญชีที่ส่งมาเป็นของ รพ. รหัส {$payloadHospcode} แต่ Server ปลายทางนี้คือ รพ. รหัส {$currentHospcode}"
            ];
        }

        // 3. Match against expected tokens for this hospital
        $expectedTokens = $this->getExpectedTokens();
        $isMatch = false;
        foreach ($expectedTokens as $expected) {
            if (hash_equals($expected, $token)) {
                $isMatch = true;
                break;
            }
        }

        if (!$isMatch) {
            return [
                'valid' => false,
                'status' => 401,
                'message' => 'Unauthorized: API Token ไม่ถูกต้องสำหรับโรงพยาบาลนี้ (กรุณาคัดลอก Token ประจำ รพ. จากหน้าศูนย์ดาวน์โหลด RiMS)'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Ingest GL Sync Data from Go Microservice
     */
    public function sync(Request $request)
    {
        $startTime = microtime(true);

        $authCheck = $this->validateRequestToken($request);
        if (!$authCheck['valid']) {
            return response()->json([
                'success' => false,
                'message' => $authCheck['message']
            ], $authCheck['status']);
        }

        if (!\App\Services\LicenseVerificationService::isModuleLicensed('hosfin')) {
            return response()->json([
                'success' => false,
                'message' => 'License Error: โรงพยาบาลของท่านยังไม่ได้รับอนุญาตให้ใช้งานระบบ HosFin (กรุณาเปิดสิทธิ์ License)'
            ], 403);
        }

        // If GET request (e.g. Test Connection from Rims-GL-Sync client)
        if ($request->isMethod('get')) {
            $hospcode = $this->getCurrentHospcode();
            $hospName = DB::table('main_setting')->where('name', 'hospital_name')->value('value')
                ?: DB::table('lookup_hospcode')->where('hospcode', $hospcode)->value('hospcode_name')
                ?: 'โรงพยาบาล';
            return response()->json([
                'success' => true,
                'message' => "เชื่อมต่อ API สำเร็จและ Token ถูกต้อง (สถานพยาบาล: {$hospName} [{$hospcode}])",
                'hospcode' => $hospcode,
                'hospital_name' => $hospName
            ]);
        }

        $syncType = $request->input('sync_type', 'full');
        $agentVersion = $request->input('agent_version', '1.0.0');
        $agentIp = $request->ip();

        $accounts = $request->input('accounts', []);
        $subledgers = $request->input('subledgers', []);
        $journals = $request->input('journals', []);

        $recordsCount = count($accounts) + count($subledgers) + count($journals);

        DB::beginTransaction();
        try {
            // 1. Process Chart of Accounts
            if (!empty($accounts)) {
                $now = now();
                foreach (array_chunk($accounts, 200) as $chunk) {
                    $upsertAccounts = [];
                    foreach ($chunk as $acc) {
                        $code = trim($acc['account_code'] ?? '');
                        if (!$code) continue;

                        $name = trim($acc['account_name'] ?? '');
                        
                        // Auto deduce cost_type if not provided
                        $costType = $acc['cost_type'] ?? 'OTHER';
                        if ($costType === 'OTHER') {
                            if (str_starts_with($code, '5101') || str_starts_with($code, '5102') || str_starts_with($code, '5103')) {
                                $costType = 'LC';
                            } elseif (str_starts_with($code, '5104')) {
                                $costType = 'MC';
                            } elseif (str_starts_with($code, '5105')) {
                                $costType = 'CC';
                            }
                        }

                        // Auto deduce service_type
                        $serviceType = $acc['service_type'] ?? 'direct';
                        if (str_contains($name, 'สนับสนุน')) {
                            $serviceType = 'indirect';
                        }

                        $upsertAccounts[] = [
                            'account_code'     => $code,
                            'account_name'     => $name,
                            'account_type'     => $acc['account_type'] ?? substr($code, 0, 1),
                            'account_category' => $acc['account_category'] ?? null,
                            'normal_balance'   => $acc['normal_balance'] ?? (in_array(substr($code, 0, 1), ['1', '5']) ? 'DR' : 'CR'),
                            'cost_type'        => $costType,
                            'service_type'     => $serviceType,
                            'is_active'        => 1,
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                    }

                    if (!empty($upsertAccounts)) {
                        HosfinGlAccount::upsert(
                            $upsertAccounts,
                            ['account_code'],
                            ['account_name', 'account_type', 'account_category', 'normal_balance', 'cost_type', 'service_type', 'is_active', 'updated_at']
                        );
                    }
                }
            }

            // 2. Process Subledgers (Vendors, Bill Mapping)
            if (!empty($subledgers)) {
                $now = now();
                foreach (array_chunk($subledgers, 300) as $chunk) {
                    $upsertSubs = [];
                    foreach ($chunk as $sub) {
                        $code = trim($sub['subledger_code'] ?? '');
                        if (!$code) continue;

                        $rawNote = trim($sub['raw_note'] ?? ($sub['vendor_name'] ?? ''));
                        $parts = explode('/', $rawNote);
                        $vendor = trim($parts[0] ?? $rawNote);
                        $category = isset($parts[1]) ? trim($parts[1]) : ($sub['category'] ?? 'ทั่วไป');

                        $upsertSubs[] = [
                            'subledger_code' => $code,
                            'vendor_name'    => $vendor ?: $rawNote,
                            'category'       => $category,
                            'raw_note'       => $rawNote,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ];
                    }

                    if (!empty($upsertSubs)) {
                        HosfinGlSubledger::upsert(
                            $upsertSubs,
                            ['subledger_code'],
                            ['vendor_name', 'category', 'raw_note', 'updated_at']
                        );
                    }
                }
            }

            // 3. Process Journals & Journal Items
            if (!empty($journals)) {
                $now = now();
                foreach ($journals as $j) {
                    $voucherNo = trim($j['voucher_no'] ?? '');
                    if (!$voucherNo) continue;

                    $journal = HosfinGlJournal::updateOrCreate(
                        ['voucher_no' => $voucherNo],
                        [
                            'voucher_date'  => $j['voucher_date'] ?? date('Y-m-d'),
                            'journal_type'  => $j['journal_type'] ?? 'JV',
                            'description'   => $j['description'] ?? null,
                            'total_debit'   => $j['total_debit'] ?? 0.00,
                            'total_credit'  => $j['total_credit'] ?? 0.00,
                            'posted_status' => $j['posted_status'] ?? 'posted',
                            'fiscal_year'   => $j['fiscal_year'] ?? 2569,
                            'fiscal_month'  => $j['fiscal_month'] ?? 1,
                            'apar'          => $j['apar'] ?? null,
                            'external_ref'  => $j['external_ref'] ?? null,
                        ]
                    );

                    if (!empty($j['items']) && is_array($j['items'])) {
                        // Delete previous items for this journal and re-insert
                        HosfinGlJournalItem::where('journal_id', $journal->id)->delete();

                        $itemsData = [];
                        foreach ($j['items'] as $item) {
                            $itemsData[] = [
                                'journal_id'   => $journal->id,
                                'voucher_no'   => $voucherNo,
                                'item_no'      => $item['item_no'] ?? 1,
                                'account_code' => $item['account_code'] ?? '',
                                'account_name' => $item['account_name'] ?? null,
                                'description'  => $item['description'] ?? null,
                                'debit'        => $item['debit'] ?? 0.00,
                                'credit'       => $item['credit'] ?? 0.00,
                                'department'   => $item['department'] ?? null,
                                'created_at'   => $now,
                                'updated_at'   => $now,
                            ];
                        }
                        if (!empty($itemsData)) {
                            HosfinGlJournalItem::insert($itemsData);
                        }
                    }
                }
            }

            // 4. Automatic Recalculations & Pre-aggregations (run on finalize, full, or when not chunk)
            if ($syncType === 'finalize' || $syncType === 'full') {
                $this->recalculateSummaries();
            }

            DB::commit();

            $duration = round(microtime(true) - $startTime, 2);

            // Log success
            HosfinGlSyncLog::create([
                'sync_type'        => $syncType,
                'records_count'    => $recordsCount,
                'status'           => 'success',
                'message'          => "Synced successfully: $recordsCount records processed.",
                'agent_ip'         => $agentIp,
                'agent_version'    => $agentVersion,
                'duration_seconds' => $duration,
            ]);

            return response()->json([
                'success'         => true,
                'message'         => 'GL Data Synced Successfully',
                'records_count'   => $recordsCount,
                'duration_seconds'=> $duration,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            $duration = round(microtime(true) - $startTime, 2);

            Log::error("GL Sync Failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            HosfinGlSyncLog::create([
                'sync_type'        => $syncType,
                'records_count'    => $recordsCount,
                'status'           => 'failed',
                'message'          => "Error: " . $e->getMessage(),
                'agent_ip'         => $agentIp,
                'agent_version'    => $agentVersion,
                'duration_seconds' => $duration,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync GL data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate AP Bills, AR Debtors, Cost Summaries, Daily Summaries
     */
    protected function recalculateSummaries()
    {
        $now = now();

        // 1. Recalculate AP Bills (Creditors Remaining Debt)
        // Group by apar and account_code where account_code LIKE '2101%'
        $apRows = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->leftJoin('hosfin_gl_subledgers as s', 'j.apar', '=', 's.subledger_code')
            ->whereNotNull('j.apar')
            ->where('j.apar', '<>', '')
            ->where('i.account_code', 'like', '2101%')
            ->select(
                'j.apar as bill_no',
                'i.account_code',
                DB::raw('MAX(i.account_name) as account_name'),
                DB::raw('MAX(s.vendor_name) as vendor_name'),
                DB::raw('MAX(s.category) as category'),
                DB::raw('MAX(j.voucher_date) as bill_date'),
                DB::raw('MAX(j.fiscal_year) as fiscal_year'),
                DB::raw('SUM(i.credit) as total_credit'),
                DB::raw('SUM(i.debit) as total_debit'),
                DB::raw('SUM(i.credit - i.debit) as remaining_debt')
        )
        ->groupBy('j.apar', 'i.account_code')
        ->get();

        // Calculate net remaining debt per bill_no across all 2101% accounts
        $billNetTotals = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->whereNotNull('j.apar')
            ->where('j.apar', '<>', '')
            ->where('i.account_code', 'like', '2101%')
            ->select('j.apar as bill_no', DB::raw('SUM(i.credit - i.debit) as bill_net_debt'))
            ->groupBy('j.apar')
            ->pluck('bill_net_debt', 'bill_no');

        if ($apRows->isNotEmpty()) {
            $apUpsert = [];
            foreach ($apRows as $row) {
                $rem = (float)$row->remaining_debt;
                $billNet = (float)($billNetTotals[$row->bill_no] ?? $rem);
                // If the entire bill is paid (or overpaid) across all 2101 accounts, then mark as paid!
                $isPaid = ($billNet <= 0.001) ? 1 : 0;
                $rem = $isPaid ? 0.00 : max(0.0, min($rem, $billNet));
                $vendor = $row->vendor_name ?: $row->bill_no;
                $apUpsert[] = [
                    'bill_no'        => $row->bill_no,
                    'account_code'   => $row->account_code,
                    'account_name'   => $row->account_name,
                    'vendor_name'    => $vendor,
                    'category'       => $row->category ?: 'ทั่วไป',
                    'bill_date'      => $row->bill_date,
                    'fiscal_year'    => $row->fiscal_year ?: 2569,
                    'total_credit'   => $row->total_credit ?: 0.00,
                    'total_debit'    => $row->total_debit ?: 0.00,
                    'remaining_debt' => $rem,
                    'is_paid'        => $isPaid,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            foreach (array_chunk($apUpsert, 200) as $chunk) {
                HosfinGlApBill::upsert(
                    $chunk,
                    ['bill_no', 'account_code'],
                    ['account_name', 'vendor_name', 'category', 'bill_date', 'fiscal_year', 'total_credit', 'total_debit', 'remaining_debt', 'is_paid', 'updated_at']
                );
            }
        }

        // 2. Recalculate AR Debtors (Debtors Outstanding Balance)
        $arRows = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->where('i.account_code', 'like', '1102%')
            ->select(
                'i.account_code',
                'j.fiscal_year',
                'j.fiscal_month',
                DB::raw('MAX(i.account_name) as account_name'),
                DB::raw('SUM(i.debit) as total_billed'),
                DB::raw('SUM(i.credit) as total_collected'),
                DB::raw('SUM(i.debit - i.credit) as outstanding_balance')
            )
            ->groupBy('i.account_code', 'j.fiscal_year', 'j.fiscal_month')
            ->get();

        if ($arRows->isNotEmpty()) {
            $arUpsert = [];
            foreach ($arRows as $row) {
                $code = $row->account_code;
                $name = $row->account_name ?: '';
                
                // Classify debtor type
                $debtorType = 'อื่นๆ';
                if (str_contains($name, 'UC') || str_contains($name, 'สปสช') || str_contains($name, 'บัตรทอง')) {
                    $debtorType = 'สปสช. (UC)';
                } elseif (str_contains($name, 'ประกันสังคม')) {
                    $debtorType = 'ประกันสังคม (SSS)';
                } elseif (str_contains($name, 'ข้าราชการ') || str_contains($name, 'เบิกจ่ายตรง') || str_contains($name, 'อปท')) {
                    $debtorType = 'ข้าราชการ / อปท.';
                } elseif (str_contains($name, 'พรบ') || str_contains($name, 'พ.ร.บ')) {
                    $debtorType = 'พ.ร.บ.รถ';
                } elseif (str_contains($name, 'ชำระเงิน')) {
                    $debtorType = 'ผู้ป่วยชำระเงิน';
                }

                $fm = ($row->fiscal_month !== null && $row->fiscal_month !== '') ? intval($row->fiscal_month) : 0;

                $arUpsert[] = [
                    'account_code'        => $code,
                    'fiscal_year'         => $row->fiscal_year ?: 2569,
                    'fiscal_month'        => $fm,
                    'account_name'        => $name,
                    'debtor_type'         => $debtorType,
                    'total_billed'        => $row->total_billed ?: 0.00,
                    'total_collected'     => $row->total_collected ?: 0.00,
                    'outstanding_balance' => $row->outstanding_balance ?: 0.00,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];
            }

            foreach (array_chunk($arUpsert, 200) as $chunk) {
                HosfinGlArDebtor::upsert(
                    $chunk,
                    ['fiscal_year', 'fiscal_month', 'account_code'],
                    ['account_name', 'debtor_type', 'total_billed', 'total_collected', 'outstanding_balance', 'updated_at']
                );
            }
        }

        // 3. Recalculate Cost Summaries (LC / MC / CC)
        $costRows = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->where('i.account_code', 'like', '5%')
            ->select(
                'j.fiscal_year',
                'j.fiscal_month',
                DB::raw("SUM(CASE WHEN i.account_code LIKE '5101%' OR i.account_code LIKE '5102%' OR i.account_code LIKE '5103%' THEN i.debit - i.credit ELSE 0 END) as lc_amount"),
                DB::raw("SUM(CASE WHEN i.account_code LIKE '5104%' THEN i.debit - i.credit ELSE 0 END) as mc_amount"),
                DB::raw("SUM(CASE WHEN i.account_code LIKE '5105%' THEN i.debit - i.credit ELSE 0 END) as cc_amount"),
                DB::raw("SUM(CASE WHEN i.account_code NOT LIKE '5101%' AND i.account_code NOT LIKE '5102%' AND i.account_code NOT LIKE '5103%' AND i.account_code NOT LIKE '5104%' AND i.account_code NOT LIKE '5105%' THEN i.debit - i.credit ELSE 0 END) as other_cost"),
                DB::raw("SUM(i.debit - i.credit) as total_cost")
            )
            ->groupBy('j.fiscal_year', 'j.fiscal_month')
            ->get();

        if ($costRows->isNotEmpty()) {
            $costUpsert = [];
            foreach ($costRows as $c) {
                $costUpsert[] = [
                    'fiscal_year'  => $c->fiscal_year ?: 2569,
                    'fiscal_month' => $c->fiscal_month ?: 1,
                    'lc_amount'    => $c->lc_amount ?: 0.00,
                    'mc_amount'    => $c->mc_amount ?: 0.00,
                    'cc_amount'    => $c->cc_amount ?: 0.00,
                    'other_cost'   => $c->other_cost ?: 0.00,
                    'total_cost'   => $c->total_cost ?: 0.00,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            HosfinGlCostSummary::upsert(
                $costUpsert,
                ['fiscal_year', 'fiscal_month'],
                ['lc_amount', 'mc_amount', 'cc_amount', 'other_cost', 'total_cost', 'updated_at']
            );
        }

        // 4. Recalculate Daily Summaries (Cash Flow)
        $dailyRows = DB::table('hosfin_gl_journals as j')
            ->join('hosfin_gl_journal_items as i', 'j.id', '=', 'i.journal_id')
            ->select(
                'j.voucher_date as summary_date',
                DB::raw('MAX(j.fiscal_year) as fiscal_year'),
                DB::raw("SUM(CASE WHEN i.account_code LIKE '4%' THEN i.credit - i.debit ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN i.account_code LIKE '5%' THEN i.debit - i.credit ELSE 0 END) as total_expense"),
                DB::raw("SUM(CASE WHEN i.account_code LIKE '1101%' THEN i.debit - i.credit ELSE 0 END) as net_cash_flow"),
                DB::raw('COUNT(DISTINCT j.id) as voucher_count')
            )
            ->groupBy('j.voucher_date')
            ->get();

        if ($dailyRows->isNotEmpty()) {
            $dailyUpsert = [];
            foreach ($dailyRows as $d) {
                $dailyUpsert[] = [
                    'summary_date'  => $d->summary_date,
                    'fiscal_year'   => $d->fiscal_year ?: 2569,
                    'total_income'  => $d->total_income ?: 0.00,
                    'total_expense' => $d->total_expense ?: 0.00,
                    'net_cash_flow' => $d->net_cash_flow ?: 0.00,
                    'cash_balance'  => 0.00, // Calculated dynamically
                    'voucher_count' => $d->voucher_count ?: 0,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }

            foreach (array_chunk($dailyUpsert, 200) as $chunk) {
                HosfinGlDailySummary::upsert(
                    $chunk,
                    ['summary_date'],
                    ['fiscal_year', 'total_income', 'total_expense', 'net_cash_flow', 'voucher_count', 'updated_at']
                );
            }
        }

        // 5. Automatic Live Monthly Balances Calculation from GL
        try {
            \App\Http\Controllers\HosFinController::syncGlMonthlyBalances();
        } catch (\Throwable $e) {
            Log::warning("Failed to auto-sync monthly balances from GL: " . $e->getMessage());
        }
    }

    /**
     * Get GL Sync Status and Summary Metrics
     */
    public function status(Request $request)
    {
        if (!\App\Services\LicenseVerificationService::isModuleLicensed('hosfin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'License Error: โรงพยาบาลของท่านยังไม่ได้รับอนุญาตให้ใช้งานระบบ HosFin'
            ], 403);
        }

        $lastLog = HosfinGlSyncLog::latest('id')->first();
        
        $totalAccounts = HosfinGlAccount::count();
        $totalJournals = HosfinGlJournal::count();
        $totalSubledgers = HosfinGlSubledger::count();

        $unpaidBillsCount = HosfinGlApBill::where('is_paid', 0)->count();
        $unpaidDebtSum = HosfinGlApBill::where('is_paid', 0)->sum('remaining_debt');

        $debtorsOutstandingSum = HosfinGlArDebtor::sum('outstanding_balance');

        $latestCost = HosfinGlCostSummary::orderBy('fiscal_year', 'desc')->orderBy('fiscal_month', 'desc')->first();

        return response()->json([
            'status' => 'ok',
            'last_sync' => $lastLog ? [
                'status'           => $lastLog->status,
                'sync_type'        => $lastLog->sync_type,
                'records_count'    => $lastLog->records_count,
                'duration_seconds' => $lastLog->duration_seconds,
                'synced_at'        => $lastLog->created_at ? $lastLog->created_at->toDateTimeString() : null,
                'message'          => $lastLog->message,
            ] : null,
            'summary' => [
                'total_accounts'          => $totalAccounts,
                'total_journals'          => $totalJournals,
                'total_subledgers'        => $totalSubledgers,
                'unpaid_bills_count'      => $unpaidBillsCount,
                'unpaid_debt_amount'      => round($unpaidDebtSum, 2),
                'outstanding_debtors_sum' => round($debtorsOutstandingSum, 2),
                'latest_cost'             => $latestCost,
            ]
        ]);
    }
}
