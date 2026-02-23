<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\App\Http\Controllers\V1\ClassGradeController;
use Modules\Academic\App\Http\Controllers\V1\LevelController;
use Modules\Academic\App\Http\Controllers\V1\GroupController;
use Modules\Academic\App\Http\Controllers\V1\SubjectController;

Route::prefix('academic/')->middleware("auth:user")
    ->group(function () {
        Route::apiResource("levels", LevelController::class);
        Route::apiResource("class-grades", ClassGradeController::class);
        Route::apiResource("groups", GroupController::class);
        Route::get("groups-metadata", [\Modules\Academic\App\Http\Controllers\V1\GroupMetadataController::class, 'index']);
        Route::apiResource("subjects", SubjectController::class);
        
        // Student Enrollments
        Route::apiResource("student-enrollments", \Modules\Academic\App\Http\Controllers\V1\StudentEnrollmentController::class);
        Route::get("students/{studentId}/enrollments", [\Modules\Academic\App\Http\Controllers\V1\StudentEnrollmentController::class, 'getByStudent']);
        Route::get("groups/{groupId}/enrollments", [\Modules\Academic\App\Http\Controllers\V1\StudentEnrollmentController::class, 'getByGroup']);
        
        // Group Users (Teachers, Assistants)
        Route::get("groups/{groupId}/users", [\Modules\Academic\App\Http\Controllers\V1\GroupUserController::class, 'index']);
        Route::post("groups/{groupId}/users", [\Modules\Academic\App\Http\Controllers\V1\GroupUserController::class, 'store']);
        Route::put("groups/{groupId}/users/{userId}", [\Modules\Academic\App\Http\Controllers\V1\GroupUserController::class, 'update']);
        Route::delete("groups/{groupId}/users/{userId}", [\Modules\Academic\App\Http\Controllers\V1\GroupUserController::class, 'destroy']);
    });
