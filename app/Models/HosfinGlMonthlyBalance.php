<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlMonthlyBalance extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_monthly_balances';

    protected $fillable = [
        'fiscal_year',
        'fiscal_month',
        'account_code',
        'account_name',
        'account_type',
        'beginning_debit',
        'beginning_credit',
        'period_debit',
        'period_credit',
        'ending_debit',
        'ending_credit',
    ];

    public function account()
    {
        return $this->belongsTo(HosfinGlAccount::class, 'account_code', 'account_code');
    }
}
