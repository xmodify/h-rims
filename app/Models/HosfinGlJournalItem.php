<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlJournalItem extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_journal_items';

    protected $fillable = [
        'journal_id',
        'voucher_no',
        'item_no',
        'account_code',
        'account_name',
        'description',
        'debit',
        'credit',
        'department',
    ];

    public function journal()
    {
        return $this->belongsTo(HosfinGlJournal::class, 'journal_id', 'id');
    }

    public function account()
    {
        return $this->belongsTo(HosfinGlAccount::class, 'account_code', 'account_code');
    }
}
