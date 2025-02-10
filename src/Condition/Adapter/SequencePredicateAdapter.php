<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

final class SequencePredicateAdapter implements Condition
{
    private SequencePredicate $predicate;
    
    public function __construct(SequencePredicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    /**
     * @inheritDoc
     */
    public function isTrueFor($value, $key): bool
    {
        return $this->predicate->evaluate();
    }
}