<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Maxima extends BaseOperation
{
    private const FLAT = 0, UP = 1, DOWN = 2;
    
    private ItemComparator $comparator;
    private Comparison $comparison;
    private ?Item $previous = null;
    
    private int $state;
    private bool $allowLimits, $reversed;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function __construct(bool $allowLimits = true, bool $reversed = false, $comparison = null)
    {
        $this->reversed = $reversed;
        $this->allowLimits = $allowLimits;
        $this->state = $allowLimits ? self::UP : self::FLAT;
        
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = $item->copy();
            $this->comparator = ItemComparatorFactory::getForComparison($this->comparison, $this->reversed);
        } else {
            $compare = $this->comparator->compare($this->previous, $item);
            if ($compare < 0) {
                $this->state = self::UP;
                
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
            } elseif ($compare === 0) {
                $this->state = self::FLAT;
            } else {
                if ($this->state === self::UP) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $item;
                }
                
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
                
                $this->state = self::DOWN;
            }
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && $this->allowLimits && $this->state === self::UP && $this->previous !== null) {
            
            $signal->resume();
            $this->next->handle($signal);
            
            $this->state = self::FLAT;
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
}