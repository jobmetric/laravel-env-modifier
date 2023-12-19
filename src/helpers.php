<?php

use JobMetric\EnvModifier\EnvModifier;
use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;

if(!function_exists('env_modifier_set')) {
    /**
     * set env data
     *
     * @param array $data
     *
     * @return void
     * @throws EnvFileNotFoundException
     */
    function env_modifier_set(array $data): void
    {
        EnvModifier::set($data);
    }
}
