<?php

namespace Modules\Channel\App\Providers;



use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ChannelServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Channel';

    protected string $nameLower = 'channel';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
        $this->registerRoutes();
        $this->registerViews();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void {}

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void {}

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {

        $moduleLangPath = module_path($this->name, 'Resources/lang'); // Module lang folder
        $overrideLangPath = resource_path('lang/modules/' . $this->nameLower); // Optional override path

        // Load override translations first (if exist)
        if (is_dir($overrideLangPath)) {
            $this->loadTranslationsFrom($overrideLangPath, $this->nameLower);
        }

        // Load default module translations
        if (is_dir($moduleLangPath)) {
            $this->loadTranslationsFrom($moduleLangPath, $this->nameLower);
        }
    }
    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/channel');

        $sourcePath = module_path('Channel', 'Resources/views');

        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, 'channel');
        } else {
            $this->loadViewsFrom($sourcePath, 'channel');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower . '.' . $config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }



    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }


    protected function registerRoutes(): void
    {
        $routesPath = module_path($this->name, 'Routes');

        if (is_dir($routesPath)) {

            if (file_exists($routesPath . '/api-v1.php')) {
                $this->loadRoutesFrom($routesPath . '/api-v1.php');
            }
        }
    }
}
