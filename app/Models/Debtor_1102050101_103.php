<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050101_103 extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050101_103'; 
    protected $primaryKey = 'vn';
    public $incrementing = false;
    protected $keyType = 'string';
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
        'debtor_change',
        'charge_date',
        'charge_no',
        'charge',
        'receive_date', 
        'receive_no',  
        'receive',
        'repno',
        'status', 
        'debtor_lock',
        'adj_inc',
        'adj_dec',
        'adj_date',
        'adj_note',
    ];
    public $timestamps = false;   
}
