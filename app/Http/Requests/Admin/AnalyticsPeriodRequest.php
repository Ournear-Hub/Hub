<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AnalyticsPeriodRequest extends FormRequest
{
    public const MAX_SPAN_DAYS = 731;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->canViewOperationalAnalytics();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $from = $this->input('from');
            $to = $this->input('to');
            $fromBlank = $from === null || $from === '';
            $toBlank = $to === null || $to === '';

            if ($fromBlank && $toBlank) {
                return;
            }

            if ($fromBlank || $toBlank) {
                $validator->errors()->add(
                    $fromBlank ? 'from' : 'to',
                    'Both from and to dates are required when filtering.',
                );

                return;
            }

            $start = Carbon::parse((string) $from, config('app.timezone'))->startOfDay();
            $end = Carbon::parse((string) $to, config('app.timezone'))->startOfDay();

            if ($start->gt($end)) {
                $validator->errors()->add('from', 'The from date must be on or before the to date.');
            }

            if ($start->diffInDays($end) > self::MAX_SPAN_DAYS) {
                $validator->errors()->add('to', 'The selected range cannot exceed 731 days.');
            }
        });
    }
}
