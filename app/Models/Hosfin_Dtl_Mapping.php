<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hosfin_Dtl_Mapping extends Model
{
    use HasFactory;

    protected $table = 'hosfin_dtl_mappings';
    protected $primaryKey = 'id';
    protected $fillable = [
        'group_code',
        'group_name',
        'account_code',
    ];
}
