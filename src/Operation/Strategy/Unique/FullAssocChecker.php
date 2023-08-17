<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\GenericComparator;
use FiiSoft\Jackdaw\Internal\Item;

final class FullAssocChecker implements UniquenessChecker
{
    /** @var callable */
    private $comparator;
    
    /** @var Item[] */
    private array $unique = [];
    
    public function __construct(GenericComparator $comparator)
    {
        if (!$comparator->isFullAssoc()) {
            throw new \InvalidArgumentException('FullAssocChecker can work only with four-argument callable');
        }
        
        $this->comparator = $comparator->comparator();
    }
    
    public function check(Item $item): bool
    {
        $comparator = $this->comparator;
        
        foreach ($this->unique as $other) {
            if ($comparator($item->value, $other->value, $item->key, $other->key) === 0) {
                return false;
            }
        }
        
        $this->unique[] = $item->copy();
        
        return true;
    }
    
    public function destroy(): void
    {
        $this->unique = [];
    }
}