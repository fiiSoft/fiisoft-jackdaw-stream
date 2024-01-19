<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;

final class FilterAdapter implements Discriminator
{
    private Filter $filter;
    
    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null): bool
    {
        return $this->filter->isAllowed($value, $key);
    }
}