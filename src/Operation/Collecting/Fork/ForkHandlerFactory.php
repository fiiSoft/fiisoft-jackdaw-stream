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
    public static function getAdapter(ForkReady $prototype): ForkHandler
    {
        if ($prototype instanceof StreamPipe) {
            return new StreamFork($prototype);
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