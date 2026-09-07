<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NhsoRightController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin') {
                    if ($user->allow_check_right !== 'Y' && ($user->allow_emr ?? 'N') !== 'Y') {
                        return response()->view('errors.restricted', ['module' => 'ตรวจสอบสิทธิการรักษา (สปสช.)'], 403);
                    }
                }
                return $next($request);
            }
        ]);
    }

    /**
     * Display the right-verification main view
     */
    public function index(Request $request)
    {
        return view('emr.nhso_right');
    }

    /**
     * Open local Smart Card SSO folder on the server/client machine
     */
    public function openFolder(Request $request)
    {
        $userprofile = getenv('USERPROFILE') ?: ($_SERVER['USERPROFILE'] ?? null);
        if (empty($userprofile)) {
            $homedrive = getenv('HOMEDRIVE') ?: 'C:';
            $homepath = getenv('HOMEPATH') ?: '';
            if ($homepath) {
                $userprofile = $homedrive . $homepath;
            }
        }

        if ($userprofile) {
            $path = $userprofile . DIRECTORY_SEPARATOR . 'SRM Smart Card Single Sign-On';
            if (is_dir($path)) {
                @shell_exec('explorer.exe "' . $path . '"');
                return response()->json(['status' => 'success', 'message' => 'เปิดโฟลเดอร์สำเร็จ']);
            }
            return response()->json(['status' => 'error', 'message' => 'ไม่พบโฟลเดอร์ในเครื่องเซิร์ฟเวอร์: ' . $path], 400);
        }
        return response()->json(['status' => 'error', 'message' => 'ไม่พบพาธผู้ใช้งานบนเครื่องเซิร์ฟเวอร์'], 400);
    }
}
