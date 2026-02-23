<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\App\Http\Controllers\StudentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('students', StudentController::class)->names('student');
});

