<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Sequence;

use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

abstract class BaseSequencePredicate implements SequencePredicate
{
    protected SequenceMemo $sequence;
    
    public function __construct(SequenceMemo $sequence)
    {
        $this->sequence = $sequence;
    }
}