<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

abstract class CompoundTimeComp extends TimeComparator
{
    abstract public function optimise(): TimeComparator;
}