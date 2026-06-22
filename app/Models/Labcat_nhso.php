<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labcat_nhso extends Model
{
    use HasFactory;

    protected $table = 'labcat_nhso'; 
    protected $fillable = [
        'lccode',
        'billgroup',  
        'cscode', 
        'tmlt', 
        'loinc',
        'panel',
        'name', 
        'sflag',
        'chargecat',
        'unitprice',
        'benefitplan',
        'reimbprice',
        'updateflag',
        'updatebeg',
        'updateend',
        'rpdatebeg',
        'rpdateend',
        'dateupd',
        'hcode',
        'message',
        'stm_filename',
    ];
    public $timestamps = false;   
}
