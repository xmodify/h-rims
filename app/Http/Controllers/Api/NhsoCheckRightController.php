<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\LicenseVerificationService;

class NhsoCheckRightController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_check_right !== 'Y') {
                    return response()->json(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ใช้งานโมดูลตรวจสอบสิทธินี้'], 403);
                }
                
                if (!LicenseVerificationService::isLicensed()) {
                    return response()->json(['status' => 'error', 'message' => 'ระบบระงับการทำงานชั่วคราว: สำหรับ License เท่านั้น กรุณาลงทะเบียน License ที่เมนูตั้งค่าระบบ'], 403);
                }
                
                return $next($request);
            }
        ]);
    }

    /**
     * ดึงข้อมูล Token ท้องถิ่นส่งให้หน้าบ้าน
     */
    public function loadLocalToken(Request $request)
    {
        // 1. ลองอ่านไฟล์ในเครื่องก่อน (กรณีใช้งานแบบ Localhost)
        $localTokens = $this->getLocalTokens();
        $accessToken = null;
        $refreshToken = null;

        if ($localTokens && !empty($localTokens['access-token'])) {
            $accessToken = $localTokens['access-token'];
            $refreshToken = $localTokens['refresh-token'] ?? '';
        }

        // 2. ถ้าไฟล์ไม่มี หรือเป็นคีย์ที่หมดอายุ (สำหรับคีย์ยาว JWT) -> ลองดึงจากฐานข้อมูล HOSxP
        // หมายเหตุ: ถ้าเป็นคีย์ 16 หลัก (SOAP Token) จะผ่านการตรวจสอบความยาวและข้ามการเช็ค JWT Expiry
        if (empty($accessToken) || ($this->isJwtExpired($accessToken) && strlen($accessToken) !== 16)) {
            $cid = Auth::user()->cid ?? null;
            $dbTokens = $this->getHosxpToken($cid, 'production');
            if ($dbTokens) {
                $accessToken = $dbTokens['access_token'];
                $refreshToken = $dbTokens['refresh_token'];
            }
        }

        if (!empty($accessToken)) {
            $expiresAt = null;
            $parts = explode('.', $accessToken);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if (isset($payload['exp'])) {
                    $expiresAt = $payload['exp'];
                }
            }

            return response()->json([
                'status' => 'success',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $expiresAt
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'ไม่พบคีย์ในเครื่องท้องถิ่น และไม่พบคีย์ที่พร้อมใช้งานในตาราง nhso_token ของ HOSxP'
        ], 404);
    }

    /**
     * ค้นหาสิทธิ์ผู้รับบริการผ่าน API สปสช. (SRM / SOAP)
     */
    public function search(Request $request)
    {
        $request->validate([
            'pid' => 'required|digits:13',
            'environment' => 'required|in:production,test'
        ]);

        $pid = $request->input('pid');
        $env = $request->input('environment');
        
        $accessToken = $request->input('access_token');
        $refreshToken = $request->input('refresh_token');
        $tokenRefreshed = false;

        // 1. ดึง Token: จาก request (ถ้ารับมาแล้วยังไม่หมดอายุ)
        if (empty($accessToken) || ($this->isJwtExpired($accessToken) && strlen($accessToken) !== 16)) {
            // 2. ถ้าไม่มี หรือคีย์หมดอายุ ให้ลองอ่านจากไฟล์ท้องถิ่น
            $localTokens = $this->getLocalTokens();
            if ($localTokens && !empty($localTokens['access-token']) && !$this->isJwtExpired($localTokens['access-token'])) {
                $accessToken = $localTokens['access-token'];
                $refreshToken = $localTokens['refresh-token'] ?? null;
                $tokenRefreshed = true;
            } else {
                // 3. ถ้าในไฟล์ท้องถิ่นไม่มี หรือหมดอายุ ให้ดึงจากฐานข้อมูล HOSxP nhso_token
                $cid = Auth::user()->cid ?? null;
                $dbTokens = $this->getHosxpToken($cid, $env);
                if ($dbTokens) {
                    $accessToken = $dbTokens['access_token'];
                    $refreshToken = $dbTokens['refresh_token'];
                    $tokenRefreshed = true;
                }
            }
        }

        if (empty($accessToken)) {
            return response()->json([
                'status' => 'error',
                'error_type' => 'token_expired',
                'message' => 'ไม่พบ Access Token ในระบบ กรุณาซิงค์คีย์เชื่อมต่อจากโปรแกรม SRM Smart Card'
            ], 401);
        }

        // ตรวจสอบว่าเป็น SOAP Token (ความยาว 16 หลัก และไม่ใช่คีย์ JWT) หรือไม่
        $isSoap = (strlen($accessToken) === 16 && !str_starts_with($accessToken, 'eyJ'));

        if ($isSoap) {
            // ค้นหา CID ของเจ้าของคีย์ 16 หลักในตาราง nhso_token เพื่อนำมาใช้สิทธิ์ยิง SOAP
            $userCid = DB::connection('hosxp')
                ->table('nhso_token')
                ->where('token', $accessToken)
                ->value('cid');

            $soapResult = $this->queryRightViaSoap($accessToken, $userCid ?? $pid, $pid);
            if ($soapResult && $soapResult['status'] === 'success') {
                $responseData = [
                    'status' => 'success',
                    'data' => $soapResult['data']
                ];
                if ($tokenRefreshed) {
                    $responseData['token_refreshed'] = true;
                    $responseData['access_token'] = $accessToken;
                    $responseData['refresh_token'] = $refreshToken;
                    $responseData['expires_at'] = null;
                }
                return response()->json($responseData);
            }

            return response()->json([
                'status' => 'error',
                'error_type' => 'token_expired',
                'message' => 'SOAP Token ตัวสั้นหมดอายุ หรือเซิร์ฟเวอร์สปสช. ปฏิเสธการเข้าถึง กรุณาล็อกอินเสียบบัตรหน้า HOSxP ใหม่'
            ], 401);
        }

        // 4. เรียกใช้งาน API สปสช. (ระบบ REST JSON)
        $baseUrl = ($env === 'test') ? 'https://tsrm.nhso.go.th' : 'https://srm.nhso.go.th';
        $endpoint = "{$baseUrl}/api/ucws/v1/right-search";

        $response = Http::withoutVerifying()
            ->withToken($accessToken)
            ->timeout(10)
            ->get($endpoint, ['pid' => $pid]);

        // 5. จัดการกรณี Token หมดอายุ (401 Unauthorized)
        if ($response->status() === 401 || (isset($response->json()['message']) && strpos(strtolower($response->json()['message']), 'token') !== false)) {
            if (!empty($refreshToken)) {
                // พยายาม Refresh Token อัตโนมัติ
                $refreshResult = $this->performTokenRefresh($refreshToken, $env);
                if ($refreshResult && isset($refreshResult['access-token'])) {
                    $newAccessToken = $refreshResult['access-token'];
                    $newRefreshToken = $refreshResult['refresh-token'] ?? $refreshToken;
                    $tokenRefreshed = true;

                    // บันทึกกลับลงไฟล์เครื่อง
                    $localTokens = $this->getLocalTokens();
                    if ($localTokens) {
                        $this->saveLocalTokens($newAccessToken, $newRefreshToken);
                    }

                    // บันทึกกลับลงตาราง nhso_token ใน HOSxP (ค้นหาตามคีย์เดิมที่มีเพื่อความถูกต้องของแถวผู้เป็นเจ้าของ)
                    try {
                        DB::connection('hosxp')
                            ->table('nhso_token')
                            ->where('refresh_token', $refreshToken)
                            ->orWhere('token', $accessToken)
                            ->update([
                                'token' => $newAccessToken,
                                'refresh_token' => $newRefreshToken,
                                'update_datetime' => now()->format('Y-m-d H:i:s'),
                                'access_token_expire' => $this->getJwtExpiryDate($newAccessToken),
                                'refresh_token_expire' => $this->getJwtExpiryDate($newRefreshToken),
                            ]);
                    } catch (\Throwable $e) {
                        Log::warning('HOSxP nhso_token update failed: ' . $e->getMessage());
                    }

                    // เรียกใช้ API สปสช. อีกครั้งด้วย Token ใหม่
                    $retryResponse = Http::withoutVerifying()
                        ->withToken($newAccessToken)
                        ->timeout(10)
                        ->get($endpoint, ['pid' => $pid]);

                    if ($retryResponse->successful()) {
                        $expiresAt = null;
                        $parts = explode('.', $newAccessToken);
                        if (count($parts) === 3) {
                            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                            $expiresAt = $payload['exp'] ?? null;
                        }

                        return response()->json([
                            'status' => 'success',
                            'data' => $retryResponse->json(),
                            'token_refreshed' => true,
                            'access_token' => $newAccessToken,
                            'refresh_token' => $newRefreshToken,
                            'expires_at' => $expiresAt
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => 'error',
                'error_type' => 'token_expired',
                'message' => 'Access Token หมดอายุ หรือไม่ถูกต้อง กรุณาตรวจสอบหรือขอ Token ใหม่จากระบบ Smart Card SSO'
            ], 401);
        }

        if ($response->failed()) {
            $errData = $response->json();
            return response()->json([
                'status' => 'error',
                'message' => $errData['message'] ?? 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์ สปสช. (HTTP ' . $response->status() . ')'
            ], $response->status() ?: 500);
        }

        // คืนค่าสำเร็จ
        $responseData = [
            'status' => 'success',
            'data' => $response->json()
        ];

        // ถ้ามีการโหลด/รีเฟรช Token ใหม่ ให้ส่งกลับไปอัปเดตหน้าบ้านด้วย
        if ($tokenRefreshed) {
            $expiresAt = null;
            $parts = explode('.', $accessToken);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                $expiresAt = $payload['exp'] ?? null;
            }

            $responseData['token_refreshed'] = true;
            $responseData['access_token'] = $accessToken;
            $responseData['refresh_token'] = $refreshToken;
            $responseData['expires_at'] = $expiresAt;
        }

        return response()->json($responseData);
    }

    /**
     * ต่ออายุ Token (Refresh Token)
     */
    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required',
            'environment' => 'required|in:production,test'
        ]);

        $refreshToken = $request->input('refresh_token');
        $env = $request->input('environment');

        $result = $this->performTokenRefresh($refreshToken, $env);
        if ($result && isset($result['access-token'])) {
            // เขียนลงไฟล์หากเครื่องเป็น Local
            $localTokens = $this->getLocalTokens();
            if ($localTokens) {
                $this->saveLocalTokens($result['access-token'], $result['refresh-token'] ?? $refreshToken);
            }

            return response()->json([
                'status' => 'success',
                'access_token' => $result['access-token'],
                'refresh_token' => $result['refresh-token'] ?? $refreshToken
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'ไม่สามารถต่ออายุ Token ได้ กรุณาเชื่อมต่อโปรแกรม Smart Card SSO ใหม่อีกครั้ง'
        ], 400);
    }

    /**
     * ดึง Token ล่าสุดที่มีการอัพเดทในโรงพยาบาลจากตาราง nhso_token ของ HOSxP
     */
    private function getHosxpToken($cid = null, $env = 'production')
    {
        try {
            // ดึงคีย์ล่าสุด 5 แถวแรกที่มีการอัปเดตเรียงลำดับเวลาล่าสุดลงไป
            $hosxpTokens = DB::connection('hosxp')
                ->table('nhso_token')
                ->orderBy('update_datetime', 'desc')
                ->limit(5)
                ->get();

            $baseUrl = ($env === 'test') ? 'https://tsrm.nhso.go.th' : 'https://srm.nhso.go.th';
            $endpoint = "{$baseUrl}/api/ucws/v1/right-search";

            foreach ($hosxpTokens as $hosxpToken) {
                $possibleAccess = $hosxpToken->token;
                $possibleRefresh = $hosxpToken->refresh_token;
                $testPid = $hosxpToken->cid; // ใช้ CID ของผู้ครองคีย์ในแถวนั้นมาทำการทดลองยิงสิทธิ์เพื่อความแม่นยำ

                // 1. ถ้าคีย์หลักในแถวนี้เป็น SOAP Token (รหัสสั้น 16 หลัก) -> ทดลองยิงจริงผ่าน SOAP XML
                if (!empty($possibleAccess) && strlen($possibleAccess) === 16 && !str_starts_with($possibleAccess, 'eyJ')) {
                    $soapTest = $this->queryRightViaSoap($possibleAccess, $testPid, $testPid);
                    if ($soapTest && $soapTest['status'] === 'success') {
                        return [
                            'access_token' => $possibleAccess,
                            'refresh_token' => $possibleRefresh
                        ];
                    }
                }

                // 2. ถ้าคีย์หลักในแถวนี้เป็น JWT และยังไม่หมดอายุตามเวลา -> ทดลองยิงจริงไปที่ สปสช. เพื่อตรวจเช็ค HTTP 200
                if (str_starts_with($possibleAccess, 'eyJ') && !$this->isJwtExpired($possibleAccess)) {
                    $testResponse = Http::withoutVerifying()
                        ->withToken($possibleAccess)
                        ->timeout(3)
                        ->get($endpoint, ['pid' => $testPid]);

                    if ($testResponse->status() === 200) {
                        return [
                            'access_token' => $possibleAccess,
                            'refresh_token' => $possibleRefresh
                        ];
                    }
                }

                // 3. ถ้าคีย์หลักหมดอายุ/ไม่ใช่ SOAP/ไม่ใช่ JWT หรือยิงไม่ผ่าน -> ลองนำ Refresh Token ไปต่ออายุ
                if (str_starts_with($possibleRefresh, 'eyJ') && !$this->isJwtExpired($possibleRefresh)) {
                    $refreshResult = $this->performTokenRefresh($possibleRefresh, $env);
                    if ($refreshResult && !empty($refreshResult['access-token'])) {
                        $accessToken = $refreshResult['access-token'];
                        $refreshToken = $refreshResult['refresh-token'] ?? $possibleRefresh;

                        // ทดลองยิงเช็คสิทธิ์คนไข้จริงด้วยคีย์ใหม่เพื่อยืนยันว่าใช้งานได้
                        $testResponse = Http::withoutVerifying()
                            ->withToken($accessToken)
                            ->timeout(3)
                            ->get($endpoint, ['pid' => $testPid]);

                        if ($testResponse->status() === 200) {
                            // อัปเดตข้อมูลกลับลงฐานข้อมูล HOSxP ที่แถวเดิมของเจ้าของคีย์ (เพื่อความถูกต้องของระบบ HOSxP)
                            DB::connection('hosxp')
                                ->table('nhso_token')
                                ->where('cid', $hosxpToken->cid)
                                ->update([
                                    'token' => $accessToken,
                                    'refresh_token' => $refreshToken,
                                    'update_datetime' => now()->format('Y-m-d H:i:s'),
                                    'access_token_expire' => $this->getJwtExpiryDate($accessToken),
                                    'refresh_token_expire' => $this->getJwtExpiryDate($refreshToken),
                                ]);

                            return [
                                'access_token' => $accessToken,
                                'refresh_token' => $refreshToken
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('HOSxP nhso_token lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    private function isJwtExpired($token)
    {
        if (empty($token) || !str_starts_with($token, 'eyJ')) {
            return true;
        }
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp'])) {
                return time() > ($payload['exp'] - 10); // ลบ 10 วินาทีกันเหนียว
            }
        }
        return true;
    }

    private function getJwtExpiryDate($token)
    {
        if (empty($token) || !str_starts_with($token, 'eyJ')) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            if (isset($payload['exp'])) {
                return date('Y-m-d H:i:s', $payload['exp']);
            }
        }
        return null;
    }

    /**
     * ส่งคำขอ Refresh Token ไปยัง สปสช.
     */
    private function performTokenRefresh($refreshToken, $env)
    {
        $host = ($env === 'test') ? 'https://tsrmportal.nhso.go.th' : 'https://srmportal.nhso.go.th';
        $endpoint = "{$host}/api/scard/access-token";

        try {
            $response = Http::withoutVerifying()
                ->asForm()
                ->timeout(10)
                ->post($endpoint, [
                    'refresh_token' => $refreshToken
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'access-token' => $data['access-token'] ?? ($data['access_token'] ?? null),
                    'refresh-token' => $data['refresh-token'] ?? ($data['refresh_token'] ?? $refreshToken)
                ];
            }
        } catch (\Throwable $e) {
            Log::error('NHSO Token Refresh Network Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * อ่านไฟล์ token.txt ท้องถิ่น (ถ้ามีสิทธิ์เข้าถึง)
     */
    private function getLocalTokens()
    {
        $userprofile = getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? null);
        if (empty($userprofile)) {
            // ลองใช้ home drive + path
            $homedrive = getenv('HOMEDRIVE') ?: 'C:';
            $homepath = getenv('HOMEPATH') ?: '';
            if ($homepath) {
                $userprofile = $homedrive . $homepath;
            }
        }

        if ($userprofile) {
            $path = $userprofile . DIRECTORY_SEPARATOR . 'SRM Smart Card Single Sign-On' . DIRECTORY_SEPARATOR . 'token.txt';
            if (file_exists($path)) {
                try {
                    $content = file_get_contents($path);
                    $lines = explode("\n", str_replace("\r", "", $content));
                    $tokens = [];
                    foreach ($lines as $line) {
                        if (strpos($line, '=') !== false) {
                            list($k, $v) = explode('=', $line, 2);
                            $tokens[trim($k)] = trim($v);
                        }
                    }
                    if (isset($tokens['access-token']) || isset($tokens['refresh-token'])) {
                        return $tokens;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Cannot read local token.txt: ' . $e->getMessage());
                }
            }
        }

        return null;
    }

    /**
     * บันทึกไฟล์ token.txt ท้องถิ่น
     */
    private function saveLocalTokens($accessToken, $refreshToken)
    {
        $userprofile = getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? null);
        if ($userprofile) {
            $path = $userprofile . DIRECTORY_SEPARATOR . 'SRM Smart Card Single Sign-On' . DIRECTORY_SEPARATOR . 'token.txt';
            if (file_exists($path) && is_writable($path)) {
                try {
                    $content = "access-token={$accessToken}\nrefresh-token={$refreshToken}\n";
                    file_put_contents($path, $content);
                    return true;
                } catch (\Throwable $e) {
                    Log::warning('Cannot write local token.txt: ' . $e->getMessage());
                }
            }
        }
        return false;
    }

    /**
     * ดึงประวัติ Token 5 ลำดับล่าสุดจาก HOSxP เพื่อแสดงบนหน้าจอตรวจเช็ค
     */
    public function getHosxpTokensHistory(Request $request)
    {
        try {
            $tokens = DB::connection('hosxp')
                ->table('nhso_token')
                ->orderBy('update_datetime', 'desc')
                ->limit(5)
                ->get();

            $formatted = [];
            foreach ($tokens as $row) {
                $possibleAccess = $row->token ?? '';
                $possibleRefresh = $row->refresh_token ?? '';

                $accessStatus = 'ไม่ใช่ JWT (รหัสสั้น)';
                if (str_starts_with($possibleAccess, 'eyJ')) {
                    $accessStatus = $this->isJwtExpired($possibleAccess) ? 'หมดอายุ' : 'พร้อมใช้งาน';
                }

                $refreshStatus = 'ไม่มี';
                if (str_starts_with($possibleRefresh, 'eyJ')) {
                    $refreshStatus = $this->isJwtExpired($possibleRefresh) ? 'หมดอายุ' : 'พร้อมใช้งาน';
                }

                $formatted[] = [
                    'cid' => $row->cid,
                    'update_datetime' => $row->update_datetime,
                    'is_invalid' => $row->is_invalid ?? 'N',
                    'token_preview' => strlen($possibleAccess) > 15 ? substr($possibleAccess, 0, 15) . '...' : $possibleAccess,
                    'access_status' => $accessStatus,
                    'refresh_status' => $refreshStatus,
                    'refresh_token_preview' => strlen($possibleRefresh) > 15 ? substr($possibleRefresh, 0, 15) . '...' : $possibleRefresh,
                    'refresh_token_expire' => $row->refresh_token_expire ?? 'ไม่มี',
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => $formatted
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล HOSxP ได้: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงสิทธิ์ผ่าน SOAP XML โดยตรงโดยใช้คีย์สั้น 16 หลัก (SMC Token)
     */
    private function queryRightViaSoap($smcToken, $userCid, $patientPid)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
   <S:Body>
      <ns2:searchCurrentByPID xmlns:ns2="http://tokenws.ucws.nhso.go.th/">
         <user_person_id>' . htmlspecialchars($userCid) . '</user_person_id>
         <smctoken>' . htmlspecialchars($smcToken) . '</smctoken>
         <person_id>' . htmlspecialchars($patientPid) . '</person_id>
      </ns2:searchCurrentByPID>
   </S:Body>
</S:Envelope>';

        $endpoint = "http://ucws.nhso.go.th/ucwstokenp1/UCWSTokenP1";
        
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => '',
                ])
                ->timeout(5)
                ->send('POST', $endpoint, [
                    'body' => $xml
                ]);

            if ($response->status() === 200) {
                $body = $response->body();
                
                // แปลง XML ของสปสช. เป็น JSON อาร์เรย์
                $xmlObj = simplexml_load_string($body);
                if ($xmlObj === false) {
                    return null;
                }
                
                $xmlObj->registerXPathNamespace('S', 'http://schemas.xmlsoap.org/soap/envelope/');
                $xmlObj->registerXPathNamespace('ns2', 'http://tokenws.ucws.nhso.go.th/');
                
                $nodes = $xmlObj->xpath('//ns2:searchCurrentByPIDResponse/return');
                if (empty($nodes)) {
                    return null;
                }
                
                $returnData = json_decode(json_encode($nodes[0]), true);
                if (isset($returnData['ws_status']) && $returnData['ws_status'] !== 'NHSO-00000' && strpos($returnData['ws_status'], 'NHSO') !== false) {
                    return [
                        'status' => 'error',
                        'message' => $returnData['ws_status_desc'] ?? 'สปสช. ปฏิเสธการสืบค้นข้อมูล'
                    ];
                }
                
                return [
                    'status' => 'success',
                    'data' => [
                        'pid' => $returnData['person_id'] ?? $patientPid,
                        'fname' => $returnData['fname'] ?? '',
                        'lname' => $returnData['lname'] ?? '',
                        'titleName' => $returnData['title_name'] ?? '',
                        'birthDate' => $returnData['birth_date'] ?? '',
                        'sex' => $returnData['sex'] ?? '',
                        'transDate' => $returnData['trans_date'] ?? null,
                        'mainInscl' => [
                            'id' => $returnData['main_inscl'] ?? '',
                            'name' => $returnData['main_inscl_name'] ?? '',
                        ],
                        'subInscl' => [
                            'id' => $returnData['sub_inscl'] ?? '',
                            'name' => $returnData['sub_inscl_name'] ?? '',
                        ],
                        'hospMain' => [
                            'hcode' => $returnData['hosp_main'] ?? '',
                            'hname' => $returnData['hosp_main_name'] ?? '',
                        ],
                        'hospSub' => [
                            'hcode' => $returnData['hosp_sub'] ?? '',
                            'hname' => $returnData['hosp_sub_name'] ?? '',
                        ],
                        'hospMainOp' => [
                            'hcode' => $returnData['hosp_main_op'] ?? '',
                            'hname' => $returnData['hosp_main_op_name'] ?? '',
                        ],
                        'startDateTime' => $returnData['start_date'] ?? '',
                        'expireDateTime' => $returnData['exp_date'] ?? '',
                        'claimTypes' => [
                            [
                                'claimType' => $returnData['main_inscl'] ?? '',
                                'claimTypeName' => $returnData['main_inscl_name'] ?? '',
                            ]
                        ]
                    ]
                ];
            }
        } catch (\Throwable $e) {
            Log::error('SOAP XML Right check exception: ' . $e->getMessage());
        }
        
        return null;
    }
}
