<?php

namespace App\Http\Requests\Admin;

use App\Models\Placement;
use Illuminate\Foundation\Http\FormRequest;

class RecordPlacementJoinedRequest extends FormRequest
{
    public function authorize(): bool
    {
        $placement = $this->route('placement');

        return $placement instanceof Placement
            && $this->user()?->can('update', $placement) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_joining_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
