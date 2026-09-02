<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RagCategory extends Model
{
    use HasFactory;

    protected $table = 'rag_categories';

    protected $fillable = [
        'name',
        'description',
        'color',
        'sort_order',
    ];

    /**
     * Relationship: Documents belonging to this category
     */
    public function documents()
    {
        return $this->hasMany(RagDocument::class, 'category_id');
    }
}
