<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;

use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\UniquenessChecker;

abstract class SingleChecker implements UniquenessChecker
{
    protected ComparisonStrategy $strategy;
    
    final public function __construct(ComparisonStrategy $strategy)
    {
        $this->strategy = $strategy;
    }
    
    final public function destroy(): void
    {
        $this->strategy->destroy();
    }
}