<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Increasing extends BaseOperation
{
    private ItemComparator $comparator;
    private Comparison $comparison;
    
    private ?Item $previous = null;
    private bool $reversed;
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
     */
    public function __construct(bool $reversed = false, $comparison = null)
    {
        $this->reversed = $reversed;
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = $signal->item->copy();
            $this->comparator = ItemComparatorFactory::getForComparison($this->comparison, $this->reversed);
            
            $this->next->handle($signal);
        } elseif ($this->comparator->compare($this->previous, $signal->item) <= 0) {
            $this->previous->key = $signal->item->key;
            $this->previous->value = $signal->item->value;
            
            $this->next->handle($signal);
        }
    }
}