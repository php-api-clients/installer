<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use Composer\Factory;
use Composer\Json\JsonFile;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\CS\AbstractFixer;
use Symfony\CS\Config\Config;
use Symfony\CS\ConfigAwareInterface;
use Symfony\CS\ConfigInterface;
use Symfony\CS\Fixer;
use Symfony\CS\FixerInterface;

final class Install extends Command
{
    const COMMAND = 'install';

    const NS_VENDOR       = '__NS_VENDOR__';
    const NS_TESTS_VENDOR = '__NS_TESTS_VENDOR__';
    const NS_PROJECT      = '__NS_PROJECT__';
    const PACKAGE_NAME    = '__PACKAGE_NAME__';
    const AUTHOR          = '__AUTHOR__';
    const AUTHOR_NAME     = '__AUTHOR_NAME__';
    const AUTHOR_EMAIL    = '__AUTHOR_EMAIL__';

    /**
     * @var AbstractFixer[]
     */
    private $fixers;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        retry:
        $style = new SymfonyStyle( $input, $output );

        $style->title('Welcome to the IceHawk installer.');
        $style->section('Please answer the following questions.');

        $replacements = [];
        $replacements[self::NS_VENDOR]       = $style->ask('What is your vendor namespace?', 'MyVendor');
        $replacements[self::NS_TESTS_VENDOR] = $style->ask('What is your vendor test namespace?', 'MyVendor\\Tests');
        $replacements[self::NS_PROJECT]      = $style->ask('What is your project namespace?', 'MyProject');
        $replacements[self::PACKAGE_NAME]    = $style->ask(
            'What is your package name?',
            strtolower($replacements[self::NS_VENDOR]) . '/' . strtolower($replacements[self::NS_PROJECT])
        );
        $replacements[self::AUTHOR_NAME]  = $style->ask('What is your name?');
        $replacements[self::AUTHOR_EMAIL] = $style->ask('What is your email address?');

        while ( false === filter_var( $replacements[self::AUTHOR_EMAIL], FILTER_VALIDATE_EMAIL ) )
        {
            $replacements[self::AUTHOR_EMAIL] = $style->ask('Invalid email address, try again.');
        }

        $replacements[self::AUTHOR] = "{$replacements[self::AUTHOR_NAME]} <{$replacements[self::AUTHOR_EMAIL]}>";

        $style->section('Summary:');

        $style->table(
            [],
            [
                ['Your namespace', $replacements[self::NS_VENDOR] . '\\' . $replacements[self::NS_PROJECT]],
                ['Your test namespace', $replacements[self::NS_TESTS_VENDOR] . '\\' . $replacements[self::NS_PROJECT]],
                ['Your package', $replacements[self::PACKAGE_NAME]],
                ['Author name', $replacements[self::AUTHOR_NAME]],
                ['Author email', $replacements[self::AUTHOR_EMAIL]],
            ]
        );

        $installNow = $style->choice(
            'All settings correct?',
            ['Yes', 'Change settings', 'Cancel installation'],
            'Yes'
        );

        switch ( $installNow )
        {
            case 'Yes':
            {
                $style->text('Creating your middleware package now.');
                $this->updateComposerJson($replacements, $style);
                $this->updatePHPFiles($replacements, $style);
                $this->removingOurSelfs($style);
                $style->section('Your middleware package creation has been successfully.');

                break;
            }
            case 'Change settings':
            {
                goto retry;
                break;
            }
            case 'Cancel installation':
            {
                $style->error( 'Installation canceled.' );

                return 9;
            }
        }

        return 0;
    }

    private function updateComposerJson(array $replacements, SymfonyStyle $style)
    {
        $style->section('Updating composer.json');
        $json = new JsonFile(Factory::getComposerFile());
        $style->text('Reading composer.json');
        $composerJson = $json->read();

        $style->text('Replacing package name');
        $composerJson['name'] = $replacements[self::PACKAGE_NAME];

        $style->text('Adding authors');
        $composerJson['authors'] = [
            [
                'name'  => $replacements[self::AUTHOR_NAME],
                'email' => $replacements[self::AUTHOR_EMAIL],
            ],
        ];

        $style->text('Updating autoload');
        $composerJson['autoload']['psr-4'][$replacements[self::NS_VENDOR] . '\\' . $replacements[self::NS_PROJECT] . '\\'] = 'src/';
        $composerJson['autoload-dev']['psr-4'][$replacements[self::NS_TESTS_VENDOR] . '\\' . $replacements[self::NS_PROJECT] . '\\'] = 'tests/';

        $style->text('Removing package needed for installation and post create script');
        unset(
            $composerJson['autoload']['psr-4']['ApiClients\\Middleware\\Installer\\'],
            $composerJson['autoload']['psr-4']['ApiClients\\Middleware\\Skeleton\\'],
            $composerJson['autoload-dev']['psr-4']['ApiClients\\Tests\\Middleware\\Skeleton\\'],
            $composerJson['require']['composer/composer'],
            $composerJson['require']['friendsofphp/php-cs-fixer'],
            $composerJson['require']['nikic/php-parser'],
            $composerJson['require']['ocramius/package-versions'],
            $composerJson['require']['symfony/console'],
            $composerJson['scripts']['post-create-project-cmd']
        );

        $style->text('Writing updated composer.json');
        $json->write($composerJson);
        $style->success('Updated composer.json');
    }

    private function updatePHPFiles(array $replacements, SymfonyStyle $style)
    {
        $style->section('Updating namespaces in PHP files');
        $this->setUpFixers();
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        foreach([
            'src' => self::NS_VENDOR,
            'tests' => self::NS_TESTS_VENDOR,
        ] as $dir => $namespace) {
            $this->iterateDirectory(
                $path . $dir . DIRECTORY_SEPARATOR,
                $style,
                $replacements[$namespace] . '\\' . $replacements[self::NS_PROJECT],
                $replacements[self::NS_VENDOR] . '\\' . $replacements[self::NS_PROJECT]
            );
        }
        $style->success('Namespaces updated');
    }

    private function iterateDirectory(string $path, SymfonyStyle $style, string $namespace, string $namespaceSrc)
    {
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            $entryPath = $path . $entry;
            if (!is_file($entryPath)) {
                continue;
            }

            $style->text('Updating ' . $entry . ' namespace');
            $this->updateNamespaces($entryPath, $namespace, $namespaceSrc);
        }
        $d->close();
    }

    private function updateNamespaces(string $fileName, string $namespace, string $namespaceSrc)
    {
        $md5 = md5_file($fileName);
        $stmts = (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse(file_get_contents($fileName));
        if ($stmts === null) {
            return;
        }
        foreach ($stmts as $index => $node) {
            if (!($node instanceof Namespace_)) {
                continue;
            }

            $nodeStmts = $node->stmts;

            foreach ($nodeStmts as $nodeIndex => $nodeNode) {
                if (!($nodeNode instanceof Use_)) {
                    continue;
                }

                foreach ($nodeNode->uses as $useUse) {
                    $nameString = $useUse->name->toString();
                    if (strpos($nameString, 'ApiClients\\Middleware\\Skeleton') !== 0) {
                        continue;
                    }

                    $useUse->name = new Name(
                        $namespaceSrc . substr($nameString, strlen('ApiClients\\Middleware\\Skeleton'))
                    );
                }
            }

            $stmts[$index] = new Namespace_(
                new Name(
                    $namespace
                ),
                $nodeStmts,
                $node->getAttributes()
            );
            break;
        }
        file_put_contents($fileName, (new Standard())->prettyPrintFile($stmts) . PHP_EOL);
        while($md5 === md5_file($fileName)) {
            usleep(500);
        }
        $this->applyPsr2($fileName);
    }


    /**
     * @param string $fileName
     */
    protected function applyPsr2(string $fileName)
    {
        $file = new \SplFileInfo($fileName);
        $new = file_get_contents($file->getRealPath());

        foreach ($this->fixers as $fixer) {
            if (!$fixer->supports($file)) {
                continue;
            }

            $new = $fixer->fix($file, $new);
        }

        file_put_contents(
            $fileName,
            str_replace(
                '<?php',
                '<?php declare(strict_types=1);',
                $new
            )
        );
    }

    protected function setUpFixers()
    {
        $fixer = new Fixer();
        $fixer->registerCustomFixers([
            new Fixer\Symfony\ExtraEmptyLinesFixer(),
            new Fixer\Symfony\SingleBlankLineBeforeNamespaceFixer(),
            new Fixer\PSR0\Psr0Fixer(),
            new Fixer\PSR1\EncodingFixer(),
            new Fixer\PSR1\ShortTagFixer(),
            new Fixer\PSR2\BracesFixer(),
            new Fixer\PSR2\ElseifFixer(),
            new Fixer\PSR2\EofEndingFixer(),
            new Fixer\PSR2\FunctionCallSpaceFixer(),
            new Fixer\PSR2\FunctionDeclarationFixer(),
            new Fixer\PSR2\IndentationFixer(),
            new Fixer\PSR2\LineAfterNamespaceFixer(),
            new Fixer\PSR2\LinefeedFixer(),
            new Fixer\PSR2\LowercaseConstantsFixer(),
            new Fixer\PSR2\LowercaseKeywordsFixer(),
            new Fixer\PSR2\MethodArgumentSpaceFixer(),
            new Fixer\PSR2\MultipleUseFixer(),
            new Fixer\PSR2\ParenthesisFixer(),
            new Fixer\PSR2\PhpClosingTagFixer(),
            new Fixer\PSR2\SingleLineAfterImportsFixer(),
            new Fixer\PSR2\TrailingSpacesFixer(),
            new Fixer\PSR2\VisibilityFixer(),
            new Fixer\Contrib\NewlineAfterOpenTagFixer(),
        ]);
        $config = Config::create()->fixers($fixer->getFixers());
        $fixer->addConfig($config);
        $this->fixers = $this->prepareFixers($config);
    }

    /**
     * @param ConfigInterface $config
     *
     * @return FixerInterface[]
     */
    private function prepareFixers(ConfigInterface $config): array
    {
        $fixers = $config->getFixers();

        foreach ($fixers as $fixer) {
            if ($fixer instanceof ConfigAwareInterface) {
                $fixer->setConfig($config);
            }
        }

        return $fixers;
    }

    private function removingOurSelfs(SymfonyStyle $style)
    {
        $style->section('Removing installer');
        unlink(__DIR__ . DIRECTORY_SEPARATOR . 'Install.php');
        $style->text('Removed Install.php');
        unlink(__DIR__ . DIRECTORY_SEPARATOR . 'Installer.php');
        $style->text('Removed Installer.php');
        rmdir(__DIR__);
        $style->text('Removed installer directory');
        $style->success('Installer removed');
    }
}