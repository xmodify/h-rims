<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_ucs_kidneyexcel extends Model
{
    use HasFactory;

    protected $table = 'stm_ucs_kidneyexcel'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'round_no',
        'no',
        'repno', 
        'hn', 
        'an', 
        'cid',
        'pt_name',
        'datetimeadm',
        'hd_type',
        'charge_total',
        'receive_total',
        'note',   
        'stm_filename',        
    ];
    public $timestamps = false;   
}
