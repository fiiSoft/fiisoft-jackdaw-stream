<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Increasing extends BaseOperation
{
    private ItemComparator $comparator;
    
    private ?Item $previous = null;
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE, bool $reversed = false)
    {
        $this->comparator = ItemComparatorFactory::getFor($mode, $reversed, $comparator);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->previous === null) {
            $this->previous = $signal->item->copy();
            $this->next->handle($signal);
        } elseif ($this->comparator->compare($this->previous, $signal->item) <= 0) {
            $this->previous->key = $signal->item->key;
            $this->previous->value = $signal->item->value;
            $this->next->handle($signal);
        }
    }
}