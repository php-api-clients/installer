<?php

namespace ApiClients\Tests\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Operation\ComposerJson;
use ApiClients\Tools\TestUtilities\TestCase;
use Composer\Json\JsonFile;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ComposerJsonTest extends TestCase
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
                'require' => [
                    'api-clients/installer' => '123',
                ],
                'scripts' => [
                    'post-create-project-cmd' => ['a', 'b', 'c'],
                ],
            ],
            [
                'require' => [],
                'scripts' => [],
                'name' => 'package_name',
                'authors' => [
                    [
                        'name' => 'author_name',
                        'email' => 'author_email',
                    ],
                ],
                'autoload' => [
                    'psr-4' => [
                        'ns_vendor\\ns_project\\' => 'src/',
                    ],
                ],
                'autoload-dev' => [
                    'psr-4' => [
                        'ns_tests_vendor\\ns_project\\' => 'tests/',
                    ],
                ],
            ],
        ];

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
                'name' => 'vendor/name',
                'authors' => [],
                'require' => [
                    'php' => '^7.0',
                    'api-clients/installer' => '123',
                ],
                'scripts' => [
                    'cs' => 'cs-fixer',
                    'post-create-project-cmd' => ['a', 'b', 'c'],
                ],
            ],
            [
                'name' => 'package_name',
                'authors' => [
                    [
                        'name' => 'author_name',
                        'email' => 'author_email',
                    ],
                ],
                'require' => [
                    'php' => '^7.0',
                ],
                'autoload' => [
                    'psr-4' => [
                        'ns_vendor\\ns_project\\' => 'src/',
                    ],
                ],
                'autoload-dev' => [
                    'psr-4' => [
                        'ns_tests_vendor\\ns_project\\' => 'tests/',
                    ],
                ],
                'scripts' => [
                    'cs' => 'cs-fixer',
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
        $style = $this->prophesize(SymfonyStyle::class);
        $jsonFile = $this->prophesize(JsonFile::class);
        $jsonFile->read()->shouldBeCalled()->willReturn($inputJson);
        $jsonFile->write($outputJson)->shouldBeCalled();

        $operation = new ComposerJson($jsonFile->reveal());

        $operation->operate($replacements, $style->reveal());
    }
}
