<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\App\Http\Controllers\V1\AttendanceController;

// RouteServiceProvider adds no prefix (only middleware('api'))
// Full URL: /api/v1/{channel_slug}/attendances

Route::prefix('api/v1/{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {
        Route::apiResource('attendances', AttendanceController::class);
        Route::post('attendances/bulk', [AttendanceController::class, 'bulkStore']);
        Route::get('attendances/statistics/student/{studentId}', [AttendanceController::class, 'studentStatistics']);
        Route::get('attendances/statistics/group/{groupId}',     [AttendanceController::class, 'groupStatistics']);
    });
