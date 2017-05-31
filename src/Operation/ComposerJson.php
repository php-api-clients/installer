<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer\Operation;

use ApiClients\Tools\Installer\OperationInterface;
use Composer\Factory;
use Composer\Json\JsonFile;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ComposerJson implements OperationInterface
{
    /**
     * @var JsonFile
     */
    private $jsonFile;

    /**
     * @internal
     * @param JsonFile $jsonFile
     */
    public function __construct(JsonFile $jsonFile)
    {
        $this->jsonFile = $jsonFile;
    }

    /**
     * @return OperationInterface
     */
    public static function create(): OperationInterface
    {
        return new self(new JsonFile(Factory::getComposerFile()));
    }

    /**
     * @param array        $replacements
     * @param array        $environment
     * @param SymfonyStyle $style
     */
    public function operate(array $replacements, array $environment, SymfonyStyle $style)
    {
        $style->section('Updating composer.json');
        $style->text('Reading composer.json');
        $composerJson = $this->jsonFile->read();

        $style->text('Replacing package name');
        $composerJson['name'] = $replacements['package_name'];

        $style->text('Adding authors');
        $composerJson['authors'] = [
            [
                'name'  => $replacements['author_name'],
                'email' => $replacements['author_email'],
            ],
        ];

        $style->text('Updating autoload');
        $composerJson['autoload']['psr-4'] = [
            $replacements['ns_vendor'] . '\\' . $replacements['ns_project'] . '\\' => 'src/',
        ];
        $composerJson['autoload-dev']['psr-4'] = [
            $replacements['ns_tests_vendor'] . '\\' . $replacements['ns_project'] . '\\' => 'tests/',
        ];

        $style->text('Removing package needed for installation and post create script');

        foreach (['require', 'require-dev', 'scripts'] as $index) {
            if (!isset($environment[$index])) {
                continue;
            }

            $itemCount = count($environment[$index]);
            $style->text('Removing ' . $itemCount . ' item' . ($itemCount > 1 ? 's' : '') . ' from ' . $index);
            $composerJson = $this->removeItemFromIndex($composerJson, $environment, $index);
        }

        $style->text('Writing updated composer.json');
        $this->jsonFile->write($composerJson);
        $style->success('Updated composer.json');
    }

    private function removeItemFromIndex(array $composerJson, array $environment, string $index): array
    {
        foreach ($environment[$index] as $package) {
            if (!isset($composerJson[$index][$package])) {
                continue;
            }

            unset($composerJson[$index][$package]);
        }

        return $composerJson;
    }
}
