<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_sss_kidney extends Model
{
    use HasFactory;

    protected $table = 'stm_sss_kidney'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'hcode', 
        'hname', 
        'stmdoc',
        'station', 
        'hreg',
        'hn',
        'invno',
        'dttran',
        'vstdate',
        'vsttime',
        'amount',
        'paid',
        'rid',
        'hdflag',
    ];
    public $timestamps = false;   
}
