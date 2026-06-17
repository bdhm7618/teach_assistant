<?php

use Illuminate\Support\Facades\Route;
use Modules\Channel\App\Http\Controllers\V1\ChannelController;
use Modules\Channel\App\Http\Controllers\V1\UserController;
use Modules\Channel\App\Http\Controllers\V1\RoleController;

// ─── Public routes (no authentication, no slug) ──────────────────────────────
Route::prefix('api/v1/auth')->group(function () {
    Route::post('register',         [ChannelController::class, 'register']);
    Route::post('verify-email',     [ChannelController::class, 'validateOtp']);
    Route::post('resend-otp',       [ChannelController::class, 'resendOtp']);
    Route::post('login',            [ChannelController::class, 'login']);
    Route::post('forget-password',  [ChannelController::class, 'forgetPassword']);
    Route::post('reset-password',   [ChannelController::class, 'resetPassword']);
});

// ─── Protected routes (slug-scoped + JWT) ────────────────────────────────────
Route::prefix('api/v1/{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {
        Route::get('auth/me',       [ChannelController::class, 'me']);
        Route::post('auth/logout',  [ChannelController::class, 'logout']);
        Route::post('auth/refresh', [ChannelController::class, 'refreshToken']);

        Route::get('channel',   [ChannelController::class, 'show']);
        Route::patch('channel', [ChannelController::class, 'show']); // placeholder until update is wired

        // Users — permission-gated
        Route::middleware('check.permission:users.view')->group(function () {
            Route::get('users',       [UserController::class, 'index']);
            Route::get('users/{id}',  [UserController::class, 'show']);
        });
        Route::post('users',           [UserController::class, 'store'])->middleware('check.permission:users.create');
        Route::put('users/{id}',       [UserController::class, 'update'])->middleware('check.permission:users.update');
        Route::patch('users/{id}',     [UserController::class, 'update'])->middleware('check.permission:users.update');
        Route::delete('users/{id}',    [UserController::class, 'destroy'])->middleware('check.permission:users.delete');

        // Roles — permission-gated
        Route::middleware('check.permission:roles.view')->group(function () {
            Route::get('roles',       [RoleController::class, 'index']);
            Route::get('roles/{id}',  [RoleController::class, 'show']);
        });
        Route::post('roles',           [RoleController::class, 'store'])->middleware('check.permission:roles.create');
        Route::put('roles/{id}',       [RoleController::class, 'update'])->middleware('check.permission:roles.update');
        Route::patch('roles/{id}',     [RoleController::class, 'update'])->middleware('check.permission:roles.update');
        Route::delete('roles/{id}',    [RoleController::class, 'destroy'])->middleware('check.permission:roles.delete');
    });
