<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Gather;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Collecting\Gather;

final class GatherKeepKeys extends Gather
{
    public function handle(Signal $signal): void
    {
        $this->data[$signal->item->key] = $signal->item->value;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->data[$key] = $value;
        }
        
        if (empty($this->data)) {
            return [];
        }
        
        yield 0 => $this->data;
        
        $this->data = [];
    }
}