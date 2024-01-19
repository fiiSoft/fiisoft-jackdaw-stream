<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Gather;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Gather;

final class GatherReindexKeys extends Gather
{
    public function handle(Signal $signal): void
    {
        $this->data[] = $signal->item->value;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $this->data[] = $value;
        }
        
        if (empty($this->data)) {
            return [];
        }
        
        yield 0 => $this->data;
        
        $this->data = [];
    }
}