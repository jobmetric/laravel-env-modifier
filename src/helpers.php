<?php

use JobMetric\EnvModifier\JEnvModifier;

if(!function_exists('env_modifier_set')) {
    /**
     * set env data
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    function env_modifier_set(string $key, mixed $value): void
    {
        JEnvModifier::set($key, $value);
    }
}
