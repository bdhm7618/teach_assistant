<?php

namespace Modules\Student\App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Channel\App\Traits\HasChannelScope;

class Guardian extends Model
{
    use HasChannelScope;

    protected $fillable = [
        'channel_id',
        'student_id',
        'name',
        'phone',
        'email',
        'relationship',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
