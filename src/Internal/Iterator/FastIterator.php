<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

final class FastIterator extends BaseFastIterator
{
    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->iterator->current();
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->iterator->key();
    }
}