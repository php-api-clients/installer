<?php declare(strict_types = 1);

namespace ApiClients\Tests\Tools\Installer;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\TestUtilities\TestCase;

final class FilesystemTest extends TestCase
{
    public function testRead()
    {
        $filesystem = new Filesystem();
        self::assertSame(file_get_contents(__FILE__), $filesystem->read(__FILE__));
    }

    public function testWrite()
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('php-api-clients-installer-filesystem-write-test-');
        touch($tmp);

        $filesystem = new Filesystem();
        $filesystem->write($tmp, file_get_contents(__FILE__));

        self::assertSame(file_get_contents(__FILE__), file_get_contents($tmp));
    }
}
