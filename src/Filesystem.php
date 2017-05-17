<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

class Filesystem
{
    /**
     * Read the file contents of the given file.
     *
     * @param  string $filename
     * @return string
     */
    public function read(string $filename): string
    {
        return file_get_contents($filename);
    }

    /**
     * Write the given contents to the given file.
     *
     * @param  string $filename
     * @param  string $contents
     * @return int
     */
    public function write(string $filename, string $contents): int
    {
        return (int)file_put_contents($filename, $contents);
    }
}
