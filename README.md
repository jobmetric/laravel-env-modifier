[contributors-shield]: https://img.shields.io/github/contributors/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[contributors-url]: https://github.com/jobmetric/laravel-env-modifier/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/jobmetric/laravel-env-modifier.svg?style=for-the-badge&label=Fork
[forks-url]: https://github.com/jobmetric/laravel-env-modifier/network/members
[stars-shield]: https://img.shields.io/github/stars/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[stars-url]: https://github.com/jobmetric/laravel-env-modifier/stargazers
[license-shield]: https://img.shields.io/github/license/jobmetric/laravel-env-modifier.svg?style=for-the-badge
[license-url]: https://github.com/jobmetric/laravel-env-modifier/blob/master/LICENCE.md
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-blue.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/majidmohammadian

[![Contributors][contributors-shield]][contributors-url]
[![Forks][forks-shield]][forks-url]
[![Stargazers][stars-shield]][stars-url]
[![MIT License][license-shield]][license-url]
[![LinkedIn][linkedin-shield]][linkedin-url]

# Laravel Env Modifier

A tiny, framework-friendly utility to **create, read, merge, update, back up, restore, and delete** `.env` style files across your Laravel projects safely and predictably.

- Ignores comments and blank lines.
- Escapes regex-sensitive keys.
- Auto-quotes values when needed.
- Persists atomically with `LOCK_EX`.
- Includes file-level helpers (create/backup/restore/delete) and key-level helpers (get/set/rename/has/delete).
- Ships with convenient global helper functions.

## Installation

```bash
composer require jobmetric/laravel-env-modifier
```

> Laravel Package Auto-Discovery will register the service provider and facade.
Facade class: `JobMetric\EnvModifier\Facades\EnvModifier` (alias: `EnvModifier`).

## Quick Start

### 1) Choose which `.env` file to work with

By default your application’s main `.env` is used (via the service container).
If you want to work on another file, bind the path explicitly:

```php
use JobMetric\EnvModifier\Facades\EnvModifier as EnvMod;

// Option A: bind an existing env file
EnvMod::setPath(base_path('.env.testing'));

// Option B: create and bind a new env file
EnvMod::createFile(base_path('.env.staging'), [
    'APP_NAME' => 'My Staging App',
    'APP_ENV'  => 'staging',
], overwrite: false, bindToPath: true);
```

### 2) Upsert keys

```php
EnvMod::set([
    'APP_NAME' => 'My App',
    'APP_ENV'  => 'local',
    'DEBUG'    => true,         // stored as "true"
    'JSON'     => ['a' => 1],   // stored as JSON string
]);
```

### 3) Read keys

```php
// any combination of variadics and arrays
$values = EnvMod::get('APP_NAME', ['APP_ENV', 'DEBUG'], 'MISSING');
// => ['APP_NAME' => 'My App', 'APP_ENV' => 'local', 'DEBUG' => 'true', 'MISSING' => '']
```

### 4) Delete keys

```php
EnvMod::delete('DEBUG', ['JSON', 'MISSING']);
```

## File Operations

### Create a new `.env` file

```php
// string payload — trailing newline ensured
EnvMod::createFile(base_path('.env.payload'), "FOO=bar\n# comment");

// array payload — rendered to KEY=VALUE lines
EnvMod::createFile(base_path('.env.qa'), [
    'APP_NAME' => 'QA App',
    'APP_ENV'  => 'qa',
], overwrite: false, bindToPath: true);
```

### Back up the current file

```php
$backupPath = EnvMod::backup('.bak'); 
// => /full/path/.env.qa.bak.20250911_101530
```

### Restore from a backup

```php
EnvMod::restore($backupPath);             // writes backup content into currently bound path
EnvMod::restore($backupPath, true);       // writes and rebinds to the backup file itself
```

### Delete a file (with main .env protection)

```php
// Trying to delete the main .env requires force=true
EnvMod::setPath(base_path('.env'));
EnvMod::deleteFile(force: true, mainEnvAbsolutePath: base_path('.env'));

// Deleting a non-main env does not require force
EnvMod::setPath(base_path('.env.qa'));
EnvMod::deleteFile(force: false, mainEnvAbsolutePath: base_path('.env'));
```

### Merge from another env file

```php
// Merge only a subset
EnvMod::mergeFromPath(base_path('.env.template'), only: ['APP_NAME', 'CACHE_DRIVER']);

// Merge everything except a few
EnvMod::mergeFromPath(base_path('.env.shared'), only: [], except: ['APP_KEY']);
```

## Key Operations

### Read everything

```php
$all = EnvMod::all(); // ['APP_NAME' => 'My App', 'APP_ENV' => 'local', ...]
```

### Get values

```php
EnvMod::get('APP_NAME');                           // ['APP_NAME' => 'My App']
EnvMod::get('APP_NAME', 'APP_ENV');               // ['APP_NAME' => 'My App', 'APP_ENV' => 'local']
EnvMod::get(['APP_NAME', 'APP_ENV'], 'APP_DEBUG');// mixed variadic + array
```

### Upsert values

```php
EnvMod::set([
    'APP_NAME' => 'Renamed App',
    'FEATURE_X'=> false,   // stored as "false"
]);
```

### Set defaults only (non-destructive)

```php
// Only writes keys that are missing or currently empty
EnvMod::setIfMissing([
    'APP_URL'     => 'http://localhost',
    'APP_TIMEZONE'=> 'UTC',
]);
```

### Rename a key

```php
// Move value NEW_KEY -> RENAMED_KEY (throws if RENAMED_KEY exists and overwrite=false)
EnvMod::rename('NEW_KEY', 'RENAMED_KEY', overwrite: false);
```

### Delete keys

```php
EnvMod::delete('FEATURE_X', ['LEGACY_1', 'LEGACY_2']);
```

### Existence check

```php
EnvMod::has('APP_NAME'); // true/false
```

## Helper Functions

These are globally available helpers that intentionally avoid naming conflicts with Laravel.

```php
use function env_modifier_use;
use function env_modifier_create;
use function env_modifier_delete_file;
use function env_modifier_all;
use function env_modifier_get;
use function env_modifier_has;
use function env_modifier_put;
use function env_modifier_set;
use function env_modifier_set_if_missing;
use function env_modifier_rename;
use function env_modifier_forget;
use function env_modifier_backup;
use function env_modifier_restore;
use function env_modifier_merge_from;
```

### Examples

```php
// Bind file
env_modifier_use(base_path('.env.staging'));

// Create (and bind) new env file
env_modifier_create(base_path('.env.preview'), [
    'APP_NAME' => 'Preview',
    'APP_ENV'  => 'preview',
], overwrite: false, bindToPath: true);

// Read / write
env_modifier_put('APP_NAME', 'Preview++');
env_modifier_set(['CACHE_DRIVER' => 'redis']);
$has = env_modifier_has('CACHE_DRIVER');          // true
$all = env_modifier_all();                        // associative array
$part= env_modifier_get('APP_NAME', 'CACHE_DRIVER');

// Defaults only
env_modifier_set_if_missing([
    'SESSION_DRIVER' => 'file',
    'APP_TIMEZONE'   => 'UTC',
]);

// Rename and delete
env_modifier_rename('APP_NAME', 'APPLICATION_NAME', overwrite: true);
env_modifier_forget('SESSION_DRIVER');

// Backup & Restore
$backup = env_modifier_backup('.bak');
env_modifier_restore($backup);

// Delete file with main .env protection
env_modifier_delete_file(force: false, mainEnvAbsolutePath: base_path('.env'));
```

---

## Value Normalization & Parsing Rules

- **Comments/whitespace**: Lines starting with `#` (including lines with leading spaces before `#`) and blank lines are ignored.
- **Reading:**
    - Surrounding quotes are stripped: `"My App"` → `My App`.
    - Escaped newlines `\n` are converted back to real newlines.
- **Writing:**
    - `true`/`false` booleans become `"true"`/`"false"` strings.
    - Arrays/objects are JSON-encoded (compact JSON).
    - Auto-quotes are added when the value contains spaces, `#`, `=`, or has leading/trailing whitespace.
    - Real newlines are escaped to `\n` so they survive the `.env` line format.
- **Atomic writes**: File writes use `LOCK_EX`.
- **Regex safety**: Keys are escaped with `preg_quote` when matched/updated.

---

## API Reference (Facade)

All methods are available via the facade:

```php
use JobMetric\EnvModifier\Facades\EnvModifier as EnvMod;
```

### File-level

- `setPath(string $path): static`

    Bind to an existing `.env` file (throws `EnvFileNotFoundException` if missing).

- `createFile(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true): static`

    Create a new file from array or string content and optionally bind to it.

- `backup(string $suffix = '.bak'): string`

    Create a timestamped backup next to the current file. Returns the backup path (e.g. `.../.env.bak.20250911_101530`).

- `restore(string $backupPath, bool $bindToPath = false): static`

    Restore the bound file’s contents from a backup. Optionally rebind to the backup file itself.

- `deleteFile(bool $force = false, ?string $mainEnvAbsolutePath = null): void`

    Delete the bound file. If `$mainEnvAbsolutePath` matches the bound file, `force=true` is required.

- `mergeFromPath(string $path, array $only = [], array $except = []): static`

    Merge keys from another `.env` file with optional allow/deny lists.

### Key-level

- `all(): array<string,string>`

    Read all key/value pairs as an associative array.

- `get(...$keys): array<string,string>`

    Read one or more keys. Variadics and nested arrays allowed. Missing keys return empty string.

- `set(array $data): static`

    Upsert multiple keys.

- `setIfMissing(array $data): static`

    Only write keys that are missing or empty.

- `rename(string $from, string $to, bool $overwrite = false): static`

    Rename a key (throws if target exists and overwrite is false).

- `delete(...$keys): static`

    Remove one or more keys (variadics and arrays allowed).

- `has(string $key): bool`

    Existence check for a non-commented `KEY=` line.

---

## Common Patterns

### Initialize a new environment from a template

```php
// Copy chosen keys from template into a fresh env file
EnvMod::createFile(base_path('.env.stage'), [], bindToPath: true);
EnvMod::mergeFromPath(base_path('.env.template'), only: [
    'APP_NAME', 'APP_ENV', 'CACHE_DRIVER', 'SESSION_DRIVER'
]);
EnvMod::setIfMissing([
    'APP_ENV'  => 'staging',
    'APP_NAME' => 'My Staging',
]);
```

### Safe refactor of key names

```php
// Move LEGACY_URL to APP_URL without risk of clobbering an existing APP_URL
if (!EnvMod::has('APP_URL')) {
    EnvMod::rename('LEGACY_URL', 'APP_URL');
}
```

### Guard the main .env

```php
try {
    EnvMod::setPath(base_path('.env'));
    EnvMod::deleteFile(force: false, mainEnvAbsolutePath: base_path('.env'));
} catch (\RuntimeException $e) {
    // Expected unless force=true
}
```

---

## Exceptions

- `JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException`

    Thrown when the bound path is missing or not set.

- `\RuntimeException`

    Thrown for:
    - Creating a file that already exists with `overwrite=false`
    - Deleting the main env without `force=true`
    - Copy/read failures during backup/restore/merge
    - Invalid directory creation

---

### Notes & Best Practices

- Prefer `setIfMissing()` to seed defaults without overriding user edits.
- Use `backup()` right before risky migrations.
- When generating values containing spaces, `#`, `=`, or surrounding whitespace, you don’t need to quote **we’ll quote for you**.
- Newlines in values are preserved (stored as `\n`, restored as real newlines on read).

## Contributing

Thank you for considering contributing to Laravel Env Modifier! See the [CONTRIBUTING.md](https://github.com/jobmetric/laravel-env-modifier/blob/master/CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See the [License File](https://github.com/jobmetric/laravel-env-modifier/blob/master/LICENCE.md) for details.
