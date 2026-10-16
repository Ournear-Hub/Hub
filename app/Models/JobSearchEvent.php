<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSearchEvent extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'keyword',
        'location',
        'weight',
        'searched_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'searched_at' => 'datetime',
            'weight' => 'integer',
        ];
    }
}
