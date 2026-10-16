<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterEntityType;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmMasterDataImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->entityType();

        return $type !== null && $this->user()?->canManageMasterData() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
        ];
    }

    public function entityType(): ?MasterEntityType
    {
        return MasterEntityType::tryFrom((string) $this->route('type'));
    }
}
