<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeImportItem extends Model
{
    protected $fillable = [
        'resume_import_batch_id',
        'user_id',
        'original_filename',
        'extracted_name',
        'extracted_email',
        'storage_path',
        'file_extension',
        'content_hash',
        'status',
        'email_status',
        'message',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ResumeImportBatch::class, 'resume_import_batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
