<?php

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

abstract class StandardChecker implements UniquenessChecker
{
    protected ComparisonStrategy $strategy;
    
    public function __construct(ComparisonStrategy $strategy)
    {
        $this->strategy = $strategy;
    }
    
    final public function destroy(): void
    {
        $this->strategy->destroy();
    }
}