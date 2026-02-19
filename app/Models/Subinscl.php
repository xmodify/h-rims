<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subinscl extends Model
{
    use HasFactory;

    protected $table = 'subinscl';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'maininscl',
        'note',
    ];

    public $timestamps = false;
}
