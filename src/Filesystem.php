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

    /**
     * List all files in a directory and it's subdirectories.
     *
     * @param  string $path
     * @return array
     */
    public function ls(string $path): array
    {
        $files = [];
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if (in_array($entry, ['.', '..'], true)) {
                continue;
            }
            $entryPath = $path . $entry;
            if (is_dir($entryPath)) {
                foreach ($this->ls($entryPath . DIRECTORY_SEPARATOR) as $entryPath) {
                    $files[] = $entryPath;
                }
                continue;
            }
            if (!is_file($entryPath)) {
                continue;
            }
            $files[] = $entryPath;
        }
        $d->close();

        return $files;
    }
}
