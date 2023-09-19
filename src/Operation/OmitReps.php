<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer\ComparerFactory;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class OmitReps extends BaseOperation
{
    private Comparison $comparison;
    private Comparer $comparer;
    
    private ?Item $previous = null;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function __construct($comparison = null)
    {
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = $signal->item->copy();
            $this->comparer = ComparerFactory::createComparer($this->comparison);
            
            $this->next->handle($signal);
        } elseif ($this->comparer->areDifferent($this->previous, $signal->item)) {
            $this->previous->key = $signal->item->key;
            $this->previous->value = $signal->item->value;
            
            $this->next->handle($signal);
        }
    }
}