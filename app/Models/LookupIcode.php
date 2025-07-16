<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LookupIcode extends Model
{
    use HasFactory;

    protected $table = 'lookup_icode'; 
    protected $primaryKey = 'icode';
    public $incrementing = false; // เพราะ icode ไม่ใช่ auto-increment
    protected $keyType = 'string';
    protected $fillable = [
        'icode',
        'name',
        'nhso_adp_code',
        'uc_cr',
        'ppfs',
        'herb32',
        'kidney',
        'ems',
    ];
    
}
