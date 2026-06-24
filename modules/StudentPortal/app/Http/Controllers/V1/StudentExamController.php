<?php

namespace Modules\StudentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Modules\Academic\App\Models\StudentEnrollment;
use Modules\Exam\App\Models\Exam;
use Modules\Exam\App\Models\ExamAnswer;
use Modules\Exam\App\Models\ExamSubmission;
use Modules\StudentPortal\App\Http\Resources\V1\ExamResource;
use Modules\StudentPortal\App\Http\Resources\V1\ExamSubmissionResource;

/**
 * @OA\Tag(name="Student Exams", description="Student portal — exam list, attempt, submit, results")
 */
class StudentExamController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/exams",
     *     summary="List published exams for student's enrolled groups",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam list with attempt status")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $student  = auth('student')->user();

        $groupIds = StudentEnrollment::where('student_id', $student->id)
            ->pluck('group_id');

        $exams = Exam::whereIn('group_id', $groupIds)
            ->where('status', 'published')
            ->with('group')
            ->when($request->input('group_id'), fn($q, $v) => $q->where('group_id', $v))
            ->orderByDesc('starts_at')
            ->get();

        $exams->each(function ($exam) use ($student) {
            $attempts = ExamSubmission::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->get();

            $exam->my_attempts_count = $attempts->count();

            $latest = $attempts->sortByDesc('created_at')->first();
            $exam->my_latest_attempt = $latest ? [
                'id'             => $latest->id,
                'status'         => $latest->status,
                'attempt_number' => $latest->attempt_number,
                'obtained_marks' => $latest->obtained_marks,
                'is_pass'        => $latest->is_pass,
                'submitted_at'   => $latest->submitted_at,
            ] : null;

            [$can, $reason] = $exam->canBeAttemptedBy($student->id);
            $exam->can_attempt          = $can;
            $exam->cannot_attempt_reason = $reason;
        });

        return successResponse(
            ExamResource::collection($exams),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}",
     *     summary="Get exam details with questions (no correct answers revealed)",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam with questions"),
     *     @OA\Response(response=404, description="Exam not found")
     * )
     */
    public function show(int $examId): JsonResponse
    {
        $student = auth('student')->user();

        $groupIds = StudentEnrollment::where('student_id', $student->id)->pluck('group_id');

        $exam = Exam::whereIn('group_id', $groupIds)
            ->where('status', 'published')
            ->with(['group', 'questions.options'])
            ->findOrFail($examId);

        $examData = (new ExamResource($exam))->toArray(request());

        $examData['questions'] = $exam->questions->map(fn($q) => [
            'id'    => $q->id,
            'question' => $q->question,
            'type'     => $q->type,
            'marks'    => $q->marks,
            'order'    => $q->order,
            'options'  => $q->options->map(fn($o) => [
                'id'   => $o->id,
                'text' => $o->text,
                'order'=> $o->order,
                // is_correct NOT exposed to student
            ]),
        ]);

        [$can, $reason] = $exam->canBeAttemptedBy($student->id);
        $examData['can_attempt']           = $can;
        $examData['cannot_attempt_reason'] = $reason;

        return successResponse($examData, __('studentportal::app.show_success'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}/start",
     *     summary="Start an exam attempt",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Attempt started — returns submission_id"),
     *     @OA\Response(response=422, description="Cannot attempt (not published, ended, max attempts, already in progress)")
     * )
     */
    public function start(int $examId): JsonResponse
    {
        $student  = auth('student')->user();
        $groupIds = StudentEnrollment::where('student_id', $student->id)->pluck('group_id');

        $exam = Exam::whereIn('group_id', $groupIds)
            ->findOrFail($examId);

        [$canAttempt, $errorKey] = $exam->canBeAttemptedBy($student->id);

        if (! $canAttempt) {
            return errorResponse(__('studentportal::app.exam_not_available') . ' (' . $errorKey . ')', null, 422);
        }

        $attemptNumber = ExamSubmission::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->max('attempt_number') + 1;

        $submission = ExamSubmission::create([
            'channel_id'     => $student->channel_id,
            'exam_id'        => $exam->id,
            'student_id'     => $student->id,
            'attempt_number' => $attemptNumber,
            'started_at'     => now(),
            'status'         => 'in_progress',
        ]);

        return successResponse(
            new ExamSubmissionResource($submission),
            __('studentportal::app.exam_started'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}/submissions/{submission_id}/answer",
     *     summary="Save a single answer during an in-progress exam",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_id"},
     *             @OA\Property(property="question_id", type="integer"),
     *             @OA\Property(property="selected_option_id", type="integer", nullable=true),
     *             @OA\Property(property="answer_text", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Answer saved"),
     *     @OA\Response(response=422, description="Exam not in progress or not yours")
     * )
     */
    public function saveAnswer(Request $request, int $examId, int $submissionId): JsonResponse
    {
        $request->validate([
            'question_id'        => 'required|integer',
            'selected_option_id' => 'nullable|integer',
            'answer_text'        => 'nullable|string',
        ]);

        $student    = auth('student')->user();
        $submission = ExamSubmission::where('exam_id', $examId)
            ->where('student_id', $student->id)
            ->findOrFail($submissionId);

        if ($submission->status !== 'in_progress') {
            return errorResponse(__('studentportal::app.exam_already_submitted'), null, 422);
        }

        ExamAnswer::updateOrCreate(
            [
                'submission_id' => $submission->id,
                'question_id'   => $request->question_id,
            ],
            [
                'selected_option_id' => $request->selected_option_id,
                'answer_text'        => $request->answer_text,
            ]
        );

        return successResponse(null, __('studentportal::app.answer_saved'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}/submissions/{submission_id}/submit",
     *     summary="Submit exam — triggers auto-grading for objective questions",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 description="Optional — final batch of answers to save before submitting",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer"),
     *                     @OA\Property(property="selected_option_id", type="integer", nullable=true),
     *                     @OA\Property(property="answer_text", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Submission complete with auto-grade results"),
     *     @OA\Response(response=422, description="Already submitted or not yours")
     * )
     */
    public function submit(Request $request, int $examId, int $submissionId): JsonResponse
    {
        $request->validate([
            'answers'                 => 'sometimes|array',
            'answers.*.question_id'   => 'required_with:answers|integer',
            'answers.*.selected_option_id' => 'nullable|integer',
            'answers.*.answer_text'   => 'nullable|string',
        ]);

        $student    = auth('student')->user();
        $submission = ExamSubmission::where('exam_id', $examId)
            ->where('student_id', $student->id)
            ->findOrFail($submissionId);

        if ($submission->status !== 'in_progress') {
            return errorResponse(__('studentportal::app.exam_already_submitted'), null, 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->input('answers', []) as $ans) {
                ExamAnswer::updateOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'question_id'   => $ans['question_id'],
                    ],
                    [
                        'selected_option_id' => $ans['selected_option_id'] ?? null,
                        'answer_text'        => $ans['answer_text'] ?? null,
                    ]
                );
            }

            $submission->update(['submitted_at' => now()]);
            $submission->load('answers');
            $submission->autoGrade();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return errorResponse(__('studentportal::app.operation_failed'), $e);
        }

        return successResponse(
            new ExamSubmissionResource($submission->fresh(['exam', 'answers'])),
            __('studentportal::app.exam_submitted')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}/submissions",
     *     summary="List student's attempts for a specific exam",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Attempt list")
     * )
     */
    public function myAttempts(int $examId): JsonResponse
    {
        $student = auth('student')->user();

        $submissions = ExamSubmission::where('exam_id', $examId)
            ->where('student_id', $student->id)
            ->with('exam')
            ->orderByDesc('attempt_number')
            ->get();

        return successResponse(
            ExamSubmissionResource::collection($submissions),
            __('studentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/student/exams/{exam_id}/submissions/{submission_id}",
     *     summary="Get a single attempt result with answers",
     *     tags={"Student Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Submission detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showAttempt(int $examId, int $submissionId): JsonResponse
    {
        $student    = auth('student')->user();
        $submission = ExamSubmission::where('exam_id', $examId)
            ->where('student_id', $student->id)
            ->with(['exam', 'answers'])
            ->findOrFail($submissionId);

        return successResponse(
            new ExamSubmissionResource($submission),
            __('studentportal::app.show_success')
        );
    }
}
