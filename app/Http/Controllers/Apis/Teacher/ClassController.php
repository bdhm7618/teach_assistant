<?php

namespace App\Http\Controllers\Apis\Teacher;

use App\Models\ClassModel;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Class\ClassResource;
use App\Http\Requests\Api\Admin\ClassRequest;
use Illuminate\Validation\ValidationException;


class ClassController extends Controller
{
    public function index()
    {

        try {
            $classes = ClassModel::where("channel_id", $this->channel->id)->get();

            return successResponse(ClassResource::collection($classes));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function store(ClassRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data["channel_id"] = $this->channel->id;
            $class = ClassModel::create($data);
            DB::commit();
            return successResponse(new ClassResource($class), "Classroom Created Successfully");
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function show($id)
    {
        $class = ClassModel::where("channel_id", $this->channel->id)->where("id", $id)->first();
        if (!$class) {
            return errorResponse('Class not found In this Channel!', null, [], 404);
        }

        try {
            return successResponse(new ClassResource($class));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function update(ClassRequest $request, ClassModel $class)
    {
        DB::beginTransaction();
        try {
            $class->update($request->validated());
            DB::commit();
            return successResponse(new ClassResource($class), "ClassRoom Updated Successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function destroy(ClassModel $class)
    {
        DB::beginTransaction();
        try {
            $class->delete();
            DB::commit();
            return SuccessResponse(['message' => 'Class deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
