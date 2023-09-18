<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Extrema extends BaseOperation
{
    private const FLAT = 0, UP = 1, DOWN = 2;
    
    private ItemComparator $comparator;
    private Comparison $comparison;
    private ?Item $previous = null;
    private Item $item;
    
    private int $direction;
    private bool $allowLimits, $isFirst = true;
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function __construct(bool $allowLimits = true, $comparison = null)
    {
        $this->allowLimits = $allowLimits;
        $this->direction = $allowLimits ? self::UP : self::FLAT;
        $this->comparison = Comparison::prepare($comparison);
        
        $this->item = new Item();
    }
    
    public function handle(Signal $signal): void
    {
        $this->item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = $this->item->copy();
            $this->comparator = ItemComparatorFactory::getForComparison($this->comparison);
        } else {
            $compare = $this->comparator->compare($this->previous, $this->item);
            if ($compare === 0) {
                $this->direction = self::FLAT;
                $this->isFirst = false;
            } elseif ($this->isFirst) {
                if ($this->allowLimits) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = $compare < 0 ? self::UP : self::DOWN;
                $this->isFirst = false;
            } elseif ($compare < 0) {
                if ($this->direction === self::DOWN) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = self::UP;
            } else {
                if ($this->direction === self::UP) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = self::DOWN;
            }
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && $this->allowLimits && $this->direction !== self::FLAT && $this->previous !== null) {
            
            $signal->resume();
            $this->next->handle($signal);
            
            $this->direction = self::FLAT;
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
}