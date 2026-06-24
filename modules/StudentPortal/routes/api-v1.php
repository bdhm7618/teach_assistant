<?php

use Illuminate\Support\Facades\Route;
use Modules\StudentPortal\App\Http\Controllers\V1\StudentAuthController;
use Modules\StudentPortal\App\Http\Controllers\V1\StudentDashboardController;
use Modules\StudentPortal\App\Http\Controllers\V1\StudentExamController;
use Modules\StudentPortal\App\Http\Controllers\V1\StudentAssignmentController;
use Modules\StudentPortal\App\Http\Controllers\V1\StudentPaymentController;

// RouteServiceProvider already adds: api/v1
// This file adds:                   {channel_slug}/student/...
// Full URL:                         /api/v1/{channel_slug}/student/...

// =========================================================================
// Public — identify tenant only (no student auth)
// =========================================================================
Route::prefix('{channel_slug}/student')
    ->middleware(['identify.tenant'])
    ->group(function () {
        Route::post('auth/login',          [StudentAuthController::class, 'login']);
        Route::post('auth/forget-password',[StudentAuthController::class, 'forgetPassword']);
        Route::post('auth/reset-password', [StudentAuthController::class, 'resetPassword']);
    });

// =========================================================================
// Protected — identify tenant + auth:student
// =========================================================================
Route::prefix('{channel_slug}/student')
    ->middleware(['identify.tenant', 'auth:student'])
    ->group(function () {

        // Auth
        Route::get('auth/me',             [StudentAuthController::class, 'me']);
        Route::put('auth/me',             [StudentAuthController::class, 'updateProfile']);
        Route::post('auth/change-password',[StudentAuthController::class, 'changePassword']);
        Route::post('auth/logout',         [StudentAuthController::class, 'logout']);
        Route::post('auth/refresh',        [StudentAuthController::class, 'refreshToken']);

        // Dashboard — enrollments, sessions, attendance
        Route::get('enrollments',               [StudentDashboardController::class, 'enrollments']);
        Route::get('sessions',                  [StudentDashboardController::class, 'sessions']);
        Route::get('sessions/upcoming',         [StudentDashboardController::class, 'upcomingSessions']);
        Route::get('attendance/summary',        [StudentDashboardController::class, 'attendanceSummary']);

        // Exams
        Route::get('exams',                                         [StudentExamController::class, 'index']);
        Route::get('exams/{exam_id}',                               [StudentExamController::class, 'show']);
        Route::post('exams/{exam_id}/start',                        [StudentExamController::class, 'start']);
        Route::post('exams/{exam_id}/submissions/{submission_id}/answer', [StudentExamController::class, 'saveAnswer']);
        Route::post('exams/{exam_id}/submissions/{submission_id}/submit',  [StudentExamController::class, 'submit']);
        Route::get('exams/{exam_id}/submissions',                   [StudentExamController::class, 'myAttempts']);
        Route::get('exams/{exam_id}/submissions/{submission_id}',   [StudentExamController::class, 'showAttempt']);

        // Assignments
        Route::get('assignments',                               [StudentAssignmentController::class, 'index']);
        Route::get('assignments/{assignment_id}',               [StudentAssignmentController::class, 'show']);
        Route::post('assignments/{assignment_id}/submit',        [StudentAssignmentController::class, 'submit']);
        Route::get('assignments/{assignment_id}/submission',     [StudentAssignmentController::class, 'mySubmission']);

        // Payments / Invoices
        Route::get('invoices',                  [StudentPaymentController::class, 'invoices']);
        Route::get('invoices/summary',          [StudentPaymentController::class, 'summary']);
        Route::get('invoices/{invoice_id}',     [StudentPaymentController::class, 'showInvoice']);
    });
