<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Collection;

final class StreamCollection extends BaseStreamCollection
{
    /**
     * @return string|int|false
     */
    public function key()
    {
        return \current($this->keys);
    }
}