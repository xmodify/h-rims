<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartMoneyBatch extends Model
{
    use HasFactory;

    protected $table = 'smart_money_batches';
    protected $primaryKey = 'id';

    protected $fillable = [
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
        'file_name',
        'receive_no',
        'receipt_date',
        'receipt_by',
        'budget_year',
    ];

    /**
     * Relationship with individual details
     */
    public function details()
    {
        return $this->hasMany(SmartMoneyDetail::class, 'batch_no', 'batch_no');
    }
}
