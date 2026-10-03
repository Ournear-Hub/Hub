<?php

namespace App\Enums;

enum AutomationScope: string
{
    case Global = 'global';
    case Job = 'job';
    case Employer = 'employer';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'All jobs',
            self::Job => 'One job',
            self::Employer => 'One employer',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $scope) => ['value' => $scope->value, 'label' => $scope->label()],
            self::cases(),
        );
    }
}
