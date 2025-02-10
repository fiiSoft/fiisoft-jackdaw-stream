<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Inspector;

use FiiSoft\Jackdaw\Memo\SequenceInspector;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

final class SequenceIsEmpty implements SequenceInspector
{
    public function inspect(SequenceMemo $sequence): bool
    {
        return $sequence->isEmpty();
    }
}