<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050102_111 extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050102_111'; 
    protected $primaryKey = 'an';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'an',   
        'vn',
        'hn', 
        'cid',
        'ptname',
        'regdate', 
        'regtime',
        'dchdate',
        'dchtime',      
        'pttype',
        'hospmain',
        'hipdata_code',
        'pdx',
        'adjrw',
        'income',
        'rcpt_money',
        'kidney',
        'other',
        'debtor',
        'debtor_change',
        'status', 
        'receive',
        'repno',
        'debtor_lock',               
    'adj_inc',
    'adj_dec',
    'adj_date',
    'adj_note',
        'charge_date',
        'charge_no',
        'charge',
        'receive_date',
        'receive_no',];
    public $timestamps = false;   
}
