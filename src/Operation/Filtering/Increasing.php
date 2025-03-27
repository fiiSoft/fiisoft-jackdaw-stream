<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Increasing extends BaseOperation
{
    private ItemComparator $comparator;
    private ?Item $previous = null;
    
    /** @var ComparatorReady|callable|null */
    private $comparison;
    
    private bool $reversed;
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function __construct(bool $reversed = false, $comparison = null)
    {
        $this->reversed = $reversed;
        $this->comparison = $comparison;
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->comparator = ItemComparatorFactory::getForComparison(
            Comparison::prepare($this->comparison), $this->reversed
        );
        
        $this->comparison = null;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = clone $signal->item;
            
            $this->next->handle($signal);
        }
        elseif ($this->comparator->compare($this->previous, $signal->item) <= 0) {
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
            elseif ($this->comparator->compare($this->previous, $item) <= 0) {
                $this->previous->key = $item->key;
                $this->previous->value = $item->value;
                
                yield $item->key => $item->value;
            }
        }
    }
}