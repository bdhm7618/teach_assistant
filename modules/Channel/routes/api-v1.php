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
        Route::get('auth/me',      [ChannelController::class, 'me']);
        Route::post('auth/logout', [ChannelController::class, 'logout']);
        Route::post('auth/refresh',[ChannelController::class, 'refreshToken']);

        Route::get('channel',   [ChannelController::class, 'show']);
        Route::patch('channel', [ChannelController::class, 'show']); // placeholder until update is wired

        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });
