<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Gather extends BaseOperation implements DataCollector, Reindexable
{
    private array $data = [];
    private bool $reindex;
    
    public function __construct(bool $reindex = false)
    {
        $this->reindex = $reindex;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->reindex) {
            $this->data[] = $signal->item->value;
        } else {
            $this->data[$signal->item->key] = $signal->item->value;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->data)) {
            return parent::streamingFinished($signal);
        }
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->acceptSimpleData([$this->data], $signal, true);
        }
        
        $signal->restartWith(Producers::fromArray([$this->data]), $this->next);
        
        return true;
    }
    
    public function isReindexed(): bool
    {
        return $this->reindex;
    }
    
    public function reindex(): void
    {
        $this->reindex = true;
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        if ($this->reindex) {
            foreach ($producer->feed($item) as $_) {
                $this->data[] = $item->value;
            }
        } else {
            foreach ($producer->feed($item) as $_) {
                $this->data[$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }

    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        if (!empty($data)) {
            $last = \array_key_last($data);
            $signal->item->key = $last;
            $signal->item->value = $data[$last];
        }
        
        $this->data = $reindexed || !$this->reindex ? $data : \array_values($data);
        
        return $this->streamingFinished($signal);
    }

    /**
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        if (!empty($items)) {
            $last = $items[\array_key_last($items)];
            $signal->item->key = $last->key;
            $signal->item->value = $last->value;
        }
        
        if ($this->reindex) {
            foreach ($items as $item) {
                $this->data[] = $item->value;
            }
        } else {
            foreach ($items as $item) {
                $this->data[$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->data = [];
            
            parent::destroy();
        }
    }
}