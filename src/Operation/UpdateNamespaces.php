<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\OperationInterface;
use Composer\Factory;
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
     * @internal
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

    public function operate(array $replacements, array $environment, SymfonyStyle $style)
    {
        foreach ([
            'path_src' => ['ns_vendor', 'current_ns'],
            'path_tests' => ['ns_tests_vendor', 'current_ns_tests'],
        ] as $dirIndex => list($namespaceIndex, $currentNamespace)) {
            $namespace = $replacements[$namespaceIndex] . '\\' . $replacements['ns_project'];
            $path = str_replace(
                'composer.json',
                $replacements[$dirIndex],
                Factory::getComposerFile()
            );
            $path = rtrim($path, '/');
            $path .= '/';
            foreach ($this->filesystem->ls($path) as $fileName) {
                $style->text(' * Updating ' . $fileName);
                $this->updateNamespaces($fileName, $namespace, $environment[$currentNamespace] ?? '');
            }
        }

        $style->success('Namespaces updated');
    }

    private function updateNamespaces(string $fileName, string $namespace, string $currentNamespace)
    {
        $stmts = $this->parseFile($fileName);
        if ($stmts === null) {
            return;
        }
        foreach ($stmts as $index => $node) {
            if (!($node instanceof Node\Stmt\Namespace_)) {
                continue;
            }

            $suffix = str_replace($currentNamespace, '', (string)$node->name);

            $stmts[$index] = new Node\Stmt\Namespace_(
                new Node\Name(
                    $namespace . $suffix
                ),
                $node->stmts,
                $node->getAttributes()
            );

            break;
        }

        $this->filesystem->write($fileName, (new Standard())->prettyPrintFile($stmts) . PHP_EOL);
    }

    private function parseFile(string $fileName)
    {
        return (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse($this->filesystem->read($fileName));
    }
}
