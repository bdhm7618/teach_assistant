<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table = "classes";
    protected $fillable = [
        'start_year',
        'end_year',
        'name',
        'code',
        'status',
        'channel_id',
        'subject_id',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class, "channel_id");
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, "subject_id");
    }
}
