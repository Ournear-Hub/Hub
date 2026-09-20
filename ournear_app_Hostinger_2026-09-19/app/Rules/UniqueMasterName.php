<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueMasterName implements ValidationRule
{
    /**
     * @param  array<string, mixed>  $scope
     */
    public function __construct(
        private string $table,
        private array $scope = [],
        private ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = mb_strtolower(trim((string) $value));

        if ($name === '') {
            return;
        }

        $query = DB::table($this->table)->whereRaw('LOWER(name) = ?', [$name]);

        foreach ($this->scope as $column => $scopeValue) {
            $query->where($column, $scopeValue);
        }

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('This name already exists in this list.');
        }
    }
}
