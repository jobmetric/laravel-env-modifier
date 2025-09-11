<?php

namespace JobMetric\EnvModifier;

use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;

/**
 * Class EnvModifier
 *
 * A lightweight utility to create, read, merge, write, and delete keys inside .env-style files.
 * - Ignores commented lines (starting with '#') and surrounding whitespaces.
 * - Escapes regex-sensitive keys to avoid pattern injection.
 * - Writes atomically using LOCK_EX to reduce race conditions.
 * - Auto-quotes values on write when they contain spaces or special characters.
 * - Can create/backup/restore/delete env files with safeguards.
 */
class EnvModifier
{
    /**
     * The absolute path of the env file to operate on.
     *
     * @var string
     */
    private string $filePath;

    /**
     * Set the env file path to be used by the modifier.
     *
     * Role: Validates that the file exists and stores its path for future operations.
     *
     * @param string $path Absolute or relative path to the .env file.
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the file does not exist at the given path.
     */
    public function setPath(string $path): static
    {
        if (!file_exists($path)) {
            throw new EnvFileNotFoundException($path);
        }

        $this->filePath = $path;

        return $this;
    }

    /**
     * Create a new env file at the given path if it does not already exist.
     *
     * Role: Ensures the directory exists, writes provided content, and optionally binds this instance to the path.
     * Content may be:
     *   - array<string, mixed>: rendered to KEY=VALUE lines with proper quoting
     *   - string: written as-is (a trailing newline will be ensured)
     *   - null: creates an empty file
     *
     * @param string $path Absolute or relative path to create (e.g., '/path/.env.staging').
     * @param array<string,mixed>|string|null $content Initial content. Array will be rendered to lines.
     * @param bool $overwrite When true, overwrite an existing file; when false, throw if file exists.
     * @param bool $bindToPath When true, sets this instance path to the created file.
     *
     * @return static
     */
    public function createFile(string $path, array|string|null $content = null, bool $overwrite = false, bool $bindToPath = true): static
    {
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Failed to create directory: {$dir}");
        }

        if (file_exists($path) && !$overwrite) {
            throw new \RuntimeException("Env file already exists: {$path}");
        }

        $payload = '';
        if (is_array($content)) {
            $lines = [];
            foreach ($content as $k => $v) {
                $lines[] = $this->renderKeyValueLine((string) $k, $v);
            }
            $payload = implode(PHP_EOL, $lines) . PHP_EOL;
        } elseif (is_string($content)) {
            $payload = $content;
            if ($payload !== '' && !str_ends_with($payload, PHP_EOL)) {
                $payload .= PHP_EOL;
            }
        }

        file_put_contents($path, $payload, LOCK_EX);

        if ($bindToPath) {
            $this->filePath = $path;
        }

        return $this;
    }

    /**
     * Delete the current env file with optional protection for the main application .env.
     *
     * Role: Deletes the file pointed to by $this->filePath. If the file matches the provided
     *       $mainEnvAbsolutePath, deletion requires $force = true.
     *
     * @param bool $force When true, allows deleting even if this is the main env path.
     * @param string|null $mainEnvAbsolutePath Absolute path of the protected "main" .env (e.g., base_path('.env')).
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If file path is unset or file does not exist.
     * @throws \RuntimeException If attempting to delete the main env without force.
     */
    public function deleteFile(bool $force = false, ?string $mainEnvAbsolutePath = null): void
    {
        $this->ensurePathSet();

        if (!file_exists($this->filePath)) {
            throw new EnvFileNotFoundException($this->filePath);
        }

        if ($mainEnvAbsolutePath !== null) {
            $current = $this->safeRealpath($this->filePath);
            $main    = $this->safeRealpath($mainEnvAbsolutePath);

            if ($current !== null && $main !== null && $current === $main && !$force) {
                throw new \RuntimeException(
                    'Deletion blocked: The target is the main application .env. Use force=true to proceed.'
                );
            }
        }

        if (!@unlink($this->filePath)) {
            throw new \RuntimeException("Failed to delete env file: {$this->filePath}");
        }
    }

    /**
     * Make a timestamped backup next to the current env file.
     *
     * Role: Copies the current env to a new file with suffix and timestamp.
     *
     * @param string $suffix Suffix to append (before timestamp extension).
     *
     * @return string Absolute path of the created backup file.
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function backup(string $suffix = '.bak'): string
    {
        $this->ensurePathSet();

        if (!file_exists($this->filePath)) {
            throw new EnvFileNotFoundException($this->filePath);
        }

        $timestamp = date('Ymd_His');
        $backup = $this->filePath . $suffix . '.' . $timestamp;

        if (!@copy($this->filePath, $backup)) {
            throw new \RuntimeException("Failed to create backup: {$backup}");
        }

        return $backup;
    }

    /**
     * Restore content from a backup file into the current env path.
     *
     * Role: Reads the backup file and writes its content into the currently bound env path.
     *
     * @param string $backupPath Absolute path of the backup file.
     * @param bool $bindToPath When true, sets this instance path to the backup file path (rare).
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the target env path has not been set.
     */
    public function restore(string $backupPath, bool $bindToPath = false): static
    {
        $this->ensurePathSet();

        if (!file_exists($backupPath)) {
            throw new \RuntimeException("Backup file not found: {$backupPath}");
        }

        $content = file_get_contents($backupPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read backup file: {$backupPath}");
        }

        $this->writeContent($content);

        if ($bindToPath) {
            $this->filePath = $backupPath;
        }

        return $this;
    }

    /**
     * Merge keys from another .env file into the current one.
     *
     * Role: Parses a source env file and applies its keys onto the current env file,
     *       optionally filtering with allow/deny lists.
     *
     * @param string $path Source env file path to read.
     * @param array<int,string> $only If non-empty, only these keys are merged.
     * @param array<int,string> $except Keys to exclude from merge.
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the current env file is not accessible.
     */
    public function mergeFromPath(string $path, array $only = [], array $except = []): static
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Merge source file not found: {$path}");
        }

        $sourceContent = file_get_contents($path);
        if ($sourceContent === false) {
            throw new \RuntimeException("Failed to read merge source: {$path}");
        }

        $incoming = $this->parseAllFromContent($sourceContent);

        if (!empty($only)) {
            $incoming = array_intersect_key($incoming, array_flip($only));
        }

        if (!empty($except)) {
            $incoming = array_diff_key($incoming, array_flip($except));
        }

        return $this->set($incoming);
    }

    /**
     * Get all keys from the env file as an associative array.
     *
     * @return array<string,string>
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function all(): array
    {
        $content = $this->getContent();

        return $this->parseAllFromContent($content);
    }

    /**
     * Get multiple keys from the env file.
     *
     * Role: Returns an associative array of requested keys => values. Missing keys return ''.
     * Accepts variadic list or nested arrays, e.g. get('A', ['B', 'C']).
     *
     * @param mixed ...$keys One or more keys or arrays of keys.
     *
     * @return array<string,string> Associative array of key => value (empty string if not found).
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function get(...$keys): array
    {
        $flat = $this->flattenKeys($keys);
        $content = $this->getContent();

        $result = [];
        foreach ($flat as $key) {
            $result[$key] = $this->getKeyFromContent($content, $key);
        }

        return $result;
    }

    /**
     * Set multiple keys (upsert) in the env file.
     *
     * Role: For each key/value, update the existing line or append a new line if it does not exist.
     *
     * @param array<string,mixed> $data Associative array of key => value.
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function set(array $data): static
    {
        $content = $this->getContent();

        foreach ($data as $key => $value) {
            $content = $this->setKeyIntoContent($content, (string) $key, $value);
        }

        $this->writeContent($content);

        return $this;
    }

    /**
     * Set keys only if they are missing or empty.
     *
     * Role: Preserves existing non-empty values, fills only blanks/missing keys.
     *
     * @param array<string,mixed> $data
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function setIfMissing(array $data): static
    {
        $content = $this->getContent();

        foreach ($data as $key => $value) {
            $current = $this->getKeyFromContent($content, (string) $key);
            if ($current === '') {
                $content = $this->setKeyIntoContent($content, (string) $key, $value);
            }
        }

        $this->writeContent($content);

        return $this;
    }

    /**
     * Rename a key to a new name (optionally overwriting existing).
     *
     * Role: Moves the value from $from to $to. If $to exists and overwrite is false, throws.
     *
     * @param string $from Source key.
     * @param string $to Target key.
     * @param bool $overwrite Allow overwriting if $to already exists.
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function rename(string $from, string $to, bool $overwrite = false): static
    {
        if ($from === $to) {
            return $this;
        }

        $content = $this->getContent();

        $value = $this->getKeyFromContent($content, $from);
        if ($value === '') {
            return $this;
        }

        $targetExists = $this->has($to);
        if ($targetExists && !$overwrite) {
            throw new \RuntimeException("Target key already exists: {$to}");
        }

        $content = $this->deleteKeyFromContent($content, $from);
        $content = $this->setKeyIntoContent($content, $to, $value);

        $this->writeContent($content);

        return $this;
    }

    /**
     * Delete one or more keys from the env file.
     *
     * Role: Removes matching lines; keeps other content and comments intact.
     *
     * @param mixed ...$keys One or more keys or arrays of keys.
     *
     * @return static
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function delete(...$keys): static
    {
        $flat = $this->flattenKeys($keys);
        $content = $this->getContent();

        foreach ($flat as $key) {
            $content = $this->deleteKeyFromContent($content, $key);
        }

        // Normalize: collapse consecutive blank lines introduced by deletions.
        $content = preg_replace("/\R{3,}/", PHP_EOL . PHP_EOL, (string) $content);

        $this->writeContent((string) $content);

        return $this;
    }

    /**
     * Check the existence of a key in the env file.
     *
     * Role: Returns true if a non-commented line with "KEY=" exists.
     *
     * @param string $key Env key to search for.
     *
     * @return bool True if present, false otherwise.
     *
     * @throws EnvFileNotFoundException If the env file is not accessible.
     */
    public function has(string $key): bool
    {
        $content = $this->getContent();

        $quotedKey = preg_quote($key, '/');

        return (bool) preg_match(
            "/^(?!\s*#)\s*{$quotedKey}\s*=/m",
            $content
        );
    }

    /**
     * Read the env file content.
     *
     * Role: Ensures the path is set and returns the entire file as string.
     *
     * @return string Full file content.
     *
     * @throws EnvFileNotFoundException If the file path is unset or file is missing.
     */
    private function getContent(): string
    {
        $this->ensurePathSet();

        if (!file_exists($this->filePath)) {
            throw new EnvFileNotFoundException($this->filePath);
        }

        $data = file_get_contents($this->filePath);

        return $data === false ? '' : $data;
    }

    /**
     * Write the env file content atomically.
     *
     * Role: Persists new content with exclusive lock to reduce race conditions.
     *
     * @param string $content New content to write.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If the file path is not set or file is missing.
     */
    private function writeContent(string $content): void
    {
        $this->ensurePathSet();

        if (!file_exists($this->filePath)) {
            throw new EnvFileNotFoundException($this->filePath);
        }

        file_put_contents($this->filePath, $content, LOCK_EX);
    }

    /**
     * Ensure the filePath has been set before doing any IO.
     *
     * @return void
     *
     * @throws EnvFileNotFoundException If file path has not been set.
     */
    private function ensurePathSet(): void
    {
        if (!isset($this->filePath) || $this->filePath === '') {
            throw new EnvFileNotFoundException('Env file path is not set.');
        }
    }

    /**
     * Extract a single key's value from the given file content.
     *
     * Role: Matches a non-commented line "KEY=VALUE" and returns VALUE with quotes stripped.
     *
     * @param string $content The full env file content.
     * @param string $key The key to search for.
     *
     * @return string The matched value ('' if not found).
     */
    private function getKeyFromContent(string $content, string $key): string
    {
        $quotedKey = preg_quote($key, '/');

        if (preg_match("/^(?!\s*#)\s*{$quotedKey}\s*=\s*(.*)$/m", $content, $m)) {
            $raw = trim($m[1]);

            return $this->stripQuotes($raw);
        }

        return '';
    }

    /**
     * Insert or update a key/value pair into the given content.
     *
     * Role: Replaces existing line for the key, or appends a new line at the end if absent.
     *
     * @param string $content The current env content.
     * @param string $key The env key.
     * @param mixed $value The env value to set.
     *
     * @return string Updated content.
     */
    private function setKeyIntoContent(string $content, string $key, mixed $value): string
    {
        $quotedKey = preg_quote($key, '/');
        $valueStr = $this->normalizeValueForWrite($value);

        if (preg_match("/^(?!\s*#)\s*{$quotedKey}\s*=/m", $content)) {
            return (string) preg_replace(
                "/^(?!\s*#)\s*{$quotedKey}\s*=.*$/m",
                "{$key}={$valueStr}",
                $content
            );
        }

        $append = ($content !== '' && !str_ends_with($content, PHP_EOL)) ? PHP_EOL : '';

        return $content . $append . "{$key}={$valueStr}" . PHP_EOL;
    }

    /**
     * Remove a key from the given content.
     *
     * Role: Deletes non-commented lines matching "KEY=...".
     *
     * @param string $content The current env content.
     * @param string $key The key to delete.
     *
     * @return string Updated content without the key line.
     */
    private function deleteKeyFromContent(string $content, string $key): string
    {
        $quotedKey = preg_quote($key, '/');

        $updated = (string) preg_replace(
            "/^(?!\s*#)\s*{$quotedKey}\s*=.*\R?/m",
            '',
            $content
        );

        return $updated;
    }

    /**
     * Parse all non-comment key/value pairs from content.
     *
     * Role: Converts the text content into an associative array using the same read rules as get().
     *
     * @param string $content
     *
     * @return array<string,string>
     */
    private function parseAllFromContent(string $content): array
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $result = [];

        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }

            $pos = strpos($trim, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($trim, 0, $pos));
            if ($key === '') {
                continue;
            }

            $raw = trim(substr($trim, $pos + 1));
            $result[$key] = $this->stripQuotes($raw);
        }

        return $result;
    }

    /**
     * Flatten a variadic list of keys into a simple array of strings.
     *
     * Role: Accepts scalars or nested arrays and returns a flat array of key names.
     *
     * @param array<int,mixed> $keys Variadic collected keys.
     *
     * @return array<int,string> Flat list of keys.
     */
    private function flattenKeys(array $keys): array
    {
        $result = [];

        $stack = $keys;
        while (!empty($stack)) {
            $item = array_pop($stack);

            if (is_array($item)) {
                foreach ($item as $v) {
                    $stack[] = $v;
                }
            } elseif (is_string($item) || is_numeric($item)) {
                $result[] = (string) $item;
            }
        }

        $result = array_reverse($result);

        return array_values(array_unique($result));
    }

    /**
     * Normalize a value for writing into .env.
     *
     * Role: Converts arbitrary values to string. Auto-quotes when needed.
     * - true/false => "true"/"false"
     * - null => empty string
     * - arrays/objects => JSON encoded
     * - If contains spaces, #, =, or leading/trailing whitespace => wrap in double quotes
     * - Escapes internal newlines as "\n"
     *
     * @param mixed $value Any value to be persisted.
     *
     * @return string String representation suitable for .env.
     */
    private function normalizeValueForWrite(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $str = (string) $value;
        $str = str_replace(["\r\n", "\r", "\n"], '\n', $str);

        $needsQuotes =
            preg_match('/\s/', $str) ||
            str_contains($str, '#') ||
            str_contains($str, '=') ||
            $str !== trim($str);

        if ($needsQuotes) {
            $escaped = str_replace('"', '\"', $str);

            return '"' . $escaped . '"';
        }

        return $str;
    }

    /**
     * Strip surrounding double or single quotes and unescape common sequences.
     *
     * Role: When reading values, remove surrounding quotes and unescape \" and \n.
     *
     * @param string $value Raw matched value part after '='.
     *
     * @return string Clean value.
     */
    private function stripQuotes(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $inner = substr($value, 1, -1);
            $inner = str_replace(['\"', "\\'"], ['"', "'"], $inner);
            $inner = str_replace('\n', "\n", $inner);

            return $inner;
        }

        $value = str_replace('\n', "\n", $value);

        return $value;
    }

    /**
     * Render a single KEY=VALUE line from given pair.
     *
     * Role: Applies the same normalization used by set(...).
     *
     * @param string $key Env key.
     * @param mixed $value Env value.
     *
     * @return string Line in "KEY=VALUE" format.
     */
    private function renderKeyValueLine(string $key, mixed $value): string
    {
        return $key . '=' . $this->normalizeValueForWrite($value);
    }

    /**
     * Safe realpath resolver.
     *
     * Role: Returns normalized absolute path or null when it cannot be resolved.
     *
     * @param string $path Input path.
     *
     * @return string|null Real path or null.
     */
    private function safeRealpath(string $path): ?string
    {
        $real = @realpath($path);

        return $real !== false ? $real : null;
    }
}
