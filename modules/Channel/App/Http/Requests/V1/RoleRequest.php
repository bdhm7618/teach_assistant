<?php

namespace Modules\Channel\App\Http\Requests\V1;

use Modules\Channel\App\Models\Role;
use Modules\Channel\App\Http\Requests\V1\BaseRequest;

class RoleRequest extends BaseRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $roleId = $this->route('role') ?? $this->route('id') ?? null;
        $channelId = $this->getChannelId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per channel (or general if channel_id is null)
                function ($attribute, $value, $fail) use ($roleId, $channelId) {
                    $query = \Modules\Channel\App\Models\Role::withoutChannelScope()
                        ->where('name', $value);
                    
                    // Check for same channel_id (or both null for general roles)
                    $query->where(function ($q) use ($channelId) {
                        if ($channelId === null) {
                            $q->whereNull('channel_id');
                        } else {
                            $q->where('channel_id', $channelId);
                        }
                    });
                    
                    if ($roleId) {
                        $query->where('id', '!=', $roleId);
                    }
                    
                    if ($query->exists()) {
                        $fail(trans('channel::app.role.name_already_exists'));
                    }
                },
            ],
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
            'channel_id' => 'nullable|integer|exists:channels,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roleId = $this->route('role') ?? $this->route('id') ?? null;

            // In update case, verify that the role exists
            if ($roleId) {
                $role = Role::find($roleId);
                if (!$role) {
                    $validator->errors()->add(
                        'id',
                        trans('channel::app.common.not_found')
                    );
                    return;
                }

                // Prevent modifying system roles
                if ($role->name === 'owner' && $this->isMethod('PUT')) {
                    $validator->errors()->add(
                        'name',
                        trans('channel::app.role.cannot_modify_system_role')
                    );
                }
            }

            // Prevent channel users from creating general roles (channel_id = null)
            // Only admins can create general roles
            $channelId = $this->getChannelId();
            $requestedChannelId = $this->input('channel_id');
            if ($channelId !== null && $requestedChannelId === null && $this->isMethod('POST')) {
                $validator->errors()->add(
                    'channel_id',
                    trans('channel::app.role.cannot_create_general_role')
                );
            }

            // Validate permissions format
            $permissions = $this->input('permissions');
            if (is_array($permissions)) {
                foreach ($permissions as $permission) {
                    if (!is_string($permission)) {
                        $validator->errors()->add(
                            'permissions',
                            trans('channel::app.role.invalid_permission_format')
                        );
                        break;
                    }
                }
            }
        });
    }
}

