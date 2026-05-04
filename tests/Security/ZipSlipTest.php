<?php

namespace DreamFactory\Core\File\Tests\Security;

use DreamFactory\Core\File\Components\LocalFileSystem;
use PHPUnit\Framework\TestCase;

/**
 * Security: extractZipFile() must refuse zip entries whose names would
 * write outside the extraction target.
 *
 * Phase 2 audit found:
 *
 *     for ($i = 0; $i < $zip->numFiles; $i++) {
 *         $name = $zip->getNameIndex($i);
 *         ...
 *         $fullPathName = $path . $name;
 *         ...
 *         $this->writeFile($container, $fullPathName, $content);
 *     }
 *
 * No traversal validation. A malicious zip with entries like
 * `../../../etc/passwd` or `/etc/passwd` would write OUTSIDE the
 * configured storage container — full filesystem write to the
 * webserver-user-writable surface.
 *
 * After the fix, zipEntryEscapesTarget() rejects:
 *   - any segment equal to ".."
 *   - absolute paths (leading "/" or "\\")
 *   - Windows drive-letter paths ("C:...")
 *   - null bytes
 *   - URL-encoded traversal (..%2f, %2e%2e/)
 */
class ZipSlipTest extends TestCase
{
    /**
     * @dataProvider unsafeEntryProvider
     */
    public function testRejectsUnsafeEntry(string $name): void
    {
        $this->assertTrue(
            LocalFileSystem::zipEntryEscapesTarget($name),
            "Zip entry should be flagged unsafe: {$name}"
        );
    }

    public static function unsafeEntryProvider(): array
    {
        return [
            'parent traversal'       => ['../passwd'],
            'deep traversal'         => ['a/b/../../../etc/passwd'],
            'leading slash absolute' => ['/etc/passwd'],
            'leading backslash'      => ['\\Windows\\System32\\evil.exe'],
            'windows drive letter'   => ['C:/Windows/System32/evil.exe'],
            'null byte'              => ["safe.txt\0../../etc/passwd"],
            'url-encoded traversal'  => ['a/..%2F../etc/passwd'],
            'mixed seps'             => ['a/b\\..\\..\\evil'],
        ];
    }

    /**
     * @dataProvider safeEntryProvider
     */
    public function testAcceptsSafeEntry(string $name): void
    {
        $this->assertFalse(
            LocalFileSystem::zipEntryEscapesTarget($name),
            "Zip entry should be accepted as safe: {$name}"
        );
    }

    public static function safeEntryProvider(): array
    {
        return [
            'simple file'     => ['file.txt'],
            'subdir'          => ['data/file.txt'],
            'deep subdir'     => ['a/b/c/d.txt'],
            'with dots in name' => ['my.file.v2.txt'],
            'with parent dir name' => ['parent_dir/file.txt'],
            'dot in middle'   => ['a/.b/c'],
        ];
    }

    public function testSourceCallsValidatorBeforeWrite(): void
    {
        $sourcePath = __DIR__ . '/../../src/Components/LocalFileSystem.php';
        $this->assertFileExists($sourcePath);
        $contents = file_get_contents($sourcePath);

        $start = strpos($contents, 'function extractZipFile');
        $this->assertNotFalse($start);
        $next = strpos($contents, "\n    /**", $start + 10);
        $body = substr($contents, $start, $next === false ? null : ($next - $start));

        $this->assertMatchesRegularExpression(
            '/(self|static)::zipEntryEscapesTarget\s*\(/',
            $body,
            'extractZipFile() must call zipEntryEscapesTarget() on each entry name'
        );
    }
}
