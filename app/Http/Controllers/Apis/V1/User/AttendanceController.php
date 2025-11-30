<?php

namespace App\Http\Controllers\Apis\Teacher;

use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\SessionTime;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Attendance\GroupResource;
use App\Http\Requests\Api\Admin\AttendanceRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function getGroups()
    {
        $results = SessionTime::with([
            'group.students' => function ($q) {
                $q->select('id', 'name', 'email', 'group_id', "code", "phone");
            },

            'group.students.attendanceForToday'
        ])
            ->whereHas('group', function ($q) {
                $q->where('channel_id', $this->channel->id);
            })
            ->where('day_name', strtolower(date("l")))
            ->get();


        return successResponse(GroupResource::collection($results));
    }
    public function index()
    {
        try {
            $attendance = Attendance::all();
            return successResponse(AttendanceResource::collection($attendance));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function show($id)
    {
        try {
            $record = Attendance::findOrFail($id);
            return successResponse(new AttendanceResource($record));
        } catch (\Exception $e) {
            return errorResponse("error", $e);
        }
    }

    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();

        $date = date("Y-m-d");

        $data_insert = [];
        foreach ($data["attendance"] as $attendance) {
            $attendance["session_time_id"]  = $data["session_time_id"];
            $attendance["date"]  = $date;
            $data_insert[] = $attendance;
        }
       
        DB::beginTransaction();
        try {
            $record = DB::table('attendance')->insert($data_insert);
            DB::commit();
            return successResponse($record);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function change(Request $request, $id)
    {
        $data = $request->validate([
            'status'     => 'required|in:present,absent,late',
            'session_time_id'   => 'required|exists:session_times,id',
        ]);

        $student = Student::findOrFail($id);

        DB::beginTransaction();

        try {

            $session_time = SessionTime::find($data["session_time_id"]);

            if ($session_time?->group_id != $student?->group_id) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'student_id' => [
                            "This student ID [{$student?->id}] is not a member of the specified group number [{$session_time?->group_id}]."
                        ]
                    ]
                ], 422);
            };

            $attendance = Attendance::where([
                ["session_time_id", "=", $data['session_time_id']],
                ["student_id", "=", $student->id],
                ["date", "=", date("Y-m-d")],
            ])->first();


            if ($attendance) {
                $attendance->status   = $data['status'];
                $attendance->save();
            } else {
                $attendance = Attendance::create([
                    "session_time_id" => $data['session_time_id'],
                    "student_id"      => $student->id,
                    "date"            => date("Y-m-d"),
                    "status"          => $data['status'],
                ]);
            }
            DB::commit();
            return successResponse(true);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function update(AttendanceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $record = Attendance::findOrFail($id);
            $record->update($request->validated());
            DB::commit();
            return successResponse(new AttendanceResource($record));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $record = Attendance::findOrFail($id);
            $record->delete();
            DB::commit();
            return successResponse("Attendance deleted successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse("error", $e);
        }
    }
}
