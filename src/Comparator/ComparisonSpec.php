<?php

namespace FiiSoft\Jackdaw\Comparator;

interface ComparisonSpec extends Comparable
{
    public function mode(): int;
}