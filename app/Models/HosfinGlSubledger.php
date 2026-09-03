<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlSubledger extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_subledgers';

    protected $fillable = [
        'subledger_code',
        'vendor_name',
        'category',
        'raw_note',
    ];

    public function apBills()
    {
        return $this->hasMany(HosfinGlApBill::class, 'vendor_name', 'vendor_name');
    }
}
