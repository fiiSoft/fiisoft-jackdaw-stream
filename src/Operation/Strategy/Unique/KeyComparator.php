<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Item;

final class KeyComparator implements Strategy
{
    private Comparator $comparator;
    private array $keys = [];
    
    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }
    
    public function check(Item $item): bool
    {
        foreach ($this->keys as $key) {
            if ($this->comparator->compare($key, $item->key) === 0) {
                return false;
            }
        }
        
        $this->keys[] = $item->key;
        
        return true;
    }
}