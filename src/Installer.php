<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use Closure;
use Composer\Factory;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use InvalidArgumentException;
use Jean85\PrettyVersions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class Installer
{
    const TITLE = 'PHP API Clients skeleton installer';

    public static function postCreateProject(Event $composerEvent)
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

            static::install($path, $composerEvent->getIO());
        } catch (Throwable $throwable) {
            echo get_class($throwable), ' thrown with message: ', $throwable->getMessage(), PHP_EOL;
            echo $throwable->getTraceAsString(), PHP_EOL;
            exit(1);
        }
    }

    private static function install(string $fileName, IOInterface $io)
    {
        $yaml = Yaml::parse(
            file_get_contents(
                $fileName
            )
        );
        $app = new Application(
            self::TITLE,
            PrettyVersions::getVersion('api-clients/installer')->getShortVersion()
        );
        $app->add((new Install(Install::COMMAND))->setYaml($yaml));
        $app->find(Install::COMMAND)->run(new ArgvInput([]), self::getOutput($io));
    }

    private static function getOutput(IOInterface $io): OutputInterface
    {
        if ($io instanceof ConsoleIO) {
            return Closure::bind(function (ConsoleIO $consoleIO): OutputInterface {
                return $consoleIO->output;
            }, null, ConsoleIO::class)($io);
        }

        return new class($io) extends Output {
            /**
             * @var IOInterface
             */
            private $io;

            /**
             *  constructor.
             * @param IOInterface $io
             */
            public function __construct(IOInterface $io)
            {
                parent::__construct();
                $this->io = $io;
            }

            protected function doWrite($message, $newline)
            {
                $this->io->write($message, $newline);
            }
        };
    }
}
