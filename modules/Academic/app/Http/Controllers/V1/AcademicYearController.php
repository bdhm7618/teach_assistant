<?php

namespace Modules\Academic\App\Http\Controllers\V1;


use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\AcademicYearRepository;
use Modules\Academic\App\Http\Requests\V1\AcademicYearRequest;
use Modules\Academic\App\Http\Resources\V1\AcademicYearResource;

class AcademicYearController extends BaseController
{
    protected AcademicYearRepository $repository;

    public function __construct(AcademicYearRepository $repository)
    {
        $this->repository = $repository;
    }
    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }
    protected function getResource(): string
    {
        return AcademicYearResource::class;
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



    public function update(AcademicYearRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->findOrFail($id);

            $year = $this->repository->update($request->validated(), $year->id);
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                trans('academic::app.academic_year.updated')
            );
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

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->findOrFail($id);
            $this->repository->delete($year);
            DB::commit();
            return successResponse(null, trans('academic::app.academic_year.deleted'));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
