<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Repositories\RoleRepository;
use Modules\Channel\App\Http\Requests\V1\RoleRequest;
use Modules\Channel\App\Http\Resources\RoleResource;
use Prettus\Repository\Eloquent\BaseRepository;

class RoleController extends BaseController
{
    protected RoleRepository $repository;

    public function __construct(RoleRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return RoleResource::class;
    }

    /**
     * Store a newly created role.
     *
     * @param RoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RoleRequest $request)
    {
        DB::beginTransaction();
        try {
            $role = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new RoleResource($role),
                trans('channel::app.role.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Update the specified role.
     *
     * @param RoleRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RoleRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $role = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;
            
            // Prevent modifying system roles (owner)
            if ($role->name === 'owner') {
                return errorResponse(
                    trans('channel::app.role.cannot_modify_system_role'),
                    null,
                    422
                );
            }

            // Prevent modifying general roles (channel_id = null) by channel users
            // Only admins can modify general roles
            if ($role->isGeneral() && $channelId !== null) {
                return errorResponse(
                    trans('channel::app.role.cannot_modify_general_role'),
                    null,
                    422
                );
            }

            // Ensure channel_id cannot be changed for channel-specific roles
            $data = $request->validated();
            if ($role->isChannelSpecific() && isset($data['channel_id'])) {
                unset($data['channel_id']); // Prevent changing channel_id
            }

            $role = $this->repository->update($data, $role->id);
            DB::commit();
            return successResponse(
                new RoleResource($role),
                trans('channel::app.role.updated')
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
     * Remove the specified role.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $role = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;
            
            // Prevent deleting system roles
            if (in_array($role->name, ['owner', 'teacher', 'assistant', 'viewer'])) {
                return errorResponse(
                    trans('channel::app.role.cannot_delete_system_role'),
                    null,
                    422
                );
            }

            // Prevent deleting general roles (channel_id = null) by channel users
            // Only admins can delete general roles
            if ($role->isGeneral() && $channelId !== null) {
                return errorResponse(
                    trans('channel::app.role.cannot_delete_general_role'),
                    null,
                    422
                );
            }

            // Check if role is assigned to any users
            $usersCount = \Modules\Channel\App\Models\User::where('role_id', $role->id)->count();
            if ($usersCount > 0) {
                return errorResponse(
                    trans('channel::app.role.cannot_delete_assigned_role', ['count' => $usersCount]),
                    null,
                    422
                );
            }

            $this->repository->delete($role->id);
            DB::commit();
            return successResponse(null, trans('channel::app.role.deleted'));
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

