<?php

namespace Modules\Attendance\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Attendance\App\Repositories\AttendanceRepository;
use Modules\Attendance\App\Http\Requests\V1\AttendanceRequest;
use Modules\Attendance\App\Http\Resources\V1\AttendanceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;

class AttendanceController extends BaseController
{
    protected AttendanceRepository $repository;

    public function __construct(AttendanceRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return AttendanceResource::class;
    }

    public function store(AttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new AttendanceResource($attendance),
                trans('attendance::app.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|integer|exists:students,id',
            'attendances.*.group_id' => 'required|integer|exists:groups,id',
            'attendances.*.date' => 'required|date',
            'attendances.*.status' => 'required|string|in:present,absent,late,excused',
            'attendances.*.notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $channelId = auth('user')->user()?->channel_id;
            $attendances = collect($request->input('attendances'))->map(function ($attendance) use ($channelId) {
                $attendance['channel_id'] = $channelId;
                return $attendance;
            })->toArray();

            $created = $this->repository->bulkCreate($attendances);
            DB::commit();
            return successResponse(
                AttendanceResource::collection($created),
                trans('attendance::app.bulk_created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    public function update(AttendanceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->findOrFail($id);
            $attendance = $this->repository->update($request->validated(), $attendance->id);
            DB::commit();
            return successResponse(
                new AttendanceResource($attendance),
                trans('attendance::app.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('attendance::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $attendance = $this->repository->findOrFail($id);
            $this->repository->delete($attendance->id);
            DB::commit();
            return successResponse(null, trans('attendance::app.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('attendance::app.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    public function studentStatistics(Request $request, $studentId)
    {
        try {
            $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : null;

            $statistics = $this->repository->getStudentStatistics($studentId, $startDate, $endDate);
            return successResponse($statistics, trans('attendance::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }

    public function groupStatistics(Request $request, $groupId)
    {
        try {
            $date = $request->input('date') ? \Carbon\Carbon::parse($request->input('date')) : null;
            $statistics = $this->repository->getGroupStatistics($groupId, $date);
            return successResponse($statistics, trans('attendance::app.statistics_retrieved'));
        } catch (\Exception $e) {
            return errorResponse(trans('attendance::app.operation_failed'), $e);
        }
    }
}
