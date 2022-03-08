<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class PsrLoggerAdapter implements ErrorLogger
{
    private LoggerInterface $logger;
    private string $level;
    
    public function __construct(LoggerInterface $logger, string $level = LogLevel::ERROR)
    {
        $this->logger = $logger;
        $this->level = $level;
    }
    
    public function log(\Throwable $error, $value, $key): void
    {
        $this->logger->log($this->level, LogFormatter::format($error, $value, $key));
    }
}