<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer\Operation;

use ApiClients\Tools\Installer\Filesystem;
use ApiClients\Tools\Installer\OperationInterface;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
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
        $classes = [];
        foreach ([
            'path_src' => ['ns_vendor', 'current_ns'],
            'path_tests' => ['ns_tests_vendor', 'current_ns_tests'],
        ] as $dirIndex => list($namespaceIndex, $currentNamespace)) {
            $oldNamespace = $environment[$currentNamespace] ?? '';
            $newNamespace = $replacements[$namespaceIndex] . '\\' . $replacements['ns_project'];
            $path = str_replace(
                'composer.json',
                $replacements[$dirIndex],
                Factory::getComposerFile()
            );
            $path = rtrim($path, '/');
            $path .= '/';
            foreach ($this->filesystem->ls($path) as $fileName) {
                $fileContents = $this->filesystem->read($fileName);
                $reflector = new ClassReflector(new StringSourceLocator($fileContents));
                foreach ($reflector->getAllClasses() as $class) {
                    $className = $class->getName();
                    $classes[$className] = str_replace($oldNamespace, $newNamespace, $className);
                }
            }
        }

        foreach ([
            'path_src' => ['ns_vendor', 'current_ns'],
            'path_tests' => ['ns_tests_vendor', 'current_ns_tests'],
        ] as $dirIndex => list($namespaceIndex, $currentNamespace)) {
            $oldNamespace = $environment[$currentNamespace] ?? '';
            $newNamespace = $replacements[$namespaceIndex] . '\\' . $replacements['ns_project'];
            $path = str_replace(
                'composer.json',
                $replacements[$dirIndex],
                Factory::getComposerFile()
            );
            $path = rtrim($path, '/');
            $path .= '/';
            foreach ($this->filesystem->ls($path) as $fileName) {
                $style->text(' * Updating ' . $fileName);
                $stmts = $this->parseFile($fileName);
                $stmts = $this->updateNamespaces($stmts, $newNamespace, $oldNamespace, $classes);
                $this->filesystem->write($fileName, (new Standard())->prettyPrintFile($stmts) . PHP_EOL);
            }
        }

        $style->success('Namespaces updated');
    }

    private function updateNamespaces(array $stmts, string $namespace, string $currentNamespace, $classes)
    {
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
                $this->updateUses($node->stmts, $classes),
                $node->getAttributes()
            );

            break;
        }

        return $stmts;
    }

    private function updateUses(array $stmts, array $classes)
    {
        if ($stmts === null) {
            return;
        }

        foreach ($stmts as $index => $node) {
            if (!($node instanceof Node\Stmt\Use_)) {
                continue;
            }

            if ($node->type !== Node\Stmt\Use_::TYPE_NORMAL) {
                continue;
            }

            foreach ($node->uses as $useIndex => $useNode) {
                if (!($useNode instanceof Node\Stmt\UseUse)) {
                    continue;
                }

                if (!isset($classes[(string)$useNode->name])) {
                    continue;
                }

                $stmts[$index]->uses[$useIndex]->name = new Node\Name(
                    $classes[(string)$useNode->name]
                );
            }
        }

        return $stmts;
    }

    private function parseFile(string $fileName)
    {
        return (new ParserFactory())->create(ParserFactory::ONLY_PHP7)->parse($this->filesystem->read($fileName));
    }
}
