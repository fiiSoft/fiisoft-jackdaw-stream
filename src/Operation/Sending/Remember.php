<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

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
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->registry->write($value, $key);
            
            yield $key => $value;
        }
    }
}