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

    }

}
