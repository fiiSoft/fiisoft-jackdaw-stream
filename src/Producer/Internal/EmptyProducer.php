<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Internal;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class EmptyProducer extends BaseProducer
{
    /**
     * @inheritDoc
     */
    public function getIterator(): \Iterator
    {
        yield from [];
    }
}