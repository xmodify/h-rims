<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_ucs_kidney extends Model
{
    use HasFactory;

    protected $table = 'stm_ucs_kidney'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
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
