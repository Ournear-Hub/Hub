<?php

namespace App\Models;

use App\Models\Concerns\HasMasterRecord;
use Illuminate\Database\Eloquent\Model;

class JobStatusOption extends Model
{
    use HasMasterRecord;

    protected $table = 'job_statuses';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_system',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isSelectable(): bool
    {
        return $this->is_active;
    }
}
