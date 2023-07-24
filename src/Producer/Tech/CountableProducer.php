<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Tech;

abstract class CountableProducer extends BaseProducer
{
    final public function isCountable(): bool
    {
        return true;
    }
    
    final public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}