<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterAdapter implements Discriminator
{
    /** @var Filter */
    private $filter;
    
    /** @var int */
    private $mode;
    
    public function __construct(Filter $filter, int $mode = Check::VALUE)
    {
        $this->filter = $filter;
        $this->mode = $mode;
    }
    
    public function classify($value, $key)
    {
        return $this->filter->isAllowed($value, $key, $this->mode);
    }
}