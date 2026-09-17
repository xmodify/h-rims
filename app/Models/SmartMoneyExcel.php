<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartMoneyExcel extends Model
{
    use HasFactory;

    protected $table = 'smart_money_excel';
    protected $primaryKey = 'id';

    protected $fillable = [
        'file_type',
        'transfer_date',
        'batch_no',
        'round_no',
        'account_code',
        'fund_main',
        'fund_sub',
        'amount',
        'hold_amount',
        'deduct_amount',
        'guarantee_amount',
        'tax_amount',
        'remain_amount',
        'offset_amount',
        'net_amount',
        'hn',
        'an',
        'pt_type',
        'cid',
        'pt_name',
        'vstdate',
        'receive_total',
        'repno',
        'main_fund',
        'sub_fund',
        'sub_fund_desc',
        'hsend',
        'hcode',
        'seq_no',
        'invoice_no',
        'invoice_lt',
        'budget_year',
    ];
}
