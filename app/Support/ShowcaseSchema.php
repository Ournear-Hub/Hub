<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class ShowcaseSchema
{
    public static function isReady(): bool
    {
        return Schema::hasTable('homepage_statistics')
            && Schema::hasTable('showcase_companies');
    }
}
