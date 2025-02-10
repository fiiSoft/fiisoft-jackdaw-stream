<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Adapter;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

final class SequencePredicateAdapter extends BaseFilter
{
    private SequencePredicate $predicate;
    
    public function __construct(SequencePredicate $predicate, ?int $mode = null)
    {
        parent::__construct($mode);
        
        $this->predicate = $predicate;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->predicate->evaluate();
    }
    
    public function inMode(?int $mode): Filter
    {
        return $this;
    }
    
    public function negate(): Filter
    {
        return $this->createDefaultNOT();
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->predicate->evaluate()) {
                yield $key => $value;
            }
        }
    }
}