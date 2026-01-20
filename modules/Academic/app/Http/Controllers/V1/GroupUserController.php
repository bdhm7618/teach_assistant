<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\GroupUser;
use Modules\Channel\App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupUserController extends BaseController
{
    public function index($groupId)
    {
        try {
            $group = Group::findOrFail($groupId);
            $groupUsers = $group->groupUsers()->with('user')->get();
            
            return successResponse(
                $groupUsers->map(function ($groupUser) {
                    return [
                        'id' => $groupUser->id,
                        'user_id' => $groupUser->user_id,
                        'user' => [
                            'id' => $groupUser->user->id,
                            'name' => $groupUser->user->name,
                            'email' => $groupUser->user->email,
                        ],
                        'role_type' => $groupUser->role_type,
                        'status' => $groupUser->status,
                        'joined_at' => $groupUser->joined_at?->toDateTimeString(),
                        'notes' => $groupUser->notes,
                    ];
                }),
                'Group users retrieved successfully'
            );
        } catch (\Exception $e) {
            return errorResponse('Operation failed', $e);
        }
    }

    public function store(Request $request, $groupId)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_type' => 'required|in:teacher,assistant,helper,coordinator',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return errorResponse('Validation failed', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $group = Group::findOrFail($groupId);
            $user = User::findOrFail($request->user_id);

            // Check if user already in group
            if ($group->groupUsers()->where('user_id', $user->id)->exists()) {
                return errorResponse('User already assigned to this group', null, 422);
            }

            $groupUser = GroupUser::create([
                'channel_id' => $group->channel_id,
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role_type' => $request->role_type,
                'status' => $request->status ?? 'active',
                'joined_at' => now(),
                'notes' => $request->notes,
            ]);

            DB::commit();
            return successResponse(
                [
                    'id' => $groupUser->id,
                    'user_id' => $groupUser->user_id,
                    'role_type' => $groupUser->role_type,
                    'status' => $groupUser->status,
                ],
                'User assigned to group successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    public function update(Request $request, $groupId, $userId)
    {
        $validator = Validator::make($request->all(), [
            'role_type' => 'nullable|in:teacher,assistant,helper,coordinator',
            'status' => 'nullable|in:active,inactive,removed',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return errorResponse('Validation failed', $validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $groupUser = GroupUser::where('group_id', $groupId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $groupUser->update($request->only(['role_type', 'status', 'notes']));

            DB::commit();
            return successResponse(
                [
                    'id' => $groupUser->id,
                    'user_id' => $groupUser->user_id,
                    'role_type' => $groupUser->role_type,
                    'status' => $groupUser->status,
                ],
                'Group user updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    public function destroy($groupId, $userId)
    {
        DB::beginTransaction();
        try {
            $groupUser = GroupUser::where('group_id', $groupId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $groupUser->delete();

            DB::commit();
            return successResponse(null, 'User removed from group successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }
}

