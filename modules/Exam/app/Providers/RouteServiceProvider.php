<?php

namespace Modules\Exam\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Exam';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        $apiV1Path = dirname(__DIR__, 2) . '/routes/api-v1.php';
        if (file_exists($apiV1Path)) {
            Route::middleware('api')->group($apiV1Path);
        }
    }
}
