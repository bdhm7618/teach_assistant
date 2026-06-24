<?php

use Illuminate\Support\Facades\Route;
use Modules\Assignment\App\Http\Controllers\V1\AssignmentController;
use Modules\Assignment\App\Http\Controllers\V1\AssignmentSubmissionController;

// RouteServiceProvider already adds: api/v1
// Full URL: /api/v1/{channel_slug}/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // ----- Assignments (teacher CRUD) -----
        Route::middleware('check.permission:assignments.view')->group(function () {
            Route::get('assignments',                   [AssignmentController::class, 'index']);
            Route::get('assignments/{id}',              [AssignmentController::class, 'show']);
            Route::get('assignments/{id}/results',      [AssignmentController::class, 'results']);
        });

        Route::post('assignments', [AssignmentController::class, 'store'])
            ->middleware('check.permission:assignments.create');

        Route::put('assignments/{id}', [AssignmentController::class, 'update'])
            ->middleware('check.permission:assignments.update');
        Route::patch('assignments/{id}', [AssignmentController::class, 'update'])
            ->middleware('check.permission:assignments.update');

        Route::delete('assignments/{id}', [AssignmentController::class, 'destroy'])
            ->middleware('check.permission:assignments.delete');

        // Lifecycle
        Route::post('assignments/{id}/publish', [AssignmentController::class, 'publish'])
            ->middleware('check.permission:assignments.update');
        Route::post('assignments/{id}/close',   [AssignmentController::class, 'close'])
            ->middleware('check.permission:assignments.update');

        // ----- Submissions -----
        Route::middleware('check.permission:assignments.view')->group(function () {
            Route::get('assignments/{assignmentId}/submissions',                [AssignmentSubmissionController::class, 'listForAssignment']);
            Route::get('assignments/{assignmentId}/submissions/{submissionId}', [AssignmentSubmissionController::class, 'showSubmission']);
        });

        // Student submits — accessible to all authenticated users
        Route::post('assignments/{assignmentId}/submit', [AssignmentSubmissionController::class, 'submit']);

        // Teacher grades a submission
        Route::post('assignments/{assignmentId}/submissions/{submissionId}/grade',
            [AssignmentSubmissionController::class, 'grade'])
            ->middleware('check.permission:assignments.update');
    });
