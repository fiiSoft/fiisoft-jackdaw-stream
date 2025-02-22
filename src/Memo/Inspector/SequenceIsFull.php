<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Inspector;

use FiiSoft\Jackdaw\Memo\SequenceInspector;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

final class SequenceIsFull implements SequenceInspector
{
    public function inspect(SequenceMemo $sequence): bool
    {
        return $sequence->isFull();
    }
    
    public function equals(SequenceInspector $other): bool
    {
        return $other instanceof self;
    }
}