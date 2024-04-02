<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Item;

final class PairChecker extends QuickChecker
{
    private Comparator $keyComparator, $valueComparator;
    
    /**
     * @param ComparatorReady|callable|null $valueComparator
     * @param ComparatorReady|callable|null $keyComparator
     */
    public function __construct($valueComparator, $keyComparator)
    {
        $this->valueComparator = Comparators::prepare($valueComparator);
        $this->keyComparator = Comparators::prepare($keyComparator);
    }
    
    protected function compare(Item $first, Item $second): int
    {
        return $this->valueComparator->compare($first->value, $second->value)
            ?: $this->keyComparator->compare($first->key, $second->key);
    }
}