<?php

namespace Derekmd\Dusk;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DuskServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function register()
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
