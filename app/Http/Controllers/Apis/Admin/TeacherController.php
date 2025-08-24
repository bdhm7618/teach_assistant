<?php

namespace App\Http\Controllers\Apis\Admin;



use App\Models\Teacher;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Admin\TeacherRequest;
use App\Http\Resources\Teacher\TeacherResource;

use function Laravel\Prompts\error;

class TeacherController extends Controller
{
    // GET all
    public function index()
    {
        $teachers = Teacher::with('channel')->get();

        return successResponse("Teachers fetched successfully", TeacherResource::collection($teachers));
    }

    // GET one
    public function show(Teacher $Teacher)
    {
        return successResponse("Teacher fetched successfully", new TeacherResource($Teacher));
    }

    // POST create
    public function store(TeacherRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['password'] = Hash::make($data['password']);

            $teacher = Teacher::create($data);
            DB::commit();
            return successResponse(new TeacherResource($teacher), "Teacher created successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    // PUT update
    public function update(TeacherRequest $request, Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $teacher->update($data);
            DB::commit();
            return successResponse(new TeacherResource($teacher), "Teacher Updated successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    // DELETE
    public function destroy(Teacher $teacher)
    {
        DB::beginTransaction();
        try {
            $teacher->delete();
            return successResponse("Teacher deleted successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
