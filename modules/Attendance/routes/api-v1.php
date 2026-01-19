<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\App\Http\Controllers\V1\AttendanceController;

Route::middleware(['auth:user'])->prefix('v1')->group(function () {
    Route::apiResource('attendances', AttendanceController::class);
    
    Route::post('attendances/bulk', [AttendanceController::class, 'bulkStore']);
    
    Route::get('attendances/statistics/student/{studentId}', [AttendanceController::class, 'studentStatistics']);
    Route::get('attendances/statistics/group/{groupId}', [AttendanceController::class, 'groupStatistics']);
});

