<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050101_303 extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050101_303'; 
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
        'other',
        'debtor', 
        'charge_date',
        'charge_no',
        'charge',
        'debtor_change',
        'receive_date', 
        'receive_no',  
        'receive',
        'repno',
        'status', 
        'debtor_lock',             
    ];
    public $timestamps = false;   
}
