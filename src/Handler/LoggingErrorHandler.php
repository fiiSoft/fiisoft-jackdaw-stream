<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler;

use FiiSoft\Jackdaw\Handler\Logger\ErrorLogger;

final class LoggingErrorHandler implements ErrorHandler
{
    private ErrorLogger $logger;
    private ?bool $action;
    
    public function __construct(ErrorLogger $logger, ?bool $action)
    {
        $this->logger = $logger;
        $this->action = $action;
    }
    
    /**
     * @inheritDoc
     */
    public function handle(\Throwable $error, $key, $value): ?bool
    {
        $this->logger->log($error, $value, $key);
        
        return $this->action;
    }
}