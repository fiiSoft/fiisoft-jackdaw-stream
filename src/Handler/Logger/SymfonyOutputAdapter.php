<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyOutputAdapter implements ErrorLogger
{
    private OutputInterface $output;
    
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }
    
    public function log(\Throwable $error, $value, $key): void
    {
        $this->output->writeln('<error>'.LogFormatter::format($error, $value, $key).'</error>');
    }
}