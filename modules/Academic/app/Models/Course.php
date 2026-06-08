<?php

namespace Modules\Academic\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Channel\App\Traits\HasChannelScope;

class Course extends Model
{
    use HasChannelScope, SoftDeletes;

    protected $fillable = [
        'channel_id', 'subject_id', 'name', 'type', 'status', 'description', 'cover_image',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function activeGroups()
    {
        return $this->hasMany(Group::class)->where('status', 'active');
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }
}
