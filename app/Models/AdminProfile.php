<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'department',
        'designation',
        'joined_at',
        'professional_details',
        'qualifications',
        'skills',
        'employment_information',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'department' => $this->department,
            'designation' => $this->designation,
            'joined_at' => optional($this->joined_at)?->toDateString(),
            'professional_details' => $this->professional_details,
            'qualifications' => $this->qualifications,
            'skills' => $this->skills,
            'employment_information' => $this->employment_information,
        ];
    }
}
