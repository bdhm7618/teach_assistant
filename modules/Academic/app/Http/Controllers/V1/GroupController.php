<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\GroupRepository;
use Modules\Academic\App\Repositories\ClassGradeRepository;
use Modules\Academic\App\Repositories\SessionTimeRepository;
use Modules\Academic\App\Http\Requests\V1\GroupRequest;
use Modules\Academic\App\Http\Resources\V1\GroupResource;

class GroupController extends BaseController
{
    protected GroupRepository $repository;
    protected ClassGradeRepository $classGradeRepository;
    protected SessionTimeRepository $sessionTimeRepository;

    public function __construct(
        GroupRepository $repository,
        ClassGradeRepository $classGradeRepository,
        SessionTimeRepository $sessionTimeRepository
    ) {
        $this->repository = $repository;
        $this->classGradeRepository = $classGradeRepository;
        $this->sessionTimeRepository = $sessionTimeRepository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return GroupResource::class;
    }

    /**
     * Display a listing of groups
     */
    public function index(\Illuminate\Http\Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            // Apply filters
            if ($request->has('class_grade_id')) {
                $query->where('class_grade_id', $request->class_grade_id);
            }

            if ($request->has('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Load relationships
            $query->with(['classGrade', 'subject.translations' => function ($q) {
                $q->where('locale', app()->getLocale());
            }]);

            // Get counts
            $query->withCount(['sessions', 'students']);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $groups = $query->paginate($perPage);

            return successResponse(
                GroupResource::collection($groups),
                trans('academic::app.group.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Store a newly created group
     */
    public function store(GroupRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateGroupCode($data['class_grade_id']);
            }

            // Remove student_ids and session_times from data (will be handled separately)
            $studentIds = $data['student_ids'] ?? [];
            $sessionTimes = $data['session_times'] ?? [];
            unset($data['student_ids'], $data['session_times']);

            $group = $this->repository->create($data);
            
            // Attach students if provided
            if (!empty($studentIds)) {
                $group->students()->sync($studentIds);
            }

            // Create session times if provided
            if (!empty($sessionTimes)) {
                $this->createSessionTimes($group, $sessionTimes);
            }
            
            // Load relationships
            $group->load(['classGrade', 'subject.translations' => function ($q) {
                $q->where('locale', app()->getLocale());
            }, 'students', 'sessions']);
            $group->loadCount(['sessions', 'students']);

            DB::commit();
            return successResponse(
                new GroupResource($group),
                trans('academic::app.group.created'),
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.group_duplicate', [
                        'name' => $request->input('name')
                    ]),
                    null,
                    422
                );
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Display the specified group
     */
    public function show($id)
    {
        try {
            $group = $this->repository->find($id);
            
            // Load relationships
            $group->load(['classGrade', 'subject.translations' => function ($q) {
                $q->where('locale', app()->getLocale());
            }, 'sessions', 'students']);
            $group->loadCount(['sessions', 'students']);

            return successResponse(
                new GroupResource($group),
                trans('academic::app.group.show_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Update the specified group
     */
    public function update(GroupRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $group = $this->repository->findOrFail($id);
            $data = $request->validated();

            // Don't update code if it's not in the request
            if (!isset($data['code'])) {
                unset($data['code']);
            }

            // Handle student_ids and session_times separately
            $studentIds = $data['student_ids'] ?? null;
            $sessionTimes = $data['session_times'] ?? null;
            unset($data['student_ids'], $data['session_times']);

            $group = $this->repository->update($data, $group->id);
            
            // Sync students if provided
            if ($studentIds !== null) {
                $group->students()->sync($studentIds);
            }

            // Update session times if provided
            if ($sessionTimes !== null) {
                $this->updateSessionTimes($group, $sessionTimes);
            }
            
            // Load relationships
            $group->load(['classGrade', 'subject.translations' => function ($q) {
                $q->where('locale', app()->getLocale());
            }, 'students', 'sessions']);
            $group->loadCount(['sessions', 'students']);

            DB::commit();
            return successResponse(
                new GroupResource($group),
                trans('academic::app.group.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.group_duplicate', [
                        'name' => $request->input('name')
                    ]),
                    null,
                    422
                );
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Remove the specified group
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $group = $this->repository->findOrFail($id);
            $this->repository->delete($group->id);
            DB::commit();
            return successResponse(null, trans('academic::app.group.deleted'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Generate unique code for group
     */
    protected function generateGroupCode($classGradeId)
    {
        $classGrade = $this->classGradeRepository->find($classGradeId);
        if (!$classGrade) {
            throw new \Exception('Class grade not found');
        }

        $prefix = 'GRP';
        $count = $this->repository->countByClassGradeId($classGradeId);
        $code = $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while ($this->repository->codeExists($code)) {
            $count++;
            $code = $prefix . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    /**
     * Create session times for a group
     */
    protected function createSessionTimes($group, array $sessionTimes)
    {
        $channelId = auth('user')->user()?->channel_id;
        
        foreach ($sessionTimes as $sessionTimeData) {
            $this->sessionTimeRepository->create([
                'day' => $sessionTimeData['day'],
                'start_time' => $sessionTimeData['start_time'],
                'end_time' => $sessionTimeData['end_time'],
                'group_id' => $group->id,
                'is_active' => $sessionTimeData['is_active'] ?? true,
                'channel_id' => $channelId,
            ]);
        }
    }

    /**
     * Update session times for a group
     */
    protected function updateSessionTimes($group, array $sessionTimes)
    {
        $channelId = auth('user')->user()?->channel_id;
        
        // Delete existing session times
        $this->sessionTimeRepository->makeModel()
            ->where('group_id', $group->id)
            ->delete();

        // Create new session times
        foreach ($sessionTimes as $sessionTimeData) {
            $this->sessionTimeRepository->create([
                'day' => $sessionTimeData['day'],
                'start_time' => $sessionTimeData['start_time'],
                'end_time' => $sessionTimeData['end_time'],
                'group_id' => $group->id,
                'is_active' => $sessionTimeData['is_active'] ?? true,
                'channel_id' => $channelId,
            ]);
        }
    }
}

