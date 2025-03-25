<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
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
    
    /** @var ComparatorReady|callable|null */
    private $comparison;
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function __construct($comparison = null)
    {
        $this->comparison = $comparison;
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->comparer = ComparerFactory::createComparer(Comparison::prepare($this->comparison));
        $this->comparison = null;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = clone $signal->item;
            
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
                $this->previous = clone $item;
                
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