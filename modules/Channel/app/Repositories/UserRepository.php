<?php

namespace Modules\Channel\App\Repositories;

use Illuminate\Support\Facades\Hash;
use Modules\Channel\App\Models\User;
use Prettus\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    /**
     * Create a new user in the current channel.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Ensure channel_id is set (should be set by request validation)
        if (!isset($data['channel_id'])) {
            $data['channel_id'] = auth('user')->user()?->channel_id;
        }

        return $this->model->create($data);
    }

    /**
     * Update a user.
     *
     * @param array $data
     * @param int $id
     * @return User
     */
    public function update(array $data, $id): User
    {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Remove password from data if not provided
            unset($data['password']);
        }

        // Use withoutChannelScope to find user by ID regardless of channel
        // Channel validation is handled in the Request
        $user = $this->model->withoutChannelScope()->findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        // Use withoutChannelScope to find user by ID regardless of channel
        // Channel validation is handled in the Controller
        $user = $this->model->withoutChannelScope()->findOrFail($id);
        return $user->delete();
    }
}
