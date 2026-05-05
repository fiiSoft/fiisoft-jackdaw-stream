<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\DataAware;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ForwardItemsIterator;
use FiiSoft\Jackdaw\Producer\Producers;

/**
 * Ugly, but useful.
 */
abstract class BaseSort extends BaseOperation
{
    /**
     * @param Item[] $items REFERENCE
     */
    final protected function restartWithSortedItems(Signal $signal, Sorting $sorting, array &$items): void
    {
        $this->sortItems($sorting, $items);
        
        if ($this->next instanceof DataAware) {
            $this->next->setCollectedItems($items);
            $producer = Producers::getAdapter([]);
        } else {
            $producer = new ForwardItemsIterator($items);
        }
        
        $signal->restartWith($producer, $this->next);
    }
    
    /**
     * @param Item[] $items REFERENCE
     */
    final protected function sortedItems(Sorting $sorting, array &$items): \Generator
    {
        $this->sortItems($sorting, $items);
        
        if ($this->next instanceof DataAware) {
            $this->next->setCollectedItems($items);
        } else {
            foreach ($items as $item) {
                yield $item->key => $item->value;
            }
        }
    }
    
    /**
     * @param Item[] $items REFERENCE
     */
    private function sortItems(Sorting $sorting, array &$items): void
    {
        if (\count($items) < 2) {
            return;
        }
        
        $comparator = ItemComparatorFactory::getForSorting($sorting);
        
        \usort($items, [$comparator, 'compare']);
    }
}