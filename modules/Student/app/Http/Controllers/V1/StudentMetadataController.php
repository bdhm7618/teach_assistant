<?php

namespace Modules\Student\App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Modules\Academic\App\Repositories\GroupRepository;

class StudentMetadataController extends Controller
{
    protected GroupRepository $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    /**
     * Get metadata for student creation/update
     * 
     * Returns groups for dropdowns
     */
    public function index()
    {
        try {
            $channelId = auth('user')->user()?->channel_id;

            $groups = $this->groupRepository->makeModel()
                ->where('channel_id', $channelId)
                ->where('is_active', true)
                ->with(['classGrade.level', 'subject'])
                ->select('id', 'name', 'code', 'class_grade_id', 'subject_id')
                ->get()
                ->map(function ($group) {
                    $level = $group->classGrade->level ?? null;
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'code' => $group->code,
                        'class_grade' => $group->classGrade ? [
                            'id' => $group->classGrade->id,
                            'level_number' => $level->level_number ?? null,
                            'stage' => $level->stage ?? null,
                        ] : null,
                        'subject' => $group->subject ? [
                            'id' => $group->subject->id,
                            'name' => $group->subject->name,
                            'code' => $group->subject->code,
                        ] : null,
                    ];
                });

            return successResponse([
                'groups' => $groups,
                'genders' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female'],
                ],
                'statuses' => [
                    ['value' => 1, 'label' => 'Active'],
                    ['value' => 0, 'label' => 'Inactive'],
                ],
            ], 'Metadata retrieved successfully');
        } catch (\Exception $e) {
            return errorResponse('Failed to retrieve metadata', $e);
        }
    }
}

