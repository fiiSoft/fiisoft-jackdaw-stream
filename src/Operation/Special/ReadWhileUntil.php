<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Operation;

abstract class ReadWhileUntil extends SwapHead
{
    protected Filter $filter;
    protected Consumer $consumer;
    
    protected int $index = -1;
    
    protected bool $reindex;
    protected bool $isFirstTime = true;
    
    /**
     * @param FilterReady|callable|array<string|int, mixed>|scalar $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    final public function __construct(
        $filter,
        ?int $mode = null,
        bool $reindex = false,
        $consumer = null
    ){
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->consumer = $consumer !== null ? Consumers::getAdapter($consumer) : Consumers::idle();
        
        $this->reindex = $reindex;
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        if (!$this->isFirstTime) {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        }
        
        return parent::streamingFinished($signal);
    }
    
    final public function preserveKeys(): bool
    {
        return !$this->reindex;
    }
    
    abstract public function createFilterOperation(): Operation;
}