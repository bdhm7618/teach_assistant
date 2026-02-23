<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\LevelRepository;
use Modules\Academic\App\Http\Requests\V1\LevelRequest;
use Modules\Academic\App\Http\Resources\V1\LevelResource;
use Modules\Academic\App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends BaseController
{
    protected LevelRepository $repository;

    public function __construct(LevelRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return LevelResource::class;
    }

    public function index(Request $request)
    {
        try {
            $channelId = auth('user')->user()?->channel_id;
            $includeDefaults = $request->get('include_defaults', true);

            $query = Level::availableForChannel($channelId)
                ->orderBy('level_number')
                ->orderBy('stage')
                ->orderBy('name');

            if (!$includeDefaults) {
                $query->where('channel_id', $channelId);
            }

            $levels = $query->get();

            return successResponse(
                LevelResource::collection($levels),
                trans('academic::app.level.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function store(LevelRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['channel_id'] = auth('user')->user()?->channel_id;
            $data['is_default'] = false; // User-created levels are never default

            $level = $this->repository->create($data);
            DB::commit();

            return successResponse(
                new LevelResource($level),
                trans('academic::app.level.created'),
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
            $level = $this->repository->find($id);
            return successResponse(
                new LevelResource($level),
                trans('academic::app.level.show_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('academic::app.level.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    public function update(LevelRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $level = $this->repository->findOrFail($id);
            $level = $this->repository->update($request->validated(), $level->id);
            DB::commit();

            return successResponse(
                new LevelResource($level),
                trans('academic::app.level.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('academic::app.level.not_found'),
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
            $level = $this->repository->findOrFail($id);

            // Prevent deleting system default levels
            if ($level->is_default && $level->channel_id === null) {
                return errorResponse(
                    trans('academic::app.validation.cannot_delete_default_level'),
                    null,
                    403
                );
            }

            // Check if level is used by any class grades
            if ($level->classGrades()->count() > 0) {
                return errorResponse(
                    trans('academic::app.validation.level_in_use'),
                    null,
                    422
                );
            }

            $this->repository->delete($level);
            DB::commit();

            return successResponse(null, trans('academic::app.level.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('academic::app.level.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}

