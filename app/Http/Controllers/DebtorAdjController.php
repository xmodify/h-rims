<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\DateHelper; // If Thai date helper exists, otherwise we'll format directly or use custom function
use PDF;

class DebtorAdjController extends Controller
{
    public function _1102050101_103(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_103
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_103
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_103_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_103.pdf');
        }

        return abort(404);
    }

    public function _1102050101_103_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_103::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note
            ];

            if ($diff > 0) {
                $update_data['adj_inc'] = $diff;
                $update_data['adj_dec'] = 0;
            } else {
                $update_data['adj_inc'] = 0;
                $update_data['adj_dec'] = abs($diff);
            }

            $row->update($update_data);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_109(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_109
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_109
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_109_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_109.pdf');
        }

        return abort(404);
    }

    public function _1102050101_109_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_109::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note
            ];

            if ($diff > 0) {
                $update_data['adj_inc'] = $diff;
                $update_data['adj_dec'] = 0;
            } else {
                $update_data['adj_inc'] = 0;
                $update_data['adj_dec'] = abs($diff);
            }

            $row->update($update_data);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_201(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_201
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_201
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_201_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_201.pdf');
        }

        return abort(404);
    }

    public function _1102050101_201_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        // Batch Query (Fetch all lockable records at once)
        $rows = \App\Models\Debtor_1102050101_201::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        // Batch Query stm_ucs to resolve N+1 issue
        $cids = $rows->pluck('cid')->filter()->unique()->toArray();
        $vstdates = $rows->pluck('vstdate')->filter()->unique()->toArray();

        $stm_data = [];
        if (!empty($cids) && !empty($vstdates)) {
            $stm_records = \DB::table('stm_ucs')
                ->select('cid', 'vstdate', \DB::raw('LEFT(vsttime, 5) as vsttime5'), \DB::raw('SUM(receive_pp) as total_receive_pp'))
                ->whereIn('cid', $cids)
                ->whereIn('vstdate', $vstdates)
                ->groupBy('cid', 'vstdate', 'vsttime5')
                ->get();

            foreach ($stm_records as $rec) {
                $key = $rec->cid . '|' . $rec->vstdate . '|' . $rec->vsttime5;
                $stm_data[$key] = (float)$rec->total_receive_pp;
            }
        }

        foreach ($rows as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $key = $row->cid . '|' . $row->vstdate . '|' . $vsttime5;
            $stm = isset($stm_data[$key]) ? $stm_data[$key] : 0.0;

            $total_received = (float)$row->receive + (float)$stm;
            $diff = (float)$row->debtor - $total_received;
            
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note
            ];

            if ($diff >= 0) {
                $update_data['adj_inc'] = $diff;
                $update_data['adj_dec'] = 0;
            } else {
                $update_data['adj_inc'] = 0;
                $update_data['adj_dec'] = abs($diff);
            }

            $row->update($update_data);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_203(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_203
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_203
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_203_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_203.pdf');
        }

        return abort(404);
    }

    public function _1102050101_203_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        // Batch Query (Fetch all lockable records at once)
        $rows = \App\Models\Debtor_1102050101_203::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        // Batch Query stm_ucs to resolve N+1 issue
        $cids = $rows->pluck('cid')->filter()->unique()->toArray();
        $vstdates = $rows->pluck('vstdate')->filter()->unique()->toArray();

        $stm_data = [];
        if (!empty($cids) && !empty($vstdates)) {
            $stm_records = \DB::table('stm_ucs')
                ->select('cid', 'vstdate', \DB::raw('LEFT(vsttime, 5) as vsttime5'), \DB::raw('SUM(receive_pp) as total_receive_pp'))
                ->whereIn('cid', $cids)
                ->whereIn('vstdate', $vstdates)
                ->groupBy('cid', 'vstdate', 'vsttime5')
                ->get();

            foreach ($stm_records as $rec) {
                $key = $rec->cid . '|' . $rec->vstdate . '|' . $rec->vsttime5;
                $stm_data[$key] = (float)$rec->total_receive_pp;
            }
        }

        foreach ($rows as $row) {
            $vsttime5 = substr($row->vsttime, 0, 5);
            $key = $row->cid . '|' . $row->vstdate . '|' . $vsttime5;
            $stm = isset($stm_data[$key]) ? $stm_data[$key] : 0.0;

            $total_received = (float)$row->receive + (float)$stm;
            $diff = (float)$row->debtor - $total_received;
            
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note
            ];

            if ($diff >= 0) {
                $update_data['adj_inc'] = $diff;
                $update_data['adj_dec'] = 0;
            } else {
                $update_data['adj_inc'] = 0;
                $update_data['adj_dec'] = abs($diff);
            }

            $row->update($update_data);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_209(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_209
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_209
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_209_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_209.pdf');
        }

        return abort(404);
    }

    public function _1102050101_209_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        // Batch Query (Fetch all lockable records at once)
        $rows = \App\Models\Debtor_1102050101_209::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            
            $update_data = [
                'adj_date' => $adj_date,
                'adj_note' => $adj_note
            ];

            if ($diff > 0) {
                $update_data['adj_inc'] = $diff;
                $update_data['adj_dec'] = 0;
            } else {
                $update_data['adj_inc'] = 0;
                $update_data['adj_dec'] = abs($diff);
            }

            $row->update($update_data);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_216(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_216
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_216
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, vn ASC
        ", [$start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_216_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_216.pdf');
        }

        return abort(404);
    }

    public function _1102050101_216_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        // Batch Query (Fetch all lockable records at once)
        $rows = \App\Models\Debtor_1102050101_216::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        if ($rows->isNotEmpty()) {
            $cids = $rows->pluck('cid')->filter()->unique()->toArray();
            $vstdates = $rows->pluck('vstdate')->filter()->unique()->toArray();

            // Fetch stm_ucs records in batch
            $ucs_records = [];
            if (!empty($cids) && !empty($vstdates)) {
                $ucs_records = DB::table('stm_ucs')
                    ->select('cid', 'vstdate', DB::raw('LEFT(vsttime, 5) as vsttime5'), DB::raw('SUM(receive_total) as receive_total_sum'), DB::raw('SUM(receive_pp) as receive_pp_sum'))
                    ->whereIn('cid', $cids)
                    ->whereIn('vstdate', $vstdates)
                    ->groupBy('cid', 'vstdate', DB::raw('LEFT(vsttime, 5)'))
                    ->get();
            }

            // Fetch stm_ucs_kidney records in batch
            $kidney_records = [];
            if (!empty($cids) && !empty($vstdates)) {
                $kidney_records = DB::table('stm_ucs_kidney')
                    ->select('cid', 'datetimeadm', DB::raw('SUM(receive_total) as receive_total_sum'))
                    ->whereIn('cid', $cids)
                    ->whereIn('datetimeadm', $vstdates)
                    ->groupBy('cid', 'datetimeadm')
                    ->get();
            }

            // Map records
            $ucs_map = [];
            foreach ($ucs_records as $rec) {
                $key = $rec->cid . '|' . $rec->vstdate . '|' . $rec->vsttime5;
                $ucs_map[$key] = (float)$rec->receive_total_sum - (float)$rec->receive_pp_sum;
            }

            $kidney_map = [];
            foreach ($kidney_records as $rec) {
                $key = $rec->cid . '|' . $rec->datetimeadm;
                $kidney_map[$key] = (float)$rec->receive_total_sum;
            }

            // Perform updates in loop
            foreach ($rows as $row) {
                $vsttime_5 = substr($row->vsttime, 0, 5);
                $ucs_key = $row->cid . '|' . $row->vstdate . '|' . $vsttime_5;
                $kidney_key = $row->cid . '|' . $row->vstdate;

                $ucs_receive = $ucs_map[$ucs_key] ?? 0.0;
                $kidney_receive = $kidney_map[$kidney_key] ?? 0.0;

                $receive = $ucs_receive;
                if ($row->kidney > 0) {
                    $receive += $kidney_receive;
                }

                $diff = (float)$row->debtor - $receive;

                $update_data = [
                    'adj_date' => $adj_date,
                    'adj_note' => $adj_note,
                ];

                if ($diff >= 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                $row->update($update_data);
                $adjusted_count++;
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('error', 'ไม่พบรายการที่สามารถปรับปรุงยอดได้ (ต้อง Lock รายการก่อน)');
        }

        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }
}
