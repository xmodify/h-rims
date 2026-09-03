<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlJournal extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_journals';

    protected $fillable = [
        'voucher_no',
        'voucher_date',
        'journal_type',
        'description',
        'total_debit',
        'total_credit',
        'posted_status',
        'fiscal_year',
        'fiscal_month',
        'apar',
        'external_ref',
    ];

    public function items()
    {
        return $this->hasMany(HosfinGlJournalItem::class, 'journal_id', 'id');
    }
}
