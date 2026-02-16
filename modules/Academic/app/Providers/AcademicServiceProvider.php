<?php

namespace Modules\Academic\App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AcademicServiceProvider extends ServiceProvider
{
    protected string $name = 'Academic';
    protected string $nameLower = 'academic';

    /**
     * Register bindings.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerConfig();
    }

    /* =====================================
     |  Migrations (CRITICAL FOR PROD)
     ===================================== */
    protected function registerMigrations(): void
    {
        $path = module_path($this->name, 'database/migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /* =====================================
     |  Translations
     ===================================== */
    protected function registerTranslations(): void
    {
        $overrideLangPath = resource_path("lang/modules/{$this->nameLower}");
        $moduleLangPath   = module_path($this->name, 'resources/lang');

        if (is_dir($overrideLangPath)) {
            $this->loadTranslationsFrom($overrideLangPath, $this->nameLower);
        }

        if (is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
        }
    }

    /* =====================================
     |  Views
     ===================================== */
    protected function registerViews(): void
    {
        $overrideViewPath = resource_path("views/modules/{$this->nameLower}");
        $moduleViewPath   = module_path($this->name, 'resources/views');

        if (is_dir($overrideViewPath)) {
            $this->loadViewsFrom($overrideViewPath, $this->nameLower);
        }

        if (is_dir($moduleViewPath)) {
            $this->loadViewsFrom($moduleViewPath, $this->nameLower);
        }

        Blade::componentNamespace(
            "Modules\\{$this->name}\\View\\Components",
            $this->nameLower
        );
    }

    /* =====================================
     |  Config
     ===================================== */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, 'config');

        if (! is_dir($configPath)) {
            return;
        }

        foreach (glob($configPath . '/*.php') as $file) {
            $key = $this->nameLower . '.' . basename($file, '.php');

            $this->mergeConfigFrom($file, $key);

            // Publish configs only in console
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $file => config_path(basename($file)),
                ], "{$this->nameLower}-config");
            }
        }
    }
}
