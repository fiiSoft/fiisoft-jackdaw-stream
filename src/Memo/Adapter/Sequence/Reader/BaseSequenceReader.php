<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

abstract class BaseSequenceReader implements MemoReader
{
    protected SequenceMemo $sequence;
    protected int $index;
    
    public function __construct(SequenceMemo $sequence, int $index)
    {
        $this->sequence = $sequence;
        $this->index = $index;
    }
    
    final public function equals(MemoReader $other): bool
    {
        return $other instanceof $this
            && $other->index === $this->index
            && $other->sequence === $this->sequence;
    }
}