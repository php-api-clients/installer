<?php declare(strict_types = 1);

namespace ApiClients\Tests\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\Operation\ResourcesYml;
use ApiClients\Tools\Installer\Operation\UpdateNamespaces;
use ApiClients\Tools\TestUtilities\TestCase;
use Composer\Factory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

final class UpdateNamespacesTest extends TestCase
{
    public function provideOperations()
    {
        yield [
            [
                'path_src'        => 'path_src',
                'path_tests'      => 'path_tests',
                'ns_vendor'       => 'ns_vendor',
                'ns_tests_vendor' => 'ns_tests_vendor',
                'ns_project'      => 'ns_project',
            ],
            [
                'current_ns' => 'Vendor\\Project',
                'current_ns_tests' => 'Vendor\\Tests\\Project',
            ],
            [
                '/path/to/foo.bar' => [
                    'input' => '<?php' . PHP_EOL . PHP_EOL . 'namespace Vendor\\Project;' . PHP_EOL . PHP_EOL . 'class fooBar {}' . PHP_EOL,
                    'output' => '<?php' . PHP_EOL . PHP_EOL . 'namespace ns_vendor\\ns_project;' . PHP_EOL . PHP_EOL . 'class fooBar' . PHP_EOL . '{' . PHP_EOL . '}' . PHP_EOL,
                ],
                '/path/to/sub/foo.bar' => [
                    'input' => '<?php' . PHP_EOL . PHP_EOL . 'namespace Vendor\\Project\\Sub;' . PHP_EOL . PHP_EOL . 'class fooBar {}' . PHP_EOL,
                    'output' => '<?php' . PHP_EOL . PHP_EOL . 'namespace ns_vendor\\ns_project\\Sub;' . PHP_EOL . PHP_EOL . 'class fooBar' . PHP_EOL . '{' . PHP_EOL . '}' . PHP_EOL,
                ],
            ],
            [
                '/path/to/foo.bar.test' => [
                    'input' => '<?php' . PHP_EOL . PHP_EOL . 'namespace Vendor\\Tests\\Project;' . PHP_EOL . PHP_EOL . 'class fooBarTest {}' . PHP_EOL,
                    'output' => '<?php' . PHP_EOL . PHP_EOL . 'namespace ns_tests_vendor\\ns_project;' . PHP_EOL . PHP_EOL . 'class fooBarTest' . PHP_EOL . '{' . PHP_EOL . '}' . PHP_EOL,
                ],
                '/path/to/sub/foo.bar.test' => [
                    'input' => '<?php' . PHP_EOL . PHP_EOL . 'namespace Vendor\\Tests\\Project\\Sub;' . PHP_EOL . PHP_EOL . 'class fooBarTest {}' . PHP_EOL,
                    'output' => '<?php' . PHP_EOL . PHP_EOL . 'namespace ns_tests_vendor\\ns_project\\Sub;' . PHP_EOL . PHP_EOL . 'class fooBarTest' . PHP_EOL . '{' . PHP_EOL . '}' . PHP_EOL,
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
    public function testOperate(array $replacements, array $env, array $lsSrcFiles, array $lsTestsFiles)
    {
        $filesystem = $this->prophesize(Filesystem::class);
        $filesystem->ls('path_src')->shouldBeCalled()->willReturn(array_keys($lsSrcFiles));
        foreach ($lsSrcFiles as $fileName => $io) {
            $filesystem->read($fileName)->shouldBeCalled()->willReturn($io['input']);
            $filesystem->write($fileName, $io['output'])->shouldBeCalled();
        }
        $filesystem->ls('path_tests')->shouldBeCalled()->willReturn(array_keys($lsTestsFiles));
        foreach ($lsTestsFiles as $fileName => $io) {
            $filesystem->read($fileName)->shouldBeCalled()->willReturn($io['input']);
            $filesystem->write($fileName, $io['output'])->shouldBeCalled();
        }

        $style = $this->prophesize(SymfonyStyle::class);

        $operation = new UpdateNamespaces($filesystem->reveal());
        $operation->operate($replacements, $env, $style->reveal());
    }
}
