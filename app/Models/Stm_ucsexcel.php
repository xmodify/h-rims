<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_ucsexcel extends Model
{
    use HasFactory;

    protected $table = 'stm_ucsexcel'; 
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
        'datetimeadm',
        'vstdate',
        'vsttime',
        'datetimedch',
        'dchdate',
        'dchtime',
        'maininscl',
        'projcode',
        'charge',
        'fund_ip_act',
        'fund_ip_adjrw',
        'fund_ip_ps',
        'fund_ip_ps2',
        'fund_ip_ccuf',
        'fund_ip_adjrw2',
        'fund_ip_payrate',
        'fund_ip_salary',
        'fund_compensate_salary',
        'receive_op',
        'receive_ip_compensate_cal',
        'receive_ip_compensate_pay',
        'receive_hc_hc',
        'receive_hc_drug',
        'receive_ae_ae',
        'receive_ae_drug',
        'receive_inst',
        'receive_dmis_compensate_cal',
        'receive_dmis_compensate_pay',
        'receive_dmis_drug',
        'receive_palliative',
        'receive_dmishd',
        'receive_pp',
        'receive_fs',
        'receive_opbkk',
        'receive_total',
        'va',
        'covid',
        'resources',
        'stm_filename',        
    ];
    public $timestamps = false;   
}
