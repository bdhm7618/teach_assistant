<?php

namespace Modules\Channel\App\Repositories;


use Illuminate\Support\Str;
use Modules\Channel\App\Models\Channel;
use Prettus\Repository\Eloquent\BaseRepository;

class ChannelRepository extends BaseRepository
{
   public function model()
   {
      return Channel::class;
   }

   public function create(array $data)
   {
      $data['code'] = Str::upper(Str::random(8));
      $data['is_private'] = $data['is_private'] ?? false;
      $data['created_by_admin'] = $data['created_by_admin'] ?? null;
      return $this->model->create($data);
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
