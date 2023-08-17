<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler\Logger;

final class Loggers
{
    /**
     * @param ErrorLogger|mixed $logger
     */
    public static function getAdapter($logger): ErrorLogger
    {
        if ($logger instanceof ErrorLogger) {
            return $logger;
        }
        
        if (\is_object($logger)) {
            if (\is_a($logger, '\Symfony\Component\Console\Logger\ConsoleLogger')) {
                return new SymfonyLoggerAdapter($logger);
            }
            
            if (\is_a($logger, '\Symfony\Component\Console\Output\OutputInterface')) {
                return new SymfonyOutputAdapter($logger);
            }
            
            if (\is_a($logger, '\Psr\Log\LoggerInterface')) {
                return new PsrLoggerAdapter($logger);
            }
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