<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'code',
        'class_id',
        'numbre_of_sessions',
        'price_of_group',
        'status',
        'channel_id',
        'teacher_id',
    ];

   protected  $prefix = 'GRP';

    public function class()
    {
        return $this->belongsTo(ClassModel::class, "class_id");
    }
    public function times()
    {
        return $this->hasMany(ClassTime::class);
    }
}
