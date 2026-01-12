<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;


if (!function_exists('successResponse')) {
    function successResponse($data = null, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
}


if (!function_exists('errorResponse')) {
    function errorResponse($message = 'Error',  null|array|string|Exception $exception = null, $code = 500): JsonResponse
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
            "errors" => $message
        ];

        if ($exception instanceof Exception && App::environment('local')) {
            $response['errors'] = $exception->getMessage();
        }

        return response()->json($response, $code);
    }
}
