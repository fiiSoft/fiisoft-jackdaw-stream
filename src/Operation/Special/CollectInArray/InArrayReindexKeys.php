<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special\CollectInArray;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Special\CollectInArray;

final class InArrayReindexKeys extends CollectInArray
{
    public function handle(Signal $signal): void
    {
        $this->result[] = $signal->item->value;
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            $this->result[] = $value;
        }
        
        yield;
    }
}