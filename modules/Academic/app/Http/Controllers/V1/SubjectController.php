<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\SubjectRepository;
use Modules\Academic\App\Http\Requests\V1\SubjectRequest;
use Modules\Academic\App\Http\Resources\V1\SubjectResource;
use Modules\Academic\App\Models\SubjectTranslations;

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
     * Display a listing of subjects
     */
    public function index(\Illuminate\Http\Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            // Apply filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('is_general')) {
                if ($request->boolean('is_general')) {
                    $query->whereNull('channel_id');
                } else {
                    $query->whereNotNull('channel_id');
                }
            }

            // Load translations
            $locale = app()->getLocale();
            $query->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $subjects = $query->paginate($perPage);

            return successResponse(
                SubjectResource::collection($subjects),
                trans('academic::app.subject.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Store a newly created subject
     */
    public function store(SubjectRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $channelId = auth('user')->user()?->channel_id;
            
            // Set channel_id for channel-specific subjects
            $data['channel_id'] = $channelId;
            
            // Remove translations from data (will be handled separately)
            $translations = $data['translations'] ?? [];
            unset($data['translations']);

            $subject = $this->repository->create($data);

            // Create translations
            if (!empty($translations)) {
                $this->createTranslations($subject, $translations);
            }
            
            // Load relationships
            $locale = app()->getLocale();
            $subject->load(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

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
                    trans('academic::app.validation.subject_duplicate', [
                        'code' => $request->input('code')
                    ]),
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
     * Display the specified subject
     */
    public function show($id)
    {
        try {
            $subject = $this->repository->find($id);
            
            // Load translations
            $locale = app()->getLocale();
            $subject->load(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

            return successResponse(
                new SubjectResource($subject),
                trans('academic::app.subject.show_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Update the specified subject
     */
    public function update(SubjectRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $subject = $this->repository->findOrFail($id);
            
            // Check if subject belongs to channel (can't update general subjects)
            $channelId = auth('user')->user()?->channel_id;
            if ($subject->channel_id !== $channelId) {
                return errorResponse(
                    trans('channel::app.common.not_found'),
                    null,
                    404
                );
            }
            
            $data = $request->validated();
            
            // Don't update channel_id
            unset($data['channel_id']);
            
            // Handle translations separately
            $translations = $data['translations'] ?? null;
            unset($data['translations']);

            $subject = $this->repository->update($data, $subject->id);

            // Update translations if provided
            if ($translations !== null) {
                $this->updateTranslations($subject, $translations);
            }
            
            // Load relationships
            $locale = app()->getLocale();
            $subject->load(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }]);

            DB::commit();
            return successResponse(
                new SubjectResource($subject),
                trans('academic::app.subject.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(
                    trans('academic::app.validation.subject_duplicate', [
                        'code' => $request->input('code')
                    ]),
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
     * Remove the specified subject
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $subject = $this->repository->findOrFail($id);
            
            // Check if subject belongs to channel (can't delete general subjects)
            $channelId = auth('user')->user()?->channel_id;
            if ($subject->channel_id !== $channelId) {
                return errorResponse(
                    trans('channel::app.common.not_found'),
                    null,
                    404
                );
            }
            
            $this->repository->delete($subject->id);
            DB::commit();
            return successResponse(null, trans('academic::app.subject.deleted'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(
                trans('channel::app.common.not_found'),
                null,
                404
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Create translations for a subject
     */
    protected function createTranslations($subject, array $translations)
    {
        foreach ($translations as $locale => $translationData) {
            SubjectTranslations::create([
                'subject_id' => $subject->id,
                'locale' => $locale,
                'name' => $translationData['name'] ?? null,
                'description' => $translationData['description'] ?? null,
            ]);
        }
    }

    /**
     * Update translations for a subject
     */
    protected function updateTranslations($subject, array $translations)
    {
        foreach ($translations as $locale => $translationData) {
            SubjectTranslations::updateOrCreate(
                [
                    'subject_id' => $subject->id,
                    'locale' => $locale,
                ],
                [
                    'name' => $translationData['name'] ?? null,
                    'description' => $translationData['description'] ?? null,
                ]
            );
        }
    }
}
