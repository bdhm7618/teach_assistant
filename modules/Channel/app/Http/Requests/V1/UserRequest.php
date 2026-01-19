<?php

namespace Modules\Channel\App\Http\Requests\V1;

use Modules\Channel\App\Models\Role;
use Modules\Channel\App\Models\User;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class UserRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = $this->route('user') ?? $this->route('id') ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                $this->uniqueInChannel(User::class, ['email'], $userId),
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                $this->uniqueInChannel(User::class, ['phone'], $userId),
            ],
            'gender' => 'required|in:male,female',
            'status' => 'sometimes|integer|in:0,1',
            'image' => 'nullable|string|max:500',
            'role_id' => [
                'required',
                'integer',
                $this->belongsToChannel(Role::class),
            ],
        ];

        // Password is required only on create, optional on update
        if (!$isUpdate) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $userId = $this->route('user') ?? $this->route('id') ?? null;
            $channelId = $this->getChannelId();

            // In update case, verify that the user belongs to the current channel
            if ($userId && ($this->isMethod('PUT') || $this->isMethod('PATCH'))) {
                if ($channelId) {
                    $existingUser = User::where('id', $userId)
                        ->where('channel_id', $channelId)
                        ->first();

                    if (!$existingUser) {
                        $validator->errors()->add(
                            'id',
                            trans('channel::app.common.not_found')
                        );
                        return;
                    }
                }
            }

            // Verify role exists
            $roleId = $this->input('role_id');
            if ($roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    $validator->errors()->add(
                        'role_id',
                        trans('channel::app.validation.role_not_found')
                    );
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     * Automatically set channel_id from authenticated user.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'channel_id' => $this->getChannelId(),
        ]);
    }
}

