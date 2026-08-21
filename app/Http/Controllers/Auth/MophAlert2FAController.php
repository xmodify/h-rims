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

        // If no OTP has been generated, generate and send one first
        if (!session()->has('moph_alert_otp') || Carbon::parse(session('moph_alert_otp_expires'))->isPast()) {
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
            return redirect()->back()->withErrors(['otp_code' => 'รหัส OTP หมดอายุแล้ว กรุณากดส่งรหัสใหม่อีกครั้ง']);
        }

        if ($enteredOtp === $cachedOtp) {
            // Mark 2FA as verified in session
            session(['moph_alert_2fa_verified' => true]);
            
            // Clean up temporary OTP data
            session()->forget(['moph_alert_otp', 'moph_alert_otp_expires', 'moph_alert_last_sent']);

            return redirect()->route('home')->with('success', 'เข้าสู่ระบบเสร็จสิ้น');
        }

        return redirect()->back()->withErrors(['otp_code' => 'รหัส OTP ไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง']);
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

        $clientId = DB::table('main_setting')->where('name', 'moph_alert_client_id')->value('value');
        $clientSecret = DB::table('main_setting')->where('name', 'moph_alert_client_secret')->value('value');

        // Moph Alert official endpoint from documentation
        $url = 'https://morpromt2c.moph.go.th/alert/v3.1/messages';

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
                            'text' => "รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ $otp",
                            'type' => 'text'
                        ]
                    ],
                    'message_title' => "รหัส OTP เข้าสู่ระบบ RiMS",
                    'message_html' => "<div>รหัสยืนยันตัวตน (2FA) สำหรับเข้าระบบ RiMS ของท่านคือ <strong>$otp</strong></div>",
                    'message_text' => "รหัส OTP ของท่านคือ $otp",
                    'message_type' => "HPT"
                ]);

            if ($response->successful()) {
                Log::info("Moph Alert OTP sent successfully for CID: $cid");
                return true;
            } else {
                Log::error("Failed to send Moph Alert OTP. Status: " . $response->status() . " Body: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error sending Moph Alert OTP: " . $e->getMessage());
            return false;
        }
    }
}
