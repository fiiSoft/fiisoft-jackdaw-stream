<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

final class StreamIterator extends BaseStreamIterator
{
    public function current()
    {
        return $this->item->value;
    }
    
    public function key()
    {
        return $this->item->key;
    }
}