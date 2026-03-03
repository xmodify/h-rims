<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EclaimStatus extends Model
{
    protected $connection = 'mysql';
    protected $table = 'eclaim_status';

    protected $fillable = [
        'hospcode',
        'eclaim_no',
        'patient_type',
        'hipdata',
        'cid',
        'ptname',
        'hn',
        'an',
        'vstdate',
        'vsttime',
        'dchdate',
        'dchtime',
        'status',
        'recorder',
        'tran_id',
        'net_charge',
        'claim_amount',
        'rep',
        'stm',
        'seq',
        'check_detail',
        'deny_warning',
        'channel',
    ];
}
