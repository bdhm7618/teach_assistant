<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;


if (!function_exists('successResponse')) {
    function successResponse($data , $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }
}


if (!function_exists('errorResponse')) {
    function errorResponse($message = 'Error',  null|array|string|Exception $exception = null, $errors = [], $code = 500): JsonResponse
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ];

        if ($exception instanceof Exception && App::environment('local')) {
            $response['error_details'] = $exception->getMessage();
        }

        return response()->json($response, $code);
    }
}
