<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\SortLimited;

use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Producer\Producers;

final class SingleSortLimited extends SortLimited
{
    private ItemComparator $comparator;
    private ?Item $best = null;
    
    protected function __construct(Sorting $sorting)
    {
        parent::__construct($sorting);
        
        $this->comparator = ItemComparatorFactory::getForSorting($sorting);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->best === null) {
            $this->best = clone $signal->item;
        } elseif ($this->comparator->compare($signal->item, $this->best) < 0) {
            $this->best->key = $signal->item->key;
            $this->best->value = $signal->item->value;
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->best === null) {
                $this->best = clone $item;
            } elseif ($this->comparator->compare($item, $this->best) < 0) {
                $this->best->key = $item->key;
                $this->best->value = $item->value;
            }
        }
        
        if ($this->best === null) {
            return [];
        }
        
        yield $this->best->key => $this->best->value;
    }
    
    protected function createProducer(): Producer
    {
        return Producers::getAdapter([$this->best->key => $this->best->value]);
    }
    
    protected function isEmpty(): bool
    {
        return $this->best === null;
    }
    
    public function applyLimit(int $limit): bool
    {
        return $limit === 1;
    }
    
    public function limit(): int
    {
        return 1;
    }
    
    protected function __clone()
    {
        parent::__clone();
        
        $this->best = null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            parent::destroy();
            
            $this->best = null;
        }
    }
}