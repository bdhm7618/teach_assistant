<?php

namespace Modules\Student\App\Http\Controllers\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Student\App\Repositories\StudentRepository;
use Modules\Student\App\Http\Requests\V1\StudentRequest;
use Modules\Student\App\Http\Resources\V1\StudentResource;

/**
 * @OA\Tag(name="Students", description="Student management — all routes under /api/v1/{channel_slug}/students")
 */
class StudentController extends BaseController
{
    protected StudentRepository $repository;

    public function __construct(StudentRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }

    protected function getResource(): string
    {
        return StudentResource::class;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/students",
     *     summary="List students with optional filters",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="group_id", in="query", required=false, @OA\Schema(type="integer"), description="Filter by group"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *     @OA\Parameter(name="gender", in="query", required=false, @OA\Schema(type="string", enum={"male","female"})),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Search by name, code, email, or phone"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated student list"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.view")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = $this->repository->makeModel()->newQuery();

            if ($request->filled('group_id')) {
                $query->whereHas('groups', fn ($q) => $q->where('groups.id', $request->group_id));
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                );
            }

            $query->with(['channel', 'groups'])->withCount('groups');

            $students = $query->paginate($request->integer('per_page', 15));

            return successResponse(
                StudentResource::collection($students),
                trans('student::app.student.list_success')
            );
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/students",
     *     summary="Create a new student",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","gender"},
     *             @OA\Property(property="name", type="string", example="Omar Hassan"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true, example="01001234567"),
     *             @OA\Property(property="password", type="string", format="password", nullable=true, minLength=6),
     *             @OA\Property(property="code", type="string", nullable=true, description="Auto-generated if omitted"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, default=1),
     *             @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Student created"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.create"),
     *     @OA\Response(response=422, description="Validation error or duplicate")
     * )
     */
    public function store(StudentRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (empty($data['code'])) {
                $data['code'] = $this->generateStudentCode();
            }

            $groupIds = $data['group_ids'] ?? [];
            unset($data['group_ids']);

            $student = $this->repository->create($data);

            if (! empty($groupIds)) {
                $student->groups()->sync($groupIds);
            }

            $student->load(['channel', 'groups'])->loadCount('groups');

            DB::commit();
            return successResponse(
                new StudentResource($student),
                trans('student::app.student.created'),
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(trans('student::app.validation.student_duplicate'), null, 422);
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/students/{id}",
     *     summary="Get a student with groups and guardians",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Student data"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.view"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function show($id)
    {
        try {
            $student = $this->repository->find($id);
            $student->load(['channel', 'groups', 'guardians'])->loadCount('groups');

            return successResponse(
                new StudentResource($student),
                trans('student::app.student.show_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/students/{id}",
     *     summary="Update a student",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="phone", type="string", nullable=true),
     *             @OA\Property(property="password", type="string", format="password", nullable=true, minLength=6),
     *             @OA\Property(property="status", type="integer", enum={0,1}),
     *             @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Student updated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.update"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(StudentRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $student  = $this->repository->findOrFail($id);
            $data     = $request->validated();

            if (isset($data['password']) && empty($data['password'])) {
                unset($data['password']);
            }

            $groupIds = $data['group_ids'] ?? null;
            unset($data['group_ids']);

            $student = $this->repository->update($data, $student->id);

            if ($groupIds !== null) {
                $student->groups()->sync($groupIds);
            }

            $student->load(['channel', 'groups'])->loadCount('groups');

            DB::commit();
            return successResponse(
                new StudentResource($student),
                trans('student::app.student.updated')
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return errorResponse(trans('student::app.validation.student_duplicate'), null, 422);
            }
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/students/{id}",
     *     summary="Delete a student",
     *     tags={"Students"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Student deleted"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.delete"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $student = $this->repository->findOrFail($id);
            $this->repository->delete($student->id);
            DB::commit();
            return successResponse(null, trans('student::app.student.deleted'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    // -------------------------------------------------------------------------

    protected function generateStudentCode(): string
    {
        $channelId = auth('user')->user()?->channel_id;
        $count = $this->repository->makeModel()->where('channel_id', $channelId)->count();
        $prefix = 'STU';
        do {
            $count++;
            $code = $prefix . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        } while ($this->repository->makeModel()->where('code', $code)->exists());

        return $code;
    }
}
