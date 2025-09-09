<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050102_107 extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050102_107'; 
    protected $primaryKey = 'an';
    protected $fillable = [  
        'vn',
        'hn', 
        'an', 
        'cid',
        'ptname',
        'mobile_phone_number',
        'vstdate', 
        'vsttime',  
        'pttype',
        'hospmain',
        'hipdata_code',
        'pdx',
        'income',
        'paid_money',
        'rcpt_money',   
        'debtor', 
        'debtor_change',
        'status', 
        'receive',
        'repno',
        'debtor_lock',             
    ];
    public $timestamps = false;   
}
