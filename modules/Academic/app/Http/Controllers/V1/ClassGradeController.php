<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Academic\App\Repositories\ClassGradeRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Http\Requests\V1\ClassGradeRequest;
use Modules\Academic\App\Http\Resources\V1\ClassGradeResource;

class ClassGradeController extends BaseController
{
    protected ClassGradeRepository $repository;

    public function __construct(ClassGradeRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }
    protected function getResource(): string
    {
        return ClassGradeResource::class;
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
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.class_grade_duplicate', [
                        'grade_level' => $request->input('grade_level'),
                        'stage' => $request->input('stage')
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



    public function update(ClassGradeRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $class = $this->repository->findOrFail($id);
            $class = $this->repository->update($request->validated(), $class->id);
            DB::commit();
            return successResponse(
                new ClassGradeResource($class),
                trans('academic::app.class.updated')
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
            // Handle duplicate entry error
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.class_grade_duplicate', [
                        'grade_level' => $request->input('grade_level'),
                        'stage' => $request->input('stage')
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

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $class = $this->repository->findOrFail($id);
            $this->repository->delete($class->id);
            DB::commit();
            return successResponse(null, trans('academic::app.class.deleted'));
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
}
