<?php

namespace JobMetric\EnvModifier;

use Illuminate\Support\ServiceProvider;

class EnvModifierServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('JEnvModifier', function ($app) {
            return new JEnvModifier($app);
        });
    }
}
