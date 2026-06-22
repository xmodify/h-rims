<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labcat_fdh extends Model
{
    use HasFactory;

    protected $table = 'labcat_fdh'; 
    protected $fillable = [
        'benefitplan',
        'cscode',
        'name',
        'unit',
        'unitprice',
        'gyear',
        'updatebeg',
        'updateend',
        'updateflag',
        'tmlt',
        'tmlt_name',
        'lccode',
        'loinc',
        'exception',
        'stm_filename',
    ];
}
