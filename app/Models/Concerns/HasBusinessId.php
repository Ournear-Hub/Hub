<?php

namespace App\Models\Concerns;

use App\Enums\BusinessIdPrefix;
use App\Services\MasterData\BusinessIdGenerator;
use Illuminate\Database\Eloquent\Model;

trait HasBusinessId
{
    public static function bootHasBusinessId(): void
    {
        static::creating(function (Model $model): void {
            if (filled($model->getAttribute('business_id'))) {
                return;
            }

            $model->setAttribute(
                'business_id',
                app(BusinessIdGenerator::class)->next(static::businessIdPrefix()),
            );
        });

        static::updating(function (Model $model): void {
            if (! $model->isDirty('business_id')) {
                return;
            }

            $original = $model->getOriginal('business_id');

            if (filled($original)) {
                $model->setAttribute('business_id', $original);
            }
        });
    }

    abstract public static function businessIdPrefix(): BusinessIdPrefix;
}
