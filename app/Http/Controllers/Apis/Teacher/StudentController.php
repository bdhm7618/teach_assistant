<?php

namespace App\Http\Controllers\Apis\Teacher;



use App\Models\Student;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StudentRequest;
use App\Http\Resources\Group\GroupResource;
use App\Http\Resources\Student\StudentResource;
use App\Models\Group;

class StudentController extends Controller
{
    public function getMetaData()
    {
        return successResponse(GroupResource::collection(Group::where("channel_id", $this->channel->id)->get()));
    }
    public function index()
    {
        try {
            $students = Student::where("channel_id", $this->channel->id)->get();
            return successResponse(StudentResource::collection($students));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function show($id)
    {
        $student = Student::where("channel_id", $this->channel->id)->findOrFail($id);

        try {
            return successResponse(new StudentResource($student));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function store(StudentRequest $request)
    {

        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data["channel_id"] = $this->channel->id;
            $student = Student::create($data);

            DB::commit();
            return successResponse(new StudentResource($student));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function update(StudentRequest $request, $id)
    {
        $student = Student::where("channel_id", $this->channel->id)->findOrFail($id);

        DB::beginTransaction();
        try {

            $student->update(attributes: $request->validated());

            DB::commit();
            return successResponse(new StudentResource($student));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function destroy($id)
    {
        $student = Student::where("channel_id", $this->channel->id)->findOrFail($id);

        DB::beginTransaction();
        try {

            $student->delete();

            DB::commit();

            return successResponse("Student deleted successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
