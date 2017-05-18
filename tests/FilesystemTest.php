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

    public function testLs()
    {
        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('php-api-clients-installer-filesystem-ls-test-') . DIRECTORY_SEPARATOR;
        mkdir($tmp);
        mkdir($tmp . 'd' . DIRECTORY_SEPARATOR);

        $fileOne = $tmp . 'one';
        $fileTwo = $tmp . 'two';
        $fileDOne = $tmp . 'd' . DIRECTORY_SEPARATOR . 'done';
        touch($fileOne);
        touch($fileTwo);
        touch($fileDOne);

        $filesystem = new Filesystem();
        $files = $filesystem->ls($tmp);

        self::assertSame(
            [
                $fileOne,
                $fileTwo,
                $fileDOne,
            ],
            $files
        );
    }
}
