<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

use Symfony\Component\Console\Logger\ConsoleLogger;

final class SymfonyLoggerAdapter implements ErrorLogger
{
    private ConsoleLogger $logger;
    private string $level;
    
    public function __construct(ConsoleLogger $logger, string $level = ConsoleLogger::ERROR)
    {
        $this->logger = $logger;
        $this->level = $level;
    }
    
    public function log(\Throwable $error, $value, $key): void
    {
        $this->logger->log($this->level, LogFormatter::format($error, $value, $key));
    }
}