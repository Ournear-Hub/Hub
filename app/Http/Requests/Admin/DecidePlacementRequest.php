<?php

namespace App\Http\Requests\Admin;

use App\Models\Placement;
use Illuminate\Foundation\Http\FormRequest;

class DecidePlacementRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
