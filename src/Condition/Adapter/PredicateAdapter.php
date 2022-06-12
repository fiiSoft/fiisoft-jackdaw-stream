<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Condition
{
    private Predicate $predicate;
    private int $mode;
    
    public function __construct(Predicate $predicate, int $mode = Check::VALUE)
    {
        $this->predicate = $predicate;
        $this->mode = Check::getMode($mode);
    }
    
    public function isTrueFor($value, $key): bool
    {
        return $this->predicate->isSatisfiedBy($value, $key, $this->mode);
    }
}