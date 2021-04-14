<?php

namespace Derekmd\Dusk;

use Illuminate\Support\ServiceProvider;

class DuskServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ChromeCommand::class,
                Console\FirefoxDriverCommand::class,
                Console\InstallCommand::class,
            ]);
        }
    }
}
