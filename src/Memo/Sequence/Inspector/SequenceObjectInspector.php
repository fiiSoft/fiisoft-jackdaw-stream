<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Sequence\Inspector;

use FiiSoft\Jackdaw\Memo\Sequence\BaseSequencePredicate;
use FiiSoft\Jackdaw\Memo\SequenceInspector;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

final class SequenceObjectInspector extends BaseSequencePredicate
{
    private SequenceInspector $inspector;
    
    public function __construct(SequenceMemo $sequence, SequenceInspector $inspector)
    {
        parent::__construct($sequence);
        
        $this->inspector = $inspector;
    }
    
    public function evaluate(): bool
    {
        return $this->inspector->inspect($this->sequence);
    }
    
    public function equals(SequencePredicate $other): bool
    {
        return $other === $this || $other instanceof self
            && $other->inspector->equals($this->inspector)
            && parent::equals($other);
    }
}