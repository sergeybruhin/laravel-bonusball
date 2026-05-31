<?php

namespace BonusBall\Laravel;

use BonusBall\Laravel\Console\PingBonusBallCommand;
use Illuminate\Support\ServiceProvider;

class BonusBallServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bonusball.php', 'bonusball');

        $this->app->singleton(BonusBallManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/bonusball.php' => config_path('bonusball.php'),
        ], 'bonusball-config');

        if ($this->app->runningInConsole()) {
            $this->commands([PingBonusBallCommand::class]);
        }
    }
}
