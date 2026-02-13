<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Modules\Academic\App\Repositories\ClassGradeRepository;
use Modules\Academic\App\Repositories\SubjectRepository;
use Modules\Student\App\Repositories\StudentRepository;

class GroupMetadataController extends Controller
{
    protected ClassGradeRepository $classGradeRepository;
    protected SubjectRepository $subjectRepository;
    protected StudentRepository $studentRepository;

    public function __construct(
        ClassGradeRepository $classGradeRepository,
        SubjectRepository $subjectRepository,
        StudentRepository $studentRepository
    ) {
        $this->classGradeRepository = $classGradeRepository;
        $this->subjectRepository = $subjectRepository;
        $this->studentRepository = $studentRepository;
    }

    /**
     * Get metadata for group creation/update
     * 
     * Returns class grades, subjects, and students for dropdowns
     */
    public function index()
    {
        try {
            $channelId = auth('user')->user()?->channel_id;

            $classGrades = $this->classGradeRepository->makeModel()
                ->where('channel_id', $channelId)
                ->where('is_active', true)
                ->with('level')
                ->get()
                ->map(function ($classGrade) {
                    $level = $classGrade->level;
                    return [
                        'id' => $classGrade->id,
                        'name' => $level ? ($level->level_number ? "Grade {$level->level_number} - " . ucfirst($level->stage) : $level->name) : ($classGrade->name ?? 'N/A'),
                        'level_number' => $level->level_number ?? null,
                        'stage' => $level->stage ?? null,
                    ];
                });

            // Get subjects: general subjects (channel_id = null) + channel-specific subjects
            $subjects = $this->subjectRepository->makeModel()
                ->withoutChannelScope()
                ->where('is_active', true)
                ->where(function ($query) use ($channelId) {
                    $query->where('channel_id', $channelId)
                          ->orWhereNull('channel_id');
                })
                ->with(['translations' => function ($query) {
                    $query->where('locale', app()->getLocale());
                }])
                ->get()
                ->map(function ($subject) {
                    $translation = $subject->translations->first();
                    return [
                        'id' => $subject->id,
                        'name' => $translation ? $translation->name : 'N/A',
                        'code' => $subject->code,
                        'is_general' => $subject->channel_id === null,
                    ];
                });

            $students = $this->studentRepository->makeModel()
                ->where('channel_id', $channelId)
                ->where('status', 1)
                ->select('id', 'name', 'code')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'code' => $student->code,
                    ];
                });

            return successResponse([
                'class_grades' => $classGrades,
                'subjects' => $subjects,
                'students' => $students,
            ], 'Metadata retrieved successfully');
        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve metadata', $e);
        }
    }
}

