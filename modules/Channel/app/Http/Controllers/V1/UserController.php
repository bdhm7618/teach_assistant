<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Repositories\UserRepository;
use Modules\Channel\App\Http\Requests\V1\UserRequest;
use Modules\Channel\App\Http\Resources\UserResource;
use Prettus\Repository\Eloquent\BaseRepository;

class UserController extends BaseController
{
    protected UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return UserResource::class;
    }

    /**
     * Store a newly created user in the current channel.
     *
     * @param UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new UserResource($user),
                trans('channel::app.user.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Update the specified user in the current channel.
     *
     * @param UserRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            // Verify user belongs to current channel
            $channelId = auth('user')->user()?->channel_id;
            $user = \Modules\Channel\App\Models\User::withoutChannelScope()
                ->where('id', $id)
                ->where('channel_id', $channelId)
                ->firstOrFail();

            $user = $this->repository->update($request->validated(), $user->id);
            DB::commit();
            return successResponse(
                new UserResource($user),
                trans('channel::app.user.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
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
     * Remove the specified user from the current channel.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // Verify user belongs to current channel
            $channelId = auth('user')->user()?->channel_id;
            $user = \Modules\Channel\App\Models\User::withoutChannelScope()
                ->where('id', $id)
                ->where('channel_id', $channelId)
                ->firstOrFail();
            
            // Prevent deleting yourself
            if ($user->id === auth('user')->id()) {
                DB::rollBack();
                return errorResponse(
                    trans('channel::app.user.cannot_delete_self'),
                    null,
                    422
                );
            }

            $this->repository->delete($user->id);
            DB::commit();
            return successResponse(null, trans('channel::app.user.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
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

