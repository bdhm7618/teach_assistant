<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTime extends Model
{
    use HasFactory;

    protected $table = 'class_times';

    protected $fillable = [
        'class_time',
        'day_name',
        'group_id',
        'status',
    ];

    /**
     * Relation: each class time belongs to one group
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
