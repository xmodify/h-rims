<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LookupWard extends Model
{
    use HasFactory;

    protected $table = 'lookup_ward'; 
    protected $primaryKey = 'ward';
    public $incrementing = false; // เพราะ icode ไม่ใช่ auto-increment
    protected $keyType = 'string';
    protected $fillable = [
        'ward',
        'ward_name',
        'ward_m',
        'ward_f',
        'ward_vip',
        'ward_lr',
        'ward_homeward',
    ];

}
