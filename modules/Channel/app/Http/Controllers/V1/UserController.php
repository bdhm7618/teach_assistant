<?php

namespace Modules\Channel\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Channel\App\Repositories\UserRepository;
use Modules\Channel\App\Http\Requests\V1\UserRequest;
use Modules\Channel\App\Http\Resources\UserResource;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * @OA\Tag(name="Users", description="Channel staff user management — all routes under /api/v1/{channel_slug}/users")
 */
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
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/users",
     *     summary="List channel staff users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=10)),
     *     @OA\Response(response=200, description="Paginated user list"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires users.view")
     * )
     */
    // index() is inherited from BaseController

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/users/{id}",
     *     summary="Get a single channel user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User data"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires users.view"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    // show() is inherited from BaseController

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/users",
     *     summary="Create a new staff user in the channel",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","phone","gender","password","password_confirmation","role_id"},
     *             @OA\Property(property="name", type="string", example="Ahmed Ali"),
     *             @OA\Property(property="email", type="string", format="email", example="ahmed@example.com"),
     *             @OA\Property(property="phone", type="string", example="01001234567"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="role_id", type="integer", example=2),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires users.create"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/users/{id}",
     *     summary="Update a channel user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}),
     *             @OA\Property(property="password", type="string", format="password", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="status", type="integer", enum={0,1})
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires users.update"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UserRequest $request, $id)
    {
        DB::beginTransaction();
        try {
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
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/users/{id}",
     *     summary="Delete a channel user",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires users.delete, or self-deletion attempt"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $channelId = auth('user')->user()?->channel_id;
            $user = \Modules\Channel\App\Models\User::withoutChannelScope()
                ->where('id', $id)
                ->where('channel_id', $channelId)
                ->firstOrFail();

            if ($user->id === auth('user')->id()) {
                DB::rollBack();
                return errorResponse(trans('channel::app.user.cannot_delete_self'), null, 422);
            }

            $this->repository->delete($user->id);
            DB::commit();
            return successResponse(null, trans('channel::app.user.deleted'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
