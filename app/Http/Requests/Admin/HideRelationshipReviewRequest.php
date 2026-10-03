<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use App\Models\RelationshipReview;
use Illuminate\Foundation\Http\FormRequest;

class HideRelationshipReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        $review = $this->route('review');

        return $employee instanceof Employee
            && $review instanceof RelationshipReview
            && (int) $review->employee_id === (int) $employee->id
            && $this->user()?->can('hide', $review) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
