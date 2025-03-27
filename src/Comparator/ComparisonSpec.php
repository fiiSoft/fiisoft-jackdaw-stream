<?php

namespace FiiSoft\Jackdaw\Comparator;

interface ComparisonSpec extends ComparatorReady
{
    public function mode(): int;
    
    public function comparator(): ?Comparator;
}