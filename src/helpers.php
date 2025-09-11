<?php

use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;
use JobMetric\EnvModifier\Facades\EnvModifier;

if (!function_exists('env_modifier_use')) {
    /**
     * Bind the EnvModifier to a specific .env file path.
     *
     * Role: Sets the working file path for subsequent helper calls.
     *
     * @param string $path Absolute or relative path to the .env file.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the file does not exist at the given path.
     */
    function env_modifier_use(string $path): void
    {
        EnvModifier::setPath($path);
    }
}

if (!function_exists('env_modifier_create')) {
    /**
     * Create a new .env file at the given path if it does not exist (or overwrite optionally).
     *
     * Role: Ensures directory exists and writes initial content. Optionally binds to the created path.
     *
     * @param string $path Target file path (e.g., base_path('.env.testing')).
     * @param array<string,mixed>|string|null $content Initial content. Array renders to KEY=VALUE lines; string is written as-is; null creates empty file.
     * @param bool $overwrite Overwrite if file already exists.
     * @param bool $bindToPath Bind EnvModifier instance to this path after creation.
     *
     * @return void
     */
    function env_modifier_create(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true): void
    {
        EnvModifier::createFile($path, $content, $overwrite, $bindToPath);
    }
}

if (!function_exists('env_modifier_delete_file')) {
    /**
     * Delete the currently bound .env file with optional main-env protection.
     *
     * Role: Prevents accidental deletion of the main application .env unless force=true is provided.
     *
     * @param bool $force Allow deletion even if this is the main application .env.
     * @param string|null $mainEnvAbsolutePath Absolute path to the protected main .env (e.g., base_path('.env')); if null, protection is skipped.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_delete_file(bool $force = false, ?string $mainEnvAbsolutePath = null): void
    {
        EnvModifier::deleteFile($force, $mainEnvAbsolutePath);
    }
}

if (!function_exists('env_modifier_all')) {
    /**
     * Read all key/value pairs from the currently bound .env file.
     *
     * Role: Parses non-comment lines into an associative array.
     *
     * @return array<string,string> Associative key/value list.
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_all(): array
    {
        return EnvModifier::all();
    }
}

if (!function_exists('env_modifier_get')) {
    /**
     * Read one or more keys from the .env file.
     *
     * Role: Supports variadic and nested arrays of keys; returns missing keys as empty strings.
     *
     * @param mixed ...$keys One or more key names or arrays of key names.
     *
     * @return array<string,string> Associative key/value list.
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_get(...$keys): array
    {
        return EnvModifier::get(...$keys);
    }
}

if (!function_exists('env_modifier_has')) {
    /**
     * Check if a key exists (non-commented) in the .env file.
     *
     * Role: Fast existence check for "KEY=" pattern.
     *
     * @param string $key The key to check.
     *
     * @return bool True if the key exists, false otherwise.
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_has(string $key): bool
    {
        return EnvModifier::has($key);
    }
}

if (!function_exists('env_modifier_put')) {
    /**
     * Upsert a single key/value pair into the .env file.
     *
     * Role: Convenience wrapper around set([...]).
     *
     * @param string $key Env key to write.
     * @param mixed $value Env value to write.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_put(string $key, mixed $value): void
    {
        EnvModifier::set([$key => $value]);
    }
}

if (!function_exists('env_modifier_set')) {
    /**
     * Upsert multiple key/value pairs into the .env file.
     *
     * Role: Writes all provided pairs; updates existing or appends new lines.
     *
     * @param array<string,mixed> $data Associative key/value list.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_set(array $data): void
    {
        EnvModifier::set($data);
    }
}

if (!function_exists('env_modifier_set_if_missing')) {
    /**
     * Set keys only if they are missing or currently empty.
     *
     * Role: Non-destructive defaults initializer (does not overwrite existing non-empty values).
     *
     * @param array<string,mixed> $data Defaults to apply.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_set_if_missing(array $data): void
    {
        EnvModifier::setIfMissing($data);
    }
}

if (!function_exists('env_modifier_rename')) {
    /**
     * Rename a key to a new name with optional overwrite.
     *
     * Role: Moves the value from $from to $to. If $to exists and overwrite=false, throws.
     *
     * @param string $from Source key.
     * @param string $to Target key.
     * @param bool $overwrite Allow overwriting if target already exists.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_rename(string $from, string $to, bool $overwrite = false): void
    {
        EnvModifier::rename($from, $to, $overwrite);
    }
}

if (!function_exists('env_modifier_forget')) {
    /**
     * Delete one or more keys from the .env file.
     *
     * Role: Removes matching lines while preserving other content and comments.
     *
     * @param mixed ...$keys One or more keys or arrays of keys to remove.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_forget(...$keys): void
    {
        EnvModifier::delete(...$keys);
    }
}

if (!function_exists('env_modifier_backup')) {
    /**
     * Create a timestamped backup file next to the currently bound .env.
     *
     * Role: Convenience wrapper returning backup path.
     *
     * @param string $suffix Suffix appended before timestamp (default ".bak").
     *
     * @return string Absolute path to the created backup.
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_backup(string $suffix = '.bak'): string
    {
        return EnvModifier::backup($suffix);
    }
}

if (!function_exists('env_modifier_restore')) {
    /**
     * Restore the currently bound .env from a backup file.
     *
     * Role: Reads backup content and writes it to the bound path. Optionally rebinds to the backup path.
     *
     * @param string $backupPath Absolute path to the backup file.
     * @param bool $bindToPath If true, bind EnvModifier to the backup file path after restore.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_restore(string $backupPath, bool $bindToPath = false): void
    {
        EnvModifier::restore($backupPath, $bindToPath);
    }
}

if (!function_exists('env_modifier_merge_from')) {
    /**
     * Merge key/values from another .env file into the currently bound .env.
     *
     * Role: Parses the source file and applies keys with optional allow/deny filters.
     *
     * @param string $path Source .env file path.
     * @param array<int,string> $only If not empty, only these keys will be merged.
     * @param array<int,string> $except Keys to exclude from merging.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the bound file is missing.
     */
    function env_modifier_merge_from(string $path, array $only = [], array $except = []): void
    {
        EnvModifier::mergeFromPath($path, $only, $except);
    }
}
