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
1. **Page Shell (`xxx.blade.php`):** Contains page title, modal markups, the root page wrapper container (`<div id="data-container">`), and script blocks.
2. **Table View (`xxx_table.blade.php`):** Contains the Chart canvas section, main action buttons, date range filter forms, tab layouts, datatables, and the table loop logic. This ensures that on initial load, the entire page below the header is covered by a clean loading card, rather than showing a giant empty white space for the chart.

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

> [!TIP]
> **Avoid Popup Overlaps:** Do not use `Swal.fire` loading dialogs inside `loadDashboard()`. Instead, inject clean inline HTML spinner templates directly into the container placeholders (e.g. replacing `#myTabContent` with a small spinner when `skip_chart` is true, or replacing `#table-container` with a full card spinner when loading both chart and tables). This prevents overlapping modals on page load.

> [!IMPORTANT]
> **Legacy Button Handlers:** If your existing views have buttons with `onclick="fetchData()"` or references to legacy loader functions, define a dummy/fallback function inside your script block to prevent `ReferenceError: fetchData is not defined` from breaking JavaScript execution:
> ```javascript
> function fetchData() {
>     // Fallback for legacy onclick handlers. The form submission event listener 
>     // (e.g. $(document).on('submit', '#form_indiv', ...)) will intercept the submit 
>     // and invoke loadDashboard() automatically.
> }
> ```

> [!CAUTION]
> **Date Filtering on Year Change:**
> - When changing the budget year (submitting `#form_budget_year`), ONLY pass the new `budget_year` parameter to `loadDashboard()`. **Do not** pass the old `start_date` and `end_date` parameters. This allows the controller to reset the default dates for the new year.
> - In the AJAX `.done()` success handler, always extract the start/end dates directly from the server-rendered HTML hidden input fields (`$('#start_date').val()`), rather than relying on request parameters (`dataParams`). This prevents the datepicker inputs from becoming blank on year changes.

---

## Pattern 4: Local View Style Overrides
To style active/inactive tabs differently without polluting `app.blade.php` or modifying global assets, define them inside the **page shell** blade file (`xxx.blade.php`) — **not** in the `xxx_table.blade.php` partial. Place the `<style>` block right after `@section('content')`.

> [!IMPORTANT]
> The CSS **must** be in the page shell (`xxx.blade.php`), because the partial (`xxx_table.blade.php`) is injected via AJAX after the `<head>` has already been rendered. Styles placed inside the partial will not apply.

```css
<style>
/* Custom pastel background for main tabs */
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
    font-weight: 600;
}
#claim-tab.active {
    background-color: #166534 !important;
    color: #fff !important;
}
</style>
```

**Pages applied:** `ucs_incup.blade.php`, `ucs_outprovince.blade.php`

---

## Pattern 5: Detailed JSON Modal Binding
When implementing modals like `showDetails(vn)`, return structured JSON from the controller and build the HTML content dynamically using a JavaScript template string. This prevents loading errors and allows you to bind events and sub-tables (like DataTables) cleanly inside the modal.

> [!WARNING]
> **Status Check Accuracy:**
> - When determining if a visit has been successfully closed in NHSO (`endpointBtn`), always verify the computed property `v.endpoint_valid` from the validation payload rather than querying raw database fields like `visit.claimCode`.
> - Maintain uniform status badges and button designs (e.g. `pullNhsoData` and `checkFdh` actions) across all modules to ensure a consistent user experience.

---

## Pages Optimized (Tracking)

| Page | Pattern 2 (Shell + AJAX) | Pattern 3 (Chart Cache) | Pattern 4 (Tab Style) | Notes |
|---|:---:|:---:|:---:|---|
| `claim_op/ucs_incup` | ✅ | ✅ | ✅ | Reference implementation |
| `claim_op/ucs_outprovince` | ✅ | ✅ | ✅ | Applied 2026-07-16 |
| `claim_op/ucs_inprovince` | ✅ | ✅ | — | Has date-range filter (no tab style needed) |
| `claim_op/ucs_kidney` | ✅ | ✅ | ✅ | Applied 2026-07-16 |

> [!TIP]
> When optimizing a new module, apply patterns in order: **2 → 3 → 4**. Pattern 1 (SQL pre-aggregation) is optional but recommended for modules with large `opitemrece` joins.
