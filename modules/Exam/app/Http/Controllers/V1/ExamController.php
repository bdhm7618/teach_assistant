<?php

namespace Modules\Exam\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Exam\App\Models\Exam;
use Modules\Exam\App\Repositories\ExamRepository;
use Modules\Exam\App\Http\Requests\V1\ExamRequest;
use Modules\Exam\App\Http\Resources\V1\ExamResource;

/**
 * @OA\Tag(name="Exams", description="Exam management — create, publish, and manage exams per group")
 */
class ExamController extends BaseController
{
    protected ExamRepository $repository;

    public function __construct(ExamRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return ExamResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams",
     *     summary="List exams",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","published","closed"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Exam list"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Exam::withCount(['questions', 'submissions'])
            ->when($request->group_id, fn($q) => $q->where('group_id', $request->group_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest();

        $data = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            ExamResource::collection($data)->response()->getData(true),
            __('exam.retrieved')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams",
     *     summary="Create an exam",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"group_id","title"},
     *             @OA\Property(property="group_id", type="integer"),
     *             @OA\Property(property="course_id", type="integer", nullable=true),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="duration_minutes", type="integer", nullable=true),
     *             @OA\Property(property="total_marks", type="number", example=100),
     *             @OA\Property(property="pass_marks", type="number", example=50),
     *             @OA\Property(property="allow_retake", type="boolean", example=false),
     *             @OA\Property(property="max_attempts", type="integer", example=1),
     *             @OA\Property(property="starts_at", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="ends_at", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Exam created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Requires exams.create")
     * )
     */
    public function store(ExamRequest $request): JsonResponse
    {
        $exam = $this->repository->create($request->validated());

        return $this->successResponse(
            new ExamResource($exam),
            __('exam.created'),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{id}",
     *     summary="Get exam details",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam details with questions"),
     *     @OA\Response(response=404, description="Exam not found"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $exam = Exam::with(['questions.options'])
            ->withCount(['questions', 'submissions'])
            ->findOrFail($id);

        return $this->successResponse(
            new ExamResource($exam),
            __('exam.retrieved')
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/exams/{id}",
     *     summary="Update an exam",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExamRequest")),
     *     @OA\Response(response=200, description="Exam updated"),
     *     @OA\Response(response=422, description="Cannot edit a published exam with submissions"),
     *     @OA\Response(response=403, description="Requires exams.update")
     * )
     */
    public function update(ExamRequest $request, int $id): JsonResponse
    {
        $exam = Exam::findOrFail($id);

        if ($exam->status === 'published' && $exam->submissions()->exists()) {
            return $this->errorResponse(__('exam.cannot_edit_published'), 422);
        }

        $exam->update($request->validated());

        return $this->successResponse(
            new ExamResource($exam->fresh()),
            __('exam.updated')
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/exams/{id}",
     *     summary="Delete an exam (soft)",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam deleted"),
     *     @OA\Response(response=422, description="Cannot delete exam with submissions"),
     *     @OA\Response(response=403, description="Requires exams.delete")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $exam = Exam::findOrFail($id);

        if ($exam->submissions()->exists()) {
            return $this->errorResponse(__('exam.cannot_delete_with_submissions'), 422);
        }

        $exam->delete();

        return $this->successResponse(null, __('exam.deleted'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{id}/publish",
     *     summary="Publish an exam (makes it available to students)",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam published"),
     *     @OA\Response(response=422, description="Exam has no questions"),
     *     @OA\Response(response=403, description="Requires exams.update")
     * )
     */
    public function publish(int $id): JsonResponse
    {
        $exam = Exam::withCount('questions')->findOrFail($id);

        if ($exam->questions_count === 0) {
            return $this->errorResponse(__('exam.no_questions'), 422);
        }

        if ($exam->status === 'published') {
            return $this->errorResponse(__('exam.already_published'), 422);
        }

        $exam->update(['status' => 'published']);

        return $this->successResponse(
            new ExamResource($exam->fresh()),
            __('exam.published')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/exams/{id}/close",
     *     summary="Close an exam (no more submissions accepted)",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Exam closed"),
     *     @OA\Response(response=403, description="Requires exams.update")
     * )
     */
    public function close(int $id): JsonResponse
    {
        $exam = Exam::findOrFail($id);
        $exam->update(['status' => 'closed']);

        return $this->successResponse(
            new ExamResource($exam->fresh()),
            __('exam.closed')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/exams/{id}/results",
     *     summary="Get exam results summary (all submissions)",
     *     tags={"Exams"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Results summary"),
     *     @OA\Response(response=403, description="Requires exams.view")
     * )
     */
    public function results(int $id): JsonResponse
    {
        $exam = Exam::findOrFail($id);

        $submissions = $exam->submissions()
            ->with(['student:id,name,code', 'answers'])
            ->whereIn('status', ['submitted', 'graded'])
            ->latest('submitted_at')
            ->get();

        $gradedSubmissions = $submissions->where('status', 'graded');

        $stats = [
            'total_submissions' => $submissions->count(),
            'graded'            => $gradedSubmissions->count(),
            'pending_grading'   => $submissions->where('status', 'submitted')->count(),
            'pass_count'        => $gradedSubmissions->where('is_pass', true)->count(),
            'fail_count'        => $gradedSubmissions->where('is_pass', false)->count(),
            'average_marks'     => $gradedSubmissions->avg('obtained_marks'),
            'highest_marks'     => $gradedSubmissions->max('obtained_marks'),
            'lowest_marks'      => $gradedSubmissions->min('obtained_marks'),
        ];

        return $this->successResponse(
            ['exam' => new ExamResource($exam), 'stats' => $stats, 'submissions' => \Modules\Exam\App\Http\Resources\V1\ExamSubmissionResource::collection($submissions)],
            __('exam.results_retrieved')
        );
    }
}
