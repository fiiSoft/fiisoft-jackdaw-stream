<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

interface FilterAdjuster
{
    /**
     * Allows to apply various changes on the filter.
     * It should return the same filter when unmodified, and must return a new filter when modified.
     */
    public function adjust(Filter $filter): Filter;
}