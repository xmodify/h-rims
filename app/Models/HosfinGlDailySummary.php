<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlDailySummary extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_daily_summaries';

    protected $fillable = [
        'summary_date',
        'fiscal_year',
        'total_income',
        'total_expense',
        'net_cash_flow',
        'cash_balance',
        'voucher_count',
    ];
}
