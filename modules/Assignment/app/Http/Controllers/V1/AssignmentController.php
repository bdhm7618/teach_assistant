<?php

namespace Modules\Assignment\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Prettus\Repository\Eloquent\BaseRepository;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Assignment\App\Models\Assignment;
use Modules\Assignment\App\Models\AssignmentAttachment;
use Modules\Assignment\App\Repositories\AssignmentRepository;
use Modules\Assignment\App\Http\Requests\V1\AssignmentRequest;
use Modules\Assignment\App\Http\Resources\V1\AssignmentResource;
use Modules\Assignment\App\Events\AssignmentPublished;

/**
 * @OA\Tag(name="Assignments", description="Assignment management — create, publish, and manage assignments per group")
 */
class AssignmentController extends BaseController
{
    protected AssignmentRepository $repository;

    public function __construct(AssignmentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return AssignmentResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/assignments",
     *     summary="List assignments",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","published","closed"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Assignment list"),
     *     @OA\Response(response=403, description="Requires assignments.view")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Assignment::withCount('submissions')
            ->with('attachments')
            ->when($request->input('group_id'), fn($q, $v) => $q->where('group_id', $v))
            ->when($request->input('status'),   fn($q, $v) => $q->where('status', $v))
            ->latest();

        $data = $query->paginate($request->input('per_page', 15));

        return successResponse(
            AssignmentResource::collection($data)->response()->getData(true)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/assignments",
     *     summary="Create an assignment",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *         @OA\Schema(required={"group_id","title"},
     *             @OA\Property(property="group_id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="instructions", type="string"),
     *             @OA\Property(property="total_marks", type="number"),
     *             @OA\Property(property="pass_marks", type="number"),
     *             @OA\Property(property="due_at", type="string", format="date-time"),
     *             @OA\Property(property="allow_late_submission", type="boolean"),
     *             @OA\Property(property="late_penalty_percent", type="integer"),
     *             @OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary"))
     *         )
     *     )),
     *     @OA\Response(response=201, description="Assignment created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=403, description="Requires assignments.create")
     * )
     */
    public function store(AssignmentRequest $request): JsonResponse
    {
        $assignment = $this->repository->create($request->except('attachments'));

        $this->storeAttachments($request, $assignment);

        $assignment->load('attachments');

        return successResponse(
            new AssignmentResource($assignment),
            __('assignment::app.created'),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/assignments/{id}",
     *     summary="Get an assignment",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment detail"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $assignment = Assignment::withCount('submissions')->with('attachments')->findOrFail($id);

        return successResponse(
            new AssignmentResource($assignment),
            __('assignment::app.retrieved')
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/assignments/{id}",
     *     summary="Update an assignment",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment updated"),
     *     @OA\Response(response=422, description="Cannot edit published assignment with submissions"),
     *     @OA\Response(response=403, description="Requires assignments.update")
     * )
     */
    public function update(AssignmentRequest $request, int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        if ($assignment->isPublished() && $assignment->submissions()->exists()) {
            return errorResponse(__('assignment::app.cannot_edit_published'), null, 422);
        }

        $assignment->update($request->except('attachments'));

        if ($request->hasFile('attachments')) {
            $this->storeAttachments($request, $assignment);
        }

        $assignment->load('attachments');

        return successResponse(
            new AssignmentResource($assignment),
            __('assignment::app.updated')
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/assignments/{id}",
     *     summary="Delete an assignment",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment deleted"),
     *     @OA\Response(response=422, description="Cannot delete assignment with submissions"),
     *     @OA\Response(response=403, description="Requires assignments.delete")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        if ($assignment->submissions()->exists()) {
            return errorResponse(__('assignment::app.cannot_delete_with_submissions'), null, 422);
        }

        $assignment->delete();

        return successResponse(null, __('assignment::app.deleted'));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/assignments/{id}/publish",
     *     summary="Publish an assignment",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment published"),
     *     @OA\Response(response=422, description="Already published"),
     *     @OA\Response(response=403, description="Requires assignments.update")
     * )
     */
    public function publish(int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);

        if ($assignment->isPublished()) {
            return errorResponse(__('assignment::app.already_published'), null, 422);
        }

        $assignment->update(['status' => 'published']);

        AssignmentPublished::dispatch($assignment->fresh());

        return successResponse(
            new AssignmentResource($assignment->load('attachments')),
            __('assignment::app.published')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/assignments/{id}/close",
     *     summary="Close an assignment (no more submissions)",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Assignment closed"),
     *     @OA\Response(response=403, description="Requires assignments.update")
     * )
     */
    public function close(int $id): JsonResponse
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->update(['status' => 'closed']);

        return successResponse(
            new AssignmentResource($assignment->load('attachments')),
            __('assignment::app.closed')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/assignments/{id}/results",
     *     summary="Get assignment results (all submissions summary)",
     *     tags={"Assignments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Results summary"),
     *     @OA\Response(response=403, description="Requires assignments.view")
     * )
     */
    public function results(int $id): JsonResponse
    {
        $assignment  = Assignment::withCount('submissions')->findOrFail($id);
        $submissions = $assignment->submissions()->with(['student', 'attachments'])->get();
        $graded      = $submissions->where('status', 'graded');
        $passRate    = $graded->count() > 0
            ? round(($graded->where('is_pass', true)->count() / $graded->count()) * 100, 1)
            : null;

        return successResponse([
            'assignment'  => new AssignmentResource($assignment),
            'summary'     => [
                'total_submissions' => $submissions->count(),
                'graded'            => $graded->count(),
                'pending_grade'     => $submissions->where('status', 'submitted')->count(),
                'pass_count'        => $graded->where('is_pass', true)->count(),
                'fail_count'        => $graded->where('is_pass', false)->count(),
                'pass_rate_percent' => $passRate,
                'average_marks'     => $graded->avg('marks_obtained') ? round($graded->avg('marks_obtained'), 2) : null,
                'highest_marks'     => $graded->max('marks_obtained'),
                'lowest_marks'      => $graded->min('marks_obtained'),
            ],
            'submissions' => \Modules\Assignment\App\Http\Resources\V1\AssignmentSubmissionResource::collection($submissions),
        ], __('assignment::app.results_retrieved'));
    }

    private function storeAttachments(Request $request, Assignment $assignment): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        $channelId = app('current_channel_id');

        foreach ($request->file('attachments') as $file) {
            $path = $file->store("assignments/{$channelId}/{$assignment->id}", 'public');

            AssignmentAttachment::create([
                'assignment_id' => $assignment->id,
                'submission_id' => null,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'file_size'     => $file->getSize(),
                'type'          => 'assignment',
            ]);
        }
    }
}
