<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drugcat_fdh extends Model
{
    use HasFactory;

    protected $table = 'drugcat_fdh'; 
    protected $fillable = [
        'hospdrugcode',
        'productcat',  
        'tmtid', 
        'specprep', 
        'genericname',
        'tradename',
        'dfscode', 
        'dosageform',
        'strength',
        'content',
        'unitprice',
        'distributor',
        'manufacturer',
        'ised',
        'ndc24',
        'packsize',
        'packprice',
        'updateflag',
        'datechange',
        'dateupdate',
        'dateeffective',
        'date_approved',
        'ised_status',
        'stm_filename',
        'date_import',
        'filename',
        'hospcode',
    ];
    public $timestamps = false;   
}
