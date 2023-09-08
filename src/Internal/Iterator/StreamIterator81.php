<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Iterator;

final class StreamIterator81 extends BaseStreamIterator
{
    public function current(): mixed
    {
        return $this->item->value;
    }
    
    public function key(): mixed
    {
        return $this->item->key;
    }
}