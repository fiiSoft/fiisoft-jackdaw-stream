<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class Loggers
{
    public static function getAdapter($logger): ErrorLogger
    {
        if ($logger instanceof ErrorLogger) {
            return $logger;
        }
    
        if ($logger instanceof ConsoleLogger) {
            return new SymfonyLoggerAdapter($logger);
        }
    
        if ($logger instanceof OutputInterface) {
            return new SymfonyOutputAdapter($logger);
        }
    
        if ($logger instanceof LoggerInterface) {
            return new PsrLoggerAdapter($logger);
        }
        
        throw new \InvalidArgumentException('Invalid param logger');
    }
    
    public static function simple(): ErrorLogger
    {
        return new class implements ErrorLogger {
            /** @inheritdoc  */
            public function log(\Throwable $error, $value, $key): void {
                echo '[ERROR] ', LogFormatter::format($error, $value, $key), \PHP_EOL;
            }
        };
    }
}