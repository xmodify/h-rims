<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RagChunk extends Model
{
    use HasFactory;

    protected $table = 'rag_chunks';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'embedding',
        'page_number',
    ];

    protected $casts = [
        'embedding' => 'array',
        'chunk_index' => 'integer',
        'page_number' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(RagDocument::class, 'document_id');
    }
}
