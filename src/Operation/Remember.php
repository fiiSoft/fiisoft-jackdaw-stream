<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Registry\RegWriter;

final class Remember extends BaseOperation
{
    private RegWriter $registry;
    
    public function __construct(RegWriter $registry)
    {
        $this->registry = $registry;
    }
    
    public function handle(Signal $signal): void
    {
        $this->registry->write($signal->item->value, $signal->item->key);
        
        $this->next->handle($signal);
    }
}