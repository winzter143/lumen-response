<?php
namespace F3\Providers;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/settings.php', 'settings');
    }

    /**
     * Called after all service providers have been registered.
     * @return void
     */
    public function boot()
    {
        // Register additional aliases.
        if (!class_exists('PDF')) {
            class_alias(\Barryvdh\Snappy\Facades\SnappyPdf::class, 'PDF');
        }

        if (!class_exists('SnappyImage')) {
            class_alias(\Barryvdh\Snappy\Facades\SnappyImage::class, 'SnappyImage');
        }

        // Register additional service providers.
        $this->app->register(\Barryvdh\Snappy\LumenServiceProvider::class);
    }
}
