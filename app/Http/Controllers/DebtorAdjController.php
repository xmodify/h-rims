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

    public function _1102050101_301(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_301
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_301
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_301_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_301.pdf');
        }

        return abort(404);
    }

    public function _1102050101_301_bulk_adj(Request $request)
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
        $rows = \App\Models\Debtor_1102050101_301::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $adj_val = (float)$row->debtor - (float)$row->receive;

            if ($adj_val >= 0) {
                $row->adj_inc = $adj_val;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($adj_val);
            }
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
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

    public function _1102050101_303(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_303
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_303
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_303_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_303.pdf');
        }

        return abort(404);
    }

    public function _1102050101_303_bulk_adj(Request $request)
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
        $rows = \App\Models\Debtor_1102050101_303::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $balance = (float)$row->receive - (float)$row->debtor;
            $adj_val = 0 - $balance;

            if ($adj_val > 0) {
                $row->adj_inc = $adj_val;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($adj_val);
            }
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
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

    public function _1102050101_307(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_307
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_307
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_307_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_307.pdf');
        }

        return abort(404);
    }

    public function _1102050101_307_bulk_adj(Request $request)
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
        $rows = \App\Models\Debtor_1102050101_307::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $balance = (float)$row->receive - (float)$row->debtor;
            $adj_val = 0 - $balance;

            if ($adj_val > 0) {
                $row->adj_inc = $adj_val;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($adj_val);
            }
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
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

    public function _1102050101_309(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_309
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_309
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_309_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_309.pdf');
        }

        return abort(404);
    }

    public function _1102050101_309_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        // Batch Query (Fetch all lockable records at once)
        $rows = \App\Models\Debtor_1102050101_309::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        if ($rows->isNotEmpty()) {
            $cids = $rows->pluck('cid')->filter()->unique()->toArray();
            $vstdates = $rows->pluck('vstdate')->filter()->unique()->toArray();

            $stm_records = [];
            if (!empty($cids) && !empty($vstdates)) {
                $stm_records = DB::table('stm_sss_kidney')
                    ->select('cid', 'vstdate', DB::raw('SUM(IFNULL(amount,0)+ IFNULL(epopay,0)+ IFNULL(epoadm,0)) as receive_sum'))
                    ->whereColumn('hreg', 'hcode')
                    ->whereIn('cid', $cids)
                    ->whereIn('vstdate', $vstdates)
                    ->groupBy('cid', 'vstdate')
                    ->get();
            }

            $stm_map = [];
            foreach ($stm_records as $rec) {
                $key = $rec->cid . '|' . $rec->vstdate;
                $stm_map[$key] = (float)$rec->receive_sum;
            }

            foreach ($rows as $row) {
                $stm_key = $row->cid . '|' . $row->vstdate;
                $stm_val = $stm_map[$stm_key] ?? 0.0;

                $balance = ((float)$row->receive + $stm_val) - (float)$row->debtor;
                $adj_val = 0 - $balance;

                $update_data = [
                    'adj_date' => $date,
                    'adj_note' => $note,
                ];

                if ($adj_val > 0) {
                    $update_data['adj_inc'] = $adj_val;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($adj_val);
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

    public function _1102050101_401(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_401
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_401
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_401_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_401.pdf');
        }

        return abort(404);
    }

    public function _1102050101_401_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        // Fetch lockable rows in one query
        $rows = \App\Models\Debtor_1102050101_401::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        if ($rows->isNotEmpty()) {
            $hns = $rows->pluck('hn')->unique()->toArray();
            $vstdates = $rows->pluck('vstdate')->unique()->toArray();

            // Batch query for stm_ofc
            $stm_ofc_data = DB::table('stm_ofc')
                ->whereIn('hn', $hns)
                ->whereIn('vstdate', $vstdates)
                ->select('hn', 'vstdate', DB::raw('LEFT(vsttime, 5) as vsttime_short'), DB::raw('SUM(receive_total) as total_receive'))
                ->groupBy('hn', 'vstdate', 'vsttime_short')
                ->get()
                ->keyBy(function($item) {
                    return "{$item->hn}|{$item->vstdate}|{$item->vsttime_short}";
                });

            // Batch query for stm_ofc_csop (sys <> HD)
            $stm_csop_data = DB::table('stm_ofc_csop')
                ->whereIn('hn', $hns)
                ->whereIn('vstdate', $vstdates)
                ->where('sys', '<>', 'HD')
                ->select('hn', 'vstdate', DB::raw('LEFT(vsttime, 5) as vsttime_short'), DB::raw('SUM(amount) as total_amount'))
                ->groupBy('hn', 'vstdate', 'vsttime_short')
                ->get()
                ->keyBy(function($item) {
                    return "{$item->hn}|{$item->vstdate}|{$item->vsttime_short}";
                });

            // Batch query for stm_ofc_csop (sys == HD)
            $stm_csop_hd_data = DB::table('stm_ofc_csop')
                ->whereIn('hn', $hns)
                ->whereIn('vstdate', $vstdates)
                ->where('sys', 'HD')
                ->select('hn', 'vstdate', DB::raw('SUM(amount) as total_amount'))
                ->groupBy('hn', 'vstdate')
                ->get()
                ->keyBy(function($item) {
                    return "{$item->hn}|{$item->vstdate}";
                });

            foreach ($rows as $row) {
                $vsttime_short = substr($row->vsttime, 0, 5);
                $key_short = "{$row->hn}|{$row->vstdate}|{$vsttime_short}";
                $key_date = "{$row->hn}|{$row->vstdate}";

                $stm1 = isset($stm_ofc_data[$key_short]) ? (float)$stm_ofc_data[$key_short]->total_receive : 0.0;
                $stm2 = isset($stm_csop_data[$key_short]) ? (float)$stm_csop_data[$key_short]->total_amount : 0.0;
                $stm3 = 0.0;
                if ($row->kidney > 0) {
                    $stm3 = isset($stm_csop_hd_data[$key_date]) ? (float)$stm_csop_hd_data[$key_date]->total_amount : 0.0;
                }

                $balance = ((float)$row->receive + $stm1 + $stm2 + $stm3) - (float)$row->debtor;
                $adj_val = 0 - $balance;
                if ($adj_val > 0) {
                    $row->adj_inc = $adj_val;
                    $row->adj_dec = 0;
                } else {
                    $row->adj_inc = 0;
                    $row->adj_dec = abs($adj_val);
                }
                $row->adj_date = $date;
                $row->adj_note = $note;
                $row->save();
                $adjusted_count++;
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'adjusted_count' => $adjusted_count,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว'
            ]);
        }

        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_501(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_501
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_501
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_501_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_501.pdf');
        }

        return abort(404);
    }

    public function _1102050101_501_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_501::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();
        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;
            if ($diff >= 0) {
                $row->adj_inc = $diff;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($diff);
            }
            $row->adj_date = $date;
            $row->adj_note = $note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'adjusted_count' => $adjusted_count,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว'
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_503(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_503
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_503
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_503_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_503.pdf');
        }

        return abort(404);
    }

    public function _1102050101_503_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_503::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();
        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;
            if ($diff >= 0) {
                $row->adj_inc = $diff;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($diff);
            }
            $row->adj_date = $date;
            $row->adj_note = $note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'adjusted_count' => $adjusted_count,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว'
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_701(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_701
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_701
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_701_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_701.pdf');
        }

        return abort(404);
    }

    public function _1102050101_701_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_701::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();
        foreach ($rows as $row) {
            $balance = (float)$row->receive - (float)$row->debtor;
            $adj_val = 0 - $balance;
            if ($adj_val > 0) {
                $row->adj_inc = $adj_val;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($adj_val);
            }
            $row->adj_date = $date;
            $row->adj_note = $note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'adjusted_count' => $adjusted_count,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว'
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_702(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_702
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_702
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_702_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_702.pdf');
        }

        return abort(404);
    }

    public function _1102050101_702_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด']);
            }
            return back()->with('error', 'กรุณาเลือกรายการที่ต้องการปรับปรุงยอด');
        }
        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050101_702::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();
        foreach ($rows as $row) {
            $balance = (float)$row->receive - (float)$row->debtor;
            $adj_val = 0 - $balance;
            if ($adj_val > 0) {
                $row->adj_inc = $adj_val;
                $row->adj_dec = 0;
            } else {
                $row->adj_inc = 0;
                $row->adj_dec = abs($adj_val);
            }
            $row->adj_date = $date;
            $row->adj_note = $note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'adjusted_count' => $adjusted_count,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว'
            ]);
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_106(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_106
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_106
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_106_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_106.pdf');
        }

        return abort(404);
    }

    public function _1102050102_106_bulk_adj(Request $request)
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

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids);

        $rows = DB::connection('hosxp')->select("
            SELECT d.vn, d.debtor, d.receive, d.rcpt_money, d.debtor_lock,
                   IFNULL(r.total_amount,0) - IFNULL(d.rcpt_money,0) AS total_bill
            FROM hrims.debtor_1102050102_106 d
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS total_amount
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                  AND r.vn IN ($placeholders)
                GROUP BY r.vn
            ) r ON r.vn = d.vn
            WHERE d.vn IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock == 'Y') {
                $receive = (float)$row->receive + (float)$row->total_bill;

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

                \App\Models\Debtor_1102050102_106::where('vn', $row->vn)->update($update_data);
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
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_108(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_108
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_108
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_108_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_108.pdf');
        }

        return abort(404);
    }

    public function _1102050102_108_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050102_108::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;

            $diff = (float)$row->debtor - (float)$receive;
            $row->adj_inc = $diff > 0 ? $diff : 0;
            $row->adj_dec = $diff < 0 ? abs($diff) : 0;
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
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

    public function _1102050102_110(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_110
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_110
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_110_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_110.pdf');
        }

        return abort(404);
    }

    public function _1102050102_110_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids, $ids, $ids, $ids, $ids, $ids);

        $rows = DB::select("
            SELECT d.vn, d.debtor, d.debtor_lock,
                   (
                       IFNULL(d.receive, 0)
                       + IFNULL(stm.receive_total, 0)
                       + IFNULL(k.receive_total, 0)
                       + IFNULL(srt.receive_total, 0)
                       + IFNULL(csop.receive_total, 0)
                       + CASE WHEN d.kidney > 0 THEN IFNULL(hd.receive_total, 0) ELSE 0 END
                       + IFNULL(pvt.receive_total, 0)
                   ) AS receive
            FROM debtor_1102050102_110 d
            LEFT JOIN (
                SELECT stm.hn, DATE(stm.datetimeadm) AS vstdate, SUM(stm.receive_total) AS receive_total
                FROM stm_bmt stm
                JOIN debtor_1102050102_110 d2 ON d2.hn = stm.hn AND d2.vstdate = DATE(stm.datetimeadm)
                WHERE d2.vn IN ($placeholders) AND SUBSTRING(stm.stm_filename, 11) LIKE 'O%'
                GROUP BY stm.hn, DATE(stm.datetimeadm)
            ) stm ON stm.hn = d.hn AND stm.vstdate = d.vstdate
            LEFT JOIN (
                SELECT k.hn, DATE(k.datetimeadm) AS vstdate, SUM(k.receive_total) AS receive_total
                FROM stm_bmt_kidney k
                JOIN debtor_1102050102_110 d2 ON d2.hn = k.hn AND d2.vstdate = DATE(k.datetimeadm)
                WHERE d2.vn IN ($placeholders)
                GROUP BY k.hn, DATE(k.datetimeadm)
            ) k ON k.hn = d.hn AND k.vstdate = d.vstdate
            LEFT JOIN (
                SELECT srt.hn, srt.vstdate, SUM(srt.receive_total) AS receive_total
                FROM stm_srt srt
                JOIN debtor_1102050102_110 d2 ON d2.hn = srt.hn AND d2.vstdate = srt.vstdate
                WHERE d2.vn IN ($placeholders)
                GROUP BY srt.hn, srt.vstdate
            ) srt ON srt.hn = d.hn AND srt.vstdate = d.vstdate
            LEFT JOIN (
                SELECT csop.hn, csop.vstdate, LEFT(csop.vsttime, 5) AS vsttime, SUM(csop.amount) AS receive_total
                FROM stm_ofc_csop csop
                JOIN debtor_1102050102_110 d2 ON d2.hn = csop.hn AND d2.vstdate = csop.vstdate AND LEFT(d2.vsttime, 5) = LEFT(csop.vsttime, 5)
                WHERE d2.vn IN ($placeholders) AND csop.sys <> 'HD'
                GROUP BY csop.hn, csop.vstdate, LEFT(csop.vsttime, 5)
            ) csop ON csop.hn = d.hn AND csop.vstdate = d.vstdate AND csop.vsttime = LEFT(d.vsttime, 5)
            LEFT JOIN (
                SELECT hd.hn, hd.vstdate, SUM(hd.amount) AS receive_total
                FROM stm_ofc_csop hd
                JOIN debtor_1102050102_110 d2 ON d2.hn = hd.hn AND d2.vstdate = hd.vstdate
                WHERE d2.vn IN ($placeholders) AND hd.sys = 'HD'
                GROUP BY hd.hn, hd.vstdate
            ) hd ON hd.hn = d.hn AND hd.vstdate = d.vstdate
            LEFT JOIN (
                SELECT pvt.hn, pvt.vstdate, LEFT(pvt.vsttime, 5) AS vsttime, SUM(pvt.receive_total) AS receive_total
                FROM stm_pvt pvt
                JOIN debtor_1102050102_110 d2 ON d2.hn = pvt.hn AND d2.vstdate = pvt.vstdate AND LEFT(d2.vsttime, 5) = LEFT(pvt.vsttime, 5)
                WHERE d2.vn IN ($placeholders)
                GROUP BY pvt.hn, pvt.vstdate, LEFT(pvt.vsttime, 5)
            ) pvt ON pvt.hn = d.hn AND pvt.vstdate = d.vstdate AND pvt.vsttime = LEFT(d.vsttime, 5)
            WHERE d.vn IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock === 'Y') {
                $receive = (float)$row->receive;
                $diff = (float)$row->debtor - $receive;

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

                \App\Models\Debtor_1102050102_110::where('vn', $row->vn)->update($update_data);
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
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_602(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_602
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_602
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_602_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_602.pdf');
        }

        return abort(404);
    }

    public function _1102050102_602_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050102_602::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;

            $diff = (float)$row->debtor - (float)$receive;
            $row->adj_inc = $diff > 0 ? $diff : 0;
            $row->adj_dec = $diff < 0 ? abs($diff) : 0;
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_801(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_801
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_801
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_801_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_801.pdf');
        }

        return abort(404);
    }

    public function _1102050102_801_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050102_801::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        $cids = $rows->pluck('cid')->unique()->filter()->toArray();
        $vstdates = $rows->pluck('vstdate')->unique()->filter()->toArray();

        $stm_lgo_data = [];
        if (!empty($cids) && !empty($vstdates)) {
            $stm_lgo_data = \DB::table('stm_lgo')
                ->whereIn('cid', $cids)
                ->whereIn('vstdate', $vstdates)
                ->get()
                ->groupBy(function($item) {
                    return $item->cid . '_' . $item->vstdate . '_' . substr($item->vsttime, 0, 5);
                });
        }

        $stm_kidney_data = [];
        if (!empty($cids) && !empty($vstdates)) {
            $stm_kidney_data = \DB::table('stm_lgo_kidney')
                ->whereIn('cid', $cids)
                ->whereIn('datetimeadm', $vstdates)
                ->get()
                ->groupBy(function($item) {
                    return $item->cid . '_' . $item->datetimeadm;
                });
        }

        foreach ($rows as $row) {
            $key = $row->cid . '_' . $row->vstdate . '_' . substr($row->vsttime, 0, 5);
            $key_kidney = $row->cid . '_' . $row->vstdate;

            $stm_lgo = isset($stm_lgo_data[$key]) ? $stm_lgo_data[$key]->sum('compensate_treatment') : 0;
            $stm_kidney = 0;
            if ($row->kidney > 0) {
                $stm_kidney = isset($stm_kidney_data[$key_kidney]) ? $stm_kidney_data[$key_kidney]->sum('receive_total') : 0;
            }

            $receive = (float)$row->receive + (float)$stm_lgo + (float)$stm_kidney;

            $diff = (float)$row->debtor - (float)$receive;
            $row->adj_inc = $diff > 0 ? $diff : 0;
            $row->adj_dec = $diff < 0 ? abs($diff) : 0;
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050102_803(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_803
        $data = DB::select("
            SELECT hn, vn, ptname, vstdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_803
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_803_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_803.pdf');
        }

        return abort(404);
    }

    public function _1102050102_803_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $rows = \App\Models\Debtor_1102050102_803::whereIn('vn', $ids)->where('debtor_lock', 'Y')->get();

        $hns = $rows->pluck('hn')->unique()->filter()->toArray();
        $vstdates = $rows->pluck('vstdate')->unique()->filter()->toArray();

        $stm_bkk_data = [];
        if (!empty($hns) && !empty($vstdates)) {
            $stm_bkk_data = DB::table('stm_bkk')
                ->whereIn('hn', $hns)
                ->whereIn('vstdate', $vstdates)
                ->get()
                ->groupBy(function($item) {
                    return $item->hn . '_' . $item->vstdate . '_' . substr($item->vsttime, 0, 5);
                });
        }

        $stm_bkk_kidney_data = [];
        if (!empty($hns) && !empty($vstdates)) {
            $stm_bkk_kidney_data = DB::table('stm_bkk_kidney')
                ->whereIn('hn', $hns)
                ->whereIn(DB::raw('DATE(datetimeadm)'), $vstdates)
                ->get()
                ->groupBy(function($item) {
                    return $item->hn . '_' . date('Y-m-d', strtotime($item->datetimeadm));
                });
        }

        foreach ($rows as $row) {
            $key = $row->hn . '_' . $row->vstdate . '_' . substr($row->vsttime, 0, 5);
            $key_kidney = $row->hn . '_' . $row->vstdate;

            $stm_bkk_val = isset($stm_bkk_data[$key]) ? $stm_bkk_data[$key]->sum('receive_total') : 0;
            $stm_kidney_val = 0;
            if ($row->kidney > 0) {
                $stm_kidney_val = isset($stm_bkk_kidney_data[$key_kidney]) ? $stm_bkk_kidney_data[$key_kidney]->sum('receive_total') : 0;
            }

            $receive = (float)$row->receive + (float)$stm_bkk_val + (float)$stm_kidney_val;

            $diff = (float)$row->debtor - (float)$receive;
            $row->adj_inc = $diff > 0 ? $diff : 0;
            $row->adj_dec = $diff < 0 ? abs($diff) : 0;
            $row->adj_date = $adj_date;
            $row->adj_note = $adj_note;
            $row->save();
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_202(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_202
        $data = DB::select("
            SELECT d.hn, d.an, d.ptname, d.dchdate, d.pttype, d.pdx, d.debtor, 
                   (IFNULL(stm.receive_total, 0) + IFNULL(d.receive, 0)) AS receive, 
                   d.adj_inc, d.adj_dec, d.adj_date, d.adj_note
            FROM debtor_1102050101_202 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM stm_ucs
                GROUP BY an
            ) stm ON stm.an = d.an
            WHERE d.adj_date BETWEEN ? AND ?
              AND (d.adj_inc > 0 OR d.adj_dec > 0)
            ORDER BY d.adj_date ASC, d.an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_202_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_202.pdf');
        }

        return abort(404);
    }

    public function _1102050101_202_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids);

        $rows = DB::select("
            SELECT d.an, d.debtor, d.receive, d.debtor_lock, stm.receive_total
            FROM debtor_1102050101_202 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM stm_ucs
                WHERE an IN ($placeholders)
                GROUP BY an
            ) stm ON stm.an = d.an
            WHERE d.an IN ($placeholders)
              AND d.debtor_lock = 'Y'
        ", $params);

        foreach ($rows as $row) {
            $total_received = (float)($row->receive_total ?? 0) + (float)($row->receive ?? 0);
            $diff = (float)$row->debtor - $total_received;
            
            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_202::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_217(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_217
        $data = DB::select("
            SELECT d.hn, d.an, d.ptname, d.dchdate, d.pttype, d.pdx, d.debtor, 
                   ((IFNULL(stm.receive_total, 0) - IFNULL(stm.receive_ip_compensate_pay, 0)) + IFNULL(k.receive_total, 0) + IFNULL(d.receive, 0)) AS receive, 
                   d.adj_inc, d.adj_dec, d.adj_date, d.adj_note
            FROM debtor_1102050101_217 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total, SUM(receive_ip_compensate_pay) AS receive_ip_compensate_pay
                FROM stm_ucs
                GROUP BY an
            ) stm ON stm.an = d.an
            LEFT JOIN (
                SELECT d2.an, SUM(sk.receive_total) AS receive_total
                FROM debtor_1102050101_217 d2
                JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.adj_date BETWEEN ? AND ?
                GROUP BY d2.an
            ) k ON k.an = d.an
            WHERE d.adj_date BETWEEN ? AND ?
              AND (d.adj_inc > 0 OR d.adj_dec > 0)
            ORDER BY d.adj_date ASC, d.an ASC
        ", [$start_date, $end_date, $start_date, $end_date]);

        if ($export_type === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        if ($export_type === 'pdf') {
            $hospital_name = DB::table('main_setting')->where('name', 'hospital_name')->value('value') ?: 'โรงพยาบาล';
            $hospital_code = DB::table('main_setting')->where('name', 'hospital_code')->value('value') ?: '';
            
            $pdf = PDF::loadView('debtor_adj.1102050101_217_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_217.pdf');
        }

        return abort(404);
    }

    public function _1102050101_217_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids, $ids);

        $rows = DB::select("
            SELECT d.an, d.debtor, d.receive, d.debtor_lock,
                   (IFNULL(stm.stm_total,0) + IFNULL(k.kidney_total,0)) AS stm_receive
            FROM debtor_1102050101_217 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) - SUM(receive_ip_compensate_pay) AS stm_total
                FROM stm_ucs
                WHERE an IN ($placeholders)
                GROUP BY an
            ) stm ON stm.an = d.an
            LEFT JOIN (
                SELECT d2.an, SUM(sk.receive_total) AS kidney_total
                FROM debtor_1102050101_217 d2
                JOIN stm_ucs_kidney sk ON sk.cid = d2.cid AND sk.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.an IN ($placeholders)
                GROUP BY d2.an
            ) k ON k.an = d.an
            WHERE d.an IN ($placeholders)
              AND d.debtor_lock = 'Y'
        ", $params);

        foreach ($rows as $row) {
            $total_received = (float)($row->stm_receive ?? 0) + (float)($row->receive ?? 0);
            $diff = (float)$row->debtor - $total_received;
            
            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_217::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_302(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_302
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_302
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_302_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_302.pdf');
        }

        return abort(404);
    }

    public function _1102050101_302_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $rows = \App\Models\Debtor_1102050101_302::whereIn('an', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            $row->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_304(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_304
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_304
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_304_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_304.pdf');
        }

        return abort(404);
    }

    public function _1102050101_304_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $rows = \App\Models\Debtor_1102050101_304::whereIn('an', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            $row->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_308(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_308
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_308
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_308_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_308.pdf');
        }

        return abort(404);
    }

    public function _1102050101_308_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $rows = \App\Models\Debtor_1102050101_308::whereIn('an', $ids)->where('debtor_lock', 'Y')->get();

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - (float)$receive;
            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            $row->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_310(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_310 (including stm_receive)
        $data = DB::select("
            SELECT d.hn, d.an, d.ptname, d.dchdate, d.pttype, d.pdx, d.debtor,
                   (IFNULL(d.receive, 0) + IFNULL(
                       (SELECT SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0))
                        FROM stm_sss_kidney s
                        WHERE s.hn = d.hn AND s.vstdate BETWEEN d.regdate AND d.dchdate AND s.hreg = s.hcode
                       ), 0
                   )) AS receive,
                   d.adj_inc, d.adj_dec, d.adj_date, d.adj_note
            FROM debtor_1102050101_310 d
            WHERE d.adj_date BETWEEN ? AND ?
              AND (d.adj_inc > 0 OR d.adj_dec > 0)
            ORDER BY d.adj_date ASC, d.an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_310_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_310.pdf');
        }

        return abort(404);
    }

    public function _1102050101_310_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::select("
            SELECT d.an, d.hn, d.regdate, d.dchdate, d.debtor, d.receive, d.debtor_lock,
                   (
                       SELECT SUM(IFNULL(s.amount,0) + IFNULL(s.epopay,0) + IFNULL(s.epoadm,0))
                       FROM stm_sss_kidney s
                       WHERE s.hn = d.hn AND s.vstdate BETWEEN d.regdate AND d.dchdate AND s.hreg = s.hcode
                   ) AS stm_receive
            FROM debtor_1102050101_310 d
            WHERE d.an IN ($placeholders)
              AND d.debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $total_receive = (float)$row->receive + (float)($row->stm_receive ?? 0);
            $diff = (float)$row->debtor - $total_receive;

            if ($diff > 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_310::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ',
                'adjusted_count' => $adjusted_count
            ]);
        }

        if ($adjusted_count == 0) {
            return back()->with('warning', 'ไม่มีรายการที่ต้องปรับปรุง (ยอดคงเหลือเป็น 0 หรือ ยังไม่ได้ Lock)');
        }
        return back()->with('success', 'ปรับปรุงยอดเรียบร้อยแล้ว ' . $adjusted_count . ' รายการ');
    }

    public function _1102050101_402(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_402 (including stm settings)
        $data = DB::select("
            SELECT d.hn, d.an, d.ptname, d.dchdate, d.pttype, d.pdx, d.debtor, d.adj_inc, d.adj_dec, d.adj_date, d.adj_note,
                   (IFNULL(d.receive, 0) 
                    + IFNULL((SELECT SUM(receive_total) FROM stm_ofc WHERE an = d.an), 0)
                    + IFNULL((SELECT SUM(gtotal) FROM stm_ofc_cipn WHERE an = d.an), 0)
                    + CASE WHEN d.kidney > 0 THEN IFNULL((
                        SELECT SUM(c.amount) 
                        FROM stm_ofc_csop c 
                        WHERE c.hn = d.hn AND c.vstdate BETWEEN d.regdate AND d.dchdate
                      ), 0) ELSE 0 END
                   ) AS receive
            FROM debtor_1102050101_402 d
            WHERE d.adj_date BETWEEN ? AND ?
              AND (d.adj_inc > 0 OR d.adj_dec > 0)
            ORDER BY d.adj_date ASC, d.an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_402_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_402.pdf');
        }

        return abort(404);
    }

    public function _1102050101_402_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adjusted_count = 0;
        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::select("
            SELECT d.an, d.hn, d.regdate, d.dchdate, d.debtor, d.receive AS receive_manual, d.debtor_lock, d.kidney,
                   (SELECT SUM(s.receive_total) FROM stm_ofc s WHERE s.an = d.an) AS stm_val,
                   (SELECT SUM(c.gtotal) FROM stm_ofc_cipn c WHERE c.an = d.an) AS cipn_val,
                   (
                       SELECT SUM(cs.amount) 
                       FROM stm_ofc_csop cs 
                       WHERE cs.hn = d.hn AND cs.vstdate BETWEEN d.regdate AND d.dchdate
                   ) AS csop_val
            FROM debtor_1102050101_402 d
            WHERE d.an IN ($placeholders)
              AND d.debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $total_received = (float)($row->receive_manual ?? 0) 
                + (float)($row->stm_val ?? 0) 
                + (float)($row->cipn_val ?? 0) 
                + (float)($row->kidney > 0 ? ($row->csop_val ?? 0) : 0);
            $diff = (float)$row->debtor - $total_received;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_402::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
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

    public function _1102050101_502(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_502
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_502
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_502_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_502.pdf');
        }

        return abort(404);
    }

    public function _1102050101_502_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select("
            SELECT an, debtor, receive, debtor_lock
            FROM debtor_1102050101_502
            WHERE an IN ($placeholders)
              AND debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_502::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $date,
                'adj_note' => $note,
            ]);
            $adjusted_count++;
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

    public function _1102050101_504(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_504
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_504
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_504_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_504.pdf');
        }

        return abort(404);
    }

    public function _1102050101_504_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select("
            SELECT an, debtor, receive, debtor_lock
            FROM debtor_1102050101_504
            WHERE an IN ($placeholders)
              AND debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_504::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $date,
                'adj_note' => $note,
            ]);
            $adjusted_count++;
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
    }

    public function _1102050101_704(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050101_704
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050101_704
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050101_704_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_704.pdf');
        }

        return abort(404);
    }

    public function _1102050101_704_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select("
            SELECT an, debtor, receive, debtor_lock
            FROM debtor_1102050101_704
            WHERE an IN ($placeholders)
              AND debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050101_704::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $date,
                'adj_note' => $note,
            ]);
            $adjusted_count++;
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

    public function _1102050102_107(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_107
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_107
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_107_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_107.pdf');
        }

        return abort(404);
    }

    public function _1102050102_107_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids);

        $rows = DB::connection('hosxp')->select("
            SELECT d.an, d.debtor, d.receive, d.rcpt_money, d.debtor_lock,
                   IFNULL(r.total_amount,0) - IFNULL(d.rcpt_money,0) AS total_bill
            FROM hrims.debtor_1102050102_107 d
            LEFT JOIN (
                SELECT r.vn, SUM(r.total_amount) AS total_amount
                FROM rcpt_print r
                LEFT JOIN rcpt_abort a ON a.rcpno = r.rcpno
                WHERE a.rcpno IS NULL
                  AND r.vn IN ($placeholders)
                GROUP BY r.vn
            ) r ON r.vn = d.an
            WHERE d.an IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock == 'Y') {
                $receive = (float)$row->receive + (float)$row->total_bill;
                $diff = (float)$row->debtor - (float)$receive;
                $update_data = [
                    'adj_date' => $date,
                    'adj_note' => $note
                ];

                if ($diff > 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                \App\Models\Debtor_1102050102_107::where('an', $row->an)->update($update_data);
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

    public function _1102050102_109(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_109
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_109
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_109_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_109.pdf');
        }

        return abort(404);
    }

    public function _1102050102_109_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $adj_date = $request->bulk_adj_date ?: date('Y-m-d');
        $adj_note = $request->bulk_adj_note ?: 'ปรับปรุงยอดเป็น 0';
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select("
            SELECT an, debtor, receive, debtor_lock
            FROM debtor_1102050102_109
            WHERE an IN ($placeholders)
              AND debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050102_109::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $adj_date,
                'adj_note' => $adj_note,
            ]);
            $adjusted_count++;
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

    public function _1102050102_111(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_111
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_111
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_111_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_111.pdf');
        }

        return abort(404);
    }

    public function _1102050102_111_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids, $ids, $ids, $ids, $ids);

        $rows = DB::select("
            SELECT d.an, d.debtor, d.receive AS receive_manual, d.debtor_lock,
                   IFNULL(stm.receive_total, 0) AS stm_val,
                   IFNULL(k.receive_total, 0) AS kidney_val,
                   IFNULL(srt.receive_total, 0) AS srt_val,
                   IFNULL(cipn.receive_total, 0) AS cipn_val,
                   IFNULL(pvt.receive_total, 0) AS pvt_val
            FROM debtor_1102050102_111 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM hrims.stm_bmt
                WHERE an IN ($placeholders)
                GROUP BY an
            ) stm ON stm.an = d.an
            LEFT JOIN (
                SELECT d2.an, SUM(c.receive_total) AS receive_total
                FROM debtor_1102050102_111 d2
                JOIN hrims.stm_bmt_kidney c ON c.hn = d2.hn AND c.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.an IN ($placeholders)
                GROUP BY d2.an
            ) k ON k.an = d.an
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM hrims.stm_srt
                WHERE an IN ($placeholders)
                GROUP BY an
            ) srt ON srt.an = d.an
            LEFT JOIN (
                SELECT an, SUM(gtotal) AS receive_total
                FROM hrims.stm_ofc_cipn
                WHERE an IN ($placeholders)
                GROUP BY an
            ) cipn ON cipn.an = d.an
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM hrims.stm_pvt
                WHERE an IN ($placeholders)
                GROUP BY an
            ) pvt ON pvt.an = d.an
            WHERE d.an IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock == 'Y') {
                $receive = (float)($row->receive_manual ?? 0)
                    + (float)($row->stm_val ?? 0)
                    + (float)($row->kidney_val ?? 0)
                    + (float)($row->srt_val ?? 0)
                    + (float)($row->cipn_val ?? 0)
                    + (float)($row->pvt_val ?? 0);

                $diff = (float)$row->debtor - (float)$receive;
                $update_data = [
                    'adj_date' => $date,
                    'adj_note' => $note
                ];

                if ($diff > 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                \App\Models\Debtor_1102050102_111::where('an', $row->an)->update($update_data);
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

    public function _1102050102_603(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_603
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_603
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_603_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_603.pdf');
        }

        return abort(404);
    }

    public function _1102050102_603_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::select("
            SELECT an, debtor, receive, debtor_lock
            FROM debtor_1102050102_603
            WHERE an IN ($placeholders)
              AND debtor_lock = 'Y'
        ", $ids);

        foreach ($rows as $row) {
            $receive = (float)$row->receive;
            $diff = (float)$row->debtor - $receive;

            if ($diff >= 0) {
                $adj_inc = $diff;
                $adj_dec = 0;
            } else {
                $adj_inc = 0;
                $adj_dec = abs($diff);
            }

            \App\Models\Debtor_1102050102_603::where('an', $row->an)->update([
                'adj_inc' => $adj_inc,
                'adj_dec' => $adj_dec,
                'adj_date' => $date,
                'adj_note' => $note,
            ]);
            $adjusted_count++;
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

    public function _1102050102_802(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_802
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_802
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_802_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_802.pdf');
        }

        return abort(404);
    }

    public function _1102050102_802_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids, $ids);

        $rows = DB::select("
            SELECT d.an, d.debtor, d.receive AS receive_manual, d.debtor_lock,
                   IFNULL(st.total, 0) AS lgo_val,
                   IFNULL(sk.amount, 0) AS kidney_val
            FROM debtor_1102050102_802 d
            LEFT JOIN (
                SELECT an, SUM(compensate_treatment) AS total
                FROM stm_lgo
                WHERE an IN ($placeholders)
                GROUP BY an
            ) st ON st.an = d.an
            LEFT JOIN (
                SELECT d2.an, SUM(k.compensate_kidney) AS amount
                FROM debtor_1102050102_802 d2
                JOIN stm_lgo_kidney k ON k.cid = d2.cid AND k.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.an IN ($placeholders)
                GROUP BY d2.an
            ) sk ON sk.an = d.an
            WHERE d.an IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock == 'Y') {
                $receive = (float)($row->receive_manual ?? 0)
                    + (float)($row->lgo_val ?? 0)
                    + (float)($row->kidney_val ?? 0);

                $diff = (float)$row->debtor - (float)$receive;
                $update_data = [
                    'adj_date' => $date,
                    'adj_note' => $note
                ];

                if ($diff > 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                \App\Models\Debtor_1102050102_802::where('an', $row->an)->update($update_data);
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

    public function _1102050102_804(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-01');
        $end_date = $request->input('end_date') ?: date('Y-m-t');
        $export_type = $request->input('export_type'); // 'json', 'pdf'

        // Query adjustments from debtor_1102050102_804
        $data = DB::select("
            SELECT hn, an, ptname, dchdate, pttype, pdx, debtor, receive, adj_inc, adj_dec, adj_date, adj_note
            FROM debtor_1102050102_804
            WHERE adj_date BETWEEN ? AND ?
              AND (adj_inc > 0 OR adj_dec > 0)
            ORDER BY adj_date ASC, an ASC
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
            
            $pdf = PDF::loadView('debtor_adj.1102050102_804_pdf', compact('data', 'start_date', 'end_date', 'hospital_name', 'hospital_code'))
                ->setPaper('a4', 'portrait');
            return $pdf->stream('adjustment_report_804.pdf');
        }

        return abort(404);
    }

    public function _1102050102_804_bulk_adj(Request $request)
    {
        $ids = $request->checkbox_d ?: [];
        if (empty($ids)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'กรุณาเลือกรายการ']);
            }
            return back()->with('error', 'กรุณาเลือกรายการ');
        }

        $note = $request->bulk_adj_note ?: 'Bulk Adjustment to Balance 0';
        $date = $request->bulk_adj_date ?: date('Y-m-d');
        $adjusted_count = 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, $ids, $ids);

        $rows = DB::select("
            SELECT d.an, d.debtor, d.receive AS receive_manual, d.debtor_lock,
                   IFNULL(stm.receive_total, 0) AS stm_val,
                   IFNULL(k.receive_total, 0) AS kidney_val
            FROM debtor_1102050102_804 d
            LEFT JOIN (
                SELECT an, SUM(receive_total) AS receive_total
                FROM hrims.stm_bkk
                WHERE an IN ($placeholders)
                GROUP BY an
            ) stm ON stm.an = d.an
            LEFT JOIN (
                SELECT d2.an, SUM(c.receive_total) AS receive_total
                FROM debtor_1102050102_804 d2
                JOIN hrims.stm_bkk_kidney c ON c.hn = d2.hn AND c.datetimeadm BETWEEN d2.regdate AND d2.dchdate
                WHERE d2.an IN ($placeholders)
                GROUP BY d2.an
            ) k ON k.an = d.an
            WHERE d.an IN ($placeholders)
        ", $params);

        foreach ($rows as $row) {
            if ($row && $row->debtor_lock == 'Y') {
                $receive = (float)($row->receive_manual ?? 0)
                    + (float)($row->stm_val ?? 0)
                    + (float)($row->kidney_val ?? 0);

                $diff = (float)$row->debtor - (float)$receive;
                $update_data = [
                    'adj_date' => $date,
                    'adj_note' => $note
                ];

                if ($diff > 0) {
                    $update_data['adj_inc'] = $diff;
                    $update_data['adj_dec'] = 0;
                } else {
                    $update_data['adj_inc'] = 0;
                    $update_data['adj_dec'] = abs($diff);
                }

                \App\Models\Debtor_1102050102_804::where('an', $row->an)->update($update_data);
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



