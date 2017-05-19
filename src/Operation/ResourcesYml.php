<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\OperationInterface;
use Composer\Factory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

final class ResourcesYml implements OperationInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @internal
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return OperationInterface
     */
    public static function create(): OperationInterface
    {
        return new self(new Filesystem());
    }

    /**
     * @param array        $replacements
     * @param array        $environment
     * @param SymfonyStyle $style
     */
    public function operate(array $replacements, array $environment, SymfonyStyle $style)
    {
        $path = str_replace(
            'composer.json',
            'resources.yml',
            Factory::getComposerFile()
        );
        $style->section('Updating resources.yml');
        $style->text('Reading resources.yml');
        $resourcesYml = Yaml::parse($this->filesystem->read($path));

        $style->text('Replacing api_settings');
        $resourcesYml['api_settings'] = $replacements['ns_vendor'] . '\\' . $replacements['ns_project'] . '\\ApiSettings';

        $style->text('Replacing src.namespace');
        $resourcesYml['src']['namespace'] = $replacements['ns_vendor'] . '\\' . $replacements['ns_project'] . '\\Resource';

        $style->text('Replacing tests.namespace');
        $resourcesYml['tests']['namespace'] = $replacements['ns_tests_vendor'] . '\\' . $replacements['ns_project'] . '\\Resource';

        $style->text('Writing updated resources.yml');
        $this->filesystem->write($path, Yaml::dump($resourcesYml));
        $style->success('Updated resources.yml');
    }
}
