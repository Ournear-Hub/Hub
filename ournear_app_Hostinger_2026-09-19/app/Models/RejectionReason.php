<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;

class RejectionReason extends Model
{
    use HasMasterRecord;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isSelectable(): bool
    {
        return $this->is_active;
    }
}
