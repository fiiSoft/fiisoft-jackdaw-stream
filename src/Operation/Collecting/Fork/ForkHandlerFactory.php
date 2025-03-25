<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork;

use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\CollectorFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\IdleForkHandler;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\ReducerFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\SequenceFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter\StreamFork;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class ForkHandlerFactory
{
    public static function adaptPrototype(ForkReady $prototype): ForkHandler
    {
        return self::createAdapter($prototype, false);
    }
    
    public static function adaptHandler(ForkReady $prototype): ForkHandler
    {
        return self::createAdapter($prototype, true);
    }
    
    private static function createAdapter(ForkReady $prototype, bool $isHandler): ForkHandler
    {
        if ($prototype instanceof StreamPipe) {
            return new StreamFork($prototype, null, $isHandler);
        }
        
        if ($prototype instanceof Reducer) {
            return new ReducerFork($prototype);
        }
        
        if ($prototype instanceof IterableCollector) {
            return CollectorFork::createAdapter($prototype);
        }
        
        if ($prototype instanceof SequenceMemo) {
            return new SequenceFork($prototype);
        }
        
        if ($prototype instanceof IdleForkHandler) {
            return $prototype;
        }
        
        throw StreamExceptionFactory::unsupportedTypeOfForkPrototype();
    }
}