<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\GroupBy;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Terminating\GroupBy;

final class GroupByReindexKeys extends GroupBy
{
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->collections[$this->discriminator->classify($value, $key)][] = $value;
        }
        
        yield;
    }
    
    protected function collect(Item $item): void
    {
        $this->collections[$this->discriminator->classify($item->value, $item->key)][] = $item->value;
    }
}