<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Reverse extends BaseOperation
{
    /** @var Item[] */
    private $items = [];
    
    public function handle(Signal $signal)
    {
        $this->items[] = $signal->item->copy();
    }
    
    public function streamingFinished(Signal $signal)
    {
        $items = \array_reverse($this->items);
        $this->items = [];
        
        $signal->restartFrom($this->next, $items);
    }
}