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
            $groups = Group::where("channel_id", $this->channel->id)->get();
            return successResponse(GroupResource::collection($groups));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }
    public function store(GroupRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $data["channel_id"] = $this->channel->id;

            $data["teacher_id"] = $data["teacher_id"] ?? $this->teacher->id;

            $group = Group::create($data);

            $group->times()->createMany($request->input('times'));

            DB::commit();

            return successResponse(new GroupResource($group->load('times')), "Group Created Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }


    public function show($id)
    {
        try {
            $group = Group::where("channel_id", $this->channel->id)->where("id", $id)->first();
            if (!$group) {
                return errorResponse('Group not found In this Channel!', null, [], 404);
            }

            return successResponse(new GroupResource($group->load('times')));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function update(GroupRequest $request, $id)
    {

        $group = Group::where("channel_id", $this->channel->id)->where("id", $id)->first();
        if (!$group) {
            return errorResponse('Group not found In this Channel!', null, [], 404);
        }

        DB::beginTransaction();

        try {

            $group->update($request->validated());

            if ($request->has('times')) {

                $group->times()->delete();

                $group->times()->createMany($request->input('times'));
            }

            DB::commit();

            return successResponse(new GroupResource($group->load('times')), "Group Updated Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }


    public function destroy($id)
    {
        $group = Group::where("channel_id", $this->channel->id)->where("id", $id)->first();
        if (!$group) {
            return errorResponse('Group not found In this Channel!', null, [], 404);
        }

        DB::beginTransaction();
        try {
            $group->times()->delete();

            $group->delete();

            DB::commit();
            return successResponse(['message' => 'Group Deleted Successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
