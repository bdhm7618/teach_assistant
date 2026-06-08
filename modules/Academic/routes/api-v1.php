<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\App\Http\Controllers\V1\AcademicYearController;
use Modules\Academic\App\Http\Controllers\V1\ClassGradeController;
use Modules\Academic\App\Http\Controllers\V1\CourseController;
use Modules\Academic\App\Http\Controllers\V1\GroupController;
use Modules\Academic\App\Http\Controllers\V1\GroupMetadataController;
use Modules\Academic\App\Http\Controllers\V1\GroupUserController;
use Modules\Academic\App\Http\Controllers\V1\SessionController;
use Modules\Academic\App\Http\Controllers\V1\StudentEnrollmentController;
use Modules\Academic\App\Http\Controllers\V1\SubjectController;

// RouteServiceProvider already adds: api/v1
// This file adds:                   {channel_slug}
// Full URL:                         /api/v1/{channel_slug}/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // Academic years
        Route::apiResource('academic-years', AcademicYearController::class)->except(['destroy']);

        // Class grades
        Route::apiResource('class-grades', ClassGradeController::class);

        // Subjects
        Route::apiResource('subjects', SubjectController::class);

        // Groups (will gain course nesting in Fix B — CourseController added then)
        Route::apiResource('groups', GroupController::class);
        Route::get('groups-metadata', [GroupMetadataController::class, 'index']);

        // Group users (teachers / assistants)
        Route::get('groups/{groupId}/users',               [GroupUserController::class, 'index']);
        Route::post('groups/{groupId}/users',              [GroupUserController::class, 'store']);
        Route::put('groups/{groupId}/users/{userId}',      [GroupUserController::class, 'update']);
        Route::delete('groups/{groupId}/users/{userId}',   [GroupUserController::class, 'destroy']);

        // Student enrollments
        Route::apiResource('student-enrollments', StudentEnrollmentController::class);
        Route::get('students/{studentId}/enrollments', [StudentEnrollmentController::class, 'getByStudent']);
        Route::get('groups/{groupId}/enrollments',     [StudentEnrollmentController::class, 'getByGroup']);

        // Courses
        Route::apiResource('courses', CourseController::class);

        // Sessions — individual dated class records
        Route::get('groups/{group}/sessions',              [SessionController::class, 'index']);
        Route::post('groups/{group}/sessions',             [SessionController::class, 'store']);
        Route::get('groups/{group}/sessions/{session}',    [SessionController::class, 'show']);
        Route::patch('groups/{group}/sessions/{session}',  [SessionController::class, 'update']);
        Route::delete('groups/{group}/sessions/{session}', [SessionController::class, 'destroy']);
    });
