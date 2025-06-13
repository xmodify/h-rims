<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nhso_Endpoint extends Model
{
    use HasFactory;

    protected $table = 'nhso_endpoint'; 
    protected $primaryKey = 'id';
    protected $fillable = [  
        'cid',
        'firstName',          
        'lastName',          
        'mainInscl',          
        'mainInsclName',          
        'subInscl',          
        'subInsclName',   
        'serviceDateTime', 
        'sourceChannel', 
        'claimCode',  
        'claimType',                        
    ];
    public $timestamps = false;   
}
