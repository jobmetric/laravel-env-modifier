<?php

namespace JobMetric\EnvModifier\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the EnvModifier service.
 *
 * @mixin \JobMetric\EnvModifier\EnvModifier
 *
 * @method static static setPath(string $path)
 * @method static static createFile(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true)
 * @method static void deleteFile(bool $force = false, ?string $mainEnvAbsolutePath = null)
 * @method static string backup(string $suffix = '.bak')
 * @method static static restore(string $backupPath, bool $bindToPath = false)
 * @method static static mergeFromPath(string $path, array $only = [], array $except = [])
 * @method static array<string,string> all()
 * @method static array<string,string> get(...$keys)
 * @method static static set(array $data)
 * @method static static setIfMissing(array $data)
 * @method static static rename(string $from, string $to, bool $overwrite = false)
 * @method static static delete(...$keys)
 * @method static bool has(string $key)
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
