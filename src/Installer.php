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

    public static function postCreateProject(array $arguments)
    {
        try {
            if (!isset($arguments[1])) {
                $path = str_replace(
                    'composer.json',
                    'installer.yml',
                    Factory::getComposerFile()
                );

                if (file_exists($path)) {
                    $arguments[1] = $path;
                    unset($path);
                }
            }
            if (!isset($arguments[1])) {
                throw new InvalidArgumentException('Missing installer configuration file');
            }

            $yaml = Yaml::parse(
                file_get_contents(
                    $arguments[1]
                )
            );
            $app = new Application(
                self::TITLE,
                Versions::getVersion('api-clients/installer')
            );
            $app->add((new Install(Install::COMMAND))->setYaml($yaml));
            $app->find(Install::COMMAND)->run(new ArgvInput([]), new ConsoleOutput());
        } catch (Throwable $throwable) {
            echo get_class($throwable), ' thrown with message: ', $throwable->getMessage(), PHP_EOL;
            echo $throwable->getTraceAsString(), PHP_EOL;
            exit(1);
        }
    }
}
