<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use Composer\Composer;
use Composer\Factory;
use InvalidArgumentException;
use PackageVersions\Versions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class Installer
{
    const TITLE = 'PHP API Clients skeleton installer';

    public static function postCreateProject()
    {
        require_once str_replace(
            'composer.json',
            'vendor/autoload.php',
            Factory::getComposerFile()
        );

        try {
            $path = str_replace(
                'composer.json',
                'installer.yml',
                Factory::getComposerFile()
            );

            if (!file_exists($path)) {
                throw new InvalidArgumentException('Missing installer configuration file');
            }

            static::install($path);
        } catch (Throwable $throwable) {
            echo get_class($throwable), ' thrown with message: ', $throwable->getMessage(), PHP_EOL;
            echo $throwable->getTraceAsString(), PHP_EOL;
            exit(1);
        }
    }

    private static function install(string $fileName)
    {
        $yaml = Yaml::parse(
            file_get_contents(
                $fileName
            )
        );
        $app = new Application(
            self::TITLE,
            Versions::getVersion('api-clients/installer')
        );
        $app->add((new Install(Install::COMMAND))->setYaml($yaml));
        $app->find(Install::COMMAND)->run(new ArgvInput([]), new ConsoleOutput());
    }
}
