<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

abstract class ReduceFinalOperation extends FinalOperation
{
    private Reducer $reducer;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, Reducer $reducer, $orElse = null)
    {
        parent::__construct($stream, $orElse);
        
        $this->reducer = $reducer;
    }
    
    final public function handle(Signal $signal): void
    {
        $this->reducer->consume($signal->item->value);
    }
    
    protected function __clone()
    {
        $this->reducer = clone $this->reducer;
        
        parent::__clone();
    }
    
    final public function hasResult(): bool
    {
        return $this->reducer->hasResult();
    }
    
    final public function getResult(): Item
    {
        return $this->reducer->getResult();
    }
    
    final public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $this->reducer->consume($item->value);
        }
        
        return $this->streamingFinished($signal);
    }
    
    final public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        foreach ($data as $key => $value) {
            $this->reducer->consume($value);
        }
        
        if (isset($key, $value)) {
            $signal->item->key = $key;
            $signal->item->value = $value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    final public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $this->reducer->consume($item->value);
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->reducer->reset();
            
            parent::destroy();
        }
    }
}