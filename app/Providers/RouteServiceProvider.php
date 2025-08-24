<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{


    /**
     * Define route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        parent::boot();

        // Example: global route pattern
        Route::pattern('id', '[0-9]+');
    }

    /**
     * Define the routes for the application.
     */
    public function map()
    {
        // Web routes
        $this->mapWebRoutes();

        // API routes
        $this->mapApiRoutes();
    }

    /**
     * Web routes
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * API routes
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }
}
