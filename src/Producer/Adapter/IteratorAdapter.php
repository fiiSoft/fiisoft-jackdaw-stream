<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class IteratorAdapter implements Producer
{
    private \Iterator $iterator;
    
    public function __construct(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->iterator as $item->key => $item->value) {
            yield;
        }
    }
}