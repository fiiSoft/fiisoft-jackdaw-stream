<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Condition
{
    private Predicate $predicate;
    
    public function __construct(Predicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->predicate->isSatisfiedBy($value, $key);
    }
}