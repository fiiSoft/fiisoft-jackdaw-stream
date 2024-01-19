<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

final class FastIterator81 extends BaseFastIterator
{
    /**
     * @inheritDoc
     */
    public function current(): mixed
    {
        return $this->iterator->current();
    }
    
    /**
     * @inheritDoc
     */
    public function key(): mixed
    {
        return $this->iterator->key();
    }
}