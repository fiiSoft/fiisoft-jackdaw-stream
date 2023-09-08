<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Collection;

final class StreamCollection81 extends BaseStreamCollection
{
    public function key(): mixed
    {
        return \current($this->keys);
    }
}