<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler;

use FiiSoft\Jackdaw\Handler\Logger\ErrorLogger;
use FiiSoft\Jackdaw\Handler\Logger\Loggers;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class OnError
{
    public static function skip(): ErrorHandler
    {
        return new class implements ErrorHandler {
            /** @inheritdoc */
            public function handle(\Throwable $error, $key, $value): ?bool {
                return true;
            }
        };
    }
    
    public static function abort(): ErrorHandler
    {
        return new class implements ErrorHandler {
            /** @inheritdoc */
            public function handle(\Throwable $error, $key, $value): ?bool {
                return false;
            }
        };
    }
    
    public static function call(callable $handler): ErrorHandler
    {
        return new GenericErrorHandler($handler);
    }
    
    /**
     * @param ErrorLogger|ConsoleLogger|OutputInterface|LoggerInterface $logger
     * @return ErrorHandler
     */
    public static function logAndSkip($logger): ErrorHandler
    {
        return new LoggingErrorHandler(Loggers::getAdapter($logger), true);
    }
    
    /**
     * @param ErrorLogger|ConsoleLogger|OutputInterface|LoggerInterface $logger
     * @return ErrorHandler
     */
    public static function logAndAbort($logger): ErrorHandler
    {
        return new LoggingErrorHandler(Loggers::getAdapter($logger), false);
    }
    
    /**
     * @param ErrorLogger|ConsoleLogger|OutputInterface|LoggerInterface $logger
     * @return ErrorHandler
     */
    public static function log($logger): ErrorHandler
    {
        return new LoggingErrorHandler(Loggers::getAdapter($logger), null);
    }
}