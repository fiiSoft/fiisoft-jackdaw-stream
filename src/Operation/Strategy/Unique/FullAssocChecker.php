<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Internal\Item;

final class FullAssocChecker implements UniquenessChecker
{
    /** @var callable */
    private $comparator;
    
    /** @var Item[] */
    private array $unique = [];
    
    private int $count = 0;
    
    public function __construct(GenericComparator $comparator)
    {
        if (!$comparator->isFullAssoc()) {
            throw new \InvalidArgumentException('FullAssocChecker can work only with four-argument callable');
        }
        
        $this->comparator = $comparator->getWrappedCallable();
    }
    
    public function check(Item $item): bool
    {
        $comparator = $this->comparator;
        
        $last = $left = 0;
        $right = $this->count - 1;
        
        while ($left <= $right) {
            $index = (int) \floor(($left + $right) / 2);
            
            $other = $this->unique[$index];
            $compare = $comparator($item->value, $other->value, $item->key, $other->key);
            
            if ($compare > 0) {
                $left = $index + 1;
                $last = $left;
            } elseif ($compare < 0) {
                $right = $index - 1;
            } else {
                return false;
            }
        }
        
        if ($last < $this->count) {
            for ($i = $this->count; $i > $last; --$i) {
                $this->unique[$i] = $this->unique[$i - 1];
            }
            
            $this->unique[$last] = $item->copy();
        } else {
            $this->unique[] = $item->copy();
        }
        
        ++$this->count;
        
        return true;
    }
    
    public function destroy(): void
    {
        $this->unique = [];
        $this->count = 0;
    }
}