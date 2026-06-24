<?php

namespace Modules\Exam\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Exam\App\Models\Exam;
use Modules\Exam\App\Models\ExamAnswer;
use Modules\Exam\App\Models\ExamSubmission;
use Modules\Exam\App\Http\Requests\V1\ExamSubmitRequest;
use Modules\Exam\App\Http\Requests\V1\ExamGradeRequest;
use Modules\Exam\App\Http\Resources\V1\ExamSubmissionResource;
use Modules\Exam\App\Events\SubmissionGraded as ExamSubmissionGraded;
use Prettus\Repository\Eloquent\BaseRepository;

/**
 * @OA\Tag(name="Exam Submissions", description="Student exam submissions — start, submit answers, grade essays")
 */
class ExamSubmissionController extends BaseController
{
    protected function getRepository(): BaseRepository
    {
        throw new \LogicException('Use direct model access.');
    }

    protected function getResource(): string
    {
        return ExamSubmissionResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/submissions",
     *     summary="List all submissions for an exam (teacher view)",
     *     tags={"Exam Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"in_progress","submitted","graded"})),
     *     @OA\Parameter(name="student_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Submission list"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function index(int $examId): JsonResponse
    {
        $exam = Exam::findOrFail($examId);

        $submissions = $exam->submissions()
            ->with(['student:id,name,code'])
            ->when(request('status'), fn($q) => $q->where('status', request('status')))
            ->when(request('student_id'), fn($q) => $q->where('student_id', request('student_id')))
            ->latest()
            ->get();

        return $this->successResponse(
            ExamSubmissionResource::collection($submissions),
            __('exam.submission.retrieved')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/start",
     *     summary="Start an exam attempt (creates in_progress submission)",
     *     tags={"Exam Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id"},
     *             @OA\Property(property="student_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Exam started — returns submission ID"),
     *     @OA\Response(response=422, description="Exam not available or max attempts reached"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function start(ExamSubmitRequest $request, int $examId): JsonResponse
    {
        $exam = Exam::findOrFail($examId);
        $studentId = $request->student_id;

        [$canAttempt, $errorKey] = $exam->canBeAttemptedBy($studentId);

        if (!$canAttempt) {
            return $this->errorResponse(__($errorKey), 422);
        }

        $attemptNumber = ExamSubmission::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->max('attempt_number') + 1;

        $channelId = app()->has('current_channel_id')
            ? app('current_channel_id')
            : auth('user')->user()->channel_id;

        $submission = ExamSubmission::create([
            'channel_id'     => $channelId,
            'exam_id'        => $exam->id,
            'student_id'     => $studentId,
            'attempt_number' => $attemptNumber,
            'started_at'     => now(),
            'status'         => 'in_progress',
        ]);

        return $this->successResponse(
            new ExamSubmissionResource($submission),
            __('exam.submission.started'),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/submissions/{submission_id}/submit",
     *     summary="Submit exam answers (triggers auto-grading for objective questions)",
     *     tags={"Exam Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id","answers"},
     *             @OA\Property(property="student_id", type="integer"),
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="question_id", type="integer"),
     *                     @OA\Property(property="selected_option_id", type="integer", nullable=true),
     *                     @OA\Property(property="answer_text", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Answers submitted — auto-graded"),
     *     @OA\Response(response=422, description="Submission not in progress or wrong student"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function submit(ExamSubmitRequest $request, int $examId, int $submissionId): JsonResponse
    {
        $submission = ExamSubmission::where('exam_id', $examId)->findOrFail($submissionId);

        if ($submission->status !== 'in_progress') {
            return $this->errorResponse(__('exam.submission.already_submitted'), 422);
        }

        if ($submission->student_id !== $request->student_id) {
            return $this->errorResponse(__('exam.submission.student_mismatch'), 403);
        }

        DB::beginTransaction();
        try {
            foreach ($request->answers as $ans) {
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
            return $this->errorResponse(__('exam.operation_failed'), 500);
        }

        return $this->successResponse(
            new ExamSubmissionResource($submission->fresh(['answers', 'answers.question', 'answers.selectedOption'])),
            __('exam.submission.submitted')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/submissions/{submission_id}",
     *     summary="Get submission details with answers",
     *     tags={"Exam Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Submission with full answer details"),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function show(int $examId, int $submissionId): JsonResponse
    {
        $submission = ExamSubmission::with([
            'student:id,name,code',
            'answers',
            'answers.question',
            'answers.selectedOption',
        ])
            ->where('exam_id', $examId)
            ->findOrFail($submissionId);

        return $this->successResponse(
            new ExamSubmissionResource($submission),
            __('exam.submission.retrieved')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{exam_id}/submissions/{submission_id}/grade",
     *     summary="Teacher grades essay/short-answer questions",
     *     tags={"Exam Submissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="exam_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="submission_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grades"},
     *             @OA\Property(
     *                 property="grades",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="answer_id", type="integer"),
     *                     @OA\Property(property="marks_obtained", type="number")
     *                 )
     *             ),
     *             @OA\Property(property="teacher_notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Grades applied — submission recalculated"),
     *     @OA\Response(response=422, description="Submission not in submitted status"),
     *     @OA\Response(response=403, description="Requires exams.update")
     * )
     */
    public function grade(ExamGradeRequest $request, int $examId, int $submissionId): JsonResponse
    {
        $submission = ExamSubmission::with('answers')
            ->where('exam_id', $examId)
            ->findOrFail($submissionId);

        if ($submission->status === 'in_progress') {
            return $this->errorResponse(__('exam.submission.not_submitted_yet'), 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->grades as $grade) {
                ExamAnswer::where('submission_id', $submission->id)
                    ->where('id', $grade['answer_id'])
                    ->update(['marks_obtained' => $grade['marks_obtained']]);
            }

            if ($request->filled('teacher_notes')) {
                $submission->update(['teacher_notes' => $request->teacher_notes]);
            }

            $submission->load('answers');
            $submission->autoGrade();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(__('exam.operation_failed'), 500);
        }

        $fresh = $submission->fresh(['answers', 'answers.question', 'answers.selectedOption']);

        if ($fresh->status === 'graded') {
            ExamSubmissionGraded::dispatch($fresh);
        }

        return $this->successResponse(
            new ExamSubmissionResource($fresh),
            __('exam.submission.graded')
        );
    }
}
