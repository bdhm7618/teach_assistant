<?php

namespace Modules\Student\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Student\App\Repositories\StudentRepository;
use Modules\Student\App\Http\Requests\V1\StudentRequest;
use Modules\Student\App\Http\Resources\V1\StudentResource;

class StudentController extends BaseController
{
    protected StudentRepository $repository;

    public function __construct(StudentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return StudentResource::class;
    }

    /**
     * Display a listing of students
     */
    public function index(\Illuminate\Http\Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            // Apply filters
            if ($request->has('group_id')) {
                $query->whereHas('groups', function ($q) use ($request) {
                    $q->where('groups.id', $request->group_id);
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('gender')) {
                $query->where('gender', $request->gender);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Load relationships
            $query->with(['channel', 'groups']);

            // Get counts
            $query->withCount(['groups', 'attendances', 'payments']);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $students = $query->paginate($perPage);

            return successResponse(
                StudentResource::collection($students),
                trans('student::app.student.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Store a newly created student
     */
    public function store(StudentRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateStudentCode();
            }

            // Remove group_ids from data (will be handled separately)
            $groupIds = $data['group_ids'] ?? [];
            unset($data['group_ids']);

            $student = $this->repository->create($data);
            
            // Attach groups if provided
            if (!empty($groupIds)) {
                $student->groups()->sync($groupIds);
            }
            
            // Load relationships
            $student->load(['channel', 'groups']);
            $student->loadCount(['groups']);

            DB::commit();
            return successResponse(
                new StudentResource($student),
                trans('student::app.student.created'),
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('student::app.validation.student_duplicate'),
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
     * Display the specified student
     */
    public function show($id)
    {
        try {
            $student = $this->repository->find($id);
            
            // Load relationships
            $student->load(['channel', 'groups', 'attendances', 'payments']);
            $student->loadCount(['groups', 'attendances', 'payments']);

            return successResponse(
                new StudentResource($student),
                trans('student::app.student.show_success')
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
     * Update the specified student
     */
    public function update(StudentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $student = $this->repository->findOrFail($id);
            $data = $request->validated();

            // Don't update code if it's not in the request
            if (!isset($data['code'])) {
                unset($data['code']);
            }

            // Handle password update
            if (isset($data['password']) && empty($data['password'])) {
                unset($data['password']);
            }

            // Handle group_ids separately
            $groupIds = $data['group_ids'] ?? null;
            unset($data['group_ids']);

            $student = $this->repository->update($data, $student->id);
            
            // Sync groups if provided
            if ($groupIds !== null) {
                $student->groups()->sync($groupIds);
            }
            
            // Load relationships
            $student->load(['channel', 'groups']);
            $student->loadCount(['groups', 'attendances', 'payments']);

            DB::commit();
            return successResponse(
                new StudentResource($student),
                trans('student::app.student.updated')
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
                    trans('student::app.validation.student_duplicate'),
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
     * Remove the specified student
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $student = $this->repository->findOrFail($id);
            $this->repository->delete($student->id);
            DB::commit();
            return successResponse(null, trans('student::app.student.deleted'));
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
     * Generate unique code for student
     */
    protected function generateStudentCode()
    {
        $channelId = auth('user')->user()?->channel_id;
        $prefix = 'STU';
        $count = $this->repository->makeModel()
            ->where('channel_id', $channelId)
            ->count();
        $code = $prefix . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        // Ensure uniqueness
        while ($this->repository->makeModel()->where('code', $code)->exists()) {
            $count++;
            $code = $prefix . '-' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        }

        return $code;
    }
}

