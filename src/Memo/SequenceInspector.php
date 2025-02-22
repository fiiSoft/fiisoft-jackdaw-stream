<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

interface SequenceInspector
{
    public function inspect(SequenceMemo $sequence): bool;
    
    public function equals(SequenceInspector $other): bool;
}