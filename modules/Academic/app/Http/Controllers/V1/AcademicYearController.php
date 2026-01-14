<?php

namespace Modules\Academic\App\Http\Controllers\V1;


use Illuminate\Support\Facades\DB;
use Prettus\Repository\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Channel\App\Http\Controllers\V1\BaseController;
use Modules\Academic\App\Repositories\AcademicYearRepository;
use Modules\Academic\App\Http\Requests\V1\AcademicYearRequest;
use Modules\Academic\App\Http\Resources\V1\AcademicYearResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Academic", description: "Academic management endpoints")]
class AcademicYearController extends BaseController
{
    protected AcademicYearRepository $repository;

    public function __construct(AcademicYearRepository $repository)
    {
        $this->repository = $repository;
    }
    protected function getRepository(): BaseRepository
    {
        return $this->repository;
    }
    protected function getResource(): string
    {
        return AcademicYearResource::class;
    }

    #[OA\Post(
        path: "/api/v1/academic/academic-years",
        summary: "Create a new academic year",
        tags: ["Academic"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    required: ["name", "start_year", "end_year"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "2024-2025"),
                        new OA\Property(property: "start_year", type: "integer", example: 2024),
                        new OA\Property(property: "end_year", type: "integer", example: 2025),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Academic year created successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(AcademicYearRequest $request)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->create($request->validated());
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                trans('academic::app.academic_year.created'),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }



    #[OA\Put(
        path: "/api/v1/academic/academic-years/{id}",
        summary: "Update an academic year",
        tags: ["Academic"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer"),
                example: 1
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "2024-2025"),
                        new OA\Property(property: "start_year", type: "integer", example: 2024),
                        new OA\Property(property: "end_year", type: "integer", example: 2025),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Academic year updated successfully",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(AcademicYearRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->findOrFail($id);

            $year = $this->repository->update($request->validated(), $year->id);
            DB::commit();
            return successResponse(
                new AcademicYearResource($year),
                trans('academic::app.academic_year.updated')
            );
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

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $year = $this->repository->findOrFail($id);
            $this->repository->delete($year);
            DB::commit();
            return successResponse(null, trans('academic::app.academic_year.deleted'));
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }
}
