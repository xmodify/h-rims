<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_ofc_csop extends Model
{
    use HasFactory;

    protected $table = 'stm_ofc_csop'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'stm_filename',
        'round_no',
        'stm_type',
        'hcode',
        'hname',
        'acc_period',
        'sys',
        'station',
        'hreg',
        'hn',
        'pt_name',
        'vstdate',
        'vsttime',
        'invno',
        'amount',
        'paid',
        'extp_code',
        'extp_amount',
        'rid',
        'cstat',
        'hdflag',
        'receive_no',
        'receipt_date',
        'receipt_by',
    ];
    public $timestamps = false;   
}
