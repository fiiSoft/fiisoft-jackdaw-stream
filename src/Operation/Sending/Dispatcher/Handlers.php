<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending\Dispatcher;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\CollectorAdapter;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\ConsumerAdapter;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\MemoWriterAdapter;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Adapter\StreamPipeAdapter;
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
            return CollectorAdapter::create($handler);
        }
        
        //it covers Result, LastOperation, and Stream
        if ($handler instanceof StreamPipe) {
            return new StreamPipeAdapter($handler);
        }
        
        if ($handler instanceof MemoWriter) {
            return new MemoWriterAdapter($handler);
        }
        
        throw InvalidParamException::describe('handler', $handler);
    }
}