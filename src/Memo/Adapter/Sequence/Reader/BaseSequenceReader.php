<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\SequenceEntries;
use FiiSoft\Jackdaw\Memo\MemoReader;

abstract class BaseSequenceReader implements MemoReader
{
    protected SequenceEntries $sequence;
    protected int $index;
    
    public function __construct(SequenceEntries $sequence, int $index)
    {
        $this->sequence = $sequence;
        $this->index = $index;
    }
}