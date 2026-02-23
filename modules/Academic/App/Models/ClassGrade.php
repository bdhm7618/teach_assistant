<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;

class ClassGrade extends Model
{
    use HasChannelScope;
    protected $table = 'class_grades';

    protected $fillable = [
        'name',
        'level_id',
        'is_active',
        'channel_id',
    ];


    public function groups()
    {
        return $this->hasMany(Group::class, 'class_id');
    }

    /**
     * Get the level for this class grade
     */
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the display name for the class grade
     * Returns name if available, otherwise returns level name
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        if ($this->level) {
            return $this->level->name;
        }

        return 'Class Grade #' . $this->id;
    }
}
