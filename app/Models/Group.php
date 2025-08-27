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

    public function class()
    {
        return $this->belongsTo(ClassModel::class, "class_id");
    }
}
