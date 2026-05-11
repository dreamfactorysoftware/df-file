<?php

namespace DreamFactory\Core\File\Tests\Security;

use DreamFactory\Core\File\Components\LocalFileSystem;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use PHPUnit\Framework\TestCase;

/**
 * Security: file-op path assembly must reject `..` traversal even when
 * the caller supplies it through container/path concatenation.
 */
class PathAssemblyTraversalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize the static $root by constructing the LocalFileSystem.
        new LocalFileSystem('/var/df/storage');
    }

    public function testNormalizationCollapsesSafeDots(): void
    {
        $reflection = new \ReflectionClass(LocalFileSystem::class);
        $method = $reflection->getMethod('lexicallyNormalize');
        $method->setAccessible(true);
        $this->assertSame('/var/df/storage/foo', $method->invoke(null, '/var/df/storage/./foo'));
        $this->assertSame('/var/df/storage', $method->invoke(null, '/var/df/storage/foo/..'));
    }

    public function testTraversalEscapeIsRejected(): void
    {
        $reflection = new \ReflectionClass(LocalFileSystem::class);
        $method = $reflection->getMethod('asFullPath');
        $method->setAccessible(true);

        $this->expectException(InternalServerErrorException::class);
        $method->invoke(null, '../../etc/passwd', false);
    }

    public function testDeepTraversalEscapeIsRejected(): void
    {
        $reflection = new \ReflectionClass(LocalFileSystem::class);
        $method = $reflection->getMethod('asFullPath');
        $method->setAccessible(true);

        $this->expectException(InternalServerErrorException::class);
        $method->invoke(null, 'a/b/../../../etc/passwd', false);
    }

    public function testLegitNestedPathAccepted(): void
    {
        $reflection = new \ReflectionClass(LocalFileSystem::class);
        $method = $reflection->getMethod('asFullPath');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'docs/2024/report.pdf', true);
        $this->assertStringStartsWith('/var/df/storage', $result);
    }
}
