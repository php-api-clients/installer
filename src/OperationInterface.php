<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use PhpParser\Node\Name;
use PhpParser\Parser;
use Symfony\Component\Console\Style\SymfonyStyle;

interface OperationInterface
{
    /**
     * Execute operation with the given $replacements and output any information via $style.
     *
     * @param array $replacements
     * @param SymfonyStyle $style
     * @return void
     */
    public function operate(array $replacements, SymfonyStyle $style);
}
