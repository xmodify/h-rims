<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LookupHospcode extends Model
{
    use HasFactory;

    protected $table = 'lookup_hospcode'; 
    protected $primaryKey = 'hospcode';
    public $incrementing = false; // เพราะ icode ไม่ใช่ auto-increment
    protected $keyType = 'string';
    protected $fillable = [
        'hospcode',
        'hospcode_name',
        'hmain_ucs',
        'hmain_sss',
        'in_province',  
    ];

}
