<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Labcat_chi extends Model
{
    use HasFactory;

    protected $table = 'labcat_chi'; 
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
