<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Handlers
{
    /**
     * @param HandlerReady[] $handlers
     * @return Handler[]
     */
    public static function prepare(array $handlers): array
    {
        return \array_map(static fn($handler): Handler => self::getAdapter($handler), $handlers);
    }
    
    public static function getAdapter(HandlerReady $handler): Handler
    {
        if ($handler instanceof Reducer) {
            return new ReducerAdapter($handler);
        }
        
        if ($handler instanceof Consumer) {
            return new ConsumerAdapter($handler);
        }
        
        if ($handler instanceof Collector) {
            return new CollectorAdapter($handler);
        }
        
        if ($handler instanceof StreamPipe) {
            return new StreamPipeAdapter($handler);
        }
        
        throw new \InvalidArgumentException('Only StrimPipe is supported as Handler for Dispatcher');
    }
}