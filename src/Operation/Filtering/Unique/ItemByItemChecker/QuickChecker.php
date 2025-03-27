<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\UniquenessChecker;

abstract class QuickChecker implements UniquenessChecker
{
    /** @var Item[] */
    private array $unique = [];
    
    private int $count = 0;
    
    /**
     * @inheritDoc
     */
    public function check(Item $item): bool
    {
        $last = $left = 0;
        $right = $this->count - 1;
        
        while ($left <= $right) {
            $index = (int) (($left + $right) / 2);
            
            $compare = $this->compare($item, $this->unique[$index]);
            
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
            
            $this->unique[$last] = clone $item;
        } else {
            $this->unique[] = clone $item;
        }
        
        ++$this->count;
        
        return true;
    }
    
    abstract protected function compare(Item $first, Item $second): int;
    
    public function destroy(): void
    {
        $this->unique = [];
        $this->count = 0;
    }
}