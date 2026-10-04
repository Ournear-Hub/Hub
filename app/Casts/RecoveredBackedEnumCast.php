<?php

namespace App\Casts;

use App\Support\RecoveredEnumValues;
use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<BackedEnum|null, BackedEnum|string|null>
 */
class RecoveredBackedEnumCast implements CastsAttributes
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     */
    public function __construct(protected string $enumClass) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?BackedEnum
    {
        if ($value === null || $value === '') {
            return null;
        }

        $context = $model::class.'.'.$key.'#'.$model->getKey();

        return RecoveredEnumValues::cast($this->enumClass, $value, $context);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        $normalized = RecoveredEnumValues::normalizeValue($this->enumClass, $value);

        return $normalized ?? (string) $value;
    }
}
