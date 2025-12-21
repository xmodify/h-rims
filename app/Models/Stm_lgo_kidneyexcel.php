<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stm_lgo_kidneyexcel extends Model
{
    use HasFactory;

    protected $table = 'stm_lgo_kidneyexcel'; 
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'round_no',
        'no',
        'repno', 
        'hn', 
        'cid',
        'pt_name',
        'dep',  
        'datetimeadm',
        'compensate_kidney',
        'note',        
    ];
    public $timestamps = false;   
}
