<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Flip extends BaseOperation
{
    public function handle(Signal $signal)
    {
        $item = $signal->item;
        
        $key = $item->key;
        $item->key = $item->value;
        $item->value = $key;
    
        $this->next->handle($signal);
    }
}