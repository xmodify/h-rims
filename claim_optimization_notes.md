# Claim OP & Claim IP Optimization Guide

This document outlines the step-by-step optimization pattern applied to `claim_op/ucs_incup` to achieve instant loads and snappy AJAX updates. Use this pattern when optimizing other Claim OP or Claim IP modules.

---

## Pattern 1: SQL Pre-Aggregation (`$sum_month`)
Instead of left-joining large transaction tables at the root level (which causes Cartesian duplicates and slows down aggregation), pre-aggregate first inside a subquery:

```sql
-- OPTIMIZED PATTERN
SELECT ...
FROM (
    -- Pre-aggregate core keys and sums first
    SELECT vn, SUM(uc_cr) as uc_cr, ...
    FROM opitemrece
    WHERE ...
    GROUP BY vn
) op
LEFT JOIN visit_pttype vp ON vp.vn = op.vn
LEFT JOIN patient pt ON pt.hn = vp.hn
...
```
* **Benefit:** Reduces rows processed before heavy joins are resolved, resulting in near-instant response times.

---

## Pattern 2: Split Views (Page Shell + AJAX Partial)
Divide the blade template into two parts:
1. **Page Shell (`xxx.blade.php`):** Contains page title, main action buttons, chart canvases, modal markups, and script blocks.
2. **Table View (`xxx_table.blade.php`):** Contains only the tab layouts, datatables, and the table loop logic.

### AJAX Route Handling in Controller
```php
public function ucs_incup(Request $request)
{
    // ... Fetch filters ...

    // 1. Conditionally skip heavy queries if skip_chart = 1
    if (!$request->input('skip_chart')) {
        $sum_month = DB::select(...); // Heavy query
    }

    // 2. Load data for tables
    $search = DB::select(...);
    $claim = DB::select(...);

    // 3. Render and return JSON on AJAX requests
    if ($request->ajax()) {
        $table_html = view('claim_op.ucs_incup_table', compact('search', 'claim', ...))->render();
        
        $patient_items = array_merge(
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $search),
            array_map(fn($row) => ['hn' => $row->hn, 'seq' => $row->seq], $claim)
        );

        return response()->json([
            'success' => true,
            'table_html' => $table_html,
            'patient_items' => $patient_items,
            'chart_data' => isset($sum_month) ? [
                'month' => $month,
                'claim_price' => $claim_price,
                ...
            ] : null
        ]);
    }

    // 4. Default return for initial full-page load
    return view('claim_op.ucs_incup', compact('sum_month', 'search', 'claim', ...));
}
```

---

## Pattern 3: Dynamic Chart Redrawing
Avoid browser reload by caching the chart data in a global JavaScript variable (`window.currentChartData`) during the initial load:

1. When **"โหลด indiv"** is clicked, call `loadDashboard({ skip_chart: 1 })`.
2. The server skips the heavy monthly query and returns only the updated tables.
3. In the AJAX `.done()` handler, check if the response contains new chart data. If it does, update `window.currentChartData`.
4. Call the global `drawChart(...)` function using `window.currentChartData`. This updates the tables instantly and redraws the chart in-place without refreshing the page.

---

## Pattern 4: Local View Style Overrides
To style active/inactive tabs differently without polluting `app.blade.php` or modifying global assets, define them inside the local page shell `<style>` section:

```css
<style>
/* Custom pastel styling for tabs */
#search-tab {
    background-color: #fef2f2 !important; /* Pastel Red */
    color: #dc2626 !important;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
}
#search-tab.active {
    background-color: #dc2626 !important;
    color: #fff !important;
}
#claim-tab {
    background-color: #f0fdf4 !important; /* Pastel Green */
    color: #166534 !important;
    border-radius: 8px 8px 0 0;
}
#claim-tab.active {
    background-color: #166534 !important;
    color: #fff !important;
}
</style>
```

---

## Pattern 5: Detailed JSON Modal Binding
When implementing modals like `showDetails(vn)`, return structured JSON from the controller and build the HTML content dynamically using a JavaScript template string. This prevents loading errors and allows you to bind events and sub-tables (like DataTables) cleanly inside the modal.
