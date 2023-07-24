<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class ResultAdapter extends NonCountableProducer
{
    private ResultApi $result;
    
    public function __construct(ResultApi $result)
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