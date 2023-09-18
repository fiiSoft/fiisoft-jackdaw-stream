<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseComparator implements Comparator
{
    final public function comparator(): Comparator
    {
        return $this;
    }
    
    final public function mode(): int
    {
        return Check::VALUE;
    }
}