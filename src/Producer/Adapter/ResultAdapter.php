<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Producer\Producer;

final class ResultAdapter implements Producer
{
    private Result $result;
    
    public function __construct(Result $result)
    {
        $this->result = $result;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->result->toArrayAssoc() as $item->key => $item->value) {
            yield;
        }
    }
}