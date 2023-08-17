<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class OmitReps extends BaseOperation
{
    private ItemComparator $comparator;
    
    private ?Item $previous = null;
    private int $mode;
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE)
    {
        $this->comparator = ItemComparatorFactory::getFor($mode, false, $comparator);
        $this->mode = $mode;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = $signal->item->copy();
            $this->next->handle($signal);
        } else {
            switch ($this->mode) {
                case Check::VALUE:
                    $isDifferent = \gettype($signal->item->value) !== \gettype($this->previous->value);
                break;
                case Check::KEY:
                    $isDifferent = \gettype($signal->item->key) !== \gettype($this->previous->key);
                break;
                default:
                    $isDifferent = \gettype($signal->item->value) !== \gettype($this->previous->value)
                        || \gettype($signal->item->key) !== \gettype($this->previous->key);
            }
            
            if ($isDifferent || $this->comparator->compare($this->previous, $signal->item) !== 0) {
                $this->previous->key = $signal->item->key;
                $this->previous->value = $signal->item->value;
                $this->next->handle($signal);
            }
        }
    }
}