<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\SubjectRepository;
use Modules\Academic\App\Http\Requests\V1\SubjectRequest;
use Modules\Academic\App\Http\Resources\V1\SubjectResource;
use Modules\Academic\App\Models\SubjectTranslations;

/**
 * @OA\Tag(name="Subjects", description="Subject management — channel-specific and general subjects. All routes under /api/v1/{channel_slug}/subjects")
 */
class SubjectController extends BaseController
{
    protected SubjectRepository $repository;

    public function __construct(SubjectRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return SubjectResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/subjects",
     *     summary="List subjects visible to this channel (channel-specific + general)",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_active", in="query", required=false, @OA\Schema(type="boolean"), description="Filter by active status"),
     *     @OA\Parameter(name="is_general", in="query", required=false, @OA\Schema(type="boolean"), description="true = general subjects only, false = channel-specific only"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated subject list with locale-aware names"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires subjects.view")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_general')) {
                $request->boolean('is_general')
                    ? $query->whereNull('channel_id')
                    : $query->whereNotNull('channel_id');
            }

            $locale = app()->getLocale();
            $query->with(['translations' => fn ($q) => $q->where('locale', $locale)]);

            $subjects = $query->paginate($request->integer('per_page', 15));

            return successResponse(
                SubjectResource::collection($subjects),
                trans('academic::app.subject.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/subjects",
     *     summary="Create a channel-specific subject with bilingual translations",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code","credits","translations"},
     *             @OA\Property(property="code", type="string", example="MATH101"),
     *             @OA\Property(property="credits", type="integer", example=3),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="translations", type="object",
     *                 @OA\Property(property="en", type="object",
     *                     @OA\Property(property="name", type="string", example="Mathematics"),
     *                     @OA\Property(property="description", type="string", nullable=true)
     *                 ),
     *                 @OA\Property(property="ar", type="object",
     *                     @OA\Property(property="name", type="string", example="الرياضيات"),
     *                     @OA\Property(property="description", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Subject created"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires subjects.create"),
     *     @OA\Response(response=422, description="Validation error or duplicate code")
     * )
     */
    public function store(SubjectRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['channel_id'] = auth('user')->user()?->channel_id;

            $translations = $data['translations'] ?? [];
            unset($data['translations']);

            $subject = $this->repository->create($data);

            if (! empty($translations)) {
                $this->saveTranslations($subject, $translations);
            }

            $locale = app()->getLocale();
            $subject->load(['translations' => fn ($q) => $q->where('locale', $locale)]);

            DB::commit();
            return successResponse(
                new SubjectResource($subject),
                trans('academic::app.subject.created'),
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.subject_duplicate', ['code' => $request->input('code')]),
                    null,
                    422
                );
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/subjects/{id}",
     *     summary="Get a subject with all translations",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subject data"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires subjects.view"),
     *     @OA\Response(response=404, description="Subject not found")
     * )
     */
    public function show($id)
    {
        try {
            $subject = $this->repository->find($id);

            $locale = app()->getLocale();
            $subject->load(['translations' => fn ($q) => $q->where('locale', $locale)]);

            return successResponse(
                new SubjectResource($subject),
                trans('academic::app.subject.show_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/subjects/{id}",
     *     summary="Update a channel-specific subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="credits", type="integer"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="translations", type="object",
     *                 @OA\Property(property="en", type="object",
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string", nullable=true)
     *                 ),
     *                 @OA\Property(property="ar", type="object",
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subject updated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires subjects.update"),
     *     @OA\Response(response=404, description="Subject not found or belongs to another channel"),
     *     @OA\Response(response=422, description="Validation error or duplicate code")
     * )
     */
    public function update(SubjectRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $subject   = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;

            if ($subject->channel_id !== $channelId) {
                return errorResponse(trans('channel::app.common.not_found'), null, 404);
            }

            $data = $request->validated();
            unset($data['channel_id']);

            $translations = $data['translations'] ?? null;
            unset($data['translations']);

            $subject = $this->repository->update($data, $subject->id);

            if ($translations !== null) {
                $this->saveTranslations($subject, $translations, true);
            }

            $locale = app()->getLocale();
            $subject->load(['translations' => fn ($q) => $q->where('locale', $locale)]);

            DB::commit();
            return successResponse(new SubjectResource($subject), trans('academic::app.subject.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.subject_duplicate', ['code' => $request->input('code')]),
                    null,
                    422
                );
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/subjects/{id}",
     *     summary="Delete a channel-specific subject",
     *     tags={"Subjects"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subject deleted"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires subjects.delete"),
     *     @OA\Response(response=404, description="Subject not found or belongs to another channel")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $subject   = $this->repository->findOrFail($id);
            $channelId = auth('user')->user()?->channel_id;

            if ($subject->channel_id !== $channelId) {
                return errorResponse(trans('channel::app.common.not_found'), null, 404);
            }

            $this->repository->delete($subject->id);
            DB::commit();
            return successResponse(null, trans('academic::app.subject.deleted'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    // -------------------------------------------------------------------------

    protected function saveTranslations($subject, array $translations, bool $upsert = false): void
    {
        foreach ($translations as $locale => $data) {
            if ($upsert) {
                SubjectTranslations::updateOrCreate(
                    ['subject_id' => $subject->id, 'locale' => $locale],
                    ['name' => $data['name'] ?? null, 'description' => $data['description'] ?? null]
                );
            } else {
                SubjectTranslations::create([
                    'subject_id'  => $subject->id,
                    'locale'      => $locale,
                    'name'        => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);
            }
        }
    }
}
