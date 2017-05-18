<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\OperationInterface;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UpdateNamespaces implements OperationInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * UpdateNamespaces constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public static function create(): OperationInterface
    {
        return new self(new Filesystem());
    }

    public function operate(array $replacements, SymfonyStyle $style)
    {
        $r = new \ReflectionClass('Composer\\Autoload\\ClassMapGenerator');
        $find = $r->getMethod('findClasses');
        $find->setAccessible(true);

        $classes = [];
        $style->section('Updating namespaces in PHP files');
        foreach ([
            'path_src',
            'path_tests',
        ] as $dirIndex) {
            $classes[$dirIndex] = [];
            $dir = $replacements[$dirIndex];
            $path = '/home/wyrihaximus/Projects/installer/';
            $style->write(' Scanning ' . $dir . ' for PHP files ');
            foreach ($this->iterateDirectory(
                $path . $dir . DIRECTORY_SEPARATOR,
                $style
            ) as $fileName) {
                $classes[$dirIndex][$fileName] = $find->invoke(null, $fileName);
            }
            $style->write(' found ' . count($classes[$dirIndex]) . ' class');
            $style->writeln(count($classes[$dirIndex]) === 1 ? '' : 'es');
        }
        //var_export($classes);
        foreach ([
            'path_src' => 'ns_vendor',
            'path_tests' => 'ns_tests_vendor',
        ] as $dirIndex => $namespaceIndex) {
            $namespace = $replacements[$namespaceIndex] . '\\' . $replacements['ns_project'];
            foreach ($classes[$dirIndex] as $fileName => $fileClasses) {
                $style->text(' * Updating ' . $fileName);
                $this->updateNamespaces($fileName, $namespace);
            }
        }

        /*$path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
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
        }*/
        $style->success('Namespaces updated');
    }

    private function iterateDirectory(string $path, SymfonyStyle $style/*, string $namespace, string $namespaceSrc*/): array
    {
        $files = [];
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            $entryPath = $path . $entry;
            if (!is_file($entryPath)) {
                continue;
            }
            $style->write('.');
            $files[] = $entryPath;
        }
        $d->close();

        return $files;
    }

    private function updateNamespaces(string $fileName, string $namespace)
    {
        $md5 = md5_file($fileName);
        $stmts = $this->parseFile($fileName);
        if ($stmts === null) {
            return;
        }
        foreach ($stmts as $index => $node) {
            if (!($node instanceof Node\Stmt\Namespace_)) {
                continue;
            }
            $stmts[$index] = new Node\Stmt\Namespace_(
                new Node\Name(
                    $namespace
                ),
                [],//$node->stmts,
                $node->getAttributes()
            );

            //var_export($stmts[$index]);
            //die();

            break;
        }

        $this->filesystem->write($fileName, (new Standard())->prettyPrintFile($stmts) . PHP_EOL);
        while ($md5 === md5($this->filesystem->read($fileName))) {
            usleep(500);
        }
    }

    private function parseFile(string $fileName)
    {
        return (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse($this->filesystem->read($fileName));
    }
}
