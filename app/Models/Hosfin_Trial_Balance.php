<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hosfin_Trial_Balance extends Model
{
    use HasFactory;

    protected $table = 'hosfin_trial_balance';
    protected $primaryKey = 'id';
    protected $fillable = [
        'acc_year',
        'acc_month',
        'acc_period',
        'main_account_code',
        'account_code',
        'account_name',
        'debit_bf',
        'credit_bf',
        'debit_month',
        'credit_month',
        'debit_net',
        'credit_net',
        'import_filename',
    ];
}
