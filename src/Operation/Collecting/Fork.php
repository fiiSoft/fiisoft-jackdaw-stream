<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Collector\IterableCollector;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Exception\StreamExceptionFactory;
use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\CollectorFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\ReducerFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\SequenceMemoFork;
use FiiSoft\Jackdaw\Operation\Collecting\Fork\StreamFork;
use FiiSoft\Jackdaw\Operation\Internal\ProcessOperation;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Reducer\Reducer;

abstract class Fork extends ProcessOperation
{
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public static function create($discriminator, ForkReady $prototype): Fork
    {
        $discriminator = Discriminators::getAdapter($discriminator);
        
        if ($prototype instanceof ForkCollaborator) {
            return new StreamFork($discriminator, $prototype);
        }
        
        if ($prototype instanceof Reducer) {
            return new ReducerFork($discriminator, $prototype);
        }
        
        if ($prototype instanceof IterableCollector) {
            return new CollectorFork($discriminator, $prototype);
        }
        
        if ($prototype instanceof SequenceMemo) {
            return new SequenceMemoFork($discriminator, $prototype);
        }
        
        throw StreamExceptionFactory::unsupportedTypeOfForkPrototype();
    }
    
    protected function __construct()
    {
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        $signal->restartWith(Producers::getAdapter($this->extractData()), $this->next);
        
        $this->cleanUp();
        
        return true;
    }
    
    /**
     * @return array<string|int, mixed>
     */
    abstract protected function extractData(): array;
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            
            $this->cleanUp();
            
            parent::destroy();
        }
    }
    
    abstract protected function cleanUp(): void;
}