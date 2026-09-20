<?php

namespace App\Http\Requests\Admin;

use App\Enums\MasterEntityType;
use App\Services\MasterData\MasterDataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class ToggleMasterRecordStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->entityType();

        if ($type === null) {
            return false;
        }

        return $this->user()?->can('update', $this->record()) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function entityType(): ?MasterEntityType
    {
        return MasterEntityType::tryFrom((string) $this->route('type'));
    }

    public function record(): Model
    {
        $type = $this->entityType();

        abort_if($type === null, 404);

        return app(MasterDataService::class)->resolve($type, (int) $this->route('id'));
    }
}
