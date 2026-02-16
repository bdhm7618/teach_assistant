<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\PaymentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('payments', PaymentController::class)->names('payment');
});

