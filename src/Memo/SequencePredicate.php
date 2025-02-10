<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Filter\FilterReady;

interface SequencePredicate extends FilterReady, ConditionReady
{
    public function evaluate(): bool;
}