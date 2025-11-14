<?php

namespace AndersonScherdovski\Base;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/base.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('base.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'base'
        );

        $this->app->bind('base', function () {
            return new Base();
        });
    }
}
