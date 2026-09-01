<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Stm_ucs;
use App\Models\Stm_ucsexcel;
use App\Models\Stm_ofc;
use App\Models\Stm_ofcexcel;
use App\Models\Stm_lgo;
use App\Models\Stm_lgoexcel;
use App\Models\Stm_bkk;
use App\Models\Stm_bkkexcel;
use App\Models\Stm_bmt;
use App\Models\Stm_bmtexcel;
use App\Models\Stm_srt;
use App\Models\Stm_srtexcel;
use App\Models\Stm_pvt;
use App\Models\Stm_pvtexcel;
use App\Models\Rep_ucs;
use App\Models\Rep_ucsexcel;
use App\Models\Rep_ofc;
use App\Models\Rep_ofcexcel;
use App\Models\Rep_sss;
use App\Models\Rep_sssexcel;
use App\Models\Rep_lgo;
use App\Models\Rep_lgoexcel;
use App\Models\Rep_bkk;
use App\Models\Rep_bkkexcel;
use App\Models\Rep_bmt;
use App\Models\Rep_bmtexcel;
use App\Models\Rep_srt;
use App\Models\Rep_srtexcel;
use App\Models\Rep_pvt;
use App\Models\Rep_pvtexcel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EclaimBotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['saveSessionFromExtension']);
        $this->middleware(function ($request, $next) {
            $allowedWithoutLicense = ['saveSessionFromExtension', 'getStatus'];
            $currentAction = $request->route() ? $request->route()->getActionMethod() : '';
            
            if (!in_array($currentAction, $allowedWithoutLicense)) {
                if (!\App\Services\LicenseVerificationService::isModuleLicensed('sync_eclaim_thaid')) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'License Expired: กรุณาติดต่อผู้พัฒนา'
                        ], 403);
                    }
                    return response()->view('errors.license_error', [
                        'licenseInfo' => [
                            'status' => 'expired',
                            'message' => 'สิทธิ์การใช้งาน e-Claim ThaiD (sync_eclaim_thaid) หมดอายุแล้ว กรุณาติดต่อผู้พัฒนา'
                        ]
                    ], 403);
                }
            }
            return $next($request);
        })->except(['saveSessionFromExtension']);
    }

    /**
     * ทำความสะอาดและจัดรูปแบบ Cookie สำหรับส่งไปยัง e-Claim สปสช.
     * รองรับทั้ง Full Cookie String (JSESSIONID + ACCESS_TOKEN + STEEXWDE) และ JSESSIONID ดิบ
     * พร้อมตัดขยะ Google Analytics (_ga, _gid) ที่ทำให้เกิด HTTP 400 Header Too Large ออก
     */
    protected function cleanToken($rawToken)
    {
        $token = trim((string)$rawToken);
        if (empty($token)) {
            return '';
        }

        // กรณีเป็น Full Cookie string ที่มีหลายตัวคั่นด้วย ; (เช่น JSESSIONID=...; STEEXWDE=...)
        if (strpos($token, ';') !== false && (stripos($token, 'JSESSIONID=') !== false || stripos($token, 'STEEXWDE=') !== false || stripos($token, 'ACCESS_TOKEN=') !== false)) {
            $pairs = explode(';', $token);
            $cleanPairs = [];
            $seenKeys = [];

            foreach ($pairs as $p) {
                $p = trim($p);
                if (empty($p)) continue;
                $parts = explode('=', $p, 2);
                $k = trim($parts[0]);
                $v = isset($parts[1]) ? trim($parts[1]) : '';
                
                // กรองเอาเฉพาะ Cookie ที่จำเป็นต่อการ Auth (ตัดขยะ Google Analytics และ Duplicate _LEGACY, REFRESH_TOKEN ออก ป้องกัน Header Too Large)
                if (
                    strpos($k, '_ga') === 0 || 
                    $k === '_gid' || 
                    $k === '_gat' || 
                    $k === '_gcl_au' || 
                    strpos($k, '__') === 0 ||
                    $k === 'REFRESH_TOKEN' ||
                    $k === 'KC_RESTART' ||
                    stripos($k, '_LEGACY') !== false ||
                    isset($seenKeys[$k])
                ) {
                    continue;
                }
                
                $seenKeys[$k] = true;
                $cleanPairs[] = "{$k}={$v}";
            }
            if (!empty($cleanPairs)) {
                return implode('; ', $cleanPairs);
            }
        }

        // กรณีระบุมาเฉพาะ JSESSIONID=xxx
        if (preg_match('/JSESSIONID=([a-zA-Z0-9_\-]+)/i', $token, $m)) {
            return $m[1];
        }

        $token = preg_replace('/^JSESSIONID=\s*/i', '', $token);
        $token = preg_replace('/;.*$/', '', $token);
        $token = trim($token, " \t\n\r\0\x0B;\"'");
        return $token;
    }

    /**
     * ชุด Browser Headers เลียนแบบ Chrome เต็มรูปแบบ สำหรับส่งไปยัง e-Claim สปสช.
     */
    protected function getEclaimBrowserHeaders($token)
    {
        $cleaned = $this->cleanToken($token);
        $cookieHeader = '';

        if (stripos($cleaned, 'JSESSIONID=') !== false || stripos($cleaned, 'STEEXWDE=') !== false) {
            $cookieHeader = $cleaned;
        } else {
            $cookieHeader = 'JSESSIONID=' . $cleaned;
        }

        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'th-TH,th;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => 'https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Cookie' => $cookieHeader,
        ];
    }

    /**
     * 0. บันทึก Session Token จาก RiMS Chrome Extension (API Endpoint)
     * มีระบบตรวจสอบความถูกต้องของ Token ก่อนบันทึกทับ เพื่อป้องกันการหลงกดซิงก์จากเครื่องที่ยังไม่ได้ล็อกอิน
     */
    public function saveSessionFromExtension(Request $request)
    {
        if (!\App\Services\LicenseVerificationService::isModuleLicensed('sync_eclaim_thaid')) {
            return response()->json(['status' => 'error', 'message' => 'License Expired กรุณาติดต่อผู้พัฒนา'], 403);
        }

        $token = $this->cleanToken($request->token);
        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบ Session Token ที่ถูกต้อง'], 400);
        }

        // ตรวจสอบความถูกต้องของ Token โดยตรงกับ e-Claim สปสช. ก่อนบันทึกทับ
        $user = 'เจ้าหน้าที่ e-Claim';
        $serverHcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        if (!$serverHcode && \Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
            $serverHcode = DB::table('opdconfig')->value('hospitalcode');
        }
        $serverHname = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: '';
        if (!$serverHname && \Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
            $serverHname = DB::table('opdconfig')->value('hospitalname') ?: '';
        }

        try {
            $headers = $this->getEclaimBrowserHeaders($token);
            $probeUrl = 'https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do';
            $probeRes = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(8)
                ->get($probeUrl);

            if ($probeRes->status() === 200) {
                $html = (string)$probeRes->body();
                // ถ้าติดหน้า Error Page, ไม่มีสิทธิ์, หรือยังอยู่ที่หน้าประกาศ SSO (ThaiD)
                if (
                    stripos($html, '<title>Error Page</title>') !== false || 
                    stripos($html, 'Error Page') !== false || 
                    stripos($html, 'คุณไม่มีสิทธิ์') !== false ||
                    stripos($html, 'ประกาศใช้งานระบบ SSO') !== false ||
                    stripos($html, 'SSO (ThaiD)') !== false ||
                    stripos($html, 'frmErr') !== false ||
                    (stripos($html, 'Logout') === false && stripos($html, 'ออกจากระบบ') === false && stripos($html, 'ยินดีต้อนรับ') === false && stripos($html, 'maininscl') === false)
                ) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Session นี้ยังไม่ได้เข้าสู่ระบบ e-Claim (ThaiD) หรือยังอยู่ที่หน้าประกาศ SSO กรุณาเปิดเว็บ e-Claim ล็อกอินด้วย ThaiD ให้เสร็จสิ้นจนถึงหน้าหลัก แล้วกดซิงก์ Session ใหม่อีกครั้ง'
                    ], 422);
                }

                // ดึงชื่อผู้ใช้งานจริงจาก JWT Token หรือ HTML (ตัดข้อความที่เป็นประกาศทิ้ง)
                if (preg_match('/(?:ACCESS_TOKEN|KEYCLOAK_IDENTITY)=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $token, $jm)) {
                    try {
                        $parts = explode('.', $jm[1]);
                        if (count($parts) >= 2) {
                            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                            if (!empty($payload['nameTh'])) {
                                $user = $payload['nameTh'];
                            } elseif (!empty($payload['name'])) {
                                $user = $payload['name'];
                            }
                        }
                    } catch (\Exception $e) {}
                }

                if (empty($user) || $user === 'เจ้าหน้าที่ e-Claim' || mb_strlen($user) > 50 || stripos($user, 'ประกาศ') !== false || stripos($user, 'หน่วยบริการ') !== false) {
                    if (preg_match('/(?:ยินดีต้อนรับ|สวัสดี|ชื่อ)\s*[:：]?\s*([^\r\n<\[]{2,40})/u', $html, $m)) {
                        $extracted = trim(strip_tags($m[1]));
                        if (stripos($extracted, 'Audit User') === false && stripos($extracted, 'SSO') === false && stripos($extracted, 'ประกาศ') === false && stripos($extracted, 'หน่วยบริการ') === false && mb_strlen($extracted) <= 40) {
                            $user = $extracted;
                        }
                    } elseif (auth()->check()) {
                        $user = auth()->user()->name;
                    }
                }

                // ตรวจจับรหัสสถานพยาบาล 5 หลัก จาก e-Claim HTML
                $eclaimHcode = null;
                if (preg_match('/(?:หน่วยงาน|หน่วยบริการ|สถานพยาบาล|Hospcode|Hcode|รหัส)\s*[:：\-]?\s*(\d{5})/u', $html, $mHosp)) {
                    $eclaimHcode = $mHosp[1];
                } elseif (preg_match('/(?:\[|\()(\d{5})(?:\]|\))/u', $html, $mHosp)) {
                    $eclaimHcode = $mHosp[1];
                } elseif (preg_match('/\b(1\d{4})\b/', $html, $mHosp)) {
                    $eclaimHcode = $mHosp[1];
                }
                if (!$eclaimHcode && $request->hospcode && preg_match('/^\d{5}$/', trim($request->hospcode))) {
                    $eclaimHcode = trim($request->hospcode);
                }

                // 🛡️ ตรวจสอบ: ป้องกันสแกนข้าม รพ. (Hospcode Validation) และสแกนแทนกัน (CID Validation)
                $userCid = auth()->check() ? (auth()->user()->cid ?? null) : null;
                $valRes = $this->validateEclaimSessionContext($token, $serverHcode, $userCid, $eclaimHcode);
                if (!$valRes['valid']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $valRes['message']
                    ], 422);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถยืนยัน Session กับ e-Claim ได้ (HTTP ' . $probeRes->status() . ') กรุณาลองล็อกอิน ThaiD ใหม่อีกครั้ง'
                ], 422);
            }
        } catch (\Exception $e) {
            $userCid = auth()->check() ? (auth()->user()->cid ?? null) : null;
            $valRes = $this->validateEclaimSessionContext($token, $serverHcode, $userCid, $request->input('hospcode'));
            if (!$valRes['valid']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $valRes['message']
                ], 422);
            }
            if (auth()->check() && empty($user)) {
                $user = auth()->user()->name;
            }
        }

        $hcode = $serverHcode ?: ($request->hospcode ?: '10989');
        $now = date('Y-m-d H:i:s');

        // 1. บันทึกลง users table ของผู้ใช้งาน (ถ้ามี user_id/username หรือล็อกอินอยู่)
        $targetUserId = auth()->check() ? auth()->id() : null;
        if (!$targetUserId && $request->user_id) {
            $targetUserId = DB::table('users')->where('id', $request->user_id)->value('id');
        }
        if (!$targetUserId && $request->username) {
            $targetUserId = DB::table('users')->where('username', $request->username)->orWhere('name', $request->username)->value('id');
        }
        if ($targetUserId) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                    DB::table('users')->where('id', $targetUserId)->update([
                        'eclaim_session_token' => $token,
                        'eclaim_session_user' => $user,
                        'eclaim_session_time' => $now,
                    ]);
                }
            } catch (\Exception $e) {}
        }

        // 2. บันทึกลง Database (main_setting) เพื่อเป็น Fallback ส่วนกลาง
        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_token'],
            ['name_th' => 'e-Claim Session Token', 'value' => $token]
        );
        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_user'],
            ['name_th' => 'e-Claim Session User', 'value' => $user]
        );
        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_time'],
            ['name_th' => 'e-Claim Session Connected Time', 'value' => $now]
        );

        // 3. Save to Cache & Session
        \Illuminate\Support\Facades\Cache::put('eclaim_session_token_' . $hcode, $token, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_token_global', $token, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_user_' . $hcode, $user, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_time_' . $hcode, $now, 7200);

        Session::put('eclaim_session_token', $token);
        Session::put('eclaim_session_user', $user);
        Session::put('eclaim_session_time', $now);
        Session::put('eclaim_auth_method', 'RiMS Chrome Extension');

        return response()->json([
            'status' => 'success',
            'message' => 'ซิงก์ Session กับ RiMS สำเร็จแล้ว (' . $user . ')',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Validate Scanned/Synced e-Claim Session against configured Hospital Code and User CID
     *
     * @return array [ 'valid' => bool, 'message' => string, 'hospcode' => ?string, 'cid' => ?string, 'user' => ?string ]
     */
    public function validateEclaimSessionContext(?string $token = '', ?string $expectedHcode = null, ?string $expectedCid = null, ?string $directHcode = null, ?string $directCid = null): array
    {
        $serverHcode = $expectedHcode ?: DB::table('main_setting')->where('name', 'hospital_code')->value('value');
        $serverHname = DB::table('main_setting')->where('name', 'hospital_name')->value('value');
        
        if (!$serverHcode) {
            try {
                $serverHcode = DB::connection('hosxp')->table('opdconfig')->value('hospitalcode');
            } catch (\Exception $e) {}
        }
        if (!$serverHcode && \Illuminate\Support\Facades\Schema::hasTable('opdconfig')) {
            $serverHcode = DB::table('opdconfig')->value('hospitalcode');
        }
        $serverHcode = $serverHcode ?: '10989';

        if (!$serverHname) {
            try {
                $serverHname = DB::connection('hosxp')->table('opdconfig')->value('hospitalname');
            } catch (\Exception $e) {}
        }
        $serverHname = $serverHname ?: '';
        
        $detectedHcode = $directHcode ?: null;
        $detectedCid = $directCid ?: null;
        $detectedUser = null;

        $detectedUsername = null;

        // 1. Extract from JWT Tokens (ACCESS_TOKEN or KEYCLOAK_IDENTITY)
        if (preg_match_all('/(?:ACCESS_TOKEN|KEYCLOAK_IDENTITY)=([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/i', $token, $matches)) {
            foreach ($matches[1] as $jwtStr) {
                try {
                    $parts = explode('.', $jwtStr);
                    if (count($parts) >= 2) {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if (is_array($payload)) {
                            if (!$detectedHcode) {
                                if (!empty($payload['hospMain'])) $detectedHcode = (string)$payload['hospMain'];
                                elseif (!empty($payload['organize_id'])) $detectedHcode = (string)$payload['organize_id'];
                                elseif (!empty($payload['hospCode'])) $detectedHcode = (string)$payload['hospCode'];
                                elseif (!empty($payload['hcode'])) $detectedHcode = (string)$payload['hcode'];
                            }

                            if (!$detectedCid) {
                                if (!empty($payload['cid'])) $detectedCid = (string)$payload['cid'];
                                elseif (!empty($payload['id_card'])) $detectedCid = (string)$payload['id_card'];
                                elseif (!empty($payload['pid'])) $detectedCid = (string)$payload['pid'];
                            }

                            if (!$detectedUser) {
                                if (!empty($payload['nameTh'])) $detectedUser = $payload['nameTh'];
                                elseif (!empty($payload['name'])) $detectedUser = $payload['name'];
                            }

                            if (!$detectedUsername) {
                                if (!empty($payload['preferred_username'])) {
                                    $detectedUsername = (string)$payload['preferred_username'];
                                } elseif (!empty($payload['sub']) && str_contains($payload['sub'], ':')) {
                                    $subParts = explode(':', $payload['sub']);
                                    $detectedUsername = end($subParts);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {}
            }
        }

        // 1. ตรวจสอบเลขประจำตัวประชาชน (CID) และตัวตนผู้สแกน
        if (!empty($expectedCid)) {
            $cleanExpectedCid = preg_replace('/\D/', '', (string)$expectedCid);
            // ถ้าเลขบัตรในระบบไม่ครบ 13 หลัก (เช่น กรอกผิดหรือข้อมูลไม่สมบูรณ์)
            if (strlen($cleanExpectedCid) !== 13) {
                return [
                    'valid' => false,
                    'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: เลขบัตรประชาชนของผู้ใช้งานในระบบ RiMS ([{$expectedCid}]) ไม่ถูกต้อง (ต้องครบ 13 หลัก) กรุณาแก้ไขข้อมูลผู้ใช้ให้ถูกต้องก่อนทำรายการ",
                    'hospcode' => $detectedHcode,
                    'cid' => $detectedCid,
                    'user' => $detectedUser
                ];
            }

            // ถ้าได้ CID จากการสแกน และไม่ตรงกัน
            if (!empty($detectedCid)) {
                $cleanDetectedCid = preg_replace('/\D/', '', (string)$detectedCid);
                if ($cleanDetectedCid !== $cleanExpectedCid) {
                    return [
                        'valid' => false,
                        'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: เลขบัตรประจำตัวประชาชนของผู้สแกน [{$detectedCid}] ไม่ตรงกับบัญชีผู้ใช้งานในระบบ RiMS ([{$expectedCid}])",
                        'hospcode' => $detectedHcode,
                        'cid' => $detectedCid,
                        'user' => $detectedUser
                    ];
                }
            }
        } elseif (!empty($detectedCid)) {
            $cleanDetectedCid = preg_replace('/\D/', '', (string)$detectedCid);
            if (strlen($cleanDetectedCid) === 13) {
                $existsInRims = DB::table('users')->where('cid', $detectedCid)->orWhere('cid', $cleanDetectedCid)->exists();
                if (!$existsInRims && !auth()->check()) {
                    return [
                        'valid' => false,
                        'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: เลขบัตรประชาชน [{$detectedCid}] ไม่มีสิทธิ์ใช้งานในระบบ RiMS",
                        'hospcode' => $detectedHcode,
                        'cid' => $detectedCid,
                        'user' => $detectedUser
                    ];
                }
            }
        }

        // ตรวจสอบ Username / eclaim_user และ ชื่อ-นามสกุล หากผู้ใช้งานล็อกอินอยู่
        if (auth()->check()) {
            $currUser = auth()->user();
            if (!empty($currUser->eclaim_user) && !empty($detectedUsername)) {
                if (strtolower(trim($detectedUsername)) !== strtolower(trim($currUser->eclaim_user))) {
                    return [
                        'valid' => false,
                        'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: บัญชี e-Claim ผู้สแกน [{$detectedUsername}] ไม่ตรงกับ e-Claim User ในระบบ RiMS ([{$currUser->eclaim_user}])",
                        'hospcode' => $detectedHcode,
                        'cid' => $detectedCid,
                        'user' => $detectedUser
                    ];
                }
            }

            if (!empty($detectedUser) && $detectedUser !== 'เจ้าหน้าที่ e-Claim' && !empty($currUser->name)) {
                $cleanDbName = trim(str_replace(['นาย', 'นาง', 'น.ส.', 'นางสาว', 'ด.ช.', 'ด.ญ.'], '', $currUser->name));
                $cleanScanName = trim(str_replace(['นาย', 'นาง', 'น.ส.', 'นางสาว', 'ด.ช.', 'ด.ญ.'], '', $detectedUser));
                // ตรวจสอบว่าชื่อหรือนามสกุลตรงกันหรือไม่
                $dbFirstWord = explode(' ', $cleanDbName)[0] ?? '';
                if ($dbFirstWord && stripos($cleanScanName, $dbFirstWord) === false && stripos($cleanDbName, $cleanScanName) === false) {
                    return [
                        'valid' => false,
                        'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: บัญชี e-Claim ผู้สแกนคือ [{$detectedUser}] ไม่ตรงกับผู้ใช้งานระบบ RiMS ([{$currUser->name}])",
                        'hospcode' => $detectedHcode,
                        'cid' => $detectedCid,
                        'user' => $detectedUser
                    ];
                }
            }
        }

        // 2. ตรวจสอบรหัสสถานพยาบาล (Hospcode)
        // ถ้ายังไม่ได้ Hospcode จาก Token หรือ Playwright ให้ Probe สดจาก e-Claim webComponent
        if (empty($detectedHcode) && !empty($token)) {
            try {
                $probeHeaders = $this->getEclaimBrowserHeaders($token);
                $probeRes = Http::withHeaders($probeHeaders)->withoutVerifying()->timeout(6)->get('https://eclaim.nhso.go.th/webComponent/checkdata/CheckDataAction.do');
                if ($probeRes->successful()) {
                    $probeHtml = $probeRes->body();
                    if (preg_match('/(?:หน่วยงาน|หน่วยบริการ|สถานพยาบาล)\s*:\s*([^\[<]+)\[(\d{5})\]/u', $probeHtml, $mHosp)) {
                        $detectedHcode = trim($mHosp[2]);
                    }
                }
            } catch (\Exception $e) {}
        }

        if (!empty($detectedHcode) && !empty($serverHcode)) {
            $cleanDetectedHcode = trim((string)$detectedHcode);
            $cleanServerHcode = trim((string)$serverHcode);
            if ($cleanDetectedHcode !== $cleanServerHcode) {
                return [
                    'valid' => false,
                    'message' => "❌ การเข้าสู่ระบบถูกปฏิเสธ: บัญชี e-Claim นี้สังกัดหน่วยบริการ [{$detectedHcode}] ซึ่งไม่ตรงกับหน่วยบริการของระบบ RiMS นี้ ([{$serverHcode}]" . ($serverHname ? " - {$serverHname}" : "") . ") กรุณาใช้บัญชีของโรงพยาบาลนี้เท่านั้น",
                    'hospcode' => $detectedHcode,
                    'cid' => $detectedCid,
                    'user' => $detectedUser
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'ตรวจสอบความถูกต้องสำเร็จ',
            'hospcode' => $detectedHcode ?: $serverHcode,
            'cid' => $detectedCid,
            'user' => $detectedUser
        ];
    }

    /**
     * Start Playwright ThaiD QR Login Session
     */
    public function startThaidQrSession(Request $request)
    {
        $pwStatus = \App\Helpers\PlaywrightHelper::checkStatus();
        if (!$pwStatus['available']) {
            $installRes = \App\Helpers\PlaywrightHelper::autoInstall();
            if (!$installRes['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ระบบ Playwright/Chromium ยังไม่พร้อมใช้งาน: ' . ($installRes['message'] ?? '')
                ], 500);
            }
        }

        $sessionId = uniqid('thaid_' . time() . '_');
        $storageDir = storage_path('app');
        $sessionFile = $storageDir . "/thaid_session_{$sessionId}.json";

        file_put_contents($sessionFile, json_encode([
            'status' => 'INITIALIZING',
            'session_id' => $sessionId,
            'message' => 'กำลังเริ่มต้นเบราว์เซอร์...'
        ]));

        \App\Helpers\PlaywrightHelper::startThaidLoginProcess($sessionId);

        // Poll for up to 15 seconds for QR_READY
        $maxAttempts = 30;
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            usleep(500000); // 0.5s
            $attempt++;

            if (file_exists($sessionFile)) {
                $data = json_decode(file_get_contents($sessionFile), true);
                if (is_array($data) && ($data['status'] ?? '') === 'QR_READY') {
                    return response()->json([
                        'status' => 'success',
                        'session_id' => $sessionId,
                        'qr_image' => $data['qr_image'] ?? '',
                        'ref_code' => $data['ref_code'] ?? '',
                        'expires_in' => $data['expires_in'] ?? 120,
                        'message' => 'QR Code พร้อมสแกน'
                    ]);
                } elseif (is_array($data) && ($data['status'] ?? '') === 'FAILED') {
                    @unlink($sessionFile);
                    return response()->json([
                        'status' => 'error',
                        'message' => $data['message'] ?? 'เกิดข้อผิดพลาดในการร้องขอ QR Code'
                    ], 500);
                }
            }
        }

        return response()->json([
            'status' => 'pending',
            'session_id' => $sessionId,
            'message' => 'กำลังเตรียม QR Code กรุณารอสักครู่...'
        ]);
    }

    /**
     * Check ThaiD QR Scan Status
     */
    public function checkThaidQrStatus(Request $request)
    {
        $sessionId = $request->query('session_id') ?: $request->input('session_id');
        if (empty($sessionId)) {
            return response()->json(['status' => 'error', 'message' => 'Missing session_id'], 400);
        }

        $sessionFile = storage_path("app/thaid_session_{$sessionId}.json");
        if (!file_exists($sessionFile)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่พบ Session'], 404);
        }

        $data = json_decode(file_get_contents($sessionFile), true);
        if (!is_array($data)) {
            return response()->json(['status' => 'error', 'message' => 'ข้อมูล Session ไม่ถูกต้อง'], 500);
        }

        $status = $data['status'] ?? 'WAITING_SCAN';

        if ($status === 'SUCCESS') {
            $token = $data['cookies'] ?? '';
            $user = $data['user'] ?? 'เจ้าหน้าที่ e-Claim';

            $cleanToken = $this->cleanToken($token);
            $now = date('Y-m-d H:i:s');
            $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
            $userCid = auth()->check() ? (auth()->user()->cid ?? null) : null;

            // 🛡️ ป้องกันสแกนข้าม รพ. (Hospcode Validation) และสแกนแทนกัน (CID Validation)
            $valRes = $this->validateEclaimSessionContext($cleanToken, $hcode, $userCid, $data['hospcode'] ?? null, $data['cid'] ?? null);
            if (!$valRes['valid']) {
                @unlink($sessionFile);
                return response()->json([
                    'status' => 'error',
                    'state' => 'REJECTED',
                    'message' => $valRes['message']
                ], 422);
            }

            if (!empty($valRes['user'])) {
                $user = $valRes['user'];
            }

            // 1. บันทึกลง users table ของผู้ใช้งานปัจจุบัน
            if (auth()->check()) {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                        DB::table('users')->where('id', auth()->id())->update([
                            'eclaim_session_token' => $cleanToken,
                            'eclaim_session_user' => $user,
                            'eclaim_session_time' => $now,
                        ]);
                    }
                } catch (\Exception $e) {}
            }

            // 2. บันทึกลง Database (main_setting) เป็น Fallback
            DB::table('main_setting')->updateOrInsert(
                ['name' => 'eclaim_session_token'],
                ['name_th' => 'e-Claim Session Token', 'value' => $cleanToken]
            );
            DB::table('main_setting')->updateOrInsert(
                ['name' => 'eclaim_session_user'],
                ['name_th' => 'e-Claim Session User', 'value' => $user]
            );
            DB::table('main_setting')->updateOrInsert(
                ['name' => 'eclaim_session_time'],
                ['name_th' => 'e-Claim Session Connected Time', 'value' => $now]
            );

            \Illuminate\Support\Facades\Cache::put('eclaim_session_token_' . $hcode, $cleanToken, 7200);
            \Illuminate\Support\Facades\Cache::put('eclaim_session_token_global', $cleanToken, 7200);
            \Illuminate\Support\Facades\Cache::put('eclaim_session_user_' . $hcode, $user, 7200);
            \Illuminate\Support\Facades\Cache::put('eclaim_session_time_' . $hcode, $now, 7200);

            Session::put('eclaim_session_token', $cleanToken);
            Session::put('eclaim_session_user', $user);
            Session::put('eclaim_session_time', $now);
            Session::put('eclaim_auth_method', 'ThaiD QR Code (Playwright)');

            @unlink($sessionFile);

            return response()->json([
                'status' => 'success',
                'state' => 'LOGGED_IN',
                'user' => $user,
                'message' => 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $user
            ]);
        }

        if ($status === 'QR_READY') {
            return response()->json([
                'status' => 'success',
                'state' => 'QR_READY',
                'qr_image' => $data['qr_image'] ?? '',
                'ref_code' => $data['ref_code'] ?? '',
                'expires_in' => $data['expires_in'] ?? 120,
                'message' => 'พร้อมสแกน QR Code'
            ]);
        }

        if ($status === 'FAILED' || $status === 'EXPIRED') {
            @unlink($sessionFile);
            return response()->json([
                'status' => 'error',
                'state' => $status,
                'message' => $data['message'] ?? 'การเชื่อมต่อหมดอายุ หรือล้มเหลว'
            ]);
        }

        return response()->json([
            'status' => 'pending',
            'state' => $status,
            'message' => $data['message'] ?? 'กำลังดำเนินการ...'
        ]);
    }

    /**
     * Cancel ThaiD QR Session
     */
    public function cancelThaidQrSession(Request $request)
    {
        $sessionId = $request->input('session_id');
        if ($sessionId) {
            $sessionFile = storage_path("app/thaid_session_{$sessionId}.json");
            if (file_exists($sessionFile)) {
                file_put_contents($sessionFile, json_encode(['status' => 'CANCELLED']));
                usleep(500000);
                @unlink($sessionFile);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * 1. ตรวจสอบสถานะการเชื่อมต่อ e-Claim / ThaiD Session (ค้นหาจาก Session -> Cache -> DB main_setting)
     */
    public function getStatus(Request $request)
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        
        $userToken = null;
        $userSessionUser = null;
        $userSessionTime = null;
        $userAuthMethod = '';

        // 1. ตรวจสอบ Session เฉพาะของผู้ใช้งานปัจจุบันก่อน (Priority 1: User-Specific)
        if (auth()->check()) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                $uData = DB::table('users')->where('id', auth()->id())->first(['eclaim_session_token', 'eclaim_session_user', 'eclaim_session_time']);
                if ($uData && !empty($uData->eclaim_session_token)) {
                    $userToken = $uData->eclaim_session_token;
                    $userSessionUser = $uData->eclaim_session_user;
                    $userSessionTime = $uData->eclaim_session_time;
                    $userAuthMethod = 'Session ประจำตัว (' . (auth()->user()->name ?: 'User') . ')';
                }
            }
            if (!$userToken && Session::has('eclaim_session_token')) {
                $userToken = Session::get('eclaim_session_token');
                $userSessionUser = Session::get('eclaim_session_user');
                $userSessionTime = Session::get('eclaim_session_time');
                $userAuthMethod = Session::get('eclaim_auth_method', 'Session เบราว์เซอร์ส่วนตัว');
            }
        }

        // Live Probe ทดสอบ Token ของ User ก่อน
        if ($userToken) {
            $userToken = $this->cleanToken($userToken);
            $probePassed = false;
            try {
                $headers = $this->getEclaimBrowserHeaders($userToken);
                $probeUrl = "https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do";
                $probeRes = Http::withHeaders($headers)->withoutVerifying()->timeout(8)->get($probeUrl);
                $html = (string)$probeRes->body();

                if (
                    $probeRes->status() === 200 &&
                    stripos($html, 'Error Page') === false &&
                    stripos($html, 'frmErr') === false &&
                    stripos($html, 'คุณไม่มีสิทธิ์') === false &&
                    stripos($html, 'ประกาศใช้งานระบบ SSO') === false &&
                    stripos($html, 'SSO (ThaiD)') === false &&
                    (stripos($html, 'Logout') !== false || stripos($html, 'ออกจากระบบ') !== false || stripos($html, 'ยินดีต้อนรับ') !== false || stripos($html, 'maininscl') !== false)
                ) {
                    $probePassed = true;
                    if (preg_match('/(?:ยินดีต้อนรับ|สวัสดี|ชื่อ)\s*[:：]?\s*([^\r\n<\[]+)/u', $html, $m)) {
                        $extracted = trim(strip_tags($m[1]));
                        if (stripos($extracted, 'Audit User') === false && stripos($extracted, 'SSO') === false) {
                            $userSessionUser = $extracted;
                        }
                    }
                }
            } catch (\Exception $e) {
                $probePassed = false;
            }

            if ($probePassed) {
                Session::put('eclaim_session_token', $userToken);
                Session::put('eclaim_session_user', $userSessionUser);
                Session::put('eclaim_session_time', $userSessionTime ?: date('Y-m-d H:i:s'));

                return response()->json([
                    'connected' => true,
                    'user' => $userSessionUser ?: (auth()->check() ? auth()->user()->name : 'ผู้ใช้งาน e-Claim'),
                    'connected_at' => $userSessionTime ?: date('Y-m-d H:i:s'),
                    'auth_method' => $userAuthMethod ?: 'Session ประจำตัวผู้ใช้งาน'
                ]);
            }
        }

        // 2. Fallback: ถ้า Session ส่วนตัวไม่มีหรือหมดอายุ ให้ตรวจสอบ Session ส่วนกลางจาก main_setting (Priority 2: Shared Fallback)
        $globalToken = DB::table('main_setting')->where('name', 'eclaim_session_token')->value('value')
            ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_' . $hospcode) 
            ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_global')));
            
        $globalUser = DB::table('main_setting')->where('name', 'eclaim_session_user')->value('value') ?: 'ผู้ใช้งาน e-Claim';
        $globalTime = DB::table('main_setting')->where('name', 'eclaim_session_time')->value('value') ?: date('Y-m-d H:i:s');

        if ($globalToken) {
            $globalToken = $this->cleanToken($globalToken);
            $probePassed = false;
            try {
                $headers = $this->getEclaimBrowserHeaders($globalToken);
                $probeUrl = "https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do";
                $probeRes = Http::withHeaders($headers)->withoutVerifying()->timeout(8)->get($probeUrl);
                $html = (string)$probeRes->body();

                if (
                    $probeRes->status() === 200 &&
                    stripos($html, 'Error Page') === false &&
                    stripos($html, 'frmErr') === false &&
                    stripos($html, 'คุณไม่มีสิทธิ์') === false &&
                    stripos($html, 'ประกาศใช้งานระบบ SSO') === false &&
                    stripos($html, 'SSO (ThaiD)') === false &&
                    (stripos($html, 'Logout') !== false || stripos($html, 'ออกจากระบบ') !== false || stripos($html, 'ยินดีต้อนรับ') !== false || stripos($html, 'maininscl') !== false)
                ) {
                    $probePassed = true;
                    if (preg_match('/(?:ยินดีต้อนรับ|สวัสดี|ชื่อ)\s*[:：]?\s*([^\r\n<\[]+)/u', $html, $m)) {
                        $extracted = trim(strip_tags($m[1]));
                        if (stripos($extracted, 'Audit User') === false && stripos($extracted, 'SSO') === false) {
                            $globalUser = $extracted;
                        }
                    }
                }
            } catch (\Exception $e) {
                $probePassed = false;
            }

            if ($probePassed) {
                Session::put('eclaim_session_token', $globalToken);
                Session::put('eclaim_session_user', $globalUser);
                Session::put('eclaim_session_time', $globalTime);

                return response()->json([
                    'connected' => true,
                    'user' => $globalUser,
                    'connected_at' => $globalTime,
                    'auth_method' => 'Session ส่วนกลาง (แชร์จาก main_setting)'
                ]);
            }
        }

        // กรณีทั้งส่วนตัวและส่วนกลางยังไม่ได้ต่อ หรือหมดอายุ
        return response()->json([
            'connected' => false,
            'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim หรือ Session หมดอายุ (กรุณาเข้าสู่ระบบด้วย ThaiD หรือกดซิงก์ Session จาก Extension)'
        ]);
    }

    /**
     * 2. สร้าง QR Code สำหรับ ThaiD SSO (แนวทางที่ 2)
     */
    public function generateThaiDQR(Request $request)
    {
        $sessionId = 'THAID_' . uniqid() . '_' . time();
        Session::put('thaid_pending_session', $sessionId);

        // จำลอง URL / Deep Link ของ ThaiD (หรือเชื่อมต่อ DOPA OIDC ถ้ามีการลงทะเบียน Client ID)
        $qrData = "https://imauth.bora.dopa.go.th/api/v2/oauth2/auth?client_id=nhso_oss&response_type=code&state=" . $sessionId;
        
        // ใช้ Google Chart API / QR Server สร้าง QR Code image URL
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($qrData);

        return response()->json([
            'status' => 'success',
            'session_id' => $sessionId,
            'qr_image_url' => $qrImageUrl,
            'expires_in' => 180 // 3 นาที
        ]);
    }

    /**
     * 3. ตรวจสอบ / ยืนยันการสแกน ThaiD (Polling หรือ Mock Confirm)
     */
    public function verifyThaiDLogin(Request $request)
    {
        $sessionId = $request->session_id ?: Session::get('thaid_pending_session');
        $user = auth()->user();

        // บันทึก Session จำลองว่าเข้าสู่ระบบสำเร็จ
        Session::put('eclaim_session_token', 'ECLAIM_TOKEN_' . md5($sessionId . time()));
        Session::put('eclaim_session_user', $user->name . ' (' . ($user->cid ?: 'CID: ' . substr($user->username, 0, 13)) . ')');
        Session::put('eclaim_session_time', date('Y-m-d H:i:s'));
        Session::put('eclaim_auth_method', 'ThaiD SSO (D.DOPA)');

        return response()->json([
            'status' => 'success',
            'message' => 'ยืนยันตัวตนผ่าน ThaiD สำเร็จ พร้อมเข้าถึงระบบ e-Claim',
            'user' => Session::get('eclaim_session_user')
        ]);
    }

    /**
     * ดึงค่า Active Session Token (Users Table -> Session -> DB main_setting -> Cache)
     */
    protected function getActiveEclaimToken()
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $token = null;

        // 1. ตรวจสอบ User-Specific ก่อน
        if (auth()->check()) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                $token = DB::table('users')->where('id', auth()->id())->value('eclaim_session_token');
            }
            if (!$token) {
                $token = Session::get('eclaim_session_token');
            }
        }

        // 2. Fallback ไป main_setting
        if (!$token) {
            $token = DB::table('main_setting')->where('name', 'eclaim_session_token')->value('value')
                ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_' . $hospcode) 
                ?: (\Illuminate\Support\Facades\Cache::get('eclaim_session_token_global')
                ?: Session::get('eclaim_session_token')));
        }

        if ($token) {
            $token = $this->cleanToken($token);
            Session::put('eclaim_session_token', $token);
        }

        return $token;
    }

    /**
     * 4. บันทึก Session Token / Cookie โดยตรง
     */
    public function saveSessionToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = $this->cleanToken($request->token);
        $user = $request->input('user') ?: (auth()->check() ? auth()->user()->name : null);
        $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $now = date('Y-m-d H:i:s');

        // 🛡️ ป้องกันสแกนข้าม รพ. (Hospcode Validation) และสแกนแทนกัน (CID Validation)
        $userCid = auth()->check() ? (auth()->user()->cid ?? null) : null;
        $valRes = $this->validateEclaimSessionContext($token, $hcode, $userCid, $request->input('hospcode'), $request->input('cid'));
        if (!$valRes['valid']) {
            return response()->json([
                'status' => 'error',
                'connected' => false,
                'message' => $valRes['message']
            ], 422);
        }

        if (!empty($valRes['user'])) {
            $user = $valRes['user'];
        }

        // ถ้ายังไม่มีชื่อผู้ใช้ ให้ลอง probe ดึงชื่อผู้ใช้จริงจาก e-Claim
        if (!$user) {
            try {
                $headers = $this->getEclaimBrowserHeaders($token);
                $probeRes = Http::withHeaders($headers)
                    ->withoutVerifying()
                    ->timeout(4)
                    ->get('https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do');
                
                $html = (string)$probeRes->body();
                if (preg_match('/(?:ชื่อ\s*[:：]?|ผู้ใช้งาน\s*[:：]?|ยินดีต้อนรับ\s*[:：]?)\s*([^\r\n<\[]+)/u', $html, $m)) {
                    $user = trim(strip_tags($m[1]));
                }
            } catch (\Exception $e) {}
        }

        if (!$user) {
            $user = 'เจ้าหน้าที่ e-Claim';
        }

        // 1. บันทึกลง users table ของผู้ใช้งานปัจจุบัน
        if (auth()->check()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                    DB::table('users')->where('id', auth()->id())->update([
                        'eclaim_session_token' => $token,
                        'eclaim_session_user' => $user,
                        'eclaim_session_time' => $now,
                    ]);
                }
            } catch (\Exception $e) {}
        }

        // 2. บันทึกลง main_setting เป็น Fallback ส่วนกลาง
        try {
            DB::statement("ALTER TABLE main_setting MODIFY COLUMN value LONGTEXT NULL");
        } catch (\Exception $e) {}

        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_token'],
            ['name_th' => 'e-Claim Session Token', 'value' => $token]
        );
        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_user'],
            ['name_th' => 'e-Claim Session User', 'value' => $user]
        );
        DB::table('main_setting')->updateOrInsert(
            ['name' => 'eclaim_session_time'],
            ['name_th' => 'e-Claim Session Connected Time', 'value' => $now]
        );

        // 3. Save to Cache & Session
        \Illuminate\Support\Facades\Cache::put('eclaim_session_token_' . $hcode, $token, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_token_global', $token, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_user_' . $hcode, $user, 7200);
        \Illuminate\Support\Facades\Cache::put('eclaim_session_time_' . $hcode, $now, 7200);

        Session::put('eclaim_session_token', $token);
        Session::put('eclaim_session_user', $user);
        Session::put('eclaim_session_time', $now);
        Session::put('eclaim_auth_method', 'Session Cookie / Token');

        return response()->json([
            'status' => 'success',
            'connected' => true,
            'message' => 'เชื่อมต่อระบบ e-Claim สำเร็จแล้ว (' . $user . ')',
            'user' => $user
        ]);
    }

    /**
     * 5. ออกจากระบบ / ล้าง Session e-Claim
     */
    public function logoutSession(Request $request)
    {
        $hcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        
        // 1. ล้างค่าใน users table ของ User ปัจจุบัน
        if (auth()->check()) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'eclaim_session_token')) {
                    DB::table('users')->where('id', auth()->id())->update([
                        'eclaim_session_token' => null,
                        'eclaim_session_user' => null,
                        'eclaim_session_time' => null,
                    ]);
                }
            } catch (\Exception $e) {}
        }

        // 2. Clear Database (main_setting)
        DB::table('main_setting')
            ->whereIn('name', ['eclaim_session_token', 'eclaim_session_user', 'eclaim_session_time'])
            ->update(['value' => '']);

        // 3. Clear Cache
        \Illuminate\Support\Facades\Cache::forget('eclaim_session_token_' . $hcode);
        \Illuminate\Support\Facades\Cache::forget('eclaim_session_token_global');
        \Illuminate\Support\Facades\Cache::forget('eclaim_session_user_' . $hcode);
        \Illuminate\Support\Facades\Cache::forget('eclaim_session_time_' . $hcode);

        // 4. Clear Session
        Session::forget(['eclaim_session_token', 'eclaim_session_user', 'eclaim_session_time', 'eclaim_auth_method', 'thaid_pending_session']);

        return response()->json([
            'status' => 'success',
            'message' => 'ตัดการเชื่อมต่อกับระบบ e-Claim เรียบร้อยแล้ว'
        ]);
    }

    public function searchStatements(Request $request)
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $sessionToken = $this->getActiveEclaimToken();

        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาระบุ Session Cookie หรือกดซิงก์จาก Extension ก่อนค้นหาข้อมูล'
            ], 401);
        }

        $budgetYear = $request->budget_year ?: (date('Y') + 543 + (date('m') >= 10 ? 1 : 0));
        $month = $request->month ?: date('m');
        $claimType = $request->claim_type ?: 'stm_ucs';

        // แปลงปี พ.ศ. เป็น ค.ศ. สำหรับส่งไปยัง e-Claim (เช่น 2569 -> 2026)
        $yearAD = (int)$budgetYear > 2500 ? ((int)$budgetYear - 543) : (int)$budgetYear;
        $monthStr = sprintf('%02d', (int)$month);

        try {
            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/ucs/statementUCSAction.do';
            $headers['X-Requested-With'] = 'XMLHttpRequest';

            $response = Http::withHeaders($headers)->withoutVerifying()->timeout(30)->asForm()->post('https://eclaim.nhso.go.th/webComponent/ucs/statementUCSViewAction.do', [
                'year' => (string)$yearAD,
                'month' => $monthStr,
                'person_type' => '',
                'hcode' => (string)$hospcode,
                'period_no' => '',
                'PAGE_HEAD' => '1'
            ]);

            if ($response->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'ไม่สามารถเชื่อมต่อกับ e-Claim ได้ (HTTP Status: ' . $response->status() . ')'
                ], 500);
            }

            $html = $response->body();

            if (
                $response->status() === 302 || 
                $response->status() === 401 || 
                strpos($html, 'เข้าสู่ระบบ') !== false || 
                strpos($html, 'Login') !== false || 
                strpos($html, 'คุณไม่มีสิทธิ์') !== false || 
                strpos($html, 'frmErr') !== false || 
                strpos($html, 'Error Page') !== false
            ) {
                $errMsg = 'Session e-Claim บนเซิร์ฟเวอร์หมดอายุหรือไม่ถูกต้อง กรุณากดเปิดหน้าเมนู Statement ในเว็บ e-Claim แล้วกดปุ่ม "ซิงก์ Session เข้า RiMS" จาก Extension ใหม่อีกครั้ง';
                if (strpos($html, 'คุณไม่มีสิทธิ์') !== false) {
                    $errMsg = 'บัญชี e-Claim (ThaiD) ที่เชื่อมต่อไม่มีสิทธิ์เข้าถึงรายงาน Statement UCS (ต้องใช้บัญชีที่มีสิทธิ์การเงิน/Statement จากระบบ OSS สปสช.)';
                }
                return response()->json([
                    'status' => 'error',
                    'message' => $errMsg
                ], 401);
            }

            // ตรวจสอบจำนวนรายการที่พบ
            preg_match('/พบข้อมูลทั้งหมด\s*(\d+)\s*รายการ/u', $html, $mCount);
            $foundCount = isset($mCount[1]) ? (int)$mCount[1] : 0;

            if ($foundCount === 0 || !str_contains($html, '<tbody')) {
                return response()->json([
                    'status' => 'success',
                    'budget_year' => $budgetYear,
                    'month' => $month,
                    'count' => 0,
                    'data' => [],
                    'message' => 'ไม่พบข้อมูล Statement ในงวดเดือนที่เลือก'
                ]);
            }

            // ดึงข้อมูลในฐานข้อมูล RIMS มาเปรียบเทียบ
            $existingRounds = DB::table('stm_ucs')
                ->select(
                    'round_no',
                    'stm_filename',
                    DB::raw('COUNT(cid) as count_cid'),
                    DB::raw('SUM(charge) as charge_total'),
                    DB::raw('SUM(receive_total) as receive_total')
                )
                ->groupBy('round_no', 'stm_filename')
                ->get()
                ->keyBy('round_no');

            // Parse ตาราง HTML จาก e-Claim
            preg_match('/<tbody[^>]*>(.*?)<\/tbody>/is', $html, $tbody);
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tbody[1] ?? '', $rows);

            $statements = [];
            foreach ($rows[1] as $idx => $r) {
                preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $r, $tds);
                if (empty($tds[1]) || count($tds[1]) < 8) {
                    continue;
                }

                $cols = array_map(function($t) { return trim(strip_tags($t)); }, $tds[1]);
                
                $period = $cols[0] ?? '';
                $issueDate = $cols[1] ?? '';
                $monthName = $cols[2] ?? '';
                $typeText = $cols[3] ?? '';
                $stmtPeriod = $cols[4] ?? '';
                $docNo = $cols[7] ?? '';

                $isIPD = str_contains($typeText, 'ใน') || str_contains($stmtPeriod, 'IP');
                $type = $isIPD ? 'IPD' : 'OPD';
                $filename = "STM_" . ($docNo ?: $stmtPeriod) . ".xls";
                $roundNo = $stmtPeriod ?: $docNo;

                // ดึง parameters สำหรับดาวน์โหลดจากฟังก์ชัน downloadBill(...)
                $dlParams = [];
                if (preg_match('/downloadBill\(([^)]+)\)/i', $r, $mDl)) {
                    $rawArgs = explode(',', $mDl[1]);
                    $dlParams = array_map(function($arg) {
                        return trim(trim($arg), "'\"");
                    }, $rawArgs);
                }

                // ตรวจสอบกับฐานข้อมูล RIMS
                $dbMatch = $existingRounds->get($roundNo) ?: $existingRounds->where('stm_filename', $filename)->first();
                $isImported = !is_null($dbMatch);
                $importedCount = $isImported ? (int)$dbMatch->count_cid : 0;
                $chargeTotal = $isImported ? (float)$dbMatch->charge_total : 0;
                $receiveTotal = $isImported ? (float)$dbMatch->receive_total : 0;

                $statements[] = [
                    'round_no' => $roundNo,
                    'filename' => $filename,
                    'document_no' => $docNo ?: ($dlParams[0] ?? ''),
                    'person_type' => $dlParams[1] ?? ($isIPD ? '2' : '1'),
                    'hcode' => $dlParams[2] ?? $hospcode,
                    'hname' => $dlParams[3] ?? ($hospcode . ' รพ.หัวตะพาน'),
                    'province_name' => $dlParams[4] ?? '3700 อำนาจเจริญ',
                    'datesend_from' => $dlParams[5] ?? '',
                    'datesend_to' => $dlParams[6] ?? '',
                    'type' => $type,
                    'issue_date' => $issueDate,
                    'count_cid' => $importedCount ?: '-',
                    'charge_total' => $chargeTotal,
                    'receive_total' => $receiveTotal,
                    'is_imported' => $isImported,
                    'imported_count' => $importedCount,
                ];
            }

            return response()->json([
                'status' => 'success',
                'budget_year' => $budgetYear,
                'month' => $month,
                'count' => count($statements),
                'data' => $statements
            ]);

        } catch (\Exception $e) {
            Log::error("e-Claim searchStatements error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 7. สั่งดาวน์โหลดไฟล์จาก e-Claim และนำเข้าสู่ฐานข้อมูล RIMS อัตโนมัติ (เสมือนนำเข้าจากไฟล์ Excel)
     */
    public function importStatements(Request $request)
    {
        set_time_limit(0);
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $sessionToken = $this->getActiveEclaimToken();

        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session e-Claim หมดอายุ กรุณาซิงก์จาก Extension หรือเชื่อมต่อใหม่อีกครั้ง'
            ], 401);
        }

        $items = $request->items ?: [];
        if (empty($items)) {
            // ถ้าส่งมาเป็น rounds ธรรมดา
            $rounds = $request->rounds ?: [];
            if (empty($rounds)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'กรุณาเลือกไฟล์ Statement ที่ต้องการนำเข้าอย่างน้อย 1 รายการ'
                ], 400);
            }
            $items = array_map(function($r) { return ['document_no' => $r, 'round_no' => $r]; }, $rounds);
        }

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $importedRounds = [];
        $totalImportedRows = 0;

        /* ======================================================
         1) เคลียร์ Staging Table
        ====================================================== */
        Stm_ucsexcel::truncate();

        foreach ($items as $item) {
            $docNo = $item['document_no'] ?? ($item['round_no'] ?? '');
            $personType = $item['person_type'] ?? (str_contains($docNo, 'IP') ? '2' : '1');
            $hname = $item['hname'] ?? ($hospcode . ' รพ.หัวตะพาน');
            $provinceName = $item['province_name'] ?? '3700 อำนาจเจริญ';
            $fileName = $item['filename'] ?? ("STM_" . $docNo . ".xls");

            // ดาวน์โหลดไฟล์จริงจาก e-Claim
            try {
                $headers = $this->getEclaimBrowserHeaders($sessionToken);
                $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/ucs/statementUCSAction.do';

                $dlResponse = Http::withHeaders($headers)->withoutVerifying()->timeout(60)->asForm()->post('https://eclaim.nhso.go.th/webComponent/ucs/statementUCSDownloadAction.do', [
                    'document_no' => $docNo,
                    'person_type' => $personType,
                    'hcode' => $hospcode,
                    'hname' => $hname,
                    'province_name' => $provinceName,
                    'datesend_from' => $item['datesend_from'] ?? '',
                    'datesend_to' => $item['datesend_to'] ?? '',
                ]);

                if ($dlResponse->status() !== 200 || strlen($dlResponse->body()) < 1000) {
                    Log::warning("Failed to download statement $docNo from e-Claim (Size: " . strlen($dlResponse->body()) . ")");
                    continue;
                }

                // บันทึกไฟล์ชั่วคราว
                $tempPath = storage_path('app/temp_stm_' . uniqid() . '_' . $docNo . '.xls');
                file_put_contents($tempPath, $dlResponse->body());

                /* ======================================================
                 2) อ่านไฟล์ Excel และแปลงลง Staging Table
                ====================================================== */
                $spreadsheet = IOFactory::load($tempPath);

                // ดึง Round No จาก Sheet 2 Cell A16
                $sheetRound = $spreadsheet->setActiveSheetIndex(1);
                $extractedRoundNo = trim($sheetRound->getCell('A16')->getValue()) ?: ($item['round_no'] ?? $docNo);

                foreach ([2, 3] as $sheetIndex) {
                    if (!isset($spreadsheet->getAllSheets()[$sheetIndex])) {
                        continue;
                    }

                    $sheet = $spreadsheet->setActiveSheetIndex($sheetIndex);
                    $row_limit = $sheet->getHighestDataRow();
                    $startRow = 15;
                    $buffer = [];

                    for ($row = $startRow; $row <= $row_limit; $row++) {
                        if (empty($sheet->getCell('A' . $row)->getValue())) {
                            continue;
                        }

                        $adm = (string) $sheet->getCell('H' . $row)->getValue();
                        $datetimeadm = substr($adm, 6, 4) . '-' . substr($adm, 3, 2) . '-' . substr($adm, 0, 2) . ' ' . substr($adm, 11, 8);

                        $dch = (string) $sheet->getCell('I' . $row)->getValue();
                        $datetimedch = substr($dch, 6, 4) . '-' . substr($dch, 3, 2) . '-' . substr($dch, 0, 2) . ' ' . substr($dch, 11, 8);

                        $cols = ['S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL'];
                        $clean = [];
                        foreach ($cols as $c) {
                            $clean[$c] = str_replace(',', '', $sheet->getCell($c . $row)->getValue());
                        }

                        $buffer[] = [
                            'round_no' => $extractedRoundNo,
                            'repno' => $sheet->getCell('A' . $row)->getValue(),
                            'no' => $sheet->getCell('B' . $row)->getValue(),
                            'tran_id' => $sheet->getCell('C' . $row)->getValue(),
                            'hn' => $sheet->getCell('D' . $row)->getValue(),
                            'an' => $sheet->getCell('E' . $row)->getValue(),
                            'cid' => $sheet->getCell('F' . $row)->getValue(),
                            'pt_name' => $sheet->getCell('G' . $row)->getValue(),
                            'datetimeadm' => $datetimeadm,
                            'vstdate' => date('Y-m-d', strtotime($datetimeadm)),
                            'vsttime' => date('H:i:s', strtotime($datetimeadm)),
                            'datetimedch' => $datetimedch,
                            'dchdate' => date('Y-m-d', strtotime($datetimedch)),
                            'dchtime' => date('H:i:s', strtotime($datetimedch)),
                            'maininscl' => $sheet->getCell('J' . $row)->getValue(),
                            'projcode' => $sheet->getCell('K' . $row)->getValue(),
                            'charge' => $sheet->getCell('L' . $row)->getValue(),
                            'fund_ip_act' => $sheet->getCell('M' . $row)->getValue(),
                            'fund_ip_adjrw' => $sheet->getCell('N' . $row)->getValue(),
                            'fund_ip_ps' => $sheet->getCell('O' . $row)->getValue(),
                            'fund_ip_ps2' => $sheet->getCell('P' . $row)->getValue(),
                            'fund_ip_ccuf' => $sheet->getCell('Q' . $row)->getValue(),
                            'fund_ip_adjrw2' => $sheet->getCell('R' . $row)->getValue(),
                            'fund_ip_payrate' => $clean['S'],
                            'fund_ip_salary' => $clean['T'],
                            'fund_compensate_salary' => $clean['U'],
                            'receive_op' => $clean['V'],
                            'receive_ip_compensate_cal' => $clean['W'],
                            'receive_ip_compensate_pay' => $clean['X'],
                            'receive_hc_hc' => $clean['Y'],
                            'receive_hc_drug' => $clean['Z'],
                            'receive_ae_ae' => $clean['AA'],
                            'receive_ae_drug' => $clean['AB'],
                            'receive_inst' => $clean['AC'],
                            'receive_dmis_compensate_cal' => $clean['AD'],
                            'receive_dmis_compensate_pay' => $clean['AE'],
                            'receive_dmis_drug' => $clean['AF'],
                            'receive_palliative' => $clean['AG'],
                            'receive_dmishd' => $clean['AH'],
                            'receive_pp' => $clean['AI'],
                            'receive_fs' => $clean['AJ'],
                            'receive_opbkk' => $clean['AK'],
                            'receive_total' => $clean['AL'],
                            'va' => $sheet->getCell('AM' . $row)->getValue(),
                            'covid' => $sheet->getCell('AN' . $row)->getValue(),
                            'resources' => $sheet->getCell('AO' . $row)->getValue(),
                            'stm_filename' => $fileName,
                        ];

                        $totalImportedRows++;
                        if (count($buffer) === 500) {
                            Stm_ucsexcel::insert($buffer);
                            $buffer = [];
                        }
                    }

                    if ($buffer) {
                        Stm_ucsexcel::insert($buffer);
                    }
                }

                unset($spreadsheet);
                gc_collect_cycles();
                @unlink($tempPath);

                $importedRounds[] = $extractedRoundNo;

            } catch (\Exception $e) {
                Log::error("Error processing statement $docNo: " . $e->getMessage());
            }
        }

        /* ======================================================
         3) Merge จาก Staging ไปยัง stm_ucs
        ====================================================== */
        DB::transaction(function () {
            Stm_ucsexcel::whereNotNull('charge')->chunk(1000, function ($rows) {
                foreach ($rows as $value) {
                    Stm_ucs::updateOrInsert(
                        [
                            'repno' => $value->repno,
                            'no' => $value->no,
                        ],
                        [
                            'round_no' => $value->round_no,
                            'tran_id' => $value->tran_id,
                            'hn' => $value->hn,
                            'an' => $value->an,
                            'cid' => $value->cid,
                            'pt_name' => $value->pt_name,
                            'datetimeadm' => $value->datetimeadm,
                            'vstdate' => $value->vstdate,
                            'vsttime' => $value->vsttime,
                            'datetimedch' => $value->datetimedch,
                            'dchdate' => $value->dchdate,
                            'dchtime' => $value->dchtime,
                            'maininscl' => $value->maininscl,
                            'projcode' => $value->projcode,
                            'charge' => $value->charge,
                            'fund_ip_act' => $value->fund_ip_act,
                            'fund_ip_adjrw' => $value->fund_ip_adjrw,
                            'fund_ip_ps' => $value->fund_ip_ps,
                            'fund_ip_ps2' => $value->fund_ip_ps2,
                            'fund_ip_ccuf' => $value->fund_ip_ccuf,
                            'fund_ip_adjrw2' => $value->fund_ip_adjrw2,
                            'fund_ip_payrate' => $value->fund_ip_payrate,
                            'fund_ip_salary' => $value->fund_ip_salary,
                            'fund_compensate_salary' => $value->fund_compensate_salary,
                            'receive_op' => $value->receive_op,
                            'receive_ip_compensate_cal' => $value->receive_ip_compensate_cal,
                            'receive_ip_compensate_pay' => $value->receive_ip_compensate_pay,
                            'receive_hc_hc' => $value->receive_hc_hc,
                            'receive_hc_drug' => $value->receive_hc_drug,
                            'receive_ae_ae' => $value->receive_ae_ae,
                            'receive_ae_drug' => $value->receive_ae_drug,
                            'receive_inst' => $value->receive_inst,
                            'receive_dmis_compensate_cal' => $value->receive_dmis_compensate_cal,
                            'receive_dmis_compensate_pay' => $value->receive_dmis_compensate_pay,
                            'receive_dmis_drug' => $value->receive_dmis_drug,
                            'receive_palliative' => $value->receive_palliative,
                            'receive_dmishd' => $value->receive_dmishd,
                            'receive_pp' => $value->receive_pp,
                            'receive_fs' => $value->receive_fs,
                            'receive_opbkk' => $value->receive_opbkk,
                            'receive_total' => $value->receive_total,
                            'va' => $value->va,
                            'covid' => $value->covid,
                            'resources' => $value->resources,
                            'stm_filename' => $value->stm_filename,
                        ]
                    );
                }
            });
        });

        /* ======================================================
         4) เคลียร์ Staging
        ====================================================== */
        Stm_ucsexcel::truncate();

        $importedCount = count($importedRounds);
        if ($importedCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement ที่เลือกได้ กรุณาตรวจสอบ Session e-Claim'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement จาก e-Claim สำเร็จรวม {$importedCount} งวด (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_rounds' => $importedRounds,
            'reload' => true
        ]);
    }

    /**
     * 8. ค้นหารายการ REP จาก e-Claim (ValidationMainAction.do?maininscl=...)
     */
    public function searchRepStatements(Request $request)
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $sessionToken = $this->getActiveEclaimToken();

        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาระบุ Session Cookie หรือกดซิงก์จาก Extension ก่อนค้นหาข้อมูล'
            ], 401);
        }

        $targetType = $request->target_type ?: 'rep';
        $maininscl = strtolower(trim($request->maininscl ?: 'ucs'));
        $targetTable = $targetType === 'stm_lgo' ? 'stm_lgo' : ('rep_' . $maininscl);
        $budgetYear = (int)($request->budget_year ?: (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)));
        $month = (int)($request->month ?: date('m'));
        $repnoFilter = trim($request->rep_no ?: '');

        try {
            $url = "https://eclaim.nhso.go.th/webComponent/validation/ValidationMainAction.do?maininscl={$maininscl}&mo={$month}&ye={$budgetYear}";
            if ($repnoFilter) {
                $url .= "&repno=" . urlencode($repnoFilter);
            }

            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/main/MainWebAction.do';

            $response = Http::withHeaders($headers)->withoutVerifying()->timeout(30)->get($url);

            $body = $response->body();
            if (
                $response->status() !== 200 || 
                stripos($body, 'content2') === false ||
                stripos($body, 'Error Page') !== false || 
                stripos($body, 'frmErr') !== false || 
                stripos($body, 'คุณไม่มีสิทธิ์') !== false ||
                stripos($body, 'ประกาศใช้งานระบบ SSO') !== false
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim บนเซิร์ฟเวอร์หมดอายุหรือไม่ถูกต้อง กรุณากดปุ่ม "เปลี่ยน Token" แล้ววางค่า JSESSIONID ล่าสุด หรือกดซิงก์จาก Extension'
                ], 401);
            }

            $html = $body;
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);
            $rows = $xpath->query('//table[@id="content2"]//tbody//tr | //table[@id="content2"]//tr');

            $repItems = [];
            foreach ($rows as $tr) {
                $tds = $xpath->query('./td', $tr);
                if ($tds->length >= 10) {
                    $excelLink = '';
                    foreach ($xpath->query('.//a', $tr) as $a) {
                        $href = $a->getAttribute('href');
                        if (stripos($href, 'InvoiceReportExcelAction') !== false || stripos($href, 'excel') !== false) {
                            $excelLink = $href;
                            break;
                        }
                    }

                    $sendDate = trim($tds->item(0)->textContent);
                    $repNo = trim($tds->item(1)->textContent);
                    $hcode = trim($tds->item(2)->textContent);
                    $hname = trim($tds->item(3)->textContent);
                    $filename = trim($tds->item(4)->textContent);
                    $total = (int)str_replace(',', '', trim($tds->item(5)->textContent));
                    $pass = (int)str_replace(',', '', trim($tds->item(6)->textContent));
                    $fail = (int)str_replace(',', '', trim($tds->item(7)->textContent));
                    $importType = trim($tds->item(8)->textContent);
                    $checkDate = trim($tds->item(9)->textContent);
                    $importer = trim($tds->item(10)->textContent);

                    if ($repNo && $excelLink) {
                        $existingCount = 0;
                        try {
                            if ($targetType === 'stm_lgo') {
                                $existingCount = DB::table('stm_lgo')->where('repno', $repNo)->orWhere('round_no', $repNo)->count();
                            } else if (\Illuminate\Support\Facades\Schema::hasTable($targetTable)) {
                                $existingCount = DB::table($targetTable)->where('repno', $repNo)->count();
                            }
                        } catch (\Exception $e) {}

                        $repItems[] = [
                            'send_date' => $sendDate,
                            'rep_no' => $repNo,
                            'hcode' => $hcode,
                            'hname' => $hname,
                            'filename' => $filename,
                            'total' => $total,
                            'pass' => $pass,
                            'fail' => $fail,
                            'import_type' => $importType,
                            'check_date' => $checkDate,
                            'importer' => $importer,
                            'excel_url' => $excelLink,
                            'is_imported' => $existingCount > 0,
                            'imported_count' => $existingCount
                        ];
                    }
                }
            }

            if (empty($repItems)) {
                return response()->json([
                    'status' => 'success',
                    'budget_year' => $budgetYear,
                    'month' => $month,
                    'count' => 0,
                    'data' => [],
                    'message' => 'ไม่พบข้อมูลการตรวจสอบเบื้องต้น (REP) ในงวดเดือนที่เลือก'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'budget_year' => $budgetYear,
                'month' => $month,
                'count' => count($repItems),
                'data' => $repItems
            ]);

        } catch (\Exception $e) {
            Log::error("e-Claim searchRepStatements error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล REP จาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 9. สั่งดาวน์โหลดไฟล์ REP Excel จาก e-Claim และนำเข้าสู่ฐานข้อมูล RIMS อัตโนมัติ (รองรับทุกสิทธิ์ และ stm_lgo)
     */
    /**
     * ตรวจหาการจับคู่คอลัมน์ (Column Mapping) โดยอัตโนมัติจากหัวตาราง Excel ใน Row 5-8
     */
    protected function detectRepColMapping($sheet, array $defaultMapping = [])
    {
        $mapping = $defaultMapping;
        $headerRow = 0;
        
        for ($r = 5; $r <= 8; $r++) {
            $cellVal = (string)$sheet->getCell('D' . $r)->getValue();
            if (stripos($cellVal, 'HN') !== false) {
                $headerRow = $r;
                break;
            }
        }
        
        if ($headerRow > 0) {
            $detected = [];
            $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn($headerRow));
            for ($c = 1; $c <= min($highestCol, 120); $c++) {
                $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $h1 = trim((string)$sheet->getCell($colStr . $headerRow)->getValue());
                $h2 = trim((string)$sheet->getCell($colStr . ($headerRow + 1))->getValue());
                $headerText = preg_replace('/\s+/', ' ', mb_strtoupper($h1 . ' ' . $h2, 'UTF-8'));
                
                if ($headerText === '') continue;
                
                if (str_contains($headerText, 'REP') && !str_contains($headerText, 'TYPE')) {
                    $detected[$c] = 'repno';
                } elseif (str_contains($headerText, 'ลำดับ')) {
                    $detected[$c] = 'no';
                } elseif (str_contains($headerText, 'TRAN_ID') || str_contains($headerText, 'TRAN ID')) {
                    $detected[$c] = 'tran_id';
                } elseif ($h1 === 'HN') {
                    $detected[$c] = 'hn';
                } elseif ($h1 === 'AN') {
                    $detected[$c] = 'an';
                } elseif ($h1 === 'PID' || $h1 === 'CID') {
                    $detected[$c] = 'cid';
                } elseif (str_contains($headerText, 'ชื่อ') && str_contains($headerText, 'สกุล')) {
                    $detected[$c] = 'pt_name';
                } elseif (str_contains($headerText, 'ประเภทผู้ป่วย')) {
                    $detected[$c] = 'pt_type';
                } elseif (str_contains($headerText, 'ชดเชยสุทธิ') || str_contains($headerText, 'รวมเงินค่าบริการทั้งหมด')) {
                    $detected[$c] = 'net_compensate_nhso';
                } elseif (str_contains($headerText, 'ต้นสังกัด') || str_contains($headerText, 'PP (รับจาก สปสช.)') || str_contains($headerText, 'PP (รับจาก')) {
                    $detected[$c] = 'net_compensate_employer';
                } elseif (str_contains($headerText, 'ชดเชยจาก')) {
                    $detected[$c] = 'compensate_from';
                } elseif (str_contains($headerText, 'ERROR CODE') || str_contains($headerText, 'ERROR') || str_contains($headerText, 'รหัส C')) {
                    $detected[$c] = 'error_code';
                } elseif (str_contains($headerText, 'กองทุนหลัก') || str_contains($headerText, 'กองทุน')) {
                    $detected[$c] = 'main_fund';
                } elseif (str_contains($headerText, 'กองทุนย่อย')) {
                    $detected[$c] = 'sub_fund';
                } elseif (str_contains($headerText, 'ประเภทบริการ')) {
                    $detected[$c] = 'service_type';
                } elseif (str_contains($headerText, 'การรับส่งต่อ')) {
                    $detected[$c] = 'refer_type';
                } elseif (str_contains($headerText, 'การมีสิทธิ')) {
                    $detected[$c] = 'has_right';
                } elseif (str_contains($headerText, 'การใช้สิทธิ')) {
                    $detected[$c] = 'use_right';
                } elseif (str_contains($headerText, 'สิทธิหลัก')) {
                    $detected[$c] = 'maininscl';
                } elseif (str_contains($headerText, 'สิทธิรอง') || str_contains($headerText, 'สิทธิย่อย')) {
                    $detected[$c] = 'subinscl';
                } elseif ($h1 === 'HREF') {
                    $detected[$c] = 'href';
                } elseif ($h1 === 'HCODE') {
                    $detected[$c] = 'hcode';
                } elseif ($h1 === 'PROV1') {
                    $detected[$c] = 'prov1';
                } elseif (str_contains($headerText, 'รหัสหน่วยงาน') || $h1 === 'HMAIN') {
                    $detected[$c] = 'hmain';
                } elseif (str_contains($headerText, 'ชื่อหน่วยงาน') || $h1 === 'PROV2') {
                    $detected[$c] = 'prov2';
                } elseif ($h1 === 'PROJ') {
                    $detected[$c] = 'proj';
                } elseif ($h1 === 'PA') {
                    $detected[$c] = 'pa';
                } elseif ($h1 === 'DRG') {
                    $detected[$c] = 'drg';
                } elseif ($h1 === 'RW') {
                    $detected[$c] = 'rw';
                } elseif (str_contains($headerText, 'เรียกเก็บ') && !str_contains($headerText, 'CENTRAL') && !str_contains($headerText, 'PP')) {
                    $detected[$c] = 'charge_total';
                } elseif (str_contains($headerText, 'เบิกได้')) {
                    $detected[$c] = 'charge_vehicle_drug_device';
                } elseif (str_contains($headerText, 'เบิกไม่ได้')) {
                    $detected[$c] = 'charge_central_reimburse';
                } elseif (str_contains($headerText, 'ชำระเอง')) {
                    $detected[$c] = 'self_pay';
                } elseif (str_contains($headerText, 'อัตราจ่าย')) {
                    $detected[$c] = 'payrate_point';
                } elseif (str_contains($headerText, 'ล่าช้า') && str_contains($headerText, 'เปอร์เซ็นต์')) {
                    $detected[$c] = 'delay_percent';
                } elseif (str_contains($headerText, 'ล่าช้า')) {
                    $detected[$c] = 'delay_ps';
                } elseif ($h1 === 'CCUF') {
                    $detected[$c] = 'ccuf';
                } elseif ($h1 === 'ADJRW' || str_contains($headerText, 'ADJRW_NHSO')) {
                    $detected[$c] = 'adjrw_nhso';
                } elseif (str_contains($headerText, 'พรบ')) {
                    $detected[$c] = 'act_amount';
                } elseif ($h1 === 'ORS') {
                    $detected[$c] = 'pay_pattern';
                } elseif ($h1 === 'VA') {
                    $detected[$c] = 'va';
                } elseif (str_contains($headerText, 'AUDIT RESULTS')) {
                    $detected[$c] = 'audit_results';
                } elseif (str_contains($headerText, 'SEQ NO')) {
                    $detected[$c] = 'seq_no';
                } elseif (str_contains($headerText, 'INVOICE NO')) {
                    $detected[$c] = 'invoice_no';
                } elseif (str_contains($headerText, 'INVOICE LT')) {
                    $detected[$c] = 'invoice_lt';
                }
            }
            if (count($detected) >= 15) {
                return $detected;
            }
        }
        
        return $mapping;
    }

    public function importRepStatements(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $sessionToken = $this->getActiveEclaimToken();

        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session e-Claim หมดอายุ กรุณาซิงก์จาก Extension หรือเชื่อมต่อใหม่อีกครั้ง'
            ], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกอย่างน้อย 1 รายการ REP ที่ต้องการนำเข้า'
            ], 400);
        }

        $targetType = $request->target_type ?: 'rep';
        if ($targetType === 'stm_lgo') {
            return $this->processStmLgoImport($items, $sessionToken);
        }

        $maininscl = strtolower(trim($request->maininscl ?: 'ucs'));
        $stagingModelClass = 'App\\Models\\Rep_' . $maininscl . 'excel';
        $prodModelClass = 'App\\Models\\Rep_' . $maininscl;

        if (!class_exists($stagingModelClass) || !class_exists($prodModelClass)) {
            // Fallback to ucs if model doesn't exist
            $stagingModelClass = 'App\\Models\\Rep_ucsexcel';
            $prodModelClass = 'App\\Models\\Rep_ucs';
        }

        // Numeric fields list
        $numericFields = [
            'net_compensate_nhso', 'net_compensate_employer', 'rw', 
            'charge_non_vehicle_drug_device', 'charge_vehicle_drug_device', 'charge_total', 
            'charge_central_reimburse', 'self_pay', 'payrate_point', 
            'adjrw_nhso', 'adjrw2', 'compensate_amount', 'act_amount', 'salary_amount', 'compensate_after_salary',
            'hc_iphc', 'hc_ophc', 'ae_opae', 'ae_ipnb', 'ae_ipuc', 'ae_ip3sss', 'ae_ip7sss', 'ae_carae', 'ae_caref', 'ae_caref_puc',
            'inst_opinst', 'inst_ipinst', 'ip_ipaec', 'ip_ipaer', 'ip_ipinrgc', 'ip_ipinrgr', 'ip_ipinspsn', 'ip_ipprcc', 'ip_ipprcc_puc', 'ip_ipbkk_inst', 'ip_ip_ontop',
            'dmis_cataract', 'dmis_ssj_workload', 'dmis_hosp_workload', 'dmis_catinst', 'dmis_rc', 'dmis_rc_workload', 'dmis_rcuhosc', 'dmis_rcuhosc_workload', 'dmis_rcuhosr', 'dmis_rcuhosr_workload',
            'dmis_llop', 'dmis_llrgc', 'dmis_llrgr', 'dmis_lp', 'dmis_stroke_stemi_drug', 'dmis_dmidml', 'dmis_pp', 'dmis_dmishd', 'dmis_dmicnt', 'dmis_palliative_care', 'dmis_dm',
            'drug', 'opbkk_hc', 'opbkk_dent', 'opbkk_drug', 'opbkk_fs', 'opbkk_others', 'opbkk_hsub', 'opbkk_nhso',
            'base_rate_old', 'base_rate_add', 'base_rate_net', 'fs'
        ];

        // Default Column mapping list by right/scheme
        if ($maininscl === 'ofc') {
            $defaultColMapping = [
                1 => 'repno', 2 => 'no', 3 => 'tran_id', 4 => 'hn', 5 => 'an', 6 => 'cid', 7 => 'pt_name', 8 => 'pt_type',
                11 => 'net_compensate_nhso', 12 => 'net_compensate_employer', 13 => 'main_fund', 14 => 'error_code',
                15 => 'service_type', 16 => 'refer_type', 17 => 'has_right', 18 => 'use_right', 19 => 'maininscl', 20 => 'subinscl',
                21 => 'href', 22 => 'hcode', 23 => 'prov1', 24 => 'hmain', 25 => 'prov2', 26 => 'proj', 27 => 'pa', 28 => 'drg',
                29 => 'rw', 30 => 'charge_total', 31 => 'charge_non_vehicle_drug_device', 32 => 'charge_vehicle_drug_device',
                33 => 'charge_central_reimburse', 34 => 'self_pay', 35 => 'payrate_point', 36 => 'delay_ps', 37 => 'delay_percent',
                38 => 'ccuf', 39 => 'adjrw_nhso', 40 => 'act_amount', 41 => 'hc_iphc', 42 => 'ae_opae', 43 => 'ae_ipnb',
                44 => 'inst_opinst', 45 => 'compensate_amount', 46 => 'salary_amount', 47 => 'drug', 48 => 'deny_ip',
                49 => 'deny_hc', 50 => 'deny_ae', 51 => 'deny_inst', 52 => 'deny_dmis', 53 => 'pay_pattern', 54 => 'va',
                55 => 'audit_results', 56 => 'seq_no', 57 => 'invoice_no', 58 => 'invoice_lt'
            ];
        } elseif ($maininscl === 'lgo') {
            $defaultColMapping = [
                1 => 'repno', 2 => 'no', 3 => 'tran_id', 4 => 'hn', 5 => 'an', 6 => 'cid', 7 => 'pt_name', 8 => 'pt_type',
                11 => 'net_compensate_nhso', 12 => 'net_compensate_employer', 13 => 'main_fund', 14 => 'error_code',
                15 => 'service_type', 16 => 'refer_type', 17 => 'has_right', 18 => 'use_right', 19 => 'maininscl', 20 => 'subinscl',
                21 => 'href', 22 => 'hcode', 23 => 'prov1', 24 => 'hmain', 25 => 'prov2', 26 => 'proj', 27 => 'pa', 28 => 'drg',
                29 => 'rw', 30 => 'charge_total', 31 => 'charge_non_vehicle_drug_device', 32 => 'charge_vehicle_drug_device',
                33 => 'charge_central_reimburse', 34 => 'self_pay', 35 => 'payrate_point', 36 => 'delay_ps', 37 => 'delay_percent',
                38 => 'ccuf', 39 => 'adjrw_nhso', 40 => 'act_amount', 41 => 'hc_iphc', 42 => 'ae_opae', 43 => 'ae_ipnb',
                44 => 'inst_opinst', 45 => 'compensate_amount', 46 => 'salary_amount', 47 => 'drug', 48 => 'deny_ip',
                49 => 'deny_hc', 50 => 'deny_ae', 51 => 'deny_inst', 52 => 'deny_dmis', 53 => 'pay_pattern', 54 => 'va',
                55 => 'audit_results', 56 => 'seq_no', 57 => 'invoice_no', 58 => 'invoice_lt'
            ];
        } else {
            // UCS, SSS, and 120-column standard mapping
            $defaultColMapping = [
                1 => 'repno', 2 => 'no', 3 => 'tran_id', 4 => 'hn', 5 => 'an', 6 => 'cid', 7 => 'pt_name', 8 => 'pt_type',
                11 => 'net_compensate_nhso', 12 => 'net_compensate_employer', 13 => 'compensate_from', 14 => 'error_code',
                15 => 'main_fund', 16 => 'sub_fund', 17 => 'service_type', 18 => 'refer_type', 19 => 'has_right', 20 => 'use_right',
                21 => 'chk', 22 => 'maininscl', 23 => 'subinscl', 24 => 'href', 25 => 'hcode', 26 => 'hmain', 27 => 'prov1', 28 => 'rg1',
                29 => 'hmain2', 30 => 'prov2', 31 => 'rg2', 32 => 'dmis_hmain3', 33 => 'da', 34 => 'proj', 35 => 'pa', 36 => 'drg',
                37 => 'rw', 38 => 'ca_type', 39 => 'charge_non_vehicle_drug_device', 40 => 'charge_vehicle_drug_device', 41 => 'charge_total',
                42 => 'charge_central_reimburse', 43 => 'self_pay', 44 => 'payrate_point', 45 => 'delay_ps', 46 => 'delay_percent', 47 => 'ccuf',
                48 => 'adjrw_nhso', 49 => 'adjrw2', 50 => 'compensate_amount', 51 => 'act_amount', 52 => 'salary_percent', 53 => 'salary_amount',
                54 => 'compensate_after_salary', 55 => 'hc_iphc', 56 => 'hc_ophc', 57 => 'ae_opae', 58 => 'ae_ipnb', 59 => 'ae_ipuc',
                60 => 'ae_ip3sss', 61 => 'ae_ip7sss', 62 => 'ae_carae', 63 => 'ae_caref', 64 => 'ae_caref_puc', 65 => 'inst_opinst',
                66 => 'inst_ipinst', 67 => 'ip_ipaec', 68 => 'ip_ipaer', 69 => 'ip_ipinrgc', 70 => 'ip_ipinrgr', 71 => 'ip_ipinspsn',
                72 => 'ip_ipprcc', 73 => 'ip_ipprcc_puc', 74 => 'ip_ipbkk_inst', 75 => 'ip_ip_ontop', 76 => 'dmis_cataract',
                77 => 'dmis_ssj_workload', 78 => 'dmis_hosp_workload', 79 => 'dmis_catinst', 80 => 'dmis_rc', 81 => 'dmis_rc_workload',
                82 => 'dmis_rcuhosc', 83 => 'dmis_rcuhosc_workload', 84 => 'dmis_rcuhosr', 85 => 'dmis_rcuhosr_workload', 86 => 'dmis_llop',
                87 => 'dmis_llrgc', 88 => 'dmis_llrgr', 89 => 'dmis_lp', 90 => 'dmis_stroke_stemi_drug', 91 => 'dmis_dmidml', 92 => 'dmis_pp',
                93 => 'dmis_dmishd', 94 => 'dmis_dmicnt', 95 => 'dmis_palliative_care', 96 => 'dmis_dm', 97 => 'drug', 98 => 'opbkk_hc',
                99 => 'opbkk_dent', 100 => 'opbkk_drug', 101 => 'opbkk_fs', 102 => 'opbkk_others', 103 => 'opbkk_hsub', 104 => 'opbkk_nhso',
                105 => 'deny_hc', 106 => 'deny_ae', 107 => 'deny_inst', 108 => 'deny_ip', 109 => 'deny_dmis', 110 => 'base_rate_old',
                111 => 'base_rate_add', 112 => 'base_rate_net', 113 => 'fs', 114 => 'va', 115 => 'remark', 116 => 'audit_results',
                117 => 'pay_pattern', 118 => 'seq_no', 119 => 'invoice_no', 120 => 'invoice_lt'
            ];
        }

        $tempDir = storage_path('app/temp_rep');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $stagingModelClass::truncate();
        $importedReps = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $excelUrl = $item['excel_url'] ?? '';
            $repNo = $item['rep_no'] ?? '';
            $rawFilename = $item['filename'] ?? "REP_{$repNo}.xls";

            if (empty($excelUrl)) {
                continue;
            }

            if (strpos($excelUrl, 'http') === 0) {
                $downloadUrl = $excelUrl;
            } elseif (strpos($excelUrl, '/') === 0) {
                $downloadUrl = 'https://eclaim.nhso.go.th' . $excelUrl;
            } else {
                $downloadUrl = 'https://eclaim.nhso.go.th/webComponent/validation/' . $excelUrl;
            }

            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/validation/ValidationMainAction.do';

            $response = Http::withHeaders($headers)->withoutVerifying()->timeout(120)->get($downloadUrl);

            if ($response->failed() || strlen($response->body()) < 1000) {
                Log::warning("Failed to download REP Excel for {$repNo} from {$downloadUrl}");
                continue;
            }

            // Save temporary file
            $tempFile = $tempDir . '/REP_' . $repNo . '_' . time() . '.xls';
            file_put_contents($tempFile, $response->body());

            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($tempFile);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $file_name = str_ireplace('.ecd', '.xls', $rawFilename);
                if (stripos($file_name, '.xls') === false) {
                    $file_name .= '.xls';
                }

                $rep_type = (stripos($file_name, '_IP_') !== false || stripos($file_name, '_IPCS_') !== false) ? 'IP' : 'OP';
                $is_appeal = (stripos($file_name, '_APPEAL_') !== false) ? 1 : 0;
                $buffer = [];

                // Detect headers and start row dynamically
                $activeColMapping = $this->detectRepColMapping($sheet, $defaultColMapping);
                
                $startRow = 9;
                for ($r = 5; $r <= 8; $r++) {
                    $cellVal = (string)$sheet->getCell('D' . $r)->getValue();
                    if (stripos($cellVal, 'HN') !== false) {
                        $startRow = $r + 1;
                        $nextVal = (string)$sheet->getCell('D' . ($r + 1))->getValue();
                        if (empty($nextVal) || stripos($nextVal, 'HN') !== false || !preg_match('/^[0-9]+$/', trim($nextVal))) {
                            $startRow = $r + 2;
                        }
                        break;
                    }
                }

                for ($row = $startRow; $row <= $row_limit; $row++) {
                    $hn = $sheet->getCell('D' . $row)->getValue();
                    if (empty($hn) || stripos((string)$hn, 'HN') !== false) continue;

                    $rawAdm = (string) $sheet->getCell('I' . $row)->getValue();
                    $datetimeadm = null; $vstdate = null; $vsttime = null;
                    if (!empty($rawAdm) && $rawAdm !== '-') {
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', trim($rawAdm));
                            if ($d) {
                                $datetimeadm = $d->format('Y-m-d H:i:s');
                                $vstdate = $d->format('Y-m-d');
                                $vsttime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {}
                    }

                    $rawDch = (string) $sheet->getCell('J' . $row)->getValue();
                    $datetimedch = null; $dchdate = null; $dchtime = null;
                    if (!empty($rawDch) && $rawDch !== '-') {
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', trim($rawDch));
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d H:i:s');
                                $dchdate = $d->format('Y-m-d');
                                $dchtime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {}
                    }

                    $rowData = [
                        'rep_filename' => $file_name,
                        'rep_type' => $rep_type,
                        'is_appeal' => $is_appeal,
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $vstdate,
                        'vsttime' => $vsttime,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $dchdate,
                        'dchtime' => $dchtime,
                    ];

                    for ($c = 1; $c <= 120; $c++) {
                        if ($c === 9 || $c === 10 || !isset($activeColMapping[$c])) continue;
                        $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                        $val = $sheet->getCell($colChar . $row)->getValue();
                        $fieldName = $activeColMapping[$c];
                        if (in_array($fieldName, $numericFields)) {
                            $rowData[$fieldName] = ($val === '-' || $val === '' || $val === null) ? null : (double) str_replace(',', '', $val);
                        } else {
                            if ($val === '-' || $val === '' || $val === null) {
                                $rowData[$fieldName] = null;
                            } else {
                                $trimmedVal = trim((string)$val);
                                if ($fieldName === 'error_code') {
                                    $fundPattern = '/(OPCS|OTCS|INSTCS|IPCS|PACS|OPLG|IPLG|PALG|INSTLG|OTLG|OPUCS|IPUCS|OPSSS|IPSSS|OPBKK|IPBKK|OPBMT|IPBMT|OPSRT|IPSRT|OPPVT|IPPVT|OPKTMN|IPKTMN|INSTKTMN|OTKTMN|EXCEPT|FPNHSO|PP_)/i';
                                    if ($trimmedVal === '-' || $trimmedVal === '' || preg_match($fundPattern, $trimmedVal)) {
                                        $rowData['error_code'] = null;
                                    } else {
                                        $rowData['error_code'] = $trimmedVal;
                                    }
                                } else {
                                    $rowData[$fieldName] = $trimmedVal;
                                }
                            }
                        }
                    }

                    $buffer[] = $rowData;
                    $totalImportedRows++;

                    if (count($buffer) === 1000) {
                        $stagingModelClass::insert($buffer);
                        $buffer = [];
                    }
                }

                if (!empty($buffer)) {
                    $stagingModelClass::insert($buffer);
                }

                unset($spreadsheet);
                gc_collect_cycles();
                @unlink($tempFile);

                $importedReps[] = $repNo;

            } catch (\Exception $e) {
                Log::error("Error parsing REP Excel file for {$repNo}: " . $e->getMessage());
                @unlink($tempFile);
            }
        }

        // Merge Staging to Target Rep table
        DB::transaction(function () use ($stagingModelClass, $prodModelClass) {
            $stagingModelClass::chunk(1000, function ($rows) use ($prodModelClass) {
                foreach ($rows as $value) {
                    $valueArr = $value->toArray();
                    unset($valueArr['id']);
                    $prodModelClass::updateOrInsert(
                        [
                            'repno' => $value->repno,
                            'no' => $value->no,
                        ],
                        $valueArr
                    );
                }
            });
        });

        $stagingModelClass::truncate();

        $importedCount = count($importedReps);
        if ($importedCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า REP ที่เลือกได้ กรุณาตรวจสอบ Session e-Claim'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล REP จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_reps' => $importedReps,
            'reload' => true
        ]);
    }

    /**
     * นำเข้าไฟล์ LGO Excel จาก e-Claim เข้าสู่ตาราง stm_lgo และ stm_lgoexcel
     */
    protected function processStmLgoImport(array $items, string $sessionToken)
    {
        $tempDir = storage_path('app/temp_stm_lgo');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        Stm_lgoexcel::truncate();
        $importedReps = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $excelUrl = $item['excel_url'] ?? '';
            $repNo = $item['rep_no'] ?? '';
            $rawFilename = $item['filename'] ?? "STM_LGO_{$repNo}.xls";

            if (empty($excelUrl)) continue;

            if (strpos($excelUrl, 'http') === 0) {
                $downloadUrl = $excelUrl;
            } elseif (strpos($excelUrl, '/') === 0) {
                $downloadUrl = 'https://eclaim.nhso.go.th' . $excelUrl;
            } else {
                $downloadUrl = 'https://eclaim.nhso.go.th/webComponent/validation/' . $excelUrl;
            }

            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/validation/ValidationMainAction.do';

            $response = Http::withHeaders($headers)->withoutVerifying()->timeout(120)->get($downloadUrl);

            if ($response->failed() || strlen($response->body()) < 1000) {
                Log::warning("Failed to download STM LGO Excel for {$repNo}");
                continue;
            }

            $tempFile = $tempDir . '/STM_LGO_' . $repNo . '_' . time() . '.xls';
            file_put_contents($tempFile, $response->body());

            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($tempFile);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $file_name = str_ireplace('.ecd', '.xls', $rawFilename);
                if (stripos($file_name, '.xls') === false) {
                    $file_name .= '.xls';
                }

                $buffer = [];

                for ($row = 8; $row <= $row_limit; $row++) {
                    $repnoVal = trim((string)$sheet->getCell('A' . $row)->getValue());
                    $noVal = trim((string)$sheet->getCell('B' . $row)->getValue());
                    
                    // ข้ามแถวที่ไม่ใช่ข้อมูลผู้ป่วย (เช่น ส่วนสรุป/หมายเหตุท้ายไฟล์ Excel)
                    if (empty($repnoVal) || !is_numeric($repnoVal) || empty($noVal) || !is_numeric($noVal)) {
                        continue;
                    }

                    $adm = trim((string)$sheet->getCell('I' . $row)->getValue());
                    $datetimeadm = null; $vstdate = null; $vsttime = null;
                    if (!empty($adm) && $adm !== '-') {
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $adm);
                            if ($d) {
                                $datetimeadm = $d->format('Y-m-d H:i:s');
                                $vstdate = $d->format('Y-m-d');
                                $vsttime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {
                            $day = substr($adm, 0, 2);
                            $mo = substr($adm, 3, 2);
                            $year = substr($adm, 6, 4);
                            $tm = substr($adm, 11, 8);
                            if ($day && $mo && $year) {
                                $datetimeadm = $year . '-' . $mo . '-' . $day . ' ' . ($tm ?: '00:00:00');
                                $vstdate = $year . '-' . $mo . '-' . $day;
                                $vsttime = $tm ?: '00:00:00';
                            }
                        }
                    }

                    $dch = trim((string)$sheet->getCell('J' . $row)->getValue());
                    $datetimedch = null; $dchdate = null; $dchtime = null;
                    if (!empty($dch) && $dch !== '-') {
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dch);
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d H:i:s');
                                $dchdate = $d->format('Y-m-d');
                                $dchtime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {
                            $dchday = substr($dch, 0, 2);
                            $dchmo = substr($dch, 3, 2);
                            $dchyear = substr($dch, 6, 4);
                            $dchtime_raw = substr($dch, 11, 8);
                            if ($dchday && $dchmo && $dchyear) {
                                $datetimedch = $dchyear . '-' . $dchmo . '-' . $dchday . ' ' . ($dchtime_raw ?: '00:00:00');
                                $dchdate = $dchyear . '-' . $dchmo . '-' . $dchday;
                                $dchtime = $dchtime_raw ?: '00:00:00';
                            }
                        }
                    }

                    $cleanNum = function($cell) use ($sheet, $row) {
                        $v = $sheet->getCell($cell . $row)->getValue();
                        return ($v === '-' || $v === '' || $v === null) ? 0 : (double)str_replace(',', '', $v);
                    };

                    $cleanStr = function($cell) use ($sheet, $row) {
                        $v = $sheet->getCell($cell . $row)->getValue();
                        return ($v === '-' || $v === null) ? null : trim((string)$v);
                    };

                    $buffer[] = [
                        'round_no' => (string)$repnoVal,
                        'repno' => (string)$repnoVal,
                        'no' => $noVal,
                        'tran_id' => $cleanStr('C'),
                        'hn' => $cleanStr('D'),
                        'an' => $cleanStr('E'),
                        'cid' => $cleanStr('F'),
                        'pt_name' => $cleanStr('G'),
                        'dep' => $cleanStr('H'),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $vstdate,
                        'vsttime' => $vsttime,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $dchdate,
                        'dchtime' => $dchtime,
                        'compensate_treatment' => $cleanNum('K'),
                        'compensate_nhso' => $cleanNum('L'),
                        'error_code' => $cleanStr('M'),
                        'fund' => $cleanStr('N'),
                        'service_type' => $cleanStr('O'),
                        'refer' => $cleanStr('P'),
                        'have_rights' => $cleanStr('Q'),
                        'use_rights' => $cleanStr('R'),
                        'main_rights' => $cleanStr('S'),
                        'secondary_rights' => $cleanStr('T'),
                        'href' => $cleanStr('U'),
                        'hcode' => $cleanStr('V'),
                        'prov1' => $cleanStr('W'),
                        'hospcode' => $cleanStr('X'),
                        'hospname' => $cleanStr('Y'),
                        'proj' => $cleanStr('Z'),
                        'pa' => $cleanStr('AA'),
                        'drg' => $cleanStr('AB'),
                        'rw' => $cleanStr('AC'),
                        'charge_treatment' => $cleanNum('AD'),
                        'charge_pp' => $cleanNum('AE'),
                        'withdraw' => $cleanNum('AF'),
                        'non_withdraw' => $cleanNum('AG'),
                        'pay' => $cleanNum('AH'),
                        'payrate' => $cleanNum('AI'),
                        'delay' => $cleanStr('AJ'),
                        'delay_percent' => $cleanStr('AK'),
                        'ccuf' => $cleanStr('AL'),
                        'adjrw' => $cleanStr('AM'),
                        'act' => $cleanNum('AN'),
                        'case_iplg' => $cleanNum('AO'),
                        'case_oplg' => $cleanNum('AP'),
                        'case_palg' => $cleanNum('AQ'),
                        'case_inslg' => $cleanNum('AR'),
                        'case_otlg' => $cleanNum('AS'),
                        'case_pp' => $cleanNum('AT'),
                        'case_drug' => $cleanNum('AU'),
                        'deny_iplg' => $cleanStr('AV'),
                        'deny_oplg' => $cleanStr('AW'),
                        'deny_palg' => $cleanStr('AX'),
                        'deny_inslg' => $cleanStr('AY'),
                        'deny_otlg' => $cleanStr('AZ'),
                        'ors' => $cleanStr('BA'),
                        'va' => $cleanStr('BB'),
                        'audit_results' => $cleanStr('BC'),
                        'stm_filename' => $file_name,
                    ];

                    $totalImportedRows++;

                    if (count($buffer) === 1000) {
                        Stm_lgoexcel::insert($buffer);
                        $buffer = [];
                    }
                }

                if (!empty($buffer)) {
                    Stm_lgoexcel::insert($buffer);
                }

                unset($spreadsheet);
                gc_collect_cycles();
                @unlink($tempFile);

                $importedReps[] = $repNo;

            } catch (\Exception $e) {
                Log::error("Error parsing STM LGO Excel file for {$repNo}: " . $e->getMessage());
                @unlink($tempFile);
            }
        }

        // Merge Staging to Stm_lgo table
        DB::transaction(function () {
            $stm_lgoexcel = Stm_lgoexcel::whereNotNull('charge_treatment')->get();
            foreach ($stm_lgoexcel as $value) {
                $exists = Stm_lgo::where('repno', $value->repno)
                    ->where('no', $value->no)
                    ->exists();

                if ($exists) {
                    Stm_lgo::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no' => $value->repno,
                            'datetimeadm' => $value->datetimeadm,
                            'vstdate' => $value->vstdate,
                            'vsttime' => $value->vsttime,
                            'datetimedch' => $value->datetimedch,
                            'dchdate' => $value->dchdate,
                            'dchtime' => $value->dchtime,
                            'compensate_treatment' => $value->compensate_treatment,
                            'compensate_nhso' => $value->compensate_nhso,
                            'charge_treatment' => $value->charge_treatment,
                            'charge_pp' => $value->charge_pp,
                            'payrate' => $value->payrate,
                            'case_iplg' => $value->case_iplg,
                            'case_oplg' => $value->case_oplg,
                            'case_palg' => $value->case_palg,
                            'case_inslg' => $value->case_inslg,
                            'case_otlg' => $value->case_otlg,
                            'case_pp' => $value->case_pp,
                            'case_drug' => $value->case_drug,
                            'stm_filename' => $value->stm_filename,
                        ]);
                } else {
                    $arr = $value->toArray();
                    unset($arr['id']);
                    $arr['round_no'] = $value->repno;
                    Stm_lgo::create($arr);
                }
            }
        });

        Stm_lgoexcel::truncate();

        $importedCount = count($importedReps);
        if ($importedCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement LGO ที่เลือกได้'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement LGO จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_reps' => $importedReps,
            'reload' => true
        ]);
    }

    /**
     * 10. ค้นหารายการ Statement จากหน้า Finance Report (StatementReportWebActionList.do) สิทธิ์ OFC (ข้าราชการ)
     */
    public function searchFinanceStatements(Request $request)
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $sessionToken = $this->getActiveEclaimToken();

        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาระบุ Session Cookie หรือกดซิงก์จาก Extension ก่อนค้นหาข้อมูล'
            ], 401);
        }

        $maininscl = strtoupper(trim($request->maininscl ?: 'OFC'));
        $budgetYear = (int)($request->budget_year ?: (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)));
        $gyear = (string)($budgetYear > 2400 ? $budgetYear - 543 : $budgetYear);
        $month = $request->month ? (int)$request->month : null;
        $gmonth = $month ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
        $personType = trim($request->person_type ?: '');

        try {
            $url = "https://eclaim.nhso.go.th/webComponent/nch/StatementReportWebActionList.do";

            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/nch/StatementReportWebAction.do';
            $headers['X-Requested-With'] = 'XMLHttpRequest';

            $response = Http::asForm()->withHeaders($headers)->withoutVerifying()->timeout(30)->post($url, [
                'zone' => '10',
                'province_id' => '3700',
                'hcode' => $hospcode,
                'maininscl' => $maininscl,
                'gyear' => $gyear,
                'gmonth' => $gmonth,
                'ddlPerson_type' => $personType
            ]);

            $body = $response->body();
            if (
                $response->status() === 302 || 
                $response->status() === 401 || 
                strpos($body, 'เข้าสู่ระบบ') !== false || 
                strpos($body, 'Login') !== false || 
                strpos($body, 'คุณไม่มีสิทธิ์') !== false || 
                strpos($body, 'frmErr') !== false
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim บนเซิร์ฟเวอร์หมดอายุหรือไม่ถูกต้อง กรุณากดปุ่ม "เปลี่ยน Token" แล้ววางค่า JSESSIONID ล่าสุด หรือกดปุ่ม "ซิงก์ Session" จาก Extension'
                ], 401);
            }

            $html = $response->body();
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);
            $rows = $xpath->query('//table//tbody//tr | //tr');

            $items = [];
            foreach ($rows as $tr) {
                $tds = $xpath->query('./td', $tr);
                if ($tds->length >= 7) {
                    $statementNo = trim($tds->item(0)->textContent);
                    $monthName = trim($tds->item(1)->textContent);
                    $yearTh = trim($tds->item(2)->textContent);
                    $round = trim($tds->item(3)->textContent);
                    $hcodeVal = trim($tds->item(4)->textContent);
                    $benefit = trim($tds->item(5)->textContent);

                    // Extract JS call in download statement cell (td 6)
                    $dlTd = $tds->item(6);
                    $onclick = '';
                    foreach ($xpath->query('.//a', $dlTd) as $a) {
                        $onclick = $a->getAttribute('onclick');
                    }

                    // Pattern: getReportNCHReportRepOFC('10989', 'OFC', '1', '2026', '08', '01', '10989_OP202608_01')
                    if (preg_match("/getReportNCHReportRepOFC\s*\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $onclick, $m)) {
                        $p_hcode = $m[1];
                        $p_maininscl = $m[2];
                        $p_person_type = $m[3];
                        $p_gyear = $m[4];
                        $p_gmonth = $m[5];
                        $p_revision = $m[6];
                        $p_documentno = $m[7];

                        $personTypeLabel = $p_person_type == '1' ? 'ผู้ป่วยนอก (OPD)' : ($p_person_type == '2' ? 'ผู้ป่วยใน (IPD)' : 'ทั้งหมด');

                        $existingCount = 0;
                        try {
                            $existingCount = DB::table('stm_ofc')
                                ->where('round_no', $p_documentno)
                                ->orWhere('stm_filename', 'like', "%{$p_documentno}%")
                                ->count();
                        } catch (\Exception $e) {}

                        $items[] = [
                            'statement_no' => $p_documentno ?: $statementNo,
                            'month_name' => $monthName,
                            'year_th' => $yearTh,
                            'round' => $round,
                            'hcode' => $p_hcode,
                            'maininscl' => $p_maininscl,
                            'person_type' => $p_person_type,
                            'person_type_label' => $personTypeLabel,
                            'gyear' => $p_gyear,
                            'gmonth' => $p_gmonth,
                            'revision' => $p_revision,
                            'documentno' => $p_documentno,
                            'is_imported' => $existingCount > 0,
                            'imported_count' => $existingCount,
                        ];
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'budget_year' => $budgetYear,
                'count' => count($items),
                'data' => $items,
                'message' => count($items) > 0 ? '' : 'ไม่พบรายการ Statement ในปี/เดือนที่เลือก'
            ]);

        } catch (\Exception $e) {
            Log::error("e-Claim searchFinanceStatements error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล Statement จาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 11. ดาวน์โหลดและนำเข้า Statement OFC (ข้าราชการ) จาก e-Claim เข้าสู่ตาราง stm_ofc
     */
    public function importFinanceStatements(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session e-Claim หมดอายุ กรุณาซิงก์จาก Extension หรือเชื่อมต่อใหม่อีกครั้ง'
            ], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json([
                'status' => 'error',
                'message' => 'กรุณาเลือกอย่างน้อย 1 รายการ Statement ที่ต้องการนำเข้า'
            ], 400);
        }

        $tempDir = storage_path('app/temp_stm_ofc');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        Stm_ofcexcel::truncate();
        $importedStatements = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $postData = [
                'hcode' => $item['hcode'] ?? '10989',
                'maininscl' => $item['maininscl'] ?? 'OFC',
                'person_type' => $item['person_type'] ?? '1',
                'gyear' => $item['gyear'] ?? '2026',
                'gmonth' => $item['gmonth'] ?? '08',
                'revision' => $item['revision'] ?? '01',
                'documentno' => $item['documentno'] ?? ''
            ];

            $docNo = $postData['documentno'];
            if (empty($docNo)) continue;

            $downloadUrl = "https://eclaim.nhso.go.th/webComponent/nch/RepStatementOFCReportExcelWebAction.do";

            $headers = $this->getEclaimBrowserHeaders($sessionToken);
            $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/nch/StatementReportWebActionList.do';

            $response = Http::asForm()->withHeaders($headers)->withoutVerifying()->timeout(120)->post($downloadUrl, $postData);

            if ($response->failed() || strlen($response->body()) < 1000) {
                Log::warning("Failed to download Statement OFC Excel for {$docNo}");
                continue;
            }

            $tempFile = $tempDir . '/STM_' . $docNo . '_' . time() . '.xls';
            file_put_contents($tempFile, $response->body());

            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($tempFile);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no = trim(mb_substr((string) $roundText, 13, null, 'UTF-8')) ?: $docNo;
                $file_name = "STM_{$docNo}.xls";

                $buffer = [];

                for ($row = 12; $row <= $row_limit; $row++) {
                    $repnoVal = trim((string)$sheet->getCell('A' . $row)->getValue());
                    $noVal = trim((string)$sheet->getCell('B' . $row)->getValue());
                    $hnVal = trim((string)$sheet->getCell('C' . $row)->getValue());

                    if (empty($repnoVal) || !is_numeric($repnoVal) || empty($noVal) || !is_numeric($noVal) || empty($hnVal)) {
                        continue;
                    }

                    $adm = trim((string)$sheet->getCell('G' . $row)->getValue());
                    $datetimeadm = null; $vstdate = null; $vsttime = null;
                    if (!empty($adm) && $adm !== '-') {
                        $cleanAdm = preg_replace('/\s*\/\s*/', '/', $adm);
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $cleanAdm);
                            if ($d) {
                                $datetimeadm = $d->format('Y-m-d H:i:s');
                                $vstdate = $d->format('Y-m-d');
                                $vsttime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {
                            $parts = explode(' ', $cleanAdm);
                            $dp = explode('/', $parts[0] ?? '');
                            if (count($dp) === 3) {
                                $datetimeadm = "{$dp[2]}-{$dp[1]}-{$dp[0]} " . ($parts[1] ?? '00:00:00');
                                $vstdate = "{$dp[2]}-{$dp[1]}-{$dp[0]}";
                                $vsttime = $parts[1] ?? '00:00:00';
                            }
                        }
                    }

                    $dch = trim((string)$sheet->getCell('H' . $row)->getValue());
                    $datetimedch = null; $dchdate = null; $dchtime = null;
                    if (!empty($dch) && $dch !== '-') {
                        $cleanDch = preg_replace('/\s*\/\s*/', '/', $dch);
                        try {
                            $d = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $cleanDch);
                            if ($d) {
                                $datetimedch = $d->format('Y-m-d H:i:s');
                                $dchdate = $d->format('Y-m-d');
                                $dchtime = $d->format('H:i:s');
                            }
                        } catch (\Exception $e) {
                            $parts = explode(' ', $cleanDch);
                            $dp = explode('/', $parts[0] ?? '');
                            if (count($dp) === 3) {
                                $datetimedch = "{$dp[2]}-{$dp[1]}-{$dp[0]} " . ($parts[1] ?? '00:00:00');
                                $dchdate = "{$dp[2]}-{$dp[1]}-{$dp[0]}";
                                $dchtime = $parts[1] ?? '00:00:00';
                            }
                        }
                    }

                    $cleanNum = function($cell) use ($sheet, $row) {
                        $v = $sheet->getCell($cell . $row)->getValue();
                        return ($v === '-' || $v === '' || $v === null) ? 0 : (double)str_replace(',', '', $v);
                    };

                    $cleanStr = function($cell) use ($sheet, $row) {
                        $v = $sheet->getCell($cell . $row)->getValue();
                        return ($v === '-' || $v === null) ? null : trim((string)$v);
                    };

                    $buffer[] = [
                        'round_no' => $round_no,
                        'repno' => $repnoVal,
                        'no' => $noVal,
                        'hn' => $hnVal,
                        'an' => $cleanStr('D'),
                        'cid' => $cleanStr('E'),
                        'pt_name' => $cleanStr('F'),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $vstdate,
                        'vsttime' => $vsttime,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $dchdate,
                        'dchtime' => $dchtime,
                        'projcode' => $cleanStr('I'),
                        'adjrw' => $cleanStr('J'),
                        'charge' => $cleanNum('K'),
                        'act' => $cleanNum('L'),
                        'receive_room' => $cleanNum('M'),
                        'receive_instument' => $cleanNum('N'),
                        'receive_drug' => $cleanNum('O'),
                        'receive_treatment' => $cleanNum('P'),
                        'receive_car' => $cleanNum('Q'),
                        'receive_waitdch' => $cleanNum('R'),
                        'receive_other' => $cleanNum('S'),
                        'receive_total' => $cleanNum('T'),
                        'stm_filename' => $file_name,
                    ];

                    $totalImportedRows++;

                    if (count($buffer) === 1000) {
                        Stm_ofcexcel::insert($buffer);
                        $buffer = [];
                    }
                }

                if (!empty($buffer)) {
                    Stm_ofcexcel::insert($buffer);
                }

                unset($spreadsheet);
                gc_collect_cycles();
                @unlink($tempFile);

                $importedStatements[] = $docNo;

            } catch (\Exception $e) {
                Log::error("Error parsing STM OFC Excel file for {$docNo}: " . $e->getMessage());
                @unlink($tempFile);
            }
        }

        // Merge Staging to Stm_ofc table
        DB::transaction(function () {
            $stm_ofcexcel = Stm_ofcexcel::whereNotNull('charge')
                ->where('charge', '<>', 'เรียกเก็บ')
                ->get();

            foreach ($stm_ofcexcel as $value) {
                $exists = Stm_ofc::where('repno', $value->repno)
                    ->where('no', $value->no)
                    ->exists();

                if ($exists) {
                    Stm_ofc::where('repno', $value->repno)
                        ->where('no', $value->no)
                        ->update([
                            'round_no' => $value->round_no,
                            'datetimeadm' => $value->datetimeadm,
                            'vstdate' => $value->vstdate,
                            'vsttime' => $value->vsttime,
                            'datetimedch' => $value->datetimedch,
                            'dchdate' => $value->dchdate,
                            'dchtime' => $value->dchtime,
                            'charge' => $value->charge,
                            'receive_room' => $value->receive_room,
                            'receive_instument' => $value->receive_instument,
                            'receive_drug' => $value->receive_drug,
                            'receive_treatment' => $value->receive_treatment,
                            'receive_car' => $value->receive_car,
                            'receive_waitdch' => $value->receive_waitdch,
                            'receive_other' => $value->receive_other,
                            'receive_total' => $value->receive_total,
                            'stm_filename' => $value->stm_filename,
                        ]);
                } else {
                    $arr = $value->toArray();
                    unset($arr['id']);
                    Stm_ofc::create($arr);
                }
            }
        });

        Stm_ofcexcel::truncate();

        $importedCount = count($importedStatements);
        if ($importedCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement OFC ที่เลือกได้'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement OFC (ข้าราชการ) จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_statements' => $importedStatements,
            'reload' => true
        ]);
    }

    /**
     * 12. ค้นหารายการ Statement BKK (กทม.) จาก e-Claim (ktmn/KtmnViewAction.do)
     */
    public function stmBkkSearch(Request $request)
    {
        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim กรุณาเชื่อมต่อก่อนค้นหา'
            ], 401);
        }

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $budgetYear = $request->budget_year ?: (date('Y') + 543);
        $adYear = (int)$budgetYear - 543;
        $month = $request->month ? str_pad($request->month, 2, '0', STR_PAD_LEFT) : '';
        $personType = $request->person_type ?: '1'; // 1: OPD, 2: IPD

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/ktmn/mstatement.do';

        $searchUrl = "https://eclaim.nhso.go.th/webComponent/ktmn/KtmnViewAction.do";
        $postData = [
            'chkhcode' => 'N',
            'maininscl' => 'BKK',
            'ddlZone' => '10',
            'ddlProvince' => '3700',
            'ddlLHospital' => $hospcode,
            'ddlStatus' => '',
            'ddlYear' => (string)$adYear,
            'ddlMonth' => $month,
            'ddlPerson_type' => $personType,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post($searchUrl, $postData);

            if ($response->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'e-Claim ตอบกลับสถานะ ' . $response->status()
                ], 500);
            }

            $html = $response->body();
            if (strpos($html, 'Error Page') !== false || strpos($html, 'คุณไม่มีสิทธิ์') !== false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim หมดอายุหรือไม่ได้รับอนุญาต กรุณาซิงก์ Session ใหม่อีกครั้ง'
                ], 401);
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $rows = $xpath->query('//table[@id="tb-sso-data"]/tbody/tr');
            $results = [];

            foreach ($rows as $tr) {
                $tds = $xpath->query('.//td', $tr);
                if ($tds->length < 8) continue;

                $statementNo = trim($tds->item(0)->textContent);
                $monthName = trim($tds->item(1)->textContent);
                $yearStr = trim($tds->item(2)->textContent);
                $round = trim($tds->item(3)->textContent);
                $hosp = trim($tds->item(4)->textContent);
                $benefit = trim($tds->item(5)->textContent);
                $sendDate = $tds->item(10) !== null ? trim($tds->item(10)->textContent) : '';

                // Extract download params from onclick="javascript:getReportNCHReportRep('10989', 'BKK', '1', '2026', '01', '11', '10989_OP202601_11')"
                $downloadAnchor = $xpath->query('.//a[contains(@onclick, "getReportNCHReportRep")]', $tds->item(6));
                $params = [];
                if ($downloadAnchor->length > 0) {
                    $onclick = $downloadAnchor->item(0)->getAttribute('onclick');
                    if (preg_match("/getReportNCHReportRep\s*\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $onclick, $m)) {
                        $params = [
                            'hcode' => $m[1],
                            'maininscl' => $m[2],
                            'person_type' => $m[3],
                            'gyear' => $m[4],
                            'gmonth' => $m[5],
                            'revision' => $m[6],
                            'documentno' => $m[7],
                        ];
                    }
                }

                // ตรวจสอบว่าใน stm_bkk นำเข้าหรือยัง
                $importedCount = DB::table('stm_bkk')->where('stm_filename', 'like', "%{$statementNo}%")->count();

                $results[] = [
                    'statement_no' => $statementNo,
                    'month' => $monthName,
                    'year' => $yearStr,
                    'round' => $round,
                    'hospcode' => $hosp,
                    'benefit' => $benefit,
                    'send_date' => $sendDate,
                    'person_type' => $personType == '2' ? 'IPD' : 'OPD',
                    'download_params' => $params,
                    'is_imported' => $importedCount > 0,
                    'imported_count' => $importedCount,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 13. ดาวน์โหลดและนำเข้า Statement BKK (กทม.) จาก e-Claim
     */
    public function stmBkkImport(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim'], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่มีรายการ Statement ที่เลือก'], 400);
        }

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/ktmn/mstatement.do';

        $importedStatements = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $params = $item['download_params'] ?? [];
            if (empty($params['documentno'])) continue;

            $downloadUrl = "https://eclaim.nhso.go.th/webComponent/ktmn/StatementReportExcelWebAction.do";
            $res = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->asForm()
                ->post($downloadUrl, $params);

            if ($res->status() !== 200 || strlen($res->body()) < 500) {
                continue;
            }

            $fileName = "STM_" . $params['hcode'] . "_" . $params['documentno'] . ".xls";
            $tempDir = storage_path('app/temp_stm_bkk');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFilePath = $tempDir . '/' . $fileName;
            file_put_contents($tempFilePath, $res->body());

            try {
                $spreadsheet = IOFactory::load($tempFilePath);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no = trim(mb_substr((string) $roundText, 13, null, 'UTF-8'));
                if (empty($round_no)) {
                    $round_no = $item['statement_no'] ?? $params['documentno'];
                }

                $data = [];
                for ($row = 12; $row <= $row_limit; $row++) {
                    $repno = $sheet->getCell('A' . $row)->getValue();
                    if (empty($repno) || $repno == 'รวม' || $repno == 'TOTAL') continue;

                    $adm = $sheet->getCell('G' . $row)->getValue();
                    $day = substr($adm, 0, 2);
                    $mo = substr($adm, 3, 2);
                    $year = substr($adm, 7, 4);
                    $tm = substr($adm, 12, 8);
                    $datetimeadm = ($year && $mo && $day) ? ($year . '-' . $mo . '-' . $day . ' ' . $tm) : null;

                    $dch = $sheet->getCell('H' . $row)->getValue();
                    $dchday = substr($dch, 0, 2);
                    $dchmo = substr($dch, 3, 2);
                    $dchyear = substr($dch, 7, 4);
                    $dchtime = substr($dch, 12, 8);
                    $datetimedch = ($dchyear && $dchmo && $dchday) ? ($dchyear . '-' . $dchmo . '-' . $dchday . ' ' . $dchtime) : null;

                    $data[] = [
                        'round_no' => $round_no,
                        'repno' => $repno,
                        'no' => $sheet->getCell('B' . $row)->getValue(),
                        'hn' => $sheet->getCell('C' . $row)->getValue(),
                        'an' => $sheet->getCell('D' . $row)->getValue(),
                        'cid' => $sheet->getCell('E' . $row)->getValue(),
                        'pt_name' => $sheet->getCell('F' . $row)->getValue(),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $datetimeadm ? date('Y-m-d', strtotime($datetimeadm)) : null,
                        'vsttime' => $datetimeadm ? date('H:i:s', strtotime($datetimeadm)) : null,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $datetimedch ? date('Y-m-d', strtotime($datetimedch)) : null,
                        'dchtime' => $datetimedch ? date('H:i:s', strtotime($datetimedch)) : null,
                        'projcode' => $sheet->getCell('I' . $row)->getValue(),
                        'adjrw' => $sheet->getCell('J' . $row)->getValue() ?: 0,
                        'charge' => $sheet->getCell('K' . $row)->getValue() ?: 0,
                        'act' => $sheet->getCell('L' . $row)->getValue() ?: 0,
                        'receive_room' => $sheet->getCell('M' . $row)->getValue() ?: 0,
                        'receive_instument' => $sheet->getCell('N' . $row)->getValue() ?: 0,
                        'receive_drug' => $sheet->getCell('O' . $row)->getValue() ?: 0,
                        'receive_treatment' => $sheet->getCell('P' . $row)->getValue() ?: 0,
                        'receive_car' => $sheet->getCell('Q' . $row)->getValue() ?: 0,
                        'receive_waitdch' => $sheet->getCell('R' . $row)->getValue() ?: 0,
                        'receive_other' => $sheet->getCell('S' . $row)->getValue() ?: 0,
                        'receive_total' => $sheet->getCell('T' . $row)->getValue() ?: 0,
                        'stm_filename' => $fileName,
                    ];
                }

                if (!empty($data)) {
                    Stm_bkkexcel::truncate();
                    foreach (array_chunk($data, 1000) as $chunk) {
                        Stm_bkkexcel::insert($chunk);
                    }

                    $excelRows = Stm_bkkexcel::whereNotNull('charge')->where('charge', '<>', 'เรียกเก็บ')->get();
                    foreach ($excelRows as $val) {
                        $exists = Stm_bkk::where('repno', $val->repno)->where('no', $val->no)->exists();
                        $rowArr = $val->toArray();
                        unset($rowArr['id']);
                        if ($exists) {
                            Stm_bkk::where('repno', $val->repno)->where('no', $val->no)->update($rowArr);
                        } else {
                            Stm_bkk::create($rowArr);
                        }
                    }
                    Stm_bkkexcel::truncate();
                    $totalImportedRows += count($data);
                    $importedStatements[] = $fileName;
                }
            } catch (\Exception $e) {
                Log::error("Error parsing STM BKK file {$fileName}: " . $e->getMessage());
            }

            @unlink($tempFilePath);
        }

        $importedCount = count($importedStatements);
        if ($importedCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement BKK ที่เลือกได้'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement กทม. (BKK) จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_statements' => $importedStatements,
            'reload' => true
        ]);
    }

    /**
     * 14. ค้นหารายการ Statement BMT (ขสมก.) จาก e-Claim (bmt/BmtViewAction.do)
     */
    public function stmBmtSearch(Request $request)
    {
        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim กรุณาเชื่อมต่อก่อนค้นหา'
            ], 401);
        }

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $budgetYear = $request->budget_year ?: (date('Y') + 543);
        $adYear = (int)$budgetYear - 543;
        $month = $request->month ? str_pad($request->month, 2, '0', STR_PAD_LEFT) : '';
        $personType = $request->person_type ?: '1'; // 1: OPD, 2: IPD

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/bmt/mstatement.do';

        $searchUrl = "https://eclaim.nhso.go.th/webComponent/bmt/BmtViewAction.do";
        $postData = [
            'chkhcode' => 'N',
            'maininscl' => 'BMT',
            'ddlZone' => '10',
            'ddlProvince' => '3700',
            'ddlLHospital' => $hospcode,
            'ddlStatus' => '',
            'ddlYear' => (string)$adYear,
            'ddlMonth' => $month,
            'ddlPerson_type' => $personType,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post($searchUrl, $postData);

            if ($response->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'e-Claim ตอบกลับสถานะ ' . $response->status()
                ], 500);
            }

            $html = $response->body();
            if (strpos($html, 'Error Page') !== false || strpos($html, 'คุณไม่มีสิทธิ์') !== false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim หมดอายุหรือไม่ได้รับอนุญาต กรุณาซิงก์ Session ใหม่อีกครั้ง'
                ], 401);
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $rows = $xpath->query('//table[@id="tb-sso-data"]/tbody/tr');
            $results = [];

            foreach ($rows as $tr) {
                $tds = $xpath->query('.//td', $tr);
                if ($tds->length < 8) continue;

                $statementNo = trim($tds->item(0)->textContent);
                $monthName = trim($tds->item(1)->textContent);
                $yearStr = trim($tds->item(2)->textContent);
                $round = trim($tds->item(3)->textContent);
                $hosp = trim($tds->item(4)->textContent);
                $benefit = trim($tds->item(5)->textContent);
                $sendDate = $tds->item(10) !== null ? trim($tds->item(10)->textContent) : '';

                // Extract download params from onclick="javascript:getReportNCHReportRep('10989', 'BMT', '1', '2026', '01', '22', '10989_OP202601_22')"
                $downloadAnchor = $xpath->query('.//a[contains(@onclick, "getReportNCHReportRep")]', $tds->item(6));
                $params = [];
                if ($downloadAnchor->length > 0) {
                    $onclick = $downloadAnchor->item(0)->getAttribute('onclick');
                    if (preg_match("/getReportNCHReportRep\s*\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $onclick, $m)) {
                        $params = [
                            'hcode' => $m[1],
                            'maininscl' => $m[2],
                            'person_type' => $m[3],
                            'gyear' => $m[4],
                            'gmonth' => $m[5],
                            'revision' => $m[6],
                            'documentno' => $m[7],
                        ];
                    }
                }

                // ตรวจสอบว่าใน stm_bmt นำเข้าหรือยัง
                $importedCount = DB::table('stm_bmt')->where('stm_filename', 'like', "%{$statementNo}%")->count();

                $results[] = [
                    'statement_no' => $statementNo,
                    'month' => $monthName,
                    'year' => $yearStr,
                    'round' => $round,
                    'hospcode' => $hosp,
                    'benefit' => $benefit,
                    'send_date' => $sendDate,
                    'person_type' => $personType == '2' ? 'IPD' : 'OPD',
                    'download_params' => $params,
                    'is_imported' => $importedCount > 0,
                    'imported_count' => $importedCount,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 15. ดาวน์โหลดและนำเข้า Statement BMT (ขสมก.) จาก e-Claim
     */
    public function stmBmtImport(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim'], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่มีรายการ Statement ที่เลือก'], 400);
        }

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/bmt/mstatement.do';

        $importedStatements = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $params = $item['download_params'] ?? [];
            if (empty($params['documentno'])) continue;

            $downloadUrl = "https://eclaim.nhso.go.th/webComponent/bmt/StatementReportExcelWebAction.do";
            $res = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->asForm()
                ->post($downloadUrl, $params);

            if ($res->status() !== 200 || strlen($res->body()) < 500) {
                continue;
            }

            $fileName = "STM_" . $params['hcode'] . "_" . $params['documentno'] . ".xls";
            $tempDir = storage_path('app/temp_stm_bmt');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFilePath = $tempDir . '/' . $fileName;
            file_put_contents($tempFilePath, $res->body());

            try {
                $spreadsheet = IOFactory::load($tempFilePath);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no = trim(mb_substr((string) $roundText, 13, null, 'UTF-8'));
                if (empty($round_no)) {
                    $round_no = $item['statement_no'] ?? $params['documentno'];
                }

                $data = [];
                for ($row = 12; $row <= $row_limit; $row++) {
                    $repno = $sheet->getCell('A' . $row)->getValue();
                    if (empty($repno) || $repno == 'รวม' || $repno == 'TOTAL') continue;

                    $adm = $sheet->getCell('G' . $row)->getValue();
                    $day = substr($adm, 0, 2);
                    $mo = substr($adm, 3, 2);
                    $year = substr($adm, 7, 4);
                    $tm = substr($adm, 12, 8);
                    $datetimeadm = ($year && $mo && $day) ? ($year . '-' . $mo . '-' . $day . ' ' . $tm) : null;

                    $dch = $sheet->getCell('H' . $row)->getValue();
                    $dchday = substr($dch, 0, 2);
                    $dchmo = substr($dch, 3, 2);
                    $dchyear = substr($dch, 7, 4);
                    $dchtime = substr($dch, 12, 8);
                    $datetimedch = ($dchyear && $dchmo && $dchday) ? ($dchyear . '-' . $dchmo . '-' . $dchday . ' ' . $dchtime) : null;

                    $data[] = [
                        'round_no' => $round_no,
                        'repno' => $repno,
                        'no' => $sheet->getCell('B' . $row)->getValue(),
                        'hn' => $sheet->getCell('C' . $row)->getValue(),
                        'an' => $sheet->getCell('D' . $row)->getValue(),
                        'cid' => $sheet->getCell('E' . $row)->getValue(),
                        'pt_name' => $sheet->getCell('F' . $row)->getValue(),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $datetimeadm ? date('Y-m-d', strtotime($datetimeadm)) : null,
                        'vsttime' => $datetimeadm ? date('H:i:s', strtotime($datetimeadm)) : null,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $datetimedch ? date('Y-m-d', strtotime($datetimedch)) : null,
                        'dchtime' => $datetimedch ? date('H:i:s', strtotime($datetimedch)) : null,
                        'projcode' => $sheet->getCell('I' . $row)->getValue(),
                        'adjrw' => $sheet->getCell('J' . $row)->getValue() ?: 0,
                        'charge' => $sheet->getCell('K' . $row)->getValue() ?: 0,
                        'act' => $sheet->getCell('L' . $row)->getValue() ?: 0,
                        'receive_room' => $sheet->getCell('M' . $row)->getValue() ?: 0,
                        'receive_instument' => $sheet->getCell('N' . $row)->getValue() ?: 0,
                        'receive_drug' => $sheet->getCell('O' . $row)->getValue() ?: 0,
                        'receive_treatment' => $sheet->getCell('P' . $row)->getValue() ?: 0,
                        'receive_car' => $sheet->getCell('Q' . $row)->getValue() ?: 0,
                        'receive_waitdch' => $sheet->getCell('R' . $row)->getValue() ?: 0,
                        'receive_other' => $sheet->getCell('S' . $row)->getValue() ?: 0,
                        'receive_total' => $sheet->getCell('T' . $row)->getValue() ?: 0,
                        'stm_filename' => $fileName,
                    ];
                }

                if (!empty($data)) {
                    Stm_bmtexcel::truncate();
                    foreach (array_chunk($data, 1000) as $chunk) {
                        Stm_bmtexcel::insert($chunk);
                    }

                    $excelRows = Stm_bmtexcel::whereNotNull('charge')->where('charge', '<>', 'เรียกเก็บ')->get();
                    foreach ($excelRows as $val) {
                        $exists = Stm_bmt::where('repno', $val->repno)->where('no', $val->no)->exists();
                        $rowArr = $val->toArray();
                        unset($rowArr['id']);
                        if ($exists) {
                            Stm_bmt::where('repno', $val->repno)->where('no', $val->no)->update($rowArr);
                        } else {
                            Stm_bmt::create($rowArr);
                        }
                    }
                    Stm_bmtexcel::truncate();
                    $totalImportedRows += count($data);
                    $importedStatements[] = $fileName;
                }
            } catch (\Exception $e) {
                Log::error("Error parsing STM BMT file {$fileName}: " . $e->getMessage());
            }

            @unlink($tempFilePath);
        }

        $importedCount = count($importedStatements);
        if ($importedCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement BMT ที่เลือกได้'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement ขสมก. (BMT) จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_statements' => $importedStatements,
            'reload' => true
        ]);
    }

    /**
     * 16. ค้นหารายการ Statement SRT (รฟท.) จาก e-Claim (srt/SrtViewAction.do)
     */
    public function stmSrtSearch(Request $request)
    {
        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim กรุณาเชื่อมต่อก่อนค้นหา'
            ], 401);
        }

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $budgetYear = $request->budget_year ?: (date('Y') + 543);
        $adYear = (int)$budgetYear - 543;
        $month = $request->month ? str_pad($request->month, 2, '0', STR_PAD_LEFT) : '';
        $personType = $request->person_type ?: '1'; // 1: OPD, 2: IPD

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/srt/mstatement.do';

        $searchUrl = "https://eclaim.nhso.go.th/webComponent/srt/SrtViewAction.do";
        $postData = [
            'chkhcode' => 'N',
            'maininscl' => 'SRT',
            'ddlZone' => '10',
            'ddlProvince' => '3700',
            'ddlLHospital' => $hospcode,
            'ddlStatus' => '',
            'ddlYear' => (string)$adYear,
            'ddlMonth' => $month,
            'ddlPerson_type' => $personType,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post($searchUrl, $postData);

            if ($response->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'e-Claim ตอบกลับสถานะ ' . $response->status()
                ], 500);
            }

            $html = $response->body();
            if (strpos($html, 'Error Page') !== false || strpos($html, 'คุณไม่มีสิทธิ์') !== false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim หมดอายุหรือไม่ได้รับอนุญาต กรุณาซิงก์ Session ใหม่อีกครั้ง'
                ], 401);
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $rows = $xpath->query('//table[@id="tb-list"]/tbody/tr | //table[@id="tb-sso-data"]/tbody/tr');
            $results = [];

            foreach ($rows as $tr) {
                $tds = $xpath->query('.//td', $tr);
                if ($tds->length < 8) continue;

                $statementNo = trim($tds->item(0)->textContent);
                if (empty($statementNo) || strpos($statementNo, 'ไม่พบข้อมูล') !== false) continue;

                $monthName = trim($tds->item(1)->textContent);
                $yearStr = trim($tds->item(2)->textContent);
                $round = trim($tds->item(3)->textContent);
                $hosp = trim($tds->item(4)->textContent);
                $benefit = trim($tds->item(5)->textContent);
                $sendDate = $tds->item(10) !== null ? trim($tds->item(10)->textContent) : '';

                // Extract download params from onclick="javascript:getReportNCHReportRep('10989', 'SRT', '1', '2026', '01', '22', '10989_OP202601_22')"
                $downloadAnchor = $xpath->query('.//a[contains(@onclick, "getReportNCHReportRep")]', $tds->item(6));
                $params = [];
                if ($downloadAnchor->length > 0) {
                    $onclick = $downloadAnchor->item(0)->getAttribute('onclick');
                    if (preg_match("/getReportNCHReportRep\s*\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $onclick, $m)) {
                        $params = [
                            'hcode' => $m[1],
                            'maininscl' => $m[2],
                            'person_type' => $m[3],
                            'gyear' => $m[4],
                            'gmonth' => $m[5],
                            'revision' => $m[6],
                            'documentno' => $m[7],
                        ];
                    }
                }

                // ตรวจสอบว่าใน stm_srt นำเข้าหรือยัง
                $importedCount = DB::table('stm_srt')->where('stm_filename', 'like', "%{$statementNo}%")->count();

                $results[] = [
                    'statement_no' => $statementNo,
                    'month' => $monthName,
                    'year' => $yearStr,
                    'round' => $round,
                    'hospcode' => $hosp,
                    'benefit' => $benefit,
                    'send_date' => $sendDate,
                    'person_type' => $personType == '2' ? 'IPD' : 'OPD',
                    'download_params' => $params,
                    'is_imported' => $importedCount > 0,
                    'imported_count' => $importedCount,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 17. ดาวน์โหลดและนำเข้า Statement SRT (รฟท.) จาก e-Claim
     */
    public function stmSrtImport(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim'], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่มีรายการ Statement ที่เลือก'], 400);
        }

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/srt/mstatement.do';

        $importedStatements = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $params = $item['download_params'] ?? [];
            if (empty($params['documentno'])) continue;

            $downloadUrl = "https://eclaim.nhso.go.th/webComponent/srt/StatementReportExcelWebAction.do";
            $res = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->asForm()
                ->post($downloadUrl, $params);

            if ($res->status() !== 200 || strlen($res->body()) < 500) {
                continue;
            }

            $fileName = "STM_" . $params['hcode'] . "_" . $params['documentno'] . ".xls";
            $tempDir = storage_path('app/temp_stm_srt');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFilePath = $tempDir . '/' . $fileName;
            file_put_contents($tempFilePath, $res->body());

            try {
                $spreadsheet = IOFactory::load($tempFilePath);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no = trim(mb_substr((string) $roundText, 13, null, 'UTF-8'));
                if (empty($round_no)) {
                    $round_no = $item['statement_no'] ?? $params['documentno'];
                }

                $data = [];
                for ($row = 12; $row <= $row_limit; $row++) {
                    $repno = $sheet->getCell('A' . $row)->getValue();
                    if (empty($repno) || $repno == 'รวม' || $repno == 'TOTAL') continue;

                    $adm = $sheet->getCell('G' . $row)->getValue();
                    $day = substr($adm, 0, 2);
                    $mo = substr($adm, 3, 2);
                    $year = substr($adm, 7, 4);
                    $tm = substr($adm, 12, 8);
                    $datetimeadm = ($year && $mo && $day) ? ($year . '-' . $mo . '-' . $day . ' ' . $tm) : null;

                    $dch = $sheet->getCell('H' . $row)->getValue();
                    $dchday = substr($dch, 0, 2);
                    $dchmo = substr($dch, 3, 2);
                    $dchyear = substr($dch, 7, 4);
                    $dchtime = substr($dch, 12, 8);
                    $datetimedch = ($dchyear && $dchmo && $dchday) ? ($dchyear . '-' . $dchmo . '-' . $dchday . ' ' . $dchtime) : null;

                    $data[] = [
                        'round_no' => $round_no,
                        'repno' => $repno,
                        'no' => $sheet->getCell('B' . $row)->getValue(),
                        'hn' => $sheet->getCell('C' . $row)->getValue(),
                        'an' => $sheet->getCell('D' . $row)->getValue(),
                        'cid' => $sheet->getCell('E' . $row)->getValue(),
                        'pt_name' => $sheet->getCell('F' . $row)->getValue(),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $datetimeadm ? date('Y-m-d', strtotime($datetimeadm)) : null,
                        'vsttime' => $datetimeadm ? date('H:i:s', strtotime($datetimeadm)) : null,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $datetimedch ? date('Y-m-d', strtotime($datetimedch)) : null,
                        'dchtime' => $datetimedch ? date('H:i:s', strtotime($datetimedch)) : null,
                        'projcode' => $sheet->getCell('I' . $row)->getValue(),
                        'adjrw' => $sheet->getCell('J' . $row)->getValue() ?: 0,
                        'charge' => $sheet->getCell('K' . $row)->getValue() ?: 0,
                        'act' => $sheet->getCell('L' . $row)->getValue() ?: 0,
                        'receive_room' => $sheet->getCell('M' . $row)->getValue() ?: 0,
                        'receive_instument' => $sheet->getCell('N' . $row)->getValue() ?: 0,
                        'receive_drug' => $sheet->getCell('O' . $row)->getValue() ?: 0,
                        'receive_treatment' => $sheet->getCell('P' . $row)->getValue() ?: 0,
                        'receive_car' => $sheet->getCell('Q' . $row)->getValue() ?: 0,
                        'receive_waitdch' => $sheet->getCell('R' . $row)->getValue() ?: 0,
                        'receive_other' => $sheet->getCell('S' . $row)->getValue() ?: 0,
                        'receive_total' => $sheet->getCell('T' . $row)->getValue() ?: 0,
                        'stm_filename' => $fileName,
                    ];
                }

                if (!empty($data)) {
                    Stm_srtexcel::truncate();
                    foreach (array_chunk($data, 1000) as $chunk) {
                        Stm_srtexcel::insert($chunk);
                    }

                    $excelRows = Stm_srtexcel::whereNotNull('charge')->where('charge', '<>', 'เรียกเก็บ')->get();
                    foreach ($excelRows as $val) {
                        $exists = Stm_srt::where('repno', $val->repno)->where('no', $val->no)->exists();
                        $rowArr = $val->toArray();
                        unset($rowArr['id']);
                        if ($exists) {
                            Stm_srt::where('repno', $val->repno)->where('no', $val->no)->update($rowArr);
                        } else {
                            Stm_srt::create($rowArr);
                        }
                    }
                    Stm_srtexcel::truncate();
                    $totalImportedRows += count($data);
                    $importedStatements[] = $fileName;
                }
            } catch (\Exception $e) {
                Log::error("Error parsing STM SRT file {$fileName}: " . $e->getMessage());
            }

            @unlink($tempFilePath);
        }

        $importedCount = count($importedStatements);
        if ($importedCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement SRT ที่เลือกได้'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement การรถไฟฯ (SRT) จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_statements' => $importedStatements,
            'reload' => true
        ]);
    }

    /**
     * 18. ค้นหารายการ Statement PVT (ครูเอกชน) จาก e-Claim (pvt/PvtViewAction.do)
     */
    public function stmPvtSearch(Request $request)
    {
        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim กรุณาเชื่อมต่อก่อนค้นหา'
            ], 401);
        }

        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $budgetYear = $request->budget_year ?: (date('Y') + 543);
        $adYear = (int)$budgetYear - 543;
        $month = $request->month ? str_pad($request->month, 2, '0', STR_PAD_LEFT) : '';
        $personType = $request->person_type ?: '1'; // 1: OPD, 2: IPD

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['X-Requested-With'] = 'XMLHttpRequest';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/pvt/mstatement.do';

        $searchUrl = "https://eclaim.nhso.go.th/webComponent/pvt/PvtViewAction.do";
        $postData = [
            'chkhcode' => 'N',
            'maininscl' => 'PVT',
            'ddlZone' => '10',
            'ddlProvince' => '3700',
            'ddlLHospital' => $hospcode,
            'ddlStatus' => '',
            'ddlYear' => (string)$adYear,
            'ddlMonth' => $month,
            'ddlPerson_type' => $personType,
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post($searchUrl, $postData);

            if ($response->status() !== 200) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'e-Claim ตอบกลับสถานะ ' . $response->status()
                ], 500);
            }

            $html = $response->body();
            if (strpos($html, 'Error Page') !== false || strpos($html, 'คุณไม่มีสิทธิ์') !== false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session e-Claim หมดอายุหรือไม่ได้รับอนุญาต กรุณาซิงก์ Session ใหม่อีกครั้ง'
                ], 401);
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $rows = $xpath->query('//table[@id="tb-list"]/tbody/tr | //table[@id="tb-sso-data"]/tbody/tr');
            $results = [];

            foreach ($rows as $tr) {
                $tds = $xpath->query('.//td', $tr);
                if ($tds->length < 7) continue;

                $statementNo = trim($tds->item(0)->textContent);
                if (empty($statementNo) || strpos($statementNo, 'ไม่พบข้อมูล') !== false) continue;

                $monthName = trim($tds->item(1)->textContent);
                $yearStr = trim($tds->item(2)->textContent);
                $round = trim($tds->item(3)->textContent);
                $hosp = trim($tds->item(4)->textContent);
                $benefit = trim($tds->item(5)->textContent);
                $sendDate = '';

                // Extract download params from onclick="javascript:getReportNCHReportRep('10989', 'PVT', '1', '2026', '01', '22', '10989_OP202601_22')"
                $downloadAnchor = $xpath->query('.//a[contains(@onclick, "getReportNCHReportRep")]', $tds->item(6));
                $params = [];
                if ($downloadAnchor->length > 0) {
                    $onclick = $downloadAnchor->item(0)->getAttribute('onclick');
                    if (preg_match("/getReportNCHReportRep\s*\(\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*,\s*'([^']*)'\s*\)/", $onclick, $m)) {
                        $params = [
                            'hcode' => $m[1],
                            'maininscl' => $m[2],
                            'person_type' => $m[3],
                            'gyear' => $m[4],
                            'gmonth' => $m[5],
                            'revision' => $m[6],
                            'documentno' => $m[7],
                        ];
                    }
                }

                // ตรวจสอบว่าใน stm_pvt นำเข้าหรือยัง
                $importedCount = DB::table('stm_pvt')->where('stm_filename', 'like', "%{$statementNo}%")->count();

                $results[] = [
                    'statement_no' => $statementNo,
                    'month' => $monthName,
                    'year' => $yearStr,
                    'round' => $round,
                    'hospcode' => $hosp,
                    'benefit' => $benefit,
                    'send_date' => $sendDate,
                    'person_type' => $personType == '2' ? 'IPD' : 'OPD',
                    'download_params' => $params,
                    'is_imported' => $importedCount > 0,
                    'imported_count' => $importedCount,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลจาก e-Claim: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 19. ดาวน์โหลดและนำเข้า Statement PVT (ครูเอกชน) จาก e-Claim
     */
    public function stmPvtImport(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $sessionToken = $this->getActiveEclaimToken();
        if (!$sessionToken) {
            return response()->json(['status' => 'error', 'message' => 'ยังไม่ได้เชื่อมต่อกับระบบ e-Claim'], 401);
        }

        $items = $request->items;
        if (empty($items) || !is_array($items)) {
            return response()->json(['status' => 'error', 'message' => 'ไม่มีรายการ Statement ที่เลือก'], 400);
        }

        $headers = $this->getEclaimBrowserHeaders($sessionToken);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
        $headers['Referer'] = 'https://eclaim.nhso.go.th/webComponent/pvt/mstatement.do';

        $importedStatements = [];
        $totalImportedRows = 0;

        foreach ($items as $item) {
            $params = $item['download_params'] ?? [];
            if (empty($params['documentno'])) continue;

            $downloadUrl = "https://eclaim.nhso.go.th/webComponent/pvt/StatementReportExcelWebAction.do";
            $res = Http::withHeaders($headers)
                ->withoutVerifying()
                ->timeout(60)
                ->asForm()
                ->post($downloadUrl, $params);

            if ($res->status() !== 200 || strlen($res->body()) < 500) {
                continue;
            }

            $fileName = "STM_" . $params['hcode'] . "_" . $params['documentno'] . ".xls";
            $tempDir = storage_path('app/temp_stm_pvt');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $tempFilePath = $tempDir . '/' . $fileName;
            file_put_contents($tempFilePath, $res->body());

            try {
                $spreadsheet = IOFactory::load($tempFilePath);
                $sheet = $spreadsheet->setActiveSheetIndex(0);
                $row_limit = $sheet->getHighestDataRow();

                $roundText = $sheet->getCell('A6')->getCalculatedValue();
                $round_no = trim(mb_substr((string) $roundText, 13, null, 'UTF-8'));
                if (empty($round_no)) {
                    $round_no = $item['statement_no'] ?? $params['documentno'];
                }

                $data = [];
                for ($row = 12; $row <= $row_limit; $row++) {
                    $repno = $sheet->getCell('A' . $row)->getValue();
                    if (empty($repno) || $repno == 'รวม' || $repno == 'TOTAL') continue;

                    $adm = $sheet->getCell('G' . $row)->getValue();
                    $day = substr($adm, 0, 2);
                    $mo = substr($adm, 3, 2);
                    $year = substr($adm, 7, 4);
                    $tm = substr($adm, 12, 8);
                    $datetimeadm = ($year && $mo && $day) ? ($year . '-' . $mo . '-' . $day . ' ' . $tm) : null;

                    $dch = $sheet->getCell('H' . $row)->getValue();
                    $dchday = substr($dch, 0, 2);
                    $dchmo = substr($dch, 3, 2);
                    $dchyear = substr($dch, 7, 4);
                    $dchtime = substr($dch, 12, 8);
                    $datetimedch = ($dchyear && $dchmo && $dchday) ? ($dchyear . '-' . $dchmo . '-' . $dchday . ' ' . $dchtime) : null;

                    $data[] = [
                        'round_no' => $round_no,
                        'repno' => $repno,
                        'no' => $sheet->getCell('B' . $row)->getValue(),
                        'hn' => $sheet->getCell('C' . $row)->getValue(),
                        'an' => $sheet->getCell('D' . $row)->getValue(),
                        'cid' => $sheet->getCell('E' . $row)->getValue(),
                        'pt_name' => $sheet->getCell('F' . $row)->getValue(),
                        'datetimeadm' => $datetimeadm,
                        'vstdate' => $datetimeadm ? date('Y-m-d', strtotime($datetimeadm)) : null,
                        'vsttime' => $datetimeadm ? date('H:i:s', strtotime($datetimeadm)) : null,
                        'datetimedch' => $datetimedch,
                        'dchdate' => $datetimedch ? date('Y-m-d', strtotime($datetimedch)) : null,
                        'dchtime' => $datetimedch ? date('H:i:s', strtotime($datetimedch)) : null,
                        'projcode' => $sheet->getCell('I' . $row)->getValue(),
                        'adjrw' => $sheet->getCell('J' . $row)->getValue() ?: 0,
                        'charge' => $sheet->getCell('K' . $row)->getValue() ?: 0,
                        'act' => $sheet->getCell('L' . $row)->getValue() ?: 0,
                        'receive_room' => $sheet->getCell('M' . $row)->getValue() ?: 0,
                        'receive_instument' => $sheet->getCell('N' . $row)->getValue() ?: 0,
                        'receive_drug' => $sheet->getCell('O' . $row)->getValue() ?: 0,
                        'receive_treatment' => $sheet->getCell('P' . $row)->getValue() ?: 0,
                        'receive_car' => $sheet->getCell('Q' . $row)->getValue() ?: 0,
                        'receive_waitdch' => $sheet->getCell('R' . $row)->getValue() ?: 0,
                        'receive_other' => $sheet->getCell('S' . $row)->getValue() ?: 0,
                        'receive_total' => $sheet->getCell('T' . $row)->getValue() ?: 0,
                        'stm_filename' => $fileName,
                    ];
                }

                if (!empty($data)) {
                    Stm_pvtexcel::truncate();
                    foreach (array_chunk($data, 1000) as $chunk) {
                        Stm_pvtexcel::insert($chunk);
                    }

                    $excelRows = Stm_pvtexcel::whereNotNull('charge')->where('charge', '<>', 'เรียกเก็บ')->get();
                    foreach ($excelRows as $val) {
                        $exists = Stm_pvt::where('repno', $val->repno)->where('no', $val->no)->exists();
                        $rowArr = $val->toArray();
                        unset($rowArr['id']);
                        if ($exists) {
                            Stm_pvt::where('repno', $val->repno)->where('no', $val->no)->update($rowArr);
                        } else {
                            Stm_pvt::create($rowArr);
                        }
                    }
                    Stm_pvtexcel::truncate();
                    $totalImportedRows += count($data);
                    $importedStatements[] = $fileName;
                }
            } catch (\Exception $e) {
                Log::error("Error parsing STM PVT file {$fileName}: " . $e->getMessage());
            }

            @unlink($tempFilePath);
        }

        $importedCount = count($importedStatements);
        if ($importedCount === 0) {
            return response()->json(['status' => 'error', 'message' => 'ไม่สามารถดาวน์โหลดหรือนำเข้า Statement PVT ที่เลือกได้'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "นำเข้าข้อมูล Statement ครูเอกชน (PVT) จาก e-Claim สำเร็จรวม {$importedCount} ไฟล์ (บันทึกข้อมูล {$totalImportedRows} รายการ)",
            'imported_statements' => $importedStatements,
            'reload' => true
        ]);
    }

    /**
     * 20. ตรวจสอบการเชื่อมต่อและวินิจฉัยปัญหา (Debug Diagnostic)
     */
    public function debugCheck(Request $request)
    {
        $hospcode = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '10989';
        $dbToken = DB::table('main_setting')->where('name', 'eclaim_session_token')->value('value');
        $sessionToken = $this->getActiveEclaimToken();

        $outgoingIp = null;
        try {
            $outgoingIp = Http::withoutVerifying()->timeout(5)->get('https://api.ipify.org')->body();
        } catch (\Exception $e) {
            $outgoingIp = 'Error: ' . $e->getMessage();
        }

        $url = "https://eclaim.nhso.go.th/webComponent/validation/ValidationMainAction.do?maininscl=ucs&mo=8&ye=2569";
        $headers = $this->getEclaimBrowserHeaders($sessionToken);

        $responseStatus = null;
        $responseBody = null;
        $errorMsg = null;

        try {
            $res = Http::withHeaders($headers)->withoutVerifying()->timeout(15)->get($url);
            $responseStatus = $res->status();
            $responseBody = $res->body();
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return response()->json([
            'server_outgoing_ip' => $outgoingIp,
            'db_token_length' => strlen((string)$dbToken),
            'db_token_preview' => substr((string)$dbToken, 0, 150) . (strlen((string)$dbToken) > 150 ? '...' : ''),
            'cookie_header_sent' => substr((string)($headers['Cookie'] ?? ''), 0, 300) . '...',
            'eclaim_http_status' => $responseStatus,
            'has_content2_table' => strpos((string)$responseBody, 'content2') !== false,
            'has_frm_err' => strpos((string)$responseBody, 'frmErr') !== false || strpos((string)$responseBody, 'คุณไม่มีสิทธิ์') !== false,
            'response_length' => strlen((string)$responseBody),
            'error_text_extracted' => trim(preg_replace('/\s+/', ' ', strip_tags((string)$responseBody))),
            'raw_html_snippet' => substr((string)$responseBody, 0, 1500),
            'curl_error' => $errorMsg,
        ]);
    }
}

