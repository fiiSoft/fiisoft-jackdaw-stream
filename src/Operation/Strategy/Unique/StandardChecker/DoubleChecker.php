<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;

use FiiSoft\Jackdaw\Operation\Strategy\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;

abstract class DoubleChecker extends StandardChecker
{
    protected ComparisonStrategy $keyStrategy;
    
    public function __construct(ComparisonStrategy $strategy)
    {
        parent::__construct($strategy);
        
        $this->keyStrategy = clone $strategy;
    }
}