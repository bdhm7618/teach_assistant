<?php

namespace Modules\Channel\App\Repositories;


use Illuminate\Support\Facades\Hash;
use Modules\Channel\App\Models\Role;
use Modules\Channel\App\Models\User;
use Prettus\Repository\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    public function model()
    {
        return User::class;
    }

    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        $data['role_id'] = Role::where('name', 'owner')->first()->id;
        $user = $this->model->create($data);
        return $user;
    }

    public function update(array $data, $id)
    {
        return $this->model->find($id)->update($data);
    }

    public function delete($id)
    {
        return $this->model->find($id)->delete();
    }
}
