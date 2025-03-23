<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

interface FilterWrapper
{
    public function wrappedFilter(): Filter;
}