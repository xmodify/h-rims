<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stm_ofc_cipn extends Model
{
    protected $table = 'stm_ofc_cipn';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        // เอกสาร
        'stm_filename',
        'round_no',
        'rid',

        // ผู้ป่วย
        'an',
        'namepat',
        'datedsc',
        'ptype',

        // DRG
        'drg',
        'adjrw',

        // ยอดเงิน
        'amreimb',
        'amlim',
        'pamreim',
        'gtotal',

        // ใบเสร็จ
        'receive_no',
        'receipt_date',
        'receipt_by',
    ];

    protected $casts = [
        'datedsc'       => 'date',
        'receipt_date' => 'date',

        'adjrw'    => 'decimal:4',
        'amreimb'  => 'decimal:2',
        'amlim'    => 'decimal:2',
        'pamreim'  => 'decimal:2',
        'gtotal'   => 'decimal:2',
    ];
}
