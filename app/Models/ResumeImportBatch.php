<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResumeImportBatch extends Model
{
    protected $fillable = [
        'uploaded_by',
        'total_files',
        'successful',
        'matched',
        'needs_review',
        'failed',
        'emails_queued',
        'status',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResumeImportItem::class);
    }
}
