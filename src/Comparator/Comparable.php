<?php

namespace FiiSoft\Jackdaw\Comparator;

interface Comparable extends ComparatorReady
{
    public function comparator(): ?Comparator;
}