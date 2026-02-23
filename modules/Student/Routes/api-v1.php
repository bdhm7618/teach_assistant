<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\App\Http\Controllers\V1\StudentController;

Route::prefix('students/')->middleware("auth:user")
    ->group(function () {
        Route::apiResource('', StudentController::class)->parameters(['' => 'student']);
        Route::get("metadata", [\Modules\Student\App\Http\Controllers\V1\StudentMetadataController::class, 'index']);
    });

