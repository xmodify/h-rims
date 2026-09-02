<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ClaimPreAuditService;
use Illuminate\Support\Facades\DB;

class ClaimAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Audit single OPD Visit details
     */
    public function visitDetails(Request $request)
    {
        $vn = $request->input('vn');
        if (empty($vn)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ VN / SEQ'
            ], 400);
        }

        $result = ClaimPreAuditService::auditVisit((string)$vn);
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Audit single IPD Admission details
     */
    public function admissionDetails(Request $request)
    {
        $an = $request->input('an');
        if (empty($an)) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาระบุ AN'
            ], 400);
        }

        $result = ClaimPreAuditService::auditAdmission((string)$an);
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Batch check multiple VNs
     */
    public function batchCheck(Request $request)
    {
        $vns = (array)$request->input('vns', []);
        if (empty($vns)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $results = ClaimPreAuditService::auditBatchVisits($vns);
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}
