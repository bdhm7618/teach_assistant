<?php

namespace Modules\Channel\App\Models;

use Modules\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_private',
        'created_by_admin'
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
