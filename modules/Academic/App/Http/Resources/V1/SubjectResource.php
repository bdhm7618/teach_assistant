<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        $translation = $this->translations->firstWhere('locale', $locale);
        
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $translation ? $translation->name : null,
            'description' => $translation ? $translation->description : null,
            'credits' => $this->credits,
            'is_active' => $this->is_active,
            'is_general' => $this->channel_id === null,
            'channel_id' => $this->channel_id,
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->map(function ($translation) {
                    return [
                        'locale' => $translation->locale,
                        'name' => $translation->name,
                        'description' => $translation->description,
                    ];
                });
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
