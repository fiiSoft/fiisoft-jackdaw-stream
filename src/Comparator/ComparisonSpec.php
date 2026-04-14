<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

interface ComparisonSpec extends ComparatorReady
{
    public function mode(): int;
    
    public function comparator(): ?Comparator;
}