<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique;

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