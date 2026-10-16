<?php

namespace App\Services\MasterData;

use App\Enums\MasterEntityType;

class MasterDataExcelSchema
{
    /**
     * @return list<string>
     */
    public static function headers(MasterEntityType $type): array
    {
        $headers = ['id', 'name'];

        if ($type->hasCode()) {
            $headers = ['id', 'code', 'name'];
        }

        if ($type->hasDescription()) {
            $headers[] = 'description';
        }

        if ($type === MasterEntityType::Categories) {
            $headers[] = 'industry_name';
        }

        if ($type === MasterEntityType::JobTitles) {
            $headers[] = 'job_category_name';
        }

        $headers[] = 'sort_order';
        $headers[] = 'is_active';

        return $headers;
    }

    /**
     * @return list<string>
     */
    public static function instructions(MasterEntityType $type): array
    {
        $notes = [
            'Leave id empty to create a new row (where creation is allowed).',
            'is_active: yes/no, 1/0, or true/false.',
        ];

        if (! $type->allowsCreate()) {
            $notes[] = 'This type is update-only: each row must include a valid id.';
        }

        if ($type === MasterEntityType::Categories) {
            $notes[] = 'industry_name must match an active industry.';
        }

        if ($type === MasterEntityType::JobTitles) {
            $notes[] = 'job_category_name must match an active category in an active industry.';
        }

        return $notes;
    }
}
