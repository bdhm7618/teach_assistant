<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Academic\App\Repositories\ClassGradeRepository;
use Modules\Academic\App\Http\Requests\V1\ClassGradeRequest;
use Modules\Academic\App\Http\Resources\V1\ClassGradeResource;

class ClassGradeController extends Controller
{
    protected ClassGradeRepository $repository;

    public function __construct(ClassGradeRepository $repository)
    {
        $this->repository = $repository;
    }


    public function store(ClassGradeRequest $request)
    {
        DB::beginTransaction();
        try {
            $class = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new ClassGradeResource($class),
                trans('academic::app.class.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }



    public function update(ClassGradeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $class = $this->repository->find($id, auth()->user()->channel_id);
            $class = $this->repository->update($class, $request->validated());
            DB::commit();
            return successResponse(
                new ClassGradeResource($class),
                trans('academic::app.class.updated')
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
            $class = $this->repository->find($id, auth()->user()->channel_id);
            $this->repository->delete($class);
            DB::commit();
            return successResponse(null, trans('academic::app.class.deleted'));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
