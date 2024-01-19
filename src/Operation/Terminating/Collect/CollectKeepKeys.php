<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\Collect;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;

final class CollectKeepKeys extends Collect
{
    public function handle(Signal $signal): void
    {
        $this->collected[$signal->item->key] = $signal->item->value;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->collected[$key] = $value;
        }
        
        yield;
    }
}