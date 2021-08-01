<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Condition
{
    /** @var Predicate */
    private $predicate;
    
    public function __construct(Predicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->predicate->isSatisfiedBy($value, $key);
    }
}