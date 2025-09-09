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
    ];
   
}
