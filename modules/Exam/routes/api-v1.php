<?php

use Illuminate\Support\Facades\Route;
use Modules\Exam\App\Http\Controllers\V1\ExamController;
use Modules\Exam\App\Http\Controllers\V1\ExamQuestionController;
use Modules\Exam\App\Http\Controllers\V1\ExamSubmissionController;

// RouteServiceProvider already adds: api/v1
// Full URL: /api/v1/{channel_slug}/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // ----- Exams (teacher CRUD) -----
        Route::middleware('check.permission:exams.view')->group(function () {
            Route::get('exams',        [ExamController::class, 'index']);
            Route::get('exams/{id}',   [ExamController::class, 'show']);
            Route::get('exams/{id}/results', [ExamController::class, 'results']);
        });

        Route::post('exams',           [ExamController::class, 'store'])
            ->middleware('check.permission:exams.create');

        Route::put('exams/{id}',       [ExamController::class, 'update'])
            ->middleware('check.permission:exams.update');
        Route::patch('exams/{id}',     [ExamController::class, 'update'])
            ->middleware('check.permission:exams.update');

        Route::delete('exams/{id}',    [ExamController::class, 'destroy'])
            ->middleware('check.permission:exams.delete');

        // Lifecycle
        Route::post('exams/{id}/publish', [ExamController::class, 'publish'])
            ->middleware('check.permission:exams.update');
        Route::post('exams/{id}/close',   [ExamController::class, 'close'])
            ->middleware('check.permission:exams.update');

        // ----- Questions (nested under exams) -----
        Route::middleware('check.permission:exams.view')->group(function () {
            Route::get('exams/{examId}/questions',        [ExamQuestionController::class, 'index']);
            Route::get('exams/{examId}/questions/{id}',   [ExamQuestionController::class, 'show']);
        });

        Route::post('exams/{examId}/questions',           [ExamQuestionController::class, 'store'])
            ->middleware('check.permission:exams.update');
        Route::put('exams/{examId}/questions/{id}',       [ExamQuestionController::class, 'update'])
            ->middleware('check.permission:exams.update');
        Route::patch('exams/{examId}/questions/{id}',     [ExamQuestionController::class, 'update'])
            ->middleware('check.permission:exams.update');
        Route::delete('exams/{examId}/questions/{id}',    [ExamQuestionController::class, 'destroy'])
            ->middleware('check.permission:exams.update');

        // ----- Submissions -----
        Route::middleware('check.permission:exams.view')->group(function () {
            Route::get('exams/{examId}/submissions',                      [ExamSubmissionController::class, 'index']);
            Route::get('exams/{examId}/submissions/{submissionId}',       [ExamSubmissionController::class, 'show']);
        });

        // Student starts exam — accessible to authenticated users (no granular permission)
        Route::post('exams/{examId}/start', [ExamSubmissionController::class, 'start']);

        // Student submits answers
        Route::post('exams/{examId}/submissions/{submissionId}/submit',
            [ExamSubmissionController::class, 'submit']);

        // Teacher grades essay/short-answer answers
        Route::post('exams/{examId}/submissions/{submissionId}/grade',
            [ExamSubmissionController::class, 'grade'])
            ->middleware('check.permission:exams.update');
    });
