<?php

namespace Modules\Academic\App\Models;

use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasChannelScope;
    
    protected $fillable = [
        'code',
        'credits',
        'is_active',
        'channel_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credits' => 'integer',
    ];

    /**
     * Get the groups for this subject
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get the translations for this subject
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SubjectTranslations::class);
    }

    /**
     * Get translated name for current locale
     */
    public function getName($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        return $translation ? $translation->name : null;
    }

    /**
     * Get translated description for current locale
     */
    public function getDescription($locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $translation = $this->translations()->where('locale', $locale)->first();
        return $translation ? $translation->description : null;
    }

    /**
     * Scope to get subject with translations
     */
    public function scopeWithTranslations($query, $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $query->with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }]);
    }
}

