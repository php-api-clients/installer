<?php declare(strict_types = 1);

namespace ApiClients\Tests\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\Operation\ResourcesYml;
use ApiClients\Tools\TestUtilities\TestCase;
use Composer\Factory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

final class ResourcesYmlTest extends TestCase
{
    public function provideOperations()
    {
        yield [
            [
                'package_name'    => 'package_name',
                'author_name'     => 'author_name',
                'author_email'    => 'author_email',
                'ns_vendor'       => 'ns_vendor',
                'ns_tests_vendor' => 'ns_tests_vendor',
                'ns_project'      => 'ns_project',
            ],
            [
                'yaml_location' => 'yaml',
                'api_settings'  => 'ApiClients\\Client\\Skeleton\\ApiSettings',
                'src' => [
                    'path'      => 'src/Resource',
                    'namespace' => 'ApiClients\\Client\\Skeleton\\Resource',
                ],
                'tests' => [
                    'path'      => 'tests/Resource',
                    'namespace' => 'ApiClients\\Tests\\Client\\Skeleton\\Resource',
                ],
            ],
            [
                'yaml_location' => 'yaml',
                'api_settings'  => 'ns_vendor\\ns_project\\ApiSettings',
                'src' => [
                    'path'      => 'src/Resource',
                    'namespace' => 'ns_vendor\\ns_project\\Resource',
                ],
                'tests' => [
                    'path'      => 'tests/Resource',
                    'namespace' => 'ns_tests_vendor\\ns_project\\Resource',
                ],
            ],
        ];
    }

    /**
     * @param array $replacements
     * @param array $inputJson
     * @param array $outputJson
     *
     * @dataProvider provideOperations
     */
    public function testOperate(array $replacements, array $inputJson, array $outputJson)
    {
        $path = str_replace(
            'composer.json',
            'resources.yml',
            Factory::getComposerFile()
        );
        $style = $this->prophesize(SymfonyStyle::class);
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->read($path)->shouldBeCalled()->willReturn(Yaml::dump($inputJson));
        $filesystem->write($path, Yaml::dump($outputJson))->shouldBeCalled();

        $operation = new ResourcesYml($filesystem->reveal());

        $operation->operate($replacements, $style->reveal());
    }
}
