<?php

namespace Xfind;

use Illuminate\Support\ServiceProvider;

class XfindServiceProvider extends ServiceProvider
{

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

    ];

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/xfind.php', 'xfind');

        $this->publishes([
            __DIR__ . '/config/xfind.php' => config_path('xfind.php'),
        ], 'config');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
