<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Generic;

use FiiSoft\Jackdaw\Consumer\GenericConsumer;

final class TwoArgs extends GenericConsumer
{
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        ($this->callable)($value, $key);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            ($this->callable)($value, $key);
            
            yield $key => $value;
        }
    }
}