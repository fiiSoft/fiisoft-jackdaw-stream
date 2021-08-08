<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Adapter;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Filter
{
    /** @var Predicate */
    private $predicate;
    
    public function __construct(Predicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        return $this->predicate->isSatisfiedBy($value, $key, $mode);
    }
}