<?php

namespace Modules\Assignment\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AssignmentAttachment extends Model
{
    protected $table = 'assignment_attachments';

    protected $fillable = [
        'assignment_id',
        'submission_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}
