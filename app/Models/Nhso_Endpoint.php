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
        'vstdate',
        'sourceChannel', 
        'claimCode',  
        'claimType',                        
        'claim_status',
        'saved_at',
        'nhso_response',
        'statusAuthen',
        'statusMessage',
        'sex',
        'birthDate_year',
        'birthDate_month',
        'nation_code',
        'nation_descriptionTh',
        'province_id',
        'province_name',
        'hcode',
        'hname',
        'serviceName',
    ];
    public $timestamps = false;   
}
