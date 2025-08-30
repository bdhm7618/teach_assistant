<?php

use App\Http\Controllers\Apis\Teacher\AuthController;
use App\Http\Controllers\Apis\Teacher\ClassController;
use App\Http\Controllers\Apis\Teacher\GroupController;
use Illuminate\Support\Facades\Route;

Route::post("teacher/login", [AuthController::class, "login"]);

Route::prefix("teacher")->middleware("auth:teacher")->group(function () {
    Route::apiResource('classes', ClassController::class);
    Route::apiResource('groups', GroupController::class);
});
    