<?php

use Illuminate\Support\Facades\Route;
use Modules\ParentPortal\App\Http\Controllers\V1\ParentAuthController;
use Modules\ParentPortal\App\Http\Controllers\V1\ParentChildController;
use Modules\ParentPortal\App\Http\Controllers\V1\ParentManagementController;

// RouteServiceProvider already adds: api/v1
// This file adds:                   {channel_slug}/parent/...  (parent-facing)
//                              and  {channel_slug}/parents/... (staff-facing)
// Full URL:                         /api/v1/{channel_slug}/parent/...

// =========================================================================
// Parent — Public (identify tenant only, no parent auth)
// =========================================================================
Route::prefix('{channel_slug}/parent')
    ->middleware(['identify.tenant'])
    ->group(function () {
        Route::post('auth/register',        [ParentAuthController::class, 'register']);
        Route::post('auth/verify-email',    [ParentAuthController::class, 'verifyEmail']);
        Route::post('auth/resend-otp',      [ParentAuthController::class, 'resendOtp']);
        Route::post('auth/login',           [ParentAuthController::class, 'login']);
        Route::post('auth/forget-password', [ParentAuthController::class, 'forgetPassword']);
        Route::post('auth/reset-password',  [ParentAuthController::class, 'resetPassword']);
    });

// =========================================================================
// Parent — Protected (identify tenant + auth:parent)
// =========================================================================
Route::prefix('{channel_slug}/parent')
    ->middleware(['identify.tenant', 'auth:parent'])
    ->group(function () {

        // Auth / profile
        Route::get('auth/me',              [ParentAuthController::class, 'me']);
        Route::put('auth/me',              [ParentAuthController::class, 'updateProfile']);
        Route::post('auth/change-password',[ParentAuthController::class, 'changePassword']);
        Route::post('auth/logout',         [ParentAuthController::class, 'logout']);
        Route::post('auth/refresh',        [ParentAuthController::class, 'refreshToken']);

        // Children — list / claim / unclaim
        Route::get('children',                 [ParentChildController::class, 'index']);
        Route::post('children/claim',          [ParentChildController::class, 'claim']);
        Route::delete('children/{student_id}', [ParentChildController::class, 'unclaim']);

        // Per-child read-only views
        Route::get('children/{student_id}/enrollments',        [ParentChildController::class, 'enrollments']);
        Route::get('children/{student_id}/sessions',           [ParentChildController::class, 'sessions']);
        Route::get('children/{student_id}/attendance/summary', [ParentChildController::class, 'attendanceSummary']);
        Route::get('children/{student_id}/exams',              [ParentChildController::class, 'exams']);
        Route::get('children/{student_id}/assignments',        [ParentChildController::class, 'assignments']);
        Route::get('children/{student_id}/invoices',           [ParentChildController::class, 'invoices']);
        Route::get('children/{student_id}/invoices/summary',   [ParentChildController::class, 'invoiceSummary']);
    });

// =========================================================================
// Staff — Parent management (identify tenant + auth:user + permission)
// =========================================================================
Route::prefix('{channel_slug}/parents')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {
        Route::get('/',                          [ParentManagementController::class, 'index'])
            ->middleware('check.permission:parents.view');
        Route::get('{parent_id}/children',       [ParentManagementController::class, 'children'])
            ->middleware('check.permission:parents.view');
        Route::post('{parent_id}/children',      [ParentManagementController::class, 'attachChild'])
            ->middleware('check.permission:parents.manage');
        Route::delete('{parent_id}/children/{student_id}', [ParentManagementController::class, 'detachChild'])
            ->middleware('check.permission:parents.manage');
    });
