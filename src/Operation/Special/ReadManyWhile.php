<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Consumer\Consumers;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;

final class ReadManyWhile extends SwapHead
{
    private Filter $filter;
    private Consumer $consumer;
    
    private int $index = -1;
    
    private bool $reindex;
    private bool $until;
    private bool $isFirstTime = true;
    
    /**
     * @param Filter|callable|mixed $filter
     * @param ConsumerReady|callable|resource|null $consumer resource must be writeable
     */
    public function __construct(
        $filter,
        ?int $mode = null,
        bool $reindex = false,
        bool $until = false,
        $consumer = null
    ){
        $this->filter = Filters::getAdapter($filter, $mode);
        $this->consumer = $consumer !== null ? Consumers::getAdapter($consumer) : Consumers::idle();
        
        $this->reindex = $reindex;
        $this->until = $until;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->isFirstTime) {
            $this->isFirstTime = false;
            $signal->swapHead($this);
        } elseif ($this->until XOR $this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            if ($this->reindex) {
                $signal->item->key = ++$this->index;
            }
            
            $this->next->handle($signal);
        } else {
            $this->index = -1;
            $this->isFirstTime = true;
            
            $this->consumer->consume($signal->item->value, $signal->item->key);
            
            $signal->restoreHead();
            $signal->setNextItem($signal->item);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (!$this->isFirstTime) {
            $this->consumer->consume($signal->item->value, $signal->item->key);
        }
        
        return parent::streamingFinished($signal);
    }
    
    public function filter(): Filter
    {
        return $this->filter;
    }
    
    public function isWhile(): bool
    {
        return !$this->until;
    }
    
    public function preserveKeys(): bool
    {
        return !$this->reindex;
    }
}