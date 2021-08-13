<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Item;

final class ValueAndKeyComparator implements Strategy
{
    /** @var Comparator */
    private $comparator;
    
    /** @var Item[] */
    private $keysAndValues = [];
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    public function check(Item $item): bool
    {
        foreach ($this->keysAndValues as $prev) {
            if ($this->comparator->compareAssoc($prev->value, $item->value, $prev->key, $item->key) === 0) {
                return false;
            }
        }
    
        $this->keysAndValues[] = $item->copy();
        return true;
    }
}