<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ImportFdhController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            function ($request, $next) {
                $user = auth()->user();
                if ($user && $user->status !== 'admin' && $user->allow_import !== 'Y') {
                    return response()->view('errors.restricted', ['module' => 'นำเข้าข้อมูล'], 403);
                }
                return $next($request);
            }
        ]);
    }

    // ข้อมูล FDH Claim Status
    public function fdh_claim_status(Request $request)
    {
        $start_date = $request->start_date ?: date('Y-m-d');
        $end_date = $request->end_date ?: date('Y-m-d');
        // อัปเดตค่าเก็บใน Session เผื่อครั้งถัดไป
        Session::put('start_date', $start_date);
        Session::put('end_date', $end_date);

        $sql = DB::connection('hosxp')->select('
            SELECT fdh.*
            FROM ovst o
            INNER JOIN hrims.fdh_claim_status fdh ON fdh.seq = o.vn						
            WHERE o.vstdate BETWEEN ? AND ?
            GROUP BY o.vn
            UNION
            SELECT fdh.*
            FROM ipt i
            INNER JOIN hrims.fdh_claim_status fdh ON fdh.an = i.an						
            WHERE i.dchdate BETWEEN ? AND ?
            GROUP BY i.an', [$start_date, $end_date, $start_date, $end_date]);

        return view('import.fdh_claim_status', compact('start_date', 'end_date', 'sql'));
    }
}
