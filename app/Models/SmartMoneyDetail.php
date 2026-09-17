<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartMoneyDetail extends Model
{
    use HasFactory;

    protected $table = 'smart_money_details';
    protected $primaryKey = 'id';

    protected $fillable = [
        'batch_no',
        'round_no',
        'transfer_date',
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

    /**
     * Relationship with batch
     */
    public function batch()
    {
        return $this->belongsTo(SmartMoneyBatch::class, 'batch_no', 'batch_no');
    }
}
