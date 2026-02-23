<?php

namespace Modules\Channel\App\Repositories;

use Modules\Channel\App\Models\Role;
use Prettus\Repository\Eloquent\BaseRepository;

class RoleRepository extends BaseRepository
{
    public function model()
    {
        return Role::class;
    }

    /**
     * Create a new role.
     *
     * @param array $data
     * @return Role
     */
    public function create(array $data): Role
    {
        // Ensure permissions is JSON encoded
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }

        // If channel_id is not provided, set it from authenticated user
        // If channel_id is explicitly null, it means general role
        if (!isset($data['channel_id']) && auth('user')->check()) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        return $this->model->create($data);
    }

    /**
     * Update a role.
     *
     * @param array $data
     * @param int $id
     * @return Role
     */
    public function update(array $data, $id): Role
    {
        // Ensure permissions is JSON encoded
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $data['permissions'] = json_encode($data['permissions']);
        }

        $role = $this->model->findOrFail($id);
        
        // Prevent changing channel_id - it should remain as is
        // General roles (channel_id = null) stay general
        // Channel-specific roles stay channel-specific
        if (isset($data['channel_id'])) {
            unset($data['channel_id']);
        }
        
        $role->update($data);
        return $role->fresh();
    }

    /**
     * Delete a role.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        $role = $this->model->findOrFail($id);
        return $role->delete();
    }
}

