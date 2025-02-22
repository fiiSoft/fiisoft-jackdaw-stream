<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\TupleReader;

use FiiSoft\Jackdaw\Memo\Adapter\TupleItem;
use FiiSoft\Jackdaw\Memo\MemoReader;

abstract class BaseTupleReader implements MemoReader
{
    protected TupleItem $tuple;
    
    public function __construct(TupleItem $tuple)
    {
        $this->tuple = $tuple;
    }
    
    final public function equals(MemoReader $other): bool
    {
        return $other instanceof $this && $other->tuple->equals($this->tuple);
    }
}