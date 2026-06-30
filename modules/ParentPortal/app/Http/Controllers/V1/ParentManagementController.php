<?php

namespace Modules\ParentPortal\App\Http\Controllers\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Student\App\Models\Student;
use Modules\ParentPortal\App\Models\ParentAccount;
use Modules\ParentPortal\App\Http\Resources\V1\ParentProfileResource;
use Modules\ParentPortal\App\Http\Resources\V1\ChildResource;

/**
 * @OA\Tag(name="Parents (Staff)", description="Staff-side parent account management — list parents, link/unlink children")
 */
class ParentManagementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parents",
     *     summary="List parent accounts in the channel",
     *     tags={"Parents (Staff)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", description="Match name/email/phone", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated parent list"),
     *     @OA\Response(response=403, description="Requires parents.view")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $parents = ParentAccount::query()
            ->withCount('students')
            ->when($search, fn($q) => $q->where(fn($w) =>
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return successResponse(
            ParentProfileResource::collection($parents)->response()->getData(true),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/{channel_slug}/parents/{parent_id}/children",
     *     summary="List a parent's linked children",
     *     tags={"Parents (Staff)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Children list"),
     *     @OA\Response(response=404, description="Parent not found")
     * )
     */
    public function children(int $parentId): JsonResponse
    {
        $parent = ParentAccount::findOrFail($parentId);

        return successResponse(
            ChildResource::collection($parent->students()->get()),
            __('parentportal::app.show_success')
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/{channel_slug}/parents/{parent_id}/children",
     *     summary="Link a student to a parent account",
     *     tags={"Parents (Staff)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id"},
     *             @OA\Property(property="student_id", type="integer"),
     *             @OA\Property(property="relationship", type="string", enum={"father","mother","brother","sister","uncle","aunt","other"}),
     *             @OA\Property(property="is_primary", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Child linked"),
     *     @OA\Response(response=404, description="Parent or student not found"),
     *     @OA\Response(response=422, description="Already linked")
     * )
     */
    public function attachChild(Request $request, int $parentId): JsonResponse
    {
        $request->validate([
            'student_id'   => 'required|integer',
            'relationship' => 'sometimes|in:father,mother,brother,sister,uncle,aunt,other',
            'is_primary'   => 'sometimes|boolean',
        ]);

        $parent  = ParentAccount::findOrFail($parentId);
        // HasChannelScope keeps both queries inside the current channel.
        $student = Student::findOrFail($request->student_id);

        if ($parent->students()->where('students.id', $student->id)->exists()) {
            return errorResponse(__('parentportal::app.child_already_linked'), null, 422);
        }

        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            // Demote any existing primary for this student across parents.
            $parent->students()->updateExistingPivot($student->id, ['is_primary' => false]);
        }

        $parent->students()->attach($student->id, [
            'channel_id'   => $parent->channel_id,
            'relationship' => $request->input('relationship', 'father'),
            'is_primary'   => $isPrimary,
        ]);

        return successResponse(
            new ChildResource($student->fresh()),
            __('parentportal::app.child_linked'),
            201
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/{channel_slug}/parents/{parent_id}/children/{student_id}",
     *     summary="Unlink a student from a parent account",
     *     tags={"Parents (Staff)"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="channel_slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="parent_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="student_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Child unlinked"),
     *     @OA\Response(response=404, description="Parent not found")
     * )
     */
    public function detachChild(int $parentId, int $studentId): JsonResponse
    {
        $parent = ParentAccount::findOrFail($parentId);
        $parent->students()->detach($studentId);

        return successResponse(null, __('parentportal::app.child_unlinked'));
    }
}
