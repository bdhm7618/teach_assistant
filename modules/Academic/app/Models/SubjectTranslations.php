<?php

namespace Modules\Academic\App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectTranslations extends Model
{
    protected $table = 'subject_translations';
    
    protected $fillable = [
        'subject_id',
        'locale',
        'name',
        'description',
    ];

    /**
     * Get the subject that owns the translation
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}

