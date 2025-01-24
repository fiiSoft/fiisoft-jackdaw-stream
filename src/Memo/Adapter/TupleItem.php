<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter;

use FiiSoft\Jackdaw\Memo\MemoReader;

final class TupleItem implements MemoReader
{
    /** @var mixed */
    public $value = null;
    
    /** @var mixed */
    public $key = null;
    
    /**
     * @return array<mixed> tuple (key, value)
     */
    public function read(): array
    {
        return [$this->key, $this->value];
    }
}