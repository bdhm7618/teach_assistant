<?php

use App\Http\Controllers\Apis\Teacher\AuthController;
use App\Http\Controllers\Apis\Teacher\ClassController;
use App\Http\Controllers\Apis\Teacher\GroupController;
use App\Http\Controllers\Apis\Teacher\StudentController;
use Illuminate\Support\Facades\Route;

Route::post("teacher/login", [AuthController::class, "login"]);

Route::prefix("teacher")->middleware("auth:teacher")->group(function () {
    Route::get("class-metadata", [ClassController::class, "getMetaData"]);

    Route::get("group-metadata", [StudentController::class, "getMetaData"]);

    Route::apiResource('classes', ClassController::class);

    Route::apiResource('groups', GroupController::class);

    Route::apiResource("students", StudentController::class);
});
