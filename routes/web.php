<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Routes
Route::get('/api-docs', function () {
    $jsonPath = storage_path('api-docs/api-documentation.json');
    
    if (!file_exists($jsonPath)) {
        Artisan::call('api:docs:generate');
    }
    
    return response()->file($jsonPath, [
        'Content-Type' => 'application/json',
    ]);
})->name('api.docs.json');

Route::get('/api-docs/view', function () {
    return view('api-docs');
})->name('api.docs.view');
