<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\StreamApi;
use FiiSoft\Jackdaw\Producer\BaseProducer;

final class StreamAdapter extends BaseProducer
{
    private StreamApi $stream;
    
    public function __construct(StreamApi $stream)
    {
        $this->stream = $stream;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->stream as $item->key => $item->value) {
            yield;
        }
    }
}