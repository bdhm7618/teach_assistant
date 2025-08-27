<?php


use App\Http\Controllers\Apis\Teacher\ClassController;
use App\Http\Controllers\Apis\Teacher\GroupController;
use Illuminate\Support\Facades\Route;


Route::prefix("teacher")->group(function () {
    Route::apiResource('classes', ClassController::class);
    Route::apiResource('groups', GroupController::class);
});
