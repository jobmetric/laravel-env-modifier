<?php

namespace JobMetric\EnvModifier\Tests;

use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;
use JobMetric\EnvModifier\Facades\EnvModifier as EnvMod;
use RuntimeException;

/**
 * EnvModifier Integration Tests
 *
 * This suite relies on the package TestCase provided by:
 * JobMetric\EnvModifier\Tests\TestCase (extends Orchestra\Testbench\TestCase)
 */
class EnvModifierTest extends TestCase
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
            . 'env_modifier_' . bin2hex(random_bytes(4));

        if (!is_dir($this->workdir) && !mkdir($this->workdir, 0775, true) && !is_dir($this->workdir)) {
            $this->fail('Failed to create temp workdir: ' . $this->workdir);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->workdir);

        parent::tearDown();
    }

    public function test_create_file_and_basic_crud(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.testing';

        // Create new file and bind to it
        EnvMod::createFile($path, [
            'APP_NAME' => 'My App',
            'APP_ENV'  => 'testing',
            'EMPTY'    => null,
        ], overwrite: false, bindToPath: true);

        // Read all
        $all = EnvMod::all();
        $this->assertSame('My App', $all['APP_NAME'] ?? null);
        $this->assertSame('testing', $all['APP_ENV'] ?? null);
        $this->assertSame('', $all['EMPTY'] ?? null);

        // has / get
        $this->assertTrue(EnvMod::has('APP_NAME'));
        $this->assertFalse(EnvMod::has('NOT_EXISTS'));

        $got = EnvMod::get('APP_ENV', 'NOT_EXISTS');
        $this->assertSame(['APP_ENV' => 'testing', 'NOT_EXISTS' => ''], $got);

        // set (upsert) multiple
        EnvMod::set([
            'APP_NAME' => 'Renamed App',
            'DEBUG'    => true,
            'JSON'     => ['a' => 1, 'b' => 2],
        ]);

        $afterSet = EnvMod::get('APP_NAME', 'DEBUG', 'JSON');
        $this->assertSame('Renamed App', $afterSet['APP_NAME']);
        $this->assertSame('true', $afterSet['DEBUG']);
        $this->assertSame('{"a":1,"b":2}', $afterSet['JSON']);

        // delete keys
        EnvMod::delete('EMPTY', 'DEBUG');
        $afterDelete = EnvMod::get('EMPTY', 'DEBUG');
        $this->assertSame('', $afterDelete['EMPTY']);
        $this->assertSame('', $afterDelete['DEBUG']);
    }

    public function test_set_if_missing_and_rename(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.ifmissing';

        EnvMod::createFile($path, [
            'FOO'   => 'bar',
            'BLANK' => '',
        ], bindToPath: true);

        // Should not overwrite FOO, should fill BLANK and create NEW
        EnvMod::setIfMissing([
            'FOO'   => 'should_not_write',
            'BLANK' => 'now_filled',
            'NEW'   => 'created',
        ]);

        $data = EnvMod::get('FOO', 'BLANK', 'NEW');
        $this->assertSame('bar', $data['FOO']);
        $this->assertSame('now_filled', $data['BLANK']);
        $this->assertSame('created', $data['NEW']);

        // Rename NEW -> RENAMED (no overwrite)
        EnvMod::rename('NEW', 'RENAMED');
        $afterRename = EnvMod::get('NEW', 'RENAMED');
        $this->assertSame('', $afterRename['NEW']);
        $this->assertSame('created', $afterRename['RENAMED']);

        // Renaming into an existing key without overwrite should throw
        $this->expectException(RuntimeException::class);
        EnvMod::rename('FOO', 'RENAMED', false);
    }

    public function test_backup_and_restore(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.backup';

        EnvMod::createFile($path, [
            'A' => '1',
            'B' => '2',
        ], bindToPath: true);

        $backupPath = EnvMod::backup();

        // Change values, then restore
        EnvMod::set(['A' => 'changed', 'C' => '3']);
        $mid = EnvMod::all();
        $this->assertSame('changed', $mid['A']);
        $this->assertSame('2', $mid['B']);
        $this->assertSame('3', $mid['C']);

        EnvMod::restore($backupPath);
        $afterRestore = EnvMod::all();
        $this->assertSame('1', $afterRestore['A']);
        $this->assertSame('2', $afterRestore['B']);
        $this->assertArrayNotHasKey('C', $afterRestore);
    }

    public function test_merge_from_path_with_filters(): void
    {
        $base = $this->workdir . DIRECTORY_SEPARATOR . '.env.base';
        $src  = $this->workdir . DIRECTORY_SEPARATOR . '.env.src';

        // Base file
        EnvMod::createFile($base, [
            'K1' => 'base1',
            'K2' => 'base2',
            'K3' => 'base3',
        ], bindToPath: true);

        // Source file content
        file_put_contents($src, "K1=src1\nK2=src2\nK4=src4\n", LOCK_EX);

        // Merge only K1,K4 (so K2 from src ignored), no except
        EnvMod::mergeFromPath($src, only: ['K1', 'K4']);

        $data = EnvMod::all();
        $this->assertSame('src1', $data['K1']);
        $this->assertSame('base2', $data['K2']);
        $this->assertSame('base3', $data['K3']);
        $this->assertSame('src4', $data['K4']);

        // Merge everything except K4 (so K2 should update to src2)
        EnvMod::mergeFromPath($src, only: [], except: ['K4']);
        $data2 = EnvMod::all();
        $this->assertSame('src1', $data2['K1']); // remains from previous merge
        $this->assertSame('src2', $data2['K2']);
        $this->assertSame('base3', $data2['K3']);
        $this->assertSame('src4', $data2['K4']); // unchanged due to except
    }

    public function test_delete_file_requires_force_for_main_env(): void
    {
        $mainPath = $this->workdir . DIRECTORY_SEPARATOR . '.env.main';
        EnvMod::createFile($mainPath, ['X' => 'y'], bindToPath: true);

        // Without force -> expect exception
        $this->expectException(RuntimeException::class);
        EnvMod::deleteFile(force: false, mainEnvAbsolutePath: $mainPath);
    }

    public function test_set_path_throws_when_file_missing(): void
    {
        $this->expectException(EnvFileNotFoundException::class);
        EnvMod::setPath($this->workdir . DIRECTORY_SEPARATOR . 'not-exists.env');
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
