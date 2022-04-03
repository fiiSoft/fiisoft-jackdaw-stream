<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Internal\ReverseItemsIterator;

final class Reverse extends BaseOperation
{
    /** @var Item[] */
    private array $items = [];
    
    public function handle(Signal $signal): void
    {
        $this->items[] = $signal->item->copy();
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (empty($this->items)) {
            return $this->next->streamingFinished($signal);
        }
        
        $signal->restartWith(new ReverseItemsIterator($this->items), $this->next);
        $this->items = [];
        
        return true;
    }
}