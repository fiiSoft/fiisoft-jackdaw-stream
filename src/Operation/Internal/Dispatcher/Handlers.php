<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal\Dispatcher;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Internal\LastOperation;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

final class Handlers
{
    /**
     * @param Stream|LastOperation|ResultApi|Collector|Consumer|Reducer $handler
     */
    public static function getAdapter($handler): Handler
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
        
        if ($handler instanceof Stream || $handler instanceof ResultApi) {
            if ($handler instanceof StreamPipe) {
                return new StreamPipeAdapter($handler);
            }
            
            throw new \InvalidArgumentException('Only StrimPipe is supported as Handler for Dispatcher');
        }
        
        throw Helper::invalidParamException('handler', $handler);
    }
    
    /**
     * @param Stream[]|LastOperation[]|ResultApi[]|Collector[]|Consumer[]|Reducer[] $handlers
     * @return Handler[]
     */
    public static function prepare(array $handlers): array
    {
        return \array_map(static fn($handler): Handler => self::getAdapter($handler), $handlers);
    }
}