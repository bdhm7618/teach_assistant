<?php

namespace Modules\Academic\App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'class_grade_id' => $this->class_grade_id,
            'class_grade' => $this->whenLoaded('classGrade', function () {
                if ($this->classGrade && $this->classGrade->relationLoaded('level')) {
                    $level = $this->classGrade->level;
                    return [
                        'id' => $this->classGrade->id,
                        'level_number' => $level->level_number ?? null,
                        'stage' => $level->stage ?? null,
                    ];
                }
                return [
                    'id' => $this->classGrade->id,
                ];
            }),
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', function () {
                $locale = app()->getLocale();
                $translation = $this->subject->translations()->where('locale', $locale)->first();
                return [
                    'id' => $this->subject->id,
                    'name' => $translation ? $translation->name : null,
                    'code' => $this->subject->code,
                    'description' => $translation ? $translation->description : null,
                ];
            }),
            'capacity' => $this->capacity,
            'price' => $this->price ? (float) $this->price : null,
            'is_active' => $this->is_active,
            'sessions_count' => $this->when(isset($this->sessions_count), $this->sessions_count),
            'students_count' => $this->when(isset($this->students_count), $this->students_count),
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'code' => $student->code,
                    ];
                });
            }),
            'sessions' => $this->whenLoaded('sessions', function () {
                return $this->sessions->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'day' => $session->day,
                        'start_time' => $session->start_time,
                        'end_time' => $session->end_time,
                        'is_active' => $session->is_active,
                    ];
                });
            }),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'channel_id' => $this->channel_id,
        ];
    }
}

