<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Internal\SimpleFinalOperation;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

final class Collect extends SimpleFinalOperation implements Reindexable
{
    private array $collected = [];
    
    private bool $reindex;
    
    public function __construct(Stream $stream, bool $reindex = false)
    {
        $this->reindex = $reindex;
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->reindex) {
            $this->collected[] = $signal->item->value;
        } else {
            $this->collected[$signal->item->key] = $signal->item->value;
        }
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->collected);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        if ($this->reindex) {
            foreach ($producer->feed($item) as $_) {
                $this->collected[] = $item->value;
            }
        } else {
            foreach ($producer->feed($item) as $_) {
                $this->collected[$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $this->collected = $reindexed || !$this->reindex ? $data : \array_values($data);
        
        if (!empty($data)) {
            $last = \array_key_last($data);
            $signal->item->key = $last;
            $signal->item->value = $data[$last];
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if ($this->reindex) {
            foreach ($items as $item) {
                $this->collected[] = $item->value;
            }
        } else {
            foreach ($items as $item) {
                $this->collected[$item->key] = $item->value;
            }
        }
        
        if (isset($item)) {
            $signal->item->key = $item->key;
            $signal->item->value = $item->value;
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collected = [];
            
            parent::destroy();
        }
    }
}