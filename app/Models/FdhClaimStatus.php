<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FdhClaimStatus extends Model
{
    protected $table = 'fdh_claim_status';

    protected $primaryKey = 'id';

    // เปิดให้ mass assign ได้เฉพาะฟิลด์นี้
    protected $fillable = [
        'hn',
        'seq',
        'an',
        'hcode',
        'status',
        'process_status',
        'status_message_th',
        'stm_period',
        'transaction_id',
        'send_channel',
        'send_date',
        'send_user',
        'send_user_cid',
        'api_response',
    ];
}