<?php

namespace Modules\Academic\App\Http\Controllers\V1;


use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Academic\App\Repositories\AcademicYearRepository;
use Modules\Academic\App\Http\Requests\V1\AcademicYearRequest;
use Modules\Academic\App\Http\Resources\V1\AcademicYearResource;

class AcademicYearController extends Controller
{
    protected AcademicYearRepository $repository;

    public function __construct(AcademicYearRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return successResponse(AcademicYearResource::collection($this->repository->all()), trans('academic::app.academic_year.list'));
    }

    public function store(AcademicYearRequest $request)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                trans('academic::app.academic_year.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function show($id)
    {
        $year = $this->repository->find($id, auth("user")->user()?->channel_id);
        return successResponse(new AcademicYearResource($year));
    }

    public function update(AcademicYearRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->find($id, auth("user")->user()?->channel_id);
            $year = $this->repository->update($year, $request->validated());
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                trans('academic::app.academic_year.updated')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->find($id, auth("user")->user()?->channel_id);
            $this->repository->delete($year);
            DB::commit();
            return successResponse(null, trans('academic::app.academic_year.deleted'));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    // Activate an academic year
    public function activate($id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->find($id, auth("user")->user()?->channel_id);
            $year = $this->repository->activate($year);
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                __('Academic year activated successfully')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse($e->getMessage(), $e);
        }
    }
}
