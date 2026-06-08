<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\App\Http\Controllers\V1\StudentController;
use Modules\Student\App\Http\Controllers\V1\StudentMetadataController;

// RouteServiceProvider already adds: api/v1
// This file adds:                   {channel_slug}
// Full URL:                         /api/v1/{channel_slug}/students/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {
        Route::apiResource('students', StudentController::class);
        Route::get('students/metadata', [StudentMetadataController::class, 'index']);
    });
