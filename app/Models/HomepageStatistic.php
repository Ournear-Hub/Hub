<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageStatistic extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lock_key',
        'registered_companies',
        'talent_pool_companies',
        'staffing_companies',
        'registered_users',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_companies' => 'integer',
            'talent_pool_companies' => 'integer',
            'staffing_companies' => 'integer',
            'registered_users' => 'integer',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function toAdminArray(): array
    {
        return [
            'registered_companies' => (int) $this->registered_companies,
            'talent_pool_companies' => (int) $this->talent_pool_companies,
            'staffing_companies' => (int) $this->staffing_companies,
            'registered_users' => (int) $this->registered_users,
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int, formatted: string}>
     */
    public function toPublicGlance(): array
    {
        return [
            [
                'key' => 'registered_companies',
                'label' => 'Registered Companies',
                'value' => (int) $this->registered_companies,
                'formatted' => number_format((int) $this->registered_companies),
            ],
            [
                'key' => 'talent_pool_companies',
                'label' => 'Talent Pool Companies',
                'value' => (int) $this->talent_pool_companies,
                'formatted' => number_format((int) $this->talent_pool_companies),
            ],
            [
                'key' => 'staffing_companies',
                'label' => 'Staffing Companies',
                'value' => (int) $this->staffing_companies,
                'formatted' => number_format((int) $this->staffing_companies),
            ],
            [
                'key' => 'registered_users',
                'label' => 'Registered Users',
                'value' => (int) $this->registered_users,
                'formatted' => number_format((int) $this->registered_users),
            ],
        ];
    }
}
