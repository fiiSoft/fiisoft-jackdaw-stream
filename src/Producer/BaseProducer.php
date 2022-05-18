<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer;

use FiiSoft\Jackdaw\Stream;

abstract class BaseProducer implements Producer
{
    final public function stream(): Stream
    {
        return Stream::from($this);
    }
}