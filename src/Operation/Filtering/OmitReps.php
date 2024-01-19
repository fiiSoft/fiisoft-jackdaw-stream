<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparer\ComparerFactory;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class OmitReps extends BaseOperation
{
    private Comparer $comparer;
    
    private ?Item $previous = null;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function __construct($comparison = null)
    {
        $this->comparer = ComparerFactory::createComparer(Comparison::prepare($comparison));
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = $signal->item->copy();
            
            $this->next->handle($signal);
        } elseif ($this->comparer->areDifferent($this->previous, $signal->item)) {
            $this->previous->key = $signal->item->key;
            $this->previous->value = $signal->item->value;
            
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->previous === null) {
                $this->previous = $item->copy();
                
                yield $item->key => $item->value;
            }
            elseif ($this->comparer->areDifferent($this->previous, $item)) {
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
                
                yield $item->key => $item->value;
            }
        }
    }
}