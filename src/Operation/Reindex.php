<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Reindex extends BaseOperation
{
    /** @var int */
    private $index = 0;
    
    public function handle(Signal $signal)
    {
        $signal->item->key = $this->index++;
    
        $this->next->handle($signal);
    }
}