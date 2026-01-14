<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\App\Http\Controllers\V1\ClassGradeController;
use Modules\Academic\App\Http\Controllers\V1\AcademicYearController;
use Modules\Academic\App\Http\Controllers\V1\GroupController;
use Modules\Academic\App\Http\Controllers\V1\SubjectController;

Route::prefix('academic/')->middleware("auth:user")
    ->group(function () {
        Route::apiResource('academic-years', AcademicYearController::class)->except(['destroy']);
        Route::apiResource("class-grades", ClassGradeController::class);
        Route::apiResource("groups", GroupController::class);
        Route::get("groups-metadata", [\Modules\Academic\App\Http\Controllers\V1\GroupMetadataController::class, 'index']);
        Route::apiResource("subjects", SubjectController::class);
    });
