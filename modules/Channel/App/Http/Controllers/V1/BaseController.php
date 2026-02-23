<?php

namespace Modules\Channel\App\Http\Controllers\V1;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Eloquent\BaseRepository;
use OpenApi\Attributes as OA;

abstract class BaseController extends Controller
{
    /**
     * Get the repository instance for the current controller
     * 
     * @return BaseRepository
     */
    abstract protected function getRepository(): BaseRepository;

    /**
     * Get the resource class name for the current controller
     * 
     * @return string
     */
    abstract protected function getResource(): string;

    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "/api/v1/{resource}",
        summary: "Get list of resources",
        tags: ["Resources"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "limit",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", default: 10),
                example: 10
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of resources",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index(Request $request)
    {
        try {
            $repository = $this->getRepository();
            $perPage = $request->input('limit', 10);

            $resources = $repository->paginate($perPage);
            $resourceClass = $this->getResource();

            $metadata = [
                'current_page' => $resources->currentPage(),
                'per_page' => $resources->perPage(),
                'total' => $resources->total(),
                'last_page' => $resources->lastPage(),
                'from' => $resources->firstItem(),
                'to' => $resources->lastItem(),
                'has_more_pages' => $resources->hasMorePages(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => trans('channel::app.common.list_success'),
                'data' => $resourceClass::collection($resources),
                'meta' => $metadata
            ], 200);
        } catch (\Exception $e) {
            return errorResponse(trans('channel::app.common.operation_failed'), $e);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "/api/v1/{resource}/{id}",
        summary: "Get a specific resource",
        tags: ["Resources"],
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
        responses: [
            new OA\Response(
                response: 200,
                description: "Resource details",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Not found")
        ]
    )]
    public function show($id)
    {
        try {
            $repository = $this->getRepository();
            
            $resource = $repository->findOrFail($id);

            $resourceClass = $this->getResource();
            
            return successResponse(
                new $resourceClass($resource),
                trans('channel::app.common.show_success')
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
}
