<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\App\Http\Controllers\V1\StudentController;
use Modules\Student\App\Http\Controllers\V1\StudentMetadataController;
use Modules\Student\App\Http\Controllers\V1\GuardianController;

// RouteServiceProvider already adds: api/v1
// This file adds:                   {channel_slug}
// Full URL:                         /api/v1/{channel_slug}/students/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // Metadata must be declared BEFORE the resource routes to prevent
        // "metadata" being matched as {student} param in show()
        Route::get('students/metadata', [StudentMetadataController::class, 'index'])
            ->middleware('check.permission:students.view');

        // Students — permission-gated
        Route::middleware('check.permission:students.view')->group(function () {
            Route::get('students',       [StudentController::class, 'index']);
            Route::get('students/{id}',  [StudentController::class, 'show']);
        });
        Route::post('students',          [StudentController::class, 'store'])->middleware('check.permission:students.create');
        Route::put('students/{id}',      [StudentController::class, 'update'])->middleware('check.permission:students.update');
        Route::patch('students/{id}',    [StudentController::class, 'update'])->middleware('check.permission:students.update');
        Route::delete('students/{id}',   [StudentController::class, 'destroy'])->middleware('check.permission:students.delete');

        // Guardians — nested under student
        Route::middleware('check.permission:students.view')->group(function () {
            Route::get('students/{student}/guardians',       [GuardianController::class, 'index']);
            Route::get('students/{student}/guardians/{id}',  [GuardianController::class, 'show']);
        });
        Route::post('students/{student}/guardians',          [GuardianController::class, 'store'])->middleware('check.permission:students.update');
        Route::put('students/{student}/guardians/{id}',      [GuardianController::class, 'update'])->middleware('check.permission:students.update');
        Route::patch('students/{student}/guardians/{id}',    [GuardianController::class, 'update'])->middleware('check.permission:students.update');
        Route::delete('students/{student}/guardians/{id}',   [GuardianController::class, 'destroy'])->middleware('check.permission:students.update');
    });
