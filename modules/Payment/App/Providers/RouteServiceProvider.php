<?php

namespace Modules\Payment\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Payment';

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
        $apiV1Path = module_path($this->name, '/Routes/api-v1.php');
        if (file_exists($apiV1Path)) {
            Route::middleware('api')->group($apiV1Path);
        }
    }
}
