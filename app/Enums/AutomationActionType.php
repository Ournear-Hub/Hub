<?php

namespace App\Enums;

enum AutomationActionType: string
{
    case RecommendScreening = 'recommend_screening';
    case CreateTask = 'create_task';
    case NotifyAdmin = 'notify_admin';

    public function label(): string
    {
        return match ($this) {
            self::RecommendScreening => 'Recommend recruiter screening review',
            self::CreateTask => 'Create recruiter review task',
            self::NotifyAdmin => 'Notify admin staff',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $action) => ['value' => $action->value, 'label' => $action->label()],
            self::cases(),
        );
    }
}
