<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\App\Http\Controllers\V1\AttendanceController;

// RouteServiceProvider adds no prefix (only middleware('api'))
// Full URL: /api/v1/{channel_slug}/attendances | sessions/{session}/attendance

Route::prefix('api/v1/{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // Attendance — read endpoints
        Route::middleware('check.permission:attendance.view')->group(function () {
            Route::get('attendances',       [AttendanceController::class, 'index']);
            Route::get('attendances/{id}',  [AttendanceController::class, 'show']);
            Route::get('attendances/statistics/student/{studentId}', [AttendanceController::class, 'studentStatistics']);
            Route::get('attendances/statistics/group/{groupId}',     [AttendanceController::class, 'groupStatistics']);
            // Live session attendance view — poll this for realtime updates
            Route::get('sessions/{session}/attendance', [AttendanceController::class, 'sessionLive']);
        });

        // Attendance — write endpoints
        Route::middleware('check.permission:attendance.manage')->group(function () {
            Route::post('attendances',       [AttendanceController::class, 'store']);
            Route::put('attendances/{id}',   [AttendanceController::class, 'update']);
            Route::patch('attendances/{id}', [AttendanceController::class, 'update']);
            Route::delete('attendances/{id}',[AttendanceController::class, 'destroy']);
            Route::post('attendances/bulk',  [AttendanceController::class, 'bulkStore']);
        });

        // QR scan — students check in via QR (auth:user for now; P12 will add auth:student)
        Route::post('attendances/qr-scan', [AttendanceController::class, 'qrScan']);
    });
