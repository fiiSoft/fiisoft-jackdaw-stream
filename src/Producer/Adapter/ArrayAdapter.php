<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class ArrayAdapter implements Producer
{
    /** @var array */
    private $source;
    
    public function __construct(array $source)
    {
        $this->source = $source;
    }
    
    public function feed(Item $item): \Generator
    {
        foreach ($this->source as $item->key => $item->value) {
            yield;
        }
    }
}