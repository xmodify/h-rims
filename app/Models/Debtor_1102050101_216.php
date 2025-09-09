<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050101_216 extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050101_216'; 
    protected $primaryKey = 'vn';
    protected $fillable = [  
        'vn',
        'hn', 
        'an', 
        'cid',
        'ptname',
        'vstdate', 
        'vsttime',  
        'pttype',
        'hospmain',
        'hipdata_code',
        'pdx',
        'income',
        'rcpt_money',
        'kidney',
        'cr',
        'anywhere',
        'debtor',
        'debtor_change',
        'status', 
        'receive',
        'repno',
        'debtor_lock',             
    ];
    public $timestamps = false;   
}
