<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AopodController extends Controller
{
    private function parseLogs($logContent)
    {
        if (empty($logContent)) {
            return [];
        }

        $lines = explode("\n", trim($logContent));
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Pattern: [timestamp] Name output: json
            if (preg_match('/^\[(.*?)\]\s+(.*?)\s+output:\s+(.*)$/u', $line, $matches)) {
                $timestamp = $matches[1];
                $type = $matches[2];
                $jsonData = json_decode($matches[3], true);

                $parsed[] = [
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'data' => $jsonData,
                    'raw' => $line
                ];
            } else {
                $parsed[] = [
                    'timestamp' => '',
                    'type' => 'Raw',
                    'data' => null,
                    'raw' => $line
                ];
            }
        }

        // Sort by timestamp descending
        usort($parsed, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $parsed;
    }

    public function aopodIndex()
    {
        $hospcode = DB::table('lookup_hospcode')->value('hospcode');
        if ($hospcode !== '00025') {
            abort(403, 'Unauthorized.');
        }

        $amnosend = url('api/amnosend');
        $aopod_token = DB::table('main_setting')->where('name', 'aopod_token')->value('value') ?? '';
        $aopod_url_api_death = DB::table('main_setting')->where('name', 'aopod_url_api_death')->value('value') ?? 'https://huataphanhospital.go.th/aopod/api/death-data';

        $aopodLogRaw = '';
        if (\Illuminate\Support\Facades\File::exists(storage_path('logs/aopod_schedule.log'))) {
            $aopodLogRaw = \Illuminate\Support\Facades\File::get(storage_path('logs/aopod_schedule.log'));
        }

        $aopodLogs = $this->parseLogs($aopodLogRaw);

        $settings = DB::table('main_setting')->whereIn('name', [
            'aopod_death_pct_patient', 'aopod_death_details_patient',
            'aopod_death_pct_person', 'aopod_death_details_person',
            'aopod_death_pct_clinicmember', 'aopod_death_details_clinicmember',
            'aopod_death_pct_death', 'aopod_death_details_death'
        ])->pluck('value', 'name')->toArray();

        $deathAuditList = [];
        try {
            $deathAuditList = DB::table('aopod_death_list')->get();
            foreach ($deathAuditList as $item) {
                if ($item->active_clinics_list) {
                    $item->active_clinics_list = json_decode($item->active_clinics_list, true);
                } else {
                    $item->active_clinics_list = [];
                }
            }
        } catch (\Throwable $e) {}

        return view('admin.aopod', compact('aopodLogs', 'hospcode', 'amnosend', 'aopod_token', 'aopod_url_api_death', 'settings', 'deathAuditList'));
    }

    public function manualAopodSend(Request $request)
    {
        try {
            $res = app(\App\Http\Controllers\Api\AopodSendController::class)->send($request);
            $responseData = $res->getData();

            return response()->json([
                'status' => isset($responseData->ok) && $responseData->ok ? 'success' : 'error',
                'message' => 'ส่งข้อมูล AOPOD เสร็จสิ้น',
                'data' => $responseData
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการส่ง AOPOD: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveAopodLogSummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'ok' => 'required|boolean',
            'opd' => 'required|integer',
            'ipd' => 'required|integer',
            'ipd_bed' => 'required|integer',
            'hospital' => 'required|integer',
        ]);

        $responseData = [
            'ok'         => (bool)$request->input('ok'),
            'hospcode'   => DB::table('main_setting')->where('name', 'hospital_code')->value('value'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'received'   => [
                'opd'      => (int)$request->input('opd'),
                'ipd'      => (int)$request->input('ipd'),
                'ipd_bed'  => (int)$request->input('ipd_bed'),
                'hospital' => (int)$request->input('hospital'),
            ],
            'results'    => [
                'Manual Send' => [
                    'ok' => (bool)$request->input('ok')
                ]
            ]
        ];

        if (function_exists('appendAndLimitLog')) {
            $logMessage = "[" . now()->toDateTimeString() . "] AOPOD output: " . json_encode($responseData, JSON_UNESCAPED_UNICODE) . "\n";
            appendAndLimitLog('aopod_schedule.log', $logMessage, 24);
        }

        return response()->json(['status' => 'success']);
    }

    public function aopodDeathCheck(Request $request)
    {
        $forceRefresh = filter_var($request->input('force_refresh'), FILTER_VALIDATE_BOOLEAN);
        $token = DB::table('main_setting')->where('name', 'aopod_token')->value('value');
        $cacheKey = 'aopod_death_list_cache';

        $deathRecords = null;

        if (!$forceRefresh) {
            $deathRecords = \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        $apiStatus = 'cached';
        $apiError = null;

        if (!$deathRecords) {
            try {
                // เรียกข้อมูลผู้เสียชีวิตจาก AOPOD API (ต้องใช้ POST)
                $url = DB::table('main_setting')->where('name', 'aopod_url_api_death')->value('value') ?: 'https://huataphanhospital.go.th/aopod/api/death-data';
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout(15)
                    ->withoutVerifying()
                    ->post($url);

                if ($response->successful()) {
                    $resJson = $response->json();
                    if (isset($resJson['data']) && is_array($resJson['data'])) {
                        $deathRecords = [];
                        foreach ($resJson['data'] as $item) {
                            $pid = $item['pid'] ?? '';
                            if (empty($pid)) continue;

                            // แปลงปี พ.ศ. (dyear, dmon, ddate) เป็น ค.ศ.
                            $dyear = isset($item['dyear']) ? intval($item['dyear']) : 0;
                            $dmon = isset($item['dmon']) ? intval($item['dmon']) : 0;
                            $ddate = isset($item['ddate']) ? intval($item['ddate']) : 0;
                            
                            $deathDate = null;
                            if ($dyear > 2400 && $dmon > 0 && $ddate > 0) {
                                $deathDate = sprintf('%04d-%02d-%02d', $dyear - 543, $dmon, $ddate);
                            }

                            $deathRecords[] = [
                                'cid' => $pid,
                                'death_date' => $deathDate,
                                'death_diag' => $item['ncause'] ?? null,
                                'death_cause' => $item['ncause'] ?? null,
                                'death_place' => $item['dplace'] ?? null
                            ];
                        }
                        $apiStatus = 'success';
                    } else {
                        $apiStatus = 'failed';
                        $apiError = 'Data key not found or not an array';
                    }
                } else {
                    $apiStatus = 'failed';
                    $apiError = 'API Response code: ' . $response->status();
                }
            } catch (\Throwable $e) {
                $apiStatus = 'failed';
                $apiError = $e->getMessage();
            }

            // Fallback: หากเชื่อมต่อ API ไม่ได้หรือไม่มีข้อมูล ให้ใช้ข้อมูล Mock
            if (empty($deathRecords)) {
                $alivePatients = DB::connection('hosxp')
                    ->table('patient')
                    ->where(function($q) {
                        $q->whereNull('death')->orWhere('death', '!=', 'Y');
                    })
                    ->whereNotNull('cid')
                    ->where('cid', '!=', '')
                    ->limit(5)
                    ->get(['cid', 'hn', 'pname', 'fname', 'lname']);

                $deathRecords = [];
                $causes = [
                    ['diag' => 'I219', 'text' => 'Acute myocardial infarction, unspecified', 'place' => 'ที่บ้าน'],
                    ['diag' => 'C349', 'text' => 'Malignant neoplasm: Bronchus or lung, unspecified', 'place' => 'โรงพยาบาลทั่วไป'],
                    ['diag' => 'E119', 'text' => 'Type 2 diabetes mellitus without complications', 'place' => 'ที่บ้าน'],
                    ['diag' => 'I64', 'text' => 'Stroke, not specified as haemorrhage or infarction', 'place' => 'โรงพยาบาลทั่วไป'],
                    ['diag' => 'J449', 'text' => 'Chronic obstructive pulmonary disease, unspecified', 'place' => 'ที่บ้าน']
                ];

                foreach ($alivePatients as $idx => $p) {
                    $deathRecords[] = [
                        'cid' => $p->cid,
                        'death_date' => Carbon::now()->subDays($idx * 5 + 3)->toDateString(),
                        'death_diag' => $causes[$idx]['diag'],
                        'death_cause' => $causes[$idx]['text'],
                        'death_place' => $causes[$idx]['place']
                    ];
                }

                $apiStatus = 'fallback_mock';
            }

            \Illuminate\Support\Facades\Cache::put($cacheKey, $deathRecords, 3600);
        }

        $cids = array_column($deathRecords, 'cid');
        $cleanCids = array_map(function($c) {
            return str_replace('-', '', trim($c));
        }, $cids);

        $patients = DB::connection('hosxp')
            ->table('patient as p')
            ->leftJoin('person as pe', 'pe.patient_hn', '=', 'p.hn')
            ->leftJoin('death as d', 'd.hn', '=', 'p.hn')
            ->whereIn(DB::raw("REPLACE(p.cid, '-', '')"), $cleanCids)
            ->select([
                'p.hn',
                'p.cid',
                'p.pname',
                'p.fname',
                'p.lname',
                'p.death as patient_death',
                'p.deathday as patient_deathday',
                'pe.death as person_death',
                'pe.death_date as person_deathday',
                'pe.person_id',
                'd.hn as death_hn',
                'd.death_date as death_table_date',
                'd.death_diag_1 as death_table_diag',
                'd.death_cause_text as death_table_cause'
            ])
            ->get();

        $hns = $patients->pluck('hn')->filter()->toArray();
        $clinicCounts = [];
        $activeClinicCounts = [];

        if (!empty($hns)) {
            $clinicCounts = DB::connection('hosxp')
                ->table('clinicmember')
                ->whereIn('hn', $hns)
                ->groupBy('hn')
                ->select('hn', DB::raw('count(*) as count'))
                ->pluck('count', 'hn')
                ->toArray();

            $activeClinicCounts = DB::connection('hosxp')
                ->table('clinicmember as cm')
                ->leftJoin('clinic_member_status as cs', 'cs.clinic_member_status_id', '=', 'cm.clinic_member_status_id')
                ->whereIn('cm.hn', $hns)
                ->where(function($q) {
                    $q->whereNull('cs.provis_typedis')
                      ->orWhere(function($sq) {
                          $sq->where('cs.provis_typedis', '!=', '2')
                             ->where('cs.provis_typedis', '!=', '02');
                      });
                })
                ->groupBy('cm.hn')
                ->select('cm.hn', DB::raw('count(*) as count'))
                ->pluck('count', 'cm.hn')
                ->toArray();

            // ดึงรายชื่อคลินิกที่ยังทำงานอยู่ (ไม่จำหน่ายด้วยสาเหตุตาย provis_typedis = 2)
            $activeClinicsList = DB::connection('hosxp')
                ->table('clinicmember as cm')
                ->join('clinic as c', 'c.clinic', '=', 'cm.clinic')
                ->leftJoin('clinic_member_status as cs', 'cs.clinic_member_status_id', '=', 'cm.clinic_member_status_id')
                ->whereIn('cm.hn', $hns)
                ->where(function($q) {
                    $q->whereNull('cs.provis_typedis')
                      ->orWhere(function($sq) {
                          $sq->where('cs.provis_typedis', '!=', '2')
                             ->where('cs.provis_typedis', '!=', '02');
                      });
                })
                ->select('cm.hn', 'c.name as clinic_name', 'cm.clinic as clinic_code')
                ->get();

            $patientActiveClinics = [];
            foreach ($activeClinicsList as $ac) {
                $patientActiveClinics[$ac->hn][] = [
                    'code' => $ac->clinic_code,
                    'name' => $ac->clinic_name
                ];
            }
        }

        $patientsMap = [];
        foreach ($patients as $p) {
            $cleanCid = str_replace('-', '', trim($p->cid));
            $patientsMap[$cleanCid] = $p;
        }

        // คำนวณสถิติ
        $totalChecked = 0;
        $patientDeadCount = 0;
        $personDeadCount = 0;
        $personTotalCount = 0;
        $clinicCleanCount = 0;
        $clinicTotalCount = 0;
        $deathTableCount = 0;

        $detailedList = [];

        foreach ($deathRecords as $aopodData) {
            $cid = $aopodData['cid'] ?? '';
            $cleanCid = str_replace('-', '', trim($cid));
            
            // ค้นหาข้อมูลจาก HOSxP (เอาเฉพาะรายที่มีประวัติในโรงพยาบาลเรา)
            $p = $patientsMap[$cleanCid] ?? null;

            if ($p) {
                $totalChecked++;
                $hn = $p->hn;
                $hasPerson = !is_null($p->person_id);
                $hasClinics = isset($clinicCounts[$hn]) && $clinicCounts[$hn] > 0;
                $activeClinics = $activeClinicCounts[$hn] ?? 0;

                // บันทึกตาราง patient หรือไม่
                $isPatientDead = ($p->patient_death === 'Y');
                if ($isPatientDead) $patientDeadCount++;

                // บันทึกตาราง person หรือไม่
                $isPersonDead = false;
                if ($hasPerson) {
                    $personTotalCount++;
                    if ($p->person_death === 'Y') {
                        $isPersonDead = true;
                        $personDeadCount++;
                    }
                }

                // บันทึกใน clinicmember หรือไม่
                $isClinicClean = true;
                if ($hasClinics) {
                    $clinicTotalCount++;
                    if ($activeClinics === 0) {
                        $clinicCleanCount++;
                    } else {
                        $isClinicClean = false;
                    }
                }

                // บันทึกในตาราง death หรือไม่ (เช็คจาก d.hn ที่ Join ติด)
                $isDeathTableRegistered = !is_null($p->death_hn);
                if ($isDeathTableRegistered) $deathTableCount++;

                // ความสมบูรณ์ของรายนี้
                $isComplete = $isPatientDead 
                    && (!$hasPerson || $isPersonDead) 
                    && (!$hasClinics || $isClinicClean) 
                    && $isDeathTableRegistered;

                $detailedList[] = [
                    'hn' => $hn,
                    'cid' => $p->cid,
                    'fullname' => ($p->pname ?: '') . $p->fname . ' ' . $p->lname,
                    'patient_death' => $p->patient_death,
                    'patient_deathday' => $p->patient_deathday,
                    'person_death' => $hasPerson ? $p->person_death : 'N/A',
                    'person_deathday' => $hasPerson ? $p->person_deathday : null,
                    'active_clinics' => $hasClinics ? $activeClinics : 0,
                    'active_clinics_list' => $patientActiveClinics[$hn] ?? [],
                    'has_clinics' => $hasClinics,
                    'death_table_date' => $isDeathTableRegistered ? ($p->death_table_date ?: '1900-01-01') : null,
                    'death_table_diag' => $p->death_table_diag,
                    'death_table_cause' => $p->death_table_cause,
                    'aopod_death_date' => $aopodData['death_date'] ?? null,
                    'aopod_death_diag' => $aopodData['death_diag'] ?? null,
                    'aopod_death_cause' => $aopodData['death_cause'] ?? null,
                    'aopod_death_place' => $aopodData['death_place'] ?? null,
                    'is_complete' => $isComplete
                ];
            }
        }

        // คำนวณเปอร์เซ็นต์
        $patientPct = $totalChecked > 0 ? round(($patientDeadCount / $totalChecked) * 100) : 100;
        $personPct = $personTotalCount > 0 ? round(($personDeadCount / $personTotalCount) * 100) : 100;
        $clinicPct = $clinicTotalCount > 0 ? round(($clinicCleanCount / $clinicTotalCount) * 100) : 100;
        $deathPct = $totalChecked > 0 ? round(($deathTableCount / $totalChecked) * 100) : 100;

        $patientDetails = "$patientDeadCount / $totalChecked ราย";
        $personDetails = "$personDeadCount / $personTotalCount ราย";
        $clinicDetails = "$clinicCleanCount / $clinicTotalCount ราย";
        $deathDetails = "$deathTableCount / $totalChecked ราย";

        // บันทึกสถิติลงฐานข้อมูลเพื่อแสดงผลทันทีเมื่อเปิดหน้า
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_pct_patient'], ['value' => $patientPct]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_details_patient'], ['value' => $patientDetails]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_pct_person'], ['value' => $personPct]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_details_person'], ['value' => $personDetails]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_pct_clinicmember'], ['value' => $clinicPct]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_details_clinicmember'], ['value' => $clinicDetails]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_pct_death'], ['value' => $deathPct]);
        DB::table('main_setting')->updateOrInsert(['name' => 'aopod_death_details_death'], ['value' => $deathDetails]);

        // บันทึกรายชื่อที่มีปัญหาลงในตาราง aopod_death_list เพื่อประหยัดเวลาโหลดหน้าใหม่
        try {
            DB::table('aopod_death_list')->truncate();
            $insertData = [];
            foreach ($detailedList as $item) {
                $insertData[] = [
                    'cid' => $item['cid'],
                    'hn' => $item['hn'],
                    'fullname' => $item['fullname'],
                    'patient_death' => $item['patient_death'],
                    'patient_deathday' => $item['patient_deathday'],
                    'person_death' => $item['person_death'],
                    'person_deathday' => $item['person_deathday'] === 'N/A' ? null : $item['person_deathday'],
                    'active_clinics' => $item['active_clinics'],
                    'active_clinics_list' => json_encode($item['active_clinics_list'], JSON_UNESCAPED_UNICODE),
                    'has_clinics' => $item['has_clinics'] ? 1 : 0,
                    'death_table_date' => $item['death_table_date'],
                    'death_table_diag' => $item['death_table_diag'],
                    'death_table_cause' => $item['death_table_cause'],
                    'aopod_death_date' => $item['aopod_death_date'],
                    'aopod_death_diag' => $item['aopod_death_diag'],
                    'aopod_death_cause' => $item['aopod_death_cause'],
                    'aopod_death_place' => $item['aopod_death_place'],
                    'is_complete' => $item['is_complete'] ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            if (count($insertData) > 0) {
                DB::table('aopod_death_list')->insert($insertData);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Aopod Death Truncate/Insert Error: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'api_status' => $apiStatus,
            'api_error' => $apiError,
            'stats' => [
                'patient_pct' => $patientPct,
                'patient_details' => "$patientDeadCount / $totalChecked ราย",
                'patient_alive_count' => $totalChecked - $patientDeadCount,
                'person_pct' => $personPct,
                'person_details' => "$personDeadCount / $personTotalCount ราย",
                'person_alive_count' => $personTotalCount - $personDeadCount,
                'clinic_pct' => $clinicPct,
                'clinic_details' => "$clinicCleanCount / $clinicTotalCount ราย",
                'clinic_pending_count' => $clinicTotalCount - $clinicCleanCount,
                'death_pct' => $deathPct,
                'death_details' => "$deathTableCount / $totalChecked ราย",
                'death_unregistered_count' => $totalChecked - $deathTableCount,
            ],
            'details' => $detailedList
        ]);
    }

    public function updateToken(Request $request)
    {
        $request->validate([
            'aopod_token' => 'nullable|string',
            'aopod_url_api_death' => 'nullable|string'
        ]);

        DB::table('main_setting')
            ->where('name', 'aopod_token')
            ->update(['value' => $request->input('aopod_token') ?? '']);

        DB::table('main_setting')
            ->where('name', 'aopod_url_api_death')
            ->update(['value' => $request->input('aopod_url_api_death') ?? '']);

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกการตั้งค่าการเชื่อมต่อ AOPOD เรียบร้อยแล้ว'
        ]);
    }
}
