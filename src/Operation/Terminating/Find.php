<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class Find extends SimpleFinalOperation
{
    private Filter $filter;
    private int $mode;
    
    private ?Item $item = null;
    
    /**
     * @param Filter|callable|mixed $predicate
     */
    public function __construct(Stream $stream, $predicate, int $mode = Check::VALUE)
    {
        $this->filter = Filters::getAdapter($predicate);
        $this->mode = Check::getMode($mode);
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key, $this->mode)) {
            $this->item = $signal->item->copy();
            $signal->stop();
        }
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
    
    protected function __clone()
    {
        parent::__clone();
    
        $this->item = null;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            if ($this->filter->isAllowed($item->value, $item->key, $this->mode)) {
                $this->item = $item->copy();
                $signal->stop();
                break;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            if ($this->filter->isAllowed($item->value, $item->key, $this->mode)) {
                $this->item = $item->copy();
                $signal->stop();
                break;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            if ($this->filter->isAllowed($item->value, $item->key, $this->mode)) {
                $this->item = $item->copy();
                $signal->stop();
                break;
            }
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
}