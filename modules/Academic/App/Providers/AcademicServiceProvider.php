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
            __DIR__ . '/../../Config/academic.php',
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
        $this->loadMigrationsFrom(module_path('Academic', 'Database/Migrations'));
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(module_path('Academic', 'Routes/api-v1.php'));
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path('Academic', 'Resources/Views'), 'academic');
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path('Academic', 'Resources/Lang'), 'academic');
    }
}
