<?php

namespace JobMetric\EnvModifier\Tests;

use JobMetric\EnvModifier\Facades\EnvModifier as EnvMod;
use RuntimeException;

/**
 * File-level operations tests (create/backup/restore/delete/merge).
 */
class EnvModifierFileOpsTest extends TestCase
{
    /**
     * Temporary working directory for test files.
     *
     * @var string
     */
    private string $workdir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workdir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'env_modifier_ops_' . bin2hex(random_bytes(4));

        if (!is_dir($this->workdir) && !mkdir($this->workdir, 0775, true) && !is_dir($this->workdir)) {
            $this->fail('Failed to create temp workdir: ' . $this->workdir);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->workdir);

        parent::tearDown();
    }

    public function test_create_with_string_payload_adds_trailing_newline(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.payload';
        $payload = "A=1\n#comment\nB=\"hello world\"";
        EnvMod::createFile($path, $payload, bindToPath: true);

        $raw = file_get_contents($path);
        $this->assertNotFalse($raw);
        $this->assertStringEndsWith(PHP_EOL, $raw);
        $this->assertStringContainsString('A=1', $raw);
        $this->assertStringContainsString('#comment', $raw);
    }

    public function test_backup_file_creation_and_suffix(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.backup';
        EnvMod::createFile($path, ['A' => '1'], bindToPath: true);

        $backup = EnvMod::backup('.bak');
        $this->assertFileExists($backup);
        $this->assertStringStartsWith($path . '.bak.', $backup);
    }

    public function test_delete_file_non_main_without_force_is_allowed(): void
    {
        $mainPath = $this->workdir . DIRECTORY_SEPARATOR . '.env.main';
        $other    = $this->workdir . DIRECTORY_SEPARATOR . '.env.other';

        EnvMod::createFile($mainPath, ['X' => 'y']);
        EnvMod::createFile($other, ['Z' => 'w'], bindToPath: true);

        // Delete "other" while protecting "main" (should be allowed)
        EnvMod::deleteFile(force: false, mainEnvAbsolutePath: $mainPath);
        $this->assertFileDoesNotExist($other);
        $this->assertFileExists($mainPath);
    }

    public function test_merge_missing_source_throws(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.merge';
        EnvMod::createFile($path, ['A' => '1'], bindToPath: true);

        $this->expectException(RuntimeException::class);
        EnvMod::mergeFromPath($this->workdir . DIRECTORY_SEPARATOR . 'missing.env');
    }

    public function test_restore_with_bind_to_path_true_rebinds_to_backup(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.restore';
        EnvMod::createFile($path, ['A' => '1', 'B' => '2'], bindToPath: true);

        $backup = EnvMod::backup();

        // Change original and then restore from backup but rebind to backup file
        EnvMod::set(['A' => 'changed']);
        EnvMod::restore($backup, bindToPath: true);

        // Now writes should target **backup file**, not the original
        EnvMod::set(['R' => 'x']);

        $original = file_get_contents($path);
        $backupRaw = file_get_contents($backup);

        $this->assertNotFalse($original);
        $this->assertNotFalse($backupRaw);

        // Original has the restored content but not the new R key (since rebind switched to backup)
        $this->assertStringContainsString("A=1", $original);
        $this->assertStringContainsString("B=2", $original);
        $this->assertStringNotContainsString("R=x", $original);

        // Backup now also includes R
        $this->assertStringContainsString("R=x", $backupRaw);
    }

    /**
     * Best-effort recursive directory deletion for cleanup.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
