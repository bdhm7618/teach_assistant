<?php

namespace Modules\StudentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Modules\Academic\App\Models\StudentEnrollment;
use Modules\Assignment\App\Models\Assignment;
use Modules\Assignment\App\Models\AssignmentAttachment;
use Modules\Assignment\App\Models\AssignmentSubmission;
use Modules\StudentPortal\App\Http\Resources\V1\AssignmentResource;
use Modules\StudentPortal\App\Http\Resources\V1\AssignmentSubmissionResource;

/**
 * @OA\Tag(name="Student Assignments", description="Student portal — assignment list, submit, view results")
 */
class StudentAssignmentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/assignments",
     *     summary="List published assignments for student's enrolled groups",
     *     tags={"Student Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment list with submission status")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $student  = auth('student')->user();

        $groupIds = StudentEnrollment::where('student_id', $student->id)
            ->pluck('group_id');

        $assignments = Assignment::whereIn('group_id', $groupIds)
            ->where('status', 'published')
            ->with(['group', 'attachments'])
            ->when($request->input('group_id'), fn($q, $v) => $q->where('group_id', $v))
            ->orderByDesc('due_at')
            ->get();

        $assignments->each(function ($assignment) use ($student) {
            $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->first();

            $assignment->my_submission = $submission ? [
                'id'             => $submission->id,
                'status'         => $submission->status,
                'is_late'        => $submission->is_late,
                'marks_obtained' => $submission->marks_obtained,
                'is_pass'        => $submission->is_pass,
                'submitted_at'   => $submission->submitted_at,
            ] : null;

            [$can, $reason]               = $assignment->canBeSubmittedBy($student->id);
            $assignment->can_submit        = $can;
            $assignment->cannot_submit_reason = $reason;
        });

        return successResponse(
            AssignmentResource::collection($assignments),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/assignments/{assignment_id}",
     *     summary="Get assignment details",
     *     tags={"Student Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignment_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $assignmentId): JsonResponse
    {
        $student  = auth('student')->user();
        $groupIds = StudentEnrollment::where('student_id', $student->id)->pluck('group_id');

        $assignment = Assignment::whereIn('group_id', $groupIds)
            ->whereIn('status', ['published', 'closed'])
            ->with(['group', 'attachments'])
            ->findOrFail($assignmentId);

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->with('attachments')
            ->first();

        $assignment->my_submission = $submission;

        [$can, $reason]               = $assignment->canBeSubmittedBy($student->id);
        $assignment->can_submit        = $can;
        $assignment->cannot_submit_reason = $reason;

        return successResponse(
            new AssignmentResource($assignment),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/assignments/{assignment_id}/submit",
     *     summary="Submit assignment answer (text + optional file attachments)",
     *     tags={"Student Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignment_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="answer_text", type="string"),
     *                 @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Assignment submitted"),
     *     @OA\Response(response=422, description="Not allowed (already submitted, past due, etc.)")
     * )
     */
    public function submit(Request $request, int $assignmentId): JsonResponse
    {
        $request->validate([
            'answer_text' => 'nullable|string',
            'files'       => 'nullable|array|max:5',
            'files.*'     => 'file|max:10240',
        ]);

        $student  = auth('student')->user();
        $groupIds = StudentEnrollment::where('student_id', $student->id)->pluck('group_id');

        $assignment = Assignment::whereIn('group_id', $groupIds)->findOrFail($assignmentId);

        [$canSubmit, $errorKey] = $assignment->canBeSubmittedBy($student->id);

        if (! $canSubmit) {
            return errorResponse(__('studentportal::app.assignment_not_available') . ' (' . $errorKey . ')', null, 422);
        }

        $isLate = $assignment->isPastDue();

        $submission = AssignmentSubmission::create([
            'channel_id'    => $student->channel_id,
            'assignment_id' => $assignment->id,
            'student_id'    => $student->id,
            'answer_text'   => $request->answer_text,
            'is_late'       => $isLate,
            'status'        => 'submitted',
            'submitted_at'  => now(),
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store(
                    "assignments/{$student->channel_id}/{$assignment->id}/submissions",
                    'public'
                );

                AssignmentAttachment::create([
                    'assignment_id' => $assignment->id,
                    'submission_id' => $submission->id,
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'file_size'     => $file->getSize(),
                    'type'          => 'submission',
                ]);
            }
        }

        return successResponse(
            new AssignmentSubmissionResource($submission->load('attachments', 'assignment')),
            __('studentportal::app.assignment_submitted'),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/assignments/{assignment_id}/submission",
     *     summary="Get student's own submission for an assignment",
     *     tags={"Student Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignment_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Submission with feedback"),
     *     @OA\Response(response=404, description="Not submitted yet")
     * )
     */
    public function mySubmission(int $assignmentId): JsonResponse
    {
        $student = auth('student')->user();

        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('student_id', $student->id)
            ->with(['assignment', 'attachments'])
            ->first();

        if (! $submission) {
            return errorResponse(__('studentportal::app.submission_not_found'), null, 404);
        }

        return successResponse(
            new AssignmentSubmissionResource($submission),
            __('studentportal::app.show_success')
        );
    }
}
