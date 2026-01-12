<?php

use Illuminate\Support\Facades\Route;
use Modules\Channel\App\Http\Controllers\V1\ChannelController;

Route::prefix('api/v1/channel/')
    ->group(function () {
        Route::post('register', [ChannelController::class, 'register']);
        Route::post("user/verify-email", [ChannelController::class, "validateOtp"]);
        Route::post("user/login", [ChannelController::class, "login"]);
        Route::post('user/forget-password', [ChannelController::class, 'forgetPassword']);
        Route::post('user/reset-password', [ChannelController::class, 'resetPassword']);
    });
