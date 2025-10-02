<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetYear extends Model
{
    use HasFactory;

    protected $table = 'budget_year'; 
    protected $primaryKey = 'LEAVE_YEAR_ID';
    public $incrementing = false; // เพราะ icode ไม่ใช่ auto-increment
    protected $keyType = 'string';
    protected $fillable = [
        'LEAVE_YEAR_ID',
        'LEAVE_YEAR_NAME',
        'DATE_BEGIN',
        'DATE_END',       
    ];

}
