<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_seamless_dmis extends Model
{
    use HasFactory;

    protected $table = 'stm_seamless_dmis';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'hospcode',
        'pttype_name',
        'repno',
        'trans_id',
        'hn',
        'an',
        'cid',
        'ptname',
        'send_date',
        'vstdate',
        'claim_type_name',
        'qty',
        'price_unit',
        'price_ceiling',
        'claim_price',
        'ps_code',
        'pay_percent',
        'receive_total',
        'deny_code',
        'deny_warning',
        'rehab_code',
        'rehab_name',
        'sub_hospcode',
        'dmis_group',
        'excel_filename',
        'round_no',
        'receive_no',
        'receipt_date',
        'receipt_by',
    ];
}
