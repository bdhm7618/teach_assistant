<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Apis\Teacher\AuthController;
use App\Http\Controllers\Apis\Teacher\ClassController;
use App\Http\Controllers\Apis\Teacher\GroupController;
use App\Http\Controllers\Apis\Teacher\PaymentController;
use App\Http\Controllers\Apis\Teacher\StudentController;
use App\Http\Controllers\Apis\Teacher\AttendanceController;
use App\Http\Controllers\Apis\Teacher\PaymentMonthController;

Route::post("teacher/login", [AuthController::class, "login"]);

Route::prefix("teacher")->middleware("auth:teacher")->group(function () {
    Route::get("class-metadata", [ClassController::class, "getMetaData"]);

    Route::get("group-metadata", [StudentController::class, "getMetaData"]);

    Route::apiResource('classes', ClassController::class);

    Route::apiResource('groups', GroupController::class);

    Route::apiResource("students", StudentController::class);

    Route::get("attendance/get-groups", [AttendanceController::class, "getGroups"]);

    Route::post("attendance", [AttendanceController::class, "store"]);

    Route::put("attendance/change/{id}", [AttendanceController::class, "change"]);


    Route::apiResource('payments', PaymentController::class)->except(['destroy']);

    Route::apiResource('payment-months', PaymentMonthController::class)->except(['destroy']);
});
