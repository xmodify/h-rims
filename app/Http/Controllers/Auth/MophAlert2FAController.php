<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MophAlert2FAController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the 2FA verification form.
     */
    public function showVerifyForm()
    {
        // If already verified, redirect to home
        if (session('moph_alert_2fa_verified') === true) {
            return redirect()->route('home');
        }

        $user = auth()->user();
        $cid = $user->cid ?? '';
        $cidError = null;

        if (empty($cid)) {
            $cidError = 'บัญชีผู้ใช้งานของคุณไม่มีข้อมูลเลขบัตรประชาชน (CID) ในระบบ กรุณาติดต่อผู้ดูแลระบบเพื่อทำการแก้ไขข้อมูล';
        } elseif (strlen($cid) !== 13 || !ctype_digit($cid)) {
            $cidError = 'เลขบัตรประชาชน (CID) ในระบบของคุณไม่ถูกต้อง (ต้องเป็นตัวเลข 13 หลัก) กรุณาติดต่อผู้ดูแลระบบเพื่อทำการแก้ไขข้อมูล';
        }

        // If CID is invalid/missing, do not generate OTP
        if ($cidError) {
            return view('auth.verify-2fa', compact('cidError'));
        }

        // Concurrency guard: if an OTP was generated less than 5 seconds ago, do not generate again
        $lastSent = session('moph_alert_last_sent');
        if ($lastSent && (time() - $lastSent) < 5) {
            return view('auth.verify-2fa');
        }

        // If no OTP has been generated, generate and send one first
        if (!session()->has('moph_alert_otp') || time() > session('moph_alert_otp_expires')) {
            $this->generateAndSendOTP();
        }

        return view('auth.verify-2fa');
    }

    /**
     * Verify the submitted OTP code.
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ], [
            'otp_code.required' => 'กรุณากรอกรหัส OTP',
            'otp_code.size' => 'รหัส OTP ต้องมีขนาด 6 หลัก',
        ]);

        $enteredOtp = $request->otp_code;
        $cachedOtp = session('moph_alert_otp');
        $expiresAt = session('moph_alert_otp_expires');

        if (!$cachedOtp || !$expiresAt || time() > $expiresAt) {
            return redirect()->back()
                ->withErrors(['otp_code' => 'รหัส OTP หมดอายุแล้ว กรุณากดส่งรหัสใหม่อีกครั้ง'])
                ->withInput($request->only('otp_code'));
        }

        if ($enteredOtp === $cachedOtp) {
            // Mark 2FA as verified in session
            session(['moph_alert_2fa_verified' => true]);
            
            // Clean up temporary OTP data
            session()->forget(['moph_alert_otp', 'moph_alert_otp_expires', 'moph_alert_last_sent']);

            return redirect()->route('home')->with('success', 'เข้าสู่ระบบเสร็จสิ้น');
        }

        return redirect()->back()
            ->withErrors(['otp_code' => 'รหัส OTP ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'])
            ->withInput($request->only('otp_code'));
    }

    /**
     * Resend a new OTP.
     */
    public function resendOTP()
    {
        $lastSent = session('moph_alert_last_sent');
        $cooldown = 120; // 120 seconds cooldown

        if ($lastSent && (time() - $lastSent) < $cooldown) {
            $secondsLeft = $cooldown - (time() - $lastSent);
            return redirect()->back()->withErrors(['resend' => "กรุณารอสักครู่ (เหลือเวลาอีก {$secondsLeft} วินาที)"]);
        }

        $this->generateAndSendOTP();

        return redirect()->back()->with('success_resend', 'ส่งรหัส OTP ใหม่เรียบร้อยแล้ว');
    }

    /**
     * Helper to generate and trigger sending the OTP.
     */
    protected function generateAndSendOTP()
    {
        $user = auth()->user();
        $otp = (string) rand(100000, 999999);
        
        // Save to session
        session([
            'moph_alert_otp' => $otp,
            'moph_alert_otp_expires' => time() + 120, // 120 seconds lifetime (2 minutes)
            'moph_alert_last_sent' => time(),
        ]);

        // Send via Moph Alert API
        $this->sendOTPViaMophAlert($user->cid ?? '', $otp);
    }

    /**
     * Core API integration to call Moph Alert API.
     */
    protected function sendOTPViaMophAlert($cid, $otp)
    {
        if (empty($cid)) {
            Log::warning('Cannot send Moph Alert OTP: User CID is empty.');
            return false;
        }

        $clientId = \App\Services\LicenseVerificationService::getConfig('moph_alert_client_id', 'moph_alert_client_id');
        $clientSecret = \App\Services\LicenseVerificationService::getConfig('moph_alert_client_secret', 'moph_alert_client_secret');

        // Moph Alert official endpoint from documentation
        $url = 'https://morpromt2c.moph.go.th/alert/v3.1/messages';

        $boldDigits = [
            '0' => '𝟬', '1' => '𝟭', '2' => '𝟮', '3' => '𝟯', '4' => '𝟰',
            '5' => '𝟱', '6' => '𝟲', '7' => '𝟳', '8' => '𝟴', '9' => '𝟵'
        ];
        $boldOtp = strtr($otp, $boldDigits);

        try {
            Log::info("Attempting to send OTP $otp via Moph Alert for CID $cid");

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'client-key' => $clientId,
                    'secret-key' => $clientSecret,
                ])
                ->post($url, [
                    'cid' => [(string)$cid], // Must be an array of strings
                    'messages' => [
                        [
                            'text' => "รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ $boldOtp",
                            'type' => 'text'
                        ]
                    ],
                    'message_title' => "รหัส OTP เข้าสู่ระบบ RiMS",
                    'message_html' => "<div>รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ <strong>$otp</strong></div>",
                    'message_text' => "รหัส OTP ของท่านคือ $boldOtp",
                    'message_type' => "HPT"
                ]);

            $data = $response->json();
            $msgCode = $data['message_code'] ?? ($data['code'] ?? null);

            if ($msgCode == 401 || $response->status() === 401) {
                Log::warning("Moph Alert returned 401 (Keys wrong). Retrying using central server licensing keys directly.");
                
                $info = \App\Services\LicenseVerificationService::getLicenseStatusInfo();
                $centralClientId = $info['configs']['moph_alert_client_id'] ?? '';
                $centralClientSecret = $info['configs']['moph_alert_client_secret'] ?? '';

                if (!empty($centralClientId) && $centralClientId !== $clientId) {
                    $response = Http::withoutVerifying()
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'client-key' => $centralClientId,
                            'secret-key' => $centralClientSecret,
                        ])
                        ->post($url, [
                            'cid' => [(string)$cid],
                            'messages' => [
                                [
                                    'text' => "รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ $boldOtp",
                                    'type' => 'text'
                                ]
                            ],
                            'message_title' => "รหัส OTP เข้าสู่ระบบ RiMS",
                            'message_html' => "<div>รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ <strong>$otp</strong></div>",
                            'message_text' => "รหัส OTP ของท่านคือ $boldOtp",
                            'message_type' => "HPT"
                        ]);
                    
                    $data = $response->json();
                    $msgCode = $data['message_code'] ?? ($data['code'] ?? null);
                }
            }

            if ($response->successful() && ($msgCode == 200 || is_null($msgCode))) {
                Log::info("Moph Alert OTP sent successfully for CID: $cid");
                return true;
            } else {
                Log::error("Failed to send Moph Alert OTP. HTTP Status: " . $response->status() . " Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error sending Moph Alert OTP: " . $e->getMessage());
            return false;
        }
    }
}
