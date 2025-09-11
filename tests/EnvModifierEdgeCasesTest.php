<?php

namespace JobMetric\EnvModifier\Tests;

use JobMetric\EnvModifier\Facades\EnvModifier as EnvMod;

/**
 * Edge-case parsing and key-level operations tests.
 */
class EnvModifierEdgeCasesTest extends TestCase
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
            . 'env_modifier_edge_' . bin2hex(random_bytes(4));

        if (!is_dir($this->workdir) && !mkdir($this->workdir, 0775, true) && !is_dir($this->workdir)) {
            $this->fail('Failed to create temp workdir: ' . $this->workdir);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->workdir);

        parent::tearDown();
    }

    public function test_auto_quoting_and_strip_quotes_roundtrip(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.quoting';
        EnvMod::createFile($path, null, bindToPath: true);

        // Values that should be auto-quoted on write
        EnvMod::set([
            'WITH_SPACE'     => 'hello world',
            'WITH_HASH'      => 'abc#def',
            'WITH_EQUALS'    => 'x=y',
            'WITH_TRIM'      => '  padded  ',
            'BOOLEAN_TRUE'   => true,
            'BOOLEAN_FALSE'  => false,
            'ARRAY_JSON'     => ['a' => 1, 'b' => 2],
        ]);

        $read = EnvMod::get(
            'WITH_SPACE', 'WITH_HASH', 'WITH_EQUALS', 'WITH_TRIM',
            'BOOLEAN_TRUE', 'BOOLEAN_FALSE', 'ARRAY_JSON'
        );

        $this->assertSame('hello world', $read['WITH_SPACE']);
        $this->assertSame('abc#def', $read['WITH_HASH']);
        $this->assertSame('x=y', $read['WITH_EQUALS']);
        $this->assertSame('  padded  ', $read['WITH_TRIM']);
        $this->assertSame('true', $read['BOOLEAN_TRUE']);
        $this->assertSame('false', $read['BOOLEAN_FALSE']);
        $this->assertSame('{"a":1,"b":2}', $read['ARRAY_JSON']);
    }

    public function test_newline_escape_roundtrip(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.newline';
        EnvMod::createFile($path, null, bindToPath: true);

        $value = "line1\nline2\nline3";
        EnvMod::set(['MULTI' => $value]);

        $read = EnvMod::get('MULTI');
        $this->assertSame($value, $read['MULTI']);
        $this->assertStringContainsString("line2", $read['MULTI']);
    }

    public function test_has_ignores_commented_and_whitespace_prefixed_comments(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.commented';
        $content = "#APP_NAME=foo\n   # COMMENTED_KEY=bar\nVISIBLE=ok\n";
        EnvMod::createFile($path, $content, bindToPath: true);

        $this->assertFalse(EnvMod::has('APP_NAME'));
        $this->assertFalse(EnvMod::has('COMMENTED_KEY'));
        $this->assertTrue(EnvMod::has('VISIBLE'));
    }

    public function test_delete_nonexistent_key_does_not_change_file(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.delete';
        EnvMod::createFile($path, "A=1\nB=2\n", bindToPath: true);

        $before = file_get_contents($path);
        EnvMod::delete('NOT_EXISTS');
        $after = file_get_contents($path);

        $this->assertSame($before, $after);
    }

    public function test_rename_same_key_is_noop(): void
    {
        $path = $this->workdir . DIRECTORY_SEPARATOR . '.env.rename';
        EnvMod::createFile($path, ['FOO' => 'bar'], bindToPath: true);

        EnvMod::rename('FOO', 'FOO'); // no-op
        $read = EnvMod::get('FOO');

        $this->assertSame('bar', $read['FOO']);
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
