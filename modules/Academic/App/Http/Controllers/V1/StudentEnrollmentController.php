<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\StudentEnrollmentRepository;
use Modules\Academic\App\Http\Requests\V1\StudentEnrollmentRequest;
use Modules\Academic\App\Http\Resources\V1\StudentEnrollmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;

class StudentEnrollmentController extends BaseController
{
    protected StudentEnrollmentRepository $repository;

    public function __construct(StudentEnrollmentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return StudentEnrollmentResource::class;
    }

    public function index(Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->has('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $query->with(['student', 'group', 'group.subject', 'group.classGrade']);

            $perPage = $request->get('per_page', 15);
            $enrollments = $query->paginate($perPage);

            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function store(StudentEnrollmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $enrollment = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group'])),
                trans('academic::app.enrollment.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function show($id)
    {
        try {
            $enrollment = $this->repository->find($id);
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group', 'group.subject', 'group.classGrade'])),
                trans('academic::app.enrollment.retrieved')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function update(StudentEnrollmentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $enrollment = $this->repository->update($request->validated(), $id);
            
            // Recalculate remaining sessions if sessions_per_month changed
            if ($request->has('sessions_per_month')) {
                $enrollment->updateRemainingSessions();
            }
            
            DB::commit();
            return successResponse(
                new StudentEnrollmentResource($enrollment->load(['student', 'group'])),
                trans('academic::app.enrollment.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->repository->delete($id);
            DB::commit();
            return successResponse(null, trans('academic::app.enrollment.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('academic::app.enrollment.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function getByStudent($studentId)
    {
        try {
            $enrollments = $this->repository->getByStudent($studentId);
            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function getByGroup($groupId)
    {
        try {
            $enrollments = $this->repository->getByGroup($groupId);
            return successResponse(
                StudentEnrollmentResource::collection($enrollments),
                trans('academic::app.enrollment.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}

