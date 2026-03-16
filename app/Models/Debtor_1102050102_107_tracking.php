<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debtor_1102050102_107_tracking extends Model
{
    use HasFactory;

    protected $table = 'debtor_1102050102_107_tracking'; 
    protected $primaryKey = 'tracking_id';
    protected $fillable = [  
        'vn', 
        'an',    
        'tracking_date',
        'tracking_type',
        'tracking_no',
        'tracking_officer', 
        'tracking_note',
        'charge_date',
        'charge_no',
        'charge',
        'receive_date',
        'receive_no',
        'receive',
        'repno',
        'status',
        'adj_inc',
        'adj_dec',
        'adj_date',
        'adj_note',];
   
}
