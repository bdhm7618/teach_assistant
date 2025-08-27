<?php

namespace App\Http\Controllers\Apis\Teacher;



use App\Models\Group;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Group\GroupResource;
use App\Http\Requests\Api\Admin\GroupRequest;

class GroupController extends Controller
{
    public function index()
    {
        try {
            $groups = Group::all();
            return successResponse(GroupResource::collection($groups));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function store(GroupRequest $request)
    {
        DB::beginTransaction();
        try {
            $group = Group::create($request->validated());
            DB::commit();
            return successResponse(new GroupResource($group), "Group Created Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function show(Group $group)
    {
        try {
            return successResponse(new GroupResource($group));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function update(GroupRequest $request, Group $group)
    {
        DB::beginTransaction();
        try {
            $group->update($request->validated());
            DB::commit();
            return successResponse(new GroupResource($group), "Group Updated Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function destroy(Group $group)
    {
        DB::beginTransaction();
        try {
            $group->delete();
            DB::commit();
            return successResponse(['message' => 'Group Deleted Successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
