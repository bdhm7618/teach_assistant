<?php

namespace Modules\Assignment\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Assignment\App\Models\Assignment;
use Modules\Assignment\App\Models\AssignmentAttachment;
use Modules\Assignment\App\Models\AssignmentSubmission;
use Modules\Assignment\App\Http\Requests\V1\AssignmentSubmitRequest;
use Modules\Assignment\App\Http\Requests\V1\AssignmentGradeRequest;
use Modules\Assignment\App\Http\Resources\V1\AssignmentSubmissionResource;
use Modules\Assignment\App\Events\SubmissionGraded as AssignmentSubmissionGraded;

/**
 * @OA\Tag(name="Assignment Submissions", description="Student submission and teacher grading for assignments")
 */
class AssignmentSubmissionController extends BaseController
{
    protected function getRepository(): BaseRepository
    {
        throw new \LogicException('AssignmentSubmissionController does not use a repository.');
    }

    protected function getResource(): string
    {
        return AssignmentSubmissionResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/assignments/{assignmentId}/submissions",
     *     summary="List submissions for an assignment",
     *     tags={"Assignment Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"submitted","graded"})),
     *     @OA\Response(response=200, description="Submission list"),
     *     @OA\Response(response=403, description="Requires assignments.view")
     * )
     */
    public function listForAssignment(Request $request, int $assignmentId): JsonResponse
    {
        Assignment::findOrFail($assignmentId);

        $query = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->with(['student', 'attachments'])
            ->when($request->input('status'), fn($q, $v) => $q->where('status', $v))
            ->latest('submitted_at');

        $data = $query->paginate($request->input('per_page', 15));

        return successResponse(
            AssignmentSubmissionResource::collection($data)->response()->getData(true)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/assignments/{assignmentId}/submissions/{submissionId}",
     *     summary="Get a single submission",
     *     tags={"Assignment Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Submission detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id): JsonResponse
    {
        // Satisfies BaseController abstract contract; actual lookup uses showSubmission().
        return successResponse(null);
    }

    public function showSubmission(int $assignmentId, int $submissionId): JsonResponse
    {
        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->with(['student', 'attachments'])
            ->findOrFail($submissionId);

        return successResponse(
            new AssignmentSubmissionResource($submission),
            __('assignment::app.submission.retrieved')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/assignments/{assignmentId}/submit",
     *     summary="Student submits an assignment",
     *     tags={"Assignment Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *         @OA\Schema(required={"student_id"},
     *             @OA\Property(property="student_id", type="integer"),
     *             @OA\Property(property="answer_text", type="string"),
     *             @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"))
     *         )
     *     )),
     *     @OA\Response(response=201, description="Submission recorded"),
     *     @OA\Response(response=422, description="Cannot submit — closed, past due, or already submitted"),
     *     @OA\Response(response=404, description="Assignment not found")
     * )
     */
    public function submit(AssignmentSubmitRequest $request, int $assignmentId): JsonResponse
    {
        $assignment = Assignment::findOrFail($assignmentId);
        $studentId  = (int) $request->input('student_id');

        [$canSubmit, $errorKey] = $assignment->canBeSubmittedBy($studentId);

        if (!$canSubmit) {
            return errorResponse(__("assignment::app.{$errorKey}"), null, 422);
        }

        $submission = AssignmentSubmission::create([
            'channel_id'    => app('current_channel_id'),
            'assignment_id' => $assignment->id,
            'student_id'    => $studentId,
            'answer_text'   => $request->input('answer_text'),
            'is_late'       => $assignment->isPastDue(),
            'submitted_at'  => now(),
            'status'        => 'submitted',
        ]);

        $this->storeSubmissionAttachments($request, $assignment, $submission);

        $submission->load(['student', 'attachments']);

        return successResponse(
            new AssignmentSubmissionResource($submission),
            __('assignment::app.submission.submitted'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/assignments/{assignmentId}/submissions/{submissionId}/grade",
     *     summary="Teacher grades a submission",
     *     tags={"Assignment Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="assignmentId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submissionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"marks_obtained"},
     *         @OA\Property(property="marks_obtained", type="number", example=85),
     *         @OA\Property(property="teacher_feedback", type="string", nullable=true)
     *     )),
     *     @OA\Response(response=200, description="Submission graded"),
     *     @OA\Response(response=422, description="marks_obtained exceeds total_marks"),
     *     @OA\Response(response=403, description="Requires assignments.update")
     * )
     */
    public function grade(AssignmentGradeRequest $request, int $assignmentId, int $submissionId): JsonResponse
    {
        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->with(['assignment', 'student', 'attachments'])
            ->findOrFail($submissionId);

        $maxMarks = $submission->assignment->total_marks;

        if ($request->marks_obtained > $maxMarks) {
            return errorResponse(
                __('assignment::app.submission.marks_exceed_total', ['max' => $maxMarks]),
                null,
                422
            );
        }

        $submission->grade(
            (float) $request->marks_obtained,
            $request->teacher_feedback
        );

        $submission->refresh()->load(['student', 'attachments']);

        AssignmentSubmissionGraded::dispatch($submission);

        return successResponse(
            new AssignmentSubmissionResource($submission),
            __('assignment::app.submission.graded')
        );
    }

    private function storeSubmissionAttachments(
        Request $request,
        Assignment $assignment,
        AssignmentSubmission $submission
    ): void {
        if (!$request->hasFile('attachments')) {
            return;
        }

        $channelId = app('current_channel_id');

        foreach ($request->file('attachments') as $file) {
            $path = $file->store(
                "assignments/{$channelId}/{$assignment->id}/submissions/{$submission->id}",
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
}
