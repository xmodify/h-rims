<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_ofc_kidney extends Model
{
    use HasFactory;

    protected $table = 'stm_ofc_kidney'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'round_no',
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
        'receive_no',
    ];
    public $timestamps = false;   
}
