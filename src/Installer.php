<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use PackageVersions\Versions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

final class Installer
{
    const TITLE = 'PHP API Clients Middleware skeleton installer';

    public static function postCreateProject()
    {
        try
        {
            $app = new Application(
                self::TITLE,
                Versions::getVersion('api-clients/middleware-skeleton')
            );
            $app->add(new Install(Install::COMMAND));
            $app->find(Install::COMMAND)->run(new ArgvInput([]), new ConsoleOutput());
        }
        catch (Throwable $throwable)
        {
            echo get_class($throwable), ' thrown with message: ', $throwable->getMessage(), PHP_EOL;
            echo $throwable->getTraceAsString(), PHP_EOL;
            exit(1);
        }
    }
}