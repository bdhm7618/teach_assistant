<?php

namespace Modules\Academic\App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\App\Models\Course;
use Modules\Academic\App\Repositories\CourseRepository;
use Modules\Academic\App\Http\Requests\V1\CourseRequest;
use Modules\Academic\App\Http\Resources\V1\CourseResource;

/**
 * @OA\Tag(name="Courses", description="Course management — slug-scoped: /api/v1/{channel_slug}/courses")
 */
class CourseController extends Controller
{
    public function __construct(protected CourseRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/courses",
     *     summary="List courses for the channel",
     *     tags={"Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"draft","active","archived"})),
     *     @OA\Parameter(name="subject_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of courses"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Channel not found")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Course::withCount('groups');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            $courses = $query->with('subject')->latest()->paginate(15);

            return successResponse(
                CourseResource::collection($courses)->response()->getData(true),
                'Courses retrieved successfully'
            );
        } catch (\Exception $e) {
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/courses",
     *     summary="Create a new course",
     *     tags={"Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","type"},
     *                 @OA\Property(property="name", type="string", example="Math Grade 10"),
     *                 @OA\Property(property="type", type="string", enum={"online","offline","hybrid"}),
     *                 @OA\Property(property="status", type="string", enum={"draft","active","archived"}),
     *                 @OA\Property(property="subject_id", type="integer", nullable=true),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="cover_image", type="string", format="binary", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Course created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Channel not found")
     * )
     */
    public function store(CourseRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('cover_image')) {
                $channelId = app('current_channel_id');
                $data['cover_image'] = $request->file('cover_image')
                    ->store("courses/{$channelId}", 'public');
            }

            $course = $this->repository->create($data);
            DB::commit();

            return successResponse(new CourseResource($course->load('subject')), 'Course created', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/courses/{id}",
     *     summary="Get a course",
     *     tags={"Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course details"),
     *     @OA\Response(response=404, description="Course or channel not found")
     * )
     */
    public function show(int $id)
    {
        try {
            $course = Course::withCount('groups')->with('subject')->findOrFail($id);
            return successResponse(new CourseResource($course), 'Course retrieved');
        } catch (\Exception $e) {
            return errorResponse('Course not found', null, 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/courses/{id}",
     *     summary="Update a course (use POST with _method=PATCH for multipart)",
     *     tags={"Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="type", type="string", enum={"online","offline","hybrid"}),
     *                 @OA\Property(property="status", type="string", enum={"draft","active","archived"}),
     *                 @OA\Property(property="cover_image", type="string", format="binary", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Course updated"),
     *     @OA\Response(response=404, description="Course or channel not found")
     * )
     */
    public function update(CourseRequest $request, int $id)
    {
        DB::beginTransaction();
        try {
            $course = Course::findOrFail($id);
            $data   = $request->validated();

            if ($request->hasFile('cover_image')) {
                if ($course->cover_image) {
                    Storage::disk('public')->delete($course->cover_image);
                }
                $channelId = app('current_channel_id');
                $data['cover_image'] = $request->file('cover_image')
                    ->store("courses/{$channelId}", 'public');
            }

            $course->update($data);
            DB::commit();

            return successResponse(new CourseResource($course->load('subject')), 'Course updated');
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/courses/{id}",
     *     summary="Delete (soft-delete) a course",
     *     tags={"Courses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Course deleted"),
     *     @OA\Response(response=422, description="Course has active groups"),
     *     @OA\Response(response=404, description="Course or channel not found")
     * )
     */
    public function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $course = Course::withCount('activeGroups')->findOrFail($id);

            if ($course->active_groups_count > 0) {
                DB::rollBack();
                return errorResponse('Archive all active groups before deleting this course', null, 422);
            }

            $course->delete();
            DB::commit();

            return successResponse(null, 'Course deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse('Operation failed', $e);
        }
    }
}
