<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Handler;

use FiiSoft\Jackdaw\Internal\Helper;

final class GenericErrorHandler implements ErrorHandler
{
    /** @var callable */
    private $handler;
    
    private int $numOfArgs;
    
    /**
     * @param callable $handler this callable MUST return true, false or null, see explanation in ErrorHandler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
        $this->numOfArgs = Helper::getNumOfArgs($handler);
    }
    
    /**
     * @inheritDoc
     */
    public function handle(\Throwable $error, $key, $value): ?bool
    {
        $handler = $this->handler;
    
        switch ($this->numOfArgs) {
            case 0: return $handler();
            case 1: return $handler($error);
            case 2: return $handler($error, $key);
            case 3: return $handler($error, $key, $value);
            default:
                throw Helper::wrongNumOfArgsException('ErrorHandler', $this->numOfArgs, 0, 1, 2, 3);
        }
    }
}