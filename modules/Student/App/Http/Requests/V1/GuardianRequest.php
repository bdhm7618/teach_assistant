<?php

namespace Modules\Student\App\Http\Requests\V1;

use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class GuardianRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name'         => ($isUpdate ? 'sometimes' : 'required') . '|string|max:255',
            'phone'        => ($isUpdate ? 'sometimes' : 'required') . '|string|max:20',
            'email'        => 'nullable|email|max:255',
            'relationship' => ($isUpdate ? 'sometimes' : 'required') . '|in:father,mother,brother,sister,uncle,aunt,other',
            'is_primary'   => 'sometimes|boolean',
            'notes'        => 'nullable|string',
        ];
    }
}
