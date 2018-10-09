<?php

namespace Xfind;

use App\Http\Kernel;
use Illuminate\Routing\Router;
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
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'xfind');

        $this->publishes([
            __DIR__ . '/config/xfind.php' => config_path('xfind.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/resources/lang' => resource_path('lang'),
        ], 'langs');

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
