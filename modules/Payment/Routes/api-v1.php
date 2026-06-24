<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\V1\PaymentController;
use Modules\Payment\App\Http\Controllers\V1\InvoiceController;
use Modules\Payment\App\Http\Controllers\V1\DiscountController;
use Modules\Payment\App\Http\Controllers\V1\PaymentPeriodController;

// RouteServiceProvider adds no prefix (only middleware('api'))
// Full URL: /api/v1/{channel_slug}/payments | invoices | discounts | payment-periods

Route::prefix('api/v1/{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // Payments — permission-gated
        Route::middleware('check.permission:payments.view')->group(function () {
            Route::get('payments',                             [PaymentController::class, 'index']);
            Route::get('payments/{id}',                        [PaymentController::class, 'show']);
            Route::get('payments/student/{studentId}',         [PaymentController::class, 'getByStudent']);
            Route::get('payments/group/{groupId}',             [PaymentController::class, 'getByGroup']);
            Route::get('payments/statistics',                  [PaymentController::class, 'getStatistics']);
            Route::get('payments/student/{studentId}/summary', [PaymentController::class, 'getStudentSummary']);
        });
        Route::post('payments',              [PaymentController::class, 'store'])->middleware('check.permission:payments.create');
        Route::put('payments/{id}',          [PaymentController::class, 'update'])->middleware('check.permission:payments.update');
        Route::patch('payments/{id}',        [PaymentController::class, 'update'])->middleware('check.permission:payments.update');
        Route::delete('payments/{id}',       [PaymentController::class, 'destroy'])->middleware('check.permission:payments.delete');
        Route::post('payments/{id}/complete',[PaymentController::class, 'markAsCompleted'])->middleware('check.permission:payments.update');
        Route::post('payments/{id}/refund',  [PaymentController::class, 'refund'])->middleware('check.permission:payments.update');

        // Invoices — permission-gated
        Route::middleware('check.permission:payments.view')->group(function () {
            Route::get('invoices',                         [InvoiceController::class, 'index']);
            Route::get('invoices/{id}',                    [InvoiceController::class, 'show']);
            Route::get('invoices/student/{studentId}',     [InvoiceController::class, 'getByStudent']);
            Route::get('invoices/overdue',                 [InvoiceController::class, 'getOverdue']);
            Route::get('invoices/pending',                 [InvoiceController::class, 'getPending']);
        });
        Route::post('invoices',                  [InvoiceController::class, 'store'])->middleware('check.permission:payments.create');
        Route::post('invoices/with-installments',[InvoiceController::class, 'createWithInstallments'])->middleware('check.permission:payments.create');
        Route::put('invoices/{id}',              [InvoiceController::class, 'update'])->middleware('check.permission:payments.update');
        Route::patch('invoices/{id}',            [InvoiceController::class, 'update'])->middleware('check.permission:payments.update');
        Route::delete('invoices/{id}',           [InvoiceController::class, 'destroy'])->middleware('check.permission:payments.delete');

        // Discounts
        Route::apiResource('discounts', DiscountController::class);
        Route::post('discounts/apply',  [DiscountController::class, 'apply']);
        Route::get('discounts/active',  [DiscountController::class, 'getActive']);

        // Payment Periods
        Route::apiResource('payment-periods', PaymentPeriodController::class);
        Route::post('payment-periods/monthly',         [PaymentPeriodController::class, 'createMonthly']);
        Route::post('payment-periods/weekly',          [PaymentPeriodController::class, 'createWeekly']);
        Route::get('payment-periods/open',             [PaymentPeriodController::class, 'getOpenPeriods']);
        Route::get('payment-periods/current',          [PaymentPeriodController::class, 'getCurrentPeriod']);
        Route::get('payment-periods/{id}/statistics',  [PaymentPeriodController::class, 'getStatistics']);
    });
