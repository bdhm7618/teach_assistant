<?php

namespace App\Http\Controllers\Apis\Admin;


use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Channel\App\Models\Channel;
use App\Http\Requests\Api\Admin\ChannelRequest;
use App\Http\Resources\Channel\ChannelResource;


class ChannelController extends Controller
{
    // GET /channels
    public function index()
    {
        try { 

            return successResponse(ChannelResource::collection(Channel::all()));
        } catch (\Exception $e) {

            return errorResponse("Something went wrong!",  $e);
        }
    }


    public function store(ChannelRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data["created_by"] = 1;
            $data["code"] = null;
            $channel = Channel::create($data);
            DB::commit();
            return successResponse(new ChannelResource($channel), "Channel Created Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("Something went wrong!",  $e);
        }
    }

    // GET /channels/{id}
    public function show($id)
    {

        $channel = Channel::findOrFail($id);

        try {
            return successResponse(new ChannelResource($channel));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch channel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // PUT/PATCH /channels/{id}
    public function update(ChannelRequest $request, $id)
    {
        $channel = Channel::findOrFail($id);

        DB::beginTransaction();
        try {
            $channel->update($request->validated());

            DB::commit();

            return successResponse(new ChannelResource($channel), "Channel Updated Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("Something went wrong!",  $e);;
        }
    }

    // DELETE /channels/{id}
    public function destroy($id)
    {
        $channel = Channel::findOrFail($id);

        DB::beginTransaction();
        try {
            $channel->delete();
            DB::commit();
            return response(['message' => 'Channel deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("Something went wrong!",  $e);
        }
    }
}
