<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlArDebtor extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_ar_debtors';

    protected $fillable = [
        'account_code',
        'account_name',
        'debtor_type',
        'total_billed',
        'total_collected',
        'outstanding_balance',
        'fiscal_year',
        'fiscal_month',
    ];
}
