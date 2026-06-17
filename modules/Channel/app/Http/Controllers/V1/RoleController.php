<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Channel\App\Repositories\RoleRepository;
use Modules\Channel\App\Http\Requests\V1\RoleRequest;
use Modules\Channel\App\Http\Resources\RoleResource;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * @OA\Tag(name="Roles", description="Channel role management — all routes under /api/v1/{channel_slug}/roles")
 */
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
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/roles",
     *     summary="List roles visible to this channel (channel-specific + general system roles)",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Paginated role list"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires roles.view")
     * )
     */
    // index() is inherited from BaseController

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/roles/{id}",
     *     summary="Get a single role",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role data"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires roles.view"),
     *     @OA\Response(response=404, description="Role not found")
     * )
     */
    // show() is inherited from BaseController

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/roles",
     *     summary="Create a channel-specific custom role",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","permissions"},
     *             @OA\Property(property="name", type="string", example="accountant"),
     *             @OA\Property(property="description", type="string", example="Handles payment records"),
     *             @OA\Property(property="permissions", type="array",
     *                 @OA\Items(type="string", example="payments.view")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Role created"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires roles.create"),
     *     @OA\Response(response=422, description="Validation error or system role conflict")
     * )
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
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/roles/{id}",
     *     summary="Update a channel-specific role",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="permissions", type="array",
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role updated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires roles.update"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=422, description="System role cannot be modified")
     * )
     */
    public function update(RoleRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $role      = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;

            if ($role->name === 'owner') {
                return errorResponse(trans('channel::app.role.cannot_modify_system_role'), null, 422);
            }
            if ($role->isGeneral() && $channelId !== null) {
                return errorResponse(trans('channel::app.role.cannot_modify_general_role'), null, 422);
            }

            $data = $request->validated();
            if ($role->isChannelSpecific()) {
                unset($data['channel_id']);
            }

            $role = $this->repository->update($data, $role->id);
            DB::commit();
            return successResponse(new RoleResource($role), trans('channel::app.role.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/roles/{id}",
     *     summary="Delete a channel-specific custom role",
     *     tags={"Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Role deleted"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires roles.delete"),
     *     @OA\Response(response=404, description="Role not found"),
     *     @OA\Response(response=422, description="System role cannot be deleted, or role is assigned to users")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $role      = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;

            if (in_array($role->name, ['owner', 'teacher', 'assistant', 'viewer'])) {
                return errorResponse(trans('channel::app.role.cannot_delete_system_role'), null, 422);
            }
            if ($role->isGeneral() && $channelId !== null) {
                return errorResponse(trans('channel::app.role.cannot_delete_general_role'), null, 422);
            }

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
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
