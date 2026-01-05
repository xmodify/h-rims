<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_lgo extends Model
{
    use HasFactory;

    protected $table = 'stm_lgo'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'round_no', 
        'repno',  
        'no', 
        'tran_id', 
        'hn', 
        'an',
        'cid',
        'pt_name',
        'dep',  
        'datetimeadm',
        'vstdate',
        'vsttime',
        'datetimedch',
        'dchdate',
        'dchtime',
        'compensate_treatment',
        'compensate_nhso',
        'error_code',
        'fund',
        'service_type',
        'refer',
        'have_rights',
        'use_rights',
        'main_rights',
        'secondary_rights',
        'href',
        'hcode',
        'prov1',
        'hospcode',
        'hospname',
        'proj',
        'pa',
        'drg',
        'rw',
        'proj',
        'charge_treatment',
        'charge_pp',
        'withdraw',
        'non_withdraw',
        'pay',
        'payrate',
        'delay',
        'delay_percent',
        'ccuf',
        'adjrw',
        'act',
        'case_iplg',
        'case_oplg',
        'case_palg',
        'case_inslg',
        'case_otlg',
        'case_pp',
        'case_drug',
        'deny_iplg',
        'deny_oplg',
        'deny_palg',
        'deny_inslg',
        'deny_otlg',
        'ors',
        'va',
        'audit_results',
        'stm_filename',
        'receive_no',
        'receipt_date',
        'receipt_by',
    ];
    public $timestamps = false;   
}
