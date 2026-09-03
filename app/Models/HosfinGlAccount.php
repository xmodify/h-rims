<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlAccount extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_accounts';

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'account_category',
        'normal_balance',
        'cost_type',
        'service_type',
        'is_active',
    ];

    public function journalItems()
    {
        return $this->hasMany(HosfinGlJournalItem::class, 'account_code', 'account_code');
    }
}
