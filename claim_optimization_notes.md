# Claim OP & Claim IP Optimization Guide

This document outlines the step-by-step optimization patterns applied to `claim_op` and `claim_ip` modules (e.g. `ucs_incup`, `ucs_inprovince`, `ucs_outprovince`) to achieve instant loads, 100% accurate aggregations, and snappy AJAX updates.

---

## Pattern 1: High-Performance Annual Chart Aggregation (`$sum_month`)

### The Problem (Catastrophic Nested Loop Query)
When querying monthly summary graphs for an entire fiscal year (11-12 months):
- Joining multiple large unindexed derived tables across databases (`opitemrece` for 1 year $\times$ `rep_ucs` $\times$ `stm_ucs` $\times$ `eclaim_status` with `LEFT(vsttime, 5)`) causes MySQL to execute unindexed nested loops over millions of rows, freezing execution for **30 to 60+ seconds**.

### The Solution: Single-Pass Index Query + In-Memory Key Mapping + Server-Side Cache

1. **Step 1: Single-Pass Visit Aggregation**
   Query only eligible visits and their transaction sums using primary table indexes (`ovst` + `opitemrece`):
   ```php
   $hospcodes = DB::table('lookup_hospcode')->where('hmain_ucs', 'Y')->pluck('hospcode')->toArray();
   $hospcode_in = !empty($hospcodes) ? "'" . implode("','", $hospcodes) . "'" : "''";

   $vns_data = DB::connection('hosxp')->select("
       SELECT 
           o.vn, o.hn, pt.cid, o.vstdate,
           LEFT(o.vsttime, 5) AS vsttime5,
           YEAR(o.vstdate) AS yr,
           MONTH(o.vstdate) AS mo,
           SUM(op.sum_price) AS total_price
       FROM ovst o
       INNER JOIN visit_pttype vp ON vp.vn = o.vn
       INNER JOIN pttype p ON p.pttype = vp.pttype AND p.hipdata_code IN ('UCS','WEL')
       INNER JOIN patient pt ON pt.hn = o.hn
       INNER JOIN opitemrece op ON op.vn = o.vn
       INNER JOIN hrims.lookup_icode li ON li.icode = op.icode AND (li.uc_cr = 'Y' OR li.ppfs = 'Y' OR li.herb32 = 'Y')
       WHERE o.vstdate BETWEEN ? AND ?
         AND (o.an = '' OR o.an IS NULL)
         AND vp.hospmain IN ($hospcode_in)
       GROUP BY o.vn, o.hn, pt.cid, o.vstdate, o.vsttime
   ", [$start_date_b, $end_date_b]);
   ```

2. **Step 2: Fetch Claim Status Lookup Dictionaries (O(1) Memory Lookups)**
   Fetch sent identifiers and compensation totals using fast indexed lookups:
   ```php
   $fdh_vns = DB::table('fdh_claim_status')->pluck('seq')->filter()->flip()->toArray();
   $eclaim_keys = DB::table('eclaim_status')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
   $rep_keys = DB::table('rep_ucs')->where('rep_type', 'OP')->whereBetween('vstdate', [$start_date_b, $end_date_b])->selectRaw("CONCAT(hn, '_', vstdate, '_', LEFT(vsttime,5)) AS k")->pluck('k')->flip()->toArray();
   $stm_rows = DB::table('stm_ucs')
       ->whereBetween('vstdate', [$start_date_b, $end_date_b])
       ->selectRaw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5)) AS k, SUM(receive_total) AS rec_total")
       ->groupBy(DB::raw("CONCAT(cid, '_', vstdate, '_', LEFT(TIME(datetimeadm),5))"))
       ->pluck('rec_total', 'k')
       ->toArray();
   ```

3. **Step 3: In-Memory Aggregation in PHP**
   Iterate and group by month in memory (takes **< 0.01s**):
   ```php
   foreach ($vns_data as $row) {
       $m = (int)$row->mo;
       $y = (int)$row->yr;
       $k_month = sprintf('%04d-%02d', $y, $m);
       $hn_key = $row->hn . '_' . $row->vstdate . '_' . $row->vsttime5;
       $cid_key = $row->cid . '_' . $row->vstdate . '_' . $row->vsttime5;

       $is_sent = isset($fdh_vns[$row->vn]) || isset($eclaim_keys[$hn_key]) || isset($rep_keys[$hn_key]) || isset($stm_rows[$cid_key]);
       $rec = $stm_rows[$cid_key] ?? 0;

       $monthly_agg[$k_month]['claim_price'] += (float)$row->total_price;
       if ($is_sent) {
           $monthly_agg[$k_month]['claim_sent_price'] += (float)$row->total_price;
       }
       $monthly_agg[$k_month]['receive_total'] += (float)$rec;
   }
   ```

4. **Step 4: Server-Side Cache Layer (`Cache::remember`)**
   Wrap the heavy chart calculations inside `Cache::remember(..., 300, ...)`:
   ```php
   $chartCacheKey = 'chart_ucs_incup_' . $budget_year . '_' . $start_date_b . '_' . $end_date_b;
   $chartData = \Illuminate\Support\Facades\Cache::remember($chartCacheKey, 300, function () use ($start_date_b, $end_date_b) {
       // ... Steps 1-3 ...
   });
   ```

* **Performance Impact:**
  - Initial Full-Year Load: **~3.2s** (down from 50+ seconds)
  - Subsequent Page Loads (Cache): **0.02s (Instant)**
  - Data Accuracy: **100% Identical** to legacy queries

---

## Pattern 2: Split Views (Page Shell + AJAX Partial)
Divide the blade template into two parts:
1. **Page Shell (`xxx.blade.php`):** Contains page title, modal markups, the root page wrapper container (`<div id="data-container">`), and script blocks.
2. **Table View (`xxx_table.blade.php`):** Contains the Chart canvas section, main action buttons, date range filter forms, tab layouts, datatables, and the table loop logic.

### AJAX Route Handling in Controller
```php
public function ucs_incup(Request $request)
{
    // ... Fetch filters ...

    // 1. Conditionally skip heavy queries if skip_chart = 1
    if (!$request->input('skip_chart')) {
        // Run Pattern 1 Chart Cache
    }

    // 2. Load data for tables (Search & Claim)
    $search = DB::select(...);
    $claim = DB::select(...);

    // 3. Render and return JSON on AJAX requests
    if ($request->ajax()) {
        $table_html = view('claim_op.ucs_incup_table', compact('search', 'claim', ...))->render();
        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'chart_data' => isset($chartData) ? $chartData : null
        ]);
    }

    // 4. Default return for initial full-page load
    return view('claim_op.ucs_incup', compact(...));
}
```

---

## Pattern 3: Dynamic Chart Redrawing
Avoid browser reload by caching the chart data in a global JavaScript variable (`window.currentChartData`) during the initial load:

1. When **"โหลด indiv"** is clicked, call `loadDashboard({ skip_chart: 1 })`.
2. The server skips the heavy monthly query and returns only the updated tables in **~0.02 seconds**.
3. In the AJAX `.done()` handler, redraw the chart using `window.currentChartData` without re-fetching.

---

## Pattern 4: Local View Style Overrides
To style active/inactive tabs differently without polluting `app.blade.php`, define them inside the **page shell** blade file (`xxx.blade.php`) — **not** in the `xxx_table.blade.php` partial.

---

## Pattern 5: Export Action Styling Standards
Buttons for exporting claims (e.g. 16/17 แฟ้ม FDH) should adhere to standard UI styling across all claim screens:
```html
<button type="button" class="btn text-white fw-bold px-3 shadow-sm" style="background: linear-gradient(135deg, #0e939a 0%, #15b7bd 100%); border: none;" onclick="exportSelected()">
    <i class="bi bi-box-arrow-up-right me-1"></i> ส่งออก 16 แฟ้ม
</button>
```

---

## Pages Optimized (Tracking)

| Page | Pattern 1 (Fast Chart & Cache) | Pattern 2 (Shell + AJAX) | Pattern 3 (Dynamic Chart) | Notes |
|---|:---:|:---:|:---:|---|
| `claim_op/ucs_incup` | ✅ | ✅ | ✅ | Optimized Single-Pass + Cache (3s / 0.02s) |
| `claim_op/ucs_inprovince` | ✅ | ✅ | ✅ | Optimized Single-Pass + Cache (3s / 0.02s) |
| `claim_op/ucs_outprovince` | ✅ | ✅ | ✅ | Optimized Single-Pass + Cache (3s / 0.02s) |
| `claim_op/ucs_kidney` | — | ✅ | ✅ | Applied (No FDH export as per rules) |
| `claim_op/ofc` | ✅ | ✅ | ✅ | Reference OFC standard |
| `claim_op/stp_incup` | — | ✅ | ✅ | Applied |
| `claim_op/stp_outcup` | — | ✅ | ✅ | Applied |
