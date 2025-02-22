<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic;

use FiiSoft\Jackdaw\Filter\Filter;

interface CompoundFilter
{
    /**
     * @return Filter[]
     */
    public function getFilters(): array;
}