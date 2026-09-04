<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlCostSummary extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_cost_summaries';

    protected $fillable = [
        'fiscal_year',
        'fiscal_month',
        'lc_amount',
        'mc_amount',
        'cc_amount',
        'other_cost',
        'total_cost',
    ];

    public function getAccPeriodAttribute()
    {
        $fy = intval($this->fiscal_year);
        $fm = intval($this->fiscal_month);
        if ($fm >= 1 && $fm <= 3) {
            $cMonth = $fm + 9;
            $cYear = $fy - 1;
        } else {
            $cMonth = $fm - 3;
            $cYear = $fy;
        }
        return sprintf('%04d-%02d', $cYear, $cMonth);
    }

    public function getMonthNameAttribute()
    {
        $months = ['', 'ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.'];
        return $months[intval($this->fiscal_month)] ?? '';
    }

    public function getPeriodLabelAttribute()
    {
        $fy = intval($this->fiscal_year);
        $fm = intval($this->fiscal_month);
        $cYear = ($fm >= 1 && $fm <= 3) ? ($fy - 1) : $fy;
        $shortYear = substr((string)$cYear, -2);
        return $this->month_name . ' ' . $shortYear;
    }
}
