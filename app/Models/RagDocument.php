<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RagDocument extends Model
{
    use HasFactory;

    protected $table = 'rag_documents';

    protected $fillable = [
        'category_id',
        'title',
        'filename',
        'file_path',
        'file_type',
        'file_size',
        'chunk_count',
        'status',
        'error_message',
    ];

    public function category()
    {
        return $this->belongsTo(RagCategory::class, 'category_id');
    }

    public function chunks()
    {
        return $this->hasMany(RagChunk::class, 'document_id');
    }

    protected static function booted()
    {
        static::deleting(function ($doc) {
            if (!empty($doc->file_path)) {
                // 1. Delete via Laravel Storage facade
                if (Storage::disk('local')->exists($doc->file_path)) {
                    Storage::disk('local')->delete($doc->file_path);
                }

                // 2. Direct absolute path unlink fallback for guaranteed deletion
                $fullPath = storage_path('app/' . $doc->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Cascade delete chunks
            $doc->chunks()->delete();
        });
    }
}
