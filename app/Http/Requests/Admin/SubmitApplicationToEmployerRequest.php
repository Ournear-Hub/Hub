<?php

namespace App\Http\Requests\Admin;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationToEmployerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof Application
            && $this->user()?->can('update', $application) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
