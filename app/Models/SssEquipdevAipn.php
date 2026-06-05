<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SssEquipdevAipn extends Model
{
    use HasFactory;

    protected $table = 'sss_equipdev_aipn';

    protected $fillable = [
        'billgroup',
        'code',
        'unit',
        'rate',
        'rate2',
        'desc',
        'daterev',
        'dateeff',
        'dateexp',
        'lastupd',
        'dtcond',
        'note',
    ];

    public $timestamps = true;
}
