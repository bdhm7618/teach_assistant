<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\App\Http\Controllers\V1\ClassGradeController;
use Modules\Academic\App\Http\Controllers\V1\AcademicYearController;

Route::prefix('academic/')->middleware("auth:user")
    ->group(function () {
        Route::apiResource('academic-years', AcademicYearController::class)->except(['destroy']);
        Route::apiResource("class-grades", ClassGradeController::class);
   
    });
