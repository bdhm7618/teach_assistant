<?php

namespace Modules\Student\App\Http\Controllers\V1;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Student\App\Models\Student;
use Modules\Student\App\Models\Guardian;
use Modules\Student\App\Http\Requests\V1\GuardianRequest;
use Modules\Student\App\Http\Resources\V1\GuardianResource;

/**
 * @OA\Tag(name="Guardians", description="Student guardian / parent management — nested under /api/v1/{channel_slug}/students/{student}/guardians")
 */
class GuardianController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/students/{student}/guardians",
     *     summary="List guardians for a student",
     *     tags={"Guardians"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Guardian list"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.view"),
     *     @OA\Response(response=404, description="Student not found")
     * )
     */
    public function index($channelSlug, $studentId)
    {
        try {
            $student   = Student::findOrFail($studentId);
            $guardians = $student->guardians()->orderByDesc('is_primary')->get();

            return successResponse(
                GuardianResource::collection($guardians),
                trans('student::app.guardian.list_success')
            );
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/students/{student}/guardians",
     *     summary="Add a guardian to a student",
     *     tags={"Guardians"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","phone","relationship"},
     *             @OA\Property(property="name", type="string", example="Hassan Ali"),
     *             @OA\Property(property="phone", type="string", example="01009876543"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="relationship", type="string", enum={"father","mother","brother","sister","uncle","aunt","other"}, example="father"),
     *             @OA\Property(property="is_primary", type="boolean", example=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Guardian added"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.update"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(GuardianRequest $request, $channelSlug, $studentId)
    {
        DB::beginTransaction();
        try {
            $student = Student::findOrFail($studentId);
            $data    = $request->validated();

            if (! empty($data['is_primary'])) {
                $student->guardians()->update(['is_primary' => false]);
            }

            $guardian = $student->guardians()->create([
                ...$data,
                'channel_id' => $student->channel_id,
                'student_id' => $student->id,
            ]);

            DB::commit();
            return successResponse(
                new GuardianResource($guardian),
                trans('student::app.guardian.created'),
                201
            );
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/students/{student}/guardians/{id}",
     *     summary="Get a single guardian",
     *     tags={"Guardians"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Guardian data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($channelSlug, $studentId, $id)
    {
        try {
            $guardian = Guardian::where('student_id', $studentId)->findOrFail($id);
            return successResponse(new GuardianResource($guardian), trans('channel::app.common.show_success'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/{channel_slug}/students/{student}/guardians/{id}",
     *     summary="Update a guardian",
     *     tags={"Guardians"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="relationship", type="string", enum={"father","mother","brother","sister","uncle","aunt","other"}),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Guardian updated"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.update"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(GuardianRequest $request, $channelSlug, $studentId, $id)
    {
        DB::beginTransaction();
        try {
            $guardian = Guardian::where('student_id', $studentId)->findOrFail($id);
            $data     = $request->validated();

            if (! empty($data['is_primary'])) {
                Guardian::where('student_id', $studentId)
                    ->where('id', '!=', $guardian->id)
                    ->update(['is_primary' => false]);
            }

            $guardian->update($data);
            DB::commit();
            return successResponse(new GuardianResource($guardian->fresh()), trans('student::app.guardian.updated'));
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/students/{student}/guardians/{id}",
     *     summary="Remove a guardian from a student",
     *     tags={"Guardians"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="student", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Guardian removed"),
     *     @OA\Response(response=403, description="Insufficient permissions — requires students.update"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy($channelSlug, $studentId, $id)
    {
        DB::beginTransaction();
        try {
            $guardian = Guardian::where('student_id', $studentId)->findOrFail($id);
            $guardian->delete();
            DB::commit();
            return successResponse(null, trans('student::app.guardian.deleted'));
        } catch (ModelNotFoundException $e) {
            return errorResponse(trans('channel::app.common.not_found'), null, 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
