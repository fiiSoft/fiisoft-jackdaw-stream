<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Tech;

use FiiSoft\Jackdaw\Producer\Producer;
use FiiSoft\Jackdaw\Stream;

abstract class BaseProducer implements Producer
{
    protected bool $isDestroying = false;
    
    final public function stream(): Stream
    {
        return Stream::from($this);
    }
    
    public function destroy(): void
    {
        $this->isDestroying = true;
    }
}