<?php declare(strict_types = 1);

namespace ApiClients\Tools\Installer;

use Symfony\Component\Console\Style\SymfonyStyle;

interface OperationInterface
{
    /**
     * Execute operation with the given $replacements and output any information via $style.
     *
     * @param array        $replacements
     * @param SymfonyStyle $style
     */
    public function operate(array $replacements, SymfonyStyle $style);
}
