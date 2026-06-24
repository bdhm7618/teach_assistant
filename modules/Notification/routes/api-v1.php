<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\App\Http\Controllers\V1\NotificationLogController;

// RouteServiceProvider already adds: api/v1
// Full URL: /api/v1/{channel_slug}/...

Route::prefix('{channel_slug}')
    ->middleware(['identify.tenant', 'auth:user'])
    ->group(function () {

        // Notification log — requires reports.view permission
        Route::middleware('check.permission:reports.view')->group(function () {
            Route::get('notifications', [NotificationLogController::class, 'index']);
        });

        // Manually trigger overdue reminders — requires payments.* permission
        Route::post('notifications/send-overdue-reminders', [NotificationLogController::class, 'sendOverdueReminders'])
            ->middleware('check.permission:payments.view');
    });
