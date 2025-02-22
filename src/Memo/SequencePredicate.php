<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Filter\FilterReady;

interface SequencePredicate extends FilterReady
{
    public function evaluate(): bool;
    
    public function equals(SequencePredicate $other): bool;
}