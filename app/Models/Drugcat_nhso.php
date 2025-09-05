<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drugcat_nhso extends Model
{
    use HasFactory;

    protected $table = 'drugcat_nhso'; 
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
        'ised_approved',
        'ndc24_approved',
        'date_approved',
        'ised_status',
        'stm_filename',
    ];
    public $timestamps = false;   
}
