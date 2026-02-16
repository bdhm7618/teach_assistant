<?php

namespace Modules\Academic\App\Providers;


use Illuminate\Support\ServiceProvider;

class AcademicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/academic.php',
            'academic'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerTranslations();
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path('Academic', 'database/migrations'));
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(module_path('Academic', 'routes/api-v1.php'));
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path('Academic', 'resources/views'), 'academic');
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path('Academic', 'resources/lang'), 'academic');
    }
}
