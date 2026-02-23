<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\App\Http\Controllers\V1\PaymentController;
use Modules\Payment\App\Http\Controllers\V1\InvoiceController;
use Modules\Payment\App\Http\Controllers\V1\DiscountController;
use Modules\Payment\App\Http\Controllers\V1\PaymentPeriodController;

Route::middleware(['auth:user'])->prefix('v1')->group(function () {
    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::post('payments/{id}/complete', [PaymentController::class, 'markAsCompleted']);
    Route::post('payments/{id}/refund', [PaymentController::class, 'refund']);
    Route::get('payments/student/{studentId}', [PaymentController::class, 'getByStudent']);
    Route::get('payments/group/{groupId}', [PaymentController::class, 'getByGroup']);
    Route::get('payments/statistics', [PaymentController::class, 'getStatistics']);
    Route::get('payments/student/{studentId}/summary', [PaymentController::class, 'getStudentSummary']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/with-installments', [InvoiceController::class, 'createWithInstallments']);
    Route::get('invoices/student/{studentId}', [InvoiceController::class, 'getByStudent']);
    Route::get('invoices/overdue', [InvoiceController::class, 'getOverdue']);
    Route::get('invoices/pending', [InvoiceController::class, 'getPending']);

    // Discounts
    Route::apiResource('discounts', DiscountController::class);
    Route::post('discounts/apply', [DiscountController::class, 'apply']);
    Route::get('discounts/active', [DiscountController::class, 'getActive']);

    // Payment Periods
    Route::apiResource('payment-periods', PaymentPeriodController::class);
    Route::post('payment-periods/monthly', [PaymentPeriodController::class, 'createMonthly']);
    Route::post('payment-periods/weekly', [PaymentPeriodController::class, 'createWeekly']);
    Route::get('payment-periods/open', [PaymentPeriodController::class, 'getOpenPeriods']);
    Route::get('payment-periods/current', [PaymentPeriodController::class, 'getCurrentPeriod']);
    Route::get('payment-periods/{id}/statistics', [PaymentPeriodController::class, 'getStatistics']);
});

