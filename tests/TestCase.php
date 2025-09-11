<?php

namespace JobMetric\EnvModifier\Tests;

use JobMetric\EnvModifier\EnvModifierServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            EnvModifierServiceProvider::class,
        ];
    }
}
