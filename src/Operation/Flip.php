<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Flip extends BaseOperation
{
    /** @var mixed|null */
    private $key;
    
    public function handle(Signal $signal): void
    {
        $this->key = $signal->item->key;
        $signal->item->key = $signal->item->value;
        $signal->item->value = $this->key;
    
        $this->next->handle($signal);
    }
}