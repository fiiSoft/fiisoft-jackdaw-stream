<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Sequence\Inspector;

use FiiSoft\Jackdaw\Memo\Sequence\BaseSequencePredicate;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

final class SequenceCallableInspector extends BaseSequencePredicate
{
    /** @var callable */
    private $inspector;
    
    public function __construct(SequenceMemo $sequence, callable $inspector)
    {
        parent::__construct($sequence);
        
        $this->inspector = $inspector;
    }
    
    public function evaluate(): bool
    {
        return ($this->inspector)($this->sequence);
    }
}