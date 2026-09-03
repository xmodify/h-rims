<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlApBill extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_ap_bills';

    protected $fillable = [
        'vendor_name',
        'category',
        'bill_no',
        'bill_date',
        'account_code',
        'account_name',
        'total_credit',
        'total_debit',
        'remaining_debt',
        'fiscal_year',
        'is_paid',
    ];

    protected $appends = [
        'parsed_bill_date',
        'thai_bill_date',
        'aging_days',
    ];

    /**
     * Parse the actual bill date into YYYY-MM-DD (A.D.)
     */
    public function getParsedBillDateAttribute()
    {
        // 1. Check if bill_no has dd/mm/yy or dd/mm/yyyy
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $this->bill_no ?? '', $m)) {
            $d = (int)$m[1];
            $mo = (int)$m[2];
            $y = (int)$m[3];
            $beYear = ($y < 100) ? (2500 + $y) : (($y > 2400) ? $y : ($y + 543));
            $adYear = $beYear - 543;
            if (checkdate($mo, $d, $adYear)) {
                return sprintf('%04d-%02d-%02d', $adYear, $mo, $d);
            }
        }

        // 2. Check stored bill_date
        if (!empty($this->bill_date)) {
            $parts = explode('-', $this->bill_date);
            if (count($parts) === 3) {
                $y = (int)$parts[0];
                $mo = (int)$parts[1];
                $d = (int)$parts[2];
                $adYear = ($y > 2400) ? ($y - 543) : $y;
                if ($mo >= 1 && $mo <= 12 && $d >= 1 && $d <= 31) {
                    return sprintf('%04d-%02d-%02d', $adYear, $mo, $d);
                }
            }
        }

        return null;
    }

    /**
     * Format the bill date in Thai (e.g. 10 เม.ย. 2567)
     */
    public function getThaiBillDateAttribute()
    {
        $parsed = $this->parsed_bill_date;
        if (!$parsed) {
            return '-';
        }

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];

        $parts = explode('-', $parsed);
        if (count($parts) === 3) {
            $d = (int)$parts[2];
            $mo = (int)$parts[1];
            $be = (int)$parts[0] + 543;
            return sprintf('%d %s %d', $d, $months[$mo] ?? '', $be);
        }

        return '-';
    }

    /**
     * Calculate days outstanding (aging) for unpaid bills
     */
    public function getAgingDaysAttribute()
    {
        if ($this->is_paid) {
            return 0;
        }

        $parsed = $this->parsed_bill_date;
        if (!$parsed) {
            return 0;
        }

        try {
            $billCarbon = \Carbon\Carbon::parse($parsed)->startOfDay();
            $now = \Carbon\Carbon::now()->startOfDay();
            $diff = $billCarbon->diffInDays($now, false);
            return $diff > 0 ? (int)$diff : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
