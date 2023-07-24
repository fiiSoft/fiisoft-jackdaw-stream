<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Tech;

use FiiSoft\Jackdaw\Internal\Item;

abstract class NonCountableProducer extends BaseProducer
{
    final public function isEmpty(): bool
    {
        return false;
    }
    
    final public function isCountable(): bool
    {
        return false;
    }
    
    final public function count(): int
    {
        throw new \BadMethodCallException('NonCountableProducer cannot count how many elements can produce!');
    }
    
    final public function getLast(): ?Item
    {
        return null;
    }
}