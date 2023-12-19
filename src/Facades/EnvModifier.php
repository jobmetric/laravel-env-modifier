<?php

namespace JobMetric\EnvModifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static static setPath(string $path)
 * @method static array get(...$keys)
 * @method static static set(array $data)
 * @method static static delete(...$keys)
 * @method static bool has($key)
 *
 * @see \JobMetric\EnvModifier\EnvModifier
 */
class EnvModifier extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'EnvModifier';
    }
}
